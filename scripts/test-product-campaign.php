<?php
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$cache = new Cache($d);
require LIBRARIES.'product_promo.php';

function campaignAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message, PHP_EOL;
}

$now = 1700000000;
$product = array('id'=>91, 'id_list'=>10, 'id_cat'=>20, 'id_brand'=>30, 'status'=>'hienthi', 'photo_khuyenmai'=>'');
$baseCampaign = array('id'=>1, 'name'=>'Danh mục', 'photo'=>'cat.png', 'priority'=>10, 'start_at'=>0, 'end_at'=>0, 'enabled'=>true, 'scopes'=>array(array('scope_type'=>'cat','scope_id'=>20)));
campaignAssert(productPromoCampaignSpecificity($baseCampaign, $product, $now) === 30, 'Chiến dịch khớp đúng danh mục cấp 2');
campaignAssert(productPromoCampaignSpecificity(array_merge($baseCampaign, array('start_at'=>$now + 1)), $product, $now) === -1, 'Chiến dịch chưa đến lịch không được áp');
campaignAssert(productPromoCampaignSpecificity(array_merge($baseCampaign, array('end_at'=>$now - 1)), $product, $now) === -1, 'Chiến dịch hết lịch không được áp');

$brandCampaign = array('id'=>2, 'name'=>'Thương hiệu', 'photo'=>'brand.png', 'priority'=>10, 'start_at'=>0, 'end_at'=>0, 'enabled'=>true, 'scopes'=>array(array('scope_type'=>'brand','scope_id'=>30)));
$specificWinner = productPromoFrameResolve($product, array($brandCampaign, $baseCampaign), $now);
campaignAssert($specificWinner['campaign_id'] === 1, 'Cùng ưu tiên thì phạm vi danh mục cụ thể hơn thương hiệu thắng');
$priorityWinner = productPromoFrameResolve($product, array($baseCampaign, array_merge($brandCampaign, array('priority'=>11))), $now);
campaignAssert($priorityWinner['campaign_id'] === 2, 'Mức ưu tiên cao hơn thắng khi nhiều chiến dịch trùng phạm vi');

$legacyProduct = $product;
$legacyProduct['status'] = 'hienthi,khuyenmai';
$legacyProduct['photo_khuyenmai'] = 'legacy.png';
$legacyWinner = productPromoFrameResolve($legacyProduct, array($brandCampaign, $baseCampaign), $now);
campaignAssert($legacyWinner['source'] === 'product' && $legacyWinner['photo'] === 'legacy.png', 'Khung riêng cũ của sản phẩm luôn là override cao nhất');
$legacyProduct['status'] = 'hienthi';
campaignAssert(productPromoFrameResolve($legacyProduct, array(), $now) === array(), 'Khung riêng đã tắt không tự xuất hiện');

$schema = $d->rawQueryOne('select database() as name');
$tables = $d->rawQueryOne("select count(*) as total from information_schema.tables where table_schema = ? and table_name in (?, ?)", array($schema['name'], 'table_product_campaign', 'table_product_campaign_scope'));
campaignAssert((int)$tables['total'] === 2, 'Migration 022 tạo đủ hai bảng chiến dịch');

$fixtureCampaignId = 0;
try
{
    $target = $d->rawQueryOne("select id, id_list, id_cat, id_brand, status, photo_khuyenmai from #_product where type = ? and (coalesce(trim(photo_khuyenmai), '') = '' or not find_in_set('khuyenmai',status)) order by id limit 1", array('san-pham'));
    campaignAssert(!empty($target['id']), 'DB local có sản phẩm để kiểm tra resolver tích hợp');
    $d->insert('product_campaign', array(
        'name'=>'CODEX campaign resolver fixture', 'photo'=>'codex-campaign-fixture.png', 'priority'=>321,
        'start_at'=>time()-60, 'end_at'=>time()+60, 'status'=>'hienthi', 'date_created'=>time(), 'date_updated'=>time()
    ));
    $fixtureCampaignId = (int)$d->getLastInsertId();
    $d->insert('product_campaign_scope', array('id_campaign'=>$fixtureCampaignId, 'scope_type'=>'product', 'scope_id'=>(int)$target['id']));
    $cache->delete();
    $integrationFrame = productPromoFrameResolve($target, productPromoCampaigns($cache));
    campaignAssert(!empty($integrationFrame) && (int)$integrationFrame['campaign_id'] === $fixtureCampaignId, 'Resolver nạp campaign/scope parameterized từ DB');
}
finally
{
    if($fixtureCampaignId)
    {
        $d->rawQuery('delete from #_product_campaign_scope where id_campaign = ?', array($fixtureCampaignId));
        $d->rawQuery('delete from #_product_campaign where id = ?', array($fixtureCampaignId));
        $cache->delete();
    }
}

echo "Product campaign tests completed.\n";
?>
