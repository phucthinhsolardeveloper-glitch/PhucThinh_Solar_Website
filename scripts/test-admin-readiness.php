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
require LIBRARIES.'login_security.php';
require LIBRARIES.'session_security.php';

function readinessAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException("FAIL: {$message}");
    echo "PASS: {$message}\n";
}

function readinessHttp($url, $method = 'GET', $data = array(), $cookie = '')
{
    $headers = array('User-Agent: PTS-Local-Readiness-Test');
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
    $setCookies = array();
    foreach($responseHeaders as $header)
    {
        if(preg_match('#^HTTP/\S+\s+(\d+)#i', $header, $match)) $status = (int)$match[1];
        if(stripos($header, 'Set-Cookie:') === 0) $setCookies[] = trim(substr($header, 11));
    }
    return array('status' => $status, 'body' => $body, 'cookies' => $setCookies);
}

function readinessSessionCookie($setCookies)
{
    foreach($setCookies as $cookie)
        if(preg_match('/^(PHPSESSID=[^;]+)/', $cookie, $match)) return array($match[1], $cookie);
    return array('', '');
}

$prefix = 'CODEX-RBAC-'.bin2hex(random_bytes(4));
$createdUsers = array();
$createdGroups = array();
$rateKeys = array();
$createdProductIds = array();
$createdContactIds = array();
$createdNewsletterIds = array();

try
{
    // Dọn fixture từ lượt test bị ngắt cứng trước khi PHP kịp chạy finally.
    foreach($d->rawQuery("select id from #_user where username like ?", array('codex-rbac-%')) as $staleUser)
    {
        $d->rawQuery("delete from #_user_log where id_user = ?", array((int)$staleUser['id']));
        $d->rawQuery("delete from #_user where id = ?", array((int)$staleUser['id']));
    }
    foreach($d->rawQuery("select id from #_permission_group where name like ?", array('CODEX-RBAC-%')) as $staleGroup)
    {
        $d->rawQuery("delete from #_permission where id_permission_group = ?", array((int)$staleGroup['id']));
        $d->rawQuery("delete from #_permission_group where id = ?", array((int)$staleGroup['id']));
    }
    foreach($d->rawQuery("select id from #_product where code like ? or code like ?", array('CODEX-BULK-STATUS-%', 'CODEX-BS-%')) as $staleProduct)
        $d->rawQuery("delete from #_product where id = ?", array((int)$staleProduct['id']));
    $matrix = array(
        'product' => array('product_man_san-pham','product_add_san-pham','product_edit_san-pham','product_delete_san-pham','product_add_photo_san-pham','product_edit_photo_san-pham','product_delete_photo_san-pham','newsletter_man_dangkynhantin','newsletter_edit_dangkynhantin'),
        'order' => array('order_man','order_edit'),
        'content' => array('news_man_tin-tuc','news_add_tin-tuc','news_edit_tin-tuc','news_delete_tin-tuc'),
        'viewer' => array('product_man_san-pham')
    );

    foreach($matrix as $roleName => $permissions)
    {
        $d->insert('permission_group', array('name' => $prefix.'-'.$roleName, 'numb' => 0, 'status' => 'hienthi', 'date_created' => time()));
        $groupId = (int)$d->getLastInsertId();
        $createdGroups[] = $groupId;
        foreach($permissions as $permission) $d->insert('permission', array('id_permission_group' => $groupId, 'permission' => $permission));

        $username = strtolower($prefix.'-'.$roleName);
        $passwordPlain = bin2hex(random_bytes(12));
        $passwordHash = $func->encryptPassword($config['website']['secret'], $passwordPlain, $config['website']['salt']);
        $d->insert('user', array('id_permission' => $groupId, 'username' => $username, 'password' => $passwordHash, 'fullname' => 'Local readiness '.$roleName, 'status' => 'hienthi', 'role' => 1, 'secret_key' => bin2hex(random_bytes(16)), 'numb' => 0));
        $userId = (int)$d->getLastInsertId();
        $createdUsers[$roleName] = array('id' => $userId, 'username' => $username, 'password' => $passwordPlain);

        $loaded = adminLoadPermissions($d, $userId);
        readinessAssert(count(array_diff($permissions, $loaded)) === 0, 'Nạp đúng quyền DB cho vai trò '.$roleName);
    }

    readinessAssert(adminPermissionGranted(true, 1, false, 'product_edit_san-pham', adminLoadPermissions($d, $createdUsers['product']['id'])), 'Nhóm sản phẩm được sửa sản phẩm');
    readinessAssert(!adminPermissionGranted(true, 1, false, 'order_edit', adminLoadPermissions($d, $createdUsers['product']['id'])), 'Nhóm sản phẩm không vượt quyền đơn hàng');
    readinessAssert(adminPermissionGranted(true, 1, false, 'order_edit', adminLoadPermissions($d, $createdUsers['order']['id'])), 'Nhóm đơn hàng được cập nhật đơn');
    readinessAssert(!adminPermissionGranted(true, 1, false, 'product_edit_san-pham', adminLoadPermissions($d, $createdUsers['viewer']['id'])), 'Nhóm chỉ xem không có quyền sửa');

    $fakeServer = array('REMOTE_ADDR' => '198.51.100.10', 'HTTP_X_FORWARDED_FOR' => '203.0.113.20');
    readinessAssert(adminLoginClientIp($fakeServer, array()) === '198.51.100.10', 'Login không tin X-Forwarded-For từ proxy chưa cấu hình');
    readinessAssert(adminLoginClientIp($fakeServer, array('198.51.100.10')) === '203.0.113.20', 'Login chỉ nhận forwarded IP từ trusted proxy');

    $rateDescriptors = adminLoginLimitDescriptors('198.51.100.77', $prefix.'-rate', 2, 20);
    foreach($rateDescriptors as $descriptor) $rateKeys[] = $descriptor['key'];
    adminLoginLimitFailure($d, $rateDescriptors, time());
    readinessAssert(adminLoginLimitState($d, $rateDescriptors, 15, time()) === 0, 'Rate limit chưa khóa trước ngưỡng');
    adminLoginLimitFailure($d, $rateDescriptors, time());
    readinessAssert(adminLoginLimitState($d, $rateDescriptors, 15, time()) > 0, 'Rate limit khóa theo IP khi đạt ngưỡng');
    readinessAssert(adminLoginLimitState($d, $rateDescriptors, 15, time() + 901) === 0, 'Rate limit tự mở và reset bộ đếm khi hết thời gian khóa');
    readinessAssert(adminLoginLimitFailure($d, $rateDescriptors, time() + 901) > 0, 'Lần sai sau khi mở khóa bắt đầu lại từ đầu');
    adminLoginLimitReset($d, $rateDescriptors, time());
    readinessAssert(adminLoginLimitState($d, $rateDescriptors, 15, time()) === 0, 'Đăng nhập thành công reset rate limit');

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/var/www/html/administrator/templates'));
    $postForms = 0;
    $csrfFields = 0;
    foreach($iterator as $file)
    {
        if(!$file->isFile() || substr($file->getFilename(), -4) !== '.php') continue;
        $source = file_get_contents($file->getPathname());
        $postForms += preg_match_all('/<form[^>]*method="post"/i', $source, $unused);
        $csrfFields += substr_count($source, 'adminCsrfField()');
    }
    readinessAssert($postForms >= 42 && $csrfFields === $postForms, 'Mọi form POST Admin đều có CSRF field');

    $productListTemplate = file_get_contents('/var/www/html/administrator/templates/product/man/mans_tpl.php');
    readinessAssert(strpos($productListTemplate, 'totalByID(') === false && strpos($productListTemplate, '$commentTotals') !== false, 'Danh sách sản phẩm dùng thống kê đánh giá theo batch');

    unset($_REQUEST['id_city'], $_REQUEST['id_district']);
    $emptyDistrict = $func->getAjaxPlace('district');
    $emptyWard = $func->getAjaxPlace('ward');
    readinessAssert(strpos($emptyDistrict, 'id="id_district"') !== false && substr_count($emptyDistrict, '<option') === 1, 'Dropdown quận không truy vấn dữ liệu khi chưa chọn tỉnh');
    readinessAssert(strpos($emptyWard, 'id="id_ward"') !== false && substr_count($emptyWard, '<option') === 1, 'Dropdown phường không truy vấn dữ liệu khi chưa chọn quận');

    $base = 'http://127.0.0.1/administrator/';
    $loginPage = readinessHttp($base.'index.php?com=user&act=login');
    list($oldCookie, $loginSetCookie) = readinessSessionCookie($loginPage['cookies']);
    readinessAssert($loginPage['status'] === 200 && $oldCookie !== '', 'Mở trang login và nhận session cookie');
    readinessAssert(stripos($loginSetCookie, 'HttpOnly') !== false && stripos($loginSetCookie, 'SameSite=Lax') !== false, 'Cookie login có HttpOnly và SameSite=Lax');
    readinessAssert(preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $loginPage['body'], $tokenMatch) === 1, 'Trang login phát hành CSRF token');

    $missingCsrf = readinessHttp($base.'api/login.php', 'POST', array('username' => $createdUsers['product']['username'], 'password' => $createdUsers['product']['password']), $oldCookie);
    readinessAssert($missingCsrf['status'] === 403, 'Login thiếu CSRF bị từ chối');

    $login = readinessHttp($base.'api/login.php', 'POST', array('username' => $createdUsers['product']['username'], 'password' => $createdUsers['product']['password'], 'admin_csrf' => $tokenMatch[1]), $oldCookie);
    list($newCookie, $authenticatedSetCookie) = readinessSessionCookie($login['cookies']);
    $loginJson = json_decode($login['body'], true);
    readinessAssert($login['status'] === 200 && !empty($loginJson['success']), 'Đăng nhập HTTP bằng tài khoản RBAC tạm thành công');
    readinessAssert($newCookie !== '' && $newCookie !== $oldCookie, 'Session ID được regenerate sau đăng nhập');
    readinessAssert(stripos($authenticatedSetCookie, 'HttpOnly') !== false && stripos($authenticatedSetCookie, 'SameSite=Lax') !== false, 'Cookie sau đăng nhập giữ thuộc tính bảo mật');

    $authStateAfterLogin = $d->rawQueryOne('select lastlogin, user_token from #_user where id = ?', array($createdUsers['product']['id']));
    $forcedLastLogin = max(1, (int)$authStateAfterLogin['lastlogin'] - 61);
    $d->rawQuery('update #_user set lastlogin = ? where id = ?', array($forcedLastLogin, $createdUsers['product']['id']));

    $adminForm = readinessHttp($base.'index.php?com=product&act=add&type=san-pham', 'GET', array(), $newCookie);
    readinessAssert($adminForm['status'] === 200 && strpos($adminForm['body'], 'name="admin_csrf"') !== false, 'Form sản phẩm authenticated render CSRF field');
    readinessAssert(preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $adminForm['body'], $authenticatedToken) === 1, 'Phiên authenticated phát hành token mới');
    $authStateAfterRefresh = $d->rawQueryOne('select lastlogin, user_token from #_user where id = ?', array($createdUsers['product']['id']));
    readinessAssert(
        $authStateAfterRefresh['user_token'] === $authStateAfterLogin['user_token']
            && (int)$authStateAfterRefresh['lastlogin'] > $forcedLastLogin,
        'Page load đến hạn cập nhật activity nhưng giữ nguyên token phiên'
    );
    $adminList = readinessHttp($base.'index.php?com=product&act=man&type=san-pham', 'GET', array(), $newCookie);
    $authStateAfterPages = $d->rawQueryOne('select lastlogin, user_token from #_user where id = ?', array($createdUsers['product']['id']));
    readinessAssert($adminList['status'] === 200, 'Phiên Admin mở liên tiếp hai trang authenticated');
    readinessAssert(
        $authStateAfterPages['user_token'] === $authStateAfterRefresh['user_token']
            && (int)$authStateAfterPages['lastlogin'] === (int)$authStateAfterRefresh['lastlogin'],
        'Các page load trong throttle không xoay token hoặc ghi lại activity'
    );

    $apiMissing = readinessHttp($base.'api/status.php', 'POST', array('id' => 999999, 'table' => 'product', 'attr' => 'hienthi', 'type' => 'san-pham', 'com' => 'product'), $newCookie);
    readinessAssert($apiMissing['status'] === 403, 'API mutation thiếu CSRF trả 403');
    $apiMissingPayload = json_decode($apiMissing['body'], true);
    readinessAssert(isset($apiMissingPayload['ok'], $apiMissingPayload['code']) && $apiMissingPayload['ok'] === false && $apiMissingPayload['code'] === 'INVALID_CSRF', 'Lỗi CSRF API dùng envelope chuẩn');
    $apiValid = readinessHttp($base.'api/status.php', 'POST', array('admin_csrf' => $authenticatedToken[1], 'id' => 999999, 'table' => 'product', 'attr' => 'hienthi', 'type' => 'san-pham', 'com' => 'product'), $newCookie);
    $apiValidPayload = json_decode($apiValid['body'], true);
    readinessAssert($apiValid['status'] === 200 && is_array($apiValidPayload) && array_key_exists('success', $apiValidPayload), 'API mutation có CSRF hợp lệ và trả JSON rõ ràng');
    readinessAssert(array_key_exists('ok', $apiValidPayload) && isset($apiValidPayload['code'], $apiValidPayload['data']['updated']), 'API status trả envelope chuẩn và giữ payload legacy');
    $apiInvalidStatus = readinessHttp($base.'api/status.php', 'POST', array('admin_csrf' => $authenticatedToken[1], 'ids' => '999998,999999', 'table' => 'product', 'attr' => 'trang-thai-tu-che', 'value' => '1', 'type' => 'san-pham', 'com' => 'product'), $newCookie);
    $apiInvalidPayload = json_decode($apiInvalidStatus['body'], true);
    readinessAssert($apiInvalidStatus['status'] === 200 && empty($apiInvalidPayload['success']), 'API trạng thái hàng loạt từ chối thuộc tính ngoài cấu hình');

    $bulkStatusPrefix = 'CODEX-BS-'.bin2hex(random_bytes(4));
    $bulkStatusCode = $bulkStatusPrefix.'-1';
    $d->insert('product', array('code' => $bulkStatusCode, 'namevi' => 'Sản phẩm kiểm thử trạng thái lọc', 'slugvi' => strtolower($bulkStatusCode), 'type' => 'san-pham', 'status' => 'hienthi', 'photo' => 'codex-fixture.png', 'descvi' => 'Đã có mô tả', 'contentvi' => 'Đã có nội dung', 'light_source' => 'LED', 'numb' => 0, 'cong_suat' => 0, 'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0, 'id_brand' => 0, 'date_created' => time(), 'date_updated' => 0, 'view' => 0));
    $bulkStatusProductId = (int)$d->getLastInsertId();
    $createdProductIds[] = $bulkStatusProductId;
    $secondBulkStatusCode = $bulkStatusPrefix.'-2';
    $d->insert('product', array('code' => $secondBulkStatusCode, 'namevi' => 'Sản phẩm kiểm thử set-based', 'slugvi' => strtolower($secondBulkStatusCode), 'type' => 'san-pham', 'status' => '', 'numb' => 0, 'cong_suat' => 0, 'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0, 'id_brand' => 0, 'date_created' => time(), 'date_updated' => 0, 'view' => 0));
    $secondBulkStatusProductId = (int)$d->getLastInsertId();
    $createdProductIds[] = $secondBulkStatusProductId;
    $missingDescriptionPage = readinessHttp($base.'index.php?com=product&act=man&type=san-pham&data_status=missing_desc&keyword='.urlencode($bulkStatusPrefix), 'GET', array(), $newCookie);
    readinessAssert(
        $missingDescriptionPage['status'] === 200
            && strpos($missingDescriptionPage['body'], 'Sản phẩm kiểm thử set-based') !== false
            && strpos($missingDescriptionPage['body'], 'Sản phẩm kiểm thử trạng thái lọc') === false
            && strpos($missingDescriptionPage['body'], 'Thiếu Mô tả') !== false
            && strpos($missingDescriptionPage['body'], 'Điền ngay') !== false,
        'Bộ lọc điền nhanh chỉ hiện sản phẩm thiếu mô tả và có chỉ báo thao tác'
    );
    $scopedBulkPage = readinessHttp($base.'index.php?com=product&act=bulk&type=san-pham&data_status=missing_desc', 'GET', array(), $newCookie);
    readinessAssert(
        $scopedBulkPage['status'] === 200
            && strpos($scopedBulkPage['body'], 'Phạm vi xuất hiện tại') !== false
            && strpos($scopedBulkPage['body'], 'data_status=missing_desc') !== false,
        'Màn bulk giữ phạm vi export sản phẩm thiếu mô tả'
    );
    readinessAssert(
        strpos($scopedBulkPage['body'], 'Xuất tất cả sản phẩm') !== false
            && strpos($scopedBulkPage['body'], 'Xuất nhóm đang lọc') !== false
            && strpos($scopedBulkPage['body'], 'pts-bulk-prepare-card') !== false,
        'Màn bulk luôn tách rõ xuất tất cả, xuất nhóm lọc và dropdown không bị cắt'
    );
    $apiSelectedStatus = readinessHttp($base.'api/status.php', 'POST', array('admin_csrf' => $authenticatedToken[1], 'ids' => $bulkStatusProductId.','.$secondBulkStatusProductId, 'scope' => 'selected', 'table' => 'product', 'attr' => 'baogia', 'value' => '1', 'type' => 'san-pham', 'com' => 'product'), $newCookie);
    $apiSelectedPayload = json_decode($apiSelectedStatus['body'], true);
    $selectedStatusRows = $d->rawQuery('select id, status from #_product where id in (?, ?)', array($bulkStatusProductId, $secondBulkStatusProductId));
    $selectedStatusApplied = count($selectedStatusRows) === 2;
    foreach($selectedStatusRows as $selectedStatusRow)
        if(strpos(','.$selectedStatusRow['status'].',', ',baogia,') === false) $selectedStatusApplied = false;
    readinessAssert($apiSelectedStatus['status'] === 200 && !empty($apiSelectedPayload['success']) && (int)$apiSelectedPayload['updated'] === 2 && $selectedStatusApplied, 'API trạng thái cập nhật set-based các dòng được chọn');
    $apiFilteredStatus = readinessHttp($base.'api/status.php', 'POST', array('admin_csrf' => $authenticatedToken[1], 'scope' => 'filtered', 'table' => 'product', 'attr' => 'baogia', 'value' => '1', 'type' => 'san-pham', 'com' => 'product', 'keyword' => $bulkStatusPrefix), $newCookie);
    $apiFilteredPayload = json_decode($apiFilteredStatus['body'], true);
    $bulkStatusRow = $d->rawQueryOne('select status from #_product where id = ?', array($bulkStatusProductId));
    readinessAssert($apiFilteredStatus['status'] === 200 && !empty($apiFilteredPayload['success']) && (int)$apiFilteredPayload['updated'] === 2 && strpos(','.$bulkStatusRow['status'].',', ',baogia,') !== false, 'API trạng thái áp dụng được toàn bộ kết quả theo bộ lọc');

    // ADM-P1-012: form liên hệ Theme V2 đã bỏ trường địa chỉ; Admin phải lưu được liên hệ khi địa chỉ trống.
    $contactFixtureCode = 'CODEX-CONTACT-'.bin2hex(random_bytes(4));
    $d->insert('contact', array(
        'fullname' => 'Khách kiểm thử '.$contactFixtureCode,
        'email' => strtolower($contactFixtureCode).'@example.com',
        'phone' => '0900000000',
        'address' => '',
        'subject' => 'Chủ đề kiểm thử',
        'content' => 'Nội dung kiểm thử',
        'status' => '',
        'date_created' => time(),
        'date_updated' => 0,
        'numb' => 1,
    ));
    $contactFixtureId = (int)$d->getLastInsertId();
    $createdContactIds[] = $contactFixtureId;

    $contactForm = readinessHttp($base.'index.php?com=contact&act=edit&id='.$contactFixtureId, 'GET', array(), $newCookie);
    readinessAssert(
        $contactForm['status'] === 200 && preg_match('/name="admin_csrf"\s+value="([^"]+)"/', $contactForm['body'], $contactToken) === 1,
        'Form sửa liên hệ authenticated render CSRF field'
    );

    $contactSave = readinessHttp($base.'index.php?com=contact&act=save', 'POST', array(
        'admin_csrf' => $contactToken[1],
        'id' => $contactFixtureId,
        'data' => array(
            'fullname' => 'Khách kiểm thử '.$contactFixtureCode,
            'email' => strtolower($contactFixtureCode).'@example.com',
            'phone' => '0900000000',
            'address' => '',
            'subject' => 'Chủ đề kiểm thử đã sửa',
            'content' => 'Nội dung kiểm thử đã sửa',
        ),
    ), $newCookie);
    $contactRow = $d->rawQueryOne('select subject, address, date_updated from #_contact where id = ?', array($contactFixtureId));
    readinessAssert(
        in_array($contactSave['status'], array(200, 302), true)
            && $contactRow
            && (int)$contactRow['date_updated'] > 0
            && $contactRow['subject'] === 'Chủ đề kiểm thử đã sửa'
            && (string)$contactRow['address'] === '',
        'Lưu liên hệ thành công khi địa chỉ để trống'
    );

    // Pipeline yêu cầu tư vấn: danh sách, bộ đếm và lưu trạng thái phải dùng cùng allowlist cấu hình.
    $newsletterFixtureCode = 'CODEX-NEWSLETTER-'.bin2hex(random_bytes(4));
    foreach(array('', '2') as $fixtureStatus)
    {
        $d->insert('newsletter', array(
            'fullname' => 'Khách tư vấn '.$newsletterFixtureCode.'-'.$fixtureStatus,
            'email' => strtolower($newsletterFixtureCode).'-'.($fixtureStatus === '' ? 'new' : 'active').'@example.com',
            'phone' => '0900000000',
            'address' => 'Địa chỉ kiểm thử',
            'notes' => '',
            'confirm_status' => $fixtureStatus,
            'type' => 'dangkynhantin',
            'status' => $fixtureStatus === '' ? '' : 'hienthi',
            'date_created' => time(),
            'date_updated' => 0,
            'numb' => 0,
        ));
        $createdNewsletterIds[] = (int)$d->getLastInsertId();
    }

    $newsletterList = readinessHttp($base.'index.php?com=newsletter&act=man&type=dangkynhantin&keyword='.urlencode($newsletterFixtureCode), 'GET', array(), $newCookie);
    readinessAssert(
        $newsletterList['status'] === 200
            && strpos($newsletterList['body'], 'Yêu cầu tư vấn') !== false
            && strpos($newsletterList['body'], 'Mới / chưa xử lý') !== false
            && strpos($newsletterList['body'], 'Đang tư vấn') !== false
            && substr_count($newsletterList['body'], $newsletterFixtureCode) >= 2,
        'Pipeline yêu cầu tư vấn render bộ đếm, lọc và kết quả tìm kiếm'
    );

    $pendingNewsletter = readinessHttp($base.'index.php?com=newsletter&act=man&type=dangkynhantin&confirm_status=pending&keyword='.urlencode($newsletterFixtureCode), 'GET', array(), $newCookie);
    readinessAssert(
        $pendingNewsletter['status'] === 200
            && strpos($pendingNewsletter['body'], $newsletterFixtureCode.'-2') === false
            && strpos($pendingNewsletter['body'], $newsletterFixtureCode.'-') !== false,
        'Bộ lọc yêu cầu mới không lẫn trạng thái đang tư vấn'
    );

    $newsletterEdit = readinessHttp($base.'index.php?com=newsletter&act=edit&type=dangkynhantin&id='.$createdNewsletterIds[0], 'GET', array(), $newCookie);
    readinessAssert(
        $newsletterEdit['status'] === 200 && preg_match('/name="admin_csrf"\s+value="([^"]+)"/', $newsletterEdit['body'], $newsletterToken) === 1,
        'Form yêu cầu tư vấn authenticated render CSRF field'
    );
    $newsletterSaveData = array(
        'fullname' => 'Khách tư vấn '.$newsletterFixtureCode.'-',
        'email' => strtolower($newsletterFixtureCode).'-new@example.com',
        'phone' => '0900000000',
        'address' => 'Địa chỉ kiểm thử',
        'notes' => 'Đã gọi xác nhận nhu cầu',
        'confirm_status' => '2',
        'numb' => '0',
    );
    $newsletterSave = readinessHttp($base.'index.php?com=newsletter&act=save&type=dangkynhantin', 'POST', array(
        'admin_csrf' => $newsletterToken[1],
        'id' => $createdNewsletterIds[0],
        'data' => $newsletterSaveData,
    ), $newCookie);
    $newsletterRow = $d->rawQueryOne('select notes, confirm_status, status from #_newsletter where id = ?', array($createdNewsletterIds[0]));
    readinessAssert(
        in_array($newsletterSave['status'], array(200, 302), true)
            && $newsletterRow['notes'] === 'Đã gọi xác nhận nhu cầu'
            && (string)$newsletterRow['confirm_status'] === '2'
            && $newsletterRow['status'] === 'hienthi',
        'Lưu ghi chú và trạng thái pipeline yêu cầu tư vấn hợp lệ'
    );

    $newsletterSaveData['confirm_status'] = 'trang-thai-tu-che';
    readinessHttp($base.'index.php?com=newsletter&act=save&type=dangkynhantin', 'POST', array(
        'admin_csrf' => $newsletterToken[1],
        'id' => $createdNewsletterIds[0],
        'data' => $newsletterSaveData,
    ), $newCookie);
    $newsletterRejectedRow = $d->rawQueryOne('select confirm_status from #_newsletter where id = ?', array($createdNewsletterIds[0]));
    readinessAssert((string)$newsletterRejectedRow['confirm_status'] === '2', 'Pipeline từ chối trạng thái ngoài allowlist cấu hình');

    // ADM-P2-001: bảng điều khiển nạp dữ liệu ở controller sources/index.php, template chỉ render.
    $dashboard = readinessHttp($base.'index.php', 'GET', array(), $newCookie);
    $dashboardNoPhpError = true;
    foreach(array('Notice:', 'Warning:', 'Fatal error', 'Undefined variable') as $needle)
        if(strpos($dashboard['body'], $needle) !== false) $dashboardNoPhpError = false;
    $dashboardClean = $dashboard['status'] === 200
        && strpos($dashboard['body'], 'Việc cần xử lý') !== false
        && $dashboardNoPhpError;
    readinessAssert($dashboardClean, 'Bảng điều khiển render 200 không PHP notice/warning');

    $expectedQueue = array(
        'orders' => (int) $d->rawQueryOne("select count(id) as t from #_order where order_status = ?", array(1))['t'],
        'reviews' => (int) $d->rawQueryOne("select count(id) as t from #_comment where id_parent = 0 and find_in_set('new-admin', status)")['t'],
        'wattage' => (int) $d->rawQueryOne("select count(id) as t from #_product where type = ? and (cong_suat is null or cong_suat <= 0)", array('san-pham'))['t'],
    );
    $queueMatches = strpos($dashboard['body'], '>'.$expectedQueue['orders'].'</span>') !== false
        && strpos($dashboard['body'], '>'.$expectedQueue['wattage'].'</span>') !== false;
    readinessAssert($queueMatches, 'Ô hàng đợi bảng điều khiển khớp số liệu truy vấn trực tiếp');
    readinessAssert(preg_match('/data:\s*\[[0-9,\s]*\]/', $dashboard['body']) === 1, 'Chart bảng điều khiển vẫn nhận mảng dữ liệu ngày');

    echo "Admin readiness tests completed.\n";
}
finally
{
    foreach($createdNewsletterIds as $newsletterId) $d->rawQuery("delete from #_newsletter where id = ?", array($newsletterId));
    foreach($createdContactIds as $contactId) $d->rawQuery("delete from #_contact where id = ?", array($contactId));
    foreach($createdProductIds as $productId) $d->rawQuery("delete from #_product where id = ?", array($productId));
    foreach($createdUsers as $user) $d->rawQuery("delete from #_user_log where id_user = ?", array($user['id']));
    foreach($createdUsers as $user) $d->rawQuery("delete from #_user where id = ?", array($user['id']));
    foreach($createdGroups as $groupId) $d->rawQuery("delete from #_permission where id_permission_group = ?", array($groupId));
    foreach($createdGroups as $groupId) $d->rawQuery("delete from #_permission_group where id = ?", array($groupId));
    foreach($rateKeys as $key) $d->rawQuery("delete from #_user_limit where login_ip = ?", array($key));
}
?>
