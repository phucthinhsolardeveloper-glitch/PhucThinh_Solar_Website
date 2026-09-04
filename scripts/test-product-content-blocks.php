<?php
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'product_content_blocks.php';

function contentBlockAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message.PHP_EOL;
}

$paired = productContentBlockParse("Tiết kiệm | Giảm điện năng\nBền bỉ | Dùng ngoài trời", true, 6);
contentBlockAssert(!$paired['errors'] && count($paired['items']) === 2 && $paired['items'][0]['title'] === 'Tiết kiệm', 'Đọc đúng khối điểm nổi bật dạng cặp');
$invalid = productContentBlockParse("Thiếu dấu phân cách", true, 6);
contentBlockAssert(count($invalid['errors']) === 1 && !$invalid['items'], 'Từ chối dòng điểm nổi bật sai định dạng');
$plain = productContentBlockParse("Sân vườn\nLối đi\n<script>alert(1)</script>", false, 3);
contentBlockAssert($plain['items'] === array('Sân vườn', 'Lối đi', 'alert(1)'), 'Khối text loại bỏ thẻ HTML');
$json = json_encode($paired['items'], JSON_UNESCAPED_UNICODE);
contentBlockAssert(productContentBlockLines($json, true) === "Tiết kiệm | Giảm điện năng\nBền bỉ | Dùng ngoài trời", 'Export Excel đổi JSON về dòng dễ nhập');
contentBlockAssert(productContentBlockDecode('{sai-json}') === array(), 'JSON lỗi không làm hỏng trang public');

echo 'Product content block tests completed.'.PHP_EOL;
