<?php

namespace App\Domain\Catalog\ProductDetail;

/** Public URLs for product files, only when the file name is safe and the file exists (ptsPdpMediaUrl). */
final class ProductMediaUrls
{
    public function __construct(private readonly string $uploadRoot, private readonly string $assetsRoot = '') {}

    /** Application card images: a stock use-case picture from assets/, or a product upload (ptsPdpApplicationImageUrl). */
    public function application(mixed $value): string
    {
        $value = is_scalar($value) ? ltrim(trim((string) $value), '/') : '';
        if (preg_match('#^assets/images/site-visuals/pdp-usecase-v2/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)$#i', $value)) {
            return $this->exists($value) ? '/'.$value : '';
        }

        return $this->content($value);
    }

    /** Downloadable documents: https links, or files under upload/ or assets/ that exist (ptsPdpDocumentUrl). */
    public function document(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return '';
        }
        $extension = strtolower(pathinfo((string) parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (! in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx'], true)) {
            return '';
        }
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $parts = parse_url($value);

            return is_array($parts) && strtolower($parts['scheme'] ?? '') === 'https' && ! empty($parts['host'])
                && empty($parts['user']) && empty($parts['pass']) ? $value : '';
        }
        $local = ltrim($value, '/');
        if (str_contains($local, '..') || ! preg_match('#^(?:upload|assets)/[A-Za-z0-9._/-]+$#', $local)) {
            return '';
        }

        return $this->exists($local) ? '/'.$local : '';
    }

    /** A bare file name inside one public folder, e.g. visual('x.png', 'upload/photo/') (ptsCatalogVisualMedia). */
    public function visual(mixed $value, string $folder): string
    {
        $value = is_scalar($value) ? ltrim(trim((string) $value), '/') : '';
        if ($value === '' || basename($value) !== $value || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) {
            return '';
        }
        $path = trim($folder, '/').'/'.$value;

        return $this->exists($path) ? '/'.$path : '';
    }

    /** upload/... lives in the shared upload volume, assets/... in the shipped assets folder. */
    public function exists(string $publicPath): bool
    {
        $file = $this->absolute($publicPath);

        return $file !== '' && is_file($file);
    }

    /** Disk path of a public file path, or '' outside upload/ and assets/ or when it tries to climb out. */
    public function absolute(string $publicPath): string
    {
        $publicPath = ltrim($publicPath, '/');
        if (str_contains($publicPath, '..') || str_contains($publicPath, "\0")) {
            return '';
        }
        if (str_starts_with($publicPath, 'upload/')) {
            return $this->uploadRoot.'/'.substr($publicPath, 7);
        }

        return $this->assetsRoot !== '' && str_starts_with($publicPath, 'assets/') ? $this->assetsRoot.'/'.substr($publicPath, 7) : '';
    }

    public function product(mixed $value): string
    {
        $value = is_scalar($value) ? ltrim(trim((string) $value), '/') : '';
        if ($value === '' || basename($value) !== $value || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) {
            return '';
        }

        return is_file($this->uploadRoot.'/product/'.$value) ? '/upload/product/'.$value : '';
    }

    /** Images inside content blocks: a bare file name or upload/product/<name> (ptsPdpContentImageUrl). */
    public function content(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $value = ltrim(trim((string) $value), '/');
        if ($value === '') {
            return '';
        }
        if (basename($value) === $value) {
            return $this->product($value);
        }
        if (! preg_match('#^upload/product/([A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif))$#i', $value, $match)) {
            return '';
        }

        return is_file($this->uploadRoot.'/product/'.$match[1]) ? '/upload/product/'.$match[1] : '';
    }
}
