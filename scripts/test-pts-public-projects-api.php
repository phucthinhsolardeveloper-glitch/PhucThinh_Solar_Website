<?php
error_reporting(E_ALL);
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_projects_api.php';

function projectsAssert($condition, $message)
{
    if(!$condition) throw new RuntimeException($message);
    echo 'PASS: ', $message, "\n";
}

$categories = array(array('id'=>7, 'namevi'=>'Hộ gia đình', 'slugvi'=>'du-an-ho-gia-dinh', 'status'=>'hienthi', '_pts_public_photo'=>''));
$rows = array(
    array('id'=>9, 'id_list'=>7, 'namevi'=>'Công trình <b>thực tế</b>', 'slugvi'=>'cong-trinh-thuc-te', 'descvi'=>'<p>Mô tả CMS</p>', 'address'=>'Gia Lai', 'status'=>'hienthi', '_pts_public_photo'=>'project.jpg'),
    array('id'=>10, 'id_list'=>7, 'namevi'=>'Dự án nổi bật', 'slugvi'=>'du-an-noi-bat', 'descvi'=>'', 'address'=>'', 'status'=>'hienthi,projecthub', '_pts_public_photo'=>'featured.jpg'),
    array('id'=>11, 'id_list'=>7, 'namevi'=>'Bị ẩn', 'slugvi'=>'bi-an', 'status'=>'projecthub', '_pts_public_photo'=>'hidden.jpg')
);
$cta = array(array('namevi'=>'Tư vấn công trình', 'descvi'=>'Nội dung CMS', 'content1vi'=>'Liên hệ', 'status'=>'hienthi', '_pts_public_photo'=>'cta.jpg'));
$payload = ptsProjectsPayload($categories, array($rows[0]), $rows[1], $cta, 1, 12, 2, ptsProjectsCategoryDto($categories[0]), '090 123');
projectsAssert($payload['schemaVersion'] === 1 && $payload['locale'] === 'vi', 'schema và locale vi');
projectsAssert($payload['featured']['slug'] === 'du-an-noi-bat' && $payload['projects'][0]['title'] === 'Công trình thực tế', 'featured và project dùng đúng CMS rows');
projectsAssert($payload['projects'][0]['description'] === 'Mô tả CMS' && $payload['projects'][0]['url'] === '/cong-trinh-thuc-te/', 'plain text và canonical URL');
projectsAssert($payload['cta']['image'] === '/upload/news/cta.jpg' && $payload['cta']['hotline']['tel'] === '090123', 'CTA và hotline lấy từ CMS/config');
$hidden = ptsProjectsPayload($categories, array(), array(), array(array('namevi'=>'Bị ẩn', 'status'=>'')), 1, 12, 0, null, '');
projectsAssert($hidden['cta'] === null, 'CTA bị ẩn không hồi sinh fallback');
$encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
projectsAssert(strpos($encoded, 'hidden.jpg') === false && strpos($encoded, '<b>') === false, 'DTO không lộ record ẩn hoặc HTML');

$endpoint = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_projects.php');
$command = escapeshellarg(PHP_BINARY).' -r '.escapeshellarg('$_SERVER["REQUEST_METHOD"] = "POST"; require '.var_export($endpoint, true).';').' 2>&1';
$methodOutput = shell_exec($command);
projectsAssert(strpos((string)$methodOutput, 'METHOD_NOT_ALLOWED') !== false, 'endpoint từ chối method khác GET');
echo "PASS: PTS public projects API contract\n";
