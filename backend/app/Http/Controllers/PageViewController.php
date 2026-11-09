<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\PageViews;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** POST /api/v1/track {path, referrer, campaign}: sent by the storefront once per page. */
class PageViewController extends Controller
{
    public function __invoke(Request $request, PageViews $views): Response
    {
        $data = json_decode((string) $request->getContent(), true);
        if (is_array($data) && is_string($data['path'] ?? null) && strlen((string) $request->getContent()) <= 2048) {
            $views->record($data['path'], is_string($data['referrer'] ?? null) ? $data['referrer'] : '',
                is_string($data['campaign'] ?? null) ? $data['campaign'] : '', (string) $request->ip(),
                mb_substr((string) $request->userAgent(), 0, 400), $request->getHost(), ($data['entry'] ?? true) !== false);
        }

        return response()->noContent();
    }
}
