<?php
	$typeParam = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
	$linkMan   = 'index.php?com=content_section&act=man&type='.$typeParam;
	$results   = isset($importResults) && is_array($importResults) ? $importResults : array();
	$summary   = isset($importSummary) && is_array($importSummary) ? $importSummary : array('create' => 0, 'update' => 0, 'unchanged' => 0, 'skip' => 0, 'error' => 0);
	$payload   = isset($importPayloadRaw) ? (string) $importPayloadRaw : '';
	$err       = isset($importError) ? (string) $importError : '';
	$hasPlan   = false;
	foreach($results as $r) if(in_array($r['status'], array('create', 'update'), true)) { $hasPlan = true; break; }
	$badge = array('create' => 'badge-success', 'update' => 'badge-primary', 'unchanged' => 'badge-light border', 'skip' => 'badge-secondary', 'error' => 'badge-danger');
	$badgeText = array('create' => 'Tạo mới', 'update' => 'Cập nhật', 'unchanged' => 'Không đổi', 'skip' => 'Bỏ qua', 'error' => 'Lỗi');
?>
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
				<li class="breadcrumb-item"><a href="<?=$linkMan?>">Khối nội dung mô tả</a></li>
				<li class="breadcrumb-item active">Nhập JSON</li>
			</ol>
		</div>
	</div>
</section>

<section class="content">
	<?=$flash->getMessages('admin')?>

	<?php if($err !== '') { ?>
	<div class="alert alert-danger text-sm"><i class="fas fa-exclamation-triangle mr-1"></i><?=htmlspecialchars($err, ENT_QUOTES, 'UTF-8')?></div>
	<?php } ?>

	<div class="card card-primary card-outline text-sm">
		<div class="card-header"><h3 class="card-title">Chọn file JSON</h3></div>
		<div class="card-body">
			<div class="alert alert-info text-sm mb-2">
				File JSON xuất từ nút <strong>Xuất JSON</strong> (cùng hoặc khác môi trường). Khớp theo <strong>tên thương hiệu / danh mục</strong>, không theo ID.
				Chỉ nhận scope <code>brand</code> / <code>cat</code> / <code>list</code>. Chỉ <strong>thêm / cập nhật</strong>, không xoá.
			</div>
			<div class="alert alert-warning text-sm">
				<i class="fas fa-image mr-1"></i><strong>Ảnh không đi kèm file JSON.</strong> Khối ảnh chỉ mang <em>tên file</em>; ảnh phải đã có sẵn trong <code>upload/product/</code> ở môi trường này.
				Nhập xong, mục nào có khối ảnh thì mở lại mục đó và upload ảnh thủ công.
			</div>
			<form method="post" action="index.php?com=content_section&act=import&type=<?=$typeParam?>" enctype="multipart/form-data">
				<?=adminCsrfField()?>
				<div class="form-group">
					<input type="file" name="import_file" accept=".json,application/json" required class="form-control-file">
				</div>
				<button type="submit" class="btn btn-sm bg-gradient-primary"><i class="fas fa-search mr-1"></i>Xem trước</button>
				<a class="btn btn-sm bg-gradient-secondary" href="<?=$linkMan?>"><i class="fas fa-sign-out-alt mr-1"></i>Thoát</a>
			</form>
		</div>
	</div>

	<?php if($results) { ?>
	<div class="card card-outline card-secondary text-sm">
		<div class="card-header"><h3 class="card-title">Xem trước — <?=count($results)?> dòng</h3></div>
		<div class="card-body">
			<p class="mb-2">
				<span class="badge badge-success"><?=(int)$summary['create']?> tạo mới</span>
				<span class="badge badge-primary"><?=(int)$summary['update']?> cập nhật</span>
				<span class="badge badge-light border"><?=(int)(isset($summary['unchanged']) ? $summary['unchanged'] : 0)?> không đổi</span>
				<span class="badge badge-secondary"><?=(int)$summary['skip']?> bỏ qua</span>
				<span class="badge badge-danger"><?=(int)$summary['error']?> lỗi</span>
			</p>
			<?php $imgWarn = (int)(isset($summary['img_warn']) ? $summary['img_warn'] : 0); if($imgWarn > 0) { ?>
			<div class="alert alert-warning text-sm">
				<i class="fas fa-image mr-1"></i><strong><?=$imgWarn?> mục</strong> có khối ảnh trỏ tới file <strong>chưa có trên server</strong>.
				Nhập xong nhớ mở từng mục đó và upload lại ảnh — file JSON không mang theo ảnh.
			</div>
			<?php } ?>
			<div class="table-responsive mb-3">
				<table class="table table-bordered table-sm mb-0">
					<thead><tr><th style="width:14%">Loại</th><th style="width:16%">Trạng thái</th><th style="width:34%">Tên</th><th style="width:12%">Khối</th><th>Ghi chú</th></tr></thead>
					<tbody>
						<?php foreach($results as $r) {
							$st = $r['status'];
							$cls = isset($badge[$st]) ? $badge[$st] : 'badge-secondary'; ?>
						<tr>
							<td><?=htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8')?></td>
							<td><span class="badge <?=$cls?>"><?=isset($badgeText[$st]) ? $badgeText[$st] : $st?></span></td>
							<td><?=htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8')?></td>
							<td>
								<?php if(in_array($st, array('create', 'update'), true)) {
									echo (int)$r['count'];
									if($st === 'update' && $r['old'] !== null) echo ' <span class="text-muted">(đang có '.(int)$r['old'].')</span>';
								} else echo '—'; ?>
							</td>
							<td class="<?=!empty($r['img_missing']) ? 'text-warning' : 'text-muted'?>"><?php if(!empty($r['img_missing'])) echo '<i class="fas fa-image mr-1"></i>'; ?><?=htmlspecialchars($r['msg'], ENT_QUOTES, 'UTF-8')?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

			<?php if($hasPlan) { ?>
			<form method="post" action="index.php?com=content_section&act=import_run&type=<?=$typeParam?>" onsubmit="return confirm('Xác nhận ghi <?=(int)$summary['create']?> tạo mới + <?=(int)$summary['update']?> cập nhật vào khối nội dung?');">
				<?=adminCsrfField()?>
				<input type="hidden" name="payload" value="<?=htmlspecialchars($payload, ENT_QUOTES, 'UTF-8')?>">
				<button type="submit" class="btn btn-sm bg-gradient-primary"><i class="far fa-save mr-1"></i>Xác nhận nhập</button>
				<a class="btn btn-sm bg-gradient-secondary" href="<?=$linkMan?>">Huỷ</a>
			</form>
			<?php } else { ?>
			<div class="alert alert-warning text-sm mb-0">Không có dòng nào ghi được. Kiểm tra lại tên hoặc nội dung khối trong file.</div>
			<?php } ?>
		</div>
	</div>
	<?php } ?>
</section>
