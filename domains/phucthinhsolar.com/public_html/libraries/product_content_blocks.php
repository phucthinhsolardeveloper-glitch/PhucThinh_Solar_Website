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

    /* ================= Khối nội dung mô tả dùng chung (table_content_section) ================= */

    function productSectionsDecode($value)
    {
        if(empty($value)) return array();
        $decoded = json_decode(htmlspecialchars_decode($value, ENT_QUOTES), true);
        if(!is_array($decoded)) return array();
        $blocks = array();
        foreach($decoded as $block)
        {
            if(is_array($block) && !empty($block['type'])) $blocks[] = $block;
        }
        return $blocks;
    }

    /**
     * Chọn khối kế thừa (bỏ qua sản phẩm) theo nguồn được yêu cầu.
     *   'auto'     -> hành vi gốc: hãng > danh mục cấp 2 > cấp 1, chỉ lấy 1 nguồn đầu tiên có khối
     *   'brand'    -> chỉ hãng (không tự lùi về danh mục nếu hãng trống)
     *   'category' -> chỉ danh mục, ưu tiên cấp 2 - cấp 1 nếu cấp 2 chưa có (bỏ qua hãng)
     *   'stack'    -> cộng dồn MỌI nguồn có khối, theo thứ tự gần sản phẩm nhất trước:
     *                 danh mục cấp 2 -> danh mục cấp 1 -> hãng
     * Trả về array('blocks' => [...], 'source' => 'brand'|'cat'|'list'|'stack'|'').
     */
    function productPickInheritedBlocks($byScope, $source = 'auto')
    {
        $blocksOf = function($scope) use ($byScope) {
            return isset($byScope[$scope]) ? productSectionsDecode($byScope[$scope]['blocks']) : array();
        };

        if($source === 'brand')
        {
            $blocks = $blocksOf('brand');
            return array('blocks' => $blocks, 'source' => $blocks ? 'brand' : '');
        }

        if($source === 'category')
        {
            foreach(array('cat', 'list') as $scope)
            {
                $blocks = $blocksOf($scope);
                if($blocks) return array('blocks' => $blocks, 'source' => $scope);
            }
            return array('blocks' => array(), 'source' => '');
        }

        if($source === 'stack')
        {
            $combined = array();
            foreach(array('cat', 'list', 'brand') as $scope) $combined = array_merge($combined, $blocksOf($scope));
            return array('blocks' => $combined, 'source' => $combined ? 'stack' : '');
        }

        /* auto (mặc định) */
        foreach(array('brand', 'cat', 'list') as $scope)
        {
            $blocks = $blocksOf($scope);
            if($blocks) return array('blocks' => $blocks, 'source' => $scope);
        }
        return array('blocks' => array(), 'source' => '');
    }

    /**
     * Chọn khối nội dung cho một sản phẩm theo chế độ + nguồn kế thừa.
     *   mode 'own'     -> chỉ dùng khối của chính sản phẩm
     *   mode 'both'    -> khối riêng của sản phẩm trước, nối thêm khối kế thừa phía sau
     *   mode 'hide'    -> không hiện mô tả dài (kể cả kế thừa lẫn đoạn tự sinh)
     *   mode 'inherit' / không có bản riêng -> sản phẩm (nếu lỡ có khối) rồi tới khối kế thừa
     * Nguồn kế thừa (cột inherit_source, xem productPickInheritedBlocks) áp dụng cho cả
     * 'inherit' lẫn 'both'.
     * Trả về array('blocks' => [...], 'source' => 'product'|'brand'|'cat'|'list'|'both'|'stack'|'hide'|'').
     */
    function productResolveSections($d, $rowDetail, $lang = 'vi')
    {
        $out = array('blocks' => array(), 'source' => '');
        if(empty($rowDetail['id'])) return $out;

        $brandId = !empty($rowDetail['id_brand']) ? (int) $rowDetail['id_brand'] : 0;
        $catId   = !empty($rowDetail['id_cat'])   ? (int) $rowDetail['id_cat']   : 0;
        $listId  = !empty($rowDetail['id_list'])  ? (int) $rowDetail['id_list']  : 0;

        /* Ngôn ngữ khác VI mà chưa soạn -> tự lùi về khối VI. */
        $rows = $d->rawQuery(
            "select scope, mode, inherit_source, coalesce(nullif(blocks$lang,''), blocksvi) as blocks from #_content_section
             where find_in_set('hienthi',status) and (
                (scope='product' and scope_id=?) or (scope='brand' and scope_id=?)
                or (scope='cat' and scope_id=?) or (scope='list' and scope_id=?)
             )",
            array((int) $rowDetail['id'], $brandId, $catId, $listId)
        );
        if(empty($rows)) return $out;

        $byScope = array();
        foreach($rows as $row) $byScope[$row['scope']] = $row;

        $productMode = isset($byScope['product']) ? $byScope['product']['mode'] : 'inherit';
        $inheritSource = (isset($byScope['product']) && !empty($byScope['product']['inherit_source'])) ? $byScope['product']['inherit_source'] : 'auto';
        if($productMode === 'hide') { $out['source'] = 'hide'; return $out; }

        if($productMode === 'own')
        {
            $blocks = isset($byScope['product']) ? productSectionsDecode($byScope['product']['blocks']) : array();
            $out['blocks'] = $blocks;
            $out['source'] = $blocks ? 'product' : 'own';
            return $out;
        }

        if($productMode === 'both')
        {
            $ownBlocks = isset($byScope['product']) ? productSectionsDecode($byScope['product']['blocks']) : array();
            $inherited = productPickInheritedBlocks($byScope, $inheritSource);
            /* Riêng của sản phẩm hiện trước (thông tin cụ thể nhất), kế thừa hãng/danh
               mục nối thêm phía sau (thông tin chung). */
            $out['blocks'] = array_merge($ownBlocks, $inherited['blocks']);
            $out['source'] = $out['blocks'] ? ($ownBlocks ? 'both' : $inherited['source']) : '';
            return $out;
        }

        /* inherit: sản phẩm (nếu lỡ có khối) -> nguồn kế thừa đã chọn (hoặc auto) */
        if(isset($byScope['product']))
        {
            $blocks = productSectionsDecode($byScope['product']['blocks']);
            if($blocks) { $out['blocks'] = $blocks; $out['source'] = 'product'; return $out; }
        }
        $inherited = productPickInheritedBlocks($byScope, $inheritSource);
        $out['blocks'] = $inherited['blocks'];
        $out['source'] = $inherited['source'];
        return $out;
    }

    /**
     * Đặt chế độ + nguồn kế thừa cho một sản phẩm (dùng khi lưu form sản phẩm).
     * $mode null/'' -> bỏ qua toàn bộ. $inheritSource null -> giữ nguyên giá trị đang lưu
     * (dùng khi nơi gọi chỉ cần đổi mode, ví dụ thao tác hàng loạt "ẩn mô tả").
     * 'inherit' + auto + không có khối riêng -> xoá bản ghi cho gọn (không có gì đặc biệt để nhớ).
     */
    function productContentSectionSetMode($d, $productId, $mode, $inheritSource = null)
    {
        $productId = (int) $productId;
        if($productId <= 0 || $mode === null || $mode === '') return;
        if(!in_array($mode, array('inherit', 'own', 'both', 'hide'), true)) $mode = 'inherit';
        if($inheritSource !== null && !in_array($inheritSource, array('auto', 'brand', 'category', 'stack'), true)) $inheritSource = 'auto';

        $row = $d->rawQueryOne("select id, blocksvi, inherit_source from #_content_section where scope='product' and scope_id=? limit 0,1", array($productId));
        $effectiveSource = $inheritSource !== null ? $inheritSource : ((!empty($row['inherit_source'])) ? $row['inherit_source'] : 'auto');

        if($row)
        {
            if($mode === 'inherit' && $effectiveSource === 'auto' && trim((string) $row['blocksvi']) === '')
            {
                $d->rawQuery("delete from #_content_section where id=?", array((int) $row['id']));
                return;
            }
            $upd = array('mode' => $mode, 'date_updated' => time());
            if($inheritSource !== null) $upd['inherit_source'] = $inheritSource;
            $d->where('id', (int) $row['id']);
            $d->update('content_section', $upd);
        }
        elseif($mode !== 'inherit' || $effectiveSource !== 'auto')
        {
            $d->insert('content_section', array('scope' => 'product', 'scope_id' => $productId, 'mode' => $mode, 'inherit_source' => $effectiveSource, 'status' => 'hienthi', 'date_updated' => time()));
        }
    }

    /** Ghi khối cho một sản phẩm (dùng khi import Excel). Mảng rỗng -> quay về kế thừa. */
    function productContentSectionApplyBlocks($d, $productId, $blocks)
    {
        $productId = (int) $productId;
        if($productId <= 0) return;
        $json = (!empty($blocks) && is_array($blocks))
            ? htmlspecialchars(json_encode(array_values($blocks), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES)
            : null;
        $row = $d->rawQueryOne("select id, mode from #_content_section where scope='product' and scope_id=? limit 0,1", array($productId));
        if($row)
        {
            $mode = $json ? 'own' : ($row['mode'] === 'hide' ? 'hide' : 'inherit');
            $d->where('id', (int) $row['id']);
            $d->update('content_section', array('blocksvi' => $json, 'mode' => $mode, 'date_updated' => time()));
        }
        elseif($json)
        {
            $d->insert('content_section', array('scope' => 'product', 'scope_id' => $productId, 'mode' => 'own', 'blocksvi' => $json, 'status' => 'hienthi', 'date_updated' => time()));
        }
    }

    /** Khối -> văn bản gọn (cho cột export Excel). */
    function productBlocksToMarkdown($blocks)
    {
        if(!is_array($blocks)) return '';
        $out = array();
        foreach($blocks as $b)
        {
            $t = isset($b['type']) ? $b['type'] : '';
            if($t === 'heading') $out[] = '## '.trim((string)(isset($b['text']) ? $b['text'] : ''));
            elseif($t === 'text') $out[] = trim((string)(isset($b['text']) ? $b['text'] : ''));
            elseif($t === 'list')
            {
                $ord = !empty($b['ordered']); $i = 0; $lines = array();
                foreach((array)(isset($b['items']) ? $b['items'] : array()) as $it)
                {
                    $it = trim((string)$it); if($it === '') continue; $i++;
                    $lines[] = ($ord ? $i.'. ' : '- ').$it;
                }
                if($lines) $out[] = implode("\n", $lines);
            }
            elseif($t === 'image')
                $out[] = '!['.trim((string)(isset($b['caption']) ? $b['caption'] : '')).']('.trim((string)(isset($b['photo']) ? $b['photo'] : '')).')';
            elseif($t === 'media')
            {
                $side = in_array(isset($b['side']) ? $b['side'] : '', array('left', 'right', 'center'), true) ? $b['side'] : 'left';
                $chunk = '[media:'.$side.']('.trim((string)(isset($b['photo']) ? $b['photo'] : '')).')';
                if(!empty($b['title'])) $chunk .= "\n**".trim((string)$b['title'])."**";
                if(!empty($b['text']))  $chunk .= "\n".trim((string)$b['text']);
                $out[] = $chunk;
            }
            elseif($t === 'faq')
            {
                $question = trim((string)(isset($b['question']) ? $b['question'] : ''));
                $answer = trim((string)(isset($b['answer']) ? $b['answer'] : ''));
                if($question !== '' && $answer !== '') $out[] = '[faq] '.$question.' | '.$answer;
            }
        }
        return implode("\n\n", array_filter($out, function($x){ return $x !== ''; }));
    }

    /** Văn bản gọn -> khối (cho cột import Excel và nút "Dán nhanh"). */
    function productMarkdownToBlocks($text)
    {
        $text = trim(str_replace("\r", '', (string) $text));
        if($text === '') return array();
        $blocks = array();
        foreach(preg_split('/\n{2,}/', $text) as $group)
        {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $group)), function($l){ return $l !== ''; }));
            if(!$lines) continue;

            if(preg_match('#^\[media:(left|right|center)\]\(([^)]*)\)$#i', $lines[0], $m))
            {
                $blk = array('type' => 'media', 'side' => strtolower($m[1]), 'photo' => trim($m[2]), 'title' => '', 'text' => '');
                $rest = array_slice($lines, 1);
                if($rest && preg_match('/^\*\*(.+)\*\*$/', $rest[0], $tm)) { $blk['title'] = trim($tm[1]); $rest = array_slice($rest, 1); }
                $blk['text'] = implode("\n", $rest);
                $blocks[] = $blk;
                continue;
            }
            if(count($lines) === 1 && preg_match('#^!\[([^\]]*)\]\(([^)]*)\)$#', $lines[0], $m))
            {
                $blocks[] = array('type' => 'image', 'photo' => trim($m[2]), 'caption' => trim($m[1]));
                continue;
            }
            if(count($lines) === 1 && preg_match('/^\[faq\]\s*(.+?)\s*\|\s*(.+)$/u', $lines[0], $m))
            {
                $blocks[] = array('type' => 'faq', 'question' => trim($m[1]), 'answer' => trim($m[2]));
                continue;
            }
            if(count($lines) > 1 && !array_filter($lines, function($l){ return !preg_match('/^([-•*]|\d+[.)])\s+/', $l); }))
            {
                $ordered = (bool) preg_match('/^\d/', $lines[0]);
                $blocks[] = array('type' => 'list', 'ordered' => $ordered, 'items' => array_map(function($l){ return preg_replace('/^([-•*]|\d+[.)])\s+/', '', $l); }, $lines));
                continue;
            }
            if(count($lines) === 1 && preg_match('/^#{1,4}\s+(.+)$/', $lines[0], $m))
            {
                $blocks[] = array('type' => 'heading', 'text' => trim($m[1]));
                continue;
            }
            $blocks[] = array('type' => 'text', 'text' => implode("\n", $lines));
        }
        return $blocks;
    }

    /** Khối nội dung riêng của MỘT danh mục (cấp 1 'list' hoặc cấp 2 'cat'),
     *  dùng để hiện dưới lưới sản phẩm ở trang danh mục — không liên quan tới
     *  chế độ kế thừa của sản phẩm (đây là nội dung của chính danh mục đó). */
    function productCategorySectionBlocks($d, $scope, $scopeId, $lang = 'vi')
    {
        if(!in_array($scope, array('cat', 'list'), true) || (int) $scopeId <= 0) return array();
        $row = $d->rawQueryOne(
            "select coalesce(nullif(blocks$lang,''), blocksvi) as blocks from #_content_section
             where scope=? and scope_id=? and find_in_set('hienthi',status) limit 0,1",
            array($scope, (int) $scopeId)
        );
        return empty($row) ? array() : productSectionsDecode($row['blocks']);
    }

    /** Cờ "Hiện khối mô tả dài" của danh mục - mặc định false (xem contentSectionStatusSetFlag). */
    function productCategoryShowDescription($d, $scope, $scopeId)
    {
        if(!in_array($scope, array('cat', 'list'), true) || (int) $scopeId <= 0) return false;
        $row = $d->rawQueryOne(
            "select status from #_content_section where scope=? and scope_id=? and find_in_set('hienthi',status) limit 0,1",
            array($scope, (int) $scopeId)
        );
        if(empty($row)) return false;
        return in_array('show_description', array_filter(array_map('trim', explode(',', (string) $row['status']))), true);
    }

    /** Khối mà sản phẩm đang KẾ THỪA (bỏ qua bản riêng) — để nút "chép từ hãng". */
    function productInheritedSectionBlocks($d, $rowDetail, $lang = 'vi')
    {
        $brandId = !empty($rowDetail['id_brand']) ? (int) $rowDetail['id_brand'] : 0;
        $catId   = !empty($rowDetail['id_cat'])   ? (int) $rowDetail['id_cat']   : 0;
        $listId  = !empty($rowDetail['id_list'])  ? (int) $rowDetail['id_list']  : 0;
        $rows = $d->rawQuery(
            "select scope, coalesce(nullif(blocks$lang,''), blocksvi) as blocks from #_content_section
             where find_in_set('hienthi',status) and (
                (scope='brand' and scope_id=?) or (scope='cat' and scope_id=?) or (scope='list' and scope_id=?)
             )",
            array($brandId, $catId, $listId)
        );
        $byScope = array();
        foreach((array) $rows as $row) $byScope[$row['scope']] = $row['blocks'];
        foreach(array('brand', 'cat', 'list') as $scope)
        {
            if(!isset($byScope[$scope])) continue;
            $blocks = productSectionsDecode($byScope[$scope]);
            if($blocks) return array('blocks' => $blocks, 'source' => $scope);
        }
        return array('blocks' => array(), 'source' => '');
    }

    /**
     * Dựng HTML cho mảng khối. Kiểu khối: heading | text | image | media | list.
     * $func là helper hiện có (dùng getImage); $uploadDir ví dụ UPLOAD_PRODUCT_L.
     */
    function productRenderSections($blocks, $uploadDir, $func)
    {
        if(empty($blocks) || !is_array($blocks)) return '';
        $esc = function($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        /* photo = tên file -> nằm trong thư mục sản phẩm; photo có "/" -> đường dẫn tương đối từ gốc site */
        $renderImg = function($photo, $alt, $cls, $sizes) use ($uploadDir, $func, $esc) {
            if(strpos($photo, '/') !== false)
                return '<img class="'.$cls.'" src="'.$esc(ltrim($photo, '/')).'" alt="'.$esc($alt).'" loading="lazy">';
            return $func->getImage(array('class' => $cls, 'sizes' => $sizes, 'upload' => $uploadDir, 'image' => $photo, 'alt' => $alt));
        };
        $html = '';

        foreach($blocks as $block)
        {
            $type = isset($block['type']) ? $block['type'] : '';

            if($type === 'heading')
            {
                $text = trim(isset($block['text']) ? $block['text'] : '');
                if($text !== '') $html .= '<h3 class="pts-desc-subtitle">'.$esc($text).'</h3>';
            }
            elseif($type === 'text')
            {
                $text = trim(isset($block['text']) ? $block['text'] : '');
                if($text === '') continue;
                foreach(preg_split('/\R{2,}/u', $text) as $para)
                {
                    $para = trim($para);
                    if($para !== '') $html .= '<p class="pts-desc-para">'.nl2br($esc($para)).'</p>';
                }
            }
            elseif($type === 'image')
            {
                $photo = trim(isset($block['photo']) ? $block['photo'] : '');
                if($photo === '') continue;
                $caption = trim(isset($block['caption']) ? $block['caption'] : '');
                $img = $renderImg($photo, ($caption !== '' ? $caption : 'Hình minh hoạ'), 'pts-desc-figure-img', '1000x680x1');
                $html .= '<figure class="pts-desc-figure">'.$img.($caption !== '' ? '<figcaption>'.$esc($caption).'</figcaption>' : '').'</figure>';
            }
            elseif($type === 'media')
            {
                $photo = trim(isset($block['photo']) ? $block['photo'] : '');
                $title = trim(isset($block['title']) ? $block['title'] : '');
                $text  = trim(isset($block['text']) ? $block['text'] : '');
                if($photo === '' && $title === '' && $text === '') continue;
                $sideVal = (isset($block['side']) && in_array($block['side'], array('right', 'center'), true)) ? $block['side'] : 'left';
                $side = ' pts-desc-media--'.$sideVal;
                $media = '';
                if($photo !== '')
                    $media = '<div class="pts-desc-media-media">'.$renderImg($photo, ($title !== '' ? $title : 'Hình minh hoạ'), 'pts-desc-figure-img', '760x620x1').'</div>';
                $body = '<div class="pts-desc-media-body">'.($title !== '' ? '<strong>'.$esc($title).'</strong>' : '').($text !== '' ? '<p>'.nl2br($esc($text)).'</p>' : '').'</div>';
                $html .= '<div class="pts-desc-media'.$side.'">'.$media.$body.'</div>';
            }
            elseif($type === 'list')
            {
                $items = array();
                if(!empty($block['items']) && is_array($block['items']))
                {
                    foreach($block['items'] as $item)
                    {
                        $item = trim((string) $item);
                        if($item !== '') $items[] = $item;
                    }
                }
                if(!$items) continue;
                $ordered = !empty($block['ordered']);
                $tag = $ordered ? 'ol' : 'ul';
                $cls = $ordered ? 'pts-desc-list pts-desc-list--ol' : 'pts-desc-list pts-desc-list--ul';
                $html .= '<'.$tag.' class="'.$cls.'">';
                foreach($items as $item) $html .= '<li>'.$esc($item).'</li>';
                $html .= '</'.$tag.'>';
            }
        }

        return $html !== '' ? '<div class="pts-desc-block-flow">'.$html.'</div>' : '';
    }

    /** Tách khối FAQ (type='faq') ra khỏi mảng khối chung — dùng cho mục
     *  "Câu hỏi thường gặp" hiện riêng dưới lưới sản phẩm danh mục, phần
     *  khối còn lại vẫn đi qua productRenderSections() như cũ. */
    function productSplitFaqBlocks($blocks)
    {
        $faq = array();
        $rest = array();
        foreach((array) $blocks as $block)
        {
            if(!is_array($block)) continue;
            if(isset($block['type']) && $block['type'] === 'faq') {
                $faq[] = $block;
                continue;
            }

            /* Dữ liệu legacy có thể lưu nhiều dòng [faq] trong một block text
               thay vì tạo block type=faq. Tách chúng trước khi render để không
               lộ cú pháp nội bộ ra phần Mô tả/Lựa chọn. */
            if(isset($block['type']) && $block['type'] === 'text' && isset($block['text'])) {
                $textLines = preg_split('/\R/u', trim((string)$block['text']), -1, PREG_SPLIT_NO_EMPTY);
                $remainingLines = array();
                $foundFaq = false;
                foreach($textLines as $textLine) {
                    $textLine = trim($textLine);
                    if(preg_match('/^\[faq\]\s*(.+?)\s*\|\s*(.+)$/u', $textLine, $faqMatch)) {
                        $faq[] = array('type' => 'faq', 'question' => trim($faqMatch[1]), 'answer' => trim($faqMatch[2]));
                        $foundFaq = true;
                    } else {
                        $remainingLines[] = $textLine;
                    }
                }
                if($foundFaq && $remainingLines) {
                    $block['text'] = implode("\n", $remainingLines);
                    $rest[] = $block;
                } elseif(!$foundFaq) {
                    $rest[] = $block;
                }
                continue;
            }
            $rest[] = $block;
        }
        return array('faq' => $faq, 'rest' => $rest);
    }

    /** Dựng HTML danh sách FAQ (mảng khối type='faq', mỗi khối 1 cặp hỏi-đáp). */
    function productRenderFaqBlocks($faqBlocks)
    {
        if(empty($faqBlocks) || !is_array($faqBlocks)) return '';
        $esc = function($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $html = '';
        foreach($faqBlocks as $block)
        {
            $question = trim(isset($block['question']) ? $block['question'] : '');
            $answer = trim(isset($block['answer']) ? $block['answer'] : '');
            if($question === '' || $answer === '') continue;
            $html .= '<details><summary>'.$esc($question).'</summary><p>'.nl2br($esc($answer)).'</p></details>';
        }
        return $html;
    }

    /** Tách khối type='cards' (lưới thẻ "Chọn X đúng nhu cầu") khỏi mảng khối còn lại. */
    function productSplitCardsBlocks($blocks)
    {
        $cards = array();
        $rest = array();
        foreach((array) $blocks as $block)
        {
            if(is_array($block) && isset($block['type']) && $block['type'] === 'cards') $cards[] = $block;
            else $rest[] = $block;
        }
        return array('cards' => $cards, 'rest' => $rest);
    }

    /**
     * Fallback an toàn cho landing Đèn NLMT. Nội dung chính vẫn lấy từ Admin;
     * bộ này chỉ lấp đúng block còn thiếu khi dữ liệu giữa các môi trường chưa
     * được đồng bộ, tránh trang danh mục rơi thẳng từ phân trang xuống CTA.
     */
    function productLightingFallbackBlocks()
    {
        return array(
            'faq' => array(
                array('type' => 'faq', 'question' => 'Nên chọn đèn theo những tiêu chí nào?', 'answer' => 'Xác định khu vực cần chiếu sáng, độ sáng mong muốn, thời gian sử dụng mỗi đêm, vị trí lắp đặt, khả năng chống bụi nước và chính sách bảo hành trước khi chọn sản phẩm.'),
                array('type' => 'faq', 'question' => 'Thời gian chiếu sáng phụ thuộc vào đâu?', 'answer' => 'Thời gian hoạt động phụ thuộc vào dung lượng pin lưu trữ, công suất đèn, khả năng thu nắng của tấm pin, chế độ vận hành và điều kiện thời tiết thực tế.'),
                array('type' => 'faq', 'question' => 'Cần lưu ý gì khi lắp đặt?', 'answer' => 'Tấm pin cần đặt ở vị trí nhận nắng tốt và hạn chế bóng che; thân đèn, móng hoặc giá đỡ phải phù hợp với vị trí sử dụng và được kiểm tra định kỳ.'),
            ),
            'cards' => array(
                array(
                    'type' => 'cards',
                    'title' => 'Chọn đèn đúng nhu cầu',
                    'items' => array(
                        array('photo' => 'den-nlmt-duong-giao-thong.png', 'title' => 'Đường giao thông', 'desc' => 'Đảm bảo chiếu sáng an toàn, ổn định', 'link' => '/danh-muc-san-pham/den-nlmt/?is_need=duong-giao-thong'),
                        array('photo' => 'den-nlmt-san-vuon.png', 'title' => 'Sân nhà & sân vườn', 'desc' => 'Tạo không gian sáng đẹp, tiện nghi', 'link' => '/danh-muc-san-pham/den-nlmt/?is_need=san-nha'),
                        array('photo' => 'den-nlmt-cong-trinh.png', 'title' => 'Khuôn viên & công trình', 'desc' => 'Giải pháp chiếu sáng linh hoạt, bền bỉ', 'link' => '/danh-muc-san-pham/den-nlmt/?is_need=khuon-vien'),
                    ),
                ),
            ),
        );
    }

    /**
     * Dựng HTML cho mảng khối type='cards' (mỗi khối: 1 tiêu đề mục + N thẻ
     * ảnh/tiêu đề/mô tả/link tuỳ chọn) - admin tự soạn qua Admin > Khối nội
     * dung mô tả, KHÔNG hardcode nội dung theo danh mục trong code. Dùng
     * đúng class .pts-related-grid/.pts-related-card đã có sẵn CSS responsive
     * (xem product-proof.css) để tái dùng style, không tạo CSS mới.
     */
    function productRenderCardsBlocks($cardsBlocks, $uploadDir, $func)
    {
        if(empty($cardsBlocks) || !is_array($cardsBlocks)) return '';
        $esc = function($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $html = '';
        foreach($cardsBlocks as $block)
        {
            $title = trim(isset($block['title']) ? $block['title'] : '');
            $items = (!empty($block['items']) && is_array($block['items'])) ? $block['items'] : array();
            $validItems = array();
            foreach($items as $item)
            {
                if(!is_array($item)) continue;
                $itemTitle = trim(isset($item['title']) ? $item['title'] : '');
                $itemPhoto = trim(isset($item['photo']) ? $item['photo'] : '');
                if($itemTitle === '' && $itemPhoto === '') continue;
                $validItems[] = $item;
            }
            if(!$validItems) continue;

            $count = min(4, count($validItems));
            $html .= '<section class="box-detail pts-related-showcase pts-content-cards" aria-label="'.$esc($title !== '' ? $title : 'Chọn theo nhu cầu').'">';
            if($title !== '') $html .= '<h2 class="pts-lighting-needs-title">'.$esc($title).'</h2>';
            $html .= '<div class="pts-related-grid pts-product-project-grid pts-product-project-grid--'.$count.'">';
            foreach($validItems as $item)
            {
                $itemTitle = trim(isset($item['title']) ? $item['title'] : '');
                $itemDesc = trim(isset($item['desc']) ? $item['desc'] : '');
                $itemPhoto = trim(isset($item['photo']) ? $item['photo'] : '');
                $itemLink = trim(isset($item['link']) ? $item['link'] : '');
                $tag = $itemLink !== '' ? 'a' : 'div';
                $attrs = $itemLink !== '' ? ' href="'.$esc($itemLink).'"' : '';
                $html .= '<'.$tag.' class="pts-related-card"'.$attrs.($itemTitle !== '' ? ' title="'.$esc($itemTitle).'"' : '').'>';
                if($itemPhoto !== '')
                {
                    $html .= '<span class="pts-project-media">';
                    $html .= $func->getImage(array('class' => 'w-100 pts-project-media-main', 'sizes' => '640x480x1', 'upload' => $uploadDir, 'image' => $itemPhoto, 'alt' => $itemTitle));
                    $html .= '</span>';
                }
                $html .= '<span class="pts-related-card-body">';
                if($itemTitle !== '') $html .= '<strong>'.$esc($itemTitle).'</strong>';
                if($itemDesc !== '') $html .= '<small>'.$esc($itemDesc).'</small>';
                $html .= '</span></'.$tag.'>';
            }
            $html .= '</div></section>';
        }
        return $html;
    }

    /**
     * Tách HTML (contentvi cũ) thành mảng khối composer (best-effort).
     * Trả về array('blocks' => [...], 'skipped_images' => int).
     */
    function productLegacyHtmlToBlocks($html)
    {
        $html = trim((string) $html);
        if($html === '' || !class_exists('DOMDocument')) return array('blocks' => array(), 'skipped_images' => 0);

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="__pts_root">'.$html.'</div>');
        libxml_clear_errors();
        $root = $dom->getElementById('__pts_root');
        if(!$root) return array('blocks' => array(), 'skipped_images' => 0);

        $blocks = array();
        $buf = '';
        $skipped = 0;

        $flush = function() use (&$blocks, &$buf) {
            $text = trim(preg_replace('/[ \t]+\n/u', "\n", $buf));
            $text = trim(preg_replace('/\n{3,}/u', "\n\n", $text));
            if($text !== '') $blocks[] = array('type' => 'text', 'text' => $text);
            $buf = '';
        };
        $localPhoto = function($src) {
            $src = (string) $src;
            if(preg_match('#(?:^|/)upload/product/([A-Za-z0-9._-]+\.(?:jpe?g|png|webp|gif))#i', $src, $m)) return $m[1];
            if(preg_match('#(?:^|/)(upload/[A-Za-z0-9._/-]+\.(?:jpe?g|png|webp|gif))#i', $src, $m)) return $m[1];
            return '';
        };

        $walk = function($node) use (&$walk, &$blocks, &$buf, $flush, $localPhoto, &$skipped) {
            foreach($node->childNodes as $child)
            {
                if($child->nodeType === XML_TEXT_NODE) { $buf .= preg_replace('/\s+/u', ' ', $child->nodeValue); continue; }
                if($child->nodeType !== XML_ELEMENT_NODE) continue;
                $tag = strtolower($child->nodeName);

                if($tag === 'img')
                {
                    $flush();
                    $photo = $localPhoto($child->getAttribute('src'));
                    if($photo !== '') $blocks[] = array('type' => 'image', 'photo' => $photo, 'caption' => trim($child->getAttribute('alt')));
                    else $skipped++;
                }
                elseif(in_array($tag, array('h1', 'h2', 'h3', 'h4'), true))
                {
                    $flush();
                    $t = trim(preg_replace('/\s+/u', ' ', $child->textContent));
                    if($t !== '') $blocks[] = array('type' => 'heading', 'text' => $t);
                }
                elseif($tag === 'ul' || $tag === 'ol')
                {
                    $flush();
                    $items = array();
                    foreach($child->getElementsByTagName('li') as $li)
                    {
                        $it = trim(preg_replace('/\s+/u', ' ', $li->textContent));
                        if($it !== '') $items[] = $it;
                    }
                    if($items) $blocks[] = array('type' => 'list', 'ordered' => ($tag === 'ol'), 'items' => $items);
                }
                elseif($tag === 'br') { $buf .= "\n"; }
                elseif(in_array($tag, array('p', 'div', 'section', 'article'), true))
                {
                    $before = strlen($buf);
                    $walk($child);
                    if(strlen($buf) > $before) $buf .= "\n\n";
                }
                else { $walk($child); }
            }
        };

        $walk($root);
        $flush();

        $merged = array();
        foreach($blocks as $b)
        {
            $n = count($merged);
            if($b['type'] === 'text' && $n && $merged[$n - 1]['type'] === 'text')
                $merged[$n - 1]['text'] = trim($merged[$n - 1]['text']."\n\n".$b['text']);
            else $merged[] = $b;
        }
        return array('blocks' => $merged, 'skipped_images' => $skipped);
    }
}
