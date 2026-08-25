<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class ExpireJobsCommand extends Command
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly CacheManager $cacheManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = time();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_smartjobfinder_domain_model_job');
        $queryBuilder->getRestrictions()->removeAll();

        $affected = $queryBuilder
            ->update('tx_smartjobfinder_domain_model_job')
            ->set('hidden', 1)
            ->set('tstamp', $now)
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->gt('valid_through', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->lt('valid_through', $queryBuilder->createNamedParameter($now, Connection::PARAM_INT)),
            )
            ->executeStatement();

        if ($affected > 0) {
            $this->cacheManager->flushCachesByTag('tx_smartjobfinder');
        }

        $pruned = $this->pruneNotificationLog();
        $output->writeln(sprintf('<info>Expired %d job(s), pruned %d log row(s).</info>', $affected, $pruned));

        return Command::SUCCESS;
    }

    private function pruneNotificationLog(): int
    {
        $cutoff = time() - 90 * 86400;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_smartjobfinder_notification_log');
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->delete('tx_smartjobfinder_notification_log')
            ->where(
                $queryBuilder->expr()->lt('tstamp', $queryBuilder->createNamedParameter($cutoff, Connection::PARAM_INT)),
            )
            ->executeStatement();
    }
}
