<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Smart Job Finder',
    'description' => 'Job board with TCA/IRRE, slug generation, categories, live frontend filters, JobPosting structured data and PSR-14 publish notifications (mock mail / Slack webhook).',
    'category' => 'plugin',
    'author' => 'Agentur',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-14.99.99',
            'php' => '8.1.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'workspaces' => '',
            'redirects' => '',
        ],
    ],
];
