<?php
$copy = isset($params['copy']) && is_array($params['copy']) ? $params['copy'] : array();
$workflow = isset($params['workflow']) && is_array($params['workflow']) ? $params['workflow'] : array();
$fields = isset($params['fields']) && is_array($params['fields']) ? $params['fields'] : array();
$mailEsc = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
?>
<table align="center" bgcolor="#eef2f7" border="0" cellpadding="0" cellspacing="0" style="margin:0;padding:0;background:#eef2f7;width:100%!important;font-family:Arial,Helvetica,sans-serif;color:#15284b" width="100%">
    <tbody>
        <?php include LIBRARIES."sample/mail/layout/header.php"; ?>
        <tr><td align="center" style="padding:0 12px 24px">
            <table border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#fff">
                <tbody><tr><td style="padding:30px 28px">
                    <p style="margin:0 0 8px;color:#a56f00;font-size:12px;font-weight:bold;letter-spacing:.7px;text-transform:uppercase"><?=$mailEsc(isset($workflow['audience']) ? $workflow['audience'] : 'Email')?></p>
                    <h1 style="margin:0 0 12px;color:#09285e;font-size:24px;line-height:32px"><?=$mailEsc(isset($copy['heading']) ? $copy['heading'] : '')?></h1>
                    <p style="margin:0 0 20px;color:#405270;font-size:15px;line-height:24px"><?=nl2br($mailEsc(isset($copy['intro']) ? $copy['intro'] : ''))?></p>
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top:2px solid {emailColor}">
                        <?php foreach($fields as $label => $value) { ?>
                            <tr>
                                <td style="padding:10px 10px 10px 0;border-bottom:1px solid #e7ebf1;color:#61708a;font-size:13px;width:34%"><strong><?=$mailEsc($label)?></strong></td>
                                <td style="padding:10px 0;border-bottom:1px solid #e7ebf1;color:#15284b;font-size:14px"><?=$mailEsc($value)?></td>
                            </tr>
                        <?php } ?>
                    </table>
                    <p style="margin:20px 0 0;color:#405270;font-size:13px;line-height:21px"><?=nl2br($mailEsc(isset($copy['closing']) ? $copy['closing'] : ''))?></p>
                </td></tr></tbody>
            </table>
        </td></tr>
        <?php include LIBRARIES."sample/mail/layout/footer.php"; ?>
    </tbody>
</table>
