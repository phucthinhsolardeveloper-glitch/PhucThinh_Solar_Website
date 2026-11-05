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
