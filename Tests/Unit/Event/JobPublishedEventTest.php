<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Event;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use PHPUnit\Framework\TestCase;

final class JobPublishedEventTest extends TestCase
{
    public function testExposesRecordFields(): void
    {
        $event = new JobPublishedEvent(42, [
            'title' => 'TYPO3 Integrator',
            'location' => 'Hamburg',
            'employment_type' => 'FULL_TIME',
            'slug' => 'typo3-integrator',
        ], 'new');

        self::assertSame(42, $event->getUid());
        self::assertSame('TYPO3 Integrator', $event->getTitle());
        self::assertSame('Hamburg', $event->getLocation());
        self::assertSame('FULL_TIME', $event->getEmploymentType());
        self::assertSame('typo3-integrator', $event->getSlug());
        self::assertTrue($event->isNew());
        self::assertSame('live', $event->getSource());
        self::assertFalse($event->isFromWorkspace());
        self::assertSame(0, $event->getWorkspaceId());
    }

    public function testWorkspaceSource(): void
    {
        $event = new JobPublishedEvent(3, ['title' => 'WS job'], 'new', 'workspace', 2);

        self::assertTrue($event->isFromWorkspace());
        self::assertSame('workspace', $event->getSource());
        self::assertSame(2, $event->getWorkspaceId());
    }

    public function testUpdateIsNotNew(): void
    {
        $event = new JobPublishedEvent(7, ['title' => 'Hidden job'], 'update');

        self::assertFalse($event->isNew());
        self::assertSame('update', $event->getStatus());
    }
}
