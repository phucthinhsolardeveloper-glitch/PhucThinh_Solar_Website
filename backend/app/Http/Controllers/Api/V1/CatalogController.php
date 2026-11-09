<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Listing\CatalogAssembler;
use App\Domain\Catalog\Listing\CatalogRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/v1/catalog — validated by CatalogRequest; unknown keys and bad values are 400. */
class CatalogController extends Controller
{
    public function __invoke(Request $request, CatalogAssembler $assembler): JsonResponse
    {
        $catalog = CatalogRequest::fromQuery($request->query());
        if ($catalog === null) {
            return response()->json(['error' => ['code' => 'BAD_QUERY']], 400);
        }
        $payload = $assembler->build($catalog);
        if ($payload === null) {
            return response()->json(['error' => ['code' => 'CATEGORY_NOT_FOUND']], 404);
        }

        return response()->json($payload, 200, ['Cache-Control' => 'private, no-store'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
