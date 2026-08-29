<?php
	$linkSave = "index.php?com=sortable&act=save";
	$options = (isset($item['options']) && $item['options'] != '') ? json_decode($item['options'],true) : null;
?>
 <style>	
			.tbl_add
			{
				width:100%;
				border-collapse : collapse; 
				font-size:14px;
				text-align:center;
				font-size:13px;
				background:#fff;
			}
			.tbl_add th
			{
				font-size: 15px;
				background: #007bff;
				color: #fff;
			}
			.tbl_add th, .tbl_add td {
				border: 1px solid #d2d2d2;
				padding: 10px 1%;
				
			}
			a.add_item
			{
				background: #007bff;
				color: #fff;
				padding: 6px 10px;
				border: navajowhite;
				text-transform: capitalize;
				display: inline-block;
				margin-top: 10px;
				border-radius: 4px;
				cursor:pointer;
			}
		</style>
<!-- Content Header -->
<section class="content-header text-sm">
	<div class="container-fluid">
		<div class="row">
			<ol class="breadcrumb float-sm-left">
				<li class="breadcrumb-item"><a href="index.php" title="Bảng điều khiển">Bảng điều khiển</a></li>
				<li class="breadcrumb-item active">Thông tin công ty</li>
			</ol>
		</div>
	</div>
</section>
<?php 
	$item_add_menu = $d->rawQuery("select * from #_sortable order by display_order asc");
	$item_add_index = $d->rawQuery("select * from #_sortable_index order by display_order asc");
	$item_add_detail = $d->rawQuery("select * from #_sortable_detail order by display_order asc");
 ?>
<section class="content mb-3">
	
	
        
       
</section>

<!-- Main content -->
<section class="content">
	<form class="validation-form" novalidate method="post" action="<?=$linkSave?>" enctype="multipart/form-data">
        <?=adminCsrfField()?>
		<div class="card-footer text-sm sticky-top">
			<button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu</button>
			<button type="reset" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-redo mr-2"></i>Làm lại</button>
		</div>

		<?=$flash->getMessages('admin')?>

		<?php if(isset($config['website']['debug-developer']) && $config['website']['debug-developer'] == true) { ?>
			<div class="card card-primary card-outline text-sm">
				<div class="card-header">
					<h3 class="card-title">Cấu hình vị trí</h3>
				</div>
				<div class="card-body">
					<div class="row mb-3">
						<div class="col-md-4 col-sm-12">
							<table class="tbl_add_item_menu tbl_add" data-item ="menu">
						        <tr class="dong">
						            <th style="width: 10px;">STT</th>
						            <th style="width: 35px;">Name</th>
						            <th style="width: 37px;">Type</th>
						            <th style="width: 7px;">DEL</th>
						        </tr>
						       
						       <?php foreach($item_add_menu as $k=>$v) { ?>
						       <tr class="dong" data-item ="menu">
						            <td ><input type="hidden" class="form-control text-sm" name="id_item_menu[]" value="<?=$v['id']?>" />
						                <input type="text" class="form-control text-sm" name="stt_item_menu[]" value="<?=$v['display_order']?>" /></td>
						            <td><input type="text" class="form-control text-sm" name="ten_item_menu[]" value="<?=$v['name']?>" /></td>
						            <td><input type="text" class="form-control text-sm" name="type_item_menu[]" value="<?=$v['type']?>" /></td>
						            <td><a href="<?=$v['id']?>" class="xoa_item"><img src="./assets/images/close.png" alt=""></a></td>
						        </tr>
						        <?php } ?>
						    </table>
						    <div class="row col-sm-12">
							    <div class="item mr-2">
							    	<a class="add_item" data-item ="menu">Thêm item menu</a>
							    </div>
							     
							</div>
						</div>
						<div class="col-md-4 col-sm-12">
							<table class="tbl_add_item_index tbl_add" data-item ="index">
						        <tr class="dong">
						            <th style="width: 10px;">STT</th>
						            <th style="width: 35px;">Name</th>
						            <th style="width: 37px;">Type</th>
						            <th style="width: 7px;">DEL</th>
						        </tr>
						       
						       <?php foreach($item_add_index as $k=>$v) { ?>
						       <tr class="dong" data-item ="index">
						            <td ><input type="hidden" class="form-control text-sm" name="id_item_index[]" value="<?=$v['id']?>" />
						                <input type="text" class="form-control text-sm" name="stt_item_index[]" value="<?=$v['display_order']?>" /></td>
						            <td><input type="text" class="form-control text-sm" name="ten_item_index[]" value="<?=$v['name']?>" /></td>
						            <td><input type="text" class="form-control text-sm" name="type_item_index[]" value="<?=$v['type']?>" /></td>
						            <td><a href="<?=$v['id']?>" class="xoa_item"><img src="./assets/images/close.png" alt=""></a></td>
						        </tr>
						        <?php } ?>
						    </table>
						    <div class="row col-sm-12">
						    	<div class="item mr-2">
						    		<a class="add_item" data-item ="index">Thêm item index</a>
						    	</div>
						    	
						    </div>
						</div>
						<div class="col-md-4 col-sm-12">
							<table class="tbl_add_item_detail tbl_add" data-item ="detail">
						        <tr class="dong">
						            <th style="width: 10px;">STT</th>
						            <th style="width: 35px;">Name</th>
						            <th style="width: 37px;">Type</th>
						            <th style="width: 7px;">DEL</th>
						        </tr>
						       
						       <?php foreach($item_add_detail as $k=>$v) { ?>
						       <tr class="dong" data-item ="detail">
						            <td ><input type="hidden" class="form-control text-sm" name="id_item_detail[]" value="<?=$v['id']?>" />
						                <input type="text" class="form-control text-sm" name="stt_item_detail[]" value="<?=$v['display_order']?>" /></td>
						            <td><input type="text" class="form-control text-sm" name="ten_item_detail[]" value="<?=$v['name']?>" /></td>
						            <td><input type="text" class="form-control text-sm" name="type_item_detail[]" value="<?=$v['type']?>" /></td>
						            <td><a href="<?=$v['id']?>" class="xoa_item"><img src="./assets/images/close.png" alt=""></a></td>
						        </tr>
						        <?php } ?>
						    </table>
						    <div class="row col-sm-12">
							    <div class="item mr-2">
							    	 <a class="add_item" data-item ="detail">Thêm item detail</a>
							    </div>
							    
							</div>
						   
						    
						</div>
					</div>
					<p style="color: #f00;">Nhập giá trị xong, nhấn nút Lưu để lưu giá trị</p>
				</div>
			</div>

			<!-- phần mới -->



			<div class="card card-primary card-outline text-sm">
				<div class="card-header">
					<h3 class="card-title">Cấu hình vị trí menu</h3>
				</div>
				<div class="card-body">
					<p style="color: #f00;">Di chuyển vị trí hệ thống tự lưu, không cần nhấn nút Lưu</p>
					<div class="form-group">
						<ul class="reorder-gallery">
				        <?php          
				        	$data_records = $d->rawQuery("select id, name from #_sortable ORDER BY display_order");
				        	foreach ($data_records as $key => $value) { ?>
				            	<li id="<?=  $value['id'] ?>" class="ui-sortable-handle form-control text-sm"><a href="javascript:void(0);"><?= $value['name']?></a></li>
				        <?php } ?>
				        </ul>
					</div>
				</div>
			</div>
			<div class="card card-primary card-outline text-sm">
				<div class="card-header">
					<h3 class="card-title">Cấu hình vị trí tổng trang chủ </h3>
				</div>
				<div class="card-body">
					<p style="color: #f00;">Di chuyển vị trí hệ thống tự lưu, không cần nhấn nút Lưu</p>
					<div class="form-group">
						<ul class="reorder-gallery-index">
				        <?php          
				        	$data_records_index = $d->rawQuery("select id, name from #_sortable_index ORDER BY display_order");
				        	foreach ($data_records_index as $key => $value) { ?>
				            	<li id="<?=  $value['id'] ?>" class="ui-sortable-handle form-control text-sm"><a href="javascript:void(0);"><?= $value['name']?></a></li>
				        <?php } ?>
				        </ul>
					</div>
				</div>
			</div>
			<div class="card card-primary card-outline text-sm">
				<div class="card-header">
					<h3 class="card-title">Cấu hình vị trí chi tiết trang chủ phần main index</h3>
				</div>
				<div class="card-body">
					<p style="color: #f00;">Di chuyển vị trí hệ thống tự lưu, không cần nhấn nút Lưu</p>
					<div class="form-group">
						<ul class="reorder-gallery-detail">
				        <?php          
				        	$data_records_detail = $d->rawQuery("select id, name from #_sortable_detail ORDER BY display_order");
				        	foreach ($data_records_detail as $key => $value) { ?>
				            	<li id="<?=  $value['id'] ?>" class="ui-sortable-handle form-control text-sm"><a href="javascript:void(0);"><?= $value['name']?></a></li>
				        <?php } ?>
				        </ul>
					</div>
				</div>
			</div>
		<?php } ?>
		<div class="card card-primary card-outline text-sm">
			<div class="card-header">
				<h3 class="card-title">Thông tin chung</h3>
			</div>
			<div class="card-body">
				<div class="form-group d-inline-block mb-2 mr-2">
                    <label for="hotline-checkbox" class="d-inline-block align-middle mb-0 mr-2">Hotline:</label>
                    <div class="custom-control custom-checkbox d-inline-block align-middle">
                        <input type="checkbox" class="custom-control-input hotline-checkbox" name="data[hotline]" id="hotline-checkbox" <?=(!isset($item['hotline']) || $item['hotline']==1)?'checked="checked"':''?>>
                        <label for="hotline-checkbox" class="custom-control-label"></label>
                    </div>
                </div>
                <div class="form-group d-inline-block mb-2 mr-2">
                    <label for="phone-checkbox" class="d-inline-block align-middle mb-0 mr-2">Phone:</label>
                    <div class="custom-control custom-checkbox d-inline-block align-middle">
                        <input type="checkbox" class="custom-control-input phone-checkbox" name="data[phone]" id="phone-checkbox" <?=(!isset($item['phone']) || $item['phone']==1)?'checked="checked"':''?>>
                        <label for="phone-checkbox" class="custom-control-label"></label>
                    </div>
                </div>

                <div class="form-group d-inline-block mb-2 mr-2">
                    <label for="email-checkbox" class="d-inline-block align-middle mb-0 mr-2">Email:</label>
                    <div class="custom-control custom-checkbox d-inline-block align-middle">
                        <input type="checkbox" class="custom-control-input email-checkbox" name="data[email]" id="email-checkbox" <?=(!isset($item['email']) || $item['email']==1)?'checked="checked"':''?>>
                        <label for="email-checkbox" class="custom-control-label"></label>
                    </div>
                </div>
				
				<div class="row">
					<?php /* ?>
						<div class="form-group col-md-4 col-sm-6">
							<label for="email">Email:</label>
							<input type="text" class="form-control text-sm" name="email" id="email" placeholder="Title email" value="<?=@$item['email']?>">
							<div class="custom-control custom-checkbox d-inline-block align-middle">
		                        <input type="checkbox" class="custom-control-input email-checkbox" name="emailck" id="email-checkbox" <?=(!isset(@$item['emailck']) || @$item['emailck']==1)?'checked="checked"':''?>>
		                        <label for="email-checkbox" class="custom-control-label">Hiển thị</label>
		                    </div>
						</div>
					<?php */ ?>

					<div class="form-group col-md-4 col-sm-6">
						<label for="chinhsach">Title chính sách footer:</label>
						<input type="text" class="form-control text-sm" name="data[options][chinhsach]" id="chinhsach" placeholder="Title chính sách footer" value="<?=$options['chinhsach']?>" required>
					</div>
					<div class="form-group col-md-4 col-sm-6">
						<label for="chinhsach">Title đăng ký footer:</label>
						<input type="text" class="form-control text-sm" name="data[options][dangky]" id="dangky" placeholder="Title đăng ký footer" value="<?=$options['dangky']?>" required>
					</div>
					<div class="form-group col-md-4 col-sm-6">
						<label for="fanpage">Title fanpage footer:</label>
						<input type="text" class="form-control text-sm" name="data[options][fanpage]" id="fanpage" placeholder="Title fanpage footer" value="<?=$options['fanpage']?>" required>
					</div>				
				</div>
			</div>
		</div>
	   
		<div class="card-footer text-sm">
			<button type="submit" class="btn btn-sm bg-gradient-primary submit-check" disabled><i class="far fa-save mr-2"></i>Lưu</button>
			<button type="reset" class="btn btn-sm bg-gradient-secondary"><i class="fas fa-redo mr-2"></i>Làm lại</button>
			<input type="hidden" name="id" value="<?=(isset($item['id']) && $item['id'] > 0) ? $item['id'] : ''?>">
		</div>
	</form>
</section>