<?php
if(!defined('LIBRARIES')) die('Error');

function newsDetailImageIdentity($source)
{
    $source = rawurldecode(html_entity_decode((string)$source, ENT_QUOTES, 'UTF-8'));
    $path = parse_url($source, PHP_URL_PATH);
    $filename = basename($path !== false ? $path : $source);
    $identity = strtolower(pathinfo($filename, PATHINFO_FILENAME));
    do {
        $previous = $identity;
        $identity = preg_replace('/-(?:scaled|\d+x\d+|\d{4,8})$/i', '', $identity);
    } while($identity !== $previous);
    return trim($identity, '-_ ');
}

function newsDetailContentRepeatsCover($html, $coverFilename)
{
    $coverIdentity = newsDetailImageIdentity($coverFilename);
    if($coverIdentity === '' || trim((string)$html) === '') return false;
    if(!preg_match_all('~<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1~isu', (string)$html, $matches)) return false;
    foreach($matches[2] as $source)
        if(newsDetailImageIdentity($source) === $coverIdentity) return true;
    return false;
}
