<?php if(!isGoogleSpeed()){ ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap">
<?php } ?>
<!-- Css Files -->
<?php
    $css->set("css/font-be-vietnam-pro.css");
    $css->set("bootstrap/bootstrap.css");
    $css->set("css/style.min080f.css");
    $css->set("css/vendors-style6b00.css");
    $css->set("css/styles5697.css");
    // $css->set("css/woocommerce-layout4698.css");
    // $css->set("css/woocommerce-smallscreen4698.css");
    // $css->set("css/woocommerce4698.css");
?>
<style>
    .woocommerce form .form-row .required {
        visibility: visible;
    }
</style>
<?php
    $css->set("fontawesome512/all.css");
    $css->set("themes/local/css/bootstrap.min080f.css");
    $css->set("themes/local/css/font-awesome.min080f.css");
    $css->set("themes/local/css/cssanimation080f.css");
    $css->set("themes/local/css/aos84a8.css");
    $css->set("themes/local/css/main6c6f.css");
    $css->set("themes/local/css/def84a8.css");
    $css->set("themes/local/css/frontendd82d.css");
    $css->set("themes/local/js/slick/slick080f.css");
    $css->set("themes/local/css/custom080f.css");
    $css->set("themes/local/fonts/Montserrat/stylesheet2080f.css");
    $css->set("themes/local/fonts/Linearicons/style9c88.css");
    $css->set("themes/local/css/core.min4ca0.css");
    $css->set("themes/local/css/home076c.css");
    $css->set("themes/local/css/mainV2173a.css");
    $css->set("css/frontendf3c4.css");

    /* Đã bỏ khối <style id='rocket-lazyload-inline-css'>: 55 dòng CSS nội tuyến
       cho class .rll-youtube-player do plugin WP Rocket LazyLoad sinh ra. Plugin
       không còn chạy, không template hay JS nào của site render class này, và quét
       toàn bộ cột text trong DB cũng không có bản ghi nào chứa nó. Bản thân CSS còn
       trỏ background tới wp-content/plugins/rocket-lazy-load/.../youtube.png — file
       chưa từng tồn tại trên đĩa. Tức là CSS chết, gửi kèm mọi lượt xem trang. */
    $css->set("holdon/HoldOn-style.css");
    $css->set("confirm/confirm.css");
    $css->set("simplenotify/simple-notify.css");
    $css->set("fileuploader/font-fileuploader.css");
    $css->set("fileuploader/jquery.fileuploader.min.css");
    $css->set("css/jssor.css");
    $css->set("fancybox3/jquery.fancybox.css");
    $css->set("fancybox3/jquery.fancybox.style.css");
    // $css->set("css/account.css");
    $css->set("mmenu/mmenu.css");
    $css->set("photobox/photobox.css");
    $css->set("slick/slick.css");
    $css->set("slick/slick-theme.css");
    $css->set("slick/slick-style.css");
    // $css->set("fotorama/fotorama.css");
    // $css->set("fotorama/fotorama-style.css");
    $css->set("magiczoomplus/magiczoomplus.css");
    // $css->set("datetimepicker/jquery.datetimepicker.css");
    $css->set("owlcarousel2/owl.carousel.css");
    $css->set("owlcarousel2/owl.theme.default.css");
    // $css->set("simplenotify/simple-notify.css");
    // $css->set("fileuploader/font-fileuploader.css");
    // $css->set("fileuploader/jquery.fileuploader.min.css");
    // $css->set("fileuploader/jquery.fileuploader-theme-dragdrop.css");
    $css->set("comment/comment.css");
    $css->set("css/style.css");
    $css->set("css/theme-v2.css");
    if($source == 'index') $css->set("css/home-story-v4.css");
    if($source == 'about') $css->set("css/about-v2.css");
    if(in_array($template, array('news/service_list', 'news/service', 'news/service_detail'), true)) $css->set("css/solution-hub.css");
    if($template == 'news/project' || $template == 'news/project_detail') $css->set("css/project-hub.css");
    if(isset($type) && $type === 'tin-tuc' && $source === 'news') $css->set('css/news-hub.css');
    if($source == 'daily') $css->set("css/dealer-v2.css");
    $css->set("css/header-v3.css");
    /* Quy tắc surface phẳng dùng toàn site phải nạp cuối để thắng CSS legacy
       và các stylesheet từng hub mà không sửa rải rác nhiều nguồn. */
    $css->set("css/flat-surface-system.css");
    /* Chuẩn tỷ lệ/object-fit và các vị trí ảnh có mục đích. Nạp sau surface
       system vì đây là lớp cuối chịu trách nhiệm kích thước media responsive. */
    $css->set("css/media-system.css");
    /* Bảng màu + chip lọc dùng chung. Phải nạp CUỐI CÙNG để thắng khai báo
       :root cục bộ của từng hub (mỗi hub trước đây tự định nghĩa navy/gold/
       muted/line riêng, lệch nhau 2-5% nên toàn site trông không liền mạch). */
    $css->set("css/design-tokens.css");
    /* Kiến trúc responsive nạp cuối: desktop giữ nguyên; mobile chỉ còn một
       lớp sở hữu gutter và flatten các wrapper cấu trúc theo từng page. */
    $css->set("css/responsive-architecture.css");
    /* Luồng mua hàng có lưới và breakpoint riêng. Nạp sau kiến trúc responsive
       chung để không quay lại các cột Bootstrap 15/12 ở giỏ hàng mobile. */
    $css->set("css/commerce-experience.css");
    /* Thang chữ public dùng chung nạp sau cùng để thay các cỡ chữ legacy
       rời rạc mà không phải vá từng template. */
    $css->set("css/typography-system.css");
    $css->set("css/footer-v3.css");
    if($source == 'contact') $css->set("css/contact-v3.css");
    if($template === 'product/product_detail') $css->set("css/product-detail-v2.css");
    echo $css->get();
?>
<script type="text/javascript" src="assets/js/jquery.min.js?v=NMr3JZBT0U"></script>
<?php if(!isGoogleSpeed()){ ?>
<!-- Js Google Analytic -->
<?=htmlspecialchars_decode($setting['analytics'])?>

<!-- Js Head -->
<?=htmlspecialchars_decode($setting['headjs'])?>
<?php } ?>
