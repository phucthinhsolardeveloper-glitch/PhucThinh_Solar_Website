<?php
    /* Tin tức */
    $nametype = "tin-tuc";
    $config['news'][$nametype]['title_main'] = "Tin tức";
    $config['news'][$nametype]['dropdown'] = true;
    $config['news'][$nametype]['list'] = true;
    $config['news'][$nametype]['cat'] = false;
    $config['news'][$nametype]['item'] = false;
    $config['news'][$nametype]['sub'] = false;
    $config['news'][$nametype]['tags'] = false;
    $config['news'][$nametype]['view'] = true;
    $config['news'][$nametype]['copy'] = true;
    $config['news'][$nametype]['copy_image'] = true;
    $config['news'][$nametype]['comment'] = false;
    $config['news'][$nametype]['slug'] = true;
    $config['news'][$nametype]['check'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị");
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['seo'] = true;
    $config['news'][$nametype]['width'] = 424;
    $config['news'][$nametype]['height'] = 363;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Tin tức (List) */
    $config['news'][$nametype]['title_main_list'] = "Tin tức cấp 1";
    $config['news'][$nametype]['images_list'] = true;
    $config['news'][$nametype]['show_images_list'] = true;
    $config['news'][$nametype]['slug_list'] = true;
    $config['news'][$nametype]['check_list'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['desc_list'] = false;
    $config['news'][$nametype]['desc_cke_list'] = false;
    $config['news'][$nametype]['content_list'] = false;
    $config['news'][$nametype]['content_cke_list'] = false;
    $config['news'][$nametype]['seo_list'] = true;
    $config['news'][$nametype]['width_list'] = 320;
    $config['news'][$nametype]['height_list'] = 240;
    $config['news'][$nametype]['thumb_list'] = '100x100x1';
    $config['news'][$nametype]['img_type_list'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Chính sách (hiển thị bằng các link riêng ở chân trang) */
    $nametype = "chinh-sach";
    $config['news'][$nametype]['title_main'] = "Chính sách";
    $config['news'][$nametype]['dropdown'] = false;
    $config['news'][$nametype]['list'] = false;
    $config['news'][$nametype]['cat'] = false;
    $config['news'][$nametype]['item'] = false;
    $config['news'][$nametype]['sub'] = false;
    $config['news'][$nametype]['tags'] = false;
    $config['news'][$nametype]['view'] = true;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['copy_image'] = false;
    $config['news'][$nametype]['comment'] = false;
    $config['news'][$nametype]['slug'] = true;
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    /* Anh dai dien dau trang - yeu cau 2026-09-11, hien qua news_detail_tpl.php. */
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = false;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['seo'] = true;

    /* Dự án */
    $nametype = "du-an";
    $config['news'][$nametype]['title_main'] = "Dự án";
    $config['news'][$nametype]['dropdown'] = true;
    $config['news'][$nametype]['list'] = true;
    $config['news'][$nametype]['cat'] = false;
    $config['news'][$nametype]['item'] = false;
    $config['news'][$nametype]['sub'] = false;
    $config['news'][$nametype]['tags'] = false;
    $config['news'][$nametype]['view'] = true;
    $config['news'][$nametype]['tenkhachhang'] = true;
    $config['news'][$nametype]['address'] = true;
    $config['news'][$nametype]['ngayhopdong'] = true;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['copy_image'] = true;
    $config['news'][$nametype]['comment'] = false;
    $config['news'][$nametype]['slug'] = true;
    $config['news'][$nametype]['check'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị");
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['seo'] = true;
    $config['news'][$nametype]['width'] = 397;
    $config['news'][$nametype]['height'] = 340;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['news'][$nametype]['gallery'] = array(
        $nametype => array(
                "title_main_photo" => "Hình ảnh dự án",
                "title_sub_photo" => "Hình ảnh",
                "check_photo" => array("hienthi" => "Hiển thị"),
                "number_photo" => 2,
                "images_photo" => true,
                "avatar_photo" => true,
                "name_photo" => true,
                "width_photo" => 300,
                "height_photo" => 200,
                "thumb_photo" => '100x100x1',
                "img_type_photo" => '.jpg|.gif|.png|.jpeg|.gif',
        ),
        "video" => array
        (
            "title_main_photo" => "Video Dự án",
            "title_sub_photo" => "Video",
            "check_photo" => array("hienthi" => "Hiển thị"),
            "number_photo" => 1,
            "video_photo" => true,
            "name_photo" => true,
            "images_photo" => true,
            "avatar_photo" => true,
            "thumb_photo" => '100x100x1',
            "img_type_photo" => '.jpg|.gif|.png|.jpeg|.gif',
        ),
    );

    /* Dự án (List) */
    $config['news'][$nametype]['title_main_list'] = "Dự án cấp 1";
    $config['news'][$nametype]['images_list'] = true;
    $config['news'][$nametype]['show_images_list'] = true;
    $config['news'][$nametype]['slug_list'] = true;
    $config['news'][$nametype]['check_list'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['desc_list'] = false;
    $config['news'][$nametype]['desc_cke_list'] = false;
    $config['news'][$nametype]['content_list'] = false;
    $config['news'][$nametype]['content_cke_list'] = false;
    $config['news'][$nametype]['seo_list'] = true;
    $config['news'][$nametype]['width_list'] = 320;
    $config['news'][$nametype]['height_list'] = 240;
    $config['news'][$nametype]['thumb_list'] = '100x100x1';
    $config['news'][$nametype]['img_type_list'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Hệ thống cửa hàng - gộp với "Hệ thống đại lý" cũ (2026-09-10): footer và
       trang Liên hệ giờ dùng chung 1 danh sách chi nhánh thay vì 2 bản riêng
       hay lệch nhau. */
    $nametype = "he-thong-cua-hang";
    $config['news'][$nametype]['title_main'] = "Hệ thống cửa hàng";
    $config['news'][$nametype]['phone'] = true;
    $config['news'][$nametype]['address'] = true;
    $config['news'][$nametype]['email'] = true;
    $config['news'][$nametype]['dropdown'] = false;
    $config['news'][$nametype]['map_iframe'] = true;
    $config['news'][$nametype]['ggmap'] = true;
    $config['news'][$nametype]['list'] = false;
    $config['news'][$nametype]['cat'] = false;
    $config['news'][$nametype]['item'] = false;
    $config['news'][$nametype]['sub'] = false;
    $config['news'][$nametype]['tags'] = false;
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['copy_image'] = false;
    $config['news'][$nametype]['comment'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['images'] = false;
    $config['news'][$nametype]['show_images'] = false;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 397;
    $config['news'][$nametype]['height'] = 340;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Tin tức (List) */
    // $config['news'][$nametype]['title_main_list'] = "Tin tức cấp 1";
    // $config['news'][$nametype]['images_list'] = true;
    // $config['news'][$nametype]['show_images_list'] = true;
    // $config['news'][$nametype]['slug_list'] = true;
    // $config['news'][$nametype]['check_list'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị");
    // $config['news'][$nametype]['gallery_list'] = array
    // (
    //     $nametype => array
    //     (
    //         "title_main_photo" => "Hình ảnh Tin tức cấp 1",
    //         "title_sub_photo" => "Hình ảnh",
    //         "check_photo" => array("hienthi" => "Hiển thị"),
    //         "number_photo" => 2,
    //         "images_photo" => true,
    //         "avatar_photo" => true,
    //         "name_photo" => true,
    //         "width_photo" => 320,
    //         "height_photo" => 240,
    //         "thumb_photo" => '100x100x1',
    //         "img_type_photo" => '.jpg|.gif|.png|.jpeg|.gif',
    //     ),
    //     "video" => array
    //     (
    //         "title_main_photo" => "Video Tin tức cấp 1",
    //         "title_sub_photo" => "Video",
    //         "check_photo" => array("hienthi" => "Hiển thị"),
    //         "number_photo" => 2,
    //         "video_photo" => true,
    //         "name_photo" => true
    //     )
    // );
    // $config['news'][$nametype]['desc_list'] = true;
    // $config['news'][$nametype]['desc_cke_list'] = true;
    // $config['news'][$nametype]['content_list'] = true;
    // $config['news'][$nametype]['content_cke_list'] = true;
    // $config['news'][$nametype]['seo_list'] = true;
    // $config['news'][$nametype]['width_list'] = 320;
    // $config['news'][$nametype]['height_list'] = 240;
    // $config['news'][$nametype]['thumb_list'] = '100x100x1';
    // $config['news'][$nametype]['img_type_list'] = '.jpg|.gif|.png|.jpeg|.gif';

    // /* Tin tức (Cat) */
    // $config['news'][$nametype]['title_main_cat'] = "Tin tức cấp 2";
    // $config['news'][$nametype]['images_cat'] = true;
    // $config['news'][$nametype]['show_images_cat'] = true;
    // $config['news'][$nametype]['slug_cat'] = true;
    // $config['news'][$nametype]['check_cat'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị");
    // $config['news'][$nametype]['desc_cat'] = true;
    // $config['news'][$nametype]['desc_cke_cat'] = true;
    // $config['news'][$nametype]['content_cat'] = true;
    // $config['news'][$nametype]['content_cke_cat'] = true;
    // $config['news'][$nametype]['seo_cat'] = true;
    // $config['news'][$nametype]['width_cat'] = 320;
    // $config['news'][$nametype]['height_cat'] = 240;
    // $config['news'][$nametype]['thumb_cat'] = '100x100x1';
    // $config['news'][$nametype]['img_type_cat'] = '.jpg|.gif|.png|.jpeg|.gif';

    // /* Tin tức (Item) */
    // $config['news'][$nametype]['title_main_item'] = "Tin tức cấp 3";
    // $config['news'][$nametype]['images_item'] = true;
    // $config['news'][$nametype]['show_images_item'] = true;
    // $config['news'][$nametype]['slug_item'] = true;
    // $config['news'][$nametype]['check_item'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị");
    // $config['news'][$nametype]['desc_item'] = true;
    // $config['news'][$nametype]['desc_cke_item'] = true;
    // $config['news'][$nametype]['content_item'] = true;
    // $config['news'][$nametype]['content_cke_item'] = true;
    // $config['news'][$nametype]['seo_item'] = true;
    // $config['news'][$nametype]['width_item'] = 320;
    // $config['news'][$nametype]['height_item'] = 240;
    // $config['news'][$nametype]['thumb_item'] = '100x100x1';
    // $config['news'][$nametype]['img_type_item'] = '.jpg|.gif|.png|.jpeg|.gif';

    // /* Tin tức (Sub) */
    // $config['news'][$nametype]['title_main_sub'] = "Tin tức cấp 4";
    // $config['news'][$nametype]['images_sub'] = true;
    // $config['news'][$nametype]['show_images_sub'] = true;
    // $config['news'][$nametype]['slug_sub'] = true;
    // $config['news'][$nametype]['check_sub'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị");
    // $config['news'][$nametype]['desc_sub'] = true;
    // $config['news'][$nametype]['desc_cke_sub'] = true;
    // $config['news'][$nametype]['content_sub'] = true;
    // $config['news'][$nametype]['content_cke_sub'] = true;
    // $config['news'][$nametype]['seo_sub'] = true;
    // $config['news'][$nametype]['width_sub'] = 320;
    // $config['news'][$nametype]['height_sub'] = 240;
    // $config['news'][$nametype]['thumb_sub'] = '100x100x1';
    // $config['news'][$nametype]['img_type_sub'] = '.jpg|.gif|.png|.jpeg|.gif';

    // /* Lợi ích */
    $nametype = "loi-ich";
    $config['news'][$nametype]['title_main'] = "Lợi ích";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = true;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['images1'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['width1'] = 320;
    $config['news'][$nametype]['height1'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['news'][$nametype]['img_type1'] = '.jpg|.gif|.png|.jpeg|.gif';

    // /* Cam két đại lý */
    $nametype = "cam-ket-dai-ly";
    $config['news'][$nametype]['title_main'] = "Cam két đại lý";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = true;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['images1'] = false;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['desc_cke'] = true;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['width1'] = 320;
    $config['news'][$nametype]['height1'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['news'][$nametype]['img_type1'] = '.jpg|.gif|.png|.jpeg|.gif';


    // /* Phản hồi từ đại lý */
    $nametype = "phan-hoi-dai-ly";
    $config['news'][$nametype]['title_main'] = "Phản hồi từ đại lý";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = true;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['images1'] = false;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['desc_cke'] = false;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['width1'] = 320;
    $config['news'][$nametype]['height1'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['news'][$nametype]['img_type1'] = '.jpg|.gif|.png|.jpeg|.gif';


    // /* Quy trình */
    $nametype = "quy-trinh";
    $config['news'][$nametype]['title_main'] = "Quy trình";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = true;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['images1'] = false;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['desc_cke'] = true;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['width1'] = 320;
    $config['news'][$nametype]['height1'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['news'][$nametype]['img_type1'] = '.jpg|.gif|.png|.jpeg|.gif';
    

    /* Giải pháp */
    $nametype = "giai-phap";
    $config['news'][$nametype]['title_main'] = "Giải pháp";
    $config['news'][$nametype]['dropdown'] = true;
    $config['news'][$nametype]['list'] = true;
    $config['news'][$nametype]['title_index'] = true;
    $config['news'][$nametype]['cat'] = false;
    $config['news'][$nametype]['item'] = false;
    $config['news'][$nametype]['sub'] = false;
    $config['news'][$nametype]['tags'] = false;
    $config['news'][$nametype]['view'] = true;
    $config['news'][$nametype]['copy'] = true;
    $config['news'][$nametype]['copy_image'] = true;
    $config['news'][$nametype]['comment'] = false;
    $config['news'][$nametype]['slug'] = true;
    $config['news'][$nametype]['check'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị");
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['images1'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['content1'] = true;
    $config['news'][$nametype]['content1_cke'] = true;
    $config['news'][$nametype]['seo'] = true;
    $config['news'][$nametype]['width'] = 424;
    $config['news'][$nametype]['height'] = 363;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['news'][$nametype]['width1'] = 424;
    $config['news'][$nametype]['height1'] = 363;
    $config['news'][$nametype]['thumb1'] = '100x100x1';
    $config['news'][$nametype]['img_type1'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['news'][$nametype]['gallery'] = array
    (
        "thongtinphu" => array
        (
            "title_main_photo" => "Thông tin phụ",
            "title_sub_photo" => "Thông tin phụ",
            "check_photo" => array("hienthi" => "Hiển thị"),
            "number_photo" => 1,
            "video_photo" => false,
            "images_photo" => true,
            "name_photo" => true,
            "solieu_photo" => true,
            "donvi_photo" => true,
            "desc_photo" => false,
            "content_photo" => false,
            "width_photo" => 540,
            "height_photo" => 540,
            "avatar_photo" => true,
            "thumb_photo" => '100x100x1',
            "img_type_photo" => '.jpg|.gif|.png|.jpeg|.gif'
        ),
    );

    /* giải pháp (List) */
    $config['news'][$nametype]['title_main_list'] = "Giải pháp cấp 1";
    $config['news'][$nametype]['images_list'] = true;
    $config['news'][$nametype]['show_images_list'] = true;
    $config['news'][$nametype]['slug_list'] = true;
    $config['news'][$nametype]['check_list'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['desc_list'] = false;
    $config['news'][$nametype]['desc_cke_list'] = false;
    $config['news'][$nametype]['content_list'] = false;
    $config['news'][$nametype]['content_cke_list'] = false;
    $config['news'][$nametype]['seo_list'] = true;
    $config['news'][$nametype]['width_list'] = 320;
    $config['news'][$nametype]['height_list'] = 240;
    $config['news'][$nametype]['thumb_list'] = '100x100x1';
    $config['news'][$nametype]['img_type_list'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Tiêu chí */
    $nametype = "tieu-chi";
    $config['news'][$nametype]['title_main'] = "Tiêu chí";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Cam kết */
    $nametype = "cam-ket";
    $config['news'][$nametype]['title_main'] = "Cam kết";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Thông tin liên hệ */
    $nametype = "thong-tin-lien-he";
    $config['news'][$nametype]['title_main'] = "Thông tin liên hệ";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['desc_cke'] = true;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';


    /* Lịch sử */
    $nametype = "lich-su";
    $config['news'][$nametype]['title_main'] = "Lịch sử";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Sứ mệnh - Tầm nhìn - Giá trị */
    $nametype = "su-menh";
    $config['news'][$nametype]['title_main'] = "Sứ mệnh - Tầm nhìn - Giá trị";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = false;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Lý do chọn chúng tôi */
    $nametype = "ly-do-chon-chung-toi";
    $config['news'][$nametype]['title_main'] = "Lý do chọn chúng tôi";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = false;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Số liệu thống kê */
    $nametype = "so-lieu-thong-ke";
    $config['news'][$nametype]['title_main'] = "Số liệu thống kê";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['solieu'] = true;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Số liệu thống kê */
    $nametype = "so-lieu-thong-ke-dai-ly";
    $config['news'][$nametype]['title_main'] = "Số liệu thống kê";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['solieu'] = true;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = false;
    $config['news'][$nametype]['show_images'] = false;
    $config['news'][$nametype]['desc'] = false;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Hình thức thanh toán */
    $nametype = "hinh-thuc-thanh-toan";
    $config['news'][$nametype]['title_main'] = "Hình thức thanh toán";
    $config['news'][$nametype]['dropdown'] = false;
    $config['news'][$nametype]['list'] = false;
    $config['news'][$nametype]['cat'] = false;
    $config['news'][$nametype]['item'] = false;
    $config['news'][$nametype]['sub'] = false;
    $config['news'][$nametype]['tags'] = false;
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['copy'] = true;
    $config['news'][$nametype]['copy_image'] = false;
    $config['news'][$nametype]['comment'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['images'] = false;
    $config['news'][$nametype]['show_images'] = false;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['desc_cke'] = false;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;

    /* Quản lý mục (Không cấp) */
    if(isset($config['news']))
    {
        foreach($config['news'] as $key => $value)
        {
            if(!isset($value['dropdown']) || (isset($value['dropdown']) && $value['dropdown'] == false))
            { 
                $config['shownews'] = 1;
                break;
            }
        }
    }
?>
