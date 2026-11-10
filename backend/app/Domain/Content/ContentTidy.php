<?php

namespace App\Domain\Content;

/**
 * Cleans the markup the PHP editor left behind in stored content.
 *
 * Four things it does, and why each is safe:
 *
 *  - `style=` and `class=` never reach a visitor — RichText::sanitize drops both — so keeping them
 *    only bloats the row and lies to whoever opens the editor. They go.
 *  - An image sized with `style="width:750px;height:1111px"` therefore renders with no size at all,
 *    and the page jumps while it loads. The numbers move into real width/height attributes first.
 *  - A link or picture written as `http://phucthinhsolar.com/upload/…` leaves the site to come back
 *    to it: an extra redirect, http on an https page, and a full page load instead of in-app
 *    navigation. Same-site addresses become relative.
 *  - `<p>&nbsp;</p>` is the editor's way of saying nothing; it leaves a gap nobody asked for.
 *
 * Idempotent: running it twice changes nothing the second time.
 */
final class ContentTidy
{
    /** What the last clean() changed, for the report. */
    public const COUNTERS = ['absolute_link', 'image_size', 'style_attr', 'class_attr', 'empty_paragraph'];

    /**
     * @param  list<string>  $hosts  site hostnames whose absolute addresses become relative
     * @return array{html: string, changes: array<string, int>}
     */
    public static function clean(string $html, array $hosts): array
    {
        $changes = array_fill_keys(self::COUNTERS, 0);
        if (trim($html) === '') {
            return ['html' => $html, 'changes' => $changes];
        }

        // 1. Same-site absolute addresses become relative, in href and src alike.
        $pattern = '~\b(href|src)="https?://(?:www\.)?(?:'.implode('|', array_map('preg_quote', $hosts)).')(/[^"]*)"~i';
        $html = (string) preg_replace_callback($pattern, function (array $m) use (&$changes) {
            $changes['absolute_link']++;

            return $m[1].'="'.$m[2].'"';
        }, $html);

        // 2. An image's inline pixel size becomes the attributes the browser reserves space with.
        $html = (string) preg_replace_callback('~<img\b[^>]*>~i', function (array $m) use (&$changes) {
            $tag = $m[0];
            if (! preg_match('~\bstyle="([^"]*)"~i', $tag, $style)) {
                return $tag;
            }
            $width = preg_match('~\bwidth\s*:\s*(\d+)\s*px~i', $style[1], $w) ? $w[1] : null;
            $height = preg_match('~\bheight\s*:\s*(\d+)\s*px~i', $style[1], $h) ? $h[1] : null;
            foreach (['width' => $width, 'height' => $height] as $name => $value) {
                if ($value !== null && ! preg_match('~\b'.$name.'="~i', $tag)) {
                    $tag = preg_replace('~<img\b~i', '<img '.$name.'="'.$value.'"', $tag, 1) ?? $tag;
                    $changes['image_size']++;
                }
            }

            return $tag;
        }, $html);

        // 3. Presentation attributes the sanitiser already refuses.
        $html = (string) preg_replace_callback('~\s(style|class)="[^"]*"~i', function (array $m) use (&$changes) {
            $changes[strtolower($m[1]) === 'style' ? 'style_attr' : 'class_attr']++;

            return '';
        }, $html);

        // 4. Paragraphs holding nothing but spacing.
        $html = (string) preg_replace_callback('~<p>(?:\s|&nbsp;|<br\s*/?>)*</p>~i', function () use (&$changes) {
            $changes['empty_paragraph']++;

            return '';
        }, $html);

        return ['html' => $html, 'changes' => $changes];
    }

    /** Site-relative upload paths a piece of content points at, for checking the files are really there. */
    public static function uploadPaths(string $html): array
    {
        preg_match_all('~(?:href|src)="(/upload/[^"]+)"~i', $html, $matches);

        return array_values(array_unique($matches[1]));
    }
}
