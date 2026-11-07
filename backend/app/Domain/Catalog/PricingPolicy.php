<?php

namespace App\Domain\Catalog;

use App\Legacy\Support\StatusFlags;

/**
 * Whether a product shows a price or asks for a quote. The PHP site computes this in three places
 * (catalog DTO, product page DTO, public_pricing.php); this is the single rule they share:
 * prices are on for the site, the product is not flagged "baogia", and it has a regular or sale price.
 */
final class PricingPolicy
{
    public function __construct(private readonly bool $pricesVisible) {}

    /**
     * @param  array<string, mixed>  $product
     * @return array{mode: 'price'|'quote', currency: 'VND', regularPrice: int|null, salePrice: int|null}
     */
    public function forProduct(array $product): array
    {
        $regular = Money::fromLegacy($product['regular_price'] ?? null);
        $sale = Money::fromLegacy($product['sale_price'] ?? null);
        $quoteOnly = (new StatusFlags($product['status'] ?? ''))->has('baogia');
        $mode = $this->pricesVisible && ! $quoteOnly && ($regular !== null || $sale !== null) ? 'price' : 'quote';

        return [
            'mode' => $mode,
            'currency' => 'VND',
            'regularPrice' => $mode === 'price' ? $regular?->dong : null,
            'salePrice' => $mode === 'price' ? $sale?->dong : null,
        ];
    }
}
