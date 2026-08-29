<?php
$bulkBase = 'index.php?com=product&type='.$type;
$bulkCsrf = productBulkCsrfToken();
?>
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="index.php?com=product&act=man&type=<?=$type?>">Quản lý sản phẩm</a></li>
                <li class="breadcrumb-item active">Nhập / xuất hàng loạt</li>
            </ol>
        </div>
    </div>
</section>

<section class="content">
    <?php if (!empty($bulkMessage)) { ?>
        <div class="alert alert-<?=$bulkMessage['type']?> text-sm"><?=htmlspecialchars($bulkMessage['text'])?></div>
    <?php } ?>
    <?php if (!empty($bulkResult)) { ?>
        <div class="alert alert-success text-sm">
            Tạo mới: <strong><?=(int) $bulkResult['create']?></strong> ·
            Cập nhật: <strong><?=(int) $bulkResult['update']?></strong> ·
            Ẩn khỏi website: <strong><?=(int) $bulkResult['archive']?></strong> ·
            Bỏ qua: <strong><?=(int) $bulkResult['skip']?></strong>
        </div>
    <?php } ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-primary card-outline text-sm h-100">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-file-download mr-2"></i>Tải mẫu và xuất dữ liệu</h3></div>
                <div class="card-body">
                    <p>Excel có thêm sheet hướng dẫn và bảng ID danh mục/thương hiệu. CSV phù hợp chỉnh nhanh hoặc xử lý bằng công cụ khác.</p>
                    <div class="mb-3">
                        <a class="btn btn-sm btn-outline-primary mr-2 mb-2" href="<?=$bulkBase?>&act=bulk_template&format=xlsx"><i class="far fa-file-excel mr-1"></i>Mẫu Excel</a>
                        <a class="btn btn-sm btn-outline-secondary mb-2" href="<?=$bulkBase?>&act=bulk_template&format=csv"><i class="fas fa-file-csv mr-1"></i>Mẫu CSV</a>
                    </div>
                    <div>
                        <a class="btn btn-sm bg-gradient-success mr-2 mb-2" href="<?=$bulkBase?>&act=bulk_export&format=xlsx"><i class="fas fa-file-export mr-1"></i>Xuất toàn bộ Excel</a>
                        <a class="btn btn-sm bg-gradient-info mb-2" href="<?=$bulkBase?>&act=bulk_export&format=csv"><i class="fas fa-file-csv mr-1"></i>Xuất toàn bộ CSV</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <form method="post" action="<?=$bulkBase?>&act=bulk_preview" enctype="multipart/form-data">
                <input type="hidden" name="product_bulk_csrf" value="<?=htmlspecialchars($bulkCsrf)?>">
                <div class="card card-primary card-outline text-sm h-100">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-file-import mr-2"></i>Nhập dữ liệu</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="product_bulk_file">File Excel hoặc CSV</label>
                            <div class="custom-file my-custom-file">
                                <input required type="file" class="custom-file-input" name="product_bulk_file" id="product_bulk_file" accept=".csv,.xls,.xlsx">
                                <label class="custom-file-label" for="product_bulk_file">Chọn file tối đa 5 MB</label>
                            </div>
                            <small class="form-text text-muted">Tối đa 500 dòng/lần. Hệ thống chỉ phân tích và hiển thị bản xem trước, chưa ghi database.</small>
                        </div>
                    </div>
                    <div class="card-footer"><button class="btn btn-sm bg-gradient-primary"><i class="fas fa-search mr-2"></i>Kiểm tra và xem trước</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-warning card-outline text-sm mt-3">
        <div class="card-header"><h3 class="card-title">Quy tắc an toàn</h3></div>
        <div class="card-body py-2">
            <ul class="mb-0 pl-3">
                <li><code>update</code> và <code>archive</code> bắt buộc có ID; ô trống giữ nguyên dữ liệu hiện tại.</li>
                <li>Dùng <code>__CLEAR__</code> khi muốn xóa nội dung một ô. <code>archive</code> chỉ ẩn sản phẩm, không xóa hình ảnh/review/SEO.</li>
                <li><code>regular_price</code> là giá gốc; <code>sale_price</code> là giá bán/khuyến mãi. Trạng thái hỗ trợ: <code>hienthi,banchay</code>.</li>
                <li>Cột <code>photo</code> và các cột bắt đầu bằng dấu gạch dưới chỉ để đối chiếu, không cập nhật từ file.</li>
            </ul>
        </div>
    </div>

    <?php if (!empty($bulkPreview)) { ?>
        <div class="card card-primary card-outline text-sm mt-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">Kết quả xem trước</h3>
                <div class="ml-auto">
                    <span class="badge badge-success mr-1">Tạo <?=(int) $bulkPreview['counts']['create']?></span>
                    <span class="badge badge-primary mr-1">Sửa <?=(int) $bulkPreview['counts']['update']?></span>
                    <span class="badge badge-warning mr-1">Ẩn <?=(int) $bulkPreview['counts']['archive']?></span>
                    <span class="badge badge-danger">Lỗi <?=(int) $bulkPreview['errors']?></span>
                </div>
            </div>
            <div class="card-body table-responsive p-0" style="max-height:560px">
                <table class="table table-hover table-sm mb-0">
                    <thead class="thead-light sticky-top">
                        <tr><th>Dòng</th><th>Thao tác</th><th>ID</th><th>Mã</th><th>Sản phẩm</th><th>Kết quả</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bulkPreview['rows'] as $row) { ?>
                        <tr class="<?=!empty($row['errors']) ? 'table-danger' : (!empty($row['warnings']) ? 'table-warning' : '')?>">
                            <td><?=(int) $row['row']?></td>
                            <td><code><?=htmlspecialchars($row['action'])?></code></td>
                            <td><?=$row['id'] ? (int) $row['id'] : ''?></td>
                            <td><?=htmlspecialchars($row['code'])?></td>
                            <td><?=htmlspecialchars($row['name'])?></td>
                            <td>
                                <?php if (empty($row['errors']) && empty($row['warnings'])) { ?><span class="text-success"><i class="fas fa-check mr-1"></i>Hợp lệ</span><?php } ?>
                                <?php foreach ($row['errors'] as $message) { ?><div class="text-danger"><i class="fas fa-times mr-1"></i><?=htmlspecialchars($message)?></div><?php } ?>
                                <?php foreach ($row['warnings'] as $message) { ?><div class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i><?=htmlspecialchars($message)?></div><?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <?php if ($bulkPreview['errors'] === 0 && !empty($bulkPreview['token'])) { ?>
                    <form method="post" action="<?=$bulkBase?>&act=bulk_commit" class="form-inline">
                        <input type="hidden" name="product_bulk_csrf" value="<?=htmlspecialchars($bulkCsrf)?>">
                        <input type="hidden" name="preview_token" value="<?=htmlspecialchars($bulkPreview['token'])?>">
                        <label class="mr-2" for="confirm_bulk">Nhập <code class="mx-1">CAP_NHAT</code> để xác nhận:</label>
                        <input required class="form-control form-control-sm mr-2" type="text" id="confirm_bulk" name="confirm_bulk" autocomplete="off">
                        <button class="btn btn-sm bg-gradient-danger"><i class="fas fa-database mr-2"></i>Áp dụng vào database</button>
                    </form>
                <?php } else { ?>
                    <span class="text-danger"><i class="fas fa-ban mr-1"></i>File còn lỗi nên chưa thể ghi database. Hãy sửa file và tải lại.</span>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <div class="card-footer px-0 text-sm">
        <a class="btn btn-sm btn-outline-secondary" href="index.php?com=product&act=man&type=<?=$type?>"><i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách sản phẩm</a>
    </div>
</section>
