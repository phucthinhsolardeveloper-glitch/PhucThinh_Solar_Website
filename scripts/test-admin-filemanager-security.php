<?php
/** HTTP integration test: authentication, RBAC and safe media upload for both file managers. */
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
function fmOk($condition, $message)
{
    global $passes, $failures;
    if($condition) { ++$passes; echo 'PASS: '.$message.PHP_EOL; }
    else { ++$failures; echo 'FAIL: '.$message.PHP_EOL; }
}
function fmRequest($url, $cookie = '', $fields = null)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => array('X-PTS-Permission-Test: enforce'),
        CURLOPT_USERAGENT => 'PTS-FileManager-Security'
    ));
    if($cookie !== '') curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    if($fields !== null)
    {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    }
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr((string)$raw, 0, $headerSize);
    $body = substr((string)$raw, $headerSize);
    curl_close($ch);
    $newCookie = '';
    if(preg_match('/Set-Cookie:\s*(PHPSESSID=[^;]+)/i', $headers, $match)) $newCookie = $match[1];
    return array('status'=>$status, 'body'=>$body, 'cookie'=>$newCookie);
}
function fmLogin($base, $username, $password)
{
    $page = fmRequest($base.'index.php?com=user&act=login');
    $cookie = $page['cookie'];
    $page = fmRequest($base.'index.php?com=user&act=login', $cookie);
    if(!preg_match("/ADMIN_CSRF\s*=\s*'([^']+)'/", $page['body'], $match)) return '';
    $login = fmRequest($base.'api/login.php', $cookie, array(
        'username'=>$username, 'password'=>$password, 'admin_csrf'=>$match[1]
    ));
    if($login['cookie'] !== '') $cookie = $login['cookie'];
    $payload = json_decode($login['body'], true);
    return !empty($payload['success']) ? $cookie : '';
}

$suffix = bin2hex(random_bytes(4));
$password = bin2hex(random_bytes(12));
$groupIds = array();
$userIds = array();
register_shutdown_function(function () use (&$groupIds, &$userIds, $d) {
    foreach($userIds as $id)
    {
        $d->rawQuery('delete from #_user_log where id_user = ?', array($id));
        $d->rawQuery('delete from #_user where id = ?', array($id));
    }
    foreach($groupIds as $id)
    {
        $d->rawQuery('delete from #_permission where id_permission_group = ?', array($id));
        $d->rawQuery('delete from #_permission_group where id = ?', array($id));
    }
});

foreach(array('low'=>'product_man_san-pham', 'editor'=>'product_edit_san-pham') as $kind=>$permission)
{
    $d->insert('permission_group', array('name'=>'FM-'.$kind.'-'.$suffix, 'numb'=>0, 'status'=>'hienthi', 'date_created'=>time()));
    $groupId = (int)$d->getLastInsertId();
    $groupIds[] = $groupId;
    $d->insert('permission', array('id_permission_group'=>$groupId, 'permission'=>$permission));
    $username = 'fm-'.$kind.'-'.$suffix;
    $d->insert('user', array(
        'id_permission'=>$groupId, 'username'=>$username,
        'password'=>$func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']),
        'fullname'=>'File manager security test', 'status'=>'hienthi', 'role'=>1,
        'secret_key'=>bin2hex(random_bytes(16)), 'numb'=>0
    ));
    $userIds[$kind] = (int)$d->getLastInsertId();
    ${$kind.'Username'} = $username;
}

$common = 'token='.rawurlencode(TOKEN).'&fm_com=product&fm_type=san-pham&fm_has_id=1';
$unauth = fmRequest($base.'elfinder/php/connector.php?cmd=open&'.$common.'&fm_act=edit');
fmOk($unauth['status'] === 401, 'elFinder từ chối unauthenticated bằng HTTP 401');
$unauthCk = fmRequest($base.'ckfinder/core/connector/php/connector.php?command=GetFiles&type=Files&currentFolder=%2F&'.$common.'&fm_act=edit');
fmOk($unauthCk['status'] === 401, 'CKFinder từ chối unauthenticated bằng HTTP 401');

$lowCookie = fmLogin($base, $lowUsername, $password);
fmOk($lowCookie !== '', 'Đăng nhập tài khoản low-role');
$low = fmRequest($base.'elfinder/php/connector.php?cmd=open&'.$common.'&fm_act=man', $lowCookie);
fmOk($low['status'] === 403, 'Low-role chỉ có quyền xem bị từ chối file manager bằng HTTP 403');
$lowCkCookie = fmLogin($base, $lowUsername, $password);
$lowCk = fmRequest($base.'ckfinder/core/connector/php/connector.php?command=GetFiles&type=Files&currentFolder=%2F&'.$common.'&fm_act=man', $lowCkCookie);
fmOk($lowCk['status'] === 403, 'Low-role bị từ chối CKFinder bằng HTTP 403');
if($lowCk['status'] !== 403) echo 'INFO: CKFinder low status='.$lowCk['status'].' body='.substr($lowCk['body'], 0, 300).PHP_EOL;

$editorCookie = fmLogin($base, $editorUsername, $password);
fmOk($editorCookie !== '', 'Đăng nhập tài khoản có đúng quyền edit');
$editorQuery = $common.'&fm_act=edit';
$editorCk = fmRequest($base.'ckfinder/core/connector/php/connector.php?command=GetFiles&type=Files&currentFolder=%2F&'.$editorQuery, $editorCookie);
$editorCkPayload = json_decode($editorCk['body'], true);
fmOk($editorCk['status'] === 200 && empty($editorCkPayload['error']), 'Editor có đúng quyền mở CKFinder');
if($editorCk['status'] !== 200 || !empty($editorCkPayload['error'])) echo 'INFO: CKFinder editor status='.$editorCk['status'].' body='.substr($editorCk['body'], 0, 300).PHP_EOL;
$editorCookie = fmLogin($base, $editorUsername, $password);
$open = fmRequest($base.'elfinder/php/connector.php?cmd=open&init=1&'.$editorQuery, $editorCookie);
$openPayload = json_decode($open['body'], true);
fmOk($open['status'] === 200 && !empty($openPayload['cwd']['hash']), 'Editor có quyền mở kho media');
$target = !empty($openPayload['cwd']['hash']) ? $openPayload['cwd']['hash'] : '';

$imagePath = tempnam(sys_get_temp_dir(), 'pts-fm-image-');
$phpPath = tempnam(sys_get_temp_dir(), 'pts-fm-php-');
$htmlPath = tempnam(sys_get_temp_dir(), 'pts-fm-html-');
file_put_contents($imagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
file_put_contents($phpPath, '<?php echo "unsafe";');
file_put_contents($htmlPath, '<html><script>alert(1)</script></html>');
$safeName = 'fm-safe-'.$suffix.'.png';
$renamedName = 'fm-renamed-'.$suffix.'.png';

$upload = fmRequest($base.'elfinder/php/connector.php?'.$editorQuery, $editorCookie, array(
    'cmd'=>'upload', 'target'=>$target,
    'upload[]'=>new CURLFile($imagePath, 'image/png', $safeName)
));
$uploadPayload = json_decode($upload['body'], true);
$uploadedHash = !empty($uploadPayload['added'][0]['hash']) ? $uploadPayload['added'][0]['hash'] : '';
fmOk($upload['status'] === 200 && $uploadedHash !== '', 'Ảnh PNG hợp lệ được upload');

$badPhp = fmRequest($base.'elfinder/php/connector.php?'.$editorQuery, $editorCookie, array(
    'cmd'=>'upload', 'target'=>$target,
    'upload[]'=>new CURLFile($phpPath, 'application/x-httpd-php', 'shell.php')
));
$badHtml = fmRequest($base.'elfinder/php/connector.php?'.$editorQuery, $editorCookie, array(
    'cmd'=>'upload', 'target'=>$target,
    'upload[]'=>new CURLFile($htmlPath, 'text/html', 'unsafe.html')
));
$badPhpPayload = json_decode($badPhp['body'], true);
$badHtmlPayload = json_decode($badHtml['body'], true);
fmOk(empty($badPhpPayload['added']), 'PHP/server-side script bị từ chối');
fmOk(empty($badHtmlPayload['added']), 'HTML nguy hiểm bị từ chối');

if($uploadedHash !== '')
{
    $rename = fmRequest($base.'elfinder/php/connector.php?'.$editorQuery, $editorCookie, array(
        'cmd'=>'rename', 'target'=>$uploadedHash, 'name'=>$renamedName
    ));
    $renamePayload = json_decode($rename['body'], true);
    $renamedHash = !empty($renamePayload['added'][0]['hash']) ? $renamePayload['added'][0]['hash'] : '';
    fmOk($renamedHash !== '', 'Editor có quyền rename media');
    $remove = fmRequest($base.'elfinder/php/connector.php?'.$editorQuery, $editorCookie, array(
        'cmd'=>'rm', 'targets[]'=>$renamedHash !== '' ? $renamedHash : $uploadedHash
    ));
    $removePayload = json_decode($remove['body'], true);
    fmOk(!empty($removePayload['removed']), 'Editor có quyền delete media');
}
else
{
    fmOk(false, 'Editor có quyền rename media');
    fmOk(false, 'Editor có quyền delete media');
}

@unlink($imagePath);
@unlink($phpPath);
@unlink($htmlPath);
echo PHP_EOL.'Kết quả: '.$passes.' pass, '.$failures.' fail'.PHP_EOL;
exit($failures ? 1 : 0);
