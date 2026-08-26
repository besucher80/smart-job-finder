<?php

declare(strict_types=1);

use Agentur\SmartJobFinder\Dashboard\JobFinderModuleButtonProvider;
use Agentur\SmartJobFinder\Dashboard\JobOverviewDataProvider;
use Agentur\SmartJobFinder\Dashboard\JobOverviewWidget;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Dashboard\WidgetRegistry;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder): void {
    // Do not call ExtensionManagementUtility::isLoaded() here: PackageManager is not
    // initialized during container compile (composer asset:publish / cache:warmup).
    if (!class_exists(WidgetRegistry::class) || !$containerBuilder->hasDefinition(WidgetRegistry::class)) {
        return;
    }

    $services = $configurator->services();
    $lll = 'LLL:EXT:smart_job_finder/Resources/Private/Language/locallang_be.xlf:';

    $services->set(JobOverviewDataProvider::class)
        ->autowire()
        ->autoconfigure();

    $services->set(JobFinderModuleButtonProvider::class)
        ->autowire()
        ->arg('$title', $lll . 'widget.overview.button');

    $services->set(JobOverviewWidget::class)
        ->autowire()
        ->arg('$buttonProvider', service(JobFinderModuleButtonProvider::class))
        ->arg('$options', ['refreshAvailable' => true])
        ->tag('dashboard.widget', [
            'identifier' => 'smartJobFinderOverview',
            'groupNames' => 'general',
            'title' => $lll . 'widget.overview.title',
            'description' => $lll . 'widget.overview.description',
            'iconIdentifier' => 'content-widget-number',
            'height' => 'medium',
            'width' => 'small',
        ]);
};
