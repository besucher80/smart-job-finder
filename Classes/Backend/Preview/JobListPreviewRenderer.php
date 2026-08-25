<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Backend\Preview;

use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class JobListPreviewRenderer extends StandardContentPreviewRenderer
{
    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $record = $item->getRecord();
        $flex = GeneralUtility::xml2array((string)($record['pi_flexform'] ?? '')) ?: [];
        $storagePid = (string)($flex['data']['sDEF']['lDEF']['persistence.storagePid']['vDEF'] ?? '');
        $liveFilter = (string)($flex['data']['sDEF']['lDEF']['settings.enableLiveFilter']['vDEF'] ?? '1');

        $count = $this->countJobs($storagePid);

        return sprintf(
            '<p><strong>Smart Job Finder</strong><br/>%d sichtbare Stellen%s<br/>Live-Filter: %s</p>',
            $count,
            $storagePid !== '' ? ' (PID ' . htmlspecialchars($storagePid, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ')' : '',
            $liveFilter === '1' || $liveFilter === 'true' ? 'an' : 'aus',
        );
    }

    private function countJobs(string $storagePidList): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_smartjobfinder_domain_model_job');

        $queryBuilder
            ->count('uid')
            ->from('tx_smartjobfinder_domain_model_job')
            ->where(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            );

        $pids = GeneralUtility::intExplode(',', $storagePidList, true);
        if ($pids !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('pid', implode(',', $pids)),
            );
        }

        return (int)$queryBuilder->executeQuery()->fetchOne();
    }
}
