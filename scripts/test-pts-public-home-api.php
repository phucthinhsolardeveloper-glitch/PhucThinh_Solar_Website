<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_home_api.php';
$payload = ptsPublicHomePayload(array('seo'=>array('title'=>'','description'=>''),'hero'=>array(),'categories'=>array(),'campaigns'=>array(),'solutions'=>array(),'process'=>array(),'dealer'=>array('title'=>'Đại lý','description'=>'Mô tả','href'=>'/dai-ly/'),'about'=>array('title'=>'About','description'=>'Mô tả','href'=>'/gioi-thieu/','action'=>'Xem'),'contact'=>array('hotline'=>'','email'=>'','address'=>'','workingHours'=>''),'productGroups'=>array()));
if($payload['schemaVersion'] !== 1 || $payload['path'] !== '/' || $payload['summary']['hero'] !== 0) { fwrite(STDERR, "homepage DTO assertion failed\n"); exit(1); }
if(ptsHomeUrl('javascript:alert(1)', '/safe/') !== '/safe/') { fwrite(STDERR, "unsafe URL assertion failed\n"); exit(1); }
echo "pts public home helper OK\n";
