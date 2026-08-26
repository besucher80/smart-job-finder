<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Domain;

/**
 * A job with a future starttime is saved silently. When starttime is due
 * and nobody has been notified yet, the scheduler announces it.
 */
final class DueAnnouncement
{
    /**
     * @param array<string, mixed> $record
     */
    public static function shouldAnnounce(array $record, ?int $now = null): bool
    {
        $now ??= time();

        if ((int)($record['notified_at'] ?? 0) > 0) {
            return false;
        }

        $starttime = (int)($record['starttime'] ?? 0);
        if ($starttime <= 0 || $starttime > $now) {
            return false;
        }

        return JobVisibility::isPubliclyVisible($record, $now);
    }
}
