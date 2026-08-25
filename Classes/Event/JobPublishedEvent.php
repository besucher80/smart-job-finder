<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Event;

/**
 * Dispatched when a job record becomes publicly visible in the backend
 * (new visible record or hidden → visible).
 */
final class JobPublishedEvent
{
    /**
     * @param array<string, mixed> $record
     */
    public function __construct(
        private readonly int $uid,
        private readonly array $record,
        private readonly string $status,
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
}
