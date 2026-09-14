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
require LIBRARIES.'admin_product_completeness.php';
require LIBRARIES.'product_promo.php';
require LIBRARIES.'login_security.php';
require LIBRARIES.'session_security.php';
require LIBRARIES.'config-type.php';

function readinessAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException("FAIL: {$message}");
    echo "PASS: {$message}\n";
}

function readinessAssertApiEnvelope($payload, $message)
{
    $valid = is_array($payload)
        && array_key_exists('ok', $payload)
        && is_bool($payload['ok'])
        && isset($payload['message'])
        && is_string($payload['message'])
        && isset($payload['code'])
        && is_string($payload['code'])
        && $payload['code'] !== ''
        && array_key_exists('data', $payload)
        && is_array($payload['data']);
    readinessAssert($valid, $message);
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

function readinessHttpMultipart($url, $fields, $fileField, $fileName, $fileContent, $cookie = '')
{
    $boundary = '----PTSReadiness'.bin2hex(random_bytes(8));
    $body = '';
    foreach($fields as $name => $value)
    {
        foreach(is_array($value) ? $value : array($value) as $item)
        {
            $body .= '--'.$boundary."\r\n";
            $body .= 'Content-Disposition: form-data; name="'.$name.'"'."\r\n\r\n".(string)$item."\r\n";
        }
    }
    $body .= '--'.$boundary."\r\n";
    $body .= 'Content-Disposition: form-data; name="'.$fileField.'"; filename="'.$fileName.'"'."\r\n";
    $body .= "Content-Type: image/png\r\n\r\n".$fileContent."\r\n";
    $body .= '--'.$boundary."--\r\n";
    $headers = array('User-Agent: PTS-Local-Readiness-Test', 'Content-Type: multipart/form-data; boundary='.$boundary, 'Content-Length: '.strlen($body));
    if($cookie !== '') $headers[] = 'Cookie: '.$cookie;
    $context = stream_context_create(array('http'=>array('method'=>'POST', 'ignore_errors'=>true, 'timeout'=>20, 'header'=>implode("\r\n", $headers), 'content'=>$body)));
    $responseBody = file_get_contents($url, false, $context);
    $responseHeaders = isset($http_response_header) ? $http_response_header : array();
    $status = 0;
    foreach($responseHeaders as $header) if(preg_match('#^HTTP/\S+\s+(\d+)#i', $header, $match)) $status = (int)$match[1];
    return array('status'=>$status, 'body'=>$responseBody, 'cookies'=>array());
}

function readinessSessionCookie($setCookies)
{
    foreach($setCookies as $cookie)
        if(preg_match('/^(PHPSESSID=[^;]+)/', $cookie, $match)) return array($match[1], $cookie);
    return array('', '');
}

$apiExceptionPayload = adminApiExceptionEnvelope();
readinessAssert(
    empty($apiExceptionPayload['ok'])
        && $apiExceptionPayload['code'] === 'INTERNAL_ERROR'
        && empty($apiExceptionPayload['success'])
        && strpos(json_encode($apiExceptionPayload), 'SQLSTATE') === false,
    'Exception boundary Admin API trả lỗi trung tính theo contract JSON'
);
$newsTypeConfigSource = file_get_contents('/var/www/html/libraries/type/config-type-news.php');
readinessAssert(substr_count($newsTypeConfigSource, '$nametype = "chinh-sach";') === 1, 'Cấu hình Chính sách chỉ còn một khai báo đầy đủ');
readinessAssert(
    isset($config['news']['chinh-sach'])
        && !empty($config['news']['chinh-sach']['images'])
        && !empty($config['news']['chinh-sach']['content'])
        && !empty($config['news']['chinh-sach']['seo']),
    'Cấu hình Chính sách giữ form nội dung/SEO, có field ảnh đại diện (og:image/card - yêu cầu 2026-09-11, không hiện to ở đầu trang chi tiết)'
);
readinessAssert(
    isset($config['news']['hinh-thuc-thanh-toan'])
        && !empty($config['news']['hinh-thuc-thanh-toan']['desc'])
        && empty($config['news']['hinh-thuc-thanh-toan']['slug'])
        && empty($config['news']['hinh-thuc-thanh-toan']['images']),
    'Cấu hình Hình thức thanh toán chỉ mở các trường đang được checkout sử dụng'
);
readinessAssert(
    !empty($config['photo']['photo_static']['watermark']['watermark-advanced'])
        && !empty($config['photo']['photo_static']['watermark-news']['watermark-advanced']),
    'Cấu hình Admin khôi phục đủ watermark sản phẩm và tin tức'
);
$adminLayoutCssSource = file_get_contents('/var/www/html/administrator/templates/layout/css.php');
$adminStyleSource = file_get_contents('/var/www/html/administrator/assets/css/adminlte-style.css');
readinessAssert(
    strpos(file_get_contents('/var/www/html/libraries/config-type.php'), 'product_campaign&act=man') !== false
        && strpos($adminLayoutCssSource, 'filemtime(ROOT') !== false
        && strpos($adminStyleSource, '.pts-admin-product-thumb{position:relative;display:inline-block;width:70px;height:55px;') !== false,
    'Sidebar mở thư viện chiến dịch và CSS Admin có cache-busting theo phiên bản file'
);
readinessAssert(!isset($config['static']['tom-tat-gioi-thieu']), 'Không khôi phục type Tóm tắt giới thiệu khi template public chưa render dữ liệu này');
readinessAssert(
    isset($config['news']['about-team'])
        && !empty($config['news']['about-team']['title_index'])
        && !empty($config['news']['about-team']['desc'])
        && !empty($config['news']['about-team']['content'])
        && !empty($config['news']['about-team']['images'])
        && isset($config['news']['about-principle'])
        && !empty($config['news']['about-principle']['images'])
        && !empty($config['news']['about-principle']['desc'])
        && isset($config['news']['ly-do-chon-chung-toi']['check']['about'])
        && isset($config['photo']['man_photo']['chungnhan']['check_photo']['noibat'])
        && isset($config['photo']['man_photo']['about-cta']),
    'Cấu hình About nối đủ Con người, điểm nhấn, card nổi bật, chứng nhận nổi bật và CTA với Admin'
);
$apiBootstrapSource = file_get_contents('/var/www/html/administrator/api/config.php');
    readinessAssert(strpos($apiBootstrapSource, 'adminInstallApiExceptionHandler();') !== false, 'Bootstrap chung cài exception boundary cho mọi Admin API');
$adminBootstrapSource = file_get_contents('/var/www/html/libraries/requick.php');
readinessAssert(
    strpos($adminBootstrapSource, 'register_shutdown_function(function() use ($cache)') !== false,
    'Mutation Admin xóa lại cache public sau khi controller hoàn tất'
);

$prefix = 'CODEX-RBAC-'.bin2hex(random_bytes(4));
$createdUsers = array();
$createdGroups = array();
$rateKeys = array();
$createdProductIds = array();
$createdContactIds = array();
$createdNewsletterIds = array();
$createdSortableIds = array('sortable'=>array(), 'sortable_index'=>array(), 'sortable_detail'=>array());
$createdCommentIds = array();
$createdGalleryIds = array();
$createdCampaignIds = array();
$createdCampaignPhotos = array();

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
        'product' => array('product_man_san-pham','product_add_san-pham','product_edit_san-pham','product_delete_san-pham','product_add_photo_san-pham','product_edit_photo_san-pham','product_delete_photo_san-pham','product_campaign_man_san-pham','product_campaign_add_san-pham','product_campaign_edit_san-pham','newsletter_man_dangkynhantin','newsletter_edit_dangkynhantin'),
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

    /* Phần HTTP phía dưới quét chéo sản phẩm, nội dung, media, liên hệ và
       newsletter. Dùng fixture role 3 để kiểm tra toàn bộ màn hình; việc chặn
       staff theo từng quyền đã có test-admin-permission-gate.php phụ trách. */
    $readinessAdminName = strtolower($prefix.'-superadmin');
    $readinessAdminPassword = bin2hex(random_bytes(12));
    $d->insert('user', array(
        'id_permission' => 0,
        'username' => $readinessAdminName,
        'password' => $func->encryptPassword($config['website']['secret'], $readinessAdminPassword, $config['website']['salt']),
        'fullname' => 'Local readiness superadmin',
        'status' => 'hienthi',
        'role' => 3,
        'secret_key' => bin2hex(random_bytes(16)),
        'numb' => 0
    ));
    $createdUsers['superadmin'] = array(
        'id' => (int)$d->getLastInsertId(),
        'username' => $readinessAdminName,
        'password' => $readinessAdminPassword
    );
    readinessAssert($createdUsers['superadmin']['id'] > 0, 'Tạo tài khoản superadmin fixture cho HTTP readiness đa module');

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

	$unauthenticatedJsonApi = readinessHttp($base.'api/status.php', 'POST', array('id'=>999999, 'table'=>'product', 'attr'=>'hienthi', 'type'=>'san-pham', 'com'=>'product'), $oldCookie);
	$unauthenticatedJsonPayload = json_decode($unauthenticatedJsonApi['body'], true);
	readinessAssert($unauthenticatedJsonApi['status'] === 401 && empty($unauthenticatedJsonPayload['ok']) && $unauthenticatedJsonPayload['code'] === 'AUTH_REQUIRED', 'Admin API JSON trả 401 rõ ràng khi phiên chưa đăng nhập');
	$unauthenticatedHtmlApi = readinessHttp($base.'api/category.php', 'POST', array('id'=>1, 'level'=>0, 'table'=>'product_cat', 'type'=>'san-pham'), $oldCookie);
	$unauthenticatedHtmlPayload = json_decode($unauthenticatedHtmlApi['body'], true);
	readinessAssert($unauthenticatedHtmlApi['status'] === 401 && empty($unauthenticatedHtmlPayload['ok']) && $unauthenticatedHtmlPayload['code'] === 'AUTH_REQUIRED', 'Admin API HTML fragment cũng trả 401 chuẩn để client nhận biết hết phiên');
	readinessAssertApiEnvelope($unauthenticatedJsonPayload, 'Lỗi hết phiên của API JSON giữ đủ schema envelope');
	readinessAssertApiEnvelope($unauthenticatedHtmlPayload, 'Lỗi hết phiên của API HTML fragment giữ đủ schema envelope');
	foreach(array('component.php', 'save_add_item.php') as $disabledEndpoint)
	{
		$disabledApi = readinessHttp($base.'api/'.$disabledEndpoint, 'POST', array(), $oldCookie);
		$disabledPayload = json_decode($disabledApi['body'], true);
		readinessAssert(
			$disabledApi['status'] === 404
				&& empty($disabledPayload['ok'])
				&& $disabledPayload['code'] === 'LEGACY_API_DISABLED',
			'Endpoint legacy '.$disabledEndpoint.' giữ trạng thái vô hiệu hóa và trả lỗi JSON rõ ràng'
		);
		readinessAssertApiEnvelope($disabledPayload, 'Endpoint legacy '.$disabledEndpoint.' giữ đủ schema envelope');
	}

    $missingCsrf = readinessHttp($base.'api/login.php', 'POST', array('username' => $createdUsers['superadmin']['username'], 'password' => $createdUsers['superadmin']['password']), $oldCookie);
    readinessAssert($missingCsrf['status'] === 403, 'Login thiếu CSRF bị từ chối');

    $login = readinessHttp($base.'api/login.php', 'POST', array('username' => $createdUsers['superadmin']['username'], 'password' => $createdUsers['superadmin']['password'], 'admin_csrf' => $tokenMatch[1]), $oldCookie);
    list($newCookie, $authenticatedSetCookie) = readinessSessionCookie($login['cookies']);
    $loginJson = json_decode($login['body'], true);
    readinessAssert($login['status'] === 200 && !empty($loginJson['success']), 'Đăng nhập HTTP bằng tài khoản superadmin fixture thành công');

    $aboutTeamForm = readinessHttp($base.'index.php?com=news&act=add&type=about-team', 'GET', array(), $newCookie);
    $aboutPrincipleForm = readinessHttp($base.'index.php?com=news&act=add&type=about-principle', 'GET', array(), $newCookie);
    $aboutTrustForm = readinessHttp($base.'index.php?com=news&act=add&type=ly-do-chon-chung-toi', 'GET', array(), $newCookie);
    $aboutCompassForm = readinessHttp($base.'index.php?com=news&act=add&type=su-menh', 'GET', array(), $newCookie);
    $aboutCtaForm = readinessHttp($base.'index.php?com=photo&act=add_photo&type=about-cta', 'GET', array(), $newCookie);
    $aboutCertificateForm = readinessHttp($base.'index.php?com=photo&act=add_photo&type=chungnhan', 'GET', array(), $newCookie);
    readinessAssert(
        $aboutTeamForm['status'] === 200
            && strpos($aboutTeamForm['body'], 'Điều khiển riêng section Con người') !== false
            && strpos($aboutTeamForm['body'], 'name="data[title_indexvi]"') !== false
            && strpos($aboutTeamForm['body'], 'name="data[descvi]"') !== false
            && strpos($aboutTeamForm['body'], 'name="data[contentvi]"') !== false
            && strpos($aboutTeamForm['body'], 'name="file"') !== false,
        'Admin About - Con người có đủ nhãn nhỏ, tiêu đề, mô tả, nội dung và ảnh'
    );
    readinessAssert(
        $aboutPrincipleForm['status'] === 200
            && strpos($aboutPrincipleForm['body'], 'Tối đa 3 record đang Hiển thị') !== false
            && strpos($aboutPrincipleForm['body'], 'name="data[title_indexvi]"') !== false
            && strpos($aboutPrincipleForm['body'], 'name="data[descvi]"') !== false
            && strpos($aboutPrincipleForm['body'], 'name="file"') !== false,
        'Admin About - Điểm nhấn con người có đủ tiêu đề, mô tả, icon và thứ tự'
    );
    readinessAssert(
        $aboutTrustForm['status'] === 200
            && strpos($aboutTrustForm['body'], 'name="status[about]"') !== false
            && strpos($aboutTrustForm['body'], 'name="data[title_indexvi]"') !== false
            && strpos($aboutTrustForm['body'], 'name="data[descvi]"') !== false,
        'Admin Lý do lựa chọn cho phép chọn card About và sửa tiêu đề ngắn/mô tả'
    );
    readinessAssert(
        $aboutCompassForm['status'] === 200
            && strpos($aboutCompassForm['body'], 'ảnh/icon, thứ tự và Hiển thị') !== false
            && strpos($aboutCompassForm['body'], 'name="data[title_indexvi]"') !== false
            && strpos($aboutCompassForm['body'], 'name="data[descvi]"') !== false
            && strpos($aboutCompassForm['body'], 'name="file"') !== false,
        'Admin Tầm nhìn - Sứ mệnh - Giá trị quản lý được nội dung và icon About'
    );
    readinessAssert(
        $aboutCtaForm['status'] === 200
            && strpos($aboutCtaForm['body'], 'Điều khiển dải CTA cuối') !== false
            && strpos($aboutCtaForm['body'], 'name="dataMulti[0][namevi]"') !== false
            && strpos($aboutCtaForm['body'], 'name="dataMulti[0][descvi]"') !== false
            && strpos($aboutCtaForm['body'], 'name="dataMulti[0][link]"') !== false,
        'Admin CTA About có đủ tiêu đề, mô tả, link và ảnh nền'
    );
    readinessAssert(
        $aboutCertificateForm['status'] === 200
            && strpos($aboutCertificateForm['body'], 'name="dataMulti[0][status][]"') !== false
            && strpos($aboutCertificateForm['body'], 'value="noibat"') !== false,
        'Admin Chứng nhận có lựa chọn Nổi bật trên About'
    );
    readinessAssert($newCookie !== '' && $newCookie !== $oldCookie, 'Session ID được regenerate sau đăng nhập');
    readinessAssert(stripos($authenticatedSetCookie, 'HttpOnly') !== false && stripos($authenticatedSetCookie, 'SameSite=Lax') !== false, 'Cookie sau đăng nhập giữ thuộc tính bảo mật');

    $authStateAfterLogin = $d->rawQueryOne('select lastlogin, user_token from #_user where id = ?', array($createdUsers['superadmin']['id']));
    $forcedLastLogin = max(1, (int)$authStateAfterLogin['lastlogin'] - 61);
    $d->rawQuery('update #_user set lastlogin = ? where id = ?', array($forcedLastLogin, $createdUsers['superadmin']['id']));

    $adminForm = readinessHttp($base.'index.php?com=product&act=add&type=san-pham', 'GET', array(), $newCookie);
    readinessAssert($adminForm['status'] === 200 && strpos($adminForm['body'], 'name="admin_csrf"') !== false, 'Form sản phẩm authenticated render CSRF field');
    readinessAssert(preg_match('/adminlte-style\.css\?v=\d+/', $adminForm['body']) === 1, 'Trang Admin phát URL CSS có phiên bản để không giữ thumbnail khung bị phóng to');
    readinessAssert(
        strpos($adminForm['body'], 'pts-promo-frame-admin-preview') !== false
            && strpos($adminForm['body'], 'id="pts-promo-frame-admin-overlay"') !== false
            && strpos($adminForm['body'], 'id="file_khuyenmai"') !== false,
        'Form sản phẩm có xem trước ghép ảnh gốc và khung campaign PNG'
    );
    readinessAssert(
        strpos($adminForm['body'], 'Chiến dịch hiển thị') !== false
            && strpos($adminForm['body'], 'data_status=campaign_active') !== false
            && strpos($adminForm['body'], 'product_campaign&act=man') !== false,
        'Sidebar gom banner, popup, thư viện khung và sản phẩm có khung trong nhóm Chiến dịch hiển thị'
    );
    $campaignListPage = readinessHttp($base.'index.php?com=product_campaign&act=man&type=san-pham', 'GET', array(), $newCookie);
    $campaignAddPage = readinessHttp($base.'index.php?com=product_campaign&act=add&type=san-pham', 'GET', array(), $newCookie);
    $campaignHasToken = preg_match('/name="admin_csrf"\s+value="([^"]+)"/', $campaignAddPage['body'], $campaignToken) === 1;
    readinessAssert(
        $campaignListPage['status'] === 200
            && strpos($campaignListPage['body'], 'Danh sách chiến dịch') !== false
            && $campaignAddPage['status'] === 200
            && strpos($campaignAddPage['body'], 'name="scopes[product][]"') !== false
            && $campaignHasToken,
        'Thư viện chiến dịch render danh sách/form, chọn được sản phẩm riêng và có CSRF'
    );
    $campaignTargetCode = 'PTS-CAMP-'.bin2hex(random_bytes(4));
    $d->insert('product', array(
        'code'=>$campaignTargetCode, 'namevi'=>$campaignTargetCode, 'slugvi'=>strtolower($campaignTargetCode),
        'type'=>'san-pham', 'status'=>'hienthi', 'numb'=>0, 'cong_suat'=>0, 'regular_price'=>0,
        'sale_price'=>0, 'discount'=>0, 'id_list'=>0, 'id_cat'=>0, 'id_item'=>0, 'id_sub'=>0,
        'id_brand'=>0, 'date_created'=>time(), 'date_updated'=>0, 'view'=>0
    ));
    $campaignTarget = array('id'=>(int)$d->getLastInsertId());
    $createdProductIds[] = (int)$campaignTarget['id'];
    readinessAssert(!empty($campaignTarget['id']), 'Tạo sản phẩm fixture riêng để kiểm tra phạm vi chiến dịch');
    $campaignFixtureName = 'CODEX-CAMPAIGN-'.bin2hex(random_bytes(4));
    $campaignPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL3WQAAAABJRU5ErkJggg==');
    $campaignSave = readinessHttpMultipart($base.'index.php?com=product_campaign&act=save&type=san-pham', array(
        'admin_csrf'=>$campaignToken[1], 'id'=>'0', 'data[name]'=>$campaignFixtureName, 'data[priority]'=>'77',
        'data[start_at]'=>'', 'data[end_at]'=>'', 'enabled'=>'1', 'scopes[product][]'=>(string)$campaignTarget['id']
    ), 'file', 'campaign-fixture.png', $campaignPng, $newCookie);
    $savedCampaign = $d->rawQueryOne('select id, photo, priority, status from #_product_campaign where name = ? limit 1', array($campaignFixtureName));
    if(!empty($savedCampaign['id']))
    {
        $createdCampaignIds[] = (int)$savedCampaign['id'];
        if(!empty($savedCampaign['photo'])) $createdCampaignPhotos[] = $savedCampaign['photo'];
    }
    $savedCampaignScope = !empty($savedCampaign['id']) ? $d->rawQueryOne('select scope_type, scope_id from #_product_campaign_scope where id_campaign = ? limit 1', array((int)$savedCampaign['id'])) : array();
    $campaignSaveValid = in_array($campaignSave['status'], array(200,302), true)
        && !empty($savedCampaign['id'])
        && strtolower(pathinfo($savedCampaign['photo'], PATHINFO_EXTENSION)) === 'png'
        && (int)$savedCampaign['priority'] === 77
        && $savedCampaign['status'] === 'hienthi'
        && $savedCampaignScope['scope_type'] === 'product'
        && (int)$savedCampaignScope['scope_id'] === (int)$campaignTarget['id'];
    readinessAssert(
        $campaignSaveValid,
        'Lưu campaign upload PNG và phạm vi sản phẩm trong transaction'
            .' [http='.(int)$campaignSave['status'].', row='.(empty($savedCampaign['id']) ? 'missing' : 'present').', scope='.(isset($savedCampaignScope['scope_type']) ? $savedCampaignScope['scope_type'] : 'missing').']'
    );
    $campaignPublicPage = readinessHttp('http://127.0.0.1/tim-kiem/?keyword='.rawurlencode($campaignTargetCode));
    readinessAssert(
        $campaignPublicPage['status'] === 200
            && strpos($campaignPublicPage['body'], $campaignTargetCode) !== false
            && strpos($campaignPublicPage['body'], 'pts-promo-frame') !== false
            && strpos($campaignPublicPage['body'], $savedCampaign['photo']) !== false,
        'Card public resolve đúng khung campaign cho riêng sản phẩm đã gán'
    );
    $campaignDisable = readinessHttp($base.'index.php?com=product_campaign&act=save&type=san-pham', 'POST', array(
        'admin_csrf'=>$campaignToken[1], 'id'=>(int)$savedCampaign['id'],
        'data'=>array('name'=>$campaignFixtureName, 'priority'=>'77', 'start_at'=>'', 'end_at'=>''),
        'scopes'=>array('product'=>array((int)$campaignTarget['id']))
    ), $newCookie);
    $disabledCampaign = $d->rawQueryOne('select status, photo from #_product_campaign where id = ?', array((int)$savedCampaign['id']));
    readinessAssert(
        in_array($campaignDisable['status'], array(200,302), true)
            && (string)$disabledCampaign['status'] === ''
            && $disabledCampaign['photo'] === $savedCampaign['photo'],
        'Tắt campaign không xóa artwork hoặc thay đổi phạm vi sản phẩm khác'
    );
    readinessAssert(
        strpos($adminForm['body'], 'Hình thức thanh toán') !== false
            && strpos($adminForm['body'], 'Watermark sản phẩm') !== false
            && strpos($adminForm['body'], 'Watermark tin tức') !== false,
        'Sidebar có lối vào ba nguồn nội dung dùng chung vừa khôi phục'
    );
    $policyForm = readinessHttp($base.'index.php?com=news&act=add&type=chinh-sach', 'GET', array(), $newCookie);
    readinessAssert(
        $policyForm['status'] === 200
            && strpos($policyForm['body'], 'name="data[contentvi]"') !== false
            && strpos($policyForm['body'], 'name="dataSeo[titlevi]"') !== false
            && strpos($policyForm['body'], 'name="file"') !== false,
        'Form Chính sách dùng đúng một cấu hình nội dung/SEO, có field ảnh đại diện (og:image/card - yêu cầu 2026-09-11)'
    );
    $paymentList = readinessHttp($base.'index.php?com=news&act=man&type=hinh-thuc-thanh-toan', 'GET', array(), $newCookie);
    $paymentForm = readinessHttp($base.'index.php?com=news&act=add&type=hinh-thuc-thanh-toan', 'GET', array(), $newCookie);
    readinessAssert(
        $paymentList['status'] === 200
            && strpos($paymentList['body'], 'Danh sách Hình thức thanh toán') !== false
            && $paymentForm['status'] === 200
            && strpos($paymentForm['body'], 'name="data[namevi]"') !== false
            && strpos($paymentForm['body'], 'name="data[descvi]"') !== false,
        'Danh sách và form Hình thức thanh toán authenticated hoạt động'
    );
    foreach(array('watermark'=>'Watermark sản phẩm', 'watermark-news'=>'Watermark tin tức') as $watermarkType => $watermarkTitle)
    {
        $watermarkForm = readinessHttp($base.'index.php?com=photo&act=photo_static&type='.$watermarkType, 'GET', array(), $newCookie);
        readinessAssert(
            $watermarkForm['status'] === 200
                && strpos($watermarkForm['body'], $watermarkTitle) !== false
                && strpos($watermarkForm['body'], 'name="data[options][watermark][position]"') !== false
                && strpos($watermarkForm['body'], 'name="admin_csrf"') !== false,
            'Form '.$watermarkTitle.' authenticated render đủ cấu hình nâng cao và CSRF'
        );
    }
    $campaignDataStatus = adminProductDataStatus('campaign_active');
    $campaignCount = !empty($campaignDataStatus['sql']) ? $d->rawQueryOne("select count(*) as total from #_product p where p.type = ? and ".$campaignDataStatus['sql'], array('san-pham')) : array();
    readinessAssert(!empty($campaignDataStatus['sql']) && strpos($campaignDataStatus['sql'], 'photo_khuyenmai') !== false && isset($campaignCount['total']), 'Bộ lọc chiến dịch chỉ lấy sản phẩm có khung đang bật và có SQL hợp lệ');
    readinessAssert(preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $adminForm['body'], $authenticatedToken) === 1, 'Phiên authenticated phát hành token mới');
    $authStateAfterRefresh = $d->rawQueryOne('select lastlogin, user_token from #_user where id = ?', array($createdUsers['superadmin']['id']));
    readinessAssert(
        $authStateAfterRefresh['user_token'] === $authStateAfterLogin['user_token']
            && (int)$authStateAfterRefresh['lastlogin'] > $forcedLastLogin,
        'Page load đến hạn cập nhật activity nhưng giữ nguyên token phiên'
    );
    $adminList = readinessHttp($base.'index.php?com=product&act=man&type=san-pham', 'GET', array(), $newCookie);
    $campaignList = readinessHttp($base.'index.php?com=product&act=man&type=san-pham&data_status=campaign_active', 'GET', array(), $newCookie);
    readinessAssert($campaignList['status'] === 200 && strpos($campaignList['body'], 'Đang lọc: Đang có khung chiến dịch') !== false, 'Lối vào chiến dịch mở đúng danh sách sản phẩm đã lọc');
	$specialSearchKeyword = "O'Reilly % Đèn";
	$specialSearchRoutes = array(
		'Kích thước sản phẩm' => 'index.php?com=product&act=man_size&type=san-pham',
		'Màu sản phẩm' => 'index.php?com=product&act=man_color&type=san-pham',
		'Thương hiệu sản phẩm' => 'index.php?com=product&act=man_brand&type=san-pham',
		'Nội dung chính' => 'index.php?com=news&act=man&type=tin-tuc',
		'Nội dung cấp 1' => 'index.php?com=news&act=man_list&type=tin-tuc'
	);
	foreach($specialSearchRoutes as $searchLabel => $searchRoute)
	{
		$searchResponse = readinessHttp($base.$searchRoute.'&keyword='.rawurlencode($specialSearchKeyword), 'GET', array(), $newCookie);
		readinessAssert(
			$searchResponse['status'] === 200
				&& strpos($searchResponse['body'], 'SQLSTATE') === false
				&& strpos($searchResponse['body'], 'PDODbException') === false,
			'Tìm kiếm '.$searchLabel.' xử lý an toàn dấu nháy, wildcard và Unicode'
		);
	}
	foreach(array(
		'phân trang thương hiệu' => 'index.php?com=product&act=man_brand&type=san-pham',
		'phân trang nội dung' => 'index.php?com=news&act=man&type=tin-tuc&per_page=10'
	) as $paginationLabel => $paginationRoute)
	{
		$paginationResponse = readinessHttp($base.$paginationRoute.'&keyword='.rawurlencode('%'), 'GET', array(), $newCookie);
		readinessAssert(
			$paginationResponse['status'] === 200 && strpos($paginationResponse['body'], 'keyword=%25') !== false,
			'Tìm kiếm Admin giữ keyword đã encode qua '.$paginationLabel
		);
	}
    $authStateAfterPages = $d->rawQueryOne('select lastlogin, user_token from #_user where id = ?', array($createdUsers['superadmin']['id']));
    readinessAssert($adminList['status'] === 200, 'Phiên Admin mở liên tiếp hai trang authenticated');
    readinessAssert(
        $authStateAfterPages['user_token'] === $authStateAfterRefresh['user_token']
            && (int)$authStateAfterPages['lastlogin'] === (int)$authStateAfterRefresh['lastlogin'],
        'Các page load trong throttle không xoay token hoặc ghi lại activity'
    );

    foreach(array('banner-top' => 'Banner đầu trang', 'popup' => 'Popup') as $campaignType => $campaignLabel)
    {
        $campaignForm = readinessHttp($base.'index.php?com=photo&act=photo_static&type='.$campaignType, 'GET', array(), $newCookie);
        readinessAssert(
            $campaignForm['status'] === 200
                && strpos($campaignForm['body'], 'pts-campaign-visibility-control') !== false
                && substr_count($campaignForm['body'], 'name="status[hienthi]"') === 1
                && strpos($campaignForm['body'], 'Bỏ chọn rồi bấm Lưu để tắt.') !== false,
            $campaignLabel.' có công tắc bật/tắt dễ thấy và không lặp field trạng thái'
        );
    }

	$emptyCategoryOptions = readinessHttp($base.'api/category.php', 'POST', array('id'=>0, 'level'=>0, 'table'=>'product_cat', 'type'=>'san-pham'), $newCookie);
	readinessAssert($emptyCategoryOptions['status'] === 200 && strpos($emptyCategoryOptions['body'], '<option value="0">Chọn danh mục</option>') !== false, 'API danh mục giữ HTML fragment tương thích khi parent rỗng');
	$invalidCategoryLevel = readinessHttp($base.'api/category.php', 'POST', array('id'=>1, 'level'=>9, 'table'=>'product_cat', 'type'=>'san-pham'), $newCookie);
	$invalidCategoryPayload = json_decode($invalidCategoryLevel['body'], true);
	readinessAssert($invalidCategoryLevel['status'] === 400 && empty($invalidCategoryPayload['ok']) && $invalidCategoryPayload['code'] === 'INVALID_CATEGORY_LEVEL', 'API danh mục trả lỗi JSON 400 cho cấp ngoài allowlist');
	readinessAssertApiEnvelope($invalidCategoryPayload, 'Lỗi validation API dropdown giữ đủ schema envelope');
	$invalidPlaceTable = readinessHttp($base.'api/place.php', 'POST', array('id'=>1, 'level'=>0, 'table'=>'product'), $newCookie);
	$invalidPlacePayload = json_decode($invalidPlaceTable['body'], true);
	readinessAssert($invalidPlaceTable['status'] === 400 && empty($invalidPlacePayload['ok']) && $invalidPlacePayload['code'] === 'INVALID_PLACE_TABLE', 'API địa giới trả lỗi JSON 400 cho bảng ngoài allowlist');
	$invalidCategoryMethod = readinessHttp($base.'api/category.php', 'GET', array(), $newCookie);
	$invalidCategoryMethodPayload = json_decode($invalidCategoryMethod['body'], true);
	readinessAssert($invalidCategoryMethod['status'] === 405 && empty($invalidCategoryMethodPayload['ok']) && $invalidCategoryMethodPayload['code'] === 'METHOD_NOT_ALLOWED', 'API dropdown từ chối phương thức ngoài POST bằng contract rõ ràng');

    foreach(array('single', 'bulk') as $gallerySuffix)
    {
        $d->insert('gallery', array(
            'id_parent'=>987654,
            'photo'=>'',
            'namevi'=>'CODEX-GALLERY-'.$gallerySuffix,
            'numb'=>0,
            'type'=>'san-pham',
            'com'=>'product',
            'kind'=>'man',
            'val'=>'san-pham',
            'status'=>'hienthi',
            'date_created'=>time(),
        ));
        $createdGalleryIds[] = (int)$d->getLastInsertId();
    }
	$galleryRefresh = readinessHttp($base.'api/filer.php', 'POST', array('cmd'=>'refresh', 'id_parent'=>987654, 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham', 'colfiler'=>'col-6'), $newCookie);
	readinessAssert($galleryRefresh['status'] === 200 && strpos($galleryRefresh['body'], 'CODEX-GALLERY-single') !== false && strpos($galleryRefresh['body'], 'CODEX-GALLERY-bulk') !== false, 'API gallery refresh giữ HTML fragment và render đủ album hợp lệ');
	$galleryInvalidCommand = readinessHttp($base.'api/filer.php', 'POST', array('cmd'=>'tu-che', 'id_parent'=>987654, 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham'), $newCookie);
	$galleryInvalidCommandPayload = json_decode($galleryInvalidCommand['body'], true);
	readinessAssert($galleryInvalidCommand['status'] === 400 && empty($galleryInvalidCommandPayload['ok']) && $galleryInvalidCommandPayload['code'] === 'INVALID_GALLERY_COMMAND', 'API gallery từ chối command ngoài allowlist bằng JSON 400');
	readinessAssertApiEnvelope($galleryInvalidCommandPayload, 'Lỗi gallery giữ đủ schema envelope');
	$galleryMissingContext = readinessHttp($base.'api/filer.php', 'POST', array('cmd'=>'refresh', 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham'), $newCookie);
	$galleryMissingContextPayload = json_decode($galleryMissingContext['body'], true);
	readinessAssert($galleryMissingContext['status'] === 400 && empty($galleryMissingContextPayload['ok']) && $galleryMissingContextPayload['code'] === 'MISSING_GALLERY_CONTEXT', 'API gallery từ chối request thiếu parent/hash thay vì trả rỗng');
	$galleryInvalidModule = readinessHttp($base.'api/filer.php', 'POST', array('cmd'=>'refresh', 'id_parent'=>987654, 'com'=>'user', 'kind'=>'man', 'type'=>'san-pham'), $newCookie);
	$galleryInvalidModulePayload = json_decode($galleryInvalidModule['body'], true);
	readinessAssert($galleryInvalidModule['status'] === 400 && empty($galleryInvalidModulePayload['ok']) && $galleryInvalidModulePayload['code'] === 'INVALID_GALLERY_MODULE', 'API gallery từ chối module ngoài allowlist bằng lỗi rõ ràng');

    $galleryInfoUpdate = readinessHttp($base.'api/filer.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'cmd'=>'info', 'info'=>'name', 'value'=>'Tiêu đề ảnh kiểm thử', 'id'=>$createdGalleryIds[0], 'id_parent'=>987654, 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham', 'colfiler'=>'col-6'), $newCookie);
    $galleryInfoPayload = json_decode($galleryInfoUpdate['body'], true);
    $galleryInfoRow = $d->rawQueryOne('select namevi from #_gallery where id = ?', array($createdGalleryIds[0]));
    readinessAssert($galleryInfoUpdate['status'] === 200 && !empty($galleryInfoPayload['ok']) && $galleryInfoPayload['code'] === 'GALLERY_INFO_UPDATED' && $galleryInfoRow['namevi'] === 'Tiêu đề ảnh kiểm thử' && strpos($galleryInfoPayload['data']['html'], 'Tiêu đề ảnh kiểm thử') !== false, 'API gallery xác minh cập nhật tiêu đề và trả HTML mới trong envelope JSON');

    $galleryInvalidInfo = readinessHttp($base.'api/filer.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'cmd'=>'info', 'info'=>'numb', 'value'=>'khong-hop-le', 'id'=>$createdGalleryIds[0], 'id_parent'=>987654, 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham'), $newCookie);
    $galleryInvalidInfoPayload = json_decode($galleryInvalidInfo['body'], true);
    $galleryInfoAfterReject = $d->rawQueryOne('select numb from #_gallery where id = ?', array($createdGalleryIds[0]));
    readinessAssert($galleryInvalidInfo['status'] === 400 && empty($galleryInvalidInfoPayload['ok']) && $galleryInvalidInfoPayload['code'] === 'INVALID_GALLERY_INFO' && (int)$galleryInfoAfterReject['numb'] === 0, 'API gallery từ chối số thứ tự sai định dạng mà không thay dữ liệu');

    $galleryDelete = readinessHttp($base.'api/filer.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'cmd'=>'delete', 'id'=>$createdGalleryIds[0], 'id_parent'=>987654, 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham'), $newCookie);
    $galleryDeletePayload = json_decode($galleryDelete['body'], true);
    $galleryDeletedRow = $d->rawQueryOne('select id from #_gallery where id = ?', array($createdGalleryIds[0]));
    readinessAssert($galleryDelete['status'] === 200 && !empty($galleryDeletePayload['ok']) && $galleryDeletePayload['code'] === 'GALLERY_ITEM_DELETED' && empty($galleryDeletedRow), 'API gallery chỉ xác nhận sau khi xóa đúng ảnh trong album');

    $galleryInvalidBulk = readinessHttp($base.'api/filer.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'cmd'=>'delete-all', 'listid'=>$createdGalleryIds[1].',999999', 'id_parent'=>987654, 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham'), $newCookie);
    $galleryInvalidPayload = json_decode($galleryInvalidBulk['body'], true);
    $galleryRetainedRow = $d->rawQueryOne('select id from #_gallery where id = ?', array($createdGalleryIds[1]));
    readinessAssert($galleryInvalidBulk['status'] === 400 && empty($galleryInvalidPayload['ok']) && $galleryInvalidPayload['code'] === 'INVALID_GALLERY_SELECTION' && !empty($galleryRetainedRow), 'API gallery từ chối toàn bộ danh sách lẫn ID ngoài album và giữ dữ liệu');

    $galleryBulkDelete = readinessHttp($base.'api/filer.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'cmd'=>'delete-all', 'listid'=>(string)$createdGalleryIds[1], 'id_parent'=>987654, 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham'), $newCookie);
    $galleryBulkPayload = json_decode($galleryBulkDelete['body'], true);
    $galleryBulkDeletedRow = $d->rawQueryOne('select id from #_gallery where id = ?', array($createdGalleryIds[1]));
    readinessAssert($galleryBulkDelete['status'] === 200 && !empty($galleryBulkPayload['ok']) && $galleryBulkPayload['code'] === 'GALLERY_ITEMS_DELETED' && (int)$galleryBulkPayload['data']['deleted'] === 1 && empty($galleryBulkDeletedRow), 'API gallery xóa danh sách hợp lệ và trả envelope JSON');

    $galleryOrderIds = array();
    foreach(array(10, 20) as $galleryOrder)
    {
        $d->insert('gallery', array(
            'id_parent'=>987655,
            'photo'=>'',
            'namevi'=>'CODEX-GALLERY-ORDER-'.$galleryOrder,
            'numb'=>$galleryOrder,
            'type'=>'san-pham',
            'com'=>'product',
            'kind'=>'man',
            'val'=>'san-pham',
            'status'=>'hienthi',
            'date_created'=>time(),
        ));
        $galleryOrderIds[] = (int)$d->getLastInsertId();
        $createdGalleryIds[] = (int)$d->getLastInsertId();
    }
    $reversedGalleryIds = array_reverse($galleryOrderIds);
    $galleryOrderUpdate = readinessHttp($base.'api/filer.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'cmd'=>'updateNumb', 'listid'=>$reversedGalleryIds, 'id_parent'=>987655, 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham'), $newCookie);
    $galleryOrderPayload = json_decode($galleryOrderUpdate['body'], true);
    $galleryOrderRows = $d->rawQuery('select id, numb from #_gallery where id in (?, ?)', $galleryOrderIds);
    $galleryOrders = array();
    foreach($galleryOrderRows as $galleryOrderRow) $galleryOrders[(int)$galleryOrderRow['id']] = (int)$galleryOrderRow['numb'];
    readinessAssert($galleryOrderUpdate['status'] === 200 && !empty($galleryOrderPayload['ok']) && $galleryOrderPayload['code'] === 'GALLERY_ORDER_UPDATED' && (int)$galleryOrderPayload['data']['updated'] === 2 && $galleryOrders[$reversedGalleryIds[0]] === 10 && $galleryOrders[$reversedGalleryIds[1]] === 20, 'API gallery cập nhật thứ tự set-based và giữ payload client cũ');

    $galleryInvalidOrder = readinessHttp($base.'api/filer.php', 'POST', array('admin_csrf'=>$authenticatedToken[1], 'cmd'=>'updateNumb', 'listid'=>array($galleryOrderIds[0], $galleryOrderIds[0]), 'id_parent'=>987655, 'com'=>'product', 'kind'=>'man', 'type'=>'san-pham'), $newCookie);
    $galleryInvalidOrderPayload = json_decode($galleryInvalidOrder['body'], true);
    $galleryOrderAfterReject = $d->rawQueryOne('select numb from #_gallery where id = ?', array($galleryOrderIds[0]));
    readinessAssert($galleryInvalidOrder['status'] === 400 && empty($galleryInvalidOrderPayload['ok']) && $galleryInvalidOrderPayload['code'] === 'INVALID_GALLERY_ORDER' && (int)$galleryOrderAfterReject['numb'] === 20, 'API gallery từ chối danh sách thứ tự trùng ID mà không ghi dở');

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
	readinessAssertApiEnvelope($apiMissingPayload, 'Lỗi CSRF giữ đủ schema envelope');
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
	readinessAssertApiEnvelope($invalidSortPayload, 'Lỗi sortable giữ đủ schema envelope');
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
    readinessAssert(
        strpos($scopedBulkPage['body'], 'Hướng dẫn bàn giao: khi nào dùng Excel, khi nào dùng ZIP ảnh?') !== false
            && strpos($scopedBulkPage['body'], 'pts-bulk-zip-action') !== false
            && strpos($scopedBulkPage['body'], 'Không gộp ảnh vào Excel') !== false,
        'Màn bulk có hướng dẫn bàn giao và hàng chọn ZIP căn chỉnh riêng'
    );
    $productPerPage = readinessHttp($base.'index.php?com=product&act=man&type=san-pham&per_page=50', 'GET', array(), $newCookie);
    readinessAssert(
        $productPerPage['status'] === 200
            && strpos($productPerPage['body'], 'id="admin-per-page-product"') !== false
            && strpos($productPerPage['body'], 'class="form-inline float-right admin-per-page-form"') !== false
            && strpos($productPerPage['body'], 'name="per_page"') !== false
            && strpos($productPerPage['body'], '>Áp dụng</button>') !== false
            && strpos($productPerPage['body'], 'value="50" selected') !== false
            && substr_count($productPerPage['body'], 'class="custom-control-input select-checkbox"') === 50,
        'Danh sách Sản phẩm chọn và render đúng 50 dòng'
    );
    readinessAssert(substr_count($productPerPage['body'], 'title="Thêm mới"') === 1, 'Danh sách Sản phẩm chỉ render một thanh nút Thêm mới');
    $productInvalidPerPage = readinessHttp($base.'index.php?com=product&act=man&type=san-pham&per_page=999', 'GET', array(), $newCookie);
    readinessAssert(
        strpos($productInvalidPerPage['body'], 'value="20" selected') !== false
            && substr_count($productInvalidPerPage['body'], 'class="custom-control-input select-checkbox"') === 20,
        'Danh sách Sản phẩm đưa số dòng ngoài allowlist về mặc định 20'
    );
    $newsPerPage = readinessHttp($base.'index.php?com=news&act=man&type=tin-tuc&per_page=50', 'GET', array(), $newCookie);
    readinessAssert(
        $newsPerPage['status'] === 200
            && strpos($newsPerPage['body'], 'id="admin-per-page-news"') !== false
            && strpos($newsPerPage['body'], 'class="form-inline float-right admin-per-page-form"') !== false
            && strpos($newsPerPage['body'], 'value="50" selected') !== false,
        'Danh sách Nội dung/Tin tức có lựa chọn số dòng dùng chung'
    );
    readinessAssert(substr_count($newsPerPage['body'], 'title="Thêm mới"') === 1, 'Danh sách Nội dung/Tin tức không còn render thanh nút trùng ở cuối');
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
            && preg_match('/<body class="[^"]*layout-fixed[^"]*"/', $newsletterList['body']) === 1
            && strpos($newsletterList['body'], 'assets/js/sidebar-state.js?v=') !== false
            && strpos($newsletterList['body'], 'Mới / chưa xử lý') !== false
            && strpos($newsletterList['body'], 'Đang tư vấn') !== false
            && substr_count($newsletterList['body'], $newsletterFixtureCode) >= 2,
        'Pipeline yêu cầu tư vấn render đúng và Admin nạp sidebar cuộn độc lập'
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

    // Bảng đăng ký đại lý gom dữ liệu theo nghiệp vụ và tô màu riêng từng trạng thái.
    $dealerFixtureCode = 'CODEX-DEALER-'.bin2hex(random_bytes(4));
	$dealerFixtureIds = array();
    foreach(array('', '2') as $fixtureStatus)
    {
        $d->insert('newsletter', array(
            'fullname' => 'Đại diện '.$dealerFixtureCode.'-'.$fixtureStatus,
            'company' => 'Công ty '.$dealerFixtureCode,
            'email' => strtolower($dealerFixtureCode).'-'.($fixtureStatus === '' ? 'new' : 'processing').'@example.com',
            'phone' => '0911000000',
            'address' => 'Quận kiểm thử, TP.HCM',
            'notes' => '',
            'confirm_status' => $fixtureStatus,
            'type' => 'dangkynhantin2',
            'status' => $fixtureStatus === '' ? '' : 'hienthi',
            'date_created' => time(),
            'date_updated' => 0,
            'numb' => 0,
        ));
		$dealerFixtureIds[] = (int)$d->getLastInsertId();
		$createdNewsletterIds[] = end($dealerFixtureIds);
    }
    $dealerList = readinessHttp($base.'index.php?com=newsletter&act=man&type=dangkynhantin2&keyword='.urlencode($dealerFixtureCode), 'GET', array(), $newCookie);
    readinessAssert(
        $dealerList['status'] === 200
            && strpos($dealerList['body'], 'pts-newsletter-table--dealer') !== false
            && strpos($dealerList['body'], 'Người đăng ký / Doanh nghiệp') !== false
            && strpos($dealerList['body'], 'Thông tin liên hệ') !== false
            && strpos($dealerList['body'], 'Công ty '.$dealerFixtureCode) !== false
            && strpos($dealerList['body'], 'pts-status-new') !== false
            && strpos($dealerList['body'], 'pts-status-processing') !== false,
        'Bảng đăng ký đại lý cân cột nghiệp vụ và phân màu trạng thái'
    );
	preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $dealerList['body'], $dealerStatusToken);
	$dealerStatusUpdate = readinessHttp($base.'api/newsletter_confirm_status.php', 'POST', array(
		'admin_csrf' => isset($dealerStatusToken[1]) ? $dealerStatusToken[1] : '',
		'id' => $dealerFixtureIds[0],
		'type' => 'dangkynhantin2',
		'value' => '1',
	), $newCookie);
	$dealerStatusPayload = json_decode($dealerStatusUpdate['body'], true);
	$dealerStatusRow = $d->rawQueryOne('select confirm_status from #_newsletter where id = ?', array($dealerFixtureIds[0]));
	readinessAssert(
		$dealerStatusUpdate['status'] === 200
			&& !empty($dealerStatusPayload['ok'])
			&& (string)$dealerStatusRow['confirm_status'] === '1',
		'Cập nhật nhanh trạng thái đại lý giữ CSRF, phân quyền và allowlist'
	);

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

    /* Form sửa sản phẩm phải chọn sẵn danh mục/thương hiệu đang lưu. Thiếu bước
       này thì mỗi lần bấm Lưu sẽ ghi đè id_list/id_cat/id_brand về 0 và sản phẩm
       biến mất khỏi mọi trang danh mục. */
    $taxonomyCat = $d->rawQueryOne("select pc.id, pc.id_list from #_product_cat pc join #_product_list pl on pl.id = pc.id_list where pc.type = ? and pl.type = ? and find_in_set('hienthi',pl.status) order by pc.id limit 1", array('san-pham', 'san-pham'));
    $taxonomyList = !empty($taxonomyCat['id_list']) ? array('id' => (int)$taxonomyCat['id_list']) : array();
    $taxonomyBrand = $d->rawQueryOne("select id from #_product_brand where type = ? order by id limit 1", array('san-pham'));
    if(!empty($taxonomyList['id']) && !empty($taxonomyCat['id']) && !empty($taxonomyBrand['id']))
    {
        $taxonomyCode = 'CODEX-TAX-'.bin2hex(random_bytes(4));
        $d->insert('product', array('code' => $taxonomyCode, 'namevi' => 'Sản phẩm kiểm thử danh mục', 'slugvi' => strtolower($taxonomyCode), 'type' => 'san-pham', 'status' => 'hienthi', 'numb' => 0, 'cong_suat' => 0, 'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'id_list' => (int)$taxonomyList['id'], 'id_cat' => (int)$taxonomyCat['id'], 'id_item' => 0, 'id_sub' => 0, 'id_brand' => (int)$taxonomyBrand['id'], 'date_created' => time(), 'date_updated' => 0, 'view' => 0));
        $taxonomyProductId = (int)$d->getLastInsertId();
        $createdProductIds[] = $taxonomyProductId;
        $editForm = readinessHttp($base.'index.php?com=product&act=edit&type=san-pham&id='.$taxonomyProductId, 'GET', array(), $newCookie);
        $selectedOk = true;
        foreach(array('id_list' => (int)$taxonomyList['id'], 'id_cat' => (int)$taxonomyCat['id'], 'id_brand' => (int)$taxonomyBrand['id']) as $selectId => $expectedValue)
        {
            if(!preg_match('#<select[^>]*id="'.$selectId.'".*?</select>#s', $editForm['body'], $selectMatch)) { $selectedOk = false; break; }
            if(!preg_match('#<option value='.$expectedValue.' selected>#', $selectMatch[0])) { $selectedOk = false; break; }
        }
        readinessAssert($editForm['status'] === 200 && $selectedOk, 'Form sửa sản phẩm chọn sẵn danh mục cấp 1/cấp 2 và thương hiệu đang lưu');
    }

    echo "Admin readiness tests completed.\n";
}
finally
{
    foreach($createdCampaignIds as $campaignId)
    {
        $d->rawQuery('delete from #_product_campaign_scope where id_campaign = ?', array($campaignId));
        $d->rawQuery('delete from #_product_campaign where id = ?', array($campaignId));
    }
    /* Script CLI không đi qua administrator/index.php nên hằng UPLOAD_PHOTO
       (đường dẫn ghi) chưa được bootstrap. Dùng ROOT + đường dẫn public đã biết;
       trước đây chuỗi hằng undefined làm mỗi lượt test sót một PNG fixture. */
    foreach($createdCampaignPhotos as $campaignPhoto) $func->deleteFile(ROOT.'upload/photo/'.$campaignPhoto);
    foreach($createdGalleryIds as $galleryId) $d->rawQuery('delete from #_gallery where id = ?', array($galleryId));
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
