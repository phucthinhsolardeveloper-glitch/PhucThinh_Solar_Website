<?php
	if(!defined('SOURCES')) die("Error");

	/* Route account khớp cả khi không có action (/account/), lúc đó
	   $match['params']['action'] không tồn tại. Đọc thẳng sẽ sinh Notice, mà
	   Notice là output — nên header('404') ở nhánh default bên dưới không gửi
	   được nữa và trang trả HTTP 200 kèm nội dung 404. Đó là soft 404: công cụ
	   tìm kiếm coi đây là trang hợp lệ và index nó. */
	$action = isset($match['params']['action']) ? htmlspecialchars($match['params']['action']) : '';

	switch($action)
	{
		case 'dang-nhap':
			$titleMain = dangnhap;
			$template = "account/login";
			if(!empty($_SESSION[$loginMember]['active'])) $func->transfer("Trang không tồn tại",$configBase, false);
			if(!empty($_POST['login-user'])) loginMember();
			break;

		case 'dang-ky':
			$titleMain = dangky;
			$template = "account/registration";
			if(!empty($_SESSION[$loginMember]['active'])) $func->transfer("Trang không tồn tại",$configBase, false);
			if(!empty($_POST['registration-user'])) signupMember();
			break;

		case 'quen-mat-khau':
			$titleMain = quenmatkhau;
			$template = "account/forgot_password";
			if(!empty($_SESSION[$loginMember]['active'])) $func->transfer("Trang không tồn tại",$configBase, false);
			if(!empty($_POST['forgot-password-user'])) forgotPasswordMember();
			break;

		case 'kich-hoat':
			$titleMain = kichhoat;
			$template = "account/activation";
			if(!empty($_SESSION[$loginMember]['active'])) $func->transfer("Trang không tồn tại",$configBase, false);
			checkActivationMember();
			break;

		case 'thong-tin':
			$titleMain = capnhatthongtin;
			$template = "account/info";
			if(empty($_SESSION[$loginMember]['active'])) $func->transfer("Trang không tồn tại",$configBase, false);
			infoMember();
			break;

		case 'dang-xuat':
			if(empty($_SESSION[$loginMember]['active'])) $func->transfer("Trang không tồn tại",$configBase, false);
			logoutMember();
			break;

		case 'danh-sach-don-hang':

			$titleMain = capnhatthongtin;
			$template = "account/order_list";

			if(empty($_SESSION[$loginMember]['active'])) $func->transfer("Trang không tồn tại",$configBase, false);
			$action = (isset($_REQUEST['action'])) ? addslashes($_REQUEST['action']) : "";

			order_list();

			switch ($action) {

				case 'edit':

					edit_order();

					$title = $title_seo = 'Chi tiết đơn hàng';

					$template = 'account/add_order';

					break;

				case 'return':

					return_order();

					break;

				default:

					$template = 'account/order_list';

					break;

			}

			break;

		case 'don-hang-doi-tra':
			$titleMain = capnhatthongtin;
			$template = "account/order_return_list";

			if(empty($_SESSION[$loginMember]['active'])) $func->transfer("Trang không tồn tại",$configBase, false);

			order_return_list();

			break;

		case 'don-hang-huy':
			$titleMain = capnhatthongtin;
			$template = "account/order_cancel_list";

			if(empty($_SESSION[$loginMember]['active'])) $func->transfer("Trang không tồn tại",$configBase, false);

			order_cancel_list();

			break;
		
		default:
			header('HTTP/1.0 404 Not Found', true, 404);
			include("404.php");
			exit();
	}

	/* SEO */
	$seo->set('title',$titleMain);

	/* breadCrumbs */
	if(!empty($titleMain)) $breadcr->set('',$titleMain);
	$breadcrumbs = $breadcr->get();

	function infoMember()
	{
		global $d, $func, $flash, $rowDetail, $configBase, $loginMember;

		$iduser = $_SESSION[$loginMember]['id'];

		if($iduser)
		{
			$rowDetail = $d->rawQueryOne("select fullname, username, gender, birthday, email, phone, address from #_member where id = ? limit 0,1",array($iduser));

		    if(!empty($_POST['info-user']))
		    {
				if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : ''))
					$func->transfer("Phiên cập nhật thông tin không hợp lệ. Vui lòng tải lại trang.", $configBase."account/thong-tin", false);
		    	$message = '';
				$response = array();
		    	$old_password = (!empty($_POST['old-password'])) ? $_POST['old-password'] : '';
		    	$old_passwordMD5 = md5($old_password);
	            $new_password = (!empty($_POST['new-password'])) ? $_POST['new-password'] : '';
	            $new_passwordMD5 = md5($new_password);
	            $new_password_confirm = (!empty($_POST['new-password-confirm'])) ? $_POST['new-password-confirm'] : '';
	            $fullname = (!empty($_POST['fullname'])) ? htmlspecialchars($_POST['fullname']) : '';
	            $email = (!empty($_POST['email'])) ? htmlspecialchars($_POST['email']) : '';
	            $phone = (!empty($_POST['phone'])) ? htmlspecialchars($_POST['phone']) : 0;
	            $address = (!empty($_POST['address'])) ? htmlspecialchars($_POST['address']) : '';
	            $gender = (!empty($_POST['gender'])) ? htmlspecialchars($_POST['gender']) : 0;
	            $birthday = (!empty($_POST['birthday'])) ? htmlspecialchars($_POST['birthday']) : '';

		        /* Valid data */
				if(empty($fullname))
				{
					$response['messages'][] = 'Họ tên không được trống';
				}

				if(!empty($old_password))
		        {
		        	$isWrongPass = false;
		            $row = $d->rawQueryOne("select id from #_member where id = ? and password = ? limit 0,1",array($iduser,$old_passwordMD5));

		            if(empty($row['id']))
		            {
		            	$isWrongPass = true;
		            	$response['messages'][] = 'Mật khẩu cũ không chính xác';
		            }
		            else if(empty($new_password))
		            {
		            	$isWrongPass = true;
		            	$response['messages'][] = 'Mật khẩu mới không được trống';
		            }
		            else if(!empty($new_password) && empty($new_password_confirm))
		            {
		            	$isWrongPass = true;
		            	$response['messages'][] = 'Xác nhận mật khẩu mới không được trống';
		            }
		            else if($new_password != $new_password_confirm)
		            {
		            	$isWrongPass = true;
		            	$response['messages'][] = 'Mật khẩu mới và xác nhận mật khẩu mới không chính xác';
		            }
		        }

				if(empty($gender))
				{
					$response['messages'][] = 'Chưa chọn giới tính';
				}

				if(empty($birthday))
				{
					$response['messages'][] = 'Ngày sinh không được trống';
				}
				
				if(!empty($birthday) && !$func->isDate($birthday))
				{
					$response['messages'][] = 'Ngày sinh không hợp lệ';
				}

				if(empty($email))
				{
					$response['messages'][] = 'Email không được trống';
				}

				if(!empty($email))
				{
					if(!$func->isEmail($email))
					{
						$response['messages'][] = 'Email không hợp lệ';
					}

					if($func->checkAccount($email, 'email', 'member', $iduser))
					{
						$response['messages'][] = 'Email đã tồn tại';
					}
				}

				if(!empty($phone) && !$func->isPhone($phone))
				{
					$response['messages'][] = 'Số điện thoại không hợp lệ';
				}

				if(empty($address))
				{
					$response['messages'][] = 'Địa chỉ không được trống';
				}

				if(!empty($response))
				{
					/* Flash data */
					$flash->set('fullname', $fullname);
					$flash->set('gender', $gender);
					$flash->set('birthday', $birthday);
					$flash->set('email', $email);
					$flash->set('phone', $phone);
					$flash->set('address', $address);

					/* Errors */
					$response['status'] = 'danger';
					$message = base64_encode(json_encode($response));
					$flash->set('message', $message);
					$func->redirect($configBase."account/thong-tin");
				}

				if(!empty($old_password) && empty($isWrongPass))
		        {
		        	$data['password'] = $new_passwordMD5;
		        }

		        $data['fullname'] = $fullname;
				$data['email'] = $email;
				$data['phone'] = $phone;
				$data['address'] = $address;
				$data['gender'] = $gender;
				$data['birthday'] = strtotime(str_replace("/","-",$birthday));

				$d->where('id', $iduser);
				if($d->update('member',$data))
				{
					if($old_password)
					{
						unset($_SESSION[$loginMember]);
						setcookie('login_member_id',"",-1,'/');
						setcookie('login_member_session',"",-1,'/');
						$func->transfer("Cập nhật thông tin thành công",$configBase."account/dang-nhap");
					}
					else
					{
						$func->transfer("Cập nhật thông tin thành công",$configBase."account/thong-tin");
					}
				}
				else
				{
					$func->transfer("Cập nhật thông tin thất bại",$configBase."account/thong-tin", false);
				}
		    }
		}
		else
		{
			$func->transfer("Trang không tồn tại",$configBase, false);
		}
	}

	function checkActivationMember()
	{
		global $d, $func, $flash, $rowDetail, $configBase;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(!empty($_POST['activation-user']))
		{
			if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : ''))
				$func->transfer("Phiên kích hoạt không hợp lệ. Vui lòng tải lại trang.", $configBase."account/kich-hoat?id=".(int)$id, false);
			/* Data */
			$message = '';
			$response = array();
			$confirm_code = (!empty($_POST['confirm_code'])) ? htmlspecialchars($_POST['confirm_code']) : '';

			/* Valid data */
			if(empty($id))
			{
				$response['messages'][] = 'Người dùng không tồn tại';
			}
			else
			{
				$rowDetail = $d->rawQueryOne("select status, confirm_code, id from #_member where id = ? limit 0,1",array($id));

				if(empty($rowDetail))
		        {
		        	$response['messages'][] = 'Tài khoản của bạn không tồn tại';
		        }
		        else if(!empty($rowDetail['status']) && strpos($rowDetail['status'], 'hienthi') !== false)
		        {
		        	$func->transfer("Trang không tồn tại",$configBase, false);
		        }
		        else
		        {
		        	if(empty($confirm_code))
					{
						$response['messages'][] = 'Mã xác nhận không được trống';
					}

					if(!empty($confirm_code) && ($rowDetail['confirm_code'] != $confirm_code))
					{
						$response['messages'][] = 'Mã xác nhận không đúng. Vui lòng nhập lại mã xác nhận.';
					}
		        }
			}

			if(!empty($response))
			{
				$response['status'] = 'danger';
				$message = base64_encode(json_encode($response));
				$flash->set("message", $message);
				$func->redirect($configBase."account/kich-hoat?id=".$id);
			}
			
	        /* Save data */
			$data['status'] = 'hienthi';
			$data['confirm_code'] = '';
			$d->where('id', $id);
			if($d->update('member',$data))
			{
				$func->transfer("Kích hoạt tài khoản thành công.",$configBase."account/dang-nhap");
			}
		}
		else
		{
			/* Valid data */
			if(empty($id))
			{
				$func->transfer("Trang không tồn tại",$configBase, false);
			}
			else
			{
				$rowDetail = $d->rawQueryOne("select status, confirm_code, id from #_member where id = ? limit 0,1",array($id));

				if(empty($rowDetail) || (!empty($rowDetail['status']) && strpos($rowDetail['status'], 'hienthi') !== false))
				{
					$func->transfer("Trang không tồn tại",$configBase, false);
				}
			}
		}
	}

	function loginMember()
	{
		global $d, $func, $flash, $loginMember, $configBase;

		if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : ''))
			$func->transfer("Phiên đăng nhập không hợp lệ. Vui lòng tải lại trang.", $configBase."account/dang-nhap", false);

		/* Data */
		$username = (!empty($_POST['username'])) ? htmlspecialchars($_POST['username']) : '';
		$password = (!empty($_POST['password'])) ? $_POST['password'] : '';
		$passwordMD5 = md5($password);
		$remember = (!empty($_POST['remember-user'])) ? htmlspecialchars($_POST['remember-user']) : false;

		/* Valid data */
		if(empty($username))
		{
			$response['messages'][] = 'Tên đăng nhập không được trống';
		}

		if(empty($password))
		{
			$response['messages'][] = 'Mật khẩu không được trống';
		}

		if(!empty($response))
		{
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set("message", $message);
			$func->redirect($configBase."account/dang-nhap");
		}
		
		$row = $d->rawQueryOne("select id, password, username, phone, address, email, fullname from #_member where username = ? and find_in_set('hienthi',status) limit 0,1",array($username));

		if(!empty($row))
		{
			if($row['password'] == $passwordMD5)
			{
				/* Tạo login session */
				$id_user = $row['id'];
				$lastlogin = time();
				$login_session = md5($row['password'].$lastlogin);
				$d->rawQuery("update #_member set login_session = ?, lastlogin = ? where id = ?",array($login_session,$lastlogin,$id_user));

				/* Lưu session login */
				$_SESSION[$loginMember]['active'] = true;
				$_SESSION[$loginMember]['id'] = $row['id'];
				$_SESSION[$loginMember]['username'] = $row['username'];
				$_SESSION[$loginMember]['phone'] = $row['phone'];
				$_SESSION[$loginMember]['address'] = $row['address'];
				$_SESSION[$loginMember]['email'] = $row['email'];
				$_SESSION[$loginMember]['fullname'] = $row['fullname'];
				$_SESSION[$loginMember]['login_session'] = $login_session;

				/* Nhớ mật khẩu */
				setcookie('login_member_id',"",-1,'/');
				setcookie('login_member_session',"",-1,'/');
				if($remember)
				{
					$time_expiry = time()+3600*24;
					setcookie('login_member_id',$row['id'],$time_expiry,'/');
					setcookie('login_member_session',$login_session,$time_expiry,'/');
				}

				$func->transfer("Đăng nhập thành công", $configBase);
			}
			else
			{
				$response['messages'][] = 'Tên đăng nhập hoặc mật khẩu không chính xác. Hoặc tài khoản của bạn chưa được xác nhận từ Quản trị website';
			}
		}
		else
		{
			$response['messages'][] = 'Tên đăng nhập hoặc mật khẩu không chính xác. Hoặc tài khoản của bạn chưa được xác nhận từ Quản trị website';
		}

		/* Response error */
		if(!empty($response))
		{
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set("message", $message);
			$func->redirect($configBase."account/dang-nhap");
		}
	}

	function signupMember()
	{
		global $d, $func, $flash, $configBase;

		if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : ''))
			$func->transfer("Phiên đăng ký không hợp lệ. Vui lòng tải lại trang.", $configBase."account/dang-ky", false);

		/* Data */
		$message = '';
		$response = array();
		$username = (!empty($_POST['username'])) ? htmlspecialchars($_POST['username']) : '';
		$password = (!empty($_POST['password'])) ? $_POST['password'] : '';
		$passwordMD5 = md5($password);
		$repassword = (!empty($_POST['repassword'])) ? $_POST['repassword'] : '';
		$fullname = (!empty($_POST['fullname'])) ? htmlspecialchars($_POST['fullname']) : '';
		$email = (!empty($_POST['email'])) ? htmlspecialchars($_POST['email']) : '';
		$confirm_code = $func->digitalRandom(0,3,6);
		$phone = (!empty($_POST['phone'])) ? htmlspecialchars($_POST['phone']) : 0;
		$address = (!empty($_POST['address'])) ? htmlspecialchars($_POST['address']) : '';
		$gender = (!empty($_POST['gender'])) ? htmlspecialchars($_POST['gender']) : 0;
		$birthday = (!empty($_POST['birthday'])) ? htmlspecialchars($_POST['birthday']) : '';

		/* Valid data */
		if(empty($fullname))
		{
			$response['messages'][] = 'Họ tên không được trống';
		}

		if(empty($username))
		{
			$response['messages'][] = 'Tài khoản không được trống';
		}

		if(!empty($username))
		{
			if(!$func->isAlphaNum($username))
			{
				$response['messages'][] = 'Tài khoản chỉ được nhập chữ thường và số (chữ thường không dấu, ghi liền nhau, không khoảng trắng)';
			}

			if($func->checkAccount($username, 'username', 'member'))
			{
				$response['messages'][] = 'Tài khoản đã tồn tại';
			}
		}

		if(empty($password))
		{
			$response['messages'][] = 'Mật khẩu không được trống';
		}
		
		if(!empty($password) && empty($repassword))
		{
			$response['messages'][] = 'Xác nhận mật khẩu không được trống';
		}
		
		if(!empty($password) && !empty($repassword) && !$func->isMatch($password, $repassword))
		{
			$response['messages'][] = 'Mật khẩu không trùng khớp';
		}

		if(empty($gender))
		{
			$response['messages'][] = 'Chưa chọn giới tính';
		}

		if(empty($birthday))
		{
			$response['messages'][] = 'Ngày sinh không được trống';
		}
		
		if(!empty($birthday) && !$func->isDate($birthday))
		{
			$response['messages'][] = 'Ngày sinh không hợp lệ';
		}

		if(empty($email))
		{
			$response['messages'][] = 'Email không được trống';
		}

		if(!empty($email))
		{
			if(!$func->isEmail($email))
			{
				$response['messages'][] = 'Email không hợp lệ';
			}

			if($func->checkAccount($email, 'email', 'member'))
			{
				$response['messages'][] = 'Email đã tồn tại';
			}
		}

		if(!empty($phone) && !$func->isPhone($phone))
		{
			$response['messages'][] = 'Số điện thoại không hợp lệ';
		}

		if(empty($address))
		{
			$response['messages'][] = 'Địa chỉ không được trống';
		}

		if(!empty($response))
		{
			/* Flash data */
            $flash->set('fullname', $fullname);
            $flash->set('username', $username);
            $flash->set('gender', $gender);
            $flash->set('birthday', $birthday);
            $flash->set('email', $email);
            $flash->set('phone', $phone);
            $flash->set('address', $address);

            /* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);
			$func->redirect($configBase."account/dang-ky");
		}

		/* Save data */
		$data['fullname'] = $fullname;
		$data['username'] = $username;
		$data['password'] = md5($password);
		$data['email'] = $email;
		$data['phone'] = $phone;
		$data['address'] = $address;
		$data['gender'] = $gender;
		$data['birthday'] = strtotime(str_replace("/","-",$birthday));
		$data['confirm_code'] = $confirm_code;
		$data['status'] = '';
		
		if($d->insert('member',$data))
		{
			sendActivation($username);
			$func->transfer("Đăng ký thành viên thành công. Vui lòng kiểm tra email: ".$data['email']." để kích hoạt tài khoản", $configBase."account/dang-nhap");
		}
		else
		{
			$func->transfer("Đăng ký thành viên thất bại. Vui lòng thử lại sau.", $configBase, false);
		}
	}

	function sendActivation($username)
	{
		global $d, $setting, $emailer, $func, $configBase, $lang;

		/* Lấy thông tin người dùng */
		$row = $d->rawQueryOne("select id, confirm_code, username, password, fullname, email, phone, address from #_member where username = ? limit 0,1",array($username));

		/* Gán giá trị gửi email */
		$iduser = $row['id'];
		$confirm_code = $row['confirm_code'];
		$tendangnhap = $row['username'];
		$tennguoidung = $row['fullname'];
		$emailnguoidung = $row['email'];
		$dienthoainguoidung = $row['phone'];
		$diachinguoidung = $row['address'];
		$linkkichhoat = $configBase."account/kich-hoat?id=".$iduser;

		/* Thông tin đăng ký */
		$thongtindangky='<td style="padding:10px 12px;background:#f3f6fa;color:#405270;font-size:13px;line-height:21px" valign="top"><strong>Tên đăng nhập:</strong> '.htmlspecialchars($tendangnhap, ENT_QUOTES, 'UTF-8').'<br><strong>Mã kích hoạt:</strong> '.htmlspecialchars($confirm_code, ENT_QUOTES, 'UTF-8').'</td><td style="padding:10px 12px;color:#405270;font-size:13px;line-height:21px" valign="top">';
		if($tennguoidung)
		{
			$thongtindangky.='<span style="text-transform:capitalize">'.$tennguoidung.'</span><br>';
		}
		if($emailnguoidung)
		{
			$thongtindangky.='<a href="mailto:'.$emailnguoidung.'" target="_blank">'.$emailnguoidung.'</a><br>';
		}
		if($diachinguoidung)
		{
			$thongtindangky.=$diachinguoidung.'<br>';
		}
		if($dienthoainguoidung)
		{
			$thongtindangky.='Tel: '.$dienthoainguoidung.'</td>';
		}

		/* Defaults attributes email */
	    $emailDefaultAttrs = $emailer->defaultAttrs();

	    /* Variables email */
	    $emailVars = array(
	    	'{emailInfoSignupMember}',
	    	'{emailLinkActiveMember}'
	    );
	    $emailVars = $emailer->addAttrs($emailVars, $emailDefaultAttrs['vars']);

	    /* Values email */
	    $emailVals = array(
	    	$thongtindangky,
	    	$linkkichhoat
	    );
	    $emailVals = $emailer->addAttrs($emailVals, $emailDefaultAttrs['vals']);

		/* Send email admin */
		$arrayEmail = array(
			"dataEmail" => array(
				"name" => $row['username'],
				"email" => $row['email']
			)
		);
		$subject = $emailer->templateSubject('member_active', array('customer_name' => $tennguoidung), "Kích hoạt tài khoản Phúc Thịnh Solar");
		$message = str_replace($emailVars, $emailVals, $emailer->markdown('member/active', array('customer_name' => $tennguoidung)));
		$file = '';

		if(!$emailer->send("customer", $arrayEmail, $subject, $message, $file))
		{
			$func->transfer("Có lỗi xảy ra trong quá trình kích hoạt tài khoản. Vui lòng liên hệ với chúng tôi.",$configBase."lien-he", false);
		}
	}

	function forgotPasswordMember()
	{
		global $d, $setting, $emailer, $func, $flash, $loginMember, $configBase, $lang, $config;

		if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : ''))
			$func->transfer("Phiên cấp lại mật khẩu không hợp lệ. Vui lòng tải lại trang.", $configBase."account/quen-mat-khau", false);
		$publicIp = formClientIp($_SERVER, isset($config['login']['trusted-proxies']) ? $config['login']['trusted-proxies'] : array());
		if(!formPersistentRateLimit($d, 'member-forgot-password', $publicIp, 3, 900))
			$func->transfer("Bạn yêu cầu quá nhanh. Vui lòng thử lại sau.", $configBase."account/quen-mat-khau", false);

		/* Data */
		$message = '';
		$response = array();
		$username = (!empty($_POST['username'])) ? htmlspecialchars($_POST['username']) : '';
		$email = (!empty($_POST['email'])) ? htmlspecialchars($_POST['email']) : '';
		$newpass = strtoupper(bin2hex(random_bytes(4)));
		$newpassMD5 = md5($newpass);

		/* Valid data */
		if(empty($username))
		{
			$response['messages'][] = 'Tài khoản không được trống';
		}

		if(!empty($username) && !$func->isAlphaNum($username))
		{
			$response['messages'][] = 'Tài khoản chỉ được nhập chữ thường và số (chữ thường không dấu, ghi liền nhau, không khoảng trắng)';
		}

		if(empty($email))
		{
			$response['messages'][] = 'Email không được trống';
		}

		if(!empty($email) && !$func->isEmail($email))
		{
			$response['messages'][] = 'Email không hợp lệ';
		}

		if(!empty($username) && !empty($email))
		{
			$row = $d->rawQueryOne("select id, username, password, fullname, email, phone, address from #_member where username = ? and email = ? limit 0,1",array($username,$email));

			if(empty($row))
			{
				$response['messages'][] = 'Tên đăng nhập hoặc/và email không tồn tại';
			}
		}

		if(!empty($response))
		{
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set('message', $message);
			$func->redirect($configBase."account/quen-mat-khau");
		}

		/* Giữ hash cũ để phục hồi nếu SMTP thất bại; tránh khóa tài khoản khi
		   người dùng không nhận được mật khẩu tạm. */
		$oldPasswordHash = $row['password'];
		$data['password'] = $newpassMD5;
		$d->where('id', (int)$row['id']);
		$d->where('password', $oldPasswordHash);
		if(!$d->update('member',$data))
			$func->transfer("Có lỗi xảy ra trong quá trình cấp lại mật khẩu. Vui lòng liên hệ với chúng tôi.", $configBase."lien-he", false);
		$row['password'] = $newpassMD5;

		/* Gán giá trị gửi email */
		$iduser = $row['id'];
		$tendangnhap = $row['username'];
		$tennguoidung = $row['fullname'];
		$emailnguoidung = $row['email'];
		$dienthoainguoidung = $row['phone'];
		$diachinguoidung = $row['address'];

	    /* Thông tin đăng ký */
	    $thongtindangky='<td style="padding:10px 12px;background:#f3f6fa;color:#405270;font-size:13px;line-height:21px" valign="top"><strong>Tên đăng nhập:</strong> '.htmlspecialchars($tendangnhap, ENT_QUOTES, 'UTF-8').'</td><td style="padding:10px 12px;color:#405270;font-size:13px;line-height:21px" valign="top">';
	    if($tennguoidung)
	    {
	    	$thongtindangky.='<span style="text-transform:capitalize">'.$tennguoidung.'</span><br>';
	    }

	    if($emailnguoidung)
	    {
	    	$thongtindangky.='<a href="mailto:'.$emailnguoidung.'" target="_blank">'.$emailnguoidung.'</a><br>';
	    }

	    if($diachinguoidung)
	    {
	    	$thongtindangky.=$diachinguoidung.'<br>';
	    }

	    if($dienthoainguoidung)
	    {
	    	$thongtindangky.='Tel: '.$dienthoainguoidung.'</td>';
	    }

	    /* Defaults attributes email */
	    $emailDefaultAttrs = $emailer->defaultAttrs();

	    /* Variables email */
	    $emailVars = array(
	    	'{emailInfoSignupMember}',
	    	'{emailNewPasswordMember}'
	    );
	    $emailVars = $emailer->addAttrs($emailVars, $emailDefaultAttrs['vars']);

	    /* Values email */
	    $emailVals = array(
	    	$thongtindangky,
	    	$newpass
	    );
	    $emailVals = $emailer->addAttrs($emailVals, $emailDefaultAttrs['vals']);

		/* Send email admin */
		$arrayEmail = array(
			"dataEmail" => array(
				"name" => $tennguoidung,
				"email" => $email
			)
		);
		$subject = $emailer->templateSubject('member_forgot', array('customer_name' => $tennguoidung), "Thông tin đăng nhập mới từ Phúc Thịnh Solar");
		$message = str_replace($emailVars, $emailVals, $emailer->markdown('member/forgot-password', array('customer_name' => $tennguoidung)));
		$file = '';

		if($emailer->send("customer", $arrayEmail, $subject, $message, $file))
		{
			unset($_SESSION[$loginMember]);
			setcookie('login_member_id',"",-1,'/');
			setcookie('login_member_session',"",-1,'/');
			$func->transfer("Cấp lại mật khẩu thành công. Vui lòng kiểm tra email: ".$email, $configBase);
		}
		else
		{
			/* Chỉ phục hồi nếu password vẫn đúng giá trị vừa tạo; không ghi đè
			   thay đổi đồng thời từ một request khác. */
			$d->where('id', (int)$row['id']);
			$d->where('password', $newpassMD5);
			$d->update('member', array('password' => $oldPasswordHash));
			$func->transfer("Có lỗi xảy ra trong quá trình cấp lại mật khẩu. Vui lòng liện hệ với chúng tôi.", $configBase."account/quen-mat-khau", false);
		}
	}

	function logoutMember()
	{
		global $d, $func, $loginMember, $configBase;

		unset($_SESSION[$loginMember]);
		setcookie('login_member_id',"",-1,'/');
		setcookie('login_member_session',"",-1,'/');

		$func->redirect($configBase);
	}
	function order_list(){

		global $d,$lists,$loginMember, $configBase;

		$sql = "select * from #_order where id_user=? order by id desc";

		$lists = $d->rawQuery($sql,array($_SESSION['signin']['id']));

	}

	function edit_order(){

		global $d,$item,$lists ,$loginMember, $configBase;

		$sql = "select * from #_order where id=? and id_user=? limit 1";

		$item = $d->rawQueryOne($sql,array((int)($_GET['id'] ?? 0), (int)($_SESSION['signin']['id'] ?? 0)));



		$sql_order = "select * from #_order_details where id_order=?";

		$lists = !empty($item['id']) ? $d->rawQuery($sql_order,array((int)$item['id'])) : array();

	}

	function return_order(){
		global $d, $func, $configBase;

		if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : ''))
			$func->transfer("Phiên gửi yêu cầu đổi trả không hợp lệ. Vui lòng tải lại trang.", $configBase.'account/danh-sach-don-hang', false);

		$userId = (int)($_SESSION['signin']['id'] ?? 0);
		$orderId = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;
		if($userId <= 0 || $orderId <= 0) $func->transfer("Đơn hàng không tồn tại.", $configBase.'account/danh-sach-don-hang', false);

		$item = $d->rawQueryOne("select id,total_price,sale_off from #_order where id=? and id_user=? limit 1", array($orderId, $userId));
		if(empty($item['id'])) $func->transfer("Đơn hàng không tồn tại.", $configBase.'account/danh-sach-don-hang', false);

		$itemReturn = $d->rawQueryOne("select id from #_order_returns where id_order=? and id_user=? order by id desc limit 1", array($orderId, $userId));
		if(!empty($itemReturn['id'])) $func->transfer(_don_hang_da_duoc_tra, $configBase.'account/danh-sach-don-hang');

		$prefix = date('dmY').'TH';
		$latest = $d->rawQueryOne("select code from #_order_returns where code like ? order by id desc limit 1", array($prefix.'%'));
		$next = !empty($latest['code']) ? (int)substr((string)$latest['code'], 10) + 1 : 1001;
		$data = array(
			'id_order' => $orderId,
			'code' => $prefix.$next,
			'total_price' => $item['total_price'],
			'sale_off' => $item['sale_off'],
			'status' => 'datra',
			'id_user' => $userId
		);
		if($d->insert('order_returns', $data)) $func->transfer(_da_tra_hang_thanh_cong, $configBase.'account/danh-sach-don-hang');
		$func->transfer(_da_tra_hang_that_bai, $configBase.'account/danh-sach-don-hang', false);
	}

	function order_return_list(){

		global $d,$lists;

		$sql = "select * from #_order_returns where id_user=? order by id desc";

		$lists = $d->rawQuery($sql,array($_SESSION['signin']['id']));

	}

	function order_cancel_list(){

		global $d,$lists;

		$sql = "select * from #_order where id_user=? and order_status=? order by id desc";

		$lists = $d->rawQuery($sql,array($_SESSION['signin']['id'],4));

	}
?>
