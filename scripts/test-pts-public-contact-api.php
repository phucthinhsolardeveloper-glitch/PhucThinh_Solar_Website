<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_contact_api.php';
function contactAssert($condition, $message) { if(!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
contactAssert(ptsContactText('<p>Địa chỉ <strong>thật</strong><script>x</script></p>') === 'Địa chỉ thậtx', 'plain text sanitization');
contactAssert(ptsContactMapUrl('<iframe src="https://www.google.com/maps/embed?pb=fixture"></iframe>') === 'https://www.google.com/maps/embed?pb=fixture', 'allowlisted map source extraction');
contactAssert(ptsContactMapUrl('&lt;iframe src=&quot;https://www.google.com/maps/embed?pb=fixture&amp;output=embed&quot; title=&quot;Map&quot;&gt;&lt;/iframe&gt;') === 'https://www.google.com/maps/embed?pb=fixture&output=embed', 'HTML-escaped CMS iframe source extraction');
contactAssert(ptsContactMapUrl('https://www.google.com/maps/embed?pb=fixture&amp;output=embed') === 'https://www.google.com/maps/embed?pb=fixture&output=embed', 'HTML-escaped query separator normalization');
contactAssert(ptsContactMapUrl('<iframe src="https://evil.example/embed"></iframe>') === '', 'untrusted map source rejected');
contactAssert(ptsContactMapUrl('&lt;iframe src=&quot;https://evil.example/maps/embed?pb=fixture&quot;&gt;&lt;/iframe&gt;') === '', 'HTML-escaped untrusted map source rejected');
contactAssert(ptsContactMapUrl('https://www.google.com.evil.example/maps/embed?pb=fixture') === '', 'map host suffix spoof rejected');
contactAssert(ptsContactMapUrl('https://www.google.com:443/maps/embed?pb=fixture') === '', 'map port rejected');
contactAssert(ptsContactMapUrl('https://www.google.com/maps.evil.example/embed?pb=fixture') === '', 'non-Maps path rejected');
contactAssert(ptsContactHref('javascript:alert(1)', '#yeu-cau-tu-van') === '#yeu-cau-tu-van', 'unsafe visual link fallback');
$payload = ptsPublicContactPayload(array('title' => '<b>Liên hệ</b>', 'intro' => '<p>Nội dung thật</p>', 'visual' => null, 'contact' => array('hotline' => '0900', 'email' => 'a@example.test'), 'locations' => array(array('id' => 2, 'namevi' => 'Trụ sở', 'address' => 'Địa chỉ thật'))));
contactAssert($payload['path'] === '/lien-he/' && $payload['title'] === 'Liên hệ', 'contact DTO shape');
contactAssert(!isset($payload['contact']['private']) && strpos(json_encode($payload), '<b>') === false, 'private and raw markup absent');
echo "PASS: PTS public contact DTO (12 assertions)\n";
