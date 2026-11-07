<?php

use App\Domain\Catalog\Health\CatalogHealth;
use Illuminate\Support\Facades\DB;

// The report covers every check, and it flags a product whose sale price is above its regular price.

afterEach(function () {
    DB::connection('legacy')->table('table_product')->where('slugvi', 'lv-test-health')->delete();
});

it('returns every check and flags a sale price above the regular price', function () {
    $id = DB::connection('legacy')->table('table_product')->insertGetId([
        'type' => 'san-pham', 'namevi' => 'Sản phẩm thử', 'slugvi' => 'lv-test-health', 'status' => 'hienthi',
        'regular_price' => 100000, 'sale_price' => 150000, 'photo' => '', 'id_list' => 0, 'id_cat' => 0, 'id_brand' => 0,
    ]);

    $issues = app(CatalogHealth::class)->run();

    expect(array_keys($issues))->toBe(array_keys(CatalogHealth::CHECKS))
        ->and(array_column($issues['sale_above_regular'], 'id'))->toContain($id)
        ->and(array_column($issues['no_image'], 'id'))->toContain($id);
});
