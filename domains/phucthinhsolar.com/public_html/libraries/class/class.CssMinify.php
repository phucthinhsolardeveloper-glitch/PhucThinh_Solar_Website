<?php
	class CssMinify
	{
		private $path = array();
		private $debug;
		private $access = array(
			'server' => ROOT.'assets/',
			'asset' => ASSET.'assets/',
			'folder' => 'caches/'
		);
		private $cacheName = '';
		private $cacheFile = '';
		private $cacheLink = '';
		private $cacheSize = false;
		private $cacheTime = 3600*24*30;
		/* Đổi version khi format bundle thay đổi để không dùng lại cache cũ đã
		   đóng cứng host nội bộ trong url() của font/icon. */
		private $cacheFormatVersion = 'root-relative-assets-v2';
		private $file = [];

		function __construct($debug, $func)
		{
			$this->debug = $debug;
			$this->func = $func;
		}

		public function init($name)
		{
			if(!$this->debug && !file_exists($this->cacheLink.$this->access['server'].$this->access['folder']))
            {
                if(!mkdir($this->cacheLink.$this->access['server'].$this->access['folder'], 0777, true))
                {
                    die('Failed to create folders...');
                }
            }

			$this->cacheName = $name;
			$this->cacheFile = $this->cacheFile.$this->access['server'].$this->access['folder'].$this->cacheName.'.css';
			$this->cacheLink = $this->cacheLink.$this->access['asset'].$this->access['folder'].$this->cacheName.'.css';
			$this->cacheSize = (file_exists($this->cacheFile)) ? filesize($this->cacheFile) : 0;
		}

		public function set($path)
		{
			$this->path[] = [
				'server' => $this->access['server'].$path,
				'asset' => $this->access['asset'].$path
			];

			$this->file[] = $path;
		}

		public function get()
		{
			$signature = array($this->cacheFormatVersion);
			foreach($this->path as $index => $path)
				$signature[] = $this->file[$index].':'.(is_file($path['server']) ? filemtime($path['server']) : 0);
			$this->init(md5(implode(",",$signature)));
			if(empty($this->path)) die("No files to optimize");
			return ($this->debug) ? $this->links() : $this->minify();
		}

		private function minify()
		{
			$strCss = '';
			$extension = '';

			if(!$this->cacheSize || $this->isExpire($this->cacheFile))
			{
				foreach($this->path as $path)
				{
					$parts = pathinfo($path['server']);
					$extension = strtolower($parts['extension']);
					if($extension != 'css') die("Invalid file");
					$myfile = fopen($path['server'], "r") or die("Unable to open file");
					$sizefile = filesize($path['server']);
			        if($sizefile) $strCss .= $this->rebaseUrls($this->compress(fread($myfile,$sizefile)), $path['asset']);
					fclose($myfile);
				}

				if($strCss)
				{
					$file = fopen($this->cacheFile, "w") or die("Unable to open file");
					fwrite($file, $strCss);
					fclose($file);
				}
			}

			return '<link href="'.$this->cacheLink.'?v='.filemtime($this->cacheFile).'" rel="stylesheet">';
		}

		private function links()
		{
			$linkCss = '';
			$extension = '';

			if($this->cacheSize)
			{
				$file = fopen($this->cacheFile, "r+") or die("Unable to open file");
				ftruncate($file, 0);
				fclose($file);
			}

			foreach($this->path as $path)
			{
				$parts = pathinfo($path['server']);
				$extension = strtolower($parts['extension']);
				if($extension != 'css') die("Invalid file");
				$linkCss .= '<link href="'.$path['asset'].'?v='.$this->func->stringRandom(10).'" rel="stylesheet">'.PHP_EOL;
			}

			return $linkCss;
		}

		/* File gộp nằm ở caches/, khác thư mục file gốc, nên url() tương đối trong
		   CSS gốc sẽ trỏ sai chỗ nếu giữ nguyên. Chỉ giữ path từ URL asset và viết
		   lại thành root-relative để một bundle dùng được qua domain, reverse proxy
		   và port khác nhau. Nếu đóng cứng host của request sinh cache (127.0.0.1),
		   request sau qua localhost:8080 sẽ mất font và icon hiện ô vuông. */
		private function rebaseUrls($css, $assetPath)
		{
			$assetUrlPath = parse_url($assetPath, PHP_URL_PATH);
			if(!is_string($assetUrlPath) || $assetUrlPath === '') $assetUrlPath = $assetPath;
			$baseDir = rtrim(str_replace('\\', '/', dirname($assetUrlPath)), '/').'/';
			if(substr($baseDir, 0, 1) !== '/') $baseDir = '/'.ltrim($baseDir, '/');
			return preg_replace_callback('/url\(\s*([\'"]?)(?!data:|https?:\/\/|\/\/|\/)([^\'")]+)\1\s*\)/i', function($m) use ($baseDir) {
				return 'url('.$m[1].$baseDir.$m[2].$m[1].')';
			}, $css);
		}

		private function compress($buffer)
		{
		    $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
		    $buffer = str_replace(': ', ':', $buffer);
		    $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
		    return $buffer;
		}

		private function isExpire($file)
		{
			$fileTime = filemtime($file);
			$isExpire = false;

			if((time() - $fileTime) > $this->cacheTime)
			{
				$isExpire = true;
			}

			return $isExpire;
		}
	}
?>
