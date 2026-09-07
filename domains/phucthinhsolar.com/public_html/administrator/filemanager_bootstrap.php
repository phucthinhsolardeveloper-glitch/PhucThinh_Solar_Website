<?php
/* Bootstrap bảo mật dùng chung cho CKFinder và elFinder. */
require_once __DIR__.'/../libraries/session_security.php';
startSecureSession();

if(!defined('LIBRARIES')) define('LIBRARIES', dirname(__DIR__).'/libraries/');
require_once LIBRARIES.'config.php';
require_once LIBRARIES.'autoload.php';
new AutoLoad();

$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);
require_once LIBRARIES.'config-type.php';
require_once LIBRARIES.'admin_security.php';

/* CKFinder nạp config trong method scope; export rõ các dependency mà code
   legacy truy cập qua global để bootstrap có cùng hành vi với Admin/API. */
$GLOBALS['config'] = $config;
$GLOBALS['loginAdmin'] = $loginAdmin;
$GLOBALS['d'] = $d;

function adminFileManagerDeny($status, $code, $message)
{
    http_response_code((int)$status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(adminApiEnvelope(false, $message, $code, array(), array('success'=>false)));
    exit();
}

if(!$func->checkLoginAdmin())
    adminFileManagerDeny(401, 'AUTH_REQUIRED', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');

$requestToken = isset($_GET['token']) ? (string)$_GET['token'] : '';
if($requestToken === '' || !defined('TOKEN') || !hash_equals((string)TOKEN, $requestToken) || empty($_SESSION[TOKEN]))
    adminFileManagerDeny(403, 'INVALID_FILE_MANAGER_TOKEN', 'Phiên truy cập kho media không hợp lệ.');

$permission = adminFileManagerContextPermission($_GET);
$role = isset($_SESSION[$loginAdmin]['role']) ? (int)$_SESSION[$loginAdmin]['role'] : 0;
$active = !empty($config['permission']['active']);
$debugDeveloper = !empty($config['website']['debug-developer']);
$permissions = array();
if($active && $role !== 3 && !$debugDeveloper)
{
    $idUser = isset($_SESSION[$loginAdmin]['id']) ? (int)$_SESSION[$loginAdmin]['id'] : 0;
    $permissions = adminLoadPermissions($d, $idUser);
}

if(!adminFileManagerAccessGranted($active, $role, $debugDeveloper, $permission, $permissions))
    adminFileManagerDeny(403, 'FILE_MANAGER_FORBIDDEN', 'Tài khoản không có quyền thay đổi kho media.');

/* CKFinder dùng biến role này để áp ACL; chỉ được đặt sau khi backend đã cấp quyền. */
$_SESSION['CKFinder_UserRole'] = 'media_editor';
