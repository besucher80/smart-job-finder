<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Http;

/**
 * CORS for /api/jobs. Empty config → no CORS headers (same-origin only).
 * `*` is allowed; a concrete origin is echoed only when it matches.
 */
final class ApiCors
{
    /**
     * @return array<string, string>
     */
    public static function headers(string $configuredOrigin, string $requestOrigin): array
    {
        $configuredOrigin = trim($configuredOrigin);
        if ($configuredOrigin === '') {
            return [];
        }

        if ($configuredOrigin === '*') {
            return [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Accept, Content-Type',
                'Access-Control-Max-Age' => '600',
            ];
        }

        if ($requestOrigin === '' || !hash_equals($configuredOrigin, $requestOrigin)) {
            return [];
        }

        return [
            'Access-Control-Allow-Origin' => $requestOrigin,
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Accept, Content-Type',
            'Access-Control-Max-Age' => '600',
            'Vary' => 'Origin',
        ];
    }
}
