<?php

namespace App\Domain\Catalog;

use App\Domain\Audit\Audit;
use App\Domain\Routing\Redirects;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Saves the category tree as arranged by drag and drop in Admin: parent, level and order of every category.
 * A category that lands under another parent keeps its old address as a 301 (its pages below follow), and a slug that
 * would clash with a new sibling gets "-<id>" appended. Products stay in their categories; their own addresses never change.
 */
final class CategoryTreeEditor
{
    public const MAX_DEPTH = 4;

    public function version(): string
    {
        return hash('sha256', $this->categories()->toJson());
    }

    /**
     * @param  list<array{id: int|string, children?: array}>  $roots  the whole tree, top categories first, in display order
     * @return array{moved: list<string>, reordered: int}
     */
    public function apply(array $roots, string $expectedVersion): array
    {
        $placed = [];
        $this->flatten($roots, null, 1, $placed);

        return DB::transaction(function () use ($placed, $expectedVersion) {
            $existing = $this->categories(true);
            if (! hash_equals($expectedVersion, hash('sha256', $existing->toJson()))
                || count($placed) !== $existing->count()
                || array_diff(array_keys($placed), $existing->keys()->map(fn ($id) => (int) $id)->all()) !== []) {
                throw new InvalidArgumentException('Cây danh mục đã thay đổi ở nơi khác. Tải lại trang rồi sắp xếp lại.');
            }

            $tree = new CategoryTree;
            $oldPaths = [];
            foreach ($existing as $id => $row) {
                $oldPaths[(int) $id] = '/'.trim($tree->path((int) $id), '/').'/';
            }

            // A category already under the destination keeps its URL when another one moves in.
            $taken = [];
            $collisions = [];
            $groups = [];
            foreach ($placed as $id => $place) {
                $slug = (string) $existing[$id]->slug;
                $key = ($place['parent'] ?? 0).'|'.$slug;
                $groups[$key][] = $id;
            }
            foreach ($groups as $key => $ids) {
                $keeper = $ids[0];
                foreach ($ids as $id) {
                    if ((int) ($existing[$id]->parent_id ?? 0) === (int) ($placed[$id]['parent'] ?? 0)) {
                        $keeper = $id;
                        break;
                    }
                }
                $taken[$key] = true;
                $placed[$keeper]['slug'] = (string) $existing[$keeper]->slug;
                foreach ($ids as $id) {
                    if ($id !== $keeper) {
                        $collisions[] = $id;
                    }
                }
            }
            foreach ($collisions as $id) {
                $base = (string) $existing[$id]->slug;
                $suffix = '-'.$id;
                $slug = mb_substr($base, 0, 255 - strlen($suffix)).$suffix;
                $number = 2;
                while (isset($taken[($placed[$id]['parent'] ?? 0).'|'.$slug])) {
                    $suffix = '-'.$id.'-'.$number++;
                    $slug = mb_substr($base, 0, 255 - strlen($suffix)).$suffix;
                }
                $taken[($placed[$id]['parent'] ?? 0).'|'.$slug] = true;
                $placed[$id]['slug'] = $slug;
            }

            $newPaths = [];
            $pathFor = function (int $id) use (&$pathFor, &$newPaths, $placed): string {
                if (isset($newPaths[$id])) {
                    return $newPaths[$id];
                }
                $parent = $placed[$id]['parent'];
                $base = $parent === null ? '/danh-muc-san-pham/' : $pathFor($parent);
                $slug = trim((string) $placed[$id]['slug'], '/');

                return $newPaths[$id] = $base.($slug === '' ? '' : $slug.'/');
            };
            $oldOwners = array_flip($oldPaths);
            foreach (array_keys($placed) as $id) {
                $newPath = $pathFor($id);
                if (isset($oldOwners[$newPath]) && $oldOwners[$newPath] !== $id) {
                    throw new InvalidArgumentException('Một link cũ sẽ bị danh mục khác dùng lại. Hãy đổi slug trước khi chuyển cấp.');
                }
            }

            $changesById = [];
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
                if (array_key_exists('parent_id', $changes)) {
                    $changes['slug'] = $place['slug'];
                }
                $changesById[$id] = $changes;
            }

            // Free all old unique (parent, slug) slots before moving any row into its final slot.
            foreach ($changesById as $id => $changes) {
                if (array_key_exists('parent_id', $changes) || array_key_exists('slug', $changes)) {
                    DB::table('categories')->where('id', $id)->update(['slug' => '__pts_tree_'.$id.'_'.Str::uuid()]);
                }
            }

            $moved = [];
            $reordered = 0;
            foreach ($changesById as $id => $changes) {
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

            return ['moved' => $moved, 'reordered' => $reordered];
        });
    }

    private function categories(bool $lock = false): Collection
    {
        $query = DB::table('categories')->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get(['id', 'parent_id', 'depth', 'sort_order', 'slug', 'name', 'updated_at'])->keyBy('id');
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
