<!-- Js Config -->
<script type="text/javascript">
    var GA_FRAMEWORK = GA_FRAMEWORK || {};
    var CONFIG_BASE = '<?= $configBase ?>';
    var ASSET = '<?= ASSET ?>';
    var WEBSITE_NAME = '<?= (!empty($setting['name' . $lang])) ? addslashes($setting['name' . $lang]) : '' ?>';
    var TIMENOW = '<?= date("d/m/Y", time()) ?>';
    var SHIP_CART = <?= (!empty($config['order']['ship'])) ? 'true' : 'false' ?>;
    var RECAPTCHA_ACTIVE = <?= (!empty($config['googleAPI']['recaptcha']['active'])) ? 'true' : 'false' ?>;
    var RECAPTCHA_SITEKEY = '<?= $config['googleAPI']['recaptcha']['sitekey'] ?>';
    var GOTOP = ASSET + 'assets/images/top.png';
    var LANG = {
        'no_keywords': '<?= chuanhaptukhoatimkiem ?>',
        'delete_product_from_cart': '<?= banmuonxoasanphamnay ?>',
        'no_products_in_cart': '<?= khongtontaisanphamtronggiohang ?>',
        'ward': '<?= phuongxa ?>',
        'back_to_home': '<?= vetrangchu ?>',
    };
</script>

<!-- Js Files -->
<script type='text/javascript' src='//cdn.jsdelivr.net/npm/uikit@3.7.3/dist/js/uikit.min.js?ver=5.8.2' id='uikit-js-js'></script>
<script type='text/javascript' src='//cdn.jsdelivr.net/npm/uikit@3.7.3/dist/js/uikit-icons.min.js?ver=5.8.2' id='uikit-icons-js-js'></script>
<?php
$js->set("wp-includes/js/dist/vendor/regenerator-runtime.minb36a.js");
$js->set("wp-includes/js/dist/vendor/wp-polyfill.min2c7c.js");
// $js->set("wp-content/plugins/contact-form-7/includes/js/index5697.js");
// $js->set("wp-content/plugins/woocommerce/assets/js/jquery-blockui/jquery.blockUI.min44fd.js");
// $js->set("wp-content/plugins/woocommerce/assets/js/js-cookie/js.cookie.min6b25.js");
// $js->set("wp-content/plugins/woocommerce/assets/js/frontend/woocommerce.min4698.js");
// $js->set("wp-content/plugins/woocommerce/assets/js/frontend/cart-fragments.min4698.js");
$js->set("wp-includes/js/underscore.min0028.js");
$js->set("wp-content/themes/local/js/bootstrap.bundle.min080f.js");
$js->set("wp-content/themes/local/js/bootstrap.min080f.js");
$js->set("wp-content/themes/local/js/labory080f.js");
$js->set("wp-content/themes/local/js/notice080f.js");
$js->set("wp-content/themes/local/js/scrolly.min080f.js");
$js->set("wp-content/themes/local/js/jquery-lockfixed/jquery.lockfixed080f.js");
$js->set("wp-content/themes/local/js/mainV2080f.js");
$js->set("wp-content/themes/local/js/aos080f.js");
$js->set("wp-content/themes/local/js/main080f.js");
$js->set("wp-content/themes/local/js/swiper.min080f.js");
$js->set("wp-content/themes/local/js/home080f.js");
$js->set("wp-content/themes/local/js/drift-master/dist/Drift.min080f.js");
$js->set("wp-content/themes/local/js/luminous-master/dist/Luminous.min080f.js");
// $js->set("wp-content/themes/local/js/ajax9a08.js");
$js->set("wp-content/plugins/variation-swatches-for-woocommerce/assets/js/frontend5b7f.js");
$js->set("wp-includes/js/wp-embed.min080f.js");
$js->set("js/lazyload.min.js");
// Native browser scrolling is used. The legacy wheel smoother cached page heights
// and could stop before the footer after lazy-loaded content changed the layout.
$js->set("bootstrap/bootstrap.js");
$js->set("js/jssor.slider.min.js");
// $js->set("js/wow.min.js");
$js->set("confirm/confirm.js");
$js->set("holdon/HoldOn.js");
$js->set("mmenu/mmenu.js");
// $js->set("easyticker/easy-ticker.js");
// $js->set("fotorama/fotorama.js");
$js->set("owlcarousel2/owl.carousel.js");
if($isProductDetail) $js->set("magiczoomplus/magiczoomplus.js");
$js->set("slick/slick.js");
$js->set("fancybox3/jquery.fancybox.js");
if($usesPhotobox) $js->set("photobox/photobox.js");
$js->set("simplenotify/simple-notify.js");
if($isProductDetail) $js->set("fileuploader/jquery.fileuploader.min.js");
// $js->set("datetimepicker/php-date-formatter.min.js");
// $js->set("datetimepicker/jquery.mousewheel.js");
// $js->set("datetimepicker/jquery.datetimepicker.js");
$js->set("js/functions.js");
if($source != 'index'){
    $js->set("js/toc.js");
}
if($isProductDetail) $js->set("comment/comment.js");
$js->set("js/share.js");
$js->set("js/apps.js");
echo $js->get();
?>
<script>
$('.view_maps').click(function() {
    var map = $(this).data('map');
    $('.view_maps').removeClass('active');
    $(this).addClass('active');
    $('#maps').html(map);
    $('html, body').animate({
        scrollTop: $("#maps").offset().top
    }, 500);
});
</script>

<link rel='stylesheet' id='pzf-style-css' href='assets/wp-content/themes/local/css/stylephone3781.css?ver=6.2.2'>
<?php if (!isGoogleSpeed()) { ?>
    <div class="progress-wrap cursor-pointer active-progress">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"
                style="transition: stroke-dashoffset 10ms linear; stroke-dasharray: 307.919, 307.919; stroke-dashoffset: 217.576;">
            </path>
        </svg>
    </div>

    <script>
        if (isExist($(".Module-327"))) {
            $(function() {
                let p = $(".Module-327").first();
                let position = p.position();
                console.log(position.top);
                $(window).scroll(function(event) {
                    let scroll = $(window).scrollTop();
                    if (scroll >= position.top - 1000) {
                        setTimeout(function() {
                            $('.so').each(function() {
                                $(this).prop('Counter', 0).animate({
                                    Counter: $(this).data('count')
                                }, {
                                    duration: 3000,
                                    easing: 'swing',
                                    step: function(now) {
                                        // Thêm dấu phẩy phân cách
                                        $(this).text(Math.ceil(now).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
                                    }
                                });
                            });
                        }, 700);
                    }
                });
            });
        }

    </script>

    <script>
        window.gtranslateSettings = {
            "default_language": "vi",
            "detect_browser_language": true,
            "native_language_names": true,
            "languages": ["vi", "en", "zh-CN", "ko", "ja"],
            "wrapper_selector": ".gtranslate_wrapper",
            "flag_size": 24
        }
    </script>
    <script src="https://cdn.gtranslate.net/widgets/latest/popup.js" defer=""></script>
    
<?php } ?>
<script>
    if (isExist($("#jssor_1"))) {
        $(document).ready(function($) {
            var jssor_1_options = {
                $AutoPlay: 1,
                $SlideDuration: 800,
                $SlideEasing: $Jease$.$OutQuint,
                $ArrowNavigatorOptions: {
                    $Class: $JssorArrowNavigator$
                },
                $BulletNavigatorOptions: {
                    $Class: $JssorBulletNavigator$
                }
            };
            var jssor_1_slider = new $JssorSlider$("jssor_1", jssor_1_options);
            /*#region responsive code begin*/
            var MAX_WIDTH = 3000;

            function ScaleSlider() {
                var containerElement = jssor_1_slider.$Elmt.parentNode;
                var containerWidth = containerElement.clientWidth;
                if (containerWidth) {
                    var expectedWidth = Math.min(MAX_WIDTH || containerWidth, containerWidth);
                    jssor_1_slider.$ScaleWidth(expectedWidth);
                } else {
                    window.setTimeout(ScaleSlider, 30);
                }
            }
            ScaleSlider();
            $(window).bind("load", ScaleSlider);
            $(window).bind("resize", ScaleSlider);
            $(window).bind("orientationchange", ScaleSlider);
            /*#endregion responsive code end*/
        });
    }
</script>
<script>
    $('.trigger-fixed-list').on('click', function() {
        $('.fixed-social-list').toggleClass('active');
    });
    $('.tab-project').on('click', function() {
        // Đợi tab hiển thị xong
        setTimeout(function() {
            $('.slider_6_slick').slick('setPosition');
        }, 100);
    });
</script>

<?php if (!isGoogleSpeed()) { ?>
    <?php if (!empty($config['googleAPI']['recaptcha']['active'])) { ?>
        <!-- Js Google Recaptcha V3 -->
        <script src="https://www.google.com/recaptcha/api.js?render=<?= $config['googleAPI']['recaptcha']['sitekey'] ?>"></script>
        <script type="text/javascript">
            grecaptcha.ready(function() {
                /* Newsletter */
                generateCaptcha('Newsletter', 'recaptchaResponseNewsletter');

                <?php if ($source == 'contact') { ?>
                    /* Contact */
                    generateCaptcha('contact', 'recaptchaResponseContact');
                <?php } ?>
                <?php if ($source == 'daily') { ?>
                    /* Contact */
                    generateCaptcha('Newsletter2', 'recaptchaResponseNewsletter2');
                <?php } ?>
            });
        </script>
    <?php } ?>

    <?php if (!empty($config['oneSignal']['active'])) { ?>
        <!-- Js OneSignal -->
        <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
        <script type="text/javascript">
            var OneSignal = window.OneSignal || [];
            OneSignal.push(function() {
                OneSignal.init({
                    appId: "<?= $config['oneSignal']['id'] ?>"
                });
            });
        </script>
    <?php } ?>

    <!-- Js Structdata -->
    <?php include TEMPLATE . LAYOUT . "strucdata.php"; ?>

    <!-- Js Addons -->
    <?= $addons->set('script-main', 'script-main', 2); ?>
    <?= $addons->get(); ?>

    <!-- Js Body -->
    <?= htmlspecialchars_decode($setting['bodyjs']) ?>
<?php } ?>
