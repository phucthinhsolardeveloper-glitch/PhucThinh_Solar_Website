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

function ptsPublicHomePayload(array $input)
{
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'path' => '/',
        'seo' => array('title' => ptsHomeText($input['seo']['title'] ?? ''), 'description' => ptsHomeText($input['seo']['description'] ?? '')),
        'hero' => $input['hero'],
        'categories' => $input['categories'],
        'campaigns' => $input['campaigns'],
        'solutions' => $input['solutions'],
        'process' => $input['process'],
        'dealer' => $input['dealer'],
        'about' => $input['about'],
        'contact' => $input['contact'],
        'productGroups' => $input['productGroups'],
        'summary' => array(
            'hero' => count($input['hero']),
            'categories' => count($input['categories']),
            'campaigns' => count($input['campaigns']),
            'solutions' => count($input['solutions']),
            'process' => count($input['process']),
            'productGroups' => count($input['productGroups'])
        )
    );
}
