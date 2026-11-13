<?php

namespace App\Domain\Solutions;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Content\PostVisibility;
use Illuminate\Database\Connection;

/**
 * Rows the solution pages choose from, and the selection rule they share: published rows, and when
 * any row is marked featured, only featured ones. A hidden featured row still counts as a selection,
 * so hiding the last chosen item never brings back unrelated content.
 */
final class SolutionHub
{
    /**
     * Every solution post in Admin order, hidden ones included.
     *
     * @return list<array{id: int, category_id: int, title: string, nav_title: string, summary: string, image: string, cover_image: string, slug: string, is_visible: bool, featured: bool}>
     */
    public static function solutions(Connection $db): array
    {
        return $db->table('posts')->where('kind', 'solution')->orderBy('sort_order')->orderByDesc('id')
            ->select(['id', 'category_id', 'title', 'nav_title', 'summary', 'image', 'cover_image', 'slug', 'in_hub'])
            ->selectRaw(PostVisibility::liveSql().' as is_visible', [now()])->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id, 'category_id' => (int) $row->category_id, 'title' => (string) $row->title, 'nav_title' => (string) $row->nav_title,
                'summary' => (string) $row->summary, 'image' => (string) $row->image, 'cover_image' => (string) $row->cover_image, 'slug' => (string) $row->slug,
                'is_visible' => (bool) $row->is_visible, 'featured' => (bool) $row->in_hub,
            ])->all();
    }

    /**
     * Top product categories offered as equipment shortcuts, hidden ones included.
     *
     * @return list<array{id: int, name: string, summary: string, image: string, slug: string, is_visible: bool, featured: bool}>
     */
    /**
     * Product groups whose page exists: a top category with no visible product answers 404, so a shortcut to it
     * would be a dead link ("Trụ đèn" in the 2026-11-13 audit).
     *
     * @return list<array<string, mixed>>
     */
    public static function liveProductGroups(Connection $db): array
    {
        $tree = new CategoryTree;

        return array_values(array_filter(self::productGroups($db), fn (array $row) => $tree->hasVisibleProducts($row['id'])));
    }

    public static function productGroups(Connection $db): array
    {
        return $db->table('categories')->where('depth', 1)->orderBy('sort_order')->orderByDesc('id')
            ->get(['id', 'name', 'summary', 'image', 'slug', 'is_visible', 'in_solution_hub'])
            ->map(fn ($row) => [
                'id' => (int) $row->id, 'name' => (string) $row->name, 'summary' => (string) $row->summary, 'image' => (string) $row->image,
                'slug' => (string) $row->slug, 'is_visible' => (bool) $row->is_visible, 'featured' => (bool) $row->in_solution_hub,
            ])->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows  each with is_visible, optionally featured
     * @return list<array<string, mixed>>
     */
    public static function select(array $rows, bool $featuredOnly = false, int $limit = 0): array
    {
        $narrow = $featuredOnly && array_any($rows, fn ($row) => ! empty($row['featured']));
        $selected = [];
        foreach ($rows as $row) {
            if (! $row['is_visible'] || ($narrow && empty($row['featured']))) {
                continue;
            }
            $selected[] = $row;
            if ($limit > 0 && count($selected) >= $limit) {
                break;
            }
        }

        return $selected;
    }
}
