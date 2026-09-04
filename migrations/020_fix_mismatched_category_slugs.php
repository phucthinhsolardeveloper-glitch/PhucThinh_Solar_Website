<?php
/**
 * Sửa slug danh mục cấp 1 không còn khớp tên danh mục.
 *
 * Một số danh mục đã được đổi tên nhưng giữ nguyên slug cũ, nên URL công khai
 * mô tả một dòng sản phẩm hoàn toàn khác với nội dung trang:
 *   "Hệ thống khung nhôm" -> /danh-muc-san-pham/austdoor/
 *   "Pin lưu trữ"         -> /danh-muc-san-pham/bien-tan-huawei/
 * Menu và sitemap đang công bố đúng những URL sai nghĩa này.
 *
 * Script chỉ đổi slug khi slug hiện tại KHÔNG chứa từ khoá nào của tên danh mục
 * (tức là sai hẳn, không phải chỉ ngắn gọn hơn), và luôn ghi 301 từ đường dẫn cũ
 * sang đường dẫn mới vào #_url_redirect — đúng cơ chế Admin dùng khi đổi slug,
 * đã kiểm chứng router trả 301. Không có URL nào bị mất.
 *
 * Slug ngắn gọn nhưng vẫn đúng chủ đề (ví dụ "Hệ thống điện" -> day-dien) được
 * liệt kê để chủ project tự quyết, script không tự đổi.
 *
 * BACKUP DB trước khi chạy --apply.
 * Rollback: đặt lại slugvi/slugen theo cột "cũ" in ra và xoá dòng #_url_redirect
 * tương ứng.
 *
 * Chạy (từ domains/phucthinhsolar.com/public_html):
 *   php ../../../migrations/020_fix_mismatched_category_slugs.php          # xem trước
 *   php ../../../migrations/020_fix_mismatched_category_slugs.php --apply  # ghi DB
 */

define('LIBRARIES', './libraries/');
require __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/config.php';

$apply = in_array('--apply', $argv, true);

$pdo = new PDO(
	'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
	$config['database']['username'],
	$config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$p = $config['database']['prefix'];
$type = 'san-pham';

/* Bỏ dấu tiếng Việt để so khớp từ khoá giữa tên và slug. */
function slugify($text)
{
	$text = mb_strtolower(trim((string) $text), 'UTF-8');
	$map = array(
		'a'=>'áàảãạăắằẳẵặâấầẩẫậ', 'e'=>'éèẻẽẹêếềểễệ', 'i'=>'íìỉĩị',
		'o'=>'óòỏõọôốồổỗộơớờởỡợ', 'u'=>'úùủũụưứừửữự', 'y'=>'ýỳỷỹỵ', 'd'=>'đ',
	);
	foreach($map as $plain => $accented)
	{
		$chars = preg_split('//u', $accented, -1, PREG_SPLIT_NO_EMPTY);
		$text = str_replace($chars, $plain, $text);
	}
	$text = preg_replace('/[^a-z0-9]+/', '-', $text);
	return trim($text, '-');
}

$rows = $pdo->prepare("select id, namevi, slugvi, slugen from {$p}product_list where type = ? order by id");
$rows->execute(array($type));
$rows = $rows->fetchAll(PDO::FETCH_ASSOC);

$existingSlugs = array();
foreach($pdo->query("select slugvi from {$p}product_list") as $r) $existingSlugs[$r['slugvi']] = true;
foreach($pdo->query("select slugvi from {$p}product_cat") as $r) $existingSlugs[$r['slugvi']] = true;
foreach($pdo->query("select slugvi from {$p}product") as $r) $existingSlugs[$r['slugvi']] = true;

$updateSlug = $pdo->prepare("update {$p}product_list set slugvi = ?, slugen = ? where id = ? and type = ?");
$addRedirect = $pdo->prepare("insert into {$p}url_redirect (old_path, entity_table, entity_id, date_created) values (?,?,?,?) on duplicate key update entity_table=values(entity_table), entity_id=values(entity_id)");

$changed = 0; $partial = array(); $ok = 0;

foreach($rows as $row)
{
	$expected = slugify($row['namevi']);
	$current = trim((string) $row['slugvi']);
	if($expected === '' || $current === '') continue;

	/* Slug đúng chủ đề nếu có ít nhất một từ (>=3 ký tự) trùng với tên. */
	$nameWords = array_filter(explode('-', $expected), function($w){ return mb_strlen($w) >= 3; });
	$shared = false;
	foreach($nameWords as $word) if(strpos($current, $word) !== false) { $shared = true; break; }

	if($current === $expected) { $ok++; continue; }
	if($shared) { $partial[] = array($row['id'], $row['namevi'], $current, $expected); continue; }

	$newSlug = $expected;
	$suffix = 1;
	while(isset($existingSlugs[$newSlug]) && $newSlug !== $current) { $suffix++; $newSlug = $expected.'-'.$suffix; }
	$existingSlugs[$newSlug] = true;

	printf("  #%-3d %s\n", $row['id'], $row['namevi']);
	printf("       slug  %s  ->  %s\n", $current, $newSlug);
	printf("       301   danh-muc-san-pham/%s  ->  danh-muc-san-pham/%s/\n", $current, $newSlug);
	if($apply)
	{
		$addRedirect->execute(array('danh-muc-san-pham/'.$current, 'product_list', (int) $row['id'], time()));
		$updateSlug->execute(array($newSlug, $newSlug, (int) $row['id'], $type));
	}
	$changed++;
}

echo "\n---\n";
echo "Danh mục cấp 1        : ".count($rows)."\n";
echo "  slug đã khớp tên    : {$ok}\n";
echo "  slug sai hẳn, đã sửa: {$changed}\n";
echo "  slug ngắn gọn, giữ nguyên (chủ project tự quyết): ".count($partial)."\n";
foreach($partial as $row) printf("     #%-3d %-34s slug=%-38s gợi ý=%s\n", $row[0], $row[1], $row[2], $row[3]);
/* Menu/danh mục công khai đọc qua cache file 2 giờ; ghi thẳng DB không tự dọn
   nên phải xoá cache, nếu không menu vẫn trỏ slug cũ cho tới khi cache hết hạn. */
if($apply && $changed > 0)
{
	$cacheDir = __DIR__.'/../domains/phucthinhsolar.com/public_html/caches';
	$cleared = 0;
	if(is_dir($cacheDir))
	{
		foreach(glob($cacheDir.'/cache_*') as $cacheFile) if(@unlink($cacheFile)) $cleared++;
	}
	echo "Đã xoá {$cleared} file cache công khai.\n";
}

echo $apply ? "\nĐÃ GHI DB. Kiểm tra 301 và chạy lại sitemap trước khi mở traffic.\n" : "\nDRY-RUN — chưa ghi. Chạy lại với --apply.\n";
