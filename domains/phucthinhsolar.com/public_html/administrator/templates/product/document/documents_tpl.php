<?php
	$linkMan = "index.php?com=product&act=man_document&type=".$type."&id_parent=".$id_parent;
	$linkAdd = "index.php?com=product&act=add_document&type=".$type."&id_parent=".$id_parent;
	$linkEdit = "index.php?com=product&act=edit_document&type=".$type."&id_parent=".$id_parent;
	$linkDelete = "index.php?com=product&act=delete_document&type=".$type."&id_parent=".$id_parent;
	$linkBack = "index.php?com=product&act=edit&type=".$type."&id=".$id_parent;
	$documentTypeLabels = array('datasheet' => 'Datasheet', 'manual' => 'Hướng dẫn sử dụng', 'warranty' => 'Bảo hành', 'certificate' => 'Chứng nhận', 'other' => 'Khác');
?>
<!-- Content Header -->
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
                <li class="breadcrumb-item"><a href="<?=$linkBack?>" title="Sản phẩm"><?=$product['namevi']?></a></li>
                <li class="breadcrumb-item active">Tài liệu</li>
            </ol>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="card-footer text-sm sticky-top">
        <a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkAdd?>" title="Thêm tài liệu"><i class="fas fa-plus mr-2"></i>Thêm tài liệu</a>
        <a class="btn btn-sm bg-gradient-secondary" href="<?=$linkBack?>" title="Quay lại sản phẩm"><i class="fas fa-sign-out-alt mr-2"></i>Quay lại sản phẩm</a>
    </div>
    <div class="card card-primary card-outline text-sm mb-0">
        <div class="card-header">
            <h3 class="card-title">Danh sách tài liệu — <?=$product['namevi']?></h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="align-middle text-center" width="8%">STT</th>
                        <th class="align-middle" width="15%">Loại</th>
                        <th class="align-middle">Tiêu đề</th>
                        <th class="align-middle text-center" width="12%">Trạng thái</th>
                        <th class="align-middle text-center" width="12%">Thao tác</th>
                    </tr>
                </thead>
                <?php if(empty($items)) { ?>
                    <tbody><tr><td colspan="100" class="text-center">Không có dữ liệu</td></tr></tbody>
                <?php } else { ?>
                    <tbody>
                        <?php for($i=0;$i<count($items);$i++) { ?>
                            <tr>
                                <td class="align-middle text-center"><?=$items[$i]['numb']?></td>
                                <td class="align-middle"><?=isset($documentTypeLabels[$items[$i]['document_type']]) ? $documentTypeLabels[$items[$i]['document_type']] : $items[$i]['document_type']?></td>
                                <td class="align-middle">
                                    <a class="text-dark text-break" href="<?=$linkEdit?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['titlevi']?>"><?=$items[$i]['titlevi']?></a>
                                    <div class="text-muted text-break" style="font-size:11px;"><?=$items[$i]['file_path']?></div>
                                </td>
                                <td class="align-middle text-center">
                                    <?php if(strpos((string)$items[$i]['status'], 'hienthi') !== false) { ?>
                                        <span class="badge bg-gradient-success">Hiển thị</span>
                                    <?php } else { ?>
                                        <span class="badge bg-gradient-secondary">Đã ẩn</span>
                                    <?php } ?>
                                </td>
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
    <div class="card-footer text-sm">
        <a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkAdd?>" title="Thêm tài liệu"><i class="fas fa-plus mr-2"></i>Thêm tài liệu</a>
        <a class="btn btn-sm bg-gradient-secondary" href="<?=$linkBack?>" title="Quay lại sản phẩm"><i class="fas fa-sign-out-alt mr-2"></i>Quay lại sản phẩm</a>
    </div>
</section>
