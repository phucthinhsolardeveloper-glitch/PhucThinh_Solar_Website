<?php
/**
 * Nạp SMTP config từ một nguồn cố định ngoài webroot.
 * DirectAdmin: /home/<user>/.pts-mail.php
 * Source local: <repository>/.pts-mail.php
 *
 * Có thể khai báo trực tiếp $config['mail'] trong libraries/config.php; nếu đã
 * có thì không nạp overlay, tránh hai source of truth trong cùng runtime.
 */
if(!function_exists('loadMailConfig'))
{
    function loadMailConfig(&$config)
    {
        if(!is_array($config)) $config = array();
        if(isset($config['mail']) && is_array($config['mail'])) return $config['mail'];

        $mailConfigFile = dirname(__DIR__, 4).'/.pts-mail.php';
        $mailConfig = is_file($mailConfigFile) ? require $mailConfigFile : array();
        $config['mail'] = is_array($mailConfig) ? $mailConfig : array();
        return $config['mail'];
    }
}

