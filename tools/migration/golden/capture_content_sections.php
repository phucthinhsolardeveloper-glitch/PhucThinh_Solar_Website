<?php
/**
 * Golden master for product content inheritance: runs the PHP-era productResolveSections() for every
 * product and prints {id: [source, sha256(blocks json)]}. Only hashes leave the database.
 *
 * Run inside the legacy PHP container (it reads that container's database):
 *   docker cp tools/migration/golden/capture_content_sections.php pts-php81-local:/tmp/capture.php
 *   docker exec pts-php81-local php /tmp/capture.php > backend/tests/fixtures/golden/content_sections.json
 * The Laravel side must read a clone made from the same database (see docs/16-laravel-migration).
 */
if (PHP_SAPI !== 'cli') exit(1);
$_SERVER['SERVER_NAME'] = 'localhost';
define('LIBRARIES', '/var/www/html/libraries/');
require LIBRARIES.'config.php';
require LIBRARIES.'autoload.php';
new AutoLoad();
require_once LIBRARIES.'product_content_blocks.php';
$d = new PDODb($config['database']);

$out = array();
foreach ($d->rawQuery("select id, id_brand, id_cat, id_list from #_product where type = 'san-pham' order by id") as $row) {
    $resolved = productResolveSections($d, $row, 'vi');
    $out[(string) $row['id']] = array($resolved['source'], hash('sha256', json_encode($resolved['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
}
echo json_encode(array('generated' => date('c'), 'products' => $out), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
