<?php
/** Pure allow-listed DTO helpers for the read-only Nuxt PDP preview. */

function ptsPdpPublished($status)
{
    return is_scalar($status) && in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsPdpSlug($value)
{
    $value = is_scalar($value) ? trim((string)$value, " /\t\n\r\0\x0B") : '';
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsPdpText($value)
{
    if(!is_scalar($value)) return '';
    return trim(preg_replace('/\s+/u', ' ', strip_tags(htmlspecialchars_decode((string)$value, ENT_QUOTES))));
}

function ptsPdpMediaUrl($value, $root = '', $uploadDirectory = 'upload/product/')
{
    $value = is_scalar($value) ? ltrim(trim((string)$value), '/') : '';
    if($value === '' || basename($value) !== $value || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    if($root !== '' && !is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.trim($uploadDirectory, '/\\').DIRECTORY_SEPARATOR.$value)) return '';
    return '/upload/product/'.$value;
}

function ptsPdpCanonicalUrl($slug)
{
    $slug = ptsPdpSlug($slug);
    return $slug === '' ? '' : '/san-pham/'.$slug;
}

function ptsPdpProfileKey($value)
{
    $value = is_scalar($value) ? trim((string)$value) : '';
    return preg_match('/^[a-z0-9_]+$/', $value) ? $value : 'general';
}

function ptsPdpPositiveNumber($value)
{
    if(!is_scalar($value) || !is_numeric($value)) return null;
    $number = (float)$value;
    return is_finite($number) && $number > 0 ? $number : null;
}

function ptsPdpMoney($value)
{
    $number = ptsPdpPositiveNumber($value);
    return $number === null || $number > PHP_INT_MAX ? null : (int)round($number);
}

/*
 * Cung cap pricing.mode qua publicProductRequiresQuote()-tuong duong (cung
 * authority voi libraries/public_pricing.php va da dung cho catalog o
 * pts_public_catalog_api.php::ptsCatalogPricingDto()): an gia khi cau hinh
 * tat gia cong khai, khi status co 'baogia', hoac khi khong co regular_price
 * lan sale_price.
 */
function ptsPdpPricingDto(array $row, $pricesVisible)
{
    $regular = ptsPdpMoney(isset($row['regular_price']) ? $row['regular_price'] : null);
    $sale = ptsPdpMoney(isset($row['sale_price']) ? $row['sale_price'] : null);
    $statuses = array_filter(array_map('trim', explode(',', isset($row['status']) ? (string)$row['status'] : '')));
    $mode = $pricesVisible && !in_array('baogia', $statuses, true) && ($regular !== null || $sale !== null) ? 'price' : 'quote';
    return array(
        'mode' => $mode,
        'currency' => 'VND',
        'regularPrice' => $mode === 'price' ? $regular : null,
        'salePrice' => $mode === 'price' ? $sale : null
    );
}

function ptsPdpSpecDto(array $row)
{
    if(!function_exists('productStructuredSpecs')) return array();
    $profile = function_exists('productPdpResolveProfile') ? productPdpResolveProfile($row, array('pdp_profile' => isset($row['list_pdp_profile']) ? $row['list_pdp_profile'] : '', 'slugvi' => isset($row['list_slugvi']) ? $row['list_slugvi'] : '')) : 'general';
    $result = productStructuredSpecs($row, 'vi', $profile);
    $specs = array();
    // Phase A: expose only profile-declared public fields. Free-form additional_specs
    // can contain editorial/private keys; those require a separate moderation gate.
    $profileFields = function_exists('productPdpProfile') ? productPdpProfile($profile) : array();
    $allowedSpecKeys = array_keys(isset($profileFields['fields']) && is_array($profileFields['fields']) ? $profileFields['fields'] : array());
    foreach((array)$result['specs'] as $spec) {
        $key = isset($spec['key']) && preg_match('/^[a-z0-9_]+$/', (string)$spec['key']) ? (string)$spec['key'] : '';
        if($key === '' || !in_array($key, $allowedSpecKeys, true)) continue;
        $label = ptsPdpText(isset($spec['label']) ? $spec['label'] : '');
        $value = ptsPdpText(isset($spec['value']) ? $spec['value'] : '');
        if($label !== '' && $value !== '') $specs[] = array('key' => $key, 'label' => $label, 'value' => $value);
    }
    return $specs;
}

function ptsPdpPayload(array $row, array $media, array $specs, $canonical, array $canonicalSources, $pricesVisible = false)
{
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $slug = ptsPdpSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    if($id <= 0 || !ptsPdpPublished(isset($row['status']) ? $row['status'] : '') || $slug === '') return null;
    $name = ptsPdpText(isset($row['namevi']) ? $row['namevi'] : '');
    if($name === '') return null;
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'product' => array(
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'url' => ptsPdpCanonicalUrl($slug),
            'code' => ptsPdpText(isset($row['code']) ? $row['code'] : ''),
            'summary' => ptsPdpText(isset($row['descvi']) ? $row['descvi'] : ''),
            'profile' => ptsPdpProfileKey(isset($row['resolved_profile']) ? $row['resolved_profile'] : ''),
            'media' => array_values(array_unique(array_filter($media))),
            'specifications' => array_values($specs)
        ),
        'pricing' => ptsPdpPricingDto($row, $pricesVisible),
        /*
         * KHAC voi news/project/solution detail: noi dung dai cua san pham
         * KHONG nam o mot cot CKEditor don (contentvi) ma la he block JSON co
         * cau truc rieng (table_content_section, doc/ghi qua
         * libraries/product_content_blocks.php, scope='product'). Khong the
         * tai dung pts_public_richtext.php cho truong hop nay - can thiet ke
         * DTO rieng cho tung loai block truoc, nen con de deferred o day.
         */
        'content' => array('rendered' => false, 'reason' => 'product_content_blocks_not_wired'),
        'canonical' => array('url' => $canonical !== '' ? '/'.trim($canonical, '/') : ptsPdpCanonicalUrl($slug), 'sources' => $canonicalSources),
        'parity' => array(
            'quote' => array('included' => false, 'reason' => 'legacy_quote_flow_stays_php'),
            'reviews' => array('included' => false, 'reason' => 'legacy_comment_mutation_stays_php'),
            'related' => array('included' => false, 'reason' => 'legacy_merchandising_order_not_verified'),
            'jsonLd' => array('included' => false, 'reason' => 'php_structured_data_authority_not_delegated'),
            'blocks' => array('included' => false, 'reason' => 'product_content_blocks_not_wired')
        )
    );
}
