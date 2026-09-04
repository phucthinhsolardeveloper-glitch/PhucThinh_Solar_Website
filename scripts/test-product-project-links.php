<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
require LIBRARIES.'product_projects.php';

function linkAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message.PHP_EOL;
}

$suffix = bin2hex(random_bytes(5));
$productId = 0;
$projectId = 0;

try {
    linkAssert((bool)$d->rawQueryOne("show tables like 'table_product_project'"), 'Migration 010 đã tạo bảng quan hệ');

    $d->insert('product', array(
        'namevi' => 'Fixture sản phẩm dự án '.$suffix,
        'slugvi' => 'fixture-san-pham-du-an-'.$suffix,
        'code' => 'FIXTURE-PP-'.$suffix,
        'type' => 'san-pham', 'status' => 'hienthi', 'numb' => 1,
        'regular_price' => 0, 'sale_price' => 0, 'discount' => 0, 'cong_suat' => 1,
        'id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0, 'id_brand' => 0,
        'date_created' => time(), 'date_updated' => 0, 'view' => 0
    ));
    $productId = (int)$d->getLastInsertId();
    $d->insert('news', array(
        'namevi' => 'Fixture dự án sản phẩm '.$suffix,
        'slugvi' => 'fixture-du-an-san-pham-'.$suffix,
        'type' => 'du-an', 'status' => 'hienthi', 'numb' => 1,
        'id_list' => 0, 'id_cat' => 0, 'id_item' => 0, 'id_sub' => 0,
        'date_created' => time(), 'date_updated' => 0, 'view' => 0
    ));
    $projectId = (int)$d->getLastInsertId();

    linkAssert($productId > 0 && $projectId > 0, 'Tạo fixture độc lập cho quan hệ');
    linkAssert(productProjectSync($d, $projectId, array($productId)), 'Lưu quan hệ bằng transaction');
    linkAssert(productProjectIdsForProject($d, $projectId) === array($productId), 'Đọc đúng sản phẩm theo dự án');
    linkAssert(!productProjectSync($d, $projectId, array(999999999)), 'Từ chối ID sản phẩm không tồn tại');
    linkAssert(productProjectIdsForProject($d, $projectId) === array($productId), 'Validation lỗi không xóa quan hệ đang có');

    $publicProduct = $d->rawQueryOne("select p.id from #_product_project pp inner join #_product p on p.id = pp.id_product where pp.id_project = ? and find_in_set('hienthi',pp.status) and p.type = ? and find_in_set('hienthi',p.status)", array($projectId, 'san-pham'));
    $publicProject = $d->rawQueryOne("select n.id from #_product_project pp inner join #_news n on n.id = pp.id_project where pp.id_product = ? and find_in_set('hienthi',pp.status) and n.type = ? and find_in_set('hienthi',n.status)", array($productId, 'du-an'));
    linkAssert((int)$publicProduct['id'] === $productId && (int)$publicProject['id'] === $projectId, 'Public đọc được quan hệ theo cả hai chiều');

    $d->where('id', $productId);
    $d->update('product', array('status' => ''));
    $hiddenProduct = $d->rawQueryOne("select p.id from #_product_project pp inner join #_product p on p.id = pp.id_product where pp.id_project = ? and find_in_set('hienthi',p.status)", array($projectId));
    linkAssert(empty($hiddenProduct), 'Sản phẩm ẩn không xuất hiện ở Dự án public');
} finally {
    if($projectId > 0) $d->rawQuery('delete from #_product_project where id_project = ?', array($projectId));
    if($productId > 0) $d->rawQuery('delete from #_product where id = ?', array($productId));
    if($projectId > 0) $d->rawQuery('delete from #_news where id = ?', array($projectId));
}

$orphan = $d->rawQueryOne('select count(*) as total from #_product_project pp left join #_product p on p.id = pp.id_product left join #_news n on n.id = pp.id_project where p.id is null or n.id is null');
linkAssert((int)$orphan['total'] === 0, 'Không còn quan hệ mồ côi sau test');
echo "Product-project link tests completed.\n";
