<?php
	if(!defined('SOURCES')) die("Error");
	$pageRendersVisibleH1 = true;

    /*
     * Trang Về chúng tôi dùng lại các module Admin đang có. Không đọc hai
     * static block cũ (tom-tat-gioi-thieu/su-menh) vì dữ liệu của chúng là
     * nội dung demo thuộc doanh nghiệp khác. Những trường desc/solieu bên
     * dưới đều đã có sẵn trong schema; config Admin chỉ mở chúng để biên tập
     * nội dung ngắn và năm cột mốc, không tạo thêm page builder hay bảng mới.
     */
    $lichsu = $cache->get("select name$lang, title_index$lang, desc$lang, content$lang, solieu, id, photo, status from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,8", array('lich-su'), 'result', 7200);
    $sumenh = $cache->get("select name$lang, title_index$lang, desc$lang, content$lang, id, photo, status from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,6", array('su-menh'), 'result', 7200);
    $lydo = $cache->get("select name$lang, title_index$lang, desc$lang, content$lang, id, photo, status from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('ly-do-chon-chung-toi'), 'result', 7200);
    $aboutTeamRows = $cache->get("select name$lang, title_index$lang, desc$lang, content$lang, id, photo, status from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,1", array('about-team'), 'result', 7200);
    $aboutPrincipleRows = $cache->get("select name$lang, title_index$lang, desc$lang, content$lang, id, photo, status from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,3", array('about-principle'), 'result', 7200);
    $chungnhan = $cache->get("select id, name$lang, desc$lang, link, photo, status from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('chungnhan'), 'result', 7200);
    $aboutCtaRows = $cache->get("select name$lang, desc$lang, link, photo, status from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc limit 0,1", array('about-cta'), 'result', 7200);
    $aboutAdv = $cache->get("select name$lang, desc$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('adv'), 'result', 7200);

    $aboutUi = ($lang === 'en') ? array(
        'story_eyebrow' => 'Our development journey',
        'story_title' => 'The story of Phuc Thinh Solar',
        'timeline_label' => 'Phuc Thinh Solar development milestones',
        'people_eyebrow' => 'People are our foundation',
        'people_title' => 'People of Phuc Thinh Solar',
        'people_fallback' => 'An experienced team committed to practical advice, dependable implementation and long-term customer support.',
        'principle_expertise' => 'Practical expertise',
        'principle_expertise_desc' => 'Experienced engineers and technicians with a strong understanding of real-world conditions.',
        'principle_support' => 'Committed all the way',
        'principle_support_desc' => 'Honest advice and continuous support throughout the project lifecycle.',
        'principle_customer' => 'Customer centricity',
        'principle_customer_desc' => 'We listen, understand and focus on delivering practical value.',
        'trust_eyebrow' => 'Distinctive values',
        'trust_title' => 'Why customers choose us',
        'trust_default' => 'A practical commitment maintained throughout consultation, implementation and after-sales support.',
        'trust_authentic' => 'Products with clear origins and transparent information.',
        'trust_team' => 'Advice based on real needs and implementation conditions.',
        'trust_support' => 'Technical support and customer care continue after handover.',
        'trust_warranty' => 'Clear warranty terms and a transparent support process.',
        'compass_eyebrow' => 'Guiding principles for the future',
        'compass_title' => 'Vision – Mission – Core values',
        'cert_eyebrow' => 'The foundation of trust',
        'cert_title' => 'Certificates & capabilities',
        'cert_intro' => 'Quality commitments supported by the certificates currently published.',
        'cert_label' => 'Certificate',
        'cert_all' => 'View all certificates',
        'cert_detail' => 'View details',
        'contact_title' => 'Need advice on a solar energy solution?',
        'contact_intro' => 'The Phuc Thinh Solar team is ready to support you.',
        'contact_primary' => 'Get advice now'
    ) : array(
        'story_eyebrow' => 'Hành trình phát triển',
        'story_title' => 'Câu chuyện Phúc Thịnh Solar',
        'timeline_label' => 'Các mốc phát triển của Phúc Thịnh Solar',
        'people_eyebrow' => 'Con người là nền tảng',
        'people_title' => 'Nhân sự Phúc Thịnh Solar',
        'people_fallback' => 'Đội ngũ giàu kinh nghiệm, tư vấn sát nhu cầu thực tế, triển khai có trách nhiệm và đồng hành lâu dài cùng khách hàng.',
        'principle_expertise' => 'Chuyên môn thực tế',
        'principle_expertise_desc' => 'Đội ngũ kỹ sư, kỹ thuật viên giàu kinh nghiệm, am hiểu thực tế.',
        'principle_support' => 'Đồng hành đến cùng',
        'principle_support_desc' => 'Tư vấn trung thực, hỗ trợ xuyên suốt vòng đời dự án.',
        'principle_customer' => 'Lấy khách hàng làm trung tâm',
        'principle_customer_desc' => 'Luôn lắng nghe, thấu hiểu và mang đến giá trị thiết thực.',
        'trust_eyebrow' => 'Giá trị khác biệt',
        'trust_title' => 'Vì sao khách hàng lựa chọn chúng tôi?',
        'trust_default' => 'Một cam kết thực tế được duy trì xuyên suốt từ tư vấn, triển khai đến hỗ trợ sau bàn giao.',
        'trust_authentic' => 'Thiết bị có nguồn gốc rõ ràng, thông tin minh bạch.',
        'trust_team' => 'Tư vấn đúng nhu cầu và điều kiện triển khai thực tế.',
        'trust_support' => 'Hỗ trợ kỹ thuật và chăm sóc khách hàng sau bàn giao.',
        'trust_warranty' => 'Điều kiện bảo hành rõ ràng, quy trình hỗ trợ minh bạch.',
        'compass_eyebrow' => 'Kim chỉ nam cho tương lai',
        'compass_title' => 'Tầm nhìn – Sứ mệnh – Giá trị cốt lõi',
        'cert_eyebrow' => 'Nền tảng của niềm tin',
        'cert_title' => 'Chứng nhận & năng lực',
        'cert_intro' => 'Cam kết chất lượng được thể hiện bằng những chứng nhận đang được công bố.',
        'cert_label' => 'Chứng nhận',
        'cert_all' => 'Xem tất cả chứng nhận',
        'cert_detail' => 'Xem chi tiết',
        'contact_title' => 'Cần tư vấn giải pháp năng lượng mặt trời?',
        'contact_intro' => 'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng đồng hành cùng bạn.',
        'contact_primary' => 'Nhận tư vấn ngay'
    );

    $aboutPlainText = static function($value) {
        $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
        $value = strip_tags($value);
        return trim(preg_replace('/\s+/u', ' ', $value));
    };

    $aboutImageExists = static function($filename, $uploadPath) {
        $filename = (string)$filename;
        return $filename !== '' && basename($filename) === $filename && is_file($uploadPath.$filename);
    };

    $aboutSentences = static function($value, $limit = 1) use ($aboutPlainText) {
        $plain = $aboutPlainText($value);
        if($plain === '') return '';
        $sentences = preg_split('/(?<=[.!?…])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
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

    $aboutStoryEyebrow = $aboutUi['story_eyebrow'];
    $aboutStoryTitle = ($lang === 'en') ? 'About Phuc Thinh Solar' : 'Giới thiệu Phúc Thịnh Solar';
    $aboutStoryIntro = '';
    if(!empty($aboutStoryRecord['desc'.$lang])) $aboutStoryIntro = $aboutPlainText($aboutStoryRecord['desc'.$lang]);
    if($aboutStoryIntro === '' && !empty($aboutStoryRecord['content'.$lang])) $aboutStoryIntro = $aboutSentences($aboutStoryRecord['content'.$lang], 2);
    $aboutStoryBody = !empty($aboutStoryRecord['content'.$lang]) ? $aboutSentences($aboutStoryRecord['content'.$lang], 4) : '';
    if($aboutStoryIntro !== '' && strpos($aboutStoryBody, $aboutStoryIntro) === 0) {
        $aboutStoryBody = trim(substr($aboutStoryBody, strlen($aboutStoryIntro)));
    }
    if($aboutStoryBody === $aboutStoryIntro) $aboutStoryBody = '';

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
        $managedDescription = !empty($row['desc'.$lang]) ? $aboutPlainText($row['desc'.$lang]) : '';
        $contentDescription = !empty($row['content'.$lang]) ? $aboutSentences($row['content'.$lang], 2) : '';
        $description = $contentDescription !== '' ? $contentDescription : $managedDescription;
        $description = preg_replace('/^'.$year.'\s*[—–-]?\s*/u', '', $description);
        $aboutTimeline[] = array(
            'year' => $year,
            'title' => !empty($row['title_index'.$lang]) ? $aboutPlainText($row['title_index'.$lang]) : $aboutPlainText($row['name'.$lang]),
            'description' => $description
        );
    }
    usort($aboutTimeline, static function($a, $b) { return (int)$a['year'] <=> (int)$b['year']; });
    $aboutTimeline = array_slice($aboutTimeline, 0, 4);

    $aboutTeamRecord = $aboutFind($lydo, array('doi ngu nhan su', 'doi ngu', 'experienced staff', 'team'));
    $aboutSupportRecord = $aboutFind($lydo, array('dich vu ho tro', 'cham soc khach hang', 'customer support', 'support'));
    $aboutWarrantyRecord = $aboutFind($lydo, array('bao hanh', 'warranty'));
    $aboutAuthenticRecord = $aboutFind($lydo, array('chinh hang', 'genuine', 'authentic'));
    $aboutCoreRecord = $aboutFind($sumenh, array('gia tri cot loi', 'core value'));

    $aboutPeopleRecord = !empty($aboutTeamRows) ? reset($aboutTeamRows) : array();
    $aboutPeopleEyebrow = !empty($aboutPeopleRecord['title_index'.$lang]) ? $aboutPlainText($aboutPeopleRecord['title_index'.$lang]) : $aboutUi['people_eyebrow'];
    $aboutPeopleTitle = !empty($aboutPeopleRecord['name'.$lang]) ? $aboutPlainText($aboutPeopleRecord['name'.$lang]) : $aboutUi['people_title'];
    $aboutPeopleIntro = !empty($aboutPeopleRecord['desc'.$lang]) ? $aboutPlainText($aboutPeopleRecord['desc'.$lang]) : $aboutUi['people_fallback'];
    $aboutPeopleBody = !empty($aboutPeopleRecord['content'.$lang]) ? $aboutSentences($aboutPeopleRecord['content'.$lang], 2) : '';

    $aboutPeopleImage = '';
    $aboutPeopleUpload = UPLOAD_PHOTO_L;
    if(!empty($aboutPeopleRecord['photo']) && $aboutImageExists($aboutPeopleRecord['photo'], ROOT.UPLOAD_NEWS_L)) {
        $aboutPeopleImage = $aboutPeopleRecord['photo'];
        $aboutPeopleUpload = UPLOAD_NEWS_L;
    }
    foreach($aboutAdv as $advRow) {
        if($aboutPeopleImage === '' && isset($advRow['link']) && $advRow['link'] === '#ptsContactModal' && !empty($advRow['photo']) && $aboutImageExists($advRow['photo'], ROOT.UPLOAD_PHOTO_L)) {
            $aboutPeopleImage = $advRow['photo'];
            break;
        }
    }
    /* Không lấy ảnh showroom làm ảnh nhân sự và không dựng người giả. Khi
       Admin chưa có ảnh đội ngũ thật, section tự chuyển sang bố cục chỉ chữ. */

    /* Ba điểm nhấn dưới section Con người có module Admin riêng. Dữ liệu cũ
       chỉ làm fallback để giao diện không bị trống trước khi biên tập viên
       tạo các record about-principle. */
    $aboutPrinciples = array();
    foreach($aboutPrincipleRows as $row) {
        $aboutPrinciples[] = array(
            'title' => !empty($row['title_index'.$lang]) ? $aboutPlainText($row['title_index'.$lang]) : $aboutPlainText($row['name'.$lang]),
            'description' => !empty($row['desc'.$lang]) ? $aboutPlainText($row['desc'.$lang]) : $aboutSentences($row['content'.$lang], 1),
            'photo' => isset($row['photo']) ? $row['photo'] : ''
        );
    }
    if(empty($aboutPrinciples)) {
        $aboutPrincipleFallbacks = array(
            array('row' => $aboutTeamRecord, 'title' => $aboutUi['principle_expertise'], 'description' => $aboutUi['principle_expertise_desc']),
            array('row' => $aboutSupportRecord, 'title' => $aboutUi['principle_support'], 'description' => $aboutUi['principle_support_desc']),
            array('row' => $aboutCoreRecord, 'title' => $aboutUi['principle_customer'], 'description' => $aboutUi['principle_customer_desc'])
        );
        foreach($aboutPrincipleFallbacks as $fallback) {
            $row = $fallback['row'];
            $aboutPrinciples[] = array(
                'title' => $fallback['title'],
                'description' => !empty($row['desc'.$lang]) ? $aboutPlainText($row['desc'.$lang]) : $fallback['description'],
                'photo' => isset($row['photo']) ? $row['photo'] : ''
            );
        }
    }

    $aboutTrustRows = array_values(array_filter($lydo, static function($row) {
        return !empty($row['status']) && in_array('about', explode(',', $row['status']), true);
    }));
    if(empty($aboutTrustRows)) {
        foreach(array($aboutAuthenticRecord, $aboutTeamRecord, $aboutSupportRecord, $aboutWarrantyRecord) as $fallbackRow) {
            if(!empty($fallbackRow['id'])) $aboutTrustRows[$fallbackRow['id']] = $fallbackRow;
        }
        $aboutTrustRows = array_values($aboutTrustRows);
    }

    $aboutTrustItems = array();
    foreach(array_slice($aboutTrustRows, 0, 4) as $row) {
        $normalizedTitle = $aboutNormalize(isset($row['name'.$lang]) ? $row['name'.$lang] : '');
        $fallbackDescription = $aboutUi['trust_default'];
        if(strpos($normalizedTitle, 'chinh hang') !== false || strpos($normalizedTitle, 'genuine') !== false || strpos($normalizedTitle, 'authentic') !== false) $fallbackDescription = $aboutUi['trust_authentic'];
        elseif(strpos($normalizedTitle, 'doi ngu') !== false || strpos($normalizedTitle, 'team') !== false || strpos($normalizedTitle, 'staff') !== false) $fallbackDescription = $aboutUi['trust_team'];
        elseif(strpos($normalizedTitle, 'bao hanh') !== false || strpos($normalizedTitle, 'warranty') !== false) $fallbackDescription = $aboutUi['trust_warranty'];
        elseif(strpos($normalizedTitle, 'ho tro') !== false || strpos($normalizedTitle, 'support') !== false) $fallbackDescription = $aboutUi['trust_support'];
        $aboutTrustItems[] = array(
            'title' => !empty($row['title_index'.$lang]) ? $aboutPlainText($row['title_index'.$lang]) : $aboutPlainText($row['name'.$lang]),
            'description' => !empty($row['desc'.$lang]) ? $aboutPlainText($row['desc'.$lang]) : $fallbackDescription,
            'photo' => isset($row['photo']) ? $row['photo'] : ''
        );
    }

    $aboutCompass = array();
    foreach(array_slice($sumenh, 0, 3) as $row) {
        $aboutCompass[] = array(
            'title' => !empty($row['title_index'.$lang]) ? $aboutPlainText($row['title_index'.$lang]) : $aboutPlainText($row['name'.$lang]),
            'description' => !empty($row['desc'.$lang]) ? $aboutPlainText($row['desc'.$lang]) : $aboutSentences($row['content'.$lang], 1),
            'photo' => isset($row['photo']) ? $row['photo'] : ''
        );
    }

    $aboutFeaturedCertificates = array_values(array_filter($chungnhan, static function($row) {
        return !empty($row['status']) && in_array('noibat', explode(',', $row['status']), true);
    }));
    $aboutCertificates = array_slice(!empty($aboutFeaturedCertificates) ? $aboutFeaturedCertificates : $chungnhan, 0, 3);
    $aboutCertificateIds = array_column($aboutCertificates, 'id');
    $aboutHiddenCertificates = array_values(array_filter($chungnhan, static function($row) use ($aboutCertificateIds) {
        return !in_array($row['id'], $aboutCertificateIds, true);
    }));

    $aboutCta = !empty($aboutCtaRows) ? reset($aboutCtaRows) : array();
    $aboutCtaTitle = !empty($aboutCta['name'.$lang]) ? $aboutPlainText($aboutCta['name'.$lang]) : $aboutUi['contact_title'];
    $aboutCtaIntro = !empty($aboutCta['desc'.$lang]) ? $aboutPlainText($aboutCta['desc'.$lang]) : $aboutUi['contact_intro'];
    $aboutCtaLink = !empty($aboutCta['link']) ? trim((string)$aboutCta['link']) : '#ptsContactModal';
    if(!preg_match('~^(?:#ptsContactModal|https?://[^\s]+|/?[a-zA-Z0-9][a-zA-Z0-9/_?&=.#%-]*)$~', $aboutCtaLink)) $aboutCtaLink = '#ptsContactModal';

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
