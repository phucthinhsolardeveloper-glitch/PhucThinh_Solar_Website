<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);

function cacheAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message.PHP_EOL;
}

/* Bundle CSS được dùng chung cho nhiều host/port. URL font phải root-relative,
   nếu giữ host của request tạo cache đầu tiên thì Font Awesome sẽ thành ô vuông
   khi người dùng truy cập qua hostname khác. */
$cssMinifier = new CssMinify(false, new stdClass());
$rebaseMethod = new ReflectionMethod('CssMinify', 'rebaseUrls');
$rebaseMethod->setAccessible(true);
$rebasedCss = $rebaseMethod->invoke(
    $cssMinifier,
    '.icon{src:url("../fonts/icon.woff2?v=1")} .remote{src:url(https://cdn.example/icon.woff2)}',
    'http://127.0.0.1:8080/subdir/assets/themes/local/css/main.css'
);
cacheAssert(strpos($rebasedCss, 'url("/subdir/assets/themes/local/css/../fonts/icon.woff2?v=1")') !== false, 'CssMinify rebase font theo path gốc của website');
cacheAssert(strpos($rebasedCss, '127.0.0.1') === false, 'CssMinify không đóng cứng host/port vào bundle dùng chung');
cacheAssert(strpos($rebasedCss, 'url(https://cdn.example/icon.woff2)') !== false, 'CssMinify giữ nguyên URL font ngoài website');

/* Bug đã vá: một file cache lỗi quyền đứng giữa thư mục từng làm delete()
   return false sớm, bỏ dở toàn bộ phần còn lại chưa xoá — khiến mỗi lần Lưu ở
   Admin phải chờ cache dọn dở dang rồi vẫn còn rác, và lần sau lại chậm hơn. */
$dir = ROOT.'caches';
file_put_contents($dir.'/cache_test_perf_a', 'x');
file_put_contents($dir.'/cache_test_perf_b', 'x');
file_put_contents($dir.'/cache_test_perf_c', 'x');
chmod($dir.'/cache_test_perf_b', 0000);

$result = $cache->delete();

cacheAssert($result === true, 'Cache::delete() trả true dù có file quyền lỗi giữa chừng');
cacheAssert(!file_exists($dir.'/cache_test_perf_a'), 'File đứng trước file lỗi quyền vẫn bị xoá');
cacheAssert(!file_exists($dir.'/cache_test_perf_c'), 'File đứng sau file lỗi quyền vẫn bị xoá, không dừng giữa chừng');

@chmod($dir.'/cache_test_perf_b', 0644);
@unlink($dir.'/cache_test_perf_b');
cacheAssert(!file_exists($dir.'/cache_test_perf_b'), 'Dữ liệu test đã được dọn sạch');

/* Request thumbnail không được quét đệ quy cây cache ở mọi lần gọi. */
$cleanupDir = sys_get_temp_dir().'/pts-cleanup-test-'.getmypid().'-'.mt_rand(1000, 9999);
mkdir($cleanupDir, 0777, true);
$expiredImage = $cleanupDir.'/expired.png';
file_put_contents($expiredImage, 'expired');
touch($expiredImage, time() - 7200);
$firstCleanup = $func->cleanupGeneratedImagesIfDue($cleanupDir, 3600, 60);
if(!is_dir($cleanupDir)) mkdir($cleanupDir, 0777, true);
file_put_contents($cleanupDir.'/second.png', 'keep');
$secondCleanup = $func->cleanupGeneratedImagesIfDue($cleanupDir, 1, 60);
cacheAssert($firstCleanup && !file_exists($expiredImage), 'Lượt cleanup đến hạn xóa file cũ');
cacheAssert(!$secondCleanup && file_exists($cleanupDir.'/second.png'), 'Cleanup kế tiếp được giới hạn, không quét lại cây cache');
@unlink($cleanupDir.'/second.png');
@rmdir($cleanupDir);

/* HTML chỉ dùng biến thể WebP đã có và còn mới; việc encode vẫn nằm ở upload. */
$webpSource = sys_get_temp_dir().'/pts-webp-test-'.getmypid().'.png';
file_put_contents($webpSource, str_repeat('x', 128));
file_put_contents($webpSource.'.webp', 'RIFF'.pack('V', 8).'WEBP'.str_repeat('x', 4));
touch($webpSource.'.webp', time() + 1);
cacheAssert($func->hasFreshWebpVariant($webpSource), 'Nhận biến thể WebP hợp lệ, mới hơn và nhỏ hơn ảnh gốc');
touch($webpSource, time() + 2);
cacheAssert(!$func->hasFreshWebpVariant($webpSource), 'Không dùng biến thể WebP cũ hơn ảnh gốc');
@unlink($webpSource.'.webp');
@unlink($webpSource);

echo "Cache performance tests completed." . PHP_EOL;
