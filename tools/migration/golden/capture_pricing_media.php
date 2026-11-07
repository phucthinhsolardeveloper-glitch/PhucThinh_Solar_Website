<?php
/**
 * Golden master for pricing and the product image pick. For every product prints the PHP results of
 * ptsPdpPricingDto(), ptsCatalogPricingDto(), publicProductRequiresQuote() and
 * productImageResolveFromDatabase(). Prices and file names are not personal data.
 *
 *   docker cp tools/migration/golden/capture_pricing_media.php pts-php81-local:/tmp/capture.php
 *   docker exec pts-php81-local php /tmp/capture.php > backend/tests/fixtures/golden/pricing_media.json
 */
if (PHP_SAPI !== 'cli') exit(1);
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$setting = $d->rawQueryOne("select options from #_setting order by id limit 1");
$optsetting = json_decode((string) ($setting['options'] ?? ''), true) ?: array();
foreach (array('public_pricing.php', 'product_images.php', 'pts_public_catalog_api.php', 'pts_public_product_detail_api.php') as $file) require_once LIBRARIES.$file;

$out = array();
foreach ($d->rawQuery("select id, type, photo, status, regular_price, sale_price from #_product where type = 'san-pham' order by id") as $row) {
    $image = productImageResolveFromDatabase($d, $row, '/var/www/html/upload/product/');
    $out[(string) $row['id']] = array(
        'pdp' => ptsPdpPricingDto($row, publicProductPricesVisible()),
        'catalog' => ptsCatalogPricingDto($row, publicProductPricesVisible()),
        'requiresQuote' => publicProductRequiresQuote($row),
        'image' => $image,
    );
}
echo json_encode(array('generated' => date('c'), 'pricesVisible' => publicProductPricesVisible(), 'products' => $out), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
