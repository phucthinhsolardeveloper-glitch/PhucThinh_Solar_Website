<!DOCTYPE html>
<html lang="<?=$config['website']['lang-doc']?>">
<head>
    <?php include TEMPLATE.LAYOUT."head.php"; ?>
    <?php include TEMPLATE.LAYOUT."css.php"; ?>
</head>
<body class="home">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-57TJSRVM"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php
        include TEMPLATE.LAYOUT."seo.php";
    ?>
    <div class="wap-all-page">

        <?php include TEMPLATE.LAYOUT."topbar-banner.php"; ?>
        <div class="wap-hearder">
            <?php include TEMPLATE.LAYOUT."header.php"; ?>
            <?php include TEMPLATE.LAYOUT."mmenu.php"; ?>
        </div>

        <?php /* Hero trang chủ được tắt theo quyết định product-first 2026-09-15.
                 Không xoá template/dữ liệu Hero; các trang con vẫn giữ breadcrumb.
                 2026-09-15: bỏ luôn cờ loại trừ "daily" - viết từ hồi hero cũ của
                 trang Đại lý nằm sát ngay dưới header, không có chỗ cho breadcrumb.
                 Giao diện Đại lý mới (banner ảnh + nội dung bên dưới) đã có chỗ,
                 giữ loại trừ cũ làm trang này mất breadcrumb so với mọi trang khác. */ ?>
        <?php if($source != 'index'){ ?>
        <div class="wap-slider pts-breadcrumb-shell">
            <?php include TEMPLATE.LAYOUT."breadcrumb.php"; ?>
        </div>
        <?php } ?>

        <div class="wap-main <?=($template=="news/news_detail" || $template=="news/project_detail")?'news_detail':''?> <?= ($source == 'user' || $source == 'order') ? 'user-pages': ''?> ">
            <div class="wrap-main  <?=($source == 'index' || $source == 'about' || $template == 'news/service_list' || $template == 'news/service' || $template == 'news/project' || $template == 'news/news' || $template == 'daily/daily' || $template == 'contact/contact') ? 'wrap-home' : 'wrap-content mt-5 mb-5'?> w-clear">
                <?php include TEMPLATE.$template."_tpl.php"; ?>
            </div>
        </div>

        <div class="wap-footer">
            <?php include TEMPLATE.LAYOUT."footer.php"; ?>
        </div>

    </div>

    <?php
        
        include TEMPLATE.LAYOUT."modal.php";
        include TEMPLATE.LAYOUT."js.php";
        // if($deviceType=='mobile') include TEMPLATE.LAYOUT."phone.php";
    ?>
</body>
</html>
