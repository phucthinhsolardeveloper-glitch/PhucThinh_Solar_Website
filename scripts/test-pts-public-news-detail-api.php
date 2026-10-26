<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_news_detail_api.php';
function detailAssert($condition, $message) { if(!$condition) throw new RuntimeException($message); echo 'PASS: ', $message, "\n"; }
detailAssert(ptsNewsDetailSlug('bai-viet-cms') === 'bai-viet-cms', 'slug public hợp lệ');
detailAssert(ptsNewsDetailSlug('../secret') === '', 'slug traversal bị loại');
detailAssert(ptsNewsDetailImage('gallery.webp') === '/upload/news/gallery.webp', 'media ảnh đúng thư mục');
detailAssert(ptsNewsDetailImage('../secret.jpg') === '', 'media ảnh traversal bị loại');
detailAssert(ptsNewsDetailVideo('javascript:alert(1)') === '', 'video javascript bị loại');
$dto = ptsNewsDetailArticleDto(array('id'=>8,'namevi'=>'Bài <b>CMS</b>','slugvi'=>'bai-cms','status'=>'hienthi','date_created'=>strtotime('2026-10-26'),'contentvi'=>'<p>Nội dung</p>','descvi'=>'Mô tả','_pts_public_photo'=>'cover.jpg'), array(), array('fullname'=>'Tác giả'), array('gallery'=>array(),'videos'=>array()), array());
detailAssert($dto['content']['format'] === 'plain-text' && strpos($dto['content']['value'], '<') === false, 'nội dung không lộ HTML');
detailAssert($dto['legacyViewMutation'] === false && $dto['canonical'] === '/bai-cms/', 'DTO detail read-only và canonical bare slug');
detailAssert(ptsNewsDetailArticleDto(array('id'=>8,'type'=>'du-an','namevi'=>'Sai loại','slugvi'=>'sai-loai','status'=>'hienthi'), array(), array(), array('gallery'=>array(),'videos'=>array()), array()) === null, 'wrong type bị loại khỏi DTO');
detailAssert(ptsNewsDetailArticleDto(array('id'=>8,'type'=>'tin-tuc','namevi'=>'Ẩn','slugvi'=>'an','status'=>'noibat'), array(), array(), array('gallery'=>array(),'videos'=>array()), array()) === null, 'bài ẩn bị loại khỏi DTO');
$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_news_detail.php');
if(PHP_BINARY && is_file($endpoint)) {
    $command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"] = "POST"; require '.var_export($endpoint, true).';').' 2>&1';
    $methodOutput = shell_exec($command);
    detailAssert(strpos((string)$methodOutput, 'METHOD_NOT_ALLOWED') !== false, 'endpoint từ chối POST');
}
echo "PASS: PTS public news detail API helper contract\n";
