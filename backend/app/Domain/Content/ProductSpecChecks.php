<?php

namespace App\Domain\Content;

use App\Domain\Catalog\ProductSpecTemplates;
use App\Models\Product;

/** Main specs a product type must show before it goes live (config/product_specs.php "required"). */
final class ProductSpecChecks
{
    /** @return list<string> labels of required specs that are empty */
    public static function missingRequired(Product $product): array
    {
        return ProductSpecTemplates::missingRequired($product);
    }
}
