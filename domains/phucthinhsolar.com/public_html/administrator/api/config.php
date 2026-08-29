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

    if($func->checkLoginAdmin()==false) { die(); }
    require_once LIBRARIES.'admin_security.php';
?>
