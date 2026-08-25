<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Seo;

/**
 * Scores a job record against Google Jobs / schema.org JobPosting requirements.
 * Pure PHP so it can be unit-tested without a TYPO3 bootstrap.
 */
final class GoogleJobsReadiness
{
    /**
     * @param array<string, mixed> $record
     * @return array{score: int, missing: list<string>, passed: list<string>}
     */
    public function evaluate(array $record): array
    {
        $checks = [
            'title' => ['weight' => 20, 'ok' => trim((string)($record['title'] ?? '')) !== '', 'label' => 'title'],
            'description' => [
                'weight' => 15,
                'ok' => trim(strip_tags((string)($record['description'] ?? $record['teaser'] ?? ''))) !== '',
                'label' => 'description',
            ],
            'datePosted' => ['weight' => 10, 'ok' => (int)($record['crdate'] ?? 0) > 0, 'label' => 'datePosted'],
            'hiringOrganization' => ['weight' => 15, 'ok' => (int)($record['company'] ?? 0) > 0, 'label' => 'hiringOrganization'],
            'jobLocation' => [
                'weight' => 15,
                'ok' => trim((string)($record['location'] ?? '')) !== ''
                    || (string)($record['workplace_type'] ?? '') === 'REMOTE',
                'label' => 'jobLocation',
            ],
            'validThrough' => ['weight' => 8, 'ok' => (int)($record['valid_through'] ?? 0) > 0, 'label' => 'validThrough'],
            'employmentType' => ['weight' => 7, 'ok' => trim((string)($record['employment_type'] ?? '')) !== '', 'label' => 'employmentType'],
            'baseSalary' => [
                'weight' => 5,
                'ok' => (int)($record['salary_min'] ?? 0) > 0 || (int)($record['salary_max'] ?? 0) > 0,
                'label' => 'baseSalary',
            ],
            'identifier' => ['weight' => 5, 'ok' => trim((string)($record['slug'] ?? '')) !== '', 'label' => 'identifier'],
        ];

        $score = 0;
        $missing = [];
        $passed = [];
        foreach ($checks as $check) {
            if ($check['ok']) {
                $score += $check['weight'];
                $passed[] = $check['label'];
            } else {
                $missing[] = $check['label'];
            }
        }

        return [
            'score' => min(100, $score),
            'missing' => $missing,
            'passed' => $passed,
        ];
    }
}
