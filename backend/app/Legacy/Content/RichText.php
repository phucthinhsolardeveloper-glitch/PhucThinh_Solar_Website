<?php

namespace App\Legacy\Content;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Sanitiser for CKEditor HTML leaving the CMS (pts_public_richtext.php, sanitizer version 1).
 * Fails closed: unknown tags are unwrapped, dangerous ones dropped, links and images normalised to
 * site paths or safe absolute URLs, every other attribute checked against a per-tag list.
 */
final class RichText
{
    public const VERSION = 1;

    private const ALLOWED = ['h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'mark', 'sub', 'sup',
        'a', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'figure', 'figcaption', 'img'];

    private const BLOCKED = ['applet', 'audio', 'base', 'button', 'canvas', 'embed', 'form', 'head', 'iframe', 'input', 'link', 'math', 'meta',
        'noscript', 'object', 'option', 'script', 'select', 'source', 'style', 'svg', 'template', 'textarea', 'title', 'track', 'video'];

    private const ATTRIBUTES = [
        'h2' => ['id', 'dir'], 'h3' => ['id', 'dir'], 'h4' => ['id', 'dir'], 'h5' => ['id', 'dir'], 'h6' => ['id', 'dir'],
        'p' => ['id'],
        'a' => ['href', 'title', 'target', 'rel'],
        'ol' => ['start', 'reversed', 'type'],
        'li' => ['value'],
        'blockquote' => ['cite'],
        'th' => ['colspan', 'rowspan', 'scope', 'headers'],
        'td' => ['colspan', 'rowspan', 'headers'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'decoding', 'aria-describedby'],
    ];

    /** The DTO wrapper Nuxt expects for sanitised HTML. */
    public static function dto(string $html): array
    {
        return ['format' => 'sanitized-html', 'html' => $html, 'sanitizedBy' => 'pts_public_richtext', 'sanitizerVersion' => self::VERSION];
    }

    public static function sanitize(mixed $value): string
    {
        $html = trim(is_scalar($value) ? htmlspecialchars_decode((string) $value, ENT_QUOTES | ENT_HTML5) : '');
        if ($html === '') {
            return '';
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"?><!DOCTYPE html><html><body><div id="pts-public-richtext-root">'.$html.'</div></body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return '';
        }
        $roots = (new DOMXPath($document))->query('//*[@id="pts-public-richtext-root"]');
        if (! $roots || $roots->length !== 1) {
            return '';
        }
        $root = $roots->item(0);
        foreach (iterator_to_array($root->childNodes) as $child) {
            self::node($child);
        }
        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    public static function href(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $value = trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($value === '' || preg_match('/[\x00-\x20\x7F<>"]/', $value) || str_contains($value, '\\')) {
            return '';
        }
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $value)) {
            $parts = parse_url($value);
            if (! is_array($parts)) {
                return '';
            }
            $scheme = strtolower((string) ($parts['scheme'] ?? ''));
            if (in_array($scheme, ['mailto', 'tel'], true)) {
                return $value;
            }
            if (! in_array($scheme, ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || empty($parts['host'])) {
                return '';
            }
            if (self::isCmsHost($parts['host'])) {
                return self::internalPath((string) ($parts['path'] ?? '/').self::suffix($parts));
            }

            return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
        }
        if ($value[0] === '#') {
            return preg_match('/^#[A-Za-z0-9_.:-]+$/', $value) ? $value : '';
        }

        return self::internalPath($value);
    }

    public static function imageUrl(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $value = trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($value === '' || preg_match('/[\x00-\x20\x7F<>"]/', $value) || str_contains($value, '\\') || str_starts_with($value, '//')) {
            return '';
        }
        $parts = parse_url($value);
        if (! is_array($parts)) {
            return '';
        }
        if (isset($parts['scheme']) && (! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)
            || ! self::isCmsHost($parts['host'] ?? '') || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']))) {
            return '';
        }
        $path = '/'.ltrim((string) ($parts['path'] ?? ''), '/');
        if (self::unsafePath($path) || ! preg_match('#^/upload(?:/[A-Za-z0-9._~%-]+)+\.(?:jpe?g|png|gif|webp|avif)$#i', $path)) {
            return '';
        }

        return $path;
    }

    private static function node(DOMNode $node): void
    {
        $parent = $node->parentNode;
        if (! $parent) {
            return;
        }
        if ($node->nodeType === XML_COMMENT_NODE) {
            $parent->removeChild($node);

            return;
        }
        if (! $node instanceof DOMElement) {
            if ($node->nodeType !== XML_TEXT_NODE) {
                $parent->removeChild($node);
            }

            return;
        }
        $tag = strtolower($node->nodeName);
        if (in_array($tag, self::BLOCKED, true)) {
            $parent->removeChild($node);

            return;
        }
        if (! in_array($tag, self::ALLOWED, true)) {
            while ($node->firstChild) {
                $child = $node->firstChild;
                $parent->insertBefore($child, $node);
                self::node($child);
            }
            $parent->removeChild($node);

            return;
        }
        self::attributes($node, $tag);
        if ($tag === 'img' && ! $node->hasAttribute('src')) {
            $parent->removeChild($node);

            return;
        }
        foreach (iterator_to_array($node->childNodes) as $child) {
            self::node($child);
        }
    }

    private static function attributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ATTRIBUTES[$tag] ?? [];
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            if (! in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);

                continue;
            }
            $value = $attribute->value;
            $safe = match (true) {
                $name === 'id' => preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $value) ? $value : '',
                $name === 'dir' => in_array(strtolower($value), ['ltr', 'rtl', 'auto'], true) ? strtolower($value) : '',
                $name === 'href', $name === 'cite' => self::href($value),
                $name === 'src' => self::imageUrl($value),
                in_array($name, ['width', 'height', 'colspan', 'rowspan', 'start', 'value'], true) => self::integer($value),
                $name === 'loading' => in_array(strtolower($value), ['lazy', 'eager'], true) ? strtolower($value) : '',
                $name === 'decoding' => in_array(strtolower($value), ['async', 'auto', 'sync'], true) ? strtolower($value) : '',
                $name === 'target' => in_array($value, ['_blank', '_self'], true) ? $value : '',
                $name === 'rel', $name === 'headers', $name === 'aria-describedby' => self::tokens($value),
                $name === 'scope' => in_array(strtolower($value), ['col', 'row', 'colgroup', 'rowgroup'], true) ? strtolower($value) : '',
                $name === 'type' => in_array($value, ['1', 'a', 'A', 'i', 'I'], true) ? $value : '',
                $name === 'reversed' => '',
                default => $value,
            };
            if ($safe === '') {
                $element->removeAttributeNode($attribute);
            } else {
                $element->setAttribute($name, $safe);
            }
        }
        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', self::tokens($element->getAttribute('rel').' noopener noreferrer'));
        }
    }

    private static function internalPath(string $value): string
    {
        $value = trim($value);
        if ($value === '' || str_starts_with($value, '//') || self::unsafePath($value)) {
            return '';
        }
        if ($value[0] !== '/') {
            $value = '/'.$value;
        }
        $parts = parse_url($value);
        if (! is_array($parts) || ! isset($parts['path'])) {
            return '';
        }
        $path = '/'.ltrim((string) $parts['path'], '/');

        return self::unsafePath($path) ? '' : $path.self::suffix($parts);
    }

    private static function unsafePath(string $path): bool
    {
        return $path === '' || preg_match('/[\x00-\x20\x7F<>"]/', $path) || str_contains($path, '\\') || str_contains(rawurldecode($path), '..');
    }

    private static function suffix(array $parts): string
    {
        return (($parts['query'] ?? '') !== '' ? '?'.$parts['query'] : '').(($parts['fragment'] ?? '') !== '' ? '#'.$parts['fragment'] : '');
    }

    private static function isCmsHost(mixed $host): bool
    {
        return in_array(strtolower((string) $host), ['phucthinhsolar.com', 'www.phucthinhsolar.com'], true);
    }

    private static function integer(string $value): string
    {
        return preg_match('/^\d+$/', $value) && (int) $value >= 1 && (int) $value <= 100 ? (string) (int) $value : '';
    }

    private static function tokens(string $value): string
    {
        $safe = array_filter(preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [], fn ($t) => (bool) preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $t));

        return implode(' ', array_values(array_unique($safe)));
    }
}
