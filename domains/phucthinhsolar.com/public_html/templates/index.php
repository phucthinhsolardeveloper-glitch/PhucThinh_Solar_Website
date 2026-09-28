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
        <div class="wap-hearder pts-header-shell-v4">
            <?php include TEMPLATE.LAYOUT."header.php"; ?>
            <?php include TEMPLATE.LAYOUT."mmenu.php"; ?>
        </div>

        <?php /* Hero V4 hiện là phần trình bày được duyệt theo ADR 0006. Các
                 trang con vẫn giữ breadcrumb dùng chung. */ ?>
        <?php if($source != 'index'){ ?>
        <div class="wap-slider pts-breadcrumb-shell">
            <?php include TEMPLATE.LAYOUT."breadcrumb.php"; ?>
        </div>
        <?php } ?>

        <div class="wap-main <?=($template=="news/news_detail" || $template=="news/project_detail")?'news_detail':''?> <?= ($source == 'user' || $source == 'order') ? 'user-pages': ''?> ">
            <div class="wrap-main  <?=($source == 'index' || $source == 'about' || $template == 'news/service_list' || $template == 'news/service' || $template == 'news/project' || $template == 'news/news' || $template == 'daily/daily' || $template == 'contact/contact') ? 'wrap-home' : (($template == 'product/product') ? 'wrap-content mt-0 mb-5' : 'wrap-content mt-5 mb-5')?> <?=($template == 'product/product') ? 'product-category-page' : ''?> <?=in_array($template, array('news/service_list', 'news/service', 'news/service_detail'), true) ? 'solution-page' : ''?> <?=($template == 'news/service_detail') ? 'solution-detail-page' : ''?> <?=($template == 'news/project_detail') ? 'project-detail-page' : ''?> w-clear">
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
