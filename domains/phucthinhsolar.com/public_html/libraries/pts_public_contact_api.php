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
    if(preg_match('~<iframe\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>~i', $value, $match)) $value = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
    if(!preg_match('~^https://([^/]+)(/[^\s]*)?$~i', $value, $match)) return '';
    $host = strtolower(rtrim($match[1], '.'));
    $allowed = array('www.google.com', 'google.com', 'maps.google.com', 'www.google.com.vn', 'google.com.vn');
    if(!in_array($host, $allowed, true)) return '';
    $path = isset($match[2]) ? $match[2] : '/';
    if(stripos($path, '/maps') !== 0) return '';
    return 'https://'.$host.$path;
}

function ptsPublicContactPayload(array $input)
{
    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'path' => '/lien-he/',
        'seo' => array(
            'title' => ptsContactText($input['seo']['title'] ?? ''),
            'description' => ptsContactText($input['seo']['description'] ?? '')
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
