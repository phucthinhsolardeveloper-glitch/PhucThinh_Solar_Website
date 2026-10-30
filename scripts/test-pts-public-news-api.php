<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_news_api.php';

function newsAssert($condition, $message) { if(!$condition) throw new RuntimeException($message); echo 'PASS: ', $message, "\n"; }

$category = array('id'=>4, 'namevi'=>'Kiến thức điện mặt trời', 'slugvi'=>'kien-thuc-dien-mat-troi', 'status'=>'hienthi', '_pts_public_photo'=>'');
$rows = array(
    array('id'=>8, 'id_list'=>4, 'namevi'=>'Bài <b>viết</b> CMS', 'slugvi'=>'bai-viet-cms', 'descvi'=>'<p>Mô tả CMS</p>', 'contentvi'=>str_repeat('nội dung ', 220), 'date_created'=>strtotime('2026-10-26'), 'status'=>'hienthi', '_pts_public_photo'=>'article.webp'),
    array('id'=>9, 'id_list'=>4, 'namevi'=>'Bài nổi bật', 'slugvi'=>'bai-noi-bat', 'descvi'=>'', 'contentvi'=>'Nội dung', 'date_created'=>strtotime('2026-10-25'), 'status'=>'hienthi,newshub', '_pts_public_photo'=>'featured.jpg'),
    array('id'=>10, 'id_list'=>4, 'namevi'=>'Bị ẩn', 'slugvi'=>'bi-an', 'status'=>'newshub', '_pts_public_photo'=>'hidden.jpg')
);
$featured = ptsNewsSelectFeatured($rows, true);
newsAssert(count($featured) === 1 && $featured[0]['id'] === 9, 'featured chỉ lấy bài publish có cờ newshub');
$categoryDto = ptsNewsCategoryDto($category);
$payload = ptsNewsPayload(array($category), array($rows[0]), $featured, array(), false, array(), false, 1, 2, '', null, '', true);
newsAssert($payload['pagination']['perPage'] === 6 && $payload['articles'][0]['description'] === 'Mô tả CMS', 'giữ page size 6 và plain text excerpt');
newsAssert($categoryDto['canonicalPath'] === '/kien-thuc-dien-mat-troi', 'category DTO giữ canonical bare path như PHP');
newsAssert($payload['articles'][0]['readingMinutes'] === 2 && $payload['articles'][0]['url'] === '/bai-viet-cms/', 'reading time và canonical bare slug');
newsAssert($payload['seo'] === array('title'=>'','description'=>'','keywords'=>'','image'=>''), 'SEO hub mặc định rỗng khi chưa có metadata Admin');
$categoryWithSeo = ptsNewsCategoryDto($category, array('type'=>'tin-tuc','level'=>'list','path'=>'kien-thuc-dien-mat-troi','seo'=>array('title'=>'SEO category','description'=>'Mô tả SEO','keywords'=>'tin tức','image'=>'/upload/news/category.webp')));
newsAssert($categoryWithSeo['seo']['image'] === '/upload/news/category.webp' && $categoryWithSeo['seo']['title'] === 'SEO category', 'SEO category được giữ trong metadata phân cấp');
$seoPayload = ptsNewsPayload(array($category), array(), array(), array(), false, array(), false, 1, 0, '', null, '', true, array('title'=>'SEO hub','description'=>'Mô tả SEO','keywords'=>'tin tức','image'=>'/upload/seopage/news.webp'));
newsAssert($seoPayload['seo'] === array('title'=>'SEO hub','description'=>'Mô tả SEO','keywords'=>'tin tức','image'=>'/upload/seopage/news.webp'), 'SEO hub được trả thành DTO riêng');
newsAssert(strpos(json_encode($payload, JSON_UNESCAPED_UNICODE), '<b>') === false && strpos(json_encode($payload, JSON_UNESCAPED_UNICODE), 'hidden.jpg') === false, 'DTO không lộ HTML hoặc bài ẩn');
$hiddenIntro = ptsNewsPayload(array($category), array(), array(), array(), true, array(), true, 1, 0, '', null, '', false);
newsAssert($hiddenIntro['intro'] === null && $hiddenIntro['cta'] === null, 'CMS đã cấu hình nhưng ẩn không hồi sinh fallback');
$visibleEmpty = ptsNewsPayload(array($category), array(), array(), array('status'=>'hienthi'), true, array(array('status'=>'hienthi')), true, 1, 0, '', null, '090 123', false);
newsAssert($visibleEmpty['intro']['title'] === 'Kiến thức' && $visibleEmpty['cta']['buttonLabel'] === 'Nhận tư vấn ngay', 'record CMS visible nhưng field rỗng vẫn dùng fallback field');
newsAssert($visibleEmpty['cta']['hotline']['tel'] === '090123', 'hotline fallback lấy từ setting PHP');
$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_news.php');
$command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"] = "POST"; require '.var_export($endpoint, true).';').' 2>&1';
$methodOutput = shell_exec($command);
newsAssert(strpos((string)$methodOutput, 'METHOD_NOT_ALLOWED') !== false, 'endpoint từ chối method khác GET');
echo "PASS: PTS public news API contract\n";
