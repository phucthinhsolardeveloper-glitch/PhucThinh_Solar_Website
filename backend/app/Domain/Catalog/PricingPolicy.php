<?php

namespace App\Domain\Catalog;

/**
 * Whether a product shows a price or asks for a quote, the one rule shared by cards, product pages and cart:
 * prices are on for the site, the product is not quotation-only, and it has a regular or sale price.
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
        $regular = Money::fromStored($product['regular_price'] ?? null);
        $sale = Money::fromStored($product['sale_price'] ?? null);
        $quoteOnly = (bool) ($product['quote_only'] ?? false);
        $mode = $this->pricesVisible && ! $quoteOnly && ($regular !== null || $sale !== null) ? 'price' : 'quote';

        return [
            'mode' => $mode,
            'currency' => 'VND',
            'regularPrice' => $mode === 'price' ? $regular?->dong : null,
            'salePrice' => $mode === 'price' ? $sale?->dong : null,
        ];
    }
}
