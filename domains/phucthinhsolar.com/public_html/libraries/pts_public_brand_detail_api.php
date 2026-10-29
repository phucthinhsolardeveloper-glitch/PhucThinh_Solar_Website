<?php
/**
 * Pure, read-only DTO helpers for the Nuxt brand detail preview
 * (/thuong-hieu/{slug}/).
 *
 * QUAN TRONG - xac minh truoc khi viet: ban dau file nay co them logo/summary/
 * content (sanitize qua pts_public_richtext.php), dua tren viec admin CO the
 * nhap mo ta/noi dung cho brand. Doc lai templates/product/product_tpl.php va
 * product_detail_tpl.php (grep "productBrand[") cho thay KHONG co template
 * cong khai nao render logo/desc/content cua #_product_brand - trang
 * /thuong-hieu/{slug}/ dung chung template "product/product" voi catalog,
 * chi set H1/breadcrumb = ten brand roi liet ke san pham loc theo id_brand.
 * Giu dung nhu vay, khong dung lai data chua tung hien thi that.
 */

function ptsBrandDetailPublished($status)
{
    return is_scalar($status) && in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsBrandDetailSlug($value)
{
    $value = is_scalar($value) ? trim((string)$value, " /\t\n\r\0\x0B") : '';
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsBrandDetailText($value)
{
    if(!is_scalar($value)) return '';
    return trim(preg_replace('/\s+/u', ' ', strip_tags(htmlspecialchars_decode((string)$value, ENT_QUOTES))));
}

function ptsBrandDetailCanonical($slug)
{
    $slug = ptsBrandDetailSlug($slug);
    /*
     * CO dau / cuoi: khac PDP ("/san-pham/{slug}" khong co - xac minh qua
     * redirect 301 legacy o router.php). Brand la trang danh sach san pham
     * theo thuong hieu, cung nhom voi product_list/category (publicProductCategoryPath()
     * luon tra ve co dau '/' cuoi) chu khong phai leaf product.
     */
    return $slug === '' ? '' : '/thuong-hieu/'.$slug.'/';
}

function ptsBrandDetailPayload(array $row, array $productRows, $page, $perPage, $total, $pricesVisible = false, $sortKey = 'default', array $filterOptions = array(), array $selectedFilters = array())
{
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $slug = ptsBrandDetailSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    $name = ptsBrandDetailText(isset($row['namevi']) ? $row['namevi'] : '');
    if($id <= 0 || !ptsBrandDetailPublished(isset($row['status']) ? $row['status'] : '') || $slug === '' || $name === '') return null;

    $products = array();
    if(function_exists('ptsCatalogProductDto'))
    {
        foreach($productRows as $productRow)
        {
            if(!is_array($productRow)) continue;
            $dto = ptsCatalogProductDto($productRow, $pricesVisible);
            if($dto !== null) $products[] = $dto;
        }
    }

    $brandOptions = array();
    foreach(isset($filterOptions['brands']) && is_array($filterOptions['brands']) ? $filterOptions['brands'] : array() as $brandRow)
    {
        if(!is_array($brandRow)) continue;
        $optionId = isset($brandRow['id']) ? (int)$brandRow['id'] : 0;
        $optionName = ptsBrandDetailText(isset($brandRow['namevi']) ? $brandRow['namevi'] : '');
        $optionSlug = ptsBrandDetailSlug(isset($brandRow['slugvi']) ? $brandRow['slugvi'] : '');
        if($optionId <= 0 || $optionName === '' || $optionSlug === '' || !ptsBrandDetailPublished(isset($brandRow['status']) ? $brandRow['status'] : '')) continue;
        $brandOptions[] = array('id' => $optionId, 'name' => $optionName, 'slug' => $optionSlug, 'url' => ptsBrandDetailCanonical($optionSlug));
    }

    $priceRanges = array();
    if($pricesVisible)
    {
        foreach(isset($filterOptions['priceRanges']) && is_array($filterOptions['priceRanges']) ? $filterOptions['priceRanges'] : array() as $range)
        {
            if(!is_array($range) || !isset($range['key'], $range['label'])) continue;
            $key = (string)$range['key'];
            $label = ptsBrandDetailText($range['label']);
            if(!in_array($key, array('1', '2', '3'), true) || $label === '') continue;
            $priceRanges[] = array('key' => $key, 'label' => $label);
        }
    }
    $priceKeys = array();
    foreach(isset($selectedFilters['priceKeys']) && is_array($selectedFilters['priceKeys']) ? $selectedFilters['priceKeys'] : array() as $key)
    {
        $key = (string)$key;
        if($pricesVisible && in_array($key, array('1', '2', '3'), true) && !in_array($key, $priceKeys, true)) $priceKeys[] = $key;
    }

    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'path' => ptsBrandDetailCanonical($slug),
        'brand' => array(
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'url' => ptsBrandDetailCanonical($slug)
        ),
        'products' => $products,
        'pagination' => array('page' => (int)$page, 'perPage' => (int)$perPage, 'total' => max(0, (int)$total)),
        'filters' => array('brands' => $brandOptions, 'priceRanges' => $priceRanges),
        /* Brand route giữ brand làm phạm vi cố định; priceKeys được áp dụng
           riêng ở endpoint này, còn brand options là liên kết chuyển trang. */
        'selected' => array('sort' => function_exists('ptsCatalogSortKey') ? ptsCatalogSortKey($sortKey) : 'default', 'priceKeys' => $priceKeys),
        'parity' => array(
            'prices' => $pricesVisible ? array('included' => true) : array('included' => false, 'reason' => 'public_pricing_disabled'),
            'seoOverrides' => array('included' => false, 'reason' => 'legacy_seo_override_table_not_delegated')
        )
    );
}
