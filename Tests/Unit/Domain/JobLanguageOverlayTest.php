<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Domain;

use Agentur\SmartJobFinder\Domain\JobLanguageOverlay;
use PHPUnit\Framework\TestCase;

final class JobLanguageOverlayTest extends TestCase
{
    public function testDefaultLanguageDropsTranslations(): void
    {
        $rows = [
            ['uid' => 1, 'sys_language_uid' => 0, 'title' => 'DE'],
            ['uid' => 2, 'sys_language_uid' => 1, 'l10n_parent' => 1, 'title' => 'EN'],
        ];

        $overlayed = JobLanguageOverlay::overlay($rows, 0);

        self::assertCount(1, $overlayed);
        self::assertSame('DE', $overlayed[0]['title']);
    }

    public function testTranslationWinsOverDefault(): void
    {
        $rows = [
            ['uid' => 1, 'sys_language_uid' => 0, 'title' => 'DE'],
            ['uid' => 2, 'sys_language_uid' => 1, 'l10n_parent' => 1, 'title' => 'EN'],
        ];

        $overlayed = JobLanguageOverlay::overlay($rows, 1);

        self::assertCount(1, $overlayed);
        self::assertSame('EN', $overlayed[0]['title']);
    }

    public function testFallbackKeepsDefaultWhenTranslationMissing(): void
    {
        $rows = [
            ['uid' => 1, 'sys_language_uid' => 0, 'title' => 'DE'],
        ];

        $overlayed = JobLanguageOverlay::overlay($rows, 1, false);

        self::assertCount(1, $overlayed);
        self::assertSame('DE', $overlayed[0]['title']);
    }

    public function testStrictDropsUntranslatedDefaults(): void
    {
        $rows = [
            ['uid' => 1, 'sys_language_uid' => 0, 'title' => 'DE'],
        ];

        self::assertSame([], JobLanguageOverlay::overlay($rows, 1, true));
    }
}
