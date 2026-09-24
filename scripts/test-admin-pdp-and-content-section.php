<?php
/**
 * Kiểm thử luồng ghi Admin thật cho đợt PDP profile + tài liệu sản phẩm +
 * content_section (FAQ/toggle/restore) — bù chỗ trống mà
 * test-admin-screen-sweep.php (chỉ đọc) và test-admin-crud-lifecycle.php
 * (chưa đụng các trường mới) không phủ.
 *
 * Dữ liệu mang tiền tố ADMPDPTEST- và được dọn ở cuối kể cả khi giữa chừng lỗi.
 *
 * Chạy: php scripts/test-admin-pdp-and-content-section.php
 */

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
require_once LIBRARIES.'product_pdp_profiles.php';
require_once LIBRARIES.'product_content_blocks.php';
require_once LIBRARIES.'admin_security.php';
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

function request($url, $cookie = '', $post = null, $enforceRbac = false)
{
    $header = "User-Agent: PTS-Admin-PDP-ContentSection-Test\r\n";
    if ($enforceRbac) $header .= "X-PTS-Permission-Test: enforce\r\n";
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
    $status = 0; $newCookie = ''; $location = '';
    foreach ((array)$http_response_header as $line)
    {
        if (preg_match('#^HTTP/\S+\s+(\d+)#i', $line, $m)) $status = (int)$m[1];
        if (stripos($line, 'Set-Cookie:') === 0 && preg_match('/(PHPSESSID=[^;]+)/', $line, $m)) $newCookie = $m[1];
        if (stripos($line, 'Location:') === 0) $location = trim(substr($line, strlen('Location:')));
    }
    return array('status' => $status, 'body' => (string)$body, 'cookie' => $newCookie, 'location' => $location);
}

function csrfFromBody($body)
{
    return preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $body, $m) ? $m[1] : '';
}

/* Đọc mọi field mặc định của form (giống test-admin-crud-lifecycle.php) rồi
   ghi đè đúng field cần kiểm thử - form Add/Edit sản phẩm có nhiều field bắt
   buộc khác (loại, id_list...) mà gửi thiếu sẽ bị chặn lưu ở tầng khác, không
   liên quan tới điều đang kiểm thử. */
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

$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$userId = 0;
$createdProductId = 0;
$contentSectionScopeId = 0;
$username = 'admpdptest_'.$suffix;
$password = 'AdmPdpTest!'.$suffix;

register_shutdown_function(function () use (&$userId, &$createdProductId, &$contentSectionScopeId, $d) {
    if ($createdProductId) $d->rawQuery('delete from #_product_document where id_product = ?', array($createdProductId));
    if ($createdProductId) $d->rawQuery('delete from #_product where id = ?', array($createdProductId));
    if ($contentSectionScopeId)
    {
        $d->rawQuery('delete from #_content_section_backup where scope = ? and scope_id = ?', array('list', $contentSectionScopeId));
        $d->rawQuery('delete from #_content_section where scope = ? and scope_id = ?', array('list', $contentSectionScopeId));
    }
    if ($userId) $d->rawQuery('delete from #_user where id = ?', array($userId));
});

/* ---------------------------------------------------------------- ĐĂNG NHẬP */

$d->insert('user', array(
    'id_permission' => 0, 'username' => $username,
    'password' => $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
    'fullname' => 'Admin PDP/ContentSection test', 'status' => 'hienthi', 'role' => 3,
    'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
));
$userId = (int)$d->getLastInsertId();

$page = request($base.'index.php?com=user&act=login');
$cookie = $page['cookie'];
$page = request($base.'index.php?com=user&act=login', $cookie);
$csrf = csrfFromBody($page['body']);
ok($csrf !== '', 'Lấy được CSRF đăng nhập');
$login = request($base.'api/login.php', $cookie, array('username' => $username, 'password' => $password, 'admin_csrf' => $csrf));
if ($login['cookie'] !== '') $cookie = $login['cookie'];
$payload = json_decode($login['body'], true);
ok(!empty($payload['success']), 'Đăng nhập tài khoản kiểm thử');
if (empty($payload['success'])) exit(1);

/* ------------------------------------------ SẢN PHẨM: pdp_profile + tài liệu */

echo PHP_EOL, '== Sản phẩm: pdp_profile + tài liệu ==', PHP_EOL;

$addForm = request($base.'index.php?com=product&act=add&type=san-pham', $cookie);
ok($addForm['status'] === 200, 'Mở form thêm sản phẩm');
$addFields = formFields($addForm['body']);
ok(!empty($addFields['admin_csrf']), 'Form thêm sản phẩm có admin_csrf');

$code = 'ADMPDPTEST-'.$suffix;
$name = 'ADMPDPTEST-'.$suffix.' San pham kiem thu PDP';
$validProfile = 'inverter';

$addFields['data']['code'] = $code;
$addFields['data']['namevi'] = $name;
$addFields['data']['slugvi'] = strtolower($code);
$addFields['data']['regular_price'] = 123000;
$addFields['data']['pdp_profile'] = $validProfile;
$addFields['status'] = array('hienthi');

$save = request($base.'index.php?com=product&act=save&type=san-pham', $cookie, $addFields);
ok(in_array($save['status'], array(200, 302), true), 'Gửi form lưu sản phẩm (pdp_profile)');

$row = $d->rawQueryOne('select id, pdp_profile from #_product where code = ? limit 0,1', array($code));
ok(!empty($row['id']), 'Sản phẩm mới xuất hiện trong DB');
if (!empty($row['id']))
{
    $createdProductId = (int)$row['id'];
    ok($row['pdp_profile'] === $validProfile, 'pdp_profile lưu đúng giá trị hợp lệ đã chọn ("'.$validProfile.'")');

    /* --------------------------------- Tài liệu sản phẩm: danh sách CRUD riêng */

    echo PHP_EOL, '== Tài liệu sản phẩm (bảng quản lý riêng) ==', PHP_EOL;

    $addDocForm = request($base.'index.php?com=product&act=add_document&type=san-pham&id_parent='.$createdProductId, $cookie);
    ok($addDocForm['status'] === 200, 'Mở form thêm tài liệu');
    $addDocCsrf = csrfFromBody($addDocForm['body']);
    ok($addDocCsrf !== '', 'Form thêm tài liệu có admin_csrf');

    /* Dòng tài liệu sai định dạng phải bị chặn, không tạo bản ghi. */
    $badDocSave = request($base.'index.php?com=product&act=save_document&type=san-pham&id_parent='.$createdProductId, $cookie, array(
        'admin_csrf' => $addDocCsrf, 'id' => '',
        'data' => array('document_type' => 'khongtontai', 'titlevi' => 'Thieu duong dan hop le', 'titleen' => '', 'file_path' => '', 'numb' => 1),
    ));
    ok(in_array($badDocSave['status'], array(200, 302), true), 'Gửi tài liệu sai định dạng');
    $badDocCount = $d->rawQueryOne('select count(*) num from #_product_document where id_product = ?', array($createdProductId));
    ok((int)$badDocCount['num'] === 0, 'Tài liệu sai định dạng không tạo bản ghi nào');
    $badDocErrorText = '';
    if (preg_match('/FLASH_TOAST\s*=\s*(\{.*?\});/s', $badDocSave['body'], $badDocToast)) $badDocErrorText = $badDocToast[1];
    elseif ($badDocSave['location'] !== '')
    {
        $followUrl = preg_match('#^https?://#i', $badDocSave['location']) ? $badDocSave['location'] : $base.ltrim($badDocSave['location'], '/');
        $followed = request($followUrl, $cookie);
        preg_match_all('/<p class="mb-1">(.*?)<\/p>/s', $followed['body'], $msgLines);
        $badDocErrorText = implode(' ', array_map('strip_tags', $msgLines[1]));
    }
    ok(mb_stripos($badDocErrorText, 'Loại tài liệu không hợp lệ') !== false, 'Báo lỗi rõ cho tài liệu sai định dạng');

    /* Tài liệu hợp lệ phải tạo được đúng 1 bản ghi. */
    $goodDocPath = 'upload/product/adm-pdp-test-'.$suffix.'.pdf';
    $goodDocSave = request($base.'index.php?com=product&act=save_document&type=san-pham&id_parent='.$createdProductId, $cookie, array(
        'admin_csrf' => $addDocCsrf, 'id' => '',
        'data' => array('document_type' => 'datasheet', 'titlevi' => 'Datasheet thu', 'titleen' => 'Datasheet', 'file_path' => $goodDocPath, 'numb' => 1),
        'status' => array('hienthi' => 'hienthi'),
    ));
    ok(in_array($goodDocSave['status'], array(200, 302), true), 'Gửi tài liệu hợp lệ');

    $docs = $d->rawQuery('select id, document_type, titlevi, file_path, status from #_product_document where id_product = ? order by numb, id', array($createdProductId));
    ok(count($docs) === 1, 'Tài liệu hợp lệ được lưu đúng 1 bản ghi (có '.count($docs).' bản ghi)');
    if (count($docs) === 1)
    {
        ok($docs[0]['document_type'] === 'datasheet', 'Loại tài liệu lưu đúng "datasheet"');
        ok($docs[0]['file_path'] === $goodDocPath, 'Đường dẫn tài liệu lưu đúng');
        ok(strpos((string)$docs[0]['status'], 'hienthi') !== false, 'Tài liệu mới ở trạng thái hiển thị');

        /* Sửa 1 tài liệu đã có — cập nhật đúng dòng, không tạo thêm bản ghi. */
        $docId = (int)$docs[0]['id'];
        $editDocForm = request($base.'index.php?com=product&act=edit_document&type=san-pham&id_parent='.$createdProductId.'&id='.$docId, $cookie);
        ok($editDocForm['status'] === 200, 'Mở form sửa tài liệu');
        $editDocCsrf = csrfFromBody($editDocForm['body']);
        $editDocSave = request($base.'index.php?com=product&act=save_document&type=san-pham&id_parent='.$createdProductId, $cookie, array(
            'admin_csrf' => $editDocCsrf, 'id' => $docId,
            'data' => array('document_type' => 'datasheet', 'titlevi' => 'Datasheet thu ban cap nhat', 'titleen' => 'Datasheet updated', 'file_path' => $goodDocPath, 'numb' => 2),
            'status' => array('hienthi' => 'hienthi'),
        ));
        ok(in_array($editDocSave['status'], array(200, 302), true), 'Gửi sửa tài liệu');
        $docsAfterEdit = $d->rawQuery('select id, titlevi, numb from #_product_document where id_product = ?', array($createdProductId));
        ok(count($docsAfterEdit) === 1, 'Sửa tài liệu không tạo thêm bản ghi (vẫn 1 bản ghi)');
        ok(!empty($docsAfterEdit) && $docsAfterEdit[0]['titlevi'] === 'Datasheet thu ban cap nhat', 'Sửa tài liệu cập nhật đúng tiêu đề');

        /* Xoá — soft-delete, danh sách quản lý vẫn thấy để có thể phục hồi.
           apps.js gửi thao tác xoá bằng POST kèm CSRF (submitAdminMutation),
           không phải GET thuần trên href hiển thị. */
        $deleteDocRes = request($base.'index.php?com=product&act=delete_document&type=san-pham&id_parent='.$createdProductId.'&id='.$docId, $cookie, array('admin_csrf' => $editDocCsrf));
        ok(in_array($deleteDocRes['status'], array(200, 302), true), 'Gửi xoá tài liệu');
        $docAfterDelete = $d->rawQueryOne('select status from #_product_document where id = ? limit 0,1', array($docId));
        ok(!empty($docAfterDelete) && strpos((string)$docAfterDelete['status'], 'an') !== false, 'Tài liệu chuyển sang trạng thái ẩn sau khi xoá (soft-delete, còn trong DB)');
    }

    /* --------------------------- Phân quyền theo loại tài liệu: HTTP thật */

    echo PHP_EOL, '== Phân quyền theo loại tài liệu (HTTP thật, ép enforcement) ==', PHP_EOL;

    ok(adminMutationPermissionKey('product_document', 'manage', 'datasheet', 'product') === 'product_document_manage_datasheet',
        'adminMutationPermissionKey() map đúng khoá quyền theo loại tài liệu');
    ok(adminMutationPermissionKey('product_document', 'manage', 'khong-ton-tai', 'product') === null,
        'adminMutationPermissionKey() từ chối loại tài liệu không tồn tại');
    ok(adminLegacyScreenPermission('product', 'save_document', '', 'san-pham', false) === 'product_edit_san-pham',
        'Cổng màn hình quy act=save_document về đúng quyền sửa sản phẩm (danh mục)');

    /* Container local mặc định bật debug-developer nên bỏ qua mọi kiểm tra
       quyền; test-admin-permission-gate.php đã có sẵn cơ chế header
       "X-PTS-Permission-Test: enforce" để config-type.php tắt debug-developer
       CHỈ cho request đó (chỉ nhận khi APP_ENV=local) — dùng lại đúng cơ chế
       này để kiểm chứng bằng HTTP thật thay vì mô phỏng gọi hàm. */
    /* Dọn tài liệu (kể cả bản đã soft-delete) còn sót từ phần CRUD ở trên,
       tránh đếm nhầm bản ghi cũ khi kiểm tra phần phân quyền dưới đây. */
    $d->rawQuery('delete from #_product_document where id_product = ?', array($createdProductId));

    $gateGroupName = 'DOCGATE-'.$suffix;
    $gateStaffName = 'docgate-staff-'.$suffix;
    $gatePassword = bin2hex(random_bytes(12));
    $d->insert('permission_group', array('name' => $gateGroupName, 'numb' => 0, 'status' => 'hienthi', 'date_created' => time()));
    $gateGroupId = (int)$d->getLastInsertId();
    $d->insert('permission', array('id_permission_group' => $gateGroupId, 'permission' => 'product_edit_san-pham'));
    $d->insert('user', array(
        'id_permission' => $gateGroupId, 'username' => $gateStaffName,
        'password' => $func->encryptPassword($config['website']['secret'], $gatePassword, $config['website']['salt']),
        'fullname' => 'Doc gate test', 'status' => 'hienthi', 'role' => 1,
        'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0
    ));
    $gateStaffId = (int)$d->getLastInsertId();

    $gatePage = request($base.'index.php?com=user&act=login', '', null, true);
    $gateCookie = $gatePage['cookie'];
    $gatePage = request($base.'index.php?com=user&act=login', $gateCookie, null, true);
    $gateCsrf = csrfFromBody($gatePage['body']);
    $gateLogin = request($base.'api/login.php', $gateCookie, array('username' => $gateStaffName, 'password' => $gatePassword, 'admin_csrf' => $gateCsrf), true);
    if ($gateLogin['cookie'] !== '') $gateCookie = $gateLogin['cookie'];
    $gateLoginPayload = json_decode($gateLogin['body'], true);
    ok(!empty($gateLoginPayload['success']), 'Đăng nhập tài khoản staff chỉ có quyền sửa sản phẩm (chưa có quyền tài liệu)');

    if (!empty($gateLoginPayload['success']))
    {
        $gateAddForm = request($base.'index.php?com=product&act=add_document&type=san-pham&id_parent='.$createdProductId, $gateCookie, null, true);
        ok($gateAddForm['status'] === 200, 'Staff có quyền sửa sản phẩm vẫn mở được màn quản lý tài liệu');
        $gateCsrf2 = csrfFromBody($gateAddForm['body']);

        $gateBlockedSave = request($base.'index.php?com=product&act=save_document&type=san-pham&id_parent='.$createdProductId, $gateCookie, array(
            'admin_csrf' => $gateCsrf2, 'id' => '',
            'data' => array('document_type' => 'datasheet', 'titlevi' => 'Datasheet cho staff', 'titleen' => '', 'file_path' => $goodDocPath, 'numb' => 1),
            'status' => array('hienthi' => 'hienthi'),
        ), true);
        ok($gateBlockedSave['status'] === 403, 'Staff KHÔNG có quyền "Quản lý Datasheet" bị chặn 403 khi lưu tài liệu loại datasheet');
        $gateCountAfterBlock = $d->rawQueryOne('select count(*) num from #_product_document where id_product = ?', array($createdProductId));
        ok((int)$gateCountAfterBlock['num'] === 0, 'Bị chặn thì không có bản ghi tài liệu nào được tạo');

        /* Cấp thêm đúng quyền loại tài liệu rồi thử lại — không cần đăng nhập
           lại vì requick.php nạp permissions từ DB ở mỗi request. */
        $d->insert('permission', array('id_permission_group' => $gateGroupId, 'permission' => 'product_document_manage_datasheet'));
        $gateAllowedSave = request($base.'index.php?com=product&act=save_document&type=san-pham&id_parent='.$createdProductId, $gateCookie, array(
            'admin_csrf' => $gateCsrf2, 'id' => '',
            'data' => array('document_type' => 'datasheet', 'titlevi' => 'Datasheet cho staff', 'titleen' => '', 'file_path' => $goodDocPath, 'numb' => 1),
            'status' => array('hienthi' => 'hienthi'),
        ), true);
        ok(in_array($gateAllowedSave['status'], array(200, 302), true), 'Cấp thêm quyền "Quản lý Datasheet" -> staff lưu được tài liệu loại datasheet');
        $gateCountAfterAllow = $d->rawQueryOne('select count(*) num from #_product_document where id_product = ?', array($createdProductId));
        ok((int)$gateCountAfterAllow['num'] === 1, 'Sau khi được cấp quyền, tài liệu được ghi vào DB thật');
    }

    $d->rawQuery('delete from #_product_document where id_product = ?', array($createdProductId));
    $d->rawQuery('delete from #_user_log where id_user = ?', array($gateStaffId));
    $d->rawQuery('delete from #_user where id = ?', array($gateStaffId));
    $d->rawQuery('delete from #_permission where id_permission_group = ?', array($gateGroupId));
    $d->rawQuery('delete from #_permission_group where id = ?', array($gateGroupId));

    /* Lưu pdp_profile rỗng phải quay về NULL (kế thừa), không phải chuỗi rỗng. */
    $editForm = request($base.'index.php?com=product&act=edit&type=san-pham&id='.$createdProductId, $cookie);
    $editFields = formFields($editForm['body']);
    $editFields['id'] = $createdProductId;
    $editFields['data']['pdp_profile'] = '';
    $editSave = request($base.'index.php?com=product&act=save&type=san-pham&id='.$createdProductId, $cookie, $editFields);
    ok(in_array($editSave['status'], array(200, 302), true), 'Gửi form xoá pdp_profile (về kế thừa)');
    $rowAfter = $d->rawQueryOne('select pdp_profile from #_product where id = ? limit 0,1', array($createdProductId));
    ok($rowAfter['pdp_profile'] === null, 'pdp_profile rỗng lưu thành NULL (kế thừa danh mục), không phải chuỗi rỗng');

    /* Profile không hợp lệ phải bị chặn, không ghi đè giá trị đang có. */
    $editForm2 = request($base.'index.php?com=product&act=edit&type=san-pham&id='.$createdProductId, $cookie);
    $editFields2 = formFields($editForm2['body']);
    $editFields2['id'] = $createdProductId;
    $editFields2['data']['pdp_profile'] = 'khong-ton-tai';
    $badSave = request($base.'index.php?com=product&act=save&type=san-pham&id='.$createdProductId, $cookie, $editFields2);
    $badErrorText = '';
    if (preg_match('/FLASH_TOAST\s*=\s*(\{.*?\});/s', $badSave['body'], $badToast)) $badErrorText = $badToast[1];
    elseif ($badSave['location'] !== '')
    {
        $followUrl = preg_match('#^https?://#i', $badSave['location']) ? $badSave['location'] : $base.ltrim($badSave['location'], '/');
        $followedBad = request($followUrl, $cookie);
        preg_match_all('/<p class="mb-1">(.*?)<\/p>/s', $followedBad['body'], $badMsgLines);
        $badErrorText = implode(' ', array_map('strip_tags', $badMsgLines[1]));
    }
    ok(mb_stripos($badErrorText, 'Profile trang chi tiết không hợp lệ') !== false, 'Profile không hợp lệ bị chặn kèm thông báo lỗi');
    $rowAfterBad = $d->rawQueryOne('select pdp_profile from #_product where id = ? limit 0,1', array($createdProductId));
    ok($rowAfterBad['pdp_profile'] === null, 'Profile không hợp lệ không ghi đè giá trị đang lưu (vẫn NULL/kế thừa)');
}
else
{
    echo "Không tạo được sản phẩm, dừng phần sản phẩm.", PHP_EOL;
    if (preg_match('/FLASH_TOAST\s*=\s*(\{.*?\});/s', $save['body'], $toast))
        echo "  Thông báo từ Admin: ", preg_replace('/\s+/', ' ', $toast[1]), PHP_EOL;
    elseif ($save['location'] !== '')
    {
        echo "  Chuyển hướng tới: ", $save['location'], PHP_EOL;
        $followUrl = preg_match('#^https?://#i', $save['location']) ? $save['location'] : $base.ltrim($save['location'], '/');
        $followed = request($followUrl, $cookie);
        if (preg_match('/FLASH_TOAST\s*=\s*(\{.*?\});/s', $followed['body'], $toast2))
            echo "  Thông báo từ Admin (sau redirect): ", preg_replace('/\s+/', ' ', $toast2[1]), PHP_EOL;
        elseif (preg_match_all('/<p class="mb-1">(.*?)<\/p>/s', $followed['body'], $msgLines) && !empty($msgLines[1]))
        {
            echo "  Thông báo lỗi từ Admin (card danger):", PHP_EOL;
            foreach ($msgLines[1] as $msgLine) echo "    ", trim(strip_tags($msgLine)), PHP_EOL;
        }
        elseif (preg_match('/alert[^>]*>([^<]{5,200})/i', $followed['body'], $alertMsg))
            echo "  Alert tìm thấy: ", trim($alertMsg[1]), PHP_EOL;
        else
            echo "  Không tìm thấy thông báo lỗi cụ thể; 400 ký tự đầu body redirect: ", substr(strip_tags($followed['body']), 0, 400), PHP_EOL;
    }
    else echo "  HTTP ", $save['status'], ", body ", strlen($save['body']), " byte", PHP_EOL;
}

/* --------------------------------- content_section: FAQ + toggle + restore */

echo PHP_EOL, '== Khối nội dung mô tả: FAQ + bật/ẩn + phục hồi (danh mục cấp 1) ==', PHP_EOL;

$list = $d->rawQueryOne("select id from #_product_list where type = 'san-pham' and find_in_set('hienthi',status) order by id desc limit 0,1");
ok(!empty($list['id']), 'Có danh mục cấp 1 để kiểm thử khối nội dung');
if (!empty($list['id']))
{
    $contentSectionScopeId = (int)$list['id'];
    $editUrl = $base.'index.php?com=content_section&act=edit&type=san-pham&scope=list&scope_id='.$contentSectionScopeId;
    $editPage = request($editUrl, $cookie);
    ok($editPage['status'] === 200, 'Mở composer khối nội dung cho danh mục');
    $csEditCsrf = csrfFromBody($editPage['body']);
    ok($csEditCsrf !== '', 'Composer có admin_csrf');

    $blocks = array(
        array('type' => 'heading', 'text' => 'ADMPDPTEST-'.$suffix.' Cau hoi thuong gap'),
        array('type' => 'faq', 'question' => 'Cau hoi kiem thu '.$suffix.'?', 'answer' => 'Cau tra loi kiem thu '.$suffix.'.'),
    );
    $saveCs = request($base.'index.php?com=content_section&act=save&type=san-pham', $cookie, array(
        'admin_csrf' => $csEditCsrf,
        'scope' => 'list',
        'scope_id' => $contentSectionScopeId,
        'blocks_json' => json_encode($blocks, JSON_UNESCAPED_UNICODE),
    ));
    ok(in_array($saveCs['status'], array(200, 302), true), 'Gửi lưu khối (heading + FAQ)');

    $csRow = $d->rawQueryOne('select id, blocksvi, status from #_content_section where scope = ? and scope_id = ? limit 0,1', array('list', $contentSectionScopeId));
    ok(!empty($csRow['id']), 'Bản ghi khối nội dung xuất hiện trong DB');
    if (!empty($csRow['id']))
    {
        $decodedBlocks = productSectionsDecode($csRow['blocksvi']);
        $faqBlock = null;
        foreach ($decodedBlocks as $b) if (isset($b['type']) && $b['type'] === 'faq') { $faqBlock = $b; break; }
        ok($faqBlock !== null, 'Khối FAQ được lưu đúng loại "faq"');
        if ($faqBlock !== null)
        {
            ok(strpos($faqBlock['question'], $suffix) !== false, 'Câu hỏi FAQ lưu đúng nội dung');
            ok(strpos($faqBlock['answer'], $suffix) !== false, 'Câu trả lời FAQ lưu đúng nội dung');
        }
        ok(strpos((string)$csRow['status'], 'hienthi') === 0 || strpos((string)$csRow['status'], 'hienthi') !== false, 'Khối mới lưu ở trạng thái hiển thị');

        /* Render thật ở trang danh mục phải thấy FAQ. */
        $listSlug = $d->rawQueryOne('select slugvi from #_product_list where id = ? limit 0,1', array($contentSectionScopeId));
        if (!empty($listSlug['slugvi']))
        {
            $publicPage = request('http://127.0.0.1/danh-muc-san-pham/'.$listSlug['slugvi'].'/');
            ok(strpos($publicPage['body'], 'Cau hoi kiem thu '.$suffix) !== false, 'FAQ vừa lưu hiển thị thật trên trang danh mục công khai');
        }

        /* Bật/ẩn */
        $hideRes = request($base.'index.php?com=content_section&act=toggle&type=san-pham', $cookie, array(
            'admin_csrf' => $csEditCsrf, 'scope' => 'list', 'scope_id' => $contentSectionScopeId, 'visible' => '0',
        ));
        ok(in_array($hideRes['status'], array(200, 302), true), 'Gửi thao tác ẩn khối nội dung');
        $afterHide = $d->rawQueryOne('select status from #_content_section where id = ? limit 0,1', array($csRow['id']));
        ok(strpos((string)$afterHide['status'], 'an') !== false, 'Trạng thái đổi thành ẩn sau khi bấm ẩn');

        $showRes = request($base.'index.php?com=content_section&act=toggle&type=san-pham', $cookie, array(
            'admin_csrf' => $csEditCsrf, 'scope' => 'list', 'scope_id' => $contentSectionScopeId, 'visible' => '1',
        ));
        ok(in_array($showRes['status'], array(200, 302), true), 'Gửi thao tác bật lại khối nội dung');
        $afterShow = $d->rawQueryOne('select status from #_content_section where id = ? limit 0,1', array($csRow['id']));
        ok(strpos((string)$afterShow['status'], 'hienthi') !== false, 'Trạng thái đổi lại thành hiển thị');

        /* Xoá -> có snapshot -> phục hồi -> khối FAQ quay lại như cũ. */
        $delRes = request($base.'index.php?com=content_section&act=delete&type=san-pham', $cookie, array(
            'admin_csrf' => $csEditCsrf, 'scope' => 'list', 'scope_id' => $contentSectionScopeId,
        ));
        ok(in_array($delRes['status'], array(200, 302), true), 'Gửi thao tác xoá khối nội dung');
        $afterDelete = $d->rawQueryOne('select id from #_content_section where scope = ? and scope_id = ? limit 0,1', array('list', $contentSectionScopeId));
        ok(empty($afterDelete), 'Bản ghi khối nội dung không còn sau khi xoá');
        $backupRow = $d->rawQueryOne('select id from #_content_section_backup where scope = ? and scope_id = ? order by id desc limit 0,1', array('list', $contentSectionScopeId));
        ok(!empty($backupRow['id']), 'Snapshot được tạo trong content_section_backup trước khi xoá');

        if (!empty($backupRow['id']))
        {
            $restoreRes = request($base.'index.php?com=content_section&act=restore&type=san-pham', $cookie, array(
                'admin_csrf' => $csEditCsrf, 'scope' => 'list', 'scope_id' => $contentSectionScopeId, 'backup_id' => $backupRow['id'],
            ));
            ok(in_array($restoreRes['status'], array(200, 302), true), 'Gửi thao tác phục hồi từ snapshot');
            $restored = $d->rawQueryOne('select blocksvi from #_content_section where scope = ? and scope_id = ? limit 0,1', array('list', $contentSectionScopeId));
            ok(!empty($restored['blocksvi']), 'Bản ghi khối nội dung xuất hiện lại sau khi phục hồi');
            if (!empty($restored['blocksvi']))
            {
                $restoredBlocks = productSectionsDecode($restored['blocksvi']);
                $restoredFaq = null;
                foreach ($restoredBlocks as $b) if (isset($b['type']) && $b['type'] === 'faq') { $restoredFaq = $b; break; }
                ok($restoredFaq !== null && strpos($restoredFaq['question'], $suffix) !== false, 'Khối FAQ khôi phục đúng nội dung đã xoá');
            }
        }
    }
}
else
{
    echo "Không có danh mục để kiểm thử, dừng phần khối nội dung.", PHP_EOL;
}

echo PHP_EOL, "Kết quả: $passes pass, $failures fail", PHP_EOL;
exit($failures > 0 ? 1 : 0);
