<?php
$headerEscape = static function($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$headerPhone = !empty($optsetting['hotline']) ? $func->parsePhone($optsetting['hotline']) : '';
$headerCartCount = !empty($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$headerSalesLabel = publicProductPricesVisible() ? 'Gọi mua hàng' : 'Tư vấn sản phẩm';
$headerPromise = !empty($optsetting['header_promise']) ? trim($optsetting['header_promise']) : 'Giải pháp năng lượng tối ưu cho mọi công trình';
$headerPromise = mb_substr($headerPromise, 0, 100, 'UTF-8');
$headerQuickCategories = !empty($splist) ? array_slice($splist, 0, 5) : array();
?>
<div class="top-utility-bar">
	<div class="wrap-content d-flex align-items-center justify-content-between">
		<div class="top-utility-left">
			<span class="top-utility-promise"><i class="fas fa-sun" aria-hidden="true"></i> <?=$headerEscape($headerPromise)?></span>
			<?php if(!empty($optsetting['thoigianlamviec'])) { ?>
			<span class="hide-mobile"><i class="far fa-clock" aria-hidden="true"></i> <?=$headerEscape($optsetting['thoigianlamviec'])?></span>
			<?php } ?>
		</div>
		<div class="top-utility-right">
			<?php if(!empty($optsetting['email'])) { ?>
			<a class="top-utility-email" href="mailto:<?=$headerEscape($optsetting['email'])?>"><i class="far fa-envelope" aria-hidden="true"></i> <?=$headerEscape($optsetting['email'])?></a>
			<?php } ?>
			<?php if(!empty($optsetting['fanpage'])) { ?>
			<a href="<?=$headerEscape($optsetting['fanpage'])?>" target="_blank" rel="noopener noreferrer">Facebook</a>
			<?php } ?>
			<?php if(!empty($optsetting['zalo'])) { ?>
			<a href="https://zalo.me/<?=$headerEscape($func->parsePhone($optsetting['zalo']))?>" target="_blank" rel="noopener noreferrer">Zalo</a>
			<?php } ?>
		</div>
	</div>
</div>

<header class="header pts-header-v3" id="headerscroll">
	<div class="header-bottom pts-header-primary">
		<div class="wrap-content">
			<div class="btn-mmenu">
				<div class="bg-mmenu">
					<a id="hamburger" href="#menu" title="Mở danh mục điều hướng" aria-label="Mở danh mục điều hướng"><span></span></a>
				</div>
			</div>

			<a class="logo-header pts-brand pts-brand-wordmark" href="" aria-label="Phúc Thịnh Solar - Trang chủ">
				<?php /* Logo là ảnh LCP, nằm ngay đầu viewport - không lazy-load. */ ?>
				<?=$func->getImage(['class' => '', 'noNativeLazy' => true, 'sizes' => '420x90x3', 'upload' => UPLOAD_PHOTO_L, 'image' => $logo['photo'], 'alt' => $setting['name'.$lang]])?>
			</a>

			<div class="hdr-search-zone">
				<form class="hdr-search-form" role="search" onsubmit="onSearch('keyword-res'); return false;">
					<label class="hdr-sr-only" for="keyword-res">Tìm kiếm sản phẩm</label>
					<input type="search" name="keyword-res" id="keyword-res" placeholder="Bạn cần tìm sản phẩm hoặc giải pháp nào?" autocomplete="off">
					<button type="submit" class="hdr-search-submit" aria-label="Tìm kiếm"><i class="fas fa-search" aria-hidden="true"></i></button>
				</form>
				<?php if($headerQuickCategories) { ?>
				<nav class="hdr-quick-links" aria-label="Danh mục tìm nhanh">
					<span>Tìm nhanh:</span>
					<?php foreach($headerQuickCategories as $headerCategory) { ?>
					<a href="danh-muc-san-pham/<?=$headerEscape($headerCategory[$sluglang])?>/"><?=$headerEscape($headerCategory['name'.$lang])?></a>
					<?php } ?>
				</nav>
				<?php } ?>
			</div>

			<div class="header-right-style">
				<div class="gtranslate_wrapper"></div>
				<?php if($headerPhone !== '') { ?>
				<a class="hdr-call" href="tel:<?=$headerEscape($headerPhone)?>" title="<?=$headerEscape($headerSalesLabel)?>">
					<span class="hdr-call-ic"><i class="fas fa-headset" aria-hidden="true"></i></span>
					<span class="hdr-call-txt"><small><?=$headerEscape($headerSalesLabel)?></small><b><?=$headerEscape($optsetting['hotline'])?></b></span>
				</a>
				<?php } ?>

				<?php if(publicProductPricesVisible()) { ?>
				<a href="gio-hang/" class="icon-cart-header" title="Xem giỏ hàng" aria-label="Giỏ hàng có <?=$headerCartCount?> sản phẩm">
					<span class="hdr-cart-icon">
						<svg width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 4h2l1 12h11l1.6-8H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.5" fill="currentColor"/><circle cx="16" cy="20" r="1.5" fill="currentColor"/></svg>
						<span class="hdr-cart-badge count-cart"><?=$headerCartCount?></span>
					</span>
					<span class="hdr-cart-copy"><small>Giỏ hàng</small><b><span class="count-cart"><?=$headerCartCount?></span> sản phẩm</b></span>
				</a>
				<?php } ?>

				<div class="search-res hdr-mobile-search">
					<button type="button" class="icon-search transition" aria-label="Mở tìm kiếm"><i class="fas fa-search" aria-hidden="true"></i></button>
					<div class="search-grid w-clear">
						<input type="search" name="keyword-res-mobile" id="keyword-res-mobile" placeholder="Tìm kiếm" onkeypress="doEnter(event,'keyword-res-mobile');">
						<button type="button" onclick="onSearch('keyword-res-mobile');" aria-label="Tìm kiếm"><i class="fas fa-search" aria-hidden="true"></i></button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="pts-header-nav">
		<div class="wrap-content">
			<?php include TEMPLATE.LAYOUT."menu.php"; ?>
			<a class="hdr-warranty" href="ho-tro/chinh-sach-bao-hanh-bao-tri-va-ho-tro-ky-thuat/">
				<i class="fas fa-shield-alt" aria-hidden="true"></i><span>Chính sách bảo hành</span>
			</a>
		</div>
	</div>
</header>
