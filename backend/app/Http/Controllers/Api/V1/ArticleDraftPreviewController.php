<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Content\PostRevisions;
use App\Domain\News\NewsDetail;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostRevision;
use Illuminate\Http\JsonResponse;

class ArticleDraftPreviewController extends Controller
{
    /** Solution, project and policy drafts, in the same shape as their public detail. */
    public function post(PostRevision $revision, PostRevisions $revisions): JsonResponse
    {
        [$status, $body] = $revisions->previewPublic($revision);

        return response()->json($body, $status, ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow, noarchive', 'Referrer-Policy' => 'no-referrer'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function __invoke(PostRevision $revision, NewsDetail $detail): JsonResponse
    {
        return $this->article($detail->preview($revision));
    }

    /** An article as saved, switched on or off; nothing is written. */
    public function saved(Post $post, NewsDetail $detail, PostRevisions $revisions): JsonResponse
    {
        $current = new PostRevision(['payload' => $revisions->snapshot($post)]);
        $current->setRelation('post', $post);

        return $this->article($detail->preview($current));
    }

    /** @param array{0: int, 1: array<string, mixed>} $result */
    private function article(array $result): JsonResponse
    {
        [$status, $body] = $result;

        return response()->json($body, $status, ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow, noarchive', 'Referrer-Policy' => 'no-referrer'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
