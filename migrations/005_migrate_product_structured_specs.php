<?php
/**
 * Tách bảng HTML thongsokythuatvi cũ sang các cột thông số chuẩn.
 * Mặc định dry-run; thêm --apply để ghi DB local/đích đã được cho phép.
 * Không ghi đè ô cấu trúc đã có và không xóa nội dung HTML cũ.
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
    'cong suat' => 'cong_suat',
    'nguon sang' => 'light_source', 'led' => 'light_source',
    'kich thuoc den' => 'lamp_dimensions',
    'nhiet do mau cct' => 'color_temperature', 'nhiet do mau' => 'color_temperature',
    'mau anh sang' => 'light_color',
    'pin luu tru' => 'battery_capacity', 'dung luong pin' => 'battery_capacity', 'pin' => 'battery_capacity',
    'tam pin nlmt' => 'panel_power', 'tam pin nang luong mat troi' => 'panel_power', 'cong suat tam pin' => 'panel_power',
    'chat lieu' => 'material',
    'thoi gian sac' => 'charging_time',
    'thoi gian chieu sang' => 'lighting_time', 'thoi gian sang' => 'lighting_time',
    'chi so chong nuoc' => 'waterproof_rating', 'chuan chong nuoc' => 'waterproof_rating', 'chong nuoc' => 'waterproof_rating',
    'chip led' => 'led_chip', 'loai chip led' => 'led_chip',
    'dien tich chieu sang' => 'lighting_area', 'dien tich phu sang' => 'lighting_area', 'khong gian chieu sang' => 'lighting_area',
    'do cao lap dat' => 'installation_height', 'chieu cao lap dat' => 'installation_height',
    'kich thuoc tam pin' => 'panel_dimensions',
    'bo san pham gom' => 'package_contents', 'bo san pham bao gom' => 'package_contents',
    'so luong thung' => 'carton_quantity', 'so luong mot thung' => 'carton_quantity',
    'kich thuoc thung' => 'carton_dimensions',
    'bao hanh' => 'warranty', 'thoi gian bao hanh' => 'warranty'
);
$fields = array_values(array_unique(array_values($aliases)));

function specText($html)
{
    $value = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if(strpos($value, '&lt;') !== false) $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function specKey($value)
{
    $value = mb_strtolower(specText($value), 'UTF-8');
    $value = strtr($value, array(
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i','ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u','ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d'
    ));
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $ascii = preg_replace('/[^a-z0-9]+/', ' ', strtolower((string) $ascii));
    return trim(preg_replace('/\s+/', ' ', $ascii));
}

function specPairs($encodedHtml)
{
    $html = html_entity_decode((string) $encodedHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if(strpos($html, '&lt;') !== false) $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $pairs = array();
    if(preg_match_all('~<tr\b[^>]*>(.*?)</tr>~isu', $html, $rows)) {
        foreach($rows[1] as $row) {
            if(preg_match_all('~<t[dh]\b[^>]*>(.*?)</t[dh]>~isu', $row, $cells) && count($cells[1]) >= 2) {
                $label = specText($cells[1][0]);
                $value = specText(implode(' ', array_slice($cells[1], 1)));
                if($label !== '' && $value !== '') $pairs[] = array($label, $value);
            }
        }
    }
    return $pairs;
}

$select = implode(',', $fields);
$rows = $pdo->query("SELECT id,namevi,thongsokythuatvi,additional_specs,{$select} FROM {$table} WHERE TRIM(COALESCE(thongsokythuatvi,'')) <> '' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$assignments = implode(',', array_map(function($field) { return "{$field} = :{$field}"; }, array_merge($fields, array('additional_specs'))));
$update = $pdo->prepare("UPDATE {$table} SET {$assignments} WHERE id = :id");
$productsChanged = 0;
$valuesFound = 0;
$unknownLabels = array();

foreach($rows as $row) {
    $changes = array();
    $additional = array();
    foreach(specPairs($row['thongsokythuatvi']) as $pair) {
        $key = specKey($pair[0]);
        if(!isset($aliases[$key])) {
            $unknownLabels[$pair[0]] = isset($unknownLabels[$pair[0]]) ? $unknownLabels[$pair[0]] + 1 : 1;
            $additional[] = array('label' => $pair[0], 'value' => $pair[1]);
            continue;
        }
        $field = $aliases[$key];
        if(trim((string) $row[$field]) !== '' || isset($changes[$field])) continue;
        $value = trim($pair[1]);
        if($field === 'cong_suat') {
            if(!preg_match('/(\d+(?:[.,]\d+)?)\s*w\b/ui', $value, $match)) continue;
            $value = str_replace(',', '.', $match[1]);
        }
        $changes[$field] = $value;
    }
    if($additional && trim((string) $row['additional_specs']) === '') {
        $changes['additional_specs'] = json_encode($additional, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if(!$changes) continue;
    $productsChanged++;
    $valuesFound += count($changes);
    echo '#'.$row['id'].' '.$row['namevi'].' -> '.implode(', ', array_keys($changes)).PHP_EOL;
    if($apply) {
        $params = array('id' => $row['id']);
        foreach(array_merge($fields, array('additional_specs')) as $field) $params[$field] = isset($changes[$field]) ? $changes[$field] : $row[$field];
        $update->execute($params);
    }
}

arsort($unknownLabels);
echo PHP_EOL.'Sản phẩm có thể chuyển: '.$productsChanged.'/'.count($rows).' | Giá trị tách được: '.$valuesFound.PHP_EOL;
echo $apply ? "Đã ghi DB; HTML cũ vẫn được giữ để rollback.\n" : "DRY-RUN, chưa ghi DB. Dùng --apply sau khi kiểm tra.\n";
echo "Nhãn chưa ánh xạ (cần rà soát):\n";
foreach(array_slice($unknownLabels, 0, 80, true) as $label => $count) echo "  [{$count}] {$label}\n";
