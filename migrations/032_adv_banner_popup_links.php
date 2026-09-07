<?php
/**
 * Chuyển 2 banner quảng cáo (table_photo, type=adv) đang trỏ tới trang
 * lien-he/dai-ly sang giá trị đặc biệt #ptsContactModal / #ptsDealerModal —
 * khớp với dropdown "Khi bấm nút" mới thêm ở màn hình sửa banner
 * (photo_edit_tpl.php) và cách templates/index/index_tpl.php nhận diện popup
 * (so khớp CHÍNH XÁC, không còn dò chuỗi con trong link như bản trước).
 *
 * Vì sao cần: 2 popup Liên hệ / Đăng ký đại lý dùng chung toàn site đã thay
 * thế việc điều hướng sang trang riêng cho đúng nút "Đăng ký ngay" trên các
 * banner này. Nếu không cập nhật dữ liệu, link cũ (lien-he/#yeu-cau-tu-van,
 * dai-ly/#dang-ky-dai-ly) không còn khớp với so sánh mới trong index_tpl.php
 * và banner sẽ lặng lẽ quay về hành vi điều hướng thường — đúng cái lỗi
 * đang sửa lại xuất hiện.
 *
 * Chỉ cập nhật các dòng có link CHÍNH XÁC là 2 giá trị legacy dưới đây; nếu
 * admin đã tự sửa link banner này thành gì khác thì bỏ qua, không suy đoán.
 *
 * Chạy:
 *   php migrations/032_adv_banner_popup_links.php          # xem trước
 *   php migrations/032_adv_banner_popup_links.php --apply  # ghi DB
 */

if (PHP_SAPI !== 'cli') exit(1);
if (!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if (!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
if (!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}
define('LIBRARIES', $publicHtml.'/libraries/');
require LIBRARIES.'config.php';

$apply = in_array('--apply', $argv, true);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';

$mapping = array(
    'lien-he/#yeu-cau-tu-van' => '#ptsContactModal',
    'dai-ly/#dang-ky-dai-ly' => '#ptsDealerModal',
);

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";

$updated = 0;
$found = 0;
foreach ($mapping as $oldLink => $newLink)
{
    $select = $pdo->prepare("select id, namevi from `{$prefix}photo` where type = 'adv' and link = ?");
    $select->execute(array($oldLink));
    $rows = $select->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row)
    {
        $found++;
        printf("Banner #%d \"%s\": %s -> %s\n", $row['id'], $row['namevi'], $oldLink, $newLink);
        if ($apply)
        {
            /* Giữ nguyên điều kiện đã dry-run trong câu UPDATE để không ghi đè
               nếu admin vừa sửa banner ở giữa bước SELECT và UPDATE. */
            $update = $pdo->prepare("update `{$prefix}photo` set link = ? where id = ? and type = 'adv' and link = ?");
            $update->execute(array($newLink, $row['id'], $oldLink));
            $updated += $update->rowCount();
        }
    }
}

if (!$found)
{
    echo "Không có dòng nào khớp link legacy cần chuyển (đã chuyển trước đó hoặc admin đã tự sửa).\n";
}

echo "\n";
if (!$apply) { echo "Chưa ghi gì. Chạy lại kèm --apply để thực hiện.\n"; exit(0); }
echo "Đã cập nhật ", $updated, " banner.\n";

/* Truy vấn banner được cache 7200s (2 tiếng) và KHÔNG có cơ chế tự nhận biết
   dữ liệu vừa đổi — ghi thẳng qua PDO như trên sẽ vô hình với người dùng
   thật cho tới khi cache hết hạn. Xoá cache ngay để thay đổi có hiệu lực tức
   thì, giống hệt việc admin bấm Lưu trên form (đáng lẽ cũng phải làm vậy). */
if ($updated > 0)
{
    $cachePath = $publicHtml.'/caches';
    if (is_dir($cachePath))
    {
        $cleared = 0;
        foreach (scandir($cachePath) as $file)
            if (strpos($file, 'cache_') === 0) { @unlink($cachePath.'/'.$file); $cleared++; }
        echo "Đã xoá ", $cleared, " file cache để thay đổi hiển thị ngay.\n";
    }
}
