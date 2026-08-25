<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Notification;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;

final class MockMailNotifier implements JobNotifierInterface
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly LoggerInterface $logger,
    ) {}

    public function notify(JobPublishedEvent $event): NotificationResult
    {
        $config = $this->getConfig();
        $to = trim((string)($config['mailTo'] ?? ''));
        $from = trim((string)($config['mailFrom'] ?? 'noreply@example.com'));
        $mockMode = (bool)($config['mockMode'] ?? true);

        $subject = sprintf('[Smart Job Finder] Neue Stelle: %s', $event->getTitle());
        $text = $this->buildBody($event);

        if ($mockMode || $to === '') {
            $this->logger->info('Mock mail for published job (not sent).', [
                'to' => $to !== '' ? $to : '(not configured)',
                'from' => $from,
                'subject' => $subject,
                'body' => $text,
                'jobUid' => $event->getUid(),
                'mockMode' => $mockMode,
            ]);

            return new NotificationResult(
                'mail',
                'mock',
                $text,
                $to === '' ? 'No recipient configured' : 'Mock mode',
            );
        }

        $email = new FluidEmail();
        $email
            ->to($to)
            ->from(new Address($from, 'Smart Job Finder'))
            ->subject($subject)
            ->format(FluidEmail::FORMAT_BOTH)
            ->setTemplate('JobPublished')
            ->assignMultiple([
                'title' => $event->getTitle(),
                'uid' => $event->getUid(),
                'location' => $event->getLocation(),
                'employmentType' => $event->getEmploymentType(),
                'slug' => $event->getSlug(),
                'isNew' => $event->isNew(),
                'source' => $event->getSource(),
                'workspaceId' => $event->getWorkspaceId(),
            ]);

        $this->mailer->send($email);

        $this->logger->info('Job publication mail sent.', [
            'to' => $to,
            'jobUid' => $event->getUid(),
        ]);

        return new NotificationResult('mail', 'sent', $text, 'Sent to ' . $to);
    }

    private function buildBody(JobPublishedEvent $event): string
    {
        $lines = [
            'Eine Stelle wurde veröffentlicht.',
            '',
            'UID: ' . $event->getUid(),
            'Titel: ' . $event->getTitle(),
            'Ort: ' . ($event->getLocation() !== '' ? $event->getLocation() : '–'),
            'Anstellungsart: ' . ($event->getEmploymentType() !== '' ? $event->getEmploymentType() : '–'),
            'Slug: ' . ($event->getSlug() !== '' ? $event->getSlug() : '–'),
            'Status: ' . ($event->isNew() ? 'neu angelegt' : 'sichtbar geschaltet'),
            'Quelle: ' . ($event->isFromWorkspace()
                ? 'Workspace' . ($event->getWorkspaceId() > 0 ? ' #' . $event->getWorkspaceId() : '')
                : 'Live'),
        ];

        return implode("\n", $lines);
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
