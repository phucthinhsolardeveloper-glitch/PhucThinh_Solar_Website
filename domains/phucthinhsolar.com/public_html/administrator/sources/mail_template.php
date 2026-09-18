<?php
if(!defined('SOURCES')) die('Error');

require_once LIBRARIES.'mail_templates.php';

$mailTemplateKey = isset($_REQUEST['key']) ? preg_replace('/[^a-z_]/', '', (string)$_REQUEST['key']) : '';

switch($act)
{
    case 'man':
        mailTemplateView();
        $template = 'mail_template/man';
        break;
    case 'save':
        mailTemplateSave();
        break;
    case 'preview':
        mailTemplatePreview();
        break;
    case 'save_test':
        mailTemplateSendTest();
        break;
    default:
        $template = '404';
}

function mailTemplateAllowedKey($key)
{
    $definitions = ptsMailTemplateDefinitions();
    return isset($definitions[$key]) ? $key : '';
}

function mailTemplateView()
{
    global $d, $config, $mailTemplateDefinitions, $mailTemplateValues, $mailTemplateStatus;
    $row = $d->rawQueryOne('select options from #_setting limit 0,1');
    $options = !empty($row['options']) ? json_decode($row['options'], true) : array();
    if(!is_array($options)) $options = array();
    $mailTemplateDefinitions = ptsMailTemplateDefinitions();
    $mailTemplateValues = ptsMailTemplateStored($options);
    $mailConfig = isset($config['mail']) && is_array($config['mail']) ? $config['mail'] : array();
    $mailTemplateStatus = array(
        'configured' => empty(Email::validateConfig($mailConfig)),
        'from' => isset($mailConfig['from']) ? (string)$mailConfig['from'] : '',
        'admin_to' => isset($mailConfig['admin_to']) ? (string)$mailConfig['admin_to'] : '',
        'test_to' => isset($mailConfig['test_to']) ? (string)$mailConfig['test_to'] : ''
    );
}

function mailTemplateSave()
{
    global $d, $func;
    $row = $d->rawQueryOne('select id, options from #_setting limit 0,1');
    if(empty($row['id'])) $func->transfer('Không tìm thấy cấu hình website.', 'index.php?com=mail_template&act=man', false);
    $options = !empty($row['options']) ? json_decode($row['options'], true) : array();
    if(!is_array($options)) $options = array();
    $options['mail_templates'] = ptsMailTemplateCleanPosted(isset($_POST['mail_templates']) ? $_POST['mail_templates'] : array());
    $payload = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if($payload === false) $func->transfer('Nội dung mẫu email không hợp lệ.', 'index.php?com=mail_template&act=man', false);
    $d->where('id', (int)$row['id']);
    if($d->update('setting', array('options' => $payload)))
        $func->transfer('Đã lưu nội dung mẫu email.', 'index.php?com=mail_template&act=man');
    $func->transfer('Không thể lưu mẫu email. Vui lòng thử lại.', 'index.php?com=mail_template&act=man', false);
}

function mailTemplatePreview()
{
    global $emailer, $mailTemplateKey;
    $key = mailTemplateAllowedKey($mailTemplateKey);
    if($key === '') { http_response_code(404); exit('Mẫu email không tồn tại.'); }
    $message = $emailer->workflowPreview($key);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'; img-src https: data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'");
    echo $message;
    exit();
}

function mailTemplateSendTest()
{
    global $emailer, $config, $func, $mailTemplateKey;
    $key = mailTemplateAllowedKey($mailTemplateKey);
    $mailConfig = isset($config['mail']) && is_array($config['mail']) ? $config['mail'] : array();
    $recipient = isset($mailConfig['test_to']) ? trim((string)$mailConfig['test_to']) : '';
    if($key === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL))
        $func->transfer('Chưa cấu hình địa chỉ test cố định hoặc mẫu email không hợp lệ.', 'index.php?com=mail_template&act=man', false);
    $definitions = ptsMailTemplateDefinitions();
    $subject = '[TEST] '.$emailer->templateSubject($key, array(
        'customer_name' => 'Nguyễn Văn An', 'customer_phone' => '0900 000 000',
        'company' => 'Công ty Minh An', 'contact_subject' => 'Tư vấn điện mặt trời',
        'order_code' => 'PTS-DEMO-001'
    ), $definitions[$key]['label']);
    $message = $emailer->workflowPreview($key);
    $sent = $emailer->send('customer', array(array('name' => 'Hộp thư kiểm thử', 'email' => $recipient)), $subject, $message);
    $func->transfer($sent ? 'Đã gửi email test tới địa chỉ kiểm thử cố định.' : 'Gửi email test thất bại. Vui lòng kiểm tra SMTP.', 'index.php?com=mail_template&act=man', $sent);
}
?>
