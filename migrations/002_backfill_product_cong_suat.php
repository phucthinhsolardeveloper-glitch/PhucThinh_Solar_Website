<?php
/**
 * Tự dò công suất (W) từ tên sản phẩm và điền vào cột cong_suat.
 *
 * Mặc định chạy DRY-RUN (chỉ in ra, không ghi DB). Thêm --apply để ghi thật.
 *
 * Cách chạy (từ thư mục domains/phucthinhsolar.com/public_html):
 *   php ../../../migrations/002_backfill_product_cong_suat.php          # xem trước
 *   php ../../../migrations/002_backfill_product_cong_suat.php --apply  # ghi DB
 *
 * Sau khi apply, cần kiểm tra các giá trị NULL hoặc kết quả nhận diện sai.
 */

define('LIBRARIES', './libraries/');
define('ADMIN', 'administrator');
require __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/config.php';

$apply = in_array('--apply', $argv, true);

$pdo = new PDO(
	'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
	$config['database']['username'],
	$config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$prefix = $config['database']['prefix'];
$rows = $pdo->query("select id, namevi, cong_suat from {$prefix}product order by id")->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare("update {$prefix}product set cong_suat = ? where id = ?");

$matched = 0;
$skipped = 0;

foreach($rows as $row)
{
	if(!preg_match('/(\d+(?:[.,]\d+)?)\s*W\b/ui', $row['namevi'], $m))
	{
		$skipped++;
		echo "[BO QUA] #{$row['id']} - {$row['namevi']}\n";
		continue;
	}

	$watt = (float) str_replace(',', '.', $m[1]);
	$matched++;
	echo "[DO DUOC] #{$row['id']} -> {$watt}W - {$row['namevi']}\n";

	if($apply)
	{
		$update->execute([$watt, $row['id']]);
	}
}

echo "\n---\n";
echo "Tong: ".count($rows)." san pham | Do duoc: {$matched} | Khong do duoc: {$skipped}\n";
echo $apply ? "Da ghi vao DB.\n" : "DRY-RUN - chua ghi gi. Chay lai voi --apply de ghi that.\n";
