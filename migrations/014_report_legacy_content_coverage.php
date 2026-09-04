<?php
/**
 * BÁO CÁO (chỉ đọc): sản phẩm nào còn hiển thị nội dung mô tả cũ (contentvi)
 * vì CHƯA có khối nội dung nào giải quyết được — theo thứ tự kế thừa
 * sản phẩm > hãng > danh mục.
 *
 * Dùng để biết cần dựng "Khối nội dung mô tả" cho hãng / danh mục nào trước.
 * KHÔNG ghi gì vào DB.
 *
 * Cách chạy (từ domains/phucthinhsolar.com/public_html):
 *   php ../../../migrations/014_report_legacy_content_coverage.php
 */

define('LIBRARIES', './libraries/');
require __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/config.php';

$pdo = new PDO(
	'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
	$config['database']['username'],
	$config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$p = $config['database']['prefix'];

/* Khối nội dung đang có, theo scope */
$has = array('product' => array(), 'brand' => array(), 'cat' => array(), 'list' => array());
foreach($pdo->query("select scope, scope_id, blocksvi from {$p}content_section") as $r)
{
	$decoded = json_decode(html_entity_decode((string) $r['blocksvi'], ENT_QUOTES), true);
	if(is_array($decoded) && count($decoded) > 0) $has[$r['scope']][(int) $r['scope_id']] = true;
}

$rows = $pdo->query("
	select pr.id, pr.namevi, pr.id_brand, pr.id_cat, pr.id_list,
	       coalesce(b.namevi,'(không hãng)') as brand_name,
	       coalesce(l.namevi,'(không danh mục)') as list_name,
	       char_length(coalesce(pr.contentvi,'')) as clen
	from {$p}product pr
	left join {$p}product_brand b on b.id = pr.id_brand
	left join {$p}product_list  l on l.id = pr.id_list
	where pr.type = 'san-pham' and find_in_set('hienthi', pr.status)
	order by pr.id
")->fetchAll(PDO::FETCH_ASSOC);

$totLegacy = 0; $covered = 0; $stillLegacy = 0; $noContent = 0;
$byBrand = array(); $byList = array();

foreach($rows as $r)
{
	if((int) $r['clen'] === 0) { $noContent++; continue; }
	$totLegacy++;

	$resolved = isset($has['product'][(int) $r['id']])
		|| (!empty($r['id_brand']) && isset($has['brand'][(int) $r['id_brand']]))
		|| (!empty($r['id_cat'])   && isset($has['cat'][(int) $r['id_cat']]))
		|| (!empty($r['id_list'])  && isset($has['list'][(int) $r['id_list']]));

	if($resolved) { $covered++; continue; }

	$stillLegacy++;
	$byBrand[$r['brand_name']] = (isset($byBrand[$r['brand_name']]) ? $byBrand[$r['brand_name']] : 0) + 1;
	$byList[$r['list_name']]   = (isset($byList[$r['list_name']]) ? $byList[$r['list_name']] : 0) + 1;
}

arsort($byBrand); arsort($byList);

echo "=== ĐỘ PHỦ KHỐI NỘI DUNG MÔ TẢ ===\n\n";
echo "Tổng sản phẩm hiển thị           : ".count($rows)."\n";
echo "  - không có contentvi           : {$noContent}\n";
echo "  - có contentvi                 : {$totLegacy}\n";
echo "      đã có khối (ẩn contentvi)  : {$covered}\n";
echo "      CHƯA có khối (còn hiện cũ) : {$stillLegacy}\n\n";

echo "--- Còn hiện contentvi, gom theo HÃNG (dựng khối cấp hãng sẽ phủ nhiều nhất) ---\n";
foreach($byBrand as $name => $n) printf("  %-34s %4d sản phẩm\n", $name, $n);

echo "\n--- Gom theo DANH MỤC ---\n";
foreach($byList as $name => $n) printf("  %-34s %4d sản phẩm\n", $name, $n);

echo "\n(Chỉ đọc — không ghi DB.)\n";
