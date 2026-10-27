<?php
/** Pure allow-listed DTO helpers for the read-only Nuxt project detail preview. */

require_once __DIR__.'/pts_public_richtext.php';

function ptsProjectDetailPublished($status)
{
    return is_scalar($status) && in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsProjectDetailSlug($value)
{
    $value = is_scalar($value) ? trim((string)$value, " /\t\n\r\0\x0B") : '';
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsProjectDetailText($value)
{
    if(!is_scalar($value)) return '';
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function ptsProjectDetailMedia($value, $root = '', $uploadDirectory = 'upload/news/')
{
    $value = is_scalar($value) ? ltrim(trim((string)$value), '/') : '';
    if($value === '' || basename($value) !== $value || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    if($root !== '' && !is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.trim($uploadDirectory, '/\\').DIRECTORY_SEPARATOR.$value)) return '';
    return '/'.trim($uploadDirectory, '/').'/'.$value;
}

function ptsProjectDetailExternalUrl($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value);
    if($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) return '';
    $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
    return in_array($scheme, array('http', 'https'), true) ? $value : '';
}

function ptsProjectDetailDate($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value);
    if($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}(?:[  T]\d{2}:\d{2}:\d{2})?$/', $value)) return '';
    $time = strtotime($value);
    return $time === false ? '' : date('Y-m-d', $time);
}

function ptsProjectDetailProductDto(array $row, $root = '')
{
    if(!ptsProjectDetailPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $name = ptsProjectDetailText(isset($row['namevi']) ? $row['namevi'] : '');
    $slug = ptsProjectDetailSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    if($id <= 0 || $name === '' || $slug === '') return null;
    return array(
        'id' => $id,
        'name' => $name,
        'slug' => $slug,
        'url' => '/san-pham/'.$slug,
        'code' => ptsProjectDetailText(isset($row['code']) ? $row['code'] : ''),
        'image' => ptsProjectDetailMedia(isset($row['photo']) ? $row['photo'] : '', $root, 'upload/product/')
    );
}

function ptsProjectDetailGalleryDto(array $row, $root = '')
{
    if(!ptsProjectDetailPublished(isset($row['status']) ? $row['status'] : '') || (isset($row['kind']) && $row['kind'] !== 'man')) return null;
    $val = isset($row['val']) ? (string)$row['val'] : '';
    $caption = ptsProjectDetailText(isset($row['namevi']) ? $row['namevi'] : '');
    $image = ptsProjectDetailMedia(isset($row['photo']) ? $row['photo'] : '', $root, 'upload/news/');
    if($val === 'du-an' && $image !== '') return array('type' => 'image', 'src' => $image, 'caption' => $caption);
    if($val === 'video')
    {
        $link = ptsProjectDetailExternalUrl(isset($row['link_video']) ? $row['link_video'] : '');
        if($link === '') return null;
        return array('type' => 'video', 'src' => $image, 'href' => $link, 'caption' => $caption);
    }
    return null;
}

function ptsProjectDetailPayload(array $row, array $gallery, array $relatedEquipment, $canonicalSources = array())
{
    if(!ptsProjectDetailPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $slug = ptsProjectDetailSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    $title = ptsProjectDetailText(isset($row['namevi']) ? $row['namevi'] : '');
    if($id <= 0 || $slug === '' || $title === '') return null;
    $categoryId = isset($row['category_id']) ? (int)$row['category_id'] : 0;
    $categoryName = ptsProjectDetailText(isset($row['category_name']) ? $row['category_name'] : '');
    $categorySlug = ptsProjectDetailSlug(isset($row['category_slug']) ? $row['category_slug'] : '');
    $category = ($categoryId > 0 && $categoryName !== '' && $categorySlug !== '') ? array(
        'id' => $categoryId,
        'name' => $categoryName,
        'slug' => $categorySlug,
        'url' => '/danh-muc-du-an/'.$categorySlug.'/'
    ) : null;
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'project' => array(
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'url' => '/'.$slug.'/',
            'summary' => ptsProjectDetailText(isset($row['descvi']) ? $row['descvi'] : ''),
            'coverImage' => isset($row['_pts_cover']) ? $row['_pts_cover'] : '',
            'category' => $category,
            'client' => ptsProjectDetailText(isset($row['tenkhachhang']) ? $row['tenkhachhang'] : ''),
            'address' => ptsProjectDetailText(isset($row['address']) ? $row['address'] : ''),
            'startDate' => ptsProjectDetailDate(isset($row['project_start_date']) ? $row['project_start_date'] : ''),
            'endDate' => ptsProjectDetailDate(isset($row['project_end_date']) ? $row['project_end_date'] : ''),
            'gallery' => array_values(array_filter($gallery)),
            'relatedEquipment' => array_values(array_filter($relatedEquipment))
        ),
        'content' => array(
            'format' => 'sanitized-html',
            'html' => ptsPublicRichTextSanitize(isset($row['contentvi']) ? $row['contentvi'] : ''),
            'sanitizedBy' => 'pts_public_richtext',
            'sanitizerVersion' => 1
        ),
        'canonical' => array('url' => '/'.$slug.'/', 'sources' => $canonicalSources),
        'parity' => array(
            'viewCount' => array('included' => false, 'reason' => 'read_only_preview_does_not_increment_legacy_view_count'),
            'contactSubmission' => array('included' => false, 'reason' => 'legacy_contact_flow_stays_php')
        )
    );
}
