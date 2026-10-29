<?php
/** GET-only public slug classifier matching the public router's ordered resolver. */
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
header('X-Robots-Tag: noindex, nofollow, noarchive');

function ptsPublicRouteResolveError($status, $code)
{
    http_response_code($status);
    echo json_encode(array('error' => array('code' => $code)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ptsPublicRouteResolveMap()
{
    /* Keep this ordered map aligned with $requick in libraries/router.php. */
    return array(
        array('tbl'=>'product_list', 'field'=>'idl', 'com'=>'san-pham', 'type'=>'san-pham'),
        array('tbl'=>'product_cat', 'field'=>'idc', 'com'=>'san-pham', 'type'=>'san-pham'),
        array('tbl'=>'product_item', 'field'=>'idi', 'com'=>'san-pham', 'type'=>'san-pham'),
        array('tbl'=>'product_sub', 'field'=>'ids', 'com'=>'san-pham', 'type'=>'san-pham'),
        array('tbl'=>'product_brand', 'field'=>'idb', 'com'=>'san-pham', 'type'=>'san-pham'),
        array('tbl'=>'product', 'field'=>'id', 'com'=>'san-pham', 'type'=>'san-pham'),
        array('tbl'=>'product', 'field'=>'id', 'com'=>'thu-vien-anh', 'type'=>'thu-vien-anh'),
        array('tbl'=>'photo', 'field'=>'id', 'com'=>'video', 'type'=>'video'),
        array('tbl'=>'news_list', 'field'=>'idl', 'com'=>'tin-tuc', 'type'=>'tin-tuc'),
        array('tbl'=>'news_cat', 'field'=>'idc', 'com'=>'tin-tuc', 'type'=>'tin-tuc'),
        array('tbl'=>'news_item', 'field'=>'idi', 'com'=>'tin-tuc', 'type'=>'tin-tuc'),
        array('tbl'=>'news_sub', 'field'=>'ids', 'com'=>'tin-tuc', 'type'=>'tin-tuc'),
        array('tbl'=>'news', 'field'=>'id', 'com'=>'tin-tuc', 'type'=>'tin-tuc'),
        array('tbl'=>'news_list', 'field'=>'idl', 'com'=>'giai-phap', 'type'=>'giai-phap'),
        array('tbl'=>'news_cat', 'field'=>'idc', 'com'=>'giai-phap', 'type'=>'giai-phap'),
        array('tbl'=>'news_item', 'field'=>'idi', 'com'=>'giai-phap', 'type'=>'giai-phap'),
        array('tbl'=>'news_sub', 'field'=>'ids', 'com'=>'giai-phap', 'type'=>'giai-phap'),
        array('tbl'=>'news', 'field'=>'id', 'com'=>'giai-phap', 'type'=>'giai-phap'),
        array('tbl'=>'news_list', 'field'=>'idl', 'com'=>'du-an', 'type'=>'du-an'),
        array('tbl'=>'news_cat', 'field'=>'idc', 'com'=>'du-an', 'type'=>'du-an'),
        array('tbl'=>'news_item', 'field'=>'idi', 'com'=>'du-an', 'type'=>'du-an'),
        array('tbl'=>'news_sub', 'field'=>'ids', 'com'=>'du-an', 'type'=>'du-an'),
        array('tbl'=>'news', 'field'=>'id', 'com'=>'du-an', 'type'=>'du-an'),
        array('tbl'=>'news', 'field'=>'id', 'com'=>'chinh-sach', 'type'=>'chinh-sach'),
        array('tbl'=>'static', 'field'=>'id', 'com'=>'gioi-thieu', 'type'=>'gioi-thieu'),
        array('tbl'=>'', 'field'=>'id', 'com'=>'lien-he', 'type'=>''),
    );
}

function ptsPublicRouteResolveSlugValue($d, $table, $id, $type)
{
    $tables = array('product'=>'#_product', 'product_brand'=>'#_product_brand', 'news'=>'#_news');
    if(!isset($tables[$table])) return '';
    $row = $d->rawQueryOne(
        "select slugvi from {$tables[$table]} where id = ? and type = ? and find_in_set('hienthi', status) limit 1",
        array((int)$id, $type)
    );
    if(empty($row['slugvi']) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $row['slugvi'])) return '';
    return $row['slugvi'];
}

function ptsPublicRouteResolvePayload($d, array $match, $slug)
{
    $id = isset($match['id']) ? (int)$match['id'] : 0;
    $field = isset($match['route_field']) ? (string)$match['route_field'] : '';
    $com = isset($match['route_com']) ? (string)$match['route_com'] : '';
    if($id < 1) return array();

    $kind = 'legacy-only';
    $canonicalPath = null;
    $reason = 'unsupported-taxonomy';

    if($com === 'san-pham' && in_array($field, array('idl','idc','idi','ids'), true))
    {
        $tableByField = array('idl'=>'product_list', 'idc'=>'product_cat', 'idi'=>'product_item', 'ids'=>'product_sub');
        $relativePath = publicProductEntityPath($d, $tableByField[$field], $id, 'vi');
        $segments = explode('/', trim($relativePath, '/'));
        $resolved = array();
        if(count($segments) >= 2 && $segments[0] === 'danh-muc-san-pham')
        {
            $resolved = publicResolveProductCategoryPath($d, array_slice($segments, 1), 'vi');
        }
        if(!empty($resolved[$field]) && (int)$resolved[$field] === $id && preg_match('~^danh-muc-san-pham/(?:[a-z0-9]+(?:-[a-z0-9]+)*/){1,4}$~', $relativePath))
        {
            $kind = 'category';
            $canonicalPath = '/'.$relativePath;
            $reason = null;
        }
        else
        {
            $reason = 'unsupported-taxonomy';
        }
    }
    elseif($com === 'san-pham' && $field === 'idb')
    {
        $slug = ptsPublicRouteResolveSlugValue($d, 'product_brand', $id, 'san-pham');
        if($slug !== '')
        {
            $kind = 'brand';
            $canonicalPath = '/thuong-hieu/'.$slug.'/';
            $reason = null;
        }
        else
        {
            $reason = 'unsupported-route';
        }
    }
    elseif($com === 'du-an' && $field === 'idl')
    {
        /* Project-list filters keep their legacy alias, while PHP canonicalizes
           them to the bare category slug (without a trailing slash). */
        $relativePath = publicCanonicalEntityPath($d, $com, array('idl' => $id), 'vi');
        if(preg_match('~^[a-z0-9]+(?:-[a-z0-9]+)*$~', $relativePath))
        {
            $kind = 'project-category';
            $canonicalPath = '/'.$relativePath;
            $reason = null;
        }
        else
        {
            $reason = 'unsupported-route';
        }
    }
    elseif($field === 'idl' && in_array($com, array('tin-tuc', 'giai-phap'), true))
    {
        /* Editorial category routes are public only when the PHP listing has
           at least one published, slugged entry that the Nuxt GET DTO can show. */
        $category = $d->rawQueryOne(
            "select id from #_news_list where id=? and type=? and find_in_set('hienthi',status) and exists(select 1 from #_news n where n.id_list=#_news_list.id and n.type=? and find_in_set('hienthi',n.status) and n.slugvi<>'') limit 1",
            array($id, $com, $com)
        );
        $relativePath = $category ? publicCanonicalEntityPath($d, $com, array('idl' => $id), 'vi') : '';
        if($relativePath !== '' && $relativePath === $slug && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $relativePath))
        {
            $kind = $com === 'tin-tuc' ? 'news-category' : 'solution-category';
            $canonicalPath = '/'.$relativePath;
            $reason = null;
        }
        else
        {
            $reason = 'unsupported-route';
        }
    }
    elseif($field === 'id')
    {
        if($com === 'san-pham')
        {
            $relativePath = publicProductEntityPath($d, 'product', $id, 'vi');
            if(preg_match('~^san-pham/[a-z0-9]+(?:-[a-z0-9]+)*$~', $relativePath))
            {
                $kind = 'pdp';
                $canonicalPath = '/'.$relativePath;
                $reason = null;
            }
            else $reason = 'unsupported-route';
        }
        elseif($com === 'thu-vien-anh')
        {
            $slug = ptsPublicRouteResolveSlugValue($d, 'product', $id, 'thu-vien-anh');
            if($slug !== '')
            {
                $kind = 'gallery';
                $canonicalPath = '/'.$slug.'/';
                $reason = null;
            }
            else $reason = 'unsupported-route';
        }
        elseif($com === 'tin-tuc' || $com === 'du-an')
        {
            $type = $com === 'tin-tuc' ? 'tin-tuc' : 'du-an';
            $slug = ptsPublicRouteResolveSlugValue($d, 'news', $id, $type);
            if($slug !== '')
            {
                $kind = $com === 'tin-tuc' ? 'news' : 'project';
                $canonicalPath = '/'.$slug.'/';
                $reason = null;
            }
            else $reason = 'unsupported-route';
        }
        elseif($com === 'giai-phap')
        {
            $relativePath = publicProductEntityPath($d, 'news_giai_phap', $id, 'vi');
            if(preg_match('~^giai-phap/[a-z0-9]+(?:-[a-z0-9]+)*/$~', $relativePath))
            {
                $kind = 'solution';
                $canonicalPath = '/'.$relativePath;
                $reason = null;
            }
            else $reason = 'unsupported-route';
        }
        elseif($com === 'chinh-sach')
        {
            $relativePath = publicProductEntityPath($d, 'news_chinh_sach', $id, 'vi');
            if(preg_match('~^ho-tro/[a-z0-9]+(?:-[a-z0-9]+)*/$~', $relativePath))
            {
                $kind = 'policy';
                $canonicalPath = '/'.$relativePath;
                $reason = null;
            }
            else $reason = 'unsupported-route';
        }
        else
        {
            $reason = 'unsupported-route';
        }
    }

    $payload = array('schemaVersion'=>1, 'slug'=>$slug, 'kind'=>$kind, 'canonicalPath'=>$canonicalPath);
    if($reason !== null) $payload['reason'] = $reason;
    return $payload;
}

function ptsPublicRouteResolveIsReservedPath($slug)
{
    /* These paths match explicit router rules or static page components before
       the generic one-segment slug route, so their CMS slug collision is moot. */
    $reserved = array(
        'he-thong-dai-ly', 've-chung-toi', 'product', 'index', 'sitemap',
        'lien-he', 'gioi-thieu', 'dai-ly', 'tin-tuc', 'giai-phap', 'du-an',
        'du-an-da-thuc-hien', 'chinh-sach', 'thuong-hieu', 'san-pham',
        'tim-kiem', 'thu-vien-anh', 'video', 'gio-hang', 'cam-on',
        'account', 'ngon-ngu',
    );
    return in_array($slug, $reserved, true);
}

try
{
    if(count($_GET) !== 1 || !isset($_GET['slug']) || !is_string($_GET['slug'])) ptsPublicRouteResolveError(400, 'BAD_QUERY');
    $slug = $_GET['slug'];
    if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) ptsPublicRouteResolveError(400, 'BAD_QUERY');

    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'product_category_urls.php';
    require_once LIBRARIES.'public_route_resolver.php';

    if(ptsPublicRouteResolveIsReservedPath($slug))
    {
        echo json_encode(array('schemaVersion'=>1, 'slug'=>$slug, 'kind'=>'legacy-only', 'canonicalPath'=>null, 'reason'=>'unsupported-route'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    $match = publicRouteResolveSlug($d, $slug, 'slugvi', ptsPublicRouteResolveMap());
    if(empty($match['id'])) ptsPublicRouteResolveError(404, 'PUBLIC_ROUTE_NOT_FOUND');
    $payload = ptsPublicRouteResolvePayload($d, $match, $slug);
    if(empty($payload)) ptsPublicRouteResolveError(404, 'PUBLIC_ROUTE_NOT_FOUND');

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_public_route_resolve: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
