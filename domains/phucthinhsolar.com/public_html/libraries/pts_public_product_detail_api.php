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

function ptsPdpContentText($value)
{
    if(!is_scalar($value)) return '';
    $text = str_replace(array("\r\n", "\r"), "\n", (string)$value);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    return trim((string)$text);
}

function ptsPdpContentImageUrl($value, $root = '')
{
    if(!is_scalar($value)) return '';
    $value = ltrim(trim((string)$value), '/');
    if($value === '') return '';

    if(basename($value) === $value)
        return ptsPdpMediaUrl($value, $root, 'upload/product/');

    if(!preg_match('#^upload/product/([A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif))$#i', $value, $match))
        return '';

    if($root !== '' && !is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.'product'.DIRECTORY_SEPARATOR.$match[1]))
        return '';

    return '/upload/product/'.$match[1];
}

function ptsPdpContentDto(array $sections, $root = '')
{
    $allowedSources = array('', 'product', 'brand', 'cat', 'list', 'both', 'stack', 'hide', 'own');
    $source = isset($sections['source']) && in_array($sections['source'], $allowedSources, true) ? $sections['source'] : '';
    $sourceBlocks = isset($sections['blocks']) && is_array($sections['blocks']) ? $sections['blocks'] : array();
    $split = function_exists('productSplitFaqBlocks')
        ? productSplitFaqBlocks($sourceBlocks)
        : array('faq' => array(), 'rest' => $sourceBlocks);

    $blocks = array();
    foreach((array)$split['rest'] as $block)
    {
        if(!is_array($block) || empty($block['type'])) continue;
        $type = (string)$block['type'];

        if($type === 'heading')
        {
            $text = ptsPdpContentText(isset($block['text']) ? $block['text'] : '');
            if($text !== '') $blocks[] = array('type' => 'heading', 'text' => $text);
        }
        elseif($type === 'text')
        {
            $text = ptsPdpContentText(isset($block['text']) ? $block['text'] : '');
            if($text !== '') $blocks[] = array('type' => 'text', 'text' => $text);
        }
        elseif($type === 'list')
        {
            $items = array();
            foreach((array)(isset($block['items']) ? $block['items'] : array()) as $item)
            {
                $item = ptsPdpContentText($item);
                if($item !== '') $items[] = $item;
                if(count($items) >= 50) break;
            }
            if($items) $blocks[] = array('type' => 'list', 'ordered' => !empty($block['ordered']), 'items' => $items);
        }
        elseif($type === 'image')
        {
            $image = ptsPdpContentImageUrl(isset($block['photo']) ? $block['photo'] : '', $root);
            if($image === '') continue;
            $blocks[] = array(
                'type' => 'image',
                'image' => $image,
                'caption' => ptsPdpContentText(isset($block['caption']) ? $block['caption'] : '')
            );
        }
        elseif($type === 'media')
        {
            $image = ptsPdpContentImageUrl(isset($block['photo']) ? $block['photo'] : '', $root);
            $title = ptsPdpContentText(isset($block['title']) ? $block['title'] : '');
            $text = ptsPdpContentText(isset($block['text']) ? $block['text'] : '');
            if($image === '' && $title === '' && $text === '') continue;
            $side = isset($block['side']) && in_array($block['side'], array('left', 'right', 'center'), true) ? $block['side'] : 'left';
            $blocks[] = array('type' => 'media', 'image' => $image, 'title' => $title, 'text' => $text, 'side' => $side);
        }
        /* type=cards chỉ thuộc category landing; PDP legacy không render nó. */
    }

    $faqs = array();
    foreach((array)$split['faq'] as $faq)
    {
        if(!is_array($faq)) continue;
        $question = ptsPdpContentText(isset($faq['question']) ? $faq['question'] : '');
        $answer = ptsPdpContentText(isset($faq['answer']) ? $faq['answer'] : '');
        if($question !== '' && $answer !== '') $faqs[] = array('question' => $question, 'answer' => $answer);
        if(count($faqs) >= 30) break;
    }

    return array('rendered' => true, 'source' => $source, 'blocks' => $blocks, 'faqs' => $faqs);
}

function ptsPdpPayload(array $row, array $media, array $specs, $canonical, array $canonicalSources, $pricesVisible = false, array $content = array())
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
         * Content PDP giữ nguyên mô hình block có cấu trúc của legacy, không
         * chuyển thành raw HTML/v-html. ptsPdpContentDto() chỉ xuất các loại
         * block mà PDP thật render và lọc ảnh về /upload/product/.
         */
        'content' => $content ? $content : ptsPdpContentDto(array()),
        'canonical' => array('url' => $canonical !== '' ? '/'.trim($canonical, '/') : ptsPdpCanonicalUrl($slug), 'sources' => $canonicalSources),
        'parity' => array(
            'quote' => array('included' => false, 'reason' => 'legacy_quote_flow_stays_php'),
            'reviews' => array('included' => false, 'reason' => 'legacy_comment_mutation_stays_php'),
            'related' => array('included' => false, 'reason' => 'legacy_merchandising_order_not_verified'),
            'jsonLd' => array('included' => false, 'reason' => 'php_structured_data_authority_not_delegated')
        )
    );
}
