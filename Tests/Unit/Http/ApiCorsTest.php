<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Http;

use Agentur\SmartJobFinder\Http\ApiCors;
use PHPUnit\Framework\TestCase;

final class ApiCorsTest extends TestCase
{
    public function testEmptyConfigSendsNoCors(): void
    {
        self::assertSame([], ApiCors::headers('', 'https://jobs.example'));
    }

    public function testWildcard(): void
    {
        $headers = ApiCors::headers('*', 'https://jobs.example');
        self::assertSame('*', $headers['Access-Control-Allow-Origin']);
    }

    public function testConcreteOriginMustMatch(): void
    {
        self::assertSame([], ApiCors::headers('https://jobs.example', 'https://evil.example'));
        self::assertSame(
            'https://jobs.example',
            ApiCors::headers('https://jobs.example', 'https://jobs.example')['Access-Control-Allow-Origin'],
        );
    }
}
