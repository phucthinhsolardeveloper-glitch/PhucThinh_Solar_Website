<?php
$videoEmbedUrl = '';
if(!empty($rowDetail['video_url']))
{
    $videoUrl = trim(htmlspecialchars_decode($rowDetail['video_url'], ENT_QUOTES));
    $videoParts = parse_url($videoUrl);
    $videoHost = (!empty($videoParts['host'])) ? strtolower($videoParts['host']) : '';
    $videoId = '';

    if(in_array($videoHost, array('youtu.be', 'www.youtu.be'), true))
    {
        $videoId = trim((!empty($videoParts['path'])) ? $videoParts['path'] : '', '/');
    }
    else if(in_array($videoHost, array('youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'), true))
    {
        if(!empty($videoParts['query']))
        {
            parse_str($videoParts['query'], $videoQuery);
            $videoId = (!empty($videoQuery['v'])) ? $videoQuery['v'] : '';
        }

        if(empty($videoId) && !empty($videoParts['path']) && preg_match('~/(?:embed|shorts)/([A-Za-z0-9_-]{11})~', $videoParts['path'], $videoMatches))
            $videoId = $videoMatches[1];
    }

    if(preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId))
        $videoEmbedUrl = 'https://www.youtube-nocookie.com/embed/'.$videoId;
}

require_once LIBRARIES.'product_specs.php';
require_once LIBRARIES.'product_content_blocks.php';
$pdpProfileKey = productPdpResolveProfile($rowDetail, isset($productList) ? $productList : array());
$pdpProfile = productPdpProfile($pdpProfileKey);
$pdpUseCaseVariant = productPdpUseCaseVariant($rowDetail, isset($productList) ? $productList : array(), $pdpProfileKey);
$structuredSpecResult = productStructuredSpecs($rowDetail, $lang, $pdpProfileKey);
$structuredSpecs = $structuredSpecResult['specs'];
$useLegacySpecs = $structuredSpecResult['use_legacy'];
/* Mô tả sản phẩm = MỘT loại: chip thông số (tự động) + khối content_section
   (kế thừa sản phẩm > hãng > danh mục) + đoạn văn tự sinh khi chưa có khối. */
$productSpecHighlights = productAutoHighlights($rowDetail, $lang, isset($type) ? $type : '', $pdpProfileKey);
$productLegacySpecs = trim(isset($rowDetail['thongsokythuat'.$lang]) ? htmlspecialchars_decode($rowDetail['thongsokythuat'.$lang]) : '');
$productPolicyOverride = trim(isset($rowDetail['chinhsachmuahang'.$lang]) ? htmlspecialchars_decode($rowDetail['chinhsachmuahang'.$lang]) : '');
$productPolicyOverrideItems = array();
$productPolicyOverrideListHtml = '';
if($productPolicyOverride !== '') {
    /* Chuẩn hóa dữ liệu cũ nhập từ Excel/CKEditor về danh sách thống nhất. */
    $plainPolicy = trim(strip_tags($productPolicyOverride));
    if($plainPolicy !== '') {
        $policyLines = preg_split('/(?:\R+|\s+)(?=[–—]\s*)/u', $plainPolicy, -1, PREG_SPLIT_NO_EMPTY);
        if(count($policyLines) < 2) $policyLines = preg_split('/\R+/u', $plainPolicy, -1, PREG_SPLIT_NO_EMPTY);
        foreach($policyLines as $policyLine) {
            $policyLine = trim(preg_replace('/^[–—-]\s*/u', '', $policyLine));
            if($policyLine !== '') $productPolicyOverrideItems[] = $policyLine;
        }
    }
    if(empty($productPolicyOverrideItems) && preg_match_all('/<p\b[^>]*>(.*?)<\/p>/isu', $productPolicyOverride, $policyParagraphs) && count($policyParagraphs[1]) >= 2) {
        foreach($policyParagraphs[1] as $policyParagraph) {
            $policyLine = trim(preg_replace('/^[–—-]\s*/u', '', strip_tags($policyParagraph)));
            if($policyLine !== '') $productPolicyOverrideItems[] = $policyLine;
        }
    }
}
$productSections = isset($productSections) ? $productSections : array('blocks' => array(), 'source' => '');
$pdpSectionSplit = productSplitFaqBlocks(isset($productSections['blocks']) ? $productSections['blocks'] : array());
$productSectionsHtml = '';
$productSpecParagraph = '';
$hasProductDescription = false;
/* Luôn hiện bảng thông số đủ khung (xem $pdpFullSpecRows bên dưới) - không
   còn ẩn cả bảng chỉ vì chưa có structuredSpecs, để Admin thấy rõ còn thiếu
   field nào cần nhập thay vì tưởng đã xong vì bảng "biến mất". */
$hasProductSpecs = true;
$hasProductPolicy = $productPolicyOverride !== '' || !empty($policy);

/* Nếu ảnh đại diện bị thiếu trên đĩa nhưng gallery còn ảnh thật, dùng ảnh đó
   làm ảnh chính và bỏ các mục gallery đã mất. Chỉ đọc filesystem, không sửa DB. */
$detailMainPhoto = !empty($rowDetail['_display_photo']) ? $rowDetail['_display_photo'] : '';
$detailGalleryPhotos = array();
if(!empty($rowDetailPhoto) && is_array($rowDetailPhoto))
{
    foreach($rowDetailPhoto as $detailPhotoRow)
    {
        $detailPhotoCandidate = !empty($detailPhotoRow['photo']) ? basename((string)$detailPhotoRow['photo']) : '';
        if($detailPhotoCandidate === '' || !is_file(UPLOAD_PRODUCT_L.$detailPhotoCandidate)) continue;
        if($detailPhotoCandidate !== $detailMainPhoto)
        {
            $detailPhotoRow['photo'] = $detailPhotoCandidate;
            $detailGalleryPhotos[] = $detailPhotoRow;
        }
    }
}
$detailZoomHref = $detailMainPhoto !== '' ? watermarkImageHref('product', '600x600x4', UPLOAD_PRODUCT_L.$detailMainPhoto) : 'assets/images/noimage.png';
$pdpCategoryName = '';
if(!empty($productCat['name'.$lang])) $pdpCategoryName = $productCat['name'.$lang];
else if(!empty($productList['name'.$lang])) $pdpCategoryName = $productList['name'.$lang];

$pdpPackageContents = productNormalizePackageContents(isset($rowDetail['package_contents']) ? $rowDetail['package_contents'] : '');

/* Bố cục "hero + bảng thông số luôn đủ dòng" theo mockup 2026-09-26 - khung/
   icon cố định, dữ liệu thật hiện khi có, còn thiếu thì hiện placeholder
   "[...]" để Admin tự biết chỗ cần cập nhật. Áp dụng cho MỌI profile, nhưng
   chọn field theo đúng nhóm (mỗi profile đã có sẵn metrics/fields riêng ở
   product_pdp_profiles.php - không ép chung 1 bộ field cho tất cả). Riêng
   nhóm đèn dùng đúng 4 field trong mockup gốc (quang thông không phải field
   cố định của DB, phải tra theo nhãn trong additional_specs). */
$pdpSpecLookup = function($needleLabel) use ($structuredSpecs) {
    /* Cho phép truyền nhiều cách gọi tên cùng 1 thông số (vd "quang thông"
       hoặc "lumen") - Admin nhập tự do nên nhãn không đồng nhất giữa các model. */
    $needles = array_map(function($n) { return mb_strtolower(trim($n), 'UTF-8'); }, (array)$needleLabel);
    foreach($structuredSpecs as $spec) {
        $specLabel = mb_strtolower(trim((string)$spec['label']), 'UTF-8');
        foreach($needles as $needleLabel)
        {
            if($specLabel === $needleLabel || mb_strpos($specLabel, $needleLabel) !== false)
                return trim(htmlspecialchars_decode((string)$spec['value'], ENT_QUOTES));
        }
    }
    return '';
};
/* Đơn vị hiển thị trong placeholder khi field chưa có dữ liệu - chỉ mang
   tính gợi ý cho người nhập liệu, không in kèm khi đã có giá trị thật (giá
   trị thật tự mang đơn vị người nhập gõ vào, vd "6-8m", "2 năm"). */
$pdpFieldUnits = array(
    'cong_suat' => 'W', 'ac_power' => 'W', 'motor_power' => 'HP', 'panel_power' => 'W',
    'battery_capacity' => 'Wh', 'usable_capacity' => 'kWh', 'battery_voltage' => 'V',
    'lighting_time' => 'giờ', 'runtime' => 'giờ', 'cycle_life' => 'chu kỳ', 'efficiency' => '%',
    'phase_count' => 'pha', 'mppt_count' => 'MPPT', 'input_voltage' => 'V', 'max_flow' => 'm³/h',
    'max_head' => 'm', 'rated_voltage' => 'V', 'rated_current' => 'A', 'installation_height' => 'm',
    'warranty' => 'tháng'
);
$pdpHeroIconByField = array(
    'battery_capacity' => 'battery', 'usable_capacity' => 'battery', 'battery_voltage' => 'battery',
    'installation_height' => 'height', 'max_head' => 'height',
    'warranty' => 'shield', 'waterproof_rating' => 'shield',
    'cong_suat' => 'bolt', 'ac_power' => 'bolt', 'motor_power' => 'bolt', 'rated_voltage' => 'bolt', 'rated_current' => 'bolt'
);
/* Icon PNG do sếp tự vẽ, chỉ có cho field của nhóm đèn - field nào không có
   trong danh sách này (vd "material") vẫn dùng fa-check-circle mặc định. */
$pdpLightingFieldIconImage = array(
    'light_source' => 'nguonsang.png', 'lamp_dimensions' => 'kichthuocsanpham.png',
    'panel_power' => 'congsuattampin.png', 'cong_suat' => 'congsuatden.png',
    'battery_capacity' => 'pin.png', 'charging_time' => 'thoigiansac.png',
    'lighting_time' => 'thoigianchieusang.png', 'lighting_area' => 'vungchieusang.png',
    'waterproof_rating' => 'chuanchongnuoc.png', 'installation_height' => 'chieucaoLapdat.png',
    'warranty' => 'baohanh.png'
);
if($pdpProfileKey === 'lighting')
{
    /* Một số sản phẩm (vd trụ đèn) không có field "installation_height" nhập
       riêng, chỉ ghi trong "Mô tả kỹ thuật" dạng văn xuôi (vd "Trụ đôi cao 8
       mét,..."). Tra theo nhãn thật trước, chỉ trích số từ mô tả khi không có
       field/nhãn nào khác - tránh đoán bừa trên các mẫu mô tả khác. */
    $pdpInstallationHeightValue = productPdpSpecValue($rowDetail, 'installation_height');
    if($pdpInstallationHeightValue === '') $pdpInstallationHeightValue = $pdpSpecLookup(array('chiều cao lắp đặt', 'độ cao lắp đặt'));
    if($pdpInstallationHeightValue === '')
    {
        $pdpTechDescText = $pdpSpecLookup(array('mô tả kỹ thuật', 'cấu tạo'));
        if($pdpTechDescText !== '' && preg_match('/tr[ụu]\s*(?:đôi|đơn)?\s*cao\s+(\d+(?:[.,]\d+)?)\s*(?:mét|m)\b/ui', $pdpTechDescText, $pdpHeightMatch))
            $pdpInstallationHeightValue = str_replace('.', ',', $pdpHeightMatch[1]).'m (theo mô tả kỹ thuật)';
    }
    /* Đúng 4 field trong mockup gốc, không dùng $pdpProfile['metrics'] mặc
       định (cong_suat/panel_power/battery_capacity/lighting_time) vì sếp
       chọn khác cho khung hero của nhóm đèn. Icon dùng ảnh PNG do sếp tự vẽ
       (assets/images/icons/pdp-lighting/), không dùng SVG mặc định nữa. */
    $pdpHeroMetricFields = array(
        array('key' => 'quang_thong', 'label' => 'Quang thông', 'value' => $pdpSpecLookup(array('quang thông', 'lumen')), 'unit' => 'lm', 'icon' => 'sun', 'iconImage' => 'quanthong.png', 'iconDir' => 'pdp-lighting'),
        array('key' => 'battery_capacity', 'label' => 'Pin lưu trữ', 'value' => productPdpSpecValue($rowDetail, 'battery_capacity'), 'unit' => 'Wh', 'icon' => 'battery', 'iconImage' => 'pin.png', 'iconDir' => 'pdp-lighting'),
        array('key' => 'installation_height', 'label' => 'Chiều cao lắp', 'value' => $pdpInstallationHeightValue, 'unit' => 'm', 'icon' => 'height', 'iconImage' => 'chieucaoLapdat.png', 'iconDir' => 'pdp-lighting'),
        array('key' => 'warranty', 'label' => 'Bảo hành', 'value' => productPdpSpecValue($rowDetail, 'warranty'), 'unit' => 'tháng', 'icon' => 'shield', 'iconImage' => 'baohanh.png', 'iconDir' => 'pdp-lighting')
    );
}
else
{
    /* Icon PNG theo đúng bản mẫu riêng của từng nhóm (vdbientan/vdtampin/
       vdpinluutru/vdbom/vdquat/vdphukien.jpg sếp gửi) - chỉ map field nào có
       icon khớp thật, field còn lại vẫn dùng SVG mặc định qua $pdpHeroIconByField. */
    $pdpHeroIconImageByProfile = array(
        'inverter' => array('ac_power' => 'congsuatAC.png', 'phase_count' => 'sopha.png', 'mppt_count' => 'somppt.png', 'warranty' => 'baohanh.png'),
        'pump_controller' => array('motor_power' => 'congsuatden.png', 'phase_count' => 'sopha.png', 'input_voltage' => 'congsuatAC.png', 'warranty' => 'baohanh.png'),
        'pump_hardware' => array('motor_power' => 'congsuatden.png', 'max_flow' => 'luuluonglamviec.png', 'max_head' => 'cotaplamviec.png', 'warranty' => 'baohanh.png'),
        'solar_panel' => array('cong_suat' => 'congsuatstc.png', 'cell_type' => 'congnghecell.png', 'efficiency' => 'quanthong.png', 'warranty' => 'baohanh.png'),
        'storage' => array('usable_capacity' => 'pin.png', 'battery_voltage' => 'congsuatAC.png', 'cycle_life' => 'tuoichuky.png', 'warranty' => 'baohanh.png'),
        'fan' => array('cong_suat' => 'congsuatden.png', 'battery_capacity' => 'pin.png', 'runtime' => 'thoigianhoatdong.png', 'warranty' => 'baohanh.png'),
        'accessory' => array('rated_voltage' => 'congsuatAC.png', 'rated_current' => 'congsuatden.png', 'compatibility' => 'tuongthich.png', 'warranty' => 'baohanh.png'),
        'general' => array('cong_suat' => 'congsuatden.png', 'material' => 'vatlieu.png', 'waterproof_rating' => 'chuanchongnuoc.png', 'warranty' => 'baohanh.png')
    );
    $pdpProfileIconMap = isset($pdpHeroIconImageByProfile[$pdpProfileKey]) ? $pdpHeroIconImageByProfile[$pdpProfileKey] : array();
    $pdpHeroMetricFields = array();
    foreach((array)$pdpProfile['metrics'] as $metricKey)
    {
        $pdpHeroMetricFields[] = array(
            'key' => $metricKey,
            'label' => isset($pdpProfile['fields'][$metricKey]) ? $pdpProfile['fields'][$metricKey] : $metricKey,
            'value' => productPdpSpecValue($rowDetail, $metricKey),
            'unit' => isset($pdpFieldUnits[$metricKey]) ? $pdpFieldUnits[$metricKey] : '',
            'icon' => isset($pdpHeroIconByField[$metricKey]) ? $pdpHeroIconByField[$metricKey] : 'bolt',
            'iconImage' => isset($pdpProfileIconMap[$metricKey]) ? $pdpProfileIconMap[$metricKey] : '',
            'iconDir' => isset($pdpProfileIconMap[$metricKey]) && in_array($metricKey, array('cycle_life', 'runtime', 'compatibility', 'material', 'waterproof_rating'), true) ? 'pdp-extra' : 'pdp'
        );
    }
}
/* Bảng thông số kỹ thuật theo profile. Field cốt lõi thiếu dữ liệu vẫn giữ
   hàng để báo "Đang cập nhật"; chỉ field tùy chọn/không phù hợp mới được rút. */
$pdpFullSpecRows = array();
if($pdpProfileKey === 'lighting')
{
    $pdpFullSpecRows[] = array('Công suất LED thực', productPdpSpecValue($rowDetail, 'cong_suat'), 'cong_suat');
    $pdpFullSpecRows[] = array('Quang thông', $pdpSpecLookup(array('quang thông', 'lumen')), 'quang_thong');
    $pdpFullSpecRows[] = array('Dung lượng pin lưu trữ', productPdpSpecValue($rowDetail, 'battery_capacity'), 'battery_capacity');
    $pdpFullSpecRows[] = array('Công suất tấm pin', productPdpSpecValue($rowDetail, 'panel_power'), 'panel_power');
    $pdpFullSpecRows[] = array('Màu ánh sáng', productPdpSpecValue($rowDetail, 'light_color'), 'light_color');
    $pdpFullSpecRows[] = array('Màu sắc thân đèn', $pdpSpecLookup('màu sắc'), 'housing_color');
    $pdpFullSpecRows[] = array('Chế độ hoạt động', $pdpSpecLookup('chế độ'), 'operating_mode');
    $lightingDims = trim(productPdpSpecValue($rowDetail, 'lamp_dimensions').(productPdpSpecValue($rowDetail, 'waterproof_rating') !== '' ? ' - '.productPdpSpecValue($rowDetail, 'waterproof_rating') : ''), ' -');
    $pdpFullSpecRows[] = array('Kích thước & cấp bảo vệ', $lightingDims, 'lamp_dimensions');
    $pdpFullSpecRows[] = array('Chiều cao lắp đặt', $pdpInstallationHeightValue, 'installation_height');
    $pdpFullSpecRows[] = array('Chuẩn chống va đập', $pdpSpecLookup('chống va đập'), 'impact_rating');
    $pdpFullSpecRows[] = array('Khoảng cách lắp đặt khuyến nghị', $pdpSpecLookup('khoảng cách lắp đặt'), 'installation_spacing');
    $pdpFullSpecRows[] = array('Thời gian chiếu sáng', productPdpSpecValue($rowDetail, 'lighting_time'), 'lighting_time');
    $pdpFullSpecRows[] = array('Thời gian sạc', productPdpSpecValue($rowDetail, 'charging_time'), 'charging_time');
    $pdpFullSpecRows[] = array('Diện tích chiếu sáng', productPdpSpecValue($rowDetail, 'lighting_area'), 'lighting_area');
    $pdpFullSpecRows[] = array('Thời hạn bảo hành', productPdpSpecValue($rowDetail, 'warranty'), 'warranty');
}
else
{
    foreach((array)$pdpProfile['fields'] as $fieldKey => $fieldLabel)
        $pdpFullSpecRows[] = array($fieldLabel, productPdpSpecValue($rowDetail, $fieldKey), $fieldKey);
}

/* Bổ sung mọi thông số Admin đã nhập trong additional_specs nhưng chưa được
   field cứng nào ở trên hiển thị (đa số entry chỉ có label/value, không có
   key nên productPdpSpecValue() không bắt được qua field key). Không giới
   hạn theo profile - áp dụng chung mọi nhóm sản phẩm để không sót dữ liệu
   Admin đã nhập thật. */
$pdpExtraSpecDenylist = array('model', 'ghi chú', 'thông tin', 'mã/model nguồn', 'model nguồn', 'tên/phiên bản bảng giá', 'chứng nhận', 'kiểm nghiệm', 'tiêu chuẩn', 'hiệu suất châu âu', 'hiệu suất theo datasheet');
$pdpExtraSpecInternalPatterns = array(
    '/\bghi chú\b/u', '/\bsuy luận\b/u', '/\bxác minh\b/u', '/\bđối chiếu\b/u',
    '/\brevision\b/u', '/\bbatch\d*/u', '/\bfamily\b/u', '/\bcatalog(?:ue)?\b/u',
    '/\btrích nguồn\b/u', '/\bnguồn tham khảo\b/u', '/\bnguồn exact\b/u', '/\btham khảo\b/u', '/\bcăn cứ\b/u',
    '/\bnguồn (?:đúng|xác nhận|hiện tại|phổ biến|độc lập|hãng)\b/u',
    '/\btrạng thái nguồn\b/u', '/\bmức xác minh\b/u', '/\bkhuyến nghị\b/u', '/\bgiữ cấu hình\b/u',
    '/\blưu ý\b/u', '/\btrạng thái\b/u', '/\bxử lý\b/u', '/\bphân loại\b/u', '/\btham khảo\b/u', '/\bcăn cứ\b/u'
);
$pdpUsedSpecLabels = array();
foreach($pdpFullSpecRows as $usedRow) $pdpUsedSpecLabels[] = mb_strtolower(trim($usedRow[0]), 'UTF-8');
/* Một số file cũ dùng đảo thứ tự nhãn "Giám sát / kết nối" thay vì nhãn
   canonical "Kết nối giám sát" của profile inverter. Đăng ký cả hai alias để
   additional_specs không in lại cùng một giá trị thành dòng thứ hai. */
if($pdpProfileKey === 'inverter') {
    $pdpUsedSpecLabels[] = 'giám sát / kết nối';
    $pdpUsedSpecLabels[] = 'kết nối giám sát';
    $pdpUsedSpecLabels[] = 'mã model';
    $pdpUsedSpecLabels[] = 'số mppt / chuỗi mỗi mppt';
    $pdpUsedSpecLabels[] = 'dòng mppt tối đa';
    $pdpUsedSpecLabels[] = 'hiệu suất tối đa';
}
/* Bí danh không bắt được qua so khớp chuỗi con (vd "Lumen" ~ "Quang thông")
   - khai báo tay để tránh hiện trùng 2 dòng cùng 1 giá trị. */
if($pdpProfileKey === 'lighting') $pdpUsedSpecLabels[] = 'lumen';
if($pdpProfileKey === 'lighting') $pdpUsedSpecLabels[] = 'độ cao lắp đặt';
foreach(productPdpAdditionalSpecs($rowDetail) as $extraSpec)
{
    $extraLabel = trim((string)$extraSpec['label']);
    $extraValue = trim(htmlspecialchars_decode((string)$extraSpec['value'], ENT_QUOTES));
    if($extraLabel === '' || $extraValue === '') continue;
    $extraLabelLower = mb_strtolower($extraLabel, 'UTF-8');
    if(in_array($extraLabelLower, $pdpExtraSpecDenylist, true)) continue;
    /* additional_specs còn có dữ liệu kiểm tra nội bộ từ quá trình rà model.
       Không được đưa URL nhà cung cấp/đối thủ, revision hay suy luận ra public. */
    $isInternalExtra = false;
    foreach($pdpExtraSpecInternalPatterns as $internalPattern) {
        if(preg_match($internalPattern, $extraLabelLower)) { $isInternalExtra = true; break; }
    }
    if($extraLabelLower !== 'nguồn sáng' && preg_match('/\b(?:nguồn|source)\b/u', $extraLabelLower)) $isInternalExtra = true;
    if(preg_match('/https?:\/\//i', $extraValue)) $isInternalExtra = true;
    if(preg_match('/\b(?:không công bố|chưa xác minh|chưa tìm được|không tự suy|chưa có nguồn|theo nguồn|theo revision|theo family|catalog(?:ue)?)\b/iu', $extraValue)) $isInternalExtra = true;
    if($isInternalExtra) continue;
    $alreadyShown = false;
    foreach($pdpUsedSpecLabels as $usedLabel)
    {
        if($usedLabel === $extraLabelLower || mb_strpos($usedLabel, $extraLabelLower) !== false || mb_strpos($extraLabelLower, $usedLabel) !== false)
        {
            $alreadyShown = true;
            break;
        }
    }
    if($alreadyShown) continue;
    $pdpFullSpecRows[] = array($extraLabel, $extraValue, $extraSpec['key']);
    $pdpUsedSpecLabels[] = $extraLabelLower;
}
/* Chỉ rút các field thực sự tùy chọn; field thuộc bộ thông số chính của
   profile vẫn giữ lại để hiển thị "Đang cập nhật" khi chưa có dữ liệu. */
$pdpOptionalMissingFields = array('light_color', 'lighting_area', 'housing_color', 'operating_mode', 'impact_rating', 'installation_spacing', 'package_contents');
$pdpFullSpecRows = array_values(array_filter($pdpFullSpecRows, function($specRow) use ($pdpOptionalMissingFields) {
    if (isset($specRow[1]) && trim((string)$specRow[1]) !== '') return true;
    return !isset($specRow[2]) || !in_array($specRow[2], $pdpOptionalMissingFields, true);
}));
$hasProductSpecs = !empty($pdpFullSpecRows);

/* Chỉ dựng hướng dẫn lựa chọn từ dữ liệu kỹ thuật thật của sản phẩm. */
$pdpGuideIconImageByField = array(
    'material' => array('vatlieu.png', 'pdp-extra'), 'waterproof_rating' => array('chuanchongnuoc.png', 'pdp-extra'),
    'compatibility' => array('tuongthich.png', 'pdp-extra'), 'runtime' => array('thoigianhoatdong.png', 'pdp-extra'),
    'cycle_life' => array('tuoichuky.png', 'pdp-extra'), 'efficiency' => array('quanthong.png', 'pdp'),
    'input_voltage' => array('congsuatAC.png', 'pdp'), 'rated_voltage' => array('congsuatAC.png', 'pdp'),
    'rated_current' => array('congsuatden.png', 'pdp'), 'package_contents' => array('quycach.png', 'pdp')
);
$pdpBuyingGuides = array();
foreach((array)$pdpProfile['fields'] as $guideKey => $guideLabel)
{
    if(in_array($guideKey, (array)$pdpProfile['metrics'], true)) continue;
    $guideValue = productPdpSpecValue($rowDetail, $guideKey);
    if($guideValue === '') continue;
    $guideIconImage = '';
    $guideIconDir = '';
    if($pdpProfileKey === 'lighting' && isset($pdpLightingFieldIconImage[$guideKey])) { $guideIconImage = $pdpLightingFieldIconImage[$guideKey]; $guideIconDir = 'pdp-lighting'; }
    elseif(isset($pdpGuideIconImageByField[$guideKey])) { $guideIconImage = $pdpGuideIconImageByField[$guideKey][0]; $guideIconDir = $pdpGuideIconImageByField[$guideKey][1]; }
    else { $guideIconImage = 'ungdung.png'; $guideIconDir = 'pdp'; }
    $pdpBuyingGuides[] = array('fa-check-circle', $guideLabel, $guideLabel.': '.$guideValue.'.', $guideIconImage, $guideIconDir);
    if(count($pdpBuyingGuides) >= 3) break;
}
$pdpBuyingGuides = array_slice($pdpBuyingGuides, 0, 3);
$pdpUseCases = array();
$collectPdpUseCases = false;
foreach((array)$productSections['blocks'] as $contentBlock)
{
    $blockType = isset($contentBlock['type']) ? $contentBlock['type'] : '';
    if($blockType === 'heading')
    {
        $headingText = trim(isset($contentBlock['text']) ? $contentBlock['text'] : '');
        $isApplicationHeading = mb_stripos($headingText, 'ứng dụng', 0, 'UTF-8') !== false || mb_stripos($headingText, 'phù hợp', 0, 'UTF-8') !== false;
        if($collectPdpUseCases && !$isApplicationHeading) break;
        $collectPdpUseCases = $isApplicationHeading;
        continue;
    }
    if($collectPdpUseCases && $blockType === 'media')
    {
        $useCasePhoto = trim(isset($contentBlock['photo']) ? (string)$contentBlock['photo'] : '');
        $useCaseTitle = trim(strip_tags(isset($contentBlock['title']) ? (string)$contentBlock['title'] : ''));
        $useCaseText = trim(strip_tags(isset($contentBlock['text']) ? (string)$contentBlock['text'] : ''));
        if($useCasePhoto !== '' && ($useCaseTitle !== '' || $useCaseText !== ''))
        {
            $pdpUseCases[] = array('title' => $useCaseTitle !== '' ? $useCaseTitle : 'Ứng dụng sản phẩm', 'text' => $useCaseText, 'photo' => $useCasePhoto);
            if(count($pdpUseCases) >= 3) break;
        }
        continue;
    }
    if(!$collectPdpUseCases || $blockType !== 'list' || empty($contentBlock['items'])) continue;
    foreach($contentBlock['items'] as $useCaseText)
    {
        $useCaseText = trim(strip_tags((string)$useCaseText));
        if($useCaseText === '') continue;
        $titleParts = preg_split('/\s+(?:cần|ưu tiên|phù hợp)\s+/u', $useCaseText, 2);
        $useCaseTitle = trim($titleParts[0], " .,:;-");
        $useCaseTitleParts = explode(',', $useCaseTitle, 2);
        if(!empty($useCaseTitleParts[0])) $useCaseTitle = trim($useCaseTitleParts[0]);
        if(mb_strlen($useCaseTitle, 'UTF-8') > 58) $useCaseTitle = rtrim(mb_substr($useCaseTitle, 0, 55, 'UTF-8')).'…';
        $pdpUseCases[] = array('title' => $useCaseTitle, 'text' => $useCaseText, 'photo' => '');
        if(count($pdpUseCases) >= 3) break 2;
    }
}
/* Mỗi profile/biến thể có bộ ảnh minh hoạ làm fallback an toàn. Nội dung do
   Admin nhập trong content block vẫn được ưu tiên, chỉ bổ sung ảnh còn thiếu
   để card không rơi về placeholder icon. */
if(!$pdpUseCases && $pdpProfileKey !== 'general') $pdpUseCases = productPdpUseCaseDefaults($pdpProfileKey, $pdpUseCaseVariant);
/* Khi đã có section Ứng dụng riêng, không render lại heading/list ứng dụng
   nằm trong phần mô tả legacy. Nếu giữ cả hai, cùng một nội dung xuất hiện
   một lần trong Mô tả và một lần nữa ở section Ứng dụng bên dưới. */
$pdpDescriptionBlocks = array();
$skipPdpApplicationBlocks = false;
foreach((array)$pdpSectionSplit['rest'] as $descriptionBlock)
{
    $descriptionType = isset($descriptionBlock['type']) ? $descriptionBlock['type'] : '';
    if($descriptionType === 'heading')
    {
        $descriptionHeading = trim(isset($descriptionBlock['text']) ? (string)$descriptionBlock['text'] : '');
        $isApplicationHeading = mb_stripos($descriptionHeading, 'ứng dụng', 0, 'UTF-8') !== false || mb_stripos($descriptionHeading, 'phù hợp', 0, 'UTF-8') !== false;
        $skipPdpApplicationBlocks = $isApplicationHeading && !empty($pdpUseCases);
        if($skipPdpApplicationBlocks) continue;
    }
    if(!$skipPdpApplicationBlocks) $pdpDescriptionBlocks[] = $descriptionBlock;
}
$productSectionsHtml = !empty($pdpDescriptionBlocks) ? productRenderSections($pdpDescriptionBlocks, UPLOAD_PRODUCT_L, $func) : '';
$productSpecParagraph = ($productSectionsHtml === '' && $productSections['source'] !== 'hide') ? productAutoDescription($rowDetail, $lang, isset($type) ? $type : '', $pdpProfileKey) : '';
$hasProductDescription = $productSpecHighlights || $productSectionsHtml !== '' || $productSpecParagraph !== '';
$pdpUseCasePhotoSeen = array();
foreach($pdpUseCases as $pdpUseCaseIndex => &$pdpUseCase)
{
    if(empty($pdpUseCase['photo'])) $pdpUseCase['photo'] = productPdpUseCasePhoto($pdpProfileKey, isset($pdpUseCase['title']) ? $pdpUseCase['title'] : '', $pdpUseCaseIndex, $pdpUseCaseVariant);
    /* Content block cũ đôi khi lặp cùng một ảnh cho cả 3 ứng dụng. Giữ ảnh
       Admin đầu tiên, nhưng thay các bản lặp bằng fallback đúng ngữ cảnh để
       card không còn hiển thị ba lần cùng một cảnh. */
    $pdpUseCasePhotoKey = strtolower(trim((string)$pdpUseCase['photo']));
    if($pdpProfileKey !== 'general' && $pdpUseCasePhotoKey !== '' && isset($pdpUseCasePhotoSeen[$pdpUseCasePhotoKey]))
    {
        $pdpFallbackDefaults = productPdpUseCaseDefaults($pdpProfileKey, $pdpUseCaseVariant);
        $pdpFallbackCandidates = array(productPdpUseCasePhoto($pdpProfileKey, isset($pdpUseCase['title']) ? $pdpUseCase['title'] : '', $pdpUseCaseIndex, $pdpUseCaseVariant));
        if(!empty($pdpFallbackDefaults))
        {
            $pdpFallbackCandidates[] = $pdpFallbackDefaults[$pdpUseCaseIndex % count($pdpFallbackDefaults)]['photo'];
            $pdpFallbackCandidates[] = $pdpFallbackDefaults[($pdpUseCaseIndex + 1) % count($pdpFallbackDefaults)]['photo'];
        }
        foreach($pdpFallbackCandidates as $pdpFallbackPhoto)
        {
            $pdpFallbackPhotoKey = strtolower(trim((string)$pdpFallbackPhoto));
            if($pdpFallbackPhotoKey !== '' && !isset($pdpUseCasePhotoSeen[$pdpFallbackPhotoKey]))
            {
                $pdpUseCase['photo'] = $pdpFallbackPhoto;
                $pdpUseCasePhotoKey = $pdpFallbackPhotoKey;
                break;
            }
        }
    }
    if($pdpUseCasePhotoKey !== '') $pdpUseCasePhotoSeen[$pdpUseCasePhotoKey] = true;
}
unset($pdpUseCase);
$pdpWarrantyPolicies = array();
if(!empty($policy) && is_array($policy))
{
    foreach($policy as $policyItem)
    {
        $policyName = isset($policyItem['name'.$lang]) ? (string)$policyItem['name'.$lang] : '';
        if($policyName !== '' && mb_stripos($policyName, 'bảo hành', 0, 'UTF-8') !== false) $pdpWarrantyPolicies[] = $policyItem;
    }
}
$hasProductWarranty = $productPolicyOverride !== '' || !empty($pdpWarrantyPolicies);
$validProductDocuments = array();
foreach((array)(isset($productDocuments) ? $productDocuments : array()) as $document)
{
    $documentPath = trim(isset($document['file_path']) ? (string)$document['file_path'] : '');
    $documentTitle = trim(isset($document['title']) ? (string)$document['title'] : '');
    $isRemoteDocument = filter_var($documentPath, FILTER_VALIDATE_URL) && strtolower((string)parse_url($documentPath, PHP_URL_SCHEME)) === 'https';
    $isLocalDocument = $documentPath !== '' && is_file(ROOT.ltrim($documentPath, '/'));
    if($documentTitle !== '' && ($isRemoteDocument || $isLocalDocument)) $validProductDocuments[] = $document;
}
/* Luôn hiện khối video (có placeholder khi chưa có link) như bản mẫu gốc,
   để Admin thấy thiếu mà bổ sung, không ẩn hẳn section như trước. */
$hasPdpSupport = true;
$pdpFaqs = array();
foreach((array)$pdpSectionSplit['faq'] as $faqBlock)
{
    $faqQuestion = trim(isset($faqBlock['question']) ? (string)$faqBlock['question'] : '');
    $faqAnswer = trim(isset($faqBlock['answer']) ? (string)$faqBlock['answer'] : '');
    if($faqQuestion !== '' && $faqAnswer !== '') $pdpFaqs[] = array($faqQuestion, $faqAnswer);
}
if(!$pdpFaqs && $pdpProfileKey === 'lighting' && (!empty($rowDetail['lighting_area']) || !empty($rowDetail['installation_height'])))
{
    $faqAnswer = 'Theo thông số sản phẩm';
    if(!empty($rowDetail['lighting_area'])) $faqAnswer .= ', diện tích chiếu sáng tham khảo là '.$rowDetail['lighting_area'];
    if(!empty($rowDetail['installation_height'])) $faqAnswer .= (!empty($rowDetail['lighting_area']) ? ' và' : ',').' độ cao lắp đặt tham khảo là '.$rowDetail['installation_height'];
    $pdpFaqs[] = array('Sản phẩm phù hợp với khu vực lắp đặt như thế nào?', $faqAnswer.'. Nên đối chiếu thêm mặt bằng thực tế trước khi chốt số lượng.');
}
if(!$pdpFaqs && $pdpProfileKey === 'lighting' && (!empty($rowDetail['charging_time']) || !empty($rowDetail['lighting_time'])))
{
    $faqParts = array();
    if(!empty($rowDetail['charging_time'])) $faqParts[] = 'thời gian sạc tham khảo '.$rowDetail['charging_time'];
    if(!empty($rowDetail['lighting_time'])) $faqParts[] = 'thời gian chiếu sáng tham khảo '.$rowDetail['lighting_time'];
    $pdpFaqs[] = array('Thời gian sạc và chiếu sáng của sản phẩm là bao lâu?', ucfirst(implode(', ', $faqParts)).'. Điều kiện nắng và vị trí lắp đặt có thể ảnh hưởng đến thời gian vận hành thực tế.');
}
if(!empty($rowDetail['warranty'])) $pdpFaqs[] = array('Sản phẩm được bảo hành trong bao lâu?', 'Thời hạn bảo hành công bố là '.$rowDetail['warranty'].', áp dụng theo điều kiện bảo hành hiện hành của Phúc Thịnh Solar.');

/* Website khai báo <base href="..."> nên href="#muc" sẽ bị trình duyệt ghép
   với URL gốc và có thể nhảy về trang chủ. Luôn neo fragment vào path hiện tại. */
$pdpCurrentPath = !empty($_SERVER['REQUEST_URI']) ? parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if(!is_string($pdpCurrentPath) || $pdpCurrentPath === '' || substr($pdpCurrentPath, 0, 1) !== '/')
    $pdpCurrentPath = '/san-pham/'.ltrim((string)$rowDetail[$sluglang], '/');
$pdpCurrentPath = htmlspecialchars($pdpCurrentPath, ENT_QUOTES, 'UTF-8');
$pdpOverviewAnchor = $hasProductDescription ? 'tong-quan' : ($pdpUseCases ? 'ung-dung-san-pham' : ($pdpBuyingGuides ? 'huong-dan-lua-chon' : ($pdpFaqs ? 'cau-hoi-thuong-gap' : '')));
$pdpSupportAnchor = $validProductDocuments ? 'tai-lieu-san-pham' : ($hasProductWarranty ? 'chinh-sach-bao-hanh' : 'video-san-pham');
if($validProductDocuments) $pdpSupportLabel = $hasProductWarranty ? 'Tài liệu & bảo hành' : 'Tài liệu sản phẩm';
elseif($hasProductWarranty) $pdpSupportLabel = 'Chính sách bảo hành';
else $pdpSupportLabel = 'Video sản phẩm';
?>
<main class="pts-pdp-v2">
<div class="box-detail pts-pdp-hero">
    <div class="">
        <div class="">
            <div class="grid-pro-detail w-clear">
                <div class="row">
                    <div class="left-pro-detail col-md-5 col-lg-5">
                        <a id="Zoom-1" class="MagicZoom"
                            data-options="zoomMode: on; hint: off; rightClick: true; selectorTrigger: hover; expandCaption: false; history: false;"
                            href="<?=$detailZoomHref?>" title="<?=$rowDetail['name'.$lang]?>">
                            <?=$func->getImage(array_merge(['isLazy' => false, 'sizes' => '600x600x4', 'upload' => UPLOAD_PRODUCT_L, 'image' => $detailMainPhoto, 'alt' => $rowDetail['name'.$lang]], watermarkImageArgs('product')))?>
                        </a>
                        <?php if($detailMainPhoto !== '' || !empty($detailGalleryPhotos)) { ?>
                        <div class="gallery-thumb-pro">
                            <div class="owl-page owl-carousel owl-theme owl-pro-detail" data-xsm-items="5:10"
                                data-sm-items="5:10" data-md-items="5:10" data-lg-items="5:10" data-xlg-items="5:10"
                                data-nav="1"
                                data-navtext="<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-chevron-left' width='44' height='45' viewBox='0 0 24 24' stroke-width='1.5' stroke='#2c3e50' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><polyline points='15 6 9 12 15 18' /></svg>|<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-chevron-right' width='44' height='45' viewBox='0 0 24 24' stroke-width='1.5' stroke='#2c3e50' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><polyline points='9 6 15 12 9 18' /></svg>"
                                data-navcontainer=".control-pro-detail">
                                <div>
                                    <a class="thumb-pro-detail" data-zoom-id="Zoom-1"
                                        href="<?=$detailZoomHref?>"
                                        title="<?=$rowDetail['name'.$lang]?>">
                                        <?=$func->getImage(array_merge(['isLazy' => false, 'sizes' => '600x600x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $detailMainPhoto, 'alt' => $rowDetail['name'.$lang]], watermarkImageArgs('product')))?>
                                    </a>
                                </div>
                                <?php foreach($detailGalleryPhotos as $v) { $thumbAlt = !empty($v['caption']) ? $v['caption'] : $rowDetail['name'.$lang]; ?>
                                <div>
                                    <a class="thumb-pro-detail" data-zoom-id="Zoom-1"
                                        href="<?=!empty($v['photo']) ? watermarkImageHref('product', '600x600x4', UPLOAD_PRODUCT_L.$v['photo']) : 'assets/images/noimage.png'?>" title="<?=htmlspecialchars($thumbAlt, ENT_QUOTES, 'UTF-8')?>">
                                        <?=$func->getImage(array_merge(['isLazy' => false, 'sizes' => '600x600x4', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $thumbAlt], watermarkImageArgs('product')))?>
                                    </a>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <div class="right-pro-detail col-md-7 col-lg-7">
                        <?php if($pdpCategoryName !== '') { ?><div class="pts-pdp-category"><?=htmlspecialchars($pdpCategoryName, ENT_QUOTES, 'UTF-8')?></div><?php } ?>
                        <h1 class="title-pro-detail"><?=$func->noBreakModelCode($rowDetail['name'.$lang])?></h1>
                        <?php if(!empty($rowDetail['code'])) { ?><div class="pts-pdp-product-code"><span><?=masp?>:</span> <?=htmlspecialchars($rowDetail['code'], ENT_QUOTES, 'UTF-8')?></div><?php } ?>
                        <div class="desc-pro-detail">
                            <?=(!empty($rowDetail['desc'.$lang])) ? htmlspecialchars_decode($rowDetail['desc'.$lang]) : ''?>
                        </div>
                        <?php if($pdpHeroMetricFields) { ?>
                        <div class="pts-pdp-decision-metrics" aria-label="Thông số chính của sản phẩm">
                            <?php foreach($pdpHeroMetricFields as $pdpMetric) {
                                $metricHasValue = $pdpMetric['value'] !== '';
                                $metricDisplay = $metricHasValue ? $pdpMetric['value'] : '[...]'.($pdpMetric['unit'] !== '' ? ' '.$pdpMetric['unit'] : '');
                            ?>
                            <div class="pts-pdp-decision-metric<?=$metricHasValue ? '' : ' is-placeholder'?>">
                                <span class="pts-pdp-decision-icon" aria-hidden="true">
                                    <?php if(!empty($pdpMetric['iconImage'])) { ?>
                                    <img src="assets/images/icons/<?=htmlspecialchars(isset($pdpMetric['iconDir']) ? $pdpMetric['iconDir'] : ($pdpProfileKey === 'lighting' ? 'pdp-lighting' : 'pdp'), ENT_QUOTES, 'UTF-8')?>/<?=htmlspecialchars($pdpMetric['iconImage'], ENT_QUOTES, 'UTF-8')?>" alt="" loading="lazy" width="28" height="28">
                                    <?php } elseif($pdpMetric['icon'] === 'battery') { ?>
                                    <svg viewBox="0 0 32 32" role="presentation"><rect x="6" y="7" width="19" height="18" rx="2"></rect><path d="M25 13h3v6h-3M10 12h11M10 16h11M10 20h11"></path></svg>
                                    <?php } elseif($pdpMetric['icon'] === 'height') { ?>
                                    <svg viewBox="0 0 32 32" role="presentation"><path d="M16 4v24M12 8l4-4 4 4M12 24l4 4 4-4M8 9h7M8 13h5M8 19h5M8 23h7"></path></svg>
                                    <?php } elseif($pdpMetric['icon'] === 'shield') { ?>
                                    <svg viewBox="0 0 32 32" role="presentation"><path d="M16 4l10 4v7c0 6-4.2 10.7-10 13-5.8-2.3-10-7-10-13V8l10-4z"></path><path d="M11 16l3 3 7-7"></path></svg>
                                    <?php } elseif($pdpMetric['icon'] === 'sun') { ?>
                                    <svg viewBox="0 0 32 32" role="presentation"><circle cx="16" cy="16" r="6"></circle><path d="M16 3v5M16 24v5M3 16h5M24 16h5M7 7l4 4M21 21l4 4M25 7l-4 4M11 21l-4 4"></path></svg>
                                    <?php } else { ?>
                                    <svg viewBox="0 0 32 32" role="presentation"><path d="M18 3L8 18h7l-1 11 10-15h-7l1-11z"></path></svg>
                                    <?php } ?>
                                </span>
                                <div><span><?=htmlspecialchars(mb_strtoupper($pdpMetric['label'], 'UTF-8'), ENT_QUOTES, 'UTF-8')?></span><strong><?=htmlspecialchars($metricDisplay, ENT_QUOTES, 'UTF-8')?></strong><small><?=$metricHasValue ? 'Theo thông số sản phẩm' : 'Đang cập nhật'?></small></div>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                        <?php if($pdpPackageContents !== '') { ?><p class="pts-pdp-package"><strong>Bộ sản phẩm gồm:</strong> <?=htmlspecialchars($pdpPackageContents, ENT_QUOTES, 'UTF-8')?></p><?php } ?>
                        <ul class="attr-pro-detail">
                            <li class="w-clear pts-pdp-price-row">
                                <label class="attr-label-pro-detail pts-pdp-price-label">GIÁ BÁN</label>
                                <div class="attr-content-pro-detail">
                                    <?php if(publicProductRequiresQuote($rowDetail)) { ?>
                                    <span class="price-new-pro-detail"><?=lienhe?></span>
                                    <small class="pts-pdp-price-note">Nhận báo giá theo số lượng và nhu cầu sử dụng</small>
                                    <?php } elseif($rowDetail['sale_price']) { $discountPercent = ($rowDetail['regular_price'] > $rowDetail['sale_price']) ? round((($rowDetail['regular_price'] - $rowDetail['sale_price']) / $rowDetail['regular_price']) * 100) : 0; ?>
                                    <span
                                        class="price-new-pro-detail"><?=$func->formatMoney($rowDetail['sale_price'])?></span><span class="pts-pdp-price-unit">/ bộ</span>
                                    <span
                                        class="price-old-pro-detail"><?=$func->formatMoney($rowDetail['regular_price'])?></span>
                                    <?php if($discountPercent > 0) { ?>
                                    <span class="badge-discount badge-discount-inline">-<?=$discountPercent?>%</span>
                                    <?php } ?>
                                    <small class="pts-pdp-price-note">Giá đã gồm VAT; chi phí vận chuyển và lắp đặt báo riêng theo khu vực.</small>
                                    <?php } else { ?>
                                    <span
                                        class="price-new-pro-detail"><?=($rowDetail['regular_price']) ? $func->formatMoney($rowDetail['regular_price']) : lienhe?></span><?php if($rowDetail['regular_price']) { ?><span class="pts-pdp-price-unit">/ bộ</span>
                                    <small class="pts-pdp-price-note">Giá đã gồm VAT; chi phí vận chuyển và lắp đặt báo riêng theo khu vực.</small>
                                    <?php } ?>
                                    <?php } ?>
                                </div>
                            </li>
                            <?php if(!empty($rowSize)) { ?>
                            <li class="size-block-pro-detail w-clear">
                                <label class="attr-label-pro-detail d-block"><?=kichthuoc?>:</label>
                                <div class="attr-content-pro-detail d-block">
                                    <?php foreach($rowSize as $k => $v) { ?>
                                    <label for="size-pro-detail-<?=$v['id']?>"
                                        class="size-pro-detail text-decoration-none">
                                        <input type="radio" value="<?=$v['id']?>" id="size-pro-detail-<?=$v['id']?>"
                                            name="size-pro-detail">
                                        <?=$v['name'.$lang]?>
                                    </label>
                                    <?php } ?>
                                </div>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php
                            /* Chuyển sang CTA báo giá khi giá công khai bị ẩn. */
                            $isContactPrice = publicProductRequiresQuote($rowDetail);
                        ?>
                        <?php if(!$isContactPrice) { ?>
                        <div class="quantity-pro-detail">
                            <label class="attr-label-pro-detail d-block">Số lượng:</label>
                            <button type="button" class="quantity-step quantity-step-minus" aria-label="Giảm số lượng">−</button>
                            <input type="number" class="qty-pro" value="1" min="1" max="999" step="1" inputmode="numeric" aria-label="Số lượng sản phẩm">
                            <button type="button" class="quantity-step quantity-step-plus" aria-label="Tăng số lượng">+</button>
                        </div>
                        <?php } ?>
                        <div class=" product-info">
                            <?php if(!empty($productList)){ ?>
                            <div class="pro-type">
                                <span>Danh mục: </span>
                                <a class="title" rel="nofollow" href="javascript:"><?=$productList['name'.$lang]?>
                                    <?=(!empty($productCat))?', '.$productCat['name'.$lang]:''?></a>
                            </div>
                            <?php } ?>
                            <?php if(!empty($productBrand)){ ?>
                            <div class="pro-type">
                                <span>Thương hiệu: </span>
                                <a class="title" rel="nofollow" href="javascript:"><?=$productBrand['name'.$lang]?></a>
                            </div>
                            <?php } ?>
                        </div>
                        <?php if($isContactPrice) { ?>
                        <div class="btn-product-detail btn-contact-price pts-catalog-jump">
                            <a href="<?=$pdpCurrentPath?>#pts-pdp-consultation" title="Yêu cầu báo giá"><i class="far fa-file-alt" aria-hidden="true"></i> Yêu cầu báo giá</a>
                            <a href="tel:<?=preg_replace('/[^0-9]/','',$optsetting['hotline'])?>" title="Gọi tư vấn: <?=$optsetting['hotline']?>"><i class="fas fa-phone-alt"></i> Gọi tư vấn</a>
                        </div>
                        <?php } else { ?>
                        <div class="btn-product-detail">
                            <a href="javascript:void(0)" class="addcart" data-id="<?=$rowDetail['id']?>" data-action="buynow" title="Mua ngay">Mua ngay</a>
                            <a href="javascript:void(0)" class="addcart" data-id="<?=$rowDetail['id']?>" data-action="addnow" title="Thêm vào giỏ hàng">Thêm giỏ hàng</a>
                        </div>
                        <?php } ?>
                        <?php
                            /* "Đăng ký đại lý" trước đây là nút to bằng với CTA mua hàng, cạnh
                               tranh sự chú ý với người chỉ muốn hỏi mua (audit UX 2026-09-08:
                               "không dùng quá nhiều CTA cùng cấp độ"). Hạ xuống thành link phụ. */
                        ?>
                        <div class="pts-pdp-dealer-link">
                            <a href="#ptsDealerModal" data-toggle="modal" data-target="#ptsDealerModal" title="Đăng ký làm đại lý">Bạn là đại lý? Đăng ký hợp tác <i class="fas fa-angle-right" aria-hidden="true"></i></a>
                        </div>
                        <?php if(count($ck)){ ?>
                        <div class="CRnew_content_def_pd">
                            <div class="CRnew_list_icon_def_pd CRcustom_dots CRcustom_dots_dif">
                                <?php foreach($ck as $k => $v){ ?>
                                <div class="item_icon_box">
                                    <div class="content_icon_box">
                                        <div class="img_icon_box_sec">
                                            <?= $func->getImage(['class' => '', 'size-error' => '20x20x2', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name' . $lang]]) ?>
                                        </div>
                                        <div class="cont_icon_box_sec">
                                            <p><?=$v['name'.$lang]?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php if($hasProductDescription || $pdpUseCases || $pdpBuyingGuides || $hasProductSpecs || $hasPdpSupport || $pdpFaqs) { ?>
<nav class="pts-pdp-section-nav" aria-label="Điều hướng nội dung sản phẩm">
    <div class="box-detail">
        <?php if($pdpOverviewAnchor !== '') { ?><a href="<?=$pdpCurrentPath?>#<?=$pdpOverviewAnchor?>">Tổng quan</a><?php } ?>
        <?php if($pdpUseCases) { ?><a href="<?=$pdpCurrentPath?>#ung-dung-san-pham">Ứng dụng</a><?php } ?>
        <?php if($pdpBuyingGuides) { ?><a href="<?=$pdpCurrentPath?>#huong-dan-lua-chon">Lựa chọn</a><?php } ?>
        <?php if($hasProductSpecs) { ?><a href="<?=$pdpCurrentPath?>#thong-so-ky-thuat">Thông số kỹ thuật</a><?php } ?>
        <?php if($hasProductWarranty) { ?><a href="<?=$pdpCurrentPath?>#chinh-sach-bao-hanh">Chính sách bảo hành</a><?php } ?>
        <?php if($pdpFaqs) { ?><a href="<?=$pdpCurrentPath?>#cau-hoi-thuong-gap">Câu hỏi thường gặp</a><?php } ?>
        <a href="<?=$pdpCurrentPath?>#pts-pdp-consultation">Yêu cầu tư vấn</a>
    </div>
</nav>

<div class="box-detail pts-pdp-details">
    <?php if($hasProductDescription) { ?>
    <section class="pts-pdp-content-section pts-product-content" id="tong-quan" aria-labelledby="pts-pdp-description-title">
        <h2 class="pts-pdp-section-title" id="pts-pdp-description-title">Mô tả sản phẩm</h2>
        <div class="pts-product-desc">
            <?php if($productSpecHighlights) { ?>
            <div class="pts-pdp-highlights">
                <h3>Điểm nổi bật</h3>
                <ul class="pts-spec-chips"><?php foreach($productSpecHighlights as $chip) { ?><li><?=htmlspecialchars($chip,ENT_QUOTES,'UTF-8')?></li><?php } ?></ul>
            </div>
            <?php } ?>
            <?php if($productSpecParagraph !== '') { ?><p class="pts-desc-para"><?=htmlspecialchars($productSpecParagraph, ENT_QUOTES, 'UTF-8')?></p><?php } ?>
            <?php if($productSectionsHtml !== '') { ?><details class="pts-pdp-more-description"><summary>Xem mô tả chi tiết</summary><div class="pts-desc-sections"><?=$productSectionsHtml?></div></details><?php } ?>
        </div>
    </section>
    <?php } ?>

    <?php if($pdpUseCases) { $pdpUseCaseIcons = array('fa-road', 'fa-industry', 'fa-building'); ?>
    <section class="pts-pdp-content-section pts-pdp-use-cases" id="ung-dung-san-pham" aria-labelledby="pts-pdp-use-case-title">
        <h2 class="pts-pdp-section-title" id="pts-pdp-use-case-title"><?=htmlspecialchars($pdpProfile['application_title'], ENT_QUOTES, 'UTF-8')?></h2>
        <div class="pts-pdp-use-case-grid">
            <?php foreach($pdpUseCases as $useCaseIndex => $useCase) { ?>
            <article class="pts-pdp-use-case-item">
                <?php if(!empty($useCase['photo'])) { ?>
                <div class="pts-pdp-use-case-visual has-image">
                    <?php if(strpos($useCase['photo'], '/') !== false) { $useCasePhotoSrc = ltrim($useCase['photo'], '/'); $useCasePhotoFallback = (strpos($useCasePhotoSrc, 'assets/images/site-visuals/pdp-usecase-v2/') === 0 && preg_match('/\.webp$/i', $useCasePhotoSrc)) ? preg_replace('/\.webp$/i', '.png', $useCasePhotoSrc) : $useCasePhotoSrc; ?>
                    <?php if($useCasePhotoFallback !== $useCasePhotoSrc) { ?><picture><source type="image/webp" srcset="<?=htmlspecialchars($useCasePhotoSrc, ENT_QUOTES, 'UTF-8')?>"><img src="<?=htmlspecialchars($useCasePhotoFallback, ENT_QUOTES, 'UTF-8')?>" alt="<?=htmlspecialchars($useCase['title'], ENT_QUOTES, 'UTF-8')?>" loading="lazy"></picture><?php } else { ?><img src="<?=htmlspecialchars($useCasePhotoSrc, ENT_QUOTES, 'UTF-8')?>" alt="<?=htmlspecialchars($useCase['title'], ENT_QUOTES, 'UTF-8')?>" loading="lazy"><?php } ?>
                    <?php } else { ?>
                    <?=$func->getImage(['class' => '', 'sizes' => '760x475x1', 'upload' => UPLOAD_PRODUCT_L, 'image' => $useCase['photo'], 'alt' => $useCase['title']])?>
                    <?php } ?>
                </div>
                <?php } else { ?>
                <div class="pts-pdp-use-case-visual"><i class="fas <?=$pdpUseCaseIcons[$useCaseIndex]?>" aria-hidden="true"></i><span><?=str_pad($useCaseIndex + 1, 2, '0', STR_PAD_LEFT)?></span></div>
                <?php } ?>
                <div class="pts-pdp-use-case-copy"><h3><?=htmlspecialchars($useCase['title'], ENT_QUOTES, 'UTF-8')?></h3><p><?=htmlspecialchars($useCase['text'], ENT_QUOTES, 'UTF-8')?></p></div>
            </article>
            <?php } ?>
        </div>
    </section>
    <?php } ?>

    <?php if($pdpBuyingGuides) { ?>
    <section class="pts-pdp-content-section pts-pdp-buying-guide" id="huong-dan-lua-chon" aria-labelledby="pts-pdp-guide-title">
        <h2 class="pts-pdp-section-title" id="pts-pdp-guide-title">Thông tin cần biết trước khi lựa chọn</h2>
        <div class="pts-pdp-guide-grid">
            <?php foreach($pdpBuyingGuides as $guide) { ?>
            <div class="pts-pdp-guide-item"><?php if(!empty($guide[3])) { ?><img class="pts-pdp-guide-icon" src="assets/images/icons/<?=htmlspecialchars($guide[4] !== '' ? $guide[4] : 'pdp', ENT_QUOTES, 'UTF-8')?>/<?=htmlspecialchars($guide[3], ENT_QUOTES, 'UTF-8')?>" alt="" loading="lazy"><?php } else { ?><i class="fas <?=$guide[0]?>" aria-hidden="true"></i><?php } ?><div><strong><?=htmlspecialchars($guide[1], ENT_QUOTES, 'UTF-8')?></strong><p><?=htmlspecialchars($guide[2], ENT_QUOTES, 'UTF-8')?></p></div></div>
            <?php } ?>
        </div>
    </section>
    <?php } ?>

    <?php if($hasProductSpecs) { ?>
    <section class="pts-pdp-content-section pts-pdp-spec-section" id="thong-so-ky-thuat" aria-labelledby="pts-pdp-spec-title">
        <h2 class="pts-pdp-section-title" id="pts-pdp-spec-title">Thông số kỹ thuật</h2>
        <?php if(!empty($pdpFullSpecRows)) { ?>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Hạng mục</th><th>Thông tin theo model</th></tr></thead><tbody>
            <?php foreach($pdpFullSpecRows as $specRow) { $specHasValue = $specRow[1] !== ''; ?><tr class="<?=$specHasValue ? '' : 'is-placeholder'?>"><th><?=htmlspecialchars($specRow[0], ENT_QUOTES, 'UTF-8')?></th><td><?=$specHasValue ? htmlspecialchars(htmlspecialchars_decode($specRow[1], ENT_QUOTES), ENT_QUOTES, 'UTF-8') : 'Đang cập nhật'?></td></tr><?php } ?>
        </tbody></table></div>
        <?php } ?>
        <?php if($useLegacySpecs) { ?><div class="pts-product-content pts-pdp-legacy-specs"><?=$productLegacySpecs?></div><?php } ?>
    </section>
    <?php } ?>

    <?php if($hasPdpSupport) { ?>
    <section class="pts-pdp-content-section pts-pdp-support-grid" aria-label="Video, tài liệu và bảo hành">
        <div class="pts-pdp-support-block" id="video-san-pham"><h2 class="pts-pdp-section-title">Video sản phẩm &amp; lắp đặt</h2>
            <?php if(!empty($videoEmbedUrl)) { ?>
            <div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="<?=htmlspecialchars($videoEmbedUrl, ENT_QUOTES, 'UTF-8')?>" title="Video thực tế <?=$rowDetail['name'.$lang]?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
            <?php } else { ?>
            <div class="pts-pdp-video-placeholder" aria-hidden="true"><span class="pts-pdp-video-play">&#9654;</span><p>Video đang được cập nhật</p></div>
            <?php } ?>
        </div>
        <?php if($validProductDocuments) { ?><div class="pts-pdp-support-block" id="tai-lieu-san-pham"><h2 class="pts-pdp-section-title">Tài liệu sản phẩm</h2><div class="pts-policy-links">
            <?php foreach($validProductDocuments as $document) { ?><a href="<?=htmlspecialchars($document['file_path'], ENT_QUOTES, 'UTF-8')?>" target="_blank" rel="noopener"><img class="pts-pdp-support-icon" src="assets/images/icons/pdp/tieuchuantailieu.png" alt="" loading="lazy" width="24" height="24"><span><?=htmlspecialchars($document['title'], ENT_QUOTES, 'UTF-8')?></span><i class="fas fa-download pts-pdp-document-download" aria-hidden="true"></i></a><?php } ?>
        </div></div><?php } ?>
        <?php if($hasProductWarranty) { ?><div class="pts-pdp-support-block" id="chinh-sach-bao-hanh"><h2 class="pts-pdp-section-title">Chính sách bảo hành</h2>
            <?php if(!empty($rowDetail['warranty'])) { ?><div class="pts-pdp-warranty-summary"><img class="pts-pdp-support-icon" src="assets/images/icons/pdp/baohanh.png" alt="" loading="lazy" width="42" height="42"><div><strong>Bảo hành <?=htmlspecialchars($rowDetail['warranty'], ENT_QUOTES, 'UTF-8')?></strong><span>Thời hạn theo thông tin công bố của sản phẩm.</span></div></div><?php } ?>
            <?php if($hasProductWarranty) { ?><div class="pts-product-content pts-pdp-policy"><?php if($productPolicyOverride !== '') { ?><h3>Chính sách riêng của sản phẩm</h3><?php if(!empty($productPolicyOverrideItems)) { ?><ul><?php foreach($productPolicyOverrideItems as $policyLine) { ?><li><?=htmlspecialchars($policyLine, ENT_QUOTES, 'UTF-8')?></li><?php } ?></ul><?php } else { ?><p><?=nl2br(htmlspecialchars($productPolicyOverride, ENT_QUOTES, 'UTF-8'))?></p><?php } ?><?php } else { ?><p>Áp dụng chính sách bảo hành hiện hành của Phúc Thịnh Solar:</p><div class="pts-policy-links"><?php foreach($pdpWarrantyPolicies as $policyItem) { ?><a href="ho-tro/<?=$policyItem[$sluglang]?>/"><img class="pts-pdp-support-icon" src="assets/images/icons/pdp/baohanh.png" alt="" loading="lazy" width="24" height="24"><span><?=htmlspecialchars($policyItem['name'.$lang],ENT_QUOTES,'UTF-8')?></span></a><?php } ?></div><?php } ?></div><?php } ?>
        </div><?php } ?>
    </section>
    <?php } ?>

    <?php if($pdpFaqs) { ?>
    <section class="pts-pdp-content-section pts-pdp-faq" id="cau-hoi-thuong-gap" aria-labelledby="pts-pdp-faq-title">
        <h2 class="pts-pdp-section-title" id="pts-pdp-faq-title">Câu hỏi thường gặp</h2>
        <?php foreach($pdpFaqs as $faq) { ?><details><summary><?=htmlspecialchars($faq[0], ENT_QUOTES, 'UTF-8')?></summary><p><?=htmlspecialchars($faq[1], ENT_QUOTES, 'UTF-8')?></p></details><?php } ?>
    </section>
    <?php } ?>

</div>
<?php } ?>

<div class="box-detail mg-top-30 pts-pdp-reviews">
    <details class="pts-pdp-review-disclosure">
        <summary>Đánh giá sản phẩm <span>(<?=intval($comment->total)?> nhận xét)</span></summary>
        <div class="pts-pdp-review-content"><?php include TEMPLATE."product/comment.php"; ?></div>
    </details>
</div>

<section class="box-detail pts-pdp-consultation" id="pts-pdp-consultation" aria-labelledby="pts-pdp-consultation-title">
    <div class="pts-pdp-consultation-copy">
        <h2 id="pts-pdp-consultation-title">Nhận tư vấn cho sản phẩm này</h2>
        <p>Đội ngũ Phúc Thịnh Solar sẽ tư vấn giải pháp phù hợp với nhu cầu thực tế của bạn.</p>
        <strong>Sản phẩm đang chọn: <span><?=htmlspecialchars($rowDetail['name'.$lang], ENT_QUOTES, 'UTF-8')?></span></strong>
    </div>
    <div class="form-register pts-pdp-consultation-form">
        <form class="validation-newsletter" novalidate method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="form_csrf" value="<?=htmlspecialchars(formCsrfToken(), ENT_QUOTES, 'UTF-8')?>">
            <input type="hidden" name="dataNewsletter[product_id]" value="<?=(int)$rowDetail['id']?>">
            <input type="hidden" name="dataNewsletter[profile_key]" value="<?=htmlspecialchars($pdpProfileKey, ENT_QUOTES, 'UTF-8')?>">
            <div class="newsletter-input pts-form-half"><label for="fullname-newsletter">Họ và tên*</label><input type="text" class="form-control" id="fullname-newsletter" name="dataNewsletter[fullname]" maxlength="100" autocomplete="name" placeholder="Nhập họ và tên" required></div>
            <div class="newsletter-input pts-form-half"><label for="phone-newsletter">Số điện thoại*</label><input type="text" class="form-control" id="phone-newsletter" name="dataNewsletter[phone]" maxlength="20" autocomplete="tel" inputmode="tel" placeholder="Nhập số điện thoại" required></div>
            <div class="newsletter-input pts-form-half"><label for="email-newsletter">Email</label><input type="email" class="form-control" id="email-newsletter" name="dataNewsletter[email]" maxlength="150" autocomplete="email" placeholder="Nhập email"></div>
            <div class="newsletter-input pts-form-half"><label for="consult-city">Tỉnh/Thành phố</label><select class="form-control pts-consult-city" name="dataNewsletter[city_id]" id="consult-city"><option value="">Chọn Tỉnh/Thành phố</option><?php if(count($city)){foreach($city as $v){ ?><option value="<?=(int)$v['id']?>"><?=htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8')?></option><?php }} ?></select></div>
            <div class="newsletter-input pts-form-half"><label for="consult-ward">Phường/Xã</label><select class="form-control pts-consult-ward" name="dataNewsletter[ward_id]" id="consult-ward" disabled><option value="">Chọn Phường/Xã</option></select></div>
            <div class="newsletter-input pts-form-full pts-pdp-user-type"><span class="pts-pdp-field-label">Bạn là...</span><div class="radio-user custom-control custom-radio"><input type="radio" id="canhan" name="dataNewsletter[loaihinh]" class="custom-control-input" value="Người dùng cá nhân"><label class="custom-control-label" for="canhan">Người dùng cá nhân</label></div><div class="radio-user custom-control custom-radio"><input type="radio" id="doanhnghiep" name="dataNewsletter[loaihinh]" class="custom-control-input" value="Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…"><label class="custom-control-label" for="doanhnghiep">Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…</label></div></div>
            <div class="newsletter-input pts-form-half"><label for="need-newsletter">Nhu cầu cần tư vấn</label><select class="form-control" id="need-newsletter" name="dataNewsletter[need_code]"><option value="">Chọn nhu cầu</option><?php foreach((array)$pdpProfile['needs'] as $needCode => $needLabel) { ?><option value="<?=htmlspecialchars($needCode, ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($needLabel, ENT_QUOTES, 'UTF-8')?></option><?php } ?></select></div>
            <div class="newsletter-input pts-form-half"><label for="congsuat-newsletter">Số lượng dự kiến</label><input type="text" class="form-control" id="congsuat-newsletter" name="dataNewsletter[congsuat]" maxlength="100" placeholder="Ví dụ: 2 bộ"></div>
            <div class="newsletter-input pts-form-full"><label for="details-newsletter">Thông tin cần tư vấn thêm</label><textarea class="form-control" id="details-newsletter" name="dataNewsletter[details]" rows="3" maxlength="1000" placeholder="Mô tả vị trí lắp đặt, tải sử dụng hoặc yêu cầu của bạn"></textarea></div>
            <div class="newsletter-button"><input type="submit" class="btn btn-sm btn-danger" name="submit-newsletter" value="Gửi yêu cầu tư vấn"><input type="hidden" name="recaptcha_response_newsletter" id="recaptchaResponseNewsletter"></div>
        </form>
    </div>
</section>
<?php if(!empty($product)) {?>
<div class="box-detail mg-top-30 pts-pdp-related-products">
    <div class="title-product-orther"><span>Sản phẩm liên quan</span></div>
    <?php if(!empty($product)) {?>
    <div class="position-relative">
        <div class="owl-page owl-carousel owl-theme" data-xsm-items="2:15" data-sm-items="2:15" data-md-items="3:15"
            data-lg-items="3:24" data-autoplay="0" data-xlg-items="4:24" data-nav="1" data-mousedrag="1"
            data-touchdrag="1"
            data-navtext="<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-chevron-left' width='44' height='45' viewBox='0 0 24 24' stroke-width='1.5' stroke='#2c3e50' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><polyline points='15 6 9 12 15 18' /></svg>|<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-chevron-right' width='44' height='45' viewBox='0 0 24 24' stroke-width='1.5' stroke='#2c3e50' fill='none' stroke-linecap='round' stroke-linejoin='round'><path stroke='none' d='M0 0h24v24H0z' fill='none'/><polyline points='9 6 15 12 9 18' /></svg>"
            data-navcontainer=".control-pro-detail2">
            <?php $pcNoLazy = true; $pcInCarousel = true; /* card nằm trong Owl carousel - xem chú thích ở partial */ ?>
            <?php foreach($product as $k => $v) { include ROOT."templates/product/_product_card.php"; } ?>
        </div>
        <div class="control-pro-detail2 control-owl transition"></div>
    </div>
    <?php } ?>
</div>
<?php } ?>
<?php if(!empty($productWhyChooseUs)) { ?>
<section class="box-detail mg-top-30 pts-why-choose-us" aria-labelledby="pts-why-choose-title">
    <h2 class="title-product-orther" id="pts-why-choose-title"><span>Lý do chọn Phúc Thịnh Solar</span></h2>
    <div class="pts-why-choose-grid">
        <?php foreach($productWhyChooseUs as $whyChooseItem) { ?>
        <div class="pts-why-choose-item">
            <span class="pts-why-choose-icon"><?=$func->getImage(['class' => '', 'sizes' => '64x64x2', 'upload' => UPLOAD_NEWS_L, 'image' => $whyChooseItem['photo'], 'alt' => ''])?></span>
            <strong><?=htmlspecialchars($whyChooseItem['name'.$lang], ENT_QUOTES, 'UTF-8')?></strong>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>

</main>
<script>
(function(){
    var consultCity = document.querySelector('.pts-consult-city');
    var consultWard = document.querySelector('.pts-consult-ward');
    if(consultCity && consultWard) consultCity.addEventListener('change', function(){
        consultWard.disabled = true;
        consultWard.innerHTML = '<option value="">Đang tải Phường/Xã…</option>';
        if(!this.value) { consultWard.innerHTML = '<option value="">Chọn Phường/Xã</option>'; return; }
        var body = new URLSearchParams(); body.append('id_city', this.value);
        fetch('api/ward-city.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'}, body:body.toString()})
            .then(function(response){ if(!response.ok) throw new Error('ward'); return response.text(); })
            .then(function(html){ consultWard.innerHTML = html; consultWard.disabled = false; })
            .catch(function(){ consultWard.innerHTML = '<option value="">Không tải được Phường/Xã</option>'; });
    });

    var nav = document.querySelector('.pts-pdp-section-nav');
    if(!nav) return;
    var links = Array.prototype.slice.call(nav.querySelectorAll('a[href*="#"]'));
    var items = links.map(function(link){
        var href = link.getAttribute('href');
        var target = document.getElementById(href.slice(href.indexOf('#') + 1));
        return target ? {link:link,target:target} : null;
    }).filter(Boolean);
    if(!items.length) return;

    var activeLink = null;
    function setActive(link){
        if(activeLink === link) return;
        links.forEach(function(item){
            var active = item === link;
            item.classList.toggle('is-active', active);
            if(active) item.setAttribute('aria-current', 'location');
            else item.removeAttribute('aria-current');
        });
        activeLink = link;
    }
    function update(){
        /* Buffer 28px từng khớp sát scroll-margin-top của section (68px mobile,
           82px desktop) nên khi cuộn/anchor tới điểm scroll-margin dừng đúng
           mép, tiêu đề vẫn còn nằm quá ngưỡng vài px và link chưa kịp active
           (bắt được bằng Playwright: 2026-09-24). Nới buffer để chắc chắn qua
           ngưỡng. */
        var marker = window.pageYOffset + nav.getBoundingClientRect().height + 60;
        var current = items[0];
        items.forEach(function(item){
            var top = item.target.getBoundingClientRect().top + window.pageYOffset;
            if(top <= marker) current = item;
        });
        setActive(current.link);
    }
    var queued = false;
    function requestUpdate(){
        if(queued) return;
        queued = true;
        window.requestAnimationFrame(function(){ queued = false; update(); });
    }
    links.forEach(function(link){ link.addEventListener('click', function(){ setActive(link); }); });
    window.addEventListener('scroll', requestUpdate, {passive:true});
    window.addEventListener('resize', requestUpdate);
    update();
})();
</script>
