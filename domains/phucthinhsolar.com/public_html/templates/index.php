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

        <div class="wap-slider<?=($source != 'index' && $source != 'daily') ? ' pts-breadcrumb-shell' : ''?>">
            <?php 
                if($source == 'index') include TEMPLATE.LAYOUT."slide.php";
                if($source != 'index' && $source != 'daily') include TEMPLATE.LAYOUT."breadcrumb.php"; 
            ?>
        </div>

        <div class="wap-main <?=($template=="news/news_detail" || $template=="news/project_detail")?'news_detail':''?> <?= ($source == 'user' || $source == 'order') ? 'user-pages': ''?> ">
            <div class="wrap-main  <?=($source == 'index' || $source == 'about' || $template == 'news/service_list' || $template == 'news/service' || $template == 'news/project_list' || $template == 'news/project' || $template == 'news/news' || $template == 'daily/daily' || $template == 'contact/contact') ? 'wrap-home' : 'wrap-content mt-5 mb-5'?> w-clear">
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
