<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Event;

/**
 * Dispatched when a job record becomes publicly visible in the backend
 * (new visible record, hidden → visible, or workspace publish to live).
 */
final class JobPublishedEvent
{
    /**
     * @param array<string, mixed> $record
     * @param string $source `live` (DataHandler) or `workspace` (EXT:workspaces publish)
     */
    public function __construct(
        private readonly int $uid,
        private readonly array $record,
        private readonly string $status,
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTitle(): string
    {
        return (string)($this->record['title'] ?? '');
    }

    public function getLocation(): string
    {
        return (string)($this->record['location'] ?? '');
    }

    public function getEmploymentType(): string
    {
        return (string)($this->record['employment_type'] ?? '');
    }

    public function getSlug(): string
    {
        return (string)($this->record['slug'] ?? '');
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function isFromWorkspace(): bool
    {
        return $this->source === 'workspace';
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }
}
