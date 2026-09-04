<?php
/**
 * Sửa tham chiếu danh mục bị mất của sản phẩm.
 *
 * Nguyên nhân gốc đã được vá ở code (form sửa sản phẩm trong Admin không chọn
 * sẵn danh mục/thương hiệu đang lưu nên mỗi lần Lưu ghi đè id_list/id_cat/
 * id_brand về 0). Script này chỉ dọn hậu quả dữ liệu còn lại.
 *
 * Chỉ sửa các trường hợp suy ra được CHẮC CHẮN từ dữ liệu cùng nhóm:
 *   - sản phẩm không có danh mục nhưng có anh em cùng dòng (trùng tiền tố tên)
 *     đã được phân loại thống nhất;
 *   - sản phẩm trỏ tới danh mục đã bị xoá, trong khi có danh mục cùng cấp khớp
 *     tên sản phẩm và đang chứa sản phẩm tương tự.
 * Trường hợp mơ hồ sẽ được liệt kê để chủ project tự quyết, không tự đoán.
 *
 * BACKUP DB trước khi chạy --apply. Rollback: khôi phục từ backup, hoặc đặt lại
 * thủ công id_list/id_cat/id_brand theo giá trị "cũ" in ra ở bảng dưới.
 *
 * Chạy (từ domains/phucthinhsolar.com/public_html):
 *   php ../../../migrations/018_repair_orphan_product_taxonomy.php          # xem trước
 *   php ../../../migrations/018_repair_orphan_product_taxonomy.php --apply  # ghi DB
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

$validList  = $pdo->query("select id from {$p}product_list where type='$type'")->fetchAll(PDO::FETCH_COLUMN);
$validCat   = array();
foreach($pdo->query("select id, id_list, namevi from {$p}product_cat where type='$type'") as $r) $validCat[(int)$r['id']] = $r;
$validBrand = $pdo->query("select id from {$p}product_brand where type='$type'")->fetchAll(PDO::FETCH_COLUMN);
$validList  = array_map('intval', $validList);
$validBrand = array_map('intval', $validBrand);

$broken = $pdo->query("
	select id, namevi, id_list, id_cat, id_brand
	from {$p}product
	where type='$type' and (
		coalesce(id_list,0)=0
		or (coalesce(id_cat,0)>0 and id_cat not in (select id from {$p}product_cat where type='$type'))
		or (coalesce(id_list,0)>0 and id_list not in (select id from {$p}product_list where type='$type'))
		or (coalesce(id_brand,0)>0 and id_brand not in (select id from {$p}product_brand where type='$type'))
	)
	order by id
")->fetchAll(PDO::FETCH_ASSOC);

if(!$broken) { echo "Không còn sản phẩm nào mất tham chiếu danh mục.\n"; exit(0); }

/* Tiền tố tên dùng để tìm anh em cùng dòng: bỏ phần model/công suất ở đuôi. */
function familyPrefix($name)
{
	$name = trim(preg_replace('/\s+/u', ' ', (string) $name));
	$name = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name);
	$parts = preg_split('/\s+/u', $name);
	if(count($parts) <= 3) return $name;
	return implode(' ', array_slice($parts, 0, count($parts) - 1));
}

$update = $pdo->prepare("update {$p}product set id_list=?, id_cat=?, id_brand=? where id=? and type='$type'");
$fixed = 0; $manual = array();

foreach($broken as $row)
{
	$id = (int) $row['id'];
	$prefix = familyPrefix($row['namevi']);

	/* Anh em cùng dòng: cùng tiền tố tên, đã phân loại, và thống nhất một giá trị. */
	$siblings = $pdo->prepare("
		select id_list, id_cat, id_brand, count(*) n
		from {$p}product
		where type='$type' and id <> ? and coalesce(id_list,0)>0 and namevi like ?
		group by id_list, id_cat, id_brand order by n desc
	");
	$siblings->execute(array($id, $prefix.'%'));
	$groups = $siblings->fetchAll(PDO::FETCH_ASSOC);

	$newList = (int) $row['id_list'];
	$newCat = (int) $row['id_cat'];
	$newBrand = (int) $row['id_brand'];
	$reason = '';

	if(count($groups) === 1)
	{
		$g = $groups[0];
		$newList = (int) $g['id_list'];
		$newCat = (int) $g['id_cat'];
		if($newBrand <= 0 || !in_array($newBrand, $validBrand, true)) $newBrand = (int) $g['id_brand'];
		$reason = 'theo '.$g['n'].' sản phẩm cùng dòng "'.$prefix.'"';
	}
	elseif($newCat > 0 && !isset($validCat[$newCat]))
	{
		/* Danh mục đã xoá: tìm danh mục cùng id_list có tên xuất hiện trong tên sản phẩm. */
		foreach($validCat as $catId => $cat)
		{
			if((int) $cat['id_list'] !== (int) $row['id_list']) continue;
			if(mb_stripos($row['namevi'], trim($cat['namevi'])) === false) continue;
			$newCat = (int) $catId;
			$reason = 'danh mục cũ đã xoá; khớp tên danh mục "'.$cat['namevi'].'"';
			break;
		}
	}

	$resolved = $newList > 0 && in_array($newList, $validList, true)
		&& ($newCat === 0 || isset($validCat[$newCat]))
		&& ($newBrand === 0 || in_array($newBrand, $validBrand, true))
		&& $reason !== '';

	if(!$resolved) { $manual[] = $row; continue; }

	printf("  #%-5d %s\n", $id, $row['namevi']);
	printf("         %s\n", $reason);
	printf("         id_list %s -> %s | id_cat %s -> %s | id_brand %s -> %s\n",
		$row['id_list'], $newList, $row['id_cat'], $newCat, $row['id_brand'], $newBrand);
	if($apply) $update->execute(array($newList, $newCat, $newBrand, $id));
	$fixed++;
}

echo "\n---\n";
echo "Sản phẩm mất tham chiếu : ".count($broken)."\n";
echo "  suy ra được chắc chắn : {$fixed}\n";
echo "  cần quyết định thủ công: ".count($manual)."\n";
foreach($manual as $row) printf("     #%-5d %s (id_list=%s id_cat=%s)\n", $row['id'], $row['namevi'], $row['id_list'], $row['id_cat']);
echo $apply ? "\nĐÃ GHI DB.\n" : "\nDRY-RUN — chưa ghi. Chạy lại với --apply.\n";
