<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * content_sections.blocks is stored flat ({"type": "text", "text": "…"}), which the site reads.
 * Through the model (the Admin) blocks are {type, data}, the shape Filament's Builder edits.
 *
 * @implements CastsAttributes<list<array{type: string, data: array<string, mixed>}>, mixed>
 */
class ContentBlocks implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        $blocks = json_decode((string) $value, true);
        if (! is_array($blocks)) {
            return [];
        }
        $out = [];
        foreach ($blocks as $block) {
            if (is_array($block) && ! empty($block['type'])) {
                $out[] = ['type' => (string) $block['type'], 'data' => array_diff_key($block, ['type' => true])];
            }
        }

        return $out;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $flat = [];
        foreach (is_array($value) ? $value : [] as $block) {
            if (! is_array($block) || empty($block['type'])) {
                continue;
            }
            $flat[] = array_key_exists('data', $block)
                ? ['type' => (string) $block['type']] + (array) $block['data']
                : $block;
        }

        return (string) json_encode($flat, JSON_UNESCAPED_UNICODE);
    }
}
