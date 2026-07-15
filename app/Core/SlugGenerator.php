<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Generates a URL-safe slug from a (possibly Vietnamese, diacritic-heavy)
 * title. Shared by Course/Lesson/Article/Forum modules — never
 * reimplement this per-module (per CODING_STANDARDS.md's "never
 * duplicate business logic").
 */
final class SlugGenerator
{
    public static function generate(string $title): string
    {
        $ascii = VietnameseTextNormalizer::stripDiacritics($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';

        return trim($slug, '-');
    }

    /**
     * Appends a numeric suffix until $existsCheck(slug) returns false.
     * @param callable(string): bool $existsCheck
     */
    public static function unique(string $baseSlug, callable $existsCheck): string
    {
        $slug = $baseSlug;
        $attempt = 1;

        while ($existsCheck($slug)) {
            $attempt++;
            $slug = "{$baseSlug}-{$attempt}";
        }

        return $slug;
    }
}
