<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\EventListener;

use Agentur\SmartJobFinder\Domain\JobVisibility;
use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Agentur\SmartJobFinder\Event\JobUnpublishedEvent;
use Agentur\SmartJobFinder\Service\SlugRedirectWriter;
use Agentur\SmartJobFinder\Workspaces\LiveRecordSnapshot;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Workspace drafts must not notify. This listener runs only after EXT:workspaces
 * has swapped the version onto the live record.
 *
 * The event class is referenced by string so the extension still boots when
 * EXT:workspaces is not installed.
 */
final class WorkspaceJobPublishedListener
{
    private const TABLE = 'tx_smartjobfinder_domain_model_job';
    private const EVENT_CLASS = 'TYPO3\\CMS\\Workspaces\\Event\\AfterRecordPublishedEvent';

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SlugRedirectWriter $slugRedirectWriter,
        private readonly LiveRecordSnapshot $liveRecordSnapshot,
    ) {}

    public function __invoke(object $event): void
    {
        if (!is_a($event, self::EVENT_CLASS)) {
            return;
        }

        $table = (string)call_user_func([$event, 'getTable']);
        if ($table !== self::TABLE) {
            return;
        }

        $uid = (int)call_user_func([$event, 'getRecordId']);
        $workspaceId = (int)call_user_func([$event, 'getWorkspaceId']);
        $before = $this->liveRecordSnapshot->pull($uid);
        $record = BackendUtility::getRecord(self::TABLE, $uid) ?? [];

        $oldSlug = (string)($before['slug'] ?? '');
        $newSlug = (string)($record['slug'] ?? '');
        if ($oldSlug !== '' && $newSlug !== '' && $oldSlug !== $newSlug) {
            $this->slugRedirectWriter->create($oldSlug, $newSlug, $uid);
        }

        $wasVisible = LiveRecordSnapshot::wasPubliclyVisible($before);
        $nowVisible = $record !== [] && JobVisibility::isPubliclyVisible($record);

        if ($nowVisible) {
            $this->eventDispatcher->dispatch(
                new JobPublishedEvent(
                    $uid,
                    $record,
                    LiveRecordSnapshot::publicationStatus($before),
                    'workspace',
                    $workspaceId,
                ),
            );
            return;
        }

        if ($wasVisible) {
            $this->eventDispatcher->dispatch(
                new JobUnpublishedEvent(
                    $uid,
                    $record !== [] ? $record : ($before ?? []),
                    'workspace',
                    'workspace',
                    $workspaceId,
                ),
            );
        }
    }
}
