<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/product_pdp_profiles.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/product_specs.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/product_content_blocks.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_product_detail_api.php';
function pdpAssert($condition, $message) { if(!$condition) throw new RuntimeException($message); echo "PASS: {$message}\n"; }
$row = array('id'=>7,'namevi'=>'<b>Đèn thật</b>','slugvi'=>'den-that','status'=>'hienthi','code'=>'PT-7','descvi'=>'<p>Mô tả thật</p>','pdp_profile'=>'lighting','cong_suat'=>30,'regular_price'=>350000,'sale_price'=>299000,'additional_specs'=>json_encode(array(array('key'=>'quang_thong','label'=>'Quang thông','value'=>'3.000 lm'),array('key'=>'secret','label'=>'Secret','value'=>'x')), JSON_UNESCAPED_UNICODE));
$sections = array(
    'source' => 'product',
    'blocks' => array(
        array('type'=>'heading','text'=>'Mô tả sản phẩm'),
        array('type'=>'text','text'=>"Dòng một\n[faq] Dùng ở đâu? | Theo nhu cầu thực tế.\nDòng hai"),
        array('type'=>'list','ordered'=>false,'items'=>array('Mục 1','Mục 2')),
        array('type'=>'image','photo'=>'block.webp','caption'=>'Ảnh thật'),
        array('type'=>'media','photo'=>'../private.jpg','title'=>'Ứng dụng','text'=>'Mô tả','side'=>'right'),
        array('type'=>'cards','title'=>'Không thuộc PDP','items'=>array(array('title'=>'Card')))
    )
);
$content = ptsPdpContentDto($sections);
$documents = ptsPdpDocumentsDto(array(
    array('document_type'=>'datasheet','title'=>'Datasheet','file_path'=>'upload/product-documents/model.pdf'),
    array('document_type'=>'manual','title'=>'Manual','file_path'=>'https://docs.example.com/model.docx'),
    array('document_type'=>'manual','title'=>'Traversal','file_path'=>'upload/../secret.pdf'),
    array('document_type'=>'other','title'=>'Executable','file_path'=>'upload/file.exe'),
    array('document_type'=>'other','title'=>'HTTP','file_path'=>'http://docs.example.com/file.pdf')
));
$payload = ptsPdpPayload($row, array('/upload/product/real.webp','/upload/product/real.webp'), ptsPdpSpecDto($row), 'san-pham/den-that', array('product'=>'publicProductEntityPath(product)'), true, $content, $documents);
pdpAssert($payload['product']['url'] === '/san-pham/den-that', 'canonical product URL has no trailing slash (matches publicProductEntityPath/legacy router)');
pdpAssert($payload['canonical']['url'] === '/san-pham/den-that', 'canonical.url mirrors product.url, no forced trailing slash');
pdpAssert($payload['product']['name'] === 'Đèn thật' && $payload['product']['summary'] === 'Mô tả thật', 'plain text product fields');
pdpAssert($payload['product']['media'] === array('/upload/product/real.webp'), 'duplicate media removed');
pdpAssert($payload['pricing'] === array('mode'=>'price','currency'=>'VND','regularPrice'=>350000,'salePrice'=>299000) && !isset($payload['product']['price']), 'pricing exposed via ptsPdpPricingDto when visible and not baogia');
pdpAssert($payload['content']['rendered'] === true && $payload['content']['source'] === 'product', 'structured content is marked rendered with resolved source');
pdpAssert(count($payload['content']['blocks']) === 5 && $payload['content']['blocks'][3]['image'] === '/upload/product/block.webp', 'PDP block DTO keeps supported blocks and resolves product image');
pdpAssert($payload['content']['blocks'][4]['image'] === '' && $payload['content']['blocks'][4]['side'] === 'right', 'unsafe media image is removed without dropping safe media text');
pdpAssert(count($payload['content']['faqs']) === 1 && $payload['content']['faqs'][0]['question'] === 'Dùng ở đâu?', 'legacy inline FAQ syntax is split into typed FAQ');
pdpAssert(strpos(json_encode($payload['content']), 'Không thuộc PDP') === false, 'category-only cards block is omitted from PDP DTO');
pdpAssert(count($payload['documents']) === 2 && $payload['documents'][0]['url'] === '/upload/product-documents/model.pdf', 'only allowlisted local/HTTPS product documents are exposed');
pdpAssert($payload['documents'][1]['url'] === 'https://docs.example.com/model.docx', 'HTTPS public product document is preserved');
$quoteRow = $row; $quoteRow['status'] = 'hienthi,baogia';
$quotePayload = ptsPdpPayload($quoteRow, array(), ptsPdpSpecDto($quoteRow), 'san-pham/den-that', array('product'=>'publicProductEntityPath(product)'), true, ptsPdpContentDto(array()));
pdpAssert($quotePayload['pricing'] === array('mode'=>'quote','currency'=>'VND','regularPrice'=>null,'salePrice'=>null), 'baogia status forces quote mode even with pricesVisible=true');
$hiddenPayload = ptsPdpPayload($row, array(), ptsPdpSpecDto($row), 'san-pham/den-that', array('product'=>'publicProductEntityPath(product)'), false, ptsPdpContentDto(array()));
pdpAssert($hiddenPayload['pricing']['mode'] === 'quote', 'pricesVisible=false forces quote mode regardless of status');
pdpAssert(strpos(json_encode($payload), 'secret') === false, 'unlisted additional field omitted');
pdpAssert(ptsPdpMediaUrl('../private.jpg', __DIR__) === '', 'unsafe media path rejected');
$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_product_detail.php');
$out = shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"]="POST"; require '.var_export($endpoint, true).';').' 2>&1');
pdpAssert(strpos((string)$out, 'METHOD_NOT_ALLOWED') !== false, 'POST rejected with 405');
$out = shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"]="GET"; $_GET["slug"]="Bad Slug"; require '.var_export($endpoint, true).';').' 2>&1');
pdpAssert(strpos((string)$out, 'BAD_QUERY') !== false, 'invalid slug rejected with 400');
echo "PASS: PTS public product detail API contract\n";
