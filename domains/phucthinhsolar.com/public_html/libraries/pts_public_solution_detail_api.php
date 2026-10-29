<?php
/** Pure, read-only DTO helpers for the Nuxt solution detail preview. */

require_once __DIR__.'/pts_public_richtext.php';

function ptsSolutionDetailPublished($status)
{
    return is_scalar($status) && in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsSolutionDetailSlug($value)
{
    $value = is_scalar($value) ? trim((string)$value, " /\t\n\r\0\x0B") : '';
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsSolutionDetailText($value)
{
    if(!is_scalar($value)) return '';
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function ptsSolutionDetailParagraphs($value)
{
    if(!is_scalar($value) || trim((string)$value) === '') return array();
    $html = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $html = preg_replace('~<(?:script|style)\b[^>]*>.*?</(?:script|style)>~is', '', $html);
    $html = preg_replace('~(?:</p\s*>|<br\s*/?>|</li\s*>)~i', "\n", $html);
    $lines = preg_split('/\R+/u', strip_tags($html));
    $paragraphs = array();
    foreach((array)$lines as $line)
    {
        $line = ptsSolutionDetailText($line);
        if($line !== '') $paragraphs[] = $line;
    }
    return $paragraphs;
}

function ptsSolutionDetailMediaUrl($value, $root = '', $uploadDirectory = 'upload/news/')
{
    $value = is_scalar($value) ? ltrim(trim((string)$value), '/') : '';
    if($value === '' || basename($value) !== $value || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    if($root !== '' && !is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.trim($uploadDirectory, '/\\').DIRECTORY_SEPARATOR.$value)) return '';
    return '/'.trim($uploadDirectory, '/').'/'.$value;
}

function ptsSolutionDetailCampaignCta(array $rows, $hotline = '')
{
    $visible = solutionHubSelect($rows, '', 1);
    if($rows && !$visible) return null;

    $row = $visible ? $visible[0] : array();
    $buttonLabel = solutionHubField($row, 'title_index', 'vi') ?: 'Nhận tư vấn';
    $image = isset($row['_pts_public_photo']) && is_string($row['_pts_public_photo'])
        && preg_match('~^/upload/news/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$~i', $row['_pts_public_photo'])
        ? $row['_pts_public_photo']
        : '/assets/images/site-visuals/catalog-street-lighting.jpg';
    $hotline = is_scalar($hotline) ? trim((string)$hotline) : '';
    $hotlineTel = preg_replace('/[^0-9]/', '', $hotline);
    if($hotlineTel !== '' && strpos($hotline, '+') === 0) $hotlineTel = '+'.$hotlineTel;

    return array(
        'title' => solutionHubField($row, 'name', 'vi') ?: 'Chưa biết giải pháp nào phù hợp?',
        'description' => solutionHubField($row, 'desc', 'vi') ?: 'Chia sẻ nhu cầu của bạn. Đội ngũ Phúc Thịnh Solar sẽ hỗ trợ lựa chọn giải pháp phù hợp.',
        'buttonLabel' => $buttonLabel,
        'image' => $image,
        'links' => array('consult' => array('label' => $buttonLabel, 'href' => '#ptsContactModal')),
        'hotline' => $hotlineTel !== '' ? array('label' => 'Gọi ngay hotline', 'text' => $hotline, 'tel' => $hotlineTel) : null
    );
}

function ptsSolutionDetailCanonical($slug)
{
    $slug = ptsSolutionDetailSlug($slug);
    return $slug === '' ? '' : '/giai-phap/'.$slug.'/';
}

function ptsSolutionDetailPayload(array $row, array $gallery, array $solutionRows, array $productRows, array $ctaRows = array(), $hotline = '')
{
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $slug = ptsSolutionDetailSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    $title = ptsSolutionDetailText(isset($row['namevi']) ? $row['namevi'] : '');
    if($id <= 0 || !ptsSolutionDetailPublished(isset($row['status']) ? $row['status'] : '') || $slug === '' || $title === '') return null;

    $navigation = array();
    foreach(solutionHubSelect($solutionRows, 'solutionhub') as $item)
    {
        $itemSlug = solutionHubDetailField($item, 'slug');
        $itemTitle = solutionHubDetailField($item, 'title_index') ?: solutionHubDetailField($item, 'name');
        if($itemSlug === '' || $itemTitle === '') continue;
        $navigation[] = array(
            'title' => $itemTitle,
            'slug' => $itemSlug,
            'url' => ptsSolutionDetailCanonical($itemSlug),
            'image' => isset($item['_pts_public_photo1']) && $item['_pts_public_photo1'] !== '' ? $item['_pts_public_photo1'] : (isset($item['_pts_public_photo']) ? $item['_pts_public_photo'] : ''),
            'active' => (int)$item['id'] === $id
        );
    }

    $media = array();
    foreach($gallery as $item) if(isset($item['photo']) && $item['photo'] !== '') $media[] = $item['photo'];
    $media = array_values(array_unique(array_filter($media)));
    /*
     * Tích hợp sanitizer: contentvi/content1vi đi qua pts_public_richtext.php
     * thay vì ptsSolutionDetailParagraphs() (vốn strip_tags về plain-text theo
     * dòng). Giữ allowlist HTML thật — heading, list, ảnh trong bài không còn
     * bị phẳng hoá về chuỗi text rời rạc.
     */
    $bodyHtml = ptsPublicRichTextSanitize(isset($row['contentvi']) ? $row['contentvi'] : '');
    $extraHtml = ptsPublicRichTextSanitize(isset($row['content1vi']) ? $row['content1vi'] : '');

    $equipment = array();
    foreach(solutionHubSelect($productRows, 'solutionhub', 4) as $item)
    {
        $name = solutionHubDetailField($item, 'name');
        if($name === '') continue;
        $equipment[] = array(
            'name' => $name,
            'description' => solutionHubDetailField($item, 'desc'),
            'url' => '/'.ltrim(publicProductCategoryPath($item, 'list', !empty($item['slugvi']) ? 'vi' : 'vi'), '/'),
            'image' => isset($item['_pts_public_photo']) ? $item['_pts_public_photo'] : ''
        );
    }

    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'path' => ptsSolutionDetailCanonical($slug),
        'solution' => array(
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'url' => ptsSolutionDetailCanonical($slug),
            'summary' => ptsSolutionDetailText(isset($row['descvi']) ? $row['descvi'] : ''),
            'image' => isset($row['_pts_public_photo1']) && $row['_pts_public_photo1'] !== '' ? $row['_pts_public_photo1'] : (isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : ''),
            'content' => array('format' => 'sanitized-html', 'html' => $bodyHtml, 'sanitizedBy' => 'pts_public_richtext', 'sanitizerVersion' => 1),
            'extraContent' => array('format' => 'sanitized-html', 'html' => $extraHtml, 'sanitizedBy' => 'pts_public_richtext', 'sanitizerVersion' => 1),
            'gallery' => $media
        ),
        'navigation' => array('allUrl' => '/giai-phap/', 'items' => $navigation),
        'equipment' => $equipment,
        'cta' => array('label' => 'Nhận tư vấn giải pháp', 'href' => '/lien-he/#yeu-cau-tu-van'),
        'campaignCta' => ptsSolutionDetailCampaignCta($ctaRows, $hotline),
        /*
         * editorial giờ CHỈ còn nói về mục lục (TOC) tự sinh từ heading — HTML
         * thật đã có ở solution.content/extraContent phía trên. Xây TOC cần
         * phân tích cấu trúc heading (id, cấp, thứ tự) thành điều hướng riêng,
         * không chỉ sanitize phẳng, nên vẫn để dành cho một task khác.
         */
        'editorial' => array('rendered' => false, 'reason' => 'toc_generation_not_implemented'),
        'parity' => array('toc' => array('included' => false, 'reason' => 'toc_generation_not_implemented'))
    );
}

function solutionHubDetailField(array $row, $field)
{
    $value = isset($row[$field.'vi']) ? ptsSolutionDetailText($row[$field.'vi']) : '';
    return $value !== '' ? $value : (isset($row[$field.'en']) ? ptsSolutionDetailText($row[$field.'en']) : '');
}
