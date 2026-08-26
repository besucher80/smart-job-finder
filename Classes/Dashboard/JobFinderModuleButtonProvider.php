<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Dashboard;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;

/**
 * Footer CTA to the Job Finder backend module (same pattern as Core ButtonProvider).
 */
final class JobFinderModuleButtonProvider implements ButtonProviderInterface
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly string $title,
        private readonly string $route = 'web_smartjobfinder',
        private readonly string $target = '',
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLink(): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute($this->route);
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
