<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/solution_hub.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_solutions_api.php';
if(!defined('ROOT')) define('ROOT', '/tmp/pts-test-root/');
if(!defined('UPLOAD_NEWS_L')) define('UPLOAD_NEWS_L', 'upload/news/');

function solutionCategoryAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException($message);
    echo 'PASS: ', $message, "\n";
}

class PtsSolutionCategoryFakeDb
{
    public $rows = array(
        'news_list'=>array('id'=>1,'namevi'=>'Giải pháp','descvi'=>'','slugvi'=>'giai-phap','status'=>'hienthi'),
        'news_cat'=>array('id'=>2,'id_list'=>1,'namevi'=>'Điện mặt trời','descvi'=>'','slugvi'=>'dien-mat-troi','status'=>'hienthi'),
        'news_item'=>array('id'=>3,'id_cat'=>2,'namevi'=>'Nhà xưởng','descvi'=>'','slugvi'=>'nha-xuong','status'=>'hienthi'),
        'news_sub'=>array('id'=>4,'id_item'=>3,'namevi'=>'Mái lớn','descvi'=>'','slugvi'=>'mai-lon','status'=>'hienthi')
    );
    public function rawQueryOne($sql, array $params)
    {
        foreach($this->rows as $table=>$row)
        {
            if(strpos($sql, '#_'.$table) === false || ($row['slugvi'] ?? '') !== ($params[0] ?? '')) continue;
            $parent = array('news_cat'=>'id_list','news_item'=>'id_cat','news_sub'=>'id_item')[$table] ?? '';
            if($parent !== '' && (int)($row[$parent] ?? 0) !== (int)($params[1] ?? -1)) continue;
            return $row;
        }
        return array();
    }
}

$db = new PtsSolutionCategoryFakeDb();
$cases = array(
    array('list',array('giai-phap')),
    array('cat',array('giai-phap','dien-mat-troi')),
    array('item',array('giai-phap','dien-mat-troi','nha-xuong')),
    array('sub',array('giai-phap','dien-mat-troi','nha-xuong','mai-lon'))
);
foreach($cases as $case)
{
    $result = ptsPublicSolutionsResolveCategory($db,$case[0],$case[1]);
    solutionCategoryAssert(is_array($result) && $result['metadata']['level'] === $case[0] && $result['metadata']['path'] === implode('/',$case[1]), 'resolve taxonomy '.$case[0].' with exact parents');
}
solutionCategoryAssert(ptsPublicSolutionsResolveCategory($db,'item',array('khong-dung','dien-mat-troi','nha-xuong')) === null, 'reject cross-parent category path');
$db->rows['news_cat']['status'] = '';
solutionCategoryAssert(ptsPublicSolutionsResolveCategory($db,'cat',array('giai-phap','dien-mat-troi')) === null, 'reject unpublished category');
$db->rows['news_cat']['status'] = 'hienthi';

$category = ptsPublicSolutionsResolveCategory($db,'sub',array('giai-phap','dien-mat-troi','nha-xuong','mai-lon'));
$solutionRows = array(array('id'=>7,'id_sub'=>4,'namevi'=>'Mái xưởng','title_indexvi'=>'Giải pháp mái xưởng','slugvi'=>'mai-xuong','descvi'=>'Tư vấn','status'=>'hienthi'));
$payload = ptsPublicSolutionsCategoryPayload($category,$solutionRows,array('cta'=>null));
solutionCategoryAssert($payload['entity'] === 'solution-category' && $payload['canonical'] === '/mai-lon/' && count($payload['cards']) === 1, 'category DTO uses bare-slug canonical and only matching published content');
$payload = ptsPublicSolutionsCategoryPayload($category,array(array('id'=>8,'id_sub'=>4,'namevi'=>'Ẩn','slugvi'=>'an','status'=>'')),array('cta'=>null));
solutionCategoryAssert($payload['cards'] === array(), 'hidden solution content is not published');
echo "PASS: PTS public solutions category contract\n";
?>
