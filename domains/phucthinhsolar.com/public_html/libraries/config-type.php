<?php
    /* Config type - Group */
    $config['group'] = array(
        "Giới thiệu" => array(
            // "product" => array("san-pham"),
            // "tags" => array("san-pham"),
            "static" => array("su-menh","gioi-thieu"),
            "news" => array("thong-tin-lien-he","lich-su","su-menh","ly-do-chon-chung-toi"),
            "photo" => array("chungnhan")
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
        "Sản phẩm" => array(
            "product" => array("san-pham"),
            // "tags" => array("san-pham"),
            // "static" => array("su-menh","gioi-thieu","tom-tat-gioi-thieu"),
            "news" => array("tieu-chi", "cam-ket"),
            // "photo" => array("chungnhan")
        ),
        "Lợi ích" => array(
            "news" => array("loi-ich"),
            "static" => array("loi-ich"),
        ),
        "Đại lý" => array(
            "photo" => array("hinh-anh-dai-ly"),
            "static" => array("dai-ly-1","dai-ly-2","dai-ly-3"),
            "newsletter" => array("dangkynhantin2"),
            "news" => array("cam-ket-dai-ly","phan-hoi-dai-ly","so-lieu-thong-ke-dai-ly"),
        ),
        "Hệ thống đại lý" => array(
            "news" => array("he-thong-dai-ly"),
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
    $config['setting']['phone'] = true;
    $config['setting']['hotline'] = true;
    $config['setting']['zalo'] = true;
    $config['setting']['oaidzalo'] = true;
    $config['setting']['email'] = true;
    $config['setting']['website'] = true;
    $config['setting']['fanpage'] = true;
    $config['setting']['coords'] = true;
    $config['setting']['coords_iframe'] = true;

    /* Quản lý import */
    $config['import']['images'] = false;
    $config['import']['thumb'] = '100x100x1';
    $config['import']['img_type'] = ".jpg|.gif|.png|.jpeg|.gif";

    /* Quản lý export */
    $config['export']['category'] = false;

    /* Quản lý tài khoản */
    $config['user']['active'] = false;
    $config['user']['admin'] = true;
    $config['user']['check_admin'] = array("hienthi" => "Kích hoạt");
    $config['user']['member'] = true;
    $config['user']['check_member'] = array("hienthi" => "Kích hoạt");

    /* Quản lý phân quyền */
    $config['permission']['active'] = false;
    $config['permission']['check'] = array("hienthi" => "Kích hoạt");

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
