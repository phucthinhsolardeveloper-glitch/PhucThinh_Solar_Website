<?php $copy = $this->templateCopy('order_customer', array('order_code' => isset($params['order_code']) ? $params['order_code'] : '', 'customer_name' => isset($params['customer_name']) ? $params['customer_name'] : '')); ?>
<table align="center" bgcolor="#eef2f7" border="0" cellpadding="0" cellspacing="0" style="margin:0;padding:0;background-color:#eef2f7;width:100%!important;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#15284b;line-height:22px" width="100%">
	<tbody>
		<?php include LIBRARIES."sample/mail/layout/header.php"; ?>
		<tr>
			<td align="center" style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444;line-height:18px;font-weight:normal" valign="top">
				<table border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px">
					<tbody>
						<tr style="background:#fff">
								<td align="left" height="auto" style="padding:30px 28px" width="640">
								<table width="100%">
									<tbody>
										<tr>
											<td>
													<p style="margin:0 0 8px;color:#b27b08;font-size:12px;font-weight:bold;letter-spacing:.8px;text-transform:uppercase">Đã tiếp nhận đơn hàng</p>
													<h1 style="font-size:24px;line-height:32px;font-weight:bold;color:#09285e;padding:0;margin:0 0 10px"><?=htmlspecialchars($copy['heading'], ENT_QUOTES, 'UTF-8')?></h1>
													<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#405270;line-height:24px;font-weight:normal"><?=nl2br(htmlspecialchars($copy['intro'], ENT_QUOTES, 'UTF-8'))?></p>
													<h3 style="font-size:13px;font-weight:bold;color:#09285e;text-transform:uppercase;margin:22px 0 0;padding:0 0 8px;border-bottom:2px solid {emailColor}">Thông tin đơn hàng #{emailOrderCode} <span style="font-size:12px;color:#777;text-transform:none;font-weight:normal">({emailDateSend})</span></h3>
								</td>
										</tr>
										<tr>
											<td style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444;line-height:18px">
												<table border="0" cellpadding="0" cellspacing="0" width="100%">
													<thead>
														<tr>
															<th align="left" style="padding:6px 9px 0px 0px;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444;font-weight:bold" width="50%">Thông tin thanh toán</th>
															<th align="left" style="padding:6px 0px 0px 9px;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444;font-weight:bold" width="50%">Địa chỉ giao hàng</th>
														</tr>
													</thead>
													<tbody>
														<tr>
															<td style="padding:3px 9px 9px 0px;border-top:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444;line-height:18px;font-weight:normal" valign="top"><span style="text-transform:capitalize">{emailOrderInfoFullname}</span><br>
																<a href="mailto:{emailOrderInfoEmail}" target="_blank">{emailOrderInfoEmail}</a><br>
															{emailOrderInfoPhone}</td>
															<td style="padding:3px 0px 9px 9px;border-top:0;border-left:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444;line-height:18px;font-weight:normal" valign="top"><span style="text-transform:capitalize">{emailOrderInfoFullname}</span><br>
																<a href="mailto:{emailOrderInfoEmail}" target="_blank">{emailOrderInfoEmail}</a><br>
																{emailOrderInfoAddress}<br>
															Tel: {emailOrderInfoPhone}</td>
														</tr>
														<tr>
															<td colspan="2" style="padding:7px 0px 0px 0px;border-top:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444" valign="top">
																<p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444;line-height:18px;font-weight:normal"><strong>Hình thức thanh toán: </strong> {emailOrderPayment}
																	
																	<?php if(!empty($params['shipPrice'])) { ?>
																		<br><strong>Phí vận chuyển: </strong> {emailOrderShipPrice}
																	<?php } ?>
																</td>
															</tr>
														</tbody>
													</table>
												</td>
											</tr>
											<tr>
												<td>
													<p style="margin:4px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#444;line-height:18px;font-weight:normal"><strong>Yêu cầu khác:</strong> <i>{emailOrderInfoRequirements}</i></p>
												</td>
											</tr>
											<tr>
												<td style="padding-top:22px;color:#405270;font-size:13px;line-height:21px"><?=nl2br(htmlspecialchars($copy['closing'], ENT_QUOTES, 'UTF-8'))?></td>
											</tr>
											<tr>
												<td>
													<h2 style="text-align:left;margin:10px 0;border-bottom:1px solid #ddd;padding-bottom:5px;font-size:13px;color:{emailColor}">CHI TIẾT ĐƠN HÀNG</h2>

													<table border="0" cellpadding="0" cellspacing="0" style="background:#f5f5f5" width="100%">
														<thead>
															<tr>
																<th align="left" bgcolor="{emailColor}" style="padding:6px 9px;color:#fff;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:14px">Sản phẩm</th>
																<th align="left" bgcolor="{emailColor}" style="padding:6px 9px;color:#fff;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:14px">Đơn giá</th>
																<th align="center" bgcolor="{emailColor}" style="padding:6px 9px;color:#fff;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:14px;min-width:55px;">Số lượng</th>
																<th align="right" bgcolor="{emailColor}" style="padding:6px 9px;color:#fff;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:14px">Tổng tạm</th>
															</tr>
														</thead>
														{emailOrderDetails}
													</table>
													<div style="margin:auto;text-align:center"><a href="{emailHome}" style="display:inline-block;text-decoration:none;background-color:{emailColor}!important;text-align:center;border-radius:3px;color:#fff;padding:5px 10px;font-size:12px;font-weight:bold;margin-top:5px" target="_blank">Chi tiết đơn hàng tại {emailCompanyWebsite}</a></div>
												</td>
											</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
		<?php include LIBRARIES."sample/mail/layout/footer.php"; ?>
	</tbody>
</table>
