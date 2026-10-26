<?php
$source = file_get_contents(__DIR__.'/../domains/phucthinhsolar.com/public_html/api/pts_v1_site.php');
if($source === false || strpos($source, "http_response_code(405)") === false || strpos($source, 'METHOD_NOT_ALLOWED') === false || strpos($source, "header('Allow: GET')") === false) {
    fwrite(STDERR, "FAIL: GET-only method gate\n"); exit(1);
}
echo "PASS: PTS public site method gate (1 assertion)\n";
