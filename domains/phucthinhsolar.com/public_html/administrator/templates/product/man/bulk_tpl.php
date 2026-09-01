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
            Hãng mới: <strong><?=!empty($bulkResult['brand_create']) ? (int) $bulkResult['brand_create'] : 0?></strong> ·
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
                    <p>Excel có sheet hướng dẫn, danh mục đối chiếu và sheet hệ thống ẩn để phát hiện xung đột. Nên dùng Excel khi gửi qua lại giữa các bộ phận.</p>
                    <div class="mb-3">
                        <a class="btn btn-sm btn-outline-primary mr-2 mb-2" href="<?=$bulkBase?>&act=bulk_template&format=xlsx"><i class="far fa-file-excel mr-1"></i>Mẫu Excel</a>
                        <a class="btn btn-sm btn-outline-secondary mb-2" href="<?=$bulkBase?>&act=bulk_template&format=csv"><i class="fas fa-file-csv mr-1"></i>Mẫu CSV</a>
                    </div>
                    <div>
                        <a class="btn btn-sm bg-gradient-success mr-2 mb-2" href="<?=$bulkBase?>&act=bulk_export&format=xlsx"><i class="fas fa-file-export mr-1"></i>Xuất toàn bộ Excel</a>
                        <a class="btn btn-sm bg-gradient-info mb-2" href="<?=$bulkBase?>&act=bulk_export&format=csv"><i class="fas fa-file-csv mr-1"></i>Xuất toàn bộ CSV</a>
                    </div>
                    <small class="text-muted d-block mt-2">Để bổ sung/cập nhật sản phẩm hiện có, hãy dùng “Xuất toàn bộ Excel” để giữ snapshot kiểm tra xung đột. “Mẫu Excel” phù hợp tạo sản phẩm mới.</small>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <form method="post" action="<?=$bulkBase?>&act=bulk_preview" enctype="multipart/form-data">
        <?=adminCsrfField()?>
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
                <li>Có ID hoặc mã đã tồn tại: cập nhật. ID trống và mã mới: tạo sản phẩm; sản phẩm mới bắt buộc có mã và tên.</li>
                <li>Ô trống giữ nguyên dữ liệu hiện tại. Hệ thống không tự xóa dữ liệu chỉ vì ô Excel để trống.</li>
                <li>ID và tên ảnh nền xám chỉ để đối chiếu. Không sửa/xóa sheet ẩn <code>_HeThong</code> nếu muốn kiểm tra xung đột.</li>
                <li>Giá bán không được lớn hơn giá gốc; video chỉ nhận YouTube. Ảnh vẫn upload riêng trong trang sản phẩm.</li>
                <li>Thương hiệu chưa có trong hệ thống sẽ được liệt kê ở bước xem trước để chọn tạo mới, ghép với hãng có sẵn hoặc bỏ trống.</li>
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
                    <?php if (!empty($bulkPreview['counts']['draft_create'])) { ?><span class="badge badge-dark mr-1">Tạo tạm ẩn <?=(int) $bulkPreview['counts']['draft_create']?></span><?php } ?>
                    <?php if (!empty($bulkPreview['counts']['hide_update'])) { ?><span class="badge badge-danger mr-1">Chuyển sang ẩn <?=(int) $bulkPreview['counts']['hide_update']?></span><?php } ?>
                    <?php if (!empty($bulkPreview['counts']['show_update'])) { ?><span class="badge badge-success mr-1">Chuyển hiển thị <?=(int) $bulkPreview['counts']['show_update']?></span><?php } ?>
                    <span class="badge badge-secondary mr-1">Không đổi <?=(int) $bulkPreview['counts']['unchanged']?></span>
                    <span class="badge badge-info mr-1">Xung đột <?=(int) $bulkPreview['conflicts']?></span>
                    <span class="badge badge-danger">Lỗi <?=(int) $bulkPreview['errors']?></span>
                </div>
            </div>
            <div class="card-body pb-2">
                <?php if (!empty($bulkPreview['product_matches']) || !empty($bulkPreview['new_brands'])) { ?>
                    <div class="alert alert-warning mb-2"><i class="fas fa-magic mr-2"></i>Hệ thống đã gom các trường hợp cần quyết định ở phần xác nhận bên dưới. Không cần sửa rồi tải lại file Excel.</div>
                <?php } ?>
                <details <?=!empty($bulkPreview['errors']) ? 'open' : ''?>>
                    <summary class="font-weight-bold cs-pointer">Xem chi tiết <?=(int) count($bulkPreview['rows'])?> dòng sản phẩm</summary>
                    <div class="table-responsive mt-2" style="max-height:560px">
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
                                <?php if (!empty($row['conflicts'])) { ?><div class="text-info"><i class="fas fa-code-branch mr-1"></i><?=count($row['conflicts'])?> ô cần xác nhận bên dưới</div><?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                    </div>
                </details>
            </div>
            <div class="card-footer">
                <?php if ($bulkPreview['errors'] === 0 && !empty($bulkPreview['token'])) { ?>
                    <form method="post" action="<?=$bulkBase?>&act=bulk_commit" class="form-inline">
        <?=adminCsrfField()?>
                        <input type="hidden" name="product_bulk_csrf" value="<?=htmlspecialchars($bulkCsrf)?>">
                        <input type="hidden" name="preview_token" value="<?=htmlspecialchars($bulkPreview['token'])?>">
                        <?php if (!empty($bulkPreview['product_matches'])) { ?>
                            <div class="w-100 mb-3">
                                <div class="alert alert-info py-2 mb-2"><strong>Sản phẩm đã có cùng URL:</strong> hệ thống mặc định cập nhật đúng sản phẩm hiện có. Anh có thể đổi sang tạo bản mới hoặc bỏ qua từng dòng.</div>
                                <div class="table-responsive"><table class="table table-sm table-bordered bg-white mb-0">
                                    <thead><tr><th>Dòng</th><th>Trong Excel</th><th>Sản phẩm đang có</th><th>Cách xử lý</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($bulkPreview['product_matches'] as $matchKey => $match) { ?>
                                        <tr>
                                            <td><?=(int) $match['row']?></td>
                                            <td><strong><?=htmlspecialchars($match['incoming_name'])?></strong><br><small>Mã: <?=htmlspecialchars($match['incoming_code'])?> · URL: <?=htmlspecialchars($match['slug'])?></small></td>
                                            <td><strong>#<?=(int) $match['existing_id']?> — <?=htmlspecialchars($match['existing_name'])?></strong><br><small>Mã hiện tại: <?=htmlspecialchars($match['existing_code'] !== '' ? $match['existing_code'] : '(đang trống)')?></small></td>
                                            <td>
                                                <select required class="form-control form-control-sm" name="product_resolution[<?=htmlspecialchars($matchKey)?>]">
                                                    <option selected value="update:<?=(int) $match['existing_id']?>">Cập nhật sản phẩm hiện có (khuyến nghị)</option>
                                                    <option value="create">Tạo sản phẩm mới với URL khác</option>
                                                    <option value="skip">Bỏ qua dòng này</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table></div>
                            </div>
                        <?php } ?>
                        <?php if (!empty($bulkPreview['new_brands'])) { ?>
                            <div class="w-100 mb-3">
                                <div class="alert alert-warning py-2 mb-2"><strong>Thương hiệu mới trong file:</strong> hãy xác nhận từng tên. Hệ thống không tự tạo để tránh trùng do cách viết khác nhau.</div>
                                <div class="table-responsive"><table class="table table-sm table-bordered bg-white mb-0">
                                    <thead><tr><th>Thương hiệu trong Excel</th><th>Dòng sử dụng</th><th>Cách xử lý khi áp dụng</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($bulkPreview['new_brands'] as $brandKey => $newBrand) { ?>
                                        <tr>
                                            <td><strong><?=htmlspecialchars($newBrand['name'])?></strong></td>
                                            <td><span class="badge badge-light border"><?=(int) count(array_unique($newBrand['rows']))?> dòng</span></td>
                                            <td>
                                                <select required class="form-control form-control-sm" name="brand_resolution[<?=htmlspecialchars($brandKey)?>]">
                                                    <option selected value="create">Tạo thương hiệu mới “<?=htmlspecialchars($newBrand['name'])?>” (khuyến nghị)</option>
                                                    <?php foreach ($bulkPreview['available_brands'] as $existingBrand) { ?>
                                                        <option value="existing:<?=(int) $existingBrand['id']?>">Ghép vào <?=htmlspecialchars($existingBrand['namevi'])?></option>
                                                    <?php } ?>
                                                    <option value="blank">Bỏ trống thương hiệu</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table></div>
                            </div>
                        <?php } ?>
                        <?php if (!empty($bulkPreview['conflicts'])) { ?>
                            <div class="w-100 mb-3">
                                <div class="alert alert-info py-2">Website và Excel cùng thay đổi các ô dưới đây. Mặc định hệ thống giữ dữ liệu website; đánh dấu ô nếu muốn dùng dữ liệu Excel.</div>
                                <div class="table-responsive"><table class="table table-sm table-bordered bg-white mb-0">
                                    <thead><tr><th>Dòng</th><th>Sản phẩm</th><th>Trường</th><th>Website hiện tại</th><th>Excel</th><th>Dùng Excel</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($bulkPreview['rows'] as $row) { foreach ($row['conflicts'] as $conflict) { ?>
                                        <tr>
                                            <td><?=(int) $row['row']?></td>
                                            <td><?=htmlspecialchars($row['name'])?></td>
                                            <td><?=htmlspecialchars($conflict['label'])?></td>
                                            <td class="text-break"><?=htmlspecialchars(mb_strimwidth($conflict['current'], 0, 180, '…', 'UTF-8'))?></td>
                                            <td class="text-break"><?=htmlspecialchars(mb_strimwidth($conflict['incoming'], 0, 180, '…', 'UTF-8'))?></td>
                                            <td class="text-center"><input type="checkbox" name="overwrite_conflict[]" value="<?=htmlspecialchars($conflict['key'])?>"></td>
                                        </tr>
                                    <?php } } ?>
                                    </tbody>
                                </table></div>
                            </div>
                        <?php } ?>
                        <?php $hiddenProductCount = (int) $bulkPreview['counts']['draft_create'] + (int) $bulkPreview['counts']['hide_update'] + (int) $bulkPreview['counts']['archive']; ?>
                        <?php if ($hiddenProductCount > 0) { ?>
                            <div class="w-100 alert alert-danger py-2 mb-3">
                                <div><strong><i class="fas fa-eye-slash mr-1"></i>Excel yêu cầu <?=$hiddenProductCount?> sản phẩm ở trạng thái Tạm ẩn:</strong> tạo mới tạm ẩn <?=(int) $bulkPreview['counts']['draft_create']?>, chuyển sản phẩm đang hiển thị sang ẩn <?=(int) $bulkPreview['counts']['hide_update']?>, thao tác ẩn riêng <?=(int) $bulkPreview['counts']['archive']?>.</div>
                                <label class="mb-0 mt-2"><input required type="checkbox" name="confirm_hidden_products" value="1"> Tôi đã kiểm tra và đồng ý áp dụng các thay đổi trạng thái này.</label>
                            </div>
                        <?php } ?>
                        <label class="mr-3 mb-0"><input required type="checkbox" name="confirm_bulk_apply" value="1"> Tôi đã kiểm tra số lượng tạo mới, cập nhật và trạng thái ở trên.</label>
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
