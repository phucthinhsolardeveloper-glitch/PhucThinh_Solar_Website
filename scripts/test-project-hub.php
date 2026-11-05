<?php
/* No DB writes: render the real project source/template with isolated fixtures. */
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new RuntimeException($message); });
chdir('/var/www/html');
define('SOURCES', '/var/www/html/sources/');
define('LIBRARIES', '/var/www/html/libraries/');
define('TEMPLATE', '/var/www/html/templates/');
define('UPLOAD_NEWS_L', 'upload/news/');
define('ASSET', 'http://localhost:8080/');
require LIBRARIES.'solution_hub.php';
function projectHubAssert($ok, $label) { if(!$ok) throw new RuntimeException($label); echo 'PASS: ', $label, "\n"; }
class ProjectHubFixtureDb {
    public $rows = array();
    public function rawQuery($sql, $params) { return isset($this->rows[$params[0]]) ? $this->rows[$params[0]] : array(); }
}
class ProjectHubFixtureFunc { public function getThumbUrl($size, $path) { return 'thumbs/'.$size.'/'.$path; } }
$func = new ProjectHubFixtureFunc();
$d = new ProjectHubFixtureDb(); $lang = 'vi'; $sluglang = 'slugvi'; $optsetting = array(); $paging = '';
foreach(array(0, 4) as $count) {
    $d->rows = array(
        'du-an' => array(
            array('id'=>5,'namevi'=>'Doanh nghiệp','nameen'=>'Business','descvi'=>'','descen'=>'','photo'=>'missing.png','slugvi'=>'du-an-doanh-nghiep','slugen'=>'business-projects','status'=>'hienthi'),
            array('id'=>6,'namevi'=>'Hộ gia đình','nameen'=>'Household','descvi'=>'','descen'=>'','photo'=>'missing.png','slugvi'=>'du-an-ho-gia-dinh','slugen'=>'household-projects','status'=>'hienthi')
        )
    );
    $news = array(); $newsList = array();
    for($i=0;$i<$count;$i++) $news[] = array('id'=>$i+1,'id_list'=>6,'namevi'=>'Dự án '.$i,'slugvi'=>'du-an-'.$i,'slugen'=>'project-'.$i,'photo'=>'missing.png','address'=>'<b>Gia Lai</b>','tenkhachhang'=>'','ngayhopdong'=>'');
    require SOURCES.'project_hub.php';
    ob_start(); require '/var/www/html/templates/news/project_tpl.php'; $html = ob_get_clean();
    /* 2026-09-14: trang danh sách gốc (chưa lọc danh mục, trang 1) giờ tách 1
       dự án ra khối "Dự án nổi bật" (.pts-ph-featured) riêng khỏi lưới
       (.pts-ph-card) - tổng 2 loại vẫn phải khớp đúng $count, không tạo/mất
       dự án nào. */
    $projectFeaturedRendered = substr_count($html, 'class="pts-ph-featured"');
    $projectCardRendered = substr_count($html, 'class="pts-ph-card"');
    projectHubAssert($projectFeaturedRendered === ($count > 0 ? 1 : 0), 'Tách đúng 1 dự án nổi bật khi có dữ liệu ('.$count.')');
    projectHubAssert($projectCardRendered === $count - $projectFeaturedRendered, 'Render '.$count.' dự án (nổi bật + lưới), không tạo card giả');
    projectHubAssert(substr_count($html, '<a class="pts-ph-filter') === 3, 'Bộ lọc lấy Tất cả và nhóm Admin');
    projectHubAssert(strpos($html, 'store-manager/') === false && ($count === 0 || strpos($html, 'href="du-an-0/"') !== false), 'Card dùng URL canonical thay cho store-manager');
    projectHubAssert(strpos($html, '<b>Gia Lai</b>') === false && strpos($html, 'missing.png') === false, 'Escape metadata và không render ảnh thiếu');
    projectHubAssert(substr_count($html, '<h1 ') === 1, 'Giữ đúng một H1 trong module');
}
/* 2026-09-14: khối nổi bật CHỈ hiện ở trang danh sách gốc, trang 1 - ẩn khi
   đã lọc theo danh mục (dù có dữ liệu) và khi sang trang 2, giống nguyên tắc
   đã dùng ở News Hub. */
$d->rows = array('du-an' => array(array('id'=>6,'namevi'=>'Hộ gia đình','nameen'=>'','descvi'=>'','descen'=>'','photo'=>'missing.png','slugvi'=>'du-an-ho-gia-dinh','slugen'=>'','status'=>'hienthi')));
$news = array(); for($i=0;$i<4;$i++) $news[] = array('id'=>$i+1,'id_list'=>6,'namevi'=>'Dự án '.$i,'slugvi'=>'du-an-'.$i,'slugen'=>'project-'.$i,'photo'=>'missing.png','address'=>'','tenkhachhang'=>'','ngayhopdong'=>'');
$newsList = array('id' => 6);
require SOURCES.'project_hub.php';
ob_start(); require '/var/www/html/templates/news/project_tpl.php'; $html = ob_get_clean();
projectHubAssert(strpos($html, 'class="pts-ph-featured"') === false && substr_count($html, 'class="pts-ph-card"') === 4, 'Đã lọc danh mục thì ẩn khối nổi bật, giữ đủ dự án ở lưới');

$_GET['p'] = '2';
$newsList = array();
require SOURCES.'project_hub.php';
ob_start(); require '/var/www/html/templates/news/project_tpl.php'; $html = ob_get_clean();
projectHubAssert(strpos($html, 'class="pts-ph-featured"') === false && substr_count($html, 'class="pts-ph-card"') === 4, 'Sang trang 2 thì ẩn khối nổi bật, giữ đủ dự án ở lưới');
unset($_GET['p']);

/* Cờ "projecthub" (Nổi bật trang Dự án) phải được ưu tiên chọn làm nổi bật
   thay vì luôn lấy dòng đầu tiên. */
$news = array(
    array('id'=>1,'id_list'=>6,'namevi'=>'Dự án A','slugvi'=>'du-an-a','slugen'=>'','photo'=>'missing.png','address'=>'','tenkhachhang'=>'','ngayhopdong'=>'','status'=>'hienthi'),
    array('id'=>2,'id_list'=>6,'namevi'=>'Dự án B (nổi bật)','slugvi'=>'du-an-b','slugen'=>'','photo'=>'missing.png','address'=>'','tenkhachhang'=>'','ngayhopdong'=>'','status'=>'projecthub,hienthi'),
);
$newsList = array();
require SOURCES.'project_hub.php';
ob_start(); require '/var/www/html/templates/news/project_tpl.php'; $html = ob_get_clean();
projectHubAssert(strpos($html, 'href="du-an-b/"') !== false && strpos($html, '>Dự án B (nổi bật)<') !== false, 'Cờ "Nổi bật trang Dự án" được ưu tiên chọn làm khối nổi bật');

/* project-benefit/project-trust từ Admin phải thay hẳn 3 gạch đầu dòng/3 icon
   tin cậy mặc định trong code, không hiện lẫn cả hai. */
$d->rows['project-benefit'] = array(array('namevi'=>'Lợi ích Admin nhập', 'nameen'=>'', 'photo'=>'missing.png', 'status'=>'hienthi'));
$d->rows['project-trust'] = array(array('namevi'=>'Tin cậy Admin nhập', 'nameen'=>'', 'photo'=>'missing.png', 'status'=>'hienthi'));
require SOURCES.'project_hub.php';
ob_start(); require '/var/www/html/templates/news/project_tpl.php'; $html = ob_get_clean();
projectHubAssert(strpos($html, 'Lợi ích Admin nhập') !== false && strpos($html, 'Khảo sát và tư vấn') === false, 'project-benefit từ Admin thay đúng 3 gạch đầu dòng mặc định');
projectHubAssert(strpos($html, 'Tin cậy Admin nhập') !== false && strpos($html, 'Tư vấn tận tâm') === false, 'project-trust từ Admin thay đúng 3 icon CTA mặc định');
unset($d->rows['project-benefit'], $d->rows['project-trust']);

$d->rows = array('project-intro'=>array(array('status'=>'')), 'project-cta'=>array(array('status'=>'')), 'du-an'=>array());
$news = array(); $newsList = array(); require SOURCES.'project_hub.php';
ob_start(); require '/var/www/html/templates/news/project_tpl.php'; $html = ob_get_clean();
projectHubAssert(strpos($html, 'class="pts-ph-heading"') === false && strpos($html, 'class="pts-ph-contact"') === false, 'Admin tắt intro/CTA sẽ ẩn module');
projectHubAssert(substr_count($html, '<h1 ') === 1, 'Intro ẩn vẫn giữ H1 truy cập được');
