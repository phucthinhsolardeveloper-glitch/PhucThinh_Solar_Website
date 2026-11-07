<?php

use App\Domain\Catalog\Health\CatalogHealth;
use Illuminate\Support\Facades\DB;

// The report covers every check, and it flags a product whose sale price is above its regular price.

it('returns every check and flags a sale price above the regular price', function () {
    $id = DB::table('products')->insertGetId([
        'name' => 'Sản phẩm thử', 'slug' => 'test-health-'.bin2hex(random_bytes(3)), 'is_visible' => true,
        'regular_price' => 100000, 'sale_price' => 150000, 'image' => null, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $issues = app(CatalogHealth::class)->run();

    expect(array_keys($issues))->toBe(array_keys(CatalogHealth::CHECKS))
        ->and(array_column($issues['sale_above_regular'], 'id'))->toContain($id)
        ->and(array_column($issues['no_image'], 'id'))->toContain($id)
        ->and(array_column($issues['missing_category'], 'id'))->toContain($id);
});
