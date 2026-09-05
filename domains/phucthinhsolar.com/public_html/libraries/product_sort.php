<?php

if(!function_exists('publicProductDefaultOrder'))
{
    /**
     * Thứ tự mặc định của danh sách public: sản phẩm có ảnh đại diện trước,
     * sau đó vẫn tôn trọng thứ tự thủ công và ID như hành vi cũ.
     */
    function publicProductDefaultOrder($alias = '')
    {
        $alias = trim((string)$alias);
        if($alias !== '' && !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $alias)) throw new InvalidArgumentException('Alias SQL không hợp lệ');
        $prefix = $alias !== '' ? $alias.'.' : '';
        return " order by case when {$prefix}photo is null or trim({$prefix}photo) = '' then 1 else 0 end, {$prefix}numb,{$prefix}id desc";
    }
}

