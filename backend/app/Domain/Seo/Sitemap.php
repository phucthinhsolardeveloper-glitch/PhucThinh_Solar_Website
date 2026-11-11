<?php

namespace App\Domain\Seo;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Content\PostVisibility;
use App\Domain\Solar\SolarEstimator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Every public page by its canonical address, so search engines never land on a redirect.
 * Hidden rows, and posts whose page does not exist, are left out.
 */
class Sitemap
{
    /** Fixed pages, in menu order. */
    private const PAGES = ['/gioi-thieu/', '/san-pham/', '/giai-phap/', '/du-an-da-thuc-hien/', '/tin-tuc/', '/thu-vien-anh/', '/video/', '/dai-ly/', '/lien-he/'];

    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(private readonly CategoryTree $tree) {}

    /** @return list<array{path: string, lastmod?: string}> */
    public function entries(): array
    {
        // Static pages have no reliable aggregate modification timestamp. Omitting
        // lastmod is more accurate than claiming they change on every request.
        $entries = [['path' => '/']];
        foreach (self::PAGES as $page) {
            $entries[] = ['path' => $page];
        }
        if (app(SolarEstimator::class)->enabled()) {
            $entries[] = ['path' => '/uoc-tinh-dien-mat-troi/'];
        }

        foreach (DB::table('categories')->where('is_visible', true)->orderBy('depth')->orderBy('sort_order')->orderBy('id')->get(['id', 'updated_at']) as $row) {
            $path = $this->tree->path((int) $row->id);
            // A category page without a visible product answers 404, so it is not listed.
            if ($path !== '' && $this->chainVisible((int) $row->id) && $this->tree->hasVisibleProducts((int) $row->id)) {
                $entry = ['path' => '/'.trim($path, '/').'/'];
                if ($lastmod = $this->date($row->updated_at)) {
                    $entry['lastmod'] = $lastmod;
                }
                $entries[] = $entry;
            }
        }
        $this->add($entries, DB::table('products')->where('is_visible', true)->orderBy('sort_order')->orderBy('id'), fn ($slug) => '/san-pham/'.$slug);
        $this->add($entries, DB::table('brands')->where('is_visible', true)->orderBy('sort_order')->orderBy('id'), fn ($slug) => '/thuong-hieu/'.$slug.'/');

        // Editorial: top categories are bare paths without a slash, posts follow their kind.
        foreach (['article', 'solution', 'project'] as $kind) {
            $this->add($entries, DB::table('post_categories')->where('kind', $kind)->where('depth', 1)->where('is_visible', true)->orderBy('sort_order')->orderBy('id'), fn ($slug) => '/'.$slug);
        }
        $paths = ['article' => fn ($slug) => '/'.$slug, 'project' => fn ($slug) => '/'.$slug, 'solution' => fn ($slug) => '/giai-phap/'.$slug.'/', 'policy' => fn ($slug) => '/ho-tro/'.$slug.'/'];
        foreach ($paths as $kind => $path) {
            $this->add($entries, DB::table('posts')->where('kind', $kind)->where(PostVisibility::live())->orderBy('sort_order')->orderByDesc('id'), $path);
        }
        $this->add($entries, DB::table('albums')->where('is_visible', true)->orderBy('sort_order')->orderBy('id'), fn ($slug) => '/'.$slug.'/');

        $seen = [];

        return array_values(array_filter($entries, function ($entry) use (&$seen) {
            if (isset($seen[$entry['path']])) {
                return false;
            }

            return $seen[$entry['path']] = true;
        }));
    }

    public function xml(string $origin): string
    {
        $origin = rtrim($origin, '/');
        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($this->entries() as $entry) {
            $out .= '<url><loc>'.htmlspecialchars($origin.$entry['path'], ENT_XML1).'</loc>';
            if (isset($entry['lastmod'])) {
                $out .= '<lastmod>'.$entry['lastmod'].'</lastmod>';
            }
            $out .= '</url>'."\n";
        }

        return $out.'</urlset>'."\n";
    }

    /**
     * @param  list<array{path: string, lastmod?: string}>  $entries
     * @param  callable(string): string  $path
     */
    private function add(array &$entries, Builder $query, callable $path): void
    {
        foreach ($query->get(['slug', 'updated_at']) as $row) {
            $slug = (string) $row->slug;
            if (preg_match(self::SLUG, $slug)) {
                $entry = ['path' => (string) $path($slug)];
                if ($lastmod = $this->date($row->updated_at)) {
                    $entry['lastmod'] = $lastmod;
                }
                $entries[] = $entry;
            }
        }
    }

    private function chainVisible(int $id): bool
    {
        foreach ($this->tree->chain($id) as $node) {
            if (! $node['is_visible']) {
                return false;
            }
        }

        return true;
    }

    private function date(mixed $value): ?string
    {
        return $value ? Carbon::parse((string) $value)->toAtomString() : null;
    }
}
