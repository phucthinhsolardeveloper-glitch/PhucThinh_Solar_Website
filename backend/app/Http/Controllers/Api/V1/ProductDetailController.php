<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\ProductDetail\ProductDetailAssembler;
use App\Http\Controllers\Controller;
use App\Support\Text;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/v1/product-detail?slug= — same query rules and error codes as the PHP endpoint. */
class ProductDetailController extends Controller
{
    public function __invoke(Request $request, ProductDetailAssembler $assembler): JsonResponse
    {
        $query = $request->query();
        if (array_diff(array_keys($query), ['slug']) || (isset($query['slug']) && ! is_string($query['slug']))) {
            return $this->error(400, 'BAD_QUERY');
        }
        $slug = Text::slug(trim((string) ($query['slug'] ?? ''), '/'));
        if ($slug === '') {
            return $this->error(400, 'BAD_QUERY');
        }
        $payload = $assembler->forSlug($slug);
        if ($payload === null) {
            return $this->error(404, 'PRODUCT_NOT_FOUND');
        }

        return response()->json($payload, 200, ['Cache-Control' => 'private, no-store'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function error(int $status, string $code): JsonResponse
    {
        return response()->json(['error' => ['code' => $code]], $status);
    }
}
