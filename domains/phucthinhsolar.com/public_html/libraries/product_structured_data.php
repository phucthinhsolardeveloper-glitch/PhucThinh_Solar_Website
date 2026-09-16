<?php
if(!function_exists('productStructuredData'))
{
    function productStructuredText($value)
    {
        $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
    }

    /**
     * Tạo Product JSON-LD chỉ từ dữ liệu thực đang có.
     *
     * Không tự sinh MPN/GTIN, đánh giá hoặc hạn giá. Các thuộc tính này chỉ
     * được phép xuất khi hệ thống có field/dữ liệu đã xác minh tương ứng.
     */
    function productStructuredData(array $product, array $brand, array $setting, $language, $url, $imageUrl, $description, $requiresQuote, $ratingValue = 0, $reviewCount = 0)
    {
        $language = preg_match('/^[a-z]{2}$/', (string)$language) ? (string)$language : 'vi';
        $name = productStructuredText(isset($product['name'.$language]) ? $product['name'.$language] : '');
        $canonicalUrl = trim((string)$url);
        $data = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'url' => $canonicalUrl
        );

        $imageUrl = trim((string)$imageUrl);
        if($imageUrl !== '') $data['image'] = array($imageUrl);

        $description = productStructuredText($description);
        if($description !== '') $data['description'] = $description;

        $sku = trim(isset($product['code']) ? (string)$product['code'] : '');
        if($sku === '' && !empty($product['id'])) $sku = 'SP'.(int)$product['id'];
        if($sku !== '') $data['sku'] = $sku;

        $brandName = productStructuredText(isset($brand['name'.$language]) ? $brand['name'.$language] : '');
        if($brandName !== '')
        {
            $data['brand'] = array('@type' => 'Brand', 'name' => $brandName);
        }

        $reviewCount = max(0, (int)$reviewCount);
        $ratingValue = (float)$ratingValue;
        if($reviewCount > 0 && $ratingValue >= 1 && $ratingValue <= 5)
        {
            $data['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $ratingValue,
                'reviewCount' => $reviewCount
            );
        }

        $regularPrice = isset($product['regular_price']) ? (float)$product['regular_price'] : 0;
        $salePrice = isset($product['sale_price']) ? (float)$product['sale_price'] : 0;
        $currentPrice = ($salePrice > 0 && ($regularPrice <= 0 || $salePrice < $regularPrice)) ? $salePrice : $regularPrice;
        if(!$requiresQuote && $currentPrice > 0)
        {
            $sellerName = productStructuredText(isset($setting['name'.$language]) ? $setting['name'.$language] : '');
            $offer = array(
                '@type' => 'Offer',
                'url' => $canonicalUrl,
                'priceCurrency' => 'VND',
                'price' => number_format($currentPrice, 0, '.', ''),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => 'https://schema.org/InStock'
            );
            if($sellerName !== '') $offer['seller'] = array('@type' => 'Organization', 'name' => $sellerName);
            $data['offers'] = $offer;
        }

        return $data;
    }
}
