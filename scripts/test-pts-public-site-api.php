<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_site_api.php';

function check($condition, $message) {
    if(!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
}
$result = ptsPublicSitePayload(
    array('namevi'=>'Phúc Thịnh Solar', 'secret'=>'SHOULD_NOT_LEAK'),
    array('hotline'=>'0123456789', 'private_token'=>'SHOULD_NOT_LEAK'),
    array(
        array('id'=>4,'namevi'=>'Đèn năng lượng', 'slugvi'=>'den-nang-luong'),
        array('id'=>0,'namevi'=>'Invalid', 'slugvi'=>'invalid'),
        array('id'=>5,'namevi'=>'','slugvi'=>'empty')
    )
);
check($result['schemaVersion'] === 1, 'schema version');
check($result['brand']['name'] === 'Phúc Thịnh Solar', 'name');
check(count($result['productCategories']) === 1, 'published DTO filtering');
check($result['productCategories'][0]['url'] === '/danh-muc-san-pham/den-nang-luong/', 'canonical category path');
check(strpos(json_encode($result), 'SHOULD_NOT_LEAK') === false, 'private config absent');
echo "PASS: PTS public site DTO (5 assertions)\n";
