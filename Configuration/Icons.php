<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'smart-job-finder-plugin' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:smart_job_finder/Resources/Public/Icons/Plugin.svg',
    ],
    'smart-job-finder-job' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:smart_job_finder/Resources/Public/Icons/Job.svg',
    ],
    'smart-job-finder-company' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:smart_job_finder/Resources/Public/Icons/Company.svg',
    ],
];
