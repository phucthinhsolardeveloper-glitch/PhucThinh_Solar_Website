<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_dealer_api.php';
$root = sys_get_temp_dir().'/pts-dealer-test-'.getmypid().'/';
@mkdir($root.'upload/photo/', 0777, true); @mkdir($root.'upload/news/', 0777, true); file_put_contents($root.'upload/photo/hero.jpg', 'fixture');
$payload = ptsDealerPayload(array(array('type'=>'dai-ly-1','namevi'=>'Đại lý','descvi'=>'Năng lực phân phối toàn quốc','status'=>'hienthi'),array('type'=>'dai-ly-2','namevi'=>'Thiết bị','descvi'=>'Sản phẩm phân phối chính hãng','status'=>'hienthi')),array(),array(array('namevi'=>'Hero','photo'=>'hero.jpg','status'=>'hienthi')),array(),array(),array(),$root,'');
if($payload['hero']['image'] !== '/upload/photo/hero.jpg' || $payload['path'] !== '/dai-ly/' || $payload['form']['action'] !== '/dai-ly/#dang-ky-dai-ly') { fwrite(STDERR, "dealer DTO assertion failed\n"); exit(1); }
if($payload['heroDescription'] !== 'Năng lực phân phối toàn quốc' || $payload['overview']['description'] !== 'Sản phẩm phân phối chính hãng') { fwrite(STDERR, "dealer CMS copy assertion failed\n"); exit(1); }
if(ptsDealerPublished('') || ptsDealerMedia('../hero.jpg','upload/photo/',$root) !== '' || ptsDealerLink('javascript:alert(1)') !== '') { fwrite(STDERR, "dealer safety assertion failed\n"); exit(1); }
echo "pts_public_dealer_api: OK\n";
