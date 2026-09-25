<?php
/**
 * Bỏ nội dung lỗi thời/không kiểm chứng được trong khối "dai-ly-1"
 * (table_static, trang /dai-ly/, section đầu):
 * - "Chương trình mục tiêu Quốc gia... (2021-2025)" - chương trình đã hết
 *   giai đoạn, nhắc tới trong nội dung 2026 gây hiểu lầm còn hiệu lực.
 * - "Năm 2021, doanh thu ngành đèn NLMT... hàng nghìn tỉ đồng/năm" - số liệu
 *   không có nguồn, không kiểm chứng được - bỏ nguyên đoạn.
 * - "chưa nhiều Đại lý đèn Năng Lượng Mặt Tròi tại Việt Nam" - claim thị
 *   trường không có căn cứ, kèm lỗi chính tả "Mặt Tròi".
 * - Ảnh check-mark nhúng từ static.xx.fbcdn.net (CDN nội bộ Facebook) - phụ
 *   thuộc domain ngoài không kiểm soát được; thay bằng ký tự ✔ thuần text.
 *
 * Dùng str_replace từng đoạn nhỏ (giống migration 053) thay vì so khớp toàn
 * bộ nội dung - nội dung DB lưu dạng HTML-entity-escaped (&lt;p&gt;...), so
 * khớp nguyên khối dễ lệch byte. Giữ nguyên khung ý chính, không thêm số
 * liệu mới.
 *
 * Mặc định chỉ preview:
 *   php migrations/054_remove_dealer_page_stale_claims.php
 *   php migrations/054_remove_dealer_page_stale_claims.php --apply
 *   php migrations/054_remove_dealer_page_stale_claims.php --rollback
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

/* [cũ, mới] - áp theo thứ tự khi --apply, ngược lại khi --rollback. Chuỗi
   "cũ" lấy nguyên văn từ DB (dạng HTML-entity-escaped), không gõ tay lại
   toàn bộ đoạn để tránh lệch byte. */
$oldImgTag = '&lt;img alt=&quot;✔&quot; data-ll-status=&quot;loaded&quot; height=&quot;16&quot; loading=&quot;lazy&quot; src=&quot;https://static.xx.fbcdn.net/images/emoji.php/v9/t51/1/16/2714.png&quot; width=&quot;16&quot; /&gt;';

$replacements = array(
    array(
        'Chương trình mục tiêu Quốc gia xây dựng nông thôn mới (2021 – 2025) &amp; phát triển hạ tầng Kinh tế -Xã hội hướng đến đô thị thông minh, đòi hỏi các công trình chiếu sáng phải cải tiến, sử dụng',
        'Đèn năng lượng mặt trời sử dụng'
    ),
    array(
        '&lt;p&gt;'.$oldImgTag.'Năm 2021, doanh thu ngành đèn NLMT tính riêng tại Việt Nam hàng nghìn tỉ đồng/năm.&lt;/p&gt;'."\r\n\r\n",
        ''
    ),
    array($oldImgTag, '✔ '),
    array(
        'Cơ hội phát triển kênh phân phối lợi nhuận cao vì hiện tại chưa nhiều Đại lý đèn Năng Lượng Mặt Tròi tại Việt Nam.',
        'Cơ hội mở rộng kênh phân phối thiết bị năng lượng mặt trời tại địa phương, theo đúng nhu cầu thị trường thực tế.'
    )
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$query = $pdo->prepare("select id, descvi from `{$prefix}static` where type = 'dai-ly-1' limit 1");
$query->execute();
$row = $query->fetch(PDO::FETCH_ASSOC);
if(!$row)
{
    fwrite(STDERR, "Không tìm thấy static type=dai-ly-1.\n");
    exit(1);
}

$original = (string) $row['descvi'];
$content = $original;
$appliedCount = 0;
foreach($replacements as $pair)
{
    list($from, $to) = $rollback ? array($pair[1], $pair[0]) : $pair;
    if($from === '') continue;
    $content = str_replace($from, $to, $content, $count);
    $appliedCount += $count;
}

if($content === $original)
{
    echo "SKIP: không có cụm nào khớp để sửa (có thể đã áp dụng rồi, hoặc nội dung đã đổi khác).\n";
    exit(0);
}

if(!$apply && !$rollback)
{
    echo "PREVIEW — không ghi gì. $appliedCount cụm sẽ được thay.\n";
    echo "--- nội dung sau khi sửa ---\n".html_entity_decode($content, ENT_QUOTES, 'UTF-8')."\n";
    echo "Chạy lại với --apply để áp dụng.\n";
    exit(0);
}

$update = $pdo->prepare("update `{$prefix}static` set descvi = ? where id = ? and descvi = ?");
$update->execute(array($content, (int)$row['id'], $original));
if($update->rowCount() !== 1)
{
    fwrite(STDERR, "Bản ghi đã thay đổi trong lúc chạy; không cập nhật.\n");
    exit(1);
}
echo ($rollback ? 'ROLLBACK' : 'APPLY')." xong: đã sửa $appliedCount cụm trong dai-ly-1.\n";
