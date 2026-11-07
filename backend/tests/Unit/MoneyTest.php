<?php

use App\Domain\Catalog\Money;
use App\Domain\Catalog\PricingPolicy;

it('turns legacy doubles into whole dong and refuses non-prices', function () {
    expect(Money::fromLegacy('1258000')?->dong)->toBe(1258000)
        ->and(Money::fromLegacy(1257999.6)?->dong)->toBe(1258000)
        ->and(Money::fromLegacy(0))->toBeNull()
        ->and(Money::fromLegacy(-5))->toBeNull()
        ->and(Money::fromLegacy('abc'))->toBeNull()
        ->and(Money::fromLegacy(null))->toBeNull();
});

it('asks for a quote when prices are off, flagged baogia, or missing', function () {
    $on = new PricingPolicy(true);
    expect($on->forProduct(['regular_price' => 100, 'status' => 'hienthi'])['mode'])->toBe('price')
        ->and($on->forProduct(['regular_price' => 100, 'status' => 'hienthi,baogia'])['mode'])->toBe('quote')
        ->and($on->forProduct(['regular_price' => 0, 'sale_price' => 0, 'status' => 'hienthi'])['mode'])->toBe('quote')
        ->and((new PricingPolicy(false))->forProduct(['regular_price' => 100])['regularPrice'])->toBeNull();
});
