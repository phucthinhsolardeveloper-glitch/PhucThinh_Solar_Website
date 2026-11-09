<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\About\AboutAssembler;
use App\Domain\Home\HomeAssembler;
use App\Domain\Pages\PageLayouts;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/** Home or About with a layout draft, from a signed Admin link. */
class PageLayoutPreviewController extends Controller
{
    public function __invoke(int $layout, HomeAssembler $home, AboutAssembler $about): JsonResponse
    {
        $row = DB::table('page_layouts')->where('id', $layout)->first();
        $headers = ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow, noarchive', 'Referrer-Policy' => 'no-referrer'];
        if (! $row || ! isset(PageLayouts::PAGES[$row->page])) {
            return response()->json(['error' => ['code' => 'PREVIEW_NOT_FOUND']], 404, $headers);
        }
        $sections = PageLayouts::visible($row->page, json_decode((string) $row->sections, true));
        $body = $row->page === 'home' ? $home->build($sections) : $about->build($sections);

        return response()->json($body + ['preview' => ['page' => $row->page, 'version' => (int) $row->version, 'state' => $row->state]], 200, $headers,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
