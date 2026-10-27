<?php
error_reporting(E_ALL);

$helper = getenv('PTS_RICHTEXT_HELPER') ?: __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_richtext.php';
require_once $helper;

function richTextAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException($message);
    echo 'PASS: ', $message, "\n";
}

function richTextAssertSafe($html, $message)
{
    richTextAssert(stripos($html, '<script') === false, $message.' không còn script');
    richTextAssert(stripos($html, '<iframe') === false, $message.' không còn iframe');
    richTextAssert(stripos($html, '<svg') === false, $message.' không còn SVG');
    richTextAssert(!preg_match('/\bon[a-z]+\s*=|javascript:|vbscript:|data:/i', $html), $message.' không còn payload executable');
    richTextAssert(stripos($html, 'style=') === false, $message.' không còn CSS inline');
}

$valid = '&lt;h2 id=&quot;section-1&quot;&gt;Nội dung &amp;amp; phạm vi&lt;/h2&gt;'
    .'&lt;p&gt;Đoạn &lt;strong&gt;đậm&lt;/strong&gt; và &lt;em&gt;nghiêng&lt;/em&gt; với '
    .'&lt;a href=&quot;https://phucthinhsolar.com/tin-tuc/bai-viet/&quot; target=&quot;_blank&quot;&gt;liên kết nội bộ&lt;/a&gt;.&lt;/p&gt;'
    .'&lt;ul&gt;&lt;li&gt;Một&lt;/li&gt;&lt;li&gt;Hai&lt;/li&gt;&lt;/ul&gt;'
    .'&lt;ol start=&quot;2&quot;&gt;&lt;li&gt;Ba&lt;/li&gt;&lt;/ol&gt;'
    .'&lt;figure class=&quot;image&quot;&gt;&lt;img src=&quot;http://phucthinhsolar.com/upload/filemanager/editor/photo.jpg&quot; alt=&quot;Ảnh thật&quot; width=&quot;800&quot; loading=&quot;lazy&quot; /&gt;&lt;figcaption&gt;Chú thích ảnh&lt;/figcaption&gt;&lt;/figure&gt;'
    .'&lt;table&gt;&lt;thead&gt;&lt;tr&gt;&lt;th scope=&quot;col&quot;&gt;Mục&lt;/th&gt;&lt;th&gt;Giá trị&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;&lt;tbody&gt;&lt;tr&gt;&lt;td&gt;A&lt;/td&gt;&lt;td colspan=&quot;1&quot;&gt;B&lt;/td&gt;&lt;/tr&gt;&lt;/tbody&gt;&lt;/table&gt;'
    .'&lt;blockquote&gt;Trích dẫn &lt;code&gt;CMS&lt;/code&gt;&lt;/blockquote&gt;';
$clean = ptsPublicRichTextSanitize($valid);

richTextAssert(strpos($clean, '<h2 id="section-1">') !== false, 'giữ heading và id hợp lệ');
richTextAssert(strpos($clean, '<strong>đậm</strong>') !== false && strpos($clean, '<em>nghiêng</em>') !== false, 'giữ inline semantics');
richTextAssert(strpos($clean, 'href="/tin-tuc/bai-viet/"') !== false, 'chuẩn hóa link nội bộ cùng host về public path');
richTextAssert(strpos($clean, 'rel="noopener noreferrer"') !== false, 'bổ sung rel an toàn cho target blank');
richTextAssert(strpos($clean, '/upload/filemanager/editor/photo.jpg') !== false, 'chuẩn hóa ảnh CMS absolute về path upload');
richTextAssert(strpos($clean, '<figcaption>Chú thích ảnh</figcaption>') !== false, 'giữ figure và caption');
richTextAssert(strpos($clean, '<table>') !== false && strpos($clean, '<thead>') !== false && strpos($clean, '<tbody>') !== false, 'giữ cấu trúc table');
richTextAssertSafe($clean, 'nội dung hợp lệ');

$xss = '<h3 onclick="alert(1)" style="color:red">An toàn</h3>'
    .'<p style="background:url(javascript:alert(1))">Văn bản <span style="color:red">được giữ</span></p>'
    .'<script>alert(1)</script><style>body{background:url(javascript:alert(1))}</style>'
    .'<a href="javascript:alert(1)" onmouseover="alert(2)">Liên kết xấu</a>'
    .'<a href="//evil.example/payload">Host xấu</a>'
    .'<img src="data:image/svg+xml,<svg onload=alert(1)>" onerror="alert(3)" alt="x">'
    .'<iframe src="https://evil.example/embed"></iframe>'
    .'<svg><script>alert(4)</script></svg>';
$safe = ptsPublicRichTextSanitize($xss);

richTextAssert(strpos($safe, 'An toàn') !== false && strpos($safe, 'được giữ') !== false, 'giữ text khi bỏ thẻ/thuộc tính nguy hiểm');
richTextAssert(strpos($safe, 'Liên kết xấu') !== false && strpos($safe, '<a') !== false, 'giữ nội dung link nhưng loại href nguy hiểm');
richTextAssert(strpos($safe, '<img') === false, 'loại ảnh data URL không an toàn');
richTextAssertSafe($safe, 'payload XSS');
richTextAssert(ptsPublicRichTextSanitize('<p><img src="/upload/news/../../secret.php" alt="x"></p>') === '<p></p>', 'loại ảnh traversal và extension không được phép');
richTextAssert(ptsPublicRichTextSanitize('<p><a href="mailto:test@example.com">Email</a> <a href="tel:0944555957">Gọi</a></p>') !== '', 'giữ mailto và tel an toàn');

if(getenv('PTS_RICHTEXT_LOCAL_DB') === '1')
{
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'phucthinhs_local';
    $user = getenv('DB_USER') ?: 'pts_local';
    $password = getenv('DB_PASSWORD') ?: '';
    $pdo = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$name.';charset=utf8mb4', $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $rows = $pdo->query("select id,type,contentvi,content1vi from table_news where (contentvi is not null and trim(contentvi) <> '') or (content1vi is not null and trim(content1vi) <> '') order by id")->fetchAll(PDO::FETCH_ASSOC);
    richTextAssert(count($rows) > 0, 'đọc được nội dung CKEditor thật từ CMS local');
    foreach($rows as $row)
    {
        foreach(array('contentvi', 'content1vi') as $field)
        {
            if(trim((string)$row[$field]) === '') continue;
            $rendered = ptsPublicRichTextSanitize($row[$field]);
            richTextAssert($rendered !== '', 'sanitize thành công nội dung local của bản ghi #'.(int)$row['id']);
            richTextAssertSafe($rendered, 'nội dung local của bản ghi #'.(int)$row['id']);
        }
    }
    echo 'PASS: local CMS rich-text rows=', count($rows), "\n";
}

echo "PASS: PTS public rich-text sanitizer contract\n";
