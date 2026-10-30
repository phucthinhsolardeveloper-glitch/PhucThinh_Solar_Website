<?php
$publicRoot = getenv('PTS_PUBLIC_ROOT');
if(!$publicRoot) {
    $repoRoot = realpath(__DIR__.'/../domains/phucthinhsolar.com/public_html');
    $publicRoot = $repoRoot ?: (is_dir('/var/www/html/libraries') ? '/var/www/html' : '');
}
if(!$publicRoot) { fwrite(STDERR, "FAIL: public root not found\n"); exit(1); }
require_once rtrim($publicRoot, '/').'/libraries/pts_public_media_library_api.php';
require_once rtrim($publicRoot, '/').'/libraries/pts_public_seo_api.php';

function ptsAssert($condition, $message)
{
    if(!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
    echo "PASS: {$message}\n";
}

ptsAssert(ptsMediaPublished('hienthi,noibat') === true, 'published status accepted');
ptsAssert(ptsMediaPublished('noibat') === false, 'hidden status rejected');
ptsAssert(ptsMediaSlug('album-thuc-te') === 'album-thuc-te', 'safe slug accepted');
ptsAssert(ptsMediaSlug('../album') === '', 'slug traversal rejected');
ptsAssert(ptsMediaProductImage('anh.webp') === '/upload/product/anh.webp', 'safe product media accepted');
ptsAssert(ptsMediaProductImage('../anh.webp') === '', 'media traversal rejected');
ptsAssert(ptsMediaProductImage('anh.svg') === '', 'unsupported image extension rejected');

$seoFixtureRoot = sys_get_temp_dir().'/pts-media-seo-'.bin2hex(random_bytes(8));
$seoFixtureDirectory = $seoFixtureRoot.'/upload/seopage';
if(!mkdir($seoFixtureDirectory, 0777, true) || file_put_contents($seoFixtureDirectory.'/admin-og.webp', 'fixture') === false)
{
    fwrite(STDERR, "FAIL: could not create temporary SEO media fixture\n");
    exit(1);
}
register_shutdown_function(static function() use ($seoFixtureRoot, $seoFixtureDirectory) {
    @unlink($seoFixtureDirectory.'/admin-og.webp');
    @rmdir($seoFixtureDirectory);
    @rmdir($seoFixtureRoot.'/upload');
    @rmdir($seoFixtureRoot);
});
$adminSeoImage = ptsPublicSeoImage('admin-og.webp', $seoFixtureRoot, 'upload/seopage');
ptsAssert($adminSeoImage === '/upload/seopage/admin-og.webp', 'Admin SEO image resolves only to existing safe seopage media');
ptsAssert(ptsPublicSeoImage('../admin-og.webp', $seoFixtureRoot, 'upload/seopage') === '', 'SEO image traversal is rejected');
$mediaSeoDto = ptsMediaSeoDto(array('titlevi'=>'Media SEO', 'descriptionvi'=>'Metadata', 'keywordsvi'=>'ảnh'), 'Fallback', 'Fallback', '/video/', $adminSeoImage);
ptsAssert($mediaSeoDto['image'] === '/upload/seopage/admin-og.webp', 'media SEO DTO carries the validated Admin image');

$youtubeCases = array(
    'https://youtu.be/dQw4w9WgXcQ',
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
    'https://youtube.com/shorts/dQw4w9WgXcQ',
);
foreach($youtubeCases as $url) ptsAssert(ptsYoutubeId($url) === 'dQw4w9WgXcQ', 'YouTube URL normalized: '.$url);
ptsAssert(ptsYoutubeId('https://evil.example/embed/dQw4w9WgXcQ') === '', 'untrusted video host rejected');
ptsAssert(ptsYoutubeId('javascript:alert(1)') === '', 'unsafe video scheme rejected');
ptsAssert(ptsYoutubeId('ftp://youtube.com/watch?v=dQw4w9WgXcQ') === '', 'non-http video scheme rejected');
ptsAssert(ptsYoutubeId('https://youtube.com/watch?v=too-short') === '', 'invalid YouTube id rejected');

$album = ptsGalleryAlbumDto(array('id'=>7,'namevi'=>' Album thật ','slugvi'=>'album-that','status'=>'hienthi','_pts_public_photo'=>'cover.jpg','descvi'=>'Mô tả'));
ptsAssert(is_array($album) && $album['url'] === '/album-that/' && $album['image'] === '/upload/product/cover.jpg', 'gallery album DTO is canonical and safe');
ptsAssert(ptsGalleryAlbumDto(array('id'=>7,'namevi'=>'Hidden','slugvi'=>'hidden','status'=>'noibat')) === null, 'hidden gallery album excluded');

$video = ptsVideoDto(array('id'=>9,'namevi'=>'Video thật','link_video'=>'https://youtu.be/dQw4w9WgXcQ','status'=>'hienthi'));
ptsAssert(is_array($video) && $video['embedUrl'] === 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'video DTO emits nocookie embed');
ptsAssert(ptsVideoDto(array('id'=>9,'namevi'=>'Unsafe','link_video'=>'https://evil.example/x','status'=>'hienthi')) === null, 'unsafe video skipped');

echo "RESULT: PASS\n";
