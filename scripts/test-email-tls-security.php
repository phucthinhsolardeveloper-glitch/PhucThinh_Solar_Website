<?php
/** Static regression: SMTP không được hạ kiểm tra TLS trong source. */
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'class/class.Email.php';

$failures = 0;
function emailTlsOk($condition, $message)
{
    global $failures;
    if($condition) echo 'PASS: '.$message.PHP_EOL;
    else { ++$failures; echo 'FAIL: '.$message.PHP_EOL; }
}

$options = Email::secureSmtpOptions();
$ssl = isset($options['ssl']) ? $options['ssl'] : array();
emailTlsOk(isset($ssl['verify_peer']) && $ssl['verify_peer'] === true, 'SMTP xác minh certificate peer');
emailTlsOk(isset($ssl['verify_peer_name']) && $ssl['verify_peer_name'] === true, 'SMTP xác minh hostname');
emailTlsOk(isset($ssl['allow_self_signed']) && $ssl['allow_self_signed'] === false, 'SMTP từ chối certificate self-signed');

$source = file_get_contents(LIBRARIES.'class/class.Email.php');
emailTlsOk(strpos($source, "'verify_peer' => false") === false, 'Email source không còn peer-verification bypass');
emailTlsOk(strpos($source, "'verify_peer_name' => false") === false, 'Email source không còn hostname-verification bypass');
emailTlsOk(strpos($source, "'allow_self_signed' => true") === false, 'Email source không còn self-signed bypass');

echo $failures ? 'Email TLS security test failed.'.PHP_EOL : 'Email TLS security tests completed.'.PHP_EOL;
exit($failures ? 1 : 0);
