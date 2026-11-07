<?php

namespace App\Legacy\Support;

/**
 * Decoding of the JSON block lists the PHP admin stores HTML-escaped (productSectionsDecode()).
 */
final class ContentBlocks
{
    /**
     * Blocks are arrays with a non-empty "type"; anything else is dropped, as in the PHP code.
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
