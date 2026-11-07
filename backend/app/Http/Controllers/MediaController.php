<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Media\Thumbnails;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Uploaded files and thumbnails. In production Caddy may serve /upload straight from the volume;
 * these routes make the site work without the PHP app either way.
 */
class MediaController extends Controller
{
    private const TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'avif' => 'image/avif', 'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'mp4' => 'video/mp4', 'mkv' => 'video/x-matroska'];

    public function file(string $path, ProductMediaUrls $urls): BinaryFileResponse
    {
        $public = request()->is('upload/*') ? 'upload/'.$path : 'assets/'.$path;
        $type = self::TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        abort_unless($type !== null && $urls->exists($public), 404);
        $response = response()->file($urls->absolute($public), ['Content-Type' => $type, 'Cache-Control' => 'public, max-age=2592000', 'X-Content-Type-Options' => 'nosniff']);
        if (str_starts_with($type, 'image/svg')) {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'");
        }

        return $response;
    }

    public function thumb(int $width, int $height, int $mode, string $path, Thumbnails $thumbnails): BinaryFileResponse
    {
        $thumb = $thumbnails->make($width, $height, $mode, $path);
        abort_if($thumb === null, 404);

        return response()->file($thumb['path'], ['Content-Type' => $thumb['mime'], 'Cache-Control' => 'public, max-age=2592000, immutable', 'X-Content-Type-Options' => 'nosniff']);
    }
}
