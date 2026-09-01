<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);
require LIBRARIES.'admin_security.php';
require LIBRARIES.'admin_product_pricing.php';

function pricingHttpAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: ', $message, PHP_EOL;
}

function pricingHttp($url, $method = 'GET', $data = array(), $cookie = '')
{
    $headers = array('User-Agent: PTS-Product-Pricing-Test');
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

function pricingFormAssign(&$data, $name, $value)
{
    if(preg_match('/^([^\[]+)\[([^\]]*)\]$/', $name, $match))
    {
        if($match[2] === '') $data[$match[1]][] = $value;
        else $data[$match[1]][$match[2]] = $value;
    }
    else $data[$name] = $value;
}

function pricingFormData($html)
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
            foreach($xpath->query('.//option[@selected]', $field) as $option) pricingFormAssign($data, $name, $option->getAttribute('value'));
            continue;
        }
        $value = $tag === 'textarea' ? $field->textContent : $field->getAttribute('value');
        pricingFormAssign($data, $name, $value);
    }
    return $data;
}

function pricingSubmitEdit($base, $cookie, $id, $regular, $sale)
{
    $form = pricingHttp($base.'index.php?com=product&act=edit&type=san-pham&id='.(int)$id, 'GET', array(), $cookie);
    pricingHttpAssert($form['status'] === 200, 'Mở form sửa sản phẩm #'.(int)$id);
    $data = pricingFormData($form['body']);
    pricingHttpAssert(!empty($data['admin_csrf']) && !empty($data['data']['namevi']), 'Đọc đủ dữ liệu form sản phẩm #'.(int)$id);
    $data['data']['regular_price'] = (string)$regular;
    $data['data']['sale_price'] = (string)$sale;
    $data['data']['discount'] = '';
    $result = pricingHttp($base.'index.php?com=product&act=save&type=san-pham', 'POST', $data, $cookie);
    pricingHttpAssert($result['status'] === 200, 'Submit form sửa sản phẩm #'.(int)$id);
    return $result;
}

$prefix = 'CODEX-PRICING-'.bin2hex(random_bytes(4));
$groupId = 0;
$userId = 0;
$fixtureId = 0;
$repairId = 0;
foreach($argv as $argument) if(preg_match('/^--repair-product=(\d+)$/', $argument, $match)) $repairId = (int)$match[1];

try
{
    foreach($d->rawQuery("select id from #_user where username like ?", array('codex-pricing-%')) as $staleUser)
    {
        $d->rawQuery('delete from #_user_log where id_user = ?', array((int)$staleUser['id']));
        $d->rawQuery('delete from #_user where id = ?', array((int)$staleUser['id']));
    }
    foreach($d->rawQuery("select id from #_permission_group where name like ?", array('CODEX-PRICING-%')) as $staleGroup)
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
        'fullname' => 'Local pricing invariant test',
        'status' => 'hienthi',
        'role' => 1,
        'secret_key' => bin2hex(random_bytes(16)),
        'numb' => 0
    ));
    $userId = (int)$d->getLastInsertId();

    $code = $prefix.'-PRODUCT';
    $d->insert('product', array('code' => $code, 'namevi' => 'Sản phẩm kiểm thử giá Admin', 'slugvi' => strtolower($code), 'type' => 'san-pham', 'status' => 'hienthi', 'regular_price' => 500000, 'sale_price' => 450000, 'discount' => 10, 'cong_suat' => 0, 'numb' => 0, 'date_created' => time()));
    $fixtureId = (int)$d->getLastInsertId();

    $base = 'http://127.0.0.1/administrator/';
    $loginPage = pricingHttp($base.'index.php?com=user&act=login');
    pricingHttpAssert($loginPage['status'] === 200 && $loginPage['cookie'] !== '', 'Mở login Admin và nhận cookie');
    pricingHttpAssert(preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $loginPage['body'], $csrf) === 1, 'Nhận CSRF login');
    $login = pricingHttp($base.'api/login.php', 'POST', array('username' => $username, 'password' => $password, 'admin_csrf' => $csrf[1]), $loginPage['cookie']);
    $loginPayload = json_decode($login['body'], true);
    pricingHttpAssert($login['status'] === 200 && !empty($loginPayload['success']) && $login['cookie'] !== '', 'Đăng nhập Admin fixture qua HTTP');
    $cookie = $login['cookie'];

    pricingSubmitEdit($base, $cookie, $fixtureId, 500000, 450000);
    $row = $d->rawQueryOne('select regular_price, sale_price, discount from #_product where id = ?', array($fixtureId));
    pricingHttpAssert((float)$row['regular_price'] === 500000.0 && (float)$row['sale_price'] === 450000.0 && (float)$row['discount'] === 10.0, 'Form Admin tự tính sale < regular thành discount 10%');

    pricingSubmitEdit($base, $cookie, $fixtureId, 500000, 500000);
    $row = $d->rawQueryOne('select regular_price, sale_price, discount from #_product where id = ?', array($fixtureId));
    pricingHttpAssert((float)$row['discount'] === 0.0, 'Form Admin đưa sale = regular thành discount 0%');

    pricingSubmitEdit($base, $cookie, $fixtureId, 500000, 550000);
    $row = $d->rawQueryOne('select regular_price, sale_price, discount from #_product where id = ?', array($fixtureId));
    pricingHttpAssert((float)$row['sale_price'] === 500000.0 && (float)$row['discount'] === 0.0, 'Form Admin chặn sale > regular và không lưu dữ liệu sai');

    if($repairId > 0)
    {
        $before = $d->rawQueryOne('select regular_price, sale_price, discount from #_product where id = ? and type = ?', array($repairId, 'san-pham'));
        pricingHttpAssert(!empty($before), 'Tìm thấy sản phẩm cần sửa #'.$repairId);
        pricingSubmitEdit($base, $cookie, $repairId, $before['regular_price'], $before['sale_price']);
        $after = $d->rawQueryOne('select regular_price, sale_price, discount from #_product where id = ?', array($repairId));
        $expected = adminNormalizeProductPricing($after);
        pricingHttpAssert((float)$after['discount'] === (float)$expected['values']['discount'], 'Sửa discount sản phẩm #'.$repairId.' qua form Admin');
    }
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
