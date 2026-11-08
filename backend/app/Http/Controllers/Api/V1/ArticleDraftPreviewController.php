<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\News\NewsDetail;
use App\Http\Controllers\Controller;
use App\Models\PostRevision;
use Illuminate\Http\JsonResponse;

class ArticleDraftPreviewController extends Controller
{
    public function __invoke(PostRevision $revision, NewsDetail $detail): JsonResponse
    {
        [$status, $body] = $detail->preview($revision);

        return response()->json($body, $status, ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow, noarchive', 'Referrer-Policy' => 'no-referrer'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
