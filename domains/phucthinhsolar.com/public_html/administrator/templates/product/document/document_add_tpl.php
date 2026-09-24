<?php
	$linkMan = "index.php?com=product&act=man_document&type=".$type."&id_parent=".$id_parent;
	$linkSave = "index.php?com=product&act=save_document&type=".$type."&id_parent=".$id_parent;
	$documentTypes = array('datasheet' => 'Datasheet', 'manual' => 'Hướng dẫn sử dụng', 'warranty' => 'Bảo hành', 'certificate' => 'Chứng nhận', 'other' => 'Khác');
	$status_array = (!empty($item['status'])) ? explode(',', $item['status']) : array();
?>
<!-- Content Header -->
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="<?=$linkMan?>" title="Tài liệu"><?=$product['namevi']?> — Tài liệu</a></li>
                <li class="breadcrumb-item active"><?=($act=="edit_document")?"Cập nhật":"Thêm mới";?></li>
            </ol>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <form class="validation-form" novalidate method="post" action="<?=$linkSave?>">
        <?=adminCsrfField()?>
        <div class="card-footer text-sm sticky-top">
            <button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu</button>
            <a class="btn btn-sm bg-gradient-danger" href="<?=$linkMan?>" title="Thoát"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
        </div>

        <?=$flash->getMessages('admin')?>

        <div class="card card-primary card-outline text-sm">
            <div class="card-header">
                <h3 class="card-title"><?=($act=="edit_document")?"Cập nhật":"Thêm mới";?> tài liệu — <?=$product['namevi']?></h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="document_type">Loại tài liệu:</label>
                    <select class="form-control text-sm" name="data[document_type]" id="document_type" required>
                        <option value="">-- Chọn loại --</option>
                        <?php foreach($documentTypes as $docKey => $docLabel) { ?>
                            <option value="<?=$docKey?>" <?=((!empty($flash->has('document_type')) ? $flash->get('document_type') : @$item['document_type']) === $docKey) ? 'selected' : ''?>><?=$docLabel?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="titlevi">Tiêu đề (vi):</label>
                    <input type="text" class="form-control text-sm" name="data[titlevi]" id="titlevi" placeholder="Ví dụ: Datasheet thông số kỹ thuật" value="<?=(!empty($flash->has('titlevi'))) ? $flash->get('titlevi') : @$item['titlevi']?>" required>
                </div>
                <div class="form-group">
                    <label for="titleen">Tiêu đề (en):</label>
                    <input type="text" class="form-control text-sm" name="data[titleen]" id="titleen" placeholder="Datasheet" value="<?=(!empty($flash->has('titleen'))) ? $flash->get('titleen') : @$item['titleen']?>">
                </div>
                <div class="form-group">
                    <label for="file_path">Đường dẫn tài liệu:</label>
                    <input type="text" class="form-control text-sm" name="data[file_path]" id="file_path" placeholder="https://... hoặc upload/product-documents/model.pdf" value="<?=(!empty($flash->has('file_path'))) ? $flash->get('file_path') : @$item['file_path']?>" required>
                    <small class="form-text text-muted">HTTPS hoặc đường dẫn nội bộ (upload/... hoặc assets/...), định dạng PDF/DOC/DOCX/XLS/XLSX.</small>
                </div>
                <div class="form-group">
                    <label for="numb" class="d-inline-block align-middle mb-0 mr-2">Số thứ tự:</label>
                    <input type="number" class="form-control form-control-mini d-inline-block align-middle text-sm" min="0" name="data[numb]" id="numb" placeholder="Số thứ tự" value="<?=isset($item['numb']) ? $item['numb'] : 1?>">
                </div>
                <div class="form-group d-inline-block mb-2 mr-2">
                    <label for="hienthi-checkbox" class="d-inline-block align-middle mb-0 mr-2">Hiển thị:</label>
                    <div class="custom-control custom-checkbox d-inline-block align-middle">
                        <input type="checkbox" class="custom-control-input" name="status[hienthi]" id="hienthi-checkbox" <?=(empty($status_array) && empty($item['id'])) || in_array('hienthi', $status_array) ? 'checked' : ''?> value="hienthi">
                        <label for="hienthi-checkbox" class="custom-control-label"></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-sm">
            <button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu</button>
            <a class="btn btn-sm bg-gradient-danger" href="<?=$linkMan?>" title="Thoát"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
            <input type="hidden" name="id" value="<?=(isset($item['id']) && $item['id'] > 0) ? $item['id'] : ''?>">
        </div>
    </form>
</section>
