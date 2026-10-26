<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_catalog_api.php';
function catalogAssert($condition, $message) { if(!$condition) throw new RuntimeException($message); echo "PASS: {$message}\n"; }

$dto = ptsCatalogProductDto(array('id'=>1,'namevi'=>'<b>Đèn</b>','slugvi'=>'den-1','status'=>'hienthi','photo'=>'ok.jpg','regular_price'=>100));
catalogAssert($dto['url'] === '/san-pham/den-1/', 'canonical product URL');
catalogAssert(ptsCatalogProductDto(array('id'=>2,'namevi'=>'Ẩn','slugvi'=>'an','status'=>'','photo'=>'ok.jpg')) === null, 'hidden record omitted');
catalogAssert(ptsCatalogProductDto(array('id'=>3,'namevi'=>'Sai','slugvi'=>'bad slug','status'=>'hienthi')) === null, 'invalid record omitted');
$dto = ptsCatalogProductDto(array('id'=>4,'namevi'=>'Sản phẩm','slugvi'=>'san-pham','status'=>'hienthi','_pts_public_photo'=>'ok.webp','_pts_public_photo2'=>'../private.jpg','secret'=>'SHOULD_NOT_LEAK','sale_price'=>10));
catalogAssert($dto['media'] === array('/upload/product/ok.webp'), 'validated media only');
$payload = ptsCatalogPayload(array(), array(array('id'=>9,'namevi'=>'Danh mục','slugvi'=>'den','status'=>'hienthi')), array(array('id'=>4,'namevi'=>'Sản phẩm','slugvi'=>'san-pham','status'=>'hienthi')), 2, 48, 100);
catalogAssert($payload['pagination']['page'] === 2 && $payload['pagination']['perPage'] === 48, 'pagination bounds fixture');
catalogAssert($payload['parity']['prices']['included'] === false && !isset($payload['products'][0]['sale_price']), 'price/private fields omitted');
catalogAssert(strpos(json_encode($payload), 'SHOULD_NOT_LEAK') === false, 'private field omission');
$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_catalog.php');
$command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"] = "POST"; require '.var_export($endpoint, true).';').' 2>&1';
$methodOutput = shell_exec($command);
catalogAssert(strpos((string)$methodOutput, 'METHOD_NOT_ALLOWED') !== false, 'POST rejected with 405');
foreach(array('page' => 'invalid', 'per_page' => '0', 'category' => array('nested')) as $key => $invalid)
{
    $input = var_export(array($key => $invalid), true);
    $source = '$_SERVER["REQUEST_METHOD"] = "GET"; $_GET = '.$input.'; require '.var_export($endpoint, true).';';
    $out = shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($source).' 2>&1');
    catalogAssert(strpos((string)$out, 'BAD_QUERY') !== false, 'invalid '.$key.' rejected with 400');
}
echo "PASS: PTS public catalog API contract\n";
