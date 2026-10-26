<?php
/**
 * Safe, read-only DTO for the published /giai-phap/ landing page.
 *
 * The caller resolves uploaded files with solutionHubImage() first and adds
 * only the resulting public paths under _pts_public_* keys. This keeps the
 * DTO deterministic and free of filesystem or database access.
 */
function ptsPublicSolutionsUi()
{
    return array(
        'eyebrow' => 'Giải pháp theo nhu cầu',
        'title' => 'Chọn đúng giải pháp cho nhu cầu của bạn',
        'intro' => 'Khám phá giải pháp năng lượng mặt trời hoặc tìm ngay thiết bị bạn cần. Đội ngũ Phúc Thịnh Solar sẵn sàng hỗ trợ bạn lựa chọn.',
        'choices' => 'Khám phá các giải pháp',
        'choice' => 'Xem giải pháp',
        'equipment' => 'Đi thẳng đến sản phẩm',
        'all_products' => 'Xem tất cả sản phẩm',
        'contact' => 'Chưa biết giải pháp nào phù hợp?',
        'contact_desc' => 'Chia sẻ nhu cầu của bạn. Đội ngũ Phúc Thịnh Solar sẽ hỗ trợ lựa chọn giải pháp phù hợp.',
        'consult' => 'Nhận tư vấn',
        'phone' => 'Gọi ngay hotline',
        'empty' => 'Các giải pháp đang được cập nhật. Hãy liên hệ để được tư vấn theo nhu cầu.'
    );
}

function ptsPublicSolutionsPath($path)
{
    if(!is_scalar($path)) return '';
    $path = trim((string)$path);
    if($path === '' || preg_match('~^(?:https?:)?//|[?#:]|(?:^|/)\.\.(?:/|$)~i', $path)) return '';
    if(!preg_match('~^/?(?:upload|assets)/~i', $path)) return '';
    return '/'.ltrim($path, '/');
}

function ptsPublicSolutionsHotline($hotline)
{
    if(!is_scalar($hotline)) return array('label' => '', 'text' => '', 'tel' => '');
    $text = trim((string)$hotline);
    return array(
        'label' => 'Gọi ngay hotline',
        'text' => $text,
        'tel' => preg_replace('/[^0-9+]/', '', $text)
    );
}

function ptsPublicSolutionsPayload(array $solutionRows, array $introRows, array $ctaRows, array $productRows, $hotline = '', array $fallbackImages = array(), $ctaFallbackImage = 'assets/images/site-visuals/catalog-street-lighting.jpg')
{
    $ui = ptsPublicSolutionsUi();
    $lang = 'vi';

    // Keep the exact publish/featured semantics from solution_hub.php.
    $featuredRows = solutionHubSelect($solutionRows, 'solutionhub');
    $cards = array();
    foreach($featuredRows as $row)
    {
        if(!is_array($row) || solutionHubField($row, 'slug', $lang) === '') continue;

        $slug = solutionHubField($row, 'slug', $lang);
        $image = ptsPublicSolutionsPath(isset($row['_pts_public_photo1']) ? $row['_pts_public_photo1'] : '');
        if($image === '') $image = ptsPublicSolutionsPath(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '');
        if($image === '' && isset($fallbackImages[$slug])) $image = ptsPublicSolutionsPath($fallbackImages[$slug]);

        $cards[] = array(
            'title' => solutionHubNavName($row, $lang),
            'description' => solutionHubField($row, 'desc', $lang),
            'slug' => $slug,
            'url' => '/giai-phap/'.$slug.'/',
            'image' => $image,
            'actionLabel' => $ui['choice']
        );
    }

    $visibleIntroRows = solutionHubSelect($introRows, '', 1);
    $visibleCtaRows = solutionHubSelect($ctaRows, '', 1);
    $intro = null;
    $title = $ui['title'];
    if(!$introRows || $visibleIntroRows)
    {
        $row = $visibleIntroRows ? $visibleIntroRows[0] : array();
        $title = solutionHubField($row, 'name', $lang) ?: $ui['title'];
        $heroImage = ptsPublicSolutionsPath(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '');
        if(!$introRows && $heroImage === '')
        {
            foreach($featuredRows as $featuredRow)
            {
                if(!is_array($featuredRow) || solutionHubField($featuredRow, 'slug', $lang) === '') continue;
                $heroImage = ptsPublicSolutionsPath(isset($featuredRow['_pts_public_photo1']) ? $featuredRow['_pts_public_photo1'] : '');
                if($heroImage === '') $heroImage = ptsPublicSolutionsPath(isset($featuredRow['_pts_public_photo']) ? $featuredRow['_pts_public_photo'] : '');
                if($heroImage !== '') break;
            }
        }
        $intro = array(
            'eyebrow' => solutionHubField($row, 'title_index', $lang) ?: $ui['eyebrow'],
            'title' => $title,
            'description' => solutionHubField($row, 'desc', $lang) ?: $ui['intro'],
            'image' => $heroImage,
            'links' => array(
                'consult' => array('label' => $ui['consult'], 'href' => '#ptsContactModal'),
                'explore' => array('label' => $ui['choices'], 'href' => '/giai-phap/#cac-giai-phap')
            )
        );
    }

    $equipment = array();
    foreach(solutionHubSelect($productRows, 'solutionhub', 4) as $row)
    {
        if(!is_array($row)) continue;
        $categoryLang = !empty($row['slug'.$lang]) ? $lang : 'vi';
        $equipment[] = array(
            'name' => solutionHubField($row, 'name', $lang),
            'description' => solutionHubField($row, 'desc', $lang),
            'url' => '/'.ltrim(publicProductCategoryPath($row, 'list', $categoryLang), '/'),
            'image' => ptsPublicSolutionsPath(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '')
        );
    }

    $cta = null;
    if(!$ctaRows || $visibleCtaRows)
    {
        $row = $visibleCtaRows ? $visibleCtaRows[0] : array();
        $ctaImage = ptsPublicSolutionsPath(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '');
        if($ctaImage === '') $ctaImage = ptsPublicSolutionsPath($ctaFallbackImage);
        $hotlineData = ptsPublicSolutionsHotline($hotline);
        if($hotlineData['tel'] === '') $hotlineData = null;
        $cta = array(
            'title' => solutionHubField($row, 'name', $lang) ?: $ui['contact'],
            'description' => solutionHubField($row, 'desc', $lang) ?: $ui['contact_desc'],
            'buttonLabel' => solutionHubField($row, 'title_index', $lang) ?: $ui['consult'],
            'image' => $ctaImage,
            'links' => array(
                'consult' => array('label' => solutionHubField($row, 'title_index', $lang) ?: $ui['consult'], 'href' => '#ptsContactModal')
            ),
            'hotline' => $hotlineData
        );
    }

    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'path' => '/giai-phap/',
        'title' => $title,
        'intro' => $intro,
        'cards' => $cards,
        'equipment' => $equipment,
        'allProductsUrl' => '/san-pham/',
        'labels' => array(
            'choices' => $ui['choices'],
            'equipment' => $ui['equipment'],
            'allProducts' => $ui['all_products'],
            'empty' => $ui['empty'],
            'consult' => $ui['consult'],
            'phone' => $ui['phone']
        ),
        'cta' => $cta,
        'editorial' => array(
            'rendered' => false,
            'reason' => 'rich_text_deferred_until_sanitizer_gate'
        )
    );
}
