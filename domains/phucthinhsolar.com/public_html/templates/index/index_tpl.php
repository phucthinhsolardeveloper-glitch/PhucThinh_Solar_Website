<?php
$homeEscape = static function($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$homePlainText = static function($value) {
    return trim(strip_tags(htmlspecialchars_decode((string)$value, ENT_QUOTES)));
};

$homeMajorCategories = array();
$homeOtherCategories = array();
foreach($splistnb as $homeCategory) {
    if(!empty($homeCategory['status']) && strpos(','.$homeCategory['status'].',', ',major,') !== false) $homeMajorCategories[] = $homeCategory;
    else $homeOtherCategories[] = $homeCategory;
}
$homeCategories = array_merge($homeMajorCategories, $homeOtherCategories);
$homeCategorySummary = static function($category, $slugKey) {
    $slug = isset($category[$slugKey]) ? strtolower((string)$category[$slugKey]) : '';
    if(strpos($slug, 'den') !== false) return 'Đèn đường, đèn pha và đèn sân vườn cho nhiều không gian sử dụng.';
    if(strpos($slug, 'tam-pin') !== false || strpos($slug, 'solar-panel') !== false) return 'Thiết bị tạo điện từ ánh nắng, sử dụng trong hệ thống điện mặt trời.';
    if(strpos($slug, 'bien-tan') !== false || strpos($slug, 'inverter') !== false) return 'Thiết bị chuyển đổi và quản lý nguồn điện trong hệ thống điện mặt trời.';
    if(strpos($slug, 'luu-tru') !== false || strpos($slug, 'battery') !== false) return 'Lưu trữ điện để chủ động sử dụng theo nhu cầu và cấu hình hệ thống.';
    if(strpos($slug, 'bom') !== false || strpos($slug, 'pump') !== false) return 'Thiết bị bơm nước phục vụ tưới tiêu và cấp nước bằng năng lượng mặt trời.';
    return 'Khám phá thiết bị và lựa chọn cấu hình phù hợp với nhu cầu sử dụng.';
};
$homeCategorySlots = array(
    array('key' => 'lighting', 'needles' => array('den-nlmt'), 'name' => 'Đèn năng lượng mặt trời', 'desc' => 'Đèn đường, đèn pha và đèn sân vườn cho nhiều không gian sử dụng.', 'action' => 'Xem các dòng đèn', 'photo' => 'home-street-lighting-v2.png', 'upload' => 'assets/images/site-visuals/', 'link' => 'san-pham/'),
    array('key' => 'panel', 'needles' => array('tam-pin'), 'name' => 'Tấm pin điện mặt trời', 'desc' => 'Thiết bị tạo điện từ ánh nắng, sử dụng trong hệ thống điện mặt trời.', 'action' => 'Xem tấm pin', 'photo' => 'category-panels-user-2026.jpg', 'upload' => 'assets/images/site-visuals/', 'link' => 'san-pham/'),
    array('key' => 'inverter', 'needles' => array('bien-tan-inverter', 'inverter'), 'name' => 'Biến tần (Inverter)', 'desc' => 'Thiết bị chuyển đổi và quản lý nguồn điện trong hệ thống điện mặt trời.', 'action' => 'Xem biến tần', 'photo' => 'category-inverter-user-2026.jpg', 'upload' => 'assets/images/site-visuals/', 'link' => 'san-pham/'),
    array('key' => 'storage', 'needles' => array('pin-luu-tru', 'luu-tru'), 'name' => 'Pin lưu trữ', 'desc' => 'Lưu trữ điện để sử dụng theo nhu cầu và cấu hình hệ thống.', 'action' => 'Xem pin lưu trữ', 'photo' => 'category-storage-user-2026.jpg', 'upload' => 'assets/images/site-visuals/', 'link' => 'san-pham/'),
    array('key' => 'pump', 'needles' => array('bien-tan-bom', 'bom-nang-luong', 'bom'), 'name' => 'Bơm nước năng lượng mặt trời', 'desc' => 'Thiết bị bơm nước phục vụ tưới tiêu và cấp nước bằng năng lượng mặt trời.', 'action' => 'Xem bơm nước', 'photo' => 'category-pump-user-2026.jpg', 'upload' => 'assets/images/site-visuals/', 'link' => 'giai-phap/')
);
$homeShowcaseCategories = array();
foreach($homeCategorySlots as $homeCategorySlotIndex => $homeCategorySlot) {
    $homeCategoryMatch = array();
    foreach($homeCategories as $homeCategoryCandidate) {
        $homeCategorySlug = isset($homeCategoryCandidate[$sluglang]) ? strtolower((string)$homeCategoryCandidate[$sluglang]) : '';
        foreach($homeCategorySlot['needles'] as $homeCategoryNeedle) {
            if($homeCategorySlug !== '' && strpos($homeCategorySlug, $homeCategoryNeedle) !== false) { $homeCategoryMatch = $homeCategoryCandidate; break 2; }
        }
    }
    if($homeCategorySlotIndex === 0 && !empty($homeCampaigns[0]['photo'])) {
        $homeCampaignPhoto = (string)$homeCampaigns[0]['photo'];
        $homeCampaignPhotoName = strtolower(basename(str_replace('\\', '/', $homeCampaignPhoto)));
        if(in_array($homeCampaignPhotoName, array('home-street-lighting.jpg', 'home-street-lighting.jpg.webp'), true)) {
            $homeCategorySlot['photo'] = 'home-street-lighting-v2.png';
            $homeCategorySlot['upload'] = 'assets/images/site-visuals/';
        } else {
            $homeCategorySlot['photo'] = $homeCampaignPhoto;
            $homeCategorySlot['upload'] = $homeCampaigns[0]['upload'];
        }
    }
    if(!empty($homeCategoryMatch['name'.$lang])) $homeCategorySlot['name'] = $homePlainText($homeCategoryMatch['name'.$lang]);
    if(!empty($homeCategoryMatch[$sluglang])) $homeCategorySlot['link'] = 'danh-muc-san-pham/'.$homeCategoryMatch[$sluglang].'/';
    $homeShowcaseCategories[] = $homeCategorySlot;
}

$homeSolutionFallbacks = array(
    array('name' => 'Điện mặt trời cho doanh nghiệp', 'label' => 'Doanh nghiệp và nhà xưởng', 'desc' => 'Đề xuất phương án theo nhu cầu vận hành, đặc điểm mái và điều kiện lắp đặt tại cơ sở.', 'chips' => array('Điện mặt trời mái nhà', 'Biến tần (Inverter)', 'Tấm pin điện mặt trời'), 'photo' => 'solution-bess-v2.jpg', 'link' => 'giai-phap/dien-mat-troi-doanh-nghiep/'),
    array('name' => 'Điện mặt trời cho gia đình', 'label' => 'Gia đình', 'desc' => 'Tư vấn hệ thống theo nhu cầu dùng điện, diện tích mái và nhu cầu lưu trữ của gia đình.', 'chips' => array('Điện mặt trời mái nhà', 'Biến tần (Inverter)', 'Pin lưu trữ'), 'photo' => 'solution-home-solar-v2.jpg', 'link' => 'giai-phap/dien-mat-troi-ho-gia-dinh/'),
    array('name' => 'Chiếu sáng đường và công trình', 'label' => 'Chiếu sáng công trình', 'desc' => 'Tư vấn lựa chọn thiết bị và phương án bố trí đèn theo đặc điểm từng khu vực.', 'chips' => array('Đèn năng lượng mặt trời', 'Trụ đèn và phụ kiện'), 'photo' => 'home-solution-construction-v1.png', 'link' => 'giai-phap/'),
    array('name' => 'Bơm nước năng lượng mặt trời', 'label' => 'Nông nghiệp', 'desc' => 'Tư vấn hệ thống theo nguồn nước, nhu cầu tưới tiêu và điều kiện sử dụng.', 'chips' => array('Bơm nước năng lượng mặt trời', 'Tấm pin điện mặt trời'), 'photo' => 'solution-solar-pump-v2.jpg', 'link' => 'giai-phap/')
);
$homeSolutionNeedles = array(
    array('doanh nghiệp', 'doanh nghiep', 'nhà xưởng', 'nha xuong'),
    array('gia đình', 'gia dinh', 'hộ gia đình', 'ho gia dinh'),
    array('chiếu sáng', 'chieu sang', 'đèn', 'den'),
    array('nông nghiệp', 'nong nghiep', 'bơm', 'bom')
);
$homeSolutions = array();
foreach($homeSolutionFallbacks as $homeSolutionIndex => $homeSolutionFallback) {
    $homeMatchedSolution = array();
    foreach($solutionRows as $homeSolutionRow) {
        $homeSolutionSearch = mb_strtolower($homePlainText((isset($homeSolutionRow['name'.$lang]) ? $homeSolutionRow['name'.$lang] : '').' '.(isset($homeSolutionRow['title_index'.$lang]) ? $homeSolutionRow['title_index'.$lang] : '')), 'UTF-8');
        foreach($homeSolutionNeedles[$homeSolutionIndex] as $homeSolutionNeedle) {
            if(strpos($homeSolutionSearch, mb_strtolower($homeSolutionNeedle, 'UTF-8')) !== false) { $homeMatchedSolution = $homeSolutionRow; break 2; }
        }
    }
    /* Hình minh họa tab công trình là asset art-directed riêng của trang chủ;
       nội dung/slug vẫn lấy từ Admin, chỉ cố định ảnh để đúng câu chuyện showroom. */
    $homeSolutionPhoto = $homeSolutionIndex === 2 ? 'home-solution-construction-v1.png' : (!empty($homeMatchedSolution['photo1']) ? $homeMatchedSolution['photo1'] : $homeSolutionFallback['photo']);
    $homeSolutionUpload = $homeSolutionIndex === 2 ? 'assets/images/site-visuals/' : (!empty($homeMatchedSolution['photo1']) ? UPLOAD_NEWS_L : 'assets/images/site-visuals/');
    $homeSolutions[] = array(
        'name' => !empty($homeMatchedSolution['name'.$lang]) ? $homePlainText($homeMatchedSolution['name'.$lang]) : $homeSolutionFallback['name'],
        'label' => $homeSolutionFallback['label'],
        'desc' => !empty($homeMatchedSolution['desc'.$lang]) ? $homePlainText($homeMatchedSolution['desc'.$lang]) : $homeSolutionFallback['desc'],
        'chips' => $homeSolutionFallback['chips'],
        'photo' => $homeSolutionPhoto,
        'upload' => $homeSolutionUpload,
        'link' => !empty($homeMatchedSolution[$sluglang]) ? 'giai-phap/'.$homeMatchedSolution[$sluglang].'/' : $homeSolutionFallback['link']
    );
}
$homeSolutionChipHref = static function($chip, $solution, $categories) {
    $label = mb_strtolower(trim((string)$chip), 'UTF-8');
    if(strpos($label, 'mái nhà') !== false || strpos($label, 'mai nha') !== false) return $solution['link'];

    $categoryKey = '';
    if(strpos($label, 'biến tần') !== false || strpos($label, 'bien tan') !== false || strpos($label, 'inverter') !== false) $categoryKey = 'inverter';
    elseif(strpos($label, 'pin lưu trữ') !== false || strpos($label, 'pin luu tru') !== false || strpos($label, 'lưu trữ') !== false || strpos($label, 'luu tru') !== false) $categoryKey = 'storage';
    elseif(strpos($label, 'tấm pin') !== false || strpos($label, 'tam pin') !== false) $categoryKey = 'panel';
    elseif(strpos($label, 'bơm') !== false || strpos($label, 'bom') !== false) $categoryKey = 'pump';
    elseif(strpos($label, 'đèn') !== false || strpos($label, 'den') !== false || strpos($label, 'trụ') !== false || strpos($label, 'tru') !== false) $categoryKey = 'lighting';

    foreach($categories as $category) {
        if($categoryKey !== '' && $category['key'] === $categoryKey) return $category['link'];
    }
    return $solution['link'];
};

$homeProcessDefaults = array(
    array('number' => '01', 'name' => 'Tư vấn & khảo sát', 'desc' => 'Lắng nghe nhu cầu, khảo sát hiện trạng và điều kiện lắp đặt để đề xuất giải pháp phù hợp với mục tiêu sử dụng và ngân sách của khách hàng.'),
    array('number' => '02', 'name' => 'Thiết kế & báo giá', 'desc' => 'Xây dựng phương án kỹ thuật, lựa chọn thiết bị phù hợp và báo giá rõ ràng. Thống nhất phạm vi công việc, tiến độ và chính sách bảo hành trước khi triển khai.'),
    array('number' => '03', 'name' => 'Thi công & lắp đặt', 'desc' => 'Triển khai theo phương án đã thống nhất, chú trọng an toàn, chất lượng và tính thẩm mỹ. Kiểm tra thiết bị, kết nối và các hạng mục trong quá trình thi công.'),
    array('number' => '04', 'name' => 'Nghiệm thu & bàn giao', 'desc' => 'Kiểm tra, chạy thử và nghiệm thu cùng khách hàng. Bàn giao hồ sơ, hướng dẫn sử dụng, theo dõi hoạt động và những lưu ý để vận hành an toàn.'),
    array('number' => '05', 'name' => 'Bảo hành & đồng hành', 'desc' => 'Tiếp nhận yêu cầu hỗ trợ, thực hiện bảo hành theo chính sách và cung cấp dịch vụ kiểm tra, vệ sinh, bảo trì theo nhu cầu. Hỗ trợ theo dõi từ xa với hệ thống có tính năng phù hợp.')
);
$homeProcessSteps = array();
foreach($homeProcessDefaults as $homeProcessIndex => $homeProcessDefault) {
    $homeProcessRow = isset($quytrinh[$homeProcessIndex]) ? $quytrinh[$homeProcessIndex] : array();
    $homeProcessSteps[] = array(
        'number' => !empty($homeProcessRow['solieu']) ? $homeProcessRow['solieu'] : $homeProcessDefault['number'],
        'name' => !empty($homeProcessRow['name'.$lang]) ? $homePlainText($homeProcessRow['name'.$lang]) : $homeProcessDefault['name'],
        'desc' => !empty($homeProcessRow['desc'.$lang]) ? $homePlainText($homeProcessRow['desc'.$lang]) : $homeProcessDefault['desc']
    );
}

$homeDealerTitle = 'TRỞ THÀNH ĐẠI LÝ PHÚC THỊNH SOLAR';
$homeDealerDesc = 'Cùng phát triển kinh doanh thiết bị năng lượng mặt trời với nguồn hàng đa dạng, chính sách hợp tác rõ ràng và sự đồng hành của đội ngũ Phúc Thịnh Solar.';
$homeAboutTitle = $homePlainText($homeAboutTitle);
$homeAboutText = $homePlainText($homeAboutDesc);
?>

<div class="pts-home-v4">
    <section class="pts-home-hero" aria-labelledby="pts-home-title">
        <div class="pts-home-hero-slider" data-home-hero-slider aria-roledescription="carousel" aria-label="Banner nhóm thiết bị nổi bật">
            <div class="pts-home-hero-track">
                <?php foreach($homeHeroTiles as $homeHeroIndex => $homeHeroTile) { ?>
                <article class="pts-home-hero-slide<?=$homeHeroIndex === 0 ? ' is-active' : ''?>" data-home-hero-slide="<?=$homeHeroIndex?>" data-home-hero-slide-key="<?=$homeEscape($homeHeroTile['key'])?>" aria-label="<?=$homeHeroIndex + 1?> trên <?=count($homeHeroTiles)?>: <?=$homeEscape($homeHeroTile['name'])?>"<?=$homeHeroIndex === 0 ? '' : ' aria-hidden="true"'?>>
                    <a href="<?=$homeEscape($homeHeroTile['link'])?>">
                    <span class="pts-home-hero-banner-media">
                        <?=$func->getImage(['isLazy' => false, 'noNativeLazy' => $homeHeroIndex === 0, 'class' => '', 'sizes' => '1600x720x1', 'upload' => $homeHeroTile['upload'], 'image' => $homeHeroTile['photo'], 'alt' => $homeHeroTile['name']])?>
                    </span>
                    <span class="pts-home-hero-banner-shade" aria-hidden="true"></span>
                    <span class="pts-home-hero-banner-copy">
                        <strong><?=$homeEscape($homeHeroTile['name'])?></strong>
                        <small><?=$homeEscape($homeHeroTile['desc'])?></small>
                    </span>
                    </a>
                </article>
                <?php } ?>
            </div>
            <?php if(count($homeHeroTiles) > 1) { ?>
            <div class="pts-home-hero-controls">
                <button type="button" data-home-hero-prev aria-label="Banner trước"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                <span><b data-home-hero-current>01</b> / <?=str_pad(count($homeHeroTiles), 2, '0', STR_PAD_LEFT)?></span>
                <button type="button" data-home-hero-next aria-label="Banner tiếp theo"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                <button type="button" data-home-hero-pause aria-pressed="false" aria-label="Dừng chuyển banner" title="Dừng chuyển banner"><i class="fas fa-pause" aria-hidden="true"></i></button>
            </div>
            <?php } ?>
        </div>

        <div class="pts-home-container pts-home-hero-grid">
            <div class="pts-home-hero-copy">
                <p class="pts-home-kicker">Phân phối thiết bị · Tư vấn · Thi công lắp đặt</p>
                <h1 id="pts-home-title"><span class="pts-home-title-primary">Thiết bị và giải pháp</span> <span class="pts-home-title-secondary">năng lượng mặt trời</span></h1>
                <p class="pts-home-lead">Phúc Thịnh Solar cung cấp thiết bị chiếu sáng, điện mặt trời và lưu trữ năng lượng; tư&nbsp;vấn phương án phù hợp cho gia đình, doanh nghiệp và công trình.</p>
                <div class="pts-home-actions">
                    <a class="pts-home-btn pts-home-btn-primary" href="#danh-muc-thiet-bi">Xem sản phẩm</a>
                    <a class="pts-home-btn pts-home-btn-outline" href="#yeu-cau-tu-van">Tư vấn lắp đặt</a>
                </div>
            </div>
        </div>
    </section>

    <?php if($homeShowcaseCategories) { ?>
    <section class="pts-home-section pts-home-catalog" id="danh-muc-thiet-bi" aria-labelledby="pts-home-catalog-title">
        <div class="pts-home-container">
            <div class="pts-home-section-head">
                <h2 id="pts-home-catalog-title">Sản phẩm năng lượng mặt trời</h2>
                <p>Khám phá các nhóm thiết bị cho nhu cầu chiếu sáng, phát điện, lưu trữ và bơm nước.</p>
            </div>

            <?php $homeFeaturedCategory = $homeShowcaseCategories[0]; ?>
            <a class="pts-home-category pts-home-category-featured is-featured" data-home-category-key="<?=$homeEscape($homeFeaturedCategory['key'])?>" href="<?=$homeEscape($homeFeaturedCategory['link'])?>">
                <span class="pts-home-category-bg"><?=$func->getImage(['class' => 'lazy', 'sizes' => '1440x420x1', 'upload' => $homeFeaturedCategory['upload'], 'image' => $homeFeaturedCategory['photo'], 'alt' => $homeFeaturedCategory['name']])?></span>
                <span class="pts-home-category-shade" aria-hidden="true"></span>
                <span class="pts-home-category-copy">
                    <i class="fas fa-sun" aria-hidden="true"></i>
                    <span>
                        <strong><?=$homeEscape($homeFeaturedCategory['name'])?></strong>
                        <small><?=$homeEscape($homeFeaturedCategory['desc'])?></small>
                    </span>
                </span>
                <span class="pts-home-category-action"><?=$homeEscape($homeFeaturedCategory['action'])?> <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            </a>

            <div class="pts-home-category-rail" tabindex="0" aria-label="Bốn nhóm thiết bị nổi bật, có thể cuộn ngang">
                <?php foreach(array_slice($homeShowcaseCategories, 1) as $homeCategory) { ?>
                <a class="pts-home-category pts-home-category--<?=$homeEscape($homeCategory['key'])?>" data-home-category-key="<?=$homeEscape($homeCategory['key'])?>" href="<?=$homeEscape($homeCategory['link'])?>">
                    <span class="pts-home-category-bg"><?=$func->getImage(['class' => 'lazy', 'sizes' => '640x500x1', 'upload' => $homeCategory['upload'], 'image' => $homeCategory['photo'], 'alt' => $homeCategory['name']])?></span>
                    <span class="pts-home-category-shade" aria-hidden="true"></span>
                    <span class="pts-home-category-copy">
                        <strong><?=$homeEscape($homeCategory['name'])?></strong>
                        <small><?=$homeEscape($homeCategory['desc'])?></small>
                        <span><?=$homeEscape($homeCategory['action'])?> <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                    </span>
                </a>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <?php if($homeCampaigns) { ?>
    <section class="pts-home-section pts-home-lighting" aria-labelledby="pts-home-lighting-title">
        <div class="pts-home-container">
            <div class="pts-home-section-head pts-home-section-head-row">
                <div>
                    <h2 id="pts-home-lighting-title">Chiếu sáng phù hợp từng không gian</h2>
                    <p>Lựa chọn thiết bị theo khu vực sử dụng và điều kiện lắp đặt thực tế.</p>
                </div>
                <a class="pts-home-btn pts-home-btn-primary" href="#yeu-cau-tu-van" data-home-quote="Đèn năng lượng mặt trời">Tư vấn chọn đèn</a>
            </div>
            <div class="pts-home-use-grid">
                <?php foreach(array_slice($homeCampaigns, 0, 3) as $homeCampaign) { ?>
                <article class="pts-home-use">
                    <a class="pts-home-use-media" href="<?=$homeEscape($homeCampaign['link'])?>">
                        <?=$func->getImage(['class' => 'lazy', 'sizes' => '760x500x1', 'upload' => $homeCampaign['upload'], 'image' => $homeCampaign['photo'], 'alt' => $homeCampaign['name']])?>
                    </a>
                    <div class="pts-home-use-copy">
                        <h3><a href="<?=$homeEscape($homeCampaign['link'])?>"><?=$homeEscape($homeCampaign['name'])?></a></h3>
                        <?php if($homeCampaign['desc'] !== '') { ?><p><?=$homeEscape($homeCampaign['desc'])?></p><?php } ?>
                        <a class="pts-home-text-link" href="<?=$homeEscape($homeCampaign['link'])?>">Xem chi tiết <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </article>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <?php if($homeSolutions) { ?>
    <section class="pts-home-section pts-home-solutions" id="giai-phap" aria-labelledby="pts-home-solutions-title">
        <div class="pts-home-container">
            <div class="pts-home-section-head">
                <h2 id="pts-home-solutions-title">Giải pháp cho từng nhu cầu</h2>
                <p>Chọn lĩnh vực sử dụng để tìm hiểu phương án phù hợp.</p>
            </div>
            <div class="pts-home-tabs" role="tablist" aria-label="Giải pháp theo nhu cầu">
                <?php foreach($homeSolutions as $homeSolutionIndex => $homeSolution) { ?>
                <button class="pts-home-tab" type="button" role="tab" id="pts-home-tab-<?=$homeSolutionIndex?>" aria-selected="<?=$homeSolutionIndex === 0 ? 'true' : 'false'?>" aria-controls="pts-home-panel-<?=$homeSolutionIndex?>" tabindex="<?=$homeSolutionIndex === 0 ? '0' : '-1'?>"><?=$homeEscape($homeSolution['label'])?></button>
                <?php } ?>
            </div>
            <?php foreach($homeSolutions as $homeSolutionIndex => $homeSolution) { ?>
            <div class="pts-home-tab-panel" id="pts-home-panel-<?=$homeSolutionIndex?>" role="tabpanel" aria-labelledby="pts-home-tab-<?=$homeSolutionIndex?>"<?=$homeSolutionIndex === 0 ? '' : ' hidden'?>>
                <div class="pts-home-solution-copy">
                    <h3><?=$homeEscape($homeSolution['name'])?></h3>
                    <p><?=$homeEscape($homeSolution['desc'])?></p>
                    <div class="pts-home-solution-chips" aria-label="Thiết bị liên quan">
                        <?php foreach($homeSolution['chips'] as $homeSolutionChip) { ?><a href="<?=$homeEscape($homeSolutionChipHref($homeSolutionChip, $homeSolution, $homeShowcaseCategories))?>"><?=$homeEscape($homeSolutionChip)?></a><?php } ?>
                    </div>
                    <div class="pts-home-actions">
                        <a class="pts-home-btn pts-home-btn-primary" href="<?=$homeEscape($homeSolution['link'])?>">Xem giải pháp</a>
                        <a class="pts-home-text-link" href="#yeu-cau-tu-van" data-home-quote="<?=$homeEscape($homeSolution['name'])?>">Nhận tư vấn <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </div>
                <div class="pts-home-solution-visual" role="img" aria-label="<?=$homeEscape($homeSolution['name'])?>">
                    <?=$func->getImage(['isLazy' => true, 'sizes' => '1200x750x1', 'upload' => $homeSolution['upload'], 'image' => $homeSolution['photo'], 'alt' => ''])?>
                    <span><?=$homeEscape($homeSolution['label'])?></span>
                </div>
            </div>
            <?php } ?>
        </div>
    </section>
    <?php } ?>

    <?php if($homeProcessSteps) { ?>
    <section class="pts-home-section pts-home-services" aria-labelledby="pts-home-services-title">
        <div class="pts-home-container">
            <div class="pts-home-section-head">
                <h2 id="pts-home-services-title">Đồng hành từ tư vấn đến vận hành</h2>
                <p>Phúc Thịnh Solar tư vấn giải pháp phù hợp, thi công chuyên nghiệp và tận tâm hỗ trợ sau bàn giao, giúp khách hàng an tâm sử dụng lâu dài.</p>
            </div>
            <ol class="pts-home-steps">
                <?php foreach($homeProcessSteps as $homeProcessStep) { ?>
                <li>
                    <span><?=$homeEscape($homeProcessStep['number'])?></span>
                    <h3><?=$homeEscape($homeProcessStep['name'])?></h3>
                    <?php if($homeProcessStep['desc'] !== '') { ?><p><?=$homeEscape($homeProcessStep['desc'])?></p><?php } ?>
                </li>
                <?php } ?>
            </ol>
        </div>
    </section>
    <?php } ?>

    <section class="pts-home-dealer" aria-labelledby="pts-home-dealer-title">
        <div class="pts-home-container pts-home-dealer-grid">
            <div>
                    <h2 id="pts-home-dealer-title">ĐĂNG KÝ ĐẠI LÝ</h2>
                <p><?=$homeEscape($homeDealerDesc)?></p>
                <ul>
                    <li><i class="fas fa-check" aria-hidden="true"></i> Danh mục sản phẩm phù hợp nhu cầu thị trường.</li>
                    <li><i class="fas fa-check" aria-hidden="true"></i> Hệ thống kho tại TP.HCM, Đà Nẵng và Gia Lai.</li>
                    <li><i class="fas fa-check" aria-hidden="true"></i> Hỗ trợ tư vấn sản phẩm, kỹ thuật và bảo hành theo chính sách.</li>
                </ul>
            </div>
            <div class="pts-home-dealer-actions">
                <a class="pts-home-btn pts-home-btn-primary" href="#yeu-cau-tu-van" data-home-quote="Hợp tác đại lý">Đăng ký làm đại lý</a>
                <a class="pts-home-btn pts-home-btn-light" href="#yeu-cau-tu-van" data-home-quote="Hợp tác đại lý">Tư vấn chính sách đại lý</a>
            </div>
        </div>
    </section>

    <?php if(!$homeAboutConfigured || !empty($homeAboutRows)) { ?>
    <section class="pts-home-section pts-home-about" aria-labelledby="pts-home-about-title">
        <div class="pts-home-container pts-home-about-grid">
            <div>
                <h2 id="pts-home-about-title"><?=$homeEscape($homeAboutTitle)?></h2>
                <p><?=$homeEscape($homeAboutText)?></p>
                <a class="pts-home-text-link" href="gioi-thieu/"><?=$homeEscape($homeAboutButton)?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <ul class="pts-home-about-points">
                <?php foreach($homeAboutPoints as $homeAboutPoint) { if($homeAboutPoint['label'] === '') continue; ?>
                <li>
                    <?php if(!empty($homeAboutPoint['photo'])) { ?>
                    <?=$func->getImage(['class' => 'pts-home-about-point-icon', 'sizes' => '48x48x1', 'upload' => UPLOAD_NEWS_L, 'image' => $homeAboutPoint['photo'], 'alt' => ''])?>
                    <?php } else { ?><i class="fas <?=$homeEscape($homeAboutPoint['icon'])?>" aria-hidden="true"></i><?php } ?>
                    <span>
                        <strong><?=$homeEscape($homeAboutPoint['label'])?></strong>
                        <?php if(!empty($homeAboutPoint['desc'])) { ?><small><?=$homeEscape($homeAboutPoint['desc'])?></small><?php } ?>
                    </span>
                </li>
                <?php } ?>
            </ul>
        </div>
    </section>
    <?php } ?>

    <section class="pts-home-section pts-home-contact" id="yeu-cau-tu-van" aria-labelledby="pts-home-contact-title">
        <div class="pts-home-container">
            <div class="pts-home-section-head">
                <h2 id="pts-home-contact-title">Bạn đang cần giải pháp nào?</h2>
                <p>Để lại thông tin và nhu cầu, Phúc Thịnh Solar sẽ liên hệ trao đổi với bạn.</p>
            </div>
            <div class="pts-home-contact-panel">
                <div class="pts-home-contact-info">
                    <h3>Thông tin liên hệ</h3>
                    <p>Đội ngũ Phúc Thịnh Solar phản hồi trong giờ làm việc.</p>
                    <?php if(!empty($optsetting['hotline'])) { ?><a href="tel:<?=$homeEscape($func->parsePhone($optsetting['hotline']))?>"><i class="fas fa-phone-alt" aria-hidden="true"></i><span><small>Hotline</small><strong><?=$homeEscape($optsetting['hotline'])?></strong></span></a><?php } ?>
                    <?php if(!empty($optsetting['email'])) { ?><a href="mailto:<?=$homeEscape($optsetting['email'])?>"><i class="far fa-envelope" aria-hidden="true"></i><span><small>Email</small><strong><?=$homeEscape($optsetting['email'])?></strong></span></a><?php } ?>
                    <?php if(!empty($optsetting['address'])) { ?><div><i class="fas fa-map-marker-alt" aria-hidden="true"></i><span><small>Địa chỉ</small><strong><?=$homeEscape($optsetting['address'])?></strong></span></div><?php } ?>
                    <?php if(!empty($optsetting['thoigianlamviec'])) { ?><div><i class="far fa-clock" aria-hidden="true"></i><span><small>Giờ làm việc</small><strong><?=$homeEscape($optsetting['thoigianlamviec'])?></strong></span></div><?php } ?>
                </div>
                <form class="pts-home-contact-form" action="#yeu-cau-tu-van" method="post">
                    <input type="hidden" name="form_csrf" value="<?=$homeEscape(formCsrfToken())?>">
                    <div class="pts-home-field-row">
                        <label for="ptsHomeFullname">Họ và tên <span>*</span><input type="text" id="ptsHomeFullname" name="dataContact[fullname]" maxlength="100" autocomplete="name" required></label>
                        <label for="ptsHomePhone">Số điện thoại <span>*</span><input type="tel" id="ptsHomePhone" name="dataContact[phone]" maxlength="20" autocomplete="tel" inputmode="tel" required></label>
                    </div>
                    <div class="pts-home-field-row">
                        <label for="ptsHomeAddress">Tỉnh/Thành phố<input type="text" id="ptsHomeAddress" name="dataContact[address]" maxlength="255" autocomplete="address-level1" placeholder="Ví dụ: Gia Lai"></label>
                        <label for="ptsHomeInterest">Nhu cầu tư vấn<select name="dataContact[subject]" id="ptsHomeInterest">
                            <option value="">-- Chọn nhu cầu --</option>
                            <option value="Đèn năng lượng mặt trời">Đèn năng lượng mặt trời</option>
                            <option value="Điện mặt trời gia đình">Điện mặt trời gia đình</option>
                            <option value="Điện mặt trời doanh nghiệp">Điện mặt trời doanh nghiệp</option>
                            <option value="Tấm pin và biến tần">Tấm pin và biến tần</option>
                            <option value="Pin lưu trữ">Pin lưu trữ</option>
                            <option value="Hợp tác đại lý">Hợp tác đại lý</option>
                            <option value="Khác">Khác</option>
                        </select></label>
                    </div>
                    <label for="ptsHomeMessage">Nội dung cần trao đổi <span>*</span><textarea name="dataContact[content]" id="ptsHomeMessage" maxlength="5000" rows="4" required placeholder="Mô tả ngắn nhu cầu của bạn"></textarea></label>
                    <input type="hidden" name="recaptcha_response_contact" id="recaptchaResponseHomeV4">
                    <button class="pts-home-btn pts-home-btn-primary" type="submit" name="submit-contact" value="Gửi yêu cầu tư vấn">Gửi yêu cầu tư vấn</button>
                    <p class="pts-home-form-status" id="ptsHomeFormStatus" role="status" aria-live="polite" aria-atomic="true" hidden></p>
                    <p class="pts-home-form-note"><i class="fas fa-lock" aria-hidden="true"></i> Thông tin chỉ được sử dụng để liên hệ tư vấn.</p>
                </form>
            </div>
        </div>
    </section>
</div>
<script>
(function(){
    var form = document.querySelector('.pts-home-contact-form');
    var status = document.getElementById('ptsHomeFormStatus');
    var invalidReported = false;
    if(!form || !status) return;
    form.addEventListener('invalid', function(event){
        var field = event.target;
        if(!field || field.type === 'hidden') return;
        field.setAttribute('aria-invalid', 'true');
        if(invalidReported) return;
        invalidReported = true;
        var label = field.labels && field.labels[0] ? field.labels[0].textContent.replace('*', '').trim() : 'thông tin này';
        status.textContent = field.validity.valueMissing ? 'Vui lòng điền ' + label + '.' : 'Vui lòng kiểm tra ' + label + '.';
        status.hidden = false;
        window.setTimeout(function(){
            invalidReported = false;
            field.focus();
        }, 0);
    }, true);
    form.addEventListener('input', function(event){
        if(event.target && event.target.validity.valid) event.target.removeAttribute('aria-invalid');
    });
    form.addEventListener('submit', function(){
        status.hidden = true;
        status.textContent = '';
    });
}
());
</script>
