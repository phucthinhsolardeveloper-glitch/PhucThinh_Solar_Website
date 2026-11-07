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

    public static function slug(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value, " /\t\n\r\0\x0B") : '';

        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
    }
}
