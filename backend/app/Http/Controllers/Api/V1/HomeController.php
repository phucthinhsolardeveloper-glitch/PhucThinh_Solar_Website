<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Home\HomeAssembler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** GET /api/v1/home */
class HomeController extends Controller
{
    public function __invoke(HomeAssembler $assembler): JsonResponse
    {
        return response()->json($assembler->build(), 200, ['Cache-Control' => 'private, no-store'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
