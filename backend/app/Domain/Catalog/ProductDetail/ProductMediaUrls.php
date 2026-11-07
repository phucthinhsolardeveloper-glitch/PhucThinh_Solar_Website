<?php

namespace App\Domain\Catalog\ProductDetail;

/** Public URLs for product files, only when the file name is safe and the file exists (ptsPdpMediaUrl). */
final class ProductMediaUrls
{
    public function __construct(private readonly string $uploadRoot) {}

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
