<?php
	if(!defined('LIBRARIES')) die("Error");

	/* Array folders */
	$upload_const = 'upload';
	$array_const = array('file', 'filemanager', 'sync', 'excel', 'seopage', 'photo', 'video', 'temp', 'user', 'product', 'color', 'news', 'tags');

	/* Define - Create folders upload */
	if(!file_exists(ROOT.$upload_const))
	{
		mkdir(ROOT.$upload_const, 0777, true);
		chmod(ROOT.$upload_const, 0777);
	}

	/* Define - Create folders childs */
	if(file_exists(ROOT.$upload_const) && $array_const)
	{
		$path_htaccess = ROOT.$upload_const.'/.htaccess';
		$content_htaccess = 'Options -ExecCGI'.PHP_EOL;
		$content_htaccess .= 'RemoveHandler .php .php3 .php4 .php5 .php7 .php8 .phtml .phar .cgi .pl'.PHP_EOL;
		$content_htaccess .= '<FilesMatch "\.(?:inc|sql|php[0-9]?|phtml|phar|cgi|pl|asp|aspx|jsp|shtml)$">'.PHP_EOL;
		$content_htaccess .= '<IfModule mod_authz_core.c>Require all denied</IfModule>'.PHP_EOL;
		$content_htaccess .= '<IfModule !mod_authz_core.c>Order allow,deny'.PHP_EOL.'Deny from all</IfModule>'.PHP_EOL;
		$content_htaccess .= '</FilesMatch>'.PHP_EOL;
		if(!file_exists($path_htaccess) || file_get_contents($path_htaccess) !== $content_htaccess)
		{
			file_put_contents($path_htaccess, $content_htaccess, LOCK_EX);
		}

		foreach($array_const as $vconst)
		{
			$define_upper_upload = strtoupper($upload_const);
			$define_upper_const = strtoupper($vconst);
			$define_lower_const = $vconst;
			$define_in = '../'.$upload_const.'/'.$define_lower_const.'/';
			$define_out = $upload_const.'/'.$define_lower_const.'/';
			if(!defined($define_upper_upload.'_'.$define_upper_const) && !defined($define_upper_upload.'_'.$define_upper_const.'_L'))
			{
				define($define_upper_upload.'_'.$define_upper_const, $define_in);
				define($define_upper_upload.'_'.$define_upper_const.'_L', $define_out);

				if(!file_exists(ROOT.$upload_const.'/'.$define_lower_const))
				{
					mkdir(ROOT.$upload_const.'/'.$define_lower_const, 0777, true);
					chmod(ROOT.$upload_const.'/'.$define_lower_const, 0777);
				}
			}
		}
	}
?>
