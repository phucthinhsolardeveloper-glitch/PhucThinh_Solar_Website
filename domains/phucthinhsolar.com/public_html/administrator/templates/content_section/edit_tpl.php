<?php
	$scopeLabels = array('brand' => 'Thương hiệu', 'cat' => 'Danh mục cấp 2', 'list' => 'Danh mục cấp 1', 'product' => 'Sản phẩm');
	$scopeLabel = isset($scopeLabels[$sectionScope]) ? $scopeLabels[$sectionScope] : $sectionScope;
	$linkMan = 'index.php?com=content_section&act=man&type='.htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
	$linkSave = 'index.php?com=content_section&act=save&type='.htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
	$blocksJsonInline = json_encode(isset($sectionBlocks) ? $sectionBlocks : array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="<?=$linkMan?>">Khối nội dung mô tả</a></li>
				<li class="breadcrumb-item active"><?=$scopeLabel?>: <?=htmlspecialchars($sectionScopeName, ENT_QUOTES, 'UTF-8')?></li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<form method="post" action="<?=$linkSave?>" enctype="multipart/form-data" id="pts-section-form" class="validation-form" novalidate>
		<?=adminCsrfField()?>
		<input type="hidden" name="scope" value="<?=htmlspecialchars($sectionScope, ENT_QUOTES, 'UTF-8')?>">
		<input type="hidden" name="scope_id" value="<?=(int)$sectionScopeId?>">
		<input type="hidden" name="blocks_json" id="pts-section-json">

		<div class="card-footer text-sm sticky-top bg-white">
			<button type="submit" class="btn btn-sm bg-gradient-primary"><i class="far fa-save mr-2"></i>Lưu</button>
			<a class="btn btn-sm bg-gradient-danger" href="<?=$linkMan?>"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
		</div>

		<?=$flash->getMessages('admin')?>

		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title">Khối nội dung — <?=$scopeLabel?>: <strong><?=htmlspecialchars($sectionScopeName, ENT_QUOTES, 'UTF-8')?></strong></h3>
			</div>
			<div class="card-body">
				<div class="alert alert-info text-sm">
					Bấm nút để thêm khối, kéo tay cầm <i class="fas fa-arrows-alt-v"></i> để đổi thứ tự. Khối rỗng sẽ bị bỏ khi lưu.
					<?php if($sectionScope === 'product') { ?><br>Đây là <strong>bản riêng của sản phẩm</strong> — có nội dung ở đây thì sản phẩm không kế thừa từ thương hiệu/danh mục nữa. Xoá hết khối để quay lại kế thừa.<?php } ?>
				</div>

				<div class="d-flex flex-wrap align-items-center mb-3" style="gap:8px">
					<div class="btn-group btn-group-sm flex-wrap" role="group">
						<button type="button" class="btn btn-outline-primary" data-add="heading"><i class="fas fa-heading mr-1"></i>Tiêu đề</button>
						<button type="button" class="btn btn-outline-primary" data-add="text"><i class="fas fa-align-left mr-1"></i>Đoạn văn</button>
						<button type="button" class="btn btn-outline-primary" data-add="image"><i class="far fa-image mr-1"></i>Ảnh</button>
						<button type="button" class="btn btn-outline-primary" data-add="media"><i class="far fa-images mr-1"></i>Ảnh + mô tả</button>
						<button type="button" class="btn btn-outline-primary" data-add="list"><i class="fas fa-list-ul mr-1"></i>Danh sách</button>
						<?php if(in_array($sectionScope, array('cat', 'list', 'product'), true)) { ?>
						<button type="button" class="btn btn-outline-primary" data-add="faq"><i class="far fa-question-circle mr-1"></i>Câu hỏi thường gặp</button>
						<?php } ?>
					</div>
					<button type="button" class="btn btn-sm btn-outline-secondary" id="pts-paste-blocks"><i class="far fa-clipboard mr-1"></i>Dán nhanh</button>
					<?php if($sectionScope === 'product') { ?>
					<button type="button" class="btn btn-sm btn-outline-secondary" id="pts-copy-inherited"
						data-url="index.php?com=content_section&act=inherited_blocks&type=<?=htmlspecialchars($type, ENT_QUOTES, 'UTF-8')?>&scope_id=<?=(int)$sectionScopeId?>">
						<i class="fas fa-clone mr-1"></i>Chép khối kế thừa
					</button>
					<button type="button" class="btn btn-sm btn-outline-secondary" id="pts-import-legacy"
						data-url="index.php?com=content_section&act=import_legacy&type=<?=htmlspecialchars($type, ENT_QUOTES, 'UTF-8')?>&scope_id=<?=(int)$sectionScopeId?>">
						<i class="fas fa-file-import mr-1"></i>Nạp nội dung cũ
					</button>
					<?php } ?>
					<span class="text-muted small ml-auto">Bấm tên khối để thu gọn · <kbd>Ctrl</kbd>+<kbd>S</kbd> để lưu</span>
				</div>

				<div id="pts-section-composer" data-upload="../upload/product/"
					data-upload-url="index.php?com=content_section&act=upload_image&type=<?=htmlspecialchars($type, ENT_QUOTES, 'UTF-8')?>"></div>
				<script type="application/json" id="pts-section-blocks"><?=$blocksJsonInline?></script>
			</div>
		</div>
	</form>
</section>

<style>
#pts-section-composer .pts-section-block{border:1px solid #e2e6ea;border-radius:8px;background:#fff;margin-bottom:12px}
#pts-section-composer .pts-section-block__bar{display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f4f6f9;border-bottom:1px solid #e2e6ea;border-radius:8px 8px 0 0}
#pts-section-composer .pts-section-block__bar .handle{cursor:grab;color:#8895a7}
#pts-section-composer .pts-section-block__bar .type{font-weight:700;color:#2c3e50;font-size:12px;text-transform:uppercase;letter-spacing:.04em;cursor:pointer;user-select:none}
#pts-section-composer .pts-section-block__bar .type:hover{color:#0d6efd}
#pts-section-composer .pts-section-block__bar .preview{font-size:12px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:45%}
#pts-section-composer .pts-section-block__bar .spacer{flex:1}
#pts-section-composer .pts-section-block.is-collapsed .pts-section-block__body{display:none}
#pts-section-composer .pts-section-block__body{padding:12px}
#pts-section-composer .pts-section-block__body .form-group:last-child{margin-bottom:0}
#pts-section-composer .pts-block-thumb{max-width:180px;max-height:120px;border:1px solid #e2e6ea;border-radius:6px;margin-top:6px;display:none}
#pts-section-composer .pts-block-thumb.show{display:block}
#pts-section-composer .pts-upload-status{font-size:12px;margin-top:4px;min-height:16px}
#pts-section-composer .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:767px){#pts-section-composer .grid-2{grid-template-columns:1fr}}
#pts-section-composer .empty-hint{color:#8895a7;font-style:italic;padding:16px;text-align:center;border:1px dashed #cfd6dd;border-radius:8px}
</style>
