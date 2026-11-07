<?php

use App\Domain\Catalog\PricingPolicy;
use App\Legacy\Catalog\ProductImageResolver;
use App\Legacy\Settings\LegacySettings;
use Illuminate\Support\Facades\DB;

// One pricing rule must reproduce the three PHP ones, and the image pick must choose the same file.

beforeEach(function () {
    $this->golden = json_decode(file_get_contents(base_path('tests/fixtures/golden/pricing_media.json')), true);
    $this->products = DB::connection('legacy')->table('table_product')->where('type', 'san-pham')->orderBy('id')
        ->get(['id', 'type', 'photo', 'status', 'regular_price', 'sale_price']);
});

it('reads the price switch as the PHP site does', function () {
    expect(app(LegacySettings::class)->productPricesVisible())->toBe($this->golden['pricesVisible']);
});

it('prices every product like the product page, the catalog and public_pricing.php', function () {
    $policy = new PricingPolicy($this->golden['pricesVisible']);
    $mismatches = [];
    foreach ($this->products as $product) {
        $expected = $this->golden['products'][(string) $product->id];
        $actual = $policy->forProduct((array) $product);
        if ($actual !== $expected['pdp'] || $actual !== $expected['catalog'] || ($actual['mode'] === 'quote') !== $expected['requiresQuote']) {
            $mismatches[] = $product->id;
        }
    }

    expect($this->products)->toHaveCount(count($this->golden['products']))->and($mismatches)->toBe([]);
});

it('picks the same product image', function () {
    $resolver = app(ProductImageResolver::class);
    $mismatches = [];
    foreach ($this->products as $product) {
        if ($resolver->resolve((array) $product) !== $this->golden['products'][(string) $product->id]['image']) {
            $mismatches[] = $product->id;
        }
    }

    expect($mismatches)->toBe([]);
});
