<?php
	require_once __DIR__.'/../../libraries/session_security.php';
	startSecureSession();
	define('LIBRARIES','../../libraries/');

	require_once LIBRARIES.'config.php';
	require_once LIBRARIES.'autoload.php';
	new AutoLoad();
	$d = new PDODb($config['database']);
	$cache = new Cache($d);
	$func = new Functions($d, $cache);
	require_once LIBRARIES.'admin_security.php';
	require_once LIBRARIES.'login_security.php';

	header('Content-Type: application/json; charset=UTF-8');
	if(!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !adminCsrfValid(isset($_POST['admin_csrf']) ? $_POST['admin_csrf'] : ''))
	{
		http_response_code(403);
		$message = 'Phiên đăng nhập không hợp lệ. Vui lòng tải lại trang.';
		echo json_encode(adminApiEnvelope(false, $message, 'INVALID_CSRF', array(), array('success'=>'', 'error'=>$message)));
		exit();
	}

	$username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
	$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
	$error = '';
	$success = '';
	$now = time();
	$ipAttempts = !empty($config['login']['attempt']) ? max(1, (int)$config['login']['attempt']) : 5;
	$accountAttempts = !empty($config['login']['account-attempt']) ? max($ipAttempts, (int)$config['login']['account-attempt']) : 20;
	$delayMinutes = !empty($config['login']['delay']) ? max(1, (int)$config['login']['delay']) : 15;
	$ip = adminLoginClientIp($_SERVER, isset($config['login']['trusted-proxies']) ? $config['login']['trusted-proxies'] : array());
	$descriptors = adminLoginLimitDescriptors($ip, $username, $ipAttempts, $accountAttempts);
	$remainingLock = adminLoginLimitState($d, $descriptors, $delayMinutes, $now);

	if($remainingLock > 0)
	{
		$error = 'Đăng nhập tạm khóa. Vui lòng thử lại sau '.max(1, (int)ceil($remainingLock / 60)).' phút.';
	}
	else if($username === '' || $password === '')
	{
		$error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
	}
	else
	{
		$row = $d->rawQueryOne("select * from #_user where username = ? and find_in_set('hienthi',status) limit 0,1", array($username));
		$validPassword = !empty($row['id']) && hash_equals((string)$row['password'], $func->encryptPassword($config['website']['secret'], $password, $config['website']['salt']));

		if($validPassword)
		{
			$idUser = (int)$row['id'];
			$token = bin2hex(random_bytes(32));
			$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
			$sessionHash = md5(sha1($row['password'].$row['username']));

			$d->rawQuery("insert into #_user_log (id_user, ip, timelog, user_agent) values (?,?,?,?)", array($idUser,$ip,$now,$userAgent));
			$d->rawQuery("update #_user set login_session = ?, lastlogin = ?, user_token = ?, secret_key = ? where id = ?", array($sessionHash,$now,$token,$sessionHash,$idUser));
			adminLoginLimitReset($d, $descriptors, $now);

			session_regenerate_id(true);
			$_SESSION[$loginAdmin] = array(
				'active' => true,
				'id' => $idUser,
				'username' => $row['username'],
				'fullname' => $row['fullname'],
				'phone' => $row['phone'],
				'email' => $row['email'],
				'role' => $row['role'],
				'secret_key' => $row['secret_key'],
				'token' => $sessionHash,
				'password' => $row['password'],
				'login_session' => $sessionHash,
				'login_token' => $token
			);
			$_SESSION[TOKEN] = true;
			unset($_SESSION['admin_csrf_token']);
			adminCsrfToken();
			$success = 'Đăng nhập thành công';
		}
		else
		{
			$remaining = adminLoginLimitFailure($d, $descriptors, $now);
			$error = $remaining > 0 ? 'Thông tin đăng nhập không hợp lệ. Còn '.$remaining.' lần thử.' : 'Đăng nhập tạm khóa. Vui lòng thử lại sau '.$delayMinutes.' phút.';
		}
	}

	$loginOk = $success !== '';
	echo json_encode(adminApiEnvelope($loginOk, $loginOk ? $success : $error, $loginOk ? 'LOGIN_OK' : 'LOGIN_FAILED', array(), array('success'=>$success, 'error'=>$error)));
?>
