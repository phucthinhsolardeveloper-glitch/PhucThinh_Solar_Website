<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Pages\ContactAssembler;
use App\Domain\Pages\DealerAssembler;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Fixed editorial pages without parameters. Each refuses a query string with the PHP error code. */
class PagesController extends Controller
{
    public function contact(Request $request, ContactAssembler $assembler): JsonResponse
    {
        return $request->query() ? self::error(400, 'BAD_REQUEST') : self::json($assembler->build());
    }

    public function dealer(Request $request, DealerAssembler $assembler): JsonResponse
    {
        return $request->query() ? self::error(400, 'BAD_QUERY') : self::json($assembler->build());
    }

    public static function json(array $payload): JsonResponse
    {
        return response()->json($payload, 200, ['Cache-Control' => 'private, no-store'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(int $status, string $code): JsonResponse
    {
        return response()->json(['error' => ['code' => $code]], $status);
    }
}
