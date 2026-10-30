<?php
/** Sanitized SEO DTO helpers for read-only public Nuxt endpoints. */

function ptsPublicSeoText($value)
{
    if(!is_scalar($value)) return '';
    $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/?p|/?div|/?li)\b[^>]*>~i', ' ', $value);
    $value = strip_tags($value);
    return trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value));
}

function ptsPublicSeoImage($value, $root, $uploadDirectory)
{
    if(!is_scalar($value)) return '';
    $filename = trim((string)$value);
    $uploadDirectory = trim((string)$uploadDirectory, '/\\');
    if($filename === '' || $uploadDirectory === '' || basename($filename) !== $filename) return '';
    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $filename)) return '';
    if(!is_file(rtrim((string)$root, '/\\').DIRECTORY_SEPARATOR.$uploadDirectory.DIRECTORY_SEPARATOR.$filename)) return '';
    return '/'.$uploadDirectory.'/'.$filename;
}

function ptsPublicSeoDto(array $row, $root, $uploadDirectory, $imageValue = null)
{
    return array(
        'title' => ptsPublicSeoText($row['titlevi'] ?? ''),
        'description' => ptsPublicSeoText($row['descriptionvi'] ?? ''),
        'keywords' => ptsPublicSeoText($row['keywordsvi'] ?? ''),
        'image' => ptsPublicSeoImage($imageValue === null ? ($row['photo'] ?? '') : $imageValue, $root, $uploadDirectory)
    );
}
