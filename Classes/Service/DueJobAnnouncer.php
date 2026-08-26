<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Service;

use Agentur\SmartJobFinder\Domain\DueAnnouncement;
use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class DueJobAnnouncer
{
    private const TABLE = 'tx_smartjobfinder_domain_model_job';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function announce(int $now): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('notified_at', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->gt('starttime', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->lte('starttime', $queryBuilder->createNamedParameter($now, Connection::PARAM_INT)),
            )
            ->setMaxResults(100)
            ->executeQuery()
            ->fetchAllAssociative();

        $announced = 0;
        foreach ($rows as $row) {
            if (!DueAnnouncement::shouldAnnounce($row, $now)) {
                continue;
            }

            $this->eventDispatcher->dispatch(
                new JobPublishedEvent((int)$row['uid'], $row, 'update', 'live'),
            );
            $announced++;
        }

        return $announced;
    }
}
