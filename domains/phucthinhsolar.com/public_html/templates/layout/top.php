<div class="top">
	<div class="header-top">
		<div class="wrap-content d-flex align-items-center justify-content-between">
			<p class="info-header"><?=$slogan['name'.$lang]?></p>
			<?php if ($setting_sort['email'] == 1){ ?>
				<p class="info-header"><i class="fas fa-envelope"></i>Email: <?=$optsetting['email']?></p>
			<?php } ?>
			
			<?php if ($setting_sort['hotline'] == 1){ ?>
				<p class="info-header"><i class="fas fa-phone-square-alt"></i>Hotline: <?=$func->formatPhone($optsetting['hotline'])?></p>
			<?php } ?>
			
			<ul class="social social-header list-unstyled p-0 m-0">
				<?php foreach($social as $k => $v) { ?>
					<li class="d-inline-block align-top mr-1">
						<a href="<?=$v['link']?>" target="_blank">
							<?=$func->getImage(['sizes' => '30x30x2', 'upload' => UPLOAD_PHOTO_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
						</a>
					</li>
				<?php } ?>
			</ul>
			<div class="lang-header">
	            <a href="ngon-ngu/vi/"><?=$func->getImage(['size-error' => '35x25x1', 'upload' => 'assets/images/', 'image' => 'vi.jpg', 'alt' => 'Tiếng Việt'])?></a>
	            <a href="ngon-ngu/en/"><?=$func->getImage(['size-error' => '35x25x1', 'upload' => 'assets/images/', 'image' => 'en.jpg', 'alt' => 'Tiếng Anh'])?></a>
	        </div>
	        <?php if(array_key_exists($loginMember, $_SESSION) && $_SESSION[$loginMember]['active'] == true) { ?>
	        	<div class="user-header">
		        	<a href="account/thong-tin">
		        		<span>Hi, <?=$_SESSION[$loginMember]['username']?></span>
		        	</a>
		        	<a href="account/dang-xuat">
		        		<i class="fas fa-sign-out-alt"></i>
		        		<span><?=dangxuat?></span>
		        	</a>
		        </div>
	        <?php } else { ?>
	        	<div class="user-header">
		        	<a href="account/dang-nhap">
		        		<i class="fas fa-sign-in-alt"></i>
		        		<span><?=dangnhap?></span>
		        	</a>
		        	<a href="account/dang-ky">
		        		<i class="fas fa-user-plus"></i>
		        		<span><?=dangky?></span>
		        	</a>
		        </div>
	        <?php } ?>
		</div>
	</div>
</div>