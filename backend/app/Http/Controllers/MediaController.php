<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Media\Thumbnails;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Uploaded files and thumbnails. Caddy may serve /upload straight from the volume; these routes
 * answer when it does not, and make every thumbnail size on first request.
 */
class MediaController extends Controller
{
    private const TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'avif' => 'image/avif', 'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'mp4' => 'video/mp4', 'mkv' => 'video/x-matroska'];

    public function file(string $path, ProductMediaUrls $urls): BinaryFileResponse
    {
        $public = 'upload/'.$path;
        $type = self::TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        abort_unless($type !== null && $urls->exists($public), 404);
        $response = $this->cached($urls->absolute($public), $type);
        if (str_starts_with($type, 'image/svg')) {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'");
        }

        return $response;
    }

    public function thumb(int $width, int $height, int $mode, string $path, Thumbnails $thumbnails): BinaryFileResponse
    {
        $thumb = $thumbnails->make($width, $height, $mode, $path);
        abort_if($thumb === null, 404);

        return $this->cached($thumb['path'], $thumb['mime']);
    }

    /**
     * Admins may replace a file under the same name, so browsers revalidate every time; an unchanged
     * file answers 304 from its modification time and is not sent again.
     */
    private function cached(string $file, string $type): BinaryFileResponse
    {
        $response = response()->file($file, ['Content-Type' => $type, 'Cache-Control' => 'public, no-cache', 'X-Content-Type-Options' => 'nosniff']);
        $response->setLastModified((new \DateTimeImmutable)->setTimestamp((int) filemtime($file)));
        $response->isNotModified(request());

        return $response;
    }
}
