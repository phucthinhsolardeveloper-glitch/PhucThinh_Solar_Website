<?php
    /* Background */
    // $nametype = "background";
    // $config['photo']['photo_static'][$nametype]['title_main'] = "Background";
    // $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['photo']['photo_static'][$nametype]['images'] = true;
    // $config['photo']['photo_static'][$nametype]['background'] = true;
    // $config['photo']['photo_static'][$nametype]['width'] = 900;
    // $config['photo']['photo_static'][$nametype]['height'] = 300;
    // $config['photo']['photo_static'][$nametype]['thumb'] = '900x300x1';
    // $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Ảnh nền Hero trang chủ (chế độ "text") - dùng thay cho nền gradient mặc
       định khi chủ site muốn có ảnh thật phía sau chữ/số liệu/khối chứng
       nhận. Nhiều ảnh -> tự chuyển dạng crossfade (không dùng chế độ
       "slider" toàn banner vì chủ site chưa hợp gu, có thể thiết kế lại
       sau). man_photo cho phép thêm/xoá/sắp thứ tự nhiều ảnh, giống
       Slideshow/Đối tác. sources/index.php đọc, render+cycle trong
       templates/layout/slide.php (audit UX 2026-09-08, phản hồi tiếp
       theo). */
    $nametype = "home-hero-bg";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Ảnh nền Hero trang chủ";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 5;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1920;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 900;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '1920x900x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Banner quảng cáo đầu trang, hiển thị trên mọi trang, phía trên header */
    $nametype = "banner-top";
    $config['photo']['photo_static'][$nametype]['title_main'] = "Banner đầu trang (trên header)";
    $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['photo']['photo_static'][$nametype]['images'] = true;
    $config['photo']['photo_static'][$nametype]['link'] = true;
    $config['photo']['photo_static'][$nametype]['width'] = 1900;
    $config['photo']['photo_static'][$nametype]['height'] = 90;
    $config['photo']['photo_static'][$nametype]['thumb'] = '1900x90x1';
    $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Logo */
    $nametype = "logo";
    $config['photo']['photo_static'][$nametype]['title_main'] = "Logo";
    $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['photo']['photo_static'][$nametype]['images'] = true;
    $config['photo']['photo_static'][$nametype]['width'] = 80;
    $config['photo']['photo_static'][$nametype]['height'] = 80;
    $config['photo']['photo_static'][$nametype]['thumb'] = '120x100x1';
    $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Logo footer */
    $nametype = "logo-footer";
    $config['photo']['photo_static'][$nametype]['title_main'] = "Logo footer";
    $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['photo']['photo_static'][$nametype]['images'] = true;
    $config['photo']['photo_static'][$nametype]['width'] = 80;
    $config['photo']['photo_static'][$nametype]['height'] = 80;
    $config['photo']['photo_static'][$nametype]['thumb'] = '120x100x1';
    $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Favicon */
    $nametype = "favicon";
    $config['photo']['photo_static'][$nametype]['title_main'] = "Favicon";
    $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['photo']['photo_static'][$nametype]['images'] = true;
    $config['photo']['photo_static'][$nametype]['width'] = 48;
    $config['photo']['photo_static'][$nametype]['height'] = 48;
    $config['photo']['photo_static'][$nametype]['thumb'] = '48x48x1';
    $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Watermark */
    $nametype = "watermark";
    $config['photo']['photo_static'][$nametype]['title_main'] = "Watermark sản phẩm";
    $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['photo']['photo_static'][$nametype]['images'] = true;
    $config['photo']['photo_static'][$nametype]['watermark'] = true;
    $config['photo']['photo_static'][$nametype]['watermark-advanced'] = true;
    $config['photo']['photo_static'][$nametype]['width'] = 50;
    $config['photo']['photo_static'][$nametype]['height'] = 50;
    $config['photo']['photo_static'][$nametype]['thumb'] = '50x50x1';
    $config['photo']['photo_static'][$nametype]['img_type'] = '.png|.PNG|.Png';

    /* Watermark tin tức */
    $nametype = "watermark-news";
    $config['photo']['photo_static'][$nametype]['title_main'] = "Watermark tin tức";
    $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['photo']['photo_static'][$nametype]['images'] = true;
    $config['photo']['photo_static'][$nametype]['watermark'] = true;
    $config['photo']['photo_static'][$nametype]['watermark-advanced'] = true;
    $config['photo']['photo_static'][$nametype]['width'] = 50;
    $config['photo']['photo_static'][$nametype]['height'] = 50;
    $config['photo']['photo_static'][$nametype]['thumb'] = '50x50x1';
    $config['photo']['photo_static'][$nametype]['img_type'] = '.png|.PNG|.Png';

    /* Video */
    // $nametype = "video";
    // $config['photo']['photo_static'][$nametype]['title_main'] = "Video";
    // $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['photo']['photo_static'][$nametype]['images'] = true;
    // $config['photo']['photo_static'][$nametype]['video'] = true;
    // $config['photo']['photo_static'][$nametype]['name'] = true;
    // $config['photo']['photo_static'][$nametype]['desc'] = true;
    // $config['photo']['photo_static'][$nametype]['content'] = true;
    // $config['photo']['photo_static'][$nametype]['width'] = 250;
    // $config['photo']['photo_static'][$nametype]['height'] = 150;
    // $config['photo']['photo_static'][$nametype]['thumb'] = '250x150x1';
    // $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    // $config['photo']['photo_static'][$nametype]['video_mp4'] = true;
    // $config['photo']['photo_static'][$nametype]['show_stt'] = true;
    // $config['photo']['photo_static'][$nametype]['video_type'] = 'mp4';

    /* Popup */
    $nametype = "popup";
    $config['photo']['photo_static'][$nametype]['title_main'] = "Popup quảng cáo (hiện khi vào trang chủ)";
    $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['photo']['photo_static'][$nametype]['images'] = true;
    $config['photo']['photo_static'][$nametype]['name'] = true;
    $config['photo']['photo_static'][$nametype]['link'] = true;
    $config['photo']['photo_static'][$nametype]['width'] = 800;
    $config['photo']['photo_static'][$nametype]['height'] = 530;
    $config['photo']['photo_static'][$nametype]['thumb'] = '800x530x1';
    $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    $nametype = "bct";
    $config['photo']['photo_static'][$nametype]['title_main'] = "Logo bộ công thương";
    $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['photo']['photo_static'][$nametype]['images'] = true;
    $config['photo']['photo_static'][$nametype]['name'] = false;
    $config['photo']['photo_static'][$nametype]['link'] = true;
    $config['photo']['photo_static'][$nametype]['width'] = 800;
    $config['photo']['photo_static'][$nametype]['height'] = 530;
    $config['photo']['photo_static'][$nametype]['thumb'] = '800x530x1';
    $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Hình ảnh giới thiệu — mồ côi: sources/about.php từng query nhưng
       templates/about/about_tpl.php không dùng biến này (đã xác nhận qua
       audit 2026-09-11). Dữ liệu cũ chỉ là 1 ảnh demo không rõ nguồn gốc,
       đã xoá. Ẩn field admin, không xoá code để có thể khôi phục nếu sau
       này thật sự cần. */
    // $nametype = "hinh-anh-gioi-thieu";
    // $config['photo']['photo_static'][$nametype]['title_main'] = "Hình ảnh giới thiệu (trang Về chúng tôi)";
    // $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['photo']['photo_static'][$nametype]['images'] = true;
    // $config['photo']['photo_static'][$nametype]['width'] = 600;
    // $config['photo']['photo_static'][$nametype]['height'] = 800;
    // $config['photo']['photo_static'][$nametype]['thumb'] = '600x800x1';
    // $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Video giới thiệu công ty — mồ côi thật sự: templates/static/about_tpl.php
       KHÔNG được route nào include (đã xoá cả file, xem sources/static.php).
       Comment cũ ở đây nói "đã render, chỉ thiếu đăng ký admin" là SAI - đó
       chính là nguyên nhân field này được thêm nhầm (audit 2026-09-11). Dữ
       liệu cũ là ảnh Pexels stock, file đã mất trên đĩa, đã xoá row. */
    // $nametype = "video";
    // $config['photo']['photo_static'][$nametype]['title_main'] = "Video giới thiệu (trang Về chúng tôi)";
    // $config['photo']['photo_static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['photo']['photo_static'][$nametype]['images'] = true;
    // $config['photo']['photo_static'][$nametype]['video_mp4'] = true;
    // $config['photo']['photo_static'][$nametype]['video_type'] = 'mp4';
    // $config['photo']['photo_static'][$nametype]['width'] = 1280;
    // $config['photo']['photo_static'][$nametype]['height'] = 720;
    // $config['photo']['photo_static'][$nametype]['thumb'] = '1280x720x1';
    // $config['photo']['photo_static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Slideshow */
    $nametype = "slide";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Slideshow";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 2;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1920;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 712;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '1920x712x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['photo']['man_photo'][$nametype]['video_mp4'] = true;
    $config['photo']['man_photo'][$nametype]['show_stt'] = true;
    $config['photo']['man_photo'][$nametype]['video_type'] = 'mp4';

    /* Banner quảng cáo */
    $nametype = "adv";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Banner quảng cáo";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 3;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_cke_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 379;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 171;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '379x171x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';


    /* Giá trị cốt lõi — mồ côi: sources/index.php từng query nhưng
       templates/index/index_tpl.php không dùng biến này. Dữ liệu cũ chỉ là
       4 ảnh stock "chuyển đổi số"/"corporate strategy" demo, đã xoá (audit
       2026-09-11). */
    // $nametype = "gia-tri-cot-loi";
    // $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Giá trị cốt lõi";
    // $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    // $config['photo']['man_photo'][$nametype]['number_photo'] = 3;
    // $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['width_photo'] = 379;
    // $config['photo']['man_photo'][$nametype]['height_photo'] = 171;
    // $config['photo']['man_photo'][$nametype]['thumb_photo'] = '379x171x1';
    // $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Hình ảnh */
    $nametype = "hinh-anh-dai-ly";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Hình ảnh";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 3;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 379;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 171;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '379x171x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Slideshow product */
    // $nametype = "slide-product";
    // $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Slideshow sản phẩm";
    // $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    // $config['photo']['man_photo'][$nametype]['number_photo'] = 2;
    // $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['width_photo'] = 1366;
    // $config['photo']['man_photo'][$nametype]['height_photo'] = 600;
    // $config['photo']['man_photo'][$nametype]['thumb_photo'] = '200x100x1';
    // $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Social */
    $nametype = "social";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Social";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 2;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 30;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 30;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '30x30x2';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';

    /* Video */
    // $nametype = "video";
    // $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Video";
    // $config['photo']['man_photo'][$nametype]['check_photo'] = array("noibat" => "Nổi bật", "hienthi" => "Hiển thị");
    // $config['photo']['man_photo'][$nametype]['number_photo'] = 2;
    // $config['photo']['man_photo'][$nametype]['video_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['name_photo'] = true;

    // /* Đối tác */
    $nametype = "doitac";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Đối tác";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 5;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 175;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 95;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '175x95x2';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';

    // /* Chứng nhận */
    $nametype = "chungnhan";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Chứng nhận";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 5;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = false;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 175;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 95;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '175x95x2';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';

    // /* Đối tác - giới thiệu — mồ côi: sources/about.php từng query nhưng
    //    templates/about/about_tpl.php không dùng biến này. Dữ liệu cũ là 6
    //    logo không liên quan (Sacombank, HUTECH...) - demo leftover, đã xoá
    //    (audit 2026-09-11). */
    // $nametype = "doitacgioithieu";
    // $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Đối tác - giới thiệu";
    // $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    // $config['photo']['man_photo'][$nametype]['number_photo'] = 5;
    // $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['link_photo'] = false;
    // $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    // $config['photo']['man_photo'][$nametype]['width_photo'] = 175;
    // $config['photo']['man_photo'][$nametype]['height_photo'] = 95;
    // $config['photo']['man_photo'][$nametype]['thumb_photo'] = '175x95x2';
    // $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';
?>
