<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Domain;

use Agentur\SmartJobFinder\Domain\DueAnnouncement;
use PHPUnit\Framework\TestCase;

final class DueAnnouncementTest extends TestCase
{
    public function testDueVisibleJobIsAnnouncedOnce(): void
    {
        $now = 1_700_000_000;
        $record = [
            'hidden' => 0,
            'deleted' => 0,
            'starttime' => $now - 10,
            'endtime' => 0,
            'valid_through' => 0,
            'notified_at' => 0,
        ];

        self::assertTrue(DueAnnouncement::shouldAnnounce($record, $now));

        $record['notified_at'] = $now;
        self::assertFalse(DueAnnouncement::shouldAnnounce($record, $now));
    }

    public function testFutureStarttimeWaits(): void
    {
        $now = 1_700_000_000;
        self::assertFalse(DueAnnouncement::shouldAnnounce([
            'hidden' => 0,
            'starttime' => $now + 3600,
            'notified_at' => 0,
        ], $now));
    }

    public function testImmediatePublishIsNotTheSchedulerPath(): void
    {
        self::assertFalse(DueAnnouncement::shouldAnnounce([
            'hidden' => 0,
            'starttime' => 0,
            'notified_at' => 0,
        ], 1_700_000_000));
    }
}
