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
    if(!function_exists('productStructuredSpecs') || !function_exists('productPdpSpecValue')) return array();
    $profile = function_exists('productPdpResolveProfile') ? productPdpResolveProfile($row, array('pdp_profile' => isset($row['list_pdp_profile']) ? $row['list_pdp_profile'] : '', 'slugvi' => isset($row['list_slugvi']) ? $row['list_slugvi'] : '')) : 'general';
    $result = productStructuredSpecs($row, 'vi', $profile);
    $structuredSpecs = isset($result['specs']) && is_array($result['specs']) ? $result['specs'] : array();
    $lookup = function($needles) use ($structuredSpecs) {
        foreach((array)$needles as $needle) {
            $needle = mb_strtolower(trim((string)$needle), 'UTF-8');
            foreach($structuredSpecs as $spec) {
                $label = mb_strtolower(trim(isset($spec['label']) ? (string)$spec['label'] : ''), 'UTF-8');
                if($needle !== '' && ($label === $needle || mb_strpos($label, $needle) !== false))
                    return trim(htmlspecialchars_decode(isset($spec['value']) ? (string)$spec['value'] : '', ENT_QUOTES));
            }
        }
        return '';
    };

    $specs = array();
    $profileFields = function_exists('productPdpProfile') ? productPdpProfile($profile) : array();
    $fields = isset($profileFields['fields']) && is_array($profileFields['fields']) ? $profileFields['fields'] : array();
    if($profile === 'lighting') {
        $lightingDimensions = trim(productPdpSpecValue($row, 'lamp_dimensions').(productPdpSpecValue($row, 'waterproof_rating') !== '' ? ' - '.productPdpSpecValue($row, 'waterproof_rating') : ''), ' -');
        $rows = array(
            array('cong_suat', 'Công suất LED thực', productPdpSpecValue($row, 'cong_suat')),
            array('quang_thong', 'Quang thông', $lookup(array('quang thông', 'lumen'))),
            array('battery_capacity', 'Dung lượng pin lưu trữ', productPdpSpecValue($row, 'battery_capacity')),
            array('panel_power', 'Công suất tấm pin', productPdpSpecValue($row, 'panel_power')),
            array('light_color', 'Màu ánh sáng', productPdpSpecValue($row, 'light_color')),
            array('housing_color', 'Màu sắc thân đèn', $lookup('màu sắc')),
            array('operating_mode', 'Chế độ hoạt động', $lookup('chế độ')),
            array('lamp_dimensions', 'Kích thước & cấp bảo vệ', $lightingDimensions),
            array('installation_height', 'Chiều cao lắp đặt', productPdpSpecValue($row, 'installation_height')),
            array('impact_rating', 'Chuẩn chống va đập', $lookup('chống va đập')),
            array('installation_spacing', 'Khoảng cách lắp đặt khuyến nghị', $lookup('khoảng cách lắp đặt')),
            array('lighting_time', 'Thời gian chiếu sáng', productPdpSpecValue($row, 'lighting_time')),
            array('charging_time', 'Thời gian sạc', productPdpSpecValue($row, 'charging_time')),
            array('lighting_area', 'Diện tích chiếu sáng', productPdpSpecValue($row, 'lighting_area')),
            array('warranty', 'Thời hạn bảo hành', productPdpSpecValue($row, 'warranty'))
        );
    } else {
        $rows = array();
        foreach($fields as $key => $label) $rows[] = array($key, $label, productPdpSpecValue($row, $key));
    }

    $optionalMissingFields = array('light_color', 'lighting_area', 'housing_color', 'operating_mode', 'impact_rating', 'installation_spacing', 'package_contents');
    $usedLabels = array();
    foreach($rows as $spec) {
        $key = preg_match('/^[a-z0-9_]+$/', (string)$spec[0]) ? (string)$spec[0] : 'spec_'.count($specs);
        $label = ptsPdpText($spec[1]);
        $value = ptsPdpText($spec[2]);
        $usedLabels[] = mb_strtolower(trim($label), 'UTF-8');
        if($label !== '' && ($value !== '' || !in_array($key, $optionalMissingFields, true)))
            $specs[] = array('key' => $key, 'label' => $label, 'value' => $value);
    }

    // Match the PHP PDP's public additional rows while filtering editorial and
    // investigation notes that are stored beside customer-facing specifications.
    $denylist = array('model', 'ghi chú', 'thông tin', 'mã/model nguồn', 'model nguồn', 'tên/phiên bản bảng giá', 'chứng nhận', 'kiểm nghiệm', 'tiêu chuẩn', 'hiệu suất châu âu', 'hiệu suất theo datasheet');
    $internalPatterns = array(
        '/\\bghi chú\\b/u', '/\\bsuy luận\\b/u', '/\\bxác minh\\b/u', '/\\bđối chiếu\\b/u',
        '/\\brevision\\b/u', '/\\bbatch\\d*/u', '/\\bfamily\\b/u', '/\\bcatalog(?:ue)?\\b/u',
        '/\\btrích nguồn\\b/u', '/\\bnguồn tham khảo\\b/u', '/\\bnguồn exact\\b/u', '/\\btham khảo\\b/u', '/\\bcăn cứ\\b/u',
        '/\\bnguồn (?:đúng|xác nhận|hiện tại|phổ biến|độc lập|hãng)\\b/u',
        '/\\btrạng thái nguồn\\b/u', '/\\bmức xác minh\\b/u', '/\\bkhuyến nghị\\b/u', '/\\bgiữ cấu hình\\b/u',
        '/\\blưu ý\\b/u', '/\\btrạng thái\\b/u', '/\\bxử lý\\b/u', '/\\bphân loại\\b/u'
    );
    if($profile === 'lighting') { $usedLabels[] = 'lumen'; $usedLabels[] = 'độ cao lắp đặt'; }
    if(function_exists('productPdpAdditionalSpecs')) foreach(productPdpAdditionalSpecs($row) as $extra) {
        $label = ptsPdpText(isset($extra['label']) ? $extra['label'] : '');
        $value = ptsPdpText(isset($extra['value']) ? $extra['value'] : '');
        $lower = mb_strtolower($label, 'UTF-8');
        if($label === '' || $value === '' || in_array($lower, $denylist, true)) continue;
        $internal = false;
        foreach($internalPatterns as $pattern) if(preg_match($pattern, $lower)) { $internal = true; break; }
        if($lower !== 'nguồn sáng' && preg_match('/\\b(?:nguồn|source)\\b/u', $lower)) $internal = true;
        if(preg_match('/https?:\\/\\//i', $value) || preg_match('/\\b(?:không công bố|chưa xác minh|chưa tìm được|không tự suy|chưa có nguồn|theo nguồn|theo revision|theo family|catalog(?:ue)?)\\b/iu', $value)) $internal = true;
        if($internal) continue;
        $duplicate = false;
        foreach($usedLabels as $used) if($used === $lower || mb_strpos($used, $lower) !== false || mb_strpos($lower, $used) !== false) { $duplicate = true; break; }
        if($duplicate) continue;
        $key = isset($extra['key']) && preg_match('/^[a-z0-9_]+$/', (string)$extra['key']) ? (string)$extra['key'] : 'extra_'.count($specs);
        $specs[] = array('key' => $key, 'label' => $label, 'value' => $value);
        $usedLabels[] = $lower;
    }
    return $specs;
}

function ptsPdpBreadcrumbDto($d, array $row)
{
    $crumbs = array(array('label' => 'Sản phẩm', 'url' => '/san-pham/'));
    if(!function_exists('publicProductEntityPath')) return $crumbs;
    $entities = array(
        array('id_list', 'product_list'),
        array('id_cat', 'product_cat'),
        array('id_item', 'product_item'),
        array('id_sub', 'product_sub')
    );
    $tables = array(
        'product_list' => '#_product_list',
        'product_cat' => '#_product_cat',
        'product_item' => '#_product_item',
        'product_sub' => '#_product_sub'
    );
    foreach($entities as $entity)
    {
        list($idKey, $table) = $entity;
        $id = isset($row[$idKey]) ? (int)$row[$idKey] : 0;
        if($id <= 0) continue;
        $tableName = isset($tables[$table]) ? $tables[$table] : '';
        if($tableName === '') continue;
        $record = $d->rawQueryOne("select id,namevi from ".$tableName." where id = ? and type = ? and find_in_set('hienthi',status) limit 1", array($id, 'san-pham'));
        if(empty($record)) continue;
        $path = trim((string)publicProductEntityPath($d, $table, $id, 'vi'), '/');
        $label = ptsPdpText(isset($record['namevi']) ? $record['namevi'] : '');
        if($label === '' || $path === '' || strpos($path, 'danh-muc-san-pham/') !== 0) continue;
        $crumbs[] = array('label' => $label, 'url' => '/'.$path.'/');
    }
    return $crumbs;
}

function ptsPdpSeoText($value)
{
    $text = ptsPdpText($value);
    return trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $text));
}

function ptsPdpSeoDto($productTitle, array $seoRow = array())
{
    $title = !empty($seoRow['titlevi']) ? ptsPdpSeoText($seoRow['titlevi']) : ptsPdpSeoText($productTitle);
    if($title !== '' && stripos($title, 'phúc thịnh s') === false) $title .= ' | Phúc Thịnh Solar';
    return array(
        'title' => $title,
        'description' => !empty($seoRow['descriptionvi']) ? ptsPdpSeoText($seoRow['descriptionvi']) : '',
        'keywords' => !empty($seoRow['keywordsvi']) ? ptsPdpSeoText($seoRow['keywordsvi']) : ''
    );
}

function ptsPdpDecisionMetricsDto(array $row)
{
    if(!function_exists('productPdpProfile') || !function_exists('productPdpSpecValue')) return array();
    $profileKey = function_exists('productPdpResolveProfile')
        ? productPdpResolveProfile($row, array('pdp_profile' => isset($row['list_pdp_profile']) ? $row['list_pdp_profile'] : '', 'slugvi' => isset($row['list_slugvi']) ? $row['list_slugvi'] : ''))
        : 'general';
    $profile = productPdpProfile($profileKey);
    $structured = function_exists('productStructuredSpecs') ? productStructuredSpecs($row, 'vi', $profileKey) : array('specs' => array());
    $structuredSpecs = isset($structured['specs']) && is_array($structured['specs']) ? $structured['specs'] : array();
    $lookup = function($needles) use ($structuredSpecs) {
        $needles = array_map(function($needle) { return mb_strtolower(trim((string)$needle), 'UTF-8'); }, (array)$needles);
        foreach($structuredSpecs as $spec)
        {
            if(!is_array($spec)) continue;
            $label = mb_strtolower(trim(isset($spec['label']) ? (string)$spec['label'] : ''), 'UTF-8');
            foreach($needles as $needle) if($needle !== '' && ($label === $needle || mb_strpos($label, $needle) !== false))
                return trim(htmlspecialchars_decode(isset($spec['value']) ? (string)$spec['value'] : '', ENT_QUOTES));
        }
        return '';
    };

    $units = array(
        'cong_suat' => 'W', 'ac_power' => 'W', 'motor_power' => 'HP', 'panel_power' => 'W',
        'battery_capacity' => 'Wh', 'usable_capacity' => 'kWh', 'battery_voltage' => 'V',
        'lighting_time' => 'giờ', 'runtime' => 'giờ', 'cycle_life' => 'chu kỳ', 'efficiency' => '%',
        'phase_count' => 'pha', 'mppt_count' => 'MPPT', 'input_voltage' => 'V', 'max_flow' => 'm³/h',
        'max_head' => 'm', 'rated_voltage' => 'V', 'rated_current' => 'A', 'installation_height' => 'm',
        'warranty' => 'tháng'
    );
    $iconByField = array(
        'battery_capacity' => 'battery', 'usable_capacity' => 'battery', 'battery_voltage' => 'battery',
        'installation_height' => 'height', 'max_head' => 'height', 'warranty' => 'shield',
        'waterproof_rating' => 'shield', 'cong_suat' => 'bolt', 'ac_power' => 'bolt',
        'motor_power' => 'bolt', 'rated_voltage' => 'bolt', 'rated_current' => 'bolt'
    );
    $profileImages = array(
        'inverter' => array('ac_power' => 'congsuatAC.png', 'phase_count' => 'sopha.png', 'mppt_count' => 'somppt.png', 'warranty' => 'baohanh.png'),
        'pump_controller' => array('motor_power' => 'congsuatden.png', 'phase_count' => 'sopha.png', 'input_voltage' => 'congsuatAC.png', 'warranty' => 'baohanh.png'),
        'pump_hardware' => array('motor_power' => 'congsuatden.png', 'max_flow' => 'luuluonglamviec.png', 'max_head' => 'cotaplamviec.png', 'warranty' => 'baohanh.png'),
        'solar_panel' => array('cong_suat' => 'congsuatstc.png', 'cell_type' => 'congnghecell.png', 'efficiency' => 'quanthong.png', 'warranty' => 'baohanh.png'),
        'storage' => array('usable_capacity' => 'pin.png', 'battery_voltage' => 'congsuatAC.png', 'cycle_life' => 'tuoichuky.png', 'warranty' => 'baohanh.png'),
        'fan' => array('cong_suat' => 'congsuatden.png', 'battery_capacity' => 'pin.png', 'runtime' => 'thoigianhoatdong.png', 'warranty' => 'baohanh.png'),
        'accessory' => array('rated_voltage' => 'congsuatAC.png', 'rated_current' => 'congsuatden.png', 'compatibility' => 'tuongthich.png', 'warranty' => 'baohanh.png'),
        'general' => array('cong_suat' => 'congsuatden.png', 'material' => 'vatlieu.png', 'waterproof_rating' => 'chuanchongnuoc.png', 'warranty' => 'baohanh.png')
    );
    $lightingImages = array(
        'quang_thong' => 'quanthong.png', 'battery_capacity' => 'pin.png',
        'installation_height' => 'chieucaoLapdat.png', 'warranty' => 'baohanh.png'
    );
    $metrics = array();
    if($profileKey === 'lighting')
    {
        $height = productPdpSpecValue($row, 'installation_height');
        if($height === '') $height = $lookup(array('chiều cao lắp đặt', 'độ cao lắp đặt'));
        if($height === '')
        {
            $technicalDescription = $lookup(array('mô tả kỹ thuật', 'cấu tạo'));
            if($technicalDescription !== '' && preg_match('/tr[ụu]\s*(?:đôi|đơn)?\s*cao\s+(\d+(?:[.,]\d+)?)\s*(?:mét|m)\b/ui', $technicalDescription, $match))
                $height = str_replace('.', ',', $match[1]).'m (theo mô tả kỹ thuật)';
        }
        $lightingMetrics = array(
            array('key' => 'quang_thong', 'label' => 'Quang thông', 'value' => $lookup(array('quang thông', 'lumen')), 'unit' => 'lm', 'icon' => 'sun'),
            array('key' => 'battery_capacity', 'label' => 'Pin lưu trữ', 'value' => productPdpSpecValue($row, 'battery_capacity'), 'unit' => 'Wh', 'icon' => 'battery'),
            array('key' => 'installation_height', 'label' => 'Chiều cao lắp', 'value' => $height, 'unit' => 'm', 'icon' => 'height'),
            array('key' => 'warranty', 'label' => 'Bảo hành', 'value' => productPdpSpecValue($row, 'warranty'), 'unit' => 'tháng', 'icon' => 'shield')
        );
        foreach($lightingMetrics as $metric)
        {
            $metric['iconImage'] = $lightingImages[$metric['key']];
            $metric['iconDir'] = 'pdp-lighting';
            $metric['value'] = ptsPdpText($metric['value']);
            $metrics[] = $metric;
        }
        return $metrics;
    }

    $images = isset($profileImages[$profileKey]) ? $profileImages[$profileKey] : array();
    $fields = isset($profile['metrics']) && is_array($profile['metrics']) ? $profile['metrics'] : array();
    foreach($fields as $key)
    {
        if(!preg_match('/^[a-z0-9_]+$/', (string)$key)) continue;
        $iconImage = isset($images[$key]) ? $images[$key] : '';
        $iconDir = $iconImage !== '' && in_array($key, array('cycle_life', 'runtime', 'compatibility', 'material', 'waterproof_rating'), true) ? 'pdp-extra' : 'pdp';
        $metrics[] = array(
            'key' => $key,
            'label' => ptsPdpText(isset($profile['fields'][$key]) ? $profile['fields'][$key] : $key),
            'value' => ptsPdpText(productPdpSpecValue($row, $key)),
            'unit' => isset($units[$key]) ? $units[$key] : '',
            'icon' => isset($iconByField[$key]) ? $iconByField[$key] : 'bolt',
            'iconImage' => $iconImage,
            'iconDir' => $iconDir
        );
    }
    return $metrics;
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

function ptsPdpApplicationImageUrl($value, $root = '')
{
    if(!is_scalar($value)) return '';
    $value = ltrim(trim((string)$value), '/');
    if(preg_match('#^assets/images/site-visuals/pdp-usecase-v2/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)$#i', $value))
    {
        if($root !== '' && !is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $value))) return '';
        return '/'.$value;
    }
    return ptsPdpContentImageUrl($value, $root);
}

function ptsPdpDocumentUrl($value, $root = '')
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value);
    if($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value)) return '';

    $path = (string)parse_url($value, PHP_URL_PATH);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if(!in_array($extension, array('pdf', 'doc', 'docx', 'xls', 'xlsx'), true)) return '';

    if(filter_var($value, FILTER_VALIDATE_URL))
    {
        $parts = parse_url($value);
        if(!is_array($parts) || strtolower(isset($parts['scheme']) ? $parts['scheme'] : '') !== 'https' || empty($parts['host']) || !empty($parts['user']) || !empty($parts['pass'])) return '';
        return $value;
    }

    $local = ltrim($value, '/');
    if(strpos($local, '..') !== false || !preg_match('#^(?:upload|assets)/[A-Za-z0-9._/-]+$#', $local)) return '';
    if($root !== '' && !is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $local))) return '';
    return '/'.$local;
}

function ptsPdpDocumentsDto(array $rows, $root = '')
{
    $allowedTypes = array('datasheet', 'manual', 'warranty', 'certificate', 'other');
    $documents = array();
    $seen = array();
    foreach($rows as $row)
    {
        if(!is_array($row)) continue;
        $type = isset($row['document_type']) && in_array($row['document_type'], $allowedTypes, true) ? $row['document_type'] : '';
        $title = ptsPdpText(isset($row['title']) ? $row['title'] : '');
        $url = ptsPdpDocumentUrl(isset($row['file_path']) ? $row['file_path'] : '', $root);
        if($type === '' || $title === '' || $url === '' || isset($seen[$url])) continue;
        $seen[$url] = true;
        $documents[] = array('type' => $type, 'title' => $title, 'url' => $url);
        if(count($documents) >= 30) break;
    }
    return $documents;
}

function ptsPdpIsApplicationHeading($value)
{
    $heading = ptsPdpContentText($value);
    return mb_stripos($heading, 'ứng dụng', 0, 'UTF-8') !== false || mb_stripos($heading, 'phù hợp', 0, 'UTF-8') !== false;
}

function ptsPdpApplicationTitle(array $row, array $list = array())
{
    if(!function_exists('productPdpProfile')) return '';
    $profileKey = function_exists('productPdpResolveProfile')
        ? productPdpResolveProfile($row, $list)
        : 'general';
    $profile = productPdpProfile($profileKey);
    return ptsPdpText(isset($profile['application_title']) ? $profile['application_title'] : '');
}

/** Extract application cards and the same allow-listed image fallbacks as PHP PDP. */
function ptsPdpApplicationsDto(array $sections, array $row, array $list = array(), $root = '')
{
    $profileKey = function_exists('productPdpResolveProfile') ? productPdpResolveProfile($row, $list) : 'general';
    $variant = function_exists('productPdpUseCaseVariant') ? productPdpUseCaseVariant($row, $list, $profileKey) : '';
    $sourceBlocks = isset($sections['blocks']) && is_array($sections['blocks']) ? $sections['blocks'] : array();
    $collect = false;
    $items = array();
    foreach($sourceBlocks as $block)
    {
        if(!is_array($block) || empty($block['type'])) continue;
        $type = (string)$block['type'];
        if($type === 'heading')
        {
            $isApplication = ptsPdpIsApplicationHeading(isset($block['text']) ? $block['text'] : '');
            if($collect && !$isApplication) break;
            $collect = $isApplication;
            continue;
        }
        if(!$collect) continue;

        if($type === 'media')
        {
            $image = ptsPdpApplicationImageUrl(isset($block['photo']) ? $block['photo'] : '', $root);
            $title = ptsPdpText(isset($block['title']) ? strip_tags((string)$block['title']) : '');
            $text = ptsPdpText(isset($block['text']) ? strip_tags((string)$block['text']) : '');
            if($image !== '' || $title !== '' || $text !== '')
                $items[] = array('title' => $title !== '' ? $title : 'Ứng dụng sản phẩm', 'text' => $text, 'image' => $image);
        }
        elseif($type === 'list')
        {
            foreach((array)(isset($block['items']) ? $block['items'] : array()) as $item)
            {
                $text = ptsPdpText($item);
                if($text === '') continue;
                $titleParts = preg_split('/\s+(?:cần|ưu tiên|phù hợp)\s+/u', $text, 2);
                $title = trim(isset($titleParts[0]) ? $titleParts[0] : $text, " .,:;-");
                $titleParts = explode(',', $title, 2);
                if(!empty($titleParts[0])) $title = trim($titleParts[0]);
                if(mb_strlen($title, 'UTF-8') > 58) $title = rtrim(mb_substr($title, 0, 55, 'UTF-8')).'…';
                if($title !== '') $items[] = array('title' => $title, 'text' => $text, 'image' => '');
                if(count($items) >= 3) break 2;
            }
        }
        if(count($items) >= 3) break;
    }

    if(!$items && $profileKey !== 'general' && function_exists('productPdpUseCaseDefaults'))
        $items = productPdpUseCaseDefaults($profileKey, $variant);
    if(!$items) return null;

    $defaults = function_exists('productPdpUseCaseDefaults') ? productPdpUseCaseDefaults($profileKey, $variant) : array();
    $seenImages = array();
    foreach($items as $index => &$item)
    {
        $title = isset($item['title']) ? (string)$item['title'] : '';
        $image = isset($item['image']) ? (string)$item['image'] : '';
        if($image === '' && $profileKey !== 'general' && function_exists('productPdpUseCasePhoto'))
            $image = productPdpUseCasePhoto($profileKey, $title, $index, $variant);

        $imageKey = strtolower(trim($image));
        if($profileKey !== 'general' && $imageKey !== '' && isset($seenImages[$imageKey]) && function_exists('productPdpUseCasePhoto'))
        {
            $candidates = array(productPdpUseCasePhoto($profileKey, $title, $index, $variant));
            if($defaults)
            {
                $candidates[] = $defaults[$index % count($defaults)]['photo'];
                $candidates[] = $defaults[($index + 1) % count($defaults)]['photo'];
            }
            foreach($candidates as $candidate)
            {
                $candidateKey = strtolower(trim((string)$candidate));
                if($candidateKey !== '' && !isset($seenImages[$candidateKey]))
                {
                    $image = $candidate;
                    $imageKey = $candidateKey;
                    break;
                }
            }
        }
        $item['image'] = ptsPdpApplicationImageUrl($image, $root);
        if($imageKey !== '' && $item['image'] !== '') $seenImages[$imageKey] = true;
    }
    unset($item);

    return array('title' => ptsPdpApplicationTitle($row, $list), 'items' => array_slice($items, 0, 3));
}

/** Build up to three buying cues from profile fields with real CMS values only. */
function ptsPdpBuyingGuidesDto(array $row, array $list = array())
{
    if(!function_exists('productPdpProfile') || !function_exists('productPdpSpecValue')) return array();
    $profileKey = function_exists('productPdpResolveProfile') ? productPdpResolveProfile($row, $list) : 'general';
    $profile = productPdpProfile($profileKey);
    $metrics = isset($profile['metrics']) && is_array($profile['metrics']) ? $profile['metrics'] : array();
    $fields = isset($profile['fields']) && is_array($profile['fields']) ? $profile['fields'] : array();
    $guides = array();
    foreach($fields as $key => $label)
    {
        if(!preg_match('/^[a-z0-9_]+$/', (string)$key) || in_array($key, $metrics, true)) continue;
        $value = ptsPdpText(productPdpSpecValue($row, $key));
        $label = ptsPdpText($label);
        if($label === '' || $value === '') continue;
        $guides[] = array('key' => $key, 'label' => $label, 'value' => $value);
        if(count($guides) >= 3) break;
    }
    return $guides;
}

function ptsPdpContentDto(array $sections, $root = '', $excludeApplications = false)
{
    $allowedSources = array('', 'product', 'brand', 'cat', 'list', 'both', 'stack', 'hide', 'own');
    $source = isset($sections['source']) && in_array($sections['source'], $allowedSources, true) ? $sections['source'] : '';
    $sourceBlocks = isset($sections['blocks']) && is_array($sections['blocks']) ? $sections['blocks'] : array();
    $split = function_exists('productSplitFaqBlocks')
        ? productSplitFaqBlocks($sourceBlocks)
        : array('faq' => array(), 'rest' => $sourceBlocks);

    $blocks = array();
    $skipApplicationBlocks = false;
    foreach((array)$split['rest'] as $block)
    {
        if(!is_array($block) || empty($block['type'])) continue;
        $type = (string)$block['type'];

        if($excludeApplications && $type === 'heading')
        {
            $skipApplicationBlocks = ptsPdpIsApplicationHeading(isset($block['text']) ? $block['text'] : '');
            if($skipApplicationBlocks) continue;
        }
        if($skipApplicationBlocks) continue;

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

function ptsPdpPayload(array $row, array $media, array $specs, $canonical, array $canonicalSources, $pricesVisible = false, array $content = array(), array $documents = array(), array $breadcrumbs = array(), array $seoRow = array(), $applications = null, array $buyingGuides = array())
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
            'brand' => ptsPdpText(isset($row['brand_name']) ? $row['brand_name'] : ''),
            'profile' => ptsPdpProfileKey(isset($row['resolved_profile']) ? $row['resolved_profile'] : ''),
            'media' => array_values(array_unique(array_filter($media))),
            'specifications' => array_values($specs),
            'packageContents' => function_exists('productNormalizePackageContents')
                ? ptsPdpText(productNormalizePackageContents(isset($row['package_contents']) ? $row['package_contents'] : ''))
                : '',
            'decisionMetrics' => ptsPdpDecisionMetricsDto($row)
        ),
        'breadcrumbs' => array_values($breadcrumbs),
        'pricing' => ptsPdpPricingDto($row, $pricesVisible),
        /*
         * Content PDP giữ nguyên mô hình block có cấu trúc của legacy, không
         * chuyển thành raw HTML/v-html. ptsPdpContentDto() chỉ xuất các loại
         * block mà PDP thật render và lọc ảnh về /upload/product/.
         */
        'content' => $content ? $content : ptsPdpContentDto(array()),
        'applications' => $applications,
        'buyingGuides' => array_values($buyingGuides),
        'documents' => array_values($documents),
        'canonical' => array('url' => $canonical !== '' ? '/'.trim($canonical, '/') : ptsPdpCanonicalUrl($slug), 'sources' => $canonicalSources),
        'seo' => ptsPdpSeoDto($name, $seoRow),
        'parity' => array(
            'quote' => array('included' => false, 'reason' => 'legacy_quote_flow_stays_php'),
            'reviews' => array('included' => false, 'reason' => 'legacy_comment_mutation_stays_php'),
            'related' => array('included' => false, 'reason' => 'legacy_merchandising_order_not_verified'),
            'jsonLd' => array('included' => false, 'reason' => 'php_structured_data_authority_not_delegated')
        )
    );
}
