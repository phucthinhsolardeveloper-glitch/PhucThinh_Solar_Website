<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/product_price_filter.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_catalog_api.php';
function catalogAssert($condition, $message) { if(!$condition) throw new RuntimeException($message); echo "PASS: {$message}\n"; }

$dto = ptsCatalogProductDto(array('id'=>1,'namevi'=>'<b>Đèn</b>','slugvi'=>'den-1','status'=>'hienthi','photo'=>'ok.jpg','regular_price'=>2648100,'sale_price'=>2490000,'cong_suat'=>650), true);
catalogAssert($dto['url'] === '/san-pham/den-1', 'canonical product URL has no trailing slash (matches legacy router)');
catalogAssert($dto['powerWatts'] === 650.0, 'positive product power projected');
catalogAssert($dto['pricing'] === array('mode'=>'price','currency'=>'VND','regularPrice'=>2648100,'salePrice'=>2490000), 'authorized public pricing projected');
catalogAssert(ptsCatalogProductDto(array('id'=>5,'namevi'=>'Báo giá','slugvi'=>'bao-gia','status'=>'hienthi,baogia','regular_price'=>999000), true)['pricing']['mode'] === 'quote', 'per-product quote status suppresses price');
catalogAssert(ptsCatalogProductDto(array('id'=>2,'namevi'=>'Ẩn','slugvi'=>'an','status'=>'','photo'=>'ok.jpg')) === null, 'hidden record omitted');
catalogAssert(ptsCatalogProductDto(array('id'=>3,'namevi'=>'Sai','slugvi'=>'bad slug','status'=>'hienthi')) === null, 'invalid record omitted');
$dto = ptsCatalogProductDto(array('id'=>4,'namevi'=>'Sản phẩm','slugvi'=>'san-pham','status'=>'hienthi','_pts_public_photo'=>'ok.webp','_pts_public_photo2'=>'../private.jpg','secret'=>'SHOULD_NOT_LEAK','sale_price'=>10));
catalogAssert($dto['media'] === array('/upload/product/ok.webp'), 'validated media only');
$payload = ptsCatalogPayload(array(), array(array('id'=>9,'namevi'=>'Danh mục','slugvi'=>'den','status'=>'hienthi')), array(array('id'=>4,'namevi'=>'Sản phẩm','slugvi'=>'san-pham','status'=>'hienthi')), 2, 48, 100, true);
catalogAssert($payload['seo'] === array('title'=>'Sản phẩm | Phúc Thịnh Solar','description'=>'','keywords'=>'','image'=>'') && $payload['canonical'] === array('url'=>'/san-pham/'), 'root catalog SEO defaults and canonical path are allow-listed');
$seoPayload = ptsCatalogPayload(array('id'=>9,'namevi'=>'<b>Đèn</b>','slugvi'=>'den','status'=>'hienthi'), array(), array(), 1, 20, 0, true, array(), array(), array('seoFallbackTitle'=>'Đèn','seoRow'=>array('titlevi'=>'<b>Đèn pha | Phúc Thịnh Solar</b>','descriptionvi'=>'<p>Chiếu sáng an toàn</p>','keywordsvi'=>'đèn pha, năng lượng'),'canonicalPath'=>'/danh-muc-san-pham/den/'));
catalogAssert($seoPayload['seo'] === array('title'=>'Đèn pha | Phúc Thịnh Solar','description'=>'Chiếu sáng an toàn','keywords'=>'đèn pha, năng lượng','image'=>'') && $seoPayload['canonical'] === array('url'=>'/danh-muc-san-pham/den/'), 'category Admin SEO and canonical are sanitized');
$ogPayload = ptsCatalogPayload(array('id'=>9,'namevi'=>'Đèn','slugvi'=>'den','status'=>'hienthi'), array(), array(), 1, 20, 0, true, array(), array(), array('seoRow'=>array(), 'seoImage'=>'/upload/product/category.webp'));
catalogAssert($ogPayload['seo']['image'] === '/upload/product/category.webp', 'category SEO image comes from a sanitized taxonomy image');
$unsafeOgPayload = ptsCatalogPayload(array(), array(), array(), 1, 20, 0, true, array(), array(), array('seoImage'=>'/upload/product/../private.jpg'));
catalogAssert($unsafeOgPayload['seo']['image'] === '', 'unsafe SEO image is omitted');
$invalidCanonicalPayload = ptsCatalogPayload(array(), array(), array(), 1, 20, 0, true, array(), array(), array('canonicalPath'=>'https://evil.example/'));
catalogAssert($invalidCanonicalPayload['canonical'] === array('url'=>'/san-pham/'), 'catalog canonical rejects external paths');
catalogAssert($payload['pagination']['page'] === 2 && $payload['pagination']['perPage'] === 48, 'pagination bounds fixture');
catalogAssert($payload['parity']['prices']['included'] === true && !isset($payload['products'][0]['sale_price']), 'price authority exposed without leaking database fields');
catalogAssert(strpos(json_encode($payload), 'SHOULD_NOT_LEAK') === false, 'private field omission');
catalogAssert($payload['parity']['filters'] === array('included'=>true) && $payload['parity']['sort'] === array('included'=>true), 'filter/sort parity nay da that, khong con legacy_*_parity_not_verified');
catalogAssert($payload['filters'] === array('brands'=>array(),'priceRanges'=>$payload['filters']['priceRanges'],'subcategories'=>array(),'needs'=>array()) && count($payload['filters']['priceRanges']) === 3, 'filters bao gom 3 bac gia va needs rong khi khong truyen brand/subcategory');
catalogAssert($payload['selected'] === array('brandIds'=>array(),'priceKeys'=>array(),'subcategoryIds'=>array(),'sort'=>'default','needKeys'=>array()), 'selected mac dinh rong khi khong co filter nao duoc chon');
catalogAssert($payload['categoryHero'] === null && $payload['categoryContent'] === array('title'=>'','applications'=>array(),'faq'=>array()) && $payload['support'] === null, 'extra catalog content defaults to empty instead of fabricated CMS fields');
$publicHtmlRoot = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html');
$lightingHero = ptsCatalogCategoryHeroFallback('danh-muc-san-pham/den-nlmt', $publicHtmlRoot);
catalogAssert($lightingHero === array('photo'=>'catalog-lighting-overview-20261008.png','upload'=>'assets/images/site-visuals/','alt'=>'Tổng quan giải pháp chiếu sáng năng lượng mặt trời'), 'catalog hero fallback matches the published PHP render contract');
catalogAssert(ptsCatalogCategoryHeroFallback('danh-muc-san-pham/den-nlmt/den-ufo', $publicHtmlRoot) !== null, 'nested category PHP hero fallback is available');
catalogAssert(ptsCatalogCategoryHeroFallback('danh-muc-san-pham/not-published', $publicHtmlRoot) === null, 'unknown category receives no fabricated hero image');

catalogAssert(ptsCatalogBrandDto(array('id'=>5,'namevi'=>'<b>LONGI</b>')) === array('id'=>5,'name'=>'LONGI'), 'brand DTO strip HTML va giu id that');
catalogAssert(ptsCatalogBrandDto(array('id'=>0,'namevi'=>'x')) === null, 'brand DTO loai id khong hop le');
catalogAssert(ptsCatalogSubcategoryDto(array('id'=>7,'namevi'=>'Kẹp tấm pin','slugvi'=>'kep-tam-pin','status'=>'hienthi')) === array('id'=>7,'name'=>'Kẹp tấm pin','slug'=>'kep-tam-pin'), 'subcategory DTO day du');
catalogAssert(ptsCatalogSubcategoryDto(array('id'=>7,'namevi'=>'Ẩn','slugvi'=>'an','status'=>'')) === null, 'subcategory an khong duoc tra ve');
catalogAssert(count(ptsCatalogPriceRangeDto()) === 3 && ptsCatalogPriceRangeDto()[0]['key'] === '1', 'price range DTO dung 3 bac tu productPriceFilterRanges()');

catalogAssert(ptsCatalogSortKey('abc') === 'abc' && ptsCatalogSortKey('relevance-hack') === 'default', 'sort key whitelist loai gia tri la');
catalogAssert(ptsCatalogSortOrderBy('abc', true) === 'p.namevi asc' && ptsCatalogSortOrderBy('price', false) === null, 'sort theo gia tra null khi gia cong khai dang tat, khong tu suy doan thu tu');
catalogAssert(ptsCatalogSortOrderBy('price', true) === 'p.regular_price asc' && ptsCatalogSortOrderBy('new', true) === 'p.date_created desc', 'sort order by dung cac truong hop con lai');

$filteredPayload = ptsCatalogPayload(array(), array(), array(), 1, 20, 0, true,
    array('brands' => array(array('id'=>5,'namevi'=>'LONGI'), array('id'=>0,'namevi'=>'bad'))),
    array('brandIds' => array(5, 5, -1), 'priceKeys' => array('2'), 'sort' => 'abc'));
catalogAssert($filteredPayload['filters']['brands'] === array(array('id'=>5,'name'=>'LONGI')), 'filter options loai brand id khong hop le');
catalogAssert($filteredPayload['selected']['brandIds'] === array(5, -1), 'selected brandIds giu nguyen gia tri da chon (bao gom so am) de UI echo dung, validation that su xay ra o entrypoint');
$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_catalog.php');
$command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"] = "POST"; require '.var_export($endpoint, true).';').' 2>&1';
$methodOutput = shell_exec($command);
catalogAssert(strpos((string)$methodOutput, 'METHOD_NOT_ALLOWED') !== false, 'POST rejected with 405');
foreach(array('page' => 'invalid', 'per_page' => '0', 'category' => array('nested'), 'brand' => 'abc', 'price' => '9', 'subcategory' => '0,1', 'sort' => 'UPPER-CASE') as $key => $invalid)
{
    $input = var_export(array($key => $invalid), true);
    $source = '$_SERVER["REQUEST_METHOD"] = "GET"; $_GET = '.$input.'; require '.var_export($endpoint, true).';';
    $out = shell_exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($source).' 2>&1');
    catalogAssert(strpos((string)$out, 'BAD_QUERY') !== false, 'invalid '.$key.' rejected with 400');
}
echo "PASS: PTS public catalog API contract\n";
