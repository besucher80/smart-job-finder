<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Notification;

final class NotificationResult
{
    public function __construct(
        public readonly string $channel,
        public readonly string $status,
        public readonly string $payload,
        public readonly string $message = '',
    ) {}

    public function isMock(): bool
    {
        return $this->status === 'mock';
    }
}
