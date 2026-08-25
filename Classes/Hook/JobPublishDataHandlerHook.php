<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Hook;

use Agentur\SmartJobFinder\Domain\JobVisibility;
use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Agentur\SmartJobFinder\Service\SlugRedirectWriter;
use Agentur\SmartJobFinder\Workspaces\LiveRecordSnapshot;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * DataHandler still has no generic PSR-14 "record saved" event in TYPO3 12–14.
 * This hook is the official integration point; it translates a real publication
 * into {@see JobPublishedEvent} so domain listeners stay hook-free.
 *
 * Workspace drafts never dispatch from datamap. Publish-to-live is handled by
 * {@see \Agentur\SmartJobFinder\EventListener\WorkspaceJobPublishedListener}
 * after this class snapshots the live row on cmdmap `version` swap.
 */
final class JobPublishDataHandlerHook
{
    private const TABLE = 'tx_smartjobfinder_domain_model_job';

    /**
     * @var array<int, string>
     */
    private array $slugBefore = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SlugRedirectWriter $slugRedirectWriter,
        private readonly LiveRecordSnapshot $liveRecordSnapshot,
    ) {}

    /**
     * @param array<string, mixed> $fieldArray
     * @param string|int $id
     */
    public function processDatamap_preProcessFieldArray(
        array &$fieldArray,
        string $table,
        $id,
        DataHandler $dataHandler,
    ): void {
        if ($table !== self::TABLE || !is_numeric($id) || !array_key_exists('slug', $fieldArray)) {
            return;
        }

        $current = BackendUtility::getRecord(self::TABLE, (int)$id, 'slug');
        $this->slugBefore[(int)$id] = (string)($current['slug'] ?? '');
    }

    /**
     * @param array<string, mixed> $fieldArray
     * @param string|int $id
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        $id,
        array $fieldArray,
        DataHandler $dataHandler,
    ): void {
        if ($table !== self::TABLE) {
            return;
        }

        if ((int)($dataHandler->BE_USER->workspace ?? 0) > 0) {
            return;
        }

        $uid = (int)($dataHandler->substNEWwithIDs[$id] ?? $id);
        if ($uid <= 0) {
            return;
        }

        if ($status === 'update' && isset($fieldArray['slug'], $this->slugBefore[$uid])) {
            $this->slugRedirectWriter->create($this->slugBefore[$uid], (string)$fieldArray['slug'], $uid);
        }

        if (!$this->isPublication($status, $fieldArray)) {
            return;
        }

        $record = BackendUtility::getRecord(self::TABLE, $uid) ?? [];
        if ($record === [] || !JobVisibility::isPubliclyVisible($record)) {
            return;
        }

        $this->eventDispatcher->dispatch(
            new JobPublishedEvent($uid, $record, $status, 'live'),
        );
    }

    /**
     * Snapshot live slug / placeholder state before EXT:workspaces swaps versions.
     * Signature uses `...$args` so TYPO3 12–14 extra parameters stay compatible.
     */
    public function processCmdmap_preProcess(...$args): void
    {
        $command = (string)($args[0] ?? '');
        $table = (string)($args[1] ?? '');
        $id = $args[2] ?? 0;
        $value = $args[3] ?? null;

        if ($table !== self::TABLE || $command !== 'version' || !is_numeric($id)) {
            return;
        }

        $action = is_array($value) ? (string)($value['action'] ?? '') : '';
        if (!in_array($action, ['swap', 'publish'], true)) {
            return;
        }

        $liveUid = (int)$id;
        $record = BackendUtility::getRecord(self::TABLE, $liveUid) ?? [];
        if ($record === []) {
            return;
        }

        $this->liveRecordSnapshot->remember($liveUid, $record);
    }

    /**
     * @param array<string, mixed> $fieldArray
     */
    private function isPublication(string $status, array $fieldArray): bool
    {
        if ($status === 'new') {
            return (int)($fieldArray['hidden'] ?? 0) === 0;
        }

        if ($status === 'update' && array_key_exists('hidden', $fieldArray)) {
            return (int)$fieldArray['hidden'] === 0;
        }

        return false;
    }
}
