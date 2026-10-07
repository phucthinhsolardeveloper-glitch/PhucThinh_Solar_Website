<?php

$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
require LIBRARIES.'product_sort.php';
require LIBRARIES.'product_images.php';
new AutoLoad();
$d = new PDODb($config['database']);

function photoPriorityAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message."\n";
}

$order = publicProductDefaultOrder();
photoPriorityAssert(strpos(publicProductDefaultOrder('p'), 'p.photo') !== false, 'Helper hỗ trợ alias bảng an toàn');

$invalidAliasRejected = false;
try { publicProductDefaultOrder('p;drop'); }
catch(InvalidArgumentException $e) { $invalidAliasRejected = true; }
photoPriorityAssert($invalidAliasRejected, 'Helper từ chối alias SQL không hợp lệ');

$rows = $d->rawQuery("select * from #_product where type = ? and find_in_set('hienthi',status)".$order, array('san-pham'));
$missingSeen = false;
$photoAfterMissing = false;
$manualOrderKept = true;
$galleryFallbackSeen = false;
$lastByGroup = array(0=>null, 1=>null);
$missingPhotoFiles = array();
foreach($rows as $row)
{
    $resolved = productImageResolveFromDatabase($d, $row);
    $missing = !$resolved['has_image'];
    if($resolved['source'] === 'gallery') $galleryFallbackSeen = true;
    if(trim((string)$row['photo']) !== '' && !is_file('/var/www/html/upload/product/'.$row['photo'])) $missingPhotoFiles[] = $row['photo'];
    if($missing) $missingSeen = true;
    elseif($missingSeen) $photoAfterMissing = true;
    $group = $missing ? 1 : 0;
    if($lastByGroup[$group] !== null)
    {
        $previous = $lastByGroup[$group];
        if(!((int)$previous['numb'] < (int)$row['numb']
            || ((int)$previous['numb'] === (int)$row['numb'] && (int)$previous['id'] > (int)$row['id']))) $manualOrderKept = false;
    }
    $lastByGroup[$group] = $row;
}
photoPriorityAssert(!$photoAfterMissing, 'Không có sản phẩm có ảnh hiển thị nằm sau nhóm thiếu ảnh');
photoPriorityAssert($manualOrderKept, 'Giữ numb tăng dần và ID giảm dần trong từng nhóm');
photoPriorityAssert(count($rows) > 0 && $missingSeen, 'Dữ liệu local kiểm tra được cả nhóm có ảnh và thiếu ảnh');
photoPriorityAssert($galleryFallbackSeen, 'Sản phẩm chỉ có gallery vẫn thuộc nhóm có ảnh hiển thị');
photoPriorityAssert(empty($missingPhotoFiles), 'Mọi ảnh đại diện được ưu tiên đều tồn tại trên ổ đĩa');
