<?php

use App\Domain\Catalog\ProductDetail\ProductContentResolver;
use Illuminate\Support\Facades\DB;

// Every product must resolve to the same blocks and source as the PHP admin. The fixture holds only
// hashes, captured by tools/migration/golden/capture_content_sections.php from the same data.

it('matches the PHP content resolution for every product', function () {
    $golden = json_decode(file_get_contents(base_path('tests/fixtures/golden/content_sections.json')), true)['products'];
    $resolver = new ProductContentResolver;
    $mismatches = [];

    $products = DB::connection('legacy')->table('table_product')->where('type', 'san-pham')->orderBy('id')->get(['id', 'id_brand', 'id_cat', 'id_list']);
    foreach ($products as $product) {
        $resolved = $resolver->resolve((array) $product);
        $actual = [$resolved['source'], hash('sha256', json_encode($resolved['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))];
        if (($golden[(string) $product->id] ?? null) !== $actual) {
            $mismatches[] = $product->id;
        }
    }

    expect($products)->toHaveCount(count($golden))
        ->and($mismatches)->toBe([]);
});
