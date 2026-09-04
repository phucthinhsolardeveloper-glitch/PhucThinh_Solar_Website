<div class="top-utility-bar">
	<div class="wrap-content d-flex align-items-center justify-content-between">
		<div class="top-utility-left">
			<?php if(!empty($optsetting['hotline'])) { ?>
			<span><i class="fas fa-phone-alt"></i> Hotline: <a href="tel:<?=$func->parsePhone($optsetting['hotline'])?>"><?=$optsetting['hotline']?></a></span>
			<?php } ?>
			<?php if(!empty($optsetting['email'])) { ?>
			<span class="hide-mobile"><i class="fas fa-envelope"></i> <?=$optsetting['email']?></span>
			<?php } ?>
			<?php if(!empty($optsetting['thoigianlamviec'])) { ?>
			<span class="hide-mobile"><i class="fas fa-clock"></i> Giờ làm việc: <?=$optsetting['thoigianlamviec']?></span>
			<?php } ?>
		</div>
		<div class="top-utility-right">
			<?php if(!empty($optsetting['fanpage'])) { ?>
			<a href="<?=$optsetting['fanpage']?>" target="_blank">Facebook</a>
			<?php } ?>
			<?php if(!empty($optsetting['zalo'])) { ?>
			<a href="https://zalo.me/<?=$func->parsePhone($optsetting['zalo'])?>" target="_blank">Zalo</a>
			<?php } ?>
		</div>
	</div>
</div>
<div class="header" id="headerscroll">
	<div class="header-bottom">
		<div class="wrap-content d-flex align-items-center justify-content-between">
			<div class="btn-mmenu">
				<div class="bg-mmenu">
					<a id="hamburger" href="#menu" title="Menu"><span></span></a>
				</div>
			</div>
			<a class="logo-header pts-brand" href="" aria-label="Phúc Thịnh Solar">
				<?=$func->getImage(['class' => '','size-error' => '108x63x2', 'upload' => UPLOAD_PHOTO_L, 'image' => $logo['photo'], 'alt' => $setting['name'.$lang]])?>
				<span class="pts-brand-copy pts-brand-text">
					<strong>PHÚC THỊNH</strong>
					<small>SOLAR</small>
				</span>
			</a>
			<div class="header-right-style">
				<?php include TEMPLATE.LAYOUT."menu.php"; ?>
				<div class="flex-search-lang">
					<?php /*
					<div class="langCon">
						<div class="execphpwidget">
							<div id="flags">            
								<a onclick="doGoogleLanguageTranslator('vi|vi'); return false;" title="Việt Nam" class="flag vi"><img src="assets/images/vi.png" border="0" alt="<?=$setting['name'.$lang]?>"></a>
								<a onclick="doGoogleLanguageTranslator('vi|en'); return false;" title="English" class="flag en"><img src="assets/images/en.png" border="0" alt="<?=$setting['name'.$lang]?>"></a> 
							</div>
							
						</div>
					</div>
					*/?>
					<div class="gtranslate_wrapper"></div>
					<?php if(!empty($optsetting['hotline'])) { ?>
					<a class="hdr-call" href="tel:<?=$func->parsePhone($optsetting['hotline'])?>" title="Gọi mua hàng">
						<span class="hdr-call-ic"><i class="fas fa-headset"></i></span>
						<span class="hdr-call-txt"><small>Gọi mua hàng</small><b><?=$optsetting['hotline']?></b></span>
					</a>
					<?php } ?>
					<a href="gio-hang/" class="icon-cart-header" title="Giỏ hàng" style="position:relative;display:inline-flex;align-items:center;margin-right:10px;">
						<span class="hdr-cart-txt">Giỏ hàng</span>
						<svg width="22px" height="22px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4h2l1 12h11l1.6-8H7" stroke="#0D0D0D" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.5" fill="#0D0D0D"/><circle cx="16" cy="20" r="1.5" fill="#0D0D0D"/></svg>
						<span class="count-cart" style="position:absolute;top:-6px;right:-8px;background:var(--color-red,#e53935);color:#fff;font-size:11px;font-weight:700;min-width:16px;height:16px;line-height:16px;border-radius:50%;text-align:center;padding:0 3px;"><?=(!empty($_SESSION['cart'])) ? count($_SESSION['cart']) : 0?></span>
					</a>
					<div class="search-res">
						<p class="icon-search transition">
							<svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 4a6 6 0 1 0 0 12 6 6 0 0 0 0-12zm-8 6a8 8 0 1 1 14.32 4.906l5.387 5.387a1 1 0 0 1-1.414 1.414l-5.387-5.387A8 8 0 0 1 2 10z" fill="#0D0D0D"></path></svg>
						</p>
						<div class="search-grid w-clear">
							<input type="text" name="keyword-res" id="keyword-res" placeholder="Tìm kiếm" onkeypress="doEnter(event,'keyword-res');">
							<p onclick="onSearch('keyword-res');">
								<svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 4a6 6 0 1 0 0 12 6 6 0 0 0 0-12zm-8 6a8 8 0 1 1 14.32 4.906l5.387 5.387a1 1 0 0 1-1.414 1.414l-5.387-5.387A8 8 0 0 1 2 10z" fill="#fff"></path></svg>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
		$quickPowerGroups = array('under-60' => 'Dưới 60W', '60-200' => '60–200W', 'over-200' => 'Trên 200W');
	?>
	<?php if(!empty($congSuatList)) { ?>
	<div class="header-quickfilter">
		<div class="wrap-content">
			<span class="hqf-label" aria-label="Chọn theo công suất"><i class="fas fa-bolt"></i></span>
			<div class="hqf-list">
				<?php foreach($quickPowerGroups as $powerKey => $powerLabel) { ?>
				<a href="danh-muc-san-pham/den-nlmt/?is_congsuat=<?=$powerKey?>"><?=$powerLabel?></a>
				<?php } ?>
				<a class="hqf-all" href="danh-muc-san-pham/den-nlmt/">Xem tất cả <i class="fas fa-angle-right"></i></a>
			</div>
		</div>
	</div>
	<?php } ?>
</div>
