<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Event;

/**
 * Dispatched when a previously public job leaves the frontend
 * (hidden, deleted, or workspace delete-placeholder published).
 *
 * Notifications do not listen here — only cache flush.
 */
final class JobUnpublishedEvent
{
    /**
     * @param array<string, mixed> $record
     * @param string $reason `hidden`, `deleted` or `workspace`
     * @param string $source `live` or `workspace`
     */
    public function __construct(
        private readonly int $uid,
        private readonly array $record,
        private readonly string $reason,
        private readonly string $source = 'live',
        private readonly int $workspaceId = 0,
    ) {}

    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getTitle(): string
    {
        return (string)($this->record['title'] ?? '');
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }
}
