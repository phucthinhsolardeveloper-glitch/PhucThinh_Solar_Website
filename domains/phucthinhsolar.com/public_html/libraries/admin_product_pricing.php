<?php
if(!function_exists('adminNormalizeProductPricing'))
{
    function adminNormalizeProductPricing($data)
    {
        $errors = array();
        $values = array();

        foreach(array('regular_price' => 'Giá bán', 'sale_price' => 'Giá mới') as $field => $label)
        {
            $raw = isset($data[$field]) ? trim((string)$data[$field]) : '';
            $normalized = str_replace(array(',', ' '), '', $raw);
            if($raw !== '' && ($normalized === '' || !is_numeric($normalized) || (float)$normalized < 0))
            {
                $errors[] = $label.' không hợp lệ';
                $values[$field] = 0;
                continue;
            }
            $values[$field] = $raw === '' ? 0 : (float)$normalized;
        }

        if($values['regular_price'] > 0 && $values['sale_price'] > $values['regular_price'])
            $errors[] = 'Giá mới không được lớn hơn giá bán';

        $values['discount'] = ($values['regular_price'] > 0 && $values['sale_price'] > 0)
            ? max(0, min(100, round((($values['regular_price'] - $values['sale_price']) / $values['regular_price']) * 100)))
            : 0;

        return array('values' => $values, 'errors' => $errors);
    }
}
