<?php

use App\Domain\Catalog\Listing\SearchTerms;
use Illuminate\Support\Facades\DB;

// Every insert is rolled back after the test.
function searchProduct(string $name, string $summary = ''): int
{
    $base = (array) DB::table('products')->where('is_visible', true)->first();
    unset($base['id']);

    return (int) DB::table('products')->insertGetId(['name' => $name, 'code' => 'ZZT-'.uniqid(), 'slug' => 'zzt-'.uniqid(), 'summary' => $summary,
        'body' => null, 'additional_specs' => null, 'specs_html' => null, 'highlights' => null, 'applications' => null, 'installation' => null, 'is_visible' => true] + $base);
}

function searchIds(string $keyword): array
{
    return collect(test()->getJson('/api/v1/search?per_page=48&keyword='.urlencode($keyword))->assertOk()->json('products'))->pluck('id')->all();
}

it('reads a power typed with a space or a comma as one figure', function () {
    expect(SearchTerms::parse('Bơm Zzq 7,5 kW'))->toBe(['words' => ['bơm', 'zzq'], 'figures' => [['7.5', 'kw']]])
        ->and(SearchTerms::where('zzq'))->toBeNull();
});

it('finds words that are apart and matches the power exactly', function () {
    $panel = searchProduct('Tấm pin Zzqpanel 550W Mono');
    $small = searchProduct('Tấm pin Zzqpanel 50W Mono');
    $inverter = searchProduct('Inverter Zzqvolt X1 Hybrid 10kw');
    $battery = searchProduct('Pin Zzqvolt lưu trữ 10kWh');
    $pump = searchProduct('Biến tần Zzqdrive 4T7.5G', 'Biến tần bơm công suất 7,5kW, 3 pha.');
    $bigger = searchProduct('Biến tần Zzqdrive 4T17.5G', 'Biến tần bơm công suất 17,5kW, 3 pha.');
    $mention = searchProduct('Inverter Zzqvolt 5kw', 'Nhận tấm pin tối đa 10kW.');

    expect(searchIds('zzqpanel tấm pin 550w'))->toBe([$panel])
        ->and(searchIds('tấm zzqpanel 50 W'))->toBe([$small])
        ->and(searchIds('zzqvolt 10kw'))->toBe([$inverter])
        ->and(searchIds('zzqvolt 10kwh'))->toBe([$battery])
        ->and(searchIds('zzqdrive 7.5kw'))->toBe([$pump])
        ->and(searchIds('zzqdrive 7,5 kW'))->toBe([$pump])
        ->and(searchIds('zzqvolt 99kw'))->toBe([])
        ->and([$bigger, $mention])->not->toBeEmpty();
});

it('keeps the phrase results when the phrase already matches', function () {
    $exact = searchProduct('Zzqlamp sân vườn đỏ');
    searchProduct('Zzqlamp đỏ treo sân vườn');

    expect(searchIds('zzqlamp sân vườn'))->toBe([$exact]);
});
