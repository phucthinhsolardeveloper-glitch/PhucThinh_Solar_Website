<?php
$mailTemplateDefinitions = isset($mailTemplateDefinitions) ? $mailTemplateDefinitions : array();
$mailTemplateValues = isset($mailTemplateValues) ? $mailTemplateValues : array();
$mailTemplateStatus = isset($mailTemplateStatus) ? $mailTemplateStatus : array();
$mailEsc = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
?>
<section class="content-header text-sm">
    <div class="container-fluid"><ol class="breadcrumb float-sm-left">
        <li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
        <li class="breadcrumb-item active">Mẫu email</li>
    </ol></div>
</section>
<section class="content">
    <?=$flash->getMessages('admin')?>
    <div class="alert <?=!empty($mailTemplateStatus['configured']) ? 'alert-success' : 'alert-warning'?> text-sm">
        <strong><?=!empty($mailTemplateStatus['configured']) ? 'SMTP production đã sẵn sàng.' : 'SMTP production chưa đủ cấu hình.'?></strong>
        From: <?=$mailEsc($mailTemplateStatus['from'])?> · Nhận nội bộ: <?=$mailEsc($mailTemplateStatus['admin_to'])?>.
        Nội dung được phép sửa là văn bản thuần; bố cục, dữ liệu nghiệp vụ và thông tin đăng nhập SMTP không thể sửa tại đây.
    </div>
    <?php if(empty($mailTemplateStatus['test_to'])) { ?>
        <div class="alert alert-info text-sm">Chưa đặt <code>test_to</code> trong secret production nên nút gửi thử đang khóa. Địa chỉ nhận thử không lấy từ form để tránh gửi nhầm ra ngoài.</div>
    <?php } ?>
    <form method="post" action="index.php?com=mail_template&amp;act=save">
        <?=adminCsrfField()?>
        <div class="card-footer text-sm sticky-top mb-3">
            <button type="submit" class="btn btn-sm bg-gradient-primary"><i class="far fa-save mr-2"></i>Lưu mẫu email</button>
        </div>
        <div class="row">
            <?php foreach($mailTemplateDefinitions as $key => $definition) { $value = $mailTemplateValues[$key]; ?>
                <div class="col-xl-6">
                    <div class="card card-primary card-outline text-sm">
                        <div class="card-header d-flex align-items-center">
                            <h3 class="card-title mb-0"><?=$mailEsc($definition['label'])?></h3>
                            <span class="badge badge-light ml-2"><?=$mailEsc($definition['audience'])?></span>
                            <a class="btn btn-xs btn-outline-primary ml-auto" target="_blank" rel="noopener" href="index.php?com=mail_template&amp;act=preview&amp;key=<?=$mailEsc($key)?>"><i class="far fa-eye mr-1"></i>Xem trước</a>
                        </div>
                        <div class="card-body">
                            <div class="form-group"><label>Tiêu đề email</label><input maxlength="180" class="form-control" name="mail_templates[<?=$mailEsc($key)?>][subject]" value="<?=$mailEsc($value['subject'])?>"></div>
                            <div class="form-group"><label>Tiêu đề nội dung</label><input maxlength="180" class="form-control" name="mail_templates[<?=$mailEsc($key)?>][heading]" value="<?=$mailEsc($value['heading'])?>"></div>
                            <div class="form-group"><label>Lời mở đầu</label><textarea maxlength="700" rows="3" class="form-control" name="mail_templates[<?=$mailEsc($key)?>][intro]"><?=$mailEsc($value['intro'])?></textarea></div>
                            <div class="form-group"><label>Lời kết</label><textarea maxlength="500" rows="2" class="form-control" name="mail_templates[<?=$mailEsc($key)?>][closing]"><?=$mailEsc($value['closing'])?></textarea></div>
                            <small class="text-muted">Biến dùng được: <?=$mailEsc(implode(', ', $definition['tokens']))?></small>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="key" value="<?=$mailEsc($key)?>" formaction="index.php?com=mail_template&amp;act=save_test" class="btn btn-xs btn-outline-secondary" <?=empty($mailTemplateStatus['test_to']) || empty($mailTemplateStatus['configured']) ? 'disabled' : ''?>><i class="far fa-paper-plane mr-1"></i>Gửi thử tới <?=$mailEsc($mailTemplateStatus['test_to'])?></button>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </form>
</section>
