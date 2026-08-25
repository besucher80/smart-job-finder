<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Tests\Unit\Notification;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Agentur\SmartJobFinder\Notification\SlackWebhookNotifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Payload shape is tested without HTTP. Core classes are skipped when
 * this test runs outside a TYPO3 project.
 */
final class SlackWebhookNotifierTest extends TestCase
{
    public function testBuildPayloadContainsJobFacts(): void
    {
        if (!class_exists(RequestFactory::class)) {
            self::markTestSkipped('TYPO3 Core is not available in this environment.');
        }

        $notifier = new SlackWebhookNotifier(
            $this->createMock(RequestFactory::class),
            $this->createMock(ExtensionConfiguration::class),
            new NullLogger(),
        );

        $payload = $notifier->buildPayload(new JobPublishedEvent(
            15,
            [
                'title' => 'Senior Fluid Developer',
                'location' => 'Berlin',
                'employment_type' => 'FULL_TIME',
                'slug' => 'senior-fluid-developer',
            ],
            'new',
        ));

        self::assertSame('Neue Stelle neu veröffentlicht: Senior Fluid Developer', $payload['text']);
        self::assertNotEmpty($payload['blocks']);
    }
}
