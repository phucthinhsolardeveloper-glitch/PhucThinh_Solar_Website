<?php

use App\Domain\Catalog\SpecFormat;
use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

it('writes time, length, area and size one way and leaves codes and free text alone', function (string $kind, string $in, string $out) {
    expect(SpecFormat::apply($in, $kind))->toBe($out);
})->with([
    ['time', '4-6H', '4–6 giờ'], ['time', '4 - 6 tiếng', '4–6 giờ'], ['time', '14-18hours', '14–18 giờ'], ['time', '6–8 giờ', '6–8 giờ'],
    ['time', '> 12 tiếng, chiếu sáng 3 ngày mưa', '> 12 giờ, chiếu sáng 3 ngày mưa'], ['time', 'Chiếu sáng cả đêm', 'Chiếu sáng cả đêm'],
    ['length', '3-5m', '3–5 m'], ['length', '2,5–3m tùy revision', '2,5–3 m tùy revision'], ['length', 'Theo cao độ trụ/cổng', 'Theo cao độ trụ/cổng'],
    ['area', '250 m2', '250 m²'], ['area', 'Khoảng 50-60 m²', 'Khoảng 50–60 m²'], ['area', '200m²', '200 m²'],
    ['size', '585 x 260 x 85mm', '585 × 260 × 85 mm'], ['size', '300x300xH240mm (Đế 300×300 mm)', '300 × 300 × H240 mm (Đế 300 × 300 mm)'], ['size', '84.8*19.5*54 cm', '84.8 × 19.5 × 54 cm'],
    ['time', 'EPV230-4T2.2G', 'EPV230-4T2.2G'],
    ['duration', '01 năm', '1 năm'], ['duration', '10 năm vật lý', '10 năm vật lý'], ['duration', 'Động cơ: 02 năm | còn lại: 01 năm', 'Động cơ: 2 năm | còn lại: 1 năm'],
    ['electric', '3.2v/100Ah Pin LifePO4', '3.2V/100Ah Pin LiFePO4'], ['electric', '3.7V/4400mah', '3.7V/4400mAh'], ['electric', 'Lithium-Ion 3.2V 25AH', 'Li-ion 3.2V 25Ah'],
    ['electric', 'pin 32650 LiFePO₄, 24.000mAh', 'pin 32650 LiFePO4, 24.000mAh'], ['electric', '5V/100w (MONO)', '5V/100W (MONO)'],
    ['cct', '3000k - 6500k', '3000–6500K'], ['cct', '3000-6500K', '3000–6500K'], ['cct', '6000K (3 Led modules)', '6000K (3 Led modules)'],
    ['ip', 'ip 65', 'IP65'], ['ip', 'IP66 - IK08', 'IP66 - IK08'],
    ['carton', '4pcs/ctn', '4 cái/thùng'], ['carton', '01pcs/cnt', '1 cái/thùng'], ['carton', '36', '36 cái/thùng'], ['carton', '1pcs/ctn (theo bộ)', '1 cái/thùng (theo bộ)'],
]);

it('normalizes on save and the command finds nothing left after --apply', function () {
    $tag = bin2hex(random_bytes(3));
    $p = Product::query()->create(['name' => 'Đèn '.$tag, 'slug' => 'den-'.$tag, 'regular_price' => 1, 'is_visible' => true,
        'charging_time' => '4-6H', 'lamp_dimensions' => '610*250mm', 'additional_specs' => [['key' => '', 'label' => 'Thời gian chiếu sáng', 'value' => '10-12h'], ['key' => '', 'label' => 'Model', 'value' => 'JD-8500']]]);
    $p->refresh();
    expect($p->charging_time)->toBe('4–6 giờ')->and($p->lamp_dimensions)->toBe('610 × 250 mm')
        ->and($p->additional_specs[0]['value'])->toBe('10–12 giờ')->and($p->additional_specs[1]['value'])->toBe('JD-8500');

    DB::table('products')->where('id', $p->id)->update(['lighting_area' => '120 m2']);
    Artisan::call('pts:normalize-specs');
    expect(DB::table('products')->where('id', $p->id)->value('lighting_area'))->toBe('120 m2');
    Artisan::call('pts:normalize-specs', ['--apply' => true]);
    expect(DB::table('products')->where('id', $p->id)->value('lighting_area'))->toBe('120 m²')
        ->and(DB::table('admin_audit_events')->where('action', 'catalog.normalize_specs')->exists())->toBeTrue();
    Artisan::call('pts:normalize-specs');
    expect(Artisan::output())->toContain('0 sản phẩm cần chuẩn hóa');
});

it('rewrites spec cells of a bulk file before the preview, extra spec lines by their name', function () {
    expect(SpecFormat::cell('charging_time', 'text', '4-6H'))->toBe('4–6 giờ')
        ->and(SpecFormat::cell('name', 'text', 'Đèn 4-6H'))->toBe('Đèn 4-6H')
        ->and(SpecFormat::cell('additional_specs', 'specs', "tg | Thời gian chiếu sáng | 10-12h\n | Model | JD-8500"))->toBe("tg | Thời gian chiếu sáng | 10–12 giờ\n | Model | JD-8500");
});

it('writes the brand in a product name as Admin writes it, once, on one line, and never renames spec rows', function () {
    expect(SpecFormat::changes(['name' => 'Biến tần bơm NLMT Hober HOBER HSHP5500H', 'brand_name' => 'Hober'])['name'])->toBe('Biến tần bơm NLMT Hober HSHP5500H')
        ->and(SpecFormat::changes(['name' => "Tấm pin ASTRONERGY 595Wp \n- 1 MẶT KÍNH", 'brand_name' => 'Astronergy'])['name'])->toBe('Tấm pin Astronergy 595Wp - 1 MẶT KÍNH')
        ->and(SpecFormat::changes(['name' => 'Đèn pha 60W JinDian JD-8860L', 'brand_name' => 'JINDIAN'])['name'])->toBe('Đèn pha 60W JINDIAN JD-8860L')
        ->and(SpecFormat::changes(['name' => 'Đèn pha JINDIAN', 'brand_name' => 'JINDIAN']))->toBe([])
        ->and(SpecFormat::changes(['additional_specs' => [['key' => '', 'label' => 'Công suất danh định', 'value' => '5kW'], ['key' => '', 'label' => 'Mã model', 'value' => 'X1'], ['key' => '', 'label' => 'Khối lượng', 'value' => '26kg']]])['additional_specs'])
        ->toBe([['key' => '', 'label' => 'Công suất danh định', 'value' => '5kW'], ['key' => '', 'label' => 'Mã model', 'value' => 'X1'], ['key' => '', 'label' => 'Khối lượng', 'value' => '26 kg']]);
});
