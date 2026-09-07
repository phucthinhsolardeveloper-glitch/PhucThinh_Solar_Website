<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;
	use PHPMailer\PHPMailer\Exception;

	include_once LIBRARIES."PHPMailer/PHPMailer.php";
	include_once LIBRARIES."PHPMailer/SMTP.php";
	include_once LIBRARIES."PHPMailer/Exception.php";

	class Email
	{
		private $d;
		private $data = array();
		private $company = array();
		private $optcompany = '';

		function __construct($d)
		{
			$this->d = $d;
			$this->info();
		}

		private function info()
		{
			global $configBase;

			$logo = array();
			$social = array();
			$socialString = '';
			$this->company = $this->d->rawQueryOne("select options, namevi from #_setting limit 0,1");
			$this->optcompany = json_decode($this->company['options'],true);
			$logo = $this->d->rawQueryOne("select photo from #_photo where type = ? and act = ? limit 0,1",array('logo','photo_static'));
			$social = $this->d->rawQuery("select photo, link from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc",array('social'));

			if($social && count($social) > 0)
			{
				foreach($social as $value)
				{
					$socialString .= '<a href="'.$value['link'].'" target="_blank"><img src="'.$configBase.UPLOAD_PHOTO_L.$value['photo'].'" style="max-height:30px;margin:0 0 0 5px" /></a>';
				}
			}

			$this->data['email'] = ($this->optcompany['mailertype']==1) ? $this->optcompany['email_host'] : $this->optcompany['email_gmail'];
			$this->data['color'] = '#94130F';
			$this->data['home'] = $configBase;
			$this->data['logo'] = '<img src="'.$configBase.UPLOAD_PHOTO_L.$logo['photo'].'" style="max-height:70px;" >';
			$this->data['social'] = $socialString;
			$this->data['datesend'] = time();
			$this->data['company'] = $this->company['namevi'];
			$this->data['company:address'] = $this->optcompany['address'];
			$this->data['company:email'] = $this->optcompany['email'];
			$this->data['company:hotline'] = $this->optcompany['hotline'];
			$this->data['company:website'] = $this->optcompany['website'];
			$this->data['company:worktime'] = '(8-21h cả T7,CN)';
		}

		public function set($key, $value)
		{
			if(!empty($key) && !empty($value))
			{
				$this->data[$key] = $value;
			}
		}

		public function get($key)
		{
			return (!empty($this->data[$key])) ? $this->data[$key] : '';
		}

		public function markdown($path='', $params=array())
		{
			$content = '';

			if(!empty($path))
			{
				ob_start();
				include dirname(__DIR__)."/sample/mail/".$path.".php";
				$content = ob_get_contents();
				ob_clean();
			}

			return $content;
		}

		public function defaultAttrs()
		{
			$default = array();
			$default['vars'] = array(
				'{emailColor}',
				'{emailHome}',
				'{emailLogo}',
				'{emailSocial}',
				'{emailMail}',
				'{emailDateSend}',
				'{emailCompanyName}',
				'{emailCompanyWebsite}',
				'{emailCompanyAddress}',
				'{emailCompanyMail}',
				'{emailCompanyHotline}',
				'{emailCompanyWorktime}'
			);
			$default['vals'] = array(
				$this->get('color'),
				$this->get('home'),
				$this->get('logo'),
				$this->get('social'),
				$this->get('email'),
				'Ngày '.date('d', time()).' tháng '.date('m', time()).' năm '.date('Y H:i:s', time()),
				$this->get('company'),
				$this->get('company:website'),
				$this->get('company:address'),
				$this->get('company:email'),
				$this->get('company:hotline'),
				$this->get('company:worktime')
			);

			return $default;
		}

		public function addAttrs($array1=array(), $array2=array())
		{
			if(!empty($array1) && !empty($array2))
			{
				foreach($array2 as $k2 => $v2)
				{
					array_push($array1, $v2);
				}
			}

			return $array1;
		}

		public static function secureSmtpOptions()
		{
			return array(
				'ssl' => array(
					'verify_peer' => true,
					'verify_peer_name' => true,
					'allow_self_signed' => false
				)
			);
		}

		public function send($owner='', $arrayEmail=array(), $subject="", $message="", $file='')
		{
			$mail = new PHPMailer(true);
			$config_host = '';
			$config_port = 0;
			$config_secure = '';
			$config_email = '';
			$config_password = '';
			if ($owner != 'adminis'){
				if($this->optcompany['mailertype'] == 1)
	            {
	                $config_host = $this->optcompany['ip_host'];
	                $config_port = $this->optcompany['port_host'];
	                $config_secure = $this->optcompany['secure_host'];
	                $config_email = $this->optcompany['email_host'];
	                $config_password = $this->optcompany['password_host'];

	                $mail->IsSMTP();
	                $mail->SMTPAuth = true;
	                $mail->SMTPDebug = false;
	                $mail->SMTPSecure = $config_secure;
	            }
	            else if($this->optcompany['mailertype'] == 2)
	            {
	                $config_host = $this->optcompany['host_gmail'];
	                $config_port = $this->optcompany['port_gmail'];
	                $config_secure = $this->optcompany['secure_gmail'];
	                $config_email = $this->optcompany['email_gmail'];
	                $config_password = $this->optcompany['password_gmail'];
	                $mail->IsSMTP();
	                $mail->SMTPAuth = true;
	                $mail->SMTPDebug = false;
	                $mail->SMTPSecure = $config_secure;             
	            }
			}else{
				$config_host = $this->optcompany['ip_host_as'];
                $config_port = $this->optcompany['port_host'];
                $config_secure = $this->optcompany['secure_host'];
                $config_email = $this->optcompany['email_host_as'];
                $config_password = $this->optcompany['password_host_as'];

                $mail->IsSMTP();
                $mail->SMTPAuth = true;
                $mail->SMTPDebug = false;
                $mail->SMTPSecure = $config_secure;
			}

			/* Không hạ chuẩn TLS theo từng provider: certificate và hostname phải
			   hợp lệ trên hosting production. */
			$mail->SMTPOptions = self::secureSmtpOptions();
			

			/* PHPMailer(true) ở đầu hàm bật ném exception, và nó ném cho rất nhiều
			   trường hợp: SetFrom/AddAddress với địa chỉ rỗng hoặc sai định dạng,
			   không kết nối được SMTP, sai mật khẩu, timeout Send()... không riêng
			   gì cấu hình thiếu. Trước đây không có try/catch nào từ đây lên tới
			   nơi gọi (contact.php, order.php, user.php, newsletter.php — 7 chỗ),
			   nên bất kỳ trục trặc gửi mail nào cũng vỡ thành fatal error lộ ra
			   thẳng trình duyệt khách hàng, kèm stack trace lộ đường dẫn server
			   thật. Với order.php, đây là email xác nhận đơn hàng: đơn đã lưu xong
			   trước khi gửi mail, nên khách mất luôn trang xác nhận dù đơn vẫn vào
			   hệ thống bình thường.
			   Bọc từ bước gán Host/SetFrom, không chỉ quanh Send(): SetFrom và
			   AddAddress ném ngay khi gặp địa chỉ rỗng, tức trước khi chạm tới
			   Send() một bước. Bắt lại và trả false: mọi nơi gọi đã sẵn nhánh xử
			   lý "gửi thất bại" ở else, chỉ là trước giờ không bao giờ chạy tới
			   được vì exception chặn ngang trước đó. */
			try
			{
				$mail->Host = $config_host;
				if($config_port)
				{
					$mail->Port = $config_port;
				}
				$mail->Username = $config_email;
				$mail->Password = $config_password;
				$mail->SetFrom($config_email,$this->company['namevi']);

				if($owner == 'admin')
				{
					$mail->AddAddress($this->optcompany['email'],$this->company['namevi']);
				}
				else if($owner == 'customer')
				{
					if($arrayEmail && count($arrayEmail) > 0)
					{
						foreach($arrayEmail as $vEmail)
						{
							$mail->AddAddress($vEmail['email'],$vEmail['name']);
						}
					}
				}else if ($owner == 'adminis'){
					$mail->AddAddress($this->optcompany['email_as'],"ERROR SERVER NOT!!!");
					$mail->AddAddress($this->optcompany['apdev_as'],"ERROR SERVER NOT DEV!!!");
				}else{
					$mail->AddReplyTo($this->optcompany['email'],$this->company['namevi']);
				}

				$mail->CharSet = "utf-8";
				$mail->AltBody = "To view the message, please use an HTML compatible email viewer!";
				$mail->Subject = $subject;
				$mail->MsgHTML($message);
				if($file != '' && isset($_FILES[$file]) && !$_FILES[$file]['error'])
				{
					$mail->AddAttachment($_FILES[$file]["tmp_name"], $_FILES[$file]["name"]);
				}

				return $mail->Send() ? true : false;
			}
			catch(Throwable $exception)
			{
				error_log('Email::send('.$owner.') thất bại: '.$exception->getMessage());
				return false;
			}
		}
	}
?>
