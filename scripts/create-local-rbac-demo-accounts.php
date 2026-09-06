<?php
/**
 * Tạo/đổi mật khẩu ba tài khoản demo theo các nhóm quyền local hiện có.
 * Chỉ chạy khi APP_ENV=local. Mật khẩu không in ra terminal; file kết quả phải
 * đặt ngoài webroot và được chmod 0600.
 *
 *   php scripts/create-local-rbac-demo-accounts.php /tmp/pts-rbac-accounts.txt
 */

if(PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'local')
{
    fwrite(STDERR, "FAIL: Script chỉ được chạy trong APP_ENV=local.\n");
    exit(1);
}

$output = isset($argv[1]) ? (string)$argv[1] : '';
if($output === '' || strpos($output, '/var/www/html') === 0)
{
    fwrite(STDERR, "FAIL: Chỉ định file credential ngoài webroot.\n");
    exit(1);
}
$outputDir = dirname($output);
if(!is_dir($outputDir) || !is_writable($outputDir))
{
    fwrite(STDERR, "FAIL: Thư mục đích không tồn tại hoặc không ghi được.\n");
    exit(1);
}

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'config-type.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);

$definitions = array(
    array('username' => 'ptscapcao', 'group' => 'Nhóm quyền cấp cao', 'label' => 'Cấp cao'),
    array('username' => 'ptscoban', 'group' => 'Nhóm quyền cơ bản', 'label' => 'Cơ bản'),
    array('username' => 'ptscontent', 'group' => 'Nhóm quyền content', 'label' => 'Content')
);
$prepared = array();
foreach($definitions as $definition)
{
    $group = $d->rawQueryOne(
        "select g.id, count(p.id) as permission_count from #_permission_group g left join #_permission p on p.id_permission_group = g.id where g.name = ? and find_in_set('hienthi', g.status) group by g.id limit 1",
        array($definition['group'])
    );
    if(empty($group['id']) || (int)$group['permission_count'] < 1)
        throw new RuntimeException('Nhóm quyền không tồn tại/rỗng: '.$definition['group']);
    $definition['group_id'] = (int)$group['id'];
    $definition['password'] = bin2hex(random_bytes(10));
    $prepared[] = $definition;
}

$temporary = tempnam($outputDir, '.pts-rbac-');
if($temporary === false) throw new RuntimeException('Không tạo được file credential tạm.');
$body = "TÀI KHOẢN DEMO RBAC LOCAL — KHÔNG DÙNG CHO PRODUCTION\n";
$body .= "Admin URL: /administrator/\n\n";
foreach($prepared as $account)
{
    $body .= 'Nhóm: '.$account['label']."\n";
    $body .= 'Username: '.$account['username']."\n";
    $body .= 'Password: '.$account['password']."\n\n";
}
if(file_put_contents($temporary, $body, LOCK_EX) === false || !chmod($temporary, 0600))
{
    @unlink($temporary);
    throw new RuntimeException('Không ghi được file credential an toàn.');
}

$createdIds = array();
$snapshots = array();
$outputWritten = false;
try
{
    foreach($prepared as $account)
    {
        $existing = $d->rawQueryOne('select * from #_user where username = ? limit 1', array($account['username']));
        if(!empty($existing) && strpos((string)$existing['fullname'], '[LOCAL RBAC DEMO]') !== 0)
            throw new RuntimeException('Username đã thuộc dữ liệu khác: '.$account['username']);

        $data = array(
            'id_permission' => $account['group_id'],
            'password' => $func->encryptPassword($config['website']['secret'], $account['password'], $config['website']['salt']),
            'fullname' => '[LOCAL RBAC DEMO] '.$account['label'],
            'email' => $account['username'].'@example.test',
            'phone' => '', 'address' => 'Local only', 'gender' => 0,
            'login_session' => '', 'user_token' => '', 'lastlogin' => '0',
            'status' => 'hienthi', 'role' => 1,
            'secret_key' => bin2hex(random_bytes(16)),
            'birthday' => 0, 'numb' => 0
        );

        if(!empty($existing['id']))
        {
            $snapshots[] = $existing;
            $d->where('id', (int)$existing['id']);
            if(!$d->update('user', $data)) throw new RuntimeException('Không cập nhật được '.$account['username']);
            $id = (int)$existing['id'];
        }
        else
        {
            $data['username'] = $account['username'];
            if(!$d->insert('user', $data)) throw new RuntimeException('Không tạo được '.$account['username']);
            $id = (int)$d->getLastInsertId();
            $createdIds[] = $id;
        }

        $verified = $d->rawQueryOne('select id_permission, role, status, password from #_user where id = ? limit 1', array($id));
        if(empty($verified) || (int)$verified['id_permission'] !== $account['group_id'] || (int)$verified['role'] !== 1
            || strpos((string)$verified['status'], 'hienthi') === false || !hash_equals($data['password'], (string)$verified['password']))
            throw new RuntimeException('Không xác minh được tài khoản '.$account['username']);
    }

    if(!rename($temporary, $output)) throw new RuntimeException('Không chốt được file credential.');
    $outputWritten = true;
    @chmod($output, 0600);
    $cache->delete();
}
catch(Exception $exception)
{
    @unlink($temporary);
    if($outputWritten) @unlink($output);
    foreach(array_reverse($createdIds) as $id)
    {
        $d->rawQuery('delete from #_user_log where id_user = ?', array($id));
        $d->rawQuery('delete from #_user where id = ?', array($id));
    }
    foreach(array_reverse($snapshots) as $snapshot)
    {
        $id = (int)$snapshot['id'];
        unset($snapshot['id']);
        $d->where('id', $id);
        $d->update('user', $snapshot);
    }
    fwrite(STDERR, 'ROLLBACK: '.$exception->getMessage()."\n");
    exit(1);
}

echo 'PASS: Đã tạo/xoay 3 tài khoản demo local và lưu credential mode 0600 tại '.$output."\n";
echo "Tài khoản: ptscapcao, ptscoban, ptscontent. Không dùng trên production.\n";
