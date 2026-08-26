<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Workspaces;

use Agentur\SmartJobFinder\Domain\JobVisibility;

/**
 * Remembers live-record state before a workspace swap so slug redirects
 * and "new vs. update" / unpublish can be decided after AfterRecordPublishedEvent.
 *
 * Scoped to a single PHP request (TYPO3 backend publish). Shared DI
 * instance so the cmdmap hook and the workspace listener see the same bag.
 */
final class LiveRecordSnapshot
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $records = [];

    /**
     * @param array<string, mixed> $liveRecord
     */
    public function remember(int $liveUid, array $liveRecord): void
    {
        if ($liveUid <= 0) {
            return;
        }

        $this->records[$liveUid] = [
            'title' => (string)($liveRecord['title'] ?? ''),
            'slug' => (string)($liveRecord['slug'] ?? ''),
            't3ver_state' => (int)($liveRecord['t3ver_state'] ?? 0),
            'hidden' => (int)($liveRecord['hidden'] ?? 0),
            'deleted' => (int)($liveRecord['deleted'] ?? 0),
            'starttime' => (int)($liveRecord['starttime'] ?? 0),
            'endtime' => (int)($liveRecord['endtime'] ?? 0),
            'valid_through' => (int)($liveRecord['valid_through'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pull(int $liveUid): ?array
    {
        $state = $this->records[$liveUid] ?? null;
        unset($this->records[$liveUid]);

        return $state;
    }

    /**
     * New-placeholder or previously hidden live record → public "new job".
     * Already-live content swap → "update".
     *
     * @param array<string, mixed>|null $before
     */
    public static function publicationStatus(?array $before): string
    {
        if ($before === null) {
            return 'update';
        }

        // t3ver_state 1 = NEW placeholder (first time this uid becomes a real live job).
        if ((int)($before['t3ver_state'] ?? 0) === 1 || (int)($before['hidden'] ?? 0) === 1) {
            return 'new';
        }

        return 'update';
    }

    /**
     * @param array<string, mixed>|null $before
     */
    public static function wasPubliclyVisible(?array $before, ?int $now = null): bool
    {
        return $before !== null && JobVisibility::isPubliclyVisible($before, $now);
    }
}
