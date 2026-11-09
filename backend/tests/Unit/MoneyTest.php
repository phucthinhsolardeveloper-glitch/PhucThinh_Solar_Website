<?php

use App\Domain\Catalog\Money;
use App\Domain\Catalog\PricingPolicy;

it('turns stored decimals into whole dong and refuses non-prices', function () {
    expect(Money::fromStored('1258000')?->dong)->toBe(1258000)
        ->and(Money::fromStored(1257999.6)?->dong)->toBe(1258000)
        ->and(Money::fromStored(0))->toBeNull()
        ->and(Money::fromStored(-5))->toBeNull()
        ->and(Money::fromStored('abc'))->toBeNull()
        ->and(Money::fromStored(null))->toBeNull();
});

it('asks for a quote when prices are off, the product is quotation-only, or has no price', function () {
    $on = new PricingPolicy(true);
    expect($on->forProduct(['regular_price' => 100])['mode'])->toBe('price')
        ->and($on->forProduct(['regular_price' => 100, 'quote_only' => true])['mode'])->toBe('quote')
        ->and($on->forProduct(['regular_price' => 0, 'sale_price' => 0])['mode'])->toBe('quote')
        ->and((new PricingPolicy(false))->forProduct(['regular_price' => 100])['regularPrice'])->toBeNull();
});
