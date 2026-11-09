<?php

namespace App\Support;

/**
 * Decoding of the JSON block lists the old Admin stored HTML-escaped; used by the one-time import.
 */
final class ContentBlocks
{
    /**
     * Blocks are arrays with a non-empty "type"; anything else is dropped.
     *
     * @return list<array<string, mixed>>
     */
    public static function decode(?string $value): array
    {
        if ($value === null || $value === '' || $value === '0') {
            return [];
        }
        $decoded = json_decode(htmlspecialchars_decode($value, ENT_QUOTES), true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($block) => is_array($block) && ! empty($block['type'])));
    }
}
