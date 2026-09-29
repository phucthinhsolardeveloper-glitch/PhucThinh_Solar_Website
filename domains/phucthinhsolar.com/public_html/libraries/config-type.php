<?php
    /* Config type - Group */
    $config['group'] = array(
        "Sản phẩm" => array(
            "product" => array("san-pham"),
            "photo" => array("catalog-campaign", "product-category-hero"),
            // "tags" => array("san-pham"),
            // "static" => array("su-menh","gioi-thieu","tom-tat-gioi-thieu"),
            "news" => array("cam-ket"),
            // "photo" => array("chungnhan")
        ),
        "Chiến dịch hiển thị" => array(
            "photo_static" => array("banner-top", "popup"),
            "shortcut" => array(
                array(
                    "title" => "Thư viện khung chiến dịch",
                    "href" => "index.php?com=product_campaign&act=man&type=san-pham",
                    "icon" => "fas fa-images",
                    "permission" => array("product_campaign", "man", "san-pham"),
                    "active" => array("com" => "product_campaign", "act" => "man", "type" => "san-pham")
                ),
                array(
                    "title" => "Sản phẩm đang có khung",
                    "href" => "index.php?com=product&act=man&type=san-pham&data_status=campaign_active",
                    "icon" => "fas fa-layer-group",
                    "permission" => array("product", "man", "san-pham"),
                    "active" => array("com" => "product", "act" => "man", "type" => "san-pham", "data_status" => "campaign_active")
                )
            )
        ),
        "Trang chủ" => array(
            /* Khối "Giới thiệu ngắn" (About teaser) - thêm 2026-09-14. Các
               khối khác của trang chủ (danh mục sản phẩm, quy trình, dự án,
               thương hiệu) đều dùng lại Admin sẵn có ở nhóm khác, không lặp
               shortcut ở đây. */
            "news" => array("home-about", "home-about-point"),
            "photo" => array("hero-carousel", "home-campaign"),
            "shortcut" => array(
                array(
                    "title" => "Xem trang chủ",
                    "href" => "../",
                    "icon" => "fas fa-external-link-alt"
                )
            )
        ),
        "Trang Giới thiệu" => array(
            /* Mọi nội dung có tác động tới GUI About được gom vào một nhóm.
               Static su-menh cũ không dùng vì chứa dữ liệu demo legacy. */
            "news" => array("lich-su","about-team","about-principle","ly-do-chon-chung-toi","su-menh"),
            "photo" => array("chungnhan","about-cta"),
            "shortcut" => array(
                array(
                    "title" => "Xem trang Giới thiệu",
                    "href" => "../gioi-thieu/",
                    "icon" => "fas fa-external-link-alt"
                )
            )
        ),
        "Trang Giải pháp" => array(
            "news" => array("solution-intro", "giai-phap", "solution-cta"),
            "shortcut" => array(
                array("title" => "Chọn danh mục tìm nhanh", "href" => "index.php?com=product&act=man_list&type=san-pham", "icon" => "fas fa-th-large", "permission" => array("product", "man_list", "san-pham")),
                array("title" => "Xem trang Giải pháp", "href" => "../giai-phap/", "icon" => "fas fa-external-link-alt")
            )
        ),
        "Trang Dự án" => array(
            "news" => array("project-intro", "du-an", "project-cta", "project-benefit", "project-trust"),
            "shortcut" => array(
                array("title" => "Quản lý nhóm dự án", "href" => "index.php?com=news&act=man_list&type=du-an", "icon" => "fas fa-filter", "permission" => array("news", "man_list", "du-an")),
                array("title" => "Xem trang Dự án", "href" => "../du-an-da-thuc-hien/", "icon" => "fas fa-external-link-alt")
            )
        ),
        "Trang Tin tức" => array(
            "news" => array("news-intro", "tin-tuc", "news-cta"),
            "shortcut" => array(
                array("title"=>"Quản lý danh mục tin tức", "href"=>"index.php?com=news&act=man_list&type=tin-tuc", "icon"=>"fas fa-filter", "permission"=>array("news","man_list","tin-tuc")),
                array("title"=>"Xem trang Tin tức", "href"=>"../tin-tuc/", "icon"=>"fas fa-external-link-alt")
            )
        ),
        "Hợp tác đại lý" => array(
            "photo" => array("dealer-hero", "hinh-anh-dai-ly"),
            "static" => array("dai-ly-1","dai-ly-2","dai-ly-3"),
            "newsletter" => array("dangkynhantin2"),
            "news" => array("cam-ket-dai-ly","phan-hoi-dai-ly","so-lieu-thong-ke-dai-ly"),
            "shortcut" => array(
                array(
                    "title" => "Xem trang Hợp tác đại lý",
                    "href" => "../dai-ly/",
                    "icon" => "fas fa-external-link-alt"
                )
            )
        ),
        "Liên hệ" => array(
            "photo" => array("contact-visual"),
            "shortcut" => array(
                array(
                    "title" => "Xem trang Liên hệ",
                    "href" => "../lien-he/",
                    "icon" => "fas fa-external-link-alt"
                )
            )
        ),
        "Hệ thống cửa hàng" => array(
            "news" => array("he-thong-cua-hang"),
            // "static" => array("cau-truc-co-cau"),
            // "photo_static" => array("watermark-news"),
            // "newsletter" => array("dangkytuyendung")
        )
    );

    /* Config type - Product */
    require_once LIBRARIES.'type/config-type-product.php';

    /* Config type - Tags */
    require_once LIBRARIES.'type/config-type-tags.php';

    /* Config type - Newsletter */
    require_once LIBRARIES.'type/config-type-newsletter.php';

    /* Config type - News */
    require_once LIBRARIES.'type/config-type-news.php';

    /* Config type - Static */
    require_once LIBRARIES.'type/config-type-static.php';

    /* Config type - Photo */
    require_once LIBRARIES.'type/config-type-photo.php';

    /* Seo page */
    $config['seopage']['page'] = array(
        "san-pham" => "Sản phẩm",
        "tin-tuc" => "Tin tức",
        "giai-phap" => "Giải pháp",
        "du-an" => "Dự án",
        "gioi-thieu" => "Giới thiệu",
        "dai-ly" => "Đại lý",
        // "video" => "Video",
        "lien-he" => "Liên hệ"
    );
    $config['seopage']['width'] = 300;
    $config['seopage']['height'] = 200;
    $config['seopage']['thumb'] = '300x200x1';
    $config['seopage']['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Setting */
    $config['setting']['address'] = true;
    $config['setting']['hotline'] = true;
    $config['setting']['zalo'] = true;
    $config['setting']['email'] = true;
    $config['setting']['website'] = true;
    $config['setting']['fanpage'] = true;
    $config['setting']['coords_iframe'] = true;

    /* Quản lý import */
    $config['import']['images'] = false;
    $config['import']['thumb'] = '100x100x1';
    $config['import']['img_type'] = ".jpg|.gif|.png|.jpeg|.gif";

    /* Quản lý export */
    $config['export']['category'] = false;

    /* Quản lý tài khoản */
    /* Cờ này chỉ điều khiển đúng một thứ: hiện khối "Quản lý user" trong menu
       Admin (templates/layout/menu.php dòng 771) — nơi có Thông tin admin, Tài
       khoản admin, Tài khoản khách và Nhóm quyền. Để false thì đặc tả
       không nhìn thấy đường vào cấp tài khoản cho nhân viên, dù các màn hình đó
       vẫn tồn tại và hoạt động bình thường. Bật lên để dùng được phân quyền.
       Riêng mục "Nhóm quyền" còn cần thêm $config['permission']['active']. */
    $config['user']['active'] = true;
    $config['user']['admin'] = true;
    $config['user']['check_admin'] = array("hienthi" => "Kích hoạt");
    $config['user']['member'] = true;
    $config['user']['check_member'] = array("hienthi" => "Kích hoạt");

    /* Quản lý phân quyền */
    /* Bật phân quyền Admin. Tài khoản role 3 vẫn đi qua mọi kiểm tra, nên chủ
       project không bao giờ bị khoá ngoài. Tài khoản role khác phải thuộc một
       nhóm quyền đang bật, nếu không sẽ không vào được màn hình nào.
       Chạy scripts/check-admin-permission-readiness.php trước khi deploy để
       biết tài khoản nào sẽ mất quyền truy cập. */
    $config['permission']['active'] = true;
    $config['permission']['check'] = array("hienthi" => "Kích hoạt");

    /* Test HTTP phân quyền cần đi qua đúng bootstrap và route thật, trong khi
       local mặc định bật debug-developer. Header này chỉ có tác dụng ở
       APP_ENV=local và chỉ chuyển sang chế độ nghiêm ngặt hơn; production bỏ
       qua hoàn toàn nên không tạo đường tắt phân quyền. */
    if(getenv('APP_ENV') === 'local'
        && isset($_SERVER['HTTP_X_PTS_PERMISSION_TEST'])
        && $_SERVER['HTTP_X_PTS_PERMISSION_TEST'] === 'enforce')
    {
        $config['website']['debug-developer'] = false;
    }

    /* Quản lý liên lệ */
    $config['contact']['check'] = array("hienthi" => "Xác nhận");

    /* Quản lý địa điểm */
    $config['places']['active'] = false;
    $config['places']['check_city'] = array("hienthi" => "Hiển thị");
    $config['places']['check_district'] = array("hienthi" => "Hiển thị");
    $config['places']['check_ward'] = array("hienthi" => "Hiển thị");
    $config['places']['placesship'] = false;

    /* Quản lý giỏ hàng */
    $config['order']['active'] = true;
    $config['order']['search'] = true;
    $config['order']['excel'] = true;
    $config['order']['word'] = true;
    $config['order']['excelall'] = true;
    $config['order']['wordall'] = true;
    $config['order']['thumb'] = '100x100x1';

    /* Quản lý thông báo đẩy */
    $config['onesignal'] = false;
?>
