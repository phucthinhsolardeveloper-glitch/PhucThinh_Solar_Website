<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\About\AboutAssembler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** GET /api/v1/about */
class AboutController extends Controller
{
    public function __invoke(AboutAssembler $assembler): JsonResponse
    {
        return response()->json($assembler->build(), 200, ['Cache-Control' => 'private, no-store'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
