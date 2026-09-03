<?php
	require_once __DIR__.'/../../libraries/session_security.php';
	startSecureSession();
	define('LIBRARIES','../../libraries/');
	define('SOURCES','../sources/');
	define('THUMBS','thumbs');

    require_once LIBRARIES."config.php";
    require_once LIBRARIES.'autoload.php';
    new AutoLoad();
    $d = new PDODb($config['database']);
    $cache = new Cache($d);
	$func = new Functions($d, $cache);
	require_once LIBRARIES."config-type.php";
	require_once LIBRARIES.'admin_security.php';

	if($func->checkLoginAdmin() == false)
	{
		http_response_code(401);
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(adminApiEnvelope(false, 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.', 'AUTH_REQUIRED', array(), array('success'=>false)));
		exit();
	}
?>
