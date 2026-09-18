<?php $copy = $this->templateCopy('contact_customer', array('customer_name' => $this->get('tennguoigui'), 'contact_subject' => $this->get('tieudelienhe'))); ?>
<table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" style="margin:0;padding:0;background:#eef2f7;width:100%;font-family:Arial,Helvetica,sans-serif;color:#15284b" width="100%">
	<tbody>
		<?php include LIBRARIES."sample/mail/layout/header.php"; ?>
		<tr><td align="center" style="padding:0 12px" valign="top">
			<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#ffffff">
				<tr><td style="padding:34px 28px 18px">
					<p style="margin:0 0 8px;color:#b27b08;font-size:12px;font-weight:bold;letter-spacing:.8px;text-transform:uppercase">Đã tiếp nhận</p>
					<h1 style="margin:0 0 14px;color:#09285e;font-size:25px;line-height:33px"><?=htmlspecialchars($copy['heading'], ENT_QUOTES, 'UTF-8')?></h1>
					<p style="margin:0;color:#405270;font-size:15px;line-height:24px"><?=nl2br(htmlspecialchars($copy['intro'], ENT_QUOTES, 'UTF-8'))?></p>
				</td></tr>
				<tr><td style="padding:4px 28px 22px">
					<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f9fc;border-left:4px solid {emailColor}">
						<tr><td style="padding:16px 18px;color:#15284b;font-size:14px;line-height:22px"><strong>Chủ đề:</strong> {emailSubjectSender}<br><br>{emailContentSender}</td></tr>
					</table>
				</td></tr>
				<tr><td style="padding:0 28px 32px;color:#405270;font-size:13px;line-height:21px"><?=nl2br(htmlspecialchars($copy['closing'], ENT_QUOTES, 'UTF-8'))?></td></tr>
			</table>
		</td></tr>
		<?php include LIBRARIES."sample/mail/layout/footer.php"; ?>
	</tbody>
</table>
