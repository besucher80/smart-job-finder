<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Notification;

/**
 * Incoming Slack webhooks only. Rejects http, empty paths and arbitrary hosts
 * so a mistyped Extension Configuration value is never POSTed.
 */
final class SlackWebhookUrl
{
    public static function isAllowed(string $url): bool
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower((string)($parts['host'] ?? ''));
        $allowedHost = $host === 'hooks.slack.com' || str_ends_with($host, '.hooks.slack.com');
        if (!$allowedHost) {
            return false;
        }

        $path = (string)($parts['path'] ?? '');

        return $path !== '' && $path !== '/';
    }
}
