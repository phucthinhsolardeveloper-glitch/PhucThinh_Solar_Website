<?php
/** GET /api/pts_v1_about.php — internal read-only About contract. */
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET')
{
    header('Allow: GET');
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(405);
    echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}';
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

try
{
    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_about_api.php';
    require_once LIBRARIES.'pts_public_seo_api.php';
    $loadRows = static function($sql, array $params) use ($cache) {
        $rows = $cache->get($sql, $params, 'result', 7200);
        return is_array($rows) ? $rows : array();
    };
    $loadOne = static function($sql, array $params) use ($cache) {
        $row = $cache->get($sql, $params, 'fetch', 7200);
        return is_array($row) ? $row : array();
    };
    $news = static function($type, $limit = '') use ($loadRows) {
        $sql = "select namevi,title_indexvi,descvi,contentvi,solieu,id,photo,status from #_news where type=? and find_in_set('hienthi',status) order by numb,id desc";
        if($limit !== '') $sql .= ' limit 0,'.(int)$limit;
        return $loadRows($sql, array($type));
    };
    $historyRows = $news('lich-su', 8);
    $compassRows = $news('su-menh', 6);
    $trustRows = $news('ly-do-chon-chung-toi');
    $teamRows = $news('about-team', 1);
    $principleRows = $news('about-principle', 3);
    $certificates = $loadRows("select id,namevi,descvi,link,photo,status from #_photo where type=? and find_in_set('hienthi',status) order by numb,id desc", array('chungnhan'));
    $seoRow = $loadOne('select titlevi,descriptionvi,keywordsvi,photo from #_seopage where type=? limit 0,1', array('gioi-thieu'));
    $seo = ptsPublicSeoDto($seoRow, ROOT, UPLOAD_SEOPAGE_L);
    $ctaRows = $loadRows("select namevi,descvi,link,photo,status from #_photo where type=? and find_in_set('hienthi',status) order by numb,id desc limit 0,1", array('about-cta'));
    $advRows = $loadRows("select namevi,descvi,link,photo from #_photo where type=? order by numb,id desc", array('adv'));
    $labels = ptsAboutLabels();

    $story = ptsAboutFind($historyRows, array('ve chung toi'), $func);
    if(!$story && $historyRows) $story = reset($historyRows);
    $historyRecord = ptsAboutFind($historyRows, array('lich su', 'hinh thanh'), $func);
    if(!$historyRecord) $historyRecord = $story;
    $storyIntro = ptsAboutPlainText($story['descvi'] ?? '');
    if($storyIntro === '') $storyIntro = ptsAboutSentences($story['contentvi'] ?? '', 2);
    $storyBody = ptsAboutSentences($story['contentvi'] ?? '', 4);
    if($storyIntro !== '' && strpos($storyBody, $storyIntro) === 0) $storyBody = trim(substr($storyBody, strlen($storyIntro)));
    if($storyBody === $storyIntro) $storyBody = '';

    $timeline = array(); $years = array();
    foreach($historyRows as $row)
    {
        $year = trim((string)($row['solieu'] ?? ''));
        if(!preg_match('/^(19|20)\d{2}$/', $year))
        {
            $source = ptsAboutPlainText(($row['contentvi'] ?? '').' '.($row['namevi'] ?? ''));
            $year = preg_match('/\b((?:19|20)\d{2})\b/', $source, $matches) ? $matches[1] : '';
        }
        if($year === '' || isset($years[$year])) continue;
        $years[$year] = true;
        $description = ptsAboutSentences($row['contentvi'] ?? '', 2) ?: ptsAboutPlainText($row['descvi'] ?? '');
        $description = preg_replace('/^'.preg_quote($year, '/').'\s*[—–-]?\s*/u', '', $description);
        $timeline[] = array('year' => $year, 'title' => ptsAboutPlainText($row['title_indexvi'] ?? $row['namevi'] ?? ''), 'description' => $description);
    }
    usort($timeline, static function($a, $b) { return (int)$a['year'] <=> (int)$b['year']; });
    $timeline = array_slice($timeline, 0, 4);

    $teamReason = ptsAboutFind($trustRows, array('doi ngu nhan su', 'doi ngu', 'experienced staff', 'team'), $func);
    $supportReason = ptsAboutFind($trustRows, array('dich vu ho tro', 'cham soc khach hang', 'customer support', 'support'), $func);
    $warrantyReason = ptsAboutFind($trustRows, array('bao hanh', 'warranty'), $func);
    $authenticReason = ptsAboutFind($trustRows, array('chinh hang', 'genuine', 'authentic'), $func);
    $coreReason = ptsAboutFind($compassRows, array('gia tri cot loi', 'core value'), $func);
    $people = $teamRows ? reset($teamRows) : array();
    $peopleImage = ptsAboutImage($people['photo'] ?? '', ROOT, UPLOAD_NEWS_L, 'upload/news/');
    if($peopleImage === '') foreach($advRows as $adv) if(($adv['link'] ?? '') === '#ptsContactModal') { $peopleImage = ptsAboutImage($adv['photo'] ?? '', ROOT, UPLOAD_PHOTO_L, 'upload/photo/'); if($peopleImage !== '') break; }

    $principles = array();
    foreach($principleRows as $row) $principles[] = array('title' => ptsAboutPlainText($row['title_indexvi'] ?? $row['namevi'] ?? ''), 'description' => ptsAboutPlainText($row['descvi'] ?? '') ?: ptsAboutSentences($row['contentvi'] ?? '', 1), 'image' => ptsAboutImage($row['photo'] ?? '', ROOT, UPLOAD_NEWS_L, 'upload/news/'));
    if(!$principles) foreach(array(array($teamReason, $labels['principleExpertise'], $labels['principleExpertiseDescription']), array($supportReason, $labels['principleSupport'], $labels['principleSupportDescription']), array($coreReason, $labels['principleCustomer'], $labels['principleCustomerDescription'])) as $fallback) $principles[] = array('title' => $fallback[1], 'description' => ptsAboutPlainText($fallback[0]['descvi'] ?? '') ?: $fallback[2], 'image' => ptsAboutImage($fallback[0]['photo'] ?? '', ROOT, UPLOAD_NEWS_L, 'upload/news/'));

    $selectedTrust = array_values(array_filter($trustRows, static function($row) { return !empty($row['status']) && in_array('about', explode(',', $row['status']), true); }));
    if(!$selectedTrust) foreach(array($authenticReason, $teamReason, $supportReason, $warrantyReason) as $row) if(!empty($row['id'])) $selectedTrust[(int)$row['id']] = $row;
    $trust = array();
    foreach(array_slice(array_values($selectedTrust), 0, 4) as $row)
    {
        $normalized = ptsAboutNormalize($row['namevi'] ?? '', $func); $fallback = $labels['trustDefault'];
        if(strpos($normalized, 'chinh hang') !== false || strpos($normalized, 'genuine') !== false || strpos($normalized, 'authentic') !== false) $fallback = $labels['trustAuthentic'];
        elseif(strpos($normalized, 'doi ngu') !== false || strpos($normalized, 'team') !== false || strpos($normalized, 'staff') !== false) $fallback = $labels['trustTeam'];
        elseif(strpos($normalized, 'bao hanh') !== false || strpos($normalized, 'warranty') !== false) $fallback = $labels['trustWarranty'];
        elseif(strpos($normalized, 'ho tro') !== false || strpos($normalized, 'support') !== false) $fallback = $labels['trustSupport'];
        $trust[] = array('title' => ptsAboutPlainText($row['title_indexvi'] ?? $row['namevi'] ?? ''), 'description' => ptsAboutPlainText($row['descvi'] ?? '') ?: $fallback, 'image' => ptsAboutImage($row['photo'] ?? '', ROOT, UPLOAD_NEWS_L, 'upload/news/'));
    }
    $compass = array(); foreach(array_slice($compassRows, 0, 3) as $row) $compass[] = array('title' => ptsAboutPlainText($row['title_indexvi'] ?? $row['namevi'] ?? ''), 'description' => ptsAboutPlainText($row['descvi'] ?? '') ?: ptsAboutSentences($row['contentvi'] ?? '', 1), 'image' => ptsAboutImage($row['photo'] ?? '', ROOT, UPLOAD_NEWS_L, 'upload/news/'));

    $featuredCertificates = array_values(array_filter($certificates, static function($row) {
        return is_array($row) && in_array('noibat', array_filter(array_map('trim', explode(',', (string)($row['status'] ?? '')))), true);
    }));
    $certificateCards = array_slice($featuredCertificates ?: $certificates, 0, 3);
    $certificateCardIds = array_column($certificateCards, 'id');
    $allCertificates = array_merge($certificateCards, array_values(array_filter($certificates, static function($row) use ($certificateCardIds) {
        return is_array($row) && !in_array($row['id'] ?? null, $certificateCardIds, true);
    })));
    $certs = array();
    foreach($allCertificates as $row)
    {
        if(!is_array($row) || (int)($row['id'] ?? 0) <= 0) continue;
        $image = ptsAboutImage($row['photo'] ?? '', ROOT, UPLOAD_PHOTO_L, 'upload/photo/');
        $status = array_filter(array_map('trim', explode(',', (string)($row['status'] ?? ''))));
        $certs[] = array(
            'id' => (int)$row['id'],
            'name' => ptsAboutPlainText($row['namevi'] ?? ''),
            'description' => ptsAboutPlainText($row['descvi'] ?? ''),
            'image' => $image,
            'href' => $image !== '' ? $image : null,
            'featured' => in_array('noibat', $status, true)
        );
    }
    $cta = $ctaRows ? reset($ctaRows) : array();
    $sections = array(
        'story' => array('eyebrow' => $labels['storyEyebrow'], 'title' => $labels['storyTitle'], 'intro' => $storyIntro, 'body' => $storyBody, 'image' => ptsAboutImage($story['photo'] ?? '', ROOT, UPLOAD_NEWS_L, 'upload/news/')),
        'history' => array('title' => ptsAboutPlainText($historyRecord['title_indexvi'] ?? $historyRecord['namevi'] ?? ''), 'image' => ptsAboutImage($historyRecord['photo'] ?? '', ROOT, UPLOAD_NEWS_L, 'upload/news/'), 'items' => $timeline),
        'people' => array('eyebrow' => ptsAboutPlainText($people['title_indexvi'] ?? '') ?: $labels['peopleEyebrow'], 'title' => ptsAboutPlainText($people['namevi'] ?? '') ?: $labels['peopleTitle'], 'intro' => ptsAboutPlainText($people['descvi'] ?? '') ?: $labels['peopleFallback'], 'body' => ptsAboutSentences($people['contentvi'] ?? '', 2), 'image' => $peopleImage, 'principles' => $principles),
        'trust' => array('title' => $labels['trustTitle'], 'items' => $trust),
        'compass' => array('title' => $labels['compassTitle'], 'items' => $compass),
        'certificates' => array('title' => $labels['certTitle'], 'intro' => $labels['certIntro'], 'items' => $certs),
        'cta' => array('title' => ptsAboutPlainText($cta['namevi'] ?? '') ?: $labels['contactTitle'], 'description' => ptsAboutPlainText($cta['descvi'] ?? '') ?: $labels['contactIntro'], 'image' => ptsAboutImage($cta['photo'] ?? '', ROOT, UPLOAD_PHOTO_L, 'upload/photo/'), 'href' => ptsAboutSafeLink($cta['link'] ?? '') ?: null)
    );
    if(function_exists('closeSessionWrite')) closeSessionWrite();
    $hotline = is_array($optsetting) && isset($optsetting['hotline']) ? $optsetting['hotline'] : '';
    $zaloValue = is_array($optsetting) && isset($optsetting['zalo']) && is_scalar($optsetting['zalo']) ? $optsetting['zalo'] : '';
    $zalo = $func->parsePhone($zaloValue);
    echo json_encode(ptsAboutPayload($sections, $labels, $hotline, $zalo, $seo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
catch(Throwable $e)
{
    error_log('pts_v1_about: '.$e->getMessage());
    http_response_code(500);
    echo '{"error":{"code":"INTERNAL_ERROR"}}';
}
