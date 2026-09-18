<?php
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'mail_config.php';
require LIBRARIES.'class/class.Email.php';

$failures = 0;
function emailConfigOk($condition, $message)
{
    global $failures;
    if($condition) echo 'PASS: '.$message.PHP_EOL;
    else { ++$failures; echo 'FAIL: '.$message.PHP_EOL; }
}

emailConfigOk(version_compare(PHPMailer\PHPMailer\PHPMailer::VERSION, '6.5.0', '>='), 'PHPMailer đã vượt minimum security fix 6.5.0');

$valid = array(
    'enabled' => true,
    'host' => 'mail.example.test',
    'port' => 587,
    'encryption' => 'starttls',
    'username' => 'website@example.test',
    'password' => 'unit-test-placeholder-only',
    'from' => 'website@example.test',
    'from_name' => 'Phúc Thịnh Solar',
    'admin_to' => 'admin@example.test',
	'test_to' => 'developer@example.test',
    'timeout' => 10
);

emailConfigOk(Email::validateConfig($valid) === array(), 'cấu hình đầy đủ và STARTTLS hợp lệ');
emailConfigOk(!empty(Email::readiness($valid, false, false)['ok']), 'readiness config-only không mở kết nối mạng');
$appConfig = array('mail' => $valid);
emailConfigOk(loadMailConfig($appConfig) === $valid, 'loader ưu tiên một nguồn $config[mail] đã khai báo');

$missingEncryption = $valid;
$missingEncryption['encryption'] = '';
emailConfigOk(in_array('encryption', Email::validateConfig($missingEncryption), true), 'không tự đoán encryption khi bị thiếu');

$badFrom = $valid;
$badFrom['from'] = "attacker@example.test\r\nBcc: victim@example.test";
emailConfigOk(in_array('from', Email::validateConfig($badFrom), true), 'chặn CRLF/header injection trong sender');

$relayConfig = $valid;
$relayConfig['username'] = 'relay-account@smtp.example.test';
$relayConfig['from'] = 'website@example.test';
emailConfigOk(Email::validateConfig($relayConfig) === array(), 'SMTP relay cho phép credential đăng nhập khác From đã xác thực');

$badTestRecipient = $valid;
$badTestRecipient['test_to'] = "developer@example.test\r\nBcc: victim@example.test";
emailConfigOk(in_array('test_to', Email::validateConfig($badTestRecipient), true), 'chặn header injection trong địa chỉ gửi thử cố định');

$placeholderPassword = $valid;
$placeholderPassword['password'] = 'REPLACE_WITH_MAILBOX_PASSWORD';
emailConfigOk(in_array('password', Email::validateConfig($placeholderPassword), true), 'placeholder password không thể pass readiness');

$source = file_get_contents(LIBRARIES.'class/class.Email.php');
emailConfigOk(strpos($source, "['password_host']") === false && strpos($source, "['password_gmail']") === false, 'runtime không còn đọc SMTP password legacy từ DB');
emailConfigOk(strpos($source, 'addBCC') !== false, 'newsletter nhiều người dùng BCC để không lộ danh sách');
emailConfigOk(strpos($source, 'addReplyTo') !== false, 'wrapper hỗ trợ Reply-To đã validate');
emailConfigOk(strpos($source, "self::cleanHeaderText(\$config['from_name'])") !== false, 'readiness dùng From name đã cấu hình và làm sạch header');
emailConfigOk(strpos($source, "getMessage()") === false, 'không ghi raw SMTP exception vào log');

$settingTemplate = file_get_contents('/var/www/html/administrator/templates/setting/man/man_add_tpl.php');
$settingSource = file_get_contents('/var/www/html/administrator/sources/setting.php');
emailConfigOk(strpos($settingTemplate, 'name="data[options][password_host]"') === false, 'Admin không render input SMTP password legacy');
emailConfigOk(strpos($settingSource, "'password_host'") !== false && strpos($settingSource, 'legacyMailKeys') !== false, 'backend bỏ qua direct POST khóa SMTP legacy');
emailConfigOk(strpos($settingTemplate, 'Hệ thống email (chỉ đọc)') !== false, 'Admin hiển thị trạng thái và bản đồ workflow mail');
emailConfigOk(strpos($settingTemplate, '$mailView(\'password\')') === false, 'Admin không hiển thị SMTP password mới');

$allpageSource = file_get_contents('/var/www/html/sources/allpage.php');
$orderSource = file_get_contents('/var/www/html/sources/order.php');
$userSource = file_get_contents('/var/www/html/sources/user.php');
$forgotTemplate = file_get_contents('/var/www/html/templates/account/forgot_password_tpl.php');
emailConfigOk(substr_count($allpageSource, 'sendAdminNotification(') === 2, 'tư vấn và đại lý đều gọi notification sau khi lưu DB');
emailConfigOk(strpos($allpageSource, "'email' => \$emailer->get('emailnguoigui')") !== false, 'mail liên hệ gắn Reply-To khách');
emailConfigOk(strpos($orderSource, "'email' => \$email") !== false, 'mail đơn hàng admin gắn Reply-To khách');
emailConfigOk(strpos($userSource, "formPersistentRateLimit(\$d, 'member-forgot-password'") !== false, 'forgot-password có persistent rate limit');
emailConfigOk(strpos($forgotTemplate, 'name="form_csrf"') !== false && strpos($userSource, 'formCsrfValid(') !== false, 'forgot-password có CSRF protection');
emailConfigOk(strpos($userSource, "array('password' => \$oldPasswordHash)") !== false, 'SMTP fail phục hồi password cũ có điều kiện');
emailConfigOk(strpos($userSource, "substr(\$matkhau,-3)") === false, 'email thành viên không còn lộ một phần password hash');

$definitions = ptsMailTemplateDefinitions();
$cleanTemplates = ptsMailTemplateCleanPosted(array(
	'consult_admin' => array(
		'subject' => "<b>Yêu cầu</b> {customer_name}\r\nBcc: bad@example.test",
		'heading' => '<script>alert(1)</script>Tiêu đề',
		'intro' => 'Nội dung',
		'closing' => 'Kết thúc'
	),
	'unknown' => array('subject' => 'Không được lưu')
));
emailConfigOk(count($definitions) === 8 && count($cleanTemplates) === 8, 'allowlist đủ 8 workflow và loại workflow không xác định');
emailConfigOk(strpos($cleanTemplates['consult_admin']['heading'], '<script>') === false, 'nội dung Admin được lưu dưới dạng plain text');
emailConfigOk(strpos($cleanTemplates['consult_admin']['subject'], "\r") === false && strpos($cleanTemplates['consult_admin']['subject'], "\n") === false, 'tiêu đề tùy chỉnh loại control character');
emailConfigOk(ptsMailTemplateReplace('Đơn {order_code}', array('order_code' => 'PTS-001')) === 'Đơn PTS-001', 'placeholder mẫu email được thay đúng');

$mailTemplateSource = file_get_contents('/var/www/html/administrator/sources/mail_template.php');
$mailTemplateView = file_get_contents('/var/www/html/administrator/templates/mail_template/man_tpl.php');
$securitySource = file_get_contents('/var/www/html/libraries/admin_security.php');
emailConfigOk(strpos($mailTemplateSource, "\$mailConfig['test_to']") !== false && strpos($mailTemplateSource, "\$_POST['test_to']") === false, 'gửi thử chỉ dùng recipient cố định từ secret');
emailConfigOk(strpos($mailTemplateView, 'adminCsrfField()') !== false, 'lưu và gửi thử có CSRF field');
emailConfigOk(strpos($securitySource, "\$com === 'mail_template'") !== false && strpos($securitySource, "return 'setting_update'") !== false, 'màn mẫu email kế thừa quyền setting_update');

$allpageSource = file_get_contents('/var/www/html/sources/allpage.php');
emailConfigOk(strpos($allpageSource, "'consult_admin'") !== false && strpos($allpageSource, "'dealer_admin'") !== false, 'tư vấn và đại lý chọn đúng workflow template');
$newsletterSource = file_get_contents('/var/www/html/administrator/sources/newsletter.php');
emailConfigOk(strpos($newsletterSource, 'ptsMailTemplateCleanText($_POST[\'subject\']') !== false, 'newsletter dùng Subject Admin nhập đã làm sạch');

echo $failures ? 'Email production config tests failed.'.PHP_EOL : 'Email production config tests completed.'.PHP_EOL;
exit($failures ? 1 : 0);
