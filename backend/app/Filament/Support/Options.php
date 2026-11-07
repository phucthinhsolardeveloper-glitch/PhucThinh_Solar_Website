<?php

namespace App\Filament\Support;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

/** Option lists shared by several Admin screens. */
final class Options
{
    /** @return array<string, string> product page profiles (config/pdp.php) */
    public static function pdpProfiles(): array
    {
        return collect(config('pdp.profiles'))->map(fn ($profile, $key) => (string) ($profile['label'] ?? $key))->all();
    }

    /**
     * Every category as "Cha › Con", for parent and product pickers.
     *
     * @return array<int, string>
     */
    public static function categoryPaths(int $maxDepth = 4, ?int $exceptId = null): array
    {
        $rows = DB::table('categories')->orderBy('depth')->orderBy('sort_order')->orderBy('id')->get(['id', 'parent_id', 'name', 'depth']);
        $byId = $rows->keyBy('id');
        $label = function ($row) use ($byId) {
            $parts = [];
            for ($node = $row; $node; $node = $node->parent_id ? $byId->get($node->parent_id) : null) {
                array_unshift($parts, $node->name);
            }

            return implode(' › ', $parts);
        };

        return $rows->filter(fn ($row) => $row->depth <= $maxDepth && $row->id !== $exceptId)
            ->mapWithKeys(fn ($row) => [$row->id => $label($row)])->sort()->all();
    }

    public static function categoryLabel(?Category $category): string
    {
        return $category ? (self::categoryPaths()[$category->id] ?? $category->name) : '';
    }
}
