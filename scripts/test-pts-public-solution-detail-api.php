<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_solution_detail_api.php';
function solutionDetailAssert($condition, $message) { if(!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } echo "PASS: $message\n"; }
function solutionHubSelect(array $rows, $flag = '', $limit = 0) { $selected = array(); foreach($rows as $row) { if(!ptsSolutionDetailPublished($row['status'] ?? '')) continue; if($flag !== '' && strpos((string)($row['status'] ?? ''), $flag) === false) continue; $selected[] = $row; if($limit && count($selected) >= $limit) break; } return $selected; }
function publicProductCategoryPath(array $row, $kind, $lang) { return 'danh-muc-san-pham/'.($row['slugvi'] ?? 'category').'/'; }
$row = array('id'=>7,'namevi'=>'Giải pháp thật','descvi'=>'Tóm tắt <b>an toàn</b>','slugvi'=>'giai-phap-that','status'=>'hienthi,solutionhub','contentvi'=>'<p>Đoạn một.</p><script>alert(1)</script><p>Đoạn hai.</p>','content1vi'=>'<p>Thông tin thêm.</p>','_pts_public_photo'=>'/upload/news/main.jpg','_pts_public_photo1'=>'');
$solutions = array($row, array('id'=>8,'namevi'=>'Ẩn','slugvi'=>'an','status'=>'solutionhub'));
$products = array(array('id'=>3,'namevi'=>'Thiết bị thật','descvi'=>'Mô tả','slugvi'=>'thiet-bi','status'=>'hienthi,solutionhub','_pts_public_photo'=>''));
$payload = ptsSolutionDetailPayload($row, array(array('photo'=>'/upload/news/gallery.jpg')), $solutions, $products);
solutionDetailAssert($payload['solution']['content']['format'] === 'sanitized-html' && $payload['solution']['content']['sanitizedBy'] === 'pts_public_richtext' && strpos($payload['solution']['content']['html'], '<script') === false && strpos($payload['solution']['content']['html'], 'alert(1)') === false, 'content da sanitize qua pts_public_richtext, loai bo script');
solutionDetailAssert(strpos($payload['solution']['content']['html'], 'Đoạn một.') !== false && strpos($payload['solution']['content']['html'], 'Đoạn hai.') !== false, 'content giu lai van ban thuc giua cac doan');
solutionDetailAssert($payload['solution']['extraContent']['format'] === 'sanitized-html' && strpos($payload['solution']['extraContent']['html'], 'Thông tin thêm.') !== false, 'extraContent sanitize tu content1vi');

solutionDetailAssert($payload['solution']['gallery'] === array('/upload/news/gallery.jpg'), 'gallery giữ ảnh đã được allowlist');
solutionDetailAssert(count($payload['navigation']['items']) === 1 && $payload['navigation']['items'][0]['active'] === true, 'navigation chỉ lấy solution published + solutionhub');
solutionDetailAssert($payload['cta']['href'] === '/lien-he/#yeu-cau-tu-van', 'CTA trỏ về form legacy');
solutionDetailAssert(ptsSolutionDetailSlug('../unsafe') === '' && ptsSolutionDetailSlug('valid-slug') === 'valid-slug', 'slug validator chặn path traversal');
echo "Solution detail API helper tests completed.\n";
