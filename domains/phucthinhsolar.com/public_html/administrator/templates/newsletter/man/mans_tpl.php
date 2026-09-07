<?php
    $linkMan = "index.php?com=newsletter&act=man&type=".$type;
    $linkAdd = "index.php?com=newsletter&act=add&type=".$type;
    $linkEdit = "index.php?com=newsletter&act=edit&type=".$type;
    $linkDelete = "index.php?com=newsletter&act=delete&type=".$type;

    /* Ẩn nút mà tài khoản này không có quyền dùng, thay vì để họ bấm rồi bị
       cổng chặn đá về trang chủ. Tài khoản cấp cao (role 3) không bị ẩn gì. */
    $canAdd     = adminUiCan('newsletter', 'add', '', $type);
    $canEdit    = adminUiCan('newsletter', 'edit', '', $type, true);
    $canDelete  = adminUiCan('newsletter', 'delete', '', $type, true);
    $hideAdd    = $canAdd ? '' : ' d-none';
    $hideEdit   = $canEdit ? '' : ' d-none';
    $hideDelete = $canDelete ? '' : ' d-none';
	$linkSearch = $linkMan.(!empty($_GET['confirm_status']) ? '&confirm_status='.urlencode($_GET['confirm_status']) : '');
	$linkConfirmStatus = $linkMan.(!empty($_GET['keyword']) ? '&keyword='.urlencode($_GET['keyword']) : '');
	$isDealerRegistration = ($type === 'dangkynhantin2');
	$currentConfirmStatus = isset($_GET['confirm_status']) ? (string) $_GET['confirm_status'] : '';
	$newsletterStatusClass = function($confirmStatus)
	{
		$statusKey = (string) $confirmStatus;
		if($statusKey === '1') return 'pts-status-received';
		if($statusKey === '2') return 'pts-status-processing';
		if($statusKey === '3') return 'pts-status-completed';
		return 'pts-status-new';
	};
?>
<!-- Content Header -->
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active"><?=htmlspecialchars($config['newsletter'][$type]['title_main'], ENT_QUOTES, 'UTF-8')?></li>
            </ol>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <?php if(!empty($config['newsletter'][$type]['pipeline']) && isset($newsletterStatusCounts)) { ?>
    <div class="row mb-3">
        <?php
            $pipelineCards = array(
                'all' => array('Tất cả', 'secondary', '', 'text-white'),
                'pending' => array('Mới', 'danger', 'pending', 'text-white')
            );
            foreach($config['newsletter'][$type]['confirm_status'] as $statusKey => $statusLabel)
            {
                $pipelineStyle = array(
                    '1' => array('primary', 'text-white'),
                    '2' => array('warning', 'text-dark'),
                    '3' => array('success', 'text-white')
                );
                $style = isset($pipelineStyle[(string)$statusKey]) ? $pipelineStyle[(string)$statusKey] : array('info', 'text-white');
                $pipelineCards[(string)$statusKey] = array($statusLabel, $style[0], (string)$statusKey, $style[1]);
            }
        ?>
        <?php foreach($pipelineCards as $cardKey => $pipelineCard) { $cardUrl = $linkMan.($pipelineCard[2] !== '' ? '&confirm_status='.urlencode($pipelineCard[2]) : ''); ?>
        <div class="col-6 col-lg">
            <a class="small-box pts-newsletter-pipeline-card bg-<?=$pipelineCard[1]?> <?=$pipelineCard[3]?> mb-2<?=($currentConfirmStatus ?? '') === $pipelineCard[2] ? ' is-active' : ''?>" href="<?=$cardUrl?>">
                <div class="inner py-3"><h3><?=(int)$newsletterStatusCounts[$cardKey]?></h3><p class="mb-0"><?=htmlspecialchars($pipelineCard[0], ENT_QUOTES, 'UTF-8')?></p></div>
            </a>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
    <div class="card-footer text-sm sticky-top pts-newsletter-toolbar d-flex flex-wrap align-items-center">
        <?php if(isset($config['newsletter'][$type]['is_send']) && $config['newsletter'][$type]['is_send'] == true) { ?>
           <a class="btn btn-sm bg-gradient-success text-white" id="send-email" title="Gửi email"><i class="fas fa-paper-plane mr-2"></i>Gửi email</a>
        <?php } ?>
        <a class="btn btn-sm bg-gradient-primary text-white<?=$hideAdd?>" href="<?=$linkAdd?>" title="Thêm mới"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
        <a class="btn btn-sm bg-gradient-danger text-white<?=$hideDelete?>" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa tất cả"><i class="far fa-trash-alt mr-2"></i>Xóa tất cả</a>
        <?php if(!empty($config['newsletter'][$type]['confirm_status'])) { ?>
        <div class="form-inline pts-newsletter-filter ml-3">
            <select class="form-control form-control-sm" aria-label="Lọc tình trạng xử lý" onchange="window.location.href='<?=$linkConfirmStatus?>&confirm_status='+encodeURIComponent(this.value)">
                <option value="" <?=$currentConfirmStatus === '' ? 'selected' : ''?>>Tất cả tình trạng</option>
                <option value="pending" <?=$currentConfirmStatus === 'pending' ? 'selected' : ''?>>Mới / chưa xử lý</option>
                <?php foreach($config['newsletter'][$type]['confirm_status'] as $statusKey => $statusLabel) { ?>
                    <option value="<?=$statusKey?>" <?=$currentConfirmStatus === (string) $statusKey ? 'selected' : ''?>><?=$statusLabel?></option>
                <?php } ?>
            </select>
        </div>
        <?php } ?>
        <div class="form-inline form-search pts-newsletter-search ml-3">
            <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar text-sm" type="search" id="keyword" placeholder="Tên, email, điện thoại, công ty" aria-label="Tìm kiếm" value="<?=(isset($_GET['keyword'])) ? htmlspecialchars($_GET['keyword'], ENT_QUOTES, 'UTF-8') : ''?>" onkeypress="doEnter(event,'keyword','<?=$linkSearch?>')">
                <div class="input-group-append bg-primary rounded-right">
                    <button class="btn btn-navbar text-white" type="button" onclick="onSearch('keyword','<?=$linkSearch?>')">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card card-primary card-outline text-sm mb-0">
        <div class="card-header">
            <h3 class="card-title">Danh sách <?=$config['newsletter'][$type]['title_main']?></h3>
            <?php if(isset($config['newsletter'][$type]['is_send']) && $config['newsletter'][$type]['is_send'] == true) { ?>
                <p class="d-block text-secondary w-100 float-left mb-0 mt-1">Chọn email sau đó kéo xuống dưới cùng danh sách này để có thể thiết lập nội dung email muốn gửi đi.</p>
            <?php } ?>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover pts-newsletter-table<?=$isDealerRegistration ? ' pts-newsletter-table--dealer' : ''?>">
                <?php if($isDealerRegistration) { ?>
                    <colgroup>
                        <col class="pts-col-select">
                        <col class="pts-col-dealer">
                        <col class="pts-col-contact">
                        <col class="pts-col-address">
                        <col class="pts-col-created">
                        <col class="pts-col-status">
                        <col class="pts-col-actions">
                    </colgroup>
                <?php } ?>
                <thead>
                    <tr>
                        <th class="align-middle" width="5%">
                            <div class="custom-control custom-checkbox my-checkbox">
                                <input type="checkbox" class="custom-control-input" id="selectall-checkbox">
                                <label for="selectall-checkbox" class="custom-control-label"></label>
                            </div>
                        </th>
                        <?php if($isDealerRegistration) { ?>
                            <th class="align-middle">Người đăng ký / Doanh nghiệp</th>
                            <th class="align-middle">Thông tin liên hệ</th>
                            <th class="align-middle">Khu vực</th>
                        <?php } else { ?>
                            <th class="align-middle text-center" width="10%">STT</th>
                            <?php if(isset($config['newsletter'][$type]['show_name']) && $config['newsletter'][$type]['show_name'] == true) { ?>
                                <th class="align-middle">Họ tên</th>
                            <?php } ?>
                            <?php if(isset($config['newsletter'][$type]['email']) && $config['newsletter'][$type]['email'] == true) { ?>
                                <th class="align-middle">Email</th>
                            <?php } ?>
                            <?php if(isset($config['newsletter'][$type]['show_phone']) && $config['newsletter'][$type]['show_phone'] == true) { ?>
                                <th class="align-middle">Điện thoại</th>
                            <?php } ?>
                            <?php if(isset($config['newsletter'][$type]['file']) && $config['newsletter'][$type]['file'] == true) { ?>
                                <th class="align-middle">Download</th>
                            <?php } ?>
                        <?php } ?>
                        <?php if(isset($config['newsletter'][$type]['show_date']) && $config['newsletter'][$type]['show_date'] == true) { ?>
                            <th class="align-middle"><?=$isDealerRegistration ? 'Ngày đăng ký' : 'Ngày tạo'?></th>
                        <?php } ?>
                        <?php if(isset($config['newsletter'][$type]['confirm_status']) && count($config['newsletter'][$type]['confirm_status']) > 0) { ?>
                            <th class="align-middle">Tình trạng</th>
                        <?php } ?>
                        <th class="align-middle text-center">Thao tác</th>
                    </tr>
                </thead>
                <?php if(empty($items)) { ?>
                    <tbody><tr><td colspan="100" class="text-center">Không có dữ liệu</td></tr></tbody>
                <?php } else { ?>
                    <tbody>
                        <?php for($i=0;$i<count($items);$i++) { ?>
                            <tr>
                                <td class="align-middle">
                                    <div class="custom-control custom-checkbox my-checkbox">
                                        <input type="checkbox" class="custom-control-input select-checkbox" id="select-checkbox-<?=$items[$i]['id']?>" value="<?=$items[$i]['id']?>">
                                        <label for="select-checkbox-<?=$items[$i]['id']?>" class="custom-control-label"></label>
                                    </div>
                                </td>
                                <?php if($isDealerRegistration) { ?>
                                    <td class="align-middle pts-dealer-identity">
                                        <a class="pts-dealer-name"<?=$canEdit ? ' href="'.$linkEdit.'&id='.$items[$i]['id'].'"' : ''?> title="<?=htmlspecialchars($items[$i]['fullname'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($items[$i]['fullname'], ENT_QUOTES, 'UTF-8')?></a>
                                        <span class="pts-dealer-company"><i class="far fa-building" aria-hidden="true"></i><?=!empty($items[$i]['company']) ? htmlspecialchars($items[$i]['company'], ENT_QUOTES, 'UTF-8') : 'Chưa ghi doanh nghiệp'?></span>
                                    </td>
                                    <td class="align-middle pts-dealer-contact">
                                        <a class="pts-dealer-phone" href="tel:<?=htmlspecialchars(preg_replace('/[^0-9+]/', '', $items[$i]['phone']), ENT_QUOTES, 'UTF-8')?>"><i class="fas fa-phone-alt" aria-hidden="true"></i><?=htmlspecialchars($items[$i]['phone'], ENT_QUOTES, 'UTF-8')?></a>
                                        <a class="pts-dealer-email" href="mailto:<?=htmlspecialchars($items[$i]['email'], ENT_QUOTES, 'UTF-8')?>" title="Gửi email"><i class="far fa-envelope" aria-hidden="true"></i><?=htmlspecialchars($items[$i]['email'], ENT_QUOTES, 'UTF-8')?></a>
                                    </td>
                                    <td class="align-middle"><span class="pts-dealer-address" title="<?=htmlspecialchars($items[$i]['address'], ENT_QUOTES, 'UTF-8')?>"><i class="fas fa-map-marker-alt" aria-hidden="true"></i><?=!empty($items[$i]['address']) ? htmlspecialchars($items[$i]['address'], ENT_QUOTES, 'UTF-8') : 'Chưa ghi khu vực'?></span></td>
                                <?php } else { ?>
                                    <td class="align-middle">
                                        <input type="number" class="form-control form-control-mini m-auto update-numb" min="0" value="<?=$items[$i]['numb']?>" data-id="<?=$items[$i]['id']?>" data-table="newsletter">
                                    </td>
                                    <?php if(isset($config['newsletter'][$type]['show_name']) && $config['newsletter'][$type]['show_name'] == true) { ?>
                                        <td class="align-middle">
                                            <a class="text-dark text-break"<?=$canEdit ? ' href="'.$linkEdit.'&id='.$items[$i]['id'].'"' : ''?> title="<?=htmlspecialchars($items[$i]['fullname'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($items[$i]['fullname'], ENT_QUOTES, 'UTF-8')?></a>
                                        </td>
                                    <?php } ?>
                                    <?php if(isset($config['newsletter'][$type]['email']) && $config['newsletter'][$type]['email'] == true) { ?>
                                        <td class="align-middle">
                                            <a class="text-dark text-break" href="mailto:<?=htmlspecialchars($items[$i]['email'], ENT_QUOTES, 'UTF-8')?>" title="Gửi email"><?=htmlspecialchars($items[$i]['email'], ENT_QUOTES, 'UTF-8')?></a>
                                        </td>
                                    <?php } ?>
                                    <?php if(isset($config['newsletter'][$type]['show_phone']) && $config['newsletter'][$type]['show_phone'] == true) { ?>
                                        <td class="align-middle"><a href="tel:<?=htmlspecialchars(preg_replace('/[^0-9+]/', '', $items[$i]['phone']), ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($items[$i]['phone'], ENT_QUOTES, 'UTF-8')?></a></td>
                                    <?php } ?>
                                    <?php if(isset($config['newsletter'][$type]['file']) && $config['newsletter'][$type]['file'] == true) { ?>
                                        <td class="align-middle">
                                            <?php if(isset($items[$i]['file_attach']) && ($items[$i]['file_attach'] != '')) { ?>
                                                <a class="btn btn-sm bg-gradient-primary text-white d-inline-block p-1 rounded" href="<?=UPLOAD_FILE.$items[$i]['file_attach']?>" title="Download tập tin"><i class="fas fa-download mr-2"></i>Download tập tin</a>
                                            <?php } else { ?>
                                                <a class="bg-gradient-secondary text-white d-inline-block p-1 rounded" href="#" title="Tập tin trống"><i class="fas fa-download mr-2"></i>Tập tin trống</a>
                                            <?php } ?>
                                        </td>
                                    <?php } ?>
                                <?php } ?>
                                <?php if(isset($config['newsletter'][$type]['show_date']) && $config['newsletter'][$type]['show_date'] == true) { ?>
                                    <td class="align-middle pts-newsletter-created">
                                        <?php if($isDealerRegistration) { ?>
                                            <strong><?=date("d/m/Y", $items[$i]['date_created'])?></strong><small><?=date("H:i", $items[$i]['date_created'])?></small>
                                        <?php } else { ?>
                                            <?=date("h:i:s A - d/m/Y", $items[$i]['date_created'])?>
                                        <?php } ?>
                                    </td>
                                <?php } ?>
                                <?php if(isset($config['newsletter'][$type]['confirm_status']) && count($config['newsletter'][$type]['confirm_status']) > 0) { ?>
                                    <td class="align-middle">
                                        <?php if($canEdit) { ?>
                                            <select class="form-control form-control-sm update-confirm-status pts-newsletter-status-control <?=$newsletterStatusClass($items[$i]['confirm_status'])?>" data-id="<?=$items[$i]['id']?>" data-type="<?=htmlspecialchars($type, ENT_QUOTES, 'UTF-8')?>" data-status="<?=htmlspecialchars((string)$items[$i]['confirm_status'], ENT_QUOTES, 'UTF-8')?>" aria-label="Tình trạng xử lý">
                                                <option value="" <?=empty($items[$i]['confirm_status']) ? 'selected' : ''?>>Mới / chưa xử lý</option>
                                                <?php foreach($config['newsletter'][$type]['confirm_status'] as $statusKey => $statusLabel) { ?>
                                                    <option value="<?=htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8')?>" <?=((string)$items[$i]['confirm_status'] === (string)$statusKey) ? 'selected' : ''?>><?=htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8')?></option>
                                                <?php } ?>
                                            </select>
                                        <?php } else { ?>
                                            <span class="pts-newsletter-status-badge <?=$newsletterStatusClass($items[$i]['confirm_status'])?>"><?=$func->getStatusNewsletter($items[$i]['confirm_status'],$type)?></span>
                                        <?php } ?>
                                    </td>
                                <?php } ?>
                                <td class="align-middle text-center text-md text-nowrap">
                                    <a class="text-primary mr-2<?=$hideEdit?>" href="<?=$linkEdit?>&id=<?=$items[$i]['id']?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                    <a class="text-danger<?=$hideDelete?>" id="delete-item" data-url="<?=$linkDelete?>&id=<?=$items[$i]['id']?>" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>
    <?php if($paging) { ?>
        <div class="card-footer text-sm">
            <?=$paging?>
        </div>
    <?php } ?>
    <?php if(isset($config['newsletter'][$type]['is_send']) && $config['newsletter'][$type]['is_send'] == true) { ?>
        <div class="card card-primary card-outline text-sm mb-0 <?=(!$paging)?'mt-3':'';?>">
            <form name="frmsendemail" method="post" action="<?=$linkMan?>" enctype="multipart/form-data">
        <?=adminCsrfField()?>
                <div class="card-header">
                    <h3 class="card-title">Gửi email đến danh sách được chọn</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="subject">Tiêu đề:</label>
                        <input type="text" class="form-control text-sm" name="subject" id="subject" placeholder="Tiêu đề">
                    </div>
                    <div class="form-group">
                        <label class="d-inline-block align-middle mb-1 mr-2">Upload tập tin:</label>
                        <strong class="d-block mt-2 mb-2 text-sm"><?php echo $config['newsletter'][$type]['file_type'] ?></strong>
                        <div class="custom-file my-custom-file">
                            <input type="file" class="custom-file-input" name="file" id="file">
                            <label class="custom-file-label" for="file">Chọn file</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="content">Nội dung thông tin:</label>
                        <textarea class="form-control form-control-ckeditor" name="content" id="content" rows="5" placeholder="Nội dung thông tin"></textarea>
                    </div>
                    <input type="hidden" name="listemail" id="listemail">
                </div>
            </form>
        </div>
    <?php } ?>
    <div class="card-footer text-sm">
        <?php if(isset($config['newsletter'][$type]['is_send']) && $config['newsletter'][$type]['is_send'] == true) { ?>
           <a class="btn btn-sm bg-gradient-success text-white" id="send-email" title="Gửi email"><i class="fas fa-paper-plane mr-2"></i>Gửi email</a>
        <?php } ?>
        <a class="btn btn-sm bg-gradient-primary text-white<?=$hideAdd?>" href="<?=$linkAdd?>" title="Thêm mới"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
        <a class="btn btn-sm bg-gradient-danger text-white<?=$hideDelete?>" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa tất cả"><i class="far fa-trash-alt mr-2"></i>Xóa tất cả</a>
    </div>
</section>
