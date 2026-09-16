<?php
require_once '/var/www/html/libraries/product_merchant_feed.php';

function merchantAssert($condition, $message)
{
    if(!$condition)
    {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function merchantTestImage($path, $width, $height)
{
    $image = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($image, 248, 248, 248);
    imagefill($image, 0, 0, $background);
    imagejpeg($image, $path, 88);
    imagedestroy($image);
}

$tempDir = sys_get_temp_dir().'/pts-merchant-feed-'.getmypid();
if(!mkdir($tempDir, 0700, true) && !is_dir($tempDir))
{
    fwrite(STDERR, "FAIL: Không tạo được thư mục test\n");
    exit(1);
}

$cleanImage = 'anh sach.jpg';
$smallImage = 'anh-nho.jpg';
merchantTestImage($tempDir.'/'.$cleanImage, 800, 800);
merchantTestImage($tempDir.'/'.$smallImage, 300, 300);

$row = array(
    'code' => 'V600-ORIANA',
    'namevi' => 'Đèn sân vườn V600 ORIANA',
    'slugvi' => 'den-san-vuon-v600-oriana',
    'photo_feed' => $cleanImage,
    'regular_price' => 2000000,
    'sale_price' => 1500000,
    'status' => 'hienthi,banchay',
    'descvi' => '<p>Đèn sân vườn chính hãng.</p>',
    'cong_suat' => 60,
    'brand_name' => 'ORIANA',
    'list_name' => 'Đèn năng lượng mặt trời',
    'cat_name' => 'Đèn sân vườn'
);

$headers = productMerchantFeedHeaders();
merchantAssert($headers === array('id','title','description','link','image_link','availability','price','sale_price','brand','condition','identifier_exists','product_type'), 'Sai schema cột Merchant Center');

$feed = productMerchantFeedRow($row, 'https://phucthinhsolar.com/', $tempDir);
merchantAssert(is_array($feed) && count($feed) === count($headers), 'Sản phẩm hợp lệ không được xuất');
$mapped = array_combine($headers, $feed);
merchantAssert($mapped['availability'] === 'in_stock', 'availability phải dùng giá trị in_stock');
merchantAssert($mapped['price'] === '2000000 VND' && $mapped['sale_price'] === '1500000 VND', 'Giá feed phải có tiền tệ và giữ đúng giá bán');
merchantAssert($mapped['brand'] === 'ORIANA' && $mapped['identifier_exists'] === 'no', 'Không được tự bịa thương hiệu hoặc mã định danh');
merchantAssert($mapped['product_type'] === 'Đèn năng lượng mặt trời > Đèn sân vườn', 'Sai cây danh mục feed');
merchantAssert(strpos($mapped['image_link'], 'anh%20sach.jpg') !== false, 'URL ảnh chưa được encode an toàn');
merchantAssert(strpos($mapped['description'], 'Công suất: 60 W.') !== false, 'Thiếu công suất trong mô tả feed');

$invalid = $row;
unset($invalid['photo_feed']);
$invalid['photo'] = $cleanImage;
merchantAssert(productMerchantFeedRow($invalid, 'https://phucthinhsolar.com/', $tempDir) === null, 'Không được fallback sang ảnh website');

$invalid = $row;
$invalid['photo_feed'] = $smallImage;
merchantAssert(productMerchantFeedRow($invalid, 'https://phucthinhsolar.com/', $tempDir) === null, 'Ảnh dưới 500×500 phải bị loại');

$invalid = $row;
$invalid['sale_price'] = $invalid['regular_price'];
merchantAssert(productMerchantFeedRow($invalid, 'https://phucthinhsolar.com/', $tempDir) === null, 'Giá khuyến mãi không thấp hơn giá gốc phải bị loại');

$invalid = $row;
$invalid['code'] = '';
merchantAssert(productMerchantFeedRow($invalid, 'https://phucthinhsolar.com/', $tempDir) === null, 'Thiếu mã sản phẩm phải bị loại');

$invalid = $row;
$invalid['status'] = 'hienthi,baogia';
merchantAssert(productMerchantFeedRow($invalid, 'https://phucthinhsolar.com/', $tempDir) === null, 'Sản phẩm báo giá không được làm lộ giá qua feed');

@unlink($tempDir.'/'.$cleanImage);
@unlink($tempDir.'/'.$smallImage);
@rmdir($tempDir);
echo "OK: Merchant feed chỉ xuất sản phẩm có mã, giá hợp lệ và ảnh sạch đủ kích thước.\n";
