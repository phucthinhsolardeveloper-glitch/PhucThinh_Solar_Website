<?php
/**
 * Kiểm thử luồng ghi của Admin qua HTTP thật: thêm -> sửa -> xoá.
 *
 * test-admin-screen-sweep.php cố tình chỉ gọi màn hình đọc, nên toàn bộ đường
 * ghi — nơi dữ liệu thật sự bị thay đổi — không được bộ nào chạy qua. Script
 * này bù vào chỗ đó: mở đúng form Add của Admin, lấy đủ field và CSRF từ chính
 * form đó, submit như trình duyệt, rồi kiểm tra trong DB.
 *
 * Chỉ đụng vào bản ghi do chính nó tạo, tên và mã đều mang tiền tố CRUDTEST-
 * kèm chuỗi ngẫu nhiên, và dọn sạch ở cuối kể cả khi giữa chừng lỗi.
 *
 * Chạy: php scripts/test-admin-crud-lifecycle.php
 */

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);

$base = 'http://127.0.0.1/administrator/';
$passes = 0;
$failures = 0;

function ok($condition, $message)
{
    global $passes, $failures;
    if ($condition) { $passes++; echo 'PASS: ', $message, PHP_EOL; }
    else { $failures++; echo 'FAIL: ', $message, PHP_EOL; }
}

function request($url, $cookie = '', $post = null)
{
    $header = "User-Agent: PTS-CRUD-Lifecycle\r\n";
    if ($cookie !== '') $header .= 'Cookie: '.$cookie."\r\n";
    $options = array('http' => array('ignore_errors' => true, 'timeout' => 30,
        'method' => $post === null ? 'GET' : 'POST', 'follow_location' => 0));
    if ($post !== null)
    {
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $options['http']['content'] = http_build_query($post);
    }
    $options['http']['header'] = $header;
    $body = @file_get_contents($url, false, stream_context_create($options));
    $status = 0; $newCookie = '';
    foreach ((array)$http_response_header as $line)
    {
        if (preg_match('#^HTTP/\S+\s+(\d+)#i', $line, $m)) $status = (int)$m[1];
        if (stripos($line, 'Set-Cookie:') === 0 && preg_match('/(PHPSESSID=[^;]+)/', $line, $m)) $newCookie = $m[1];
    }
    return array('status' => $status, 'body' => (string)$body, 'cookie' => $newCookie);
}

/* Đọc mọi field của form Add để submit y như trình duyệt gửi. */
function formFields($html)
{
    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    $xpath = new DOMXPath($document);
    $form = $xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " validation-form ")]')->item(0);
    if (!$form) return array();
    $data = array();
    $assign = function (&$data, $name, $value) {
        if (preg_match('/^([^\[]+)((?:\[[^\]]*\])+)$/', $name, $m))
        {
            preg_match_all('/\[([^\]]*)\]/', $m[2], $keys);
            $ref = &$data[$m[1]];
            foreach ($keys[1] as $key)
            {
                if ($key === '') { $ref[] = array(); $ref = &$ref[count($ref) - 1]; }
                else { if (!isset($ref[$key])) $ref[$key] = array(); $ref = &$ref[$key]; }
            }
            $ref = $value;
        }
        else $data[$name] = $value;
    };
    foreach ($xpath->query('.//input|.//textarea|.//select', $form) as $field)
    {
        $name = $field->getAttribute('name');
        if ($name === '' || $field->hasAttribute('disabled')) continue;
        $tag = strtolower($field->nodeName);
        $type = strtolower($field->getAttribute('type'));
        if ($tag === 'input' && in_array($type, array('file', 'submit', 'reset', 'button'), true)) continue;
        if ($tag === 'input' && in_array($type, array('checkbox', 'radio'), true) && !$field->hasAttribute('checked')) continue;
        if ($tag === 'select')
        {
            $selected = $xpath->query('.//option[@selected]', $field);
            if ($selected->length) foreach ($selected as $option) $assign($data, $name, $option->getAttribute('value'));
            continue;
        }
        $assign($data, $name, $tag === 'textarea' ? $field->textContent : $field->getAttribute('value'));
    }
    return $data;
}

$suffix = strtoupper(bin2hex(random_bytes(4)));
$username = 'crud-'.strtolower($suffix);
$password = bin2hex(random_bytes(12));
$userId = 0;
$createdProducts = array();
$createdNews = array();

register_shutdown_function(function () use (&$userId, &$createdProducts, &$createdNews, $d, $suffix) {
    foreach ($createdProducts as $id)
    {
        $d->rawQuery('delete from #_product where id = ?', array((int)$id));
        $d->rawQuery("delete from #_seo where com = 'product' and act = 'man' and id_parent = ?", array((int)$id));
    }
    foreach ($createdNews as $id)
    {
        $d->rawQuery('delete from #_news where id = ?', array((int)$id));
        $d->rawQuery("delete from #_seo where com = 'news' and act = 'man' and id_parent = ?", array((int)$id));
    }
    /* Quét lại theo tiền tố phòng khi script chết trước lúc ghi id vào mảng. */
    $d->rawQuery('delete from #_product where code like ?', array('CRUDTEST-'.$suffix.'%'));
    $d->rawQuery('delete from #_news where namevi like ?', array('CRUDTEST-'.$suffix.'%'));
    if ($userId)
    {
        $d->rawQuery('delete from #_user_log where id_user = ?', array($userId));
        $d->rawQuery('delete from #_user where id = ?', array($userId));
    }
});

$d->insert('user', array(
    'id_permission' => 0, 'username' => $username,
    'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
    'fullname' => 'CRUD lifecycle test', 'status' => 'hienthi', 'role' => 3,
    'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
));
$userId = (int)$d->getLastInsertId();

$page = request($base.'index.php?com=user&act=login');
$cookie = $page['cookie'];
$page = request($base.'index.php?com=user&act=login', $cookie);
if (!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $page['body'], $csrf))
{
    fwrite(STDERR, "Không lấy được CSRF đăng nhập.\n");
    exit(1);
}
$login = request($base.'api/login.php', $cookie,
    array('username' => $username, 'password' => $password, 'admin_csrf' => $csrf[1]));
if ($login['cookie'] !== '') $cookie = $login['cookie'];
$payload = json_decode($login['body'], true);
ok(!empty($payload['success']), 'Đăng nhập tài khoản kiểm thử');
if (empty($payload['success'])) exit(1);

/* ---------------------------------------------------------------- SẢN PHẨM */

$code = 'CRUDTEST-'.$suffix;
$name = 'CRUDTEST-'.$suffix.' San pham kiem thu';

$form = request($base.'index.php?com=product&act=add&type=san-pham', $cookie);
ok($form['status'] === 200, 'Mở form thêm sản phẩm');
$fields = formFields($form['body']);
ok(!empty($fields['admin_csrf']), 'Form thêm sản phẩm có CSRF token');

$fields['data']['code'] = $code;
$fields['data']['namevi'] = $name;
$fields['data']['slugvi'] = strtolower($code);
$fields['data']['regular_price'] = 123000;
$fields['status'] = array('hienthi');

$save = request($base.'index.php?com=product&act=save&type=san-pham', $cookie, $fields);
ok(in_array($save['status'], array(200, 302), true), 'Gửi form thêm sản phẩm');

$row = $d->rawQueryOne('select id, namevi, regular_price from #_product where code = ? limit 0,1', array($code));
ok(!empty($row['id']), 'Sản phẩm mới xuất hiện trong DB');
if (empty($row['id']))
{
    echo "Không tạo được sản phẩm, dừng phần sản phẩm.", PHP_EOL;
    if (preg_match('/FLASH_TOAST\s*=\s*(\{.*?\});/s', $save['body'], $toast))
        echo "  Thông báo từ Admin: ", preg_replace('/\s+/', ' ', $toast[1]), PHP_EOL;
    elseif (preg_match('/Location:\s*(\S+)/i', $save['body'], $loc))
        echo "  Chuyển hướng tới: ", $loc[1], PHP_EOL;
    else echo "  HTTP ", $save['status'], ", body ", strlen($save['body']), " byte", PHP_EOL;
}
else
{
    $productId = (int)$row['id'];
    $createdProducts[] = $productId;
    ok(trim($row['namevi']) === $name, 'Tên sản phẩm lưu đúng như đã gửi');

    /* Sửa: mở form edit, đổi tên, lưu lại. */
    $editForm = request($base.'index.php?com=product&act=edit&type=san-pham&id='.$productId, $cookie);
    ok($editForm['status'] === 200, 'Mở form sửa sản phẩm vừa tạo');
    $editFields = formFields($editForm['body']);
    ok(!empty($editFields['data']['namevi']), 'Form sửa nạp sẵn dữ liệu cũ');

    $newName = $name.' (da sua)';
    $editFields['data']['namevi'] = $newName;
    $editFields['id'] = $productId;
    $editSave = request($base.'index.php?com=product&act=save&type=san-pham&id='.$productId, $cookie, $editFields);
    ok(in_array($editSave['status'], array(200, 302), true), 'Gửi form sửa sản phẩm');

    $after = $d->rawQueryOne('select namevi, id_list, id_cat, id_brand from #_product where id = ? limit 0,1', array($productId));
    ok(trim($after['namevi']) === $newName, 'Tên sản phẩm đã đổi đúng sau khi lưu');

    /* Khoá lại lỗi cũ: form Admin từng ghi đè danh mục về 0 mỗi lần Lưu. */
    $before = $d->rawQueryOne('select id_list, id_cat, id_brand from #_product where id = ? limit 0,1', array($productId));
    ok((int)$after['id_list'] === (int)$before['id_list']
       && (int)$after['id_cat'] === (int)$before['id_cat']
       && (int)$after['id_brand'] === (int)$before['id_brand'],
       'Lưu sản phẩm không tự ghi đè danh mục/thương hiệu');

    /* Xoá qua đúng đường Admin dùng: POST kèm CSRF. */
    $deleteFields = array('com' => 'product', 'act' => 'delete', 'type' => 'san-pham',
                          'id' => $productId, 'admin_csrf' => $editFields['admin_csrf']);
    /* deleteMan() đọc $_GET['id'], nên id phải nằm trên URL chứ không chỉ trong
       body POST — trình duyệt cũng gửi vậy vì form action giữ nguyên query string. */
    $delete = request($base.'index.php?com=product&act=delete&type=san-pham&id='.$productId, $cookie, $deleteFields);
    ok(in_array($delete['status'], array(200, 302), true), 'Gửi yêu cầu xoá sản phẩm');

    $gone = $d->rawQueryOne('select id from #_product where id = ? limit 0,1', array($productId));
    ok(empty($gone), 'Sản phẩm đã bị xoá khỏi DB');
    $orphanSeo = $d->rawQueryOne("select count(*) as n from #_seo where com = 'product' and act = 'man' and id_parent = ?", array($productId));
    ok((int)$orphanSeo['n'] === 0, 'Xoá sản phẩm dọn luôn bản ghi SEO, không để mồ côi');
    if (empty($gone)) $createdProducts = array_diff($createdProducts, array($productId));
}

/* ----------------------------------------------------------------- TIN TỨC */

$newsName = 'CRUDTEST-'.$suffix.' Bai kiem thu';
$newsForm = request($base.'index.php?com=news&act=add&type=tin-tuc', $cookie);
ok($newsForm['status'] === 200, 'Mở form thêm tin tức');
$newsFields = formFields($newsForm['body']);
if (!empty($newsFields['admin_csrf']))
{
    $newsFields['data']['namevi'] = $newsName;
    $newsFields['data']['slugvi'] = 'crudtest-'.strtolower($suffix).'-bai-kiem-thu';
    $newsFields['status'] = array('hienthi');
    $newsSave = request($base.'index.php?com=news&act=save&type=tin-tuc', $cookie, $newsFields);
    ok(in_array($newsSave['status'], array(200, 302), true), 'Gửi form thêm tin tức');

    $newsRow = $d->rawQueryOne('select id from #_news where namevi = ? limit 0,1', array($newsName));
    ok(!empty($newsRow['id']), 'Bài viết mới xuất hiện trong DB');
    if (!empty($newsRow['id']))
    {
        $newsId = (int)$newsRow['id'];
        $createdNews[] = $newsId;
        $newsDelete = request($base.'index.php?com=news&act=delete&type=tin-tuc&id='.$newsId, $cookie,
            array('com' => 'news', 'act' => 'delete', 'type' => 'tin-tuc', 'id' => $newsId,
                  'admin_csrf' => $newsFields['admin_csrf']));
        ok(in_array($newsDelete['status'], array(200, 302), true), 'Gửi yêu cầu xoá bài viết');
        $newsGone = $d->rawQueryOne('select id from #_news where id = ? limit 0,1', array($newsId));
        ok(empty($newsGone), 'Bài viết đã bị xoá khỏi DB');
        if (empty($newsGone)) $createdNews = array_diff($createdNews, array($newsId));
    }
}
else ok(false, 'Form thêm tin tức có CSRF token');

echo PHP_EOL, 'Kết quả: ', $passes, ' pass, ', $failures, ' fail', PHP_EOL;
exit($failures ? 1 : 0);
