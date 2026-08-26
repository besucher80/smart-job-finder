<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Workspaces;

use Agentur\SmartJobFinder\Workspaces\LiveRecordSnapshot;
use PHPUnit\Framework\TestCase;

final class LiveRecordSnapshotTest extends TestCase
{
    public function testPlaceholderBecomesNew(): void
    {
        self::assertSame('new', LiveRecordSnapshot::publicationStatus([
            'slug' => 'draft-job',
            't3ver_state' => 1,
            'hidden' => 1,
        ]));
    }

    public function testHiddenLiveRecordBecomesNew(): void
    {
        self::assertSame('new', LiveRecordSnapshot::publicationStatus([
            'slug' => 'hidden-job',
            't3ver_state' => 0,
            'hidden' => 1,
        ]));
    }

    public function testAlreadyLiveSwapIsUpdate(): void
    {
        self::assertSame('update', LiveRecordSnapshot::publicationStatus([
            'slug' => 'live-job',
            't3ver_state' => 0,
            'hidden' => 0,
        ]));
    }

    public function testMissingSnapshotIsUpdate(): void
    {
        self::assertSame('update', LiveRecordSnapshot::publicationStatus(null));
    }

    public function testPullRemovesSnapshot(): void
    {
        $snapshot = new LiveRecordSnapshot();
        $snapshot->remember(9, ['slug' => 'old-slug', 't3ver_state' => 0, 'hidden' => 0]);

        self::assertSame('old-slug', $snapshot->pull(9)['slug'] ?? null);
        self::assertNull($snapshot->pull(9));
    }

    public function testWasPubliclyVisibleUsesJobVisibility(): void
    {
        self::assertTrue(LiveRecordSnapshot::wasPubliclyVisible([
            'slug' => 'live-job',
            't3ver_state' => 0,
            'hidden' => 0,
            'deleted' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'valid_through' => 0,
        ], 1_700_000_000));
        self::assertFalse(LiveRecordSnapshot::wasPubliclyVisible([
            'hidden' => 1,
        ], 1_700_000_000));
        self::assertFalse(LiveRecordSnapshot::wasPubliclyVisible(null));
    }
}
