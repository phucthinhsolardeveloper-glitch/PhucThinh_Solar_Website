<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);

function cacheAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message.PHP_EOL;
}

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

echo "Cache performance tests completed." . PHP_EOL;
