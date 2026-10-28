<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_dealer_api.php';
$root = sys_get_temp_dir().'/pts-dealer-test-'.getmypid().'/';
@mkdir($root.'upload/photo/', 0777, true); @mkdir($root.'upload/news/', 0777, true); file_put_contents($root.'upload/photo/hero.jpg', 'fixture');
$static = array(
    array('type'=>'dai-ly-1','namevi'=>'Đại lý','descvi'=>'<p>Intro <strong>riêng</strong>.</p><script>alert(1)</script>','status'=>'hienthi'),
    array('type'=>'dai-ly-2','namevi'=>'Thiết bị','descvi'=>'<p>Mô tả sản phẩm riêng.</p><ul><li>Ý một</li></ul>','status'=>'hienthi')
);
$stats = array(
    array('namevi'=>'Legacy hidden row','solieu'=>'999','status'=>'noibat'),
    array('namevi'=>'Năm kinh nghiệm','solieu'=>'5','status'=>'hienthi'),
    array('namevi'=>'Nhân sự','solieu'=>'44','status'=>'hienthi'),
    array('namevi'=>'Khách hàng','solieu'=>'4362','status'=>'hienthi')
);
$payload = ptsDealerPayload($static,array(),array(array('namevi'=>'Hero','photo'=>'hero.jpg','status'=>'hienthi')),array(),array(),$stats,$root,'');
if($payload['hero']['image'] !== '/upload/photo/hero.jpg' || $payload['path'] !== '/dai-ly/' || $payload['form']['action'] !== '/dai-ly/#dang-ky-dai-ly') { fwrite(STDERR, "dealer DTO assertion failed\n"); exit(1); }
if(strpos($payload['intro']['html'], '<strong>riêng</strong>') === false || strpos($payload['intro']['html'], '<script') !== false) { fwrite(STDERR, "dealer intro sanitizer assertion failed\n"); exit(1); }
if(strpos($payload['overview']['descriptionContent']['html'], 'Mô tả sản phẩm riêng.') === false || strpos($payload['overview']['descriptionContent']['html'], 'Intro') !== false) { fwrite(STDERR, "dealer overview source mapping assertion failed\n"); exit(1); }
if(array_column($payload['stats'], 'value') !== array('5', '44', '4362')) { fwrite(STDERR, "dealer visible statistics assertion failed\n"); exit(1); }
if(ptsDealerPublished('') || ptsDealerMedia('../hero.jpg','upload/photo/',$root) !== '' || ptsDealerLink('javascript:alert(1)') !== '') { fwrite(STDERR, "dealer safety assertion failed\n"); exit(1); }
echo "pts_public_dealer_api: OK\n";
