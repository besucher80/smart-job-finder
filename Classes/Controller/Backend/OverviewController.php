<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Controller\Backend;

use Agentur\SmartJobFinder\Seo\GoogleJobsReadiness;
use Agentur\SmartJobFinder\Service\NotificationLogWriter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Native backend module (TYPO3 14 style: PSR-7 + ModuleTemplate, no Extbase).
 */
#[AsController]
final class OverviewController
{
    private const LLL = 'LLL:EXT:smart_job_finder/Resources/Private/Language/locallang_mod.xlf:';

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly GoogleJobsReadiness $googleJobsReadiness,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly PageRenderer $pageRenderer,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $now = time();
        $expiringUntil = $now + 14 * 86400;
        $sum = 0;
        $jobs = $this->fetchOpenJobs();
        foreach ($jobs as &$job) {
            $job['googleJobs'] = $this->googleJobsReadiness->evaluate($job);
            $score = (int)$job['googleJobs']['score'];
            $sum += $score;
            $job['scoreTone'] = $this->scoreTone($score);
            $validThrough = (int)($job['valid_through'] ?? 0);
            $job['isExpiring'] = $validThrough > $now && $validThrough < $expiringUntil;
            $job['featured'] = (int)($job['featured'] ?? 0);
        }
        unset($job);

        $averageScore = $jobs !== [] ? (int)round($sum / count($jobs)) : 0;
        $config = $this->extensionConfig();

        $view = $this->moduleTemplateFactory->create($request);
        $this->pageRenderer->addCssFile('EXT:smart_job_finder/Resources/Public/Css/backend.css');
        $this->configureModule($view);
        $view->assignMultiple([
            'jobCount' => count($jobs),
            'featuredCount' => count(array_filter($jobs, static fn (array $job): bool => (int)$job['featured'] === 1)),
            'expiringCount' => $this->countExpiring(),
            'averageScore' => $averageScore,
            'scoreTone' => $this->scoreTone($averageScore),
            'jobs' => $jobs,
            'logs' => $this->decorateLogs($this->fetchLogs()),
            'mockMode' => (bool)($config['mockMode'] ?? true),
            'notificationsEnabled' => (bool)($config['notificationsEnabled'] ?? true),
        ]);

        return $view->renderResponse('Overview/Index');
    }

    private function configureModule(ModuleTemplate $view): void
    {
        $title = (string)$this->getLanguageService()->sL(self::LLL . 'dashboard.title');
        $view->setTitle($title);
        $view->setModuleClass('module-smart-job-finder');

        $docHeader = $view->getDocHeaderComponent();
        if (method_exists($docHeader, 'setShortcutContext')) {
            $docHeader->setShortcutContext('web_smartjobfinder', $title);
            return;
        }

        $this->addLegacyShortcutButton($docHeader, $title);
    }

    private function addLegacyShortcutButton(object $docHeader, string $title): void
    {
        if (!method_exists($docHeader, 'getButtonBar')) {
            return;
        }

        $buttonBar = $docHeader->getButtonBar();
        $shortcutClass = 'TYPO3\\CMS\\Backend\\Template\\Components\\Buttons\\Action\\ShortcutButton';
        if (!class_exists($shortcutClass)) {
            $shortcutClass = 'TYPO3\\CMS\\Backend\\Template\\Components\\Buttons\\ShortcutButton';
        }
        if (!class_exists($shortcutClass) || !method_exists($buttonBar, 'makeButton')) {
            return;
        }

        $shortcutButton = $buttonBar->makeButton($shortcutClass)
            ->setRouteIdentifier('web_smartjobfinder')
            ->setDisplayName($title);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchOpenJobs(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_smartjobfinder_domain_model_job');

        return $queryBuilder
            ->select('*')
            ->from('tx_smartjobfinder_domain_model_job')
            ->where(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('featured', 'DESC')
            ->addOrderBy('crdate', 'DESC')
            ->setMaxResults(20)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function countExpiring(): int
    {
        $now = time();
        $limit = $now + 14 * 86400;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_smartjobfinder_domain_model_job');

        return (int)$queryBuilder
            ->count('uid')
            ->from('tx_smartjobfinder_domain_model_job')
            ->where(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->gt('valid_through', $queryBuilder->createNamedParameter($now, Connection::PARAM_INT)),
                $queryBuilder->expr()->lt('valid_through', $queryBuilder->createNamedParameter($limit, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchLogs(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(NotificationLogWriter::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder
            ->select('*')
            ->from(NotificationLogWriter::TABLE)
            ->orderBy('tstamp', 'DESC')
            ->setMaxResults(25)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param list<array<string, mixed>> $logs
     * @return list<array<string, mixed>>
     */
    private function decorateLogs(array $logs): array
    {
        foreach ($logs as &$log) {
            $status = (string)($log['status'] ?? '');
            $log['tone'] = match ($status) {
                'failed', 'error' => 'danger',
                'mock' => 'warning',
                default => 'success',
            };
        }
        unset($log);

        return $logs;
    }

    /**
     * @return array<string, mixed>
     */
    private function extensionConfig(): array
    {
        try {
            $config = $this->extensionConfiguration->get('smart_job_finder');
        } catch (\Throwable) {
            return [];
        }

        return is_array($config) ? $config : [];
    }

    private function scoreTone(int $score): string
    {
        return match (true) {
            $score >= 80 => 'success',
            $score >= 50 => 'warning',
            default => 'danger',
        };
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
