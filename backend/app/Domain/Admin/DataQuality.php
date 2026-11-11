<?php

namespace App\Domain\Admin;

use App\Domain\Catalog\Health\CatalogHealth;
use App\Domain\Catalog\ProductSpecTemplates;
use App\Domain\Content\PostVisibility;
use App\Domain\Content\PublishChecks;
use App\Filament\Resources\PostRevisions\PostRevisionResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Post;
use App\Models\Product;
use App\Models\SeoMeta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Every data-quality check in one report: products (catalog health + missing main specs of their type),
 * posts on the website (the publish checks), SEO titles used twice. Cached ten minutes; counts are recorded
 * nightly so the page can show the trend.
 */
final class DataQuality
{
    /**
     * @return list<array{key: string, group: string, label: string, severity: string, count: int, items: list<array{name: string, url: string}>}>
     */
    public function report(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('data-quality');
            Cache::forget('catalog-health');
        }

        return Cache::remember('data-quality', 600, fn () => $this->build());
    }

    /** @return list<array{key: string, group: string, label: string, severity: string, count: int, items: list<array{name: string, url: string}>}> */
    private function build(): array
    {
        $checks = [];
        $health = app(CatalogHealth::class)->run();
        foreach (CatalogHealth::CHECKS as $key => [$severity, $label]) {
            $checks[] = $this->check('product.'.$key, 'Sản phẩm', $label, $severity,
                array_map(fn ($p) => ['name' => $p['name'], 'url' => ProductResource::getUrl('edit', ['record' => $p['id']])], $health[$key] ?? []));
        }
        $missing = [];
        foreach (Product::query()->where('is_visible', true)->orderBy('id')->get() as $product) {
            $labels = ProductSpecTemplates::missingRequired($product);
            if ($labels !== []) {
                $missing[] = ['name' => $product->name.' (thiếu: '.implode(', ', $labels).')', 'url' => ProductResource::getUrl('edit', ['record' => $product])];
            }
        }
        $checks[] = $this->check('product.missing_specs', 'Sản phẩm', 'Đang hiện nhưng thiếu thông số chính theo loại', 'warning', $missing);

        $byMessage = [];
        foreach (Post::query()->where(PostVisibility::live())->with('seo')->orderBy('id')->get() as $post) {
            $result = app(PublishChecks::class)->post(PublishChecks::postData($post), (int) $post->id);
            foreach (['errors' => 'error', 'warnings' => 'warning'] as $bucket => $severity) {
                foreach ($result[$bucket] as $message) {
                    $byMessage[$severity][$message][] = ['name' => (string) $post->title,
                        'url' => PostRevisionResource::ownerResource((string) $post->kind)::getUrl('edit', ['record' => $post])];
                }
            }
        }
        foreach ($byMessage as $severity => $messages) {
            foreach ($messages as $message => $items) {
                $checks[] = $this->check('post.'.md5($message), 'Bài viết, dự án, giải pháp', $message, $severity, $items);
            }
        }

        $duplicates = DB::table('seo_metas')->whereNotNull('title')->where('title', '<>', '')
            ->select('title', DB::raw('count(*) as n'))->groupBy('title')->having('n', '>', 1)->pluck('title');
        $items = [];
        foreach (SeoMeta::query()->whereIn('title', $duplicates)->orderBy('title')->get() as $meta) {
            $owner = $meta->owner;
            $resource = match (true) {
                $owner instanceof Product => ProductResource::class,
                $owner instanceof Post => PostRevisionResource::ownerResource((string) $owner->kind),
                default => null,
            };
            $items[] = ['name' => $meta->title, 'url' => $resource !== null ? $resource::getUrl('edit', ['record' => $owner]) : ''];
        }
        $checks[] = $this->check('seo.duplicate_title', 'SEO', 'Tiêu đề SEO trùng với trang khác', 'warning', $items);

        return array_values(array_filter($checks, fn ($c) => $c['count'] > 0));
    }

    /** Writes today's counts (nightly). @return int checks recorded */
    public function record(): int
    {
        $rows = array_map(fn ($c) => ['day' => now()->toDateString(), 'check_key' => $c['key'], 'count' => $c['count']], $this->report(true));
        $rows[] = ['day' => now()->toDateString(), 'check_key' => 'total', 'count' => (int) array_sum(array_column($rows, 'count'))];
        DB::table('data_quality_daily')->upsert($rows, ['day', 'check_key'], ['count']);

        return count($rows);
    }

    /** @return array<string, int> check key => count recorded $days ago (closest earlier day) */
    public function countsAgo(int $days): array
    {
        $day = DB::table('data_quality_daily')->where('day', '<=', now()->subDays($days)->toDateString())->max('day');

        return $day ? DB::table('data_quality_daily')->where('day', $day)->pluck('count', 'check_key')->map(fn ($n) => (int) $n)->all() : [];
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array{key: string, group: string, label: string, severity: string, count: int, items: list<array{name: string, url: string}>}
     */
    private function check(string $key, string $group, string $label, string $severity, array $items): array
    {
        return ['key' => $key, 'group' => $group, 'label' => $label, 'severity' => $severity, 'count' => count($items), 'items' => array_slice($items, 0, 25)];
    }
}
