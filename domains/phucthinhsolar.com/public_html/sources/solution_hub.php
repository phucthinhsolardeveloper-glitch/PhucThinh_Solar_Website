<?php
if(!defined('SOURCES')) die('Error');
require_once LIBRARIES.'solution_hub.php';
require_once LIBRARIES.'product_category_urls.php';
$pageRendersVisibleH1 = true;
$solutionUi = $lang === 'en' ? array(
    'eyebrow' => 'Solutions for your needs', 'title' => 'Choose the right solution for your needs',
    'intro' => 'Explore our solar energy solutions or go directly to the equipment you need. Our team can help you choose.',
    'choices' => 'Explore our solutions', 'choice' => 'View solution', 'category_prefix' => 'Solutions for',
    'equipment_eyebrow' => 'Find equipment quickly', 'equipment' => 'Go directly to products', 'all_products' => 'View all products',
    'contact' => 'Not sure which solution is right for you?', 'contact_desc' => 'Tell Phuc Thinh Solar about your needs. Our team is ready to help.',
    'consult' => 'Get advice', 'phone' => 'Call our hotline', 'empty' => 'Solutions are being updated. Please contact us for advice.',
    'category_intro' => 'Explore solutions designed for this need and choose the option that fits your project.'
) : array(
    'eyebrow' => 'Giải pháp theo nhu cầu', 'title' => 'Chọn đúng giải pháp cho nhu cầu của bạn',
    'intro' => 'Khám phá giải pháp năng lượng mặt trời hoặc tìm ngay thiết bị bạn cần. Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ bạn lựa chọn.',
    'choices' => 'Khám phá các giải pháp', 'choice' => 'Xem giải pháp', 'category_prefix' => 'Giải pháp cho',
    'equipment_eyebrow' => 'Tìm nhanh theo thiết bị', 'equipment' => 'Đi thẳng đến sản phẩm', 'all_products' => 'Xem tất cả sản phẩm',
    'contact' => 'Chưa biết giải pháp nào phù hợp?', 'contact_desc' => 'Chia sẻ nhu cầu của bạn. Đội ngũ Phúc Thịnh Solar sẽ hỗ trợ lựa chọn giải pháp phù hợp.',
    'consult' => 'Nhận tư vấn', 'phone' => 'Gọi ngay hotline', 'empty' => 'Các giải pháp đang được cập nhật. Hãy liên hệ để được tư vấn theo nhu cầu.',
    'category_intro' => 'Khám phá các giải pháp phù hợp với nhu cầu thực tế và quy mô công trình của bạn.'
);
// Read hidden records too, so Admin hide actions override fallback content.
$solutionRows = $d->rawQuery('select id,id_list,namevi,nameen,title_indexvi,title_indexen,descvi,descen,photo,photo1,slugvi,slugen,status from #_news where type=? order by numb,id desc', array('giai-phap'));
$solutionGroupRows = $d->rawQuery('select id,namevi,nameen,descvi,descen,photo,slugvi,slugen,status from #_news_list where type=? order by numb,id desc', array('giai-phap'));
$solutionIntroRows = $d->rawQuery('select namevi,nameen,title_indexvi,title_indexen,descvi,descen,contentvi,contenten,photo,status from #_news where type=? order by numb,id desc', array('solution-intro'));
$solutionCtaRows = $d->rawQuery('select namevi,nameen,title_indexvi,title_indexen,descvi,descen,photo,status from #_news where type=? order by numb,id desc', array('solution-cta'));
$solutionProductRows = $d->rawQuery('select id,namevi,nameen,descvi,descen,photo,slugvi,slugen,status from #_product_list where type=? order by numb,id desc', array('san-pham'));
$solutionGroups = solutionHubSelect($solutionGroupRows, 'solutionhub');
$solutionVisibleRows = solutionHubSelect($solutionRows);
/* Landing đi thẳng tới từng bài Giải pháp. news_list chỉ còn phục vụ URL
   nhóm legacy, không còn là một bước trong hành trình chính. */
$solutionFeaturedRows = solutionHubSelect($solutionRows, 'solutionhub');
$solutionCards = array();
foreach($solutionFeaturedRows as $solutionRow) {
    if(solutionHubField($solutionRow, 'slug', $lang) === '') continue;
    $solutionRow['_representative'] = $solutionRow;
    $solutionCards[] = $solutionRow;
}
/* Ảnh minh hoạ an toàn cho card và hero bài chưa được biên tập ảnh trong
   Admin. Ảnh gắn theo đúng slug; upload từ Admin luôn được ưu tiên. */
$solutionFallbackImages = array(
    'dien-mat-troi-ho-gia-dinh' => 'assets/images/site-visuals/solution-home-solar-v2.jpg',
    'he-thong-luu-tru-bess' => 'assets/images/site-visuals/solution-bess-v2.jpg',
    'bom-nuoc-nang-luong-mat-troi' => 'assets/images/site-visuals/solution-solar-pump-v2.jpg'
);
$solutionEquipment = solutionHubSelect($solutionProductRows, 'solutionhub', 4);
$solutionIntroVisible = solutionHubSelect($solutionIntroRows, '', 1);
$solutionCtaVisible = solutionHubSelect($solutionCtaRows, '', 1);
$solutionIntro = $solutionIntroVisible ? $solutionIntroVisible[0] : array();
$solutionCta = $solutionCtaVisible ? $solutionCtaVisible[0] : array();
$solutionShowIntro = !$solutionIntroRows || (bool)$solutionIntroVisible;
$solutionShowCta = !$solutionCtaRows || (bool)$solutionCtaVisible;
$solutionHeroTitle = solutionHubField($solutionIntro, 'name', $lang) ?: $solutionUi['title'];
$solutionHeroEyebrow = solutionHubField($solutionIntro, 'title_index', $lang) ?: $solutionUi['eyebrow'];
$solutionHeroDesc = solutionHubField($solutionIntro, 'desc', $lang) ?: $solutionUi['intro'];
$solutionEditorialHtml = !empty($solutionIntro['content'.$lang])
    ? htmlspecialchars_decode($solutionIntro['content'.$lang], ENT_QUOTES)
    : (!empty($solutionIntro['contentvi']) ? htmlspecialchars_decode($solutionIntro['contentvi'], ENT_QUOTES) : '');
$solutionHeroImage = solutionHubImage(isset($solutionIntro['photo']) ? $solutionIntro['photo'] : '', UPLOAD_NEWS_L);
if(!$solutionIntroRows && !$solutionHeroImage) {
    foreach($solutionCards as $solutionGroup) {
        $solutionRow = $solutionGroup['_representative'];
        $solutionHeroImage = solutionHubImage($solutionRow['photo1'], UPLOAD_NEWS_L) ?: solutionHubImage($solutionRow['photo'], UPLOAD_NEWS_L);
        if($solutionHeroImage) break;
    }
}
$solutionCtaTitle = solutionHubField($solutionCta, 'name', $lang) ?: $solutionUi['contact'];
$solutionCtaDesc = solutionHubField($solutionCta, 'desc', $lang) ?: $solutionUi['contact_desc'];
$solutionCtaButton = solutionHubField($solutionCta, 'title_index', $lang) ?: $solutionUi['consult'];
$solutionCtaImage = solutionHubImage(isset($solutionCta['photo']) ? $solutionCta['photo'] : '', UPLOAD_NEWS_L);
$solutionHotline = !empty($optsetting['hotline']) ? $optsetting['hotline'] : '';
$solutionHotlineTel = preg_replace('/[^0-9+]/', '', $solutionHotline);
