<div id="page-login" class="account-profile">
	<div class="account-page row d-flex justify-content-between flex-wrap">
		<div class="item col-2">
			<?php include 'templates/accounts/menu_account.php'; ?>
		</div>
		<div class="item col-10">
			<div class="form-signup">
				<div class="text-left margin-bottom-20">
					<h1 class="title-head line"><?=$title?> <span style="color: #0099FF"><?=$item['code']?></span></h1>
				</div>
				<div class="desc-order">
					<?php 
						$city = $apiPlace->getPlaceId('id','place_citys',$item['id_city'],"name_$lang as name");
						$dist = $apiPlace->getPlaceId('id','place_dists',$item['id_dist'],"name_$lang as name, code");
					?>
					<div class="row d-flex flex-wrap  justify-content-start">
						<div class="item col--2">
							<h6 class="title-mul"><?=_thong_tin_nguoi_dat?></h6>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_ho_ten?>:</strong></label>
								<div class="order-title"><?=$item['fullname']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong>Email:</strong></label>
								<div class="order-title"><?=$item['email']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_dien_thoai?>:</strong></label>
								<div class="order-title"><?=$item['phone']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_dia_chi?>:</strong></label>
								<div class="order-title"><?=$item['address']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_tinh_thanh?>:</strong></label>
								<div class="order-title"><?=$city['name']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_quan_huyen?>:</strong></label>
								<div class="order-title"><?=$dist['code'].' '.$dist['name']?></div>
							</div>
						</div>
						<?php if($item['other_address']==1){ ?>
						<?php 
							$city_other = $apiPlace->getPlaceId('id','place_citys',$item['id_city_other'],"name_$lang as name");
							$dist_other = $apiPlace->getPlaceId('id','place_dists',$item['id_dist_other'],"name_$lang as name, code");
						?>
						<div class="item col--2">
							<h6 class="title-mul"><?=_thong_tin_nguoi_nhan?></h6>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_ho_ten?>:</strong></label>
								<div class="order-title"><?=$item['fullname_other']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong>Email:</strong></label>
								<div class="order-title"><?=$item['email_other']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_dien_thoai?>:</strong></label>
								<div class="order-title"><?=$item['phone_other']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_dia_chi?>:</strong></label>
								<div class="order-title"><?=$item['address_other']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_tinh_thanh?>:</strong></label>
								<div class="order-title"><?=$city_other['name']?></div>
							</div>
							<div class="row-order w-100 d-flex flex-wrap justify-content-start">
								<label class="margin-right-15"><strong><?=_quan_huyen?>:</strong></label>
								<div class="order-title"><?=$dist_other['code'].' '.$dist_other['name']?></div>
							</div>
						</div>
						<?php } ?>
					</div>
				</div>
				<div class="desc-order margin-top-20">
					<h4 class="thongtin-donhang">
						<?=_chi_tiet_don_hang?>
					</h4>
				</div>
				<div class="table">
					<table class="display list-table">
						<thead>
							<tr>
								<th align="left"><?=_ma_san_pham?></th>
								<th align="left"><?=_hinh?></th>
								<th align="left"><?=_ten_san_pham?></th>
								<th align="left"><?=_so_luong?></th>
								<th align="left"><?=_don_gia?></th>
								<th align="left"><?=_thanh_tien?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($lists as $k=>$v){ $product = $apiCart->getProduct($v['id_product']); ?>
							<tr>
								<td><?=$product['code']?></td>
								<td><img src="<?=$config_base._upload_product_l.$product['thumb']?>" alt="" width="50"></td>
								<td><?=$product['name_'.$lang]?></td>
								<td><?=$v['qty']?></td>
								<td align="right"><?=$func->moneyFormat($v['price'],' đ')?></td>
								<td align="right"><?=$func->moneyFormat($v['price']*$v['qty'],' đ')?></td>
							</tr>
							<?php } ?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan="5" align="right">
									<?=_tam_tinh?>:
								</td>
								<td align="right">
									<?=$func->moneyFormat($item['total_price']+$item['sale_off'],' đ')?>
								</td>
							</tr>
							<tr>
								<td colspan="5" align="right">
									<?=_giam_gia?>:
								</td>
								<td align="right">
									<?=$func->moneyFormat($item['sale_off'],' đ')?>
								</td>
							</tr>
							<tr>
								<td colspan="5" align="right">
									<?=_thanh_tien?>:
								</td>
								<td align="right">
									<span style="color: #FF0000;"><?=$func->moneyFormat($item['total_price'],' đ')?></span>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>