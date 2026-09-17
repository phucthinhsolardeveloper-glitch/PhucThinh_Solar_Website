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

        /* VPS/container có webroot khác DirectAdmin. Cho phép chỉ định đường
           dẫn secret tuyệt đối, nhưng vẫn giữ fallback cũ để không làm đổi
           hành vi hosting hiện tại. File này phải nằm ngoài webroot và chỉ
           đọc được bởi tiến trình vận hành. */
        $configuredPath = getenv('PTS_MAIL_CONFIG_FILE');
        $mailConfigFile = is_string($configuredPath) && $configuredPath !== ''
            ? $configuredPath
            : dirname(__DIR__, 4).'/.pts-mail.php';
        $mailConfig = is_file($mailConfigFile) ? require $mailConfigFile : array();
        $config['mail'] = is_array($mailConfig) ? $mailConfig : array();
        return $config['mail'];
    }
}
