<?php
	/* Check HTTP */
	$func->checkHTTP($http, $config['arrayDomainSSL'], $configBase, $configUrl);

	/* Validate URL */
	$func->checkUrl($config['website']['index']);

	/* Check login */
    $func->checkLoginMember();

    /* Mobile detect */
    $deviceType = ($detect->isMobile() || $detect->isTablet()) ? 'mobile' : 'computer';
    // define('TEMPLATE', ($deviceType == 'computer') ? './templates/' : './templates-mobile/');
	define('TEMPLATE', './templates/');

	/* Router */
	require_once LIBRARIES.'product_category_urls.php';
    $router->setBasePath($config['database']['url']);
    /* AltoRouter->map() nhận $route là string (hoặc '*'/'@regex'), không phải
       mảng nhiều pattern - truyền mảng khiến match() gọi strpos($route,'[')
       trên một array. PHP 7.4 chỉ cảnh báo "Array to string conversion" rồi
       coi $route như chuỗi "Array" (route coi như chết, không bao giờ khớp);
       PHP 8 ném TypeError fatal cho MỌI request vì route này được duyệt sớm.
       ADMIN === 'administrator' (xem config.php) nên tách thành 2 route
       string tương đương, giữ đúng hành vi redirect dự định ban đầu. */
    $router->map('GET', ADMIN.'/', function(){
    	global $func, $config;
    	$func->redirect($config['database']['url'].ADMIN."/index.php");
    	exit;
    });
    $router->map('GET', ADMIN, function(){
    	global $func, $config;
    	$func->redirect($config['database']['url'].ADMIN."/index.php");
    	exit;
    });
    $router->map('GET|POST', '', 'index', 'home');
    $router->map('GET|POST', 'index.php', 'index', 'index');
    $router->map('GET|POST', 'sitemap.xml', 'sitemap', 'sitemap');
	/* '?' cuối chuỗi literal (không có [param]) chỉ so bằng strcmp() trong
	   AltoRouter này (xem compileRoute) nên KHÔNG khớp gì cả - 'product/?' bên
	   dưới bị lỗi y hệt từ trước, chưa từng chạy. Dùng delimiter '@' để ép
	   preg_match thật. */
	$router->map('GET', '@^he-thong-dai-ly/?$', function(){ global $configBase; header('Location: '.$configBase.'lien-he/', true, 301); exit; }, 'legacy-he-thong-dai-ly');
	$router->map('GET', '@^ve-chung-toi/?$', function(){ global $configBase; header('Location: '.$configBase.'gioi-thieu/', true, 301); exit; }, 'legacy-about');
	$router->map('GET', '@^product/?$', function(){ global $configBase; header('Location: '.$configBase.'san-pham/', true, 301); exit; }, 'legacy-product');
	$router->map('GET', 'product/[a:product]/?', function($product){ global $d, $configBase; $row=$d->rawQueryOne("select slugvi from #_product where slugvi=? and type=? and find_in_set('hienthi',status) limit 1",array($product,'san-pham')); if(empty($row)){ header('HTTP/1.0 404 Not Found',true,404); include('404.php'); exit; } header('Location: '.$configBase.'san-pham/'.$row['slugvi'],true,301); exit; }, 'legacy-product-detail');
	/* Canonical mới cho landing Giải pháp. Các URL bare slug và solution/...
	   vẫn được resolver bên dưới nhận rồi chuyển 301, nên không làm mất link cũ. */
	$router->map('GET|POST', 'giai-phap/[a:solution]/?', 'solutionLanding', 'solution-landing');
	$router->map('GET', 'danh-muc-san-pham/[a:list]/[a:cat]/[a:item]/[a:sub]/?', 'productHierarchy', 'product-hierarchy-4');
	$router->map('GET', 'danh-muc-san-pham/[a:list]/[a:cat]/[a:item]/?', 'productHierarchy', 'product-hierarchy-3');
	$router->map('GET', 'danh-muc-san-pham/[a:list]/[a:cat]/?', 'productHierarchy', 'product-hierarchy-2');
    $router->map('GET|POST', '[a:com]', 'allpage', 'show');
	$router->map('GET|POST', '[a:com]/', 'allpage', 'show1');
    $router->map('GET|POST', 'account/[a:action]', 'account', 'account');
	$router->map('GET|POST', '[a:sub]/[a:com]/', 'allpage', 'show2');
	$router->map('GET|POST', '[a:sub]/[a:com]', 'allpage', 'show3');
    $router->map('GET|POST', '[a:com]/[a:lang]/', 'allpagelang', 'lang');
    $router->map('GET', THUMBS.'/[i:w]x[i:h]x[i:z]/[**:src]', function($w,$h,$z,$src){
    	global $func;
    	$func->createThumb($w, $h, $z, $src, null, THUMBS);
    },'thumb');
    $router->map('GET', WATERMARK.'/product/[i:w]x[i:h]x[i:z]/[**:src]', function($w,$h,$z,$src){
    	global $func, $cache;
    	$wtm = $cache->get("select status, photo, options from #_photo where type = ? and act = ? limit 0,1", array('watermark', 'photo_static'), 'fetch', 7200);
    	$func->createThumb($w, $h, $z, $src, $wtm, "product");
    },'watermark');
    $router->map('GET', WATERMARK.'/news/[i:w]x[i:h]x[i:z]/[**:src]', function($w,$h,$z,$src){
    	global $func, $cache;
    	$wtm = $cache->get("select status, photo, options from #_photo where type = ? and act = ? limit 0,1", array('watermark-news', 'photo_static'), 'fetch', 7200);
    	$func->createThumb($w, $h, $z, $src, $wtm, "news");
    },'watermarkNews');

    /* Router match */
    $match = $router->match();

    /* Router check */
	if(is_array($match))
	{
		if(is_callable($match['target']))
		{
			call_user_func_array($match['target'], $match['params']); 
		}
		else
		{
			$com = (!empty($match['params']['com'])) ? htmlspecialchars($match['params']['com']) : htmlspecialchars($match['target']);
			$getPage = !empty($_GET['p']) ? htmlspecialchars($_GET['p']) : 1;
		}
	}
	else
	{
		header('HTTP/1.0 404 Not Found', true, 404);
		include("404.php");
		exit;
	}

    /* Setting */
    $sqlCache = "select * from #_setting";
    $setting = $cache->get($sqlCache, null, 'fetch', 7200);
    $optsetting = (!empty($setting['options'])) ? json_decode($setting['options'],true) : null;

    /* setting_sort */
    $setting_sort = $cache->get("select * from #_setting_sort", null, 'fetch', 7200);
    $optsetting_sort = (!empty($setting_sort['options'])) ? json_decode($setting_sort['options'],true) : null;
    /* Lang */
    if(!empty($match['params']['lang'])) $_SESSION['lang'] = $match['params']['lang'];
    else if(empty($_SESSION['lang']) && empty($match['params']['lang'])) $_SESSION['lang'] = $optsetting['lang_default'];
    $lang = $_SESSION['lang'];
    /* Check lang */
    $weblang = (!empty($config['website']['lang'])) ? array_keys($config['website']['lang']) : array();

    if(!in_array($lang, $weblang))
    {
    	$_SESSION['lang'] = 'vi';
    	$lang = $_SESSION['lang'];
    }

    /* Slug lang */
    $sluglang = 'slugvi';

    /* SEO Lang */
    $seolang = "vi";

    /* Require datas lang */
    require_once LIBRARIES."lang/$lang.php";

	/* Tối ưu link */
	$requick = array(
		/* Sản phẩm */
		array("tbl" => "product_list", "field" => "idl", "source" => "product", "com" => "san-pham", "type" => "san-pham"),
		array("tbl" => "product_cat", "field" => "idc", "source" => "product", "com" => "san-pham", "type" => "san-pham"),
		array("tbl" => "product_item", "field" => "idi", "source" => "product", "com" => "san-pham", "type" => "san-pham"),
		array("tbl" => "product_sub", "field" => "ids", "source" => "product", "com" => "san-pham", "type" => "san-pham"),
		array("tbl" => "product_brand", "field" => "idb", "source" => "product", "com" => "san-pham", "type" => "san-pham"),
		array("tbl" => "product", "field" => "id", "source" => "product", "com" => "san-pham", "type" => "san-pham", "menu" => true),
	
		/* Thư viện ảnh */
		array("tbl" => "product", "field" => "id", "source" => "product", "com" => "thu-vien-anh", "type" => "thu-vien-anh", "menu" => true),

		/* Video */
		array("tbl" => "photo", "field" => "id", "source" => "video", "com" => "video", "type" => "video", "menu" => true),

		/* Tin tức */
		array("tbl" => "news_list", "field" => "idl", "source" => "news", "com" => "tin-tuc", "type" => "tin-tuc"),
		array("tbl" => "news_cat", "field" => "idc", "source" => "news", "com" => "tin-tuc", "type" => "tin-tuc"),
		array("tbl" => "news_item", "field" => "idi", "source" => "news", "com" => "tin-tuc", "type" => "tin-tuc"),
		array("tbl" => "news_sub", "field" => "ids", "source" => "news", "com" => "tin-tuc", "type" => "tin-tuc"),
		array("tbl" => "news", "field" => "id", "source" => "news", "com" => "tin-tuc", "type" => "tin-tuc", "menu" => true),

		/* Dịch vụ */
		array("tbl" => "news_list", "field" => "idl", "source" => "news", "com" => "giai-phap", "type" => "giai-phap"),
		array("tbl" => "news_cat", "field" => "idc", "source" => "news", "com" => "giai-phap", "type" => "giai-phap"),
		array("tbl" => "news_item", "field" => "idi", "source" => "news", "com" => "giai-phap", "type" => "giai-phap"),
		array("tbl" => "news_sub", "field" => "ids", "source" => "news", "com" => "giai-phap", "type" => "giai-phap"),
		array("tbl" => "news", "field" => "id", "source" => "news", "com" => "giai-phap", "type" => "giai-phap", "menu" => true),

		/* Dự án */
		array("tbl" => "news_list", "field" => "idl", "source" => "news", "com" => "du-an", "type" => "du-an"),
		array("tbl" => "news_cat", "field" => "idc", "source" => "news", "com" => "du-an", "type" => "du-an"),
		array("tbl" => "news_item", "field" => "idi", "source" => "news", "com" => "du-an", "type" => "du-an"),
		array("tbl" => "news_sub", "field" => "ids", "source" => "news", "com" => "du-an", "type" => "du-an"),
		array("tbl" => "news", "field" => "id", "source" => "news", "com" => "du-an", "type" => "du-an", "menu" => true),

		/* Bài viết */
		array("tbl" => "news", "field" => "id", "source" => "news", "com" => "chinh-sach", "type" => "chinh-sach", "menu" => false),

		/* Trang tĩnh */
		array("tbl" => "static", "field" => "id", "source" => "static", "com" => "gioi-thieu", "type" => "gioi-thieu", "menu" => true),

		/* Liên hệ */
		array("tbl" => "", "field" => "id", "source" => "", "com" => "lien-he", "type" => "", "menu" => true),
	);

	/* Find data */
	if($_SERVER['REQUEST_METHOD'] === 'GET')
	{
		$requestPath = trim(parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH), '/');
		try { $redirectRow = $d->rawQueryOne("select entity_table, entity_id from #_url_redirect where old_path = ? limit 1", array($requestPath)); }
		catch(Exception $redirectException) { $redirectRow = array(); }
		if(!empty($redirectRow))
		{
			$redirectPath = publicProductEntityPath($d, $redirectRow['entity_table'], $redirectRow['entity_id'], $lang);
			if($redirectPath !== '' && trim($redirectPath, '/') !== $requestPath)
			{
				header('Location: '.$configBase.$redirectPath, true, 301);
				exit;
			}
		}
	}
	if($com === 'productHierarchy')
	{
		$segments = array();
		foreach(array('list','cat','item','sub') as $segment) if(!empty($match['params'][$segment])) $segments[] = $match['params'][$segment];
		$hierarchy = publicResolveProductCategoryPath($d, $segments, $lang);
		if(empty($hierarchy)) { header('HTTP/1.0 404 Not Found', true, 404); include('404.php'); exit; }
		foreach(array('ids','idi','idc','idl') as $field) if(isset($hierarchy[$field])) { $_GET[$field] = $hierarchy[$field]; break; }
		$com = 'san-pham';
	}
	if($com === 'solutionLanding')
	{
		$solutionSlug = !empty($match['params']['solution']) ? $match['params']['solution'] : '';
		$solutionRow = $d->rawQueryOne("select id from #_news where $sluglang = ? and type = ? and find_in_set('hienthi',status) limit 1", array($solutionSlug, 'giai-phap'));
		if(empty($solutionRow)) { header('HTTP/1.0 404 Not Found', true, 404); include('404.php'); exit; }
		$_GET['id'] = (int)$solutionRow['id'];
		$com = 'giai-phap';
	}
	if(!empty($com) && !in_array($com, ['tim-kiem', 'account', 'sitemap']))
	{
		require_once LIBRARIES.'public_route_resolver.php';
		$routeMatch = publicRouteResolveSlug($d, $com, $sluglang, $requick);
		if(!empty($routeMatch['id']))
		{
			$productHierarchyTables = array('idl'=>'product_list','idc'=>'product_cat','idi'=>'product_item','ids'=>'product_sub');
			if(isset($productHierarchyTables[$routeMatch['route_field']]) && $routeMatch['route_field'] !== 'idl' && $_SERVER['REQUEST_METHOD'] === 'GET')
			{
				$legacyPath = publicProductEntityPath($d, $productHierarchyTables[$routeMatch['route_field']], $routeMatch['id'], 'vi');
				if($legacyPath !== '')
				{
					$query = !empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '';
					header('Location: '.$configBase.$legacyPath.$query, true, 301);
					exit;
				}
			}
			$_GET[$routeMatch['route_field']] = $routeMatch['id'];
			$com = $routeMatch['route_com'];
			/* Gom các URL chi tiết Giải pháp cũ (bare slug, solution/slug) về
			   một cấu trúc rõ hierarchy. Category cũ vẫn giữ nguyên để rollback. */
			if($_SERVER['REQUEST_METHOD'] === 'GET' && $com === 'giai-phap' && $routeMatch['route_field'] === 'id')
			{
				$solutionCanonical = $d->rawQueryOne("select $sluglang from #_news where id = ? and type = ? limit 1", array((int)$routeMatch['id'], 'giai-phap'));
				if(!empty($solutionCanonical[$sluglang]))
				{
					$solutionPath = 'giai-phap/'.$solutionCanonical[$sluglang].'/';
					if(trim($solutionPath, '/') !== trim($requestPath, '/'))
					{
						header('Location: '.$configBase.$solutionPath, true, 301);
						exit;
					}
				}
			}
		}
	}

	/* Switch coms */
	switch($com)
	{
		case 'lien-he':
			$source = "contact";
			$template = "contact/contact";
			$seo->set('type','object');
			$titleMain = lienhe;
			break;

		case 'gioi-thieu':
		case 've-chung-toi':
			$source = "about";
			$template = "about/about";
			$type = 'gioi-thieu';
			$seo->set('type','article');
			$titleMain = "Giới thiệu";
			break;

		case 'dai-ly':
			$source = "daily";
			$template = "daily/daily";
			$type = 'dai-ly';
			$seo->set('type','article');
			$titleMain = "Hợp tác đại lý";
			break;

		case 'tin-tuc':
			$source = "news";
			$template = isset($_GET['id']) ? "news/news_detail" : "news/news";
			$seo->set('type',isset($_GET['id']) ? "article" : "object");
			$type = $com;
			$titleMain = "Kiến thức";
			break;

		// case 'dich-vu':
		// 	$source = "news";
		// 	$template = isset($_GET['id']) ? "news/service_detail" : "news/service";
		// 	$seo->set('type',isset($_GET['id']) ? "article" : "object");
		// 	$type = $com;
		// 	$titleMain = "Dịch vụ";
		// 	break;

		case 'giai-phap':
			$source = "news";
			$template = "news/service_list";
			if(isset($_GET['id']))
			{
				$template = "news/service_detail";
			}
			if(isset($_GET['idl']))
			{
				$template = "news/service";
			}
			$seo->set('type',isset($_GET['id']) ? "article" : "object");
			$type = $com;
			$titleMain = "Giải pháp";
			break;

		case 'du-an':
		case 'du-an-da-thuc-hien':
			$source = "news";
			$template = isset($_GET['id']) ? "news/project_detail" : "news/project";
			$seo->set('type',isset($_GET['id']) ? "article" : "object");
			$type = 'du-an';
			$titleMain = "Dự án đã thực hiện";
			break;

		case 'chinh-sach':
			$source = "news";
			$template = isset($_GET['id']) ? "news/news_detail" : "";
			$seo->set('type','article');
			$type = $com;
			$titleMain = null;
			break;

		case 'thuong-hieu':
			$source = "product";
			$template = "product/product";
			$seo->set('type','object');
			$type = 'san-pham';
			$titleMain = null;
			break;

		case 'san-pham':
			$source = "product";
			/* Landing catalog dùng chung một lưới "Tất cả sản phẩm" với category
			   rail và bộ lọc. URL danh mục con vẫn giữ nguyên; template cũ chia
			   nhiều block không còn là entry point chính. */
			$template = "product/product";
			if(isset($_GET['id']))
			{
				$template = "product/product_detail";
			}
			if(isset($_GET['idl']) || isset($_GET['idc']) || isset($_GET['idi']) || isset($_GET['ids']))
			{
				$template = "product/product";
			}
			$seo->set('type',isset($_GET['id']) ? "article" : "object");
			$type = $com;
			$titleMain = "Sản phẩm";
			break;

		case 'tim-kiem':
			$source = "search";
			$template = "product/product";
			$seo->set('type','object');
			$titleMain = timkiem;
			break;

		// case 'tags-san-pham':
		// 	$source = "tags";
		// 	$template = "product/product";
		// 	$type = $urlType;
		// 	$table = $urlTblTag;
		// 	$seo->set('type','object');
		// 	$titleMain = null;
		// 	break;

		// case 'tags-tin-tuc':
		// 	$source = "tags";
		// 	$template = "news/news";
		// 	$type = $urlType;
		// 	$table = $urlTblTag;
		// 	$seo->set('type','object');
		// 	$titleMain = null;
		// 	break;

		case 'thu-vien-anh':
			$source = "product";
			$template = isset($_GET['id']) ? "album/album_detail" : "album/album";
			$seo->set('type',isset($_GET['id']) ? "article" : "object");
			$type = $com;
			$titleMain = thuvienanh;
			break;
		
		case 'video':
			$source = "video";
			$template = "video/video";
			$type = $com;
			$seo->set('type','object');
			$titleMain = "Video";
			break;

		case 'gio-hang':
			$source = "order";
			$template = 'order/order';
			$titleMain = giohang;
			$seo->set('type','object');
			break;

		case 'cam-on':
			$source = "thankyou";
			$template = 'order/thanks';
			$titleMain = "Đặt hàng thành công";
			$seo->set('type','object');
			break;

		case 'account':
			$source = "user";
			break;

		case 'ngon-ngu':
			if(isset($lang))
			{
				switch($lang)
				{
					case 'vi':
						$_SESSION['lang'] = 'vi';
						break;
					case 'en':
						$_SESSION['lang'] = 'en';
						break;
					default:
						$_SESSION['lang'] = 'vi';
						break;
				}
			}
			$func->redirect($_SERVER['HTTP_REFERER']);
			break;

		case 'sitemap':
			include_once LIBRARIES."sitemap.php";
			exit();
			
		case '':
		case 'index':
			$source = "index";
			$template ="index/index";
			$seo->set('type','website');
			break;

		default: 
			header('HTTP/1.0 404 Not Found', true, 404);
			include("404.php");
			exit();
	}

	/* Require datas for all page */
	require_once SOURCES."allpage.php";

	/* Include sources */
	if(!empty($source))
    {
        include SOURCES.$source.".php";
    }

    /* Include sources */
	if(empty($template))
	{
		header('HTTP/1.0 404 Not Found', true, 404);
		include("404.php");
		exit();
	}
?>
