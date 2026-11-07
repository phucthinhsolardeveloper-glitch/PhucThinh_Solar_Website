<?php

namespace App\Domain\Routing;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Pages\PageSupport;

/**
 * One-segment URL classifier: which published entity owns "/<slug>" (categories, brands, products,
 * albums, then posts by kind) and its canonical path. Anything without a page is a 404.
 */
class PublicRouteResolver extends PageSupport
{
    /** [table, depth or null, kind or null, route field, com] in router priority order. */
    private const ROUTES = [
        ['categories', 1, null, 'idl', 'san-pham'], ['categories', 2, null, 'idc', 'san-pham'], ['categories', 3, null, 'idi', 'san-pham'], ['categories', 4, null, 'ids', 'san-pham'],
        ['brands', null, null, 'idb', 'san-pham'], ['products', null, null, 'id', 'san-pham'], ['albums', null, null, 'id', 'thu-vien-anh'],
        ['post_categories', 1, 'article', 'idl', 'tin-tuc'], ['post_categories', 2, 'article', 'idc', 'tin-tuc'], ['post_categories', 3, 'article', 'idi', 'tin-tuc'], ['post_categories', 4, 'article', 'ids', 'tin-tuc'],
        ['posts', null, 'article', 'id', 'tin-tuc'],
        ['post_categories', 1, 'solution', 'idl', 'giai-phap'], ['post_categories', 2, 'solution', 'idc', 'giai-phap'], ['post_categories', 3, 'solution', 'idi', 'giai-phap'], ['post_categories', 4, 'solution', 'ids', 'giai-phap'],
        ['posts', null, 'solution', 'id', 'giai-phap'],
        ['post_categories', 1, 'project', 'idl', 'du-an'], ['post_categories', 2, 'project', 'idc', 'du-an'], ['post_categories', 3, 'project', 'idi', 'du-an'], ['post_categories', 4, 'project', 'ids', 'du-an'],
        ['posts', null, 'project', 'id', 'du-an'],
        ['posts', null, 'policy', 'id', 'chinh-sach'],
    ];

    private const KIND = ['tin-tuc' => 'article', 'giai-phap' => 'solution', 'du-an' => 'project', 'chinh-sach' => 'policy'];

    public function __construct(ProductMediaUrls $urls, private readonly CategoryTree $tree)
    {
        parent::__construct($urls);
    }

    /** Paths the router serves itself before the generic slug route. */
    private const RESERVED = ['he-thong-dai-ly', 've-chung-toi', 'product', 'index', 'sitemap', 'lien-he', 'gioi-thieu', 'dai-ly', 'tin-tuc', 'giai-phap', 'du-an',
        'du-an-da-thuc-hien', 'chinh-sach', 'thuong-hieu', 'san-pham', 'tim-kiem', 'thu-vien-anh', 'video', 'gio-hang', 'cam-on', 'account', 'ngon-ngu'];

    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        if (count($get) !== 1 || ! isset($get['slug']) || ! is_string($get['slug']) || ! preg_match(self::SLUG, $get['slug'])) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $slug = $get['slug'];
        $notFound = [404, ['error' => ['code' => 'PUBLIC_ROUTE_NOT_FOUND']]];
        if (in_array($slug, self::RESERVED, true)) {
            return $notFound;
        }
        $parts = [];
        $params = [];
        foreach (self::ROUTES as $priority => [$table, $depth, $kind, $field, $com]) {
            $parts[] = "select id, ? as route_field, ? as route_com, {$priority} as route_priority from {$table} where slug = ? and is_visible = 1"
                .($depth ? ' and depth = '.$depth : '').($kind ? " and kind = '{$kind}'" : '');
            array_push($params, $field, $com, $slug);
        }
        $match = $this->row('select id, route_field, route_com from ('.implode(' union all ', $parts).') as route_matches order by route_priority, id limit 0,1', $params);
        if (! $match || (int) $match['id'] < 1) {
            return $notFound;
        }
        $payload = $this->classify((int) $match['id'], (string) $match['route_field'], (string) $match['route_com'], $slug);

        return $payload === null ? $notFound : [200, $payload];
    }

    /** @return array<string, mixed>|null */
    private function classify(int $id, string $field, string $com, string $slug): ?array
    {
        $kind = self::KIND[$com] ?? null;
        [$routeKind, $path] = match (true) {
            $com === 'san-pham' && in_array($field, ['idl', 'idc', 'idi', 'ids'], true) => $this->productCategory($id),
            $com === 'san-pham' && $field === 'idb' => $this->bySlug('brands', $id, 'brand', fn ($s) => '/thuong-hieu/'.$s.'/'),
            $com === 'du-an' && $field === 'idl' => (fn ($p) => preg_match(self::SLUG, $p) ? ['project-category', '/'.$p] : [null, null])((string) ($this->row('select slug from post_categories where id = ? limit 1', [$id])['slug'] ?? '')),
            $field === 'idl' && in_array($com, ['tin-tuc', 'giai-phap'], true) => $this->editorialCategory($id, (string) $kind, $com, $slug),
            $field === 'id' && $com === 'san-pham' => (fn ($s) => $s !== '' && preg_match(self::SLUG, $s) ? ['pdp', '/san-pham/'.$s] : [null, null])((string) ($this->row('select slug from products where id = ? limit 1', [$id])['slug'] ?? '')),
            $field === 'id' && $com === 'thu-vien-anh' => $this->bySlug('albums', $id, 'gallery', fn ($s) => '/'.$s.'/'),
            $field === 'id' && $com === 'tin-tuc' => $this->bySlug('posts', $id, 'news', fn ($s) => '/'.$s.'/'),
            $field === 'id' && $com === 'du-an' => $this->bySlug('posts', $id, 'project', fn ($s) => '/'.$s.'/'),
            $field === 'id' && $com === 'giai-phap' => $this->postPath($id, 'giai-phap', 'solution'),
            $field === 'id' && $com === 'chinh-sach' => $this->postPath($id, 'ho-tro', 'policy'),
            default => [null, null],
        };

        return $routeKind === null ? null : ['schemaVersion' => 1, 'slug' => $slug, 'kind' => $routeKind, 'canonicalPath' => $path];
    }

    /** danh-muc-san-pham/<path>/ when that path resolves back to the same visible category. */
    private function productCategory(int $id): array
    {
        $relative = $this->tree->path($id);
        $segments = array_slice(explode('/', trim($relative, '/')), 1);
        if (! preg_match('~^danh-muc-san-pham/(?:[a-z0-9]+(?:-[a-z0-9]+)*/){1,4}$~', $relative) || $this->tree->resolvePath($segments) !== $id) {
            return [null, null];
        }

        return ['category', '/'.$relative];
    }

    /** Knowledge and solution category pages exist only when they list at least one published, slugged post. */
    private function editorialCategory(int $id, string $kind, string $com, string $slug): array
    {
        $listed = $this->row('select id, slug from post_categories where id=? and kind=? and is_visible=1 limit 1', [$id, $kind]);
        $relative = (string) ($listed['slug'] ?? '');
        if ($relative !== '' && $relative === $slug && preg_match(self::SLUG, $relative)) {
            return [$com === 'tin-tuc' ? 'news-category' : 'solution-category', '/'.$relative];
        }

        return [null, null];
    }

    private function bySlug(string $table, int $id, string $kind, callable $path): array
    {
        $slug = (string) ($this->row("select slug from {$table} where id = ? and is_visible = 1 limit 1", [$id])['slug'] ?? '');

        return $slug !== '' && preg_match(self::SLUG, $slug) ? [$kind, $path($slug)] : [null, null];
    }

    private function postPath(int $id, string $prefix, string $kind): array
    {
        $slug = (string) ($this->row('select slug from posts where id = ? limit 1', [$id])['slug'] ?? '');

        return $slug !== '' && preg_match(self::SLUG, $slug) ? [$kind, '/'.$prefix.'/'.$slug.'/'] : [null, null];
    }
}
