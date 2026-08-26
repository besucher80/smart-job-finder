<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Notification;

use Agentur\SmartJobFinder\Notification\SlackWebhookUrl;
use PHPUnit\Framework\TestCase;

final class SlackWebhookUrlTest extends TestCase
{
    public function testAcceptsOfficialIncomingWebhook(): void
    {
        self::assertTrue(SlackWebhookUrl::isAllowed(
            'https://hooks.slack.com/services/T000/B000/XXX',
        ));
    }

    public function testRejectsHttpAndForeignHosts(): void
    {
        self::assertFalse(SlackWebhookUrl::isAllowed('http://hooks.slack.com/services/T000/B000/XXX'));
        self::assertFalse(SlackWebhookUrl::isAllowed('https://example.com/hooks'));
        self::assertFalse(SlackWebhookUrl::isAllowed('https://hooks.slack.com/'));
        self::assertFalse(SlackWebhookUrl::isAllowed('not-a-url'));
    }
}
