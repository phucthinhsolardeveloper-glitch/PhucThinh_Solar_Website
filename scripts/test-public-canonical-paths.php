<?php
require __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/product_category_urls.php';

function canonicalPathAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message."\n";
}

class PublicCanonicalPathMockDb
{
    public $queries = array();

    public function rawQueryOne($sql, $params)
    {
        $this->queries[] = array('sql' => $sql, 'params' => $params);

        if(strpos($sql, '#_product_brand') !== false && $params === array(42, 'san-pham'))
        {
            return array('slugvi' => 'longi');
        }
        if(strpos($sql, '#_news') !== false && $params === array(84, 'chinh-sach'))
        {
            return array('slugvi' => 'chinh-sach-bao-hanh-bao-tri-va-ho-tro-ky-thuat');
        }

        return array();
    }
}

$db = new PublicCanonicalPathMockDb();

canonicalPathAssert(
    publicCanonicalEntityPath($db, 'san-pham', array('idb' => 42), 'vi') === 'thuong-hieu/longi/',
    'brand detail canonical uses /thuong-hieu/{slug}/'
);
canonicalPathAssert(
    publicCanonicalEntityPath($db, 'chinh-sach', array('id' => 84), 'vi') === 'ho-tro/chinh-sach-bao-hanh-bao-tri-va-ho-tro-ky-thuat/',
    'policy detail canonical uses the /ho-tro/{slug}/ route'
);
canonicalPathAssert(count($db->queries) === 2, 'each canonical lookup performs one read-only query');
canonicalPathAssert(
    strpos($db->queries[0]['sql'], '?') !== false && $db->queries[0]['params'] === array(42, 'san-pham'),
    'brand lookup uses parameterized ID and route type'
);
canonicalPathAssert(
    strpos($db->queries[1]['sql'], '?') !== false && $db->queries[1]['params'] === array(84, 'chinh-sach'),
    'policy lookup uses parameterized ID and route type'
);

echo "Public canonical path tests completed.\n";
?>
