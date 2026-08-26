<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Domain;

/**
 * Overlays job rows the way TYPO3 page overlays do: translation wins,
 * default language is the fallback unless the site language is strict.
 *
 * Pure PHP so /api/jobs can stay a cheap middleware without PageRepository.
 */
final class JobLanguageOverlay
{
    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public static function overlay(array $rows, int $languageId, bool $strict = false): array
    {
        if ($languageId <= 0) {
            return array_values(array_filter(
                $rows,
                static fn (array $row): bool => (int)($row['sys_language_uid'] ?? 0) <= 0,
            ));
        }

        $defaults = [];
        $translations = [];
        foreach ($rows as $row) {
            $language = (int)($row['sys_language_uid'] ?? 0);
            if ($language <= 0) {
                $defaults[(int)($row['uid'] ?? 0)] = $row;
                continue;
            }
            if ($language === $languageId) {
                $parent = (int)($row['l10n_parent'] ?? 0);
                if ($parent > 0) {
                    $translations[$parent] = $row;
                }
            }
        }

        $overlayed = [];
        foreach ($defaults as $uid => $row) {
            if ($uid <= 0) {
                continue;
            }
            if (isset($translations[$uid])) {
                $overlayed[] = $translations[$uid];
            } elseif (!$strict) {
                $overlayed[] = $row;
            }
        }

        return $overlayed;
    }
}
