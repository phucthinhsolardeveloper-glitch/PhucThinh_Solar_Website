<?php
/**
 * Chuẩn hóa additional_specs sau migration 005.
 * Mặc định dry-run; thêm --apply để ghi. Không xóa HTML kỹ thuật cũ.
 */
define('LIBRARIES', './libraries/');
if(empty($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';
$configFile = __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/config.php';
if(!is_file($configFile)) $configFile = getcwd().'/libraries/config.php';
require $configFile;

$apply = in_array('--apply', $argv, true);
$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);
$table = $config['database']['prefix'].'product';
$aliases = array(
    'ip' => 'waterproof_rating', 'cap do chong nuoc' => 'waterproof_rating', 'cap do bao ve' => 'waterproof_rating',
    'chipled' => 'led_chip', 'chip led' => 'led_chip', 'chip led smd' => 'led_chip',
    'bo san pham' => 'package_contents', 'bo sp bao gom' => 'package_contents',
    'phuong phap sac thoi gian' => 'charging_time',
    'kich thuoc tam pin nang luong' => 'panel_dimensions'
);
$limits = array('waterproof_rating' => 50, 'led_chip' => 100, 'charging_time' => 100, 'panel_dimensions' => 150);

function normalizeSpecKey006($value)
{
    $value = mb_strtolower(trim(strip_tags((string) $value)), 'UTF-8');
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', strtolower((string) $ascii))));
}

$fields = array_values(array_unique(array_values($aliases)));
$rows = $pdo->query('SELECT id,namevi,additional_specs,'.implode(',', $fields).' FROM '.$table." WHERE TRIM(COALESCE(additional_specs,'')) <> '' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$sets = array_merge($fields, array('additional_specs'));
$update = $pdo->prepare('UPDATE '.$table.' SET '.implode(',', array_map(function($field) { return $field.' = :'.$field; }, $sets)).' WHERE id = :id');
$changedProducts = 0;
$movedValues = 0;
$renamedDuplicates = 0;
$invalidProducts = 0;

foreach($rows as $row) {
    $specs = json_decode(htmlspecialchars_decode((string) $row['additional_specs'], ENT_QUOTES), true);
    if(!is_array($specs)) {
        $invalidProducts++;
        echo '#'.$row['id'].' JSON không hợp lệ, bỏ qua'.PHP_EOL;
        continue;
    }
    $remaining = array();
    $changes = array();
    $labelCounts = array();
    foreach($specs as $spec) {
        $label = isset($spec['label']) ? trim(strip_tags((string) $spec['label'])) : '';
        $value = isset($spec['value']) ? trim(strip_tags((string) $spec['value'])) : '';
        if($label === '' || $value === '') continue;
        $key = normalizeSpecKey006($label);
        if(isset($aliases[$key])) {
            $field = $aliases[$key];
            $fits = !isset($limits[$field]) || mb_strlen($value, 'UTF-8') <= $limits[$field];
            if($fits && trim((string) $row[$field]) === '' && !isset($changes[$field])) {
                $changes[$field] = $value;
                $movedValues++;
                continue;
            }
        }
        $labelKey = mb_strtolower(preg_replace('/\s+/u', ' ', $label), 'UTF-8');
        $labelCounts[$labelKey] = isset($labelCounts[$labelKey]) ? $labelCounts[$labelKey] + 1 : 1;
        if($labelCounts[$labelKey] > 1) {
            $label .= ' ('.$labelCounts[$labelKey].')';
            $renamedDuplicates++;
        }
        $remaining[] = array('label' => $label, 'value' => $value);
    }
    $normalizedJson = $remaining ? json_encode($remaining, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    if($normalizedJson !== $row['additional_specs']) $changes['additional_specs'] = $normalizedJson;
    if(!$changes) continue;
    $changedProducts++;
    echo '#'.$row['id'].' '.$row['namevi'].' -> '.implode(', ', array_keys($changes)).PHP_EOL;
    if($apply) {
        $params = array('id' => $row['id']);
        foreach($sets as $field) $params[$field] = array_key_exists($field, $changes) ? $changes[$field] : $row[$field];
        $update->execute($params);
    }
}

echo PHP_EOL.'Sản phẩm thay đổi: '.$changedProducts.'/'.count($rows)
    .' | Chuyển vào trường chuẩn: '.$movedValues
    .' | Đổi tên nhãn trùng: '.$renamedDuplicates
    .' | JSON lỗi: '.$invalidProducts.PHP_EOL;
echo $apply ? "Đã ghi DB local/đích được chỉ định.\n" : "DRY-RUN, chưa ghi DB. Dùng --apply sau khi duyệt.\n";
