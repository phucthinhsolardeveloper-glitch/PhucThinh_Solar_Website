<?php
/**
 * Xóa metadata sản phẩm đã bị loại khỏi giao diện public:
 * - thông số riêng có nhãn "Hiệu suất châu Âu";
 * - nội dung chứng nhận/kiểm nghiệm cũ ở certificatevi/certificateen.
 *
 * Mặc định chỉ dry-run. Chỉ dùng --apply sau khi đã backup và đối chiếu số liệu.
 */
if(PHP_SAPI !== 'cli') exit(1);
if(empty($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', is_dir('/var/www/html/libraries')
    ? '/var/www/html/libraries/'
    : dirname(__DIR__).'/domains/phucthinhsolar.com/public_html/libraries/');

$configFile = __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/config.php';
if(!is_file($configFile)) $configFile = '/var/www/html/libraries/config.php';
if(!is_file($configFile)) throw new RuntimeException('Không tìm thấy libraries/config.php.');
require $configFile;

$apply = in_array('--apply', $argv, true);
$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);
$table = $config['database']['prefix'].'product';

function normalizeProductMetadataLabel072($value)
{
    $value = mb_strtolower(trim(strip_tags((string) $value)), 'UTF-8');
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', strtolower((string) $ascii))));
}

$rows = $pdo->query('SELECT id,namevi,additional_specs,certificatevi,certificateen FROM '.$table.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$updates = array();
$specRows = 0;
$specEntries = 0;
$certificateRows = 0;
$invalidJson = 0;

foreach($rows as $row) {
    $changes = array();
    $rawSpecs = trim((string) $row['additional_specs']);
    if($rawSpecs !== '') {
        $specs = json_decode(htmlspecialchars_decode($rawSpecs, ENT_QUOTES), true);
        if(is_array($specs)) {
            $remaining = array();
            $removed = 0;
            foreach($specs as $spec) {
                $label = isset($spec['label']) ? trim(strip_tags((string) $spec['label'])) : '';
                $labelKey = normalizeProductMetadataLabel072($label);
                if($labelKey === 'hieu suat chau au') {
                    $removed++;
                    continue;
                }
                $remaining[] = $spec;
            }
            if($removed > 0) {
                $specRows++;
                $specEntries += $removed;
                $changes['additional_specs'] = $remaining
                    ? json_encode($remaining, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null;
            }
        } else {
            $invalidJson++;
        }
    }
    if(trim((string) $row['certificatevi']) !== '' || trim((string) $row['certificateen']) !== '') {
        $certificateRows++;
        $changes['certificatevi'] = null;
        $changes['certificateen'] = null;
    }
    if($changes) $updates[] = array('id' => (int) $row['id'], 'namevi' => (string) $row['namevi'], 'changes' => $changes);
}

echo 'Sản phẩm cập nhật: '.count($updates).PHP_EOL;
echo 'Dòng xóa Hiệu suất châu Âu: '.$specRows.' | mục: '.$specEntries.PHP_EOL;
echo 'Dòng xóa chứng nhận cũ: '.$certificateRows.PHP_EOL;
echo 'JSON additional_specs không hợp lệ, giữ nguyên: '.$invalidJson.PHP_EOL;

if(!$apply) {
    echo "DRY-RUN: chưa ghi DB. Dùng --apply sau khi duyệt.\n";
    exit(0);
}

$pdo->beginTransaction();
try {
    foreach($updates as $update) {
        $assignments = array();
        $params = array(':id' => $update['id']);
        foreach($update['changes'] as $field => $value) {
            $assignments[] = $field.' = :'.$field;
            $params[':'.$field] = $value;
        }
        $set = $pdo->prepare('UPDATE '.$table.' SET '.implode(', ', $assignments).' WHERE id = :id');
        $set->execute($params);
    }
    $pdo->commit();
    echo 'Đã apply thành công: '.count($updates).' sản phẩm.'.PHP_EOL;
} catch(Throwable $exception) {
    if($pdo->inTransaction()) $pdo->rollBack();
    throw $exception;
}
