<?php
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'product_power_filter.php';

function powerAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message.PHP_EOL;
}

function powerMatches($raw, $value)
{
    $keys = explode(',', $raw);
    foreach($keys as $key)
    {
        if($key === 'under-60' && $value > 0 && $value < 60) return true;
        if($key === 'over-1000' && $value > 1000) return true;
        if(is_numeric($key) && (float)$key > 0 && (float)$key === (float)$value) return true;
    }
    return false;
}

list($underSql, $underParams) = productPowerFilterSql('under-60');
powerAssert(strpos($underSql, 'cong_suat > 0') !== false && $underParams === array(60), 'Nhóm dưới 60W loại 0 và NULL');
powerAssert(!powerMatches('under-60', 0) && powerMatches('under-60', 2) && powerMatches('under-60', 59.9) && !powerMatches('under-60', 60), 'Biên nhóm dưới 60W đúng');
powerAssert(powerMatches('60', 60) && powerMatches('200', 200) && powerMatches('1000', 1000), 'Các mức phổ biến từ 60W đến 1000W giữ giá trị riêng');
powerAssert(!powerMatches('over-1000', 1000) && powerMatches('over-1000', 1000.1), 'Biên nhóm trên 1000W đúng');
list($combinedSql, $combinedParams) = productPowerFilterSql('under-60,over-1000');
powerAssert(strpos($combinedSql, ' or ') !== false && $combinedParams === array(60, 1000), 'Hai nhóm biên dùng điều kiện allowlist và tham số cố định');
list($legacySql, $legacyParams) = productPowerFilterSql('60,100');
powerAssert(strpos($legacySql, ' in (?,?)') !== false && $legacyParams === array(60.0, 100.0), 'URL số cũ tiếp tục được hỗ trợ');
list($invalidSql, $invalidParams) = productPowerFilterSql('0,abc');
powerAssert($invalidSql === '' && $invalidParams === array(), 'Giá trị 0 và khóa lạ không tạo điều kiện lọc');

echo 'Product power filter tests completed.'.PHP_EOL;
