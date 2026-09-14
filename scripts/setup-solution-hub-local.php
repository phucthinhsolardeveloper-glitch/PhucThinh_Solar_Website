<?php
/* Local-only additive selection of existing product categories. Default dry
   run; never seed imaginary solutions or overwrite content/ordering. */
if(PHP_SAPI !== 'cli') exit('CLI only');
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php'; new AutoLoad();
if(getenv('APP_ENV') !== 'local') exit("Local development environment required.\n");
$d = new PDODb($config['database']);
$slugs = array('den-nlmt','tam-pin-nang-luong-mat-troi','bien-tan-inverter','pin-luu-tru');
$rows = $d->rawQuery('select id,namevi,slugvi,status from #_product_list where type=? and slugvi in (?,?,?,?) order by numb,id desc', array_merge(array('san-pham'), $slugs));
if(count($rows) !== count($slugs)) throw new RuntimeException('Expected four verified categories; no changes applied.');
$changes = array();
foreach($rows as $row) {
    $statuses = array_filter(explode(',', $row['status']));
    if(in_array('solutionhub', $statuses, true)) continue;
    $statuses[] = 'solutionhub';
    $changes[] = array('id' => $row['id'], 'before' => $row['status'], 'after' => implode(',', $statuses));
    echo 'Select: ', $row['namevi'], "\n";
}
if(!in_array('--apply', $argv, true)) exit("Dry run. Pass --apply to add selection flags on local DB.\n");
if(!$changes) exit("Already configured; no changes.\n");
$backup = '/tmp/solution-hub-selection-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
if(file_put_contents($backup, json_encode($changes, JSON_PRETTY_PRINT)) === false) throw new RuntimeException('Could not back up original status values.');
$d->startTransaction();
try {
    foreach($changes as $change) $d->rawQuery('update #_product_list set status=? where id=? and type=? and status=?', array($change['after'], $change['id'], 'san-pham', $change['before']));
    $d->commit();
} catch(Throwable $e) { $d->rollback(); throw $e; }
echo 'Applied. Original statuses: ', $backup, "\n";
