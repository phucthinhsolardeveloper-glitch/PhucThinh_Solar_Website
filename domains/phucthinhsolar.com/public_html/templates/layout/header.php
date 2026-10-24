<?php
$headerEscape = static function($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$headerPhone = !empty($optsetting['hotline']) ? $func->parsePhone($optsetting['hotline']) : '';
$headerCartCount = !empty($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$headerSalesLabel = publicProductPricesVisible() ? 'Mua hàng' : 'Tư vấn';
$headerQuickCategories = !empty($splist) ? array_slice($splist, 0, 5) : array();
$headerSearchKeyword = (isset($_GET['keyword']) && is_string($_GET['keyword'])) ? trim($_GET['keyword']) : '';
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
                <input type="search" name="keyword-res" id="keyword-res" value="<?=$headerEscape($headerSearchKeyword)?>" placeholder="Bạn cần tìm sản phẩm hoặc giải pháp nào?" autocomplete="off">
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

<div class="pts-catalog-overlay" id="ptsCatalogOverlay" aria-hidden="true" inert hidden>
    <button class="pts-catalog-scrim" type="button" aria-label="Đóng danh mục sản phẩm"></button>
    <section class="pts-catalog-panel" role="dialog" aria-modal="true" aria-labelledby="ptsCatalogTitle">
        <div class="pts-catalog-heading">
            <div><h2 id="ptsCatalogTitle">Khám phá sản phẩm</h2><p>Chọn thiết bị phù hợp với nhu cầu của bạn</p></div>
            <form class="pts-catalog-search" role="search" onsubmit="onSearch('ptsCatalogKeyword'); return false;">
                <label class="hdr-sr-only" for="ptsCatalogKeyword">Tìm sản phẩm, mã sản phẩm</label>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="ptsCatalogKeyword" value="<?=$headerEscape($headerSearchKeyword)?>" placeholder="Tìm sản phẩm, mã sản phẩm..." autocomplete="off">
                <button type="button" class="pts-catalog-clear" id="ptsCatalogKeywordClear" aria-label="Xóa tìm kiếm danh mục" hidden><i class="fas fa-times" aria-hidden="true"></i></button>
            </form>
            <button class="pts-catalog-close" type="button" aria-label="Đóng danh mục sản phẩm"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <div class="pts-catalog-columns">
            <?php foreach($menuCatalogGroups as $menuCatalogGroupKey => $menuCatalogGroup) { if(!$menuCatalogGroup['items']) continue; ?>
            <section class="pts-catalog-group" aria-labelledby="ptsCatalogGroup-<?=$menuCatalogGroupKey?>">
                <h3 id="ptsCatalogGroup-<?=$menuCatalogGroupKey?>"><a class="pts-catalog-group-link" href="<?=$menuCatalogEscape($menuCatalogGroup['href'])?>"><span class="pts-catalog-group-icon"><?=$menuCatalogRenderIcon($menuCatalogGroup['icon'])?></span><span><?=$menuCatalogEscape($menuCatalogGroup['title'])?><?php if($menuCatalogGroup['subtitle'] !== '') { ?><small><?=$menuCatalogEscape($menuCatalogGroup['subtitle'])?></small><?php } ?></span></a></h3>
                <ul>
                    <?php foreach($menuCatalogGroup['items'] as $menuCatalogItem) { ?>
                    <li class="pts-catalog-list-item" data-search="<?=$menuCatalogEscape($menuCatalogItem['search'])?>">
                        <a class="pts-catalog-list-link" href="<?=$menuCatalogEscape($menuCatalogItem['href'])?>"><span class="pts-catalog-item-icon"><?=$menuCatalogRenderIcon($menuCatalogItem['icon'])?></span><span><?=$menuCatalogEscape($menuCatalogItem['label'])?></span><?php if($menuCatalogItem['children']) { ?><i class="fas fa-angle-right pts-catalog-row-arrow" aria-hidden="true"></i><?php } ?></a>
                        <?php if($menuCatalogItem['children']) { ?>
                        <ul class="pts-catalog-sublist" aria-label="Danh mục con: <?=$menuCatalogEscape($menuCatalogItem['label'])?>">
                            <?php foreach($menuCatalogItem['children'] as $menuCatalogChild) { ?>
                            <li><a href="<?=$menuCatalogEscape($menuCatalogChild['href'])?>"><?=$menuCatalogEscape($menuCatalogChild['label'])?></a></li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                    </li>
                    <?php } ?>
                </ul>
                <?php if($menuCatalogGroupKey === 'devices') { ?>
                <a class="pts-catalog-help-card" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal"><?=$menuCatalogRenderIcon('headset', 'pts-catalog-help-svg')?><span><strong>Cần hỗ trợ lựa chọn?</strong><small>Nhận tư vấn <i class="fas fa-arrow-right" aria-hidden="true"></i></small></span></a>
                <?php } ?>
            </section>
            <?php } ?>
        </div>
        <p class="pts-catalog-empty" id="ptsCatalogEmpty" role="status" aria-live="polite" hidden>Không tìm thấy danh mục phù hợp</p>
        <div class="pts-catalog-footer">
            <a href="/san-pham/">Xem tất cả sản phẩm <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            <a class="pts-catalog-footer-advice" href="#ptsContactModal" data-toggle="modal" data-target="#ptsContactModal">Tư vấn chọn sản phẩm <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    </section>
</div>
