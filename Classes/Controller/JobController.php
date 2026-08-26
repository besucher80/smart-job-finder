<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Controller;

use Agentur\SmartJobFinder\Domain\JobVisibility;
use Agentur\SmartJobFinder\Domain\Model\Job;
use Agentur\SmartJobFinder\Domain\Repository\JobRepository;
use Agentur\SmartJobFinder\Seo\JobFrontendSeoWriter;
use Agentur\SmartJobFinder\Seo\JobPostingJsonLdBuilder;
use Agentur\SmartJobFinder\Service\FrontendCacheTagger;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

final class JobController extends ActionController
{
    public function __construct(
        private readonly JobRepository $jobRepository,
        private readonly JobPostingJsonLdBuilder $jsonLdBuilder,
        private readonly JobFrontendSeoWriter $seoWriter,
        private readonly FrontendCacheTagger $cacheTagger,
    ) {}

    public function listAction(int $page = 1): ResponseInterface
    {
        return $this->renderList([], $page, false);
    }

    public function filterAction(
        string $q = '',
        string $location = '',
        string $employmentType = '',
        string $workplaceType = '',
        int $category = 0,
        int $page = 1,
    ): ResponseInterface {
        $filter = $this->normalizeFilter($q, $location, $employmentType, $workplaceType, $category);

        return $this->renderList($filter, $page, true);
    }

    public function showAction(Job $job): ResponseInterface
    {
        if (JobVisibility::isExpired(['valid_through' => $job->getValidThrough()])) {
            return $this->responseFactory->createResponse(404);
        }

        $canonicalUrl = $this->jobUrl($job);
        $this->seoWriter->apply($job, $canonicalUrl);
        $this->cacheTagger->add([
            'tx_smartjobfinder',
            'tx_smartjobfinder_job_' . (int)$job->getUid(),
        ]);
        $this->view->assignMultiple([
            'job' => $job,
            'applyAvailable' => $this->isApplyFormAvailable(),
            'jobPostingJson' => $this->jsonLdBuilder->encode(
                $this->jsonLdBuilder->build($job, $canonicalUrl),
            ),
        ]);

        return $this->htmlResponse();
    }

    /**
     * @param array{q?: string, location?: string, employmentType?: string, workplaceType?: string, category?: int} $filter
     */
    private function renderList(array $filter, int $page, bool $isFilter): ResponseInterface
    {
        $filter = $this->normalizeFilter(
            (string)($filter['q'] ?? ''),
            (string)($filter['location'] ?? ''),
            (string)($filter['employmentType'] ?? ''),
            (string)($filter['workplaceType'] ?? ''),
            (int)($filter['category'] ?? 0),
        );

        $perPage = max(1, (int)($this->settings['itemsPerPage'] ?? 12));
        $allJobs = $this->jobRepository->findByFilter($filter)->toArray();
        $total = count($allJobs);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $jobs = array_slice($allJobs, ($page - 1) * $perPage, $perPage);

        $jobUids = array_map(static fn (Job $job): int => (int)$job->getUid(), $jobs);
        $isAjax = $isFilter && $this->isAjaxRequest();

        $this->view->assignMultiple([
            'jobs' => $jobs,
            'jobCount' => $total,
            'companyNames' => $this->jobRepository->findCompanyNamesByJobUids($jobUids),
            'filter' => $filter,
            'locations' => $isAjax ? [] : $this->jobRepository->findDistinctLocations(),
            'employmentTypes' => $isAjax ? [] : $this->employmentTypes(),
            'workplaceTypes' => $isAjax ? [] : $this->workplaceTypes(),
            'pagination' => [
                'current' => $page,
                'pages' => $pages,
                'prev' => $page > 1 ? $page - 1 : 0,
                'next' => $page < $pages ? $page + 1 : 0,
                'perPage' => $perPage,
                'action' => $isFilter ? 'filter' : 'list',
            ],
            'itemListJson' => $isAjax ? '' : $this->jsonLdBuilder->encode(
                $this->jsonLdBuilder->buildItemList($jobs, fn (Job $job): string => $this->jobUrl($job)),
            ),
            'applyAvailable' => $this->isApplyFormAvailable(),
        ]);

        $this->cacheTagger->add($this->jobTags($jobs));

        if ($isFilter && !$isAjax) {
            $this->useTemplate('List');
        }

        return $this->htmlResponse();
    }

    /**
     * Switch the Fluid template for the current action.
     *
     * TYPO3 12 TemplateView has setTemplate(); TYPO3 13/14 FluidViewAdapter
     * removed that method in favour of RenderingContext::setControllerAction().
     */
    private function useTemplate(string $templateName): void
    {
        if (method_exists($this->view, 'getRenderingContext')) {
            $this->view->getRenderingContext()->setControllerAction($templateName);

            return;
        }

        if (method_exists($this->view, 'setTemplate')) {
            $this->view->setTemplate($templateName);
        }
    }

    /**
     * @return array{q: string, location: string, employmentType: string, workplaceType: string, category: int}
     */
    private function normalizeFilter(
        string $q,
        string $location,
        string $employmentType,
        string $workplaceType,
        int $category,
    ): array {
        return [
            'q' => trim($q),
            'location' => trim($location),
            'employmentType' => trim($employmentType),
            'workplaceType' => trim($workplaceType),
            'category' => $category,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function employmentTypes(): array
    {
        return [
            'FULL_TIME' => 'job.employmentType.FULL_TIME',
            'PART_TIME' => 'job.employmentType.PART_TIME',
            'CONTRACTOR' => 'job.employmentType.CONTRACTOR',
            'TEMPORARY' => 'job.employmentType.TEMPORARY',
            'INTERN' => 'job.employmentType.INTERN',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function workplaceTypes(): array
    {
        return [
            'ONSITE' => 'job.workplaceType.ONSITE',
            'HYBRID' => 'job.workplaceType.HYBRID',
            'REMOTE' => 'job.workplaceType.REMOTE',
        ];
    }

    private function jobUrl(Job $job): string
    {
        $detailPid = (int)($this->settings['detailPid'] ?? 0);
        $uriBuilder = $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true);

        if ($detailPid > 0) {
            $uriBuilder->setTargetPageUid($detailPid);
        }

        return $uriBuilder->uriFor('show', ['job' => $job]);
    }

    /**
     * @param list<Job> $jobs
     * @return list<string>
     */
    private function jobTags(array $jobs): array
    {
        $tags = ['tx_smartjobfinder'];
        foreach ($jobs as $job) {
            $uid = (int)$job->getUid();
            if ($uid > 0) {
                $tags[] = 'tx_smartjobfinder_job_' . $uid;
            }
        }

        return $tags;
    }

    private function isAjaxRequest(): bool
    {
        return strtolower($this->request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * True when Apply is loaded and lib.smartJobApplyForm is present.
     * TYPO3 14 f:cObject throws if the TypoScript path is missing.
     */
    private function isApplyFormAvailable(): bool
    {
        if (!ExtensionManagementUtility::isLoaded('smart_job_apply')) {
            return false;
        }

        $setup = $this->frontendTypoScriptSetup();
        foreach (['lib.', 'lib'] as $libKey) {
            $lib = $setup[$libKey] ?? null;
            if (!is_array($lib)) {
                continue;
            }
            if (trim((string)($lib['smartJobApplyForm'] ?? '')) !== '') {
                return true;
            }
            if (isset($lib['smartJobApplyForm.']) && is_array($lib['smartJobApplyForm.'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function frontendTypoScriptSetup(): array
    {
        $frontendTypoScript = $this->request->getAttribute('frontend.typoscript');
        if (is_object($frontendTypoScript) && method_exists($frontendTypoScript, 'getSetupArray')) {
            try {
                $setup = $frontendTypoScript->getSetupArray();
            } catch (\Throwable) {
                $setup = [];
            }

            return is_array($setup) ? $setup : [];
        }

        $tsfe = $GLOBALS['TSFE'] ?? null;
        if (is_object($tsfe) && isset($tsfe->tmpl) && is_object($tsfe->tmpl) && is_array($tsfe->tmpl->setup ?? null)) {
            return $tsfe->tmpl->setup;
        }

        return [];
    }
}
