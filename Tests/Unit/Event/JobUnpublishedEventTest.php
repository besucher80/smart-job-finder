<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Event;

use Agentur\SmartJobFinder\Event\JobUnpublishedEvent;
use PHPUnit\Framework\TestCase;

final class JobUnpublishedEventTest extends TestCase
{
    public function testExposesReasonAndSource(): void
    {
        $event = new JobUnpublishedEvent(
            11,
            ['title' => 'Retired job'],
            'hidden',
        );

        self::assertSame(11, $event->getUid());
        self::assertSame('Retired job', $event->getTitle());
        self::assertSame('hidden', $event->getReason());
        self::assertSame('live', $event->getSource());
        self::assertSame(0, $event->getWorkspaceId());
    }
}
