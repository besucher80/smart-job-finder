<?php

declare(strict_types=1);

use Agentur\SmartJobFinder\Compatibility\PluginRegistration;
use Agentur\SmartJobFinder\Controller\JobController;
use Agentur\SmartJobFinder\Hook\JobPublishDataHandlerHook;

defined('TYPO3') or die();

(static function (): void {
    PluginRegistration::configure(
        'SmartJobFinder',
        'JobList',
        [JobController::class => 'list, show, filter'],
        [JobController::class => 'filter'],
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['smart_job_finder']
        = JobPublishDataHandlerHook::class;

    $excluded = [
        'tx_smartjobfinder_joblist[q]',
        'tx_smartjobfinder_joblist[location]',
        'tx_smartjobfinder_joblist[employmentType]',
        'tx_smartjobfinder_joblist[workplaceType]',
        'tx_smartjobfinder_joblist[category]',
        'tx_smartjobfinder_joblist[action]',
        'tx_smartjobfinder_joblist[page]',
    ];
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = array_merge(
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] ?? [],
        $excluded,
    );

    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][705] = 'EXT:smart_job_finder/Resources/Private/Templates/Email/';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'][705] = 'EXT:smart_job_finder/Resources/Private/Partials/Email/';

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['smart_job_finder'] ??= [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
        'options' => [
            'defaultLifetime' => 60,
        ],
        'groups' => ['pages', 'all'],
    ];
})();
