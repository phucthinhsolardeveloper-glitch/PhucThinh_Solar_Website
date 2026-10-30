<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_catalog_api.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_brand_detail_api.php';
function brandDetailAssert($condition, $message) { if(!$condition) throw new RuntimeException($message); echo "PASS: {$message}\n"; }

$row = array('id' => 3, 'namevi' => '<b>LONGi</b>', 'slugvi' => 'longi', 'status' => 'hienthi');
$products = array(array('id' => 20, 'namevi' => 'Tấm pin thật', 'slugvi' => 'tam-pin-that', 'status' => 'hienthi', '_pts_public_photo' => 'real.webp', '_pts_public_gallery' => array('gallery.webp', '../private.jpg'), '_pts_public_photo2' => 'second.webp', 'regular_price' => 2648100, 'cong_suat' => 650));
$seoRow = array('titlevi' => '<b>LONGi chính hãng</b>', 'descriptionvi' => 'Tấm pin LONGi &amp; giải pháp thật.', 'keywordsvi' => 'LONGi, năng lượng mặt trời');
$payload = ptsBrandDetailPayload($row, $products, 1, 20, 1, true, 'abc', array(), array(), $seoRow, '/upload/product/brand.webp');
brandDetailAssert($payload['brand'] === array('id'=>3,'name'=>'LONGi','slug'=>'longi','url'=>'/thuong-hieu/longi/'), 'brand DTO strip HTML, URL co dau / cuoi (khac PDP)');
brandDetailAssert($payload['seo'] === array('title'=>'LONGi chính hãng | Phúc Thịnh Solar','description'=>'Tấm pin LONGi & giải pháp thật.','keywords'=>'LONGi, năng lượng mặt trời','image'=>'/upload/product/brand.webp'), 'SEO Admin và ảnh thương hiệu được đưa vào metadata DTO an toàn');
brandDetailAssert($payload['path'] === $payload['brand']['url'], 'path khop dung url brand');
brandDetailAssert($payload['products'][0]['pricing'] === array('mode'=>'price','currency'=>'VND','regularPrice'=>2648100,'salePrice'=>null), 'san pham tai dung dung ptsCatalogProductDto/pricing that');
brandDetailAssert($payload['products'][0]['media'] === array('/upload/product/real.webp', '/upload/product/gallery.webp', '/upload/product/second.webp'), 'brand DTO giữ gallery Admin hợp lệ theo sau ảnh chính và loại path không an toàn');
brandDetailAssert($payload['selected'] === array('sort'=>'abc','priceKeys'=>array()), 'sort và bộ lọc giá echo theo contract catalog');
brandDetailAssert(!isset($payload['brand']['logo']) && !isset($payload['brand']['content']) && !isset($payload['brand']['summary']), 'khong bia logo/content/summary - cac truong nay khong duoc render o template PHP that');
$fallbackPayload = ptsBrandDetailPayload(array('id'=>5,'namevi'=>'JinDian','slugvi'=>'jindian','status'=>'hienthi'), array(), 1, 20, 0, false);
brandDetailAssert($fallbackPayload['seo'] === array('title'=>'JinDian | Phúc Thịnh Solar','description'=>'','keywords'=>'','image'=>''), 'SEO thiếu dữ liệu rơi về tên thương hiệu, để mô tả/ảnh trống như PHP');
$unsafeImagePayload = ptsBrandDetailPayload($row, array(), 1, 20, 0, false, 'default', array(), array(), array(), '/upload/product/../secret.php');
brandDetailAssert($unsafeImagePayload['seo']['image'] === '', 'ảnh thương hiệu ngoài allowlist bị loại khỏi metadata');

brandDetailAssert(ptsBrandDetailPayload(array('id'=>4,'namevi'=>'Ẩn','slugvi'=>'an','status'=>''), array(), 1, 20, 0, true) === null, 'brand an bi loai');
brandDetailAssert(ptsBrandDetailPayload(array('id'=>0,'namevi'=>'x','slugvi'=>'x','status'=>'hienthi'), array(), 1, 20, 0, true) === null, 'brand id khong hop le bi loai');

$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_brand_detail.php');
$command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"] = "POST"; require '.var_export($endpoint, true).';').' 2>&1';
brandDetailAssert(strpos((string)shell_exec($command), 'METHOD_NOT_ALLOWED') !== false, 'POST bi tu choi voi 405');
foreach(array('slug' => 'Bad Slug', 'page' => '0', 'sort' => 'UPPER-CASE') as $key => $invalid)
{
    $input = var_export(array('slug' => 'longi', $key => $invalid), true);
    $source = '$_SERVER["REQUEST_METHOD"] = "GET"; $_GET = '.$input.'; require '.var_export($endpoint, true).';';
    $out = shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($source).' 2>&1');
    brandDetailAssert(strpos((string)$out, 'BAD_QUERY') !== false, 'invalid '.$key.' bi tu choi voi 400');
}
echo "PASS: PTS public brand detail API contract\n";
