<?php
/** Pure DTO helpers for the read-only Nuxt news preview. */

function ptsNewsPublished($status)
{
    if(!is_scalar($status)) return false;
    return in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsNewsHasStatus($status, $flag)
{
    if(!is_scalar($status)) return false;
    return in_array($flag, array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsNewsPlainText($value)
{
    if(!is_scalar($value)) return '';
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function ptsNewsSlug($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value, " /\t\n\r\0\x0B");
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsNewsMedia($value)
{
    if(!is_scalar($value)) return '';
    $value = ltrim(trim((string)$value), '/');
    if($value === '' || basename($value) !== $value) return '';
    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    return '/upload/news/'.$value;
}

function ptsNewsCategoryDto(array $row)
{
    if(!ptsNewsPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $name = ptsNewsPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    $slug = ptsNewsSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    if($id <= 0 || $name === '' || $slug === '') return null;
    return array(
        'id' => $id,
        'name' => $name,
        'description' => ptsNewsPlainText(isset($row['descvi']) ? $row['descvi'] : ''),
        'slug' => $slug,
        'url' => '/'.$slug.'/',
        'image' => ptsNewsMedia(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '')
    );
}

function ptsNewsReadingMinutes($content)
{
    $text = ptsNewsPlainText($content);
    if($text === '') return 0;
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    return max(1, (int)ceil(count($words) / 220));
}

function ptsNewsArticleDto(array $row, array $categories = array(), $featured = false)
{
    if(!ptsNewsPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $title = ptsNewsPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    $slug = ptsNewsSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    if($id <= 0 || $title === '' || $slug === '') return null;
    $content = ptsNewsPlainText(isset($row['contentvi']) ? $row['contentvi'] : '');
    $description = ptsNewsPlainText(isset($row['descvi']) ? $row['descvi'] : '');
    if($description === '') $description = $content;
    if(mb_strlen($description, 'UTF-8') > 180) $description = mb_substr($description, 0, 177, 'UTF-8').'…';
    $category = null;
    $categoryId = isset($row['id_list']) ? (int)$row['id_list'] : 0;
    if($categoryId > 0 && isset($categories[$categoryId])) $category = $categories[$categoryId];
    $date = !empty($row['date_created']) ? date('Y-m-d', (int)$row['date_created']) : '';
    return array(
        'id' => $id,
        'title' => $title,
        'slug' => $slug,
        'url' => '/'.$slug.'/',
        'description' => $description,
        'date' => $date,
        'image' => ptsNewsMedia(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : ''),
        'readingMinutes' => ptsNewsReadingMinutes($content),
        'category' => $category,
        'featured' => (bool)$featured
    );
}

function ptsNewsSelectFeatured(array $rows, $hasNewshubSelection = false)
{
    $selected = array();
    foreach($rows as $row) {
        if(!is_array($row) || !ptsNewsPublished(isset($row['status']) ? $row['status'] : '')) continue;
        if($hasNewshubSelection && !ptsNewsHasStatus(isset($row['status']) ? $row['status'] : '', 'newshub')) continue;
        $selected[] = $row;
        if(count($selected) >= 3) break;
    }
    return $selected;
}

function ptsNewsCtaDto(array $rows, $hotline = '')
{
    foreach($rows as $row) {
        if(!is_array($row) || !ptsNewsPublished(isset($row['status']) ? $row['status'] : '')) continue;
        $title = ptsNewsPlainText(isset($row['namevi']) ? $row['namevi'] : '');
        $description = ptsNewsPlainText(isset($row['descvi']) ? $row['descvi'] : '');
        if($title === '' && $description === '') continue;
        $phone = is_scalar($hotline) ? trim((string)$hotline) : '';
        return array(
            'title' => $title,
            'description' => $description,
            'buttonLabel' => ptsNewsPlainText(isset($row['content1vi']) ? $row['content1vi'] : ''),
            'image' => ptsNewsMedia(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : ''),
            'hotline' => $phone === '' ? null : array('label' => 'Gọi ngay hotline', 'text' => $phone, 'tel' => preg_replace('/[^0-9+]/', '', $phone))
        );
    }
    return null;
}

function ptsNewsPayload(array $categories, array $rows, array $featuredRows, array $intro, $introConfigured, array $ctaRows, $ctaConfigured, $page, $total, $query, $category, $hotline, $heroEnabled)
{
    $categoryMap = array();
    $categoryDtos = array();
    foreach($categories as $row) {
        if(!is_array($row)) continue;
        $dto = ptsNewsCategoryDto($row);
        if($dto === null) continue;
        $categoryMap[(int)$row['id']] = $dto;
        $categoryDtos[] = $dto;
    }
    $articles = array();
    foreach($rows as $row) {
        if(!is_array($row)) continue;
        $dto = ptsNewsArticleDto($row, $categoryMap, false);
        if($dto !== null) $articles[] = $dto;
    }
    $featured = array();
    foreach($featuredRows as $row) {
        if(!is_array($row)) continue;
        $dto = ptsNewsArticleDto($row, $categoryMap, true);
        if($dto !== null) $featured[] = $dto;
    }
    $introRow = $intro;
    $introTitle = ptsNewsPlainText(isset($introRow['namevi']) ? $introRow['namevi'] : '');
    $introDescription = ptsNewsPlainText(isset($introRow['descvi']) ? $introRow['descvi'] : '');
    $cta = ptsNewsCtaDto($ctaRows, $hotline);
    $ctaVisible = false;
    foreach($ctaRows as $row) if(is_array($row) && ptsNewsPublished(isset($row['status']) ? $row['status'] : '')) { $ctaVisible = true; break; }
    if(($ctaVisible || !$ctaConfigured) && $cta === null) {
        $phone = is_scalar($hotline) ? trim((string)$hotline) : '';
        $cta = array('title' => 'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng hỗ trợ', 'description' => 'Liên hệ ngay để được tư vấn và cập nhật những thông tin mới nhất.', 'buttonLabel' => 'Nhận tư vấn ngay', 'image' => '', 'hotline' => $phone === '' ? null : array('label' => 'Gọi ngay hotline', 'text' => $phone, 'tel' => preg_replace('/[^0-9+]/', '', $phone)));
    }
    $introVisible = !$introConfigured || (bool)$introRow;
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'path' => '/tin-tuc/',
        'title' => $category ? $category['name'] : ($introTitle !== '' ? $introTitle : 'Kiến thức'),
        'description' => $category ? ($category['description'] ?? '') : ($introDescription !== '' ? $introDescription : 'Kiến thức năng lượng mặt trời, thông tin sản phẩm và câu chuyện từ Phúc Thịnh Solar.'),
        'category' => $category,
        'categories' => $categoryDtos,
        'featured' => $heroEnabled && !$category && $query === '' ? $featured : array(),
        'articles' => $articles,
        'pagination' => array('page' => (int)$page, 'perPage' => 6, 'total' => max(0, (int)$total)),
        'intro' => $introVisible ? array('title' => $introTitle !== '' ? $introTitle : 'Kiến thức', 'description' => $introDescription !== '' ? $introDescription : 'Kiến thức năng lượng mặt trời, thông tin sản phẩm và câu chuyện từ Phúc Thịnh Solar.') : null,
        'cta' => $cta,
        'labels' => array('all' => 'Tất cả', 'search' => 'Tìm kiếm bài viết', 'featured' => 'Bài viết nổi bật', 'results' => 'Bài viết', 'empty' => 'Chưa tìm thấy bài viết phù hợp.', 'reset' => 'Xem tất cả bài viết', 'read' => 'phút đọc', 'pages' => 'Trang bài viết', 'readMore' => 'Đọc tiếp', 'ctaPhone' => 'Gọi ngay hotline')
    );
}
