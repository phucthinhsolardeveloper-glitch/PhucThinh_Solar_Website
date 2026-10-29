<?php
/** Pure DTO helpers for the read-only Nuxt dealer preview. */
require_once __DIR__.'/pts_public_richtext.php';

function ptsDealerPublished($status)
{
    return is_scalar($status) && in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsDealerText($value)
{
    if(!is_scalar($value)) return '';
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function ptsDealerRichText($value)
{
    return array(
        'format' => 'sanitized-html',
        'html' => ptsPublicRichTextSanitize($value),
        'sanitizedBy' => 'pts_public_richtext',
        'sanitizerVersion' => 1
    );
}

function ptsDealerMedia($value, $upload, $root)
{
    if(!is_scalar($value)) return '';
    $file = ltrim(trim((string)$value), '/');
    if($file === '' || basename($file) !== $file || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $file)) return '';
    if(!is_file($root.$upload.$file)) return '';
    return '/'.ltrim($upload, '/').$file;
}

function ptsDealerLink($value)
{
    if(!is_scalar($value)) return '';
    $link = trim((string)$value);
    if($link === '' || preg_match('/[\x00-\x20<>"\'`]/', $link) || preg_match('/^(?:javascript|data|vbscript):/i', $link)) return '';
    return ($link[0] === '/' || preg_match('/^https?:\/\//i', $link)) ? $link : '';
}

function ptsDealerItem(array $row, $root, $upload)
{
    if(!ptsDealerPublished($row['status'] ?? '')) return null;
    $title = ptsDealerText($row['namevi'] ?? '');
    if($title === '') return null;
    return array(
        'title' => $title,
        'description' => ptsDealerText($row['descvi'] ?? ''),
        'descriptionContent' => ptsDealerRichText($row['descvi'] ?? ''),
        'image' => ptsDealerMedia($row['photo'] ?? '', $upload, $root)
    );
}

function ptsDealerPayload(array $staticRows, array $photoRows, array $heroRows, array $supportRows, array $testimonialRows, array $statRows, $root, $hotline = '')
{
    $static = array();
    foreach($staticRows as $row) if(is_array($row) && ptsDealerPublished($row['status'] ?? '')) $static[$row['type'] ?? ''] = $row;
    $hero = null;
    foreach($heroRows as $row) {
        if(!is_array($row) || !ptsDealerPublished($row['status'] ?? '')) continue;
        $image = ptsDealerMedia($row['photo'] ?? '', 'upload/photo/', $root);
        if($image !== '') { $hero = array('title' => ptsDealerText($row['namevi'] ?? '') ?: 'Năng lực phân phối thiết bị năng lượng mặt trời', 'image' => $image); break; }
    }
    if(!$hero && !$heroRows && is_file($root.'assets/images/site-visuals/catalog-equipment-system.jpg')) $hero = array('title' => 'Thiết bị năng lượng mặt trời phân phối chính hãng', 'image' => '/assets/images/site-visuals/catalog-equipment-system.jpg');

    $products = array();
    foreach($photoRows as $row) if(is_array($row) && ptsDealerPublished($row['status'] ?? '')) {
        $title = ptsDealerText($row['namevi'] ?? ''); $image = ptsDealerMedia($row['photo'] ?? '', 'upload/photo/', $root); $link = ptsDealerLink($row['link'] ?? '');
        if($title !== '' && ($image !== '' || $link !== '')) $products[] = array('title' => $title, 'image' => $image, 'href' => $link);
    }
    $support = array(); foreach($supportRows as $row) { $item = is_array($row) ? ptsDealerItem($row, $root, 'upload/news/') : null; if($item) $support[] = $item; }
    $testimonials = array(); foreach($testimonialRows as $row) if(is_array($row) && ptsDealerPublished($row['status'] ?? '')) { $item = ptsDealerItem($row, $root, 'upload/news/'); if($item) { $item['quote'] = ptsDealerText($row['contentvi'] ?? ''); $testimonials[] = $item; } }
    $stats = array(); foreach($statRows as $row) if(is_array($row) && ptsDealerPublished($row['status'] ?? '')) { $label = ptsDealerText($row['namevi'] ?? ''); $value = ptsDealerText($row['solieu'] ?? ''); if($label !== '' && preg_match('/^[0-9][0-9.,]*$/', $value)) $stats[] = array('label' => $label, 'value' => $value); }
    $hotline = is_scalar($hotline) ? trim((string)$hotline) : '';
    return array(
        'schemaVersion' => 1, 'locale' => 'vi', 'path' => '/dai-ly/',
        'title' => ptsDealerText($static['dai-ly-1']['namevi'] ?? '') ?: 'Đại lý Phúc Thịnh Solar',
        'hero' => $hero,
        'intro' => ptsDealerRichText($static['dai-ly-1']['descvi'] ?? ''),
        'overview' => array(
            'title' => ptsDealerText($static['dai-ly-2']['namevi'] ?? ''),
            'description' => ptsDealerText($static['dai-ly-2']['descvi'] ?? ''),
            'descriptionContent' => ptsDealerRichText($static['dai-ly-2']['descvi'] ?? ''),
            'products' => $products
        ),
        'support' => array(
            'title' => ptsDealerText($static['dai-ly-3']['namevi'] ?? ''),
            'description' => ptsDealerText($static['dai-ly-3']['descvi'] ?? ''),
            'descriptionContent' => ptsDealerRichText($static['dai-ly-3']['descvi'] ?? ''),
            'items' => $support
        ),
        'testimonials' => $testimonials, 'stats' => $stats,
        'form' => array('action' => '/dai-ly/#dang-ky-dai-ly', 'method' => 'POST', 'title' => 'Đăng ký làm đối tác phân phối', 'note' => 'Để lại thông tin, Phúc Thịnh Solar sẽ liên hệ tư vấn chính sách đại lý phù hợp.'),
        'hotline' => $hotline === '' ? null : array('text' => $hotline, 'tel' => preg_replace('/[^0-9+]/', '', $hotline)),
        'labels' => array('overview' => 'Sản phẩm phân phối', 'support' => 'Quyền lợi đại lý', 'testimonials' => 'Phản hồi từ khách hàng và đối tác', 'stats' => 'Tự hào Phúc Thịnh Solar', 'empty' => 'Nội dung đang được cập nhật.')
    );
}
