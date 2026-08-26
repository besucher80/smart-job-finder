<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Seo;

use Agentur\SmartJobFinder\Domain\JobVisibility;
use TYPO3\CMS\Seo\XmlSitemap\RecordsXmlSitemapDataProvider;

/**
 * Official RecordsXmlSitemapDataProvider plus `valid_through` — enable-fields
 * alone would keep expired jobs in /sitemap.xml until the scheduler hides them.
 *
 * Instantiated by EXT:seo from TypoScript, not by DI.
 */
final class JobXmlSitemapDataProvider extends RecordsXmlSitemapDataProvider
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $now = time();
        $this->items = array_values(array_filter(
            $this->items,
            static fn (array $item): bool => JobVisibility::isPubliclyVisible($item['data'] ?? [], $now),
        ));
    }
}
