<?php
require_once '/var/www/html/libraries/product_structured_data.php';

function structuredAssert($condition, $message)
{
    if(!$condition)
    {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$product = array(
    'id' => 71,
    'code' => 'SUN2000-10KTL',
    'namevi' => 'Biến tần Huawei 10kW',
    'regular_price' => 30000000,
    'sale_price' => 27500000
);
$schema = productStructuredData(
    $product,
    array('namevi' => 'Huawei'),
    array('namevi' => 'Phúc Thịnh Solar'),
    'vi',
    'https://phucthinhsolar.com/san-pham/bien-tan-huawei-10kw',
    'https://phucthinhsolar.com/upload/product/huawei-10kw.webp',
    'Biến tần chính hãng &amp; bảo hành rõ ràng.',
    false,
    4.5,
    2
);

structuredAssert($schema['name'] === 'Biến tần Huawei 10kW', 'Sai tên Product');
structuredAssert($schema['sku'] === 'SUN2000-10KTL', 'Structured data không dùng mã sản phẩm thật');
structuredAssert($schema['brand']['name'] === 'Huawei', 'Structured data không dùng thương hiệu thật');
structuredAssert($schema['offers']['price'] === '27500000', 'Offer phải dùng giá bán hiện tại');
structuredAssert($schema['offers']['itemCondition'] === 'https://schema.org/NewCondition', 'Sản phẩm mới không được khai báo UsedCondition');
structuredAssert($schema['offers']['seller']['name'] === 'Phúc Thịnh Solar', 'Sai người bán');
structuredAssert($schema['aggregateRating']['ratingValue'] === 4.5 && $schema['aggregateRating']['reviewCount'] === 2, 'Đánh giá thật không được xuất đúng');

$encoded = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
foreach(array('925872', 'Executive Objects', 'UsedCondition', '2020-11-05', 'priceValidUntil', '"review"') as $forbidden)
{
    structuredAssert(strpos($encoded, $forbidden) === false, 'Còn dữ liệu structured data giả/cũ: '.$forbidden);
}

$quoteSchema = productStructuredData($product, array(), array('namevi' => 'Phúc Thịnh Solar'), 'vi', 'https://phucthinhsolar.com/san-pham/test', '', '', true, 0, 0);
structuredAssert(!isset($quoteSchema['offers']), 'Chế độ báo giá không được xuất Offer có giá');
structuredAssert(!isset($quoteSchema['brand']), 'Không được lấy danh mục hoặc tên công ty thay cho thương hiệu thiếu');
structuredAssert(!isset($quoteSchema['aggregateRating']), 'Không được tạo đánh giá khi chưa có đánh giá thật');
structuredAssert($quoteSchema['url'] === 'https://phucthinhsolar.com/san-pham/test', 'Trang báo giá vẫn cần URL Product');

echo "OK: Product JSON-LD chỉ dùng giá, thương hiệu và đánh giá thực.\n";
