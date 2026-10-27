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
    return $slug === '' ? '' : '/san-pham/'.$slug;
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

function ptsCatalogBrandDto(array $row)
{
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $name = ptsCatalogPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    if($id <= 0 || $name === '') return null;
    return array('id' => $id, 'name' => $name);
}

function ptsCatalogSubcategoryDto(array $row)
{
    if(!ptsCatalogPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $name = ptsCatalogPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    $slug = ptsCatalogSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    if($id <= 0 || $name === '' || $slug === '') return null;
    return array('id' => $id, 'name' => $name, 'slug' => $slug);
}

/*
 * Doc dung 3 bac gia tu libraries/product_price_filter.php::productPriceFilterRanges()
 * - mot nguon duy nhat voi where-builder da dung o pts_v1_catalog.php, tranh
 * lech nhan hien thi voi logic loc that.
 */
function ptsCatalogPriceRangeDto()
{
    if(!function_exists('productPriceFilterRanges')) return array();
    $ranges = array();
    foreach(productPriceFilterRanges() as $key => $range)
    {
        if(!isset($range[2])) continue;
        $ranges[] = array('key' => (string)$key, 'label' => ptsCatalogPlainText($range[2]));
    }
    return $ranges;
}

/*
 * Whitelist sap xep - khop dung cac option that cua sources/product.php
 * (switch $orderby). 'price'/'price-desc' van duoc chap nhan la gia tri
 * selected hop le ke ca khi publicProductPricesVisible() dang tat (giong
 * legacy: dropdown van giu lua chon, chi order by thuc te la khong doi) -
 * xem ptsCatalogSortOrderBy() cho phan ap dung.
 */
function ptsCatalogSortKey($value)
{
    $allowed = array('default', 'abc', 'abc-desc', 'price', 'price-desc', 'new', 'new-desc');
    $value = is_scalar($value) ? (string)$value : '';
    return in_array($value, $allowed, true) ? $value : 'default';
}

/*
 * Tra ve fragment ORDER BY co dinh cho mot sort key da whitelist, hoac null
 * khi khong co sap xep rieng (dung default p.id desc o noi goi). 'price'/
 * 'price-desc' tra null khi gia cong khai dang tat - dung hanh vi "break"
 * cua switch legacy (khong tu suy doan sap xep theo gia an).
 */
function ptsCatalogSortOrderBy($key, $pricesVisible)
{
    switch($key)
    {
        case 'abc': return 'p.namevi asc';
        case 'abc-desc': return 'p.namevi desc';
        case 'price': return $pricesVisible ? 'p.regular_price asc' : null;
        case 'price-desc': return $pricesVisible ? 'p.regular_price desc' : null;
        case 'new': return 'p.date_created desc';
        case 'new-desc': return 'p.date_created asc';
        default: return null;
    }
}

function ptsCatalogPayload(array $category, array $categories, array $rows, $page, $perPage, $total, $pricesVisible = false, array $filterOptions = array(), array $selectedFilters = array())
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
    $brandDtos = array();
    foreach(isset($filterOptions['brands']) && is_array($filterOptions['brands']) ? $filterOptions['brands'] : array() as $row)
    {
        if(!is_array($row)) continue;
        $dto = ptsCatalogBrandDto($row);
        if($dto !== null) $brandDtos[] = $dto;
    }
    $subcategoryDtos = array();
    foreach(isset($filterOptions['subcategories']) && is_array($filterOptions['subcategories']) ? $filterOptions['subcategories'] : array() as $row)
    {
        if(!is_array($row)) continue;
        $dto = ptsCatalogSubcategoryDto($row);
        if($dto !== null) $subcategoryDtos[] = $dto;
    }
    $selectedBrandIds = isset($selectedFilters['brandIds']) && is_array($selectedFilters['brandIds']) ? array_values(array_unique(array_map('intval', $selectedFilters['brandIds']))) : array();
    $selectedPriceKeys = isset($selectedFilters['priceKeys']) && is_array($selectedFilters['priceKeys']) ? array_values(array_unique(array_map('strval', $selectedFilters['priceKeys']))) : array();
    $selectedSubcategoryIds = isset($selectedFilters['subcategoryIds']) && is_array($selectedFilters['subcategoryIds']) ? array_values(array_unique(array_map('intval', $selectedFilters['subcategoryIds']))) : array();
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'category' => $category,
        'categories' => $categoryDtos,
        'products' => $products,
        'pagination' => array('page' => (int)$page, 'perPage' => (int)$perPage, 'total' => max(0, (int)$total)),
        'filters' => array(
            'brands' => $brandDtos,
            'priceRanges' => ptsCatalogPriceRangeDto(),
            'subcategories' => $subcategoryDtos
        ),
        'selected' => array(
            'brandIds' => $selectedBrandIds,
            'priceKeys' => $selectedPriceKeys,
            'subcategoryIds' => $selectedSubcategoryIds,
            'sort' => ptsCatalogSortKey(isset($selectedFilters['sort']) ? $selectedFilters['sort'] : '')
        ),
        'parity' => array(
            'prices' => $pricesVisible ? array('included' => true) : array('included' => false, 'reason' => 'public_pricing_disabled'),
            'filters' => array('included' => true),
            'sort' => array('included' => true)
        )
    );
}
