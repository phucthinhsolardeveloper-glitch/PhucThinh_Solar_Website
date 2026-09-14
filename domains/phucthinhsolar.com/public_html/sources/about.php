<?php
	if(!defined('SOURCES')) die("Error");

    /*
     * Trang Về chúng tôi dùng lại các module Admin đang có. Không đọc hai
     * static block cũ (tom-tat-gioi-thieu/su-menh) vì dữ liệu của chúng là
     * nội dung demo thuộc doanh nghiệp khác. Những trường desc/solieu bên
     * dưới đều đã có sẵn trong schema; config Admin chỉ mở chúng để biên tập
     * nội dung ngắn và năm cột mốc, không tạo thêm page builder hay bảng mới.
     */
    $lichsu = $cache->get("select name$lang, desc$lang, content$lang, solieu, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('lich-su'), 'result', 7200);
    $sumenh = $cache->get("select name$lang, desc$lang, content$lang, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('su-menh'), 'result', 7200);
    $lydo = $cache->get("select name$lang, desc$lang, content$lang, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('ly-do-chon-chung-toi'), 'result', 7200);
    $camket = $cache->get("select name$lang, desc$lang, content$lang, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('cam-ket'), 'result', 7200);
    $chungnhan = $cache->get("select name$lang, desc$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('chungnhan'), 'result', 7200);
    $aboutAdv = $cache->get("select name$lang, desc$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('adv'), 'result', 7200);

    $aboutPlainText = static function($value) {
        $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
        $value = strip_tags($value);
        return trim(preg_replace('/\s+/u', ' ', $value));
    };

    $aboutSentences = static function($value, $limit = 1) use ($aboutPlainText) {
        $plain = $aboutPlainText($value);
        if($plain === '') return '';
        $sentences = preg_split('/(?<=[.!?])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
        return trim(implode(' ', array_slice($sentences ?: array($plain), 0, max(1, (int)$limit))));
    };

    $aboutNormalize = static function($value) use ($aboutPlainText, $func) {
        return str_replace('-', ' ', $func->changeTitle($aboutPlainText($value)));
    };

    $aboutFind = static function($rows, $needles) use ($aboutNormalize, $lang) {
        foreach($rows as $row) {
            $haystack = $aboutNormalize(isset($row['name'.$lang]) ? $row['name'.$lang] : '');
            foreach((array)$needles as $needle) {
                if(strpos($haystack, $aboutNormalize($needle)) !== false) return $row;
            }
        }
        return array();
    };

    /* Intro và ảnh câu chuyện ưu tiên đúng record lịch sử thật đang quản lý. */
    $aboutStoryRecord = $aboutFind($lichsu, array('ve chung toi'));
    if(empty($aboutStoryRecord)) $aboutStoryRecord = !empty($lichsu) ? reset($lichsu) : array();
    $aboutHistoryRecord = $aboutFind($lichsu, array('lich su', 'hinh thanh'));
    if(empty($aboutHistoryRecord)) $aboutHistoryRecord = $aboutStoryRecord;

    $aboutStoryIntro = '';
    if(!empty($aboutStoryRecord['desc'.$lang])) $aboutStoryIntro = $aboutPlainText($aboutStoryRecord['desc'.$lang]);
    if($aboutStoryIntro === '' && !empty($aboutStoryRecord['content'.$lang])) $aboutStoryIntro = $aboutSentences($aboutStoryRecord['content'.$lang], 1);

    /* Một record tương ứng một mốc. Chỉ render khi năm hợp lệ; không dựng thêm
       2021/2023/2026 từ mockup nếu Admin chưa có dữ liệu xác thực. */
    $aboutTimeline = array();
    $aboutTimelineYears = array();
    foreach($lichsu as $row) {
        $year = !empty($row['solieu']) ? trim((string)$row['solieu']) : '';
        if(!preg_match('/^(19|20)\d{2}$/', $year)) {
            $sourceText = (isset($row['content'.$lang]) ? $row['content'.$lang] : '').' '.(isset($row['name'.$lang]) ? $row['name'.$lang] : '');
            $year = preg_match('/\b((?:19|20)\d{2})\b/', $aboutPlainText($sourceText), $matches) ? $matches[1] : '';
        }
        if($year === '' || isset($aboutTimelineYears[$year])) continue;
        $aboutTimelineYears[$year] = true;
        $hasManagedDescription = !empty($row['desc'.$lang]);
        $description = $hasManagedDescription ? $aboutPlainText($row['desc'.$lang]) : $aboutSentences($row['content'.$lang], 1);
        $description = preg_replace('/^'.$year.'\s*[—–-]?\s*/u', '', $description);
        if(!$hasManagedDescription && strpos($description, ':') !== false) {
            $description = rtrim(strtok($description, ':'), " \t\n\r\0\x0B.,;—–-").'.';
        }
        $aboutTimeline[] = array(
            'year' => $year,
            'title' => $aboutPlainText($row['name'.$lang]),
            'description' => $description
        );
    }
    usort($aboutTimeline, static function($a, $b) { return (int)$a['year'] <=> (int)$b['year']; });
    $aboutTimeline = array_slice($aboutTimeline, 0, 4);

    $aboutTeamRecord = $aboutFind($lydo, array('doi ngu nhan su', 'doi ngu'));
    $aboutSupportRecord = $aboutFind($lydo, array('dich vu ho tro', 'cham soc khach hang'));
    $aboutCoreRecord = $aboutFind($sumenh, array('gia tri cot loi'));
    $aboutMissionRecord = $aboutFind($sumenh, array('su menh'));
    $aboutVisionRecord = $aboutFind($sumenh, array('tam nhin'));
    $aboutAuthenticRecord = $aboutFind($camket, array('chinh hang'));

    $aboutPrinciples = array(
        array('icon' => 'fa-hard-hat', 'title' => 'Chuyên môn thực tế', 'description' => !empty($aboutTeamRecord['desc'.$lang]) ? $aboutPlainText($aboutTeamRecord['desc'.$lang]) : 'Kinh nghiệm thực tế trong tư vấn và triển khai hệ thống.'),
        array('icon' => 'fa-handshake', 'title' => 'Đồng hành đến cùng', 'description' => !empty($aboutSupportRecord['desc'.$lang]) ? $aboutPlainText($aboutSupportRecord['desc'.$lang]) : 'Hỗ trợ xuyên suốt từ tư vấn đến vận hành.'),
        array('icon' => 'fa-users', 'title' => 'Lấy khách hàng làm trung tâm', 'description' => !empty($aboutCoreRecord['desc'.$lang]) ? $aboutPlainText($aboutCoreRecord['desc'.$lang]) : ($aboutSentences(isset($aboutCoreRecord['content'.$lang]) ? $aboutCoreRecord['content'.$lang] : '', 1) ?: 'Lắng nghe nhu cầu và đặt hiệu quả của khách hàng làm trọng tâm.'))
    );

    $aboutPeopleIntroParts = array();
    if(!empty($aboutTeamRecord['name'.$lang])) $aboutPeopleIntroParts[] = $aboutPlainText($aboutTeamRecord['name'.$lang]).'.';
    if(!empty($aboutSupportRecord['name'.$lang])) $aboutPeopleIntroParts[] = $aboutPlainText($aboutSupportRecord['name'.$lang]).'.';
    $aboutPeopleIntro = implode(' ', array_slice($aboutPeopleIntroParts, 0, 2));

    $aboutPeopleImage = '';
    $aboutPeopleUpload = UPLOAD_PHOTO_L;
    foreach($aboutAdv as $advRow) {
        if(isset($advRow['link']) && $advRow['link'] === '#ptsContactModal' && !empty($advRow['photo'])) {
            $aboutPeopleImage = $advRow['photo'];
            break;
        }
    }
    if($aboutPeopleImage === '' && !empty($aboutHistoryRecord['photo'])) {
        $aboutPeopleImage = $aboutHistoryRecord['photo'];
        $aboutPeopleUpload = UPLOAD_NEWS_L;
    }

    $aboutTrustItems = array(
        array('icon' => 'fa-gem', 'title' => 'Sản phẩm chính hãng', 'description' => !empty($aboutAuthenticRecord['desc'.$lang]) ? $aboutPlainText($aboutAuthenticRecord['desc'.$lang]) : 'Thiết bị có nguồn gốc rõ ràng, thông tin minh bạch.'),
        array('icon' => 'fa-users-cog', 'title' => 'Đội ngũ chuyên môn', 'description' => !empty($aboutTeamRecord['desc'.$lang]) ? $aboutPlainText($aboutTeamRecord['desc'.$lang]) : 'Tư vấn đúng nhu cầu, triển khai theo điều kiện thực tế.'),
        array('icon' => 'fa-sun', 'title' => 'Giải pháp toàn diện', 'description' => 'Đồng bộ từ khảo sát, thiết kế đến thi công và vận hành.'),
        array('icon' => 'fa-shield-alt', 'title' => 'Đồng hành lâu dài', 'description' => !empty($aboutSupportRecord['desc'.$lang]) ? $aboutPlainText($aboutSupportRecord['desc'.$lang]) : 'Hỗ trợ kỹ thuật và chăm sóc sau bàn giao.')
    );

    $aboutCompass = array();
    $aboutCompassMap = array(
        array('record' => $aboutVisionRecord, 'icon' => 'fa-eye', 'title' => 'Tầm nhìn'),
        array('record' => $aboutMissionRecord, 'icon' => 'fa-bullseye', 'title' => 'Sứ mệnh'),
        array('record' => $aboutCoreRecord, 'icon' => 'fa-gem', 'title' => 'Giá trị cốt lõi')
    );
    foreach($aboutCompassMap as $item) {
        if(empty($item['record'])) continue;
        $item['description'] = !empty($item['record']['desc'.$lang]) ? $aboutPlainText($item['record']['desc'.$lang]) : $aboutSentences($item['record']['content'.$lang], 1);
        $item['keywords'] = array();
        if($item['title'] === 'Giá trị cốt lõi') {
            preg_match_all('/(?:^|\s)(Tâm|Tầm|Tình)\s*:/u', $aboutPlainText($item['record']['content'.$lang]), $keywordMatches);
            $item['keywords'] = !empty($keywordMatches[1]) ? array_values(array_unique($keywordMatches[1])) : array();
        }
        $aboutCompass[] = $item;
    }

    $aboutCertificates = array_slice($chungnhan, 0, 4);

	/* SEO */
	$seopage = $d->rawQueryOne("select * from #_seopage where type = ? limit 0,1",array('gioi-thieu'));
	$seo->set('h1',$titleMain);
	if(!empty($seopage['title'.$seolang])) $seo->set('title',$seopage['title'.$seolang]);
	else $seo->set('title',$titleMain);
	if(!empty($seopage['keywords'.$seolang])) $seo->set('keywords',$seopage['keywords'.$seolang]);
	if(!empty($seopage['description'.$seolang])) $seo->set('description',$seopage['description'.$seolang]);
	$seo->set('url',$func->getPageURL());
	$imgJson = (!empty($seopage['options'])) ? json_decode($seopage['options'],true) : null;
	if(!empty($seopage['photo']))
	{
		if(empty($imgJson) || ($imgJson['p'] != $seopage['photo']))
		{
			$imgJson = $func->getImgSize($seopage['photo'],UPLOAD_SEOPAGE_L.$seopage['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'seopage',$seopage['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_SEOPAGE_L.$seopage['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}
	}

	/* breadCrumbs */
	if(!empty($titleMain)) $breadcr->set($com,$titleMain);
	$breadcrumbs = $breadcr->get();
?>
