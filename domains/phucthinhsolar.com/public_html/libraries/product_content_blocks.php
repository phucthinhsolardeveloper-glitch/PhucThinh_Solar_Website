<?php
if(!function_exists('productContentBlockParse'))
{
    function productContentBlockParse($text, $paired = false, $limit = 10)
    {
        $items = array();
        $errors = array();
        foreach(preg_split('/\R/u', trim((string)$text)) as $lineNumber => $line)
        {
            $line = trim(strip_tags($line));
            if($line === '') continue;
            if(count($items) >= $limit) { $errors[] = 'chỉ nhận tối đa '.$limit.' dòng.'; break; }
            if($paired)
            {
                $parts = array_map('trim', explode('|', $line, 2));
                if(count($parts) !== 2 || $parts[0] === '' || $parts[1] === '')
                {
                    $errors[] = 'dòng '.($lineNumber + 1).' phải theo mẫu “Tiêu đề | Giải thích”.';
                    continue;
                }
                $items[] = array('title' => $parts[0], 'text' => $parts[1]);
            }
            else $items[] = $line;
        }
        return array('items' => $items, 'errors' => array_unique($errors));
    }

    function productContentBlockDecode($value)
    {
        if(empty($value)) return array();
        $decoded = json_decode(htmlspecialchars_decode($value, ENT_QUOTES), true);
        return is_array($decoded) ? $decoded : array();
    }

    function productContentBlockLines($value, $paired = false)
    {
        $lines = array();
        foreach(productContentBlockDecode($value) as $item)
        {
            if($paired && is_array($item) && !empty($item['title']) && isset($item['text'])) $lines[] = $item['title'].' | '.$item['text'];
            elseif(!$paired && is_scalar($item)) $lines[] = (string)$item;
        }
        return implode("\n", $lines);
    }
}
