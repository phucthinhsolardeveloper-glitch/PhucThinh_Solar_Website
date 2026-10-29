<?php
error_reporting(E_ALL);

require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/solution_hub.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/product_category_urls.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_richtext.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_solutions_api.php';

function solutionsAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException($message);
    echo 'PASS: ', $message, "\n";
}

$solutionRows = array(
    array(
        'id' => 1,
        'id_list' => 5,
        'namevi' => 'Điện mặt trời <b>hộ gia đình</b>',
        'title_indexvi' => 'Giải pháp điện mặt trời',
        'descvi' => '<p>Mô tả <em>an toàn</em></p>',
        'slugvi' => 'dien-mat-troi-ho-gia-dinh',
        'photo' => 'private-name.jpg',
        'status' => 'hienthi,solutionhub',
        'private_token' => 'SHOULD_NOT_LEAK',
        '_pts_public_photo1' => '/upload/news/solution-1.jpg'
    ),
    array(
        'id' => 2,
        'namevi' => 'Không được chọn',
        'slugvi' => 'not-featured',
        'status' => 'hienthi',
        '_pts_public_photo' => '/upload/news/not-featured.jpg'
    ),
    array(
        'id' => 3,
        'namevi' => 'Bị ẩn',
        'slugvi' => 'hidden',
        'status' => 'solutionhub',
        '_pts_public_photo' => '/upload/news/hidden.jpg'
    ),
    array(
        'id' => 4,
        'namevi' => 'Không có slug',
        'slugvi' => '',
        'status' => 'hienthi,solutionhub'
    )
);
$introRows = array(array(
    'namevi' => 'Giải pháp cho công trình',
    'title_indexvi' => 'Tư vấn đúng nhu cầu',
    'descvi' => '<p>Intro <strong>plain text</strong></p>',
    'contentvi' => '<h2 id="chon-giai-phap" class="legacy-heading" style="color:red" onclick="SHOULD_NOT_RUN()">Nội dung tư vấn</h2><p>Đã qua <strong>sanitizer</strong>.</p><script>SHOULD_NOT_LEAK</script>',
    'photo' => 'intro.jpg',
    'status' => 'hienthi',
    '_pts_public_photo' => '/upload/news/intro.jpg'
));
$ctaRows = array(array(
    'namevi' => 'Nhận tư vấn cho dự án',
    'descvi' => 'Gửi nhu cầu để được hỗ trợ.',
    'title_indexvi' => 'Nhận tư vấn',
    'photo' => 'cta.jpg',
    'status' => 'hienthi',
    '_pts_public_photo' => '/upload/news/cta.jpg'
));
$productRows = array(
    array('namevi' => 'Tấm pin', 'descvi' => 'Thiết bị', 'slugvi' => 'tam-pin', 'status' => 'hienthi,solutionhub', '_pts_public_photo' => '/upload/product/panel.jpg'),
    array('namevi' => 'Không nổi bật', 'slugvi' => 'not-featured-product', 'status' => 'hienthi'),
    array('namevi' => 'Ẩn', 'slugvi' => 'hidden-product', 'status' => 'solutionhub'),
    array('namevi' => 'Biến tần', 'slugvi' => 'bien-tan', 'status' => 'hienthi,solutionhub'),
    array('namevi' => 'Lưu trữ', 'slugvi' => 'luu-tru', 'status' => 'hienthi,solutionhub'),
    array('namevi' => 'Đèn', 'slugvi' => 'den', 'status' => 'hienthi,solutionhub'),
    array('namevi' => 'Vượt giới hạn 4', 'slugvi' => 'extra', 'status' => 'hienthi,solutionhub')
);

$sanitizedFixtureHtml = ptsPublicRichTextSanitize($introRows[0]['contentvi']);
solutionsAssert(strpos($sanitizedFixtureHtml, '<h2 id="chon-giai-phap">Nội dung tư vấn</h2>') !== false && strpos($sanitizedFixtureHtml, '<strong>sanitizer</strong>') !== false, 'sanitizer giữ safe heading id và strong markup');
solutionsAssert(strpos($sanitizedFixtureHtml, 'SHOULD_NOT_LEAK') === false && strpos($sanitizedFixtureHtml, 'SHOULD_NOT_RUN') === false && strpos($sanitizedFixtureHtml, ' style=') === false && strpos($sanitizedFixtureHtml, ' class=') === false, 'sanitizer loại script text và thuộc tính ngoài allowlist');

$result = ptsPublicSolutionsPayload(
    $solutionRows,
    $introRows,
    $ctaRows,
    $productRows,
    '090 123-4567',
    array('dien-mat-troi-ho-gia-dinh' => '/assets/images/site-visuals/fallback.jpg')
);
$categoryDto = ptsPublicSolutionCategoryDto(
    array('id'=>5, 'namevi'=>'Hộ gia đình', 'descvi'=>'Mô tả nhóm', 'slugvi'=>'ho-gia-dinh', 'status'=>'hienthi'),
    array(array('id'=>5, 'namevi'=>'Hộ gia đình', 'slugvi'=>'ho-gia-dinh', 'status'=>'hienthi')),
    $solutionRows
);
solutionsAssert($result['schemaVersion'] === 1 && $result['locale'] === 'vi', 'schema và locale vi');
solutionsAssert($result['category'] === null, 'solutions hub không tự nhận là category');
solutionsAssert($categoryDto['canonicalPath'] === '/ho-gia-dinh' && $categoryDto['cards'][0]['title'] === 'Điện mặt trời hộ gia đình', 'solution category dùng canonical bare path và tên bài như template PHP');
solutionsAssert($categoryDto['navigation'][0]['url'] === '/giai-phap/dien-mat-troi-ho-gia-dinh/' && $categoryDto['navigation'][0]['active'] === true, 'solution category nav giữ representative detail link PHP');
solutionsAssert(count($result['cards']) === 1, 'featured selection chỉ trả card được chọn và có slug');
solutionsAssert($result['cards'][0]['url'] === '/giai-phap/dien-mat-troi-ho-gia-dinh/', 'canonical solution URL');
solutionsAssert($result['cards'][0]['title'] === 'Giải pháp điện mặt trời', 'ưu tiên title_indexvi');
solutionsAssert($result['cards'][0]['description'] === 'Mô tả an toàn', 'editorial field được hạ về plain text');
solutionsAssert($result['cards'][0]['image'] === '/upload/news/solution-1.jpg', 'ảnh upload đã được duyệt được giữ');
solutionsAssert($result['intro']['image'] === '/upload/news/intro.jpg' && $result['intro']['title'] === 'Giải pháp cho công trình', 'intro visible và ưu tiên dữ liệu CMS');
solutionsAssert(count($result['equipment']) === 4, 'equipment giữ featured selection và giới hạn 4');
solutionsAssert($result['equipment'][0]['url'] === '/danh-muc-san-pham/tam-pin/', 'canonical equipment URL');
solutionsAssert($result['cta']['image'] === '/upload/news/cta.jpg' && $result['cta']['hotline']['tel'] === '0901234567', 'CTA upload và hotline');
solutionsAssert($result['labels']['choices'] === 'Khám phá các giải pháp' && $result['labels']['empty'] !== '', 'nhãn section public đầy đủ');
solutionsAssert($result['editorial']['content']['format'] === 'sanitized-html' && $result['editorial']['content']['sanitizedBy'] === 'pts_public_richtext', 'editorial dùng envelope sanitizer dùng chung');
$editorialHtml = $result['editorial']['content']['html'];
$editorialToc = $result['editorial']['toc'];
solutionsAssert(strpos($editorialHtml, '>Nội dung tư vấn</h2>') !== false && strpos($editorialHtml, '<strong>sanitizer</strong>') !== false, 'editorial giữ heading content và strong markup từ solution-intro.contentvi');
solutionsAssert(strpos($editorialHtml, 'SHOULD_NOT_LEAK') === false && strpos($editorialHtml, 'SHOULD_NOT_RUN') === false && strpos($editorialHtml, '<script') === false && strpos($editorialHtml, ' onclick=') === false && strpos($editorialHtml, ' style=') === false && strpos($editorialHtml, ' class=') === false, 'editorial loại script text và thuộc tính ngoài allowlist');
solutionsAssert($editorialToc['rendered'] === true && count($editorialToc['items']) === 1 && $editorialToc['items'][0]['label'] === 'Nội dung tư vấn' && $editorialToc['items'][0]['level'] === 2 && preg_match('/^solution-section-[1-9][0-9]*$/', $editorialToc['items'][0]['id']) === 1 && strpos($editorialHtml, 'id="'.$editorialToc['items'][0]['id'].'"') !== false, 'editorial sinh TOC và đồng bộ id heading an toàn');
$encoded = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
solutionsAssert(strpos($encoded, 'SHOULD_NOT_LEAK') === false && strpos($encoded, 'private_token') === false && strpos($encoded, 'contentvi') === false, 'DTO không lộ field private hoặc raw CMS field');

$fallbackResult = ptsPublicSolutionsPayload(
    array(array('namevi' => 'Fallback', 'slugvi' => 'fallback', 'status' => 'hienthi,solutionhub')),
    array(),
    array(),
    array(),
    '',
    array('fallback' => '/assets/fallback.jpg')
);
solutionsAssert($fallbackResult['intro']['title'] === 'Chọn đúng giải pháp cho nhu cầu của bạn', 'intro fallback khi CMS chưa có record');
solutionsAssert($fallbackResult['cards'][0]['image'] === '/assets/fallback.jpg', 'card dùng fallback ảnh đã được duyệt');
solutionsAssert($fallbackResult['cta']['image'] === '/assets/images/site-visuals/catalog-street-lighting.jpg', 'CTA fallback giữ đúng source');
solutionsAssert($fallbackResult['cta']['hotline'] === null, 'hotline rỗng không tạo CTA hotline');

$hiddenResult = ptsPublicSolutionsPayload(
    array(array('namevi' => 'Visible card', 'slugvi' => 'visible-card', 'status' => 'hienthi')),
    array(array('namevi' => 'Hidden intro', 'status' => '')),
    array(array('namevi' => 'Hidden CTA', 'status' => '')),
    array(),
    '',
    array()
);
solutionsAssert($hiddenResult['intro'] === null && $hiddenResult['cta'] === null, 'record CMS bị ẩn không hồi sinh fallback');
solutionsAssert($hiddenResult['editorial']['content']['html'] === '' && $hiddenResult['editorial']['toc']['reason'] === 'no_editorial_content', 'intro bị ẩn cũng không làm rich text hồi sinh');

$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_solutions.php');
$command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"] = "POST"; require '.var_export($endpoint, true).';').' 2>&1';
$methodOutput = shell_exec($command);
solutionsAssert(strpos((string)$methodOutput, 'METHOD_NOT_ALLOWED') !== false, 'endpoint từ chối method khác GET');

echo "PASS: PTS public solutions API contract\n";
