<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\EventListener;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use TYPO3\CMS\Core\Cache\CacheManager;

final class FlushJobCacheListener
{
    public function __construct(
        private readonly CacheManager $cacheManager,
    ) {}

    public function __invoke(JobPublishedEvent $event): void
    {
        $this->cacheManager->flushCachesByTag('tx_smartjobfinder');
        $this->cacheManager->flushCachesByTag('tx_smartjobfinder_job_' . $event->getUid());
    }
}
