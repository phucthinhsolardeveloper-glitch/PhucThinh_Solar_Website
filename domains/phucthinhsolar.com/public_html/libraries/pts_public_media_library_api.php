<?php
/** Pure DTO helpers for public Gallery/Video Nuxt previews. */

function ptsMediaPublished($status)
{
    if(!is_scalar($status)) return false;
    return in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsMediaPlainText($value)
{
    if(!is_scalar($value)) return '';
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function ptsMediaSlug($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value, " /\t\n\r\0\x0B");
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsMediaImageName($value)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value);
    if($value === '' || basename($value) !== $value) return '';
    return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value) ? $value : '';
}

function ptsMediaProductImage($value)
{
    $file = ptsMediaImageName($value);
    return $file === '' ? '' : '/upload/product/'.$file;
}

function ptsMediaSeoDto(array $row, $fallbackTitle, $fallbackDescription, $canonical)
{
    $title = ptsMediaPlainText(isset($row['titlevi']) ? $row['titlevi'] : '');
    $description = ptsMediaPlainText(isset($row['descriptionvi']) ? $row['descriptionvi'] : '');
    $keywords = ptsMediaPlainText(isset($row['keywordsvi']) ? $row['keywordsvi'] : '');
    if($title === '') $title = ptsMediaPlainText($fallbackTitle);
    if($description === '') $description = ptsMediaPlainText($fallbackDescription);
    return array(
        'title' => $title,
        'description' => $description,
        'keywords' => $keywords,
        'canonical' => $canonical
    );
}

function ptsGalleryAlbumDto(array $row)
{
    if(!ptsMediaPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $title = ptsMediaPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    $slug = ptsMediaSlug(isset($row['slugvi']) ? $row['slugvi'] : '');
    if($id <= 0 || $title === '' || $slug === '') return null;
    return array(
        'id' => $id,
        'title' => $title,
        'slug' => $slug,
        'url' => '/'.$slug.'/',
        'description' => ptsMediaPlainText(isset($row['descvi']) ? $row['descvi'] : ''),
        'image' => ptsMediaProductImage(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '')
    );
}

function ptsGalleryImageDto(array $row)
{
    if(!ptsMediaPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $image = ptsMediaProductImage(isset($row['_pts_public_photo']) ? $row['_pts_public_photo'] : '');
    if($image === '') return null;
    return array(
        'id' => isset($row['id']) ? max(0, (int)$row['id']) : 0,
        'image' => $image,
        'caption' => ptsMediaPlainText(isset($row['namevi']) ? $row['namevi'] : '')
    );
}

function ptsYoutubeId($value)
{
    if(!is_scalar($value)) return '';
    $raw = trim(htmlspecialchars_decode((string)$value, ENT_QUOTES));
    if($raw === '') return '';
    $parts = @parse_url($raw);
    if(!is_array($parts) || empty($parts['host'])) return '';
    if(empty($parts['scheme']) || !in_array(strtolower((string)$parts['scheme']), array('http','https'), true)) return '';
    $host = strtolower((string)$parts['host']);
    $id = '';
    if(in_array($host, array('youtu.be','www.youtu.be'), true))
    {
        $id = trim(isset($parts['path']) ? (string)$parts['path'] : '', '/');
    }
    else if(in_array($host, array('youtube.com','www.youtube.com','m.youtube.com','youtube-nocookie.com','www.youtube-nocookie.com'), true))
    {
        if(!empty($parts['query']))
        {
            $query = array();
            parse_str((string)$parts['query'], $query);
            if(isset($query['v']) && is_scalar($query['v'])) $id = (string)$query['v'];
        }
        if($id === '' && !empty($parts['path']) && preg_match('~/(?:embed|shorts)/([A-Za-z0-9_-]{11})(?:/|$)~', (string)$parts['path'], $matches))
        {
            $id = $matches[1];
        }
    }
    return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : '';
}

function ptsVideoDto(array $row)
{
    if(!ptsMediaPublished(isset($row['status']) ? $row['status'] : '')) return null;
    $id = isset($row['id']) ? (int)$row['id'] : 0;
    $title = ptsMediaPlainText(isset($row['namevi']) ? $row['namevi'] : '');
    $youtubeId = ptsYoutubeId(isset($row['link_video']) ? $row['link_video'] : '');
    if($id <= 0 || $title === '' || $youtubeId === '') return null;
    return array(
        'id' => $id,
        'title' => $title,
        'youtubeId' => $youtubeId,
        'embedUrl' => 'https://www.youtube-nocookie.com/embed/'.$youtubeId,
        'thumbnail' => 'https://img.youtube.com/vi/'.$youtubeId.'/0.jpg'
    );
}
