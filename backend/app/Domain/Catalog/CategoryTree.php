<?php

namespace App\Domain\Catalog;

use Illuminate\Support\Facades\DB;

/**
 * The product category tree loaded once per request: parents, children in Admin order, slug paths
 * and which categories hold visible products.
 */
class CategoryTree
{
    /** @var array<int, array<string, mixed>> */
    private array $nodes = [];

    /** @var array<int, list<int>> parent id (0 = root) => child ids in display order */
    private array $children = [];

    /** @var array<int, bool>|null category id => has a visible product in it or below */
    private ?array $stocked = null;

    public function __construct()
    {
        foreach (DB::table('categories')->orderBy('sort_order')->orderByDesc('id')->get() as $row) {
            $this->nodes[(int) $row->id] = (array) $row;
            $this->children[(int) ($row->parent_id ?? 0)][] = (int) $row->id;
        }
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        return $this->nodes[$id] ?? null;
    }

    /** @return list<array<string, mixed>> children in Admin order (all, visible or not) */
    public function children(int $parentId): array
    {
        return array_map(fn ($id) => $this->nodes[$id], $this->children[$parentId] ?? []);
    }

    /** @return list<array<string, mixed>> top categories in Admin order */
    public function roots(): array
    {
        return $this->children(0);
    }

    /** @return list<int> the category and everything below it */
    public function descendantIds(int $id): array
    {
        $ids = [$id];
        foreach ($this->children[$id] ?? [] as $child) {
            array_push($ids, ...$this->descendantIds($child));
        }

        return $ids;
    }

    /** @return list<array<string, mixed>> from the top category down to this one */
    public function chain(int $id): array
    {
        $chain = [];
        for ($node = $this->nodes[$id] ?? null; $node; $node = $node['parent_id'] ? ($this->nodes[(int) $node['parent_id']] ?? null) : null) {
            array_unshift($chain, $node);
        }

        return $chain;
    }

    /** The category at a given depth (1 = top) above or at this one. */
    public function atDepth(int $id, int $depth): ?array
    {
        return $this->chain($id)[$depth - 1] ?? null;
    }

    /** True when a visible product sits in this category or one below it. */
    public function hasVisibleProducts(int $id): bool
    {
        if ($this->stocked === null) {
            $this->stocked = [];
            foreach (DB::table('products')->where('is_visible', true)->whereNotNull('category_id')->distinct()->pluck('category_id') as $categoryId) {
                foreach ($this->chain((int) $categoryId) as $node) {
                    $this->stocked[(int) $node['id']] = true;
                }
            }
        }

        return isset($this->stocked[$id]);
    }

    /** Category id of a visible slug path ("den-nlmt/den-duong"), each level a visible child of the one before. */
    public function resolvePath(array $segments): ?int
    {
        $segments = array_values(array_filter(array_map('trim', $segments)));
        if (count($segments) < 1 || count($segments) > 4) {
            return null;
        }
        $parent = 0;
        foreach ($segments as $segment) {
            $match = null;
            foreach ($this->children($parent) as $node) {
                if ($node['slug'] === $segment && $node['is_visible']) {
                    $match = (int) $node['id'];
                    break;
                }
            }
            if ($match === null) {
                return null;
            }
            $parent = $match;
        }

        return $parent;
    }

    /** "danh-muc-san-pham/<list>/<cat>/" from the slugs of the chain; empty slugs are skipped. */
    public function path(int $id): string
    {
        $parts = ['danh-muc-san-pham'];
        foreach ($this->chain($id) as $node) {
            if ((string) $node['slug'] !== '') {
                $parts[] = trim((string) $node['slug'], '/');
            }
        }

        return implode('/', $parts).'/';
    }
}
