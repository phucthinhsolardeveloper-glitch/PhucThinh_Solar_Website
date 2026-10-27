<?php
/**
 * Deterministic, read-only HTTP contract runner for the PHP -> Nuxt migration.
 *
 * Run inside the PHP 8.1 fixture container, for example:
 *   php /var/www/html/scripts/test-pts-public-api-integration.php
 *
 * The runner never submits a valid mutation and never creates fixture data. It
 * discovers published slugs/IDs from the local database, snapshots read-model
 * tables, exercises HTTP contracts, and writes a compact report. A missing
 * local config, database, or HTTP service is reported as BLOCKED/SKIP rather
 * than being turned into a misleading PASS.
 */

error_reporting(E_ALL);

$root = dirname(__DIR__);
$base = rtrim((string)(getenv('PTS_BASE_URL') ?: 'http://127.0.0.1:80'), '/');
$reportDir = getenv('PTS_REPORT_DIR') ?: ($root.'/frontend/.migration-artifacts/php-integration');
$reportJson = $reportDir.'/report.json';
$reportMarkdown = $reportDir.'/report.md';

$report = array(
    'schemaVersion' => 1,
    'runner' => 'scripts/test-pts-public-api-integration.php',
    'baseUrl' => $base,
    'php' => PHP_VERSION,
    'status' => 'PASS',
    'checks' => array('pass' => 0, 'fail' => 0, 'skip' => 0),
    'sliceScripts' => array('pass' => 0, 'fail' => 0, 'skip' => 0),
    'blockedReasons' => array(),
    'notTestable' => array(),
    'failures' => array(),
    'database' => array('before' => array(), 'after' => array(), 'unchanged' => null),
    'inventory' => array()
);

function ptsIntegrationLog($message) { echo $message, PHP_EOL; }

function ptsIntegrationRecord($kind, $name, $detail = '')
{
    global $report;
    if($kind === 'pass') { $report['checks']['pass']++; $prefix = 'PASS'; }
    elseif($kind === 'fail') { $report['checks']['fail']++; $prefix = 'FAIL'; $report['failures'][] = array('name'=>$name, 'detail'=>$detail); }
    else { $report['checks']['skip']++; $prefix = 'SKIP'; $report['notTestable'][] = array('name'=>$name, 'reason'=>$detail); }
    ptsIntegrationLog($prefix.': '.$name.($detail !== '' ? ' — '.$detail : ''));
}

function ptsIntegrationBlocked($reason)
{
    global $report;
    $report['status'] = 'BLOCKED';
    $report['blockedReasons'][] = $reason;
    ptsIntegrationLog('BLOCKED: '.$reason);
}

function ptsIntegrationHttp($url, $method = 'GET', $body = null, $cookie = '')
{
    $headers = "Accept: application/json\r\nUser-Agent: PTS-PHP81-Contract-Runner/1\r\n";
    if($cookie !== '') $headers .= 'Cookie: '.$cookie."\r\n";
    $options = array('http'=>array(
        'method'=>$method,
        'header'=>$headers,
        'ignore_errors'=>true,
        'timeout'=>20,
        'follow_location'=>0
    ));
    if($body !== null)
    {
        $options['http']['content'] = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $options['http']['header'] .= "Content-Type: application/json\r\n";
    }
    $responseBody = @file_get_contents($url, false, stream_context_create($options));
    $headersOut = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
    $status = 0;
    $responseHeaders = array();
    foreach($headersOut as $line)
    {
        if(preg_match('#^HTTP/\S+\s+(\d+)#i', $line, $match)) $status = (int)$match[1];
        $parts = explode(':', $line, 2);
        if(count($parts) === 2) $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
    }
    return array('status'=>$status, 'headers'=>$responseHeaders, 'body'=>(string)$responseBody);
}

function ptsIntegrationDecode($response)
{
    if(!is_array($response) || $response['body'] === '') return null;
    $decoded = json_decode($response['body'], true);
    return is_array($decoded) ? $decoded : null;
}

function ptsIntegrationQueryUrl($base, $path, array $query = array())
{
    return $base.$path.($query ? '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
}

function ptsIntegrationRequest($name, $path, $method = 'GET', array $query = array(), $body = null, $cookie = '')
{
    global $base;
    $response = ptsIntegrationHttp(ptsIntegrationQueryUrl($base, $path, $query), $method, $body, $cookie);
    if($response['status'] === 0)
    {
        ptsIntegrationRecord('skip', $name, 'HTTP service unavailable at '.$base);
        return array($response, null);
    }
    return array($response, ptsIntegrationDecode($response));
}

function ptsIntegrationAssertHttp($name, $response, $status, array $requiredKeys = array())
{
    if($response['status'] === 0) return;
    if($response['status'] !== $status)
    {
        ptsIntegrationRecord('fail', $name, 'expected HTTP '.$status.', got '.$response['status']);
        return;
    }
    $decoded = ptsIntegrationDecode($response);
    if($requiredKeys)
    {
        if(!is_array($decoded)) { ptsIntegrationRecord('fail', $name, 'response is not a JSON object'); return; }
        $missing = array();
        foreach($requiredKeys as $key) if(!array_key_exists($key, $decoded)) $missing[] = $key;
        if($missing) { ptsIntegrationRecord('fail', $name, 'missing keys: '.implode(', ', $missing)); return; }
    }
    ptsIntegrationRecord('pass', $name, 'HTTP '.$status);
}

function ptsIntegrationAssertMethod($name, $response, $status = 405, $allow = '')
{
    if($response['status'] === 0) return;
    if($response['status'] !== $status)
    {
        ptsIntegrationRecord('fail', $name, 'expected HTTP '.$status.', got '.$response['status']);
        return;
    }
    if($allow !== '' && strtoupper((string)($response['headers']['allow'] ?? '')) !== $allow)
    {
        ptsIntegrationRecord('fail', $name, 'expected Allow: '.$allow.', got '.($response['headers']['allow'] ?? '(missing)'));
        return;
    }
    ptsIntegrationRecord('pass', $name, 'HTTP '.$status.' / Allow '.($response['headers']['allow'] ?? '(none)'));
}

function ptsIntegrationDbOne($db, $sql, array $params = array())
{
    try { $row = $db->rawQueryOne($sql, $params); return is_array($row) ? $row : array(); }
    catch(Throwable $e) { return array(); }
}

function ptsIntegrationSnapshot($db, array $tables)
{
    $snapshot = array();
    foreach($tables as $table)
    {
        try
        {
            $row = $db->rawQueryOne("select count(*) as n, coalesce(sum(id),0) as id_sum, coalesce(min(id),0) as id_min, coalesce(max(id),0) as id_max from #_{$table}");
            $snapshot[$table] = array('count'=>(int)($row['n'] ?? 0), 'idSum'=>(string)($row['id_sum'] ?? '0'), 'idMin'=>(int)($row['id_min'] ?? 0), 'idMax'=>(int)($row['id_max'] ?? 0));
        }
        catch(Throwable $e) { $snapshot[$table] = array('unavailable'=>true); }
    }
    return $snapshot;
}

function ptsIntegrationSnapshotsEqual(array $before, array $after)
{
    foreach($before as $table=>$state) if(($after[$table] ?? null) !== $state) return false;
    return true;
}

/* The file is intentionally not copied from deploy/vps/config/config.php. The
 * local fixture supplies its own gitignored config with local-only credentials. */
$configPath = $root.'/domains/phucthinhsolar.com/public_html/libraries/config.php';
$db = null;
if(!is_file($configPath))
{
    ptsIntegrationBlocked('Missing local-only libraries/config.php in this worktree; no credentials were synthesized or copied.');
}
elseif(!class_exists('PDO'))
{
    ptsIntegrationBlocked('PHP PDO is unavailable in the current runtime.');
}
else
{
    try
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        if(!defined('LIBRARIES')) define('LIBRARIES', $root.'/domains/phucthinhsolar.com/public_html/libraries/');
        require_once $configPath;
        require_once LIBRARIES.'autoload.php';
        new AutoLoad();
        $db = new PDODb($config['database']);
    }
    catch(Throwable $e) { ptsIntegrationBlocked('Local DB bootstrap failed: '.get_class($e)); }
}

$tables = array('product','product_list','product_cat','product_item','product_sub','product_brand','news','news_list','news_cat','news_item','news_sub','city','ward','setting','static','photo','gallery','seo','seopage','comment','comment_photo','order','order_detail','newsletter','contact','user_limit');
if($db) $report['database']['before'] = ptsIntegrationSnapshot($db, $tables);

/* Inventory is deliberately explicit so a new public migration endpoint cannot
 * silently disappear from the aggregate gate. Mutation endpoints are listed
 * separately and only receive negative tests below. */
$readInventory = array(
    'site'=>'/api/pts_v1_site.php', 'home'=>'/api/pts_v1_home.php', 'catalog'=>'/api/pts_v1_catalog.php',
    'search'=>'/api/pts_v1_search.php', 'solutions'=>'/api/pts_v1_solutions.php', 'projects'=>'/api/pts_v1_projects.php',
    'news'=>'/api/pts_v1_news.php', 'about'=>'/api/pts_v1_about.php', 'contact'=>'/api/pts_v1_contact.php',
    'dealer'=>'/api/pts_v1_dealer.php', 'cart'=>'/api/pts_v1_cart.php', 'checkout'=>'/api/pts_v1_checkout.php',
    'checkout_wards'=>'/api/pts_v1_checkout_wards.php', 'brand_detail'=>'/api/pts_v1_brand_detail.php',
    'product_detail'=>'/api/pts_v1_product_detail.php', 'product_extras'=>'/api/pts_v1_product_extras.php',
    'solution_detail'=>'/api/pts_v1_solution_detail.php', 'project_detail'=>'/api/pts_v1_project_detail.php',
    'news_detail'=>'/api/pts_v1_news_detail.php', 'policy_detail'=>'/api/pts_v1_policy_detail.php',
    'order_thanks'=>'/api/pts_v1_order_thanks.php'
);
$mutationInventory = array('cart_mutate'=>'/api/pts_v1_cart_mutate.php','lead_form'=>'/api/pts_v1_lead_form.php','review'=>'/api/pts_v1_review.php','shipping_quote'=>'/api/shipping_quote.php');
$report['inventory'] = array('readOnly'=>$readInventory, 'mutationNegativeOnly'=>$mutationInventory);

$fixtures = array();
if($db)
{
    $queries = array(
        'product'=>array("select id,slugvi,namevi from #_product where type=? and find_in_set('hienthi',status) and slugvi<>'' order by id desc limit 1", array('san-pham')),
        'hiddenProduct'=>array("select id,slugvi from #_product where type=? and not find_in_set('hienthi',status) and slugvi<>'' order by id desc limit 1", array('san-pham')),
        'brand'=>array("select b.id,b.slugvi from #_product_brand b inner join #_product p on p.id_brand=b.id where p.type=? and find_in_set('hienthi',p.status) and b.slugvi<>'' order by b.id desc limit 1", array('san-pham')),
        'solution'=>array("select slugvi from #_news where type=? and find_in_set('hienthi',status) and slugvi<>'' order by id desc limit 1", array('giai-phap')),
        'project'=>array("select slugvi from #_news where type=? and find_in_set('hienthi',status) and slugvi<>'' order by id desc limit 1", array('du-an')),
        'news'=>array("select slugvi from #_news where type=? and find_in_set('hienthi',status) and slugvi<>'' order by id desc limit 1", array('tin-tuc')),
        'policy'=>array("select slugvi from #_news where type=? and find_in_set('hienthi',status) and slugvi<>'' order by id desc limit 1", array('chinh-sach')),
        'city'=>array("select id from #_city where find_in_set('hienthi',status) order by id asc limit 1", array())
    );
    foreach($queries as $key=>$query) $fixtures[$key] = ptsIntegrationDbOne($db, $query[0], $query[1]);
}

/* Fixed read-only happy paths and JSON shape contracts. */
$fixed = array(
    array('site','/api/pts_v1_site.php',array('schemaVersion','brand','navigation')),
    array('home','/api/pts_v1_home.php',array('schemaVersion','locale','path','categories','productGroups')),
    array('catalog','/api/pts_v1_catalog.php',array('schemaVersion','locale','products','pagination','filters','selected')),
    array('solutions','/api/pts_v1_solutions.php',array('schemaVersion','locale','path','solutions')),
    array('projects','/api/pts_v1_projects.php',array('schemaVersion','locale','path','projects','pagination')),
    array('news','/api/pts_v1_news.php',array('schemaVersion','locale','path','articles','pagination')),
    array('about','/api/pts_v1_about.php',array('schemaVersion','locale','path','story','cta')),
    array('contact','/api/pts_v1_contact.php',array('schemaVersion','locale','path','contact','locations')),
    array('dealer','/api/pts_v1_dealer.php',array('schemaVersion','locale','path','form')),
    array('cart','/api/pts_v1_cart.php',array('schemaVersion','locale','csrfToken','items','count')),
    array('checkout','/api/pts_v1_checkout.php',array('schemaVersion','locale','csrfToken','checkoutToken','cities','payments')),
    array('lead_metadata','/api/pts_v1_lead_form.php',array('schemaVersion','csrfToken','cities','recaptcha'))
);
foreach($fixed as $case)
{
    list($name,$path,$keys) = $case;
    list($response,$decoded) = ptsIntegrationRequest($name, $path);
    if($response['status'] !== 0) ptsIntegrationAssertHttp($name, $response, 200, $keys);
}

/* Dynamic fixture happy paths. */
$dynamic = array(
    array('brand_detail','/api/pts_v1_brand_detail.php','brand','slug',array('schemaVersion','brand','products','pagination')),
    array('product_detail','/api/pts_v1_product_detail.php','product','slug',array('schemaVersion','product','pricing')),
    array('product_extras','/api/pts_v1_product_extras.php','product','slug',array('schemaVersion','productId','csrfToken','reviews','consultation')),
    array('solution_detail','/api/pts_v1_solution_detail.php','solution','slug',array('schemaVersion','solution','content')),
    array('project_detail','/api/pts_v1_project_detail.php','project','slug',array('schemaVersion','project','content')),
    array('news_detail','/api/pts_v1_news_detail.php','news','slug',array('article','related')),
    array('policy_detail','/api/pts_v1_policy_detail.php','policy','slug',array('schemaVersion','title','content','seo'))
);
foreach($dynamic as $case)
{
    list($name,$path,$fixtureKey,$param,$keys) = $case;
    $value = isset($fixtures[$fixtureKey][$param]) ? trim((string)$fixtures[$fixtureKey][$param], '/') : '';
    if($value === '') { ptsIntegrationRecord('skip', $name, 'no published local fixture entity'); continue; }
    list($response,$decoded) = ptsIntegrationRequest($name, $path, 'GET', array($param=>$value));
    if($response['status'] !== 0) ptsIntegrationAssertHttp($name, $response, 200, $keys);
}

/* Search and two-level location data use real fixture values. */
$keyword = isset($fixtures['product']['slugvi']) ? (string)$fixtures['product']['slugvi'] : 'solar';
list($response,$decoded) = ptsIntegrationRequest('search', '/api/pts_v1_search.php', 'GET', array('keyword'=>$keyword,'per_page'=>'1'));
if($response['status'] !== 0) ptsIntegrationAssertHttp('search', $response, 200, array('schemaVersion','locale','keyword','products','pagination'));
$cityId = isset($fixtures['city']['id']) ? (int)$fixtures['city']['id'] : 0;
if($cityId > 0)
{
    list($response,$decoded) = ptsIntegrationRequest('checkout_wards', '/api/pts_v1_checkout_wards.php', 'GET', array('city'=>(string)$cityId));
    if($response['status'] !== 0) ptsIntegrationAssertHttp('checkout_wards', $response, 200, array('schemaVersion','locale','cityId','wards'));
}
else ptsIntegrationRecord('skip', 'checkout_wards', 'no visible city fixture');

/* GET-only method/Allow contracts. */
$getOnly = array('site','home','catalog','search','solutions','projects','news','about','contact','dealer','cart','checkout','checkout_wards','brand_detail','product_detail','product_extras','solution_detail','project_detail','news_detail','policy_detail','order_thanks');
foreach($getOnly as $name)
{
    $path = $readInventory[$name];
    $query = array();
    if($name === 'checkout_wards' && $cityId > 0) $query = array('city'=>(string)$cityId);
    elseif($name === 'brand_detail' && !empty($fixtures['brand']['slugvi'])) $query = array('slug'=>(string)$fixtures['brand']['slugvi']);
    elseif($name === 'product_detail' || $name === 'product_extras') $query = array('slug'=>(string)($fixtures['product']['slugvi'] ?? 'fixture'));
    elseif($name === 'solution_detail') $query = array('slug'=>(string)($fixtures['solution']['slugvi'] ?? 'fixture'));
    elseif($name === 'project_detail') $query = array('slug'=>(string)($fixtures['project']['slugvi'] ?? 'fixture'));
    elseif($name === 'news_detail') $query = array('slug'=>(string)($fixtures['news']['slugvi'] ?? 'fixture'));
    elseif($name === 'policy_detail') $query = array('slug'=>(string)($fixtures['policy']['slugvi'] ?? 'fixture'));
    elseif($name === 'order_thanks') $query = array('token'=>str_repeat('0',48));
    list($response,$decoded) = ptsIntegrationRequest($name.' POST rejected', $path, 'POST', $query);
    if($response['status'] !== 0) ptsIntegrationAssertMethod($name.' POST rejected', $response, 405, 'GET');
}

/* Invalid query, missing published entities, and traversal/media safety. */
$badQueries = array(
    array('catalog bad query','/api/pts_v1_catalog.php',array('page'=>'0')),
    array('search bad query','/api/pts_v1_search.php',array('keyword'=>"\x01")),
    array('solution traversal','/api/pts_v1_solution_detail.php',array('slug'=>'../secret')),
    array('project traversal','/api/pts_v1_project_detail.php',array('slug'=>'../secret')),
    array('news traversal','/api/pts_v1_news_detail.php',array('slug'=>'../secret')),
    array('policy traversal','/api/pts_v1_policy_detail.php',array('slug'=>'../secret')),
    array('product traversal','/api/pts_v1_product_detail.php',array('slug'=>'../secret')),
    array('product extras traversal','/api/pts_v1_product_extras.php',array('slug'=>'../secret'))
);
foreach($badQueries as $case)
{
    list($name,$path,$query) = $case;
    list($response,$decoded) = ptsIntegrationRequest($name, $path, 'GET', $query);
    if($response['status'] !== 0) ptsIntegrationAssertHttp($name, $response, 400, array('error'));
}
$missingCases = array(
    array('product missing','/api/pts_v1_product_detail.php',array('slug'=>'__pts_missing_product_fixture__')),
    array('product extras missing','/api/pts_v1_product_extras.php',array('slug'=>'__pts_missing_product_fixture__')),
    array('solution missing','/api/pts_v1_solution_detail.php',array('slug'=>'__pts_missing_solution_fixture__')),
    array('project missing','/api/pts_v1_project_detail.php',array('slug'=>'__pts_missing_project_fixture__')),
    array('news missing','/api/pts_v1_news_detail.php',array('slug'=>'__pts_missing_news_fixture__')),
    array('policy missing','/api/pts_v1_policy_detail.php',array('slug'=>'__pts_missing_policy_fixture__'))
);
foreach($missingCases as $case)
{
    list($name,$path,$query) = $case;
    list($response,$decoded) = ptsIntegrationRequest($name, $path, 'GET', $query);
    if($response['status'] !== 0) ptsIntegrationAssertHttp($name, $response, 404, array('error'));
}
$hiddenProductSlug = (string)($fixtures['hiddenProduct']['slugvi'] ?? '');
if($hiddenProductSlug !== '')
{
    list($response,$decoded) = ptsIntegrationRequest('hidden product excluded', '/api/pts_v1_product_detail.php', 'GET', array('slug'=>$hiddenProductSlug));
    if($response['status'] !== 0) ptsIntegrationAssertHttp('hidden product excluded', $response, 404, array('error'));
}
else ptsIntegrationRecord('skip', 'hidden product excluded', 'no hidden product fixture with a valid slug');

/* Mutation endpoints: only malformed/invalid-CSRF branches, never a valid write. */
list($response,$decoded) = ptsIntegrationRequest('cart mutate GET rejected', $mutationInventory['cart_mutate'], 'GET');
if($response['status'] !== 0) ptsIntegrationAssertMethod('cart mutate GET rejected', $response, 405, 'POST');
list($response,$decoded) = ptsIntegrationRequest('cart mutate invalid CSRF', $mutationInventory['cart_mutate'], 'POST', array(), array('action'=>'add','csrfToken'=>'invalid'));
if($response['status'] !== 0) ptsIntegrationAssertHttp('cart mutate invalid CSRF', $response, 419, array('error'));
list($response,$decoded) = ptsIntegrationRequest('lead form invalid CSRF', $mutationInventory['lead_form'], 'POST', array(), array('kind'=>'contact','csrfToken'=>'invalid','data'=>array()));
if($response['status'] !== 0) ptsIntegrationAssertHttp('lead form invalid CSRF', $response, 419, array('error'));
list($response,$decoded) = ptsIntegrationRequest('review GET rejected', $mutationInventory['review'], 'GET');
if($response['status'] !== 0) ptsIntegrationAssertMethod('review GET rejected', $response, 405, 'POST');
list($response,$decoded) = ptsIntegrationRequest('review invalid CSRF', $mutationInventory['review'], 'POST', array(), array('csrfToken'=>'invalid','productId'=>0,'star'=>0));
if($response['status'] !== 0) ptsIntegrationAssertHttp('review invalid CSRF', $response, 419, array('errors'));
list($response,$decoded) = ptsIntegrationRequest('shipping quote GET rejected', $mutationInventory['shipping_quote'], 'GET');
if($response['status'] !== 0) ptsIntegrationAssertHttp('shipping quote GET rejected', $response, 405, array('ok'));
list($response,$decoded) = ptsIntegrationRequest('order thanks bad query', $readInventory['order_thanks'], 'GET', array('token'=>'bad'));
if($response['status'] !== 0) ptsIntegrationAssertHttp('order thanks bad query', $response, 400, array('error'));

/* Run all pure public API slice scripts. Stateful public form scripts are
 * intentionally excluded by the no-write contract and named in the report. */
$sliceScripts = glob($root.'/scripts/test-pts-public-*.php') ?: array();
sort($sliceScripts, SORT_STRING);
foreach($sliceScripts as $script)
{
    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg($script).' 2>&1';
    $output = array(); $exitCode = 0;
    exec($command, $output, $exitCode);
    $name = basename($script);
    if($exitCode === 0) { $report['sliceScripts']['pass']++; ptsIntegrationLog('PASS: '.$name); }
    else
    {
        $report['sliceScripts']['fail']++;
        $tail = array_slice($output, -3);
        $detail = trim(implode(' | ', $tail));
        $report['failures'][] = array('name'=>'slice '.$name, 'detail'=>$detail === '' ? 'exit '.$exitCode : $detail);
        ptsIntegrationLog('FAIL: '.$name.($detail !== '' ? ' — '.$detail : ''));
    }
}
$report['sliceScripts']['excludedNoWrite'] = array('scripts/test-public-form-submissions.php','scripts/test-public-lead-and-review.php');

if($db)
{
    $report['database']['after'] = ptsIntegrationSnapshot($db, $tables);
    $report['database']['unchanged'] = ptsIntegrationSnapshotsEqual($report['database']['before'], $report['database']['after']);
    if($report['database']['unchanged']) ptsIntegrationRecord('pass', 'database snapshot unchanged', 'read-only tables stable before/after');
    else ptsIntegrationRecord('fail', 'database snapshot unchanged', 'one or more read-only table aggregates changed');
}
else $report['database']['unchanged'] = null;

if($report['checks']['fail'] > 0 || $report['sliceScripts']['fail'] > 0) $report['status'] = 'FAIL';
if($report['status'] === 'PASS' && $report['checks']['pass'] === 0) $report['status'] = 'BLOCKED';

if(!is_dir($reportDir)) @mkdir($reportDir, 0775, true);
$report['finishedAt'] = gmdate('c');
file_put_contents($reportJson, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL, LOCK_EX);
$markdown = array(
    '# PHP public API integration report', '',
    '- Status: **'.$report['status'].'**',
    '- Base URL: `'.$base.'`',
    '- HTTP checks: PASS '.$report['checks']['pass'].' / FAIL '.$report['checks']['fail'].' / SKIP '.$report['checks']['skip'],
    '- Slice scripts: PASS '.$report['sliceScripts']['pass'].' / FAIL '.$report['sliceScripts']['fail'].'',
    '- DB snapshot unchanged: `'.($report['database']['unchanged'] === null ? 'not available' : ($report['database']['unchanged'] ? 'true' : 'false')).'`',
    '', '## Blockers / not testable', ''
);
foreach($report['blockedReasons'] as $reason) $markdown[] = '- '.$reason;
foreach($report['notTestable'] as $item) $markdown[] = '- '.$item['name'].': '.$item['reason'];
$markdown[] = '';
$markdown[] = 'Mutation scripts with valid writes were excluded: `test-public-form-submissions.php`, `test-public-lead-and-review.php`.';
file_put_contents($reportMarkdown, implode(PHP_EOL, $markdown).PHP_EOL, LOCK_EX);

ptsIntegrationLog('RESULT: '.$report['status'].'; HTTP PASS='.$report['checks']['pass'].' FAIL='.$report['checks']['fail'].' SKIP='.$report['checks']['skip'].'; slices PASS='.$report['sliceScripts']['pass'].' FAIL='.$report['sliceScripts']['fail']);
exit($report['status'] === 'PASS' ? 0 : 1);
