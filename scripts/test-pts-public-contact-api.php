<?php
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_contact_api.php';
function contactAssert($condition, $message) { if(!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
contactAssert(ptsContactText('<p>Địa chỉ <strong>thật</strong><script>x</script></p>') === 'Địa chỉ thậtx', 'plain text sanitization');
contactAssert(ptsContactMapUrl('<iframe src="https://www.google.com/maps/embed?pb=fixture"></iframe>') === 'https://www.google.com/maps/embed?pb=fixture', 'allowlisted map source extraction');
contactAssert(ptsContactMapUrl('<iframe src="https://evil.example/embed"></iframe>') === '', 'untrusted map source rejected');
contactAssert(ptsContactHref('javascript:alert(1)', '#yeu-cau-tu-van') === '#yeu-cau-tu-van', 'unsafe visual link fallback');
$payload = ptsPublicContactPayload(array('title' => '<b>Liên hệ</b>', 'intro' => '<p>Nội dung thật</p>', 'visual' => null, 'contact' => array('hotline' => '0900', 'email' => 'a@example.test'), 'locations' => array(array('id' => 2, 'namevi' => 'Trụ sở', 'address' => 'Địa chỉ thật'))));
contactAssert($payload['path'] === '/lien-he/' && $payload['title'] === 'Liên hệ', 'contact DTO shape');
contactAssert(!isset($payload['contact']['private']) && strpos(json_encode($payload), '<b>') === false, 'private and raw markup absent');
contactAssert(!isset($payload['contact']['technicalHotline']), 'technicalHotline omitted entirely when CMS leaves it empty, not invented as empty string');
$withTechnical = ptsPublicContactPayload(array('title' => 'Liên hệ', 'contact' => array('hotline' => '0900', 'technicalHotline' => '0945 536 899')));
contactAssert($withTechnical['contact']['technicalHotline'] === '0945 536 899', 'technicalHotline projected when CMS provides a value, same field used by SiteFooter.vue');
echo "PASS: PTS public contact DTO (8 assertions)\n";
