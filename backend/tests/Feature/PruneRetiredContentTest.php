<?php

use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\PageItems\PageItemResource;
use Illuminate\Support\Facades\DB;

it('removes only retired Admin content after writing a restorable backup', function () {
    DB::table('banners')->insert(['placement' => 'slide', 'title' => 'Ảnh cũ trong test', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('page_items')->insert(['section' => 'archive.test', 'title' => 'Khối cũ trong test', 'created_at' => now(), 'updated_at' => now()]);
    $activeBannerCount = DB::table('banners')->whereIn('placement', array_keys(BannerResource::PLACEMENTS))->count();
    $activeItemCount = DB::table('page_items')->whereIn('section', array_keys(PageItemResource::SECTIONS))->count();
    $retiredBanners = DB::table('banners')->whereIn('placement', ['slide', 'home-hero-bg', 'banner-top', 'doitac', 'social', 'watermark', 'watermark-news'])->count();
    $retiredItems = DB::table('page_items')->where('section', 'like', 'archive.%')->count();
    expect($retiredBanners + $retiredItems)->toBeGreaterThan(0);

    $this->artisan('pts:prune-retired-content')->assertSuccessful();
    expect(DB::table('banners')->count())->toBeGreaterThanOrEqual($activeBannerCount)
        ->and(DB::table('page_items')->count())->toBeGreaterThanOrEqual($activeItemCount);

    $before = glob(storage_path('app/private/cleanup/retired-content-*.json')) ?: [];
    $this->artisan('pts:prune-retired-content', ['--apply' => true])->assertSuccessful();
    $after = glob(storage_path('app/private/cleanup/retired-content-*.json')) ?: [];
    $new = array_values(array_diff($after, $before));
    expect($new)->toHaveCount(1);
    $backup = json_decode((string) file_get_contents($new[0]), true, 512, JSON_THROW_ON_ERROR);
    expect($backup['banners'])->toHaveCount($retiredBanners)
        ->and($backup['page_items'])->toHaveCount($retiredItems)
        ->and(DB::table('banners')->whereIn('placement', array_keys(BannerResource::PLACEMENTS))->count())->toBe($activeBannerCount)
        ->and(DB::table('page_items')->whereIn('section', array_keys(PageItemResource::SECTIONS))->count())->toBe($activeItemCount)
        ->and(DB::table('banners')->whereIn('placement', ['slide', 'home-hero-bg', 'banner-top', 'doitac', 'social', 'watermark', 'watermark-news'])->count())->toBe(0)
        ->and(DB::table('page_items')->where('section', 'like', 'archive.%')->count())->toBe(0);
    unlink($new[0]);
});

it('refuses the candidate override for another database', function () {
    $id = DB::table('banners')->insertGetId(['placement' => 'slide', 'title' => 'Ảnh cũ trong test', 'created_at' => now(), 'updated_at' => now()]);

    $this->artisan('pts:prune-retired-content', ['--apply' => true, '--isolated-candidate' => true])->assertFailed();

    expect(DB::table('banners')->where('id', $id)->exists())->toBeTrue();
});
