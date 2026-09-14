<?php
    $linkMan = "index.php?com=photo&act=man_photo&type=".$type;
    $linkSave = "index.php?com=photo&act=save_photo&type=".$type;
?>
<!-- Content Header -->
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="<?=$linkMan?>" title="<?=$config['photo']['man_photo'][$type]['title_main_photo']?>">Quản lý <?=$config['photo']['man_photo'][$type]['title_main_photo']?></a></li>
                <li class="breadcrumb-item active">Thêm mới <?=$config['photo']['man_photo'][$type]['title_main_photo']?></li>
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
            <a class="btn btn-sm bg-gradient-danger" href="<?=$linkMan?>" title="Thoát"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
        </div>

        <?=$flash->getMessages('admin')?>
        <?php if(!empty($config['photo']['man_photo'][$type]['admin_note_photo'])) { ?>
        <div class="alert alert-info text-sm">
            <i class="fas fa-info-circle mr-2"></i><?=htmlspecialchars($config['photo']['man_photo'][$type]['admin_note_photo'], ENT_QUOTES, 'UTF-8')?>
        </div>
        <?php } ?>

		<?php $numberPhoto = (isset($config['photo']['man_photo'][$type]['number_photo'])) ? $config['photo']['man_photo'][$type]['number_photo'] : 0; ?>
		<?php for($i=0;$i<$numberPhoto;$i++) { $numb = $i+1; ?>
			<div class="card card-primary card-outline text-sm">
	            <div class="card-header">
	                <h3 class="card-title"><?=$config['photo']['man_photo'][$type]['title_main_photo'].": ".$numb?></h3>
	                <div class="card-tools">
	                	<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
	                </div>
	            </div>
	            <div class="card-body">
					<?php if(isset($config['photo']['man_photo'][$type]['show_stt']) && $config['photo']['man_photo'][$type]['show_stt'] == true) { ?>
					<div class="form-group col-md-3" style="padding:0 !important;">
						<label for="type_show">Loại hiển thị:</label>
						<select class="custom-select text-sm type_show" name="dataMulti[<?=$i?>][show_stt]" id="type_show">
							<option selected value="0">Ảnh</option>
							<option value="1">Video</option>
						</select>
					</div>
					<?php }?>
					<?php if(isset($config['photo']['man_photo'][$type]['images_photo']) && $config['photo']['man_photo'][$type]['images_photo'] == true) { ?>
	                    <div class="form-group">
	                    	<div class="upload-file">
	                    		<p>Upload hình ảnh:</p>
	                    		<label class="upload-file-label mb-2" for="file<?=$i?>">
	                    			<div class="upload-file-image rounded mb-3">
	                    				<?=$func->getImage(['class' => 'rounded img-upload', 'size-error' => '250x250x1', 'upload' => 'assets/images/', 'image' => 'noimage.png', 'alt' => 'No Image'])?>
	                    			</div>
	                    			<div class="custom-file my-custom-file">
	                    				<input type="file" class="custom-file-input" name="file<?=$i?>" id="file<?=$i?>" lang="vi">
	                    				<label class="custom-file-label mb-0" data-browse="Chọn" for="file<?=$i?>">Chọn file</label>
	                    			</div>
	                    		</label>
	                    		<strong class="d-block text-sm"><?php echo "Width: ".$config['photo']['man_photo'][$type]['width_photo']." px - Height: ".$config['photo']['man_photo'][$type]['height_photo']." px (".$config['photo']['man_photo'][$type]['img_type_photo'].")" ?></strong>
	                    	</div>
	                    </div>
	                <?php } ?>
					<?php if(isset($config['photo']['man_photo'][$type]['video_mp4']) && $config['photo']['man_photo'][$type]['video_mp4'] == true) { ?>
					<div class="video_upload_box">
						<div class="form-group">
							<label for="file_video<?=$i?>">Video upload: (mp4)
								<br><br>
								<div class="custom-file my-custom-file">
									<input type="file" class="custom-file-input" name="file_video<?=$i?>"
										id="file_video<?=$i?>" lang="vi"
										onchange="mp4Preview(event,this.value,'#load_mp4Video<?=$i?>');">
									<label class="custom-file-label mb-0" data-browse="Chọn" for="file_video<?=$i?>">Chọn
										Video mp4</label>
								</div>
							</label>
						</div>
						<div class="form-group">
							<label for="file_video">Video preview:</label>
							<div id="load_mp4Video<?=$i?>">

							</div>
						</div>
					</div>
					<?php } ?>
	                <?php if(isset($config['photo']['man_photo'][$type]['link_photo']) && $config['photo']['man_photo'][$type]['link_photo'] == true) { ?>
		                <?php if($type === 'adv' || $type === 'hero-carousel') { ?>
		                <div class="form-group">
		                    <label for="link_action<?=$i?>">Link nút mở popup thay vì đường dẫn thường:</label>
		                    <select class="form-control text-sm" id="link_action<?=$i?>" onchange="
		                        var v = this.value;
		                        var input = document.getElementById('link<?=$i?>');
		                        if(v !== '') { input.value = v; input.readOnly = true; }
		                        else { if(['#ptsContactModal','#ptsDealerModal'].indexOf(input.value) !== -1) input.value = ''; input.readOnly = false; }
		                    ">
		                        <option value="">Liên kết bình thường</option>
		                        <option value="#ptsContactModal">Mở popup: Liên hệ</option>
		                        <option value="#ptsDealerModal">Mở popup: Đăng ký đại lý</option>
		                    </select>
		                </div>
		                <?php } ?>
		                <div class="form-group">
		                    <?php $linkLabel = !empty($config['photo']['man_photo'][$type]['link_label_photo']) ? $config['photo']['man_photo'][$type]['link_label_photo'] : ($type === 'adv' ? 'Link (khi chọn "Liên kết bình thường")' : 'Link'); ?>
		                    <label for="link<?=$i?>"><?=htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8')?>:</label>
		                    <input type="text" class="form-control text-sm" name="dataMulti[<?=$i?>][link]" id="link<?=$i?>" placeholder="<?=htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8')?>" value="<?=(!empty($flash->has('link'.$i))) ? $flash->get('link'.$i) : ''?>">
		                </div>
		            <?php } ?>
		            <?php if($type === 'hero-carousel') { ?>
		            <div class="card card-secondary card-outline text-sm">
		                <div class="card-header"><h3 class="card-title">Nội dung nâng cao (slide <?=$numb?>)</h3></div>
		                <div class="card-body">
		                    <div class="form-group">
		                        <label for="opt_eyebrow<?=$i?>">Nhãn nhỏ phía trên tiêu đề:</label>
		                        <input type="text" class="form-control text-sm" name="dataMulti[<?=$i?>][options][eyebrow]" id="opt_eyebrow<?=$i?>" placeholder="VD: PHÚC THỊNH SOLAR">
		                    </div>
		                    <div class="form-group">
		                        <label for="opt_titleLine2_<?=$i?>">Tiêu đề (dòng 2, chữ mờ hơn dòng 1):</label>
		                        <input type="text" class="form-control text-sm" name="dataMulti[<?=$i?>][options][titleLine2]" id="opt_titleLine2_<?=$i?>">
		                    </div>
		                    <div class="form-group">
		                        <label for="opt_theme<?=$i?>">Kiểu nền chữ:</label>
		                        <select class="form-control text-sm" name="dataMulti[<?=$i?>][options][theme]" id="opt_theme<?=$i?>">
		                            <option value="dark">Tối (chữ trắng, mặc định)</option>
		                            <option value="light">Sáng (chữ tối màu)</option>
		                        </select>
		                    </div>
		                    <div class="form-group">
		                        <label for="opt_ctaLabel<?=$i?>">Chữ trên nút chính:</label>
		                        <input type="text" class="form-control text-sm" name="dataMulti[<?=$i?>][options][ctaLabel]" id="opt_ctaLabel<?=$i?>" placeholder="VD: Khám phá sản phẩm">
		                    </div>
		                    <hr>
		                    <div class="form-group">
		                        <label for="opt_cta2Label<?=$i?>">Chữ trên link phụ (để trống = không hiện link phụ):</label>
		                        <input type="text" class="form-control text-sm" name="dataMulti[<?=$i?>][options][cta2Label]" id="opt_cta2Label<?=$i?>" placeholder="VD: Nhận tư vấn">
		                    </div>
		                    <div class="form-group">
		                        <label for="opt_cta2_action<?=$i?>">Link phụ mở popup thay vì đường dẫn thường:</label>
		                        <select class="form-control text-sm" id="opt_cta2_action<?=$i?>" onchange="
		                            var v = this.value;
		                            var input = document.getElementById('opt_cta2Href<?=$i?>');
		                            if(v !== '') { input.value = v; input.readOnly = true; }
		                            else { if(['#ptsContactModal','#ptsDealerModal'].indexOf(input.value) !== -1) input.value = ''; input.readOnly = false; }
		                        ">
		                            <option value="">Liên kết bình thường</option>
		                            <option value="#ptsContactModal">Mở popup: Liên hệ</option>
		                            <option value="#ptsDealerModal">Mở popup: Đăng ký đại lý</option>
		                        </select>
		                    </div>
		                    <div class="form-group">
		                        <label for="opt_cta2Href<?=$i?>">Link phụ:</label>
		                        <input type="text" class="form-control text-sm" name="dataMulti[<?=$i?>][options][cta2Href]" id="opt_cta2Href<?=$i?>">
		                    </div>
		                    <div class="form-group">
		                        <label for="opt_tags<?=$i?>">Tag cuối slide, cách nhau bằng dấu phẩy (để trống = không hiện):</label>
		                        <input type="text" class="form-control text-sm" name="dataMulti[<?=$i?>][options][tags]" id="opt_tags<?=$i?>" placeholder="VD: GIA ĐÌNH, DOANH NGHIỆP, DỰ ÁN">
		                    </div>
		                </div>
		            </div>
		            <?php } ?>
		            <?php if(isset($config['photo']['man_photo'][$type]['video_photo']) && $config['photo']['man_photo'][$type]['video_photo'] == true) { ?>
		                <div class="form-group">
		                    <label for="link_video<?=$i?>">Video:</label>
		                    <input type="text" class="form-control text-sm" name="dataMulti[<?=$i?>][link_video]" id="link_video<?=$i?>" onchange="youtubePreview(this.value,'#loadVideo<?=$i?>');" placeholder="Video" value="<?=(!empty($flash->has('link_video'.$i))) ? $flash->get('link_video'.$i) : ''?>">
		                </div>
		                <div class="form-group">
		                    <label for="link_video">Video preview:</label>
		                    <div><iframe id="loadVideo<?=$i?>" width="0px" height="0px" frameborder="0" allowfullscreen></iframe></div>
		                </div>
		            <?php } ?>
	                <div class="form-group">
					    <?php if(isset($config['photo']['man_photo'][$type]['check_photo'])) { foreach($config['photo']['man_photo'][$type]['check_photo'] as $key => $value) { ?>
					        <div class="form-group d-inline-block mb-2 mr-2">
					            <label for="<?=$key?>-checkbox<?=$i?>" class="d-inline-block align-middle mb-0 mr-2"><?=$value?>:</label>
					            <div class="custom-control custom-checkbox d-inline-block align-middle">
					                <input type="checkbox" class="custom-control-input <?=$key?>-checkbox<?=$i?>" name="dataMulti[<?=$i?>][status][]" id="<?=$key?>-checkbox<?=$i?>" value="<?=$key?>" checked>
					                <label for="<?=$key?>-checkbox<?=$i?>" class="custom-control-label"></label>
					            </div>
					        </div>
					    <?php } } ?>
					</div>
	                <div class="form-group">
						<label for="numb<?=$i?>" class="d-inline-block align-middle mb-0 mr-2">Số thứ tự:</label>
						<input type="number" class="form-control form-control-mini d-inline-block align-middle text-sm" min="0" name="dataMulti[<?=$i?>][numb]" id="numb<?=$i?>" placeholder="Số thứ tự" value="1">
					</div>
		            <?php if(
		            	(isset($config['photo']['man_photo'][$type]['name_photo']) && $config['photo']['man_photo'][$type]['name_photo'] == true) || 
		            	(isset($config['photo']['man_photo'][$type]['desc_photo']) && $config['photo']['man_photo'][$type]['desc_photo'] == true) || 
		            	(isset($config['photo']['man_photo'][$type]['content_photo']) && $config['photo']['man_photo'][$type]['content_photo'] == true)
		            ) { ?>
		                <div class="card card-primary card-outline card-outline-tabs">
		                    <div class="card-header p-0 border-bottom-0">
		                        <ul class="nav nav-tabs" id="custom-tabs-three-tab-lang" role="tablist">
		                            <?php foreach($config['website']['lang'] as $k => $v) { ?>
		                                <li class="nav-item">
		                                    <a class="nav-link <?=($k=='vi')?'active':''?>" id="tabs-lang" data-toggle="pill" href="#tabs-lang-<?=$k?>-<?=$i?>" role="tab" aria-controls="tabs-lang-<?=$k?>-<?=$i?>" aria-selected="true"><?=$v?></a>
		                                </li>
		                            <?php } ?>
		                        </ul>
		                    </div>
		                    <div class="card-body">
		                        <div class="tab-content" id="custom-tabs-three-tabContent-lang">
		                            <?php foreach($config['website']['lang'] as $k => $v) { ?>
		                                <div class="tab-pane fade show <?=($k=='vi')?'active':''?>" id="tabs-lang-<?=$k?>-<?=$i?>" role="tabpanel" aria-labelledby="tabs-lang">
		                                    <?php if((isset($config['photo']['man_photo'][$type]['name_photo']) && $config['photo']['man_photo'][$type]['name_photo'] == true)) { ?>
		                                        <div class="form-group">
		                                            <?php $nameLabel = !empty($config['photo']['man_photo'][$type]['name_label_photo']) ? $config['photo']['man_photo'][$type]['name_label_photo'] : 'Tiêu đề'; ?>
		                                            <label for="name<?=$k?><?=$i?>"><?=htmlspecialchars($nameLabel, ENT_QUOTES, 'UTF-8')?> (<?=$k?>):</label>
		                                            <input type="text" class="form-control text-sm" name="dataMulti[<?=$i?>][name<?=$k?>]" id="name<?=$k?><?=$i?>" placeholder="<?=htmlspecialchars($nameLabel, ENT_QUOTES, 'UTF-8')?> (<?=$k?>)" value="<?=(!empty($flash->has('name'.$k.$i))) ? $flash->get('name'.$k.$i) : ''?>">
		                                        </div>
		                                    <?php } ?>
		                                    <?php if((isset($config['photo']['man_photo'][$type]['desc_photo']) && $config['photo']['man_photo'][$type]['desc_photo'] == true)) { ?>
		                                        <div class="form-group">
		                                            <?php $descLabel = !empty($config['photo']['man_photo'][$type]['desc_label_photo']) ? $config['photo']['man_photo'][$type]['desc_label_photo'] : 'Mô tả'; ?>
		                                            <label for="desc<?=$k?><?=$i?>"><?=htmlspecialchars($descLabel, ENT_QUOTES, 'UTF-8')?> (<?=$k?>):</label>
		                                            <textarea class="form-control text-sm <?=((isset($config['photo']['man_photo'][$type]['desc_cke_photo']) && $config['photo']['man_photo'][$type]['desc_cke_photo'] == true))?'form-control-ckeditor':''?>" name="dataMulti[<?=$i?>][desc<?=$k?>]" id="desc<?=$k?><?=$i?>" rows="5" placeholder="<?=htmlspecialchars($descLabel, ENT_QUOTES, 'UTF-8')?> (<?=$k?>)"><?=htmlspecialchars_decode((!empty($flash->has('desc'.$k.$i))) ? $flash->get('desc'.$k.$i) : '')?></textarea>
		                                        </div>
		                                    <?php } ?>
		                                    <?php if((isset($config['photo']['man_photo'][$type]['content_photo']) && $config['photo']['man_photo'][$type]['content_photo'] == true)) { ?>
		                                        <div class="form-group">
		                                            <label for="content<?=$k?><?=$i?>">Nội dung (<?=$k?>):</label>
		                                            <textarea class="form-control text-sm <?=((isset($config['photo']['man_photo'][$type]['content_cke_photo']) && $config['photo']['man_photo'][$type]['content_cke_photo'] == true))?'form-control-ckeditor':''?>" name="dataMulti[<?=$i?>][content<?=$k?>]" id="content<?=$k?><?=$i?>" rows="5" placeholder="Nội dung (<?=$k?>)"><?=htmlspecialchars_decode((!empty($flash->has('content'.$k.$i))) ? $flash->get('content'.$k.$i) : '')?></textarea>
		                                        </div>
		                                    <?php } ?>
		                                </div>
		                            <?php } ?>
		                        </div>
		                    </div>
		                </div>
		            <?php } ?>
	            </div>
	        </div>
		<?php } ?>
        <div class="card-footer text-sm">
            <button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu</button>
            <button type="reset" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-redo mr-2"></i>Làm lại</button>
            <a class="btn btn-sm bg-gradient-danger" href="<?=$linkMan?>" title="Thoát"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
        </div>
    </form>
</section>
