<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Single source of truth for stripping Vietnamese diacritics down to
 * plain ASCII. Used by SlugGenerator (URL slugs), ChemistryCompoundService
 * (typo-tolerant alias search), and the chemistry seeder (must produce
 * results identical to what this class returns, since aliases are looked
 * up by exact match against the normalized form stored at seed time).
 *
 * Do not reimplement this map anywhere else — every prior duplicate
 * (there were three) has been consolidated into this class.
 */
final class VietnameseTextNormalizer
{
    private const DIACRITIC_MAP = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
    ];

    /**
     * Lowercase + strip diacritics (e.g. "Hóa học" -> "hoa hoc"). Does
     * NOT remove spaces or punctuation — callers that need a URL slug
     * should use SlugGenerator, which builds on top of this.
     */
    public static function stripDiacritics(string $text): string
    {
        return strtr(mb_strtolower($text), self::DIACRITIC_MAP);
    }
}
