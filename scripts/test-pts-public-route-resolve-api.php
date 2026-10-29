<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_route_resolve_api.php';

function testPublicRouteAssert($condition, $message)
{
    if(!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
}

class PtsPublicRouteFakeDb
{
    public function rawQueryOne($sql, array $params)
    {
        if(strpos($sql, '#_news_list') !== false) return array('id'=>10,'parent_id'=>10,'namevi'=>'Kiến thức','descvi'=>'','slugvi'=>'kien-thuc','photo'=>'','status'=>'hienthi');
        if(strpos($sql, '#_news_cat') !== false) return array('id'=>20,'parent_id'=>10,'namevi'=>'Điện mặt trời','descvi'=>'','slugvi'=>'dien-mat-troi','photo'=>'','status'=>'hienthi');
        if(strpos($sql, '#_news') !== false) return array('slugvi'=>'tin-tuc-thu-nghiem','status'=>'hienthi');
        return array();
    }
}

$routes = ptsPublicRouteResolveMap();
$newsListPriority = null; $newsArticlePriority = null;
foreach($routes as $priority=>$route)
{
    if($route['com'] === 'tin-tuc' && $route['field'] === 'idl' && $newsListPriority === null) $newsListPriority = $priority;
    if($route['com'] === 'tin-tuc' && $route['field'] === 'id' && $newsArticlePriority === null) $newsArticlePriority = $priority;
}
testPublicRouteAssert($newsListPriority !== null && $newsArticlePriority !== null && $newsListPriority < $newsArticlePriority, 'preserves PHP news-category precedence over article slugs');

$db = new PtsPublicRouteFakeDb();
$category = ptsPublicRouteResolveCategory($db, 'news_cat', 'idc', 20, 'tin-tuc');
testPublicRouteAssert(is_array($category) && $category['path'] === 'kien-thuc/dien-mat-troi' && $category['level'] === 'cat', 'resolves category ancestry safely');
$dto = ptsPublicRouteResolveDto($db, array('id'=>35,'route_field'=>'id','route_com'=>'tin-tuc'), 'tin-tuc-thu-nghiem');
testPublicRouteAssert(is_array($dto) && $dto['entity'] === 'news' && $dto['canonical'] === '/tin-tuc-thu-nghiem/', 'maps a published news article to its canonical bare slug');
testPublicRouteAssert(ptsPublicRouteResolveDto($db, array('id'=>0,'route_field'=>'id','route_com'=>'tin-tuc'), 'missing') === null, 'rejects an empty resolver match');

echo "PASS: PTS public route resolver helpers\n";
?>
