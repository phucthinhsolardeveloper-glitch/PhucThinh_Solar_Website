<?php
/** Read-only, sanitized projection for the Nuxt contact migration preview. */
function ptsContactScalar($value)
{
    return is_scalar($value) ? trim((string)$value) : '';
}

function ptsContactText($value)
{
    $value = html_entity_decode(ptsContactScalar($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/?p|/?div|/?li)\b[^>]*>~i', ' ', $value);
    $value = strip_tags($value);
    return trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value));
}

function ptsContactAsset($filename, $upload, $root)
{
    $filename = ptsContactScalar($filename);
    $upload = trim((string)$upload, '/');
    if($filename === '' || $upload === '' || basename($filename) !== $filename) return '';
    if(!preg_match('/\.(?:png|jpe?g|gif|webp)$/i', $filename)) return '';
    $relative = $upload.'/'.$filename;
    return is_file(rtrim($root, '/\\').'/'.$relative) ? '/'.$relative : '';
}

function ptsContactHref($value, $fallback = '')
{
    $value = ptsContactScalar($value);
    if($value === '' || preg_match('~[\x00-\x20<>"\']|\.\.~', $value)) return $fallback;
    if(strpos($value, '#') === 0 || strpos($value, '/') === 0) return $value;
    return $fallback;
}

function ptsContactMapUrl($value)
{
    $value = ptsContactScalar($value);
    if($value === '') return '';
    // The legacy template decodes this CMS field before emitting the iframe;
    // normalize the same HTML-escaped representation before extracting only
    // its src. The returned DTO never contains markup.
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if(preg_match('~<iframe\b[^>]*\bsrc\s*=\s*(["\'])([^"\']+)\1[^>]*>~i', $value, $match)) {
        $value = html_entity_decode(trim($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if(preg_match('/[\x00-\x20\x7f<>]/', $value)) return '';
    $parts = parse_url($value);
    if(!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return '';
    $host = strtolower(rtrim($parts['host'] ?? '', '.'));
    $allowed = array('www.google.com', 'google.com', 'maps.google.com', 'www.google.com.vn', 'google.com.vn');
    if(!in_array($host, $allowed, true)) return '';
    $path = $parts['path'] ?? '/';
    if(!preg_match('~^/maps(?:/|$)~i', $path)) return '';
    $url = 'https://'.$host.$path;
    if(isset($parts['query'])) $url .= '?'.$parts['query'];
    if(isset($parts['fragment'])) $url .= '#'.$parts['fragment'];
    return $url;
}

function ptsPublicContactPayload(array $input)
{
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'path' => '/lien-he/',
        'seo' => array(
            'title' => ptsContactText($input['seo']['title'] ?? ''),
            'description' => ptsContactText($input['seo']['description'] ?? ''),
            'keywords' => ptsContactText($input['seo']['keywords'] ?? ''),
            'image' => is_scalar($input['seo']['image'] ?? null) ? trim((string)$input['seo']['image']) : ''
        ),
        'title' => ptsContactText($input['title'] ?? '') ?: 'Liên hệ Phúc Thịnh Solar',
        'intro' => ptsContactText($input['intro'] ?? ''),
        'visual' => $input['visual'] ?? null,
        'contact' => array(
            'company' => ptsContactText($input['contact']['company'] ?? ''),
            'hotline' => ptsContactText($input['contact']['hotline'] ?? ''),
            'email' => ptsContactText($input['contact']['email'] ?? ''),
            'address' => ptsContactText($input['contact']['address'] ?? ''),
            'workingHours' => ptsContactText($input['contact']['workingHours'] ?? '')
        ),
        'locations' => $input['locations'] ?? array(),
        'labels' => array(
            'locations' => 'Hệ thống chi nhánh',
            'formCta' => 'Đăng ký nhận tư vấn',
            'formNote' => 'Thông tin được gửi qua biểu mẫu bảo mật và lưu vào hệ thống để đội ngũ Phúc Thịnh Solar liên hệ lại.'
        )
    );
}
