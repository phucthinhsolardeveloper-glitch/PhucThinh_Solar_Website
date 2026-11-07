<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Site\SiteAssembler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** GET /api/v1/site — header, footer and navigation data every page needs. */
class SiteController extends Controller
{
    public function __invoke(SiteAssembler $assembler): JsonResponse
    {
        return response()->json($assembler->build(), 200, ['Cache-Control' => 'private, no-store'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
