<?php
/**
 * Bổ sung đúng các block FAQ/cards còn thiếu của danh mục Đèn NLMT (list 20).
 * Giữ nguyên mọi block đã được Admin soạn và có thể chạy lặp lại an toàn.
 *
 * Preview: php migrations/073_ensure_lighting_catalog_content.php
 * Apply:   php migrations/073_ensure_lighting_catalog_content.php --apply
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$apply = in_array('--apply', $argv, true);
$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php')) $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php')) { fwrite(STDERR, "Không tìm thấy libraries/config.php.\n"); exit(1); }

define('LIBRARIES', $publicHtml.'/libraries/');
require $publicHtml.'/libraries/config.php';
require_once $publicHtml.'/libraries/product_content_blocks.php';

$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) { fwrite(STDERR, "Table prefix không hợp lệ.\n"); exit(1); }

$db = $config['database'];
$pdo = new PDO(
    'mysql:host='.$db['host'].';port='.(int)$db['port'].';dbname='.$db['dbname'].';charset=utf8mb4',
    $db['username'],
    $db['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$stmt = $pdo->prepare("select id, blocksvi from `{$prefix}content_section` where scope='list' and scope_id=? limit 1");
$stmt->execute(array(20));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$row) { fwrite(STDERR, "Không tìm thấy content_section của danh mục Đèn NLMT (list 20).\n"); exit(1); }

$blocks = json_decode(htmlspecialchars_decode((string)$row['blocksvi'], ENT_QUOTES), true);
$blocks = is_array($blocks) ? $blocks : array();
$hasFaq = false;
$hasCards = false;
foreach($blocks as $block)
{
    if(!is_array($block)) continue;
    if(($block['type'] ?? '') === 'faq') $hasFaq = true;
    if(($block['type'] ?? '') === 'cards') $hasCards = true;
}

$fallback = productLightingFallbackBlocks();
$added = array();
if(!$hasFaq) { $blocks = array_merge($blocks, $fallback['faq']); $added[] = '3 FAQ'; }
if(!$hasCards) { $blocks = array_merge($blocks, $fallback['cards']); $added[] = '1 lưới thẻ'; }
if(!$added) { echo "SKIP: danh mục Đèn NLMT đã đủ FAQ và lưới thẻ.\n"; exit(0); }

echo 'PREVIEW: content_section id='.$row['id'].' sẽ thêm '.implode(' + ', $added)."; giữ nguyên dữ liệu hiện hữu.\n";
if(!$apply) exit(0);

$json = htmlspecialchars(json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
$pdo->beginTransaction();
try
{
    $update = $pdo->prepare("update `{$prefix}content_section` set blocksvi=?, date_updated=? where id=?");
    $update->execute(array($json, time(), (int)$row['id']));
    $pdo->commit();
    echo "APPLY xong: ".implode(' + ', $added).".\n";
}
catch(Throwable $e)
{
    if($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
