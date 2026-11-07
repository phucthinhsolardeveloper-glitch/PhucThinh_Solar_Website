<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Media\MediaLibrary;
use App\Domain\News\NewsDetail;
use App\Domain\News\NewsListing;
use App\Domain\Pages\ContactAssembler;
use App\Domain\Pages\DealerAssembler;
use App\Domain\Projects\ProjectDetail;
use App\Domain\Projects\ProjectListing;
use App\Domain\Solutions\SolutionDetail;
use App\Domain\Solutions\SolutionsListing;
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

    public function news(Request $request, NewsListing $listing): JsonResponse
    {
        return self::result($listing->handle($request->query()));
    }

    public function newsDetail(Request $request, NewsDetail $detail): JsonResponse
    {
        return self::result($detail->article($request->query()));
    }

    public function policyDetail(Request $request, NewsDetail $detail): JsonResponse
    {
        return self::result($detail->policy($request->query()));
    }

    public function projects(Request $request, ProjectListing $listing): JsonResponse
    {
        return self::result($listing->handle($request->query()));
    }

    public function projectDetail(Request $request, ProjectDetail $detail): JsonResponse
    {
        return self::result($detail->handle($request->query()));
    }

    public function solutions(Request $request, SolutionsListing $listing): JsonResponse
    {
        return self::result($listing->handle($request->query()));
    }

    public function solutionDetail(Request $request, SolutionDetail $detail): JsonResponse
    {
        return self::result($detail->handle($request->query()));
    }

    public function gallery(Request $request, MediaLibrary $media): JsonResponse
    {
        return self::result($media->albums($request->query()));
    }

    public function galleryDetail(Request $request, MediaLibrary $media): JsonResponse
    {
        return self::result($media->albumPage($request->query()));
    }

    public function video(Request $request, MediaLibrary $media): JsonResponse
    {
        return self::result($media->videos($request->query()));
    }

    /** @param array{0: int, 1: array<string, mixed>} $result */
    public static function result(array $result): JsonResponse
    {
        return $result[0] === 200 ? self::json($result[1]) : response()->json($result[1], $result[0]);
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
