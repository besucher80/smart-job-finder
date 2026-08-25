<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Service;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates a 301 in EXT:redirects when a published job slug changes.
 */
final class SlugRedirectWriter
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly LoggerInterface $logger,
    ) {}

    public function create(string $oldSlug, string $newSlug, int $jobUid): void
    {
        $oldSlug = trim($oldSlug, '/');
        $newSlug = trim($newSlug, '/');
        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) {
            return;
        }

        if (!ExtensionManagementUtility::isLoaded('redirects')) {
            $this->logger->info('Slug changed but EXT:redirects is not loaded; skip 301.', [
                'jobUid' => $jobUid,
                'from' => $oldSlug,
                'to' => $newSlug,
            ]);
            return;
        }

        $prefix = trim((string)($this->getConfig()['jobPathPrefix'] ?? '/jobs'), '/');
        $sourcePath = '/' . ($prefix !== '' ? $prefix . '/' : '') . $oldSlug;
        $targetPath = '/' . ($prefix !== '' ? $prefix . '/' : '') . $newSlug;

        $connection = $this->connectionPool->getConnectionForTable('sys_redirect');
        $existing = $connection->select(['uid'], 'sys_redirect', [
            'source_host' => '*',
            'source_path' => $sourcePath,
            'disabled' => 0,
        ])->fetchOne();
        if ($existing) {
            return;
        }

        try {
            $connection->insert('sys_redirect', [
                'pid' => 0,
                'source_host' => '*',
                'source_path' => $sourcePath,
                'target' => $targetPath,
                'target_statuscode' => 301,
                'hitcount' => 0,
                'disabled' => 0,
                'respect_query_parameters' => 0,
                'keep_query_parameters' => 0,
                'is_regexp' => 0,
            ]);
            $this->rebuildRedirectCache();
        } catch (\Throwable $exception) {
            $this->logger->warning('Could not create slug redirect.', [
                'jobUid' => $jobUid,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function rebuildRedirectCache(): void
    {
        $className = 'TYPO3\\CMS\\Redirects\\Service\\RedirectCacheService';
        if (!class_exists($className)) {
            return;
        }

        $service = GeneralUtility::makeInstance($className);
        if (method_exists($service, 'rebuild')) {
            $service->rebuild();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        try {
            return $this->extensionConfiguration->get('smart_job_finder') ?? [];
        } catch (\Throwable) {
            return [];
        }
    }
}
