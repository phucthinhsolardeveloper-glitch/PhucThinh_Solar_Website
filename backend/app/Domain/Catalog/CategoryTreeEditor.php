<?php

namespace App\Domain\Catalog;

use App\Domain\Audit\Audit;
use App\Domain\Routing\Redirects;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Saves the category tree as arranged by drag and drop in Admin: parent, level and order of every category.
 * A category that lands under another parent keeps its old address as a 301 (its pages below follow), and a slug that
 * would clash with a new sibling gets "-<id>" appended. Products stay in their categories; their own addresses never change.
 */
final class CategoryTreeEditor
{
    public const MAX_DEPTH = 4;

    /**
     * @param  list<array{id: int|string, children?: array}>  $roots  the whole tree, top categories first, in display order
     * @return array{moved: list<string>, reordered: int}
     */
    public function apply(array $roots): array
    {
        $existing = DB::table('categories')->get(['id', 'parent_id', 'depth', 'sort_order', 'slug', 'name'])->keyBy('id');
        $placed = [];
        $this->flatten($roots, null, 1, $placed);
        if (count($placed) !== $existing->count() || array_diff(array_keys($placed), $existing->keys()->map(fn ($id) => (int) $id)->all()) !== []) {
            throw new InvalidArgumentException('Cây danh mục đã thay đổi ở nơi khác. Tải lại trang rồi sắp xếp lại.');
        }

        $tree = new CategoryTree;
        $oldPaths = [];
        foreach ($existing as $id => $row) {
            $oldPaths[(int) $id] = '/'.trim($tree->path((int) $id), '/').'/';
        }

        $moved = [];
        $reordered = 0;
        DB::transaction(function () use ($placed, $existing, $oldPaths, &$moved, &$reordered) {
            // Clear slug clashes first: two siblings may not share a slug.
            $taken = [];
            foreach ($placed as $id => $place) {
                $slug = (string) $existing[$id]->slug;
                $key = ($place['parent'] ?? 0).'|'.$slug;
                if (isset($taken[$key])) {
                    $slug .= '-'.$id;
                }
                $taken[($place['parent'] ?? 0).'|'.$slug] = true;
                $placed[$id]['slug'] = $slug;
            }
            foreach ($placed as $id => $place) {
                $row = $existing[$id];
                $changes = array_filter([
                    'parent_id' => (int) ($row->parent_id ?? 0) !== (int) ($place['parent'] ?? 0) ? ($place['parent']) : false,
                    'depth' => (int) $row->depth !== $place['depth'] ? $place['depth'] : false,
                    'sort_order' => (int) $row->sort_order !== $place['sort'] ? $place['sort'] : false,
                    'slug' => (string) $row->slug !== $place['slug'] ? $place['slug'] : false,
                ], fn ($value) => $value !== false);
                if ($changes === []) {
                    continue;
                }
                DB::table('categories')->where('id', $id)->update($changes + ['updated_at' => now()]);
                if (array_key_exists('parent_id', $changes) || array_key_exists('slug', $changes)) {
                    $moved[] = $id;
                } else {
                    $reordered++;
                }
            }
            $tree = new CategoryTree;
            $names = [];
            foreach ($moved as $id) {
                $to = '/'.trim($tree->path($id), '/').'/';
                if ($oldPaths[$id] !== $to) {
                    Redirects::record($oldPaths[$id], $to, true);
                }
                $names[] = $existing[$id]->name.' → '.trim($to, '/');
            }
            $moved = $names;
            if ($moved !== [] || $reordered > 0) {
                Audit::record('catalog.category_tree', 'Category', null, null, ['moved' => $moved, 'reordered' => $reordered]);
            }
        });

        return ['moved' => $moved, 'reordered' => $reordered];
    }

    /** @param array<int, array{parent: ?int, depth: int, sort: int}> $placed */
    private function flatten(array $nodes, ?int $parent, int $depth, array &$placed): void
    {
        if ($depth > self::MAX_DEPTH && $nodes !== []) {
            throw new InvalidArgumentException('Danh mục chỉ có tối đa '.self::MAX_DEPTH.' cấp.');
        }
        foreach (array_values($nodes) as $index => $node) {
            $id = (int) ($node['id'] ?? 0);
            if ($id <= 0 || isset($placed[$id])) {
                throw new InvalidArgumentException('Dữ liệu cây danh mục không hợp lệ.');
            }
            $placed[$id] = ['parent' => $parent, 'depth' => $depth, 'sort' => $index + 1];
            $this->flatten(is_array($node['children'] ?? null) ? $node['children'] : [], $id, $depth + 1, $placed);
        }
    }
}
