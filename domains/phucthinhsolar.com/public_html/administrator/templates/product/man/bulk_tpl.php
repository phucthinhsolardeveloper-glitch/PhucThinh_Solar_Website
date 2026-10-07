<?php
$bulkBase = 'index.php?com=product&type='.$type;
$bulkCsrf = productBulkCsrfToken();
$bulkDataStatus = !empty($_GET['data_status']) ? (string)$_GET['data_status'] : '';
$bulkDataStatusDefinition = adminProductDataStatus($bulkDataStatus);
$bulkScopeQuery = $bulkDataStatusDefinition ? '&data_status='.urlencode($bulkDataStatus) : '';
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
    <style>
        .pts-bulk-hero{background:linear-gradient(135deg,#17385f,#245d8f);border-radius:12px;color:#fff;padding:20px 24px;margin-bottom:16px;box-shadow:0 8px 22px rgba(18,52,86,.14)}
        .pts-bulk-steps{display:flex;flex-wrap:wrap;gap:10px;margin-top:15px}.pts-bulk-step{display:flex;align-items:center;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.16);border-radius:20px;padding:6px 12px;font-size:12px}.pts-bulk-step b{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;margin-right:7px;border-radius:50%;background:#fff;color:#245d8f}
        .pts-bulk-card{border:0;border-radius:12px;box-shadow:0 5px 18px rgba(31,45,61,.08);overflow:hidden}.pts-bulk-card .card-header{border-bottom:1px solid #edf1f5;background:#fff;padding:16px 18px}.pts-bulk-card .card-body{padding:18px}.pts-bulk-prepare-card{overflow:visible;position:relative;z-index:20}.pts-bulk-export-menu{max-height:min(360px,65vh);overflow-y:auto;z-index:1050}.pts-bulk-primary-action{min-height:42px}.pts-bulk-help{border:1px solid #e5e9ef;border-radius:10px;background:#fff}.pts-bulk-help summary{cursor:pointer;padding:13px 16px;font-weight:600;color:#34495e}.pts-bulk-help[open] summary{border-bottom:1px solid #edf1f5}.pts-bulk-result{border:0;border-radius:12px;box-shadow:0 5px 18px rgba(40,167,69,.13)}.pts-bulk-zip-action .btn{height:38px}.pts-bulk-guide ol{padding-left:18px}.pts-bulk-guide li{margin-bottom:6px}.pts-bulk-guide code{white-space:nowrap}
        @media(max-width:767px){.pts-bulk-hero{padding:18px}.pts-bulk-steps{display:none}}
    </style>
    <div class="pts-bulk-hero">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div><h2 class="h5 font-weight-bold mb-1">Nhập và xuất sản phẩm</h2><div class="text-white-50">Cập nhật hàng loạt với bước xem trước và khả năng phục hồi khi có lỗi.</div></div>
            <a class="btn btn-sm btn-light mt-2 mt-md-0" href="index.php?com=product&act=man&type=<?=$type?>"><i class="fas fa-arrow-left mr-1"></i>Danh sách sản phẩm</a>
        </div>
        <div class="pts-bulk-steps"><span class="pts-bulk-step"><b>1</b>Chuẩn bị file</span><span class="pts-bulk-step"><b>2</b>Tải lên</span><span class="pts-bulk-step"><b>3</b>Kiểm tra thay đổi</span><span class="pts-bulk-step"><b>4</b>Xác nhận áp dụng</span></div>
    </div>
    <?php if (!empty($bulkMessage) && empty($bulkResult) && empty($bulkImageResult)) { ?>
        <div class="alert alert-<?=$bulkMessage['type']?> text-sm"><?=htmlspecialchars($bulkMessage['text'])?></div>
    <?php } ?>
    <?php if (!empty($bulkResult)) { ?>
        <div class="alert alert-success pts-bulk-result text-sm d-flex flex-wrap align-items-center mb-3">
            <i class="fas fa-check-circle fa-2x mr-3"></i>
            <div class="mr-auto"><strong class="d-block mb-1"><?=htmlspecialchars(!empty($bulkMessage['text']) ? $bulkMessage['text'] : 'Đã áp dụng dữ liệu thành công.')?></strong><span>Tạo <?=(int) $bulkResult['create']?> · Hãng mới <?=!empty($bulkResult['brand_create']) ? (int) $bulkResult['brand_create'] : 0?> · Cập nhật <?=(int) $bulkResult['update']?> · Ẩn <?=(int) $bulkResult['archive']?> · Bỏ qua <?=(int) $bulkResult['skip']?></span></div>
            <a class="btn btn-sm btn-success border-white mt-2 mt-md-0" href="index.php?com=product&act=man&type=<?=$type?>">Xem danh sách</a>
        </div>
    <?php } ?>
    <?php if (!empty($bulkImageResult)) { ?>
        <div class="alert alert-success text-sm"><i class="fas fa-images mr-2"></i>Đã bổ sung <strong><?=(int)$bulkImageResult['updated']?></strong> ảnh đại diện và <strong><?=(int)($bulkImageResult['gallery'] ?? 0)?></strong> ảnh phụ. Không ảnh cũ nào bị thay hoặc xóa.</div>
    <?php } ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card pts-bulk-card pts-bulk-prepare-card text-sm h-100">
                <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-file-export text-primary mr-2"></i>Chuẩn bị dữ liệu</h3></div>
                <div class="card-body">
                    <p class="text-muted">Cập nhật dữ liệu đang có bằng bản Excel xuất từ hệ thống; tạo danh sách mới bằng file mẫu trống.</p>
                    <div class="d-flex flex-wrap align-items-center">
                        <a class="btn btn-primary pts-bulk-primary-action mr-2 mb-2" href="<?=$bulkBase?>&act=bulk_export&format=xlsx"><i class="fas fa-file-export mr-2"></i>Xuất tất cả sản phẩm</a>
                        <?php if($bulkDataStatusDefinition) { ?><a class="btn btn-warning pts-bulk-primary-action mr-2 mb-2" href="<?=$bulkBase?>&act=bulk_export&format=xlsx<?=$bulkScopeQuery?>"><i class="fas fa-filter mr-2"></i>Xuất nhóm đang lọc</a><?php } ?>
                        <a class="btn btn-outline-primary pts-bulk-primary-action mr-2 mb-2" href="<?=$bulkBase?>&act=bulk_template&format=xlsx"><i class="far fa-file-excel mr-2"></i>Tải file mẫu</a>
                        <div class="dropdown mb-2"><button class="btn btn-light border pts-bulk-primary-action dropdown-toggle" type="button" data-toggle="dropdown">Xuất theo tình trạng dữ liệu</button><div class="dropdown-menu dropdown-menu-right pts-bulk-export-menu">
                            <?php foreach(adminProductDataStatusGroups() as $groupLabel => $statusKeys) { ?>
                                <h6 class="dropdown-header"><?=htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8')?></h6>
                                <?php foreach($statusKeys as $statusKey) { $statusDefinition = adminProductDataStatus($statusKey); if(!$statusDefinition) continue; ?><a class="dropdown-item" href="<?=$bulkBase?>&act=bulk_export&format=xlsx&data_status=<?=urlencode($statusKey)?>">Excel: <?=htmlspecialchars($statusDefinition['label'], ENT_QUOTES, 'UTF-8')?></a><?php } ?>
                            <?php } ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?=$bulkBase?>&act=bulk_export&format=csv">CSV: Tất cả sản phẩm</a>
                            <?php if($bulkDataStatusDefinition) { ?><a class="dropdown-item" href="<?=$bulkBase?>&act=bulk_export&format=csv<?=$bulkScopeQuery?>">CSV: Nhóm đang lọc</a><?php } ?>
                            <a class="dropdown-item" href="<?=$bulkBase?>&act=bulk_template&format=csv">Tải mẫu CSV</a>
                        </div></div>
                    </div>
                    <small class="text-muted d-block mt-2"><i class="fas fa-shield-alt mr-1"></i>Excel xuất từ hệ thống có snapshot để phát hiện người khác đã sửa dữ liệu trong lúc bạn làm việc.</small>
                    <?php if($bulkDataStatusDefinition) { ?><div class="alert alert-warning py-2 mt-3 mb-0"><i class="fas fa-filter mr-1"></i>Phạm vi xuất hiện tại: <strong><?=htmlspecialchars($bulkDataStatusDefinition['label'], ENT_QUOTES, 'UTF-8')?></strong>. File chỉ chứa các sản phẩm phù hợp tại thời điểm tải xuống.</div><?php } ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <form method="post" action="<?=$bulkBase?>&act=bulk_preview" enctype="multipart/form-data">
        <?=adminCsrfField()?>
                <input type="hidden" name="product_bulk_csrf" value="<?=htmlspecialchars($bulkCsrf)?>">
                <div class="card pts-bulk-card text-sm h-100">
                    <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-cloud-upload-alt text-primary mr-2"></i>Tải file lên kiểm tra</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="product_bulk_file">Chọn file Excel hoặc CSV</label>
                            <div class="custom-file my-custom-file">
                                <input required type="file" class="custom-file-input" name="product_bulk_file" id="product_bulk_file" accept=".csv,.xls,.xlsx">
                                <label class="custom-file-label" for="product_bulk_file">Chọn file tối đa 5 MB</label>
                            </div>
                            <small class="form-text text-muted"><i class="fas fa-info-circle mr-1"></i>Tối đa 500 dòng và 5 MB. Bước này chỉ phân tích, chưa thay đổi dữ liệu.</small>
                        </div>
                        <button class="btn btn-primary pts-bulk-primary-action btn-block"><i class="fas fa-search mr-2"></i>Kiểm tra file và xem thay đổi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <details class="pts-bulk-help text-sm mt-3">
        <summary><i class="far fa-question-circle text-primary mr-2"></i>Cần trợ giúp khi chuẩn bị file?</summary>
        <div class="p-3">
            <ul class="mb-0 pl-3">
                <li>Có ID hoặc mã đã tồn tại: cập nhật. ID trống và mã mới: tạo sản phẩm; sản phẩm mới bắt buộc có mã và tên.</li>
                <li>Ô trống giữ nguyên dữ liệu hiện tại. Hệ thống không tự xóa dữ liệu chỉ vì ô Excel để trống.</li>
                <li>ID và tên ảnh nền xám chỉ để đối chiếu. Không sửa/xóa sheet ẩn <code>_HeThong</code> nếu muốn kiểm tra xung đột.</li>
                <li>Giá bán không được lớn hơn giá gốc; video chỉ nhận YouTube. Ảnh vẫn upload riêng trong trang sản phẩm.</li>
                <li>Thương hiệu chưa có trong hệ thống sẽ được liệt kê ở bước xem trước để chọn tạo mới, ghép với hãng có sẵn hoặc bỏ trống.</li>
            </ul>
        </div>
    </details>

    <details class="pts-bulk-help pts-bulk-guide text-sm mt-3">
        <summary><i class="fas fa-book-open text-primary mr-2"></i>Hướng dẫn bàn giao: khi nào dùng Excel, khi nào dùng ZIP ảnh?</summary>
        <div class="p-3">
            <div class="row">
                <div class="col-lg-6">
                    <h4 class="h6 font-weight-bold">A. Cập nhật nội dung bằng Excel</h4>
                    <ol>
                        <li>Chọn <strong>Xuất tất cả sản phẩm</strong>, hoặc xuất đúng nhóm đang thiếu dữ liệu.</li>
                        <li>Chỉ sửa các ô nghiệp vụ trong sheet <code>SanPham</code>; giữ nguyên tiêu đề cột, ID và sheet ẩn <code>_HeThong</code>.</li>
                        <li>Ô trống nghĩa là <strong>giữ dữ liệu hiện tại</strong>. Chỉ nhập <code>__CLEAR__</code> khi thật sự muốn xóa giá trị cũ.</li>
                        <li>Tải file lên mục <strong>Tải file lên kiểm tra</strong>, đọc toàn bộ lỗi, cảnh báo và xung đột.</li>
                        <li>Chỉ xác nhận áp dụng khi số lượng tạo/sửa/ẩn đúng với phiếu bàn giao. Xuất lại để đối chiếu sau khi hoàn tất.</li>
                    </ol>
                </div>
                <div class="col-lg-6">
                    <h4 class="h6 font-weight-bold">B. Bổ sung ảnh bằng ZIP</h4>
                    <ol>
                        <li>Dùng đúng <strong>mã sản phẩm</strong>: <code>JD-T200-01.jpg</code> là ảnh chính; <code>JD-T200-02.jpg</code>, <code>-03.jpg</code>... là ảnh phụ.</li>
                        <li>Đặt tất cả ảnh ngay ở thư mục gốc của ZIP, không tạo thư mục con; chỉ dùng JPG, PNG hoặc WEBP.</li>
                        <li>Mỗi ZIP tối đa 20 MB/200 ảnh; mỗi ảnh tối đa 5 MB. Nên chia thành nhiều ZIP nhỏ và ghi lại lô đã làm.</li>
                        <li>Chọn <strong>Kiểm tra ZIP trước</strong>. Xử lý hết dòng “không thấy mã” hoặc lỗi tên file trước khi xác nhận.</li>
                        <li>Hệ thống chỉ bổ sung chỗ còn thiếu, không thay/xóa ảnh hiện hữu. Kiểm tra lại ảnh chính và gallery trên trang sản phẩm sau mỗi lô.</li>
                    </ol>
                </div>
            </div>
            <div class="alert alert-warning py-2 mb-0"><strong>Không gộp ảnh vào Excel.</strong> Excel dùng cho dữ liệu chữ/thông số/trạng thái; ZIP dùng cho file ảnh. Luôn xem trước trước khi ghi và không giao tài khoản Admin dùng chung.</div>
        </div>
    </details>

    <div class="card pts-bulk-card text-sm mt-3">
        <div class="card-header"><h3 class="card-title font-weight-bold"><i class="far fa-images text-primary mr-2"></i>Bổ sung ảnh sản phẩm bằng ZIP</h3></div>
        <div class="card-body">
            <div class="alert alert-info py-2"><strong>Quy tắc tên file:</strong> <code>JD-T200-01.jpg</code> là ảnh đại diện; <code>JD-T200-02.jpg</code>, <code>-03</code>... là ảnh phụ. Tên cũ <code>JD-T200.jpg</code> vẫn được hiểu là ảnh đại diện. Hệ thống không thay hay xóa ảnh hiện hữu.</div>
            <form method="post" action="<?=$bulkBase?>&act=bulk_image_preview" enctype="multipart/form-data" class="form-row">
                <?=adminCsrfField()?>
                <input type="hidden" name="product_bulk_csrf" value="<?=htmlspecialchars($bulkCsrf)?>">
                <div class="form-group col-lg-8 mb-lg-0"><label for="product_bulk_images">Chọn file ZIP</label><div class="custom-file"><input required type="file" class="custom-file-input" name="product_bulk_images" id="product_bulk_images" accept=".zip,application/zip"><label class="custom-file-label" for="product_bulk_images">Tối đa 20 MB, 200 ảnh</label></div><small class="form-text text-muted">Mỗi ảnh tối đa 5 MB; JPG, PNG hoặc WEBP. ZIP không chứa thư mục con.</small></div>
                <div class="form-group col-lg-4 mb-lg-0 pts-bulk-zip-action"><label class="d-none d-lg-block" aria-hidden="true">&nbsp;</label><button class="btn btn-outline-primary btn-block"><i class="fas fa-search mr-2"></i>Kiểm tra ZIP trước</button></div>
            </form>
        </div>
    </div>

    <?php if (!empty($bulkImagePreview)) { ?>
        <div class="card card-primary card-outline text-sm mt-3">
            <div class="card-header"><h3 class="card-title">Xem trước ảnh</h3><div class="card-tools"><span class="badge badge-success">Ảnh chính <?=(int)$bulkImagePreview['counts']['ready_main']?></span> <span class="badge badge-info">Ảnh phụ <?=(int)$bulkImagePreview['counts']['ready_gallery']?></span> <span class="badge badge-secondary">Đã có <?=(int)$bulkImagePreview['counts']['existing']?></span> <span class="badge badge-warning">Không thấy mã <?=(int)$bulkImagePreview['counts']['missing_product']?></span></div></div>
            <div class="card-body table-responsive p-0" style="max-height:420px"><table class="table table-sm table-hover mb-0"><thead class="thead-light sticky-top"><tr><th>File</th><th>Mã</th><th>Vai trò</th><th>Sản phẩm</th><th>Kết quả</th></tr></thead><tbody>
            <?php foreach($bulkImagePreview['rows'] as $row) { ?><tr><td><?=htmlspecialchars($row['entry'])?></td><td><code><?=htmlspecialchars($row['code'])?></code></td><td><?=$row['role'] === 'main' ? 'Ảnh chính' : 'Ảnh phụ #'.(int)$row['sequence']?></td><td><?=!empty($row['product']) ? htmlspecialchars($row['product']['namevi']) : '—'?></td><td class="<?=$row['status'] === 'ready' ? 'text-success' : ($row['status'] === 'existing' ? 'text-muted' : 'text-warning')?>"><?=htmlspecialchars($row['message'])?></td></tr><?php } ?>
            </tbody></table></div>
            <div class="card-footer">
                <?php if(empty($bulkImagePreview['errors']) && !empty($bulkImagePreview['token'])) { ?><form method="post" action="<?=$bulkBase?>&act=bulk_image_commit" class="d-flex flex-wrap align-items-center"><?=adminCsrfField()?><input type="hidden" name="product_bulk_csrf" value="<?=htmlspecialchars($bulkCsrf)?>"><input type="hidden" name="image_preview_token" value="<?=htmlspecialchars($bulkImagePreview['token'])?>"><label class="mr-3 mb-2"><input required type="checkbox" name="confirm_bulk_images" value="1"> Tôi xác nhận bổ sung <?=(int)$bulkImagePreview['counts']['ready_main']?> ảnh chính và <?=(int)$bulkImagePreview['counts']['ready_gallery']?> ảnh phụ.</label><button class="btn btn-sm btn-success mb-2"><i class="fas fa-check mr-2"></i>Bổ sung ảnh</button></form><?php } else { ?><span class="text-danger">ZIP còn lỗi; chưa có file hay database nào được thay đổi.</span><?php } ?>
            </div>
        </div>
    <?php } ?>

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
                                <?php if (isset($row['changes']['sectionsvi'])) { $sc = $row['changes']['sectionsvi']; ?>
                                <details class="mt-1">
                                    <summary class="text-primary cs-pointer"><i class="fas fa-align-left mr-1"></i>Mô tả dài (khối): xem thay đổi</summary>
                                    <div class="row no-gutters mt-1" style="font-size:12px">
                                        <div class="col-6 pr-1">
                                            <div class="text-muted font-weight-bold">Hiện tại</div>
                                            <pre class="bg-light border rounded p-2 mb-0" style="max-height:220px;overflow:auto;white-space:pre-wrap;word-break:break-word;"><?=htmlspecialchars((string) $sc['current'], ENT_QUOTES, 'UTF-8')?></pre>
                                        </div>
                                        <div class="col-6 pl-1">
                                            <div class="text-muted font-weight-bold">Sẽ đổi thành</div>
                                            <pre class="bg-light border rounded p-2 mb-0" style="max-height:220px;overflow:auto;white-space:pre-wrap;word-break:break-word;"><?=htmlspecialchars((string) $sc['incoming'], ENT_QUOTES, 'UTF-8')?></pre>
                                        </div>
                                    </div>
                                </details>
                                <?php } ?>
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
                        <input type="hidden" name="overwrite_conflict_json" value="" data-conflict-payload>
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
                                <?php
                                    $conflictFieldLabels = array();
                                    foreach ($bulkPreview['rows'] as $conflictRow) {
                                        foreach ($conflictRow['conflicts'] as $conflictField) {
                                            $conflictLabel = trim((string) $conflictField['label']);
                                            if ($conflictLabel !== '') $conflictFieldLabels[$conflictLabel] = true;
                                        }
                                    }
                                    $conflictFieldLabels = array_keys($conflictFieldLabels);
                                    sort($conflictFieldLabels, SORT_NATURAL | SORT_FLAG_CASE);
                                ?>
                                <div class="border rounded bg-light p-2 mb-2 d-flex flex-wrap align-items-center" data-conflict-toolbar>
                                    <strong class="mr-3 mb-1"><i class="fas fa-tasks mr-1"></i>Chọn nhanh:</strong>
                                    <button type="button" class="btn btn-sm btn-outline-primary mr-1 mb-1" data-conflict-select-all>Chọn tất cả ô xung đột</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mr-2 mb-1" data-conflict-clear>Tắt chọn tất cả</button>
                                    <?php if (!empty($conflictFieldLabels)) { ?>
                                        <select class="form-control form-control-sm mr-2 mb-1" style="max-width:280px" data-conflict-field-select aria-label="Chọn theo trường">
                                            <option value="">Chọn theo trường...</option>
                                            <?php foreach ($conflictFieldLabels as $conflictLabel) { ?>
                                                <option value="<?=htmlspecialchars($conflictLabel, ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($conflictLabel, ENT_QUOTES, 'UTF-8')?></option>
                                            <?php } ?>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-outline-info mr-2 mb-1" data-conflict-select-field>Chọn trường này</button>
                                    <?php } ?>
                                    <span class="small text-muted mb-1" data-conflict-count>Đã chọn 0 ô</span>
                                </div>
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
                                            <td class="text-center"><input type="checkbox" value="<?=htmlspecialchars($conflict['key'])?>" data-conflict-checkbox data-conflict-label="<?=htmlspecialchars($conflict['label'], ENT_QUOTES, 'UTF-8')?>"></td>
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

</section>
<?php if (!empty($bulkPreview['conflicts'])) { ?>
<script>
(function(){
    var toolbar = document.querySelector('[data-conflict-toolbar]');
    if (!toolbar) return;
    var boxes = Array.prototype.slice.call(document.querySelectorAll('[data-conflict-checkbox]'));
    var count = toolbar.querySelector('[data-conflict-count]');
    var fieldSelect = toolbar.querySelector('[data-conflict-field-select]');
    var payload = document.querySelector('[data-conflict-payload]');
    var form = payload ? payload.form : null;
    function updateCount(){
        var selected = boxes.filter(function(box){ return box.checked; }).length;
        if (count) count.textContent = 'Đã chọn ' + selected + '/' + boxes.length + ' ô';
    }
    toolbar.querySelector('[data-conflict-select-all]').addEventListener('click', function(){
        boxes.forEach(function(box){ box.checked = true; });
        updateCount();
    });
    toolbar.querySelector('[data-conflict-clear]').addEventListener('click', function(){
        boxes.forEach(function(box){ box.checked = false; });
        updateCount();
    });
    var selectFieldButton = toolbar.querySelector('[data-conflict-select-field]');
    if (selectFieldButton && fieldSelect) {
        selectFieldButton.addEventListener('click', function(){
            var label = fieldSelect.value;
            if (!label) return;
            boxes.forEach(function(box){
                if (box.getAttribute('data-conflict-label') === label) box.checked = true;
            });
            updateCount();
        });
    }
    boxes.forEach(function(box){ box.addEventListener('change', updateCount); });
    if (form && payload) {
        form.addEventListener('submit', function(){
            payload.value = JSON.stringify(boxes.filter(function(box){ return box.checked; }).map(function(box){ return box.value; }));
        });
    }
    updateCount();
})();
</script>
<?php } ?>
