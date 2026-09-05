<?php
/**
 * Kiểm tra trước khi bật/deploy phân quyền: tài khoản nào sẽ mất quyền truy cập.
 *
 * Khi $config['permission']['active'] bật, tài khoản role khác 3 chỉ vào được
 * những màn hình có trong nhóm quyền của mình. Tài khoản không thuộc nhóm nào,
 * hoặc thuộc nhóm đang tắt, hoặc thuộc nhóm rỗng, sẽ bị chặn khỏi mọi thứ và
 * người dùng chỉ thấy thông báo "không có quyền" ở mọi trang.
 *
 * Script chỉ đọc, không sửa gì. Chạy trên đúng môi trường sắp deploy.
 *
 * Chạy: php scripts/check-admin-permission-readiness.php
 */

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);

$active = !empty($config['permission']['active']);
$debugDeveloper = !empty($config['website']['debug-developer']);

echo "KIỂM TRA SẴN SÀNG PHÂN QUYỀN ADMIN\n";
echo str_repeat('=', 74), "\n";
echo "permission.active      : ", $active ? 'BẬT' : 'TẮT', "\n";
echo "website.debug-developer: ", $debugDeveloper ? 'BẬT' : 'TẮT', "\n";
if ($debugDeveloper)
    echo "  CẢNH BÁO: debug-developer đang bật thì MỌI kiểm tra quyền đều bị bỏ qua.\n".
         "            Trên production phải đặt về false, nếu không phân quyền vô nghĩa.\n";
echo "\n";

$groups = array();
foreach ($d->rawQuery("select id, name, status from #_permission_group") as $row)
{
    $count = $d->rawQueryOne("select count(*) as n from #_permission where id_permission_group = ?", array((int)$row['id']));
    $groups[(int)$row['id']] = array(
        'name' => $row['name'],
        'enabled' => in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$row['status']))), true),
        'count' => (int)$count['n']
    );
}

echo "NHÓM QUYỀN\n", str_repeat('-', 74), "\n";
if (!$groups) echo "  (chưa có nhóm quyền nào)\n";
foreach ($groups as $id => $group)
    printf("  #%-4s %-34s %4d quyền   %s\n", $id, mb_substr($group['name'], 0, 32), $group['count'],
           $group['enabled'] ? 'đang bật' : 'ĐANG TẮT');
echo "\n";

$blocked = array();
$fine = array();
$bypass = array();
foreach ($d->rawQuery("select id, username, fullname, role, id_permission, status from #_user order by role desc, id") as $user)
{
    $enabled = in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$user['status']))), true);
    if (!$enabled) continue;
    if ((int)$user['role'] === 3) { $bypass[] = $user; continue; }

    $groupId = (int)$user['id_permission'];
    $reason = '';
    if ($groupId === 0) $reason = 'không thuộc nhóm quyền nào';
    elseif (!isset($groups[$groupId])) $reason = 'trỏ tới nhóm quyền #'.$groupId.' không tồn tại';
    elseif (!$groups[$groupId]['enabled']) $reason = 'nhóm "'.$groups[$groupId]['name'].'" đang tắt';
    elseif ($groups[$groupId]['count'] === 0) $reason = 'nhóm "'.$groups[$groupId]['name'].'" chưa có quyền nào';

    if ($reason !== '') $blocked[] = array('user' => $user, 'reason' => $reason);
    else $fine[] = $user;
}

echo "TÀI KHOẢN\n", str_repeat('-', 74), "\n";
printf("  Bỏ qua kiểm tra (role 3): %d\n", count($bypass));
foreach ($bypass as $user) printf("    #%-4s %-24s %s\n", $user['id'], $user['username'], mb_substr((string)$user['fullname'], 0, 30));
printf("  Có nhóm quyền hợp lệ    : %d\n", count($fine));
foreach ($fine as $user)
    printf("    #%-4s %-24s nhóm \"%s\" (%d quyền)\n", $user['id'], $user['username'],
           $groups[(int)$user['id_permission']]['name'], $groups[(int)$user['id_permission']]['count']);
printf("  SẼ BỊ CHẶN HOÀN TOÀN    : %d\n", count($blocked));
foreach ($blocked as $item)
    printf("    #%-4s %-24s %s\n", $item['user']['id'], $item['user']['username'], $item['reason']);

echo "\n";
if (!$active)
    echo "KẾT LUẬN: phân quyền đang TẮT, chưa ảnh hưởng ai. Bật lên rồi chạy lại script này.\n";
elseif ($debugDeveloper)
    echo "KẾT LUẬN: debug-developer đang bật nên phân quyền chưa có tác dụng thật.\n";
elseif ($blocked)
    echo "KẾT LUẬN: CÓ ", count($blocked), " tài khoản sẽ không vào được Admin. Gán nhóm quyền trước khi deploy.\n";
else
    echo "KẾT LUẬN: Mọi tài khoản đang bật đều có đường vào hợp lệ.\n";

exit($active && !$debugDeveloper && $blocked ? 1 : 0);
