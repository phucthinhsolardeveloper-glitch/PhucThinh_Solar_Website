<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_home_api.php';
$payload = ptsPublicHomePayload(array('seo'=>array('title'=>'','description'=>''),'hero'=>array(),'categories'=>array(),'campaigns'=>array(),'solutions'=>array(),'process'=>array(),'dealer'=>array('title'=>'Đại lý','description'=>'Mô tả','href'=>'/dai-ly/'),'about'=>array('title'=>'About','description'=>'Mô tả','href'=>'/gioi-thieu/','action'=>'Xem'),'contact'=>array('hotline'=>'','email'=>'','address'=>'','workingHours'=>''),'productGroups'=>array()));
if($payload['schemaVersion'] !== 1 || $payload['path'] !== '/' || $payload['summary']['hero'] !== 0) { fwrite(STDERR, "homepage DTO assertion failed\n"); exit(1); }
if($payload['summary']['campaignsConfigured'] !== 0) { fwrite(STDERR, "unconfigured campaigns should use the approved fallback\n"); exit(1); }
$input = array('seo'=>array('title'=>'','description'=>''),'hero'=>array(),'categories'=>array(),'campaigns'=>array(),'campaignsConfigured'=>true,'solutions'=>array(),'process'=>array(),'dealer'=>array('title'=>'Đại lý','description'=>'Mô tả','href'=>'/dai-ly/'),'about'=>array('title'=>'About','description'=>'Mô tả','href'=>'/gioi-thieu/','action'=>'Xem'),'contact'=>array('hotline'=>'','email'=>'','address'=>'','workingHours'=>''),'productGroups'=>array());
$payload = ptsPublicHomePayload($input);
if($payload['summary']['campaignsConfigured'] !== 1) { fwrite(STDERR, "configured campaigns must suppress the approved fallback when all rows are hidden\n"); exit(1); }
if(ptsHomeUrl('javascript:alert(1)', '/safe/') !== '/safe/') { fwrite(STDERR, "unsafe URL assertion failed\n"); exit(1); }
if(ptsPublicHomePopup(null) !== null || ptsPublicHomePopup(array('photo'=>'../config.php')) !== null || $payload['popup'] !== null) { fwrite(STDERR, "popup must be null without a safe visible image\n"); exit(1); }
$popupRoot = sys_get_temp_dir().'/pts-home-popup-'.getmypid();
@mkdir($popupRoot.'/upload/photo', 0777, true);
file_put_contents($popupRoot.'/upload/photo/campaign.png', 'x');
if(!defined('ROOT')) define('ROOT', $popupRoot);
$popupRow = array('id'=>5,'date_updated'=>100,'namevi'=>'<b>Khuyến mãi</b>','photo'=>'campaign.png','link'=>'javascript:alert(1)');
$popup = ptsPublicHomePopup($popupRow);
$edited = ptsPublicHomePopup(array('date_updated'=>101) + $popupRow);
@unlink($popupRoot.'/upload/photo/campaign.png'); @rmdir($popupRoot.'/upload/photo'); @rmdir($popupRoot.'/upload'); @rmdir($popupRoot);
if(!is_array($popup) || $popup['image'] !== '/upload/photo/campaign.png' || $popup['title'] !== 'Khuyến mãi' || $popup['href'] !== '' || !preg_match('/^[a-f0-9]{20}$/', $popup['key'])) { fwrite(STDERR, "popup DTO shape/sanitising failed\n"); exit(1); }
if($edited['key'] === $popup['key']) { fwrite(STDERR, "editing the popup must issue a new campaign key\n"); exit(1); }
echo "pts public home helper OK\n";
