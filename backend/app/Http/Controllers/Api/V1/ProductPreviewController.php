<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\ProductDetail\ProductDetailAssembler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** GET /api/v1/product-preview/{id} (signed, 15 minutes): the product page as saved, also while hidden. */
class ProductPreviewController extends Controller
{
    public function __invoke(int $product, ProductDetailAssembler $assembler): JsonResponse
    {
        $payload = $assembler->forPreview($product);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow, noarchive', 'Referrer-Policy' => 'no-referrer'];

        return $payload === null
            ? response()->json(['error' => ['code' => 'PRODUCT_NOT_FOUND']], 404, $headers)
            : response()->json($payload, 200, $headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
