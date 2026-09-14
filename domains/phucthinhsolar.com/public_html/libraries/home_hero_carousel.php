<?php
/* Dữ liệu Hero carousel trang chủ (layout 3) - Admin hoá 2026-09-14.
   Đọc table_photo type='hero-carousel' (man_photo, đúng 4 slide, xem
   libraries/type/config-type-photo.php). Field chuẩn (name/desc/link/photo)
   dùng cột có sẵn; eyebrow/dòng tiêu đề 2/CTA phụ/tag/theme lưu trong cột
   options JSON (merge qua administrator/sources/photo.php savePhoto(), theo
   đúng cách savePhotoStatic() đã dùng cho watermark - không ALTER TABLE).

   Chưa đủ 4 slide đang Hiển thị (chưa tạo, hoặc admin ẩn bớt) thì dùng
   nguyên mảng tĩnh HERO_CAROUSEL_FALLBACK bên dưới - giữ đúng nội dung đã
   duyệt ban đầu (2026-09-12), không để Hero trống. slide.php/CSS/JS không
   cần biết nguồn dữ liệu là DB hay fallback, shape mảng luôn giống nhau. */

function heroCarouselFallbackSlides()
{
    return array(
        array(
            'id' => 'ecosystem',
            'theme' => 'dark',
            'eyebrow' => 'PHÚC THỊNH SOLAR',
            'titleLine1' => 'Thiết bị đồng bộ.',
            'titleLine2' => 'Giải pháp phù hợp.',
            'description' => 'Tấm pin, inverter, pin lưu trữ và đèn năng lượng mặt trời. Một hệ sinh thái cho những nhu cầu khác nhau.',
            'primaryCTA' => array('label' => 'Khám phá sản phẩm', 'href' => 'san-pham/'),
            'secondaryCTA' => array('label' => 'Nhận tư vấn', 'href' => '#ptsContactModal', 'modal' => 'ptsContactModal'),
            'tags' => array('GIA ĐÌNH', 'DOANH NGHIỆP', 'DỰ ÁN'),
            'image' => '1-27050.png',
            'alt' => 'Hệ thống tấm pin năng lượng mặt trời lắp trên mái nhà, sản phẩm Phúc Thịnh Solar',
        ),
        array(
            'id' => 'lighting',
            'theme' => 'light',
            'eyebrow' => 'CHIẾU SÁNG NĂNG LƯỢNG MẶT TRỜI',
            'titleLine1' => 'Ánh sáng cho',
            'titleLine2' => 'từng công trình.',
            'description' => 'Lựa chọn đèn theo mặt bằng, thời gian sử dụng và điều kiện lắp đặt của đường phố, khu dân cư và dự án.',
            'primaryCTA' => array('label' => 'Khám phá giải pháp', 'href' => 'giai-phap/'),
            'secondaryCTA' => array('label' => 'Tư vấn dự án', 'href' => '#ptsContactModal', 'modal' => 'ptsContactModal'),
            'tags' => array('ĐƯỜNG PHỐ', 'KHU DÂN CƯ', 'CÔNG TRÌNH'),
            'image' => 'hero-den-duong-nlmt-thu-nghiem-63880.png',
            'alt' => 'Hàng đèn đường năng lượng mặt trời thực tế của Phúc Thịnh Solar dọc tuyến đường',
        ),
        array(
            'id' => 'storage',
            'theme' => 'dark',
            'eyebrow' => 'ĐIỆN MẶT TRỜI & LƯU TRỮ',
            'titleLine1' => 'Chủ động năng lượng.',
            'titleLine2' => 'Vững bước dài lâu.',
            'description' => 'Tư vấn cấu hình theo phụ tải, diện tích mái và nhu cầu sử dụng điện của gia đình, doanh nghiệp và nhà xưởng.',
            'primaryCTA' => array('label' => 'Xem giải pháp', 'href' => 'giai-phap/'),
            'secondaryCTA' => array('label' => 'Yêu cầu khảo sát', 'href' => '#ptsContactModal', 'modal' => 'ptsContactModal'),
            'tags' => array('ĐIỆN MẶT TRỜI', 'LƯU TRỮ', 'VẬN HÀNH'),
            'image' => '3-11502.png',
            'alt' => 'Hệ thống điện mặt trời áp mái công trình thực tế của Phúc Thịnh Solar',
        ),
        array(
            'id' => 'partner',
            'theme' => 'dark',
            'eyebrow' => 'ĐẠI LÝ · NHÀ PHÂN PHỐI · ĐỐI TÁC DỰ ÁN',
            'titleLine1' => 'Cùng phát triển.',
            'titleLine2' => 'Cùng đi xa hơn.',
            'description' => 'Kết nối hợp tác phân phối thiết bị và triển khai giải pháp năng lượng mặt trời trên toàn quốc.',
            'primaryCTA' => array('label' => 'Đăng ký nhận chính sách', 'href' => '#ptsDealerModal', 'modal' => 'ptsDealerModal'),
            'secondaryCTA' => array('label' => 'Tìm hiểu hợp tác', 'href' => 'dai-ly'),
            'tags' => array('KẾT NỐI', 'ĐỒNG HÀNH', 'PHÁT TRIỂN'),
            'image' => '2-97031.png',
            'alt' => 'Công trình điện mặt trời quy mô lớn Phúc Thịnh Solar đã triển khai',
        ),
    );
}

function getHeroCarouselSlides()
{
    global $d, $cache, $lang;
    if(empty($d) || empty($cache)) return heroCarouselFallbackSlides();

    $rows = $cache->get("select id, name$lang, desc$lang, link, photo, options from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('hero-carousel'), 'result', 7200);
    if(empty($rows) || count($rows) < 1) return heroCarouselFallbackSlides();

    $slides = array();
    foreach($rows as $row)
    {
        $titleLine1 = trim((string)$row['name'.$lang]);
        $imagePhoto = trim((string)$row['photo']);
        /* Slide thiếu tiêu đề hoặc ảnh thì bỏ qua (không render slide rỗng) -
           không tự bịa nội dung thay Admin. */
        if($titleLine1 === '' || $imagePhoto === '') continue;

        $options = (!empty($row['options'])) ? json_decode($row['options'], true) : array();
        if(!is_array($options)) $options = array();

        $primaryHref = trim((string)$row['link']);
        $primaryModal = in_array($primaryHref, array('#ptsContactModal', '#ptsDealerModal'), true) ? ltrim($primaryHref, '#') : '';
        $primaryCTA = array('label' => !empty($options['ctaLabel']) ? $options['ctaLabel'] : 'Xem thêm', 'href' => $primaryHref !== '' ? $primaryHref : 'san-pham/');
        if($primaryModal !== '') $primaryCTA['modal'] = $primaryModal;

        $secondaryCTA = null;
        if(!empty($options['cta2Label']))
        {
            $secondaryHref = !empty($options['cta2Href']) ? trim((string)$options['cta2Href']) : '#ptsContactModal';
            $secondaryModal = in_array($secondaryHref, array('#ptsContactModal', '#ptsDealerModal'), true) ? ltrim($secondaryHref, '#') : '';
            $secondaryCTA = array('label' => $options['cta2Label'], 'href' => $secondaryHref);
            if($secondaryModal !== '') $secondaryCTA['modal'] = $secondaryModal;
        }

        $tags = array();
        if(!empty($options['tags']))
        {
            foreach(explode(',', (string)$options['tags']) as $tag) { $tag = trim($tag); if($tag !== '') $tags[] = $tag; }
        }

        $slides[] = array(
            'id' => 'hero-'.$row['id'],
            'theme' => (!empty($options['theme']) && $options['theme'] === 'light') ? 'light' : 'dark',
            'eyebrow' => !empty($options['eyebrow']) ? $options['eyebrow'] : '',
            'titleLine1' => $titleLine1,
            'titleLine2' => !empty($options['titleLine2']) ? $options['titleLine2'] : '',
            'description' => trim((string)$row['desc'.$lang]),
            'primaryCTA' => $primaryCTA,
            'secondaryCTA' => $secondaryCTA,
            'tags' => $tags,
            'image' => $imagePhoto,
            'alt' => $titleLine1,
        );
    }

    return $slides ?: heroCarouselFallbackSlides();
}
