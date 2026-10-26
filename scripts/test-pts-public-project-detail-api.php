<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_project_detail_api.php';
function projectDetailAssert($condition, $message) { if(!$condition) throw new RuntimeException($message); echo 'PASS: ', $message, "\n"; }
$row = array('id'=>7,'namevi'=>'Công trình <b>thật</b>','slugvi'=>'cong-trinh-that','descvi'=>'<p>Mô tả CMS</p>','contentvi'=>'<script>alert(1)</script>','photo'=>'cover.jpg','status'=>'hienthi','category_id'=>3,'category_name'=>'Hộ gia đình','category_slug'=>'ho-gia-dinh','tenkhachhang'=>'Khách CMS','address'=>'Địa chỉ CMS','project_start_date'=>'2026-01-02','project_end_date'=>'2026-02-03','_pts_cover'=>'/upload/news/cover.jpg');
$gallery = array(array('type'=>'image','src'=>'/upload/news/gallery.jpg','caption'=>'Ảnh CMS'),array('type'=>'video','src'=>'/upload/news/video.jpg','href'=>'https://video.example/watch','caption'=>'Video CMS'));
$related = array(array('id'=>9,'name'=>'Thiết bị CMS','slug'=>'thiet-bi-cms','url'=>'/san-pham/thiet-bi-cms/','code'=>'PT-9','image'=>'/upload/product/device.jpg'));
$payload = ptsProjectDetailPayload($row, $gallery, $related, array('project'=>'legacy /cong-trinh-that/'));
projectDetailAssert($payload['project']['url'] === '/cong-trinh-that/' && $payload['project']['title'] === 'Công trình thật', 'plain text và canonical URL');
projectDetailAssert($payload['project']['client'] === 'Khách CMS' && $payload['project']['startDate'] === '2026-01-02', 'field chi tiết chỉ lấy từ CMS');
projectDetailAssert($payload['content']['rendered'] === false && strpos(json_encode($payload), '<script>') === false, 'không render hoặc lộ HTML editorial');
projectDetailAssert($payload['project']['relatedEquipment'][0]['url'] === '/san-pham/thiet-bi-cms/', 'related equipment giữ canonical product URL');
projectDetailAssert(ptsProjectDetailSlug('../secret') === '' && ptsProjectDetailSlug('Good Slug') === '', 'chặn traversal và slug sai');
$hidden = $row; $hidden['status'] = ''; projectDetailAssert(ptsProjectDetailPayload($hidden, array(), array()) === null, 'record ẩn không được hồi sinh');
$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_project_detail.php');
$command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"] = "POST"; require '.var_export($endpoint, true).';').' 2>&1';
projectDetailAssert(strpos((string)shell_exec($command), 'METHOD_NOT_ALLOWED') !== false, 'endpoint từ chối method khác GET');
echo "PASS: PTS public project detail API contract\n";
