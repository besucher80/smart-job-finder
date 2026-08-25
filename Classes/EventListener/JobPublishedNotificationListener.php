<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\EventListener;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Agentur\SmartJobFinder\Notification\MockMailNotifier;
use Agentur\SmartJobFinder\Notification\SlackWebhookNotifier;
use Agentur\SmartJobFinder\Service\NotificationLogWriter;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Reacts to {@see JobPublishedEvent}: sends a mock (or real) mail and
 * posts a Slack incoming-webhook payload, then persists both into the
 * backend-visible notification log.
 */
final class JobPublishedNotificationListener
{
    public function __construct(
        private readonly MockMailNotifier $mailNotifier,
        private readonly SlackWebhookNotifier $slackNotifier,
        private readonly NotificationLogWriter $notificationLogWriter,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(JobPublishedEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $this->notificationLogWriter->write($event, $this->mailNotifier->notify($event));
        } catch (\Throwable $exception) {
            $this->logger->error('Job publication mail failed.', [
                'jobUid' => $event->getUid(),
                'exception' => $exception->getMessage(),
            ]);
        }

        try {
            $this->notificationLogWriter->write($event, $this->slackNotifier->notify($event));
        } catch (\Throwable $exception) {
            $this->logger->error('Job publication Slack webhook failed.', [
                'jobUid' => $event->getUid(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function isEnabled(): bool
    {
        try {
            $config = $this->extensionConfiguration->get('smart_job_finder') ?? [];
        } catch (\Throwable) {
            return true;
        }

        return (bool)($config['notificationsEnabled'] ?? true);
    }
}
