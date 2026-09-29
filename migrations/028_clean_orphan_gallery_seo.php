<?php
/**
 * Dọn bản ghi gallery và SEO mồ côi — trỏ tới sản phẩm/bài viết đã xoá từ lâu.
 *
 * Trên local có 570 dòng như vậy, đều tham chiếu id nhỏ hơn id nhỏ nhất còn tồn
 * tại, tức là tàn dư từ những lần xoá cũ không dọn kèm (luồng xoá hiện tại của
 * Admin đã dọn đúng — xem migrations/023 và deleteMan()). Không dòng nào còn
 * file ảnh trên đĩa nên đây thuần là rác trong DB, không ảnh hưởng hiển thị,
 * nhưng làm nhiễu mọi truy vấn đếm và mọi lần rà soát toàn vẹn dữ liệu.
 *
 * Xử lý đúng bốn nhóm, mỗi nhóm đối chiếu với bảng cha tương ứng:
 *   gallery com=product kind=man  -> table_product
 *   gallery com=news    kind=man  -> table_news
 *   seo     com=product act=man   -> table_product
 *   seo     com=news    act=man   -> table_news
 *
 * KHÔNG đụng các act khác của SEO (man_list, man_brand, man_cat, update...) vì
 * chúng trỏ tới bảng danh mục/thương hiệu/trang tĩnh khác, cần đối chiếu riêng.
 *
 * BACKUP DB trước khi chạy --apply.
 *
 * QUY ƯỚC CỦA DỰ ÁN: không xoá trắng. Trước khi chạy --apply hãy tạo backup
 * (scripts/backup-local.sh), và sau khi xoá thì trích đúng những dòng vừa mất
 * từ bản backup đó ra file .sql đặt trong thư mục chủ tài khoản dùng để kiểm tra:
 *
 *   D:\FIleLinhTinh\phucthinhsolar-donkho-20260905\du-lieu-da-xoa-khoi-database\
 *
 * Cách làm: nạp backup vào một DB tạm, chạy đúng các truy vấn tìm mồ côi bên
 * dưới, sinh câu INSERT cho từng dòng, rồi xoá DB tạm. Lần chạy 2026-09-06 đã
 * xuất 570 dòng theo cách này — xem file 570-dong-mo-coi-gallery-seo-20260906.sql.
 *
 * Rollback: chạy lại file .sql đó, hoặc khôi phục nguyên bản backup.
 *
 * Chạy:
 *   php migrations/028_clean_orphan_gallery_seo.php          # xem trước
 *   php migrations/028_clean_orphan_gallery_seo.php --apply  # xoá thật
 */

if (PHP_SAPI !== 'cli') exit(1);
if (!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', './libraries/');

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if (!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
if (!is_file($publicHtml.'/libraries/config.php'))
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

$groups = array(
    array(
        'label'  => 'gallery ảnh sản phẩm',
        'table'  => $p.'gallery',
        'parent' => $p.'product',
        'where'  => "g.com = 'product' and g.kind = 'man'",
        'fk'     => 'id_parent'
    ),
    array(
        'label'  => 'gallery ảnh bài viết',
        'table'  => $p.'gallery',
        'parent' => $p.'news',
        'where'  => "g.com = 'news' and g.kind = 'man'",
        'fk'     => 'id_parent'
    ),
    array(
        'label'  => 'SEO của sản phẩm',
        'table'  => $p.'seo',
        'parent' => $p.'product',
        'where'  => "g.com = 'product' and g.act = 'man'",
        'fk'     => 'id_parent'
    ),
    array(
        'label'  => 'SEO của bài viết',
        'table'  => $p.'seo',
        'parent' => $p.'news',
        'where'  => "g.com = 'news' and g.act = 'man'",
        'fk'     => 'id_parent'
    )
);

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để xoá thật)\n\n";

$totalOrphans = 0;
$plan = array();
foreach ($groups as $group)
{
    $sql = "select g.id, g.{$group['fk']} as parent_id
            from `{$group['table']}` g
            left join `{$group['parent']}` t on t.id = g.{$group['fk']}
            where {$group['where']} and t.id is null";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $ids = array();
    $parents = array();
    foreach ($rows as $row) { $ids[] = (int)$row['id']; $parents[(int)$row['parent_id']] = true; }

    printf("  %-24s %4d dòng mồ côi", $group['label'], count($ids));
    if ($ids)
    {
        $parentIds = array_keys($parents);
        sort($parentIds);
        printf("  (trỏ tới %d id cha đã mất, từ %d đến %d)", count($parentIds), $parentIds[0], end($parentIds));
    }
    echo "\n";

    $totalOrphans += count($ids);
    if ($ids) $plan[] = array('group' => $group, 'ids' => $ids);
}

/* Đối chiếu an toàn: id cha đã mất phải nhỏ hơn id nhỏ nhất còn tồn tại, tức là
   dữ liệu cũ chứ không phải bản ghi vừa tạo mà transaction chưa commit xong. */
echo "\n";
foreach (array($p.'product' => 'sản phẩm', $p.'news' => 'bài viết') as $table => $label)
{
    $min = $pdo->query("select min(id) from `$table`")->fetchColumn();
    printf("  id %s nhỏ nhất còn tồn tại: %s\n", $label, $min === null ? '(bảng rỗng)' : $min);
}

if (!$totalOrphans)
{
    echo "\nKhông có bản ghi mồ côi nào.\n";
    exit(0);
}

if (!$apply)
{
    echo "\nTổng: ", $totalOrphans, " dòng sẽ bị xoá. Chưa ghi gì.\n";
    echo "Chạy lại kèm --apply để xoá thật.\n";
    exit(0);
}

$deleted = 0;
$pdo->beginTransaction();
try
{
    foreach ($plan as $item)
    {
        /* Xoá theo lô để câu lệnh không dài vô hạn khi dữ liệu lớn. */
        foreach (array_chunk($item['ids'], 500) as $chunk)
        {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $statement = $pdo->prepare("delete from `{$item['group']['table']}` where id in ($placeholders)");
            $statement->execute($chunk);
            $deleted += $statement->rowCount();
        }
    }
    $pdo->commit();
}
catch (Throwable $exception)
{
    $pdo->rollBack();
    echo "LỖI: ", $exception->getMessage(), "\nĐã rollback, dữ liệu giữ nguyên.\n";
    exit(1);
}

echo "\nĐã xoá ", $deleted, " dòng.\n";

/* Kiểm chứng lại từ đầu. */
$left = 0;
foreach ($groups as $group)
{
    $sql = "select count(*) from `{$group['table']}` g
            left join `{$group['parent']}` t on t.id = g.{$group['fk']}
            where {$group['where']} and t.id is null";
    $left += (int)$pdo->query($sql)->fetchColumn();
}
echo "KIỂM CHỨNG: còn lại ", $left, " dòng mồ côi (mong đợi 0).\n";
exit($left === 0 ? 0 : 1);
