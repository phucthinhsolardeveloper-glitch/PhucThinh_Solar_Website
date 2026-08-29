<?php
	$linkMan = "index.php?com=contact&act=man";
	$linkEdit = "index.php?com=contact&act=edit";
	$linkDelete = "index.php?com=contact&act=delete";
	$linkSearch = $linkMan.(!empty($_GET['contact_status']) ? '&contact_status='.urlencode($_GET['contact_status']) : '');
	$linkStatus = $linkMan.(!empty($_GET['keyword']) ? '&keyword='.urlencode($_GET['keyword']) : '');
?>

<!-- Content Header -->
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active">Quản lý liên hệ</li>
            </ol>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="card-footer text-sm sticky-top">
        <a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa tất cả"><i class="far fa-trash-alt mr-2"></i>Xóa tất cả</a>
        <div class="form-inline form-search d-inline-block align-middle ml-3">
            <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar text-sm" type="search" id="keyword" placeholder="Tên, SĐT, email hoặc chủ đề" aria-label="Tìm kiếm" value="<?=isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword'], ENT_QUOTES, 'UTF-8') : ''?>" onkeypress="doEnter(event,'keyword','<?=$linkSearch?>')">
                <div class="input-group-append bg-primary rounded-right">
                    <button class="btn btn-navbar text-white" type="button" onclick="onSearch('keyword','<?=$linkSearch?>')">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="form-inline d-inline-block align-middle ml-2">
            <select class="custom-select custom-select-sm text-sm" onchange="window.location.href='<?=$linkStatus?>&contact_status='+encodeURIComponent(this.value)">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" <?=(isset($_GET['contact_status']) && $_GET['contact_status'] === 'pending') ? 'selected' : ''?>>Chưa xác nhận</option>
                <option value="confirmed" <?=(isset($_GET['contact_status']) && $_GET['contact_status'] === 'confirmed') ? 'selected' : ''?>>Đã xác nhận</option>
            </select></div>
    </div>
    <div class="card card-primary card-outline text-sm mb-0">
        <div class="card-header">
            <h3 class="card-title">Danh sách liên hệ</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="align-middle" width="5%">
                            <div class="custom-control custom-checkbox my-checkbox">
                                <input type="checkbox" class="custom-control-input" id="selectall-checkbox">
                                <label for="selectall-checkbox" class="custom-control-label"></label>
                            </div>
                        </th>
                        <th class="align-middle text-center" width="10%">STT</th>
                        <th class="align-middle">Họ tên</th>
                        <th class="align-middle">Điện thoại</th>
                        <th class="align-middle">Email</th>
                        <th class="align-middle">Chủ đề</th>
                        <th class="align-middle text-center text-nowrap">Gửi lúc</th>
                        <?php if(isset($config['contact']['check'])) { foreach($config['contact']['check'] as $key => $value) { ?>
                            <th class="align-middle text-center"><?=$value?></th>
                        <?php } } ?>
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
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-mini m-auto update-numb" min="0" value="<?=$items[$i]['numb']?>" data-id="<?=$items[$i]['id']?>" data-table="contact">
                                </td>
                                <td class="align-middle">
                                    <a class="text-dark text-break" href="<?=$linkEdit?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['fullname']?>"><?=$items[$i]['fullname']?></a>
                                </td>
                                <td class="align-middle">
                                    <a class="text-dark text-break" href="<?=$linkEdit?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['phone']?>"><?=$items[$i]['phone']?></a>
                                </td>
                                <td class="align-middle">
                                    <a class="text-dark text-break" href="<?=$linkEdit?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['email']?>"><?=$items[$i]['email']?></a>
                                </td>
                                <td class="align-middle text-break"><?=htmlspecialchars($items[$i]['subject'], ENT_QUOTES, 'UTF-8')?></td>
                                <td class="align-middle text-center text-nowrap"><?=(!empty($items[$i]['date_created'])) ? date('d/m/Y H:i', $items[$i]['date_created']) : '-'?></td>
                                <?php $status_array = (!empty($items[$i]['status'])) ? explode(',', $items[$i]['status']) : array(); ?>
                                <?php if(isset($config['contact']['check'])) { foreach($config['contact']['check'] as $key => $value) { ?>
                                    <td class="align-middle text-center">
                                        <div class="custom-control custom-checkbox my-checkbox">
                                            <input type="checkbox" class="custom-control-input show-checkbox" id="show-checkbox-<?=$key?>-<?=$items[$i]['id']?>" data-table="contact" data-id="<?=$items[$i]['id']?>" data-attr="<?=$key?>" <?=(in_array($key, $status_array)) ? 'checked' : ''?>>
                                            <label for="show-checkbox-<?=$key?>-<?=$items[$i]['id']?>" class="custom-control-label"></label>
                                        </div>
                                    </td>
                                <?php } } ?>
                                <td class="align-middle text-center text-md text-nowrap">
                                    <a class="text-primary mr-2" href="<?=$linkEdit?>&id=<?=$items[$i]['id']?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                    <a class="text-danger" id="delete-item" data-url="<?=$linkDelete?>&id=<?=$items[$i]['id']?>" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>
    <?php if($paging) { ?>
        <div class="card-footer text-sm pb-0">
            <?=$paging?>
        </div>
    <?php } ?>
    <div class="card-footer text-sm">
        <a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?>" title="Xóa tất cả"><i class="far fa-trash-alt mr-2"></i>Xóa tất cả</a>
    </div>
</section>
