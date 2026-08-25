<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Notification;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

final class SlackWebhookNotifier implements JobNotifierInterface
{
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly LoggerInterface $logger,
    ) {}

    public function notify(JobPublishedEvent $event): NotificationResult
    {
        $config = $this->getConfig();
        $webhookUrl = trim((string)($config['slackWebhookUrl'] ?? ''));
        $mockMode = (bool)($config['mockMode'] ?? true);
        $payload = $this->buildPayload($event);
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        if ($mockMode || $webhookUrl === '') {
            $this->logger->info('Mock Slack webhook for published job (not posted).', [
                'webhookConfigured' => $webhookUrl !== '',
                'payload' => $payload,
                'jobUid' => $event->getUid(),
                'mockMode' => $mockMode,
            ]);

            return new NotificationResult(
                'slack',
                'mock',
                $json,
                $webhookUrl === '' ? 'No webhook URL configured' : 'Mock mode',
            );
        }

        $this->requestFactory->request($webhookUrl, 'POST', [
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'body' => $json,
            'timeout' => 5,
            'http_errors' => false,
        ]);

        $this->logger->info('Slack webhook posted for published job.', [
            'jobUid' => $event->getUid(),
        ]);

        return new NotificationResult('slack', 'sent', $json, 'Posted to Slack');
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(JobPublishedEvent $event): array
    {
        $title = $event->getTitle() !== '' ? $event->getTitle() : ('Job #' . $event->getUid());
        $location = $event->getLocation() !== '' ? $event->getLocation() : 'k. A.';
        $type = $event->getEmploymentType() !== '' ? $event->getEmploymentType() : 'k. A.';
        $action = $event->isNew() ? 'neu veröffentlicht' : 'sichtbar geschaltet';

        return [
            'text' => sprintf('Neue Stelle %s: %s', $action, $title),
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'Smart Job Finder',
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => sprintf("*%s* wurde %s.", $title, $action),
                    ],
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => '*Ort*\n' . $location,
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => '*Anstellungsart*\n' . $type,
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => '*UID*\n' . $event->getUid(),
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => '*Slug*\n' . ($event->getSlug() !== '' ? $event->getSlug() : '–'),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        try {
            return $this->extensionConfiguration->get('smart_job_finder') ?? [];
        } catch (\Throwable) {
            return [];
        }
    }
}
