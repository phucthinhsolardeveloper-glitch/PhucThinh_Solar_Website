<?php
	if(!defined('SOURCES')) die("Error");
	require_once LIBRARIES.'product_sort.php';

	$popup = $cache->get("select id, date_updated, name$lang, photo, link from #_photo where type = ? and act = ? and find_in_set('hienthi',status) limit 0,1", array('popup','photo_static'), 'fetch', 7200);
    $adv = $cache->get("select name$lang, desc$lang, photo, link from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('adv'), 'result', 7200);
    
    // pl.status thêm để tách 4 danh mục "chủ lực" (find_in_set('major',...))
    // hiện card lớn khỏi 6 danh mục còn lại (card nhỏ) ở khối "Hệ sinh thái
    // sản phẩm" trang chủ - 2026-09-12, không đổi điều kiện lọc/sắp xếp.
    $splistnb = $cache->get("select pl.name$lang, pl.slugvi, pl.slugen, pl.id, pl.photo, pl.status from #_product_list pl where pl.type = ? and find_in_set('noibat',pl.status) and find_in_set('hienthi',pl.status) and exists (select 1 from #_product p where p.id_list = pl.id and p.type = ? and find_in_set('hienthi',p.status)) order by pl.numb,pl.id desc", array('san-pham','san-pham'), 'result', 7200);

    /* Ba banner dẫn vào nhóm sản phẩm/chiến dịch ở đầu trang. Lấy cả record đã
       tắt để phân biệt "chưa từng cấu hình" với "Admin chủ động tắt": chỉ khi
       type hoàn toàn chưa có record mới dùng bộ ảnh mặc định đóng gói cùng
       website. Khi đã có record, cờ Hiển thị được tôn trọng tuyệt đối. */
    $homeCampaignRows = $cache->get("select name$lang, desc$lang, photo, link, status from #_photo where type = ? order by numb,id desc", array('home-campaign'), 'result', 7200);
    $homeCampaigns = array();
    foreach($homeCampaignRows as $campaignRow)
    {
        if(empty($campaignRow['photo']) || strpos(','.(string)$campaignRow['status'].',', ',hienthi,') === false) continue;
        $homeCampaigns[] = array(
            'name' => !empty($campaignRow['name'.$lang]) ? $campaignRow['name'.$lang] : 'Giải pháp năng lượng',
            'desc' => !empty($campaignRow['desc'.$lang]) ? trim(strip_tags(htmlspecialchars_decode($campaignRow['desc'.$lang], ENT_QUOTES))) : '',
            'photo' => $campaignRow['photo'],
            'upload' => UPLOAD_PHOTO_L,
            'link' => !empty($campaignRow['link']) ? $campaignRow['link'] : 'san-pham/'
        );
        if(count($homeCampaigns) >= 3) break;
    }
    if(!$homeCampaignRows)
    {
        $homeCampaigns = array(
            array(
                'name' => 'Chiếu sáng năng lượng mặt trời cho công trình',
                'desc' => 'Thiết bị đồng bộ cho đường nội bộ, khuôn viên và hạ tầng thương mại.',
                'photo' => 'home-street-lighting-v2.png',
                'upload' => 'assets/images/site-visuals/',
                'link' => 'san-pham/'
            ),
            array(
                'name' => 'Hệ điện mặt trời và lưu trữ',
                'desc' => 'Tấm pin, inverter và lưu trữ được lựa chọn theo đúng quy mô sử dụng.',
                'photo' => 'home-solar-storage.jpg',
                'upload' => 'assets/images/site-visuals/',
                'link' => 'giai-phap/'
            ),
            array(
                'name' => 'Chiếu sáng sân vườn và lối đi',
                'desc' => 'Tạo không gian an toàn, chỉn chu và vận hành độc lập bằng năng lượng sạch.',
                'photo' => 'home-outdoor-lighting.jpg',
                'upload' => 'assets/images/site-visuals/',
                'link' => 'san-pham/'
            )
        );
    }

    /* Bốn banner ảnh trong Hero V4. Dùng bộ ảnh sáng đã tối ưu, đóng gói theo
       từng nhóm để bộ nhận diện hình ảnh không bị ảnh cũ trong Admin phủ lại.
       Tên, mô tả và liên kết vẫn lấy từ Admin khi đủ cả bốn nhóm; thiếu bộ thì
       dùng nội dung fallback. Tuyệt đối không trộn record Admin đang bị tắt. */
    $homeCategoryHref = static function($categories, $slugKey, $needles) {
        foreach($categories as $category) {
            $candidate = isset($category[$slugKey]) ? strtolower((string)$category[$slugKey]) : '';
            foreach($needles as $needle) {
                if($candidate !== '' && strpos($candidate, $needle) !== false) return 'danh-muc-san-pham/'.$category[$slugKey].'/';
            }
        }
        return 'san-pham/';
    };
    $homeHeroDefaults = array(
        array(
            'key' => 'street-light',
            'name' => 'Đèn đường năng lượng mặt trời',
            'desc' => 'Chiếu sáng đường nội bộ, khu dân cư và công trình.',
            'photo' => 'home-hero-street-lighting-v1.webp',
            'upload' => 'assets/images/site-visuals/',
            'link' => $homeCategoryHref($splist, $sluglang, array('den-nang-luong-mat-troi', 'den-duong', 'street-light')),
            'needles' => array('đèn đường', 'den duong', 'street light')
        ),
        array(
            'key' => 'inverter',
            'name' => 'Biến tần năng lượng mặt trời',
            'desc' => 'Thiết bị chuyển đổi và quản lý nguồn điện.',
            'photo' => 'home-hero-inverter-v1.webp',
            'upload' => 'assets/images/site-visuals/',
            'link' => $homeCategoryHref($splist, $sluglang, array('bien-tan', 'inverter')),
            'needles' => array('biến tần', 'bien tan', 'inverter')
        ),
        array(
            'key' => 'panel',
            'name' => 'Tấm pin năng lượng mặt trời',
            'desc' => 'Thiết bị quang điện cho hệ thống năng lượng mặt trời.',
            'photo' => 'home-hero-solar-panels-v1.webp',
            'upload' => 'assets/images/site-visuals/',
            'link' => $homeCategoryHref($splist, $sluglang, array('tam-pin', 'solar-panel')),
            'needles' => array('tấm pin', 'tam pin', 'solar panel')
        ),
        array(
            'key' => 'storage',
            'name' => 'Pin lưu trữ',
            'desc' => 'Chủ động lưu trữ điện theo nhu cầu sử dụng.',
            'photo' => 'home-hero-battery-storage-v1.webp',
            'upload' => 'assets/images/site-visuals/',
            'link' => $homeCategoryHref($splist, $sluglang, array('pin-luu-tru', 'luu-tru', 'battery')),
            'needles' => array('pin lưu trữ', 'pin luu tru', 'battery', 'lưu trữ')
        )
    );
    $homeHeroRows = $cache->get("select name$lang, desc$lang, photo, link, status from #_photo where type = ? order by numb,id desc", array('hero-carousel'), 'result', 7200);
    $homeHeroTiles = array();
    foreach($homeHeroDefaults as $homeHeroDefault) {
        foreach($homeHeroRows as $homeHeroRow) {
            if(empty($homeHeroRow['photo']) || strpos(','.(string)$homeHeroRow['status'].',', ',hienthi,') === false) continue;
            $homeHeroRowName = mb_strtolower(trim((string)$homeHeroRow['name'.$lang]), 'UTF-8');
            $homeHeroMatches = false;
            foreach($homeHeroDefault['needles'] as $homeHeroNeedle) {
                if(strpos($homeHeroRowName, mb_strtolower($homeHeroNeedle, 'UTF-8')) !== false) { $homeHeroMatches = true; break; }
            }
            if(!$homeHeroMatches) continue;
            $homeHeroDisplayName = !empty($homeHeroRow['name'.$lang]) ? $homeHeroRow['name'.$lang] : $homeHeroDefault['name'];
            if($homeHeroDefault['key'] !== 'storage' && strpos(mb_strtolower($homeHeroDisplayName, 'UTF-8'), 'năng lượng mặt trời') === false) {
                $homeHeroDisplayName .= ' năng lượng mặt trời';
            }
            $homeHeroTiles[] = array(
                'key' => $homeHeroDefault['key'],
                'name' => $homeHeroDisplayName,
                'desc' => !empty($homeHeroRow['desc'.$lang]) ? trim(strip_tags(htmlspecialchars_decode($homeHeroRow['desc'.$lang], ENT_QUOTES))) : $homeHeroDefault['desc'],
                'photo' => $homeHeroDefault['photo'],
                'upload' => $homeHeroDefault['upload'],
                'link' => !empty($homeHeroRow['link']) ? $homeHeroRow['link'] : $homeHeroDefault['link']
            );
            break;
        }
    }
    if(count($homeHeroTiles) !== 4) {
        $homeHeroTiles = array();
        foreach($homeHeroDefaults as $homeHeroDefault) {
            unset($homeHeroDefault['needles']);
            $homeHeroTiles[] = $homeHeroDefault;
        }
    }

    /* Nền cho dải chứng nhận trang chủ. Lấy cả record đã tắt để công tắc
       Hiển thị có ý nghĩa: chỉ dùng ảnh đóng gói khi Admin chưa từng cấu hình
       type này; nếu đã cấu hình rồi tắt thì section trở về nền sáng an toàn. */
    $homeProofBackgroundRows = $cache->get("select name$lang, photo, status from #_photo where type = ? order by numb,id desc", array('home-proof-background'), 'result', 7200);
    $homeProofBackground = array();
    foreach($homeProofBackgroundRows as $homeProofBackgroundRow)
    {
        if(empty($homeProofBackgroundRow['photo']) || strpos(','.(string)$homeProofBackgroundRow['status'].',', ',hienthi,') === false) continue;
        $homeProofBackground = array(
            'photo' => $homeProofBackgroundRow['photo'],
            'upload' => UPLOAD_PHOTO_L,
            'alt' => !empty($homeProofBackgroundRow['name'.$lang]) ? $homeProofBackgroundRow['name'.$lang] : ''
        );
        break;
    }
    if(!$homeProofBackgroundRows)
    {
        $homeProofBackground = array(
            'photo' => 'home-proof-showroom-v2.jpg',
            'upload' => 'assets/images/site-visuals/',
            'alt' => ''
        );
    }

    /* Trang chủ mới ưu tiên catalog: lấy ba nhóm chủ lực theo cờ major rồi
       hiển thị tối đa 10 sản phẩm/nhóm. Mỗi truy vấn đều parameterized; thứ tự
       sản phẩm dùng đúng rule chung của toàn site. */
    $homeProductCategoryPool = array();
    foreach($splistnb as $categoryRow) if(!empty($categoryRow['status']) && strpos(','.$categoryRow['status'].',', ',major,') !== false) $homeProductCategoryPool[] = $categoryRow;
    foreach($splistnb as $categoryRow) if(empty($categoryRow['status']) || strpos(','.$categoryRow['status'].',', ',major,') === false) $homeProductCategoryPool[] = $categoryRow;
    $homeProductGroups = array();
    foreach($homeProductCategoryPool as $homeProductCategory)
    {
        if(count($homeProductGroups) >= 3) break;
        $homeGroupProducts = $cache->get("select name$lang, slugvi, slugen, id, id_list, id_cat, id_brand, photo, photo_khuyenmai, regular_price, sale_price, discount, type, status, cong_suat, waterproof_rating, lighting_time from #_product where type = ? and id_list = ? and photo <> '' and find_in_set('hienthi',status)".publicProductDefaultOrder()." limit 0,10", array('san-pham', (int)$homeProductCategory['id']), 'result', 7200);
        if(!$homeGroupProducts) continue;
        $homeProductGroups[] = array('category' => $homeProductCategory, 'products' => $homeGroupProducts);
    }

    /* Giới thiệu ngắn (About teaser) trang chủ - thêm Admin 2026-09-14 (trước
       đó hardcode). Ảnh/nhãn/tiêu đề/mô tả/chữ nút lấy record home-about hiển
       thị đầu tiên; bốn ô lợi ích lấy tối đa 4 record home-about-point.
       Không có record nào (hoặc tắt hết) thì dùng nội dung mặc định đã duyệt. */
    $homeAboutRows = $cache->get("select name$lang, title_index$lang, desc$lang, content1$lang, photo, status from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,1", array('home-about'), 'result', 7200);
    $homeAboutPointRows = $cache->get("select name$lang, desc$lang, photo, status from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('home-about-point'), 'result', 7200);
    $homeAbout = !empty($homeAboutRows) ? $homeAboutRows[0] : array();
    $homeAboutEyebrow = !empty($homeAbout['title_index'.$lang]) ? $homeAbout['title_index'.$lang] : 'VỀ PHÚC THỊNH SOLAR';
    $homeAboutTitle = !empty($homeAbout['name'.$lang]) ? $homeAbout['name'.$lang] : 'Cung cấp thiết bị và thi công năng lượng mặt trời';
    $homeAboutDesc = !empty($homeAbout['desc'.$lang]) ? $homeAbout['desc'.$lang] : 'Phúc Thịnh Solar cung cấp đèn năng lượng mặt trời, tấm pin, biến tần, pin lưu trữ và thiết bị bơm nước. Chúng tôi tư vấn lựa chọn thiết bị, thiết kế và thi công hệ thống phù hợp với nhu cầu sử dụng và ngân sách của khách hàng.';
    $homeAboutButton = !empty($homeAbout['content1'.$lang]) ? $homeAbout['content1'.$lang] : 'Tìm hiểu về Phúc Thịnh Solar';
    $homeAboutImage = !empty($homeAbout['photo']) ? $homeAbout['photo'] : '';
    $homeAboutImageUpload = UPLOAD_NEWS_L;
    if($homeAboutImage === '') { $homeAboutImage = '1-27050.png'; $homeAboutImageUpload = UPLOAD_PHOTO_L; }
    $homeAboutDefaultIcons = array('fa-certificate', 'fa-tools', 'fa-shield-alt', 'fa-warehouse');
    $homeAboutDefaultPoints = array(
        array('label' => 'Sản phẩm chính hãng', 'desc' => 'Thông tin thương hiệu, thông số kỹ thuật và chính sách bảo hành rõ ràng.'),
        array('label' => 'Tư vấn và thi công', 'desc' => 'Đề xuất thiết bị và phương án lắp đặt phù hợp với điều kiện thực tế của từng công trình.'),
        array('label' => 'Hỗ trợ sau bàn giao', 'desc' => 'Hướng dẫn sử dụng, tiếp nhận yêu cầu bảo hành và hỗ trợ kỹ thuật trong quá trình vận hành.'),
        array('label' => 'Hệ thống kho ba khu vực', 'desc' => 'Kho tại TP.HCM, Đà Nẵng và Gia Lai, hỗ trợ giao hàng toàn quốc.')
    );
    $homeAboutPoints = array();
    if(!empty($homeAboutPointRows)) {
        foreach($homeAboutPointRows as $pointIndex => $pointRow) {
            $homeAboutPoints[] = array(
                'label' => !empty($pointRow['name'.$lang]) ? $pointRow['name'.$lang] : '',
                'desc' => !empty($pointRow['desc'.$lang]) ? $pointRow['desc'.$lang] : (isset($homeAboutDefaultPoints[$pointIndex]) ? $homeAboutDefaultPoints[$pointIndex]['desc'] : ''),
                'icon' => isset($homeAboutDefaultIcons[$pointIndex]) ? $homeAboutDefaultIcons[$pointIndex] : 'fa-check',
                'photo' => !empty($pointRow['photo']) ? $pointRow['photo'] : ''
            );
        }
    } else {
        foreach($homeAboutDefaultPoints as $pointIndex => $point) $homeAboutPoints[] = array('label' => $point['label'], 'desc' => $point['desc'], 'icon' => $homeAboutDefaultIcons[$pointIndex], 'photo' => '');
    }
    $newsnb = $cache->get("select name$lang, slugvi, slugen, desc$lang, date_created, id, photo from #_news where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc limit 0,6", array('tin-tuc'), 'result', 7200);
    $solieu = $cache->get("select name$lang, solieu, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('so-lieu-thong-ke'), 'result', 7200);
    // loi ich
    $loiich = $cache->get("select name$lang, desc$lang, id, photo, photo1 from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('loi-ich'), 'result', 7200);
    $loiich_title = $cache->get("select name$lang, desc$lang,photo from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('loi-ich'), 'fetch', 7200);    
    
    // quy trinh
    $quytrinh = $cache->get("select name$lang, solieu, id, photo, desc$lang from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,5", array('quy-trinh'), 'result', 7200);
    /* Không tải lại danh sách "Hoạt động dự án" ở cuối trang chủ: dữ liệu type
       du-an đã được lấy theo nhóm trong $featuredProjects và hiển thị ở khối
       "Dự án đã thực hiện" bên dưới. Trang danh sách dự án vẫn giữ nguyên. */
    $testimonial = $cache->get("select name$lang, desc$lang, content$lang, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('phan-hoi-dai-ly'), 'result', 7200);
    // $videonb = $cache->get("select id from #_photo where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status)", array('video'), 'result', 7200);
    $partner = $cache->get("select name$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('doitac'), 'result', 7200);
    /* Chứng nhận uỷ quyền cho khối tin cậy ở hero; dùng chung nguồn với trang Về chúng tôi nên Admin chỉ cần quản lý một chỗ. */
    $chungnhan = $cache->get("select name$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('chungnhan'), 'result', 7200);
    /* Ảnh nền Hero (chế độ 'text') - tuỳ chọn, quản lý qua Admin > Ảnh nền Hero trang chủ.
       Nhiều ảnh thì slide.php tự crossfade; không có ảnh nào thì dùng nền gradient mặc định. */
    $heroBgList = $cache->get("select photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('home-hero-bg'), 'result', 7200);
    $tieuchi = $cache->get("select name$lang, slugvi, slugen, desc$lang, date_created, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('tieu-chi'), 'result', 7200);
    $solutionTabs = array();
    $solutionRows = $cache->get("select name$lang, title_index$lang, slugvi, slugen, desc$lang, date_created, id, id_list, photo1 from #_news where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap'), 'result', 7200);
    foreach($solutionRows as $solutionRow) $solutionTabs[(int)$solutionRow['id_list']][] = $solutionRow;

    $solutionGallery = array();
    $solutionGalleryRows = $cache->get("select g.photo, g.id, g.id_parent, g.solieu$lang, g.donvi$lang, g.name$lang from #_gallery g inner join #_news n on n.id = g.id_parent where g.com = ? and g.type = ? and g.kind = ? and g.val = ? and find_in_set('hienthi',g.status) and n.type = ? and find_in_set('noibat',n.status) and find_in_set('hienthi',n.status) order by g.numb,g.id desc", array('news', 'giai-phap', 'man', 'thongtinphu', 'giai-phap'), 'result', 7200);
    foreach($solutionGalleryRows as $solutionGalleryRow)
    {
        $solutionId = (int)$solutionGalleryRow['id_parent'];
        if(!isset($solutionGallery[$solutionId]) || count($solutionGallery[$solutionId]) < 4) $solutionGallery[$solutionId][] = $solutionGalleryRow;
    }

    $featuredProjects = array();
    $featuredProjectRows = $cache->get("select name$lang, address, ngayhopdong, tenkhachhang, slugvi, slugen, desc$lang, date_created, id, id_list, photo from #_news where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('du-an'), 'result', 7200);
    foreach($featuredProjectRows as $featuredProjectRow) $featuredProjects[(int)$featuredProjectRow['id_list']][] = $featuredProjectRow;
    /* SEO */
    $seoDB = $seo->getOnDB(0,'setting','update','setting');
    if(!empty($seoDB['title'.$seolang])) $seo->set('h1',$seoDB['title'.$seolang]);
    if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
    if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
    if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
    $seo->set('url',$func->getPageURL());
    $imgJson = (!empty($logo['options'])) ? json_decode($logo['options'],true) : null;
    if(empty($imgJson) || ($imgJson['p'] != $logo['photo']))
    {
        $imgJson = $func->getImgSize($logo['photo'],UPLOAD_PHOTO_L.$logo['photo']);
        $seo->updateSeoDB(json_encode($imgJson),'photo',$logo['id']);
    }
    if(!empty($imgJson))
    {
        $seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_PHOTO_L.$logo['photo']);
        $seo->set('photo:width',$imgJson['w']);
        $seo->set('photo:height',$imgJson['h']);
        $seo->set('photo:type',$imgJson['m']);
    }
?>
