<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Seo;

use Agentur\SmartJobFinder\Seo\GoogleJobsReadiness;
use PHPUnit\Framework\TestCase;

final class GoogleJobsReadinessTest extends TestCase
{
    public function testPerfectRecordScoresOneHundred(): void
    {
        $result = (new GoogleJobsReadiness())->evaluate([
            'title' => 'TYPO3 Integrator',
            'description' => '<p>Come work with us.</p>',
            'crdate' => 1_700_000_000,
            'company' => 4,
            'location' => 'Hamburg',
            'workplace_type' => 'ONSITE',
            'valid_through' => 1_800_000_000,
            'employment_type' => 'FULL_TIME',
            'salary_min' => 60000,
            'slug' => 'typo3-integrator',
        ]);

        self::assertSame(100, $result['score']);
        self::assertSame([], $result['missing']);
    }

    public function testRemoteJobDoesNotNeedCity(): void
    {
        $result = (new GoogleJobsReadiness())->evaluate([
            'title' => 'Remote Fluid Dev',
            'teaser' => 'Build Fluid templates.',
            'crdate' => 1_700_000_000,
            'company' => 1,
            'location' => '',
            'workplace_type' => 'REMOTE',
        ]);

        self::assertNotContains('jobLocation', $result['missing']);
        self::assertGreaterThanOrEqual(70, $result['score']);
    }

    public function testEmptyRecordIsNotReady(): void
    {
        $result = (new GoogleJobsReadiness())->evaluate([]);

        self::assertSame(0, $result['score']);
        self::assertContains('title', $result['missing']);
        self::assertContains('hiringOrganization', $result['missing']);
    }
}
