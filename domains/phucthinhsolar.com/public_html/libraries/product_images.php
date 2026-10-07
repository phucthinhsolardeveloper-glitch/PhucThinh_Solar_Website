<?php

if(!function_exists('productImageResolve'))
{
    function productImageFilename($value)
    {
        $value = trim((string)$value);
        if($value === '' || strpos($value, "\0") !== false || basename($value) !== $value) return '';
        return $value;
    }

    function productImageHasVisibleStatus($status)
    {
        return in_array('hienthi', array_map('trim', explode(',', (string)$status)), true);
    }

    function productImageResolve($product, $galleryRows = array(), $uploadDirectory = '')
    {
        $uploadDirectory = rtrim((string)$uploadDirectory, '/\\').DIRECTORY_SEPARATOR;
        $broken = array();
        $candidates = array();

        $rawMain = trim(isset($product['photo']) ? (string)$product['photo'] : '');
        if($rawMain !== '') $candidates[] = array('raw' => $rawMain, 'source' => 'photo');

        foreach((array)$galleryRows as $galleryRow)
        {
            if(isset($galleryRow['status']) && !productImageHasVisibleStatus($galleryRow['status'])) continue;
            $rawGallery = trim(isset($galleryRow['photo']) ? (string)$galleryRow['photo'] : '');
            if($rawGallery !== '') $candidates[] = array('raw' => $rawGallery, 'source' => 'gallery');
        }

        foreach($candidates as $candidate)
        {
            $filename = productImageFilename($candidate['raw']);
            if($filename !== '' && $uploadDirectory !== DIRECTORY_SEPARATOR && is_file($uploadDirectory.$filename))
            {
                return array(
                    'has_image' => true,
                    'photo' => $filename,
                    'source' => $candidate['source'],
                    'broken' => array_values(array_unique($broken))
                );
            }
            $broken[] = $candidate['raw'];
        }

        return array(
            'has_image' => false,
            'photo' => '',
            'source' => 'none',
            'broken' => array_values(array_unique($broken))
        );
    }

    function productImageUploadDirectory($override = '')
    {
        if(trim((string)$override) !== '') return rtrim((string)$override, '/\\').DIRECTORY_SEPARATOR;
        if(defined('ROOT') && defined('UPLOAD_PRODUCT_L')) return rtrim((string)ROOT, '/\\').DIRECTORY_SEPARATOR.trim((string)UPLOAD_PRODUCT_L, '/\\').DIRECTORY_SEPARATOR;
        if(defined('UPLOAD_PRODUCT') && trim((string)UPLOAD_PRODUCT) !== '') return rtrim((string)UPLOAD_PRODUCT, '/\\').DIRECTORY_SEPARATOR;
        return '';
    }

    function productImageGalleryRows($d, $product)
    {
        if($d === null) return array();
        $id = isset($product['id']) ? (int)$product['id'] : 0;
        $type = isset($product['type']) ? trim((string)$product['type']) : '';
        if($id <= 0 || $type === '') return array();
        return $d->rawQuery(
            "select photo,status,numb,id from #_gallery where id_parent = ? and com = 'product' and kind = 'man' and val = ? and find_in_set('hienthi',status) order by numb,id",
            array($id, $type)
        );
    }

    function productImageResolveFromDatabase($d, $product, $uploadDirectory = '')
    {
        $directory = productImageUploadDirectory($uploadDirectory);
        static $cache = array();
        $cacheKey = ($d && is_object($d) ? spl_object_hash($d) : 'none').'|'.(int)(isset($product['id']) ? $product['id'] : 0).'|'.trim(isset($product['photo']) ? (string)$product['photo'] : '').'|'.$directory;
        if(isset($cache[$cacheKey])) return $cache[$cacheKey];
        $mainOnly = productImageResolve($product, array(), $directory);
        if($mainOnly['has_image']) return $cache[$cacheKey] = $mainOnly;
        return $cache[$cacheKey] = productImageResolve($product, productImageGalleryRows($d, $product), $directory);
    }
}
