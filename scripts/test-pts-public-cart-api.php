<?php
error_reporting(E_ALL);
$library = __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_cart_api.php';
if(!is_file($library)) $library = '/var/www/html/libraries/pts_public_cart_api.php';
require_once $library;

function cartAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}

$product = array(
    'id' => 2199,
    'slugvi' => 'den-pha-nang-luong-mat-troi-jindian-jd-18500-500w',
    'namevi' => 'Đèn pha năng lượng mặt trời',
    'status' => 'hienthi',
    'regular_price' => '1758000',
    'sale_price' => '0',
    'photo' => 'jd-18500.png',
);
$pricing = ptsCartPricingDto($product, true);
cartAssert($pricing === array('mode'=>'price','currency'=>'VND','regularPrice'=>1758000,'salePrice'=>null), 'zero sale price is normalized to null like the catalog API');
$item = ptsCartItemDto(array('productid'=>2199,'qty'=>2,'code'=>'0123456789abcdef0123456789abcdef'), $product, '', '', '', 'upload/product/', true);
cartAssert($item !== null && $item['unitPrice'] === 1758000 && $item['lineTotal'] === 3516000, 'cart totals use the public regular price when sale price is empty');
$noPrice = ptsCartPricingDto(array('regular_price'=>'0','sale_price'=>0,'status'=>'hienthi'), true);
cartAssert($noPrice['mode'] === 'quote' && $noPrice['regularPrice'] === null && $noPrice['salePrice'] === null, 'products with no positive price remain quote-only');
$quote = ptsCartPricingDto(array('regular_price'=>1758000,'sale_price'=>0,'status'=>'hienthi,baogia'), true);
cartAssert($quote['mode'] === 'quote' && $quote['regularPrice'] === null && $quote['salePrice'] === null, 'Admin quote status still suppresses public prices');

echo "PASS: PTS public cart API contract\n";
