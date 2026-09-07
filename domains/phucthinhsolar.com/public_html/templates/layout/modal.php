<?php if(!empty($popup)) { ?>
	<!-- Modal popup -->
	<?php $popupCampaignKey = substr(hash('sha256', $popup['id'].'|'.$popup['date_updated'].'|'.$popup['photo']), 0, 20); ?>
	<div class="modal fade" id="popup" data-campaign-key="<?=htmlspecialchars($popupCampaignKey, ENT_QUOTES, 'UTF-8')?>" tabindex="-1" role="dialog" aria-label="Quảng cáo" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-lg pts-popup-dialog" role="document">
			<div class="modal-content pts-popup-content">
				<button type="button" class="close pts-popup-close" data-dismiss="modal" aria-label="Đóng quảng cáo">
					<span aria-hidden="true">&times;</span>
				</button>
				<a class="pts-popup-media" href="<?=$popup['link']?>" title="<?=htmlspecialchars($popup['name'.$lang], ENT_QUOTES, 'UTF-8')?>">
					<?=$func->getImage(['sizes' => '800x530x1', 'upload' => UPLOAD_PHOTO_L, 'image' => $popup['photo'], 'alt' => (!empty($popup['name'.$lang])) ? $popup['name'.$lang] : 'Quảng cáo'])?>
				</a>
			</div>
		</div>
	</div>
<?php } ?>

<?php if($com !== 'lien-he') { ?>
<!-- Modal liên hệ dùng chung toàn site: các nút "Liên hệ" / "Yêu cầu tư vấn" ở
     trang khác mở popup này thay vì điều hướng sang trang lien-he. Trang
     lien-he tự có form riêng nên không lặp lại ở đây để tránh trùng id. -->
<div class="modal fade pts-lead-modal" id="ptsContactModal" tabindex="-1" role="dialog" aria-labelledby="ptsContactModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal" aria-label="Đóng biểu mẫu">
					<span aria-hidden="true">&times;</span>
				</button>
				<div class="content formContact">
					<div role="form" class="wpcf7">
						<form action="" method="post" class="wpcf7-form init" data-status="init">
							<input type="hidden" name="form_csrf" value="<?=htmlspecialchars(formCsrfToken(), ENT_QUOTES, 'UTF-8')?>">
							<div class="inf_default_form">
								<h4 class="title" id="ptsContactModalLabel">Gửi tin nhắn cho chúng tôi</h4>
								<p class="des">Chúng tôi sẵn sàng phục vụ bạn!</p>
								<ul>
									<li class="inf_default_row inf_row_half">
										<div class="inf_input"><label>Họ và tên</label><span class="wpcf7-form-control-wrap name-contact"><input type="text" name="dataContact[fullname]" value="" size="40" maxlength="100" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" placeholder="Nguyễn Văn A" autocomplete="name" required></span></div>
									</li>
									<li class="inf_default_row inf_row_half">
										<div class="inf_input"><label>Số điện thoại</label><span class="wpcf7-form-control-wrap tel-contact"><input type="tel" name="dataContact[phone]" value="" size="40" maxlength="20" class="wpcf7-form-control wpcf7-text wpcf7-tel wpcf7-validates-as-required wpcf7-validates-as-tel" placeholder="090xxxxxxx" autocomplete="tel" inputmode="tel" required></span></div>
									</li>
									<li class="inf_default_row inf_row_half">
										<div class="inf_input"><label>Email</label><span class="wpcf7-form-control-wrap mail-contact"><input type="email" name="dataContact[email]" value="" size="40" maxlength="150" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" placeholder="abc@gmail.com" autocomplete="email" required></span></div>
									</li>
									<li class="inf_default_row inf_row_half">
										<div class="inf_input"><label>Tiêu đề</label><span class="wpcf7-form-control-wrap title-form-contact"><input type="text" name="dataContact[subject]" value="" size="40" maxlength="180" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" placeholder="Nhập tiêu đề" required></span></div>
									</li>
									<li class="inf_default_row inf_row_full">
										<div class="inf_input"><label>Nội dung liên hệ</label><span class="wpcf7-form-control-wrap content-contact"><textarea name="dataContact[content]" cols="40" rows="6" class="wpcf7-form-control wpcf7-textarea wpcf7-validates-as-required" placeholder="Nhập tin nhắn..." required></textarea></span></div>
									</li>
									<li class="inf_default_row inf_row_full">
										<div class="inf_input inf_button"><input type="submit" name="submit-contact" value="Gửi ngay" class="wpcf7-form-control has-spinner wpcf7-submit"><span class="wpcf7-spinner"></span></div>
									</li>
								</ul>
							</div>
							<input type="hidden" name="recaptcha_response_contact" id="recaptchaResponseContact">
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<?php if($com !== 'dai-ly') { ?>
<!-- Modal đăng ký đại lý dùng chung toàn site, cùng lý do như modal liên hệ
     ở trên: trang dai-ly tự có form riêng nên bỏ qua để tránh trùng id. -->
<div class="modal fade pts-lead-modal" id="ptsDealerModal" tabindex="-1" role="dialog" aria-labelledby="ptsDealerModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal" aria-label="Đóng biểu mẫu">
					<span aria-hidden="true">&times;</span>
				</button>
				<div class="content formContact form_agency">
					<div role="form" class="wpcf7">
						<form action="" method="post" class="wpcf7-form init" data-status="init">
							<input type="hidden" name="form_csrf" value="<?=htmlspecialchars(formCsrfToken(), ENT_QUOTES, 'UTF-8')?>">
							<div class="inf_default_form">
								<h4 class="title" id="ptsDealerModalLabel">Đăng ký làm đối tác phân phối Đèn năng lượng mặt trời!</h4>
								<ul>
									<li class="inf_default_row inf_row_half">
										<div class="inf_input"><label>Họ và tên</label><span class="wpcf7-form-control-wrap name-contact"><input type="text" name="dataNewsletter[fullname]" size="40" maxlength="100" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" autocomplete="name" required placeholder="Nguyễn Văn A"></span></div>
									</li>
									<li class="inf_default_row inf_row_half">
										<div class="inf_input"><label>Số điện thoại</label><span class="wpcf7-form-control-wrap tel-contact"><input type="tel" name="dataNewsletter[phone]" size="40" maxlength="20" class="wpcf7-form-control wpcf7-text wpcf7-tel wpcf7-validates-as-required wpcf7-validates-as-tel" autocomplete="tel" inputmode="tel" required placeholder="090xxxxxxx"></span></div>
									</li>
									<li class="inf_default_row inf_row_half">
										<div class="inf_input"><label>Email</label><span class="wpcf7-form-control-wrap mail-contact"><input type="email" name="dataNewsletter[email]" size="40" maxlength="150" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" autocomplete="email" required placeholder="abc@gmail.com"></span></div>
									</li>
									<li class="inf_default_row inf_row_half">
										<div class="inf_input"><label>Tỉnh/Thành phố</label><span class="wpcf7-form-control-wrap cities"><select name="dataNewsletter[address]" class="wpcf7-form-control wpcf7-select" aria-label="Tỉnh thành" required>
											<option value="">Chọn tỉnh/thành phố</option>
											<?php if(count($city)){foreach($city as $k => $v){ ?>
											<option value="<?=$v['name']?>"><?=$v['name']?></option>
											<?php }} ?>
										</select></span></div>
									</li>
									<li class="inf_default_row inf_row_full">
										<div class="inf_input"><label>Tên doanh nghiệp/Cửa hàng</label><span class="wpcf7-form-control-wrap name-company"><input type="text" name="dataNewsletter[company]" size="40" maxlength="180" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" autocomplete="organization" required placeholder="Tên đơn vị của bạn"></span></div>
									</li>
									<li class="inf_default_row inf_row_full">
										<div class="inf_input inf_button"><input type="submit" name="submit-newsletter2" value="Đăng ký ngay" class="wpcf7-form-control has-spinner wpcf7-submit"><span class="wpcf7-spinner"></span></div>
									</li>
								</ul>
							</div>
							<input type="hidden" name="recaptcha_response_newsletter2" id="recaptchaResponseNewsletter2">
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<!-- Modal cart -->
<div class="modal fade" id="popup-cart" tabindex="-1" role="dialog" aria-labelledby="popup-cart-label" aria-hidden="true">
	<div class="modal-dialog modal-dialog-top modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h6 class="modal-title" id="popup-cart-label"><?=giohangcuaban?></h6>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body"></div>
		</div>
	</div>
</div>

<?php /*
<!-- Modal prototype -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target=".exampleModal">
	Open Modal
</button>
<div class="modal fade exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h6 class="modal-title" id="exampleModalLabel">Modal title</h6>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				...
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary">Save changes</button>
			</div>
		</div>
	</div>
</div>
*/ ?>
