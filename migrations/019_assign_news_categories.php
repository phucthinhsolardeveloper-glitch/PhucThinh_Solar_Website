<?php
/**
 * Gán lại danh mục cho bài viết Tin tức bị mất id_list.
 *
 * Nguyên nhân gốc đã vá ở code: form sửa bài viết trong Admin không chọn sẵn
 * danh mục đang lưu nên mỗi lần Lưu ghi đè id_list về 0. Hậu quả: toàn bộ bài
 * viết rơi khỏi 4 trang danh mục tin tức được liên kết ngay trên header.
 *
 * Phân loại dựa trên tiêu đề + nội dung thực tế của từng bài:
 *   - Kiến thức sản phẩm : giải thích sản phẩm/công nghệ, lợi ích, lưu ý sử dụng
 *   - Tin truyền thông   : thông cáo của công ty, hợp tác, phân phối độc quyền
 *   - Tin mới nhất       : tin ngành/chính sách bên ngoài
 * Bài không khớp tiêu chí nào sẽ được liệt kê để cần phê duyệt thủ công.
 *
 * An toàn: chỉ ghi cho bài đang có id_list = 0, và chỉ khi danh mục đích tồn
 * tại. Không đụng bài đã được phân loại.
 *
 * BACKUP DB trước khi chạy --apply.
 * Rollback: update #_news set id_list = 0 where id in (<danh sách in ra>);
 *
 * Chạy (từ domains/phucthinhsolar.com/public_html):
 *   php ../../../migrations/019_assign_news_categories.php          # xem trước
 *   php ../../../migrations/019_assign_news_categories.php --apply  # ghi DB
 */


/* Chỉ cho chạy từ dòng lệnh (cron / terminal). Nếu thư mục migrations lỡ nằm
   trong web root thì người ngoài không gọi được qua trình duyệt. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
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
$type = 'tin-tuc';

/* Danh mục đích tra theo slug để không phụ thuộc ID cứng giữa các môi trường. */
$listBySlug = array();
$stmt = $pdo->prepare("select id, slugvi, namevi from {$p}news_list where type = ?");
$stmt->execute(array($type));
foreach($stmt as $row) $listBySlug[$row['slugvi']] = $row;

/* id bài viết => slug danh mục. Khoá theo id vì tiêu đề có ký tự đã HTML-encode. */
$assignment = array(
	100 => 'tin-truyen-thong',    // Phân phối độc quyền tấm pin Sharp
	101 => 'tin-truyen-thong',    // Blue Carbon thông báo nhà phân phối độc quyền
	102 => 'kien-thuc-san-pham',  // Đèn NLMT - những lý do nên chọn
	103 => 'tin-moi-nhat',        // Phó Thủ tướng: thúc đẩy điện mặt trời mái nhà
	104 => 'kien-thuc-san-pham',  // Top 4 lợi ích khi sử dụng đèn NLMT
	105 => 'kien-thuc-san-pham',  // 4 lưu ý khi lắp đặt và sử dụng
	106 => 'kien-thuc-san-pham',  // Blue Carbon đèn NLMT cho dự án
	107 => 'kien-thuc-san-pham',  // Đèn NLMT - những lý do bạn nên chọn
	108 => 'tin-truyen-thong',    // Được gì khi hợp tác cùng Phúc Thịnh Solar
	109 => 'tin-truyen-thong',    // Đối tác phân phối độc quyền Blue Carbon
	110 => 'kien-thuc-san-pham',  // Chip LED CREE thương hiệu top 1
	111 => 'kien-thuc-san-pham',  // 8 lý do nên sử dụng đèn NLMT
);

$rows = $pdo->prepare("select id, namevi, id_list from {$p}news where type = ? order by id");
$rows->execute(array($type));
$rows = $rows->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare("update {$p}news set id_list = ? where id = ? and type = ? and coalesce(id_list,0) = 0");
$assigned = 0; $skipped = 0; $manual = array();

foreach($rows as $row)
{
	$id = (int) $row['id'];
	$title = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $row['namevi']), ENT_QUOTES)));

	if((int) $row['id_list'] > 0) { $skipped++; continue; }
	if(!isset($assignment[$id]) || !isset($listBySlug[$assignment[$id]])) { $manual[] = array($id, $title); continue; }

	$target = $listBySlug[$assignment[$id]];
	printf("  #%-4d -> %-22s %s\n", $id, $target['namevi'], mb_substr($title, 0, 62));
	if($apply) $update->execute(array((int) $target['id'], $id, $type));
	$assigned++;
}

echo "\n---\n";
echo "Bài viết tin tức        : ".count($rows)."\n";
echo "  đã có danh mục, bỏ qua: {$skipped}\n";
echo "  gán danh mục          : {$assigned}\n";
echo "  cần quyết định thủ công: ".count($manual)."\n";
foreach($manual as $row) printf("     #%-4d %s\n", $row[0], mb_substr($row[1], 0, 70));

$after = $pdo->prepare("select l.namevi, count(n.id) n from {$p}news_list l left join {$p}news n on n.id_list = l.id and n.type = ? where l.type = ? group by l.id order by l.id");
$after->execute(array($type, $type));
echo "\nSố bài theo danh mục ".($apply ? "(sau khi ghi)" : "(hiện tại, chưa ghi)").":\n";
foreach($after as $row) printf("  %-24s %d\n", $row['namevi'], $row['n']);

echo $apply ? "\nĐÃ GHI DB.\n" : "\nDRY-RUN — chưa ghi. Chạy lại với --apply.\n";
