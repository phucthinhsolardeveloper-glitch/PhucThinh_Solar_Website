<?php if(!isGoogleSpeed()){ ?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap');
</style>
<?php } ?>
<!-- Css Files -->
<?php
    $isProductDetail = ($template === 'product/product_detail');
    $usesPhotobox = in_array($template, array('news/project_detail', 'album/album_detail'), true);
    $usesFancybox = in_array($template, array('news/project_detail', 'video/video'), true);
    $usesJssor = ($source === 'index');

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
?>
<style id='rocket-lazyload-inline-css' type='text/css'>
        .rll-youtube-player {
            position: relative;
            padding-bottom: 56.23%;
            height: 0;
            overflow: hidden;
            max-width: 100%;
        }

        .rll-youtube-player iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 100;
            background: 0 0
        }

        .rll-youtube-player img {
            bottom: 0;
            display: block;
            left: 0;
            margin: auto;
            max-width: 100%;
            width: 100%;
            position: absolute;
            right: 0;
            top: 0;
            border: none;
            height: auto;
            cursor: pointer;
            -webkit-transition: .4s all;
            -moz-transition: .4s all;
            transition: .4s all
        }

        .rll-youtube-player img:hover {
            -webkit-filter: brightness(75%)
        }

        .rll-youtube-player .play {
            height: 72px;
            width: 72px;
            left: 50%;
            top: 50%;
            margin-left: -36px;
            margin-top: -36px;
            position: absolute;
            background: url(wp-content/plugins/rocket-lazy-load/assets/img/youtube.png) no-repeat;
            cursor: pointer
        }
    </style>
<?php
    $css->set("holdon/HoldOn-style.css");
    $css->set("confirm/confirm.css");
    $css->set("simplenotify/simple-notify.css");
    if($isProductDetail)
    {
        $css->set("fileuploader/font-fileuploader.css");
        $css->set("fileuploader/jquery.fileuploader.min.css");
    }
    if($usesJssor) $css->set("css/jssor.css");
    if($usesFancybox)
    {
        $css->set("fancybox3/jquery.fancybox.css");
        $css->set("fancybox3/jquery.fancybox.style.css");
    }
    // $css->set("css/account.css");
    $css->set("mmenu/mmenu.css");
    if($usesPhotobox) $css->set("photobox/photobox.css");
    $css->set("slick/slick.css");
    $css->set("slick/slick-theme.css");
    $css->set("slick/slick-style.css");
    // $css->set("fotorama/fotorama.css");
    // $css->set("fotorama/fotorama-style.css");
    if($isProductDetail) $css->set("magiczoomplus/magiczoomplus.css");
    // $css->set("datetimepicker/jquery.datetimepicker.css");
    $css->set("owlcarousel2/owl.carousel.css");
    $css->set("owlcarousel2/owl.theme.default.css");
    // $css->set("simplenotify/simple-notify.css");
    // $css->set("fileuploader/font-fileuploader.css");
    // $css->set("fileuploader/jquery.fileuploader.min.css");
    // $css->set("fileuploader/jquery.fileuploader-theme-dragdrop.css");
    if($isProductDetail) $css->set("comment/comment.css");
    $css->set("css/style.css");
    $css->set("css/theme-v2.css");
    echo $css->get();
?>
<script type="text/javascript" src="assets/js/jquery.min.js?v=NMr3JZBT0U"></script>
<?php if(!isGoogleSpeed()){ ?>
<!-- Js Google Analytic -->
<?=htmlspecialchars_decode($setting['analytics'])?>

<!-- Js Head -->
<?=htmlspecialchars_decode($setting['headjs'])?>
<?php } ?>
