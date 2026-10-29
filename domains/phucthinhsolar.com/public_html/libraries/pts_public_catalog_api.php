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

function ptsCatalogSeoText($value)
{
    $text = ptsCatalogPlainText($value);
    return trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $text));
}

function ptsCatalogSeoDto($fallbackTitle, array $seoRow = array())
{
    $title = !empty($seoRow['titlevi']) ? ptsCatalogSeoText($seoRow['titlevi']) : ptsCatalogSeoText($fallbackTitle);
    if($title !== '' && stripos($title, 'phúc thịnh s') === false) $title .= ' | Phúc Thịnh Solar';
    return array(
        'title' => $title,
        'description' => !empty($seoRow['descriptionvi']) ? ptsCatalogSeoText($seoRow['descriptionvi']) : '',
        'keywords' => !empty($seoRow['keywordsvi']) ? ptsCatalogSeoText($seoRow['keywordsvi']) : ''
    );
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

function ptsCatalogVisualMedia($value, $prefix, $root = '')
{
    if(!is_scalar($value)) return '';
    $value = ltrim(trim((string)$value), '/');
    if($value === '' || basename($value) !== $value || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    if($root !== '' && !is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.trim($prefix, '/\\').DIRECTORY_SEPARATOR.$value)) return '';
    return '/'.trim($prefix, '/').'/'.$value;
}

function ptsCatalogHref($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value);
    if($value === '' || preg_match('/[\x00-\x20<>"\'`]/', $value) || strpos($value, '..') !== false || preg_match('/^(?:javascript|data|vbscript):/i', $value) || strpos($value, '//') === 0) return '';
    return strpos($value, '/') === 0 ? $value : '/'.$value;
}

function ptsCatalogPageExtrasDto(array $extras)
{
    $hero = null;
    if(!empty($extras['hero']) && is_array($extras['hero'])) {
        $image = ptsCatalogVisualMedia($extras['hero']['photo'] ?? '', $extras['hero']['upload'] ?? 'upload/photo/', $extras['root'] ?? '');
        if($image === '') $image = ptsCatalogVisualMedia($extras['hero']['photo'] ?? '', 'assets/images/site-visuals/', $extras['root'] ?? '');
        if($image === '') $image = ptsCatalogVisualMedia($extras['hero']['photo'] ?? '', 'upload/product/', $extras['root'] ?? '');
        if($image !== '') $hero = array('image' => $image, 'alt' => ptsCatalogPlainText($extras['hero']['alt'] ?? ''));
    }
    $applications = array();
    $applicationTitle = '';
    foreach(isset($extras['cards']) && is_array($extras['cards']) ? $extras['cards'] : array() as $block) {
        if($applicationTitle === '') $applicationTitle = ptsCatalogPlainText($block['title'] ?? '');
        foreach(isset($block['items']) && is_array($block['items']) ? $block['items'] : array() as $item) {
            if(!is_array($item)) continue;
            $title = ptsCatalogPlainText($item['title'] ?? '');
            if($title === '') continue;
            $photo = (string)($item['photo'] ?? '');
            $image = ptsCatalogVisualMedia($photo, 'upload/product/', $extras['root'] ?? '');
            if($image === '') $image = ptsCatalogVisualMedia($photo, 'assets/images/site-visuals/', $extras['root'] ?? '');
            $applications[] = array('title' => $title, 'description' => ptsCatalogPlainText($item['desc'] ?? ''), 'image' => $image, 'href' => ptsCatalogHref($item['link'] ?? ''));
        }
    }
    $faq = array();
    foreach(isset($extras['faq']) && is_array($extras['faq']) ? $extras['faq'] : array() as $item) {
        if(!is_array($item)) continue;
        $question = ptsCatalogPlainText($item['question'] ?? '');
        $answer = ptsCatalogPlainText($item['answer'] ?? '');
        if($question !== '' && $answer !== '') $faq[] = array('question' => $question, 'answer' => $answer);
    }
    $support = null;
    if(!empty($extras['support']) && is_array($extras['support'])) {
        $photo = (string)($extras['support']['photo'] ?? '');
        $image = ptsCatalogVisualMedia($photo, 'upload/photo/', $extras['root'] ?? '');
        if($image === '') $image = ptsCatalogVisualMedia($photo, 'assets/images/site-visuals/', $extras['root'] ?? '');
        $title = ptsCatalogPlainText($extras['support']['name'] ?? '');
        if($title !== '') $support = array('title' => $title, 'description' => ptsCatalogPlainText($extras['support']['desc'] ?? ''), 'image' => $image, 'href' => ptsCatalogHref($extras['support']['link'] ?? 'lien-he/'));
    }
    $canonical = isset($extras['canonicalPath']) && is_string($extras['canonicalPath']) ? $extras['canonicalPath'] : '/san-pham/';
    if(!preg_match('#^/(?:san-pham/|danh-muc-san-pham/(?:[a-z0-9]+(?:-[a-z0-9]+)*/){1,4})$#', $canonical)) $canonical = '/san-pham/';
    return array(
        'categoryHero' => $hero,
        'categoryContent' => array('title' => $applicationTitle, 'applications' => $applications, 'faq' => $faq),
        'support' => $support,
        'seo' => ptsCatalogSeoDto($extras['seoFallbackTitle'] ?? 'Sản phẩm', isset($extras['seoRow']) && is_array($extras['seoRow']) ? $extras['seoRow'] : array()),
        'canonical' => array('url' => $canonical)
    );
}

/**
 * Mirrors the published static hero allowlist in sources/product.php. Keeping
 * the paths fixed prevents CMS route data from becoming an arbitrary asset URL.
 */
function ptsCatalogCategoryHeroFallback($path, $root = '')
{
    $fallbacks = array(
        'san-pham' => array('catalog-products-hero-20261007-v2.png', 'Danh mục thiết bị năng lượng mặt trời Phúc Thịnh Solar'),
        'danh-muc-san-pham/den-nlmt' => array('catalog-lighting-overview-20261008.png', 'Tổng quan giải pháp chiếu sáng năng lượng mặt trời'),
        'danh-muc-san-pham/den-nlmt/den-duong' => array('catalog-solar-street-lighting-20261008.png', 'Đèn đường năng lượng mặt trời cho đường phố và lối đi'),
        'danh-muc-san-pham/den-nlmt/den-pha-nang-luong-mat-troi' => array('catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'),
        'danh-muc-san-pham/den-nlmt/den-pha' => array('catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'),
        'danh-muc-san-pham/den-nlmt/den-san-vuon' => array('catalog-solar-garden-lighting-20261008-v2.png', 'Đèn sân vườn năng lượng mặt trời cho nhà ở và khuôn viên'),
        'danh-muc-san-pham/den-nlmt/den-ufo' => array('catalog-solar-ufo-highbay-20261008-v2.png', 'Đèn UFO năng lượng mặt trời cho nhà xưởng và không gian cao'),
        'danh-muc-san-pham/den-nlmt/den-op-tran' => array('catalog-solar-ceiling-lighting-20261008.png', 'Đèn ốp trần năng lượng mặt trời cho hiên nhà và hành lang'),
        'danh-muc-san-pham/tru-den/tru-den-duong' => array('catalog-solar-street-poles-20261008.png', 'Trụ đèn đường cho hệ thống chiếu sáng ngoài trời'),
        'danh-muc-san-pham/tru-den/tru-den-trang-tri' => array('catalog-decorative-lamp-posts-20261008.png', 'Trụ đèn trang trí cho sân vườn và khuôn viên'),
        'danh-muc-san-pham/tru-den/can-den-gia-do' => array('catalog-street-light-arms-20261008.png', 'Cần đèn và giá đỡ cho hệ thống chiếu sáng'),
        'danh-muc-san-pham/tru-den/mong-de-tru' => array('catalog-street-pole-foundations-20261008.png', 'Móng và đế trụ cho công trình chiếu sáng'),
        'danh-muc-san-pham/tam-pin-nang-luong-mat-troi/kep-tam-pin' => array('catalog-utility-solar-mounting-20261008.png', 'Kẹp tấm pin và phụ kiện cố định cho trang trại điện mặt trời'),
        'danh-muc-san-pham/bien-tan-inverter/hoa-luoi' => array('catalog-grid-tie-solar-20261008.png', 'Biến tần hòa lưới cho nhà máy điện mặt trời quy mô lớn'),
        'danh-muc-san-pham/bien-tan-inverter/hybrid-luu-tru' => array('catalog-hybrid-inverter-storage-20261008.png', 'Biến tần hybrid kết hợp điện mặt trời và hệ thống lưu trữ'),
        'danh-muc-san-pham/pin-luu-tru/pin-battery' => array('catalog-lithium-battery-modules-20261008.png', 'Module pin lithium cho hệ thống lưu trữ năng lượng'),
        'danh-muc-san-pham/pin-luu-tru/he-thong-ess' => array('catalog-ess-container-site-20261008.png', 'Hệ thống ESS container cho dự án lưu trữ năng lượng'),
        'danh-muc-san-pham/pin-luu-tru/bms-rack-phu-kien' => array('catalog-bms-rack-20261008.png', 'BMS, rack và phụ kiện quản lý hệ thống pin lưu trữ'),
        'danh-muc-san-pham/bien-tan-bom/bien-tan-bom-nlmt' => array('catalog-solar-pump-inverter-20261008.png', 'Biến tần bơm năng lượng mặt trời cho hệ thống tưới và cấp nước quy mô lớn'),
        'danh-muc-san-pham/bien-tan-bom/may-bom-nlmt' => array('catalog-solar-water-pump-20261008.png', 'Máy bơm năng lượng mặt trời cho nông nghiệp và công trình'),
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/bo-dieu-khien-sac-mppt' => array('catalog-mppt-controller-20261008-v2.png', 'Bộ điều khiển sạc MPPT cho hệ thống điện mặt trời'),
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/bo-dieu-khien-sac-pwm' => array('catalog-pwm-controller-20261008.png', 'Bộ điều khiển sạc PWM cho hệ thống điện mặt trời'),
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/cap-day-dien' => array('catalog-solar-cables-wiring-20261008.png', 'Cáp và dây điện chuyên dụng cho hệ thống năng lượng mặt trời'),
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/giam-sat-do-luong' => array('catalog-solar-monitoring-20261008.png', 'Thiết bị giám sát và đo lường hệ thống điện mặt trời'),
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/phu-kien-den-nlmt' => array('catalog-solar-lighting-accessories-20261008.png', 'Phụ kiện lắp đặt đèn năng lượng mặt trời'),
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/tiep-dia-ong-bao-ve' => array('catalog-solar-protection-grounding-20261008.png', 'Tiếp địa và ống bảo vệ cho hệ thống điện mặt trời'),
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/vat-tu-phu-tro' => array('catalog-solar-electrical-accessories-v2.jpg', 'Vật tư phụ trợ cho thi công và lắp đặt điện mặt trời'),
        /* Legacy category paths remain supported by the PHP router. */
        'danh-muc-san-pham/den-duong' => array('catalog-solar-street-lighting-20261008.png', 'Đèn đường năng lượng mặt trời cho đường phố và lối đi'),
        'danh-muc-san-pham/den-pha-nang-luong-mat-troi' => array('catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'),
        'danh-muc-san-pham/den-pha' => array('catalog-solar-flood-lighting-20261008.png', 'Đèn pha năng lượng mặt trời cho sân bãi và công trình'),
        'danh-muc-san-pham/den-san-vuon' => array('catalog-solar-garden-lighting-20261008-v2.png', 'Đèn sân vườn năng lượng mặt trời cho nhà ở và khuôn viên'),
        'danh-muc-san-pham/den-ufo' => array('catalog-solar-ufo-highbay-20261008-v2.png', 'Đèn UFO năng lượng mặt trời cho nhà xưởng và không gian cao'),
        'danh-muc-san-pham/den-op-tran' => array('catalog-solar-ceiling-lighting-20261008.png', 'Đèn ốp trần năng lượng mặt trời cho hiên nhà và hành lang'),
        'danh-muc-san-pham/bien-tan-inverter' => array('catalog-inverter-overview-20261008.png', 'Hệ thống biến tần điện mặt trời cho công trình và nhà máy'),
        'danh-muc-san-pham/tam-pin-nang-luong-mat-troi' => array('catalog-utility-solar-farm-20261008.png', 'Trang trại tấm pin năng lượng mặt trời quy mô lớn'),
        'danh-muc-san-pham/tru-den' => array('catalog-lamp-posts-v2.jpg', 'Trụ đèn cổ điển và phụ kiện lắp đặt ngoài trời'),
        'danh-muc-san-pham/den-san-vuon' => array('catalog-ai-garden-lighting.webp', 'Không gian sân vườn sử dụng đèn năng lượng mặt trời'),
        'danh-muc-san-pham/quat-nang-luong-mat-troi' => array('catalog-solar-fan-20261008-v2.png', 'Quạt đứng sử dụng năng lượng mặt trời cho không gian gia đình'),
        'danh-muc-san-pham/bien-tan-bom' => array('catalog-solar-pumping-overview-20261008.png', 'Hệ thống bơm nước năng lượng mặt trời cho nông nghiệp và công trình'),
        'danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi' => array('catalog-solar-bos-accessories-20261008.png', 'Dây dẫn, đầu nối và thiết bị bảo vệ cho hệ thống điện mặt trời'),
        'danh-muc-san-pham/he-thong-khung-nhom' => array('catalog-utility-solar-mounting-20261008.png', 'Hệ khung và phụ kiện lắp đặt tấm pin cho dự án quy mô lớn'),
        'danh-muc-san-pham/pin-luu-tru' => array('catalog-utility-bess-20261008.png', 'Hệ thống pin lưu trữ năng lượng cho gia đình và doanh nghiệp'),
    );
    if(!isset($fallbacks[$path])) return null;
    $image = ptsCatalogVisualMedia($fallbacks[$path][0], 'assets/images/site-visuals/', $root);
    return $image === '' ? null : array('photo' => $fallbacks[$path][0], 'upload' => 'assets/images/site-visuals/', 'alt' => ptsCatalogPlainText($fallbacks[$path][1]));
}

function ptsCatalogPositiveNumber($value)
{
    if(!is_scalar($value) || !is_numeric($value)) return null;
    $number = (float)$value;
    return is_finite($number) && $number > 0 ? $number : null;
}

function ptsCatalogQuickSpecsDto(array $row)
{
    $specs = array();
    $power = ptsCatalogPositiveNumber(isset($row['cong_suat']) ? $row['cong_suat'] : null);
    if($power !== null) $specs[] = (string)(int)round($power).'W';

    foreach(array('waterproof_rating', 'lighting_time') as $field)
    {
        $value = ptsCatalogPlainText(isset($row[$field]) ? $row[$field] : '');
        if($value === '' || preg_match('/^(?:không công bố|không áp dụng|không có thông tin|chưa cập nhật|đang cập nhật|chưa xác minh|chưa tìm được|n\/a|na|-)(?:\s|$)/iu', $value)) continue;
        $specs[] = $value;
    }
    return array_slice($specs, 0, 3);
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
    $path = ptsCatalogPublicMedia(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : (isset($row['photo']) ? $row['photo'] : ''));
    if($path !== '') $media[] = $path;
    foreach(isset($row['_pts_public_gallery']) && is_array($row['_pts_public_gallery']) ? $row['_pts_public_gallery'] : array() as $filename)
    {
        $path = ptsCatalogPublicMedia($filename);
        if($path !== '' && !in_array($path, $media, true)) $media[] = $path;
    }
    $path = ptsCatalogPublicMedia(isset($row['_pts_public_photo2']) ? $row['_pts_public_photo2'] : (isset($row['photo2']) ? $row['photo2'] : ''));
    if($path !== '' && !in_array($path, $media, true)) $media[] = $path;
    return array(
        'id' => $id,
        'name' => $name,
        'slug' => $slug,
        'url' => $url,
        'media' => $media,
        'powerWatts' => ptsCatalogPositiveNumber(isset($row['cong_suat']) ? $row['cong_suat'] : null),
        'quickSpecs' => ptsCatalogQuickSpecsDto($row),
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

function ptsCatalogNeedFilterOptions($enabled)
{
    if(!$enabled) return array();
    return array(
        array('key' => 'du-an-cong-trinh', 'label' => 'Dự án & công trình'),
        array('key' => 'chieu-sang-gia-dinh', 'label' => 'Chiếu sáng gia đình'),
        array('key' => 'san-vuon-canh-quan', 'label' => 'Sân vườn & cảnh quan')
    );
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

function ptsCatalogPayload(array $category, array $categories, array $rows, $page, $perPage, $total, $pricesVisible = false, array $filterOptions = array(), array $selectedFilters = array(), array $extras = array())
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
    $selectedNeedKeys = isset($selectedFilters['needKeys']) && is_array($selectedFilters['needKeys']) ? array_values(array_unique(array_map('strval', $selectedFilters['needKeys']))) : array();
    return array_merge(array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'category' => $category,
        'categories' => $categoryDtos,
        'products' => $products,
        'pagination' => array('page' => (int)$page, 'perPage' => (int)$perPage, 'total' => max(0, (int)$total)),
        'filters' => array(
            'brands' => $brandDtos,
            'priceRanges' => ptsCatalogPriceRangeDto(),
            'subcategories' => $subcategoryDtos,
            'needs' => ptsCatalogNeedFilterOptions(!empty($filterOptions['needsEnabled']))
        ),
        'selected' => array(
            'brandIds' => $selectedBrandIds,
            'priceKeys' => $selectedPriceKeys,
            'subcategoryIds' => $selectedSubcategoryIds,
            'sort' => ptsCatalogSortKey(isset($selectedFilters['sort']) ? $selectedFilters['sort'] : ''),
            'needKeys' => $selectedNeedKeys
        ),
        'parity' => array(
            'prices' => $pricesVisible ? array('included' => true) : array('included' => false, 'reason' => 'public_pricing_disabled'),
            'filters' => array('included' => true),
            'sort' => array('included' => true)
        )
    ), ptsCatalogPageExtrasDto($extras));
}
