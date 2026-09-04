<?php
	if($act=="add") $labelAct = "Thêm mới";
	else if($act=="edit") $labelAct = "Chỉnh sửa";
	else if($act=="copy")  $labelAct = "Sao chép";

	$linkMan = "index.php?com=product&act=man&type=".$type;
	if($act=='add') $linkFilter = "index.php?com=product&act=add&type=".$type;
	else if($act=='edit') $linkFilter = "index.php?com=product&act=edit&type=".$type."&id=".$id;
    if($act=="copy") $linkSave = "index.php?com=product&act=save_copy&type=".$type;
    else $linkSave = "index.php?com=product&act=save&type=".$type;

    /* Check cols */
    if(isset($config['product'][$type]['gallery']) && count($config['product'][$type]['gallery']) > 0)
    {
	    foreach($config['product'][$type]['gallery'] as $key => $value)
	    {
	        if($key == $type)
	        {
	        	$keyGallery = $key;
	            $flagGallery = true;
	            break;
	        }
	    }
    }

    if(
    	(isset($config['product'][$type]['dropdown']) && $config['product'][$type]['dropdown'] == true) || 
    	(isset($config['product'][$type]['brand']) && $config['product'][$type]['brand'] == true) || 
    	(isset($config['product'][$type]['tags']) && $config['product'][$type]['tags'] == true) || 
    	(isset($config['product'][$type]['color']) && $config['product'][$type]['color'] == true) || 
    	(isset($config['product'][$type]['size']) && $config['product'][$type]['size'] == true) || 
    	(isset($config['product'][$type]['images']) && $config['product'][$type]['images'] == true))
    {
    	$colLeft = "col-xl-8";
    	$colRight = "col-xl-4";
    }
    else
    {
    	$colLeft = "col-12";
    	$colRight = "d-none";	
    }
?>
<!-- Content Header -->
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active"><?=$labelAct?> <?=$config['product'][$type]['title_main']?></li>
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
            <button type="submit" class="btn btn-sm bg-gradient-success submit-check" name="save-here" disabled><i class="far fa-save mr-2"></i>Lưu tại trang</button>
            <button type="reset" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-redo mr-2"></i>Làm lại</button>
            <a class="btn btn-sm bg-gradient-danger" href="<?=$linkMan?>" title="Thoát"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
        </div>

        <?=$flash->getMessages('admin')?>
        
        <div class="row">
        	<div class="<?=$colLeft?>">
	            <?php
                	if(isset($config['product'][$type]['slug']) && $config['product'][$type]['slug'] == true)
	                {
	                	$slugchange = ($act=='edit') ? 1 : 0;
	                	$copy = ($act!='copy') ? 0 : 1;
						include TEMPLATE.LAYOUT."slug.php";
				    }
			    ?>
	        	<div class="card card-primary card-outline text-sm">
		            <div class="card-header">
		                <h3 class="card-title">Nội dung <?=$config['product'][$type]['title_main']?></h3>
		                <div class="card-tools">
		                	<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
		                </div>
		            </div>
		            <div class="card-body">
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
		                                    <?php if(isset($config['product'][$type]['desc']) && $config['product'][$type]['desc'] == true) { ?>
		                                        <div class="form-group">
		                                            <label for="desc<?=$k?>">Mô tả (<?=$k?>):</label>
		                                            <textarea class="form-control for-seo text-sm <?=(isset($config['product'][$type]['desc_cke']) && $config['product'][$type]['desc_cke'] == true)?'form-control-ckeditor':''?>" name="data[desc<?=$k?>]" id="desc<?=$k?>" rows="5" placeholder="Mô tả (<?=$k?>)"><?=htmlspecialchars_decode((!empty($flash->has('desc'.$k))) ? $flash->get('desc'.$k) : @$item['desc'.$k])?></textarea>
		                                        </div>
		                                    <?php } ?>
		                                    <?php if(isset($config['product'][$type]['content']) && $config['product'][$type]['content'] == true) { ?>
		                                        <div class="form-group">
		                                            <label for="content<?=$k?>">Nội dung (<?=$k?>):</label>
		                                            <textarea class="form-control for-seo text-sm <?=(isset($config['product'][$type]['content_cke']) && $config['product'][$type]['content_cke'] == true)?'form-control-ckeditor':''?>" name="data[content<?=$k?>]" id="content<?=$k?>" rows="5" placeholder="Nội dung (<?=$k?>)"><?=htmlspecialchars_decode((!empty($flash->has('content'.$k))) ? $flash->get('content'.$k) : @$item['content'.$k])?></textarea>
		                                        </div>
		                                    <?php } ?>
		                                    <?php
		                                    require_once LIBRARIES.'product_content_blocks.php';
		                                    $blockInputs = array(
		                                        'highlights' => array('Điểm nổi bật', 'Mỗi dòng: Tiêu đề ngắn | Giải thích một câu', true),
		                                        'applications' => array('Ứng dụng phù hợp', 'Mỗi dòng là một bối cảnh sử dụng', false),
		                                        'installation' => array('Bộ sản phẩm / hướng dẫn lắp đặt', 'Mỗi dòng là một bước hoặc một thành phần', false)
		                                    );
		                                    foreach($blockInputs as $blockField => $blockMeta) {
		                                        $blockPostKey = $blockField.'_text_'.$k;
		                                        $blockValue = $flash->has($blockPostKey) ? $flash->get($blockPostKey) : productContentBlockLines(isset($item[$blockField.$k]) ? $item[$blockField.$k] : '', $blockMeta[2]); ?>
		                                        <div class="form-group pts-content-block-input">
		                                            <label for="<?=$blockPostKey?>"><?=$blockMeta[0]?> (<?=$k?>):</label>
		                                            <textarea class="form-control text-sm" name="<?=$blockPostKey?>" id="<?=$blockPostKey?>" rows="4" placeholder="<?=$blockMeta[1]?>"><?=htmlspecialchars($blockValue, ENT_QUOTES, 'UTF-8')?></textarea>
		                                            <small class="form-text text-muted"><?=$blockMeta[1]?>. Khối rỗng sẽ tự ẩn ngoài website.</small>
		                                        </div>
		                                    <?php } ?>
		                                    <details class="mb-3"><summary class="text-primary">Xem trước bố cục nội dung chuẩn</summary><div class="border rounded p-3 mt-2 pts-content-block-preview" data-lang="<?=$k?>"><em class="text-muted">Nhập các khối phía trên để xem trước.</em></div></details>
											<?php if(isset($config['product'][$type]['chinhsachmuahang']) && $config['product'][$type]['chinhsachmuahang'] == true) { ?>
		                                        <div class="form-group">
		                                            <label for="chinhsachmuahang<?=$k?>">Chính sách mua hàng (<?=$k?>):</label>
		                                            <textarea class="form-control text-sm <?=(isset($config['product'][$type]['chinhsachmuahang_cke']) && $config['product'][$type]['chinhsachmuahang_cke'] == true)?'form-control-ckeditor':''?>" name="data[chinhsachmuahang<?=$k?>]" id="chinhsachmuahang<?=$k?>" rows="5" placeholder="Chính sách mua hàng (<?=$k?>)"><?=htmlspecialchars_decode((!empty($flash->has('chinhsachmuahang'.$k))) ? $flash->get('chinhsachmuahang'.$k) : @$item['chinhsachmuahang'.$k])?></textarea>
		                                        </div>
		                                    <?php } ?>
                                            <?php if(isset($config['product'][$type]['certificate']) && $config['product'][$type]['certificate'] == true) { ?>
                                                <div class="form-group">
                                                    <label for="certificate<?=$k?>">Giấy chứng nhận / kiểm nghiệm (<?=$k?>):</label>
                                                    <textarea class="form-control text-sm <?=(isset($config['product'][$type]['certificate_cke']) && $config['product'][$type]['certificate_cke'] == true)?'form-control-ckeditor':''?>" name="data[certificate<?=$k?>]" id="certificate<?=$k?>" rows="5" placeholder="Nhập mô tả và chèn ảnh chứng nhận thật (nếu có)"><?=htmlspecialchars_decode((!empty($flash->has('certificate'.$k))) ? $flash->get('certificate'.$k) : @$item['certificate'.$k])?></textarea>
                                                </div>
                                            <?php } ?>
		                                </div>
		                            <?php } ?>
		                        </div>
		                    </div>
		                </div>
		            </div>
		        </div>
            <?php if(
                (!empty($config['product'][$type]['video'])) ||
                (!empty($config['product'][$type]['structured_specs']))
            ) { ?>
                <div class="card card-primary card-outline text-sm">
                    <div class="card-header">
                        <h3 class="card-title">Dữ liệu chi tiết phục vụ bán hàng / quảng cáo</h3>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($config['product'][$type]['video'])) { ?>
                            <div class="form-group">
                                <label for="video_url">Video thực tế sản phẩm (YouTube):</label>
                                <input type="url" class="form-control text-sm" name="data[video_url]" id="video_url"
                                    placeholder="https://www.youtube.com/watch?v=..."
                                    value="<?=(!empty($flash->has('video_url'))) ? $flash->get('video_url') : @$item['video_url']?>">
                                <small class="form-text text-muted">Chỉ nhận URL YouTube/youtu.be. Không dán mã iframe.</small>
                            </div>
                        <?php } ?>
                        <?php if(!empty($config['product'][$type]['structured_specs'])) { ?>
                            <div class="row">
                                <?php
                                $structuredSpecFields = array(
                                    'cong_suat' => 'Công suất',
                                    'light_source' => 'Nguồn sáng',
                                    'lamp_dimensions' => 'Kích thước đèn',
                                    'color_temperature' => 'Nhiệt độ màu (CCT)',
                                    'light_color' => 'Màu ánh sáng',
                                    'battery_capacity' => 'Dung lượng pin',
                                    'panel_power' => 'Công suất tấm pin',
                                    'material' => 'Chất liệu',
                                    'charging_time' => 'Thời gian sạc',
                                    'lighting_time' => 'Thời gian chiếu sáng',
                                    'waterproof_rating' => 'Chỉ số chống nước',
                                    'led_chip' => 'Loại chip LED',
                                    'lighting_area' => 'Diện tích chiếu sáng',
                                    'installation_height' => 'Độ cao lắp đặt',
                                    'panel_dimensions' => 'Kích thước tấm pin',
                                    'package_contents' => 'Bộ sản phẩm gồm',
                                    'carton_quantity' => 'Số lượng / thùng',
                                    'carton_dimensions' => 'Kích thước thùng',
                                    'warranty' => 'Bảo hành'
                                );
                                foreach($structuredSpecFields as $field => $label) { ?>
                                    <div class="form-group col-md-6">
                                        <label for="<?=$field?>"><?=$label?>:</label>
                                        <input type="text" class="form-control text-sm" name="data[<?=$field?>]" id="<?=$field?>"
                                            placeholder="<?=$label?>"
                                            value="<?=(!empty($flash->has($field))) ? $flash->get($field) : @$item[$field]?>">
                                    </div>
                                <?php } ?>
                            </div>
                            <?php
                            $additionalSpecLines = array();
                            if($flash->has('additional_specs_text')) {
                                $additionalSpecLines = preg_split('/\R/u', (string) $flash->get('additional_specs_text'));
                            } elseif(!empty($item['additional_specs'])) {
                                $additionalSpecRows = json_decode(htmlspecialchars_decode($item['additional_specs'], ENT_QUOTES), true);
                                if(is_array($additionalSpecRows)) foreach($additionalSpecRows as $spec) {
                                    if(!empty($spec['label']) && isset($spec['value'])) $additionalSpecLines[] = $spec['label'].' | '.$spec['value'];
                                }
                            }
                            ?>
                            <div class="form-group">
                                <label for="additional_specs_text">Thông số riêng khác:</label>
                                <textarea class="form-control text-sm" name="additional_specs_text" id="additional_specs_text" rows="8"
                                    placeholder="Mỗi dòng: Tên thông số | Giá trị"><?=htmlspecialchars(implode("\n", $additionalSpecLines), ENT_QUOTES, 'UTF-8')?></textarea>
                                <small class="form-text text-muted">Ví dụ: Model | JD-T200. Dùng cho thông số riêng của inverter, tấm pin, phụ kiện…</small>
                            </div>
                            <div class="alert alert-info mb-0">
                                Mỗi thông số nhập vào một ô riêng. Phần giải thích và ứng dụng tiếp tục nhập trong “Nội dung”; không cần soạn bảng HTML.
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
	        </div>
			<div class="<?=$colRight?>">
			<?php if($act === 'edit' && !empty($linkedProjects)) { ?>
				<div class="card card-primary card-outline text-sm">
					<div class="card-header"><h3 class="card-title">Dự án đang liên kết</h3></div>
					<div class="card-body">
						<ul class="mb-0 pl-3">
							<?php foreach($linkedProjects as $linkedProject) { ?>
								<li class="mb-2">
									<a href="index.php?com=news&amp;act=edit&amp;type=du-an&amp;id=<?=(int)$linkedProject['id']?>"><?=htmlspecialchars($linkedProject['namevi'], ENT_QUOTES, 'UTF-8')?></a>
									<?=strpos((string)$linkedProject['status'], 'hienthi') === false ? '<span class="badge badge-secondary ml-1">Đang ẩn</span>' : ''?>
								</li>
							<?php } ?>
						</ul>
						<small class="form-text text-muted">Quan hệ được quản lý tại form Dự án để chỉ có một nguồn chỉnh sửa.</small>
					</div>
				</div>
			<?php } ?>
        		<?php if(
        			(isset($config['product'][$type]['dropdown']) && $config['product'][$type]['dropdown'] == true) || 
        			(isset($config['product'][$type]['brand']) && $config['product'][$type]['brand'] == true) || 
        			(isset($config['product'][$type]['tags']) && $config['product'][$type]['tags'] == true) || 
        			(isset($config['product'][$type]['color']) && $config['product'][$type]['color'] == true) || 
        			(isset($config['product'][$type]['size']) && $config['product'][$type]['size'] == true)
        		) { ?>
			        <div class="card card-primary card-outline text-sm">
			            <div class="card-header">
			                <h3 class="card-title">Danh mục <?=$config['product'][$type]['title_main']?></h3>
			                <div class="card-tools">
			                	<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
			                </div>
			            </div>
			            <div class="card-body">
		            		<div class="form-group-category row">
				            	<?php if(isset($config['product'][$type]['dropdown']) && $config['product'][$type]['dropdown'] == true) { ?>
				            		<?php if(isset($config['product'][$type]['list']) && $config['product'][$type]['list'] == true) { ?>
						                <div class="form-group col-xl-6 col-sm-4">
						                    <label class="d-block" for="id_list">Danh mục cấp 1:</label>
											<?=$func->getAjaxCategory('product', 'list', $type)?>
						                </div>
						            <?php } ?>
						            <?php if(isset($config['product'][$type]['cat']) && $config['product'][$type]['cat'] == true) { ?>
						                <div class="form-group col-xl-6 col-sm-4">
						                    <label class="d-block" for="id_cat">Danh mục cấp 2:</label>
											<?=$func->getAjaxCategory('product', 'cat', $type)?>
						                </div>
						            <?php } ?>
					                <?php if(isset($config['product'][$type]['item']) && $config['product'][$type]['item'] == true) { ?>
						                <div class="form-group col-xl-6 col-sm-4">
						                    <label class="d-block" for="id_item">Danh mục cấp 3:</label>
											<?=$func->getAjaxCategory('product', 'item', $type)?>
						                </div>
						            <?php } ?>
					                <?php if(isset($config['product'][$type]['sub']) && $config['product'][$type]['sub'] == true) { ?>
						                <div class="form-group col-xl-6 col-sm-4">
						                    <label class="d-block" for="id_sub">Danh mục cấp 4:</label>
						                    <?=$func->getAjaxCategory('product', 'sub', $type)?>
						                </div>
						            <?php } ?>
					            <?php } ?>
					            <?php if(isset($config['product'][$type]['brand']) && $config['product'][$type]['brand'] == true) { ?>
							    	<div class="form-group col-xl-6 col-sm-4">
					                    <label class="d-block" for="id_brand">Danh mục hãng:</label>
					                    <?=$func->getAjaxCategory('product', 'brand', $type, 'Chọn hãng')?>
					                </div>
							    <?php } ?>
							    <?php if(isset($config['product'][$type]['tags']) && $config['product'][$type]['tags'] == true) { ?>
							    	<div class="form-group col-xl-6 col-sm-4">
					                    <label class="d-block" for="id_tags">Danh mục tags:</label>
					                    <?=$func->getTags(@$item['id'], 'dataTags', 'product_tags', $type)?>
					                </div>
							    <?php } ?>
							    <?php if(isset($config['product'][$type]['color']) && $config['product'][$type]['color'] == true) { ?>
							    	<div class="form-group col-xl-6 col-sm-4">
					                    <label class="d-block" for="id_color">Danh mục màu sắc:</label>
					                    <?=$func->getColor(@$item['id'])?>
					                </div>
							    <?php } ?>
							    <?php if(isset($config['product'][$type]['size']) && $config['product'][$type]['size'] == true) { ?>
							    	<div class="form-group col-xl-6 col-sm-4">
					                    <label class="d-block" for="id_size">Danh mục kích thước:</label>
					                    <?=$func->getSize(@$item['id'])?>
					                </div>
							    <?php } ?>
							</div>
			            </div>
			        </div>
			    <?php } ?>
				
				<?php if(isset($config['product'][$type]['images']) && $config['product'][$type]['images'] == true) { ?>
			        <div class="card card-primary card-outline text-sm">
			            <div class="card-header">
			                <h3 class="card-title">Hình ảnh <?=$config['product'][$type]['title_main']?></h3>
			                <div class="card-tools">
			                	<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
			                </div>
			            </div>
			            <div class="card-body">
	                    	<?php
	                    		/* Photo detail */
	                    		$photoDetail = array();
	                    		$photoDetail['upload'] = UPLOAD_PRODUCT_L;
	                    		$photoDetail['image'] = (!empty($item) && $act != 'copy') ? $item['photo'] : '';
	                    		$photoDetail['dimension'] = "Width: ".$config['product'][$type]['width']." px - Height: ".$config['product'][$type]['height']." px (".$config['product'][$type]['img_type'].")";

	                    		/* Image */
	                    		include TEMPLATE.LAYOUT."image.php";
	                    	?>
			            </div>
			        </div>
		        <?php } ?>
				<?php if(isset($config['product'][$type]['images2']) && $config['product'][$type]['images2'] == true) { ?>
			        <div class="card card-primary card-outline text-sm">
			            <div class="card-header">
			                <h3 class="card-title">Hình ảnh <?=$config['product'][$type]['title_main']?> 2</h3>
			                <div class="card-tools">
			                	<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
			                </div>
			            </div>
			            <div class="card-body">
	                    	<?php
	                    		/* Photo detail */
	                    		$photoDetail = array();
	                    		$photoDetail['upload'] = UPLOAD_PRODUCT_L;
	                    		$photoDetail['image'] = (!empty($item) && $act != 'copy') ? $item['photo2'] : '';
	                    		$photoDetail['dimension'] = "Width: ".$config['product'][$type]['width2']." px - Height: ".$config['product'][$type]['height2']." px (".$config['product'][$type]['img_type2'].")";

	                    		/* Image */
	                    		include TEMPLATE.LAYOUT."image2.php";
	                    	?>
			            </div>
			        </div>
		        <?php } ?>
	        </div>
	    </div>
	    <div class="card card-primary card-outline text-sm">
            <div class="card-header">
                <h3 class="card-title">Thông tin <?=$config['product'][$type]['title_main']?></h3>
                <div class="card-tools">
                	<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
				<div class="form-group">
					<?php $status_array = (!empty($item['status'])) ? explode(',', $item['status']) : array(); ?>
                    <?php if(isset($config['product'][$type]['check'])) { foreach($config['product'][$type]['check'] as $key => $value) { ?>
                        <div class="form-group d-inline-block mb-2 mr-2">
                        	<label for="<?=$key?>-checkbox" class="d-inline-block align-middle mb-0 mr-2"><?=$value?>:</label>
		                    <div class="custom-control custom-checkbox d-inline-block align-middle">
		                        <input type="checkbox" class="custom-control-input <?=$key?>-checkbox" name="status[<?=$key?>]" id="<?=$key?>-checkbox" <?=(empty($status_array) && empty($item['id']) ? 'checked' : in_array($key, $status_array)) ? 'checked' : ''?> value="<?=$key?>">
		                        <label for="<?=$key?>-checkbox" class="custom-control-label"></label>
		                    </div>
                        </div>
					<?php } } ?>
                    <small class="form-text text-muted d-block">
                        “Hiển thị” cho phép sản phẩm xuất hiện công khai; “Bán chạy” đưa sản phẩm vào nhóm bán chạy. Bật “Liên hệ báo giá” để giữ sản phẩm công khai nhưng ẩn giá và thao tác mua riêng sản phẩm đó. Bỏ “Hiển thị” mới là ẩn toàn bộ sản phẩm.
                    </small>
                </div>
                <div class="form-group">
                    <label for="numb" class="d-inline-block align-middle mb-0 mr-2">Số thứ tự:</label>
                    <input type="number" class="form-control form-control-mini d-inline-block align-middle text-sm" min="0" name="data[numb]" id="numb" placeholder="Số thứ tự" value="<?=isset($item['numb']) ? $item['numb'] : 1?>">
                </div>
                <div class="row">
	                <?php if(isset($config['product'][$type]['code']) && $config['product'][$type]['code'] == true) { ?>
	                    <div class="form-group col-md-4">
	                        <label class="d-block" for="code">Mã sản phẩm:</label>
	                        <input type="text" class="form-control text-sm" name="data[code]" id="code" placeholder="Mã sản phẩm" value="<?=(!empty($flash->has('code'))) ? $flash->get('code') : @$item['code']?>">
	                    </div>
	                <?php } ?>
					<?php if(isset($config['product'][$type]['regular_price']) && $config['product'][$type]['regular_price'] == true) { ?>
				    	<div class="form-group col-md-4">
	                        <label class="d-block" for="regular_price">Giá bán:</label>
	                        <div class="input-group">
	                        	<input type="text" class="form-control format-price regular_price text-sm" name="data[regular_price]" id="regular_price" placeholder="Giá bán" value="<?=(!empty($flash->has('regular_price'))) ? $flash->get('regular_price') : @$item['regular_price']?>">
	                        	<div class="input-group-append">
	                        		<div class="input-group-text"><strong>VNĐ</strong></div>
	                        	</div>
	                        </div>
	                    </div>
				    <?php } ?>
					<?php if(isset($config['product'][$type]['khoanggia']) && $config['product'][$type]['khoanggia'] == true) { ?>
	                    <div class="form-group col-md-4">
	                        <label class="d-block" for="khoanggia">Khoảng giá:</label>
	                        <input type="text" class="form-control text-sm" name="data[khoanggia]" id="khoanggia" placeholder="Khoảng giá" value="<?=(!empty($flash->has('khoanggia'))) ? $flash->get('khoanggia') : @$item['khoanggia']?>">
	                    </div>
	                <?php } ?>
				    <?php if(isset($config['product'][$type]['sale_price']) && $config['product'][$type]['sale_price'] == true) { ?>
				    	<div class="form-group col-md-4">
	                        <label class="d-block" for="sale_price">Giá mới:</label>
	                        <div class="input-group">
	                        	<input type="text" class="form-control format-price sale_price text-sm" name="data[sale_price]" id="sale_price" placeholder="Giá mới" value="<?=(!empty($flash->has('sale_price'))) ? $flash->get('sale_price') : @$item['sale_price']?>">
	                        	<div class="input-group-append">
	                        		<div class="input-group-text"><strong>VNĐ</strong></div>
	                        	</div>
	                        </div>
	                    </div>
				    <?php } ?>
				    <?php if(isset($config['product'][$type]['discount']) && $config['product'][$type]['discount'] == true) { ?>
				    	<div class="form-group col-md-4">
	                        <label class="d-block" for="discount">Chiết khấu:</label>
	                        <div class="input-group">
	                        	<input type="text" class="form-control discount text-sm" name="data[discount]" id="discount" placeholder="Chiết khấu" value="<?=(!empty($flash->has('discount'))) ? $flash->get('discount') : @$item['discount']?>" maxlength="3" readonly>
	                        	<div class="input-group-append">
	                        		<div class="input-group-text"><strong>%</strong></div>
	                        	</div>
	                        </div>
	                    </div>
				    <?php } ?>
				</div>
            </div>
        </div>
        <?php if(isset($flagGallery) && $flagGallery == true) { ?>
        	<div class="card card-primary card-outline text-sm">
        		<div class="card-header">
        			<h3 class="card-title">Bộ sưu tập <?=$config['product'][$type]['title_main']?></h3>
        			<div class="card-tools">
        				<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        			</div>
        		</div>
        		<div class="card-body">
        			<div class="form-group">
        				<label for="filer-gallery" class="label-filer-gallery mb-3">Album hình: (<?=$config['product'][$type]['gallery'][$keyGallery]['img_type_photo']?>)</label>
        				<input type="file" name="files[]" id="filer-gallery" multiple="multiple">
        				<input type="hidden" class="col-filer" value="col-xl-2 col-lg-3 col-md-3 col-sm-4 col-6">
        				<input type="hidden" class="act-filer" value="man">
        				<input type="hidden" class="folder-filer" value="product">
        			</div>
        			<?php if(isset($gallery) && count($gallery) > 0) { ?>
        				<div class="form-group form-group-gallery">
        					<label class="label-filer">Album hiện tại:</label>
        					<div class="action-filer mb-3">
        						<a class="btn btn-sm bg-gradient-primary text-white check-all-filer mr-1"><i class="far fa-square mr-2"></i>Chọn tất cả</a>
        						<button type="button" class="btn btn-sm bg-gradient-success text-white sort-filer mr-1"><i class="fas fa-random mr-2"></i>Sắp xếp</button>
        						<a class="btn btn-sm bg-gradient-danger text-white delete-all-filer"><i class="far fa-trash-alt mr-2"></i>Xóa tất cả</a>
        					</div>
        					<div class="alert my-alert alert-sort-filer alert-info text-sm text-white bg-gradient-info"><i class="fas fa-info-circle mr-2"></i>Có thể chọn nhiều hình để di chuyển</div>
        					<div class="jFiler-items my-jFiler-items jFiler-row">
        						<ul class="jFiler-items-list jFiler-items-grid row scroll-bar" id="jFilerSortable">
        							<?php foreach($gallery as $v) echo $func->galleryFiler($v['numb'],$v['id'],$v['photo'],$v['namevi'],'product','col-xl-2 col-lg-3 col-md-3 col-sm-4 col-6'); ?>
        						</ul>
        					</div>
        				</div>
        			<?php } ?>
        		</div>
        	</div>
        <?php } ?>
        <?php if(isset($config['product'][$type]['seo']) && $config['product'][$type]['seo'] == true) { ?>
			<div class="card card-primary card-outline text-sm collapsed-card">
	            <div class="card-header">
	                <h3 class="card-title">Nội dung SEO</h3>
	                <a class="btn btn-sm bg-gradient-success d-inline-block text-white float-right create-seo" title="Tạo SEO">Tạo SEO</a>
	                <button type="button" class="btn btn-tool float-right" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
	            </div>
	            <div class="card-body" style="display:none;">
                    <?php
                    	$seoDB = $seo->getOnDB($id,$com,'man',$type);
                    	include TEMPLATE.LAYOUT."seo.php";
                    ?>
	            </div>
	        </div>
	    <?php } ?>
        <div class="card-footer text-sm">
            <button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu</button>
            <button type="submit" class="btn btn-sm bg-gradient-success submit-check" name="save-here" disabled><i class="far fa-save mr-2"></i>Lưu tại trang</button>
            <button type="reset" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-redo mr-2"></i>Làm lại</button>
            <a class="btn btn-sm bg-gradient-danger" href="<?=$linkMan?>" title="Thoát"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
            <input type="hidden" name="id" value="<?=(isset($item['id']) && $item['id'] > 0) ? $item['id'] : ''?>">
        </div>
    </form>
</section>
