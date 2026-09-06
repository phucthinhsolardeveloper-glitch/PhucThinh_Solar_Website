<?php
/**
 * Gom ảnh WordPress cũ còn được nội dung dùng về thư mục ảnh của trình soạn thảo.
 *
 * public_html/wp-content/uploads chiếm 1,2 GB với 18.786 file, nhưng chỉ một
 * nhúm nhỏ còn được nhúng trong nội dung sản phẩm/tin tức. Sau khi chạy script
 * này, toàn bộ thư mục wp-content có thể chuyển ra khỏi web root mà không mất
 * ảnh nào đang hiển thị.
 *
 * Quét toàn bộ 402 cột kiểu text trong DB cho thấy chỉ đúng hai cột tham chiếu
 * wp-content: table_product.contentvi và table_news.contentvi. Script chỉ đụng
 * hai cột đó.
 *
 * Ảnh được chép sang upload/filemanager/wp-legacy/ và giữ nguyên cấu trúc thư
 * mục theo năm/tháng để không đụng tên nhau. Chọn upload/filemanager vì đó là
 * kho ảnh của trình soạn thảo — sau này sửa bài vẫn duyệt lại được ảnh cũ.
 *
 * Nội dung được thay theo tiền tố, nên mọi dạng đường dẫn đều đúng:
 *   wp-content/uploads/a.jpg                 -> upload/filemanager/wp-legacy/a.jpg
 *   /wp-content/uploads/a.jpg                -> /upload/filemanager/wp-legacy/a.jpg
 *   https://ten-mien/wp-content/uploads/a.jpg-> https://ten-mien/upload/filemanager/wp-legacy/a.jpg
 *
 * CHÉP chứ không di chuyển: file gốc còn nguyên cho tới khi bạn tự tay chuyển
 * cả thư mục wp-content đi. Rollback: đổi ngược tiền tố trong hai cột trên.
 *
 * BACKUP DB trước khi chạy --apply.
 *
 * Chạy:
 *   php migrations/024_move_legacy_wp_images.php          # xem trước
 *   php migrations/024_move_legacy_wp_images.php --apply  # chép ảnh + sửa nội dung
 */


/* Chỉ cho chạy từ dòng lệnh (cron / terminal). Nếu thư mục migrations lỡ nằm
   trong web root thì người ngoài không gọi được qua trình duyệt. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('LIBRARIES', './libraries/');
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';
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

$oldPrefix = 'wp-content/uploads/';
$newPrefix = 'upload/filemanager/wp-legacy/';

/* Chỉ hai cột này tham chiếu wp-content trong toàn DB. */
$columns = array(
	array('table' => $p.'product', 'column' => 'contentvi', 'label' => 'product'),
	array('table' => $p.'news',    'column' => 'contentvi', 'label' => 'news')
);

/* Bắt mọi đường dẫn dưới tiền tố, kể cả đuôi lạ, rồi cắt rác đuôi câu. */
function extractPaths($html, $oldPrefix)
{
	$text = html_entity_decode((string)$html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$found = array();
	if(preg_match_all('#'.preg_quote($oldPrefix, '#').'[^\s"\'<>()\\\\]+#i', $text, $matches))
		foreach($matches[0] as $path)
		{
			$path = rtrim(urldecode($path), ".,;:!?");
			$found[$path] = true;
		}
	return array_keys($found);
}

$rows = array();
$paths = array();
foreach($columns as $target)
{
	$statement = $pdo->prepare("select id, `{$target['column']}` as body from `{$target['table']}` where `{$target['column']}` like ?");
	$statement->execute(array('%'.$oldPrefix.'%'));
	foreach($statement->fetchAll(PDO::FETCH_ASSOC) as $row)
	{
		$rows[] = array('target' => $target, 'id' => (int)$row['id'], 'body' => $row['body']);
		foreach(extractPaths($row['body'], $oldPrefix) as $path)
			$paths[$path][] = $target['label'].'#'.$row['id'];
	}
}

ksort($paths);
$copyable = array();
$missing = array();
$bytes = 0;
foreach($paths as $path => $refs)
{
	$source = $publicHtml.'/'.$path;
	if(is_file($source))
	{
		$copyable[$path] = filesize($source);
		$bytes += filesize($source);
	}
	else $missing[$path] = array_values(array_unique($refs));
}

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";
echo "Bản ghi nội dung phải sửa : ", count($rows), "\n";
echo "Ảnh cần chép sang chỗ mới : ", count($copyable), " (", round($bytes / 1024 / 1024, 2), " MB)\n";
echo "Ảnh nội dung trỏ tới nhưng KHÔNG có file: ", count($missing), "\n\n";

foreach($rows as $row)
	printf("  sửa %-9s #%-5s\n", $row['target']['label'], $row['id']);

if($missing)
{
	echo "\nCẢNH BÁO - nội dung trỏ tới file không tồn tại (ảnh này vốn đã hỏng sẵn):\n";
	foreach($missing as $path => $refs) echo "  ", $path, "  <- ", implode(',', $refs), "\n";
}

if(!$apply)
{
	echo "\nChưa ghi gì. Chạy lại kèm --apply để chép ảnh và sửa nội dung.\n";
	exit(0);
}

/* Chép ảnh trước: nội dung trỏ sang chỗ mới thì file phải có sẵn ở đó. */
$copied = 0;
$skipped = 0;
foreach($copyable as $path => $size)
{
	$source = $publicHtml.'/'.$path;
	$relative = substr($path, strlen($oldPrefix));
	$destination = $publicHtml.'/'.$newPrefix.$relative;
	$directory = dirname($destination);
	if(!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory))
	{
		fwrite(STDERR, "Không tạo được thư mục: ".$directory."\n");
		exit(1);
	}
	if(is_file($destination) && filesize($destination) === $size) { $skipped++; continue; }
	if(!copy($source, $destination))
	{
		fwrite(STDERR, "Không chép được: ".$path."\n");
		exit(1);
	}
	$copied++;
}
echo "\nĐã chép ", $copied, " ảnh (", $skipped, " ảnh đã có sẵn ở đích).\n";

/* Đổi tiền tố trong nội dung, trong một transaction. */
$updated = 0;
$pdo->beginTransaction();
try
{
	foreach($rows as $row)
	{
		$body = str_replace($oldPrefix, $newPrefix, $row['body']);
		if($body === $row['body']) continue;
		$statement = $pdo->prepare("update `{$row['target']['table']}` set `{$row['target']['column']}` = ? where id = ?");
		$statement->execute(array($body, $row['id']));
		$updated++;
	}
	$pdo->commit();
}
catch(Throwable $exception)
{
	$pdo->rollBack();
	echo "LỖI: ", $exception->getMessage(), "\nĐã rollback nội dung. Ảnh đã chép vẫn còn, chạy lại được.\n";
	exit(1);
}
echo "Đã sửa ", $updated, " bản ghi nội dung.\n";

/* Kiểm chứng: mọi đường dẫn mới trong nội dung phải trỏ tới file có thật. */
$broken = array();
foreach($columns as $target)
{
	$statement = $pdo->prepare("select id, `{$target['column']}` as body from `{$target['table']}` where `{$target['column']}` like ?");
	$statement->execute(array('%'.$newPrefix.'%'));
	foreach($statement->fetchAll(PDO::FETCH_ASSOC) as $row)
		foreach(extractPaths($row['body'], $newPrefix) as $path)
			if(!is_file($publicHtml.'/'.$path)) $broken[] = $target['label'].'#'.$row['id'].' -> '.$path;
}
$left = $pdo->query("select (select count(*) from `{$p}product` where contentvi like '%{$oldPrefix}%') + (select count(*) from `{$p}news` where contentvi like '%{$oldPrefix}%')")->fetchColumn();

echo "\nKIỂM CHỨNG\n";
echo "  Bản ghi còn trỏ về wp-content : ", (int)$left, " (mong đợi 0)\n";
echo "  Đường dẫn mới bị hỏng         : ", count($broken), " (mong đợi 0)\n";
foreach($broken as $item) echo "    ", $item, "\n";
echo "\nXong. Giờ có thể chuyển cả thư mục wp-content ra khỏi web root.\n";
echo "Nhớ xoá cache công khai.\n";
