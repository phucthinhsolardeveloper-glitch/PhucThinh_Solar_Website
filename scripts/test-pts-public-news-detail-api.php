<?php
error_reporting(E_ALL);
if(!defined('LIBRARIES')) define('LIBRARIES', __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/');
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_news_detail_api.php';
function detailAssert($condition, $message) { if(!$condition) throw new RuntimeException($message); echo 'PASS: ', $message, "\n"; }
detailAssert(ptsNewsDetailSlug('bai-viet-cms') === 'bai-viet-cms', 'slug public hợp lệ');
detailAssert(ptsNewsDetailSlug('../secret') === '', 'slug traversal bị loại');
detailAssert(ptsNewsDetailImage('gallery.webp') === '/upload/news/gallery.webp', 'media ảnh đúng thư mục');
detailAssert(ptsNewsDetailImage('../secret.jpg') === '', 'media ảnh traversal bị loại');
detailAssert(ptsNewsDetailVideo('javascript:alert(1)') === '', 'video javascript bị loại');
$dto = ptsNewsDetailArticleDto(array('id'=>8,'namevi'=>'Bài <b>CMS</b>','slugvi'=>'bai-cms','status'=>'hienthi','date_created'=>strtotime('2026-10-26'),'contentvi'=>'<p>Nội dung</p>','descvi'=>'Mô tả','_pts_public_photo'=>'cover.jpg'), array(), array('fullname'=>'Tác giả'), array('gallery'=>array(),'videos'=>array()), array());
detailAssert($dto['content']['format'] === 'sanitized-html' && $dto['content']['sanitizedBy'] === 'pts_public_richtext' && strpos($dto['content']['html'], '<script') === false, 'noi dung da qua pts_public_richtext, khong lo script tho');

detailAssert($dto['legacyViewMutation'] === false && $dto['canonical'] === '/bai-cms/', 'DTO detail read-only và canonical bare slug');
detailAssert($dto['datePublished'] === '2026-10-26' && $dto['dateModified'] === '2026-10-26' && $dto['showCover'] === true, 'schema date fields use published record data and expose its cover');
$updatedDto = ptsNewsDetailArticleDto(array('id'=>10,'namevi'=>'Bài đã cập nhật','slugvi'=>'bai-da-cap-nhat','status'=>'hienthi','date_created'=>strtotime('2026-10-26'),'date_updated'=>strtotime('2026-10-27'),'contentvi'=>'Nội dung'), array(), array(), array('gallery'=>array(),'videos'=>array()), array());
detailAssert($updatedDto['datePublished'] === '2026-10-26' && $updatedDto['dateModified'] === '2026-10-27', 'dateModified follows the PHP date_updated field');
$repeatCover = ptsNewsDetailArticleDto(array('id'=>9,'namevi'=>'Ảnh đã nằm trong nội dung','slugvi'=>'anh-trong-noi-dung','status'=>'hienthi','date_created'=>strtotime('2026-10-26'),'photo'=>'cover-1200x750.webp','_pts_public_photo'=>'cover-1200x750.webp','contentvi'=>'<p><img src="/upload/news/cover.webp"></p>'), array(), array(), array('gallery'=>array(),'videos'=>array()), array());
detailAssert($repeatCover['showCover'] === false, 'cover không lặp khi nội dung biên tập đã chứa cùng ảnh');
detailAssert(ptsNewsDetailCtaDto(array(), '')['title'] === 'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng hỗ trợ', 'news detail dùng CTA fallback khi Admin chưa cấu hình');
detailAssert(ptsNewsDetailCtaDto(array(array('status'=>'an','namevi'=>'Ẩn CTA')), '') === null, 'CTA bị ẩn trong Admin không được render');
$cta = ptsNewsDetailCtaDto(array(array('status'=>'hienthi','namevi'=>'Tư vấn thật','descvi'=>'Nội dung CMS','content1vi'=>'Gửi yêu cầu','_pts_public_photo'=>'cta.jpg')), '0901 234 567');
detailAssert($cta['title'] === 'Tư vấn thật' && $cta['buttonLabel'] === 'Gửi yêu cầu' && $cta['image'] === '/upload/news/cta.jpg' && $cta['hotline']['tel'] === '0901234567', 'CTA lấy nội dung Admin và hotline từ cấu hình');
$apiSource = file_get_contents(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_news_detail.php');
detailAssert(strpos($apiSource, 'date_updated') !== false && strpos($apiSource, "array('news-cta')") !== false && strpos($apiSource, 'ptsNewsDetailCtaDto') !== false, 'endpoint chuyển ngày cập nhật và CTA Admin vào DTO chi tiết');
detailAssert(ptsNewsDetailArticleDto(array('id'=>8,'type'=>'du-an','namevi'=>'Sai loại','slugvi'=>'sai-loai','status'=>'hienthi'), array(), array(), array('gallery'=>array(),'videos'=>array()), array()) === null, 'wrong type bị loại khỏi DTO');
detailAssert(ptsNewsDetailArticleDto(array('id'=>8,'type'=>'tin-tuc','namevi'=>'Ẩn','slugvi'=>'an','status'=>'noibat'), array(), array(), array('gallery'=>array(),'videos'=>array()), array()) === null, 'bài ẩn bị loại khỏi DTO');
$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_news_detail.php');
if(PHP_BINARY && is_file($endpoint)) {
    $command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"] = "POST"; require '.var_export($endpoint, true).';').' 2>&1';
    $methodOutput = shell_exec($command);
    detailAssert(strpos((string)$methodOutput, 'METHOD_NOT_ALLOWED') !== false, 'endpoint từ chối POST');
}
echo "PASS: PTS public news detail API helper contract\n";
