<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\EventListener;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Agentur\SmartJobFinder\Event\JobUnpublishedEvent;
use TYPO3\CMS\Core\Cache\CacheManager;

final class FlushJobCacheListener
{
    public function __construct(
        private readonly CacheManager $cacheManager,
    ) {}

    public function onPublished(JobPublishedEvent $event): void
    {
        $this->flush($event->getUid());
    }

    public function onUnpublished(JobUnpublishedEvent $event): void
    {
        $this->flush($event->getUid());
    }

    private function flush(int $uid): void
    {
        $this->cacheManager->flushCachesByTag('tx_smartjobfinder');
        if ($uid > 0) {
            $this->cacheManager->flushCachesByTag('tx_smartjobfinder_job_' . $uid);
        }
    }
}
