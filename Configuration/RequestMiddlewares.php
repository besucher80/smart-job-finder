<?php

declare(strict_types=1);

use Agentur\SmartJobFinder\Middleware\JobApiMiddleware;

return [
    'frontend' => [
        'agentur/smart-job-finder/api' => [
            'target' => JobApiMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
