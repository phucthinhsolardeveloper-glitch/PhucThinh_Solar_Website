<?php

use App\Domain\Catalog\ProductDetail\ProductSpecs;

beforeEach(fn () => $this->specs = app(ProductSpecs::class));

it('falls back from product to category to slug map, and unknown profiles become general', function () {
    expect($this->specs->profileKey(['pdp_profile' => 'fan'], ['pdp_profile' => 'inverter']))->toBe('fan')
        ->and($this->specs->profileKey([], ['pdp_profile' => 'inverter']))->toBe('inverter')
        ->and($this->specs->profileKey([], ['slug' => 'den-nlmt']))->toBe('lighting')
        ->and($this->specs->profileKey(['pdp_profile' => 'made-up'], []))->toBe('general');
});

it('normalises package lists glued together by spreadsheet exports', function () {
    expect($this->specs->packageContents('Đèn + Tấm pin, tấm pin; Remote'))
        ->toBe('Đèn · Tấm pin · Remote');
});

it('treats placeholders as missing and formats wattage', function () {
    expect($this->specs->value(['warranty' => 'Đang cập nhật'], 'warranty'))->toBe('')
        ->and($this->specs->value(['power_watts' => '100.0'], 'cong_suat'))->toBe('100W');
});

it('reads aliased values from additional specs', function () {
    $row = ['additional_specs' => json_encode([['label' => 'Voc', 'value' => '49,5 V']])];
    expect($this->specs->value($row, 'open_circuit_voltage'))->toBe('49,5 V');
});

it('keeps both PHP lookup orders: the table tries needles first, metrics walk specs first', function () {
    $row = ['pdp_profile' => 'lighting', 'power_watts' => '100', 'additional_specs' => json_encode([
        ['label' => 'Lumen (đo thực tế)', 'value' => '12.000 - 12.800 LM'],
        ['label' => 'Quang thông', 'value' => '12.000-12.800 lm'],
    ])];
    $table = collect($this->specs->table($row, 'lighting'))->firstWhere('key', 'quang_thong');
    $metric = collect($this->specs->decisionMetrics($row, 'lighting'))->firstWhere('key', 'quang_thong');

    expect($table['value'])->toBe('12.000-12.800 lm')
        ->and($metric['value'])->toBe('12.000 - 12.800 LM');
});

it('shows size and IP code on their own rows, without the "cấp bảo vệ" wording, and keeps hand-typed size rows', function () {
    $row = ['pdp_profile' => 'lighting', 'lamp_dimensions' => '400 × 315 × 90 mm', 'waterproof_rating' => 'IP66', 'additional_specs' => json_encode([
        ['label' => 'Kích thước thùng', 'value' => '45 × 40 × 12 cm'],
        ['label' => 'Cấp bảo vệ', 'value' => 'IP66'],
    ], JSON_UNESCAPED_UNICODE)];
    $table = collect($this->specs->table($row, 'lighting'));

    expect($table->firstWhere('key', 'lamp_dimensions'))->toMatchArray(['label' => 'Kích thước', 'value' => '400 × 315 × 90 mm'])
        ->and($table->firstWhere('key', 'waterproof_rating'))->toMatchArray(['label' => 'Chuẩn IP', 'value' => 'IP66'])
        ->and($table->pluck('label')->filter(fn ($l) => str_contains(mb_strtolower($l), 'cấp bảo vệ'))->all())->toBe([])
        ->and($table->firstWhere('label', 'Kích thước thùng')['value'] ?? null)->toBe('45 × 40 × 12 cm');
    expect(config('pdp.profiles.solar_panel.fields.waterproof_rating') ?? 'Chuẩn IP')->toBe('Chuẩn IP');
});
