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
		private $mailConfig = array();

		function __construct($d, $mailConfig = array())
		{
			$this->d = $d;
			$this->mailConfig = is_array($mailConfig) ? $mailConfig : array();
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
			if(!is_array($this->optcompany)) $this->optcompany = array();
			$logo = $this->d->rawQueryOne("select photo from #_photo where type = ? and act = ? limit 0,1",array('logo','photo_static'));
			$social = $this->d->rawQuery("select photo, link from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc",array('social'));

			if($social && count($social) > 0)
			{
				foreach($social as $value)
				{
					$socialString .= '<a href="'.$value['link'].'" target="_blank"><img src="'.$configBase.UPLOAD_PHOTO_L.$value['photo'].'" style="max-height:30px;margin:0 0 0 5px" /></a>';
				}
			}

			$this->data['email'] = isset($this->mailConfig['from']) ? trim((string)$this->mailConfig['from']) : '';
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

		private static function validAddress($email)
		{
			$email = trim((string)$email);
			return $email !== ''
				&& strpos($email, "\r") === false
				&& strpos($email, "\n") === false
				&& filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
		}

		private static function cleanHeaderText($value)
		{
			return trim(preg_replace('/[\r\n]+/', ' ', (string)$value));
		}

		private static function normalizeEncryption($encryption)
		{
			$encryption = strtolower(trim((string)$encryption));
			if($encryption === 'starttls') return PHPMailer::ENCRYPTION_STARTTLS;
			if($encryption === 'smtps') return PHPMailer::ENCRYPTION_SMTPS;
			return $encryption;
		}

		public static function validateConfig($config)
		{
			$errors = array();
			$config = is_array($config) ? $config : array();
			if(!isset($config['enabled']) || $config['enabled'] !== true) $errors[] = 'enabled';
			$host = isset($config['host']) ? trim((string)$config['host']) : '';
			if($host === '' || strpos($host, "\r") !== false || strpos($host, "\n") !== false || !preg_match('/^[a-z0-9.-]+$/i', $host)) $errors[] = 'host';
			$port = isset($config['port']) ? (int)$config['port'] : 0;
			if($port < 1 || $port > 65535) $errors[] = 'port';
			$encryption = self::normalizeEncryption(isset($config['encryption']) ? $config['encryption'] : '');
			if(!in_array($encryption, array(PHPMailer::ENCRYPTION_STARTTLS, PHPMailer::ENCRYPTION_SMTPS), true)) $errors[] = 'encryption';
			if(!self::validAddress(isset($config['username']) ? $config['username'] : '')) $errors[] = 'username';
			$password = trim((string)(isset($config['password']) ? $config['password'] : ''));
			if($password === '' || $password === 'REPLACE_WITH_MAILBOX_PASSWORD') $errors[] = 'password';
			if(!self::validAddress(isset($config['from']) ? $config['from'] : '')) $errors[] = 'from';
			if(!self::validAddress(isset($config['admin_to']) ? $config['admin_to'] : '')) $errors[] = 'admin_to';
			/* SMTP relay (Brevo, SES, Mailgun...) dùng một credential đăng nhập
			   riêng với địa chỉ From đã xác thực. Không bắt hai giá trị phải trùng;
			   quyền dùng From do relay xác minh ở cấp sender/domain. Cả hai field
			   vẫn được kiểm tra độc lập để chặn địa chỉ sai và header injection. */
			$timeout = isset($config['timeout']) ? (int)$config['timeout'] : 10;
			if($timeout < 3 || $timeout > 30) $errors[] = 'timeout';
			return array_values(array_unique($errors));
		}

		private static function configureMailer($mail, $config)
		{
			$mail->isSMTP();
			$mail->SMTPAuth = true;
			$mail->SMTPDebug = 0;
			$mail->Host = trim((string)$config['host']);
			$mail->Port = (int)$config['port'];
			$mail->SMTPSecure = self::normalizeEncryption($config['encryption']);
			$mail->SMTPAutoTLS = true;
			$mail->Username = trim((string)$config['username']);
			$mail->Password = (string)$config['password'];
			$mail->Timeout = isset($config['timeout']) ? (int)$config['timeout'] : 10;
			$mail->Timelimit = $mail->Timeout + 5;
			$mail->SMTPOptions = self::secureSmtpOptions();
			$mail->CharSet = 'utf-8';
		}

		/** Dùng cho CLI readiness; không trả host, account, recipient hay secret. */
		public static function readiness($config, $connect = false, $sendTest = false)
		{
			$errors = self::validateConfig($config);
			if($errors) return array('ok' => false, 'stage' => 'config', 'errors' => $errors);
			if(!$connect && !$sendTest) return array('ok' => true, 'stage' => 'config', 'errors' => array());

			$mail = new PHPMailer(true);
			try
			{
				self::configureMailer($mail, $config);
				$mail->setFrom(trim((string)$config['from']), 'PTS Website');
				if($sendTest)
				{
					$mail->addAddress(trim((string)$config['admin_to']));
					$mail->Subject = '[PTS] SMTP production readiness test';
					$mail->Body = 'SMTP production test completed at '.date('c').'.';
					$mail->AltBody = 'SMTP production test completed at '.date('c').'.';
					$mail->send();
					return array('ok' => true, 'stage' => 'send', 'errors' => array());
				}

				if(!$mail->smtpConnect(self::secureSmtpOptions())) return array('ok' => false, 'stage' => 'connect', 'errors' => array('smtp_connection'));
				$mail->smtpClose();
				return array('ok' => true, 'stage' => 'connect', 'errors' => array());
			}
			catch(Throwable $exception)
			{
				$mail->smtpClose();
				return array('ok' => false, 'stage' => $sendTest ? 'send' : 'connect', 'errors' => array('smtp_operation'));
			}
		}

		private static function attachmentAllowed($field)
		{
			if(!isset($_FILES[$field]) || !is_array($_FILES[$field]) || !empty($_FILES[$field]['error'])) return false;
			$file = $_FILES[$field];
			if(empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return false;
			if(empty($file['size']) || (int)$file['size'] > 10 * 1024 * 1024) return false;
			$extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
			$allowed = array('doc','docx','pdf','rar','zip','ppt','pptx','xls','xlsx','jpg','jpeg','png','gif');
			if(!in_array($extension, $allowed, true)) return false;
			if(function_exists('finfo_open'))
			{
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
				if($finfo) finfo_close($finfo);
				if($mime === 'text/html' || preg_match('/php|x-httpd|x-sh/i', (string)$mime)) return false;
			}
			return true;
		}

		public function sendAdminNotification($subject, $fields, $replyToEmail = '', $replyToName = '')
		{
			$rows = '';
			foreach((array)$fields as $label => $value)
			{
				$rows .= '<tr><th align="left" style="padding:6px 10px">'.htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8').'</th>'
					.'<td style="padding:6px 10px">'.nl2br(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')).'</td></tr>';
			}
			$message = '<table border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse">'.$rows.'</table>';
			return $this->send('admin', array(), $subject, $message, '', array('email' => $replyToEmail, 'name' => $replyToName));
		}

		public function send($owner='', $arrayEmail=array(), $subject="", $message="", $file='', $replyTo=array())
		{
			$mail = new PHPMailer(true);
			$configErrors = self::validateConfig($this->mailConfig);
			if($configErrors)
			{
				error_log('Email::send configuration invalid: '.implode(',', $configErrors));
				return false;
			}

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
				self::configureMailer($mail, $this->mailConfig);
				$fromName = isset($this->mailConfig['from_name']) && trim((string)$this->mailConfig['from_name']) !== ''
					? self::cleanHeaderText($this->mailConfig['from_name']) : self::cleanHeaderText($this->company['namevi']);
				$mail->setFrom(trim((string)$this->mailConfig['from']), $fromName);

				if($owner === 'admin' || $owner === 'adminis')
				{
					$mail->addAddress(trim((string)$this->mailConfig['admin_to']), self::cleanHeaderText($this->company['namevi']));
				}
				else if($owner === 'customer')
				{
					$validRecipients = array();
					foreach((array)$arrayEmail as $vEmail)
					{
						if(is_array($vEmail) && self::validAddress(isset($vEmail['email']) ? $vEmail['email'] : '')) $validRecipients[] = $vEmail;
					}
					if(!$validRecipients) return false;
					$bulk = count($validRecipients) > 1;
					foreach($validRecipients as $recipient)
					{
						$name = isset($recipient['name']) ? self::cleanHeaderText($recipient['name']) : '';
						if($bulk) $mail->addBCC(trim((string)$recipient['email']), $name);
						else $mail->addAddress(trim((string)$recipient['email']), $name);
					}
				}
				else
				{
					return false;
				}

				if(is_array($replyTo) && self::validAddress(isset($replyTo['email']) ? $replyTo['email'] : ''))
				{
					$mail->addReplyTo(trim((string)$replyTo['email']), isset($replyTo['name']) ? self::cleanHeaderText($replyTo['name']) : '');
				}

				$mail->CharSet = "utf-8";
				$mail->AltBody = "To view the message, please use an HTML compatible email viewer!";
				$mail->Subject = self::cleanHeaderText($subject);
				$mail->MsgHTML($message);
				if($file !== '' && self::attachmentAllowed($file))
				{
					$attachmentName = preg_replace('/[\r\n]+/', '', basename((string)$_FILES[$file]['name']));
					$mail->addAttachment($_FILES[$file]['tmp_name'], $attachmentName);
				}

				return $mail->send() ? true : false;
			}
			catch(Throwable $exception)
			{
				/* Không log exception/message của SMTP vì có thể chứa account,
				   recipient hoặc phản hồi từ provider. */
				error_log('Email::send failed owner='.(in_array($owner, array('admin','adminis','customer'), true) ? $owner : 'invalid'));
				return false;
			}
		}
	}
?>
