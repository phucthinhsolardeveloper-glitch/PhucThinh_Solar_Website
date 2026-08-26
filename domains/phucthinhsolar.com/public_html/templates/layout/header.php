<div class="header" id="headerscroll">
	<div class="header-bottom">
		<div class="wrap-content d-flex align-items-center justify-content-between">
			<div class="btn-mmenu">
				<div class="bg-mmenu">
					<a id="hamburger" href="#menu" title="Menu"><span></span></a>
				</div>
			</div>
			<a class="logo-header" href="">
				<?=$func->getImage(['class' => '','size-error' => '108x63x2', 'upload' => UPLOAD_PHOTO_L, 'image' => $logo['photo'], 'alt' => $setting['name'.$lang]])?>
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
</div>