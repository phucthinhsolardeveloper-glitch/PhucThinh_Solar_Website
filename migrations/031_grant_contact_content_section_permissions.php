<?php
/**
 * Cấp quyền "Liên hệ" và "Khối nội dung mô tả" cho nhóm quyền cấp cao nhất.
 *
 * Hai màn hình admin này (com=contact, com=content_section) tồn tại và có
 * link trong sidebar từ trước, nhưng CHƯA BAO GIỜ xuất hiện trong danh mục
 * quyền — không nhóm quyền nào, kể cả nhóm cấp cao nhất, có thể được cấp
 * quyền vào chúng. Cổng phân quyền (requick.php) vẫn đòi hỏi một quyền cho
 * mọi request, nên hai màn hình này trên thực tế chỉ tài khoản role 3 (bỏ qua
 * mọi kiểm tra) mới dùng được — bất kỳ tài khoản role 1 nào, kể cả thuộc nhóm
 * quyền đầy đủ nhất, bấm vào đều bị từ chối.
 *
 * Sau khi thêm hai màn hình này vào form "Thêm nhóm quyền"
 * (permission_group_tpl.php) và gắn cổng chặn tương ứng
 * (admin_security.php, menu.php), cần cấp lại các quyền mới này cho (các)
 * nhóm quyền đang giữ vai trò "đầy đủ" — xác định bằng số quyền đang có gần
 * bằng hoặc bằng tổng danh mục hiện có (tức đã áp dụng
 * scripts/sync-permission-groups.php --fill trước đó), không suy đoán tên
 * nhóm hay ID cố định vì có thể khác nhau giữa local/production.
 *
 * Không đụng tới các nhóm quyền hẹp hơn — chúng chưa từng có ý định truy cập
 * hai màn hình này nên không cần thêm gì.
 *
 * Chạy:
 *   php migrations/031_grant_contact_content_section_permissions.php          # xem trước
 *   php migrations/031_grant_contact_content_section_permissions.php --apply  # ghi DB
 */

if (PHP_SAPI !== 'cli') exit(1);
if (!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';

$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if (!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
if (!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}
define('LIBRARIES', $publicHtml.'/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';

$apply = in_array('--apply', $argv, true);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$prefix = !empty($config['database']['prefix']) ? $config['database']['prefix'] : '';

/* Quyền mới cần có trong danh mục, đúng chuỗi mà cổng chặn (requick.php ->
   admin_security.php) và menu.php thực sự kiểm tra. */
$newPermissions = array('contact_man', 'contact_edit', 'contact_delete');
if (isset($config['product']) && is_array($config['product']))
    foreach ($config['product'] as $productKey => $productValue)
        foreach (array('man', 'edit', 'delete') as $verb)
            $newPermissions[] = 'content_section_'.$verb.'_'.$productKey;

/* Tổng số quyền trong TOÀN BỘ danh mục hiện tại (sau khi thêm 2 màn hình
   trên), dùng để nhận diện nhóm nào đang giữ vai trò "đầy đủ". Đếm bằng cách
   cộng dồn từng khối y hệt cách permission_group_tpl.php sinh checkbox, để
   không lệch nếu sau này có người thêm bớt module. */
$fullCatalogue = array();
if (isset($config['product']) && is_array($config['product']))
{
    foreach ($config['product'] as $key => $value)
    {
        foreach (array('list', 'cat', 'item', 'sub', 'brand', 'color', 'size') as $variant)
        {
            if (empty($value[$variant])) continue;
            foreach (array('man', 'add', 'edit', 'delete') as $verb)
                $fullCatalogue[] = 'product_'.$verb.'_'.$variant.'_'.$key;
        }
        foreach (array('man', 'add', 'edit', 'delete') as $verb) $fullCatalogue[] = 'product_'.$verb.'_'.$key;
        foreach (array('man', 'add', 'edit') as $verb) $fullCatalogue[] = 'product_campaign_'.$verb.'_'.$key;
        if (!empty($value['comment'])) $fullCatalogue[] = 'comment_man_product_'.$key;
        if (!empty($value['gallery'])) foreach (array('man', 'add', 'edit', 'delete') as $verb) $fullCatalogue[] = 'product_'.$verb.'_photo_'.$key;
        if (!empty($value['import'])) { $fullCatalogue[] = 'import_man_'.$key; $fullCatalogue[] = 'import_editImages_'.$key; $fullCatalogue[] = 'import_deleteImages_'.$key; }
        if (!empty($value['export'])) $fullCatalogue[] = 'export_man_'.$key;
    }
}
if (isset($config['news']) && is_array($config['news']))
{
    foreach ($config['news'] as $key => $value)
    {
        foreach (array('list', 'cat', 'item', 'sub') as $variant)
        {
            if (empty($value[$variant])) continue;
            foreach (array('man', 'add', 'edit', 'delete') as $verb)
                $fullCatalogue[] = 'news_'.$verb.'_'.$variant.'_'.$key;
        }
        foreach (array('man', 'add', 'edit', 'delete') as $verb) $fullCatalogue[] = 'news_'.$verb.'_'.$key;
        if (!empty($value['comment'])) $fullCatalogue[] = 'comment_man_news_'.$key;
        if (!empty($value['gallery'])) foreach (array('man', 'add', 'edit', 'delete') as $verb) $fullCatalogue[] = 'news_'.$verb.'_photo_'.$key;
    }
}
if (isset($config['photo']['photo_static']) && is_array($config['photo']['photo_static']))
    foreach ($config['photo']['photo_static'] as $key => $value) $fullCatalogue[] = 'photo_photo_static_'.$key;
if (isset($config['photo']['man_photo']) && is_array($config['photo']['man_photo']))
    foreach ($config['photo']['man_photo'] as $key => $value)
        foreach (array('man', 'add', 'edit', 'delete') as $verb) $fullCatalogue[] = 'photo_'.$verb.'_photo_'.$key;
if (!empty($config['order']['active']))
    foreach (array('man', 'edit', 'delete') as $verb) $fullCatalogue[] = 'order_'.$verb;
if (isset($config['tags']) && is_array($config['tags']))
    foreach ($config['tags'] as $key => $value)
        foreach (array('man', 'add', 'edit', 'delete') as $verb) $fullCatalogue[] = 'tags_'.$verb.'_'.$key;
if (isset($config['newsletter']) && is_array($config['newsletter']))
    foreach ($config['newsletter'] as $key => $value)
        foreach (array('man', 'add', 'edit', 'delete') as $verb) $fullCatalogue[] = 'newsletter_'.$verb.'_'.$key;
if (isset($config['static']) && is_array($config['static']))
    foreach ($config['static'] as $key => $value) $fullCatalogue[] = 'static_update_'.$key;
if (!empty($config['places']['active']))
    foreach (array('city', 'district', 'ward') as $level)
        foreach (array('man', 'add', 'edit', 'delete') as $verb) $fullCatalogue[] = 'places_'.$verb.'_'.$level;
if (!empty($config['onesignal']))
    foreach (array('man', 'add', 'edit', 'sync', 'delete') as $verb) $fullCatalogue[] = 'pushOnesignal_'.$verb;
if (isset($config['seopage']['page']) && is_array($config['seopage']['page']))
    foreach ($config['seopage']['page'] as $key => $value) $fullCatalogue[] = 'seopage_update_'.$key;
$fullCatalogue[] = 'setting_update';
$fullCatalogue = array_values(array_unique(array_merge($fullCatalogue, $newPermissions)));
$oldCatalogue = array_values(array_diff($fullCatalogue, $newPermissions));
$fullCatalogueCount = count($fullCatalogue);

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";
echo "Tổng danh mục quyền sau khi thêm 2 màn hình mới: ", $fullCatalogueCount, "\n";
echo "Quyền mới cần cấp cho nhóm đầy đủ: ", implode(', ', $newPermissions), "\n\n";

$groups = $pdo->query("select id, name from `{$prefix}permission_group` order by id")->fetchAll(PDO::FETCH_ASSOC);
$updated = 0;
foreach ($groups as $group)
{
    $heldStmt = $pdo->prepare("select permission from `{$prefix}permission` where id_permission_group = ?");
    $heldStmt->execute(array($group['id']));
    $held = $heldStmt->fetchAll(PDO::FETCH_COLUMN);

    /* Nhóm "đầy đủ" là nhóm đã có ít nhất 90% danh mục CŨ (trước khi thêm 2
       màn hình này) — ngưỡng này chỉ nhằm phân biệt nhóm cấp cao (được đồng
       bộ đầy đủ trước đó) khỏi các nhóm hẹp (cơ bản, content), không dùng số
       tuyệt đối vì danh mục có thể đã đổi giữa các lần chạy. */
    $oldCatalogueCount = count($oldCatalogue);
    $heldCatalogueCount = count(array_intersect(array_unique($held), $oldCatalogue));
    $isFullGroup = $oldCatalogueCount > 0 && ($heldCatalogueCount / $oldCatalogueCount) >= 0.9;

    if (!$isFullGroup)
    {
        printf("Bỏ qua nhóm #%d \"%s\": %d/%d quyền hợp lệ, không phải nhóm đầy đủ.\n", $group['id'], $group['name'], $heldCatalogueCount, $oldCatalogueCount);
        continue;
    }

    $missing = array_values(array_diff($newPermissions, $held));
    if (!$missing)
    {
        printf("Nhóm #%d \"%s\": đã có đủ %d quyền mới, không cần làm gì.\n", $group['id'], $group['name'], count($newPermissions));
        continue;
    }

    printf("Nhóm #%d \"%s\" (%d/%d quyền, nhóm đầy đủ): sẽ thêm %d quyền: %s\n",
        $group['id'], $group['name'], $heldCatalogueCount, $oldCatalogueCount, count($missing), implode(', ', $missing));

    if ($apply)
    {
        $insert = $pdo->prepare("insert into `{$prefix}permission` (id_permission_group, permission) values (?, ?)");
        foreach ($missing as $permission) $insert->execute(array($group['id'], $permission));
        $updated++;
    }
}

echo "\n";
if (!$apply) { echo "Chưa ghi gì. Chạy lại kèm --apply để thực hiện.\n"; exit(0); }

echo "Đã cập nhật ", $updated, " nhóm quyền.\n";
echo "Nhắc: người đang đăng nhập cần đăng xuất rồi vào lại để nhận quyền mới.\n";
