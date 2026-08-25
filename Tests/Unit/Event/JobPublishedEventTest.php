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
    }

    public function testUpdateIsNotNew(): void
    {
        $event = new JobPublishedEvent(7, ['title' => 'Hidden job'], 'update');

        self::assertFalse($event->isNew());
        self::assertSame('update', $event->getStatus());
    }
}
