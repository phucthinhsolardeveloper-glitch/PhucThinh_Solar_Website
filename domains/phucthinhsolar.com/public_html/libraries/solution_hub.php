<?php
function solutionHubHasStatus(array $row, $status)
{
    return in_array($status, explode(',', isset($row['status']) ? $row['status'] : ''), true);
}

function solutionHubSelect(array $rows, $flag = '', $limit = 0)
{
    // Hidden flagged records count as a selection. Hiding the final selected
    // item must not resurrect unrelated fallback content.
    $hasSelection = false;
    foreach($rows as $row) if($flag !== '' && solutionHubHasStatus($row, $flag)) $hasSelection = true;
    $selected = array();
    foreach($rows as $row) {
        if(!solutionHubHasStatus($row, 'hienthi')) continue;
        if($hasSelection && !solutionHubHasStatus($row, $flag)) continue;
        $selected[] = $row;
        if($limit > 0 && count($selected) >= $limit) break;
    }
    return $selected;
}

function solutionHubText($value)
{
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function solutionHubField(array $row, $field, $lang)
{
    $value = isset($row[$field.$lang]) ? solutionHubText($row[$field.$lang]) : '';
    return $value !== '' ? $value : (isset($row[$field.'vi']) ? solutionHubText($row[$field.'vi']) : '');
}

function solutionHubNavName(array $row, $lang)
{
    return solutionHubField($row, 'title_index', $lang) ?: solutionHubField($row, 'name', $lang);
}

function solutionHubImage($filename, $upload)
{
    if(!$filename || basename($filename) !== $filename) return '';
    if(!preg_match('/\.(?:png|jpe?g|gif|webp)$/i', $filename)) return '';
    return is_file($upload.$filename) ? $upload.$filename : '';
}

function solutionHubPublicPath(array $row, $lang = 'vi')
{
    $slug = solutionHubField($row, 'slug', $lang);
    return $slug !== '' ? 'giai-phap/'.$slug.'/' : 'giai-phap/';
}
