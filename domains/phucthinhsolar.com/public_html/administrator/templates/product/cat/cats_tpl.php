<?php
	$linkMan = $linkFilter = "index.php?com=product&act=man_cat&type=".$type;
	$linkAdd = "index.php?com=product&act=add_cat&type=".$type;
    $linkEdit = "index.php?com=product&act=edit_cat&type=".$type;
    $linkDelete = "index.php?com=product&act=delete_cat&type=".$type;
?>
<!-- Content Header -->
<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active">Quản lý <?=$config['product'][$type]['title_main_cat']?></li>
            </ol>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="card-footer text-sm sticky-top">
    	<a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkAdd?>" title="Thêm mới"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
        <a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?><?=$strUrl?>" title="Xóa tất cả"><i class="far fa-trash-alt mr-2"></i>Xóa tất cả</a>
        <div class="form-inline form-search d-inline-block align-middle ml-3">
            <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar text-sm" type="search" id="keyword" placeholder="Tìm kiếm" aria-label="Tìm kiếm" value="<?=(isset($_GET['keyword'])) ? $_GET['keyword'] : ''?>" onkeypress="doEnter(event,'keyword','<?=$linkMan?>')">
                <div class="input-group-append bg-primary rounded-right">
                    <button class="btn btn-navbar text-white" type="button" onclick="onSearch('keyword','<?=$linkMan?>')">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <form class="form-inline float-right" method="get" action="index.php">
            <?php foreach($_GET as $queryKey => $queryValue) { if(in_array($queryKey, array('per_page','p'), true) || !is_scalar($queryValue)) continue; ?>
                <input type="hidden" name="<?=htmlspecialchars($queryKey, ENT_QUOTES, 'UTF-8')?>" value="<?=htmlspecialchars((string)$queryValue, ENT_QUOTES, 'UTF-8')?>">
            <?php } ?>
            <label class="mr-2 mb-0" for="admin-per-page-cat">Số dòng</label>
            <select id="admin-per-page-cat" name="per_page" class="custom-select custom-select-sm">
                <?php foreach($adminPerPageOptions as $perPageOption) { ?><option value="<?=$perPageOption?>" <?=$adminPerPage===$perPageOption?'selected':''?>><?=$perPageOption?></option><?php } ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary ml-1">Áp dụng</button>
        </form>
    </div>
    <div class="card-footer form-group-category text-sm bg-light row">
        <div class="form-group col-xl-2 col-lg-3 col-md-4 col-sm-4 mb-2"><?=$func->getLinkCategory('product', 'list', $type)?></div>
    </div>
    <div class="card card-primary card-outline text-sm mb-0">
        <div class="card-header">
            <h3 class="card-title">Danh sách <?=$config['product'][$type]['title_main_cat']?></h3>
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
						<?php if(isset($config['product'][$type]['show_images_cat']) && $config['product'][$type]['show_images_cat'] == true) { ?>
							<th class="align-middle">Hình</th>
						<?php } ?>
						<th class="align-middle" style="width:30%">Tiêu đề</th>
						<th class="align-middle text-center">Sản phẩm</th>
						<?php if(isset($config['product'][$type]['check_cat'])) { foreach($config['product'][$type]['check_cat'] as $key => $value) { ?>
							<th class="align-middle text-center"><?=$value?></th>
						<?php } } ?>
                        <th class="align-middle text-center">Thao tác</th>
                    </tr>
                </thead>
                <?php if(empty($items)) { ?>
                    <tbody><tr><td colspan="100" class="text-center">Không có dữ liệu</td></tr></tbody>
                <?php } else { ?>
                    <tbody>
                        <?php for($i=0;$i<count($items);$i++) {
                            $linkID = "";
                            if($items[$i]['id_list']) $linkID .= "&id_list=".$items[$i]['id_list']; ?>
                            <tr>
                                <td class="align-middle">
                                    <div class="custom-control custom-checkbox my-checkbox">
                                        <input type="checkbox" class="custom-control-input select-checkbox" id="select-checkbox-<?=$items[$i]['id']?>" value="<?=$items[$i]['id']?>">
                                        <label for="select-checkbox-<?=$items[$i]['id']?>" class="custom-control-label"></label>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <input type="number" class="form-control form-control-mini m-auto update-numb" min="0" value="<?=$items[$i]['numb']?>" data-id="<?=$items[$i]['id']?>" data-table="product_cat">
                                </td>
                                <?php if(isset($config['product'][$type]['show_images_cat']) && $config['product'][$type]['show_images_cat'] == true) { ?>
                                    <td class="align-middle">
                                    	<a href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['namevi']?>">
                                            <?=$func->getImage(['class' => 'rounded img-preview', 'sizes' => $config['product'][$type]['thumb_cat'], 'upload' => UPLOAD_PRODUCT_L, 'image' => $items[$i]['photo'], 'alt' => $items[$i]['namevi']])?>
                                        </a>
                                    </td>
                                <?php } ?>
                                <td class="align-middle">
                                    <a class="text-dark text-break" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="<?=$items[$i]['namevi']?>"><?=$items[$i]['namevi']?></a>
                                </td>
								<td class="align-middle text-center">
									<?php if((int)$items[$i]['product_count'] > 0) { ?>
										<span class="badge badge-success"><?=(int)$items[$i]['product_count']?> sản phẩm</span>
									<?php } else { ?>
										<span class="badge badge-warning" title="Danh mục chưa xuất hiện ở menu hoặc bộ lọc công khai vì chưa có sản phẩm">Chưa có sản phẩm</span>
									<?php } ?>
								</td>
                                <?php $status_array = (!empty($items[$i]['status'])) ? explode(',', $items[$i]['status']) : array(); ?>
                                <?php if(isset($config['product'][$type]['check_cat'])) { foreach($config['product'][$type]['check_cat'] as $key => $value) { ?>
                                    <td class="align-middle text-center">
                                        <div class="custom-control custom-checkbox my-checkbox">
                                            <input type="checkbox" class="custom-control-input show-checkbox" id="show-checkbox-<?=$key?>-<?=$items[$i]['id']?>" data-table="product_cat" data-id="<?=$items[$i]['id']?>" data-attr="<?=$key?>" <?=(in_array($key, $status_array)) ? 'checked' : ''?>>
                                            <label for="show-checkbox-<?=$key?>-<?=$items[$i]['id']?>" class="custom-control-label"></label>
                                        </div>
                                    </td>
                                <?php } } ?>
                                <td class="align-middle text-center text-md text-nowrap">
                                    <a class="text-primary mr-2" href="<?=$linkEdit?><?=$linkID?>&id=<?=$items[$i]['id']?>" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                    <a class="text-danger" id="delete-item" data-url="<?=$linkDelete?><?=$strUrl?>&id=<?=$items[$i]['id']?>" title="Xóa"><i class="fas fa-trash-alt"></i></a>
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
    	<a class="btn btn-sm bg-gradient-primary text-white" href="<?=$linkAdd?>" title="Thêm mới"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
        <a class="btn btn-sm bg-gradient-danger text-white" id="delete-all" data-url="<?=$linkDelete?><?=$strUrl?>" title="Xóa tất cả"><i class="far fa-trash-alt mr-2"></i>Xóa tất cả</a>
    </div>
</section>
