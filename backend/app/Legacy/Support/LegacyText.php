<?php

namespace App\Legacy\Support;

/** Text clean-up helpers shared by the public DTOs (ptsPdpText, ptsPdpContentText, ptsPdpSeoText). */
final class LegacyText
{
    /** One line of plain text: entities decoded, tags stripped, whitespace collapsed. */
    public static function plain(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return trim((string) preg_replace('/\s+/u', ' ', strip_tags(htmlspecialchars_decode((string) $value, ENT_QUOTES))));
    }

    /** Multi-line editorial text: line endings normalised, control characters removed. */
    public static function content(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $text = str_replace(["\r\n", "\r"], "\n", (string) $value);

        return trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text));
    }

    public static function seo(mixed $value): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', self::plain($value)));
    }

    /** Rich text flattened to one line: entities decoded, block tags become spaces (ptsPublicNavText, ptsPublicSeoText). */
    public static function flat(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '') {
            return '';
        }
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = (string) preg_replace('~<(?:br\s*/?|/?p|/?div|/?li)\b[^>]*>~i', ' ', $value);

        return trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', strip_tags($value)));
    }

    /** Plain text cut at a word boundary with an ellipsis, as the PHP site API does for short labels. */
    public static function excerpt(mixed $value, int $limit): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8'))));
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        $cut = mb_substr($value, 0, $limit);
        $space = strrpos($cut, ' ');
        if ($space !== false && $space > strlen($cut) / 2) {
            $cut = substr($cut, 0, $space);
        }

        return rtrim($cut, ' ,.;:-').'…';
    }

    public static function slug(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value, " /\t\n\r\0\x0B") : '';

        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
    }
}
