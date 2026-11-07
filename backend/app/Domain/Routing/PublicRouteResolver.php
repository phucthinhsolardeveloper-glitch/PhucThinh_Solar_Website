<?php

namespace App\Domain\Routing;

use App\Domain\Pages\PageSupport;

/**
 * One-segment URL classifier (api/pts_v1_public_route_resolve.php): which published entity owns
 * "/<slug>" in the same priority order as the PHP router ($requick), and its canonical path.
 */
class PublicRouteResolver extends PageSupport
{
    /** [table, route field, com, type] in router priority order. */
    private const ROUTES = [
        ['product_list', 'idl', 'san-pham', 'san-pham'], ['product_cat', 'idc', 'san-pham', 'san-pham'], ['product_item', 'idi', 'san-pham', 'san-pham'],
        ['product_sub', 'ids', 'san-pham', 'san-pham'], ['product_brand', 'idb', 'san-pham', 'san-pham'], ['product', 'id', 'san-pham', 'san-pham'],
        ['product', 'id', 'thu-vien-anh', 'thu-vien-anh'],
        ['news_list', 'idl', 'tin-tuc', 'tin-tuc'], ['news_cat', 'idc', 'tin-tuc', 'tin-tuc'], ['news_item', 'idi', 'tin-tuc', 'tin-tuc'], ['news_sub', 'ids', 'tin-tuc', 'tin-tuc'], ['news', 'id', 'tin-tuc', 'tin-tuc'],
        ['news_list', 'idl', 'giai-phap', 'giai-phap'], ['news_cat', 'idc', 'giai-phap', 'giai-phap'], ['news_item', 'idi', 'giai-phap', 'giai-phap'], ['news_sub', 'ids', 'giai-phap', 'giai-phap'], ['news', 'id', 'giai-phap', 'giai-phap'],
        ['news_list', 'idl', 'du-an', 'du-an'], ['news_cat', 'idc', 'du-an', 'du-an'], ['news_item', 'idi', 'du-an', 'du-an'], ['news_sub', 'ids', 'du-an', 'du-an'], ['news', 'id', 'du-an', 'du-an'],
        ['news', 'id', 'chinh-sach', 'chinh-sach'],
    ];

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
        if (in_array($slug, self::RESERVED, true)) {
            return [200, ['schemaVersion' => 1, 'slug' => $slug, 'kind' => 'legacy-only', 'canonicalPath' => null, 'reason' => 'unsupported-route']];
        }
        $parts = [];
        $params = [];
        foreach (self::ROUTES as $priority => [$table, $field, $com, $type]) {
            $parts[] = "select id, ? as route_field, ? as route_com, {$priority} as route_priority from table_{$table} where slugvi = ? and type = ? and find_in_set('hienthi',status)";
            array_push($params, $field, $com, $slug, $type);
        }
        $match = $this->row('select id, route_field, route_com from ('.implode(' union all ', $parts).') as route_matches order by route_priority limit 0,1', $params);
        if (! $match || (int) $match['id'] < 1) {
            return [404, ['error' => ['code' => 'PUBLIC_ROUTE_NOT_FOUND']]];
        }

        return [200, $this->classify((int) $match['id'], (string) $match['route_field'], (string) $match['route_com'], $slug)];
    }

    private function classify(int $id, string $field, string $com, string $slug): array
    {
        [$kind, $path] = match (true) {
            $com === 'san-pham' && in_array($field, ['idl', 'idc', 'idi', 'ids'], true) => $this->productCategory($id, $field),
            $com === 'san-pham' && $field === 'idb' => $this->bySlug('product_brand', $id, 'san-pham', 'brand', fn ($s) => '/thuong-hieu/'.$s.'/'),
            $com === 'du-an' && $field === 'idl' => (fn ($p) => preg_match(self::SLUG, $p) ? ['project-category', '/'.$p] : [null, null])($this->newsSlug('news_list', $id)),
            $field === 'idl' && in_array($com, ['tin-tuc', 'giai-phap'], true) => $this->editorialCategory($id, $com, $slug),
            $field === 'id' && $com === 'san-pham' => (fn ($s) => $s !== '' && preg_match(self::SLUG, $s) ? ['pdp', '/san-pham/'.$s] : [null, null])((string) ($this->row("select slugvi from table_product where id = ? and type = 'san-pham' limit 1", [$id])['slugvi'] ?? '')),
            $field === 'id' && $com === 'thu-vien-anh' => $this->bySlug('product', $id, 'thu-vien-anh', 'gallery', fn ($s) => '/'.$s.'/'),
            $field === 'id' && $com === 'tin-tuc' => $this->bySlug('news', $id, 'tin-tuc', 'news', fn ($s) => '/'.$s.'/'),
            $field === 'id' && $com === 'du-an' => $this->bySlug('news', $id, 'du-an', 'project', fn ($s) => '/'.$s.'/'),
            $field === 'id' && $com === 'giai-phap' => $this->newsPath($id, 'giai-phap', 'giai-phap', 'solution'),
            $field === 'id' && $com === 'chinh-sach' => $this->newsPath($id, 'chinh-sach', 'ho-tro', 'policy'),
            default => [null, null],
        };
        $isProductCategory = $com === 'san-pham' && in_array($field, ['idl', 'idc', 'idi', 'ids'], true);
        $payload = ['schemaVersion' => 1, 'slug' => $slug, 'kind' => $kind ?? 'legacy-only', 'canonicalPath' => $path];
        if ($kind === null) {
            $payload['reason'] = $isProductCategory || ! in_array($field, ['idl', 'idb', 'id'], true) ? 'unsupported-taxonomy' : 'unsupported-route';
        }

        return $payload;
    }

    /** danh-muc-san-pham/<list>/<cat>/... built from the row and its parents, then resolved back through visible levels. */
    private function productCategory(int $id, string $field): array
    {
        $levels = ['idl' => 'list', 'idc' => 'cat', 'idi' => 'item', 'ids' => 'sub'];
        $level = $levels[$field];
        $chain = ['list' => [], 'cat' => ['list'], 'item' => ['cat', 'list'], 'sub' => ['item', 'cat', 'list']][$level];
        $select = ["t.slugvi as {$level}"];
        $joins = '';
        foreach ($chain as $parent) {
            $select[] = "{$parent}_t.slugvi as {$parent}";
            $joins .= " join table_product_{$parent} {$parent}_t on {$parent}_t.id = t.id_{$parent}";
        }
        $row = $this->row('select '.implode(',', $select)." from table_product_{$level} t{$joins} where t.id = ? and t.type = 'san-pham' limit 1", [$id]);
        if (! $row) {
            return [null, null];
        }
        $segments = [];
        foreach (['list', 'cat', 'item', 'sub'] as $part) {
            if (! empty($row[$part])) {
                $segments[] = trim($row[$part], '/');
            }
            if ($part === $level) {
                break;
            }
        }
        $relative = 'danh-muc-san-pham/'.implode('/', $segments).'/';
        if (! preg_match('~^danh-muc-san-pham/(?:[a-z0-9]+(?:-[a-z0-9]+)*/){1,4}$~', $relative) || $this->resolveCategory($segments, $field) !== $id) {
            return [null, null];
        }

        return ['category', '/'.$relative];
    }

    /** The id at the last level of a visible category slug path, or 0. */
    private function resolveCategory(array $segments, string $field): int
    {
        $tables = [['table_product_list', null, 'idl'], ['table_product_cat', 'id_list', 'idc'], ['table_product_item', 'id_cat', 'idi'], ['table_product_sub', 'id_item', 'ids']];
        if (count($segments) < 1 || count($segments) > 4) {
            return 0;
        }
        $parentId = 0;
        $resolved = [];
        foreach ($segments as $i => $segment) {
            [$table, $parentColumn, $key] = $tables[$i];
            $row = $this->row("select id from {$table} where slugvi = ?".($parentColumn ? " and {$parentColumn} = ?" : '')." and type = 'san-pham' and find_in_set('hienthi',status) limit 1",
                $parentColumn ? [$segment, $parentId] : [$segment]);
            if (! $row) {
                return 0;
            }
            $resolved[$key] = $parentId = (int) $row['id'];
        }

        return $resolved[$field] ?? 0;
    }

    /** Knowledge and solution category pages exist only when they list at least one published, slugged entry. */
    private function editorialCategory(int $id, string $com, string $slug): array
    {
        $listed = $this->row("select id from table_news_list where id=? and type=? and find_in_set('hienthi',status) and exists(select 1 from table_news n where n.id_list=table_news_list.id and n.type=? and find_in_set('hienthi',n.status) and n.slugvi<>'') limit 1", [$id, $com, $com]);
        $relative = $listed ? $this->newsSlug('news_list', $id) : '';
        if ($relative !== '' && $relative === $slug && preg_match(self::SLUG, $relative)) {
            return [$com === 'tin-tuc' ? 'news-category' : 'solution-category', '/'.$relative];
        }

        return [null, null];
    }

    private function bySlug(string $table, int $id, string $type, string $kind, callable $path): array
    {
        $slug = (string) ($this->row("select slugvi from table_{$table} where id = ? and type = ? and find_in_set('hienthi', status) limit 1", [$id, $type])['slugvi'] ?? '');

        return $slug !== '' && preg_match(self::SLUG, $slug) ? [$kind, $path($slug)] : [null, null];
    }

    private function newsSlug(string $table, int $id): string
    {
        return (string) ($this->row("select slugvi from table_{$table} where id = ? limit 1", [$id])['slugvi'] ?? '');
    }

    private function newsPath(int $id, string $type, string $prefix, string $kind): array
    {
        $slug = (string) ($this->row('select slugvi from table_news where id = ? and type = ? limit 1', [$id, $type])['slugvi'] ?? '');

        return $slug !== '' && preg_match(self::SLUG, $slug) ? [$kind, '/'.$prefix.'/'.$slug.'/'] : [null, null];
    }
}
