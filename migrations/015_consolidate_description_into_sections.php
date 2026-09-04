<?php
/**
 * Gom mô tả về MỘT loại: khối content_section.
 *
 * Với mỗi sản phẩm CHƯA có khối nào giải quyết (không có bản riêng, không
 * kế thừa được hãng/danh mục): dựng khối từ dữ liệu cũ theo thứ tự
 *   highlights -> applications -> installation -> contentvi
 * rồi lưu thành content_section scope=product.
 *
 * KHÔNG xoá cột nào (contentvi / highlightsvi / ... giữ làm bản gốc).
 * Sau migration frontend không còn render các cột cũ.
 *
 * BACKUP DB (backup/local/...) trước khi chạy --apply. Chỉ chèn thêm dòng
 * table_content_section, có thể rollback bằng:
 *   delete from table_content_section where scope='product';
 *
 * Chạy (từ domains/phucthinhsolar.com/public_html):
 *   php ../../../migrations/015_consolidate_description_into_sections.php          # xem trước
 *   php ../../../migrations/015_consolidate_description_into_sections.php --apply  # ghi DB
 */

define('LIBRARIES', './libraries/');
$root = __DIR__.'/../domains/phucthinhsolar.com/public_html/';
require $root.'libraries/config.php';
require_once $root.'libraries/product_content_blocks.php';

$apply = in_array('--apply', $argv, true);

$pdo = new PDO(
	'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
	$config['database']['username'],
	$config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$p = $config['database']['prefix'];

/* Khối đang có, theo scope */
$has = array('product' => array(), 'brand' => array(), 'cat' => array(), 'list' => array());
foreach($pdo->query("select scope, scope_id, blocksvi from {$p}content_section") as $r)
{
	$decoded = json_decode(html_entity_decode((string) $r['blocksvi'], ENT_QUOTES), true);
	if(is_array($decoded) && count($decoded) > 0) $has[$r['scope']][(int) $r['scope_id']] = true;
}

$rows = $pdo->query("
	select id, id_brand, id_cat, id_list, highlightsvi, applicationsvi, installationvi, contentvi
	from {$p}product
	where type='san-pham' and (
		coalesce(highlightsvi,'')<>'' or coalesce(applicationsvi,'')<>'' or
		coalesce(installationvi,'')<>'' or coalesce(contentvi,'')<>''
	)
	order by id
")->fetchAll(PDO::FETCH_ASSOC);

$ins = $pdo->prepare("insert into {$p}content_section (scope, scope_id, blocksvi, status, date_updated) values ('product', ?, ?, 'hienthi', ?)");

$converted = 0; $skipInherit = 0; $skipExists = 0; $skipEmpty = 0; $imgWarn = 0;

function pairedListBlock($heading, $rawJson, $ordered)
{
	$items = array();
	foreach(productContentBlockDecode($rawJson) as $it)
	{
		if(is_array($it)) { $t = trim((string)(isset($it['title']) ? $it['title'] : '')); $x = trim((string)(isset($it['text']) ? $it['text'] : '')); if($t !== '') $items[] = $x !== '' ? $t.' — '.$x : $t; }
		elseif(is_scalar($it)) { $s = trim((string)$it); if($s !== '') $items[] = $s; }
	}
	if(!$items) return array();
	return array(array('type' => 'heading', 'text' => $heading), array('type' => 'list', 'ordered' => $ordered, 'items' => $items));
}

foreach($rows as $r)
{
	$pid = (int) $r['id'];
	if(isset($has['product'][$pid])) { $skipExists++; continue; }
	if((!empty($r['id_brand']) && isset($has['brand'][(int) $r['id_brand']]))
	 || (!empty($r['id_cat'])   && isset($has['cat'][(int) $r['id_cat']]))
	 || (!empty($r['id_list'])  && isset($has['list'][(int) $r['id_list']])))
	{ $skipInherit++; continue; }

	$blocks = array();
	if(trim((string) $r['highlightsvi'])   !== '') $blocks = array_merge($blocks, pairedListBlock('Điểm nổi bật', $r['highlightsvi'], false));
	if(trim((string) $r['applicationsvi']) !== '') $blocks = array_merge($blocks, pairedListBlock('Ứng dụng phù hợp', $r['applicationsvi'], false));
	if(trim((string) $r['installationvi']) !== '') $blocks = array_merge($blocks, pairedListBlock('Bộ sản phẩm / hướng dẫn lắp đặt', $r['installationvi'], true));

	if(trim((string) $r['contentvi']) !== '')
	{
		$parsed = productLegacyHtmlToBlocks(html_entity_decode((string) $r['contentvi'], ENT_QUOTES));
		$blocks = array_merge($blocks, $parsed['blocks']);
		$imgWarn += $parsed['skipped_images'];
	}

	if(!$blocks) { $skipEmpty++; continue; }

	$json = htmlspecialchars(json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES);
	echo sprintf("  #%-5d -> %2d khối\n", $pid, count($blocks));
	if($apply) $ins->execute(array($pid, $json, time()));
	$converted++;
}

echo "\n---\n";
echo "Có dữ liệu mô tả cũ : ".count($rows)." sản phẩm\n";
echo "  chuyển thành khối : {$converted}\n";
echo "  bỏ (đã có bản riêng): {$skipExists}\n";
echo "  bỏ (kế thừa hãng/dm): {$skipInherit}\n";
echo "  bỏ (không tách được): {$skipEmpty}\n";
echo "  ảnh cần tải lại thủ công: {$imgWarn}\n";
echo $apply ? "\nĐÃ GHI DB.\n" : "\nDRY-RUN — chưa ghi. Chạy lại với --apply.\n";
