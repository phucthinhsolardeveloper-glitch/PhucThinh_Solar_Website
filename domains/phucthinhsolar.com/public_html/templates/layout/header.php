<?php
$headerEscape = static function($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$headerPhone = !empty($optsetting['hotline']) ? $func->parsePhone($optsetting['hotline']) : '';
$headerCartCount = !empty($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$headerSalesLabel = publicProductPricesVisible() ? 'Mua hàng' : 'Tư vấn';
$headerQuickCategories = !empty($splist) ? array_slice($splist, 0, 5) : array();
?>

<header class="header pts-header-v3 pts-header-v4" id="headerscroll">
    <div class="header-bottom pts-header-primary">
        <div class="wrap-content pts-header-row">
            <div class="btn-mmenu">
                <div class="bg-mmenu">
                    <a id="hamburger" href="#menu" title="Mở danh mục điều hướng" aria-label="Mở danh mục điều hướng"><span></span></a>
                </div>
            </div>

            <a class="logo-header pts-brand pts-brand-wordmark" href="" aria-label="Phúc Thịnh Solar - Trang chủ">
                <?=$func->getImage(['class' => '', 'noNativeLazy' => true, 'sizes' => '520x112x3', 'upload' => UPLOAD_PHOTO_L, 'image' => $logo['photo'], 'alt' => $setting['name'.$lang]])?>
            </a>

            <div class="pts-header-nav" aria-label="Điều hướng chính">
                <?php include TEMPLATE.LAYOUT."menu.php"; ?>
            </div>

            <div class="header-right-style pts-header-actions">
                <div class="gtranslate_wrapper" aria-label="Chọn ngôn ngữ"></div>

                <button class="pts-header-action pts-header-search-toggle" type="button" aria-expanded="false" aria-controls="ptsHeaderSearch" aria-label="Mở tìm kiếm">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </button>

                <?php if($headerPhone !== '') { ?>
                <a class="hdr-call" href="tel:<?=$headerEscape($headerPhone)?>" title="<?=$headerEscape($headerSalesLabel)?>">
                    <span class="hdr-call-ic"><i class="fas fa-phone-alt" aria-hidden="true"></i></span>
                    <span class="hdr-call-txt"><small><?=$headerEscape($headerSalesLabel)?></small><b><?=$headerEscape($optsetting['hotline'])?></b></span>
                </a>
                <?php } ?>

                <?php if(publicProductPricesVisible()) { ?>
                <a href="gio-hang/" class="icon-cart-header pts-header-action" title="Xem giỏ hàng" aria-label="Giỏ hàng có <?=$headerCartCount?> sản phẩm">
                    <span class="hdr-cart-icon">
                        <svg width="23" height="23" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 4h2l1 12h11l1.6-8H7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.5" fill="currentColor"/><circle cx="16" cy="20" r="1.5" fill="currentColor"/></svg>
                        <span class="hdr-cart-badge count-cart"><?=$headerCartCount?></span>
                    </span>
                    <span class="hdr-cart-copy"><small>Giỏ hàng</small><b><span class="count-cart"><?=$headerCartCount?></span> sản phẩm</b></span>
                </a>
                <?php } ?>

                <a class="pts-header-consult" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal">Nhận tư vấn</a>
            </div>
        </div>
    </div>

    <div class="hdr-search-zone" id="ptsHeaderSearch" hidden>
        <div class="wrap-content">
            <form class="hdr-search-form" role="search" onsubmit="onSearch('keyword-res'); return false;">
                <label class="hdr-sr-only" for="keyword-res">Tìm kiếm sản phẩm hoặc giải pháp</label>
                <input type="search" name="keyword-res" id="keyword-res" placeholder="Bạn cần tìm sản phẩm hoặc giải pháp nào?" autocomplete="off">
                <button type="submit" class="hdr-search-submit" aria-label="Tìm kiếm"><i class="fas fa-search" aria-hidden="true"></i></button>
            </form>
            <?php if($headerQuickCategories) { ?>
            <nav class="hdr-quick-links" aria-label="Danh mục tìm nhanh">
                <span>Tìm nhanh:</span>
                <?php foreach($headerQuickCategories as $headerCategory) { ?>
                <a href="/danh-muc-san-pham/<?=$headerEscape($headerCategory[$sluglang])?>/"><?=$headerEscape($headerCategory['name'.$lang])?></a>
                <?php } ?>
            </nav>
            <?php } ?>
        </div>
    </div>
</header>
