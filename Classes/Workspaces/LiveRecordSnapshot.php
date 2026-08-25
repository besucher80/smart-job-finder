<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Workspaces;

/**
 * Remembers live-record state before a workspace swap so slug redirects
 * and "new vs. update" can be decided after AfterRecordPublishedEvent.
 *
 * Scoped to a single PHP request (TYPO3 backend publish). Shared DI
 * instance so the cmdmap hook and the workspace listener see the same bag.
 */
final class LiveRecordSnapshot
{
    /**
     * @var array<int, array{slug: string, t3ver_state: int, hidden: int}>
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
            'slug' => (string)($liveRecord['slug'] ?? ''),
            't3ver_state' => (int)($liveRecord['t3ver_state'] ?? 0),
            'hidden' => (int)($liveRecord['hidden'] ?? 0),
        ];
    }

    /**
     * @return array{slug: string, t3ver_state: int, hidden: int}|null
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
     * @param array{slug: string, t3ver_state: int, hidden: int}|null $before
     */
    public static function publicationStatus(?array $before): string
    {
        if ($before === null) {
            return 'update';
        }

        // t3ver_state 1 = NEW placeholder (first time this uid becomes a real live job).
        if ($before['t3ver_state'] === 1 || $before['hidden'] === 1) {
            return 'new';
        }

        return 'update';
    }
}
