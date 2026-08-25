<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Job extends AbstractEntity
{
    protected string $title = '';
    protected string $slug = '';
    protected string $teaser = '';
    protected string $description = '';
    protected string $department = '';
    protected string $location = '';
    protected string $locationCountry = 'DE';
    protected string $employmentType = 'FULL_TIME';
    protected string $workplaceType = 'ONSITE';
    protected int $salaryMin = 0;
    protected int $salaryMax = 0;
    protected string $salaryCurrency = 'EUR';
    protected string $salaryInterval = 'YEAR';
    protected int $validThrough = 0;
    protected string $applicationUrl = '';
    protected string $contactEmail = '';
    protected bool $featured = false;
    protected int $crdate = 0;
    #[Lazy]
    protected ?Company $company = null;

    /**
     * @var ObjectStorage<Requirement>
     */
    #[Lazy]
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $requirements;

    /**
     * @var ObjectStorage<Benefit>
     */
    #[Lazy]
    #[Cascade(['value' => 'remove'])]
    protected ObjectStorage $benefits;

    /**
     * @var ObjectStorage<Category>
     */
    #[Lazy]
    protected ObjectStorage $categories;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->requirements = new ObjectStorage();
        $this->benefits = new ObjectStorage();
        $this->categories = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getTeaser(): string
    {
        return $this->teaser;
    }

    public function setTeaser(string $teaser): void
    {
        $this->teaser = $teaser;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDepartment(): string
    {
        return $this->department;
    }

    public function setDepartment(string $department): void
    {
        $this->department = $department;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getLocationCountry(): string
    {
        return $this->locationCountry;
    }

    public function setLocationCountry(string $locationCountry): void
    {
        $this->locationCountry = $locationCountry;
    }

    public function getEmploymentType(): string
    {
        return $this->employmentType;
    }

    public function setEmploymentType(string $employmentType): void
    {
        $this->employmentType = $employmentType;
    }

    public function getWorkplaceType(): string
    {
        return $this->workplaceType;
    }

    public function setWorkplaceType(string $workplaceType): void
    {
        $this->workplaceType = $workplaceType;
    }

    public function getSalaryMin(): int
    {
        return $this->salaryMin;
    }

    public function setSalaryMin(int $salaryMin): void
    {
        $this->salaryMin = $salaryMin;
    }

    public function getSalaryMax(): int
    {
        return $this->salaryMax;
    }

    public function setSalaryMax(int $salaryMax): void
    {
        $this->salaryMax = $salaryMax;
    }

    public function getSalaryCurrency(): string
    {
        return $this->salaryCurrency;
    }

    public function setSalaryCurrency(string $salaryCurrency): void
    {
        $this->salaryCurrency = $salaryCurrency;
    }

    public function getSalaryInterval(): string
    {
        return $this->salaryInterval;
    }

    public function setSalaryInterval(string $salaryInterval): void
    {
        $this->salaryInterval = $salaryInterval;
    }

    public function getValidThrough(): int
    {
        return $this->validThrough;
    }

    public function setValidThrough(int $validThrough): void
    {
        $this->validThrough = $validThrough;
    }

    public function getApplicationUrl(): string
    {
        return $this->applicationUrl;
    }

    public function setApplicationUrl(string $applicationUrl): void
    {
        $this->applicationUrl = $applicationUrl;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void
    {
        $this->featured = $featured;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }

    /**
     * @return ObjectStorage<Requirement>
     */
    public function getRequirements(): ObjectStorage
    {
        return $this->requirements;
    }

    /**
     * @param ObjectStorage<Requirement> $requirements
     */
    public function setRequirements(ObjectStorage $requirements): void
    {
        $this->requirements = $requirements;
    }

    /**
     * @return ObjectStorage<Benefit>
     */
    public function getBenefits(): ObjectStorage
    {
        return $this->benefits;
    }

    /**
     * @param ObjectStorage<Benefit> $benefits
     */
    public function setBenefits(ObjectStorage $benefits): void
    {
        $this->benefits = $benefits;
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    /**
     * @param ObjectStorage<Category> $categories
     */
    public function setCategories(ObjectStorage $categories): void
    {
        $this->categories = $categories;
    }

    public function getCrdate(): int
    {
        return $this->crdate;
    }

    public function setCrdate(int $crdate): void
    {
        $this->crdate = $crdate;
    }

    public function getDatePosted(): int
    {
        return $this->crdate;
    }
}
