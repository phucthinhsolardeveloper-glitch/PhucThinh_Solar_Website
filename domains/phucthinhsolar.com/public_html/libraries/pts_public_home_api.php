<?php
/** Read-only, allowlisted projection for the homepage Nuxt pilot. */
function ptsHomeText($value)
{
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function ptsHomePath($filename, $upload)
{
    if(!is_scalar($filename) || basename((string)$filename) !== (string)$filename) return '';
    $filename = trim((string)$filename);
    if($filename === '' || !preg_match('/\.(?:png|jpe?g|gif|webp)$/i', $filename)) return '';
    $relative = ltrim((string)$upload, '/').$filename;
    if(!defined('ROOT') || !is_file(rtrim(ROOT, '/\\').'/'.$relative)) return '';
    return '/'.$relative;
}

function ptsHomeUrl($value, $fallback = '')
{
    if(!is_scalar($value)) return $fallback;
    $value = trim((string)$value);
    if($value === '') return $fallback;
    if(preg_match('~[\x00-\x20<>"`]|(?:^|/)\.\.(?:/|$)|^(?:javascript|data|vbscript):~i', $value)) return $fallback;
    if(preg_match('~^https?://~i', $value)) return $value;
    if($value[0] !== '/') $value = '/'.$value;
    return $value;
}

function ptsHomeRowVisible(array $row)
{
    return strpos(','.(string)($row['status'] ?? '').',', ',hienthi,') !== false;
}

function ptsHomeLink($label, $href)
{
    return array('label' => ptsHomeText($label), 'href' => ptsHomeUrl($href, '/'));
}

/* Admin "Popup quảng cáo": the key changes whenever the record, its photo or
   its update time changes, so an edited campaign shows again once per session. */
function ptsPublicHomePopup($row)
{
    if(!is_array($row) || !$row) return null;
    $image = ptsHomePath($row['photo'] ?? '', defined('UPLOAD_PHOTO_L') ? UPLOAD_PHOTO_L : 'upload/photo/');
    if($image === '') return null;
    return array(
        'key' => substr(hash('sha256', ($row['id'] ?? '').'|'.($row['date_updated'] ?? '').'|'.($row['photo'] ?? '')), 0, 20),
        'image' => $image,
        'title' => ptsHomeText($row['namevi'] ?? ''),
        'href' => ptsHomeUrl($row['link'] ?? '')
    );
}

/* Safety cap for Admin text: cuts on a word boundary and marks the cut; the
   frontend still clamps visually so long copy cannot move the layout. */
function ptsHomeWordCut($value, $limit)
{
    $value = (string)$value;
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if($length <= $limit) return $value;
    $cut = function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
    $space = strrpos($cut, ' ');
    if($space !== false && $space > strlen($cut) / 2) $cut = substr($cut, 0, $space);
    return rtrim($cut, " ,.;:-").'…';
}

/* Admin "Trang chủ - Chuỗi năng lượng": position N overrides card N; empty
   fields stay empty so the frontend keeps its default copy for them. */
function ptsPublicHomeEnergyChain($rows)
{
    $chain = array();
    $cut = static function($value, $limit) { return ptsHomeWordCut(ptsHomeText($value), $limit); };
    foreach((array)$rows as $row) {
        if(!is_array($row) || count($chain) >= 5) continue;
        $chain[] = array(
            'name' => $cut($row['namevi'] ?? '', 80),
            'stage' => $cut($row['title_indexvi'] ?? '', 28),
            'description' => $cut($row['descvi'] ?? '', 220),
            'action' => $cut($row['solieu'] ?? '', 40),
            'image' => ptsHomePath($row['photo'] ?? '', defined('UPLOAD_NEWS_L') ? UPLOAD_NEWS_L : 'upload/news/')
        );
    }
    return $chain;
}

function ptsPublicHomePayload(array $input)
{
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'path' => '/',
        'seo' => array(
            'title' => ptsHomeText($input['seo']['title'] ?? ''),
            'description' => ptsHomeText($input['seo']['description'] ?? ''),
            'keywords' => ptsHomeText($input['seo']['keywords'] ?? ''),
            'image' => ptsHomeText($input['seo']['image'] ?? '')
        ),
        'hero' => $input['hero'],
        'categories' => $input['categories'],
        'campaigns' => $input['campaigns'],
        'solutions' => $input['solutions'],
        'process' => $input['process'],
        'dealer' => $input['dealer'],
        'about' => $input['about'],
        'contact' => $input['contact'],
        'productGroups' => $input['productGroups'],
        'popup' => ptsPublicHomePopup($input['popup'] ?? null),
        'energyChain' => ptsPublicHomeEnergyChain($input['energyChain'] ?? array()),
        'summary' => array(
            'hero' => count($input['hero']),
            'categories' => count($input['categories']),
            'campaigns' => count($input['campaigns']),
            'campaignsConfigured' => !empty($input['campaignsConfigured']) ? 1 : 0,
            'solutions' => count($input['solutions']),
            'process' => count($input['process']),
            'productGroups' => count($input['productGroups'])
        )
    );
}
