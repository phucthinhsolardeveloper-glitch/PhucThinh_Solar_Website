<?php
/** Pure read-only DTO helpers for the published news detail contract. */

require_once __DIR__.'/pts_public_richtext.php';
require_once __DIR__.'/news_detail_media.php';

function ptsNewsDetailPublished($status)
{
    if(!is_scalar($status)) return false;
    return in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsNewsDetailText($value)
{
    if(!is_scalar($value)) return '';
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/p|/li|/h[1-6])>~i', "\n", $value);
    return trim(preg_replace('/[ \t\r\f\v]+/u', ' ', strip_tags($value)));
}

function ptsNewsDetailSlug($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value, " /\t\n\r\0\x0B");
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsNewsDetailImage($value)
{
    if(!is_scalar($value)) return '';
    $value = ltrim(trim((string)$value), '/');
    if($value === '' || basename($value) !== $value) return '';
    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    return '/upload/news/'.$value;
}

function ptsNewsDetailVideo($value)
{
    if(!is_scalar($value)) return '';
    $url = trim((string)$value);
    if($url === '' || preg_match('/[\\x00-\\x1F<>"]/', $url) || strpos($url, chr(92)) !== false) return '';
    $parts = parse_url($url);
    if(!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https'
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return '';
    $host = strtolower($parts['host'] ?? '');
    if(!in_array($host, array('youtube.com', 'www.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com'), true)) return '';
    return $url;
}

function ptsNewsDetailAuthor($value)
{
    $name = ptsNewsDetailText($value);
    return $name === '' ? 'Phúc Thịnh Solar' : $name;
}

function ptsNewsDetailDate($value)
{
    $timestamp = is_numeric($value) ? (int)$value : 0;
    return $timestamp > 0 ? date('Y-m-d', $timestamp) : '';
}

function ptsNewsDetailCtaDto(array $rows, $hotline = '')
{
    $configured = (bool)$rows;
    $cta = array();
    foreach($rows as $row)
    {
        if(!is_array($row) || !ptsNewsDetailPublished(isset($row['status']) ? $row['status'] : '')) continue;
        $cta = $row;
        break;
    }

    // An explicit Admin hide suppresses the section; a missing record uses the
    // same safe fallback copy as the legacy news hub.
    if($configured && !$cta) return null;

    $title = ptsNewsDetailText(isset($cta['namevi']) ? $cta['namevi'] : '') ?: 'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng hỗ trợ';
    $description = ptsNewsDetailText(isset($cta['descvi']) ? $cta['descvi'] : '') ?: 'Liên hệ ngay để được tư vấn và cập nhật những thông tin mới nhất.';
    $buttonLabel = ptsNewsDetailText(isset($cta['content1vi']) ? $cta['content1vi'] : '') ?: 'Nhận tư vấn ngay';
    $phone = is_scalar($hotline) ? trim((string)$hotline) : '';
    $tel = preg_replace('/[^0-9+]/', '', $phone);
    $phoneDto = $phone !== '' && $tel !== ''
        ? array('label' => 'Gọi ngay hotline', 'text' => ptsNewsDetailText($phone), 'tel' => $tel)
        : null;

    return array(
        'title' => $title,
        'description' => $description,
        'buttonLabel' => $buttonLabel,
        'image' => ptsNewsDetailImage(isset($cta['_pts_public_photo']) ? $cta['_pts_public_photo'] : ''),
        'hotline' => $phoneDto
    );
}

function ptsNewsDetailMediaDto(array $photoRows, array $videoRows)
{
    $gallery = array();
    foreach($photoRows as $row) {
        if(!is_array($row)) continue;
        $image = ptsNewsDetailImage(isset($row['photo']) ? $row['photo'] : '');
        if($image !== '') $gallery[] = array('image' => $image, 'alt' => ptsNewsDetailText(isset($row['namevi']) ? $row['namevi'] : ''));
    }
    $videos = array();
    foreach($videoRows as $row) {
        if(!is_array($row)) continue;
        $url = ptsNewsDetailVideo(isset($row['link_video']) ? $row['link_video'] : '');
        if($url !== '') $videos[] = array('url' => $url, 'title' => ptsNewsDetailText(isset($row['namevi']) ? $row['namevi'] : ''));
    }
    return array('gallery' => $gallery, 'videos' => $videos);
}

function ptsNewsDetailArticleDto(array $row, array $category, array $author, array $media, array $seo)
{
    if(($row['type'] ?? 'tin-tuc') !== 'tin-tuc' || !ptsNewsDetailPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $title = ptsNewsDetailText(isset($row['namevi']) ? $row['namevi'] : '');
    $slug = ptsNewsDetailSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    if($id <= 0 || $title === '' || $slug === '') return null;
    $seoTitle = ptsNewsDetailText(isset($seo['titlevi']) ? $seo['titlevi'] : '') ?: $title;
    if(stripos($seoTitle, 'phúc thịnh s') === false) $seoTitle .= ' | Phúc Thịnh Solar';
    $description = ptsNewsDetailText(isset($row['descvi']) ? $row['descvi'] : '');
    /*
     * Tích hợp sanitizer (RICHTEXT-CONTRACT.md): contentvi và content1vi đi qua
     * pts_public_richtext.php thay vì strip_tags() về plain-text. Sanitizer
     * tự decode một lớp HTML-entity encoding của CMS và trả về allowlist HTML
     * an toàn cho v-html ở Nuxt — không còn mất định dạng heading/list/ảnh
     * như bản plain-text trước đây.
     */
    $contentHtml = ptsPublicRichTextSanitize(isset($row['contentvi']) ? $row['contentvi'] : '');
    $quoteHtml = ptsPublicRichTextSanitize(isset($row['content1vi']) ? $row['content1vi'] : '');
    $datePublished = ptsNewsDetailDate(isset($row['date_created']) ? $row['date_created'] : 0);
    $dateModified = ptsNewsDetailDate(isset($row['date_updated']) && (int)$row['date_updated'] > 0 ? $row['date_updated'] : (isset($row['date_created']) ? $row['date_created'] : 0));
    $coverFilename = isset($row['photo']) ? $row['photo'] : '';
    $cover = ptsNewsDetailImage(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '');
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'id' => $id,
        'title' => $title,
        'slug' => $slug,
        'canonical' => '/'.$slug.'/',
        'date' => $datePublished,
        'datePublished' => $datePublished,
        'dateModified' => $dateModified,
        'author' => array('name' => ptsNewsDetailAuthor(isset($author['fullname']) ? $author['fullname'] : '')),
        'description' => $description,
        'cover' => $cover,
        'showCover' => $cover !== '' && !newsDetailContentRepeatsCover(isset($row['contentvi']) ? $row['contentvi'] : '', $coverFilename),
        'content' => array('format' => 'sanitized-html', 'html' => $contentHtml, 'sanitizedBy' => 'pts_public_richtext', 'sanitizerVersion' => 1),
        'quote' => $quoteHtml !== '' ? array('format' => 'sanitized-html', 'html' => $quoteHtml, 'sanitizedBy' => 'pts_public_richtext', 'sanitizerVersion' => 1) : null,
        'category' => $category,
        'media' => $media,
        'seo' => array(
            'title' => $seoTitle,
            'description' => ptsNewsDetailText(isset($seo['descriptionvi']) ? $seo['descriptionvi'] : '') ?: $description,
            'keywords' => ptsNewsDetailText(isset($seo['keywordsvi']) ? $seo['keywordsvi'] : ''),
            'canonical' => '/'.$slug
        ),
        'readOnly' => true,
        'legacyViewMutation' => false
    );
}

function ptsNewsDetailRelatedDto(array $rows)
{
    $result = array();
    foreach($rows as $row) {
        if(!is_array($row) || !ptsNewsDetailPublished(isset($row['status']) ? $row['status'] : '')) continue;
        $slug = ptsNewsDetailSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
        $title = ptsNewsDetailText(isset($row['namevi']) ? $row['namevi'] : '');
        if((int)($row['id'] ?? 0) <= 0 || $slug === '' || $title === '') continue;
        $result[] = array(
            'id' => (int)$row['id'], 'title' => $title, 'slug' => $slug,
            'canonical' => '/'.$slug.'/', 'date' => !empty($row['date_created']) ? date('Y-m-d', (int)$row['date_created']) : '',
            'author' => ptsNewsDetailAuthor(isset($row['_pts_public_author']) ? $row['_pts_public_author'] : ''),
            'description' => ptsNewsDetailText(isset($row['descvi']) ? $row['descvi'] : ''),
            'image' => ptsNewsDetailImage(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '')
        );
    }
    return $result;
}
