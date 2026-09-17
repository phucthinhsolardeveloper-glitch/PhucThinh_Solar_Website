<?php if($template == 'product/product_detail') {
    require_once LIBRARIES.'product_structured_data.php';
    $productSchemaImage = !empty($rowDetail['photo']) ? $configBase.UPLOAD_PRODUCT_L.$rowDetail['photo'] : '';
    $productSchema = productStructuredData(
        isset($rowDetail) && is_array($rowDetail) ? $rowDetail : array(),
        isset($productBrand) && is_array($productBrand) ? $productBrand : array(),
        isset($setting) && is_array($setting) ? $setting : array(),
        $lang,
        $func->getCurrentPageURL_CANO(),
        $productSchemaImage,
        $seo->get('description'),
        publicProductRequiresQuote(isset($rowDetail) ? $rowDetail : array()),
        isset($comment) ? $comment->avgPoint() : 0,
        isset($comment) ? $comment->total : 0
    );
?>
    <!-- Product: chỉ xuất dữ liệu thực, không tạo MPN/đánh giá/hạn giá giả -->
    <script type="application/ld+json"><?=json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>
<?php } else if($template == 'news/news_detail') { ?>
    <!-- News -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "NewsArticle",
            "mainEntityOfPage":
            {
                "@type": "WebPage",
                "@id": "https://google.com/article"
            },
            "headline": "<?=@$rowDetail['name'.$lang]?>",
            "image":
            [
                "<?=$configBase.UPLOAD_NEWS_L.@$rowDetail['photo']?>"
            ],
            "datePublished": "<?=date('Y-m-d',@$rowDetail['date_created'])?>",
            "dateModified": "<?=date('Y-m-d',@$rowDetail['date_updated'])?>",
            "author":
            {
                "@type": "Person",
                "name": "<?=@$setting['name'.$lang]?>"
            },
            "publisher":
            {
                "@type": "Organization",
                "name": "Phúc Thịnh Solar",
                "logo":
                {
                    "@type": "ImageObject",
                    "url": "<?=$configBase.UPLOAD_PHOTO_L.@$logo['photo']?>"
                }
            },
            "description": "<?=$seo->get('description')?>"
        }
    </script>
<?php } else if($template == 'static/static') { ?>
    <!-- Static -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "NewsArticle",
            "mainEntityOfPage":
            {
                "@type": "WebPage",
                "@id": "https://google.com/article"
            },
            "headline": "<?=@$static['name'.$lang]?>",
            "image":
            [
                "<?=$configBase.UPLOAD_NEWS_L.@$static['photo']?>"
            ],
            "datePublished": "<?=date('Y-m-d',@$static['date_created'])?>",
            "dateModified": "<?=date('Y-m-d',@$static['date_updated'])?>",
            "author":
            {
                "@type": "Person",
                "name": "<?=@$setting['name'.$lang]?>"
            },
            "publisher":
            {
                "@type": "Organization",
                "name": "Phúc Thịnh Solar",
                "logo":
                {
                    "@type": "ImageObject",
                    "url": "<?=$configBase.UPLOAD_PHOTO_L.@$logo['photo']?>"
                }
            },
            "description": "<?=$seo->get('description')?>"
        }
    </script>
<?php } else { ?>
    <!-- General -->
    <?php
        $publicBrandName = 'Phúc Thịnh Solar';
        $organizationId = $configBase.'#organization';
        $websiteId = $configBase.'#website';
        $sameAs = array();
        if(!empty($social)) foreach($social as $socialItem)
        {
            if(empty($socialItem['link'])) continue;
            $socialLink = trim($socialItem['link']);
            $genericLinks = array(
                'http://google.com',
                'http://www.google.com',
                'https://google.com',
                'https://www.google.com'
            );
            if(in_array(rtrim($socialLink, '/'), $genericLinks, true)) continue;
            if(filter_var($socialLink, FILTER_VALIDATE_URL)) $sameAs[] = $socialLink;
        }
        $organizationSchema = array(
            '@type' => 'Organization',
            '@id' => $organizationId,
            'name' => $publicBrandName,
            'legalName' => isset($setting['name'.$lang]) ? $setting['name'.$lang] : $publicBrandName,
            'alternateName' => 'PHUCTHINH SOLAR',
            'url' => $configBase,
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => $configBase.UPLOAD_PHOTO_L.@$logo['photo']
            ),
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => isset($optsetting['address']) ? $optsetting['address'] : '',
                'addressRegion' => 'Gia Lai',
                'addressCountry' => 'VN'
            )
        );
        if(!empty($sameAs)) $organizationSchema['sameAs'] = array_values(array_unique($sameAs));
        $schemaData = $organizationSchema;
        if(isset($source) && $source === 'index')
        {
            $schemaData = array(
                '@context' => 'https://schema.org',
                '@graph' => array(
                    $organizationSchema,
                    array(
                        '@type' => 'WebSite',
                        '@id' => $websiteId,
                        'url' => $configBase,
                        'name' => $publicBrandName,
                        'alternateName' => 'PHUCTHINH SOLAR',
                        'publisher' => array('@id' => $organizationId),
                        'inLanguage' => 'vi-VN'
                    )
                )
            );
        }
        else
        {
            $schemaData['@context'] = 'https://schema.org';
        }
    ?>
    <script type="application/ld+json"><?=json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>
<?php } ?>
