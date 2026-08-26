<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Middleware;

use Agentur\SmartJobFinder\Domain\JobLanguageOverlay;
use Agentur\SmartJobFinder\Http\ApiCors;
use Agentur\SmartJobFinder\Http\ApiRateLimiter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Public JSON feed at /api/jobs. Tagged cache (60s), enable-fields via QueryBuilder
 * restrictions, hard limit — this middleware runs on every FE request so the
 * path check stays cheap and the query never unbounded.
 */
final class JobApiMiddleware implements MiddlewareInterface
{
    private const LIMIT = 100;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly CacheManager $cacheManager,
        private readonly ApiRateLimiter $rateLimiter,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = rtrim($request->getUri()->getPath(), '/');
        if (!str_ends_with($path, '/api/jobs')) {
            return $handler->handle($request);
        }

        $config = [];
        try {
            $config = $this->extensionConfiguration->get('smart_job_finder') ?? [];
        } catch (\Throwable) {
        }

        $storagePid = (int)($config['apiStoragePid'] ?? 0);
        $languageId = $this->languageId($request);
        $headers = array_merge(
            [
                'Cache-Control' => 'public, max-age=60',
                'X-Smart-Job-Finder' => 'api',
            ],
            ApiCors::headers((string)($config['apiCorsOrigin'] ?? ''), $request->getHeaderLine('Origin')),
        );

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return new JsonResponse([], 204, $headers);
        }

        $rateLimit = (int)($config['apiRateLimit'] ?? 60);
        if ($rateLimit > 0) {
            if ($this->rateLimiter->isLimited($request, $rateLimit)) {
                $this->rateLimiter->hit($request);

                return new JsonResponse(
                    ['meta' => ['error' => 'rate limit exceeded'], 'data' => []],
                    429,
                    $headers,
                );
            }
            $this->rateLimiter->hit($request);
        }

        if ($storagePid <= 0) {
            return new JsonResponse(
                [
                    'meta' => [
                        'count' => 0,
                        'error' => 'apiStoragePid is not configured',
                    ],
                    'data' => [],
                ],
                403,
                $headers,
            );
        }

        $strict = $this->isStrictLanguage($request);
        $cacheIdentifier = 'api_' . md5($storagePid . '_' . $languageId . '_' . ($strict ? '1' : '0'));
        $cache = $this->cacheManager->hasCache('smart_job_finder')
            ? $this->cacheManager->getCache('smart_job_finder')
            : null;
        if ($cache !== null) {
            $cached = $cache->get($cacheIdentifier);
            if (is_array($cached)) {
                $headers['X-Smart-Job-Finder-Cache'] = 'hit';

                return new JsonResponse($cached, 200, $headers);
            }
        }

        $payload = $this->buildPayload($storagePid, $languageId, $strict);
        $cache?->set($cacheIdentifier, $payload, ['tx_smartjobfinder'], 60);
        $headers['X-Smart-Job-Finder-Cache'] = 'miss';

        return new JsonResponse($payload, 200, $headers);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(int $storagePid, int $languageId, bool $strict): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_smartjobfinder_domain_model_job');
        $languages = [-1, 0];
        if ($languageId > 0) {
            $languages[] = $languageId;
        }

        $queryBuilder
            ->select(
                'uid',
                'title',
                'slug',
                'teaser',
                'location',
                'employment_type',
                'workplace_type',
                'featured',
                'crdate',
                'sys_language_uid',
                'l10n_parent',
            )
            ->from('tx_smartjobfinder_domain_model_job')
            ->andWhere(
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    implode(',', $languages),
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('valid_through', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->gte('valid_through', $queryBuilder->createNamedParameter(time(), Connection::PARAM_INT)),
                ),
            )
            ->orderBy('featured', 'DESC')
            ->addOrderBy('crdate', 'DESC')
            ->setMaxResults(self::LIMIT * 3);

        if ($storagePid > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storagePid, Connection::PARAM_INT)),
            );
        }

        $jobs = [];
        foreach (JobLanguageOverlay::overlay($queryBuilder->executeQuery()->fetchAllAssociative(), $languageId, $strict) as $row) {
            if (count($jobs) >= self::LIMIT) {
                break;
            }
            $jobs[] = [
                'uid' => (int)$row['uid'],
                'title' => (string)$row['title'],
                'slug' => (string)$row['slug'],
                'teaser' => (string)$row['teaser'],
                'location' => (string)$row['location'],
                'employmentType' => (string)$row['employment_type'],
                'workplaceType' => (string)$row['workplace_type'],
                'featured' => (bool)$row['featured'],
                'datePosted' => gmdate('c', (int)$row['crdate']),
            ];
        }

        return [
            'meta' => [
                'count' => count($jobs),
                'limit' => self::LIMIT,
                'generatedAt' => gmdate('c'),
            ],
            'data' => $jobs,
        ];
    }

    private function languageId(ServerRequestInterface $request): int
    {
        $language = $request->getAttribute('language');
        if (is_object($language) && method_exists($language, 'getLanguageId')) {
            return (int)$language->getLanguageId();
        }

        return 0;
    }

    private function isStrictLanguage(ServerRequestInterface $request): bool
    {
        $language = $request->getAttribute('language');
        if (!is_object($language) || !method_exists($language, 'getFallbackType')) {
            return false;
        }

        return (string)$language->getFallbackType() === 'strict';
    }
}
