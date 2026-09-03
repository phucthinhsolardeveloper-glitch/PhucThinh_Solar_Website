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
$createdSortableIds = array('sortable'=>array(), 'sortable_index'=>array(), 'sortable_detail'=>array());
$createdCommentIds = array();

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

    $sortableSettingBefore = $d->rawQueryOne('select id, hotline, phone, email, options from #_setting_sort order by id asc limit 0,1');
    $sortableInvalidMarker = 'CODEX-SORTABLE-INVALID-'.bin2hex(random_bytes(4));
    $sortableInvalidSave = readinessHttp($base.'index.php?com=sortable&act=save', 'POST', array(
        'admin_csrf'=>$authenticatedToken[1],
        'id'=>(int)$sortableSettingBefore['id'],
        'data'=>array('hotline'=>'1', 'phone'=>'1', 'email'=>'1', 'options'=>array('chinhsach'=>'Chính sách', 'dangky'=>'Đăng ký', 'fanpage'=>'Fanpage')),
        'id_item_menu'=>array($sortableInvalidMarker),
    ), $newCookie);
    $sortableSettingAfter = $d->rawQueryOne('select id, hotline, phone, email, options from #_setting_sort order by id asc limit 0,1');
    readinessAssert($sortableInvalidSave['status'] === 200 && strpos($sortableInvalidSave['body'], 'Cập nhật dữ liệu bị lỗi') !== false && strpos($sortableInvalidSave['body'], $sortableInvalidMarker) === false && $sortableSettingAfter === $sortableSettingBefore, 'Lưu sortable từ chối nhóm mảng thiếu, không lộ POST debug và không ghi dở dữ liệu');

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

    $sortableEndpoints = array('sortable'=>'update_sttMenu.php', 'sortable_index'=>'update_sttIndex.php', 'sortable_detail'=>'update_sttDetail.php');
    foreach($sortableEndpoints as $sortableTable => $sortableEndpoint)
    {
        foreach(array(8, 9) as $initialOrder)
        {
            $d->insert($sortableTable, array('name'=>'CODEX-SORT-'.bin2hex(random_bytes(4)), 'display_order'=>$initialOrder, 'created'=>date('Y-m-d H:i:s'), 'modified'=>date('Y-m-d H:i:s'), 'status'=>'1', 'type'=>'codex-test'));
            $createdSortableIds[$sortableTable][] = (int)$d->getLastInsertId();
        }
        $orderedIds = array_reverse($createdSortableIds[$sortableTable]);
        $sortResponse = readinessHttp($base.'api/'.$sortableEndpoint, 'POST', array('admin_csrf'=>$authenticatedToken[1], 'order'=>implode(',', $orderedIds)), $newCookie);
        $sortPayload = json_decode($sortResponse['body'], true);
        $sortRows = $d->rawQuery("select id, display_order from #_{$sortableTable} where id in (?, ?)", $orderedIds);
        $sortPositions = array();
        foreach($sortRows as $sortRow) $sortPositions[(int)$sortRow['id']] = (int)$sortRow['display_order'];
        readinessAssert($sortResponse['status'] === 200 && !empty($sortPayload['ok']) && $sortPayload['code'] === 'SORT_ORDER_UPDATED' && (int)$sortPayload['data']['updated'] === 2 && $sortPositions[$orderedIds[0]] === 0 && $sortPositions[$orderedIds[1]] === 1, 'API '.$sortableTable.' cập nhật thứ tự bằng transaction và contract JSON');
    }
    $invalidSortResponse = readinessHttp($base.'api/update_sttMenu.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'order'=>$createdSortableIds['sortable'][0].','.$createdSortableIds['sortable'][0]), $newCookie);
    $invalidSortPayload = json_decode($invalidSortResponse['body'], true);
    readinessAssert($invalidSortResponse['status'] === 400 && empty($invalidSortPayload['ok']) && $invalidSortPayload['code'] === 'INVALID_SORT_ORDER', 'API sắp xếp từ chối ID trùng mà không cập nhật dữ liệu');
    foreach(array('sortable'=>'menu', 'sortable_index'=>'index', 'sortable_detail'=>'detail') as $sortableTable => $sortableItem)
    {
        $deleteSortableId = $createdSortableIds[$sortableTable][0];
        $deleteSortableResponse = readinessHttp($base.'api/delete_add_item.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'id'=>$deleteSortableId, 'item'=>$sortableItem), $newCookie);
        $deleteSortablePayload = json_decode($deleteSortableResponse['body'], true);
        $deletedSortableRow = $d->rawQueryOne("select id from #_{$sortableTable} where id = ?", array($deleteSortableId));
        readinessAssert($deleteSortableResponse['status'] === 200 && !empty($deleteSortablePayload['ok']) && $deleteSortablePayload['code'] === 'SORTABLE_ITEM_DELETED' && empty($deletedSortableRow), 'API xóa đúng bảng sortable cho nhóm '.$sortableItem);
    }
    $invalidDeleteResponse = readinessHttp($base.'api/delete_add_item.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'id'=>$createdSortableIds['sortable'][1], 'item'=>'unknown'), $newCookie);
    $invalidDeletePayload = json_decode($invalidDeleteResponse['body'], true);
    $retainedSortableRow = $d->rawQueryOne('select id from #_sortable where id = ?', array($createdSortableIds['sortable'][1]));
    readinessAssert($invalidDeleteResponse['status'] === 400 && empty($invalidDeletePayload['ok']) && $invalidDeletePayload['code'] === 'INVALID_SORTABLE_ITEM' && !empty($retainedSortableRow['id']), 'API xóa từ chối nhóm ngoài allowlist và giữ nguyên dữ liệu');

    $commentMarker = 'CODEX-COMMENT-'.bin2hex(random_bytes(4));
    $d->insert('comment', array('id_parent'=>0, 'id_variant'=>123456, 'star'=>5, 'content'=>'Nội dung kiểm thử', 'fullname'=>$commentMarker, 'status'=>'new-admin', 'type'=>'san-pham', 'date_posted'=>time()));
    $commentId = (int)$d->getLastInsertId();
    $createdCommentIds[] = $commentId;
    $commentStatusResponse = readinessHttp($base.'api/comment.php?get=status', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'id'=>$commentId, 'status'=>'hienthi'), $newCookie);
    $commentStatusPayload = json_decode($commentStatusResponse['body'], true);
    $commentStatusRow = $d->rawQueryOne('select status from #_comment where id = ?', array($commentId));
    readinessAssert($commentStatusResponse['status'] === 200 && !empty($commentStatusPayload['ok']) && $commentStatusPayload['code'] === 'COMMENT_UPDATED' && $commentStatusRow['status'] === 'hienthi', 'API bình luận duyệt trạng thái allowlist và trả envelope JSON');
    $invalidCommentStatus = readinessHttp($base.'api/comment.php?get=status', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'id'=>$commentId, 'status'=>'tu-che'), $newCookie);
    $invalidCommentPayload = json_decode($invalidCommentStatus['body'], true);
    $unchangedCommentRow = $d->rawQueryOne('select status from #_comment where id = ?', array($commentId));
    readinessAssert($invalidCommentStatus['status'] === 200 && empty($invalidCommentPayload['ok']) && $unchangedCommentRow['status'] === 'hienthi', 'API bình luận từ chối trạng thái ngoài allowlist');
    $commentReplyResponse = readinessHttp($base.'api/comment.php?get=addAdmin', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'dataReview'=>array('content'=>'Phản hồi kiểm thử', 'id_parent'=>$commentId, 'id_variant'=>999999, 'type'=>'gia-mao', 'status'=>'new-admin')), $newCookie);
    $commentReplyPayload = json_decode($commentReplyResponse['body'], true);
    $commentReplyRow = $d->rawQueryOne('select id, id_variant, type, status, poster from #_comment where id_parent = ? order by id desc limit 0,1', array($commentId));
    if(!empty($commentReplyRow['id'])) $createdCommentIds[] = (int)$commentReplyRow['id'];
    readinessAssert($commentReplyResponse['status'] === 200 && !empty($commentReplyPayload['ok']) && (int)$commentReplyRow['id_variant'] === 123456 && $commentReplyRow['type'] === 'san-pham' && $commentReplyRow['status'] === 'hienthi' && $commentReplyRow['poster'] === 'admin', 'API phản hồi lấy phạm vi từ bình luận gốc và bỏ field ngoài allowlist');
    $commentDeleteResponse = readinessHttp($base.'api/comment.php?get=delete', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'id'=>$commentId), $newCookie);
    $commentDeletePayload = json_decode($commentDeleteResponse['body'], true);
    $remainingComments = $d->rawQueryOne('select count(id) as num from #_comment where id = ? or id_parent = ?', array($commentId, $commentId));
    readinessAssert($commentDeleteResponse['status'] === 200 && !empty($commentDeletePayload['ok']) && (int)$remainingComments['num'] === 0, 'API xóa bình luận gốc cùng phản hồi và xác minh kết quả');

    $bulkStatusPrefix = 'CODEX-BS-'.bin2hex(random_bytes(4));
    $bulkStatusCode = $bulkStatusPrefix.'-1';
    $d->insert('product', array('code' => $bulkStatusCode, 'namevi' => 'Sản phẩm kiểm thử trạng thái lọc', 'slugvi' => strtolower($bulkStatusCode), 'type' => 'san-pham', 'status' => 'hienthi', 'photo' => 'codex-fixture.png', 'descvi' => 'Đã có mô tả', 'contentvi' => 'Đã có nội dung', 'light_source' => 'LED', 'numb' => 0, 'cong_suat' => 0, 'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0, 'id_brand' => 0, 'date_created' => time(), 'date_updated' => 0, 'view' => 0));
    $bulkStatusProductId = (int)$d->getLastInsertId();
    $createdProductIds[] = $bulkStatusProductId;
    $secondBulkStatusCode = $bulkStatusPrefix.'-2';
    $d->insert('product', array('code' => $secondBulkStatusCode, 'namevi' => 'Sản phẩm kiểm thử set-based', 'slugvi' => strtolower($secondBulkStatusCode), 'type' => 'san-pham', 'status' => '', 'numb' => 0, 'cong_suat' => 0, 'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0, 'id_brand' => 0, 'date_created' => time(), 'date_updated' => 0, 'view' => 0));
    $secondBulkStatusProductId = (int)$d->getLastInsertId();
    $createdProductIds[] = $secondBulkStatusProductId;
    $existingSlug = strtolower($bulkStatusCode);
    $slugExistsResponse = readinessHttp($base.'api/slug.php', 'POST', array('slug'=>$existingSlug, 'id'=>0, 'copy'=>0), $newCookie);
    $slugExistsPayload = json_decode($slugExistsResponse['body'], true);
    readinessAssert($slugExistsResponse['status'] === 200 && !empty($slugExistsPayload['ok']) && $slugExistsPayload['code'] === 'SLUG_EXISTS' && empty($slugExistsPayload['data']['available']), 'API slug phát hiện đường dẫn đã tồn tại bằng contract JSON');
    $slugCurrentResponse = readinessHttp($base.'api/slug.php', 'POST', array('slug'=>$existingSlug, 'id'=>$bulkStatusProductId, 'copy'=>0), $newCookie);
    $slugCurrentPayload = json_decode($slugCurrentResponse['body'], true);
    readinessAssert($slugCurrentResponse['status'] === 200 && !empty($slugCurrentPayload['ok']) && $slugCurrentPayload['code'] === 'SLUG_AVAILABLE' && !empty($slugCurrentPayload['data']['available']), 'API slug loại trừ đúng bản ghi đang chỉnh sửa');
    $uniqueSlug = 'codex-slug-'.bin2hex(random_bytes(4));
    $slugUniqueResponse = readinessHttp($base.'api/slug.php', 'POST', array('slug'=>$uniqueSlug, 'id'=>'0 OR 1=1', 'copy'=>0), $newCookie);
    $slugUniquePayload = json_decode($slugUniqueResponse['body'], true);
    readinessAssert($slugUniqueResponse['status'] === 200 && !empty($slugUniquePayload['ok']) && $slugUniquePayload['code'] === 'SLUG_AVAILABLE' && !empty($slugUniquePayload['data']['available']), 'API slug ép kiểu ID và chấp nhận đường dẫn chưa tồn tại');
    $slugInvalidResponse = readinessHttp($base.'api/slug.php', 'POST', array('slug'=>'../slug-khong-hop-le', 'id'=>0, 'copy'=>0), $newCookie);
    $slugInvalidPayload = json_decode($slugInvalidResponse['body'], true);
    readinessAssert($slugInvalidResponse['status'] === 400 && empty($slugInvalidPayload['ok']) && $slugInvalidPayload['code'] === 'INVALID_SLUG' && empty($slugInvalidPayload['data']['available']), 'API slug từ chối định dạng không hợp lệ bằng lỗi JSON 400');
    $numbUpdate = readinessHttp($base.'api/numb.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'id'=>$secondBulkStatusProductId, 'table'=>'product', 'value'=>7, 'type'=>'san-pham', 'com'=>'product'), $newCookie);
    $numbPayload = json_decode($numbUpdate['body'], true);
    $numbRow = $d->rawQueryOne('select numb from #_product where id = ?', array($secondBulkStatusProductId));
    readinessAssert($numbUpdate['status'] === 200 && !empty($numbPayload['ok']) && $numbPayload['code'] === 'NUMB_UPDATED' && (int)$numbRow['numb'] === 7, 'API thứ tự trả contract JSON và cập nhật đúng dữ liệu');
    $copyResponse = readinessHttp($base.'api/copy.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'id'=>$bulkStatusProductId, 'table'=>'product', 'copyimg'=>'0', 'type'=>'san-pham'), $newCookie);
    $copyPayload = json_decode($copyResponse['body'], true);
    $copyId = !empty($copyPayload['data']['id']) ? (int)$copyPayload['data']['id'] : 0;
    if($copyId) $createdProductIds[] = $copyId;
    $copyRow = $copyId ? $d->rawQueryOne('select namevi, slugvi, status from #_product where id = ?', array($copyId)) : array();
    readinessAssert($copyResponse['status'] === 200 && !empty($copyPayload['ok']) && $copyPayload['code'] === 'COPY_CREATED' && !empty($copyRow) && $copyRow['status'] === '' && $copyRow['slugvi'] !== strtolower($bulkStatusCode), 'API sao chép tạo bản nháp với slug mới và contract JSON');
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
    readinessAssert($apiFilteredStatus['status'] === 200 && !empty($apiFilteredPayload['success']) && (int)$apiFilteredPayload['updated'] === 3 && strpos(','.$bulkStatusRow['status'].',', ',baogia,') !== false, 'API trạng thái áp dụng được toàn bộ kết quả theo bộ lọc');

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
    foreach($createdCommentIds as $commentId) $d->rawQuery('delete from #_comment where id = ? or id_parent = ?', array($commentId, $commentId));
    foreach($createdSortableIds as $sortableTable => $sortableIds)
        foreach($sortableIds as $sortableId) $d->rawQuery("delete from #_{$sortableTable} where id = ?", array($sortableId));
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
