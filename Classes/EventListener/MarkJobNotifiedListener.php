<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\EventListener;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Prevents the starttime scheduler from sending a second mail after a
 * live/workspace publish already dispatched {@see JobPublishedEvent}.
 */
final class MarkJobNotifiedListener
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function __invoke(JobPublishedEvent $event): void
    {
        $uid = $event->getUid();
        if ($uid <= 0) {
            return;
        }

        $this->connectionPool
            ->getConnectionForTable('tx_smartjobfinder_domain_model_job')
            ->update(
                'tx_smartjobfinder_domain_model_job',
                ['notified_at' => time()],
                ['uid' => $uid],
                [Connection::PARAM_INT, Connection::PARAM_INT],
            );
    }
}
