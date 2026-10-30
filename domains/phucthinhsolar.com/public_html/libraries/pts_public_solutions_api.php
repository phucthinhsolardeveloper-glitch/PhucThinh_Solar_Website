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

function ptsPublicSolutionsSeoDto(array $seo)
{
    $text = static function($value) {
        if(!is_scalar($value)) return '';
        $value = solutionHubText($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value);
        return trim(is_string($value) ? preg_replace('/\s+/u', ' ', $value) : '');
    };
    return array(
        'title' => $text($seo['title'] ?? ''),
        'description' => $text($seo['description'] ?? ''),
        'keywords' => $text($seo['keywords'] ?? ''),
        'image' => ptsPublicSolutionsPath($seo['image'] ?? '')
    );
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

function ptsPublicSolutionsSlug($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value, " /\t\n\r\0\x0B");
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}


function ptsPublicSolutionsEditorialToc($html)
{
    $html = is_scalar($html) ? (string)$html : '';
    if($html === '') return array('html'=>'','toc'=>array('rendered'=>false,'reason'=>'no_editorial_content'));
    $items = array();
    $index = 0;
    $rendered = preg_replace_callback('~<h([23])([^>]*)>(.*?)</h\1>~is', function($match) use (&$items, &$index) {
        if($index >= 30) return $match[0];
        $label = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        if($label === '') return $match[0];
        $index++;
        $id = 'solution-section-'.$index;
        $level = (int)$match[1];
        $attrs = preg_replace('~\s+id\s*=\s*(["\']).*?\1~i', '', $match[2]);
        $items[] = array('id'=>$id,'label'=>$label,'level'=>$level);
        return '<h'.$level.$attrs.' id="'.$id.'">'.$match[3].'</h'.$level.'>';
    }, $html);
    if(!is_string($rendered)) $rendered = $html;
    return array(
        'html'=>$rendered,
        'toc'=>$items
            ? array('rendered'=>true,'items'=>$items)
            : array('rendered'=>false,'reason'=>'no_editorial_headings')
    );
}

function ptsPublicSolutionCategoryDto(array $group, array $groups, array $solutionRows, array $seo = array())
{
    $id = isset($group['id']) ? (int)$group['id'] : 0;
    $slug = ptsPublicSolutionsSlug(isset($group['slugvi']) ? $group['slugvi'] : '');
    $name = solutionHubText(isset($group['namevi']) ? $group['namevi'] : '');
    if($id <= 0 || $slug === '' || $name === '') return null;

    $cards = array();
    foreach(solutionHubSelect($solutionRows) as $row)
    {
        if(!is_array($row) || (int)(isset($row['id_list']) ? $row['id_list'] : 0) !== $id) continue;
        $itemSlug = ptsPublicSolutionsSlug(solutionHubField($row, 'slug', 'vi'));
        if($itemSlug === '') continue;
        $cards[] = array(
            'title' => solutionHubField($row, 'name', 'vi'),
            'description' => solutionHubField($row, 'desc', 'vi'),
            'slug' => $itemSlug,
            'url' => '/giai-phap/'.$itemSlug.'/',
            'image' => ptsPublicSolutionsPath(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : ''),
            'actionLabel' => 'Xem giải pháp'
        );
    }
    if(!$cards) return null;

    $navigation = array();
    $featuredRows = solutionHubSelect($solutionRows, 'solutionhub');
    foreach($groups as $navGroup)
    {
        if(!is_array($navGroup)) continue;
        $navSlug = ptsPublicSolutionsSlug(isset($navGroup['slugvi']) ? $navGroup['slugvi'] : '');
        $navId = isset($navGroup['id']) ? (int)$navGroup['id'] : 0;
        $navName = solutionHubText(isset($navGroup['namevi']) ? $navGroup['namevi'] : '');
        if($navId <= 0 || $navSlug === '' || $navName === '') continue;
        $url = '/danh-muc-giai-phap/'.$navSlug.'/';
        foreach($featuredRows as $featuredRow)
        {
            if(!is_array($featuredRow) || (int)(isset($featuredRow['id_list']) ? $featuredRow['id_list'] : 0) !== $navId) continue;
            $representativeSlug = ptsPublicSolutionsSlug(solutionHubField($featuredRow, 'slug', 'vi'));
            if($representativeSlug !== '') $url = '/giai-phap/'.$representativeSlug.'/';
            break;
        }
        $navigation[] = array('id'=>$navId, 'name'=>$navName, 'slug'=>$navSlug, 'url'=>$url, 'active'=>$navId === $id);
    }

    $description = solutionHubText(isset($group['descvi']) ? $group['descvi'] : '');
    if($description === '') $description = 'Khám phá các giải pháp phù hợp với nhu cầu thực tế và quy mô công trình của bạn.';
    return array(
        'id' => $id,
        'name' => $name,
        'description' => $description,
        'seo' => ptsPublicSolutionsSeoDto($seo),
        'slug' => $slug,
        'canonicalPath' => '/'.$slug,
        'cards' => $cards,
        'navigation' => $navigation
    );
}

function ptsPublicSolutionsPayload(array $solutionRows, array $introRows, array $ctaRows, array $productRows, $hotline = '', array $fallbackImages = array(), $ctaFallbackImage = 'assets/images/site-visuals/catalog-street-lighting.jpg', array $seo = array())
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
    $editorialHtml = $visibleIntroRows
        ? ptsPublicRichTextSanitize(isset($visibleIntroRows[0]['contentvi']) ? $visibleIntroRows[0]['contentvi'] : '')
        : '';
    $editorial = ptsPublicSolutionsEditorialToc($editorialHtml);
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
        'seo' => ptsPublicSolutionsSeoDto($seo),
        'category' => null,
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
            'content' => array(
                'format' => 'sanitized-html',
                'html' => $editorial['html'],
                'sanitizedBy' => 'pts_public_richtext',
                'sanitizerVersion' => 1
            ),
            'toc' => $editorial['toc']
        )
    );
}
