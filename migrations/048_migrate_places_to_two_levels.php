<?php
/**
 * Migrate the public address catalogue to Province/City -> Ward/Commune.
 *
 * Usage inside the PHP container:
 *   php migrations/048_migrate_places_to_two_levels.php /path/data.tsv
 *   php migrations/048_migrate_places_to_two_levels.php /path/data.tsv --commit
 *
 * The default mode is a read-only preview. Existing city/ward rows are kept
 * for historical orders and marked hidden; new rows receive fresh IDs so old
 * order references remain readable.
 */

$_SERVER['SERVER_NAME'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
if(!defined('LIBRARIES'))
{
	$libraryRoot = __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/';
	if(!is_dir($libraryRoot)) $libraryRoot = '/var/www/html/libraries/';
	define('LIBRARIES', $libraryRoot);
}
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();

$d = new PDODb($config['database']);
$cache = new Cache($d);
$func = new Functions($d, $cache);

$path = isset($argv[1]) ? $argv[1] : '';
$commit = in_array('--commit', $argv, true);
if($path === '' || !is_file($path))
{
	fwrite(STDERR, "Không tìm thấy file dữ liệu: ".$path."\n");
	exit(1);
}

$clean = function($value) {
	$value = trim((string)$value);
	$value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
	return trim($value);
};

$cities = array();
$wards = array();
$handle = fopen($path, 'rb');
if(!$handle) { fwrite(STDERR, "Không thể đọc file dữ liệu.\n"); exit(1); }
$lineNo = 0;
while(($line = fgets($handle)) !== false)
{
	$lineNo++;
	$columns = str_getcsv(rtrim($line, "\r\n"), "\t");
	if($lineNo === 1) continue;
	$cityName = $clean(isset($columns[0]) ? $columns[0] : '');
	$wardName = $clean(isset($columns[1]) ? $columns[1] : '');
	$wardType = $clean(isset($columns[2]) ? $columns[2] : '');
	if($cityName === '' || $wardName === '') continue;
	if(!isset($cities[$cityName])) $cities[$cityName] = count($cities) + 1;
	$key = $cityName."\x1F".$wardName;
	if(!isset($wards[$key]))
	{
		$wards[$key] = array('city' => $cityName, 'name' => $wardName, 'type' => $wardType);
	}
}
fclose($handle);

if(empty($cities) || empty($wards))
{
	fwrite(STDERR, "File không có dữ liệu Tỉnh/TP và Phường/Xã hợp lệ.\n");
	exit(2);
}

ksort($cities, SORT_NATURAL | SORT_FLAG_CASE);
$wards = array_values($wards);
usort($wards, function($a, $b) {
	$city = strcasecmp($a['city'], $b['city']);
	return $city !== 0 ? $city : strcasecmp($a['name'], $b['name']);
});

$before = $d->rawQueryOne("select (select count(*) from #_city where find_in_set('hienthi',status)) as cities, (select count(*) from #_ward where find_in_set('hienthi',status)) as wards");
echo "Nguồn: ".count($cities)." tỉnh/TP, ".count($wards)." phường/xã duy nhất.\n";
echo "Đang hiển thị trước migration: ".(int)$before['cities']." tỉnh/TP, ".(int)$before['wards']." phường/xã.\n";
if(!$commit)
{
	echo "DRY-RUN: chưa ghi database. Thêm --commit để áp dụng vào DB local.\n";
	exit(0);
}

$cityIds = array();
$now = time();
$transaction = false;
try
{
	$d->startTransaction();
	$transaction = true;
	/* Hide old rows instead of deleting them: historical orders keep their IDs. */
	$d->rawQuery("update #_city set status = ? where find_in_set('hienthi',status)", array('an'));
	$d->rawQuery("update #_ward set status = ? where find_in_set('hienthi',status)", array('an'));

	$numb = 0;
	foreach($cities as $cityName => $cityNumb)
	{
		$row = $d->rawQueryOne("select id from #_city where binary name = binary ? order by id asc limit 0,1", array($cityName));
		$data = array(
			'name' => $cityName,
			'slug' => $func->changeTitle($cityName),
			'level' => 'Tỉnh/TP',
			'numb' => ++$numb,
			'status' => 'hienthi',
			'date_updated' => $now
		);
		if(!empty($row['id']))
		{
			$d->where('id', (int)$row['id']);
			if(!$d->update('city', $data)) throw new RuntimeException('Không cập nhật được tỉnh/TP '.$cityName);
			$cityIds[$cityName] = (int)$row['id'];
		}
		else
		{
			$data['date_created'] = $now;
			if(!$d->insert('city', $data)) throw new RuntimeException('Không tạo được tỉnh/TP '.$cityName);
			$cityIds[$cityName] = (int)$d->getLastInsertId();
		}
	}

	$wardNumb = array();
	foreach($wards as $ward)
	{
		$cityId = $cityIds[$ward['city']];
		if(!isset($wardNumb[$ward['city']])) $wardNumb[$ward['city']] = 0;
		$wardNumb[$ward['city']]++;
		$row = $d->rawQueryOne("select id from #_ward where id_city = ? and id_district = 0 and binary name = binary ? order by id asc limit 0,1", array($cityId, $ward['name']));
		$data = array(
			'id_city' => $cityId,
			'id_district' => 0,
			'name' => $ward['name'],
			'slug' => $func->changeTitle($ward['name']),
			'level' => $ward['type'] !== '' ? $ward['type'] : 'Phường/Xã',
			'numb' => $wardNumb[$ward['city']],
			'status' => 'hienthi',
			'ship_price' => 0,
			'date_updated' => $now
		);
		if(!empty($row['id']))
		{
			$d->where('id', (int)$row['id']);
			if(!$d->update('ward', $data)) throw new RuntimeException('Không cập nhật được phường/xã '.$ward['name']);
		}
		else
		{
			$data['date_created'] = $now;
			if(!$d->insert('ward', $data)) throw new RuntimeException('Không tạo được phường/xã '.$ward['name']);
		}
	}
	$d->commit();
	$transaction = false;
	$cache->delete();
	$after = $d->rawQueryOne("select (select count(*) from #_city where find_in_set('hienthi',status)) as cities, (select count(*) from #_ward where find_in_set('hienthi',status)) as wards");
	echo "ĐÃ ÁP DỤNG: ".(int)$after['cities']." tỉnh/TP, ".(int)$after['wards']." phường/xã đang hiển thị.\n";
}
catch(Throwable $e)
{
	if($transaction) $d->rollback();
	fwrite(STDERR, "LỖI — đã rollback: ".$e->getMessage()."\n");
	exit(3);
}
