<div id="page-login" class="account-profile">
	<div class="account-page row d-flex justify-content-between flex-wrap">
		<div class="item col-2">
			<?php include 'templates/accounts/menu_account.php'; ?>
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
								<th align="left"><?=_ma_tra_hang?></th>
								<th align="left"><?=_ngay_tra?></th>
								<th align="left"><?=_so_tien?></th>
								<th align="left"><?=_trang_thai?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($lists as $k=>$v){ ?>
							<tr>
								<td><?=$v['code']?></td>
								<td><?=$v['createdAt']?></td>
								<td><?=$func->moneyFormat($v['total_price'],' đ')?></td>
								<td>Đã trả</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>