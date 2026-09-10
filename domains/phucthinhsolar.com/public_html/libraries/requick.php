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

	/* Quay lại đúng trang danh sách (kèm trang/lọc) sau khi thêm/sửa/xoá, thay
	   vì luôn về trang 1 chưa lọc. Dùng chung cho mọi module danh sách+sửa.
	   Lấy giá trị 1 lần rồi gỡ khỏi $_POST['data'] để nó không lọt vào mảng
	   lưu DB của module (từng gây "Unknown column '_return'" khi lưu sản phẩm). */
	$adminReturnRaw = (isset($_POST['data']['_return']) && is_scalar($_POST['data']['_return'])) ? (string) $_POST['data']['_return'] : '';
	if(isset($_POST['data']) && is_array($_POST['data'])) unset($_POST['data']['_return']);
	if(!function_exists('adminReturnUrl'))
	{
		function adminReturnUrl($fallback)
		{
			global $com, $adminReturnRaw;
			$raw = ($adminReturnRaw !== '') ? $adminReturnRaw : (isset($_GET['_return']) ? (string) $_GET['_return'] : '');
			if($raw === '') return $fallback;
			if(strpos($raw, '://') !== false || substr($raw, 0, 2) === '//') return $fallback;
			if(strpos($raw, 'com='.$com) === false || strpos($raw, 'act=man') === false) return $fallback;
			return $raw;
		}
		function adminReturnSuffix()
		{
			global $adminReturnRaw;
			if($adminReturnRaw === '') return '';
			return '&_return='.urlencode($adminReturnRaw);
		}
	}
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
	$isAdminMutationRequest = adminLegacyMutationNeedsCsrf($com, $act, isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '', $_POST);
	if($isAdminMutationRequest) adminRequirePostCsrf();

	/* Delete gallery */
	$func->deleteGallery();

	/* Delete cache - dùng chung đúng 1 định nghĩa "hành động ghi dữ liệu" với CSRF
	   ở trên (adminLegacyMutationNeedsCsrf: mọi $act bắt đầu bằng save, delete
	   hoặc upload, hoặc đúng bằng sync/create), thay vì danh sách $act liệt kê
	   tay trước đây - danh sách
	   cũ chỉ khớp đúng product.php/news.php/places.php, còn contact.php,
	   tags.php, user.php, seopage.php, order.php, gallery.php... dùng tên action
	   khác nên sửa xong phải chờ tới hết TTL cache (2 tiếng) mới lên site thật
	   (audit UX 2026-09-08). */
	if($isAdminMutationRequest)
	{
		$cache->delete();
		/* Xóa lại sau controller vì thao tác lưu có thể tạo cache mới trước khi DB cập nhật xong. */
		register_shutdown_function(function() use ($cache) {
			$cache->delete();
		});
	}
	
	/* Include sources */
	if(file_exists(SOURCES.$com.'.php')) include SOURCES.$com.".php";
	else $template = "index";
?>
