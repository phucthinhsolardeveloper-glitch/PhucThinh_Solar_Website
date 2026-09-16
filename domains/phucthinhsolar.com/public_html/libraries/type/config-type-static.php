<?php

    /* Sứ mệnh - tầm nhìn-giá trị */
    $nametype = "su-menh";
    $config['static'][$nametype]['title_main'] = "Sứ mệnh - Tầm nhìn - Giá trị (title)";
    $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['static'][$nametype]['images'] = false;
    $config['static'][$nametype]['namkinhnghiem'] = false;
    $config['static'][$nametype]['name'] = true;
    $config['static'][$nametype]['desc'] = false;
    $config['static'][$nametype]['desc_cke'] = false;
    $config['static'][$nametype]['content'] = false;
    $config['static'][$nametype]['content_cke'] = false;
    $config['static'][$nametype]['seo'] = false;
    $config['static'][$nametype]['width'] = 470;
    $config['static'][$nametype]['height'] = 470;
    $config['static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['static'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    /* Bài giới thiệu đại lý 1 */
    $nametype = "dai-ly-1";
    $config['static'][$nametype]['title_main'] = "Đại lý - Nội dung cạnh form đăng ký";
    $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    /* Ảnh đã tách sang photo type dealer-hero để có đúng tỷ lệ 16:5 và công
       tắc bật/tắt độc lập. Giữ file/dữ liệu ảnh legacy, không xoá. */
    $config['static'][$nametype]['images'] = false;
    $config['static'][$nametype]['namkinhnghiem'] = false;
    $config['static'][$nametype]['name'] = true;
    $config['static'][$nametype]['desc'] = true;
    $config['static'][$nametype]['desc_cke'] = true;
    $config['static'][$nametype]['content'] = false;
    $config['static'][$nametype]['content_cke'] = false;
    $config['static'][$nametype]['seo'] = false;
    $config['static'][$nametype]['width'] = 470;
    $config['static'][$nametype]['height'] = 470;
    $config['static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['static'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    /* Bài giới thiệu đại lý 2 */
    $nametype = "dai-ly-2";
    $config['static'][$nametype]['title_main'] = "Đại lý - Năng lực phân phối";
    $config['static'][$nametype]['admin_note'] = "Tên là tiêu đề section; Mô tả là nội dung năng lực hiển thị trước thư viện ảnh thực tế tại /dai-ly/.";
    $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['static'][$nametype]['images'] = false;
    $config['static'][$nametype]['namkinhnghiem'] = false;
    $config['static'][$nametype]['name'] = true;
    $config['static'][$nametype]['desc'] = true;
    $config['static'][$nametype]['desc_cke'] = true;
    $config['static'][$nametype]['content'] = false;
    $config['static'][$nametype]['content_cke'] = false;
    $config['static'][$nametype]['seo'] = false;
    $config['static'][$nametype]['width'] = 470;
    $config['static'][$nametype]['height'] = 470;
    $config['static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['static'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    /* Bài giới thiệu đại lý 3 */
    $nametype = "dai-ly-3";
    $config['static'][$nametype]['title_main'] = "Đại lý - Chính sách và hỗ trợ";
    $config['static'][$nametype]['admin_note'] = "Tên là tiêu đề section; Mô tả là lời dẫn trước tối đa 4 nội dung hỗ trợ đại lý tại /dai-ly/.";
    $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['static'][$nametype]['images'] = false;
    $config['static'][$nametype]['namkinhnghiem'] = false;
    $config['static'][$nametype]['name'] = true;
    $config['static'][$nametype]['desc'] = true;
    $config['static'][$nametype]['desc_cke'] = true;
    $config['static'][$nametype]['content'] = false;
    $config['static'][$nametype]['content_cke'] = false;
    $config['static'][$nametype]['seo'] = false;
    $config['static'][$nametype]['width'] = 470;
    $config['static'][$nametype]['height'] = 470;
    $config['static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['static'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    /* Cấu trúc cơ cấu */
    $nametype = "loi-ich";
    $config['static'][$nametype]['title_main'] = "Tiêu đề trang chủ";
    $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['static'][$nametype]['images'] = false;
    $config['static'][$nametype]['namkinhnghiem'] = false;
    $config['static'][$nametype]['name'] = true;
    $config['static'][$nametype]['desc'] = true;
    $config['static'][$nametype]['desc_cke'] = false;
    $config['static'][$nametype]['content'] = false;
    $config['static'][$nametype]['content_cke'] = false;
    $config['static'][$nametype]['seo'] = false;
    $config['static'][$nametype]['width'] = 470;
    $config['static'][$nametype]['height'] = 470;
    $config['static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    $config['static'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    /* Giới thiệu — mồ côi: sources/index.php từng query nhưng
       templates/index/index_tpl.php không dùng biến này. Dữ liệu cũ là
       text tiếng Anh mẫu cho NHÀ HÀNG ("PAM = People're Always Merry...")
       - xác nhận theme gốc không phải làm cho công ty đèn NLMT, đã xoá
       (audit 2026-09-11). */
    // $nametype = "gioi-thieu";
    // $config['static'][$nametype]['title_main'] = "Giới thiệu trang chủ";
    // $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['static'][$nametype]['images'] = true;
    // $config['static'][$nametype]['namkinhnghiem'] = true;
    // $config['static'][$nametype]['name'] = true;
    // $config['static'][$nametype]['desc'] = true;
    // $config['static'][$nametype]['desc_cke'] = true;
    // $config['static'][$nametype]['content'] = false;
    // $config['static'][$nametype]['content_cke'] = false;
    // $config['static'][$nametype]['seo'] = false;
    // $config['static'][$nametype]['width'] = 470;
    // $config['static'][$nametype]['height'] = 470;
    // $config['static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    // $config['static'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    /* Đối tác công nghệ — mồ côi: sources/index.php từng query nhưng
       templates/index/index_tpl.php không dùng biến này. Dữ liệu cũ rỗng,
       chỉ có ảnh stock "teamwork", đã xoá (audit 2026-09-11). */
    // $nametype = "doi-tac-cong-nghe";
    // $config['static'][$nametype]['title_main'] = "Đối tác công nghệ";
    // $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['static'][$nametype]['images'] = true;
    // $config['static'][$nametype]['namkinhnghiem'] = false;
    // $config['static'][$nametype]['name'] = true;
    // $config['static'][$nametype]['desc'] = true;
    // $config['static'][$nametype]['desc_cke'] = true;
    // $config['static'][$nametype]['content'] = false;
    // $config['static'][$nametype]['content_cke'] = false;
    // $config['static'][$nametype]['seo'] = false;
    // $config['static'][$nametype]['width'] = 470;
    // $config['static'][$nametype]['height'] = 470;
    // $config['static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    // $config['static'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    /* Tóm tắt giới thiệu */
    // $nametype = "tom-tat-gioi-thieu";
    // $config['static'][$nametype]['title_main'] = "Tóm tắt giới thiệu";
    // $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['static'][$nametype]['images'] = false;
    // $config['static'][$nametype]['namkinhnghiem'] = false;
    // $config['static'][$nametype]['name'] = true;
    // $config['static'][$nametype]['desc'] = true;
    // $config['static'][$nametype]['desc_cke'] = true;
    // $config['static'][$nametype]['content'] = false;
    // $config['static'][$nametype]['content_cke'] = false;
    // $config['static'][$nametype]['seo'] = false;
    // $config['static'][$nametype]['width'] = 470;
    // $config['static'][$nametype]['height'] = 470;
    // $config['static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    // $config['static'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    /* Giới thiệu sản phẩm */
    // $nametype = "gioi-thieu-san-pham";
    // $config['static'][$nametype]['title_main'] = "Giới thiệu sản phẩm";
    // $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['static'][$nametype]['images'] = true;
    // $config['static'][$nametype]['file'] = true;
    // $config['static'][$nametype]['name'] = true;
    // $config['static'][$nametype]['desc'] = true;
    // $config['static'][$nametype]['desc_cke'] = false;
    // $config['static'][$nametype]['content'] = true;
    // $config['static'][$nametype]['content_cke'] = true;
    // $config['static'][$nametype]['seo'] = true;
    // $config['static'][$nametype]['width'] = 300;
    // $config['static'][$nametype]['height'] = 200;
    // $config['static'][$nametype]['img_type'] = '.jpg|.gif|.png|.jpeg|.gif';
    // $config['static'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    // /* Slogan */
    $nametype = "lienhe";
    $config['static'][$nametype]['title_main'] = "Liên hệ";
    $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['static'][$nametype]['name'] = false;
    $config['static'][$nametype]['content'] = true;
    $config['static'][$nametype]['content_cke'] = true;

    /* Liên hệ */
    $nametype = "footer";
    $config['static'][$nametype]['title_main'] = "Footer";
    $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    $config['static'][$nametype]['content'] = true;
    $config['static'][$nametype]['content_cke'] = true;
    $config['static'][$nametype]['name'] = true;

    // /* Bộ công thương (bản text) — trùng chức năng với bct (photo, badge+link
    //    đang sống ở footer). Query đã bị comment sẵn ở sources/allpage.php
    //    từ trước, field admin này chỉ gây nhầm lẫn khi sửa nhầm không tác
    //    dụng (audit 2026-09-11). */
    // $nametype = "bct";
    // $config['static'][$nametype]['title_main'] = "Bộ công thương";
    // $config['static'][$nametype]['check'] = array("hienthi" => "Hiển thị");
    // $config['static'][$nametype]['name'] = false;
    // $config['static'][$nametype]['content'] = true;
    // $config['static'][$nametype]['content_cke'] = true;
?>
