<?php
/** Pure contract checks for the read-only About projection; no CMS connection. */
require_once __DIR__.'/../domains/phucthinhsolar.com/public_html/libraries/pts_public_about_api.php';

$failures = 0;
$assert = static function($condition, $message) use (&$failures) {
    if(!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); $failures++; }
};

$assert(ptsAboutPublished('hienthi,about') === true, 'published status is accepted');
$assert(ptsAboutPublished('noibat') === false, 'unpublished status is rejected');
$assert(ptsAboutPlainText('<p>CMS&nbsp;text<br>next</p>') === 'CMS text next', 'rich text is reduced to plain text');
$assert(ptsAboutSentences('Một câu. Câu thứ hai.', 1) === 'Một câu.', 'sentence fallback preserves PHP limit');
$assert(ptsAboutSafeLink('#ptsContactModal') === null, 'modal form target is not exposed to Nuxt');
$assert(ptsAboutSafeLink('/lien-he/#yeu-cau-tu-van') === '/lien-he/#yeu-cau-tu-van', 'CMS internal link is preserved');
$assert(ptsAboutSafeLink('javascript:alert(1)') === null, 'unsafe CTA link is rejected');

$payload = ptsAboutPayload(array(
    'story' => array('eyebrow' => 'E', 'title' => 'T', 'intro' => '', 'body' => '', 'image' => ''),
    'history' => array('title' => '', 'image' => '', 'items' => array()),
    'people' => array('eyebrow' => 'E', 'title' => 'T', 'intro' => '', 'body' => '', 'image' => '', 'principles' => array()),
    'trust' => array('title' => 'T', 'items' => array()),
    'compass' => array('title' => 'T', 'items' => array()),
    'certificates' => array('title' => 'T', 'intro' => '', 'items' => array()),
    'cta' => array('title' => 'T', 'description' => '', 'image' => '', 'href' => null),
), ptsAboutLabels(), '');
$assert($payload['schemaVersion'] === 1 && $payload['locale'] === 'vi', 'payload has stable schema metadata');
$assert($payload['cta']['hotline'] === null, 'empty hotline remains nullable');

if($failures > 0) exit(1);
fwrite(STDOUT, "About API helper contract: PASS\n");
