<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;
	use PHPMailer\PHPMailer\Exception;

	include_once LIBRARIES."PHPMailer/PHPMailer.php";
	include_once LIBRARIES."PHPMailer/SMTP.php";
	include_once LIBRARIES."PHPMailer/Exception.php";
	require_once LIBRARIES."mail_templates.php";

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
			$this->data['color'] = '#D9A326';
			$this->data['home'] = $configBase;
			$this->data['logo'] = '<img src="'.$configBase.UPLOAD_PHOTO_L.$logo['photo'].'" alt="Phúc Thịnh Solar" width="210" style="display:block;width:210px;max-width:100%;height:auto;border:0;" >';
			$this->data['social'] = $socialString;
			$this->data['datesend'] = time();
			$this->data['company'] = $this->company['namevi'];
			$this->data['company:address'] = $this->optcompany['address'];
			$this->data['company:email'] = isset($this->mailConfig['from']) ? trim((string)$this->mailConfig['from']) : (isset($this->optcompany['email']) ? $this->optcompany['email'] : '');
			$this->data['company:hotline'] = isset($this->optcompany['hotline']) ? $this->optcompany['hotline'] : '';
			$this->data['company:website'] = isset($this->optcompany['website']) ? $this->optcompany['website'] : '';
			$this->data['company:worktime'] = !empty($this->optcompany['thoigianlamviec']) ? $this->optcompany['thoigianlamviec'] : 'Thứ Hai - Thứ Bảy, 08:00 - 17:00';
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

		public function templateCopy($key, $values = array())
		{
			return ptsMailTemplateResolvedCopy($this->optcompany, $key, $values);
		}

		public function templateSubject($key, $values = array(), $fallback = '')
		{
			$copy = $this->templateCopy($key, $values);
			return !empty($copy['subject']) ? self::cleanHeaderText($copy['subject']) : self::cleanHeaderText($fallback);
		}

		public function workflowPreview($key)
		{
			$definitions = ptsMailTemplateDefinitions();
			if(!isset($definitions[$key])) return '';
			$values = array(
				'customer_name' => 'Nguyễn Văn An',
				'customer_phone' => '0900 000 000',
				'company' => 'Công ty Minh An',
				'contact_subject' => 'Tư vấn hệ thống điện mặt trời',
				'order_code' => 'PTS-DEMO-001'
			);
			$copy = $this->templateCopy($key, $values);
			$message = $this->markdown('notification/preview', array(
				'copy' => $copy,
				'workflow' => $definitions[$key],
				'fields' => array(
					'Họ tên' => $values['customer_name'],
					'Điện thoại' => $values['customer_phone'],
					'Email' => 'khachhang@example.com',
					'Nội dung minh họa' => $key === 'order_admin' || $key === 'order_customer'
						? 'Đơn hàng '.$values['order_code'].' gồm các sản phẩm được khách lựa chọn.'
						: 'Nội dung này chỉ dùng để xem trước cách trình bày email.'
				)
			));
			$defaults = $this->defaultAttrs();
			return str_replace($defaults['vars'], $defaults['vals'], $message);
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
			if(isset($config['test_to']) && trim((string)$config['test_to']) !== '' && !self::validAddress($config['test_to'])) $errors[] = 'test_to';
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
				$fromName = isset($config['from_name']) && trim((string)$config['from_name']) !== ''
					? self::cleanHeaderText($config['from_name']) : 'Phúc Thịnh Solar';
				$mail->setFrom(trim((string)$config['from']), $fromName);
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

		public function sendAdminNotification($subject, $fields, $replyToEmail = '', $replyToName = '', $workflow = '')
		{
			$templateValues = array(
				'customer_name' => isset($fields['Họ tên']) ? $fields['Họ tên'] : '',
				'customer_phone' => isset($fields['Điện thoại']) ? $fields['Điện thoại'] : '',
				'company' => isset($fields['Doanh nghiệp/Cửa hàng']) ? $fields['Doanh nghiệp/Cửa hàng'] : ''
			);
			$copy = $workflow !== '' ? $this->templateCopy($workflow, $templateValues) : array();
			if(!empty($copy['subject'])) $subject = $copy['subject'];
			$message = $this->markdown('notification/admin', array('copy' => $copy, 'fields' => (array)$fields));
			$defaults = $this->defaultAttrs();
			$message = str_replace($defaults['vars'], $defaults['vals'], $message);
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
				$plainBody = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", (string)$message);
				$plainBody = html_entity_decode(strip_tags($plainBody), ENT_QUOTES, 'UTF-8');
				$plainBody = trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\n{3,}/', "\n\n", $plainBody)));
				$mail->AltBody = $plainBody !== '' ? $plainBody : 'Thông báo từ Phúc Thịnh Solar.';
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
