<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Controller\Backend;

use Agentur\SmartJobFinder\Seo\GoogleJobsReadiness;
use Agentur\SmartJobFinder\Service\NotificationLogWriter;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

final class OverviewController extends ActionController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly GoogleJobsReadiness $googleJobsReadiness,
    ) {}

    public function indexAction(): ResponseInterface
    {
        $sum = 0;
        $jobs = $this->fetchOpenJobs();
        foreach ($jobs as &$job) {
            $job['googleJobs'] = $this->googleJobsReadiness->evaluate($job);
            $sum += $job['googleJobs']['score'];
        }
        unset($job);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assignMultiple([
            'jobCount' => count($jobs),
            'featuredCount' => count(array_filter($jobs, static fn (array $job): bool => (int)$job['featured'] === 1)),
            'expiringCount' => $this->countExpiring(),
            'averageScore' => $jobs !== [] ? (int)round($sum / count($jobs)) : 0,
            'jobs' => $jobs,
            'logs' => $this->fetchLogs(),
        ]);

        return $moduleTemplate->renderResponse('Overview/Index');
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
}
