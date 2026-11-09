<?php

use App\Models\Brand;
use Illuminate\Support\Facades\Storage;

it('installs the bundled partner logos and keeps logos chosen in Admin', function () {
    Storage::fake('upload_product');
    $longi = Brand::query()->where('slug', 'longi')->firstOrFail();
    $longi->update(['image' => null]);
    $oriana = Brand::query()->where('slug', 'oriana')->firstOrFail();
    $oriana->update(['image' => 'thuong-hieu-oriana-3909.png']);
    $custom = Brand::query()->where('slug', 'solax')->firstOrFail();
    $custom->update(['image' => 'logo-solax-tu-chon.png']);

    $this->artisan('pts:brand-logos --dry-run')->assertSuccessful();
    expect($longi->fresh()->image)->toBeNull();

    $this->artisan('pts:brand-logos')->assertSuccessful();
    expect($longi->fresh()->image)->toBe('brand-longi.webp')
        ->and(Storage::disk('upload_product')->exists('brand-longi.webp'))->toBeTrue()
        ->and($oriana->fresh()->image)->toBe('brand-oriana.webp')
        ->and($custom->fresh()->image)->toBe('logo-solax-tu-chon.png')
        ->and(Brand::query()->where('slug', 'jindian')->value('image'))->toBe('brand-jindian.webp');

    $this->artisan('pts:brand-logos --force')->assertSuccessful();
    expect($custom->fresh()->image)->toBe('brand-solax.webp');
});

it('shows brands with a logo on the About wall and the product landing, linked only when they sell something', function () {
    Brand::query()->update(['image' => null]);
    $selling = Brand::query()->whereHas('products', fn ($q) => $q->where('is_visible', true))->firstOrFail();
    $empty = Brand::query()->whereDoesntHave('products')->firstOrFail();
    $selling->update(['image' => 'brand-a.webp']);
    $empty->update(['image' => 'brand-b.webp']);

    $partners = collect($this->getJson('/api/v1/about')->assertOk()->json('partners'))->keyBy('id');
    expect($partners)->toHaveCount(2)
        ->and($partners[$selling->id]['href'])->toBe('/san-pham/?brand='.$selling->id)
        ->and($partners[$empty->id]['href'])->toBeNull()
        ->and($partners[$empty->id]['logo'])->toBe('/upload/product/brand-b.webp');

    $landing = $this->getJson('/api/v1/catalog')->assertOk()->json('filters');
    expect(collect($landing['partners'])->pluck('id')->all())->toContain($selling->id, $empty->id)
        ->and(collect($landing['brands'])->firstWhere('id', $selling->id)['logo'])->toBe('/upload/product/brand-a.webp');
});
