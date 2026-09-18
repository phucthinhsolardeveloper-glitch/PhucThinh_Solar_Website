<?php
	$copy = !empty($params['copy']) && is_array($params['copy']) ? $params['copy'] : array();
	$fields = !empty($params['fields']) && is_array($params['fields']) ? $params['fields'] : array();
?>
<table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" style="margin:0;padding:0;background:#eef2f7;width:100%;font-family:Arial,Helvetica,sans-serif;color:#15284b" width="100%">
	<tbody>
		<?php include LIBRARIES."sample/mail/layout/header.php"; ?>
		<tr>
			<td align="center" style="padding:0 12px" valign="top">
				<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#ffffff">
					<tr>
						<td style="padding:32px 28px 18px">
							<p style="margin:0 0 8px;color:#b27b08;font-size:12px;font-weight:bold;letter-spacing:.8px;text-transform:uppercase">Thông báo từ website</p>
							<h1 style="margin:0 0 12px;color:#09285e;font-size:24px;line-height:32px"><?=htmlspecialchars(isset($copy['heading']) ? $copy['heading'] : 'Yêu cầu mới từ website', ENT_QUOTES, 'UTF-8')?></h1>
							<p style="margin:0;color:#405270;font-size:15px;line-height:24px"><?=nl2br(htmlspecialchars(isset($copy['intro']) ? $copy['intro'] : '', ENT_QUOTES, 'UTF-8'))?></p>
						</td>
					</tr>
					<tr>
						<td style="padding:0 28px 24px">
							<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background:#f7f9fc">
								<?php foreach($fields as $label => $value) { ?>
								<tr>
									<td style="width:35%;padding:11px 14px;border-bottom:1px solid #e5eaf2;color:#60708b;font-size:13px;font-weight:bold;vertical-align:top"><?=htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8')?></td>
									<td style="padding:11px 14px;border-bottom:1px solid #e5eaf2;color:#15284b;font-size:14px;line-height:21px;vertical-align:top"><?=nl2br(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'))?></td>
								</tr>
								<?php } ?>
							</table>
						</td>
					</tr>
					<?php if(!empty($copy['closing'])) { ?>
					<tr><td style="padding:0 28px 30px;color:#405270;font-size:13px;line-height:21px"><?=nl2br(htmlspecialchars($copy['closing'], ENT_QUOTES, 'UTF-8'))?></td></tr>
					<?php } ?>
				</table>
			</td>
		</tr>
		<?php include LIBRARIES."sample/mail/layout/footer.php"; ?>
	</tbody>
</table>
