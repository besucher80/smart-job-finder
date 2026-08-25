<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Service;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Adds frontend page-cache tags on TYPO3 12 (TSFE) and 13/14 (AddCacheTagEvent).
 */
final class FrontendCacheTagger
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @param list<string> $tags
     */
    public function add(array $tags): void
    {
        $tags = array_values(array_unique(array_filter($tags)));
        if ($tags === []) {
            return;
        }

        $cacheTagClass = 'TYPO3\\CMS\\Core\\Cache\\CacheTag';
        $eventClass = 'TYPO3\\CMS\\Core\\Cache\\Event\\AddCacheTagEvent';
        if (class_exists($cacheTagClass) && class_exists($eventClass)) {
            foreach ($tags as $tag) {
                $this->eventDispatcher->dispatch(
                    new $eventClass(new $cacheTagClass($tag)),
                );
            }
            return;
        }

        $tsfe = $GLOBALS['TSFE'] ?? null;
        if (is_object($tsfe) && method_exists($tsfe, 'addCacheTags')) {
            $tsfe->addCacheTags($tags);
        }
    }
}
