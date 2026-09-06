<?php
/**
 * Nén lại ảnh quá khổ trong upload/ mà KHÔNG đổi tên file, kích thước hay định dạng.
 *
 * Ảnh sản phẩm nhập từ ORIANA nặng 2-4 MB mỗi tấm ở 1920x1920, và một số PNG
 * (logo đối tác, ảnh xuất từ svgviewer) được lưu gần như không nén — có tấm
 * 13,9 MB. Ảnh hiển thị ra trang đều đi qua thumbnail nên người xem không tải
 * bản gốc, nhưng bản gốc vẫn chiếm chỗ trên host và làm mọi lần sinh thumbnail
 * chậm hơn.
 *
 * Cách xử lý theo định dạng:
 *   PNG  - mã hoá lại ở mức nén 9. Đây là nén KHÔNG MẤT DỮ LIỆU: từng điểm ảnh
 *          giữ nguyên y hệt, chỉ khác cách đóng gói. Giữ nguyên kênh trong suốt.
 *   JPEG - mã hoá lại ở chất lượng 88. Có mất dữ liệu ở mức rất nhỏ, nên chỉ ghi
 *          đè khi tiết kiệm được từ 25% trở lên; dưới ngưỡng đó thì ảnh vốn đã
 *          được nén tốt, đụng vào chỉ làm giảm chất lượng mà không lợi gì.
 *
 * Không đổi tên, không đổi kích thước, không đổi định dạng, không xoá ảnh nào,
 * nên mọi đường dẫn trong DB và mọi thumbnail đã sinh vẫn đúng.
 *
 * Ảnh gốc được chép sang thư mục sao lưu TRƯỚC khi ghi đè. Rollback: chép ngược
 * từ thư mục đó về.
 *
 * Mỗi ảnh sau khi ghi đều được mở lại kiểm tra: đọc được, đúng kích thước cũ,
 * và nhỏ hơn bản cũ. Sai một điều là khôi phục ngay bản gốc cho riêng ảnh đó.
 *
 * Chạy:
 *   php migrations/025_compress_oversized_images.php                 # xem trước
 *   php migrations/025_compress_oversized_images.php --apply         # nén thật
 *   php migrations/025_compress_oversized_images.php --apply --min-kb=500
 */

if (PHP_SAPI !== 'cli') exit(1);
if (!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if (!is_dir($publicHtml.'/upload') && is_dir('/var/www/html/upload')) $publicHtml = '/var/www/html';
if (!is_dir($publicHtml.'/upload'))
{
    fwrite(STDERR, "Không tìm thấy thư mục upload/.\n");
    exit(1);
}

$apply = in_array('--apply', $argv, true);
$minBytes = 1048576;
$backupDir = '';
foreach ($argv as $arg)
{
    if (strpos($arg, '--min-kb=') === 0) $minBytes = max(1, (int)substr($arg, 9)) * 1024;
    if (strpos($arg, '--backup-dir=') === 0) $backupDir = rtrim(substr($arg, 13), '/');
}
if ($backupDir === '') $backupDir = '/mnt/d/FIleLinhTinh/phucthinhsolar-donkho-20260905/anh-goc-truoc-khi-nen';

$jpegQuality = 88;
$jpegMinGain = 0.25;   /* JPEG phải tiết kiệm >=25% mới đáng đổi */
$pngMinGain  = 0.02;   /* PNG không mất dữ liệu nên chỉ cần lợi là làm */

/* Duyệt upload/, lấy ảnh vượt ngưỡng. */
$targets = array();
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($publicHtml.'/upload', FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file)
{
    if (!$file->isFile() || $file->getSize() < $minBytes) continue;
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, array('jpg', 'jpeg', 'png'), true)) continue;
    $targets[] = $file->getPathname();
}
sort($targets);

/* Mã hoá ra file tạm chứ không dùng ob_start(): đỡ giữ cả ảnh trong RAM, và
   quan trọng hơn là lỗi nặng của GD không bị output buffer nuốt mất. */
function recompress($path, $ext, $jpegQuality)
{
    $image = $ext === 'png' ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
    if (!$image) return null;
    $tmp = tempnam(sys_get_temp_dir(), 'pts-img-');
    if ($tmp === false) { imagedestroy($image); return null; }
    if ($ext === 'png')
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $ok = @imagepng($image, $tmp, 9);
    }
    else $ok = @imagejpeg($image, $tmp, $jpegQuality);
    imagedestroy($image);
    if (!$ok) { @unlink($tmp); return null; }
    clearstatcache(true, $tmp);
    return array('tmp' => $tmp, 'size' => filesize($tmp));
}

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để nén thật)\n";
echo "Ngưỡng xét: ảnh từ ", round($minBytes / 1024), " KB trở lên | JPEG q", $jpegQuality,
     " (chỉ đổi khi lợi >=", round($jpegMinGain * 100), "%) | PNG nén lại không mất dữ liệu\n";
echo "Thư mục sao lưu ảnh gốc: ", $backupDir, "\n\n";

$plan = array();
$sumOld = 0;
$sumNew = 0;
$skipped = 0;
foreach ($targets as $path)
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $old = filesize($path);
    $info = @getimagesize($path);
    if (!$info) { $skipped++; continue; }
    $result = recompress($path, $ext, $jpegQuality);
    if ($result === null) { $skipped++; echo "  BỎ QUA (GD không xử lý được): ", str_replace($publicHtml.'/', '', $path), "\n"; continue; }
    $new = $result['size'];
    @unlink($result['tmp']);
    $gain = $old > 0 ? (1 - $new / $old) : 0;
    $threshold = $ext === 'png' ? $pngMinGain : $jpegMinGain;
    if ($gain < $threshold) { $skipped++; continue; }
    $plan[] = array('path' => $path, 'ext' => $ext, 'old' => $old, 'new' => $new,
                    'gain' => $gain, 'w' => $info[0], 'h' => $info[1]);
    $sumOld += $old;
    $sumNew += $new;
}

printf("Ảnh vượt ngưỡng: %d | sẽ nén: %d | bỏ qua (đã nén tốt / không đọc được): %d\n\n",
       count($targets), count($plan), $skipped);
printf("Tổng: %.1f MB -> %.1f MB  (tiết kiệm %.1f MB, %.0f%%)\n\n",
       $sumOld / 1048576, $sumNew / 1048576, ($sumOld - $sumNew) / 1048576,
       $sumOld > 0 ? (1 - $sumNew / $sumOld) * 100 : 0);

usort($plan, function ($a, $b) { return ($b['old'] - $b['new']) - ($a['old'] - $a['new']); });
foreach (array_slice($plan, 0, 15) as $item)
    printf("  %-52s %6.1fMB -> %5.1fMB  (-%2.0f%%)\n",
           substr(str_replace($publicHtml.'/', '', $item['path']), 0, 52),
           $item['old'] / 1048576, $item['new'] / 1048576, $item['gain'] * 100);
if (count($plan) > 15) printf("  ... và %d ảnh nữa\n", count($plan) - 15);

if (!$apply)
{
    echo "\nChưa ghi gì. Chạy lại kèm --apply để nén thật.\n";
    exit(0);
}

if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir))
{
    fwrite(STDERR, "Không tạo được thư mục sao lưu: ".$backupDir."\n");
    exit(1);
}

$done = 0;
$reverted = 0;
$freed = 0;
foreach ($plan as $item)
{
    $path = $item['path'];
    $relative = str_replace($publicHtml.'/', '', $path);
    $backupPath = $backupDir.'/'.$relative;
    $backupParent = dirname($backupPath);
    if (!is_dir($backupParent) && !mkdir($backupParent, 0775, true) && !is_dir($backupParent))
    {
        fwrite(STDERR, "Không tạo được thư mục sao lưu cho ".$relative."\n");
        exit(1);
    }
    /* Sao lưu trước khi ghi đè; không sao lưu được thì không đụng vào ảnh. */
    if (!file_exists($backupPath) && !copy($path, $backupPath))
    {
        fwrite(STDERR, "Không sao lưu được, bỏ qua: ".$relative."\n");
        continue;
    }

    $result = recompress($path, $item['ext'], $jpegQuality);
    if ($result === null) continue;
    if ($result['size'] >= $item['old']) { @unlink($result['tmp']); continue; }
    if (!@copy($result['tmp'], $path))
    {
        @unlink($result['tmp']);
        copy($backupPath, $path);
        $reverted++;
        continue;
    }
    @unlink($result['tmp']);

    /* Mở lại kiểm chứng: đọc được, đúng kích thước cũ, nhỏ hơn bản cũ. */
    clearstatcache(true, $path);
    $check = @getimagesize($path);
    $sizeNow = filesize($path);
    if (!$check || (int)$check[0] !== (int)$item['w'] || (int)$check[1] !== (int)$item['h'] || $sizeNow >= $item['old'])
    {
        copy($backupPath, $path);
        $reverted++;
        fwrite(STDERR, "Kiểm chứng thất bại, đã khôi phục: ".$relative."\n");
        continue;
    }
    $done++;
    $freed += $item['old'] - $sizeNow;
}

printf("\nĐã nén %d ảnh, khôi phục %d ảnh do kiểm chứng thất bại.\n", $done, $reverted);
printf("Giải phóng %.1f MB. Ảnh gốc nằm ở %s\n", $freed / 1048576, $backupDir);
echo "Tên file, kích thước và định dạng không đổi nên không cần sửa DB hay xoá thumbnail.\n";
