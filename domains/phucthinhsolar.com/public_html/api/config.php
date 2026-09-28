<?php
	require_once __DIR__.'/../libraries/session_security.php';
	startSecureSession();
	define('LIBRARIES','../libraries/');
    define('THUMBS','thumbs');
    define('WATERMARK','watermark');

	if(empty($_SESSION['lang'])) $_SESSION['lang'] = 'vi';
    $lang = $_SESSION['lang'];

    require_once LIBRARIES."config.php";
    require_once LIBRARIES."form_security.php";
    require_once LIBRARIES."public_pricing.php";
    require_once LIBRARIES."public_watermark.php";
    require_once LIBRARIES."product_power_filter.php";
    require_once LIBRARIES."product_price_filter.php";
    require_once LIBRARIES."product_need_filter.php";
    require_once LIBRARIES."product_sort.php";
    require_once LIBRARIES.'autoload.php';
    new AutoLoad();
    $d = new PDODb($config['database']);
    $cache = new Cache($d);
    $func = new Functions($d, $cache);
    $cart = new Cart($d);
    require_once LIBRARIES."lang/$lang.php";

    /* Slug lang */
    $sluglang = 'slugvi';

    /* Setting */
    $sqlCache = "select * from #_setting";
    $setting = $cache->get($sqlCache, null, 'fetch', 7200);
    $optsetting = (!empty($setting['options'])) ? json_decode($setting['options'],true) : null;
?>
