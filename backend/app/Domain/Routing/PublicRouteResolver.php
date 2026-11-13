<?php

namespace App\Domain\Routing;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Content\PostVisibility;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Which published page owns "/<slug>", and its canonical address. Owners are tried in a fixed order
 * (product categories, brands, products, albums, then articles, solutions, projects, policies with
 * their categories); the first one holding the slug decides, even when it has no page to show.
 */
class PublicRouteResolver
{
    /** Paths the router serves itself before the generic slug route. */
    private const RESERVED = ['he-thong-dai-ly', 've-chung-toi', 'product', 'index', 'sitemap', 'lien-he', 'gioi-thieu', 'dai-ly', 'tin-tuc', 'giai-phap', 'du-an',
        'du-an-da-thuc-hien', 'chinh-sach', 'thuong-hieu', 'san-pham', 'tim-kiem', 'thu-vien-anh', 'video', 'gio-hang', 'cam-on', 'account', 'ngon-ngu'];

    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(private readonly CategoryTree $tree) {}

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(array $get): array
    {
        if (count($get) !== 1 || ! isset($get['slug']) || ! is_string($get['slug']) || ! preg_match(self::SLUG, $get['slug'])) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $slug = $get['slug'];
        $page = in_array($slug, self::RESERVED, true) ? null : $this->owner($slug);

        return $page === null
            ? [404, ['error' => ['code' => 'PUBLIC_ROUTE_NOT_FOUND']]]
            : [200, ['schemaVersion' => 1, 'slug' => $slug, 'kind' => $page[0], 'canonicalPath' => $page[1]]];
    }

    /** @return array{0: string, 1: string}|null [kind, canonical path] */
    private function owner(string $slug): ?array
    {
        $owners = [
            ['categories', fn ($q) => $q->whereBetween('depth', [1, 4])->orderBy('depth'), fn ($row) => $this->productCategory((int) $row->id)],
            ['brands', null, fn ($row) => ['brand', '/thuong-hieu/'.$row->slug.'/']],
            ['products', null, fn ($row) => ['pdp', '/san-pham/'.$row->slug]],
            ['albums', null, fn ($row) => ['gallery', '/'.$row->slug.'/']],
            ['post_categories', self::categoryOf('article'), fn ($row) => (int) $row->depth === 1 ? ['news-category', '/'.$row->slug] : null],
            ['posts', self::postOf('article'), fn ($row) => ['news', '/'.$row->slug.'/']],
            ['post_categories', self::categoryOf('solution'), fn ($row) => (int) $row->depth === 1 ? ['solution-category', '/'.$row->slug] : null],
            ['posts', self::postOf('solution'), fn ($row) => ['solution', '/giai-phap/'.$row->slug.'/']],
            ['post_categories', self::categoryOf('project'), fn ($row) => (int) $row->depth === 1 ? ['project-category', '/'.$row->slug] : null],
            ['posts', self::postOf('project'), fn ($row) => ['project', '/'.$row->slug.'/']],
            ['posts', self::postOf('policy'), fn ($row) => ['policy', '/ho-tro/'.$row->slug.'/']],
        ];
        foreach ($owners as [$table, $scope, $page]) {
            $row = DB::table($table)->where('slug', $slug)->where('is_visible', true)->when($scope, $scope)->orderBy('id')->first();
            if ($row) {
                return $page($row);
            }
        }

        return null;
    }

    private static function categoryOf(string $kind): Closure
    {
        return fn ($q) => $q->where('kind', $kind)->whereBetween('depth', [1, 4])->orderBy('depth');
    }

    private static function postOf(string $kind): Closure
    {
        return fn ($q) => $q->where('kind', $kind)->where(PostVisibility::live());
    }

    /** /danh-muc-san-pham/<path>/ when that path leads back to the same category. */
    private function productCategory(int $id): ?array
    {
        $relative = $this->tree->path($id);
        $segments = array_slice(explode('/', trim($relative, '/')), 1);
        if (! preg_match('~^danh-muc-san-pham/(?:[a-z0-9]+(?:-[a-z0-9]+)*/){1,4}$~', $relative) || $this->tree->resolvePath($segments) !== $id) {
            return null;
        }

        // A category without a visible product has no page: go straight to where its address was moved, if anywhere,
        // instead of sending the visitor (and Google) through a second redirect or to a 404.
        if (! $this->tree->hasVisibleProducts($id)) {
            $moved = Redirects::find('/'.$relative);

            return $moved !== null && str_starts_with($moved, '/danh-muc-san-pham/') ? ['category', $moved] : null;
        }

        return ['category', '/'.$relative];
    }
}
