<?php
	require LIBRARIES.'WebpConvert/vendor/autoload.php';
	use WebPConvert\WebPConvert;

	class Functions
	{
		private $d;
		private $hash;
		private $cache;

		function __construct($d, $cache)
		{
			$this->d = $d;
			$this->cache = $cache;
		}

		private function uploadAllowedExtensions($extension)
		{
			$allowed = array();
			foreach(preg_split('/[|,\s]+/', strtolower((string) $extension)) as $item)
			{
				$item = ltrim(trim($item), '.');
				if($item !== '') $allowed[$item] = true;
			}
			return array_keys($allowed);
		}

		private function uploadLimitBytes()
		{
			$value = trim((string) ini_get('post_max_size'));
			if(!preg_match('/^(\d+(?:\.\d+)?)\s*([KMG])?$/i', $value, $match)) return 5 * 1048576;
			$bytes = (float) $match[1];
			$unit = !empty($match[2]) ? strtoupper($match[2]) : '';
			if($unit === 'G') $bytes *= 1073741824;
			elseif($unit === 'M') $bytes *= 1048576;
			elseif($unit === 'K') $bytes *= 1024;
			return (int) $bytes;
		}

		private function validImageUpload($file, $extension)
		{
			if(empty($_FILES[$file]['tmp_name']) || !is_uploaded_file($_FILES[$file]['tmp_name']) && PHP_SAPI !== 'cli') return false;
			if((int) $_FILES[$file]['size'] <= 0 || (int) $_FILES[$file]['size'] > $this->uploadLimitBytes()) return false;
			$ext = strtolower((string) pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION));
			if(!in_array($ext, $this->uploadAllowedExtensions($extension), true)) return false;
			$image = @getimagesize($_FILES[$file]['tmp_name']);
			if(!$image || empty($image['mime'])) return false;
			$mimes = array('jpg' => array('image/jpeg'), 'jpeg' => array('image/jpeg'), 'png' => array('image/png'), 'gif' => array('image/gif'), 'webp' => array('image/webp'));
			return isset($mimes[$ext]) && in_array(strtolower($image['mime']), $mimes[$ext], true);
		}

		/* Upload images */
		public function uploadFile($file='', $extension='', $folder='', $newname='')
		{
			global $config;

			if(isset($_FILES[$file]) && !$_FILES[$file]['error'])
			{
				$postMaxSize = ini_get('post_max_size');
				if((int) $_FILES[$file]['size'] <= 0 || (int) $_FILES[$file]['size'] > $this->uploadLimitBytes())
				{
					$this->alert('Dung lượng file không được vượt quá '.$postMaxSize);
					return false;
				}

				$ext = explode('.', $_FILES[$file]['name']);
				$ext = strtolower($ext[count($ext)-1]);
				$name = basename($_FILES[$file]['name'], '.'.$ext);

				if(!in_array($ext, $this->uploadAllowedExtensions($extension), true))
				{
					$this->alert('Chỉ hỗ trợ upload file dạng '.$extension);
					return false;
				}

				$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
				$mime = $finfo ? finfo_file($finfo, $_FILES[$file]['tmp_name']) : '';
				if($finfo) finfo_close($finfo);
				$allowedMimes = array(
					'mp4' => array('video/mp4','application/mp4'),
					'mkv' => array('video/x-matroska','application/octet-stream'),
					'pdf' => array('application/pdf'),
					'doc' => array('application/msword','application/CDFV2'),
					'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip'),
					'xls' => array('application/vnd.ms-excel','application/CDFV2'),
					'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip'),
					'ppt' => array('application/vnd.ms-powerpoint','application/CDFV2'),
					'pptx' => array('application/vnd.openxmlformats-officedocument.presentationml.presentation','application/zip'),
					'zip' => array('application/zip'),
					'rar' => array('application/vnd.rar','application/x-rar','application/x-rar-compressed'),
					'jpg' => array('image/jpeg'), 'jpeg' => array('image/jpeg'), 'png' => array('image/png'), 'gif' => array('image/gif')
				);
				if(!isset($allowedMimes[$ext]) || !in_array(strtolower((string) $mime), $allowedMimes[$ext], true))
				{
					$this->alert('Nội dung tập tin không đúng định dạng cho phép.');
					return false;
				}
				if(in_array($ext, array('jpg','jpeg','png','gif'), true) && !@getimagesize($_FILES[$file]['tmp_name'])) return false;

				if($newname=='' && file_exists($folder.$_FILES[$file]['name']))
					for($i=0; $i<100; $i++)
					{
						if(!file_exists($folder.$name.$i.'.'.$ext))
						{
							$_FILES[$file]['name'] = $name.$i.'.'.$ext;
							break;
						}
					}
				else
				{
					$_FILES[$file]['name'] = $newname.'.'.$ext;
				}

				if(!copy($_FILES[$file]["tmp_name"], $folder.$_FILES[$file]['name']))	
				{
					if(!move_uploaded_file($_FILES[$file]["tmp_name"], $folder.$_FILES[$file]['name']))	
					{
						return false;
					}
				}

				return $_FILES[$file]['name'];
			}
			return false;
		}

		/* Markdown */
		public function markdown($path='', $params=array())
		{
			$content = '';

			if(!empty($path))
			{
				ob_start();
				include dirname(__DIR__)."/sample/".$path.".php";
				$content = ob_get_contents();
				ob_clean();
			}

			return $content;
		}
		public function isAjax() {

		    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));

		}
		/* Check URL */
		public function checkURL($index=false)
		{
			global $configBase;
			
			$url = '';
			$urls = array('index','index.html','trang-chu','trang-chu.html');

			if(array_key_exists('REDIRECT_URL', $_SERVER))
			{
				$url = explode("/", $_SERVER['REDIRECT_URL']);
			}
			else
			{
				$url = explode("/", $_SERVER['REQUEST_URI']);
			}

			if(is_array($url))
			{
				$url = $url[count($url)-1];
				if(strpos($url, "?"))
				{
					$url = explode("?", $url);
					$url = $url[0];
				}
			}

			if($index) array_push($urls,"index.php");
			else if(array_search('index.php', $urls)) $urls = array_diff($urls, ["index.php"]);
			if(in_array($url, $urls)) $this->redirect($configBase,301);
		}

		/* Check HTTP */
		public function checkHTTP($http, $arrayDomain, &$configBase, $configUrl)
		{
			/* Với domain không ép SSL (điển hình là local), code legacy hạ
			   configBase về HTTP. Không được hạ khi HTTPS đã được reverse proxy
			   xác nhận, nếu không base/canonical sẽ thành mixed-content. */
			$forwardedProto = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
				? strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]))
				: '';
			if(count($arrayDomain) == 0 && $http == 'https://' && $forwardedProto !== 'https')
			{
				$configBase = 'http://'.$configUrl;
			}
		}

		/* Create sitemap */
		public function createSitemap($com='', $type='', $field='', $table='', $time='', $changefreq='', $priority='', $lang='vi', $orderby='', $menu=true)
		{
			global $configBase;

			$urlSm = '';
			$sitemap = null;

			if(!empty($type) && !in_array($table, ['photo', 'static']))
			{
				$where = "type = ? and find_in_set('hienthi',status)";
				$params = array($type);
				$productHierarchy = array('product_list' => 'id_list', 'product_cat' => 'id_cat', 'product_item' => 'id_item', 'product_sub' => 'id_sub');
				if(isset($productHierarchy[$table]))
				{
					$where .= " and exists (select 1 from #_product p where p.".$productHierarchy[$table]." = #_".$table.".id and p.type = ? and find_in_set('hienthi',p.status))";
					$params[] = $type;
				}
				$where .= ($table != 'static') ? 'order by '.$orderby.' desc' : '';
				$sitemap = $this->d->rawQuery("select id, slug$lang, date_created from #_$table where $where",$params);
			}

			if($menu == true && $field == 'id')
			{
				$urlSm = $configBase.$com;
				echo '<url>';
				echo '<loc>'.$urlSm.'</loc>';
				echo '<lastmod>'.date('c',time()).'</lastmod>';
				echo '<changefreq>'.$changefreq.'</changefreq>';
				echo '<priority>'.$priority.'</priority>';
				echo '</url>';
			}

			if(!empty($sitemap))
			{
				foreach($sitemap as $value)
				{
                    if(!empty($value['slug'.$lang]))
                    {
						/* Sitemap phải công bố đúng URL canonical mà site đang liên kết:
						   sản phẩm là san-pham/<slug>, danh mục là dạng phân cấp. */
						$hierarchyTables = array('product','product_list','product_cat','product_item','product_sub');
						$hierarchyPath = in_array($table, $hierarchyTables, true) && function_exists('publicProductEntityPath') ? publicProductEntityPath($this->d, $table, $value['id'], $lang) : '';
						$urlSm = $configBase.($hierarchyPath !== '' ? $hierarchyPath : $value['slug'.$lang]);
                        echo '<url>';
                        echo '<loc>'.$urlSm.'</loc>';
                        echo '<lastmod>'.date('c',$value['date_created']).'</lastmod>';
                        echo '<changefreq>'.$changefreq.'</changefreq>';
                        echo '<priority>'.$priority.'</priority>';
                        echo '</url>';
                    }
				}
			}
		}
		// phần check cart
		public function countOrderProduct($id){

			$row = $this->d->rawQueryOne("select COUNT(*) as total from #_order_detail where id_product='".$id."' ");

			return $row['total'];

		}

		public function getFieldId($id,$table){

			$this->d->where("id", $id);

			$item = $this->d->getOne($table);

			return $item;

		}

		public function getOrderDetails($id){

			$items  =  $this->d->rawQuery("select * from #_order_detail where id_order=? order by id desc",array($id));

			return $items;

		}

		public function getOrderReturns($id){

			$items  =  $this->d->rawQuery("select * from #_order_detail where id_order=? order by id desc",array($id));

			return $items;

		}

		public function getTotalPriceOrder(){

			$item  =  $this->d->rawQueryOne("select sum(total_price) as total from #_order");

			return $item['total'];

		}

		/* Kiểm tra dữ liệu nhập vào */
		public function cleanInput($input='', $type='')
		{
			$output = '';

			if($input != '')
			{
				/*
					// Loại bỏ HTML tags
					'@<[\/\!]*?[^<>]*?>@si',
				*/

				$search = array(
					'script' => '@<script[^>]*?>.*?</script>@si',
					'style' => '@<style[^>]*?>.*?</style>@siU',
					'blank' => '@<![\s\S]*?--[ \t\n\r]*>@',
					'iframe' => '/<iframe(.*?)<\/iframe>/is',
					'title' => '/<title(.*?)<\/title>/is',
					'pre' => '/<pre(.*?)<\/pre>/is',
					'frame' => '/<frame(.*?)<\/frame>/is',
					'frameset' => '/<frameset(.*?)<\/frameset>/is',
					'object' => '/<object(.*?)<\/object>/is',
					'embed' => '/<embed(.*?)<\/embed>/is',
					'applet' => '/<applet(.*?)<\/applet>/is',
					'meta' => '/<meta(.*?)<\/meta>/is',
					'doctype' => '/<!doctype(.*?)>/is',
					'link' => '/<link(.*?)>/is',
					'body' => '/<body(.*?)<\/body>/is',
					'html' => '/<html(.*?)<\/html>/is',
					'head' => '/<head(.*?)<\/head>/is',
					'onclick' => '/onclick="(.*?)"/is',
					'ondbclick' => '/ondbclick="(.*?)"/is',
					'onchange' => '/onchange="(.*?)"/is',
					'onmouseover' => '/onmouseover="(.*?)"/is',
					'onmouseout' => '/onmouseout="(.*?)"/is',
					'onmouseenter' => '/onmouseenter="(.*?)"/is',
					'onmouseleave' => '/onmouseleave="(.*?)"/is',
					'onmousemove' => '/onmousemove="(.*?)"/is',
					'onkeydown' => '/onkeydown="(.*?)"/is',
					'onload' => '/onload="(.*?)"/is',
					'onunload' => '/onunload="(.*?)"/is',
					'onkeyup' => '/onkeyup="(.*?)"/is',
					'onkeypress' => '/onkeypress="(.*?)"/is',
					'onblur' => '/onblur="(.*?)"/is',
					'oncopy' => '/oncopy="(.*?)"/is',
					'oncut' => '/oncut="(.*?)"/is',
					'onpaste' => '/onpaste="(.*?)"/is',
					'php-tag' => '/<(\?|\%)\=?(php)?/',
					'php-short-tag' => '/(\%|\?)>/'
				);

				if(!empty($type))
				{
					unset($search[$type]);
				}
				
				$output = preg_replace($search, '', $input);
			}

			return $output;
		}

		/* Kiểm tra dữ liệu nhập vào */
		public function sanitize($input='', $type='')
		{
			if(is_array($input))
			{
				foreach($input as $var=>$val)
				{
					$output[$var] = $this->sanitize($val, $type);
				}
			}
			else
			{
				$output  = $this->cleanInput($input, $type);
			}

			return $output;
		}

		/* Kiểm tra đăng nhập */
		public function checkLoginAdmin()
		{
			global $loginAdmin;

			$token = (!empty($_SESSION[$loginAdmin]['token'])) ? $_SESSION[$loginAdmin]['token'] : '';
			$row = $this->d->rawQuery("select secret_key from #_user where secret_key = ? and find_in_set('hienthi',status)",array($token));

			if(count($row) == 1 && $row[0]['secret_key'] != '')
			{
				return true;
			}
			else
			{
				if(!empty($_SESSION[TOKEN])) unset($_SESSION[TOKEN]);
				unset($_SESSION[$loginAdmin]);
				return false;
			}
		}

		/* Mã hóa mật khẩu admin */
		public function encryptPassword($secret='', $str='', $salt='')
		{
			return md5($secret.$str.$salt);
		}

		/* Kiểm tra phân quyền menu */
		public function checkPermission($com='', $act='', $type='', $array=null, $case='')
		{
			global $loginAdmin;
			
			$str = $com;
			
			if($act) $str .= '_'.$act;

			if($case == 'phrase-1')
			{
				if($type!='') $str .= '_'.$type;
				if(!in_array($str, $_SESSION[$loginAdmin]['permissions'])) return true;
				else return false;
			}
			else if($case == 'phrase-2')
			{
				$count = 0;

				if($array)
				{
					foreach($array as $key => $value)
					{
						if(!empty($value['dropdown']))
						{
							unset($array[$key]);
						}
					}

					foreach($array as $key => $value)
					{
						if(!in_array($str."_".$key, $_SESSION[$loginAdmin]['permissions'])) $count++;
					}

					if($count == count($array)) return true;
				}
				else return false;
			}

			return false;
		}

		/* Kiểm tra phân quyền */
		public function checkRole()
		{
			global $config, $loginAdmin;
			
			if((!empty($_SESSION[$loginAdmin]['role']) && $_SESSION[$loginAdmin]['role'] == 3) || !empty($config['website']['debug-developer'])) return false;
			else return true;
		}

		/* Lấy tình trạng nhận tin */
		public function getStatusNewsletter($confirm_status=0, $type='')
		{
			global $config;

			$loai = '';

			if(!empty($config['newsletter'][$type]['confirm_status']))
			{
				foreach($config['newsletter'][$type]['confirm_status'] as $key => $value)
				{
					if($key == $confirm_status)
					{
						$loai = $value;
						break;
					}
				}
			}

			if($loai == '') $loai="Mới / chưa xử lý";

			return $loai;
		}

		/* Database maintenance */
		public function databaseMaintenance($action='', $tables=array())
		{
			$result = array();
			$row = array();

			if(!empty($action) && !empty($tables))
			{
				foreach($tables as $k => $v)
				{
					foreach($v as $table)
					{
						$result = $this->d->rawQuery("$action TABLE $table");
						
						if(!empty($result))
						{
							$row[$k]['table'] = $result[0]['Table'];
							$row[$k]['action'] = $result[0]['Op'];
							$row[$k]['type'] = $result[0]['Msg_type'];
							$row[$k]['text'] = $result[0]['Msg_text'];
						}
					}
				}
			}

			return $row;
		}

		/* Giữ mã model (vd "JD-8500L") và vài cụm từ có nghĩa hay gặp trong tên sản
		   phẩm (vd "Mặt Trời") không bị trình duyệt ngắt dòng giữa chừng - dấu "-"
		   thường (U+002D) là điểm ngắt dòng hợp lệ theo thuật toán line-break
		   Unicode dù có word-break:keep-all, và khoảng trắng thường giữa 2 âm tiết
		   một cụm từ cũng luôn là điểm ngắt. Thay dấu gạch ngang giữa chữ/số bằng
		   non-breaking hyphen (U+2011), và nối các cụm từ trong $noBreakPhrases
		   bằng non-breaking space (audit UX 2026-09-08, phản hồi "JD xuống dòng
		   -5434" + "mặt trời không nên xuống dòng"). */
		public function noBreakModelCode($str='')
		{
			$str = preg_replace('/(?<=[A-Za-z0-9])-(?=[A-Za-z0-9])/u', "\xE2\x80\x91", (string)$str);
			$noBreakPhrases = array('Năng Lượng Mặt Trời', 'năng lượng mặt trời', 'Mặt Trời', 'mặt trời', 'Phúc Thịnh Solar', 'Phúc Thịnh');
			foreach($noBreakPhrases as $phrase)
			{
				$str = str_replace($phrase, str_replace(' ', "\xC2\xA0", $phrase), $str);
			}
			return $str;
		}

		/* Format money */
		public function formatMoney($price=0, $unit='đ', $html=false)
		{
			$str = '';

			if($price)
			{
				$str .= number_format($price, 0, ',', '.');
				if($unit != '')
				{
					if($html)
					{
						$str .= '<span>'.$unit.'</span>';
					}
					else
					{
						$str .= $unit;
					}
				}
			}

			return $str;
		}

		/* Is phone */
		public function isPhone($number)
		{
			$number = trim($number);
			if(preg_match('/(03|05|07|08|09|01[2|6|8|9])+([0-9]{8})\b/', $number) && strlen($number) == 10)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Format phone */
		public function formatPhone($number, $dash=' ')
		{
			if(preg_match('/^(\d{4})(\d{3})(\d{3})$/', $number, $matches))
			{
				return $matches[1].$dash.$matches[2].$dash.$matches[3];
			}
		}

		/* Parse phone */
		public function parsePhone($number)
		{
			return (!empty($number)) ? preg_replace('/[^0-9]/', '', $number) : '';
		}

		/* Check letters and nums */
		public function isAlphaNum($str)
		{
			if(preg_match('/^[a-z0-9]+$/', $str))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Is email */
		public function isEmail($email)
		{
			if(filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Is match */
		public function isMatch($value1, $value2)
		{
			if($value1 == $value2)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Is decimal */
		public function isDecimal($number)
		{
			if(preg_match('/^\d{1,10}(\.\d{1,4})?$/', $number))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Is coordinates */
		public function isCoords($str)
		{
			if(preg_match('/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/', $str))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Is url */
		public function isUrl($str)
		{
			if(preg_match('/^(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})/', $str))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Is url youtube */
		public function isYoutube($str)
		{
			if(preg_match('/https?:\/\/(?:[a-zA_Z]{2,3}.)?(?:youtube\.com\/watch\?)((?:[\w\d\-\_\=]+&amp;(?:amp;)?)*v(?:&lt;[A-Z]+&gt;)?=([0-9a-zA-Z\-\_]+))/i', $str))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Is fanpage */
		public function isFanpage($str)
		{
			if(preg_match('/^(https?:\/\/)?(?:www\.)?facebook\.com\/(?:(?:\w)*#!\/)?(?:pages\/)?(?:[\w\-]*\/)*([\w\-\.]*)/', $str))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Is date */
		public function isDate($str)
		{
			if(preg_match('/^([0-2][0-9]|(3)[0-1])(\/)(((0)[0-9])|((1)[0-2]))(\/)\d{4}$/', $str))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Is number */
		public function isNumber($numbs)
		{
			if(preg_match('/^[0-9]+$/', $numbs))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/* Check account */
		public function checkAccount($data='', $type='', $tbl='', $id=0)
		{
			$result = false;
			$row = array();

			if(!empty($data) && !empty($type) && !empty($tbl))
			{
				$where = (!empty($id)) ? ' and id != '.$id : '';
				$row = $this->d->rawQueryOne("select id from #_$tbl where $type = ? $where limit 0,1", array($data));

				if(!empty($row))
				{
					$result = true;
				}
			}

			return $result;
		}

		/* Check title */
		public function checkTitle($data=array())
		{
			global $config;

			$result = array();

			foreach($config['website']['lang'] as $k => $v)
			{
				if(isset($data['name'.$k]))
				{
					$title = trim($data['name'.$k]);

					if(empty($title))
					{
						$result[] = 'Tiêu đề ('.$v.') không được trống';
					}
				}
			}

			return $result;
		}

		/* Check slug */
		public function checkSlug($data=array())
		{
			$result = 'valid';

			if(isset($data['slug']))
			{
				$slug = trim($data['slug']);

				if(!empty($slug))
				{
					$table = array(
						"#_product_list",
						"#_product_cat",
						"#_product_item",
						"#_product_sub",
						"#_product_brand",
						"#_product",
						"#_news_list",
						"#_news_cat",
						"#_news_item",
						"#_news_sub",
						"#_news",
						"#_tags"
					);

					$excludeId = (!empty($data['id']) && empty($data['copy'])) ? (int)$data['id'] : 0;

					foreach($table as $v)
					{
						$where = $excludeId > 0 ? "id != ? and " : "";
						$params = $excludeId > 0 ? array($excludeId, $data['slug'], $data['slug']) : array($data['slug'], $data['slug']);
						$check = $this->d->rawQueryOne("select id from $v where $where (slugvi = ? or slugen = ?) limit 0,1", $params);

						if(!empty($check['id']))
						{
							$result = 'exist';
							break;
						}
					}
				}
				else
				{
					$result = 'empty';
				}
			}

			return $result;
		}

		/* Check recaptcha */
		public function checkRecaptcha($response='')
		{
			global $config;

			$result = null;
			$active = $config['googleAPI']['recaptcha']['active'];

			if($active == true && $response != '')
		    {
		        $recaptcha = file_get_contents($config['googleAPI']['recaptcha']['urlapi'].'?secret='.$config['googleAPI']['recaptcha']['secretkey'].'&response='.$response);
				$recaptcha = json_decode($recaptcha);
				$result['score'] = $recaptcha->score;
				$result['action'] = $recaptcha->action;
		    }
		    else if(!$active)
		    {
		    	$result['test'] = true;
		    }

		    return $result;
		}

		/* Login */
		public function checkLoginMember()
		{
			global $configBase, $loginMember;

			if(!empty($_SESSION[$loginMember]) || !empty($_COOKIE['login_member_id']))
			{
				$flag = true;
				$iduser = (!empty($_COOKIE['login_member_id'])) ? $_COOKIE['login_member_id'] : $_SESSION[$loginMember]['id'];

				if($iduser)
				{
					$row = $this->d->rawQueryOne("select login_session, id, username, phone, address, email, fullname from #_member where id = ? and find_in_set('hienthi',status)",array($iduser));

					if(!empty($row['id']))
				    {
				    	$login_session = (!empty($_COOKIE['login_member_session'])) ? $_COOKIE['login_member_session'] : $_SESSION[$loginMember]['login_session'];

				    	if($login_session == $row['login_session'])
				    	{
				    		$_SESSION[$loginMember]['active'] = true;
					        $_SESSION[$loginMember]['id'] = $row['id'];
					        $_SESSION[$loginMember]['username'] = $row['username'];
					        $_SESSION[$loginMember]['phone'] = $row['phone'];
					        $_SESSION[$loginMember]['address'] = $row['address'];
					        $_SESSION[$loginMember]['email'] = $row['email'];
					        $_SESSION[$loginMember]['fullname'] = $row['fullname'];
				    	}
				    	else $flag = false;
				    }
				    else $flag = false;

				    if(!$flag)
				    {
						unset($_SESSION[$loginMember]);
						setcookie('login_member_id',"",-1,'/');
						setcookie('login_member_session',"",-1,'/');

						$this->transfer("Tài khoản của bạn đã hết hạn đăng nhập hoặc đã đăng nhập trên thiết bị khác", $configBase, false);
				    }
				}
			}
		}

		/* Lấy youtube */
		public function getYoutube($url='') 
		{
			if($url != '')
			{
			    $parts = parse_url($url);
			    if(isset($parts['query'])) 
			    {
			        parse_str($parts['query'], $qs);
			        if(isset($qs['v'])) return $qs['v'];
			        else if($qs['vi']) return $qs['vi'];
			    }

			    if(isset($parts['path']))
			    {
			        $path = explode('/', trim($parts['path'], '/'));
			        return $path[count($path) - 1];
			    }
			}

		    return false;
		}

		/* URL thumbnail dùng chung cho các template tự render thẻ img/background.
		   SEO/social image vẫn giữ định dạng gốc để tương thích crawler cũ. */
		public function getThumbUrl($sizes='', $path='', $preferWebp=true)
		{
			$url = ASSET.THUMBS.'/'.trim((string) $sizes, '/').'/'.ltrim((string) $path, '/');
			$extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
			if($preferWebp && $this->canEncodeWebp() && in_array($extension, array('jpg','jpeg','png'), true)) $url .= '.webp';
			return $url;
		}

		/* Get image */
		// public function getImage($data=array())
		// {
		// 	global $config;

		// 	/* Defaults */
		// 	$defaults = [
		// 		'class' => 'lazy',
		// 		'id' => '',
		// 		'isLazy' => true,
		// 		'thumbs' => THUMBS,
		// 		'isWatermark' => false,
		// 		'watermark' => (defined('WATERMARK')) ? WATERMARK : '',
		// 		'prefix' => '',
		// 		'size-error' => '',
		// 		'size-src' => '',
		// 		'sizes' => '',
		// 		'url' => '',
		// 		'upload' => '',
		// 		'image' => '',
		// 		'upload-error' => 'assets/images/',
		// 		'image-error' => 'noimage.png',
		// 		'alt' => ''
		// 	];

		// 	/* Data */
		// 	$info = array_merge($defaults, $data);

		// 	/* Upload - Image */
		// 	if(empty($info['upload']) || empty($info['image']))
		// 	{
		// 		$info['upload'] = $info['upload-error'];
		// 		$info['image'] = $info['image-error'];
		// 	}

		// 	/* Size */
		// 	if(!empty($info['sizes']))
		// 	{
		// 		$info['size-error'] = $info['size-src'] = $info['sizes'];
		// 	}

		// 	/* Path origin */
		// 	$info['pathOrigin'] = $info['upload'].$info['image'];

		// 	/* Path src */
		// 	if(!empty($info['url']))
		// 	{
		// 		$info['pathSrc'] = $info['url'];
		// 	}
		// 	else
		// 	{
		// 		if(!empty($info['size-src']))
		// 		{
		// 			$info['pathSize'] = $info['size-src']."/".$info['upload'].$info['image'];
		// 			$info['pathSrc'] = (!empty($info['isWatermark']) && !empty($info['prefix'])) ? ASSET.$info['watermark']."/".$info['prefix']."/".$info['pathSize'] : ASSET.$info['thumbs']."/".$info['pathSize'];
		// 		}
		// 		else
		// 		{
		// 			$info['pathSrc'] = ASSET.$info['pathOrigin'];
		// 		}
		// 	}

		// 	/* Path error */
		// 	$info['pathError'] = ASSET.$info['thumbs']."/".$info['size-error']."/".$info['upload-error'].$info['image-error'];

		// 	/* Class */
		// 	$info['class'] = (empty($info['isLazy'])) ? str_replace('lazy', '', $info['class']) : $info['class'];
		// 	$info['class'] = (!empty($info['class'])) ? "class='".$info['class']."'" : "";

		// 	/* Id */
		// 	$info['id'] = (!empty($info['id'])) ? "id='".$info['id']."'" : "";

		// 	/* Check to convert Webp */
		// 	$info['hasURL'] = false;

		// 	if(filter_var(str_replace(ASSET, "", $info['pathSrc']), FILTER_VALIDATE_URL))
		// 	{
		// 		$info['hasURL'] = true;
		// 	}
			
		// 	if($config['website']['image']['hasWebp'])
		// 	{
		// 		if(!$info['sizes'])
		// 		{
		// 			if(!$info['hasURL'])
		// 			{
		// 				$this->converWebp($info['pathSrc']);
		// 			}
		// 		}

		// 		if(!$info['hasURL'])
		// 		{
		// 			$info['pathSrc'] .= '.webp';
		// 		}
		// 	}

		// 	/* Src */
		// 	$info['src'] = (!empty($info['isLazy']) && strpos($info['class'], 'lazy') !== false) ? "data-src='".$info['pathSrc']."'" : "src='".$info['pathSrc']."'";

		// 	/* Image */
		// 	$result = "<img ".$info['class']." ".$info['id']." onerror=\"this.src='".$info['pathError']."';\" ".$info['src']." alt='".$info['alt']."'/>";

		// 	return $result;
		// }
		public function getImage($data = array())
{
    global $config;

    /* Defaults */
    $defaults = [
        'class' => 'lazy',
        'id' => '',
        'isLazy' => true,
        'thumbs' => THUMBS,
        'isWatermark' => false,
        'watermark' => (defined('WATERMARK')) ? WATERMARK : '',
        'prefix' => '',
        'size-error' => '',
        'size-src' => '',
        'sizes' => '',
        'url' => '',
        'upload' => '',
        'image' => '',
        'upload-error' => 'assets/images/',
        'image-error' => 'noimage.png',
        'alt' => ''
    ];

    /* Data */
    $info = array_merge($defaults, $data);

    /* Upload - Image */
    if (empty($info['upload']) || empty($info['image'])) {
        $info['upload'] = $info['upload-error'];
        $info['image'] = $info['image-error'];
    }

    /* Size */
    if (!empty($info['sizes'])) {
        $info['size-error'] = $info['size-src'] = $info['sizes'];
    }

    /* Path origin */
    $info['pathOrigin'] = $info['upload'] . $info['image'];

    /* Path src */
    if (!empty($info['url'])) {
        $info['pathSrc'] = $info['url'];
    } else {
        $ext = strtolower(pathinfo($info['image'], PATHINFO_EXTENSION));

        // Nếu là .webp thì bỏ qua thumbs, load ảnh gốc
        if ($ext === 'webp') {
            $info['pathSrc'] = ASSET . $info['pathOrigin'];
        } else {
            if (!empty($info['size-src'])) {
                $info['pathSize'] = $info['size-src'] . "/" . $info['upload'] . $info['image'];
                $info['pathSrc'] = (!empty($info['isWatermark']) && !empty($info['prefix']))
                    ? ASSET . $info['watermark'] . "/" . $info['prefix'] . "/" . $info['pathSize']
                    : ASSET . $info['thumbs'] . "/" . $info['pathSize'];
            } else {
                $info['pathSrc'] = ASSET . $info['pathOrigin'];
            }
        }
    }

    /* Path error */
    $info['pathError'] = ASSET . $info['thumbs'] . "/" . $info['size-error'] . "/" . $info['upload-error'] . $info['image-error'];

    /* Class */
    $info['class'] = (empty($info['isLazy'])) ? str_replace('lazy', '', $info['class']) : $info['class'];
    $info['class'] = (!empty($info['class'])) ? "class='" . $info['class'] . "'" : "";

    /* Id */
    $info['id'] = (!empty($info['id'])) ? "id='" . $info['id'] . "'" : "";

    /* Check to convert Webp */
    $info['hasURL'] = false;
    if (filter_var(str_replace(ASSET, "", $info['pathSrc']), FILTER_VALIDATE_URL)) {
        $info['hasURL'] = true;
    }

	/* Chỉ phát URL WebP khi runtime có codec. Ảnh gốc không resize phải tạo
	   biến thể thành công trước; thumbnail sẽ được createThumb tạo theo yêu cầu. */
	$imageExtension = strtolower(pathinfo($info['image'], PATHINFO_EXTENSION));
	$canUseWebp = $this->canEncodeWebp()
		&& in_array($imageExtension, array('jpg','jpeg','png'), true)
		&& !$info['hasURL'];
	if ($canUseWebp) {
		$useWebp = !empty($info['sizes']);
		if (!$useWebp) {
			/* Không encode ảnh trong lúc render HTML. Upload đã tạo WebP; nếu
			   sidecar chưa có thì dùng ảnh gốc để TTFB không bất chợt tăng. */
			$useWebp = $this->hasFreshWebpVariant($info['pathSrc']);
		}
		if ($useWebp) $info['pathSrc'] .= '.webp';
	}

    /* Src */
    $isJsLazy = (!empty($info['isLazy']) && strpos($info['class'], 'lazy') !== false);
    $info['src'] = $isJsLazy
        ? "data-src='" . $info['pathSrc'] . "'"
        : "src='" . $info['pathSrc'] . "'";

    /* Lazy-load NATIVE của trình duyệt cho ảnh phát src ngay (2026-09-16).
       Vì sao: đo trang chủ thấy 91 thẻ img, trong đó 77 ảnh nằm DƯỚI màn hình
       nhưng vẫn tải ngay -> 68 file WebP = 1.2 MB tải một lượt, 126 request,
       đúng nguyên nhân chủ project báo "hơi đơ và lag".
       Dùng loading="lazy" của trình duyệt thay vì thư viện lazy JS legacy:
       không cần JS, không bị lỗi "card trắng" khi thư viện bỏ qua section nằm
       sâu trong trang (chính lý do trước đây phải tắt lazy cho card sản phẩm),
       và trình duyệt tự quyết định tải khi ảnh gần viewport.
       Ảnh đã dùng lazy JS thì giữ nguyên, không gắn hai cơ chế lên một thẻ.
       Ảnh ưu tiên (LCP: logo, hero) có thể tắt bằng 'noNativeLazy' => true.

       Ảnh trong CAROUSEL phải truyền 'noNativeLazy' => true: slider dùng
       transform đẩy slide ra ngoài vùng nhìn nên trình duyệt hoãn tải vô thời
       hạn - đo trang chi tiết sản phẩm thấy 10/15 ảnh "Sản phẩm liên quan" có
       src trả 200 nhưng naturalWidth=0, chỉ tải khi bấm mũi tên (ô trắng chớp).
       Lưu ý KHÔNG suy ra điều đó từ 'isLazy' => false: cờ đó chỉ có nghĩa "tắt
       lazy JS", và lưới sản phẩm tĩnh ở trang chủ cũng dùng nó (để tránh lỗi
       card trắng của thư viện JS legacy) - những ảnh đó vẫn cần native lazy vì
       chúng chiếm phần lớn dung lượng trang. */
    $nativeLazy = (!$isJsLazy && empty($info['noNativeLazy']))
        ? " loading='lazy' decoding='async'"
        : '';

    /* Image */
    $result = "<img " . $info['class'] . " " . $info['id'] . " onerror=\"this.src='" . $info['pathError'] . "';\" " . $info['src'] . $nativeLazy . " alt='" . $info['alt'] . "'/>";

    return $result;
}

		

		/* Get list gallery */
		public function listsGallery($file='')
		{
			$result = array();

			if(!empty($file) && !empty($_POST['fileuploader-list-'.$file]))
			{
				$fileLists = '';
				$fileLists = str_replace('"','',$_POST['fileuploader-list-'.$file]);
				$fileLists = str_replace('[','',$fileLists);
				$fileLists = str_replace(']','',$fileLists);
				$fileLists = str_replace('{','',$fileLists);
				$fileLists = str_replace('}','',$fileLists);
				$fileLists = str_replace('0:/','',$fileLists);
				$fileLists = str_replace('file:','',$fileLists);
				$result = explode(',',$fileLists);
			}

			return $result;
		}

		/* Template gallery */
		public function galleryFiler($numb=1, $id=0, $photo='', $name='', $folder='', $col='')
		{
			/* Params */
			$params = array();
			$params['numb'] = $numb;
			$params['id'] = $id;
			$params['photo'] = $photo;
			$params['name'] = $name;
			$params['folder'] = $folder;
			$params['col'] = $col;

			/* Get markdown */
			$str = $this->markdown('gallery/admin', $params);
			
			return $str;
		}

		/* Delete gallery */
		public function deleteGallery()
		{
			$row = $this->d->rawQuery("select id, com, photo from #_gallery where hash != '' and date_created < ?", array(time()-3*3600));
			$array = array("product" => UPLOAD_PRODUCT, "news" => UPLOAD_NEWS);

			if($row)
			{
				$ids = array();
				foreach($row as $item)
				{
					@unlink($array[$item['com']].$item['photo']);
					$ids[] = (int)$item['id'];
				}
				/* Một câu DELETE cho cả lô thay vì một câu cho từng dòng. */
				$placeholders = implode(',', array_fill(0, count($ids), '?'));
				$this->d->rawQuery("delete from #_gallery where id in ($placeholders)", $ids);
			}
		}
		
		/* Generate hash */
		public function generateHash()
		{
			if(!$this->hash)
			{
				$this->hash = $this->stringRandom(10);
			}
			return $this->hash;
		}

		/* Lấy date */
		public function makeDate($time=0, $dot='.', $lang='vi', $f=false)
		{
			$str = ($lang == 'vi') ? date("d{$dot}m{$dot}Y",$time) : date("m{$dot}d{$dot}Y",$time);

			if($f == true)
			{
				$thu['vi'] = array('Chủ nhật','Thứ hai','Thứ ba','Thứ tư','Thứ năm','Thứ sáu','Thứ bảy');
				$thu['en'] = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
				$str = $thu[$lang][date('w',$time)].', '.$str;
			}

			return $str;
		}

		/* Alert */
		public function alert($notify='')
		{
			echo '<script language="javascript">alert("'.$notify.'")</script>';
		}

		/* Delete file */
		public function deleteFile($file='')
		{
			return @unlink($file);
		}

		/* Transfer */
		public function transfer($msg='', $page='', $numb=true)
		{
			/* Chuyển trang ngay, không còn qua màn "Thông báo" đếm ngược 4.5 giây và
			   nút "Click vào đây nếu không muốn đợi lâu" nữa — cả Admin lẫn công khai
			   (liên hệ, đặt hàng, đăng ký...). Kết quả hiện bằng toast (showNotify)
			   trên trang đích thay vì bắt đợi cả một trang trung gian. */
			global $flash;
			if(!empty($msg) && isset($flash) && is_object($flash))
			{
				$flash->set('toast', json_encode(array('status' => $numb ? 'success' : 'error', 'text' => (string)$msg), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			}
			$this->redirect($page);
		}

		/* Redirect */
		public function redirect($url='', $response=null)
		{
			header("location:$url", true, $response);
			exit();
		}

		/* Dump */
		public function dump($value='', $exit=false)
		{
			echo "<pre>";	
			print_r($value);
			echo "</pre>";
			if($exit) exit();
		}

		/* Pagination */
		public function pagination($totalq=0, $perPage=10, $page=1, $url='?')
		{
			$urlpos = strpos($url, "?");
			$url = ($urlpos) ? $url."&" : $url."?";
			$total = $totalq;
			$adjacents = "2";
			$firstlabel = "First";
			$prevlabel = "Prev";
			$nextlabel = "Next";
			$lastlabel = "Last";
			$page = ($page == 0 ? 1 : $page);
			$start = ($page - 1) * $perPage;
			$prev = $page - 1;
			$next = $page + 1;
			$lastpage = ceil($total/$perPage);
			$lpm1 = $lastpage - 1;
			$pagination = "";

			if($lastpage > 1)
			{
				$pagination .= "<ul class='pagination flex-wrap justify-content-center mb-0'>";
				// $pagination .= "<li class='page-item'><a class='page-link'>Page {$page} / {$lastpage}</a></li>";

				if($page > 1)
				{
					$pagination.= "<li class='page-item'><a class='page-link' href='{$this->getCurrentPageURL()}'>{$firstlabel}</a></li>";
					$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$prev}'>{$prevlabel}</a></li>";
				}

				if($lastpage < 7 + ($adjacents * 2))
				{
					for($counter = 1; $counter <= $lastpage; $counter++)
					{
						if($counter == $page) $pagination.= "<li class='page-item active'><a class='page-link'>{$counter}</a></li>";
						else $pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$counter}'>{$counter}</a></li>";
					}
				}
				elseif($lastpage > 5 + ($adjacents * 2))
				{
					if($page < 1 + ($adjacents * 2))
					{
						for($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
						{
							if($counter == $page) $pagination.= "<li class='page-item active'><a class='page-link'>{$counter}</a></li>";
							else $pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$counter}'>{$counter}</a></li>";
						}

						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p=1'>...</a></li>";
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$lpm1}'>{$lpm1}</a></li>";
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$lastpage}'>{$lastpage}</a></li>";
					}
					elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
					{
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p=1'>1</a></li>";
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p=2'>2</a></li>";
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p=1'>...</a></li>";

						for($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
						{
							if($counter == $page) $pagination.= "<li class='page-item active'><a class='page-link'>{$counter}</a></li>";
							else $pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$counter}'>{$counter}</a></li>";
						}

						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p=1'>...</a></li>";
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$lpm1}'>{$lpm1}</a></li>";
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$lastpage}'>{$lastpage}</a></li>";
					}
					else
					{
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p=1'>1</a></li>";
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p=2'>2</a></li>";
						$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p=1'>...</a></li>";

						for($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
						{
							if($counter == $page) $pagination.= "<li class='page-item active'><a class='page-link'>{$counter}</a></li>";
							else $pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$counter}'>{$counter}</a></li>";
						}
					}
				}

				if($page < $counter - 1)
				{
					$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p={$next}'>{$nextlabel}</a></li>";
					$pagination.= "<li class='page-item'><a class='page-link' href='{$url}p=$lastpage'>{$lastlabel}</a></li>";
				}

				$pagination.= "</ul>";
			}

			return $pagination;
		}

		/* UTF8 convert */
		public function utf8Convert($str='')
		{
			if($str != '')
			{
				$utf8 = array(
					'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ|Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
					'd'=>'đ|Đ',
					'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ|É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
					'i'=>'í|ì|ỉ|ĩ|ị|Í|Ì|Ỉ|Ĩ|Ị',
					'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ|Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
					'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự|Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
					'y'=>'ý|ỳ|ỷ|ỹ|ỵ|Ý|Ỳ|Ỷ|Ỹ|Ỵ',
					''=>'`|\~|\!|\@|\#|\||\$|\%|\^|\&|\*|\(|\)|\+|\=|\,|\.|\/|\?|\>|\<|\'|\"|\“|\”|\:|\;|_',
				);

				foreach($utf8 as $ascii => $uni)
				{
					$str = preg_replace("/($uni)/i",$ascii,$str);
				}
			}

			return $str;
		}

		/* Change title */
		public function changeTitle($text='')
		{
			if($text != '')
			{
				$text = strtolower($this->utf8Convert($text));
				$text = preg_replace("/[^a-z0-9-\s]/", "",$text);
				$text = preg_replace('/([\s]+)/', '-', $text);
				$text = str_replace(array('%20', ' '), '-', $text);
				$text = preg_replace("/\-\-\-\-\-/","-",$text);
				$text = preg_replace("/\-\-\-\-/","-",$text);
				$text = preg_replace("/\-\-\-/","-",$text);
				$text = preg_replace("/\-\-/","-",$text);
				$text = '@'.$text.'@';
				$text = preg_replace('/\@\-|\-\@|\@/', '', $text);	
			}

			return $text;
		}

		/* Lấy IP */
		public function getRealIPAddress()
		{
			if(!empty($_SERVER['HTTP_CLIENT_IP']))
			{
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			}
			elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			else
			{
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			return $ip;
		}

		/* Lấy getPageURL */
		public function getPageURL()
		{
			$isHttps = (isset($_SERVER['HTTPS']) && (strtolower((string) $_SERVER['HTTPS']) === 'on' || (string) $_SERVER['HTTPS'] === '1'))
				|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) === 'https');
			$host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
			return ($isHttps ? 'https://' : 'http://').$host.$_SERVER['REQUEST_URI'];
		}

		/* Lấy getCurrentPageURL */
		public function getCurrentPageURL()
		{
			$isForwarded = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && trim((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) !== '';
			$isHttps = (isset($_SERVER['HTTPS']) && (strtolower((string) $_SERVER['HTTPS']) === 'on' || (string) $_SERVER['HTTPS'] === '1'))
				|| ($isForwarded && strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) === 'https');
			if(!empty($_SERVER['HTTP_HOST']))
			{
				/* HTTP_HOST đã mang cổng public nếu có; không ghép cổng backend
				   (thường là 80) vào URL HTTPS của reverse proxy. */
				$host = $_SERVER['HTTP_HOST'];
			}
			else
			{
				$port = (!empty($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : '';
				$isDefaultPort = ($port == '' || ($isHttps && $port == '443') || (!$isHttps && $port == '80') || $isForwarded);
				$host = $_SERVER['SERVER_NAME'].(($isDefaultPort) ? '' : ':'.$port);
			}
			$pageURL = ($isHttps ? 'https://' : 'http://').$host.$_SERVER['REQUEST_URI'];
			$urlpos = strpos($pageURL, "?p");
			$pageURL = ($urlpos) ? explode("?p=", $pageURL) : explode("&p=", $pageURL);
			return $pageURL[0];
		}

		/* Lấy getCurrentPageURL Cano */
		public function getCurrentPageURL_CANO()
		{
			$pageURL = $this->getCurrentPageURL();
			$pageURL = str_replace("amp/", "", $pageURL);
			$pageURL = explode("?", $pageURL, 2)[0];
			$pageURL = explode("#", $pageURL, 2)[0];
			return preg_replace('~/index(?:\.php)?/?$~', '/', $pageURL);
		}

		/* Has file */
		public function hasFile($file)
		{
			if(isset($_FILES[$file]))
			{
				if($_FILES[$file]['error'] == 4)
				{
					return false;
				}
				else if($_FILES[$file]['error'] == 0)
				{
					return true;
				}
			}
			else
			{
				return false;
			}
		}

		/* Size file */
		public function sizeFile($file)
		{
			if($this->hasFile($file))
			{
				if($_FILES[$file]['error'] == 0)
				{
					return $_FILES[$file]['size'];
				}
			}
			else
			{
				return 0;
			}
		}

		/* Check file */
		public function checkFile($file)
		{
			global $config;

			$result = true;

			if($this->hasFile($file))
			{
				if($this->sizeFile($file) > $config['website']['video']['max-size'])
				{
					$result = false;
				}
			}

			return $result;
		}

		/* Check extension file */
		public function checkExtFile($file)
		{
			global $config;

			$result = true;

			if($this->hasFile($file))
			{
				$ext = $this->infoPath($_FILES[$file]["name"], 'extension');

				if(!in_array($ext, $config['website']['video']['extension']))
				{
					$result = false;
				}
			}

			return $result;
		}

		/* Info path */
		public function infoPath($filename='', $type='')
		{
			$result = '';

			if(!empty($filename) && !empty($type))
			{
				if($type == 'extension')
				{
					$result = pathinfo($filename, PATHINFO_EXTENSION);
				}
				else if($type == 'filename')
				{
					$result = pathinfo($filename, PATHINFO_FILENAME);
				}
			}

			return $result;
		}

		/* Format bytes */
		public function formatBytes($size, $precision=2)
		{
			$result = array();
			$base = log($size, 1024);
			$suffixes = array('', 'Kb', 'Mb', 'Gb', 'Tb');
			$result['numb'] = round(pow(1024, $base - floor($base)), $precision);
			$result['ext'] = $suffixes[floor($base)];

			return $result;
		}

		/* Copy image */
		public function copyImg($photo='', $constant='')
		{
			$str = '';

			if($photo != '' && $constant != '')
			{
				$rand = rand(1000,9999);
				$name = pathinfo($photo, PATHINFO_FILENAME);
				$ext = pathinfo($photo, PATHINFO_EXTENSION);
				$photo_new = $name.'-'.$rand.'.'.$ext;
				$oldpath = '../../upload/'.$constant.'/'.$photo;
				$newpath = '../../upload/'.$constant.'/'.$photo_new;

				if(file_exists($oldpath))
				{
					if(copy($oldpath,$newpath))
					{
						$str = $photo_new;
					}
				}
			}

			return $str;
		}

		/* Get Img size */
		public function getImgSize($photo='', $patch='')
		{
			$array = array();
			if($photo != '')
			{
				$x = (file_exists($patch)) ? getimagesize($patch) : null;
				$array = (!empty($x)) ? array("p"=>$photo,"w"=>$x[0],"h"=>$x[1],"m"=>$x['mime']) : null;
			}
			return $array;
		}

		/* Upload name */
		public function uploadName($name='')
		{
			$result = '';

			if($name != '')
			{
				$rand = rand(1000,9999);
				$ten_anh = pathinfo($name, PATHINFO_FILENAME);
				$result = $this->changeTitle($ten_anh)."-".$rand;
			}

			return $result;
		}

		/* Resize images */
		public function smartResizeImage($file='', $string=null, $width=0, $height=0, $proportional=false, $output='file', $delete_original=true, $use_linux_commands=false, $quality=100, $grayscale=false)
		{
		    if($height <= 0 && $width <= 0) return false;
		    if($file === null && $string === null) return false;
		    $info = $file !== null ? getimagesize($file) : getimagesizefromstring($string);
		    $image = '';
		    $final_width = 0;
		    $final_height = 0;
		    list($width_old, $height_old) = $info;
		    $cropHeight = $cropWidth = 0;
		    if($proportional)
		    {
		        if($width == 0) $factor = $height / $height_old;
		        elseif($height == 0) $factor = $width / $width_old;
		        else $factor = min($width / $width_old, $height / $height_old);
		        $final_width = round($width_old * $factor);
		        $final_height = round($height_old * $factor);
		    }
		    else
		    {
		        $final_width = ($width <= 0) ? $width_old : $width;
		        $final_height = ($height <= 0) ? $height_old : $height;
		        $widthX = $width_old / $width;
		        $heightX = $height_old / $height;
		        $x = min($widthX, $heightX);
		        $cropWidth = ($width_old - $width * $x) / 2;
		        $cropHeight = ($height_old - $height * $x) / 2;
		    }
		    switch($info[2])
		    {
		        case IMAGETYPE_JPEG:
		            $file !== null ? $image = imagecreatefromjpeg($file) : $image = imagecreatefromstring($string);
		        break;
		        case IMAGETYPE_GIF:
		            $file !== null ? $image = imagecreatefromgif($file) : $image = imagecreatefromstring($string);
		        break;
		        case IMAGETYPE_PNG:
		            $file !== null ? $image = imagecreatefrompng($file) : $image = imagecreatefromstring($string);
		        break;
		        default:
		            return false;
		    }
		    if($grayscale)
		    {
		        imagefilter($image, IMG_FILTER_GRAYSCALE);
		    }
		    $image_resized = imagecreatetruecolor($final_width, $final_height);
		    if(($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG))
		    {
		        $transparency = imagecolortransparent($image);
		        $palletsize = imagecolorstotal($image);
		        if($transparency >= 0 && $transparency < $palletsize)
		        {
		            $transparent_color = imagecolorsforindex($image, $transparency);
		            $transparency = imagecolorallocate($image_resized, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
		            imagefill($image_resized, 0, 0, $transparency);
		            imagecolortransparent($image_resized, $transparency);
		        }
		        elseif($info[2] == IMAGETYPE_PNG)
		        {
		            imagealphablending($image_resized, false);
		            $color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
		            imagefill($image_resized, 0, 0, $color);
		            imagesavealpha($image_resized, true);
		        }
		    }
		    imagecopyresampled($image_resized, $image, 0, 0, $cropWidth, $cropHeight, $final_width, $final_height, $width_old - 2 * $cropWidth, $height_old - 2 * $cropHeight);
		    if($delete_original)
		    {
		        if($use_linux_commands) exec('rm ' . $file);
		        else @unlink($file);
		    }
		    switch(strtolower($output))
		    {
		        case 'browser':
		            $mime = image_type_to_mime_type($info[2]);
		            header("Content-type: $mime");
		            $output = NULL;
		        break;
		        case 'file':
		            $output = $file;
		        break;
		        case 'return':
		            return $image_resized;
		        break;
		        default:
		        break;
		    }
		    switch($info[2])
		    {
		        case IMAGETYPE_GIF:
		            imagegif($image_resized, $output);
		        break;
		        case IMAGETYPE_JPEG:
		            imagejpeg($image_resized, $output, $quality);
		        break;
		        case IMAGETYPE_PNG:
		            $quality = 9 - (int)((0.9 * $quality) / 10.0);
		            imagepng($image_resized, $output, $quality);
		        break;
		        default:
		            return false;
		    }
		    return true;
		}

		/* Correct images orientation */
		public function correctImageOrientation($filename)
		{
			ini_set('memory_limit', '1024M');
			if(function_exists('exif_read_data'))
			{
				$exif = @exif_read_data($filename);
				if($exif && isset($exif['Orientation']))
				{
					$orientation = $exif['Orientation'];
					if($orientation != 1)
					{
						$img = imagecreatefromjpeg($filename);
						$deg = 0;

						switch($orientation)
						{
							case 3:
							$image = imagerotate($img, 180, 0);
							break;

							case 6:
							$image = imagerotate($img, -90, 0);
							break;

							case 8:
							$image = imagerotate($img, 90, 0);
							break;
						}

						imagejpeg($image, $filename, 90);
					}

				} 
			}
		}

		/* Upload images */
		public function uploadImage($file='', $extension='', $folder='', $newname='')
		{
			global $config;

			if(isset($_FILES[$file]) && !$_FILES[$file]['error'])
			{
				if(!$this->validImageUpload($file, $extension))
				{
					$this->alert('File ảnh không hợp lệ, sai định dạng hoặc vượt quá dung lượng cho phép.');
					return false;
				}
				$postMaxSize = ini_get('post_max_size');
				$MaxSize = explode('M', $postMaxSize);
				$MaxSize = $MaxSize[0];
				if($_FILES[$file]['size'] > $MaxSize*1048576)
				{
					$this->alert('Dung lượng file không được vượt quá '.$postMaxSize);
					return false;
				}

				$ext = explode('.', $_FILES[$file]['name']);
				$ext = strtolower($ext[count($ext)-1]);
				$name = basename($_FILES[$file]['name'], '.'.$ext);

				if(!in_array($ext, $this->uploadAllowedExtensions($extension), true))
				{
					$this->alert('Chỉ hỗ trợ upload file dạng '.$extension);
					return false;
				}

				if($newname=='' && file_exists($folder.$_FILES[$file]['name']))
					for($i=0; $i<100; $i++)
					{
						if(!file_exists($folder.$name.$i.'.'.$ext))
						{
							$_FILES[$file]['name'] = $name.$i.'.'.$ext;
							break;
						}
					}
				else
				{
					$_FILES[$file]['name'] = $newname.'.'.$ext;
				}

				if(!copy($_FILES[$file]["tmp_name"], $folder.$_FILES[$file]['name']))	
				{
					if(!move_uploaded_file($_FILES[$file]["tmp_name"], $folder.$_FILES[$file]['name']))	
					{
						return false;
					}
				}

				/* Fix correct Image Orientation */
				$this->correctImageOrientation($folder.$_FILES[$file]['name']);

				/* Resize image if width origin > config max width */
				$array = @getimagesize($folder.$_FILES[$file]['name']);
				if(!$array) { $this->deleteFile($folder.$_FILES[$file]['name']); return false; }
				list($image_w, $image_h) = $array;
				$maxWidth = $config['website']['upload']['max-width'];
				$maxHeight = $config['website']['upload']['max-height'];
				if($image_w > $maxWidth) $this->smartResizeImage($folder.$_FILES[$file]['name'],null,$maxWidth,$maxHeight,true);

				/* Tạo WebP ngay sau upload khi runtime hỗ trợ. Không để lỗi tối ưu ảnh
				   làm hỏng thao tác lưu nội dung hoặc làm mất ảnh gốc. */
				if($this->canEncodeWebp())
				{
					$webpQuality = !empty($config['website']['image']['webp-quality']) ? (int) $config['website']['image']['webp-quality'] : 82;
					$this->converWebp($folder.$_FILES[$file]['name'], $webpQuality);
				}

				return $_FILES[$file]['name'];
			}
			return false;
		}

		/* Delete folder */
		public function removeDir($dirname='')
		{
			if(is_dir($dirname)) $dir_handle = opendir($dirname);
			if(!isset($dir_handle) || $dir_handle == false) return false;
			while($file = readdir($dir_handle))
			{
				if($file != "." && $file != "..")
				{
					if(!is_dir($dirname."/".$file)) unlink($dirname."/".$file);
					else $this->removeDir($dirname.'/'.$file);
				}
			}
			closedir($dir_handle);
			/* @: xoa het noi dung la du de cache tu sinh lai dung; ban than
			   thu muc watermark/ khong nhat thiet phai mat di, va tren local
			   Docker no khong the rmdir duoc (thu muc cha /var/www/html chi
			   755, khac chu voi www-data) - khong co sudo de chown sua tan
			   goc. Khong @ o day se in Warning ra giua response va lam hong
			   header() redirect ngay sau. */
			@rmdir($dirname);
			return true;
		}

		/* Remove Sub folder */
		public function RemoveEmptySubFolders($path='')
		{
			$empty = true;

			foreach(glob($path.DIRECTORY_SEPARATOR."*") as $file)
			{
				if(is_dir($file))
				{
					if(!$this->RemoveEmptySubFolders($file)) $empty = false;
				}
				else
				{
					$empty = false;
				}
			}

			if($empty)
			{
				if(is_dir($path))
				{
					rmdir($path);
				}
			}

			return $empty;
		}

		/* Remove files from dir in x seconds */
		public function RemoveFilesFromDirInXSeconds($dir='', $seconds=3600)
		{
		    $files = glob(rtrim($dir, '/')."/*");
		    $now = time();

		    if($files)
		    {
			    foreach($files as $file)
			    {
			        if(is_file($file))
			        {
			            if($now - filemtime($file) >= $seconds)
			            {
			                unlink($file);
			            }
			        }
			        else
			        {
			            $this->RemoveFilesFromDirInXSeconds($file,$seconds);
			        }
			    }
		    }
		}

		/* Remove zero bytes */
		public function removeZeroByte($dir)
		{
			$files = glob(rtrim($dir, '/')."/*");
			if($files)
			{
				foreach($files as $file)
				{ 
					if(is_file($file))
					{ 
						if(!filesize($file))
						{ 
							unlink($file); 
						} 
					}
					else
					{ 
						$this->removeZeroByte($file); 
					}
				} 
			}
		}

		/* Filter opacity */
		public function filterOpacity($img='', $opacity=80)
		{
			return true;
			/*
			if(!isset($opacity) || $img == '') return false;

			$opacity /= 100;
			$w = imagesx($img);
			$h = imagesy($img);
			imagealphablending($img, false);
			$minalpha = 127;

			for($x = 0; $x < $w; $x++)
			{
				for($y = 0; $y < $h; $y++)
				{
					$alpha = (imagecolorat($img, $x, $y) >> 24) & 0xFF;
					if($alpha < $minalpha) $minalpha = $alpha;
				}
			}

			for($x = 0; $x < $w; $x++)
			{
				for($y = 0; $y < $h; $y++)
				{
					$colorxy = imagecolorat($img, $x, $y);
					$alpha = ($colorxy >> 24) & 0xFF;
					if($minalpha !== 127) $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minalpha);
					else $alpha += 127 * $opacity;
					$alphacolorxy = imagecolorallocatealpha($img, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
					if(!imagesetpixel($img, $x, $y, $alphacolorxy)) return false;
				}
			}

			return true;
			*/
		}

		private function resolveLocalImagePath($in)
		{
			global $config;

			if(empty($in)) return '';
			if(substr($in, 0, 1) === '/' && is_file($in)) return $in;

			$documentRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/').'/' : ROOT;
			$baseUrl = !empty($config['database']['url']) ? trim($config['database']['url'], '/').'/' : '';
			return $documentRoot.$baseUrl.ltrim(str_replace(ASSET, '', $in), '/');
		}

		private function validWebpFile($path)
		{
			if(!is_file($path) || filesize($path) < 16) return false;
			$header = @file_get_contents($path, false, null, 0, 12);
			return strlen((string) $header) === 12 && substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP';
		}

		public function canEncodeWebp()
		{
			return function_exists('imagewebp');
		}

		/* Chỉ kiểm tra sidecar có sẵn; tuyệt đối không phát sinh encode trong
		   đường render HTML vì một ảnh lớn có thể chặn toàn bộ phản hồi trang. */
		public function hasFreshWebpVariant($in)
		{
			$in = $this->resolveLocalImagePath($in);
			$extension = strtolower(pathinfo($in, PATHINFO_EXTENSION));
			if(!is_file($in) || !in_array($extension, array('jpg','jpeg','png'), true)) return false;

			$out = $in.'.webp';
			return $this->validWebpFile($out)
				&& filemtime($out) >= filemtime($in)
				&& filesize($out) < filesize($in);
		}

		/* Tạo biến thể WebP cạnh ảnh gốc (ten.jpg.webp). Luôn giữ ảnh gốc làm
		   fallback và chỉ công bố biến thể sau khi đã kiểm tra đúng header WebP. */
		public function converWebp($in, $quality=82)
		{
			global $config;

			if(empty($in)) return false;
			$in = $this->resolveLocalImagePath($in);

			$extension = strtolower(pathinfo($in, PATHINFO_EXTENSION));
			if(!is_file($in) || !in_array($extension, array('jpg','jpeg','png'), true)) return false;

			$out = $in.'.webp';
			if($this->validWebpFile($out) && filemtime($out) >= filemtime($in) && filesize($out) < filesize($in)) return true;

			$quality = max(55, min(92, (int) $quality));
			$temp = $out.'.tmp-'.getmypid().'-'.uniqid('', true).'.webp';
			$converted = false;

			try
			{
				if(function_exists('imagewebp'))
				{
					$info = @getimagesize($in);
					$image = false;
					if(!empty($info['mime']) && $info['mime'] === 'image/jpeg') $image = @imagecreatefromjpeg($in);
					elseif(!empty($info['mime']) && $info['mime'] === 'image/png') $image = @imagecreatefrompng($in);
					if($image)
					{
						if(function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($image);
						imagealphablending($image, true);
						imagesavealpha($image, true);
						$converted = @imagewebp($image, $temp, $quality);
						imagedestroy($image);
					}
				}
				else
				{
					WebPConvert::convert($in, $temp, array(
						'quality' => $quality,
						'metadata' => 'none',
						'converters' => array('cwebp','imagick','imagemagick','gd')
					));
					$converted = true;
				}
			}
			catch(\Throwable $exception)
			{
				$converted = false;
			}

			if(!$converted || !$this->validWebpFile($temp) || filesize($temp) >= filesize($in))
			{
				if(is_file($temp)) @unlink($temp);
				return false;
			}

			if(!@rename($temp, $out))
			{
				@unlink($temp);
				return false;
			}

			return true;
		}

		/* Dọn file phát sinh tối đa một lần mỗi interval trên mỗi thư mục.
		   flock ngăn nhiều request ảnh đầu tiên cùng quét toàn bộ cây cache. */
		public function cleanupGeneratedImagesIfDue($dir, $seconds=3600, $interval=3600)
		{
			if(empty($dir) || !is_dir($dir)) return false;
			$interval = max(60, (int) $interval);
			$key = hash('sha256', (string) (realpath($dir) ?: $dir));
			$stamp = rtrim(sys_get_temp_dir(), '/').'/pts-image-cleanup-'.$key.'.stamp';
			if(is_file($stamp) && time() - filemtime($stamp) < $interval) return false;

			$lock = @fopen($stamp.'.lock', 'c');
			if(!$lock || !@flock($lock, LOCK_EX | LOCK_NB))
			{
				if($lock) fclose($lock);
				return false;
			}

			$cleaned = false;
			if(!is_file($stamp) || time() - filemtime($stamp) >= $interval)
			{
				$this->RemoveFilesFromDirInXSeconds($dir, max(1, (int) $seconds));
				$this->RemoveEmptySubFolders($dir);
				@touch($stamp);
				$cleaned = true;
			}

			@flock($lock, LOCK_UN);
			fclose($lock);
			return $cleaned;
		}

		/* Create thumb */
		public function createThumb($width_thumb=0, $height_thumb=0, $zoom_crop='1', $src='', $watermark=null, $path=THUMBS, $preview=false, $args=array(), $quality=100)
		{
			global $config;
			if(function_exists('closeSessionWrite')) closeSessionWrite();

			$webp = false;
			$t = 3600*24*30;

			if(strpos($src, ".webp") !== false)
			{
				$webp = true;
				$src = str_replace(".webp", "", $src);
			}

			$this->cleanupGeneratedImagesIfDue(UPLOAD_TEMP_L, 3600, 300);

			if($watermark != null)
			{
				$this->cleanupGeneratedImagesIfDue(WATERMARK.'/'.$path."/", $t, 3600);
			}
			else
			{
				$this->cleanupGeneratedImagesIfDue($path."/", $t, 3600);
			}

			$src = str_replace("%20"," ",$src);
			if(!file_exists($src)) die("NO IMAGE $src");

			$image_url = $src;
			$origin_x = 0;
			$origin_y = 0;
			$new_width = $width_thumb;
			$new_height = $height_thumb;

			if($new_width < 10 && $new_height < 10)
			{
				header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
				die("Width and height larger than 10px");
			}
			if($new_width > 2000 || $new_height > 2000)
			{
				header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
				die("Width and height less than 2000px");
			}

			/* URL thumbnail chứa kích thước + tên file, nên có thể cache dài hạn.
			   Gỡ header no-cache do session_start() tự thêm. */
			header_remove('Pragma');
			header_remove('Expires');
			header_remove('Cache-Control');
			header('Cache-Control: public, max-age=2592000, immutable');
			header('Expires: '.gmdate('D, d M Y H:i:s', time() + 2592000).' GMT');

			$array = getimagesize($image_url);
			if($array) list($image_w, $image_h) = $array;
			else die("NO IMAGE $image_url");

			$width = $image_w;
			$height = $image_h;

			if($new_height && !$new_width) $new_width = $width * ($new_height / $height);
			else if($new_width && !$new_height) $new_height = $height * ($new_width / $width);

			$image_ext = explode('.', $image_url);
			$image_ext = trim(strtolower(end($image_ext)));
			$image_name = explode('/', $image_url);
			$image_name = trim(basename($image_url), "/");

			switch($array['mime'])
			{
				/* Chặn warning libpng/GD của ảnh gốc lỗi profile (vd iCCP sRGB sai)
				   không cho in ra output làm hỏng header ảnh thumbnail. */
				case 'image/jpeg':
				case 'image/jpg':
					$image = @imagecreatefromjpeg($image_url);
					$func='imagejpeg';
					$mime_type = 'jpeg';
					break;

				case 'image/x-ms-bmp':
				case 'image/png':
					$image = @imagecreatefrompng($image_url);
					$func='imagepng';
					$mime_type = 'png';
					break;

				case 'image/gif':
					$image = @imagecreatefromgif($image_url);
					$func='imagegif';
					$mime_type = 'png';
					break;

				default: die("UNKNOWN IMAGE TYPE: $image_url");
			}

			$_new_width = $new_width;
			$_new_height = $new_height;

			if($zoom_crop == 3)
			{
				$final_height = $height * ($new_width / $width);
				if($final_height > $new_height) $new_width = $width * ($new_height / $height);
				else $new_height = $final_height;
			}

			$canvas = imagecreatetruecolor($new_width, $new_height);
			imagealphablending($canvas, false);
			$color = imagecolorallocatealpha($canvas, 255, 255, 255, 0);
			imagefill ($canvas, 0, 0, $color);
			
			if($zoom_crop == 2)
			{
				$final_height = $height * ($new_width / $width);
				if($final_height > $new_height)
				{
					$origin_x = $new_width / 2;
					$new_width = $width * ($new_height / $height);
					$origin_x = round($origin_x - ($new_width / 2));
				}
				else
				{
					$origin_y = $new_height / 2;
					$new_height = $final_height;
					$origin_y = round($origin_y - ($new_height / 2));
				}
			}

			imagesavealpha($canvas, true);

			if($zoom_crop > 0)
			{
				$align = '';
				$src_x = $src_y = 0;
				$src_w = $width;
				$src_h = $height;

				$cmp_x = $width / $new_width;
				$cmp_y = $height / $new_height;

				if($cmp_x > $cmp_y)
				{
					$src_w = round($width / $cmp_x * $cmp_y);
					$src_x = round(($width - ($width / $cmp_x * $cmp_y)) / 2);
				}
				else if($cmp_y > $cmp_x)
				{
					$src_h = round($height / $cmp_y * $cmp_x);
					$src_y = round(($height - ($height / $cmp_y * $cmp_x)) / 2);
				}

				if($align)
				{
					if(strpos($align, 't') !== false)
					{
						$src_y = 0;
					}
					if(strpos($align, 'b') !== false)
					{
						$src_y = $height - $src_h;
					}
					if(strpos($align, 'l') !== false)
					{
						$src_x = 0;
					}
					if(strpos($align, 'r') !== false)
					{
						$src_x = $width - $src_w;
					}
				}

				imagecopyresampled($canvas, $image, $origin_x, $origin_y, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h);
			}
			else
			{
				imagecopyresampled($canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			}

			if($preview)
			{
				$watermark = array();
				$watermark['status'] = 'hienthi';
				$options = $args;
				$overlay_url = $args['watermark'];
			}
			
			$upload_dir = '';
			$folder_old = dirname($image_url).'/';

			if(!empty($watermark['status']) && strpos('hienthi', $watermark['status']) !== false)
			{
				$upload_dir = WATERMARK.'/'.$path.'/'.$width_thumb.'x'.$height_thumb.'x'.$zoom_crop.'/'.$folder_old;
			}
			else
			{
				if($watermark != null) $upload_dir = WATERMARK.'/'.$path.'/'.$width_thumb.'x'.$height_thumb.'x'.$zoom_crop.'/'.$folder_old;
				else $upload_dir = $path.'/'.$width_thumb.'x'.$height_thumb.'x'.$zoom_crop.'/'.$folder_old;
			}

			if(!file_exists($upload_dir)) if(!mkdir($upload_dir, 0777, true)) die('Failed to create folders...');

			if(!empty($watermark['status']) && strpos('hienthi', $watermark['status']) !== false)
			{
				$options = (isset($options))?$options:json_decode($watermark['options'],true)['watermark'];
				$per_scale = $options['per'];
				$per_small_scale = $options['small_per'];
				$max_width_w = $options['max'];
				$min_width_w = $options['min'];
				$opacity = @$options['opacity'];
				$overlay_url = (isset($overlay_url))?$overlay_url:UPLOAD_PHOTO_L.$watermark['photo'];
				$overlay_ext = explode('.', $overlay_url);
				$overlay_ext = trim(strtolower(end($overlay_ext)));

				switch(strtoupper($overlay_ext))
				{
					case 'JPG':
					case 'JPEG':
						$overlay_image = imagecreatefromjpeg($overlay_url);
						break;

					case 'PNG':
						$overlay_image = imagecreatefrompng($overlay_url);
						break;

					case 'GIF':
						$overlay_image = imagecreatefromgif($overlay_url);
						break;

					default: die("UNKNOWN IMAGE TYPE: $overlay_url");
				}
				
				$this->filterOpacity($overlay_image,$opacity);
				$overlay_width = imagesx($overlay_image);
				$overlay_height = imagesy($overlay_image);
				$overlay_padding = 5;				
		        imagealphablending($canvas, true);
				
				if(min($_new_width,$_new_height) <= 300) $per_scale = $per_small_scale;
					
				$oz = max($overlay_width,$overlay_height);				
				
				if($overlay_width > $overlay_height)
				{
					$scale = $_new_width/$oz;
				}
				else
				{
					$scale = $_new_height/$oz;
				}

				if($_new_height > $_new_width)
				{
					$scale = $_new_height/$oz;
				}
				$new_overlay_width = (floor($overlay_width*$scale) - $overlay_padding*2)/$per_scale;
				$new_overlay_height = (floor($overlay_height*$scale) - $overlay_padding*2)/$per_scale;
				$scale_w = $new_overlay_width/$new_overlay_height;
				$scale_h = $new_overlay_height/$new_overlay_width;
				$new_overlay_height = $new_overlay_width/$scale_w;
				
				if($new_overlay_height > $_new_height)
				{
					$new_overlay_height = $_new_height / $per_scale;
					$new_overlay_width = $new_overlay_height * $scale_w;
				}
				if($new_overlay_width > $_new_width)
				{
					$new_overlay_width = $_new_width/$per_scale;
					$new_overlay_height = $new_overlay_width * $scale_h;
				}
				if(($_new_width / $new_overlay_width) < $per_scale)
				{
					$new_overlay_width = $_new_width/$per_scale;
					$new_overlay_height = $new_overlay_width * $scale_h;
				}
				if($_new_height < $_new_width && ($_new_height / $new_overlay_height) < $per_scale)
				{
					$new_overlay_height = $_new_height/$per_scale;
					$new_overlay_width = $new_overlay_height / $scale_h;
				}
				if($new_overlay_width > $max_width_w && $new_overlay_width)
				{
					$new_overlay_width = $max_width_w;
					$new_overlay_height = $new_overlay_width * $scale_h;
				}
				if($new_overlay_width < $min_width_w && $_new_width <= $min_width_w*3)
				{
					$new_overlay_width = $min_width_w;	
					$new_overlay_height = $new_overlay_width * $scale_h;
				}
				$new_overlay_width = round($new_overlay_width);
				$new_overlay_height = round($new_overlay_height);
				
				switch($options['position'])
				{
					case 1:
						$khoancachx = $overlay_padding;
						$khoancachy = $overlay_padding;
						break;

					case 2:
						$khoancachx = abs($_new_width - $new_overlay_width)/2;
						$khoancachy = $overlay_padding;
						break;

					case 3:
						$khoancachx = abs($_new_width - $new_overlay_width) - $overlay_padding;
						$khoancachy = $overlay_padding;
						break;

					case 4:
						$khoancachx = abs($_new_width - $new_overlay_width) - $overlay_padding;
						$khoancachy = abs($_new_height - $new_overlay_height)/2;
						break;

					case 5:
						$khoancachx = abs($_new_width - $new_overlay_width) - $overlay_padding;
						$khoancachy = abs($_new_height - $new_overlay_height) - $overlay_padding;
						break;

					case 6:
						$khoancachx = abs($_new_width - $new_overlay_width)/2;
						$khoancachy = abs($_new_height - $new_overlay_height) - $overlay_padding;
						break;

					case 7:
						$khoancachx = $overlay_padding;
						$khoancachy = abs($_new_height - $new_overlay_height) - $overlay_padding;
						break;

					case 8:
						$khoancachx = $overlay_padding;
						$khoancachy = abs($_new_height - $new_overlay_height)/2;
						break;

					case 9:
						$khoancachx = abs($_new_width - $new_overlay_width)/2;
						$khoancachy = abs($_new_height - $new_overlay_height)/2;
						break;
					
					default:
						$khoancachx = $overlay_padding;
						$khoancachy = $overlay_padding;
						break;
				}
				
	            $overlay_new_image = imagecreatetruecolor($new_overlay_width, $new_overlay_height);
	            imagealphablending($overlay_new_image, false);
	            imagesavealpha($overlay_new_image, true);
	            imagecopyresampled($overlay_new_image, $overlay_image, 0, 0, 0, 0, $new_overlay_width, $new_overlay_height, $overlay_width, $overlay_height);
	            imagecopy($canvas, $overlay_new_image, $khoancachx, $khoancachy, 0, 0, $new_overlay_width, $new_overlay_height);
				imagealphablending($canvas, false);
				imagesavealpha($canvas, true);
			}
			
			if($preview)
			{
				$upload_dir = '';
				$this->RemoveEmptySubFolders(WATERMARK.'/'.$path."/");
			}

			$webpQuality = !empty($config['website']['image']['webp-quality']) ? (int) $config['website']['image']['webp-quality'] : 82;
			$serveWebp = $webp && $this->canEncodeWebp();

			if($upload_dir)
			{
				if(!isset($_GET['preview']))
				{
					$generatedPath = $upload_dir.$image_name.($serveWebp ? '.webp' : '');
					$tempGeneratedPath = $generatedPath.'.tmp-'.getmypid().'-'.uniqid('', true);
					if($serveWebp) $written = @imagewebp($canvas, $tempGeneratedPath, $webpQuality);
					elseif($func == 'imagejpeg') $written = @$func($canvas, $tempGeneratedPath, 100);
					else $written = @$func($canvas, $tempGeneratedPath, floor($quality * 0.09));

					if($written && is_file($tempGeneratedPath) && filesize($tempGeneratedPath) > 0) @rename($tempGeneratedPath, $generatedPath);
					elseif(is_file($tempGeneratedPath)) @unlink($tempGeneratedPath);
				}
			}

			if($serveWebp)
			{
				header('Content-Type: image/webp');
				imagewebp($canvas, NULL, $webpQuality);
			}
			else
			{
				header('Content-Type: image/' . $mime_type);
				if($func=='imagejpeg') $func($canvas, NULL,100);
				else $func($canvas, NULL,floor($quality * 0.09));
			}
			imagedestroy($canvas);

			exit;
		}

		/* String random */
		public function stringRandom($sokytu=10)
		{
			$str = '';

			if($sokytu > 0)
			{
				$chuoi = 'ABCDEFGHIJKLMNOPQRSTUVWXYZWabcdefghijklmnopqrstuvwxyzw0123456789';
				for($i=0; $i<$sokytu; $i++)
				{
					$vitri = mt_rand( 0 ,strlen($chuoi) );
					$str= $str . substr($chuoi,$vitri,1 );
				}
			}

			return $str;
		}

		/* Digital random */
		public function digitalRandom($min=1, $max=10, $num=10)
		{
			$result = '';

			if($num > 0)
			{
				for($i=0; $i<$num; $i++)
				{
					$result .= rand($min,$max);
				}
			}

			return $result;	
		}

		/* Get permission */
		public function getPermission($id_permission=0)
		{
			$row = $this->cache->get("select * from #_permission_group where find_in_set('hienthi',status) order by numb,id desc", null, "result", 7200);

			$str = '<select id="id_permission" name="data[id_permission]" class="form-control select2"><option value="0">Nhóm quyền</option>';
			foreach($row as $v)
			{
				if($v["id"] == (int)@$id_permission) $selected = "selected";
				else $selected = "";

				$str .= '<option value='.$v["id"].' '.$selected.'>'.$v["name"].'</option>';			
			}
			$str .= '</select>';

			return $str;
		}

		/* Get status order */
		public function orderStatus($status=0)
		{
	        $row = $this->cache->get("select * from #_order_status order by id", null, "result", 7200);

	        $str = '<select id="order_status" name="data[order_status]" class="form-control custom-select text-sm"><option value="0">Chọn tình trạng</option>';
	        foreach($row as $v)
	        {
	            if(isset($_REQUEST['order_status']) && ($v["id"] == (int)$_REQUEST['order_status']) || ($v["id"] == $status)) $selected = "selected";
	            else $selected = "";

	            $str .= '<option value='.$v["id"].' '.$selected.'>'.$v["namevi"].'</option>';
	        }
	        $str .= '</select>';

	        return $str;
		}

		/* Lấy thông tin chi tiết */
		public function getInfoDetail($cols='', $table='', $id=0)
		{
			$row = array();

			if(!empty($cols) && !empty($table) && !empty($id))
			{
				$row = $this->cache->get("select $cols from #_$table where id = ? limit 0,1", array($id), "fetch", 7200);
			}

			return $row;
		}

		/* Join column */
		public function joinCols($array=null, $column=null)
		{
			$str = '';
			$arrayTemp = array();

			if($array && $column)
			{
				foreach($array as $k => $v)
				{
					if(!empty($v[$column]))
					{
						$arrayTemp[] = $v[$column];
					}
				}

				if(!empty($arrayTemp))
				{
					$arrayTemp = array_unique($arrayTemp);
					$str = implode(",", $arrayTemp);
				}
			}

			return $str;
		}

		/* Get payments order */
		function orderPayments()
	    {
	        $row = $this->cache->get("select * from #_news where type='hinh-thuc-thanh-toan' order by numb,id desc", null, "result", 7200);

	        $str = '<select id="order_payment" name="order_payment" class="form-control custom-select text-sm"><option value="0">Chọn hình thức thanh toán</option>';
	        foreach($row as $v)
	        {
	            if(isset($_REQUEST['order_payment']) && ($v["id"] == (int)$_REQUEST['order_payment'])) $selected = "selected";
	            else $selected = "";
	            $str .= '<option value='.$v["id"].' '.$selected.'>'.$v["namevi"].'</option>';
	        }
	        $str .= '</select>';

	        return $str;
	    }

	    /* Get color */
		public function getColor($id=0)
		{
			global $type;

			if($id)
			{
				$temps = $this->d->rawQuery("select id_color from #_product_sale where id_parent = ?",array($id));
				$temps = (!empty($temps)) ? $this->joinCols($temps, 'id_color') : array();
				$temps = (!empty($temps)) ? explode(",", $temps) : array();
			}

			$row_color = $this->d->rawQuery("select namevi, id from #_color where type = ? order by numb,id desc",array($type));

			$str = '<select id="dataColor" name="dataColor[]" class="select multiselect" multiple="multiple" >';
			for($i=0;$i<count($row_color);$i++)
			{
				if(!empty($temps))
				{
					if(in_array($row_color[$i]['id'],$temps)) $selected = 'selected="selected"';
					else $selected = '';
				}
				else
				{
					$selected = '';
				}
				$str .= '<option value="'.$row_color[$i]["id"].'" '.$selected.' /> '.$row_color[$i]["namevi"].'</option>';
			}
			$str .= '</select>';

			return $str;
		}

		/* Get size */
		public function getSize($id=0)
		{
			global $type;

			if($id)
			{
				$temps = $this->d->rawQuery("select id_size from #_product_sale where id_parent = ?",array($id));
				$temps = (!empty($temps)) ? $this->joinCols($temps, 'id_size') : array();
				$temps = (!empty($temps)) ? explode(",", $temps) : array();
			}

			$row_size = $this->d->rawQuery("select namevi, id from #_size where type = ? order by numb,id desc",array($type));

			$str = '<select id="dataSize" name="dataSize[]" class="select multiselect" multiple="multiple" >';
			for($i=0;$i<count($row_size);$i++)
			{
				if(!empty($temps))
				{	
					if(in_array($row_size[$i]['id'],$temps)) $selected = 'selected="selected"';
					else $selected = '';
				}
				else
				{
					$selected = '';
				}
				$str .= '<option value="'.$row_size[$i]["id"].'" '.$selected.' /> '.$row_size[$i]["namevi"].'</option>';
			}
			$str .= '</select>';
			
			return $str;
		}

		/* Get tags */
		public function getTags($id=0, $element='', $table='', $type='')
		{
			if($id)
			{
				$temps = $this->d->rawQuery("select id_tags from #_".$table." where id_parent = ?",array($id));
				$temps = (!empty($temps)) ? $this->joinCols($temps, 'id_tags') : array();
				$temps = (!empty($temps)) ? explode(",", $temps) : array();
			}

			$row_tags = $this->cache->get("select namevi, id from #_tags where type = ? order by numb,id desc", array($type), "result", 7200);

			$str = '<select id="'.$element.'" name="'.$element.'[]" class="select multiselect" multiple="multiple" >';
			for($i=0;$i<count($row_tags);$i++)
			{
				if(!empty($temps))
				{
					if(in_array($row_tags[$i]['id'],$temps)) $selected = 'selected="selected"';
					else $selected = '';
				}
				else
				{
					$selected = '';
				}
				$str .= '<option value="'.$row_tags[$i]["id"].'" '.$selected.' /> '.$row_tags[$i]["namevi"].'</option>';
			}
			$str .= '</select>';

			return $str;
		}

		/* Get category by ajax */
		/**
		 * Giá trị danh mục đang áp dụng cho ô select.
		 *
		 * Ưu tiên $_REQUEST (cascade AJAX và lần render lại sau lỗi validation đều
		 * truyền qua URL), sau đó mới tới bản ghi đang sửa. Thiếu bước fallback này
		 * thì form sửa sản phẩm không chọn sẵn danh mục/thương hiệu hiện có, và mỗi
		 * lần Lưu sẽ ghi đè id_list/id_cat/id_brand về 0.
		 */
		protected function categoryFieldValue($field)
		{
			if(isset($_REQUEST[$field])) return (int) $_REQUEST[$field];
			global $item;
			if(isset($item) && is_array($item) && isset($item[$field])) return (int) $item[$field];
			return 0;
		}

		public function getAjaxCategory($table='', $level='', $type='', $title_select='Chọn danh mục', $class_select='select-category')
	    {
	    	$where = '';
	    	$params = array($type);
	    	$id_parent = 'id_'.$level;
	    	$data_level = '';
	    	$data_type = 'data-type="'.$type.'"';
	    	$data_table = '';
	    	$data_child = '';

	    	if($level == 'list')
	    	{
	    		$data_level = 'data-level="0"';
		    	$data_table = 'data-table="#_'.$table.'_cat"';
		    	$data_child = 'data-child="id_cat"';
	    	}
	    	else if($level == 'cat')
	    	{
	    		$data_level = 'data-level="1"';
		    	$data_table = 'data-table="#_'.$table.'_item"';
		    	$data_child = 'data-child="id_item"';

	    		$idlist = $this->categoryFieldValue('id_list');
	    		$where .= ' and id_list = ?';
	    		array_push($params, $idlist);
	    	}
	    	else if($level == 'item')
	    	{
	    		$data_level = 'data-level="2"';
		    	$data_table = 'data-table="#_'.$table.'_sub"';
		    	$data_child = 'data-child="id_sub"';

	    		$idlist = $this->categoryFieldValue('id_list');
	    		$where .= ' and id_list = ?';
	    		array_push($params, $idlist);

	    		$idcat = $this->categoryFieldValue('id_cat');
	    		$where .= ' and id_cat = ?';
	    		array_push($params, $idcat);
	    	}
	    	else if($level == 'sub')
	    	{
	    		$data_level = '';
		    	$data_type = '';
		    	$class_select = '';

	    		$idlist = $this->categoryFieldValue('id_list');
	    		$where .= ' and id_list = ?';
	    		array_push($params, $idlist);
	    		
	    		$idcat = $this->categoryFieldValue('id_cat');
	    		$where .= ' and id_cat = ?';
	    		array_push($params, $idcat);

	    		$iditem = $this->categoryFieldValue('id_item');
	    		$where .= ' and id_item = ?';
	    		array_push($params, $iditem);
	    	}
	    	else if($level == 'brand')
	    	{
	    		$data_level = '';
		    	$data_type = '';
		    	$class_select = '';
	    	}

	        $rows = $this->cache->get("select namevi, id from #_".$table."_".$level." where type = ? ".$where." order by numb,id desc", $params, "result", 7200);

	        $str = '<select id="'.$id_parent.'" name="data['.$id_parent.']" '.$data_level.' '.$data_type.' '.$data_table.' '.$data_child.' class="form-control select2 '.$class_select.'"><option value="0">'.$title_select.'</option>';
	        foreach($rows as $v)
	        {
	            $selectedValue = $this->categoryFieldValue($id_parent);
	            $selected = ($selectedValue > 0 && (int)$v["id"] === $selectedValue) ? "selected" : "";

	            $str .= '<option value='.$v["id"].' '.$selected.'>'.$v["namevi"].'</option>';
	        }
	        $str .= '</select>';

	        return $str;
	    }

	    /* Get category by link */
		public function getLinkCategory($table='', $level='', $type='', $title_select='Chọn danh mục')
	    {
	    	$where = '';
	    	$params = array($type);
	    	$id_parent = 'id_'.$level;

	    	if($level == 'cat')
	    	{
	    		$idlist = (isset($_REQUEST['id_list'])) ? htmlspecialchars($_REQUEST['id_list']) : 0;
	    		$where .= ' and id_list = ?';
	    		array_push($params, $idlist);
	    	}
	    	else if($level == 'item')
	    	{
	    		$idlist = (isset($_REQUEST['id_list'])) ? htmlspecialchars($_REQUEST['id_list']) : 0;
	    		$where .= ' and id_list = ?';
	    		array_push($params, $idlist);

	    		$idcat = (isset($_REQUEST['id_cat'])) ? htmlspecialchars($_REQUEST['id_cat']) : 0;
	    		$where .= ' and id_cat = ?';
	    		array_push($params, $idcat);
	    	}
	    	else if($level == 'sub')
	    	{
	    		$idlist = (isset($_REQUEST['id_list'])) ? htmlspecialchars($_REQUEST['id_list']) : 0;
	    		$where .= ' and id_list = ?';
	    		array_push($params, $idlist);
	    		
	    		$idcat = (isset($_REQUEST['id_cat'])) ? htmlspecialchars($_REQUEST['id_cat']) : 0;
	    		$where .= ' and id_cat = ?';
	    		array_push($params, $idcat);

	    		$iditem = (isset($_REQUEST['id_item'])) ? htmlspecialchars($_REQUEST['id_item']) : 0;
	    		$where .= ' and id_item = ?';
	    		array_push($params, $iditem);
	    	}

	        $rows = $this->cache->get("select namevi, id from #_".$table."_".$level." where type = ? ".$where." order by numb,id desc", $params, "result", 7200);

	        $str = '<select id="'.$id_parent.'" name="'.$id_parent.'" onchange="onchangeCategory($(this))" class="form-control filter-category select2"><option value="0">'.$title_select.'</option>';
	        foreach($rows as $v)
	        {
	            if(isset($_REQUEST[$id_parent]) && ($v["id"] == (int)$_REQUEST[$id_parent])) $selected = "selected";
	            else $selected = "";

	            $str .= '<option value='.$v["id"].' '.$selected.'>'.$v["namevi"].'</option>';
	        }
	        $str .= '</select>';

	        return $str;
	    }

	    /* Get place by ajax */
		public function getAjaxPlace($table='', $title_select='Chọn danh mục')
	    {
	    	$where = '';
	    	$params = array('0');
			$loadRows = true;
	    	$id_parent = 'id_'.$table;
	    	$data_level = '';
	    	$data_table = '';
	    	$data_child = '';

	    	if($table == 'city')
	    	{
	    		$data_level = 'data-level="0"';
		    	$data_table = 'data-table="#_district"';
		    	$data_child = 'data-child="id_district"';
	    	}
	    	else if($table == 'district')
	    	{
	    		$data_level = 'data-level="1"';
		    	$data_table = 'data-table="#_ward"';
		    	$data_child = 'data-child="id_ward"';

	    		$idcity = (isset($_REQUEST['id_city'])) ? htmlspecialchars($_REQUEST['id_city']) : 0;
	    		$where .= ' and id_city = ?';
	    		array_push($params, $idcity);
				$loadRows = ((int)$idcity > 0);
	    	}
	    	else if($table == 'ward')
	    	{
	    		$data_level = '';
		    	$data_table = '';
		    	$data_child = '';

	    		$idcity = (isset($_REQUEST['id_city'])) ? htmlspecialchars($_REQUEST['id_city']) : 0;
	    		$where .= ' and id_city = ?';
	    		array_push($params, $idcity);

	    		$iddistrict = (isset($_REQUEST['id_district'])) ? htmlspecialchars($_REQUEST['id_district']) : 0;
	    		$where .= ' and id_district = ?';
	    		array_push($params, $iddistrict);
				$loadRows = ((int)$idcity > 0 && (int)$iddistrict > 0);
	    	}

	        $rows = $loadRows ? $this->cache->get("select name, id from #_".$table." where id <> ? ".$where." order by id asc", $params, "result", 7200) : array();

	        $str = '<select id="'.$id_parent.'" name="data['.$id_parent.']" '.$data_level.' '.$data_table.' '.$data_child.' class="form-control select2 select-place"><option value="0">'.$title_select.'</option>';
	        foreach($rows as $v)
	        {
	            if(isset($_REQUEST[$id_parent]) && ($v["id"] == (int)$_REQUEST[$id_parent])) $selected = "selected";
	            else $selected = "";

	            $str .= '<option value='.$v["id"].' '.$selected.'>'.$v["name"].'</option>';
	        }
	        $str .= '</select>';

	        return $str;
	    }

	    /* Get place by link */
		public function getLinkPlace($table='', $title_select='Chọn danh mục')
	    {
	    	$where = '';
	    	$params = array('0');
	    	$id_parent = 'id_'.$table;

	    	if($table == 'district')
	    	{
	    		$idcity = (isset($_REQUEST['id_city'])) ? htmlspecialchars($_REQUEST['id_city']) : 0;
	    		$where .= ' and id_city = ?';
	    		array_push($params, $idcity);
	    	}
	    	else if($table == 'ward')
	    	{
	    		$idcity = (isset($_REQUEST['id_city'])) ? htmlspecialchars($_REQUEST['id_city']) : 0;
	    		$where .= ' and id_city = ?';
	    		array_push($params, $idcity);

	    		$iddistrict = (isset($_REQUEST['id_district'])) ? htmlspecialchars($_REQUEST['id_district']) : 0;
	    		$where .= ' and id_district = ?';
	    		array_push($params, $iddistrict);
	    	}

	        $rows = $this->cache->get("select name, id from #_".$table." where id <> ? ".$where." order by id asc", $params, "result", 7200);

	        $str = '<select id="'.$id_parent.'" name="'.$id_parent.'" onchange="onchangeCategory($(this))" class="form-control filter-category select2"><option value="0">'.$title_select.'</option>';
	        foreach($rows as $v)
	        {
	            if(isset($_REQUEST[$id_parent]) && ($v["id"] == (int)$_REQUEST[$id_parent])) $selected = "selected";
	            else $selected = "";

	            $str .= '<option value='.$v["id"].' '.$selected.'>'.$v["name"].'</option>';
	        }
	        $str .= '</select>';

	        return $str;
	    }
	}
?>
