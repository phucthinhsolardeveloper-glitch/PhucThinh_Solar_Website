<?php
$isEdit = !empty($campaign['id']);
$linkMan = 'index.php?com=product_campaign&act=man&type='.urlencode($type);
$linkSave = 'index.php?com=product_campaign&act=save&type='.urlencode($type);
$selectedScopes = array('all'=>array(), 'list'=>array(), 'cat'=>array(), 'brand'=>array(), 'product'=>array());
foreach($campaignScopes as $scope) $selectedScopes[$scope['scope_type']][] = (int)$scope['scope_id'];
$scopeAll = !empty($selectedScopes['all']);
if(!function_exists('productCampaignScopeLabel'))
{
    function productCampaignScopeLabel($label, $status)
    {
        $statuses = array_filter(array_map('trim', explode(',', (string)$status)));
        return $label.(!in_array('hienthi', $statuses, true) ? ' (đang ẩn)' : '');
    }
}
?>
<section class="content-header text-sm"><div class="container-fluid"><div class="row"><ol class="breadcrumb float-sm-left">
    <li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
    <li class="breadcrumb-item"><a href="<?=$linkMan?>">Chiến dịch khung sản phẩm</a></li>
    <li class="breadcrumb-item active"><?=$isEdit ? 'Chỉnh sửa' : 'Thêm mới'?></li>
</ol></div></div></section>
<section class="content">
    <form class="validation-form" novalidate method="post" action="<?=$linkSave?>" enctype="multipart/form-data">
        <?=adminCsrfField()?>
        <input type="hidden" name="id" value="<?=$isEdit ? (int)$campaign['id'] : 0?>">
        <div class="card-footer text-sm sticky-top">
            <button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu chiến dịch</button>
            <a class="btn btn-sm bg-gradient-secondary ml-1" href="<?=$linkMan?>"><i class="fas fa-sign-out-alt mr-2"></i>Thoát</a>
        </div>
        <?=$flash->getMessages('admin')?>
        <div class="row">
            <div class="col-xl-4">
                <div class="card card-primary card-outline text-sm">
                    <div class="card-header"><h3 class="card-title">Khung PNG của chiến dịch</h3></div>
                    <div class="card-body">
                        <div class="pts-promo-frame-admin-preview mb-3">
                            <?=$func->getImage(array('isLazy'=>false, 'class'=>'pts-promo-frame-admin-base', 'sizes'=>'300x300x2', 'upload'=>'', 'image'=>'', 'alt'=>'Ảnh sản phẩm mẫu'))?>
                            <img id="campaign-frame-preview" class="pts-promo-frame-admin-overlay<?=empty($campaign['photo']) ? ' d-none' : ''?>" src="<?=!empty($campaign['photo']) ? UPLOAD_PHOTO_L.$campaign['photo'] : ''?>" alt="" aria-hidden="true">
                        </div>
                        <div class="custom-file my-custom-file">
                            <input type="file" class="custom-file-input" name="file" id="campaign-file" accept=".png,image/png" <?=$isEdit ? '' : 'required'?>>
                            <label class="custom-file-label" data-browse="Chọn" for="campaign-file"><?=$isEdit ? 'Chọn PNG mới nếu cần thay' : 'Chọn khung PNG'?></label>
                        </div>
                        <small class="form-text text-muted">Ảnh vuông 600×600, nền trong suốt, phần giữa để trống. Thay ảnh ở đây chỉ ảnh hưởng chiến dịch này.</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card card-primary card-outline text-sm">
                    <div class="card-header"><h3 class="card-title">Thông tin và lịch chạy</h3></div>
                    <div class="card-body row">
                        <div class="form-group col-md-8"><label for="campaign-name">Tên nội bộ:</label><input class="form-control text-sm" id="campaign-name" name="data[name]" maxlength="255" required value="<?=htmlspecialchars(isset($campaign['name']) ? $campaign['name'] : '', ENT_QUOTES, 'UTF-8')?>"></div>
                        <div class="form-group col-md-4"><label for="campaign-priority">Mức ưu tiên:</label><input type="number" min="-1000" max="1000" class="form-control text-sm" id="campaign-priority" name="data[priority]" value="<?=isset($campaign['priority']) ? (int)$campaign['priority'] : 0?>"><small class="text-muted">Số lớn hơn sẽ thắng.</small></div>
                        <div class="form-group col-md-6"><label for="campaign-start">Bắt đầu:</label><input type="datetime-local" class="form-control text-sm" id="campaign-start" name="data[start_at]" value="<?=!empty($campaign['start_at']) ? date('Y-m-d\TH:i', (int)$campaign['start_at']) : ''?>"></div>
                        <div class="form-group col-md-6"><label for="campaign-end">Kết thúc:</label><input type="datetime-local" class="form-control text-sm" id="campaign-end" name="data[end_at]" value="<?=!empty($campaign['end_at']) ? date('Y-m-d\TH:i', (int)$campaign['end_at']) : ''?>"></div>
                        <div class="form-group col-12 mb-0"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" name="enabled" id="campaign-enabled" value="1" <?=(!$isEdit || in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$campaign['status']))), true)) ? 'checked' : ''?>><label class="custom-control-label font-weight-bold" for="campaign-enabled">Bật chiến dịch</label></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-primary card-outline text-sm">
            <div class="card-header"><h3 class="card-title">Phạm vi áp dụng</h3></div>
            <div class="card-body">
                <div class="alert alert-warning">Chỉ sản phẩm khớp ít nhất một phạm vi dưới đây mới nhận khung. Chọn sản phẩm riêng không làm thay đổi ảnh hay trạng thái của sản phẩm khác.</div>
                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" name="scope_all" id="campaign-scope-all" value="1" <?=$scopeAll ? 'checked' : ''?>>
                    <label class="custom-control-label font-weight-bold" for="campaign-scope-all">Áp dụng toàn bộ sản phẩm</label>
                </div>
                <div class="row" id="campaign-scoped-pickers">
                    <div class="form-group col-lg-6"><label>Danh mục cấp 1:</label><select class="form-control select2" multiple name="scopes[list][]" data-placeholder="Chọn danh mục cấp 1" style="width:100%">
                        <?php foreach($campaignLists as $row) { ?><option value="<?=(int)$row['id']?>" <?=in_array((int)$row['id'], $selectedScopes['list'], true) ? 'selected' : ''?>><?=htmlspecialchars(productCampaignScopeLabel($row['namevi'], $row['status']), ENT_QUOTES, 'UTF-8')?></option><?php } ?>
                    </select></div>
                    <div class="form-group col-lg-6"><label>Danh mục cấp 2:</label><select class="form-control select2" multiple name="scopes[cat][]" data-placeholder="Chọn danh mục cấp 2" style="width:100%">
                        <?php foreach($campaignCats as $row) { ?><option value="<?=(int)$row['id']?>" <?=in_array((int)$row['id'], $selectedScopes['cat'], true) ? 'selected' : ''?>><?=htmlspecialchars(productCampaignScopeLabel(($row['parent_name'] ? $row['parent_name'].' → ' : '').$row['namevi'], $row['status']), ENT_QUOTES, 'UTF-8')?></option><?php } ?>
                    </select></div>
                    <div class="form-group col-lg-6"><label>Thương hiệu:</label><select class="form-control select2" multiple name="scopes[brand][]" data-placeholder="Chọn thương hiệu" style="width:100%">
                        <?php foreach($campaignBrands as $row) { ?><option value="<?=(int)$row['id']?>" <?=in_array((int)$row['id'], $selectedScopes['brand'], true) ? 'selected' : ''?>><?=htmlspecialchars(productCampaignScopeLabel($row['namevi'], $row['status']), ENT_QUOTES, 'UTF-8')?></option><?php } ?>
                    </select></div>
                    <div class="form-group col-lg-6"><label>Sản phẩm chọn riêng:</label><select class="form-control select2" multiple name="scopes[product][]" data-placeholder="Tìm theo mã hoặc tên sản phẩm" style="width:100%">
                        <?php foreach($campaignProducts as $row) { ?><option value="<?=(int)$row['id']?>" <?=in_array((int)$row['id'], $selectedScopes['product'], true) ? 'selected' : ''?>><?=htmlspecialchars(productCampaignScopeLabel((!empty($row['code']) ? '['.$row['code'].'] ' : '').$row['namevi'], $row['status']), ENT_QUOTES, 'UTF-8')?></option><?php } ?>
                    </select></div>
                </div>
            </div>
        </div>
        <div class="card-footer text-sm"><button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu chiến dịch</button></div>
    </form>
</section>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var all = document.getElementById('campaign-scope-all');
    var pickers = document.getElementById('campaign-scoped-pickers');
    function sync(){ if(pickers) pickers.style.opacity = all && all.checked ? '.45' : '1'; }
    if(all) all.addEventListener('change', sync);
    sync();
});
</script>
