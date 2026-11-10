<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;

function comparable(string $name, array $specs, bool $visible = true): Product
{
    $tag = bin2hex(random_bytes(3));

    return Product::query()->create(['name' => $name.' '.$tag, 'slug' => 'so-sanh-'.$tag, 'regular_price' => 100000, 'is_visible' => $visible,
        'additional_specs' => array_map(fn ($label, $value) => ['key' => '', 'label' => $label, 'value' => $value], array_keys($specs), $specs)]);
}

it('lines specs up by label and flags rows that do not differ', function () {
    $a = comparable('Biến tần A', ['Điện áp DC tối đa' => '500V', 'Hiệu suất MPPT' => '99,9%', 'Số pha' => '3 pha']);
    $b = comparable('Biến tần B', ['điện áp DC  tối đa' => '550V', 'Hiệu suất MPPT' => '99,9%']);
    $hidden = comparable('Ẩn', ['Hiệu suất MPPT' => '90%'], false);

    $r = $this->getJson("/api/v1/compare?ids={$a->id},{$b->id},{$hidden->id}")->assertOk()->json();
    $rows = collect($r['rows'])->keyBy('label');

    expect(collect($r['products'])->pluck('id')->all())->toBe([$a->id, $b->id])
        ->and($r['products'][0]['url'])->toBe('/san-pham/'.$a->slug)
        ->and($rows['Điện áp DC tối đa'])->toMatchArray(['values' => ['500V', '550V'], 'same' => false])
        ->and($rows['Hiệu suất MPPT']['same'])->toBeTrue()
        ->and($rows['Số pha']['values'])->toBe(['3 pha', ''])
        ->and(collect($r['rows'])->pluck('label')->filter(fn ($l) => in_array($l, ['Điện áp DC tối đa', 'Số pha'], true))->values()->all())->toBe(['Điện áp DC tối đa', 'Số pha']);
});

it('treats values written slightly differently as the same', function () {
    // Written straight to the table: saving through the model would already rewrite them in one form.
    $raw = fn (Product $p, array $specs) => DB::table('products')->where('id', $p->id)->update(['additional_specs' => json_encode(array_map(fn ($l, $v) => ['key' => '', 'label' => $l, 'value' => $v], array_keys($specs), $specs), JSON_UNESCAPED_UNICODE)]);
    $a = comparable('Đèn A', []);
    $b = comparable('Đèn B', []);
    $raw($a, ['Thời gian nạp' => '4-6H', 'Kích cỡ' => '282x235 mm', 'Vùng sáng' => '400 m2']);
    $raw($b, ['Thời gian nạp' => '4–6 giờ', 'Kích cỡ' => '282×235mm', 'Vùng sáng' => '400m²']);
    $rows = collect($this->getJson("/api/v1/compare?ids={$a->id},{$b->id}")->assertOk()->json('rows'))->keyBy('label');

    expect($rows['Thời gian nạp'])->toMatchArray(['values' => ['4-6H', '4–6 giờ'], 'same' => true])
        ->and($rows['Kích cỡ']['same'])->toBeTrue()->and($rows['Vùng sáng']['same'])->toBeTrue();
});

it('refuses more than four products and anything but a list of ids', function (string $query) {
    $this->getJson('/api/v1/compare'.$query)->assertStatus(400);
})->with(['', '?ids=1,2,3,4,5', '?ids=a', '?ids=1&x=1', '?ids=0']);
