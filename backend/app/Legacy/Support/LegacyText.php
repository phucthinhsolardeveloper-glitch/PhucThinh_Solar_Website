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

    /** Vietnamese text to an ASCII slug, exactly as the PHP $func->changeTitle() does (used for search and matching). */
    public static function changeTitle(string $text): string
    {
        if ($text === '') {
            return '';
        }
        $map = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ|Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'd' => 'đ|Đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ|É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'i' => 'í|ì|ỉ|ĩ|ị|Í|Ì|Ỉ|Ĩ|Ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ|Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự|Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ|Ý|Ỳ|Ỷ|Ỹ|Ỵ',
            '' => '`|\~|\!|\@|\#|\||\$|\%|\^|\&|\*|\(|\)|\+|\=|\,|\.|\/|\?|\>|\<|\'|\"|\“|\”|\:|\;|_',
        ];
        foreach ($map as $ascii => $pattern) {
            $text = (string) preg_replace("/({$pattern})/i", (string) $ascii, $text);
        }
        $text = strtolower($text);
        $text = (string) preg_replace('/[^a-z0-9-\s]/', '', $text);
        $text = (string) preg_replace('/([\s]+)/', '-', $text);
        $text = str_replace(['%20', ' '], '-', $text);
        foreach (['/\-\-\-\-\-/', '/\-\-\-\-/', '/\-\-\-/', '/\-\-/'] as $dashes) {
            $text = (string) preg_replace($dashes, '-', $text);
        }

        return (string) preg_replace('/\@\-|\-\@|\@/', '', '@'.$text.'@');
    }
}
