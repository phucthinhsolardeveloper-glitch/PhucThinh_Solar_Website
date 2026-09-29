<?php
/**
 * Xoá các sản phẩm đã ngừng kinh doanh theo danh sách mã do nguồn dữ liệu đã phê duyệt.
 *
 * Danh sách gồm nhiều cặp trùng lặp được nhập hai lần với mã khác nhau
 * (UFO1000/UFO-1000, UFO500-500W/UFO-500, JD-9300/JD9300,
 * "BCT-OLC1.0"/"BCT - OLC1.0"), nên đây vừa là dọn hàng ngừng bán vừa là dọn
 * bản ghi trùng.
 *
 * Script dọn đúng những bảng mà deleteMan() trong administrator/sources/product.php
 * dọn khi xoá một sản phẩm qua Admin, cộng thêm phạm vi chiến dịch
 * (#_product_campaign_scope) mà luồng Admin bỏ sót:
 *   product, product_project, content_section, seo, product_tags, product_sale,
 *   gallery (+ file ảnh/đính kèm), comment (+ comment_photo, comment_video),
 *   product_campaign_scope
 *
 * Sản phẩm còn nằm trong #_order_detail sẽ bị BỎ QUA và liệt kê riêng, vì xoá
 * sẽ làm hỏng lịch sử đơn hàng — cần phê duyệt thủ công thay vì script đoán.
 * Mã không tìm thấy cũng được liệt kê riêng (có thể đã xoá tay trước đó).
 *
 * Ảnh chỉ bị xoá khỏi đĩa khi không còn sản phẩm nào khác dùng chung tên file.
 *
 * BACKUP DB trước khi chạy --apply. Không có rollback tự động: khôi phục bằng
 * bản backup, vì bản ghi bị xoá hẳn chứ không đánh dấu ẩn.
 *
 * Chạy (từ domains/phucthinhsolar.com/public_html):
 *   php ../../../migrations/023_delete_retired_products.php          # xem trước
 *   php ../../../migrations/023_delete_retired_products.php --apply  # ghi DB
 */


/* Chỉ cho chạy từ dòng lệnh (cron / terminal). Nếu thư mục migrations lỡ nằm
   trong web root thì người ngoài không gọi được qua trình duyệt. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('LIBRARIES', './libraries/');
/* config.php đọc SERVER_NAME để chọn cấu hình; chạy CLI thì biến này không có. */
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';
/* Trên server thì migrations/ nằm cạnh domains/. Trong container local chỉ có
   public_html được mount vào /var/www/html, nên dò thêm nhánh đó. */
$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
	$publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php'))
{
	fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
	exit(1);
}
require $publicHtml.'/libraries/config.php';

$apply = in_array('--apply', $argv, true);

$pdo = new PDO(
	'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
	$config['database']['username'],
	$config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$p = $config['database']['prefix'];
$type = 'san-pham';

$uploadProduct = $publicHtml.'/upload/product/';
$uploadFile    = $publicHtml.'/upload/file/';
$uploadPhoto   = $publicHtml.'/upload/photo/';
$uploadVideo   = $publicHtml.'/upload/video/';

/* Mã do nguồn dữ liệu đã phê duyệt ngày 2026-09-05. Giữ nguyên khoảng trắng trong
   "BCT - OLC1.0": đó là mã có thật trong DB, khác với "BCT-OLC1.0". */
$codes = array(
	'JDL-BULB200', 'JD-19600L', 'JD-19400L', 'JD-5100', 'JD-5648', 'JD-4624',
	'JD-3618', 'JD-9300', 'JD-500', 'JD-18500', 'JD-18300', 'JD-18200',
	'JD-81000L', 'JD-8500L-New', 'BCT-OLC1.0', 'ENTELECHY-T800', 'ENTELECHY-V800',
	'JD-L300', 'UFO-KimCuong800w', 'UFO500-500W', 'EN-M25', 'JD-X75', 'BTC-WW4.0',
	'UFO1000', 'JD-L200', 'JD-7300', 'JD-369-100W', 'JD9300', 'JD-1000',
	'TC300-A01', 'TC300-A03', 'TC300-A02', 'K20', 'JD-200NEW', 'BCT - OLC1.0',
	'JDL-OT400', 'UFO-1000', 'UFO-500'
);
$codes = array_values(array_unique($codes));

/* Gom sản phẩm theo mã, tách sẵn ba nhóm: xoá được / vướng đơn hàng / không thấy. */
$targets = array();
$missing = array();
$blocked = array();
foreach($codes as $code)
{
	$statement = $pdo->prepare("select id, code, namevi, photo from {$p}product where code = ? and type = ?");
	$statement->execute(array($code, $type));
	$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
	if(!$rows) { $missing[] = $code; continue; }
	foreach($rows as $row)
	{
		$order = $pdo->prepare("select count(*) from {$p}order_detail where id_product = ?");
		$order->execute(array((int)$row['id']));
		if((int)$order->fetchColumn() > 0) { $blocked[] = $row; continue; }
		$targets[(int)$row['id']] = $row;
	}
}

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";
echo "Mã yêu cầu: ", count($codes), " | Sẽ xoá: ", count($targets),
     " | Vướng đơn hàng: ", count($blocked), " | Không tìm thấy: ", count($missing), "\n\n";

if(!$targets)
{
	echo "Không có sản phẩm nào để xoá.\n";
	exit(0);
}

$ids = array_keys($targets);
$ph = implode(',', array_fill(0, count($ids), '?'));

/* Đếm dữ liệu phụ thuộc để in ra trước khi xoá. */
$counts = array();
foreach(array(
	'gallery'          => "select count(*) from {$p}gallery where kind='man' and com='product' and id_parent in ($ph)",
	'seo'              => "select count(*) from {$p}seo where com='product' and act='man' and id_parent in ($ph)",
	'content_section'  => "select count(*) from {$p}content_section where scope='product' and scope_id in ($ph)",
	'product_tags'     => "select count(*) from {$p}product_tags where id_parent in ($ph)",
	'product_sale'     => "select count(*) from {$p}product_sale where id_parent in ($ph)",
	'product_project'  => "select count(*) from {$p}product_project where id_product in ($ph)",
	'comment'          => "select count(*) from {$p}comment where id_variant in ($ph)",
	'campaign_scope'   => "select count(*) from {$p}product_campaign_scope where scope_type='product' and scope_id in ($ph)"
) as $label => $sql)
{
	$statement = $pdo->prepare($sql);
	$statement->execute($ids);
	$counts[$label] = (int)$statement->fetchColumn();
}

echo "Sản phẩm sẽ bị xoá:\n";
foreach($targets as $id => $row)
	printf("  #%-5s %-18s %s\n", $id, $row['code'], mb_substr((string)$row['namevi'], 0, 62));

echo "\nDữ liệu phụ thuộc bị xoá theo:\n";
foreach($counts as $label => $total) printf("  %-18s %d\n", $label, $total);

if($blocked)
{
	echo "\nBỎ QUA - còn trong đơn hàng, cần phê duyệt thủ công:\n";
	foreach($blocked as $row) printf("  #%-5s %-18s %s\n", $row['id'], $row['code'], mb_substr((string)$row['namevi'], 0, 62));
}
if($missing)
{
	echo "\nKhông tìm thấy trong DB (có thể đã xoá tay trước đó):\n";
	foreach($missing as $code) echo "  ", $code, "\n";
}

if(!$apply)
{
	echo "\nChưa ghi gì. Chạy lại kèm --apply để xoá thật.\n";
	exit(0);
}

/* Ảnh dùng chung với sản phẩm khác thì giữ lại file trên đĩa. */
function fileStillUsed(PDO $pdo, $p, $column, $table, $name, array $excludeIds)
{
	if($name === null || trim((string)$name) === '') return true;
	$ph = implode(',', array_fill(0, count($excludeIds), '?'));
	$statement = $pdo->prepare("select count(*) from {$p}{$table} where {$column} = ? and id not in ($ph)");
	$statement->execute(array_merge(array($name), $excludeIds));
	return (int)$statement->fetchColumn() > 0;
}

function removeFile($directory, $name)
{
	if($name === null || trim((string)$name) === '') return false;
	$path = $directory.$name;
	if(!is_file($path)) return false;
	return @unlink($path);
}

/* Thu thập file trước khi xoá bản ghi, vì sau khi xoá không truy ra được nữa. */
$galleryStatement = $pdo->prepare("select id, photo, file_attach from {$p}gallery where kind='man' and com='product' and id_parent in ($ph)");
$galleryStatement->execute($ids);
$galleryRows = $galleryStatement->fetchAll(PDO::FETCH_ASSOC);

$commentStatement = $pdo->prepare("select id, id_parent from {$p}comment where id_variant in ($ph)");
$commentStatement->execute($ids);
$commentRows = $commentStatement->fetchAll(PDO::FETCH_ASSOC);
$commentIds = array();
foreach($commentRows as $row) $commentIds[] = (int)$row['id'];

$commentPhotoRows = array();
$commentVideoRows = array();
if($commentIds)
{
	$cph = implode(',', array_fill(0, count($commentIds), '?'));
	$statement = $pdo->prepare("select photo from {$p}comment_photo where id_parent in ($cph)");
	$statement->execute($commentIds);
	$commentPhotoRows = $statement->fetchAll(PDO::FETCH_ASSOC);
	$statement = $pdo->prepare("select photo, video from {$p}comment_video where id_parent in ($cph)");
	$statement->execute($commentIds);
	$commentVideoRows = $statement->fetchAll(PDO::FETCH_ASSOC);
}

$deletedFiles = 0;
$keptFiles = 0;

$pdo->beginTransaction();
try
{
	$pdo->prepare("delete from {$p}product where id in ($ph)")->execute($ids);
	$pdo->prepare("delete from {$p}product_project where id_product in ($ph)")->execute($ids);
	$pdo->prepare("delete from {$p}content_section where scope='product' and scope_id in ($ph)")->execute($ids);
	$pdo->prepare("delete from {$p}seo where com='product' and act='man' and id_parent in ($ph)")->execute($ids);
	$pdo->prepare("delete from {$p}product_tags where id_parent in ($ph)")->execute($ids);
	$pdo->prepare("delete from {$p}product_sale where id_parent in ($ph)")->execute($ids);
	$pdo->prepare("delete from {$p}gallery where kind='man' and com='product' and id_parent in ($ph)")->execute($ids);
	$pdo->prepare("delete from {$p}product_campaign_scope where scope_type='product' and scope_id in ($ph)")->execute($ids);
	if($commentIds)
	{
		$cph = implode(',', array_fill(0, count($commentIds), '?'));
		$pdo->prepare("delete from {$p}comment_photo where id_parent in ($cph)")->execute($commentIds);
		$pdo->prepare("delete from {$p}comment_video where id_parent in ($cph)")->execute($commentIds);
		$pdo->prepare("delete from {$p}comment where id_parent in ($cph)")->execute($commentIds);
		$pdo->prepare("delete from {$p}comment where id in ($cph)")->execute($commentIds);
	}
	$pdo->commit();
}
catch(Throwable $exception)
{
	$pdo->rollBack();
	echo "\nLỖI: ", $exception->getMessage(), "\nĐã rollback, dữ liệu giữ nguyên.\n";
	exit(1);
}

/* Xoá file sau khi DB đã commit: file mất mà DB rollback thì không cứu được. */
foreach($targets as $id => $row)
{
	if(fileStillUsed($pdo, $p, 'photo', 'product', $row['photo'], $ids)) { $keptFiles++; continue; }
	if(removeFile($uploadProduct, $row['photo'])) $deletedFiles++;
}
foreach($galleryRows as $row)
{
	if(removeFile($uploadProduct, $row['photo'])) $deletedFiles++;
	if(removeFile($uploadFile, $row['file_attach'])) $deletedFiles++;
}
foreach($commentPhotoRows as $row) if(removeFile($uploadPhoto, $row['photo'])) $deletedFiles++;
foreach($commentVideoRows as $row)
{
	if(removeFile($uploadPhoto, $row['photo'])) $deletedFiles++;
	if(removeFile($uploadVideo, $row['video'])) $deletedFiles++;
}

echo "\nĐÃ XOÁ ", count($targets), " sản phẩm.\n";
echo "File đã xoá khỏi đĩa: ", $deletedFiles, " | ảnh giữ lại vì còn sản phẩm khác dùng: ", $keptFiles, "\n";
echo "Nhớ xoá cache công khai để menu/sitemap cập nhật.\n";
