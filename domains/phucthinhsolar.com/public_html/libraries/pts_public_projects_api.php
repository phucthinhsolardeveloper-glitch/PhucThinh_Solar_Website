<?php
/** Pure DTO helpers for the read-only Nuxt projects preview. */

function ptsProjectsPublished($status)
{
    if(!is_scalar($status)) return false;
    return in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsProjectsPlainText($value)
{
    if(!is_scalar($value)) return '';
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function ptsProjectsSlug($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value, " /\t\n\r\0\x0B");
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsProjectsMedia($value)
{
    if(!is_scalar($value)) return '';
    $value = ltrim(trim((string)$value), '/');
    if($value === '' || basename($value) !== $value) return '';
    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    return '/upload/news/'.$value;
}

function ptsProjectsCategoryDto(array $row)
{
    if(!ptsProjectsPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $name = ptsProjectsPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    $slug = ptsProjectsSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    if($id <= 0 || $name === '' || $slug === '') return null;
    return array(
        'id' => $id,
        'name' => $name,
        'slug' => $slug,
        'description' => ptsProjectsPlainText(isset($row['descvi']) ? $row['descvi'] : ''),
        'url' => '/danh-muc-du-an/'.$slug.'/',
        'image' => ptsProjectsMedia(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '')
    );
}

function ptsProjectsResolveCategoryHierarchy($d, $categoryType, array $segments)
{
    $specs = array('list'=>array('field'=>'id_list','depth'=>1),'cat'=>array('field'=>'id_cat','depth'=>2),'item'=>array('field'=>'id_item','depth'=>3),'sub'=>array('field'=>'id_sub','depth'=>4));
    if(!isset($specs[$categoryType]) || count($segments) !== $specs[$categoryType]['depth']) return null;
    foreach($segments as $segment) if(!is_string($segment) || ptsProjectsSlug($segment) !== $segment) return null;
    $tables = array('news_list','news_cat','news_item','news_sub');
    $parents = array('news_cat'=>'id_list','news_item'=>'id_cat','news_sub'=>'id_item');
    $parentId = 0;
    $row = null;
    foreach($segments as $index=>$segment)
    {
        $table = $tables[$index];
        if($index === 0) $row = $d->rawQueryOne("select id,namevi,descvi,slugvi,photo,status from #_{$table} where slugvi=? and type=? and find_in_set('hienthi',status) limit 1", array($segment,'du-an'));
        else
        {
            $parentField = $parents[$table];
            $row = $d->rawQueryOne("select id,namevi,descvi,slugvi,photo,status from #_{$table} where slugvi=? and {$parentField}=? and type=? and find_in_set('hienthi',status) limit 1", array($segment,$parentId,'du-an'));
        }
        if(empty($row) || (int)($row['id'] ?? 0) <= 0 || !ptsProjectsPublished($row['status'] ?? '') || ptsProjectsSlug($row['slugvi'] ?? '') !== $segment) return null;
        $parentId = (int)$row['id'];
    }
    $row['_pts_public_photo'] = isset($row['photo']) ? $row['photo'] : '';
    $metadata = array('type'=>'du-an','level'=>$categoryType,'path'=>implode('/', $segments));
    $row['_pts_public_category_metadata'] = $metadata;
    return array('row'=>$row,'article_field'=>$specs[$categoryType]['field'],'metadata'=>$metadata);
}

function ptsProjectsProjectDto(array $row, array $categories, $featured = false)
{
    if(!ptsProjectsPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $name = ptsProjectsPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    $slug = ptsProjectsSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    if($id <= 0 || $name === '' || $slug === '') return null;
    $category = null;
    $categoryId = isset($row['id_list']) ? (int)$row['id_list'] : 0;
    if($categoryId > 0 && isset($categories[$categoryId])) $category = $categories[$categoryId];
    return array(
        'id' => $id,
        'title' => $name,
        'slug' => $slug,
        'url' => '/'.$slug.'/',
        'description' => ptsProjectsPlainText(isset($row['descvi']) ? $row['descvi'] : ''),
        'address' => ptsProjectsPlainText(isset($row['address']) ? $row['address'] : ''),
        'image' => ptsProjectsMedia(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : ''),
        'category' => $category,
        'featured' => (bool)$featured
    );
}

function ptsProjectsCtaDto(array $rows, $hotline = '')
{
    foreach($rows as $row)
    {
        if(!is_array($row) || !ptsProjectsPublished(isset($row['status']) ? $row['status'] : '')) continue;
        $title = ptsProjectsPlainText(isset($row['namevi']) ? $row['namevi'] : '');
        $description = ptsProjectsPlainText(isset($row['descvi']) ? $row['descvi'] : '');
        $hotlineText = is_scalar($hotline) ? trim((string)$hotline) : '';
        return array(
            'title' => $title !== '' ? $title : 'Bạn có công trình cần tư vấn?',
            'description' => $description !== '' ? $description : 'Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ, tư vấn giải pháp điện mặt trời phù hợp nhất cho nhu cầu của bạn.',
            'buttonLabel' => ptsProjectsPlainText(isset($row['content1vi']) && trim((string)$row['content1vi']) !== '' ? $row['content1vi'] : 'Nhận tư vấn miễn phí'),
            'image' => ptsProjectsMedia(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : ''),
            'hotline' => $hotlineText === '' ? null : array(
                'label' => 'Gọi ngay hotline',
                'text' => $hotlineText,
                'tel' => preg_replace('/[^0-9+]/', '', $hotlineText)
            )
        );
    }
    return null;
}

function ptsProjectsPayload(array $categories, array $rows, array $featuredRow, array $ctaRows, $page, $perPage, $total, $category = null, $hotline = '', array $introRows = array(), array $categoryMetadata = array())
{
    $categoryMap = array();
    $categoryDtos = array();
    foreach($categories as $row)
    {
        if(!is_array($row)) continue;
        $dto = ptsProjectsCategoryDto($row);
        if($dto === null) continue;
        $categoryMap[(int)$row['id']] = $dto;
        $categoryDtos[] = $dto;
    }
    $projects = array();
    foreach($rows as $row)
    {
        if(!is_array($row)) continue;
        $dto = ptsProjectsProjectDto($row, $categoryMap, false);
        if($dto !== null) $projects[] = $dto;
    }
    $featured = null;
    if($featuredRow)
    {
        $featured = ptsProjectsProjectDto($featuredRow, $categoryMap, true);
    }
    $introVisibleRows = array_values(array_filter($introRows, static function($row) { return is_array($row) && ptsProjectsPublished(isset($row['status']) ? $row['status'] : ''); }));
    // Match project_hub.php: no configured row enables the fallback intro;
    // an explicitly configured but hidden row suppresses it on the root page.
    $introVisible = $category !== null || !$introRows || (bool)$introVisibleRows;
    $introRow = $introVisibleRows ? reset($introVisibleRows) : array();
    $introTitle = ptsProjectsPlainText(isset($introRow['namevi']) ? $introRow['namevi'] : '');
    $introDescription = ptsProjectsPlainText(isset($introRow['descvi']) ? $introRow['descvi'] : '');
    if($category !== null)
    {
        $introTitle = 'Dự án '.ptsProjectsPlainText(isset($category['name']) ? $category['name'] : '');
        $categoryDescription = ptsProjectsPlainText(isset($category['description']) ? $category['description'] : '');
        if($categoryDescription !== '') $introDescription = $categoryDescription;
    }
    if($introTitle === '') $introTitle = 'Dự án đã thực hiện';
    if($introDescription === '') $introDescription = 'Phúc Thịnh Solar tự hào đồng hành cùng khách hàng kiến tạo những công trình năng lượng sạch, bền vững cho tương lai.';
    if($category && $categoryMetadata) $category = array_merge($category, $categoryMetadata);
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'path' => '/du-an-da-thuc-hien/',
        'title' => $category ? 'Dự án '.($category['name'] ?? '') : 'Dự án đã thực hiện',
        'intro' => array(
            'visible' => $introVisible,
            'eyebrow' => ptsProjectsPlainText(isset($introRow['title_indexvi']) ? $introRow['title_indexvi'] : ''),
            'title' => $introTitle,
            'description' => $introDescription
        ),
        'category' => $category,
        'categories' => $categoryDtos,
        'featured' => $featured,
        'projects' => $projects,
        'pagination' => array('page' => (int)$page, 'perPage' => (int)$perPage, 'total' => max(0, (int)$total)),
        'cta' => ptsProjectsCtaDto($ctaRows, $hotline),
        'labels' => array(
            'all' => 'Tất cả',
            'filters' => 'Lọc dự án',
            'featured' => 'Dự án nổi bật',
            'view' => 'Xem dự án',
            'empty' => 'Chưa có dự án trong nhóm này.',
            'cta' => 'Liên hệ ngay'
        )
    );
}
