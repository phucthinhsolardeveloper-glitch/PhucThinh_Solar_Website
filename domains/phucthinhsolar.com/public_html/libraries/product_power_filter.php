<?php
if(!function_exists('productPowerFilterSql'))
{
    function productPowerFilterSql($rawValue, $column = 'cong_suat')
    {
        $definitions = array(
            'under-60' => array("$column > 0 and $column < ?", array(60)),
            '60-200' => array("$column >= ? and $column <= ?", array(60, 200)),
            'over-200' => array("$column > ?", array(200))
        );
        $conditions = array();
        $params = array();
        foreach(array_unique(explode(',', (string)$rawValue)) as $key)
        {
            $key = trim($key);
            if(!isset($definitions[$key])) continue;
            $conditions[] = '('.$definitions[$key][0].')';
            $params = array_merge($params, $definitions[$key][1]);
        }
        if(!empty($conditions)) return array(' and ('.implode(' or ', $conditions).')', $params);

        /* Tương thích URL công suất số đã được chia sẻ trước đây. */
        $watts = array_values(array_filter(array_map('floatval', explode(',', (string)$rawValue)), function($value) { return $value > 0; }));
        if(empty($watts)) return array('', array());
        return array(' and '.$column.' in ('.implode(',', array_fill(0, count($watts), '?')).')', $watts);
    }
}
