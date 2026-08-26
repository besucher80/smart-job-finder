<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Dashboard;

use Agentur\SmartJobFinder\Seo\GoogleJobsReadiness;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final class JobOverviewDataProvider
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly GoogleJobsReadiness $googleJobsReadiness,
    ) {}

    /**
     * @return array{openJobs: int, averageScore: int, newApplications: int, applyAvailable: bool}
     */
    public function getStats(): array
    {
        $jobs = $this->openJobs();
        $scoreSum = 0;
        foreach ($jobs as $job) {
            $scoreSum += $this->googleJobsReadiness->evaluate($job)['score'];
        }

        $applyAvailable = ExtensionManagementUtility::isLoaded('smart_job_apply');

        return [
            'openJobs' => count($jobs),
            'averageScore' => $jobs !== [] ? (int)round($scoreSum / count($jobs)) : 0,
            'newApplications' => $applyAvailable ? $this->newApplicationCount() : 0,
            'applyAvailable' => $applyAvailable,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function openJobs(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_smartjobfinder_domain_model_job');

        return $queryBuilder
            ->select('title', 'description', 'teaser', 'crdate', 'company', 'location', 'workplace_type', 'valid_through', 'employment_type', 'salary_min', 'salary_max', 'slug')
            ->from('tx_smartjobfinder_domain_model_job')
            ->where(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->setMaxResults(200)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function newApplicationCount(): int
    {
        if (!ExtensionManagementUtility::isLoaded('smart_job_apply')) {
            return 0;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_smartjobapply_domain_model_application');

        return (int)$queryBuilder
            ->count('uid')
            ->from('tx_smartjobapply_domain_model_application')
            ->where(
                $queryBuilder->expr()->eq('status', $queryBuilder->createNamedParameter('new')),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
