<?php

namespace App\Domain\Media;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use GdImage;
use Illuminate\Support\Facades\Storage;

/**
 * Resized pictures for /thumbs/<w>x<h>x<mode>/<public path>[.webp].
 * Mode 1 crops to fill the box, mode 2 fits inside the box on a white canvas, mode 3 fits and keeps
 * the picture's own proportions. A trailing ".webp" asks for a WebP copy of a JPG/PNG. Results are
 * kept on the "thumbs" disk, so each size is made once.
 */
class Thumbnails
{
    private const SOURCE = '#^(upload/(?:news|photo|product|seopage)/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp)|assets/images/site-visuals/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png))$#i';

    public function __construct(private readonly ProductMediaUrls $urls) {}

    /** @return array{path: string, mime: string}|null the cached file, or null when the request is not a valid thumbnail */
    public function make(int $width, int $height, int $mode, string $path): ?array
    {
        if (($width < 10 && $height < 10) || $width > 2000 || $height > 2000 || ! in_array($mode, [1, 2, 3, 4], true)) {
            return null;
        }
        $webp = false;
        if (str_ends_with($path, '.webp') && preg_match(self::SOURCE, substr($path, 0, -5)) && $this->urls->exists(substr($path, 0, -5))) {
            $path = substr($path, 0, -5);
            $webp = true;
        }
        if (! preg_match(self::SOURCE, $path) || ! $this->urls->exists($path)) {
            return null;
        }
        $source = $this->urls->absolute($path);
        $info = @getimagesize($source);
        if (! $info) {
            return null;
        }
        $outMime = $webp || $info['mime'] === 'image/webp' ? 'image/webp' : ($info['mime'] === 'image/jpeg' ? 'image/jpeg' : 'image/png');
        $key = "{$width}x{$height}x{$mode}/".$path.($webp ? '.webp' : '');
        $disk = Storage::disk('thumbs');
        if ($disk->exists($key) && $disk->lastModified($key) >= filemtime($source)) {
            return ['path' => $disk->path($key), 'mime' => $outMime];
        }
        $image = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/gif' => @imagecreatefromgif($source),
            'image/webp' => @imagecreatefromwebp($source),
            default => false,
        };
        if (! $image instanceof GdImage) {
            return null;
        }
        $canvas = $this->resize($image, $info[0], $info[1], $width, $height, $mode);
        $disk->makeDirectory(dirname($key));
        $target = $disk->path($key);
        match ($outMime) {
            'image/webp' => imagewebp($canvas, $target, 82),
            'image/jpeg' => imagejpeg($canvas, $target, 86),
            default => imagepng($canvas, $target, 6),
        };

        return ['path' => $target, 'mime' => $outMime];
    }

    private function resize(GdImage $image, int $sourceW, int $sourceH, int $width, int $height, int $mode): GdImage
    {
        if ($height && ! $width) {
            $width = (int) round($sourceW * $height / $sourceH);
        } elseif ($width && ! $height) {
            $height = (int) round($sourceH * $width / $sourceW);
        }
        if ($mode === 3) {
            // Fit: the output takes the picture's proportions inside the requested box.
            $fitHeight = $sourceH * $width / $sourceW;
            if ($fitHeight > $height) {
                $width = (int) round($sourceW * $height / $sourceH);
            } else {
                $height = (int) round($fitHeight);
            }
        }
        $width = max(1, $width);
        $height = max(1, $height);
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 255, 255, 255, $mode === 2 || $mode === 4 ? 0 : 127));
        imagesavealpha($canvas, true);
        if ($mode === 2 || $mode === 4) {
            // Letterbox: the whole picture, centred on white, never enlarged beyond the box.
            $scale = min($width / $sourceW, $height / $sourceH);
            $copyW = max(1, (int) round($sourceW * $scale));
            $copyH = max(1, (int) round($sourceH * $scale));
            imagecopyresampled($canvas, $image, (int) (($width - $copyW) / 2), (int) (($height - $copyH) / 2), 0, 0, $copyW, $copyH, $sourceW, $sourceH);

            return $canvas;
        }
        // Crop to fill, centred (modes 1 and 3; for 3 the box already has the picture's proportions).
        $ratioX = $sourceW / $width;
        $ratioY = $sourceH / $height;
        $srcW = $sourceW;
        $srcH = $sourceH;
        $srcX = $srcY = 0;
        if ($ratioX > $ratioY) {
            $srcW = (int) round($sourceW / $ratioX * $ratioY);
            $srcX = (int) round(($sourceW - $srcW) / 2);
        } elseif ($ratioY > $ratioX) {
            $srcH = (int) round($sourceH / $ratioY * $ratioX);
            $srcY = (int) round(($sourceH - $srcH) / 2);
        }
        imagecopyresampled($canvas, $image, 0, 0, $srcX, $srcY, $width, $height, $srcW, $srcH);

        return $canvas;
    }
}
