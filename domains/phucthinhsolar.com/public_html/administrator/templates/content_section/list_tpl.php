<?php
	$scopeRows = array(
		array('scope' => 'brand', 'label' => 'Thương hiệu', 'items' => isset($sectionBrands) ? $sectionBrands : array()),
		array('scope' => 'cat',   'label' => 'Danh mục cấp 2', 'items' => isset($sectionCats) ? $sectionCats : array()),
		array('scope' => 'list',  'label' => 'Danh mục cấp 1', 'items' => isset($sectionLists) ? $sectionLists : array()),
	);
	$sectionExisting = isset($sectionExisting) ? $sectionExisting : array();
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Khối nội dung mô tả</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<div class="card card-primary card-outline text-sm">
		<div class="card-header d-flex justify-content-between align-items-center flex-wrap">
			<h3 class="card-title mb-0">Khối nội dung mô tả dùng chung</h3>
			<div class="btn-group btn-group-sm">
				<a class="btn btn-outline-secondary" href="index.php?com=content_section&act=export&type=<?=htmlspecialchars($type, ENT_QUOTES, 'UTF-8')?>"><i class="fas fa-file-export mr-1"></i>Xuất JSON</a>
				<a class="btn btn-outline-primary" href="index.php?com=content_section&act=import&type=<?=htmlspecialchars($type, ENT_QUOTES, 'UTF-8')?>"><i class="fas fa-file-import mr-1"></i>Nhập JSON</a>
			</div>
		</div>
		<div class="card-body">
			<div class="alert alert-info text-sm">
				Mỗi bản khối nội dung gắn vào <strong>thương hiệu</strong> hoặc <strong>danh mục</strong>. Sản phẩm tự kế thừa theo thứ tự
				<strong>bản riêng của sản phẩm &rarr; thương hiệu &rarr; danh mục cấp 2 &rarr; danh mục cấp 1</strong>. Sửa một bản là áp cho mọi sản phẩm trong nhóm.
			</div>

			<?php if(!empty($sectionSummary)) { $s = $sectionSummary; ?>
			<div class="row text-center mb-3">
				<div class="col"><div class="border rounded py-2"><div class="h5 mb-0"><?=$s['total']?></div><small class="text-muted">Tổng SP</small></div></div>
				<div class="col"><div class="border rounded py-2"><div class="h5 mb-0 text-primary"><?=$s['inherit']?></div><small class="text-muted">Kế thừa hãng/dm</small></div></div>
				<div class="col"><div class="border rounded py-2"><div class="h5 mb-0 text-success"><?=$s['own']?></div><small class="text-muted">Nội dung riêng</small></div></div>
				<div class="col"><div class="border rounded py-2"><div class="h5 mb-0 text-secondary"><?=$s['hide']?></div><small class="text-muted">Ẩn mô tả dài</small></div></div>
				<div class="col"><div class="border rounded py-2"><div class="h5 mb-0 text-warning"><?=$s['auto_only']?></div><small class="text-muted">Chỉ chip + đoạn tự sinh</small></div></div>
			</div>
			<?php if($s['auto_only'] > 0) { ?><p class="text-muted small mb-3"><?=$s['auto_only']?> sản phẩm chưa có khối nào — soạn nội dung cho <strong>hãng / danh mục</strong> của chúng để phủ nhanh nhất.</p><?php } ?>
			<?php } ?>

			<?php foreach($scopeRows as $group) { ?>
			<h6 class="mt-3 mb-2 text-primary"><?=$group['label']?></h6>
			<div class="table-responsive mb-3">
				<table class="table table-bordered table-striped table-sm mb-0">
					<thead><tr><th style="width:55%">Tên</th><th style="width:20%">Khối nội dung</th><th style="width:25%"></th></tr></thead>
					<tbody>
						<?php foreach($group['items'] as $it) {
							$key = $group['scope'].':'.(int)$it['id'];
							$info = isset($sectionExisting[$key]) ? $sectionExisting[$key] : null;
							$editUrl = 'index.php?com=content_section&act=edit&type='.htmlspecialchars($type, ENT_QUOTES, 'UTF-8').'&scope='.$group['scope'].'&scope_id='.(int)$it['id']; ?>
						<tr>
							<td><?=htmlspecialchars($it['namevi'], ENT_QUOTES, 'UTF-8')?></td>
							<td>
								<?php if($info && $info['count'] > 0) { ?>
									<span class="badge badge-success"><?=$info['count']?> khối</span>
								<?php } else { ?>
									<span class="badge badge-secondary">Chưa có</span>
								<?php } ?>
							</td>
							<td>
								<div class="d-flex justify-content-between align-items-center">
								<a class="btn btn-xs bg-gradient-primary text-white" href="<?=$editUrl?>"><i class="far fa-edit mr-1"></i><?=($info && $info['count'] > 0) ? 'Sửa' : 'Tạo'?></a>
								<?php if($info && $info['count'] > 0) { ?>
								<form method="post" action="index.php?com=content_section&act=delete&type=<?=htmlspecialchars($type, ENT_QUOTES, 'UTF-8')?>" onsubmit="return confirm('Xoá toàn bộ khối nội dung của mục này? Các sản phẩm sẽ quay lại kế thừa.');">
									<?=adminCsrfField()?>
									<input type="hidden" name="scope" value="<?=$group['scope']?>">
									<input type="hidden" name="scope_id" value="<?=(int)$it['id']?>">
									<button type="submit" class="btn btn-xs bg-gradient-danger text-white"><i class="far fa-trash-alt"></i></button>
								</form>
								<?php } ?>
								</div>
							</td>
						</tr>
						<?php } ?>
						<?php if(!$group['items']) { ?><tr><td colspan="3" class="text-muted">Chưa có <?=mb_strtolower($group['label'])?>.</td></tr><?php } ?>
					</tbody>
				</table>
			</div>
			<?php } ?>
		</div>
	</div>
</section>
