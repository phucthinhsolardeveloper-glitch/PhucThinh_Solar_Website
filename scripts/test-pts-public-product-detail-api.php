<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/product_pdp_profiles.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/product_specs.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_product_detail_api.php';
function pdpAssert($condition, $message) { if(!$condition) throw new RuntimeException($message); echo "PASS: {$message}\n"; }
$row = array('id'=>7,'namevi'=>'<b>Đèn thật</b>','slugvi'=>'den-that','status'=>'hienthi','code'=>'PT-7','descvi'=>'<p>Mô tả thật</p>','pdp_profile'=>'lighting','cong_suat'=>30,'additional_specs'=>json_encode(array(array('key'=>'quang_thong','label'=>'Quang thông','value'=>'3.000 lm'),array('key'=>'secret','label'=>'Secret','value'=>'x')), JSON_UNESCAPED_UNICODE));
$payload = ptsPdpPayload($row, array('/upload/product/real.webp','/upload/product/real.webp'), ptsPdpSpecDto($row), 'san-pham/den-that', array('product'=>'publicProductEntityPath(product)'));
pdpAssert($payload['product']['url'] === '/san-pham/den-that/', 'canonical product URL');
pdpAssert($payload['product']['name'] === 'Đèn thật' && $payload['product']['summary'] === 'Mô tả thật', 'plain text product fields');
pdpAssert($payload['product']['media'] === array('/upload/product/real.webp'), 'duplicate media removed');
pdpAssert($payload['pricing']['included'] === false && !isset($payload['product']['price']), 'price omitted without pricing delegation');
pdpAssert(strpos(json_encode($payload), 'secret') === false, 'unlisted additional field omitted');
pdpAssert(ptsPdpMediaUrl('../private.jpg', __DIR__) === '', 'unsafe media path rejected');
$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_product_detail.php');
$out = shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"]="POST"; require '.var_export($endpoint, true).';').' 2>&1');
pdpAssert(strpos((string)$out, 'METHOD_NOT_ALLOWED') !== false, 'POST rejected with 405');
$out = shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"]="GET"; $_GET["slug"]="Bad Slug"; require '.var_export($endpoint, true).';').' 2>&1');
pdpAssert(strpos((string)$out, 'BAD_QUERY') !== false, 'invalid slug rejected with 400');
echo "PASS: PTS public product detail API contract\n";
