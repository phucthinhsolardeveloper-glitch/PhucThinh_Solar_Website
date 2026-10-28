<?php
/**
 * Safe public DTO for Nuxt migration. Never return raw #_setting/options rows:
 * they may contain private configuration not intended for website visitors.
 */
function ptsPublicSitePayload(array $setting, array $options, array $productLists)
{
    $name = isset($setting['namevi']) && is_scalar($setting['namevi'])
        ? trim((string)$setting['namevi']) : '';
    if($name === '') $name = 'Phúc Thịnh Solar';

    $hotline = isset($options['hotline']) && is_scalar($options['hotline'])
        ? trim((string)$options['hotline']) : '';
    $categories = array();
    foreach($productLists as $row)
    {
        if(!is_array($row)) continue;
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        $label = isset($row['namevi']) && is_scalar($row['namevi'])
            ? trim((string)$row['namevi']) : '';
        $slug = isset($row['slugvi']) && is_scalar($row['slugvi'])
            ? trim((string)$row['slugvi'], '/') : '';
        if($id <= 0 || $label === '' || $slug === '') continue;
        $categories[] = array(
            'id' => $id,
            'name' => $label,
            'slug' => $slug,
            'url' => '/danh-muc-san-pham/'.rawurlencode($slug).'/'
        );
    }

    return array(
        'schemaVersion' => 1,
        'brand' => array('name' => $name, 'hotline' => $hotline),
        'productCategories' => $categories
    );
}
