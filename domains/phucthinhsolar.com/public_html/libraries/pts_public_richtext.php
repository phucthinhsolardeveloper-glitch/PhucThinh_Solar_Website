<?php
/**
 * Shared sanitizer for CKEditor HTML crossing from PHP CMS into public clients.
 *
 * The helper is intentionally fail-closed for media URLs and executable markup.
 * It decodes the CMS's one HTML-entity encoding layer, sanitizes a DOM fragment,
 * and returns HTML that is safe for a consumer which has explicitly opted into
 * the `sanitized-html` contract.
 */

function ptsPublicRichTextAllowedTags()
{
    return array(
        'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'mark', 'sub', 'sup',
        'a', 'ul', 'ol', 'li',
        'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'figure', 'figcaption', 'img'
    );
}

function ptsPublicRichTextBlockedTags()
{
    return array(
        'applet', 'audio', 'base', 'button', 'canvas', 'embed', 'form', 'head',
        'iframe', 'input', 'link', 'math', 'meta', 'noscript', 'object', 'option',
        'script', 'select', 'source', 'style', 'svg', 'template', 'textarea',
        'title', 'track', 'video'
    );
}

function ptsPublicRichTextDecodeCmsHtml($value)
{
    if(!is_scalar($value)) return '';
    return (string)htmlspecialchars_decode((string)$value, ENT_QUOTES | ENT_HTML5);
}

function ptsPublicRichTextHasUnsafePath($path)
{
    if(!is_string($path) || $path === '') return true;
    if(preg_match('/[\x00-\x20\x7F<>"]/', $path) || strpos($path, chr(92)) !== false) return true;
    $decoded = rawurldecode($path);
    if(strpos($decoded, '..') !== false) return true;
    return false;
}

function ptsPublicRichTextPathSuffix(array $parts)
{
    $suffix = '';
    if(isset($parts['query']) && $parts['query'] !== '') $suffix .= '?'.$parts['query'];
    if(isset($parts['fragment']) && $parts['fragment'] !== '') $suffix .= '#'.$parts['fragment'];
    return $suffix;
}

function ptsPublicRichTextIsCmsHost($host)
{
    return in_array(strtolower((string)$host), array('phucthinhsolar.com', 'www.phucthinhsolar.com'), true);
}

function ptsPublicRichTextNormalizeInternalPath($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value);
    if($value === '' || strpos($value, '//') === 0 || ptsPublicRichTextHasUnsafePath($value)) return '';
    if($value[0] !== '/') $value = '/'.$value;

    $parts = parse_url($value);
    if(!is_array($parts) || !isset($parts['path'])) return '';
    $path = '/'.ltrim((string)$parts['path'], '/');
    if(ptsPublicRichTextHasUnsafePath($path)) return '';
    return $path.ptsPublicRichTextPathSuffix($parts);
}

function ptsPublicRichTextNormalizeHref($value)
{
    if(!is_scalar($value)) return '';
    $value = trim(html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if($value === '' || preg_match('/[\x00-\x20\x7F<>"]/', $value) || strpos($value, chr(92)) !== false) return '';
    if(preg_match('/^[a-z][a-z0-9+.-]*:/i', $value))
    {
        $parts = parse_url($value);
        if(!is_array($parts)) return '';
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if(in_array($scheme, array('mailto', 'tel'), true)) return $value;
        if(!in_array($scheme, array('http', 'https'), true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || empty($parts['host'])) return '';
        if(ptsPublicRichTextIsCmsHost($parts['host']))
        {
            return ptsPublicRichTextNormalizeInternalPath(
                (string)($parts['path'] ?? '/').ptsPublicRichTextPathSuffix($parts)
            );
        }
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
    }
    if($value[0] === '#') return preg_match('/^#[A-Za-z0-9_.:-]+$/', $value) ? $value : '';
    return ptsPublicRichTextNormalizeInternalPath($value);
}

function ptsPublicRichTextNormalizeImageUrl($value)
{
    if(!is_scalar($value)) return '';
    $value = trim(html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if($value === '' || preg_match('/[\x00-\x20\x7F<>"]/', $value) || strpos($value, chr(92)) !== false || strpos($value, '//') === 0) return '';

    $parts = parse_url($value);
    if(!is_array($parts)) return '';
    if(isset($parts['scheme']))
    {
        if(!in_array(strtolower((string)$parts['scheme']), array('http', 'https'), true)
            || !ptsPublicRichTextIsCmsHost($parts['host'] ?? '')
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return '';
    }
    $path = '/'.ltrim((string)($parts['path'] ?? ''), '/');
    if(ptsPublicRichTextHasUnsafePath($path)) return '';
    if(!preg_match('#^/upload(?:/[A-Za-z0-9._~%-]+)+\.(?:jpe?g|png|gif|webp|avif)$#i', $path)) return '';
    return $path;
}

function ptsPublicRichTextSafeTokenList($value)
{
    if(!is_scalar($value)) return '';
    $tokens = preg_split('/\s+/', trim((string)$value), -1, PREG_SPLIT_NO_EMPTY);
    $safe = array();
    foreach($tokens as $token)
    {
        if(preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $token)) $safe[] = $token;
    }
    return implode(' ', array_values(array_unique($safe)));
}

function ptsPublicRichTextSafeInteger($value, $min = 1, $max = 100)
{
    if(!is_scalar($value) || !preg_match('/^\d+$/', (string)$value)) return '';
    $value = (int)$value;
    return $value >= $min && $value <= $max ? (string)$value : '';
}

function ptsPublicRichTextAllowedAttributes($tag)
{
    $common = array('id');
    $map = array(
        'h2' => array_merge($common, array('dir')),
        'h3' => array_merge($common, array('dir')),
        'h4' => array_merge($common, array('dir')),
        'h5' => array_merge($common, array('dir')),
        'h6' => array_merge($common, array('dir')),
        'p' => $common,
        'a' => array('href', 'title', 'target', 'rel'),
        'ol' => array('start', 'reversed', 'type'),
        'li' => array('value'),
        'blockquote' => array('cite'),
        'th' => array('colspan', 'rowspan', 'scope', 'headers'),
        'td' => array('colspan', 'rowspan', 'headers'),
        'img' => array('src', 'alt', 'title', 'width', 'height', 'loading', 'decoding', 'aria-describedby'),
    );
    return $map[$tag] ?? array();
}

function ptsPublicRichTextSanitizeAttributes(DOMElement $element, $tag)
{
    $allowed = ptsPublicRichTextAllowedAttributes($tag);
    $attributes = array();
    foreach($element->attributes as $attribute) $attributes[] = $attribute;
    foreach($attributes as $attribute)
    {
        $name = strtolower($attribute->name);
        if(!in_array($name, $allowed, true))
        {
            $element->removeAttributeNode($attribute);
            continue;
        }
        $value = $attribute->value;
        $safe = $value;
        if($name === 'id') $safe = preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $value) ? $value : '';
        elseif($name === 'dir') $safe = in_array(strtolower($value), array('ltr', 'rtl', 'auto'), true) ? strtolower($value) : '';
        elseif($name === 'href') $safe = ptsPublicRichTextNormalizeHref($value);
        elseif($name === 'cite') $safe = ptsPublicRichTextNormalizeHref($value);
        elseif($name === 'src') $safe = ptsPublicRichTextNormalizeImageUrl($value);
        elseif(in_array($name, array('width', 'height', 'colspan', 'rowspan', 'start', 'value'), true)) $safe = ptsPublicRichTextSafeInteger($value);
        elseif($name === 'loading') $safe = in_array(strtolower($value), array('lazy', 'eager'), true) ? strtolower($value) : '';
        elseif($name === 'decoding') $safe = in_array(strtolower($value), array('async', 'auto', 'sync'), true) ? strtolower($value) : '';
        elseif($name === 'target') $safe = in_array($value, array('_blank', '_self'), true) ? $value : '';
        elseif($name === 'rel') $safe = ptsPublicRichTextSafeTokenList($value);
        elseif($name === 'scope') $safe = in_array(strtolower($value), array('col', 'row', 'colgroup', 'rowgroup'), true) ? strtolower($value) : '';
        elseif($name === 'headers' || $name === 'aria-describedby') $safe = ptsPublicRichTextSafeTokenList($value);
        elseif($name === 'type') $safe = in_array($value, array('1', 'a', 'A', 'i', 'I'), true) ? $value : '';
        elseif($name === 'reversed') $safe = '';

        if($safe === '') $element->removeAttributeNode($attribute);
        else $element->setAttribute($name, $safe);
    }
    if($tag === 'a' && $element->getAttribute('target') === '_blank')
    {
        $rel = ptsPublicRichTextSafeTokenList($element->getAttribute('rel').' noopener noreferrer');
        $element->setAttribute('rel', $rel);
    }
}

function ptsPublicRichTextSanitizeNode(DOMNode $node)
{
    $parent = $node->parentNode;
    if(!$parent) return;
    if($node->nodeType === XML_COMMENT_NODE)
    {
        $parent->removeChild($node);
        return;
    }
    if($node->nodeType !== XML_ELEMENT_NODE)
    {
        if($node->nodeType !== XML_TEXT_NODE) $parent->removeChild($node);
        return;
    }

    $tag = strtolower($node->nodeName);
    if(in_array($tag, ptsPublicRichTextBlockedTags(), true))
    {
        $parent->removeChild($node);
        return;
    }
    if(!in_array($tag, ptsPublicRichTextAllowedTags(), true))
    {
        while($node->firstChild)
        {
            $child = $node->firstChild;
            $parent->insertBefore($child, $node);
            ptsPublicRichTextSanitizeNode($child);
        }
        $parent->removeChild($node);
        return;
    }

    ptsPublicRichTextSanitizeAttributes($node, $tag);
    if($tag === 'img' && !$node->hasAttribute('src'))
    {
        $parent->removeChild($node);
        return;
    }
    $children = array();
    foreach($node->childNodes as $child) $children[] = $child;
    foreach($children as $child) ptsPublicRichTextSanitizeNode($child);
}

function ptsPublicRichTextSanitize($value)
{
    $html = trim(ptsPublicRichTextDecodeCmsHtml($value));
    if($html === '' || !class_exists('DOMDocument')) return '';

    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8"?><!DOCTYPE html><html><body><div id="pts-public-richtext-root">'.$html.'</div></body></html>',
        LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if(!$loaded) return '';

    $xpath = new DOMXPath($document);
    $roots = $xpath->query('//*[@id="pts-public-richtext-root"]');
    if(!$roots || $roots->length !== 1) return '';
    $root = $roots->item(0);
    $children = array();
    foreach($root->childNodes as $child) $children[] = $child;
    foreach($children as $child) ptsPublicRichTextSanitizeNode($child);

    $result = '';
    foreach($root->childNodes as $child) $result .= $document->saveHTML($child);
    return trim($result);
}
