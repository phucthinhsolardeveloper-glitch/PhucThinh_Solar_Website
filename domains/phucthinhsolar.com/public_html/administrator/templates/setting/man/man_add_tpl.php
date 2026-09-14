<?php
	$linkSave = "index.php?com=setting&act=save";
	$options = (isset($item['options']) && $item['options'] != '') ? json_decode($item['options'],true) : null;
?>
<!-- Content Header -->
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Thông tin công ty</li>
			</ol>
		</div>
	</div>
</section>

<!-- Main content -->
<section class="content">
	<form class="validation-form" novalidate method="post" action="<?=$linkSave?>" enctype="multipart/form-data">
        <?=adminCsrfField()?>
		<div class="card-footer text-sm sticky-top">
			<button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu</button>
			<button type="reset" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-redo mr-2"></i>Làm lại</button>
		</div>

		<?=$flash->getMessages('admin')?>

		<?php if(isset($config['website']['debug-developer']) && $config['website']['debug-developer'] == true) { ?>
			<div class="card card-primary card-outline text-sm">
				<div class="card-header">
					<h3 class="card-title">Database</h3>
				</div>
				<div class="card-body">
					<div class="form-group">
						<button type="button" class="btn btn-database bg-gradient-warning" data-action="ANALYZE"><i class="fas fa-database mr-2"></i>Analyze</button>
						<button type="button" class="btn btn-database bg-gradient-success" data-action="OPTIMIZE"><i class="fas fa-magic mr-2"></i>Optimize</button>
						<button type="button" class="btn btn-database bg-gradient-info" data-action="CHECK"><i class="fas fa-tasks mr-2"></i>Check</button>
						<button type="button" class="btn btn-database bg-gradient-primary" data-action="REPAIR"><i class="fas fa-tools mr-2"></i>Repair</button>
					</div>
					<div class="form-group result-database row"></div>
				</div>
			</div>
		<?php } ?>
		<?php
			$mailConfig = isset($config['mail']) && is_array($config['mail']) ? $config['mail'] : array();
			$mailConfigured = empty(Email::validateConfig($mailConfig));
			$mailView = function($key) use ($mailConfig) {
				return htmlspecialchars(isset($mailConfig[$key]) && trim((string)$mailConfig[$key]) !== '' ? (string)$mailConfig[$key] : '—', ENT_QUOTES, 'UTF-8');
			};
		?>
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title">Hệ thống email (chỉ đọc)</h3>
			</div>
			<div class="card-body">
				<div class="alert <?=$mailConfigured ? 'alert-success' : 'alert-warning'?>">
					<strong><?=$mailConfigured ? 'SMTP đã đủ cấu hình.' : 'SMTP chưa đủ cấu hình production.'?></strong>
					Password mailbox được quản lý ngoài Git/webroot và không hiển thị hoặc cập nhật tại Admin.
				</div>
				<div class="row mb-3">
					<div class="col-md-4"><strong>SMTP:</strong> <?=$mailView('host')?>:<?=$mailView('port')?> / <?=$mailView('encryption')?></div>
					<div class="col-md-4"><strong>From:</strong> <?=$mailView('from')?></div>
					<div class="col-md-4"><strong>Nhận thông báo:</strong> <?=$mailView('admin_to')?></div>
				</div>
				<div class="table-responsive">
					<table class="table table-sm table-bordered mb-0">
						<thead><tr><th>Workflow</th><th>Nội dung/khung mẫu</th><th>Người nhận</th></tr></thead>
						<tbody>
							<tr><td>Liên hệ</td><td><code>contact/admin</code>, <code>contact/customer</code></td><td>Công ty + xác nhận khách; Reply-To khách</td></tr>
							<tr><td>Tư vấn / Đại lý</td><td>Bảng tóm tắt dữ liệu form đã validate</td><td>Công ty; Reply-To khách</td></tr>
							<tr><td>Đơn hàng</td><td><code>order/admin</code>, <code>order/customer</code></td><td>Công ty + xác nhận khách; Reply-To khách</td></tr>
							<tr><td>Thành viên (legacy, route chưa public)</td><td><code>member/active</code>, <code>member/forgot-password</code></td><td>Email thành viên</td></tr>
							<tr><td>Newsletter Admin</td><td>Admin nhập tiêu đề/nội dung/file khi gửi</td><td>Danh sách đã chọn qua BCC</td></tr>
						</tbody>
					</table>
				</div>
				<small class="text-muted d-block mt-2">Các template tự động đang được version hóa trong source; Admin chưa chỉnh sửa trực tiếp.</small>
			</div>
		</div>
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title">Hero trang chủ</h3>
			</div>
			<div class="card-body">
				<?php $heroModeIsText = isset($options['home_hero_mode']) && (string)$options['home_hero_mode'] === 'text'; ?>
				<div class="form-group mb-2">
					<div class="custom-control custom-radio mb-2">
						<input class="custom-control-input" type="radio" id="home-hero-mode-slider" name="data[options][home_hero_mode]" value="slider" <?=!$heroModeIsText ? 'checked' : ''?>>
						<label for="home-hero-mode-slider" class="custom-control-label font-weight-normal"><strong>Banner slide ảnh/video</strong> — quản lý ảnh, thứ tự, link riêng từng slide ở mục <a href="index.php?com=photo&act=man_photo&type=slide">Slideshow</a>. Chưa có slide nào thì tự hiện lại bản chữ.</label>
					</div>
					<div class="custom-control custom-radio">
						<input class="custom-control-input" type="radio" id="home-hero-mode-text" name="data[options][home_hero_mode]" value="text" <?=$heroModeIsText ? 'checked' : ''?>>
						<label for="home-hero-mode-text" class="custom-control-label font-weight-normal"><strong>Hero chữ (mặc định cũ)</strong> — tiêu đề "Năng lượng mặt trời...", số liệu tin cậy, chứng nhận uỷ quyền.</label>
					</div>
					<small class="text-muted d-block mt-2">Muốn dùng ảnh thật làm nền cho "Hero chữ" thay vì nền màu, tải ảnh ở mục <a href="index.php?com=photo&act=man_photo&type=home-hero-bg">Ảnh nền Hero trang chủ</a> — chữ, số liệu và hiệu ứng vẫn giữ nguyên, hiện trên ảnh. Tải nhiều ảnh thì tự đổi vòng (crossfade), 1 ảnh thì đứng yên.</small>
					<small class="text-muted d-block mt-2">Từ 2026-09-12, chọn "Hero chữ" thực chất hiện <strong>3 bản</strong> khách tự bấm nút tròn để chuyển qua lại (không lưu được vào đây): bản gốc có cột chứng nhận, bản có form tư vấn nổi (mặc định), và 1 carousel 4 slide biên tập/kiểu ảnh riêng. Nội dung carousel hiện chưa có màn Admin (đang ở <code>libraries/home_hero_carousel.php</code>), chỉ sửa được qua code.</small>
				</div>
			</div>
		</div>
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title">Hiển thị giá sản phẩm</h3>
			</div>
			<div class="card-body">
				<?php $publicProductPrices = isset($options['public_product_prices']) && (string)$options['public_product_prices'] === '1'; ?>
				<div class="form-group mb-2">
					<div class="custom-control custom-radio mb-2">
						<input class="custom-control-input" type="radio" id="public-product-prices-quote" name="data[options][public_product_prices]" value="0" <?=!$publicProductPrices ? 'checked' : ''?>>
						<label for="public-product-prices-quote" class="custom-control-label font-weight-normal"><strong>Liên hệ báo giá</strong> — ẩn giá, bộ lọc giá và thao tác mua hàng trên website</label>
					</div>
					<div class="custom-control custom-radio">
						<input class="custom-control-input" type="radio" id="public-product-prices-show" name="data[options][public_product_prices]" value="1" <?=$publicProductPrices ? 'checked' : ''?>>
						<label for="public-product-prices-show" class="custom-control-label font-weight-normal"><strong>Hiển thị giá</strong> — công khai giá và bật lại giỏ hàng, đặt hàng</label>
					</div>
				</div>
				<small class="text-muted">Thiết lập áp dụng cho toàn bộ sản phẩm. Sản phẩm không có giá vẫn hiển thị “Liên hệ”.</small>
			</div>
		</div>
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title">Đánh giá sản phẩm — Mã đơn hàng</h3>
			</div>
			<div class="card-body">
				<?php $reviewRequireCode = isset($options['review_require_order_code']) && (string)$options['review_require_order_code'] === '1'; ?>
				<div class="form-group mb-2">
					<div class="custom-control custom-radio mb-2">
						<input class="custom-control-input" type="radio" id="review-require-code-off" name="data[options][review_require_order_code]" value="0" <?=!$reviewRequireCode ? 'checked' : ''?>>
						<label for="review-require-code-off" class="custom-control-label font-weight-normal"><strong>Không bắt buộc</strong> — chỉ cần tên + số điện thoại là gửi đánh giá được. Vẫn qua duyệt của Admin trước khi hiển thị.</label>
					</div>
					<div class="custom-control custom-radio">
						<input class="custom-control-input" type="radio" id="review-require-code-on" name="data[options][review_require_order_code]" value="1" <?=$reviewRequireCode ? 'checked' : ''?>>
						<label for="review-require-code-on" class="custom-control-label font-weight-normal"><strong>Bắt buộc</strong> — người đánh giá phải nhập mã đơn hàng khớp số điện thoại + email của đơn đã mua (chống spam mạnh hơn).</label>
					</div>
				</div>
				<small class="text-muted">Đánh giá nhập đúng mã đơn khớp thông tin luôn được gắn nhãn “Đã mua hàng”, ở cả hai chế độ.</small>
			</div>
		</div>
			<div class="card card-primary card-outline text-sm">
				<div class="card-header">
					<h3 class="card-title">Đóng logo lên ảnh sản phẩm &amp; tin tức</h3>
				</div>
				<div class="card-body">
					<?php $watermarkActive = isset($options['watermark_active']) && (string)$options['watermark_active'] === '1'; ?>
					<div class="form-group mb-2">
						<div class="custom-control custom-radio mb-2">
							<input class="custom-control-input" type="radio" id="watermark-off" name="data[options][watermark_active]" value="0" <?=!$watermarkActive ? 'checked' : ''?>>
							<label for="watermark-off" class="custom-control-label font-weight-normal"><strong>Tắt</strong> — ảnh sản phẩm/tin tức hiển thị nguyên bản, không đóng logo.</label>
						</div>
						<div class="custom-control custom-radio">
							<input class="custom-control-input" type="radio" id="watermark-on" name="data[options][watermark_active]" value="1" <?=$watermarkActive ? 'checked' : ''?>>
							<label for="watermark-on" class="custom-control-label font-weight-normal"><strong>Bật</strong> — tự động đóng logo Phúc Thịnh Solar lên ảnh đại diện sản phẩm và tin tức trên website.</label>
						</div>
					</div>
					<small class="text-muted">Đổi logo/vị trí/kích thước watermark tại Quản lý photo - video &gt; Watermark sản phẩm / Watermark tin tức.</small>
				</div>
			</div>
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title">Thông tin chung</h3>
			</div>
			<div class="card-body">
				<?php if(count($config['website']['lang']) > 1) { ?>
					<div class="form-group">
						<label>Ngôn ngữ mặc định:</label>
						<div class="form-group">
							<?php foreach($config['website']['lang'] as $k => $v) { ?>
								<div class="custom-control custom-radio d-inline-block mr-3 text-md">
									<input class="custom-control-input" type="radio" id="lang_default-<?=$k?>" name="data[options][lang_default]" <?=($k=='vi' ? "checked" : ($k == $options['lang_default'])) ? "checked" : ""?> value="<?=$k?>">
									<label for="lang_default-<?=$k?>" class="custom-control-label font-weight-normal"><?=$v?></label>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
				<div class="row">
					<?php if(isset($config['setting']['address']) && $config['setting']['address'] == true) { ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="address">Địa chỉ:</label>
							<input type="text" class="form-control text-sm" name="data[options][address]" id="address" placeholder="Địa chỉ" value="<?=(!empty($flash->has('address'))) ? $flash->get('address') : @$options['address']?>" required>
						</div>
					<?php } ?>
					<?php if(isset($config['setting']['email']) && $config['setting']['email'] == true) { ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="email">Email:</label>
							<input type="email" class="form-control text-sm" name="data[options][email]" id="email" placeholder="Email" value="<?=(!empty($flash->has('email'))) ? $flash->get('email') : @$options['email']?>" required>
						</div>
					<?php } ?>
					<?php if(isset($config['setting']['hotline']) && $config['setting']['hotline'] == true) { ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="hotline">Hotline:</label>
							<input type="text" class="form-control text-sm" name="data[options][hotline]" id="hotline" placeholder="Hotline" value="<?=(!empty($flash->has('hotline'))) ? $flash->get('hotline') : @$options['hotline']?>" required>
						</div>
					<?php } ?>
					<?php if(isset($config['setting']['phone']) && $config['setting']['phone'] == true) { ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="phone">Điện thoại:</label>
							<input type="text" class="form-control text-sm" name="data[options][phone]" id="phone" placeholder="Điện thoại" value="<?=(!empty($flash->has('phone'))) ? $flash->get('phone') : @$options['phone']?>" required>
						</div>
					<?php } ?>
					<?php if(isset($config['setting']['zalo']) && $config['setting']['zalo'] == true) { ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="zalo">Zalo:</label>
							<input type="text" class="form-control text-sm" name="data[options][zalo]" id="zalo" placeholder="Zalo" value="<?=(!empty($flash->has('zalo'))) ? $flash->get('zalo') : @$options['zalo']?>">
						</div>
					<?php } ?>
					<?php if(isset($config['setting']['oaidzalo']) && $config['setting']['oaidzalo'] == true) { ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="oaidzalo">OAID Zalo:</label>
							<input type="text" class="form-control text-sm" name="data[options][oaidzalo]" id="oaidzalo" placeholder="OAID Zalo" value="<?=(!empty($flash->has('oaidzalo'))) ? $flash->get('oaidzalo') : @$options['oaidzalo']?>">
						</div>
					<?php } ?>
					<?php if(isset($config['setting']['website']) && $config['setting']['website'] == true) { ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="website">Website:</label>
							<input type="text" class="form-control text-sm" name="data[options][website]" id="website" placeholder="Website" value="<?=(!empty($flash->has('website'))) ? $flash->get('website') : @$options['website']?>" required>
						</div>
					<?php } ?>
					<?php if(isset($config['setting']['fanpage']) && $config['setting']['fanpage'] == true) { ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="fanpage">Fanpage:</label>
							<input type="text" class="form-control text-sm" name="data[options][fanpage]" id="fanpage" placeholder="Fanpage" value="<?=(!empty($flash->has('fanpage'))) ? $flash->get('fanpage') : @$options['fanpage']?>">
						</div>
						<div class="form-group col-md-4 col-sm-6">
							<label for="twitter">Twitter:</label>
							<input type="text" class="form-control text-sm" name="data[options][twitter]" id="twitter" placeholder="twitter" value="<?=(!empty($flash->has('twitter'))) ? $flash->get('twitter') : @$options['twitter']?>">
						</div>
						<div class="form-group col-md-4 col-sm-6">
							<label for="youtube">Youtube:</label>
							<input type="text" class="form-control text-sm" name="data[options][youtube]" id="youtube" placeholder="youtube" value="<?=(!empty($flash->has('youtube'))) ? $flash->get('youtube') : @$options['youtube']?>">
						</div>
						<div class="form-group col-md-4 col-sm-6">
							<label for="linkedin">Linkedin:</label>
							<input type="text" class="form-control text-sm" name="data[options][linkedin]" id="linkedin" placeholder="linkedin" value="<?=(!empty($flash->has('linkedin'))) ? $flash->get('linkedin') : @$options['linkedin']?>">
						</div>
					<?php } ?>
					<?php if(isset($config['setting']['coords']) && $config['setting']['coords'] == true) { ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="coords">Tọa độ google map:</label>
							<input type="text" class="form-control text-sm" name="data[options][coords]" id="coords" placeholder="Tọa độ google map" value="<?=(!empty($flash->has('coords'))) ? $flash->get('coords') : @$options['coords']?>">
						</div>
					<?php } ?>
					<div class="form-group col-md-4 col-sm-6">
						<label for="thoigianlamviec">Thời gian làm việc:</label>
						<input type="text" class="form-control text-sm" name="data[options][thoigianlamviec]" id="thoigianlamviec" placeholder="Thời gian làm việc" value="<?=(!empty($flash->has('thoigianlamviec'))) ? $flash->get('thoigianlamviec') : @$options['thoigianlamviec']?>">
					</div>
				</div>
				<?php if(isset($config['setting']['coords_iframe']) && $config['setting']['coords_iframe'] == true) { ?>
					<div class="form-group">
						<label for="coords_iframe">
							<span>Tọa độ google map iframe:</span>
							<a class="text-sm font-weight-normal ml-1" href="https://www.google.com/maps" target="_blank" title="Lấy mã nhúng google map">(Lấy mã nhúng)</a>
						</label>
						<textarea class="form-control text-sm" name="data[options][coords_iframe]" id="coords_iframe" rows="5" placeholder="Tọa độ google map iframe"><?=htmlspecialchars_decode((!empty($flash->has('coords_iframe'))) ? $flash->get('coords_iframe') : @$options['coords_iframe'])?></textarea>
					</div>
				<?php } ?>
				<div class="form-group">
					<label for="analytics">Google analytics:</label>
					<textarea class="form-control text-sm" name="data[analytics]" id="analytics" rows="5" placeholder="Google analytics"><?=htmlspecialchars_decode((!empty($flash->has('analytics'))) ? $flash->get('analytics') : @$item['analytics'])?></textarea>
				</div>
				<div class="form-group">
					<label for="mastertool">Google Webmaster Tool:</label>
					<textarea class="form-control text-sm" name="data[mastertool]" id="mastertool" rows="5" placeholder="Google Webmaster Tool"><?=htmlspecialchars_decode((!empty($flash->has('mastertool'))) ? $flash->get('mastertool') : @$item['mastertool'])?></textarea>
				</div>
				<div class="form-group">
					<label for="headjs">Head JS:</label>
					<textarea class="form-control text-sm" name="data[headjs]" id="headjs" rows="5" placeholder="Head JS"><?=htmlspecialchars_decode((!empty($flash->has('headjs'))) ? $flash->get('headjs') : @$item['headjs'])?></textarea>
				</div>
				<div class="form-group">
					<label for="bodyjs">Body JS:</label>
					<textarea class="form-control text-sm" name="data[bodyjs]" id="bodyjs" rows="5" placeholder="Body JS"><?=htmlspecialchars_decode((!empty($flash->has('bodyjs'))) ? $flash->get('bodyjs') : @$item['bodyjs'])?></textarea>
				</div>
				<div class="card card-primary card-outline card-outline-tabs">
					<div class="card-header p-0 border-bottom-0">
						<ul class="nav nav-tabs" id="custom-tabs-three-tab-lang" role="tablist">
							<?php foreach($config['website']['lang'] as $k => $v) { ?>
                                <li class="nav-item">
									<a class="nav-link <?=($k=='vi')?'active':''?>" id="tabs-lang" data-toggle="pill" href="#tabs-lang-<?=$k?>" role="tab" aria-controls="tabs-lang-<?=$k?>" aria-selected="true"><?=$v?></a>
								</li>
                            <?php } ?>
						</ul>
					</div>
					<div class="card-body card-article">
						<div class="tab-content" id="custom-tabs-three-tabContent-lang">
							<?php foreach($config['website']['lang'] as $k => $v) { ?>
								<div class="tab-pane fade show <?=($k=='vi')?'active':''?>" id="tabs-lang-<?=$k?>" role="tabpanel" aria-labelledby="tabs-lang">
									<div class="form-group">
										<label for="name<?=$k?>">Tiêu đề (<?=$k?>):</label>
										<input type="text" class="form-control for-seo text-sm" name="data[name<?=$k?>]" id="name<?=$k?>" placeholder="Tiêu đề (<?=$k?>)" value="<?=(!empty($flash->has('name'.$k))) ? $flash->get('name'.$k) : @$item['name'.$k]?>" required>
									</div>
								</div>
                            <?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	    <div class="card card-primary card-outline text-sm">
	        <div class="card-header">
	            <h3 class="card-title">Nội dung SEO</h3>
	            <a class="btn btn-sm bg-gradient-success d-inline-block text-white float-right create-seo" title="Tạo SEO">Tạo SEO</a>
	        </div>
	        <div class="card-body">
	            <?php
	                $seoDB = $seo->getOnDB(0,$com,'update',$com);
	                include TEMPLATE.LAYOUT."seo.php";
	            ?>
	        </div>
	    </div>
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu</button>
			<button type="reset" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-redo mr-2"></i>Làm lại</button>
			<input type="hidden" name="id" value="<?=(isset($item['id']) && $item['id'] > 0) ? $item['id'] : ''?>">
		</div>
	</form>
</section>
