<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Hook;

use Agentur\SmartJobFinder\Domain\JobVisibility;
use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Agentur\SmartJobFinder\Event\JobUnpublishedEvent;
use Agentur\SmartJobFinder\Service\SlugRedirectWriter;
use Agentur\SmartJobFinder\Workspaces\LiveRecordSnapshot;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * DataHandler still has no generic PSR-14 "record saved" event in TYPO3 12–14.
 * This hook is the official integration point; it translates a real visibility
 * change into domain events so listeners stay hook-free.
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

    /**
     * @var array<int, bool>
     */
    private array $visibleBefore = [];

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
        if ($table !== self::TABLE || !is_numeric($id)) {
            return;
        }

        $uid = (int)$id;
        $current = BackendUtility::getRecord(self::TABLE, $uid) ?? [];
        if ($current === []) {
            return;
        }

        $this->visibleBefore[$uid] = JobVisibility::isPubliclyVisible($current);
        if (array_key_exists('slug', $fieldArray)) {
            $this->slugBefore[$uid] = (string)($current['slug'] ?? '');
        }
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

        $record = BackendUtility::getRecord(self::TABLE, $uid) ?? [];
        $wasVisible = $this->visibleBefore[$uid] ?? false;
        $nowVisible = $record !== [] && JobVisibility::isPubliclyVisible($record);

        if ($nowVisible && !$wasVisible) {
            $this->eventDispatcher->dispatch(
                new JobPublishedEvent($uid, $record, $status === 'new' ? 'new' : 'update', 'live'),
            );
            return;
        }

        if ($wasVisible && !$nowVisible) {
            $this->eventDispatcher->dispatch(
                new JobUnpublishedEvent($uid, $record !== [] ? $record : ['title' => ''], 'hidden', 'live'),
            );
        }
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
     * Live delete of a public job must drop the page cache. Workspace deletes stay silent.
     */
    public function processCmdmap_deleteAction(...$args): void
    {
        $table = (string)($args[0] ?? '');
        $id = $args[1] ?? 0;
        $recordToDelete = is_array($args[2] ?? null) ? $args[2] : [];
        $dataHandler = $args[4] ?? null;

        if ($table !== self::TABLE || !is_numeric($id) || $recordToDelete === []) {
            return;
        }

        $workspace = 0;
        if (is_object($dataHandler) && isset($dataHandler->BE_USER)) {
            $workspace = (int)($dataHandler->BE_USER->workspace ?? 0);
        }
        if ($workspace > 0) {
            return;
        }

        if (!JobVisibility::isPubliclyVisible($recordToDelete)) {
            return;
        }

        $uid = (int)$id;
        $this->eventDispatcher->dispatch(
            new JobUnpublishedEvent($uid, $recordToDelete, 'deleted', 'live'),
        );
    }
}
