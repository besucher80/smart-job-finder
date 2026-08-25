<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Seo;

use Agentur\SmartJobFinder\Domain\Model\Job;

/**
 * Builds schema.org JobPosting JSON-LD from a job record.
 *
 * @see https://schema.org/JobPosting
 */
final class JobPostingJsonLdBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Job $job, string $canonicalUrl = ''): array
    {
        $data = [
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => $job->getTitle(),
            'description' => $this->plainText($job->getDescription() !== '' ? $job->getDescription() : $job->getTeaser()),
            'datePosted' => $this->isoDate($job->getDatePosted()),
            'employmentType' => $job->getEmploymentType() !== '' ? $job->getEmploymentType() : 'FULL_TIME',
        ];

        if ($canonicalUrl !== '') {
            $data['url'] = $canonicalUrl;
        }

        if ($job->getValidThrough() > 0) {
            $data['validThrough'] = $this->isoDate($job->getValidThrough());
        }

        if ($job->getDepartment() !== '') {
            $data['industry'] = $job->getDepartment();
        }

        $organization = $this->buildOrganization($job);
        if ($organization !== []) {
            $data['hiringOrganization'] = $organization;
        }

        $workplace = $job->getWorkplaceType();
        if ($workplace === 'REMOTE') {
            $data['jobLocationType'] = 'TELECOMMUTE';
            $data['applicantLocationRequirements'] = [
                '@type' => 'Country',
                'name' => $job->getLocationCountry() !== '' ? $job->getLocationCountry() : 'DE',
            ];
        }

        if ($workplace !== 'REMOTE' || $job->getLocation() !== '') {
            $data['jobLocation'] = $this->buildJobLocation($job);
        }

        $baseSalary = $this->buildBaseSalary($job);
        if ($baseSalary !== []) {
            $data['baseSalary'] = $baseSalary;
        }

        $identifier = $job->getSlug() !== '' ? $job->getSlug() : (string)($job->getUid() ?? '');
        if ($identifier !== '') {
            $data['identifier'] = [
                '@type' => 'PropertyValue',
                'name' => 'Smart Job Finder',
                'value' => $identifier,
            ];
        }

        return $data;
    }

    /**
     * @param iterable<Job> $jobs
     * @return array<string, mixed>
     */
    public function buildItemList(iterable $jobs, callable $urlBuilder): array
    {
        $elements = [];
        $position = 1;
        foreach ($jobs as $job) {
            $url = (string)$urlBuilder($job);
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'url' => $url,
                'name' => $job->getTitle(),
            ];
            $position++;
        }

        return [
            '@context' => 'https://schema.org/',
            '@type' => 'ItemList',
            'itemListElement' => $elements,
            'numberOfItems' => count($elements),
        ];
    }

    public function encode(array $data): string
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOrganization(Job $job): array
    {
        $company = $job->getCompany();
        if ($company === null) {
            return [];
        }

        $organization = [
            '@type' => 'Organization',
            'name' => $company->getName(),
        ];

        $website = $this->typoLinkUrl($company->getWebsite());
        if ($website !== '') {
            $organization['sameAs'] = $website;
        }

        $logo = $company->getLogo();
        if ($logo !== null) {
            try {
                $original = $logo->getOriginalResource();
                $publicUrl = $original->getPublicUrl();
                if (is_string($publicUrl) && $publicUrl !== '') {
                    $organization['logo'] = $publicUrl;
                }
            } catch (\Throwable) {
                // Logo optional in structured data.
            }
        }

        return $organization;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildJobLocation(Job $job): array
    {
        $address = [
            '@type' => 'PostalAddress',
        ];

        if ($job->getLocation() !== '') {
            $address['addressLocality'] = $job->getLocation();
        }

        $address['addressCountry'] = $job->getLocationCountry() !== '' ? $job->getLocationCountry() : 'DE';

        return [
            '@type' => 'Place',
            'address' => $address,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBaseSalary(Job $job): array
    {
        if ($job->getSalaryMin() <= 0 && $job->getSalaryMax() <= 0) {
            return [];
        }

        $value = [
            '@type' => 'QuantitativeValue',
            'unitText' => $job->getSalaryInterval() !== '' ? $job->getSalaryInterval() : 'YEAR',
        ];

        if ($job->getSalaryMin() > 0) {
            $value['minValue'] = $job->getSalaryMin();
        }
        if ($job->getSalaryMax() > 0) {
            $value['maxValue'] = $job->getSalaryMax();
        }
        if ($job->getSalaryMin() > 0 && $job->getSalaryMax() === 0) {
            $value['value'] = $job->getSalaryMin();
        }

        return [
            '@type' => 'MonetaryAmount',
            'currency' => $job->getSalaryCurrency() !== '' ? $job->getSalaryCurrency() : 'EUR',
            'value' => $value,
        ];
    }

    private function isoDate(int $timestamp): string
    {
        if ($timestamp <= 0) {
            return gmdate('Y-m-d');
        }

        return gmdate('c', $timestamp);
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function typoLinkUrl(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (str_starts_with($value, 't3://')) {
            return '';
        }

        return $value;
    }
}
