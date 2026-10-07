<?php

function productImageTestAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message."\n";
}

$publicRoot = is_dir('/var/www/html/libraries')
    ? '/var/www/html'
    : dirname(__DIR__).'/domains/phucthinhsolar.com/public_html';
$tmp = sys_get_temp_dir().'/pts-product-image-test-'.bin2hex(random_bytes(6));
if(!mkdir($tmp, 0700)) throw new RuntimeException('Không tạo được thư mục test');
if(!defined('ROOT')) define('ROOT', $tmp.'/');
if(!defined('UPLOAD_PRODUCT')) define('UPLOAD_PRODUCT', '../upload/product/');
if(!defined('UPLOAD_PRODUCT_L')) define('UPLOAD_PRODUCT_L', 'upload/product/');
$resolverFile = $publicRoot.'/libraries/product_images.php';
productImageTestAssert(is_file($resolverFile), 'Có resolver ảnh sản phẩm dùng chung');
require_once $resolverFile;

try
{
    file_put_contents($tmp.'/main.webp', 'main');
    file_put_contents($tmp.'/gallery.webp', 'gallery');

    productImageTestAssert(productImageUploadDirectory() === $tmp.'/upload/product/', 'Đường dẫn kiểm tra ảnh luôn neo vào ROOT public');

    $main = productImageResolve(array('photo' => 'main.webp', 'photo_feed' => 'feed.webp'), array(
        array('photo' => 'gallery.webp', 'status' => 'hienthi')
    ), $tmp);
    productImageTestAssert($main['has_image'] && $main['photo'] === 'main.webp' && $main['source'] === 'photo', 'Ảnh đại diện hợp lệ được ưu tiên');

    $gallery = productImageResolve(array('photo' => 'lost.webp'), array(
        array('photo' => 'hidden.webp', 'status' => ''),
        array('photo' => 'gallery.webp', 'status' => 'hienthi')
    ), $tmp);
    productImageTestAssert($gallery['has_image'] && $gallery['photo'] === 'gallery.webp' && $gallery['source'] === 'gallery', 'Gallery hiển thị thay ảnh đại diện bị mất file');
    productImageTestAssert($gallery['broken'] === array('lost.webp'), 'Resolver báo riêng liên kết file bị mất');

    $feedOnly = productImageResolve(array('photo' => '', 'photo_feed' => 'main.webp'), array(), $tmp);
    productImageTestAssert(!$feedOnly['has_image'] && $feedOnly['source'] === 'none', 'Ảnh Shopping không bị tính nhầm là ảnh đại diện public');

    $unsafe = productImageResolve(array('photo' => '../main.webp'), array(), $tmp);
    productImageTestAssert(!$unsafe['has_image'] && $unsafe['broken'] === array('../main.webp'), 'Tên file có đường dẫn không được nhận là ảnh hợp lệ');

    require_once $publicRoot.'/libraries/admin_product_completeness.php';
    $missingPhoto = adminProductDataStatus('missing_photo');
    productImageTestAssert(!isset($missingPhoto['sql']), 'Bộ lọc thiếu ảnh dùng resolver có kiểm tra file vật lý');
    productImageTestAssert(isset(adminProductDataStatus('missing_photo_feed')['sql']), 'Ảnh Shopping vẫn là trạng thái dữ liệu riêng');
}
finally
{
    foreach(glob($tmp.'/*') ?: array() as $file) if(is_file($file)) unlink($file);
    if(is_dir($tmp)) rmdir($tmp);
}
