<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_news_api.php';

function testNewsHierarchyAssert($condition, $message)
{
    if(!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
}

class PtsNewsHierarchyFakeDb
{
    public $rows;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function rawQueryOne($sql, array $params)
    {
        if(!preg_match('/#_(news_list|news_cat|news_item|news_sub)/', $sql, $match)) return array();
        $table = $match[1];
        $slug = $params[0] ?? '';
        $parentId = null;
        if($table !== 'news_list') $parentId = (int)($params[1] ?? 0);
        foreach($this->rows[$table] ?? array() as $row)
        {
            if(($row['slugvi'] ?? '') !== $slug || ($row['type'] ?? '') !== 'tin-tuc' || !ptsNewsPublished($row['status'] ?? '')) continue;
            if($parentId !== null)
            {
                $field = array('news_cat'=>'id_list','news_item'=>'id_cat','news_sub'=>'id_item')[$table];
                if((int)($row[$field] ?? 0) !== $parentId) continue;
            }
            return $row;
        }
        return array();
    }
}

$fixture = array(
    'news_list'=>array(array('id'=>10,'namevi'=>'Kiến thức','descvi'=>'','slugvi'=>'kien-thuc','photo'=>'','status'=>'hienthi','type'=>'tin-tuc')),
    'news_cat'=>array(
        array('id'=>20,'id_list'=>10,'namevi'=>'Điện mặt trời','descvi'=>'','slugvi'=>'dien-mat-troi','photo'=>'','status'=>'hienthi','type'=>'tin-tuc'),
        array('id'=>21,'id_list'=>99,'namevi'=>'Nhóm lệch cha','descvi'=>'','slugvi'=>'dien-mat-troi','photo'=>'','status'=>'hienthi','type'=>'tin-tuc'),
        array('id'=>22,'id_list'=>10,'namevi'=>'Nhóm ẩn','descvi'=>'','slugvi'=>'nhom-an','photo'=>'','status'=>'an','type'=>'tin-tuc')
    ),
    'news_item'=>array(array('id'=>30,'id_cat'=>20,'namevi'=>'Ứng dụng','descvi'=>'','slugvi'=>'ung-dung','photo'=>'','status'=>'hienthi','type'=>'tin-tuc')),
    'news_sub'=>array(array('id'=>40,'id_item'=>30,'namevi'=>'Gia đình','descvi'=>'','slugvi'=>'gia-dinh','photo'=>'','status'=>'hienthi','type'=>'tin-tuc'))
);
$db = new PtsNewsHierarchyFakeDb($fixture);

$valid = ptsNewsResolveCategoryHierarchy($db, 'cat', array('kien-thuc','dien-mat-troi'));
testNewsHierarchyAssert(is_array($valid) && $valid['row']['id'] === 20 && $valid['article_field'] === 'id_cat' && $valid['metadata']['path'] === 'kien-thuc/dien-mat-troi', 'resolves published child through its exact published parent');
testNewsHierarchyAssert(ptsNewsResolveCategoryHierarchy($db, 'cat', array('kien-thuc','missing')) === null, 'returns empty for an unresolved category');
testNewsHierarchyAssert(ptsNewsResolveCategoryHierarchy($db, 'cat', array('kien-thuc','nhom-an')) === null, 'excludes unpublished categories');
testNewsHierarchyAssert(ptsNewsResolveCategoryHierarchy($db, 'cat', array('missing-parent','dien-mat-troi')) === null, 'rejects a child under a mismatched parent');
testNewsHierarchyAssert(ptsNewsResolveCategoryHierarchy($db, 'item', array('kien-thuc','dien-mat-troi')) === null, 'rejects category path depth that does not match its type');
testNewsHierarchyAssert(ptsNewsResolveCategoryHierarchy($db, 'unknown', array('kien-thuc')) === null, 'rejects unrecognized category type');
testNewsHierarchyAssert(ptsNewsResolveCategoryHierarchy($db, 'list', array('../unsafe')) === null, 'rejects malformed slug segments');

$dto = ptsNewsCategoryDto(array('id'=>20,'namevi'=>'Điện mặt trời','slugvi'=>'dien-mat-troi','status'=>'hienthi'), $valid['metadata']);
testNewsHierarchyAssert(is_array($dto) && $dto['url'] === '/dien-mat-troi/' && $dto['type'] === 'tin-tuc' && $dto['level'] === 'cat', 'emits a canonical root-category DTO with hierarchy metadata');

echo "PASS: PTS public news category hierarchy\n";
?>
