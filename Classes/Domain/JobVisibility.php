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

        $starttime = self::timestamp($record['starttime'] ?? 0);
        if ($starttime > 0 && $starttime > $now) {
            return false;
        }

        $endtime = self::timestamp($record['endtime'] ?? 0);
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
        $validThrough = self::timestamp($record['valid_through'] ?? 0);

        return $validThrough > 0 && $validThrough < $now;
    }

    public static function timestamp(mixed $value): int
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int)$value;
        }
        if (is_string($value) && trim($value) !== '' && trim($value) !== '0') {
            $parsed = strtotime($value);

            return $parsed !== false ? $parsed : 0;
        }

        return 0;
    }
}
