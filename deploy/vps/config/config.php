<?php
if(!defined('LIBRARIES')) die('Error');

date_default_timezone_set('Asia/Ho_Chi_Minh');
define('CONTRACT', 'coder');
define('AUTHOR', '');

/* Đọc secret qua Docker secret file. Không fallback sang giá trị mặc định để
   tránh vô tình khởi chạy production với mật khẩu rỗng/yếu. */
$ptsReadSecret = function($envName, $required = true) {
    $path = getenv($envName);
    $value = is_string($path) && $path !== '' && is_readable($path)
        ? rtrim((string) file_get_contents($path), "\r\n")
        : '';
    if($required && $value === '') throw new RuntimeException('Missing required runtime secret: '.$envName);
    return $value;
};

$siteDomain = getenv('PTS_SITE_DOMAIN') ?: '';
if($siteDomain === '' || !preg_match('/^[a-z0-9.-]+(?::[0-9]+)?$/iD', $siteDomain))
    throw new RuntimeException('PTS_SITE_DOMAIN is missing or invalid.');

$config = array(
    'author' => array('name'=>'CODER', 'email'=>'', 'timefinish'=>'03/02/2023'),
    /* TLS/redirect do Caddy quản lý. Để rỗng nhằm tránh checkSSLv2.php ghi
       ssl.txt vào image ứng dụng bất biến. */
    'arrayDomainSSL' => array(),
    'database' => array(
        'server-name' => $siteDomain,
        'url' => '/',
        'type' => 'mysql',
        'host' => getenv('DB_HOST') ?: 'db',
        'username' => getenv('DB_USER') ?: '',
        'password' => $ptsReadSecret('PTS_DB_PASSWORD_FILE'),
        'dbname' => getenv('DB_NAME') ?: '',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'prefix' => 'table_',
        'charset' => 'utf8mb4'
    ),
    'website' => array(
        'error-reporting' => false,
        /* Phải mang đúng hai giá trị từ config production cũ để hash legacy
           còn xác minh được. Không tự sinh lại khi chuyển VPS. */
        'secret' => $ptsReadSecret('PTS_APP_SECRET_FILE'),
        'salt' => $ptsReadSecret('PTS_APP_SALT_FILE'),
        'debug-developer' => false,
        'debug-css' => false,
        'debug-js' => true,
        'index' => false,
        'image' => array('hasWebp'=>true),
        'video' => array(
            'extension'=>array('mp4','mkv'),
            'poster'=>array('width'=>700,'height'=>610,'extension'=>'.jpg|.png|.jpeg'),
            'allow-size'=>'100Mb',
            'max-size'=>100 * 1024 * 1024
        ),
        'upload' => array('max-width'=>1920, 'max-height'=>1920),
        'lang' => array('vi'=>'Tiếng Việt'),
        'lang-doc' => 'vi',
        'slug' => array('vi'=>'Tiếng Việt'),
        'seo' => array('vi'=>'Tiếng Việt'),
        'comlang' => array(
            'gioi-thieu'=>array('vi'=>'gioi-thieu','en'=>'about-us'),
            'san-pham'=>array('vi'=>'san-pham','en'=>'product'),
            'tin-tuc'=>array('vi'=>'tin-tuc','en'=>'news'),
            'tuyen-dung'=>array('vi'=>'tuyen-dung','en'=>'recruitment'),
            'thu-vien-anh'=>array('vi'=>'thu-vien-anh','en'=>'gallery'),
            'video'=>array('vi'=>'video','en'=>'video'),
            'lien-he'=>array('vi'=>'lien-he','en'=>'contact')
        )
    ),
    'order' => array(
        'ship'=>true,
        'check'=>true,
        'payment'=>array('Chưa thanh toán','Đã thanh toán')
    ),
    'login' => array(
        'admin'=>'LoginAdmin',
        'member'=>'LoginMember',
        'attempt'=>5,
        'account-attempt'=>20,
        'delay'=>15,
        'timeout'=>3600,
        'trusted-proxies'=>array()
    ),
    'googleAPI' => array(
        'recaptcha'=>array(
            'active'=>getenv('RECAPTCHA_ACTIVE') === '1',
            'urlapi'=>'https://www.google.com/recaptcha/api/siteverify',
            'sitekey'=>getenv('RECAPTCHA_SITE_KEY') ?: '',
            'secretkey'=>$ptsReadSecret('RECAPTCHA_SECRET_FILE', false)
        )
    ),
    'other' => array('sortby'=>true),
    'oneSignal' => array(
        'active'=>getenv('ONESIGNAL_ACTIVE') === '1',
        'id'=>getenv('ONESIGNAL_APP_ID') ?: '',
        'restId'=>$ptsReadSecret('ONESIGNAL_REST_ID_FILE', false)
    ),
    'license' => array('version'=>'7.4.0', 'powered'=>'')
);

$ckfinderName = $ptsReadSecret('CKFINDER_LICENSE_NAME_FILE', false);
$ckfinderKey = $ptsReadSecret('CKFINDER_LICENSE_KEY_FILE', false);
if($ckfinderName !== '' && !defined('CKFINDER_LICENSE_NAME')) define('CKFINDER_LICENSE_NAME', $ckfinderName);
if($ckfinderKey !== '' && !defined('CKFINDER_LICENSE_KEY')) define('CKFINDER_LICENSE_KEY', $ckfinderKey);

error_reporting(0);

$forwardedProto = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
    ? strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]))
    : '';
$http = ((!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || $forwardedProto === 'https')
    ? 'https://'
    : 'http://';

$configUrl = $config['database']['server-name'].$config['database']['url'];
$configBase = $http.$configUrl;
define('TOKEN', md5(CONTRACT.$config['database']['url']));
define('ROOT', str_replace(basename(__DIR__), '', __DIR__));
define('ASSET', $http.$configUrl);
define('ADMIN', 'administrator');
$loginAdmin = $config['login']['admin'];
$loginMember = $config['login']['member'];
require_once LIBRARIES.'constant.php';
