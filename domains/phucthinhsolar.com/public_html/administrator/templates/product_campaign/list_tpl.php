<?php
$linkAdd = 'index.php?com=product_campaign&act=add&type='.urlencode($type);
$now = time();
$scopeLabels = array('all' => 'Toàn bộ', 'list' => 'Danh mục cấp 1', 'cat' => 'Danh mục cấp 2', 'brand' => 'Thương hiệu', 'product' => 'Sản phẩm chọn riêng');
?>
<section class="content-header text-sm">
    <div class="container-fluid"><div class="row"><ol class="breadcrumb float-sm-left">
        <li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
        <li class="breadcrumb-item active">Chiến dịch khung sản phẩm</li>
    </ol></div></div>
</section>
<section class="content">
    <div class="card-footer text-sm sticky-top">
        <a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkAdd?>"><i class="fas fa-plus mr-2"></i>Thêm chiến dịch</a>
        <a class="btn btn-sm btn-outline-secondary ml-1" href="index.php?com=product&act=man&type=san-pham&data_status=campaign_active"><i class="fas fa-layer-group mr-1"></i>Xem sản phẩm đang có khung</a>
    </div>
    <?=$flash->getMessages('admin')?>
    <div class="alert alert-info text-sm">
        Mỗi chiến dịch giữ một PNG riêng và chỉ áp vào phạm vi đã chọn. Ưu tiên cao hơn thắng; nếu bằng nhau thì phạm vi cụ thể hơn thắng. Khung riêng đang lưu trong sản phẩm luôn là override cao nhất.
    </div>
    <div class="card card-primary card-outline text-sm">
        <div class="card-header"><h3 class="card-title">Danh sách chiến dịch</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead><tr>
                    <th>Khung</th><th>Chiến dịch</th><th class="text-center">Ưu tiên</th><th>Phạm vi</th><th>Thời gian</th><th class="text-center">Trạng thái</th><th class="text-center">Thao tác</th>
                </tr></thead>
                <tbody>
                <?php if(empty($campaigns)) { ?>
                    <tr><td colspan="7" class="text-center py-4">Chưa có chiến dịch. Thêm một chiến dịch rồi chọn danh mục, thương hiệu hoặc sản phẩm cần áp khung.</td></tr>
                <?php } else { foreach($campaigns as $campaign) {
                    $enabled = in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$campaign['status']))), true);
                    $scheduled = (!$campaign['start_at'] || (int)$campaign['start_at'] <= $now) && (!$campaign['end_at'] || (int)$campaign['end_at'] >= $now);
                    $scopeTypes = array_filter(explode(',', (string)$campaign['scope_types']));
                ?>
                    <tr>
                        <td class="align-middle"><?=$func->getImage(array('isLazy'=>false, 'class'=>'rounded img-preview', 'sizes'=>'70x70x2', 'upload'=>UPLOAD_PHOTO_L, 'image'=>$campaign['photo'], 'alt'=>''))?></td>
                        <td class="align-middle"><strong><?=htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8')?></strong></td>
                        <td class="align-middle text-center"><?=(int)$campaign['priority']?></td>
                        <td class="align-middle">
                            <span class="badge badge-primary"><?=(int)$campaign['scope_count']?> mục</span>
                            <?php foreach($scopeTypes as $scopeType) { ?><span class="badge badge-light border"><?=htmlspecialchars(isset($scopeLabels[$scopeType]) ? $scopeLabels[$scopeType] : $scopeType, ENT_QUOTES, 'UTF-8')?></span><?php } ?>
                        </td>
                        <td class="align-middle text-nowrap">
                            <small>Từ: <?=$campaign['start_at'] ? date('d/m/Y H:i', (int)$campaign['start_at']) : 'Ngay khi bật'?><br>Đến: <?=$campaign['end_at'] ? date('d/m/Y H:i', (int)$campaign['end_at']) : 'Không giới hạn'?></small>
                        </td>
                        <td class="align-middle text-center">
                            <?php if(!$enabled) { ?><span class="badge badge-secondary">Đang tắt</span>
                            <?php } elseif(!$scheduled) { ?><span class="badge badge-warning">Ngoài lịch</span>
                            <?php } else { ?><span class="badge badge-success">Đang chạy</span><?php } ?>
                        </td>
                        <td class="align-middle text-center"><a class="text-primary" href="index.php?com=product_campaign&act=edit&type=<?=urlencode($type)?>&id=<?=(int)$campaign['id']?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a></td>
                    </tr>
                <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
