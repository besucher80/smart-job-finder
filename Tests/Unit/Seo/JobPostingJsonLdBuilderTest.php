<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Seo;

use Agentur\SmartJobFinder\Seo\JobPostingJsonLdBuilder;
use PHPUnit\Framework\TestCase;

final class JobPostingJsonLdBuilderTest extends TestCase
{
    public function testEncodeEscapesHtmlTagsForSafeJsonLdEmbedding(): void
    {
        $builder = new JobPostingJsonLdBuilder();
        $json = $builder->encode(['title' => '<script>alert(1)</script>']);

        self::assertStringNotContainsString('<script>', $json);
        self::assertStringContainsString('\u003Cscript\u003E', $json);
    }

    public function testEncodeKeepsUnicode(): void
    {
        $builder = new JobPostingJsonLdBuilder();
        $json = $builder->encode(['title' => 'Entwickler:in']);

        self::assertStringContainsString('Entwickler:in', $json);
    }
}
