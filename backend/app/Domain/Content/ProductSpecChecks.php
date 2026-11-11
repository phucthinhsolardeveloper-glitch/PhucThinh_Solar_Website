<?php

namespace App\Domain\Content;

use App\Models\Product;

/** Main specs a product type must show before it goes live; filled by the typed product form (ProductSpecTemplates). */
final class ProductSpecChecks
{
    /** @return list<string> labels of required specs that are empty */
    public static function missingRequired(Product $product): array
    {
        return [];
    }
}
