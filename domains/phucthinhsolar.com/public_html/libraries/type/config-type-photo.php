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

    /* Hero carousel trang chủ (layout 3, thêm 2026-09-12, Admin hoá 2026-09-14)
       - đúng 4 slide cố định, mỗi slide 1 record. Field chuẩn (tên/mô tả/
       link/ảnh) dùng hệ thống có sẵn; eyebrow/dòng tiêu đề 2/nhãn+link CTA
       phụ/tag/theme không có cột riêng nên lưu qua options JSON (field khối
       "hero-carousel-extra" tự thêm trong photo_add_tpl.php/photo_edit_tpl.php,
       merge JSON đã có sẵn trong savePhoto() - xem administrator/sources/photo.php).
       libraries/home_hero_carousel.php đọc đúng 4 record này, rơi về mảng
       tĩnh cũ nếu chưa đủ/chưa có record nào (không để trang chủ trống Hero). */
    $nametype = "hero-carousel";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Hero carousel trang chủ";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Layout 3 của Hero (carousel 4 slide, bấm nút tròn giữa Hero để xem). Đúng 4 slide theo thứ tự Số thứ tự. \"Tiêu đề\" là dòng 1 (chữ trắng đậm); dòng 2 (chữ mờ hơn), nhãn nhỏ phía trên, CTA phụ và tag nằm trong khối riêng bên dưới. Link chính có thể mở popup tư vấn/đại lý thay vì đường dẫn thường. Chưa đủ 4 slide Hiển thị thì trang chủ tự dùng nội dung mặc định có sẵn trong code, không để trống.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 4;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1920;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 900;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '1920x900x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_label_photo'] = 'Tiêu đề (dòng 1)';
    $config['photo']['man_photo'][$nametype]['desc_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_label_photo'] = 'Mô tả ngắn';
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_label_photo'] = 'Link nút chính';

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
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Banner CTA dùng ở các khối chuyển đổi hiện hữu. Ảnh nguồn 1600x900, chủ thể nằm trong 70% vùng giữa để crop desktop/mobile không che nội dung; không chèn chữ trực tiếp vào ảnh vì tiêu đề và nút do website render.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 3;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_cke_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1600;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 900;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '320x180x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';

    /* Ba ô chiến dịch ngay dưới Hero trang chủ. Tách khỏi type adv vì adv đang
       được dùng cho CTA tư vấn/đại lý; nhờ đó biên tập viên có thể đổi ảnh,
       tiêu đề, mô tả và đích đến mà không làm thay đổi các popup hiện hữu. */
    $nametype = "home-campaign";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Trang chủ - Banner chiến dịch";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Tối đa 3 banner ở đầu trang chủ product-first. Ảnh chuẩn 1600x900 (16:9), chủ thể và chi tiết quan trọng nằm trong 70% vùng giữa; không chèn chữ vào ảnh. Nếu type chưa từng có record, website dùng bộ ảnh mặc định. Khi đã có record, tắt cờ Hiển thị sẽ ẩn đúng banner đó và không tự lấp lại.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 3;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_label_photo'] = "Tiêu đề banner";
    $config['photo']['man_photo'][$nametype]['desc_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_label_photo'] = "Mô tả ngắn";
    $config['photo']['man_photo'][$nametype]['desc_cke_photo'] = false;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1600;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 900;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '320x180x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';

    /* Ảnh bối cảnh phía sau dải chứng nhận thật trên trang chủ. Đây chỉ là
       background marketing, tuyệt đối không thay thế tài liệu chứng nhận. */
    $nametype = "home-proof-background";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Trang chủ - Nền dải chứng nhận";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Một ảnh nền 1920x600 (16:5) cho dải chứng nhận. Chọn ảnh ít chi tiết, tông tối và giữ chủ thể trong 75% vùng giữa để chữ cùng bản quét chứng nhận vẫn rõ trên mọi màn hình. Không đưa chữ, logo hoặc chứng nhận giả vào ảnh nền. Tắt cờ Hiển thị để dùng nền sáng phẳng.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 1;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = false;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_label_photo'] = "Tên ảnh / mô tả nội bộ";
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1920;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 600;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '320x100x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';

    /* Banner ngắt nhịp trong trang /san-pham/. Mỗi record được đặt tự động
       giữa các nhóm danh mục, không chen vào kết quả lọc của trang con. */
    $nametype = "catalog-campaign";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Sản phẩm - Banner giữa danh mục";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Tối đa 3 banner ngắt nhịp trong trang /san-pham/, tự phân bố giữa các nhóm sản phẩm. Ảnh chuẩn 1920x600 (16:5), chủ thể nằm bên phải và vùng trái đủ tối/thoáng cho chữ HTML. Không chèn chữ hoặc logo trực tiếp vào ảnh. Tắt cờ Hiển thị để ẩn từng banner.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 3;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_label_photo'] = "Tiêu đề banner";
    $config['photo']['man_photo'][$nametype]['desc_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_label_photo'] = "Mô tả ngắn";
    $config['photo']['man_photo'][$nametype]['desc_cke_photo'] = false;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1920;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 600;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '320x100x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';

    /* Ảnh tư vấn của trang Liên hệ. Một record duy nhất để có công tắc bật/tắt
       rõ ràng và không trộn lẫn với banner quảng cáo trang chủ. */
    $nametype = "contact-visual";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Liên hệ - Ảnh tư vấn";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Ảnh mở đầu trang /lien-he/, đặt cạnh thông tin công ty. Ảnh chuẩn 1600x1000 (8:5), ưu tiên showroom/tư vấn/kỹ thuật thật; chủ thể nằm trong 75% vùng giữa để dùng được trên mobile. Tắt cờ Hiển thị để ẩn toàn bộ ảnh.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 1;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_label_photo'] = "Tiêu đề tư vấn";
    $config['photo']['man_photo'][$nametype]['desc_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_label_photo'] = "Mô tả ngắn";
    $config['photo']['man_photo'][$nametype]['desc_cke_photo'] = false;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1600;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 1000;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '320x200x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';

    /* Ảnh năng lực phân phối ở đầu trang Đại lý. Nội dung chữ vẫn nằm trong
       static dai-ly-1; tách ảnh để có kích thước/công tắc đúng mục đích. */
    $nametype = "dealer-hero";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Đại lý - Ảnh năng lực phân phối";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Banner đầu trang Đại lý, chuẩn 1920x600 (16:5) - giống banner giữa catalog. Chủ thể/chi tiết quan trọng nằm bên phải, 1/3 bên trái tối hoặc thoáng vì chữ tiêu đề phủ lên đó (không chèn chữ vào ảnh). Ưu tiên ảnh kho hàng, showroom, thiết bị hoặc đội vận hành thật; không dùng ảnh dự án/khách hàng không xác minh. Tắt cờ Hiển thị để ẩn ảnh.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 1;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = false;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_label_photo'] = "Mô tả ảnh";
    $config['photo']['man_photo'][$nametype]['desc_photo'] = false;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1920;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 600;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '320x100x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';


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
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Đại lý - Hình ảnh thực tế";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Gallery ở trang Đại lý. Ảnh chuẩn 1600x1000 (8:5), cùng tỷ lệ để hàng ảnh không cao thấp; chỉ dùng kho hàng, showroom, trưng bày hoặc hoạt động đại lý có thể xác minh.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 3;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1600;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 1000;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '320x200x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';

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

    // /* Thương hiệu hợp tác */
    $nametype = "doitac";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Thương hiệu hợp tác";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Logo xuất hiện trong dải Thương hiệu hợp tác gần cuối trang chủ và tại trang Về chúng tôi. Upload canvas 600x240 nền trong suốt hoặc trắng, logo nằm gọn trong 80% vùng giữa; frontend dùng contain, không cắt logo. Chỉ record đang Hiển thị mới xuất hiện.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 5;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 600;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 240;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '200x80x2';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';

    /* Banner ảnh theo từng danh mục sản phẩm. Trường Link chứa canonical path
       (ví dụ danh-muc-san-pham/den-san-vuon/) để không phải hard-code quan hệ
       vào template. */
    $nametype = "product-category-hero";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Banner danh mục sản phẩm";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Mỗi record áp dụng cho một danh mục. Nhập Link đúng canonical path, ví dụ danh-muc-san-pham/den-san-vuon/. Ảnh chuẩn 1800x600 (16:5), không chèn chữ; giữ chủ thể trong vùng giữa 75% để crop responsive. Frontend phủ lớp tối và tự render tiêu đề/nút.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 3;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_label_photo'] = "Canonical path danh mục";
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_label_photo'] = "Tên ảnh / mô tả nội bộ";
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1800;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 600;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '300x100x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';

    /* CTA riêng cho trang About. Record đầu tiên đang hiển thị cung cấp tiêu
       đề, mô tả, link nút chính và ảnh nền; frontend có fallback khi chưa có. */
    $nametype = "about-cta";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "About - CTA tư vấn";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Điều khiển dải CTA cuối /gioi-thieu/. Tiêu đề, mô tả, link và ảnh nền đều lấy từ record đầu tiên đang Hiển thị; link để trống sẽ mở popup tư vấn hiện hữu.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 1;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['name_label_photo'] = "Tiêu đề CTA";
    $config['photo']['man_photo'][$nametype]['desc_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_label_photo'] = "Mô tả CTA";
    $config['photo']['man_photo'][$nametype]['desc_cke_photo'] = false;
    $config['photo']['man_photo'][$nametype]['link_label_photo'] = "Link nút Nhận tư vấn";
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1600;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 520;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '320x104x1';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.gif|.png|.jpeg|.gif';

    // /* Chứng nhận */
    $nametype = "chungnhan";
    $config['photo']['man_photo'][$nametype]['title_main_photo'] = "Chứng nhận";
    $config['photo']['man_photo'][$nametype]['admin_note_photo'] = "Trang /gioi-thieu/ hiển thị tối đa 3 chứng nhận có cờ Nổi bật. Upload bản quét dọc rõ nét 1600x2200, không crop mép, không kéo giãn; frontend dùng contain. Tên là tên chứng nhận, Mô tả là thông tin ngắn. Chỉ dùng hồ sơ có thể xác minh.";
    $config['photo']['man_photo'][$nametype]['check_photo'] = array("noibat" => "Nổi bật trên About", "hienthi" => "Hiển thị");
    $config['photo']['man_photo'][$nametype]['number_photo'] = 5;
    $config['photo']['man_photo'][$nametype]['images_photo'] = true;
    $config['photo']['man_photo'][$nametype]['avatar_photo'] = true;
    $config['photo']['man_photo'][$nametype]['link_photo'] = false;
    $config['photo']['man_photo'][$nametype]['name_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_photo'] = true;
    $config['photo']['man_photo'][$nametype]['desc_cke_photo'] = false;
    $config['photo']['man_photo'][$nametype]['width_photo'] = 1600;
    $config['photo']['man_photo'][$nametype]['height_photo'] = 2200;
    $config['photo']['man_photo'][$nametype]['thumb_photo'] = '160x220x2';
    $config['photo']['man_photo'][$nametype]['img_type_photo'] = '.jpg|.jpeg|.png|.webp';

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
