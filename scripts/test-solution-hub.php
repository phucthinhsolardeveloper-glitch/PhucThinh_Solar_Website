<?php
/* No DB writes: exercise actual source/template with isolated fixtures. */
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new RuntimeException($message); });
chdir('/var/www/html');
define('SOURCES', '/var/www/html/sources/');
define('LIBRARIES', '/var/www/html/libraries/');
define('UPLOAD_NEWS_L', 'upload/news/');
define('UPLOAD_PRODUCT_L', 'upload/product/');
define('ASSET', 'http://localhost:8080/');
require LIBRARIES.'solution_hub.php';
function hubAssert($ok, $label) { if(!$ok) throw new RuntimeException($label); echo 'PASS: ', $label, "\n"; }
class SolutionFixtureDb {
    public $rows = array();
    public function rawQuery($sql, $params) {
        $key = strpos($sql, '#_news_list') !== false ? 'giai-phap-list' : $params[0];
        return isset($this->rows[$key]) ? $this->rows[$key] : array();
    }
}
$d = new SolutionFixtureDb(); $lang = 'vi'; $optsetting = array();
foreach(array(0,1,2,4) as $count) {
    $d->rows = array('giai-phap' => array(), 'giai-phap-list' => array());
    for($i=0;$i<$count;$i++) {
        $d->rows['giai-phap-list'][] = array('id'=>$i+1,'namevi'=>'Nhóm '.$i,'nameen'=>'Group '.$i,'slugvi'=>'nhom-'.$i,'slugen'=>'group-'.$i,'status'=>'hienthi','photo'=>'missing.png','descvi'=>'');
        $d->rows['giai-phap'][] = array('id'=>$i+1,'id_list'=>$i+1,'namevi'=>'Giải pháp '.$i,'nameen'=>'Solution '.$i,'slugvi'=>'fixture-'.$i,'slugen'=>'fixture-en-'.$i,'status'=>'hienthi','photo'=>'missing.png','photo1'=>'','descvi'=>'<b>Mô tả</b> &lt;img src=x onerror=alert(1)&gt;');
    }
    require SOURCES.'solution_hub.php';
    ob_start(); require '/var/www/html/templates/news/service_list_tpl.php'; $html = ob_get_clean();
    hubAssert(substr_count($html, 'class="pts-sh-choice"') === $count, 'Render '.$count.' nhóm giải pháp; không dựng card giả');
    hubAssert($count === 0 || strpos($html, 'danh-muc-giai-phap/nhom-0/') !== false, 'Hub dẫn qua tầng nhóm giải pháp hiện hữu');
    hubAssert(strpos($html, 'src=x') === false && strpos($html, 'missing.png') === false, 'Không render HTML nguy hiểm/ảnh không tồn tại');
    hubAssert(strpos($html, 'tel:') === false, 'Không render hotline khi thiếu dữ liệu');
    hubAssert(substr_count($html,'<h1 ') === 1, 'Giữ đúng một H1');
}
$rows = array(array('id'=>1,'status'=>'solutionhub'),array('id'=>2,'status'=>'hienthi'));
hubAssert(solutionHubSelect($rows, 'solutionhub') === array(), 'Ẩn mục đã chọn không hồi sinh fallback');
$rows[0]['status'] = 'hienthi,solutionhub';
hubAssert(array_column(solutionHubSelect($rows,'solutionhub'),'id') === array(1), 'Cờ chọn loại các mục không được chọn');
$d->rows['solution-intro'] = array(array('status'=>''));
$d->rows['solution-cta'] = array(array('status'=>''));
require SOURCES.'solution_hub.php';
ob_start(); require '/var/www/html/templates/news/service_list_tpl.php'; $html = ob_get_clean();
hubAssert(strpos($html,'class="pts-sh-intro"') === false && strpos($html,'class="pts-sh-contact"') === false, 'Tắt module intro/CTA ẩn cả fallback');
hubAssert(substr_count($html,'<h1 ') === 1, 'Intro ẩn vẫn có H1');
hubAssert(solutionHubImage('../escape.png',UPLOAD_NEWS_L)==='', 'Ảnh không được vượt thư mục upload');
$lang='en'; $d->rows = array(); require SOURCES.'solution_hub.php';
hubAssert($solutionHeroTitle==='Choose the right solution for your needs', 'Nhãn tiếng Anh đúng locale');
hubAssert(solutionHubField(array('namevi'=>'Tên Việt'), 'name', 'en')==='Tên Việt', 'Dữ liệu chưa dịch có fallback');
