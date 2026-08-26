<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Http;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Cheap per-IP counter in the extension cache. Not a WAF — just a brake.
 */
final class ApiRateLimiter
{
    private const WINDOW = 60;

    public function __construct(
        private readonly CacheManager $cacheManager,
    ) {}

    public function isLimited(ServerRequestInterface $request, int $maxHits): bool
    {
        if ($maxHits <= 0) {
            return false;
        }

        return $this->hits($request) >= $maxHits;
    }

    public function hit(ServerRequestInterface $request): void
    {
        $key = $this->key($request);
        $this->cache()->set($key, $this->hits($request) + 1, [], self::WINDOW);
    }

    private function hits(ServerRequestInterface $request): int
    {
        return (int)$this->cache()->get($this->key($request));
    }

    private function key(ServerRequestInterface $request): string
    {
        $ip = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '0');

        return 'api_rl_' . hash('sha256', $ip);
    }

    private function cache(): FrontendInterface
    {
        if ($this->cacheManager->hasCache('smart_job_finder')) {
            return $this->cacheManager->getCache('smart_job_finder');
        }

        return $this->cacheManager->getCache('pages');
    }
}
