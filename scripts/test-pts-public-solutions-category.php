<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/solution_hub.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_solutions_api.php';
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_seo_api.php';
if(!defined('ROOT')) define('ROOT', '/tmp/pts-test-root/');
if(!defined('UPLOAD_NEWS_L')) define('UPLOAD_NEWS_L', 'upload/news/');

function solutionCategoryAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException($message);
    echo 'PASS: ', $message, "\n";
}

$group = array('id'=>4,'namevi'=>'Điện mặt trời','descvi'=>'<p>Giải pháp cho <strong>nhà xưởng</strong></p>','slugvi'=>'dien-mat-troi','status'=>'hienthi');
$groups = array(
    $group,
    array('id'=>5,'namevi'=>'Bơm nước','descvi'=>'','slugvi'=>'bom-nuoc','status'=>'hienthi')
);
$rows = array(
    array('id'=>7,'id_list'=>4,'namevi'=>'Điện mái xưởng','slugvi'=>'dien-mai-xuong','descvi'=>'<p>Tư vấn thực tế</p>','status'=>'hienthi','_pts_public_photo'=>'/upload/news/mai-xuong.jpg'),
    array('id'=>8,'id_list'=>4,'namevi'=>'Bài ẩn','slugvi'=>'bai-an','status'=>''),
    array('id'=>9,'id_list'=>5,'namevi'=>'Bơm nước','slugvi'=>'bom-nuoc','status'=>'hienthi')
);
$dto = ptsPublicSolutionCategoryDto($group, $groups, $rows, array('title'=>'Điện mặt trời','description'=>'Mô tả SEO','keywords'=>'solar'));
solutionCategoryAssert($dto['id'] === 4 && $dto['name'] === 'Điện mặt trời' && $dto['description'] === 'Giải pháp cho nhà xưởng', 'category DTO trả nội dung CMS đã làm sạch');
solutionCategoryAssert($dto['slug'] === 'dien-mat-troi' && $dto['canonicalPath'] === '/dien-mat-troi', 'category DTO giữ slug và canonical path API hiện hành');
solutionCategoryAssert(count($dto['cards']) === 1 && $dto['cards'][0]['url'] === '/giai-phap/dien-mai-xuong/' && $dto['cards'][0]['image'] === '/upload/news/mai-xuong.jpg', 'category cards chỉ chứa nội dung đã xuất bản đúng nhóm');
solutionCategoryAssert($dto['navigation'][0]['active'] === true && $dto['navigation'][1]['url'] === '/giai-phap/bom-nuoc/', 'navigation đánh dấu nhóm hiện tại và ưu tiên landing giải pháp đại diện');
solutionCategoryAssert($dto['seo']['title'] === 'Điện mặt trời' && strpos(json_encode($dto, JSON_UNESCAPED_UNICODE), 'Bài ẩn') === false, 'SEO được allowlist và record ẩn không lọt vào DTO');
$fixtureRoot = sys_get_temp_dir().'/pts-solutions-category-seo-'.bin2hex(random_bytes(5));
$fixtureUpload = $fixtureRoot.'/upload/news';
if(!mkdir($fixtureUpload, 0700, true)) throw new RuntimeException('Không tạo được thư mục fixture SEO tạm');
$fixtureImage = $fixtureUpload.'/taxonomy.png';
try
{
    if(file_put_contents($fixtureImage, 'fixture') === false) throw new RuntimeException('Không tạo được ảnh fixture SEO tạm');
    $categorySeo = ptsPublicSeoDto(array(), $fixtureRoot, 'upload/news/', 'taxonomy.png');
    $categoryWithSeo = ptsPublicSolutionCategoryDto($group, $groups, $rows, $categorySeo);
    solutionCategoryAssert(
        $categoryWithSeo['seo']['image'] === '/upload/news/taxonomy.png',
        'SEO DTO nhận tên file taxonomy và trả đường dẫn ảnh sau khi kiểm tra file tồn tại'
    );
}
finally
{
    if(is_file($fixtureImage)) unlink($fixtureImage);
    if(is_dir($fixtureUpload)) rmdir($fixtureUpload);
    if(is_dir($fixtureRoot.'/upload')) rmdir($fixtureRoot.'/upload');
    if(is_dir($fixtureRoot)) rmdir($fixtureRoot);
}
solutionCategoryAssert(ptsPublicSolutionCategoryDto($group, $groups, array()) === null, 'category không có card published được loại');
solutionCategoryAssert(ptsPublicSolutionCategoryDto(array_merge($group, array('slugvi'=>'invalid slug')), $groups, $rows) === null, 'category có slug không hợp lệ bị loại');
echo "PASS: PTS public solutions category contract\n";
?>
