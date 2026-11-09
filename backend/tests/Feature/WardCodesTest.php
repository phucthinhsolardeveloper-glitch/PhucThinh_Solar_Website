<?php

use Illuminate\Support\Facades\DB;

it('fills every province and ward with its official code, keeping leading zeros', function () {
    DB::table('wards')->update(['tax_code' => null]);
    DB::table('provinces')->update(['code' => null, 'tax_code' => null]);
    $this->artisan('pts:ward-codes')->assertSuccessful();

    expect(DB::table('provinces')->where('name', 'Hà Nội')->value('code'))->toBe('01')
        ->and(DB::table('wards')->whereNull('tax_code')->count())->toBe(0)
        ->and(DB::table('wards')->distinct()->count('tax_code'))->toBe(DB::table('wards')->count())
        ->and(DB::table('wards')->join('provinces', 'provinces.id', '=', 'wards.province_id')->where('provinces.name', 'Hồ Chí Minh')->whereRaw('binary wards.name = ?', ['Thanh An'])->value('wards.tax_code'))
        ->not->toBe(DB::table('wards')->join('provinces', 'provinces.id', '=', 'wards.province_id')->where('provinces.name', 'Hồ Chí Minh')->whereRaw('binary wards.name = ?', ['Thạnh An'])->value('wards.tax_code'));
});
