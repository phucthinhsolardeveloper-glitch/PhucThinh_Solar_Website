<?php
/** Pure, allow-listed DTO helpers for the Nuxt catalog migration. */

function ptsCatalogPublished($status)
{
    if(!is_scalar($status)) return false;
    return in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsCatalogSlug($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value, " /\t\n\r\0\x0B");
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsCatalogPlainText($value)
{
    if(!is_scalar($value)) return '';
    return trim(preg_replace('/\s+/u', ' ', strip_tags(htmlspecialchars_decode((string)$value, ENT_QUOTES))));
}

function ptsCatalogCategoryUrl(array $slugs)
{
    $clean = array();
    foreach($slugs as $slug)
    {
        $slug = ptsCatalogSlug($slug);
        if($slug === '') return '';
        $clean[] = $slug;
    }
    return $clean ? '/danh-muc-san-pham/'.implode('/', $clean).'/' : '';
}

function ptsCatalogProductUrl($slug)
{
    $slug = ptsCatalogSlug($slug);
    return $slug === '' ? '' : '/san-pham/'.$slug.'/';
}

function ptsCatalogPublicMedia($value)
{
    if(!is_scalar($value)) return '';
    $value = ltrim(trim((string)$value), '/');
    if($value === '' || basename($value) !== $value) return '';
    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    return '/upload/product/'.$value;
}

function ptsCatalogPositiveNumber($value)
{
    if(!is_scalar($value) || !is_numeric($value)) return null;
    $number = (float)$value;
    return is_finite($number) && $number > 0 ? $number : null;
}

function ptsCatalogMoney($value)
{
    $number = ptsCatalogPositiveNumber($value);
    return $number === null || $number > PHP_INT_MAX ? null : (int)round($number);
}

function ptsCatalogPricingDto(array $row, $pricesVisible)
{
    $regular = ptsCatalogMoney(isset($row['regular_price']) ? $row['regular_price'] : null);
    $sale = ptsCatalogMoney(isset($row['sale_price']) ? $row['sale_price'] : null);
    $statuses = array_filter(array_map('trim', explode(',', isset($row['status']) ? (string)$row['status'] : '')));
    $mode = $pricesVisible && !in_array('baogia', $statuses, true) && ($regular !== null || $sale !== null) ? 'price' : 'quote';
    return array(
        'mode' => $mode,
        'currency' => 'VND',
        'regularPrice' => $mode === 'price' ? $regular : null,
        'salePrice' => $mode === 'price' ? $sale : null
    );
}

function ptsCatalogProductDto(array $row, $pricesVisible = false)
{
    if(!ptsCatalogPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $name = ptsCatalogPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    $slug = ptsCatalogSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    $url = ptsCatalogProductUrl($slug);
    if($id <= 0 || $name === '' || $url === '') return null;
    $media = array();
    foreach(array('photo', 'photo2') as $field)
    {
        $path = ptsCatalogPublicMedia(isset($row['_pts_public_'.$field]) ? $row['_pts_public_'.$field] : (isset($row[$field]) ? $row[$field] : ''));
        if($path !== '' && !in_array($path, $media, true)) $media[] = $path;
    }
    return array(
        'id' => $id,
        'name' => $name,
        'slug' => $slug,
        'url' => $url,
        'media' => $media,
        'powerWatts' => ptsCatalogPositiveNumber(isset($row['cong_suat']) ? $row['cong_suat'] : null),
        'pricing' => ptsCatalogPricingDto($row, $pricesVisible)
    );
}

function ptsCatalogCategoryDto(array $row, array $slugs)
{
    if(!ptsCatalogPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $name = ptsCatalogPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    $url = ptsCatalogCategoryUrl($slugs);
    if($id <= 0 || $name === '' || $url === '') return null;
    return array('id' => $id, 'name' => $name, 'slug' => end($slugs), 'url' => $url);
}

function ptsCatalogPayload(array $category, array $categories, array $rows, $page, $perPage, $total, $pricesVisible = false)
{
    $products = array();
    foreach($rows as $row)
    {
        if(!is_array($row)) continue;
        $dto = ptsCatalogProductDto($row, $pricesVisible);
        if($dto !== null) $products[] = $dto;
    }
    $categoryDtos = array();
    foreach($categories as $row)
    {
        if(!is_array($row)) continue;
        $dto = ptsCatalogCategoryDto($row, array(isset($row['slugvi']) ? $row['slugvi'] : ''));
        if($dto !== null) $categoryDtos[] = $dto;
    }
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'category' => $category,
        'categories' => $categoryDtos,
        'products' => $products,
        'pagination' => array('page' => (int)$page, 'perPage' => (int)$perPage, 'total' => max(0, (int)$total)),
        'parity' => array(
            'prices' => $pricesVisible ? array('included' => true) : array('included' => false, 'reason' => 'public_pricing_disabled'),
            'filters' => array('included' => false, 'reason' => 'legacy_filter_parity_not_verified'),
            'sort' => array('included' => false, 'reason' => 'legacy_sort_parity_not_verified')
        )
    );
}
