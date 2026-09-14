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
    $config['news'][$nametype]['check'] = array("noibat" => "Nổi bật trang chủ", "newshub" => "Nổi bật trang Tin tức", "hienthi" => "Hiển thị");
    $config['news'][$nametype]['admin_note'] = 'Trang Tin tức lấy tối đa 3 bài Nổi bật trang Tin tức theo STT rồi ngày mới nhất. Chưa chọn thì dùng bài mới nhất. Cờ trang chủ độc lập. Mô tả dùng trên card; thời gian đọc tự tính từ Nội dung. Ảnh ngang 16:9 phù hợp nhất.';
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
    $config['news'][$nametype]['desc_list'] = true;
    $config['news'][$nametype]['desc_cke_list'] = false;
    $config['news'][$nametype]['content_list'] = false;
    $config['news'][$nametype]['content_cke_list'] = false;
    $config['news'][$nametype]['seo_list'] = true;
    $config['news'][$nametype]['width_list'] = 320;
    $config['news'][$nametype]['height_list'] = 240;
    $config['news'][$nametype]['thumb_list'] = '100x100x1';
    $config['news'][$nametype]['img_type_list'] = '.jpg|.gif|.png|.jpeg|.gif';

    foreach(array('news-intro' => 'Tin tức - Mở đầu', 'news-cta' => 'Tin tức - CTA') as $nametype => $newsBlockTitle) {
        $config['news'][$nametype] = array(
            'title_main' => $newsBlockTitle,
            'admin_note' => $nametype === 'news-intro'
                ? 'Tên: tiêu đề trang; mô tả: lời giới thiệu; nhãn nhỏ: phía trên tiêu đề. Chưa có record dùng mặc định; tắt tất cả record ẩn mở đầu. Cờ Hiện bài nổi bật bật/tắt cụm 3 bài khi có record mở đầu.'
                : 'CTA cuối danh sách và chi tiết Tin tức. Sửa tên, nhãn nhỏ, mô tả, chữ nút và ảnh nền. Nút mở popup tư vấn dùng chung; hotline lấy từ Cấu hình website. Tắt tất cả record để ẩn CTA.',
            'check' => $nametype === 'news-intro' ? array('hienthi'=>'Hiển thị', 'hero'=>'Hiện bài nổi bật') : array('hienthi'=>'Hiển thị'),
            'view'=>false, 'slug'=>false, 'copy'=>false, 'seo'=>false,
            'images'=>$nametype === 'news-cta', 'show_images'=>$nametype === 'news-cta',
            'title_index'=>true, 'title_index_required'=>false, 'title_index_label'=>'Nhãn nhỏ',
            'desc'=>true, 'desc_cke'=>false, 'content'=>false,
            'content1'=>$nametype === 'news-cta', 'content1_cke'=>false, 'content1_label'=>'Chữ trên nút tư vấn',
            'width'=>1600, 'height'=>400, 'thumb'=>'200x50x1', 'img_type'=>'.jpg|.jpeg|.png|.webp'
        );
    }

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
    /* Anh dai dien - chi dung lam og:image khi share MXH va cho card/thumbnail,
       KHONG hien to dau trang chi tiet (news_detail_tpl.php tu kiem tra
       type=='chinh-sach' de an rieng, khong doc show_images vi bien nay chi
       ton tai ben administrator/, frontend khong nap file config nay).
       width/height/img_type khop voi size render thuc te '1000x560x1'. */
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = false;
    $config['news'][$nametype]['width'] = 1000;
    $config['news'][$nametype]['height'] = 560;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
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
    $config['news'][$nametype]['desc_list'] = true;
    $config['news'][$nametype]['desc_cke_list'] = false;
    $config['news'][$nametype]['content_list'] = false;
    $config['news'][$nametype]['content_cke_list'] = false;
    $config['news'][$nametype]['seo_list'] = true;
    $config['news'][$nametype]['width_list'] = 320;
    $config['news'][$nametype]['height_list'] = 240;
    $config['news'][$nametype]['thumb_list'] = '100x100x1';
    $config['news'][$nametype]['img_type_list'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Phần mở đầu và CTA của trang danh sách dự án. Bài dự án và nhóm dự án
       vẫn dùng type du-an hiện hữu để giữ URL, SEO, gallery và liên kết sản phẩm. */
    foreach(array('project-intro' => 'Dự án - Mở đầu', 'project-cta' => 'Dự án - CTA') as $nametype => $projectBlockTitle) {
        $config['news'][$nametype] = array(
            'title_main' => $projectBlockTitle,
            'admin_note' => $nametype === 'project-intro'
                ? 'Mở đầu trang Dự án: tên là tiêu đề lớn, tiêu đề phụ là nhãn nhỏ, mô tả là đoạn giới thiệu. Lấy record Hiển thị đầu tiên theo STT. Nếu có record nhưng tắt tất cả, phần giới thiệu được ẩn.'
                : 'CTA cuối trang Dự án: tên là tiêu đề, tiêu đề phụ là nhãn nhỏ, mô tả là lời mời tư vấn, Nội dung kết luận là chữ trên nút. Ảnh nền tùy chọn. Nút mở popup tư vấn hiện hữu; hotline lấy từ Cấu hình website.',
            'check' => array('hienthi' => 'Hiển thị'),
            'view' => false, 'slug' => false, 'copy' => false,
            'images' => $nametype === 'project-cta', 'show_images' => $nametype === 'project-cta',
            'title_index' => true, 'title_index_required' => false,
            'title_index_label' => 'Nhãn nhỏ phía trên tiêu đề',
            'desc' => true, 'desc_cke' => false,
            'content' => false, 'seo' => false,
            'content1' => $nametype === 'project-cta', 'content1_cke' => false,
            'content1_label' => 'Chữ trên nút tư vấn',
            'width' => 1600, 'height' => 500, 'thumb' => '150x70x1',
            'img_type' => '.jpg|.jpeg|.png|.webp'
        );
    }

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
    $config['news'][$nametype]['admin_note'] = "Trang /giai-phap/ điều hướng theo Giải pháp cấp 1 (ví dụ Doanh nghiệp, Hộ gia đình). Cờ Đại diện trên Trang Giải pháp chọn bài cung cấp ảnh/nội dung đại diện cho nhóm; nếu chưa chọn, bài Hiển thị đầu tiên trong nhóm được dùng. Tiêu đề ngắn, Ảnh cấp 1 và Thông tin phụ vẫn dùng ở trang chủ. Không đổi slug của nội dung đã xuất bản.";
    $config['news'][$nametype]['title_index_label'] = "Tiêu đề ngắn trên trang chủ";
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
    $config['news'][$nametype]['check'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị", "solutionhub" => "Đại diện trên Trang Giải pháp");
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
    $config['news'][$nametype]['check_list'] = array("hienthi" => "Hiển thị", "solutionhub" => "Trang Giải pháp");
    $config['news'][$nametype]['desc_list'] = true;
    $config['news'][$nametype]['desc_cke_list'] = false;
    $config['news'][$nametype]['content_list'] = false;
    $config['news'][$nametype]['content_cke_list'] = false;
    $config['news'][$nametype]['seo_list'] = true;
    $config['news'][$nametype]['width_list'] = 320;
    $config['news'][$nametype]['height_list'] = 240;
    $config['news'][$nametype]['thumb_list'] = '100x100x1';
    $config['news'][$nametype]['img_type_list'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Khối mở đầu/CTA của hub: dùng news để có thể lưu chỉ chữ, không bắt
       buộc ảnh. Cùng session, phân quyền, CSRF và upload validator hiện hữu. */
    foreach(array('solution-intro' => 'Giải pháp - Mở đầu', 'solution-cta' => 'Giải pháp - CTA') as $nametype => $solutionBlockTitle) {
        $config['news'][$nametype] = array(
            'title_main' => $solutionBlockTitle,
            'admin_note' => $nametype === 'solution-intro'
                ? 'Mở đầu /giai-phap/: tên là tiêu đề lớn, tiêu đề phụ là nhãn nhỏ, mô tả là đoạn giới thiệu, ảnh là ảnh công trình. Lấy record Hiển thị đầu tiên theo STT. Nếu có record nhưng tắt tất cả, phần mở đầu được ẩn (vẫn giữ H1 cho trang).'
                : 'CTA cuối /giai-phap/: tên là tiêu đề, mô tả là lời mời tư vấn, tiêu đề phụ là chữ trên nút. Ảnh nền tùy chọn. Nút mở popup tư vấn hiện hữu; hotline lấy từ Cấu hình website. Tắt tất cả record để ẩn CTA.',
            'check' => array('hienthi' => 'Hiển thị'),
            'view' => false, 'slug' => false, 'copy' => false,
            'images' => true, 'show_images' => true,
            'title_index' => true, 'title_index_required' => false,
            'title_index_label' => $nametype === 'solution-intro' ? 'Nhãn nhỏ phía trên tiêu đề' : 'Chữ trên nút tư vấn',
            'desc' => true, 'desc_cke' => false, 'content' => false, 'seo' => false,
            'width' => 1200, 'height' => 800, 'thumb' => '150x100x1',
            'img_type' => '.jpg|.jpeg|.png|.webp'
        );
    }

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

    /* Thông tin liên hệ — mồ côi: sources/about.php từng query nhưng
       templates/about/about_tpl.php không dùng biến này. 3 dòng dữ liệu cũ
       (Phone/Email/Địa chỉ) chưa từng được nhập nội dung thật, đã xoá
       (audit 2026-09-11). */
    // $nametype = "thong-tin-lien-he";
    // $config['news'][$nametype]['title_main'] = "Thông tin liên hệ";
    // $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['news'][$nametype]['view'] = false;
    // $config['news'][$nametype]['slug'] = false;
    // $config['news'][$nametype]['copy'] = false;
    // $config['news'][$nametype]['images'] = true;
    // $config['news'][$nametype]['show_images'] = true;
    // $config['news'][$nametype]['desc'] = true;
    // $config['news'][$nametype]['content'] = false;
    // $config['news'][$nametype]['desc_cke'] = true;
    // $config['news'][$nametype]['seo'] = false;
    // $config['news'][$nametype]['width'] = 320;
    // $config['news'][$nametype]['height'] = 240;
    // $config['news'][$nametype]['thumb'] = '100x100x1';
    // $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';


    /* Nội dung và ảnh riêng cho section Con người trên trang About. Dùng bảng
       news hiện hữu; frontend vẫn có fallback legacy khi chưa tạo record. */
    $nametype = "about-team";
    $config['news'][$nametype]['title_main'] = "About - Con người";
    $config['news'][$nametype]['admin_note'] = "Điều khiển riêng section Con người tại /ve-chung-toi/: nhãn nhỏ, tiêu đề, mô tả, nội dung và ảnh. Frontend lấy record đang hiển thị có thứ tự nhỏ nhất.";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['title_index'] = true;
    $config['news'][$nametype]['title_index_label'] = "Nhãn nhỏ phía trên tiêu đề";
    $config['news'][$nametype]['title_index_required'] = false;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['desc_cke'] = false;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 1200;
    $config['news'][$nametype]['height'] = 900;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Ba điểm nhấn dưới section Con người của trang About. Tách thành record
       riêng để cả icon, tiêu đề, mô tả và thứ tự đều biên tập được. */
    $nametype = "about-principle";
    $config['news'][$nametype]['title_main'] = "About - Điểm nhấn con người";
    $config['news'][$nametype]['admin_note'] = "Tối đa 3 record đang Hiển thị tạo dải Chuyên môn / Đồng hành / Khách hàng ngay dưới section Con người tại /ve-chung-toi/. Ảnh tải lên được dùng như icon; nên dùng PNG nền trong, cùng một phong cách.";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['title_index'] = true;
    $config['news'][$nametype]['title_index_label'] = "Tiêu đề ngắn trên giao diện";
    $config['news'][$nametype]['title_index_required'] = false;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['desc_cke'] = false;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content_cke'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 160;
    $config['news'][$nametype]['height'] = 160;
    $config['news'][$nametype]['thumb'] = '100x100x2';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Lịch sử */
    $nametype = "lich-su";
    $config['news'][$nametype]['title_main'] = "Lịch sử";
    $config['news'][$nametype]['admin_note'] = "Điều khiển phần Câu chuyện và timeline tại /ve-chung-toi/. Record giới thiệu có thể để trống Năm cột mốc; mỗi record có năm hợp lệ tạo một mốc trên timeline.";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['title_index'] = true;
    $config['news'][$nametype]['title_index_label'] = "Tiêu đề ngắn hiển thị trên About";
    $config['news'][$nametype]['title_index_required'] = false;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['desc_cke'] = false;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['solieu'] = true;
    $config['news'][$nametype]['solieu_label'] = "Năm cột mốc";
    $config['news'][$nametype]['solieu_placeholder'] = "Ví dụ: 2019";
    $config['news'][$nametype]['solieu_required'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 320;
    $config['news'][$nametype]['height'] = 240;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Sứ mệnh - Tầm nhìn - Giá trị */
    $nametype = "su-menh";
    $config['news'][$nametype]['title_main'] = "Sứ mệnh - Tầm nhìn - Giá trị";
    $config['news'][$nametype]['admin_note'] = "Ba record đầu tạo khối Tầm nhìn - Sứ mệnh - Giá trị cốt lõi tại /ve-chung-toi/. Tiêu đề ngắn, mô tả, ảnh/icon, thứ tự và Hiển thị được áp dụng trực tiếp.";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['title_index'] = true;
    $config['news'][$nametype]['title_index_label'] = "Tiêu đề ngắn trên card About";
    $config['news'][$nametype]['title_index_required'] = false;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['desc_cke'] = false;
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
    $config['news'][$nametype]['admin_note'] = "Tên/tiêu đề ngắn, mô tả và ảnh của record tạo card Lý do lựa chọn tại /ve-chung-toi/. Chọn tối đa 4 mục bằng cờ Trang Về chúng tôi; nếu chưa chọn mục nào, frontend dùng bốn mục phù hợp hiện hữu làm fallback.";
    $config['news'][$nametype]['check'] = array("about" => "Trang Về chúng tôi", "hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = false;
    $config['news'][$nametype]['title_index'] = true;
    $config['news'][$nametype]['title_index_label'] = "Tiêu đề ngắn trên card About";
    $config['news'][$nametype]['title_index_required'] = false;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['desc_cke'] = false;
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
