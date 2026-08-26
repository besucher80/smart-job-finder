<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Domain;

use Agentur\SmartJobFinder\Domain\JobSearchQuery;
use PHPUnit\Framework\TestCase;

final class JobSearchQueryTest extends TestCase
{
    public function testBooleanModeRequiresEachTokenAsPrefix(): void
    {
        self::assertSame('+developer*', JobSearchQuery::booleanExpression('developer'));
        self::assertSame('+TYPO3* +Integrator*', JobSearchQuery::booleanExpression('  TYPO3   Integrator '));
    }

    public function testBooleanModeStripsOperators(): void
    {
        self::assertSame('+developer* +foo*', JobSearchQuery::booleanExpression('+developer -foo'));
        self::assertSame('', JobSearchQuery::booleanExpression('++ --'));
    }

    public function testLikeNeedleEscapesWildcards(): void
    {
        self::assertSame('%developer%', JobSearchQuery::likeNeedle('developer'));
        self::assertSame('%100\\% remote%', JobSearchQuery::likeNeedle('100% remote'));
    }

    public function testTitleMatchesIgnoresCaseAndFindsTypos(): void
    {
        self::assertTrue(JobSearchQuery::titleMatches('Developer', 'developer'));
        self::assertTrue(JobSearchQuery::titleMatches('Develoer', 'developer'));
        self::assertTrue(JobSearchQuery::titleMatches('Senior TYPO3 Developer (m/w/d)', 'developer'));
        self::assertFalse(JobSearchQuery::titleMatches('Project Manager', 'developer'));
        self::assertFalse(JobSearchQuery::titleMatches('Developer', 'mgr'));
    }
}
