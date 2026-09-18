<?php
$mailTemplateDefinitions = isset($mailTemplateDefinitions) ? $mailTemplateDefinitions : array();
$mailTemplateValues = isset($mailTemplateValues) ? $mailTemplateValues : array();
$mailTemplateStatus = isset($mailTemplateStatus) ? $mailTemplateStatus : array();
$mailEsc = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
?>
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active">Mẫu email</li>
            </ol>
        </div>
    </div>
</section>
<section class="content">
    <?=$flash->getMessages('admin')?>
    <form method="post" action="index.php?com=mail_template&amp;act=save">
        <?=adminCsrfField()?>
        <div class="card card-outline card-primary text-sm mb-3">
            <div class="card-body py-3 d-flex flex-column flex-md-row align-items-md-center">
                <div class="mr-md-auto mb-3 mb-md-0">
                    <strong class="d-block mb-1">Nội dung email tự động</strong>
                    <span class="text-muted">Chỉnh câu chữ gửi cho khách hàng và bộ phận phụ trách. Bố cục, dữ liệu nghiệp vụ và thông tin gửi email luôn được hệ thống bảo vệ.</span>
                </div>
                <button type="submit" class="btn btn-sm bg-gradient-primary flex-shrink-0"><i class="far fa-save mr-2"></i>Lưu mẫu email</button>
            </div>
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
                        <?php if(!empty($mailTemplateStatus['test_to']) && !empty($mailTemplateStatus['configured'])) { ?>
                            <div class="card-footer">
                                <button type="submit" name="key" value="<?=$mailEsc($key)?>" formaction="index.php?com=mail_template&amp;act=save_test" class="btn btn-xs btn-outline-secondary"><i class="far fa-paper-plane mr-1"></i>Gửi thử tới <?=$mailEsc($mailTemplateStatus['test_to'])?></button>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </form>
</section>
