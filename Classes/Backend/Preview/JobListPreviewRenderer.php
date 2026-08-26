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
        $flex = $this->flexSettings($item);
        $storagePid = $flex['storagePid'];
        $liveFilter = $flex['liveFilter'];

        $count = $this->countJobs($storagePid);

        return sprintf(
            '<p><strong>Smart Job Finder</strong><br/>%d sichtbare Stellen%s<br/>Live-Filter: %s</p>',
            $count,
            $storagePid !== '' ? ' (PID ' . htmlspecialchars($storagePid, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ')' : '',
            $liveFilter ? 'an' : 'aus',
        );
    }

    /**
     * TYPO3 12/13: getRecord() is an array. TYPO3 14: RecordInterface.
     *
     * @return array{storagePid: string, liveFilter: bool}
     */
    private function flexSettings(GridColumnItem $item): array
    {
        $record = $item->getRecord();

        if (is_object($record) && method_exists($record, 'has') && method_exists($record, 'get')) {
            if ($record->has('pi_flexform')) {
                $parsed = $this->settingsFromFlexValue($record->get('pi_flexform'));
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        $row = is_array($record) ? $record : [];
        if ($row === [] && method_exists($item, 'getRow')) {
            $maybeRow = $item->getRow();
            $row = is_array($maybeRow) ? $maybeRow : [];
        }

        return $this->settingsFromFlexValue($row['pi_flexform'] ?? '')
            ?? ['storagePid' => '', 'liveFilter' => true];
    }

    /**
     * @return array{storagePid: string, liveFilter: bool}|null
     */
    private function settingsFromFlexValue(mixed $flex): ?array
    {
        if (is_object($flex) && method_exists($flex, 'has') && method_exists($flex, 'get')) {
            $storage = $flex->has('sDEF/persistence.storagePid') ? $flex->get('sDEF/persistence.storagePid') : '';
            $filter = $flex->has('sDEF/settings.enableLiveFilter') ? $flex->get('sDEF/settings.enableLiveFilter') : '1';

            return [
                'storagePid' => $this->stringifyFlexValue($storage),
                'liveFilter' => $this->boolFlexValue($filter),
            ];
        }

        if (is_array($flex) && isset($flex['data'])) {
            return [
                'storagePid' => (string)($flex['data']['sDEF']['lDEF']['persistence.storagePid']['vDEF'] ?? ''),
                'liveFilter' => $this->boolFlexValue($flex['data']['sDEF']['lDEF']['settings.enableLiveFilter']['vDEF'] ?? '1'),
            ];
        }

        if (is_string($flex) && $flex !== '') {
            $parsed = GeneralUtility::xml2array($flex);
            if (is_array($parsed)) {
                return $this->settingsFromFlexValue($parsed);
            }
        }

        return null;
    }

    private function stringifyFlexValue(mixed $value): string
    {
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                $part = $this->stringifyFlexValue($item);
                if ($part !== '') {
                    $parts[] = $part;
                }
            }

            return implode(',', $parts);
        }

        if (is_object($value) && method_exists($value, 'getUid')) {
            return (string)$value->getUid();
        }

        return trim((string)$value);
    }

    private function boolFlexValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string)$value));

        return !in_array($normalized, ['', '0', 'false', 'off'], true);
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
