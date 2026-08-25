<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Domain;

/**
 * Frontend visibility beyond TCA enable-fields.
 * `valid_through` is a domain deadline, not starttime/endtime — the scheduler
 * may lag, so queries must still hide expired jobs.
 */
final class JobVisibility
{
    /**
     * @param array<string, mixed> $record
     */
    public static function isPubliclyVisible(array $record, ?int $now = null): bool
    {
        $now ??= time();

        if ((int)($record['deleted'] ?? 0) !== 0) {
            return false;
        }
        if ((int)($record['hidden'] ?? 0) !== 0) {
            return false;
        }

        $starttime = (int)($record['starttime'] ?? 0);
        if ($starttime > 0 && $starttime > $now) {
            return false;
        }

        $endtime = (int)($record['endtime'] ?? 0);
        if ($endtime > 0 && $endtime <= $now) {
            return false;
        }

        return !self::isExpired($record, $now);
    }

    /**
     * @param array<string, mixed> $record
     */
    public static function isExpired(array $record, ?int $now = null): bool
    {
        $now ??= time();
        $validThrough = (int)($record['valid_through'] ?? 0);

        return $validThrough > 0 && $validThrough < $now;
    }
}
