<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Seo;

use Agentur\SmartJobFinder\Domain\Model\Job;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;

final class JobFrontendSeoWriter
{
    public function __construct(
        private readonly JobPageTitleProvider $pageTitleProvider,
        private readonly MetaTagManagerRegistry $metaTagManagerRegistry,
    ) {}

    public function apply(Job $job, string $canonicalUrl = ''): void
    {
        $title = $job->getTitle();
        if ($title !== '') {
            $this->pageTitleProvider->setTitle($title);
            $this->addMeta('og:title', $title);
            $this->addMeta('twitter:title', $title);
        }

        $description = $this->plainText($job->getTeaser() !== '' ? $job->getTeaser() : $job->getDescription());
        if ($description !== '') {
            $short = mb_substr($description, 0, 160);
            $this->addMeta('description', $short);
            $this->addMeta('og:description', $short);
            $this->addMeta('twitter:description', $short);
        }

        $this->addMeta('og:type', 'article');
        $this->addMeta('twitter:card', 'summary');

        if ($canonicalUrl !== '') {
            $this->addMeta('og:url', $canonicalUrl);
        }
    }

    private function addMeta(string $property, string $content): void
    {
        $manager = $this->metaTagManagerRegistry->getManagerForProperty($property);
        $manager->addProperty($property, $content);
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
