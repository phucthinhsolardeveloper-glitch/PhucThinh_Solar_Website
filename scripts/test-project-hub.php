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
    projectHubAssert(substr_count($html, 'class="pts-ph-card"') === $count, 'Render '.$count.' dự án, không tạo card giả');
    projectHubAssert(substr_count($html, '<a class="pts-ph-filter') === 3, 'Bộ lọc lấy Tất cả và nhóm Admin');
    projectHubAssert(strpos($html, 'store-manager/') === false && ($count === 0 || strpos($html, 'href="du-an-0/"') !== false), 'Card dùng URL canonical thay cho store-manager');
    projectHubAssert(strpos($html, '<b>Gia Lai</b>') === false && strpos($html, 'missing.png') === false, 'Escape metadata và không render ảnh thiếu');
    projectHubAssert(substr_count($html, '<h1 ') === 1, 'Giữ đúng một H1 trong module');
}
$d->rows = array('project-intro'=>array(array('status'=>'')), 'project-cta'=>array(array('status'=>'')), 'du-an'=>array());
$news = array(); $newsList = array(); require SOURCES.'project_hub.php';
ob_start(); require '/var/www/html/templates/news/project_tpl.php'; $html = ob_get_clean();
projectHubAssert(strpos($html, 'class="pts-ph-heading"') === false && strpos($html, 'class="pts-ph-contact"') === false, 'Admin tắt intro/CTA sẽ ẩn module');
projectHubAssert(substr_count($html, '<h1 ') === 1, 'Intro ẩn vẫn giữ H1 truy cập được');
