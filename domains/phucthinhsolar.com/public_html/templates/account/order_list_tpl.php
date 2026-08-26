<div id="page-login" class="account-profile">
	<div class="account-page row d-flex justify-content-between flex-wrap">
		<div class="item col-2">
			<?php include 'templates/account/menu_account.php'; ?>
		</div>
		<div class="item col-10">
			<div class="form-signup">
				<div class="text-left margin-bottom-20">
					<h1 class="title-head line"><?=$title?></h1>
				</div>
				<div class="table">
					<table class="display list-table">
						<thead>
							<tr>
								<th align="left"><?=_ma_don_hang?></th>
								<th align="left"><?=_ngay_dat?></th>
								<th align="left"><?=hoten?></th>
								<th align="left"><?=dienthoai?></th>
								<th align="left"><?=tongtien?></th>
								<th align="left"><?=tinhtrang?></th>
								<th align="left"><?=_thao_tac?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($lists as $k=>$v){ ?>
							<tr>
								<td><?=$v['code']?></td>
								<td><?=$v['date_created']?></td>
								<td><?=$v['fullname']?></td>
								<td><?=$v['phone']?></td>
								<td><?=$func->formatMoney($v['total_price'])?></td>
								<td>
									<?= ($v['payment_status']==0) ? _chua:_da ?> <?=_thanh_toan?>
								</td>
								<td>
									<a href="account/danh-sach-don-hang&action=edit&id=<?=$v['id']?>" title="" class="btn-ap r">
										<i class="fa fa-edit"></i> <?=_xem?>
									</a>
									<?php if($func->isReturnOrder($v['id'])){ ?>
									<a href="account/danh-sach-don-hang" title="" class="btn-ap r">
										<?=_da_tra_hang?>
									</a>
									<?php }else{ ?>
									<a href="account/danh-sach-don-hang&action=return&id=<?=$v['id']?>" title="" class="btn-ap b">
										<i class="fa fa-refresh"></i> <?=_tra_hang?>
									</a>
									<?php } ?>
									
								</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>