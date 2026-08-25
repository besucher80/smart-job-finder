<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Middleware;

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
        $cacheIdentifier = 'api_' . md5($storagePid . '_' . $languageId);
        $headers = [
            'Cache-Control' => 'public, max-age=60',
            'X-Smart-Job-Finder' => 'api',
        ];

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

        $payload = $this->buildPayload($storagePid, $languageId);
        $cache?->set($cacheIdentifier, $payload, ['tx_smartjobfinder'], 60);
        $headers['X-Smart-Job-Finder-Cache'] = 'miss';

        return new JsonResponse($payload, 200, $headers);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(int $storagePid, int $languageId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_smartjobfinder_domain_model_job');
        $queryBuilder
            ->select('uid', 'title', 'slug', 'teaser', 'location', 'employment_type', 'workplace_type', 'featured', 'crdate')
            ->from('tx_smartjobfinder_domain_model_job')
            ->andWhere(
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    implode(',', [-1, $languageId]),
                ),
            )
            ->orderBy('featured', 'DESC')
            ->addOrderBy('crdate', 'DESC')
            ->setMaxResults(self::LIMIT);

        if ($storagePid > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storagePid, Connection::PARAM_INT)),
            );
        }

        $jobs = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
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
}
