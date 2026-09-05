<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);

function toastAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: ', $message, PHP_EOL;
}

function toastHttp($url, $method = 'GET', $data = array(), $cookie = '')
{
    $headers = array('User-Agent: PTS-Admin-Transfer-Toast-Test');
    if($cookie !== '') $headers[] = 'Cookie: '.$cookie;
    $options = array('http' => array('method' => $method, 'ignore_errors' => true, 'timeout' => 20, 'header' => implode("\r\n", $headers)));
    if($method === 'POST')
    {
        $options['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
        $options['http']['content'] = http_build_query($data);
    }
    $body = file_get_contents($url, false, stream_context_create($options));
    $responseHeaders = isset($http_response_header) ? $http_response_header : array();
    $status = 0;
    $cookies = array();
    foreach($responseHeaders as $header)
    {
        if(preg_match('#^HTTP/\S+\s+(\d+)#i', $header, $match)) $status = (int)$match[1];
        if(stripos($header, 'Set-Cookie:') === 0 && preg_match('/^(PHPSESSID=[^;]+)/', trim(substr($header, 11)), $match)) $cookies[] = $match[1];
    }
    return array('status' => $status, 'body' => $body, 'cookie' => $cookies ? end($cookies) : '');
}

function toastFormAssign(&$data, $name, $value)
{
    if(preg_match('/^([^\[]+)\[([^\]]*)\]$/', $name, $match))
    {
        if($match[2] === '') $data[$match[1]][] = $value;
        else $data[$match[1]][$match[2]] = $value;
    }
    else $data[$name] = $value;
}

function toastFormData($html)
{
    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($document);
    $form = $xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " validation-form ")]')->item(0);
    if(!$form) return array();
    $data = array();
    foreach($xpath->query('.//input|.//textarea|.//select', $form) as $field)
    {
        $name = $field->getAttribute('name');
        if($name === '' || $field->hasAttribute('disabled')) continue;
        $tag = strtolower($field->nodeName);
        $type = strtolower($field->getAttribute('type'));
        if($tag === 'input' && in_array($type, array('file','submit','reset','button'), true)) continue;
        if($tag === 'input' && in_array($type, array('checkbox','radio'), true) && !$field->hasAttribute('checked')) continue;
        if($tag === 'select')
        {
            foreach($xpath->query('.//option[@selected]', $field) as $option) toastFormAssign($data, $name, $option->getAttribute('value'));
            continue;
        }
        $value = $tag === 'textarea' ? $field->textContent : $field->getAttribute('value');
        toastFormAssign($data, $name, $value);
    }
    return $data;
}

$prefix = 'CODEX-TOAST-'.bin2hex(random_bytes(4));
$groupId = 0;
$userId = 0;
$fixtureId = 0;

try
{
    foreach($d->rawQuery("select id from #_user where username like ?", array('codex-toast-%')) as $staleUser)
    {
        $d->rawQuery('delete from #_user_log where id_user = ?', array((int)$staleUser['id']));
        $d->rawQuery('delete from #_user where id = ?', array((int)$staleUser['id']));
    }
    foreach($d->rawQuery("select id from #_permission_group where name like ?", array('CODEX-TOAST-%')) as $staleGroup)
    {
        $d->rawQuery('delete from #_permission where id_permission_group = ?', array((int)$staleGroup['id']));
        $d->rawQuery('delete from #_permission_group where id = ?', array((int)$staleGroup['id']));
    }
    $d->insert('permission_group', array('name' => $prefix, 'numb' => 0, 'status' => 'hienthi', 'date_created' => time()));
    $groupId = (int)$d->getLastInsertId();
    foreach(array('product_man_san-pham','product_add_san-pham','product_edit_san-pham') as $permission)
        $d->insert('permission', array('id_permission_group' => $groupId, 'permission' => $permission));
    $username = strtolower($prefix);
    $password = bin2hex(random_bytes(12));
    $d->insert('user', array(
        'id_permission' => $groupId,
        'username' => $username,
        'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
        'fullname' => 'Local transfer toast test',
        'status' => 'hienthi',
        'role' => 1,
        'secret_key' => bin2hex(random_bytes(16)),
        'numb' => 0
    ));
    $userId = (int)$d->getLastInsertId();

    $code = $prefix.'-PRODUCT';
    $d->insert('product', array('code' => $code, 'namevi' => 'Sản phẩm kiểm thử toast Admin', 'slugvi' => strtolower($code), 'type' => 'san-pham', 'status' => 'hienthi', 'regular_price' => 100000, 'sale_price' => 0, 'discount' => 0, 'cong_suat' => 0, 'numb' => 0, 'date_created' => time()));
    $fixtureId = (int)$d->getLastInsertId();

    $base = 'http://127.0.0.1/administrator/';
    $loginPage = toastHttp($base.'index.php?com=user&act=login');
    toastAssert($loginPage['status'] === 200 && $loginPage['cookie'] !== '', 'Mở login Admin và nhận cookie');
    toastAssert(preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $loginPage['body'], $csrf) === 1, 'Nhận CSRF login');
    $login = toastHttp($base.'api/login.php', 'POST', array('username' => $username, 'password' => $password, 'admin_csrf' => $csrf[1]), $loginPage['cookie']);
    $loginPayload = json_decode($login['body'], true);
    toastAssert($login['status'] === 200 && !empty($loginPayload['success']) && $login['cookie'] !== '', 'Đăng nhập Admin fixture qua HTTP');
    $cookie = $login['cookie'];

    $form = toastHttp($base.'index.php?com=product&act=edit&type=san-pham&id='.$fixtureId, 'GET', array(), $cookie);
    toastAssert($form['status'] === 200, 'Mở form sửa sản phẩm fixture');
    $data = toastFormData($form['body']);
    toastAssert(!empty($data['admin_csrf']) && !empty($data['data']['namevi']), 'Đọc đủ dữ liệu form sản phẩm fixture');
    $result = toastHttp($base.'index.php?com=product&act=save&type=san-pham', 'POST', $data, $cookie);
    toastAssert($result['status'] === 200, 'Submit form Lưu trả về 200 sau khi theo redirect');

    toastAssert(strpos($result['body'], 'Click vào đây nếu không muốn đợi lâu') === false, 'Không còn màn "Thông báo" đếm ngược 4.5s sau khi Lưu ở Admin');
    toastAssert(strpos($result['body'], 'meta http-equiv="REFRESH"') === false && stripos($result['body'], 'meta http-equiv="refresh"') === false, 'Không còn thẻ meta refresh trên trang đích');
    toastAssert(preg_match('/var\s+FLASH_TOAST\s*=\s*(\{[^;]*\});/', $result['body'], $toastMatch) === 1, 'Trang đích có biến FLASH_TOAST cho JS hiển thị');
    $toastPayload = json_decode($toastMatch[1], true);
    toastAssert(is_array($toastPayload) && $toastPayload['status'] === 'success' && !empty($toastPayload['text']), 'FLASH_TOAST mang đúng trạng thái success và có nội dung thông báo');
    toastAssert(strpos($result['body'], 'showNotify(FLASH_TOAST.text') !== false, 'Trang đích có gọi showNotify() để hiện toast');

    /* Lần load trang kế tiếp không được hiện lại toast cũ (flash chỉ đọc một lần). */
    $again = toastHttp($base.'index.php?com=product&act=man&type=san-pham', 'GET', array(), $cookie);
    toastAssert($again['status'] === 200, 'Mở lại danh sách sản phẩm');
    toastAssert(preg_match('/var\s+FLASH_TOAST\s*=\s*null;/', $again['body']) === 1, 'Toast không hiện lại ở lần tải trang tiếp theo');
}
finally
{
    if($fixtureId > 0)
    {
        $d->rawQuery('delete from #_seo where id_parent = ? and com = ? and type = ?', array($fixtureId, 'product', 'san-pham'));
        $d->rawQuery('delete from #_product where id = ?', array($fixtureId));
    }
    if($userId > 0)
    {
        $d->rawQuery('delete from #_user_log where id_user = ?', array($userId));
        $d->rawQuery('delete from #_user where id = ?', array($userId));
    }
    if($groupId > 0)
    {
        $d->rawQuery('delete from #_permission where id_permission_group = ?', array($groupId));
        $d->rawQuery('delete from #_permission_group where id = ?', array($groupId));
    }
}

echo "Admin transfer toast tests completed." . PHP_EOL;
