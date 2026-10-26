<?php
/**
 * GET /api/pts_v1_site.php (Phase A internal contract; no production routing
 * is moved to Nuxt). Mirrors the published read-only inputs from allpage.php.
 */
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET')
{
    header('Allow: GET');
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}';
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

try
{
    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'pts_public_site_api.php';
    require_once LIBRARIES.'pts_public_navigation_api.php';

    $loadRows = static function($sql, array $params) use ($cache) {
        $rows = $cache->get($sql, $params, 'result', 7200);
        return is_array($rows) ? $rows : array();
    };
    $loadOne = static function($sql, array $params) use ($cache) {
        $row = $cache->get($sql, $params, 'fetch', 7200);
        return is_array($row) ? $row : array();
    };

    $productLists = $loadRows("select pl.id,pl.namevi,pl.slugvi from #_product_list pl where pl.type=? and find_in_set('hienthi',pl.status) and exists (select 1 from #_product p where p.id_list=pl.id and p.type=? and find_in_set('hienthi',p.status)) order by pl.numb,pl.id desc", array('san-pham','san-pham'));
    $productCats = $loadRows("select pc.namevi,pc.slugvi,pc.id,pc.id_list from #_product_cat pc where pc.type=? and find_in_set('hienthi',pc.status) and exists (select 1 from #_product p where p.id_cat=pc.id and p.type=? and find_in_set('hienthi',p.status)) order by pc.numb,pc.id desc", array('san-pham','san-pham'));
    $productItems = $loadRows("select pi.namevi,pi.slugvi,pi.id,pi.id_cat from #_product_item pi where pi.type=? and find_in_set('hienthi',pi.status) and exists (select 1 from #_product p where p.id_item=pi.id and p.type=? and find_in_set('hienthi',p.status)) order by pi.numb,pi.id desc", array('san-pham','san-pham'));
    $productSubs = $loadRows("select ps.namevi,ps.slugvi,ps.id,ps.id_item from #_product_sub ps where ps.type=? and find_in_set('hienthi',ps.status) and exists (select 1 from #_product p where p.id_sub=ps.id and p.type=? and find_in_set('hienthi',p.status)) order by ps.numb,ps.id desc", array('san-pham','san-pham'));
    $solutionRows = $loadRows("select id,namevi,title_indexvi,slugvi,status from #_news where type=? order by numb,id desc", array('giai-phap'));
    $newsLists = $loadRows("select nl.namevi,nl.slugvi,nl.id from #_news_list nl where nl.type=? and find_in_set('hienthi',nl.status) and exists (select 1 from #_news n where n.id_list=nl.id and n.type=? and find_in_set('hienthi',n.status)) order by nl.numb,nl.id desc", array('tin-tuc','tin-tuc'));
    $newsCats = $loadRows("select namevi,slugvi,id,id_list from #_news_cat where type in (?,?) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap','tin-tuc'));
    $newsItems = $loadRows("select namevi,slugvi,id,id_cat from #_news_item where type in (?,?) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap','tin-tuc'));
    $newsSubs = $loadRows("select namevi,slugvi,id,id_item from #_news_sub where type in (?,?) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap','tin-tuc'));
    $policy = $loadRows("select namevi,slugvi,id from #_news where type=? and find_in_set('hienthi',status) order by numb,id desc", array('chinh-sach'));
    $locations = $loadRows("select namevi,address,id,ggmap from #_news where type=? and find_in_set('hienthi',status) order by case when namevi like 'Trụ sở%' then 0 when namevi like 'Kho Gia Lai%' then 1 else 2 end,numb,id desc", array('he-thong-cua-hang'));
    $footer = $loadOne("select namevi,contentvi from #_static where type=? and find_in_set('hienthi',status) limit 0,1", array('footer'));
    $logo = $loadOne("select photo from #_photo where type=? and act=? limit 0,1", array('logo','photo_static'));
    $logoFooter = $loadOne("select photo from #_photo where type=? and act=? limit 0,1", array('logo-footer','photo_static'));
    if(function_exists('closeSessionWrite')) closeSessionWrite();

    $data = ptsPublicSitePayload(is_array($setting) ? $setting : array(), is_array($optsetting) ? $optsetting : array(), $productLists);
    $assets = array(
        'logo' => ptsPublicNavAsset(isset($logo['photo']) ? $logo['photo'] : '', UPLOAD_PHOTO_L, ROOT),
        'logoFooter' => ptsPublicNavAsset(isset($logoFooter['photo']) ? $logoFooter['photo'] : '', UPLOAD_PHOTO_L, ROOT)
    );
    $data = array_merge($data, ptsPublicSiteNavigationPayload(array(
        'setting' => is_array($setting) ? $setting : array(), 'options' => is_array($optsetting) ? $optsetting : array(),
        'asset' => $assets, 'footer' => $footer, 'policy' => $policy, 'locations' => $locations,
        'productLists' => $productLists, 'productCats' => $productCats, 'productItems' => $productItems, 'productSubs' => $productSubs,
        'solutionRows' => $solutionRows, 'newsLists' => $newsLists, 'newsCats' => $newsCats, 'newsItems' => $newsItems, 'newsSubs' => $newsSubs
    )));
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_site: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
