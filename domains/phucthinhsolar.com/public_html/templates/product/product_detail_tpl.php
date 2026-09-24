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
$structuredSpecResult = productStructuredSpecs($rowDetail, $lang, $pdpProfileKey);
$structuredSpecs = $structuredSpecResult['specs'];
$useLegacySpecs = $structuredSpecResult['use_legacy'];
$certificateContent = (!empty($rowDetail['certificate'.$lang])) ? htmlspecialchars_decode($rowDetail['certificate'.$lang]) : '';
/* Mô tả sản phẩm = MỘT loại: chip thông số (tự động) + khối content_section
   (kế thừa sản phẩm > hãng > danh mục) + đoạn văn tự sinh khi chưa có khối. */
$productSpecHighlights = productAutoHighlights($rowDetail, $lang, isset($type) ? $type : '', $pdpProfileKey);
$productLegacySpecs = trim(isset($rowDetail['thongsokythuat'.$lang]) ? htmlspecialchars_decode($rowDetail['thongsokythuat'.$lang]) : '');
$productPolicyOverride = trim(isset($rowDetail['chinhsachmuahang'.$lang]) ? htmlspecialchars_decode($rowDetail['chinhsachmuahang'.$lang]) : '');
$productSections = isset($productSections) ? $productSections : array('blocks' => array(), 'source' => '');
$pdpSectionSplit = productSplitFaqBlocks(isset($productSections['blocks']) ? $productSections['blocks'] : array());
$productSectionsHtml = (!empty($pdpSectionSplit['rest'])) ? productRenderSections($pdpSectionSplit['rest'], UPLOAD_PRODUCT_L, $func) : '';
$productSpecParagraph = ($productSectionsHtml === '' && $productSections['source'] !== 'hide') ? productAutoDescription($rowDetail, $lang, isset($type) ? $type : '', $pdpProfileKey) : '';
$hasProductDescription = $productSpecHighlights || $productSectionsHtml !== '' || $productSpecParagraph !== '';
$hasProductSpecs = !empty($structuredSpecs) || ($useLegacySpecs && $productLegacySpecs !== '');
$hasProductPolicy = $productPolicyOverride !== '' || !empty($policy);

/* Nếu ảnh đại diện bị thiếu trên đĩa nhưng gallery còn ảnh thật, dùng ảnh đó
   làm ảnh chính và bỏ các mục gallery đã mất. Chỉ đọc filesystem, không sửa DB. */
$detailMainPhoto = '';
$detailPhotoCandidate = !empty($rowDetail['photo']) ? basename((string)$rowDetail['photo']) : '';
if($detailPhotoCandidate !== '' && is_file(UPLOAD_PRODUCT_L.$detailPhotoCandidate)) $detailMainPhoto = $detailPhotoCandidate;
$detailGalleryPhotos = array();
if(!empty($rowDetailPhoto) && is_array($rowDetailPhoto))
{
    foreach($rowDetailPhoto as $detailPhotoRow)
    {
        $detailPhotoCandidate = !empty($detailPhotoRow['photo']) ? basename((string)$detailPhotoRow['photo']) : '';
        if($detailPhotoCandidate === '' || !is_file(UPLOAD_PRODUCT_L.$detailPhotoCandidate)) continue;
        if($detailMainPhoto === '') $detailMainPhoto = $detailPhotoCandidate;
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

/* Ma trận thông số quyết định ở hero: chỉ lấy dữ liệu thật đã có, ưu tiên các
   field có cấu trúc rồi mới bổ sung từ additional_specs. Không dựng số liệu
   mẫu của file tham chiếu cho sản phẩm chưa được xác minh. */
$pdpDecisionMetrics = array();
$pdpMetricDefinitions = array(
    'cong_suat' => array('Công suất', 'fa-bolt', 'Công suất định mức của sản phẩm'),
    'battery_capacity' => array('Pin lưu trữ', 'fa-battery-three-quarters', 'Dung lượng năng lượng lưu trữ'),
    'panel_power' => array('Tấm pin', 'fa-solar-panel', 'Công suất tấm pin đi kèm'),
    'lighting_area' => array('Phạm vi sử dụng', 'fa-expand', 'Diện tích chiếu sáng tham khảo'),
    'installation_height' => array('Chiều cao lắp', 'fa-ruler-vertical', 'Theo khuyến nghị của sản phẩm'),
    'lighting_time' => array('Thời gian hoạt động', 'fa-clock', 'Theo điều kiện sử dụng công bố'),
    'waterproof_rating' => array('Cấp bảo vệ', 'fa-shield-alt', 'Khả năng bảo vệ ngoài trời'),
    'warranty' => array('Bảo hành', 'fa-award', 'Xem điều kiện áp dụng')
);
$pdpMetricIcons = array(
    'ac_power' => 'fa-bolt', 'motor_power' => 'fa-bolt', 'phase_count' => 'fa-wave-square',
    'mppt_count' => 'fa-th', 'usable_capacity' => 'fa-battery-three-quarters', 'battery_voltage' => 'fa-plug',
    'cycle_life' => 'fa-sync-alt', 'cell_type' => 'fa-solar-panel', 'efficiency' => 'fa-chart-line',
    'runtime' => 'fa-clock', 'max_flow' => 'fa-water', 'max_head' => 'fa-ruler-vertical',
    'rated_voltage' => 'fa-plug', 'rated_current' => 'fa-bolt', 'compatibility' => 'fa-link'
);
$profileMetricDefinitions = array();
foreach((array)$pdpProfile['metrics'] as $metricKey)
{
    $metricLabel = isset($pdpProfile['fields'][$metricKey]) ? $pdpProfile['fields'][$metricKey] : $metricKey;
    $profileMetricDefinitions[$metricKey] = array($metricLabel, isset($pdpMetricIcons[$metricKey]) ? $pdpMetricIcons[$metricKey] : 'fa-check-circle', 'Thông tin theo đúng model');
}
$pdpMetricDefinitions = array_merge($pdpMetricDefinitions, $profileMetricDefinitions);
$pdpMetricLabels = array();
$pdpMetricKeys = array();
foreach(array_keys($profileMetricDefinitions) as $metricField)
{
    $metricMeta = $pdpMetricDefinitions[$metricField];
    if(count($pdpDecisionMetrics) >= 4) continue;
    $metricValue = trim(strip_tags(productPdpSpecValue($rowDetail, $metricField)));
    if($metricValue === '') continue;
    if($metricField === 'cong_suat' && is_numeric(str_replace(',', '.', $metricValue))) $metricValue = sprintf('%g', (float)str_replace(',', '.', $metricValue)).'W';
    $pdpDecisionMetrics[] = array('label' => $metricMeta[0], 'icon' => $metricMeta[1], 'note' => $metricMeta[2], 'value' => $metricValue);
    $pdpMetricLabels[mb_strtolower($metricMeta[0], 'UTF-8')] = true;
    $pdpMetricKeys[$metricField] = true;
}
foreach($structuredSpecs as $metricSpec)
{
    if(count($pdpDecisionMetrics) >= 4) break;
    $metricKey = isset($metricSpec['key']) ? (string)$metricSpec['key'] : '';
    if($metricKey !== '' && isset($pdpMetricKeys[$metricKey])) continue;
    if($pdpProfileKey !== 'general' && ($metricKey === '' || !in_array($metricKey, (array)$pdpProfile['metrics'], true))) continue;
    $metricLabel = trim((string)$metricSpec['label']);
    $metricValue = trim(htmlspecialchars_decode((string)$metricSpec['value'], ENT_QUOTES));
    if($metricLabel === '' || $metricValue === '' || isset($pdpMetricLabels[mb_strtolower($metricLabel, 'UTF-8')])) continue;
    $pdpDecisionMetrics[] = array('label' => $metricLabel, 'icon' => 'fa-check-circle', 'note' => 'Thông tin theo đúng model', 'value' => $metricValue);
    $pdpMetricLabels[mb_strtolower($metricLabel, 'UTF-8')] = true;
    if($metricKey !== '') $pdpMetricKeys[$metricKey] = true;
}
$pdpPackageContents = !empty($rowDetail['package_contents']) ? trim(strip_tags((string)$rowDetail['package_contents'])) : '';

/* Chỉ dựng hướng dẫn lựa chọn từ dữ liệu kỹ thuật thật của sản phẩm. */
$pdpBuyingGuides = array();
foreach((array)$pdpProfile['fields'] as $guideKey => $guideLabel)
{
    if(in_array($guideKey, (array)$pdpProfile['metrics'], true)) continue;
    $guideValue = productPdpSpecValue($rowDetail, $guideKey);
    if($guideValue === '') continue;
    $pdpBuyingGuides[] = array(isset($pdpMetricIcons[$guideKey]) ? $pdpMetricIcons[$guideKey] : 'fa-check-circle', $guideLabel, $guideLabel.': '.$guideValue.'.');
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
/* Mỗi profile có bộ ảnh minh hoạ dùng chung làm fallback an toàn. Nội dung
   do Admin nhập trong content block vẫn được ưu tiên, chỉ bổ sung ảnh còn
   thiếu để card không rơi về placeholder icon. */
if(!$pdpUseCases && $pdpProfileKey !== 'general') $pdpUseCases = productPdpUseCaseDefaults($pdpProfileKey);
foreach($pdpUseCases as $pdpUseCaseIndex => &$pdpUseCase)
{
    if(empty($pdpUseCase['photo'])) $pdpUseCase['photo'] = productPdpUseCasePhoto($pdpProfileKey, isset($pdpUseCase['title']) ? $pdpUseCase['title'] : '', $pdpUseCaseIndex);
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
$hasCertificate = !empty($certificateContent);
$validProductDocuments = array();
foreach((array)(isset($productDocuments) ? $productDocuments : array()) as $document)
{
    $documentPath = trim(isset($document['file_path']) ? (string)$document['file_path'] : '');
    $documentTitle = trim(isset($document['title']) ? (string)$document['title'] : '');
    $isRemoteDocument = filter_var($documentPath, FILTER_VALIDATE_URL) && strtolower((string)parse_url($documentPath, PHP_URL_SCHEME)) === 'https';
    $isLocalDocument = $documentPath !== '' && is_file(ROOT.ltrim($documentPath, '/'));
    if($documentTitle !== '' && ($isRemoteDocument || $isLocalDocument)) $validProductDocuments[] = $document;
}
$hasPdpSupport = !empty($videoEmbedUrl) || $hasCertificate || $hasProductWarranty || !empty($validProductDocuments);
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
$pdpSupportAnchor = $validProductDocuments ? 'tai-lieu-san-pham' : ($hasProductWarranty ? 'chinh-sach-bao-hanh' : ($hasCertificate ? 'chung-nhan-san-pham' : (!empty($videoEmbedUrl) ? 'video-san-pham' : '')));
if($validProductDocuments || $hasCertificate) $pdpSupportLabel = $hasProductWarranty ? 'Tài liệu & bảo hành' : 'Tài liệu sản phẩm';
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
                        <?php if($pdpDecisionMetrics) { ?>
                        <div class="pts-pdp-decision-metrics" aria-label="Thông số chính của sản phẩm">
                            <?php foreach($pdpDecisionMetrics as $pdpMetric) { ?>
                            <div class="pts-pdp-decision-metric">
                                <i class="fas <?=$pdpMetric['icon']?>" aria-hidden="true"></i>
                                <div><span><?=htmlspecialchars($pdpMetric['label'], ENT_QUOTES, 'UTF-8')?></span><strong><?=htmlspecialchars($pdpMetric['value'], ENT_QUOTES, 'UTF-8')?></strong><small><?=htmlspecialchars($pdpMetric['note'], ENT_QUOTES, 'UTF-8')?></small></div>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                        <?php if($pdpPackageContents !== '') { ?><p class="pts-pdp-package"><strong>Bộ sản phẩm gồm:</strong> <?=htmlspecialchars($pdpPackageContents, ENT_QUOTES, 'UTF-8')?></p><?php } ?>
                        <ul class="attr-pro-detail">
                            <li class="w-clear pts-pdp-price-row">
                                <label class="attr-label-pro-detail"><?=gia?>:</label>
                                <div class="attr-content-pro-detail">
                                    <?php if(publicProductRequiresQuote($rowDetail)) { ?>
                                    <span class="price-new-pro-detail"><?=lienhe?></span>
                                    <small class="pts-pdp-price-note">Nhận báo giá theo số lượng và nhu cầu sử dụng</small>
                                    <?php } elseif($rowDetail['sale_price']) { $discountPercent = ($rowDetail['regular_price'] > $rowDetail['sale_price']) ? round((($rowDetail['regular_price'] - $rowDetail['sale_price']) / $rowDetail['regular_price']) * 100) : 0; ?>
                                    <span
                                        class="price-new-pro-detail"><?=$func->formatMoney($rowDetail['sale_price'])?></span>
                                    <span
                                        class="price-old-pro-detail"><?=$func->formatMoney($rowDetail['regular_price'])?></span>
                                    <?php if($discountPercent > 0) { ?>
                                    <span class="badge-discount badge-discount-inline">-<?=$discountPercent?>%</span>
                                    <?php } ?>
                                    <?php } else { ?>
                                    <span
                                        class="price-new-pro-detail"><?=($rowDetail['regular_price']) ? $func->formatMoney($rowDetail['regular_price']) : lienhe?></span>
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
        <?php if($hasProductSpecs) { ?><a href="<?=$pdpCurrentPath?>#thong-so-ky-thuat">Thông số kỹ thuật</a><?php } ?>
        <?php if($pdpSupportAnchor !== '') { ?><a href="<?=$pdpCurrentPath?>#<?=$pdpSupportAnchor?>"><?=htmlspecialchars($pdpSupportLabel, ENT_QUOTES, 'UTF-8')?></a><?php } ?>
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
                    <?php if(strpos($useCase['photo'], '/') !== false) { ?><img src="<?=htmlspecialchars(ltrim($useCase['photo'], '/'), ENT_QUOTES, 'UTF-8')?>" alt="<?=htmlspecialchars($useCase['title'], ENT_QUOTES, 'UTF-8')?>" loading="lazy"><?php } else { ?>
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
            <div class="pts-pdp-guide-item"><i class="fas <?=$guide[0]?>" aria-hidden="true"></i><div><strong><?=htmlspecialchars($guide[1], ENT_QUOTES, 'UTF-8')?></strong><p><?=htmlspecialchars($guide[2], ENT_QUOTES, 'UTF-8')?></p></div></div>
            <?php } ?>
        </div>
    </section>
    <?php } ?>

    <?php if($hasProductSpecs) { ?>
    <section class="pts-pdp-content-section pts-pdp-spec-section" id="thong-so-ky-thuat" aria-labelledby="pts-pdp-spec-title">
        <h2 class="pts-pdp-section-title" id="pts-pdp-spec-title">Thông số kỹ thuật</h2>
        <?php if(!empty($structuredSpecs)) { ?>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Hạng mục</th><th>Thông tin sản phẩm</th></tr></thead><tbody>
            <?php foreach($structuredSpecs as $spec) { ?><tr><th><?=htmlspecialchars($spec['label'], ENT_QUOTES, 'UTF-8')?></th><td><?=htmlspecialchars(htmlspecialchars_decode($spec['value'], ENT_QUOTES), ENT_QUOTES, 'UTF-8')?></td></tr><?php } ?>
        </tbody></table></div>
        <?php } ?>
        <?php if($useLegacySpecs) { ?><div class="pts-product-content pts-pdp-legacy-specs"><?=$productLegacySpecs?></div><?php } ?>
    </section>
    <?php } ?>

    <?php if($hasPdpSupport) { ?>
    <section class="pts-pdp-content-section pts-pdp-support-grid" aria-label="Video, chứng nhận và bảo hành">
        <?php if(!empty($videoEmbedUrl)) { ?>
        <div class="pts-pdp-support-block" id="video-san-pham"><h2 class="pts-pdp-section-title">Video sản phẩm &amp; lắp đặt</h2><div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="<?=htmlspecialchars($videoEmbedUrl, ENT_QUOTES, 'UTF-8')?>" title="Video thực tế <?=$rowDetail['name'.$lang]?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></div>
        <?php } ?>
        <?php if($hasCertificate) { ?><div class="pts-pdp-support-block" id="chung-nhan-san-pham"><h2 class="pts-pdp-section-title">Chứng nhận / kiểm nghiệm</h2>
            <div class="pts-product-content pts-pdp-certificate"><?=$certificateContent?></div>
        </div><?php } ?>
        <?php if($validProductDocuments) { ?><div class="pts-pdp-support-block" id="tai-lieu-san-pham"><h2 class="pts-pdp-section-title">Tài liệu sản phẩm</h2><div class="pts-policy-links">
            <?php foreach($validProductDocuments as $document) { ?><a href="<?=htmlspecialchars($document['file_path'], ENT_QUOTES, 'UTF-8')?>" target="_blank" rel="noopener"><i class="far fa-file-pdf" aria-hidden="true"></i><?=htmlspecialchars($document['title'], ENT_QUOTES, 'UTF-8')?></a><?php } ?>
        </div></div><?php } ?>
        <?php if($hasProductWarranty) { ?><div class="pts-pdp-support-block" id="chinh-sach-bao-hanh"><h2 class="pts-pdp-section-title">Chính sách bảo hành</h2>
            <?php if(!empty($rowDetail['warranty'])) { ?><div class="pts-pdp-warranty-summary"><i class="fas fa-shield-alt" aria-hidden="true"></i><div><strong>Bảo hành <?=htmlspecialchars($rowDetail['warranty'], ENT_QUOTES, 'UTF-8')?></strong><span>Thời hạn theo thông tin công bố của sản phẩm.</span></div></div><?php } ?>
            <?php if($hasProductWarranty) { ?><div class="pts-product-content pts-pdp-policy"><?php if($productPolicyOverride !== '') { ?><h3>Chính sách riêng của sản phẩm</h3><?=$productPolicyOverride?><?php } else { ?><p>Áp dụng chính sách bảo hành hiện hành của Phúc Thịnh Solar:</p><div class="pts-policy-links"><?php foreach($pdpWarrantyPolicies as $policyItem) { ?><a href="ho-tro/<?=$policyItem[$sluglang]?>/"><i class="fas fa-shield-alt" aria-hidden="true"></i><?=htmlspecialchars($policyItem['name'.$lang],ENT_QUOTES,'UTF-8')?></a><?php } ?></div><?php } ?></div><?php } ?>
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
            <div class="newsletter-input pts-form-half"><label for="email-newsletter">Email*</label><input type="email" class="form-control" id="email-newsletter" name="dataNewsletter[email]" maxlength="150" autocomplete="email" placeholder="Nhập email" required></div>
            <div class="newsletter-input pts-form-half"><label for="consult-city">Tỉnh/Thành phố*</label><select class="form-control pts-consult-city" name="dataNewsletter[city_id]" id="consult-city" required><option value="">Chọn Tỉnh/Thành phố</option><?php if(count($city)){foreach($city as $v){ ?><option value="<?=(int)$v['id']?>"><?=htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8')?></option><?php }} ?></select></div>
            <div class="newsletter-input pts-form-half"><label for="consult-ward">Phường/Xã*</label><select class="form-control pts-consult-ward" name="dataNewsletter[ward_id]" id="consult-ward" required disabled><option value="">Chọn Phường/Xã</option></select></div>
            <div class="newsletter-input pts-form-full pts-pdp-user-type"><span class="pts-pdp-field-label">Bạn là...*</span><div class="radio-user custom-control custom-radio"><input type="radio" id="canhan" name="dataNewsletter[loaihinh]" class="custom-control-input" value="Người dùng cá nhân" required><label class="custom-control-label" for="canhan">Người dùng cá nhân</label></div><div class="radio-user custom-control custom-radio"><input type="radio" id="doanhnghiep" name="dataNewsletter[loaihinh]" class="custom-control-input" value="Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…" required><label class="custom-control-label" for="doanhnghiep">Đại lý/Nhà phân phối, bán lẻ/Nhà thầu…</label></div></div>
            <div class="newsletter-input pts-form-half"><label for="need-newsletter">Nhu cầu cần tư vấn*</label><select class="form-control" id="need-newsletter" name="dataNewsletter[need_code]" required><option value="">Chọn nhu cầu</option><?php foreach((array)$pdpProfile['needs'] as $needCode => $needLabel) { ?><option value="<?=htmlspecialchars($needCode, ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($needLabel, ENT_QUOTES, 'UTF-8')?></option><?php } ?></select></div>
            <div class="newsletter-input pts-form-half"><label for="congsuat-newsletter">Số lượng dự kiến</label><input type="text" class="form-control" id="congsuat-newsletter" name="dataNewsletter[congsuat]" maxlength="100" placeholder="Ví dụ: 2 bộ"></div>
            <div class="newsletter-input pts-form-full"><label for="details-newsletter">Thông tin cần tư vấn thêm</label><textarea class="form-control" id="details-newsletter" name="dataNewsletter[details]" rows="3" maxlength="1000" placeholder="Mô tả vị trí lắp đặt, tải sử dụng hoặc yêu cầu của bạn"></textarea></div>
            <div class="newsletter-button"><input type="submit" class="btn btn-sm btn-danger" name="submit-newsletter" value="Gửi yêu cầu tư vấn"><input type="hidden" name="recaptcha_response_newsletter" id="recaptchaResponseNewsletter"></div>
        </form>
    </div>
</section>
<?php if(!empty($productRelatedProjects)) { ?>
<div class="box-detail mg-top-30 pts-related-showcase pts-product-projects pts-pdp-projects">
    <div class="title-product-orther"><span>Dự án thực tế sử dụng sản phẩm</span></div>
    <div class="pts-related-grid">
        <?php foreach($productRelatedProjects as $relatedProject) { ?>
            <a class="pts-related-card" href="<?=$relatedProject[$sluglang]?>" title="<?=$relatedProject['name'.$lang]?>">
                <span class="pts-project-media">
                    <?=$func->getImage(['class' => 'w-100 pts-project-media-main', 'sizes' => '397x340x1', 'upload' => UPLOAD_NEWS_L, 'image' => $relatedProject['photo'], 'alt' => $relatedProject['name'.$lang]])?>
                    <?php $relatedGallery = isset($productProjectGallery[(int)$relatedProject['id']]) ? array_slice($productProjectGallery[(int)$relatedProject['id']], 0, 3) : array(); ?>
                    <?php if($relatedGallery) { ?>
                        <span class="pts-project-media-thumbs" aria-label="Ảnh thực tế dự án">
                            <?php foreach($relatedGallery as $projectPhoto) { ?>
                                <?=$func->getImage(['class' => 'w-100', 'sizes' => '160x120x1', 'upload' => UPLOAD_NEWS_L, 'image' => $projectPhoto, 'alt' => $relatedProject['name'.$lang]])?>
                            <?php } ?>
                        </span>
                    <?php } ?>
                </span>
                <span class="pts-related-card-body">
                    <strong><?=$relatedProject['name'.$lang]?></strong>
                    <?php if(!empty($relatedProject['address'])) { ?><small><i class="fas fa-map-marker-alt"></i> <?=$relatedProject['address']?></small><?php } ?>
                </span>
            </a>
        <?php } ?>
    </div>
</div>
<?php } ?>
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
