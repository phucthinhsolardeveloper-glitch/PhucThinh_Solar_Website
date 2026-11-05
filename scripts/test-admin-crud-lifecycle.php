<?php
/**
 * Kiểm thử luồng ghi của Admin qua HTTP thật: thêm -> sửa -> xoá.
 *
 * test-admin-screen-sweep.php cố tình chỉ gọi màn hình đọc, nên toàn bộ đường
 * ghi — nơi dữ liệu thật sự bị thay đổi — không được bộ nào chạy qua. Script
 * này bù vào chỗ đó: mở đúng form Add/Edit của Admin, lấy field và CSRF từ
 * chính form, submit như trình duyệt, rồi kiểm tra DB, public PHP API và Nuxt
 * adapter sau khi đã làm ấm cache.
 *
 * Chỉ đụng vào bản ghi do chính nó tạo, tên và mã đều mang tiền tố CRUDTEST-
 * kèm chuỗi ngẫu nhiên, và dọn sạch ở cuối kể cả khi giữa chừng lỗi.
 *
 * Chạy với local stack: docker exec -i pts-php81-local php /dev/stdin < scripts/test-admin-crud-lifecycle.php
 * Có thể đặt PTS_PHP_PUBLIC_BASE_URL và PTS_NUXT_BASE_URL để đổi endpoint test.
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
$phpPublicBase = rtrim((string)(getenv('PTS_PHP_PUBLIC_BASE_URL') ?: 'http://127.0.0.1'), '/');
$nuxtBase = rtrim((string)(getenv('PTS_NUXT_BASE_URL') ?: 'http://pts-nuxt-service-rehearsal:3000'), '/');
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

function publicJson($base, $path)
{
    $response = request(rtrim($base, '/').$path);
    $payload = json_decode($response['body'], true);
    return array('status' => $response['status'], 'payload' => is_array($payload) ? $payload : array());
}

function findPayloadItem(array $payload, $collection, $key, $value)
{
    foreach(isset($payload[$collection]) && is_array($payload[$collection]) ? $payload[$collection] : array() as $item)
    {
        if(is_array($item) && isset($item[$key]) && (string)$item[$key] === (string)$value) return $item;
    }
    return null;
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
$searchPath = '/api/pts_v1_search.php?keyword='.rawurlencode($code);
$nuxtSearchPath = '/_migration/search?keyword='.rawurlencode($code);
/* Prime both public paths before the Admin write so this also verifies cache invalidation. */
$productPrimePhp = publicJson($phpPublicBase, $searchPath);
$productPrimeNuxt = publicJson($nuxtBase, $nuxtSearchPath);
ok($productPrimePhp['status'] === 200 && $productPrimeNuxt['status'] === 200,
   'Prime PHP và Nuxt product search trước CRUD');

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

    $phpCreated = publicJson($phpPublicBase, $searchPath);
    $nuxtCreated = publicJson($nuxtBase, $nuxtSearchPath);
    ok($phpCreated['status'] === 200 && findPayloadItem($phpCreated['payload'], 'products', 'name', $name) !== null,
       'Product search API PHP đọc được sản phẩm vừa tạo');
    ok($nuxtCreated['status'] === 200 && findPayloadItem($nuxtCreated['payload'], 'products', 'name', $name) !== null,
       'Nuxt search đọc được sản phẩm vừa tạo qua PHP adapter');

    /* Sửa: mở form edit, đổi tên, lưu lại. */
    $editForm = request($base.'index.php?com=product&act=edit&type=san-pham&id='.$productId, $cookie);
    ok($editForm['status'] === 200, 'Mở form sửa sản phẩm vừa tạo');
    $editFields = formFields($editForm['body']);
    ok(!empty($editFields['data']['namevi']), 'Form sửa nạp sẵn dữ liệu cũ');

    $before = $d->rawQueryOne('select id_list, id_cat, id_brand from #_product where id = ? limit 0,1', array($productId));

    $newName = $name.' (da sua)';
    $editFields['data']['namevi'] = $newName;
    $editFields['id'] = $productId;
    $editSave = request($base.'index.php?com=product&act=save&type=san-pham&id='.$productId, $cookie, $editFields);
    ok(in_array($editSave['status'], array(200, 302), true), 'Gửi form sửa sản phẩm');

    $after = $d->rawQueryOne('select namevi, id_list, id_cat, id_brand from #_product where id = ? limit 0,1', array($productId));
    ok(trim($after['namevi']) === $newName, 'Tên sản phẩm đã đổi đúng sau khi lưu');

    $phpUpdated = publicJson($phpPublicBase, $searchPath);
    $nuxtUpdated = publicJson($nuxtBase, $nuxtSearchPath);
    ok($phpUpdated['status'] === 200 && findPayloadItem($phpUpdated['payload'], 'products', 'name', $newName) !== null,
       'Product search API PHP phản ánh nội dung vừa sửa');
    ok($nuxtUpdated['status'] === 200 && findPayloadItem($nuxtUpdated['payload'], 'products', 'name', $newName) !== null,
       'Nuxt search phản ánh nội dung sản phẩm vừa sửa');

    /* Khoá lại lỗi cũ: form Admin từng ghi đè danh mục về 0 mỗi lần Lưu. */
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
    $phpDeleted = publicJson($phpPublicBase, $searchPath);
    $nuxtDeleted = publicJson($nuxtBase, $nuxtSearchPath);
    ok($phpDeleted['status'] === 200 && findPayloadItem($phpDeleted['payload'], 'products', 'slug', strtolower($code)) === null,
       'Product search API PHP ngừng trả sản phẩm đã xóa');
    ok($nuxtDeleted['status'] === 200 && findPayloadItem($nuxtDeleted['payload'], 'products', 'slug', strtolower($code)) === null,
       'Nuxt search ngừng trả sản phẩm đã xóa');
    $orphanSeo = $d->rawQueryOne("select count(*) as n from #_seo where com = 'product' and act = 'man' and id_parent = ?", array($productId));
    ok((int)$orphanSeo['n'] === 0, 'Xoá sản phẩm dọn luôn bản ghi SEO, không để mồ côi');
    if (empty($gone)) $createdProducts = array_diff($createdProducts, array($productId));
}

/* ----------------------------------------------------------------- TIN TỨC */

$newsName = 'CRUDTEST-'.$suffix.' Bai kiem thu';
$newsSlug = 'crudtest-'.strtolower($suffix).'-bai-kiem-thu';
$newsQuery = 'CRUDTEST-'.$suffix;
$newsPhpPath = '/api/pts_v1_news.php?q='.rawurlencode($newsQuery).'&p=1';
$newsNuxtPath = '/_migration/news?q='.rawurlencode($newsQuery).'&p=1';
/* News DTO caches list rows for up to two hours; warm it before Admin CRUD. */
$newsPrimePhp = publicJson($phpPublicBase, $newsPhpPath);
$newsPrimeNuxt = publicJson($nuxtBase, $newsNuxtPath);
ok($newsPrimePhp['status'] === 200 && $newsPrimeNuxt['status'] === 200,
   'Prime PHP và Nuxt news list trước CRUD');
$newsForm = request($base.'index.php?com=news&act=add&type=tin-tuc', $cookie);
ok($newsForm['status'] === 200, 'Mở form thêm tin tức');
$newsFields = formFields($newsForm['body']);
if (!empty($newsFields['admin_csrf']))
{
    $newsFields['data']['namevi'] = $newsName;
    $newsFields['data']['slugvi'] = $newsSlug;
    $newsSeoTitle = $newsName.' SEO title';
    if(!isset($newsFields['dataSeo']) || !is_array($newsFields['dataSeo'])) $newsFields['dataSeo'] = array();
    $newsFields['dataSeo']['titlevi'] = $newsSeoTitle;
    $newsFields['status'] = array('hienthi');
    $newsSave = request($base.'index.php?com=news&act=save&type=tin-tuc', $cookie, $newsFields);
    ok(in_array($newsSave['status'], array(200, 302), true), 'Gửi form thêm tin tức');

    $newsRow = $d->rawQueryOne('select id from #_news where namevi = ? limit 0,1', array($newsName));
    ok(!empty($newsRow['id']), 'Bài viết mới xuất hiện trong DB');
    if (!empty($newsRow['id']))
    {
        $newsId = (int)$newsRow['id'];
        $createdNews[] = $newsId;

        $phpNewsCreated = publicJson($phpPublicBase, $newsPhpPath);
        $nuxtNewsCreated = publicJson($nuxtBase, $newsNuxtPath);
        ok($phpNewsCreated['status'] === 200 && findPayloadItem($phpNewsCreated['payload'], 'articles', 'slug', $newsSlug) !== null,
           'News API PHP phản ánh bài viết vừa tạo sau khi cache đã được prime');
        ok($nuxtNewsCreated['status'] === 200 && findPayloadItem($nuxtNewsCreated['payload'], 'articles', 'slug', $newsSlug) !== null,
           'Nuxt news list phản ánh bài viết vừa tạo qua PHP adapter');

        $newsDetailPhpPath = '/api/pts_v1_news_detail.php?slug='.rawurlencode($newsSlug);
        $newsDetailNuxtPath = '/_migration/news-detail?slug='.rawurlencode($newsSlug);
        $phpNewsDetailCreated = publicJson($phpPublicBase, $newsDetailPhpPath);
        $nuxtNewsDetailCreated = publicJson($nuxtBase, $newsDetailNuxtPath);
        ok($phpNewsDetailCreated['status'] === 200 && ($phpNewsDetailCreated['payload']['article']['seo']['title'] ?? '') === $newsSeoTitle.' | Phúc Thịnh Solar',
           'News detail PHP API đọc SEO title vừa tạo trong Admin');
        ok($nuxtNewsDetailCreated['status'] === 200 && ($nuxtNewsDetailCreated['payload']['article']['seo']['title'] ?? '') === $newsSeoTitle.' | Phúc Thịnh Solar',
           'Nuxt news detail đọc SEO title vừa tạo qua adapter');

        $newsEditForm = request($base.'index.php?com=news&act=edit&type=tin-tuc&id='.$newsId, $cookie);
        ok($newsEditForm['status'] === 200, 'Mở form sửa bài viết vừa tạo');
        $newsEditFields = formFields($newsEditForm['body']);
        $newsEditedName = $newsName.' (da sua)';
        $newsEditedSeoTitle = $newsEditedName.' SEO title';
        $newsEditFields['id'] = $newsId;
        $newsEditFields['data']['namevi'] = $newsEditedName;
        $newsEditFields['dataSeo']['titlevi'] = $newsEditedSeoTitle;
        $newsEditSave = request($base.'index.php?com=news&act=save&type=tin-tuc&id='.$newsId, $cookie, $newsEditFields);
        ok(in_array($newsEditSave['status'], array(200, 302), true), 'Gửi form sửa bài viết');
        $newsAfterEdit = $d->rawQueryOne('select namevi from #_news where id = ? limit 0,1', array($newsId));
        ok(trim((string)($newsAfterEdit['namevi'] ?? '')) === $newsEditedName, 'Tên bài viết đã đổi đúng sau khi lưu');

        $phpNewsUpdated = publicJson($phpPublicBase, $newsPhpPath);
        $nuxtNewsUpdated = publicJson($nuxtBase, $newsNuxtPath);
        $phpUpdatedArticle = findPayloadItem($phpNewsUpdated['payload'], 'articles', 'slug', $newsSlug);
        $nuxtUpdatedArticle = findPayloadItem($nuxtNewsUpdated['payload'], 'articles', 'slug', $newsSlug);
        ok($phpNewsUpdated['status'] === 200 && isset($phpUpdatedArticle['title']) && $phpUpdatedArticle['title'] === $newsEditedName,
           'News API PHP phản ánh tiêu đề vừa sửa');
        ok($nuxtNewsUpdated['status'] === 200 && isset($nuxtUpdatedArticle['title']) && $nuxtUpdatedArticle['title'] === $newsEditedName,
           'Nuxt news list phản ánh tiêu đề vừa sửa');

        $phpNewsDetailUpdated = publicJson($phpPublicBase, $newsDetailPhpPath);
        $nuxtNewsDetailUpdated = publicJson($nuxtBase, $newsDetailNuxtPath);
        ok($phpNewsDetailUpdated['status'] === 200 && ($phpNewsDetailUpdated['payload']['article']['seo']['title'] ?? '') === $newsEditedSeoTitle.' | Phúc Thịnh Solar',
           'News detail PHP API phản ánh SEO title vừa sửa');
        ok($nuxtNewsDetailUpdated['status'] === 200 && ($nuxtNewsDetailUpdated['payload']['article']['seo']['title'] ?? '') === $newsEditedSeoTitle.' | Phúc Thịnh Solar',
           'Nuxt news detail phản ánh SEO title vừa sửa');

        $newsDelete = request($base.'index.php?com=news&act=delete&type=tin-tuc&id='.$newsId, $cookie,
            array('com' => 'news', 'act' => 'delete', 'type' => 'tin-tuc', 'id' => $newsId,
                  'admin_csrf' => $newsFields['admin_csrf']));
        ok(in_array($newsDelete['status'], array(200, 302), true), 'Gửi yêu cầu xoá bài viết');
        $newsGone = $d->rawQueryOne('select id from #_news where id = ? limit 0,1', array($newsId));
        ok(empty($newsGone), 'Bài viết đã bị xoá khỏi DB');
        $phpNewsDeleted = publicJson($phpPublicBase, $newsPhpPath);
        $nuxtNewsDeleted = publicJson($nuxtBase, $newsNuxtPath);
        ok($phpNewsDeleted['status'] === 200 && findPayloadItem($phpNewsDeleted['payload'], 'articles', 'slug', $newsSlug) === null,
           'News API PHP ngừng trả bài viết đã xóa');
        ok($nuxtNewsDeleted['status'] === 200 && findPayloadItem($nuxtNewsDeleted['payload'], 'articles', 'slug', $newsSlug) === null,
           'Nuxt news list ngừng trả bài viết đã xóa');
        if (empty($newsGone)) $createdNews = array_diff($createdNews, array($newsId));
    }
}
else ok(false, 'Form thêm tin tức có CSRF token');

echo PHP_EOL, 'Kết quả: ', $passes, ' pass, ', $failures, ' fail', PHP_EOL;
exit($failures ? 1 : 0);
