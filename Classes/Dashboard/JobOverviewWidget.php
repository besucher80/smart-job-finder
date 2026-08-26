<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Dashboard;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * Dashboard widget aligned with TYPO3 12–14 Core widgets (Fluid layout Widget/Widget,
 * widget-table, footer CTA). WidgetRendererInterface is TYPO3 14-only; the legacy
 * WidgetInterface is still mapped to WidgetResult in v14.
 */
final class JobOverviewWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly JobOverviewDataProvider $dataProvider,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly ?ButtonProviderInterface $buttonProvider = null,
        private readonly array $options = [],
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->backendViewFactory->create($this->request, [
            'typo3/cms-dashboard',
            'agentur/smart-job-finder',
        ]);
        $view->assignMultiple([
            'stats' => $this->dataProvider->getStats(),
            'options' => $this->options,
            'button' => $this->buttonProvider,
            'configuration' => $this->configuration,
        ]);

        return $view->render('Widget/JobOverview');
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
