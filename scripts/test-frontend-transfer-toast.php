<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';

function frontToastAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: ', $message, PHP_EOL;
}

function frontToastHttp($url, $method = 'GET', $data = array(), $cookie = '', $followLocation = 1)
{
    $headers = array('User-Agent: PTS-Frontend-Transfer-Toast-Test');
    if($cookie !== '') $headers[] = 'Cookie: '.$cookie;
    $options = array('http' => array('method' => $method, 'ignore_errors' => true, 'timeout' => 20, 'header' => implode("\r\n", $headers), 'follow_location' => $followLocation));
    if($method === 'POST')
    {
        $options['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
        $options['http']['content'] = http_build_query($data);
    }
    $body = file_get_contents($url, false, stream_context_create($options));
    $responseHeaders = isset($http_response_header) ? $http_response_header : array();
    $status = 0;
    $location = '';
    $cookies = array();
    foreach($responseHeaders as $header)
    {
        if(preg_match('#^HTTP/\S+\s+(\d+)#i', $header, $match)) $status = (int)$match[1];
        if(stripos($header, 'Location:') === 0) $location = trim(substr($header, 9));
        if(stripos($header, 'Set-Cookie:') === 0 && preg_match('/^(PHPSESSID=[^;]+)/', trim(substr($header, 11)), $match)) $cookies[] = $match[1];
    }
    return array('status' => $status, 'body' => $body, 'location' => $location, 'cookie' => $cookies ? end($cookies) : '');
}

$base = 'http://127.0.0.1/';

/* Kích hoạt nhánh lỗi CSRF của contact.php mà không cần gửi email thật:
   thiếu submit-contact thì code còn chưa chạy tới, phải gửi kèm field này. */
$page = frontToastHttp($base.'lien-he');
frontToastAssert($page['status'] === 200 && $page['cookie'] !== '', 'Mở /lien-he và nhận session cookie');

/* Chuyển hướng về đúng URL trang đang đứng (getPageURL()), không còn dùng
   chuỗi tương đối "lien-he" — chuỗi tương đối bị trình duyệt phân giải sai
   thành /lien-he/lien-he khi trang được truy cập kèm dấu / cuối. */
$postNoFollow = frontToastHttp($base.'lien-he', 'POST', array('submit-contact' => '1', 'form_csrf' => 'gia-mao-token-sai'), $page['cookie'], 0);
frontToastAssert($postNoFollow['status'] === 302 && $postNoFollow['location'] === $base.'lien-he', 'Submit CSRF sai chuyển hướng ngay bằng 302, không còn màn chờ 4.5s');
frontToastAssert(trim((string)$postNoFollow['body']) === '', 'Response redirect không kèm theo HTML trang chờ cũ');

$dest = frontToastHttp($base.'lien-he', 'GET', array(), $page['cookie']);
frontToastAssert($dest['status'] === 200, 'Mở lại trang đích sau redirect');
frontToastAssert(preg_match('/var\s+FLASH_TOAST\s*=\s*(\{[^;]*\});/', $dest['body'], $toastMatch) === 1, 'Trang đích công khai có biến FLASH_TOAST');
$toastPayload = json_decode($toastMatch[1], true);
frontToastAssert(is_array($toastPayload) && $toastPayload['status'] === 'error' && strpos($toastPayload['text'], 'Phiên gửi liên hệ không hợp lệ') !== false, 'FLASH_TOAST mang đúng nội dung lỗi CSRF');
frontToastAssert(strpos($dest['body'], 'showNotify(FLASH_TOAST.text') !== false, 'Trang đích công khai có gọi showNotify() để hiện toast');

$again = frontToastHttp($base.'lien-he', 'GET', array(), $page['cookie']);
frontToastAssert(preg_match('/var\s+FLASH_TOAST\s*=\s*null;/', $again['body']) === 1, 'Toast công khai không hiện lại ở lần tải trang tiếp theo');

echo "Frontend transfer toast tests completed." . PHP_EOL;
