<?php
/**
 * Sửa lỗi chính tả và bỏ claim marketing quá đà trong bài
 * "8 LÝ DO BẠN NÊN SỬ DỤNG ĐÈN NĂNG LƯỢNG MẶT TRỜI NGAY BÂY GIỜ"
 * (table_news, type=tin-tuc, slugvi=8-ly-do-ban-nen-su-dung-den-nang-luong-mat-troi-ngay-bay-gio).
 *
 * Sửa:
 * - "Tiết kiện tiền điện" -> "Tiết kiệm tiền điện" (lỗi chính tả).
 * - "Tiết kiệm cho phí lắp đặt" -> "Tiết kiệm chi phí lắp đặt" (lỗi chính tả).
 * - "Tuổi thọ đèn có để đạt đến" -> "Tuổi thọ đèn có thể đạt đến" (lỗi chính tả).
 * - "An toàn tuyệt đối 100%" -> bỏ, không sản phẩm điện nào an toàn tuyệt đối
 *   100% - đây là claim marketing không có bằng chứng, audit SEO 2026-09-25
 *   đã flag.
 * - "hạn chế tốt đa" -> "hạn chế tối đa" (lỗi chính tả).
 *
 * Chỉ sửa đúng các cụm lỗi trên bằng str_replace nguyên văn, không viết lại
 * nội dung bài. Mặc định chỉ preview:
 *   php migrations/053_fix_solar_light_article_claims_and_typos.php
 *   php migrations/053_fix_solar_light_article_claims_and_typos.php --apply
 *   php migrations/053_fix_solar_light_article_claims_and_typos.php --rollback
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$apply = in_array('--apply', $argv, true);
$rollback = in_array('--rollback', $argv, true);
if($apply && $rollback)
{
    fwrite(STDERR, "Chỉ được chọn một trong --apply hoặc --rollback.\n");
    exit(1);
}

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}

define('LIBRARIES', $publicHtml.'/libraries/');
require $publicHtml.'/libraries/config.php';
$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix))
{
    fwrite(STDERR, "Table prefix không hợp lệ.\n");
    exit(1);
}

/* [cũ, mới] - áp theo thứ tự khi --apply, ngược lại khi --rollback. */
$replacements = array(
    array('Tiết kiện tiền điện', 'Tiết kiệm tiền điện'),
    array('Tiết kiệm cho phí lắp đặt', 'Tiết kiệm chi phí lắp đặt'),
    array('Tuổi thọ đèn có để đạt đến', 'Tuổi thọ đèn có thể đạt đến'),
    array('An toàn: bạn sẽ không bao giờ phải lo lắng đến sự cố rò rỉ điện, cháy nổ,… An toàn tuyệt đối 100%.', 'An toàn: đèn không dùng điện lưới nên giảm hẳn rủi ro rò rỉ điện qua dây dẫn ngoài trời so với đèn điện truyền thống.'),
    array('hạn chế tốt đa', 'hạn chế tối đa')
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$slug = '8-ly-do-ban-nen-su-dung-den-nang-luong-mat-troi-ngay-bay-gio';
$query = $pdo->prepare("select id, contentvi from `{$prefix}news` where type = 'tin-tuc' and slugvi = ? limit 1");
$query->execute(array($slug));
$row = $query->fetch(PDO::FETCH_ASSOC);
if(!$row)
{
    fwrite(STDERR, "Không tìm thấy bài viết slug=$slug.\n");
    exit(1);
}

$original = (string) $row['contentvi'];
$content = $original;
$appliedCount = 0;
foreach($replacements as $pair)
{
    list($from, $to) = $rollback ? array($pair[1], $pair[0]) : $pair;
    $newContent = str_replace($from, $to, $content, $count);
    if($count > 0) $appliedCount += $count;
    $content = $newContent;
}

if($content === $original)
{
    echo "SKIP: không có cụm nào khớp để sửa (có thể đã áp dụng rồi, hoặc nội dung đã đổi khác).\n";
    exit(0);
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì. $appliedCount cụm sẽ được thay.\n";
    foreach($replacements as $pair)
    {
        list($from, $to) = $rollback ? array($pair[1], $pair[0]) : $pair;
        if(strpos($original, $from) !== false) echo "  \"$from\"\n      -> \"$to\"\n";
    }
    echo "Chạy lại với --apply để áp dụng.\n";
    exit(0);
}

$update = $pdo->prepare("update `{$prefix}news` set contentvi = ? where id = ? and contentvi = ?");
$update->execute(array($content, (int)$row['id'], $original));
if($update->rowCount() !== 1)
{
    fwrite(STDERR, "Bản ghi đã thay đổi trong lúc chạy; không cập nhật.\n");
    exit(1);
}
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong: đã sửa $appliedCount cụm trong bài viết.\n";
