<?php
/**
 * Rà thêm nội dung tin tức/dự án cũ theo yêu cầu chủ project, sửa lỗi chính
 * tả và claim lỗi thời còn sót (đợt trước mới sửa 1 bài mẫu #111).
 *
 * table_news id=100 (bài Sharp): "Gp solar" là tên công ty SAI hoàn toàn -
 * rõ ràng copy nguyên văn từ website khác, chưa từng được sửa lại thành
 * "Phúc Thịnh Solar". Câu mở đầu "công ty ... đầu tiên trên thế giới" cũng
 * tự mâu thuẫn với chính thân bài phía dưới (chỉ nói Sharp là "một trong
 * những công ty đầu tiên") - sửa cho khớp.
 *
 * table_news id=108: lặp lại cùng claim lỗi thời "chương trình mục tiêu
 * Quốc gia... (giai đoạn 2021-2025)" đã sửa ở static dai-ly-1
 * (migration 054) - chương trình đã hết giai đoạn.
 *
 * table_news id=110: lỗi gõ "chIp" (chữ I hoa giữa từ) và thiếu chuẩn dấu
 * câu "Bạn Nghĩ sao về điều này ?" (hoa nhầm chữ N, có khoảng trắng thừa
 * trước dấu ?).
 *
 * table_news id=73, 74 (dự án Thu Hiền/Thu Hồng): "khuông viên nhà" ->
 * "khuôn viên nhà" (lỗi chính tả), "Phúc thịnh solar" -> "Phúc Thịnh Solar"
 * (viết hoa không nhất quán với tên thương hiệu dùng ở mọi nơi khác).
 *
 * Mặc định chỉ preview:
 *   php migrations/055_fix_news_typos_and_stale_claims.php
 *   php migrations/055_fix_news_typos_and_stale_claims.php --apply
 *   php migrations/055_fix_news_typos_and_stale_claims.php --rollback
 */

if(PHP_SAPI !== 'cli') exit(1);
if(!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$apply = in_array('--apply', $argv, true);
$rollback = in_array('--rollback', $argv, true);
if($apply && $rollback)
{
    fwrite(STDERR, "Chỉ được chọn một trong --apply hoặc --rollback.\n");
    exit(1);
}

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if(!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
if(!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}

define('LIBRARIES', $publicHtml.'/libraries/');
require $publicHtml.'/libraries/config.php';
$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';
if(!preg_match('/^[a-zA-Z0-9_]*$/', $prefix))
{
    fwrite(STDERR, "Table prefix không hợp lệ.\n");
    exit(1);
}

/* id => [[cũ, mới], ...] */
$plan = array(
    100 => array(
        array(
            'Gp solar là công ty năng lượng hàng đầu và tiên phong trong việc đưa nguồn năng lượng tái tạo vào nước ta. Chúng tôi luôn làm việc với cái tâm, cái tầm muốn mang tất cả những sản phẩm tốt nhất đến khách hàng của mình.',
            'Phúc Thịnh Solar luôn làm việc với cái tâm, cái tầm, muốn mang những sản phẩm chính hãng tốt nhất đến khách hàng của mình.'
        ),
        array(
            'Sharp là công ty nghiên cứu và sản xuất pin năng lượng mặt trời đầu tiên trên thế giới với lịch sử 59 năm tồn tại và phát triển.',
            'Sharp là một trong những công ty nghiên cứu và sản xuất pin năng lượng mặt trời đầu tiên trên thế giới, với lịch sử hơn 60 năm tồn tại và phát triển.'
        )
    ),
    108 => array(
        array(
            'chương trình mục tiêu Quốc gia xây dựng nông thôn mới (giai đoạn 2021 – 2025) và phát triển hạ tầng kinh tế',
            'chương trình phát triển hạ tầng kinh tế'
        )
    ),
    110 => array(
        array('Bạn Nghĩ sao về điều này ?', 'Bạn nghĩ sao về điều này?'),
        array('chIp', 'chip')
    ),
    73 => array(
        array('khuông viên nhà', 'khuôn viên nhà'),
        array('do Phúc thịnh solar nhập khẩu', 'do Phúc Thịnh Solar nhập khẩu')
    ),
    74 => array(
        array('khuông viên nhà', 'khuôn viên nhà'),
        array('do Phúc thịnh solar nhập khẩu', 'do Phúc Thịnh Solar nhập khẩu')
    )
);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';port='.(int)$config['database']['port'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password'],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$select = $pdo->prepare("select contentvi from `{$prefix}news` where id = ? limit 1");
$update = $pdo->prepare("update `{$prefix}news` set contentvi = ? where id = ? and contentvi = ?");

$totalApplied = 0;
foreach($plan as $id => $replacements)
{
    $select->execute(array($id));
    $row = $select->fetch(PDO::FETCH_ASSOC);
    if(!$row)
    {
        fwrite(STDERR, "SKIP id=$id: không tìm thấy.\n");
        continue;
    }
    $original = (string) $row['contentvi'];
    $content = $original;
    $idApplied = 0;
    foreach($replacements as $pair)
    {
        list($from, $to) = $rollback ? array($pair[1], $pair[0]) : $pair;
        $content = str_replace($from, $to, $content, $count);
        $idApplied += $count;
    }
    if($content === $original)
    {
        echo "SKIP id=$id: không có cụm nào khớp (có thể đã áp dụng rồi).\n";
        continue;
    }
    if(!$apply && !$rollback)
    {
        echo "PREVIEW id=$id — $idApplied cụm sẽ được thay.\n";
        foreach($replacements as $pair)
        {
            list($from, $to) = $rollback ? array($pair[1], $pair[0]) : $pair;
            if(strpos($original, $from) !== false) echo "  \"".mb_substr($from,0,60,'UTF-8')."...\"\n      -> \"".mb_substr($to,0,60,'UTF-8')."...\"\n";
        }
        continue;
    }
    $update->execute(array($content, $id, $original));
    if($update->rowCount() !== 1)
    {
        fwrite(STDERR, "SKIP id=$id: bản ghi đã thay đổi trong lúc chạy.\n");
        continue;
    }
    $totalApplied += $idApplied;
    echo ($rollback ? 'ROLLBACK' : 'APPLY')." id=$id xong: $idApplied cụm.\n";
}

if(!$apply && !$rollback) echo "Chạy lại với --apply để áp dụng.\n";
else echo "Hoàn tất: tổng $totalApplied cụm đã sửa.\n";
