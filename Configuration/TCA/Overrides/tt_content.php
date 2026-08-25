<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

(static function (): void {
    $pluginSignature = ExtensionUtility::registerPlugin(
        'SmartJobFinder',
        'JobList',
        'LLL:EXT:smart_job_finder/Resources/Private/Language/locallang_db.xlf:plugin.joblist.title',
        'smart-job-finder-plugin',
        'plugins',
        'LLL:EXT:smart_job_finder/Resources/Private/Language/locallang_db.xlf:plugin.joblist.description',
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:plugin, pi_flexform',
        $pluginSignature,
        'after:header',
    );

    // CType-based plugin: first argument is list_type wildcard, third is CType.
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:smart_job_finder/Configuration/FlexForms/JobList.xml',
        $pluginSignature,
    );

    $GLOBALS['TCA']['tt_content']['types'][$pluginSignature]['previewRenderer']
        = \Agentur\SmartJobFinder\Backend\Preview\JobListPreviewRenderer::class;
})();
