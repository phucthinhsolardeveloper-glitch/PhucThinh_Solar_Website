<?php

namespace App\Domain\Leads;

use App\Models\Product;

/** Stores a public same-site path and a small allowlist of campaign codes. */
final class LeadSource
{
    private const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign'];

    public static function normalize(mixed $input): ?string
    {
        if (! is_string($input) || $input === '' || strlen($input) > 2048 || str_starts_with($input, '//') || str_contains($input, '\\')
            || preg_match('/[\x00-\x1f\x7f]/', $input)) {
            return null;
        }
        $parts = parse_url($input);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])
            || isset($parts['user']) || isset($parts['fragment'])) {
            return null;
        }
        $path = $parts['path'] ?? '';
        if (! preg_match('#^/(?:[A-Za-z0-9._-]+(?:/[A-Za-z0-9._-]+)*/?)?$#', $path)
            || preg_match('#(?:^|/)\.{1,2}(?:/|$)#', $path)
            || preg_match('#^/(?:admin|api|_api|upload|thumbs|livewire|health|xem-truoc)(?:/|$)#i', $path)
            || strlen($path) > 255) {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $params);
        $campaign = [];
        foreach (self::UTM_KEYS as $key) {
            $value = $params[$key] ?? null;
            if (is_string($value) && preg_match('/^[A-Za-z0-9._-]{1,60}$/', $value)) {
                $campaign[$key] = $value;
            }
        }
        $query = http_build_query($campaign, '', '&', PHP_QUERY_RFC3986);
        $source = $path.($query !== '' ? '?'.$query : '');

        return strlen($source) <= 255 ? $source : $path;
    }

    public static function productId(?string $source): ?int
    {
        if (! $source || ! preg_match('#^/san-pham/([a-z0-9]+(?:-[a-z0-9]+)*)/?(?:\?|$)#', $source, $match)) {
            return null;
        }

        $id = Product::query()->where('slug', $match[1])->where('is_visible', true)->value('id');

        return $id ? (int) $id : null;
    }
}
