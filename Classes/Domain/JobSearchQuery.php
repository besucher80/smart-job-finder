<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Domain;

/**
 * Builds a MySQL BOOLEAN MODE expression from a user search string.
 * NATURAL LANGUAGE MODE is unusable on small catalogs: words in ≥50% of rows
 * are treated as stopwords and return zero hits.
 */
final class JobSearchQuery
{
    public static function booleanExpression(string $search): string
    {
        $tokens = preg_split('/\s+/u', trim($search)) ?: [];
        $parts = [];
        foreach ($tokens as $token) {
            $token = preg_replace('/[+\-><()~*"\\\\@]+/u', '', $token) ?? '';
            $token = trim($token);
            if ($token === '' || mb_strlen($token) < 2) {
                continue;
            }
            $parts[] = '+' . $token . '*';
        }

        return implode(' ', $parts);
    }

    public static function likeNeedle(string $search): string
    {
        return '%' . addcslashes(trim($search), '%_\\') . '%';
    }

    /**
     * Substring match, plus a tight Levenshtein check on title words
     * so "developer" still finds a mistyped "Develoer".
     */
    public static function titleMatches(string $title, string $search): bool
    {
        $needle = mb_strtolower(trim($search));
        $haystack = mb_strtolower(trim($title));
        if ($needle === '' || $haystack === '') {
            return false;
        }
        if (str_contains($haystack, $needle)) {
            return true;
        }

        $maxDistance = self::fuzzyDistance($needle);
        if ($maxDistance === 0) {
            return false;
        }

        foreach (preg_split('/[^\p{L}\p{N}]+/u', $haystack) ?: [] as $word) {
            if ($word === '' || abs(strlen($word) - strlen($needle)) > $maxDistance) {
                continue;
            }
            if (strlen($word) > 255 || strlen($needle) > 255) {
                continue;
            }
            if (levenshtein($word, $needle) <= $maxDistance) {
                return true;
            }
        }

        return false;
    }

    private static function fuzzyDistance(string $needle): int
    {
        $length = mb_strlen($needle);

        return match (true) {
            $length >= 8 => 2,
            $length >= 5 => 1,
            default => 0,
        };
    }
}
