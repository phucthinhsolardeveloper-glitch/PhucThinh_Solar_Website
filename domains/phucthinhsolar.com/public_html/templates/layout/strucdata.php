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
                "name": "Google",
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
                "name": "Google",
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
    <script type="application/ld+json">
        {
            "@context" : "https://schema.org",
            "@type" : "Organization",
            "name" : "<?=@$setting['name'.$lang]?>",
            "url" : "<?=$configBase?>",
            "sameAs" :
            [
                <?php if(!empty($social)) { $sum_social = count($social); foreach($social as $key => $value) { ?>
                    "<?=@$value['link']?>"<?=(($key+1)<$sum_social)?',':''?>
                <?php } } ?>
            ],
            "address":
            {
                "@type": "PostalAddress",
                "streetAddress": "<?=$optsetting['address']?>",
                "addressRegion": "Ho Chi Minh",
                "postalCode": "70000",
                "addressCountry": "vi"
            }
        }
    </script>
<?php } ?>
