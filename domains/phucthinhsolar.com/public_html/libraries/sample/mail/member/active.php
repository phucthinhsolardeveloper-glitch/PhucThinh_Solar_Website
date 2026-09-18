<?php $copy = $this->templateCopy('member_active', array('customer_name' => isset($params['customer_name']) ? $params['customer_name'] : '')); ?>
<table align="center" bgcolor="#eef2f7" border="0" cellpadding="0" cellspacing="0" style="margin:0;padding:0;background:#eef2f7;width:100%!important;font-family:Arial,Helvetica,sans-serif;color:#15284b" width="100%">
    <tbody>
        <?php include LIBRARIES."sample/mail/layout/header.php"; ?>
        <tr><td align="center" style="padding:0 12px 24px"><table border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#fff"><tbody><tr><td style="padding:30px 28px">
            <p style="margin:0 0 8px;color:#a56f00;font-size:12px;font-weight:bold;letter-spacing:.7px;text-transform:uppercase">Tài khoản thành viên</p>
            <h1 style="margin:0 0 12px;color:#09285e;font-size:24px;line-height:32px"><?=htmlspecialchars($copy['heading'], ENT_QUOTES, 'UTF-8')?></h1>
            <p style="margin:0 0 18px;color:#405270;font-size:15px;line-height:24px"><?=nl2br(htmlspecialchars($copy['intro'], ENT_QUOTES, 'UTF-8'))?></p>
            <table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody><tr>{emailInfoSignupMember}</tr></tbody></table>
            <p style="margin:22px 0 0"><a href="{emailLinkActiveMember}" style="display:inline-block;text-decoration:none;background:{emailColor};border-radius:3px;color:#09285e;padding:11px 18px;font-size:14px;font-weight:bold" target="_blank">Kích hoạt tài khoản</a></p>
            <p style="margin:20px 0 0;color:#61708a;font-size:13px;line-height:21px"><?=nl2br(htmlspecialchars($copy['closing'], ENT_QUOTES, 'UTF-8'))?></p>
        </td></tr></tbody></table></td></tr>
        <?php include LIBRARIES."sample/mail/layout/footer.php"; ?>
    </tbody>
</table>
