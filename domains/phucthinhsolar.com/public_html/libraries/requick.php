<?php
	/* Request data */
	$com = (!empty($_REQUEST['com'])) ? htmlspecialchars($_REQUEST['com']) : '';
	$act = (!empty($_REQUEST['act'])) ? htmlspecialchars($_REQUEST['act']) : '';
	$type = (!empty($_REQUEST['type'])) ? htmlspecialchars($_REQUEST['type']) : '';
	$kind = (!empty($_REQUEST['kind'])) ? htmlspecialchars($_REQUEST['kind']) : '';
	$val = (!empty($_REQUEST['val'])) ? htmlspecialchars($_REQUEST['val']) : '';
	$variant = (!empty($_GET['variant'])) ? htmlspecialchars($_GET['variant']) : '';
	$id_parent = (!empty($_REQUEST['id_parent'])) ? htmlspecialchars($_REQUEST['id_parent']) : '';
	$id = (!empty($_REQUEST['id'])) ? htmlspecialchars($_REQUEST['id']) : '';
	$curPage = (!empty($_GET['p'])) ? htmlspecialchars($_GET['p']) : 1;
	if(!empty($kind)) $dfgallery = ($kind == 'man_list') ? 'gallery_list' : 'gallery';
	else $dfgallery = '';

	/* Kiểm tra 2 máy đăng nhập cùng 1 tài khoản */
	if(!empty($_SESSION[$loginAdmin]['active']))
	{
		require_once LIBRARIES.'login_security.php';
		$id_user = (int)$_SESSION[$loginAdmin]['id'];
		$timenow = time();

		$row = $d->rawQueryOne("select username, password, lastlogin, user_token from #_user WHERE id = ? limit 0,1",array($id_user));

		$sessionTimeout = !empty($config['login']['timeout']) ? max(300, (int)$config['login']['timeout']) : 3600;
		if(adminSessionInvalid($row, $_SESSION[$loginAdmin], isset($_SESSION[TOKEN]), $timenow, $sessionTimeout))
		{
			if(!empty($_SESSION[TOKEN])) unset($_SESSION[TOKEN]);
			unset($_SESSION[$loginAdmin]);
			$func->redirect("index.php?com=user&act=login");
		}

		if(adminSessionHasConcurrentLogin($row, $_SESSION[$loginAdmin])) $alertlogin = 'Có người đang đăng nhập tài khoản của bạn.';
		else $alertlogin ='';

		/* Giới hạn auth write và giữ token ổn định giữa các request song song. */
		$activityInterval = !empty($config['login']['activity-interval']) ? max(30, (int)$config['login']['activity-interval']) : 60;
		adminTouchSessionActivity($d, $id_user, (int)$row['lastlogin'], $timenow, $activityInterval);
	}

	/* Kiểm tra phân quyền */
	if(!empty($config['permission']['active']) && !empty($_SESSION[$loginAdmin]['active']))
	{
		/* Lấy quyền */
		$_SESSION[$loginAdmin]['permissions'] = array();
		if(!empty($_SESSION[$loginAdmin]['id']))
		{
			$id_permission = $d->rawQueryOne("select id_permission from #_user where id = ? and find_in_set('hienthi',status) limit 0,1",array($_SESSION[$loginAdmin]['id']));
			if(!empty($id_permission['id_permission']))
			{
				$permission = $d->rawQueryOne("select id from #_permission_group where id = ? and find_in_set('hienthi',status) limit 0,1",array($id_permission['id_permission']));
				if(!empty($permission['id']))
				{
					$user_permission = $d->rawQuery("select permission from #_permission where id_permission_group = ?",array($permission['id']));
					if(!empty($user_permission))
					{
						foreach($user_permission as $value)
						{
							$_SESSION[$loginAdmin]['permissions'][] = $value['permission'];
						}
					}
				}
			}
		}

		/* Kiểm tra quyền */
		if($func->checkRole())
		{
			$is_permission = true;

			require_once LIBRARIES.'admin_security.php';
			$hasIdForPermission = !empty($_REQUEST['id']);
			$sum_permission = adminLegacyScreenPermission($com, $act, $variant, $type, $hasIdForPermission);

			if($sum_permission === 'SUPERADMIN_ONLY')
			{
				/* Quản lý tài khoản và nhóm quyền chỉ dành cho tài khoản cấp cao
				   nhất; không nhóm quyền nào cấp được khu vực này. */
				$func->transfer("Chỉ tài khoản quản trị cấp cao mới vào được khu vực này", "index.php", false);
			}
			elseif($sum_permission !== '')
			{
				$grantedPermissions = isset($_SESSION[$loginAdmin]['permissions']) && is_array($_SESSION[$loginAdmin]['permissions'])
					? $_SESSION[$loginAdmin]['permissions']
					: array();
				if(!in_array($sum_permission, $grantedPermissions, true))
				{
					$func->transfer("Bạn không có quyền truy cập vào khu vực này", "index.php", false);
				}
			}
		}
	}

	/* Kiểm tra đăng nhập */
	if($func->checkLoginAdmin() == false && $act != "login")
	{
		$func->redirect("index.php?com=user&act=login");
	}

	require_once LIBRARIES.'admin_security.php';


	/* Kiểm tra POST và CSRF cho các thao tác thay đổi dữ liệu. */
	if(adminLegacyMutationNeedsCsrf($com, $act, isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '', $_POST)) adminRequirePostCsrf();

	/* Delete gallery */
	$func->deleteGallery();

	/* Delete cache */
	$cacheAction = array(
		'save',
		'save_copy',
		'save_list',
		'save_cat',
		'save_item',
		'save_sub',
		'save_brand',
		'save_size',
		'save_color',
		'save_static',
		'save_photo',
		'save_city',
		'save_district',
		'save_ward',
		'update',
		'delete',
		'delete_list',
		'delete_cat',
		'delete_item',
		'delete_sub',
		'delete_brand',
		'delete_city',
		'delete_district',
		'delete_ward'
	);
	if(isset($_POST) && isset($cacheAction) && count($cacheAction) > 0) 
	{
		if(in_array($act, $cacheAction))
		{
			$cache->delete();
			/* Xóa lại sau controller vì thao tác lưu có thể tạo cache mới trước khi DB cập nhật xong. */
			register_shutdown_function(function() use ($cache) {
				$cache->delete();
			});
		}
	}
	
	/* Include sources */
	if(file_exists(SOURCES.$com.'.php')) include SOURCES.$com.".php";
	else $template = "index";
?>
