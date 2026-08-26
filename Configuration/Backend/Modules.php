<?php

declare(strict_types=1);

use Agentur\SmartJobFinder\Controller\Backend\OverviewController;

return [
    'web_smartjobfinder' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/smart-job-finder',
        'iconIdentifier' => 'smart-job-finder-plugin',
        'labels' => 'LLL:EXT:smart_job_finder/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => OverviewController::class . '::handleRequest',
            ],
        ],
    ],
];
