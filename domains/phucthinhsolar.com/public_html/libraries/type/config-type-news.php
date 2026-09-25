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
    $config['news'][$nametype]['width'] = 1600;
    $config['news'][$nametype]['height'] = 900;
    $config['news'][$nametype]['thumb'] = '240x135x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.jpeg|.png|.webp';

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
    /* Giữ màn nhóm legacy trong Admin để URL/quyền cũ còn quản trị được; public
       menu và card không dùng các nhóm này làm bước điều hướng. */
    $config['news'][$nametype]['dropdown'] = true;
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
    // "projecthub": chọn dự án nào lên khối "Dự án nổi bật" lớn ở đầu trang
    // Dự án - độc lập với "Nổi bật" (trang chủ), vì cờ đó đang bật đều cho
    // mọi dự án nên không phân biệt được. Không chọn dòng nào thì trang tự
    // lấy dự án đầu tiên làm nổi bật (2026-09-14).
    $config['news'][$nametype]['check'] = array("noibat" => "Nổi bật", "projecthub" => "Nổi bật trang Dự án", "hienthi" => "Hiển thị");
    $config['news'][$nametype]['admin_note'] = 'Cờ "Nổi bật trang Dự án" chọn dự án hiện ở khối lớn đầu trang Dự án (ảnh + mô tả + lợi ích); không chọn dòng nào thì tự lấy dự án mới nhất. Mô tả ngắn dùng trên card lưới và khối nổi bật; để trống thì card không hiện dòng mô tả.';
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['seo'] = true;
    $config['news'][$nametype]['width'] = 1600;
    $config['news'][$nametype]['height'] = 1200;
    $config['news'][$nametype]['thumb'] = '200x150x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.jpeg|.png|.webp';
    $config['news'][$nametype]['gallery'] = array(
        $nametype => array(
                "title_main_photo" => "Hình ảnh dự án",
                "title_sub_photo" => "Hình ảnh",
                "check_photo" => array("hienthi" => "Hiển thị"),
                "number_photo" => 2,
                "images_photo" => true,
                "avatar_photo" => true,
                "name_photo" => true,
                "width_photo" => 1600,
                "height_photo" => 1200,
                "thumb_photo" => '200x150x1',
                "img_type_photo" => '.jpg|.jpeg|.png|.webp',
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

    /* 3 gạch đầu dòng lợi ích trên khối "Dự án nổi bật" + 3 icon tin cậy ở
       CTA cuối trang Dự án - thêm 2026-09-14 theo audit tích hợp Admin (trước
       đó hardcode trong sources/project_hub.php). Cố ý dùng chung 1 bộ cho
       mọi dự án (không phải số liệu riêng từng dự án), giống cách đã làm ở
       home-about-point. */
    foreach(array('project-benefit' => 'Dự án - Lợi ích nổi bật', 'project-trust' => 'Dự án - Tin cậy CTA') as $nametype => $projectPointTitle) {
        $config['news'][$nametype] = array(
            'title_main' => $projectPointTitle,
            'admin_note' => $nametype === 'project-benefit'
                ? 'Tối đa 3 record đang Hiển thị tạo 3 gạch đầu dòng trong khối "Dự án nổi bật" đầu trang Dự án. Tên record là nội dung dòng, dùng chung cho mọi dự án (không phải số liệu riêng dự án đó). Icon tuỳ chọn.'
                : 'Tối đa 3 record đang Hiển thị tạo hàng icon tin cậy ở CTA cuối trang Dự án. Tên record là nhãn ngắn. Icon tuỳ chọn.',
            'check' => array('hienthi' => 'Hiển thị'),
            'view' => false, 'slug' => false, 'copy' => false,
            'images' => true, 'show_images' => true,
            'desc' => false, 'content' => false, 'seo' => false,
            'width' => 80, 'height' => 80, 'thumb' => '100x100x2',
            'img_type' => '.jpg|.gif|.png|.jpeg|.gif'
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

    // /* Cam két đại lý */
    $nametype = "cam-ket-dai-ly";
    $config['news'][$nametype]['title_main'] = "Đại lý - Chính sách cam kết";
    $config['news'][$nametype]['admin_note'] = "Tối đa 4 nội dung hỗ trợ hiển thị tại mục Chính sách và hỗ trợ đại lý trên /dai-ly/. Tên là tiêu đề, Mô tả là nội dung ngắn và ảnh là biểu tượng hoặc minh họa có thể xác minh.";
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


    // /* Câu chuyện khách hàng và đối tác */
    $nametype = "phan-hoi-dai-ly";
    $config['news'][$nametype]['title_main'] = "Đại lý - Khách hàng & đối tác";
    $config['news'][$nametype]['admin_note'] = "Tối đa 3 câu chuyện hiển thị ở trang Đại lý (/dai-ly/). Tên là tên người chia sẻ, Mô tả là chức danh/đơn vị, Nội dung là lời nhận xét và ảnh là ảnh đại diện. Giữ type cũ để không làm mất dữ liệu đã nhập.";
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
    $config['news'][$nametype]['admin_note'] = "Mỗi record là một landing Giải pháp và đi thẳng từ /giai-phap/ tới /giai-phap/{slug}/. Mô tả dùng cho Hero/card; Nội dung CKEditor bắt đầu từ H2/H3; không chép Hero, sản phẩm, dự án, CTA hoặc form vào editor vì template render riêng. Cờ Trang Giải pháp chọn bài xuất hiện ở landing/menu. Nhóm cấp 1 cũ chỉ giữ để tương thích URL, không còn là bước điều hướng công khai.";
    $config['news'][$nametype]['title_index_label'] = "Tên ngắn trong menu/card";
    $config['news'][$nametype]['title_main'] = "Giải pháp";
    $config['news'][$nametype]['dropdown'] = false;
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
    $config['news'][$nametype]['check'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị", "solutionhub" => "Trang Giải pháp và menu");
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['images1'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['content'] = true;
    $config['news'][$nametype]['content_cke'] = true;
    $config['news'][$nametype]['content1'] = true;
    $config['news'][$nametype]['content1_cke'] = true;
    $config['news'][$nametype]['seo'] = true;
    $config['news'][$nametype]['width'] = 1600;
    $config['news'][$nametype]['height'] = 1000;
    $config['news'][$nametype]['thumb'] = '240x150x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.jpeg|.png|.webp';
    $config['news'][$nametype]['width1'] = 1600;
    $config['news'][$nametype]['height1'] = 1000;
    $config['news'][$nametype]['thumb1'] = '240x150x1';
    $config['news'][$nametype]['img_type1'] = '.jpg|.jpeg|.png|.webp';
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
    $config['news'][$nametype]['title_main_list'] = "Nhóm Giải pháp cũ (giữ URL)";
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
                ? 'Trang tổng /giai-phap/: tên là H1, tiêu đề phụ là ngữ cảnh ngắn, mô tả là đoạn mở đầu, ảnh là ảnh công trình. Nội dung CKEditor chứa các section biên tập tiếp theo; dùng H2/H3 đúng cấp, ảnh ngang đồng nhất, không tự bọc card/viền. Lấy record Hiển thị đầu tiên theo STT. Nếu có record nhưng tắt tất cả, phần mở đầu và nội dung biên tập được ẩn (vẫn giữ H1 cho trang).'
                : 'CTA cuối /giai-phap/: tên là tiêu đề, mô tả là lời mời tư vấn, tiêu đề phụ là chữ trên nút. Ảnh nền tùy chọn. Nút mở popup tư vấn hiện hữu; hotline lấy từ Cấu hình website. Tắt tất cả record để ẩn CTA.',
            'check' => array('hienthi' => 'Hiển thị'),
            'view' => false, 'slug' => false, 'copy' => false,
            'images' => true, 'show_images' => true,
            'title_index' => true, 'title_index_required' => false,
            'title_index_label' => $nametype === 'solution-intro' ? 'Nhãn nhỏ phía trên tiêu đề' : 'Chữ trên nút tư vấn',
            'desc' => true, 'desc_cke' => false,
            'content' => $nametype === 'solution-intro',
            'content_cke' => $nametype === 'solution-intro',
            'seo' => false,
            'width' => 1600,
            'height' => $nametype === 'solution-cta' ? 500 : 1000,
            'thumb' => $nametype === 'solution-cta' ? '320x100x1' : '240x150x1',
            'img_type' => '.jpg|.jpeg|.png|.webp'
        );
    }

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
    $config['news'][$nametype]['admin_note'] = "Điều khiển riêng section Con người tại /gioi-thieu/. Ảnh chuẩn 1600x1120, chỉ dùng ảnh đội ngũ thật có thể xác minh; chủ thể nằm trong vùng giữa để crop responsive. Frontend lấy record đang hiển thị có thứ tự nhỏ nhất và tự bỏ ảnh trỏ tới file đã mất.";
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
    $config['news'][$nametype]['width'] = 1600;
    $config['news'][$nametype]['height'] = 1120;
    $config['news'][$nametype]['thumb'] = '200x140x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.jpeg|.png|.webp';

    /* Ba điểm nhấn dưới section Con người của trang About. Tách thành record
       riêng để cả icon, tiêu đề, mô tả và thứ tự đều biên tập được. */
    $nametype = "about-principle";
    $config['news'][$nametype]['title_main'] = "About - Điểm nhấn con người";
    $config['news'][$nametype]['admin_note'] = "Tối đa 3 record đang Hiển thị tạo dải Chuyên môn / Đồng hành / Khách hàng ngay dưới section Con người tại /gioi-thieu/. Ảnh tải lên được dùng như icon; nên dùng PNG nền trong, cùng một phong cách.";
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

    /* Giới thiệu ngắn (About teaser) trang chủ - thêm 2026-09-14 theo audit
       tích hợp Admin sau đợt redesign trang chủ. Trước đó toàn bộ hardcode
       trong templates/index/index_tpl.php. */
    $nametype = "home-about";
    $config['news'][$nametype]['title_main'] = "Trang chủ - Giới thiệu ngắn";
    $config['news'][$nametype]['admin_note'] = "Khối \"Giới thiệu ngắn\" ngay dưới danh mục sản phẩm ở trang chủ: nhãn nhỏ, tiêu đề, mô tả, ảnh và chữ trên nút (nút luôn trỏ /gioi-thieu/). Lấy đúng 1 record đang Hiển thị có thứ tự (STT) nhỏ nhất; chưa tạo record nào, hoặc đã tạo nhưng tắt hết Hiển thị, thì dùng nội dung mặc định có sẵn trong code.";
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
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['content1'] = true;
    $config['news'][$nametype]['content1_cke'] = false;
    $config['news'][$nametype]['content1_label'] = "Chữ trên nút";
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 860;
    $config['news'][$nametype]['height'] = 680;
    $config['news'][$nametype]['thumb'] = '100x100x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* 4 gạch đầu dòng trong khối Giới thiệu ngắn - dùng tên (name) làm nội
       dung dòng, ảnh tuỳ chọn làm icon. */
    $nametype = "home-about-point";
    $config['news'][$nametype]['title_main'] = "Trang chủ - Điểm nhấn giới thiệu";
    $config['news'][$nametype]['admin_note'] = "Tối đa 4 record đang Hiển thị tạo 4 gạch đầu dòng trong khối Giới thiệu ngắn ở trang chủ, theo đúng thứ tự (STT). Tên record là nội dung dòng; mô tả là phần giải thích ngắn; ảnh tuỳ chọn dùng làm icon (PNG nền trong suốt), không tải ảnh thì dùng icon mặc định.";
    $config['news'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['news'][$nametype]['view'] = false;
    $config['news'][$nametype]['slug'] = false;
    $config['news'][$nametype]['copy'] = false;
    $config['news'][$nametype]['images'] = true;
    $config['news'][$nametype]['show_images'] = true;
    $config['news'][$nametype]['desc'] = true;
    $config['news'][$nametype]['content'] = false;
    $config['news'][$nametype]['seo'] = false;
    $config['news'][$nametype]['width'] = 80;
    $config['news'][$nametype]['height'] = 80;
    $config['news'][$nametype]['thumb'] = '100x100x2';
    $config['news'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Lịch sử */
    $nametype = "lich-su";
    $config['news'][$nametype]['title_main'] = "Lịch sử";
    $config['news'][$nametype]['admin_note'] = "Điều khiển phần Câu chuyện và timeline tại /gioi-thieu/. Ảnh câu chuyện chuẩn 1600x1120, dùng ảnh doanh nghiệp thật và không chèn chữ vào ảnh. Record giới thiệu có thể để trống Năm cột mốc; mỗi record có năm hợp lệ tạo một mốc trên timeline.";
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
    $config['news'][$nametype]['width'] = 1600;
    $config['news'][$nametype]['height'] = 1120;
    $config['news'][$nametype]['thumb'] = '200x140x1';
    $config['news'][$nametype]['img_type'] = '.jpg|.jpeg|.png|.webp';

    /* Sứ mệnh - Tầm nhìn - Giá trị */
    $nametype = "su-menh";
    $config['news'][$nametype]['title_main'] = "Sứ mệnh - Tầm nhìn - Giá trị";
    $config['news'][$nametype]['admin_note'] = "Ba record đầu tạo khối Tầm nhìn - Sứ mệnh - Giá trị cốt lõi tại /gioi-thieu/. Tiêu đề ngắn, mô tả, ảnh/icon, thứ tự và Hiển thị được áp dụng trực tiếp.";
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
    $config['news'][$nametype]['admin_note'] = "Tên/tiêu đề ngắn, mô tả và ảnh của record tạo card Lý do lựa chọn tại /gioi-thieu/. NÊN LUÔN bật cờ Trang Giới thiệu cho đúng 4 record muốn hiện - nếu chưa bật mục nào, hệ thống tự đoán theo TỪ KHOÁ TRONG TÊN record (vd tên có \"bảo hành\"/\"chính hãng\"...) để không bỏ trống khối, dễ ra kết quả không như ý nếu đổi tên record. Bật cờ rõ ràng để không phụ thuộc cách đoán này.";
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
