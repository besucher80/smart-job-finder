<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Domain;

use Agentur\SmartJobFinder\Domain\JobVisibility;
use PHPUnit\Framework\TestCase;

final class JobVisibilityTest extends TestCase
{
    public function testVisibleRecordPasses(): void
    {
        self::assertTrue(JobVisibility::isPubliclyVisible([
            'hidden' => 0,
            'deleted' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'valid_through' => 0,
        ], 1_700_000_000));
    }

    public function testExpiredDeadlineIsNotPublic(): void
    {
        $now = 1_700_000_000;
        $record = ['valid_through' => $now - 10];

        self::assertTrue(JobVisibility::isExpired($record, $now));
        self::assertFalse(JobVisibility::isPubliclyVisible($record, $now));
    }

    public function testFutureStarttimeIsNotPublic(): void
    {
        $now = 1_700_000_000;
        self::assertFalse(JobVisibility::isPubliclyVisible([
            'starttime' => $now + 3600,
        ], $now));
    }

    public function testHiddenRecordIsNotPublic(): void
    {
        self::assertFalse(JobVisibility::isPubliclyVisible(['hidden' => 1], 1_700_000_000));
    }

    public function testDatetimeStringsAreNotCastToTinyIntegers(): void
    {
        $now = 1_700_000_000;
        self::assertFalse(JobVisibility::isExpired(['valid_through' => '2026-12-31 00:00:00'], $now));
        self::assertTrue(JobVisibility::isPubliclyVisible([
            'endtime' => '2026-12-31 12:00:00',
            'valid_through' => 0,
        ], $now));
        self::assertSame(1_700_000_000, JobVisibility::timestamp('1700000000'));
    }
}
