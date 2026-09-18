<table align="center" bgcolor="#eef2f7" border="0" cellpadding="0" cellspacing="0" style="margin:0;padding:0;background:#eef2f7;width:100%!important;font-family:Arial,Helvetica,sans-serif;color:#15284b" width="100%">
	<tbody>
		<?php include LIBRARIES."sample/mail/layout/header.php"; ?>
		<tr><td align="center" style="padding:0 12px 24px">
			<table border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#fff">
				<tbody><tr><td style="padding:30px 28px">
					<p style="margin:0 0 8px;color:#a56f00;font-size:12px;font-weight:bold;letter-spacing:.7px;text-transform:uppercase">Thông tin từ Phúc Thịnh Solar</p>
					<h1 style="margin:0 0 18px;color:#09285e;font-size:24px;line-height:32px">{emailSubjectSender}</h1>
					<div style="color:#405270;font-size:15px;line-height:24px">{emailContentSender}</div>
					<p style="margin:24px 0 0;padding-top:18px;border-top:1px solid #e7ebf1;color:#61708a;font-size:13px;line-height:21px">Email này được gửi từ hệ thống Phúc Thịnh Solar tới địa chỉ đã được lựa chọn trong Admin.</p>
				</td></tr></tbody>
			</table>
		</td></tr>
		<?php include LIBRARIES."sample/mail/layout/footer.php"; ?>
	</tbody>
</table>
