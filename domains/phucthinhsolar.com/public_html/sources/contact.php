<?php
	if(!defined('SOURCES')) die("Error");

	/* Xử lý submit-contact đã chuyển sang sources/allpage.php để form Liên hệ
	   dùng được từ MỌI trang (popup toàn site), không chỉ riêng trang lien-he
	   — cùng cách submit-newsletter/submit-newsletter2 đã hoạt động từ trước. */

	/* SEO */
	$seopage = $d->rawQueryOne("select * from #_seopage where type = ? limit 0,1",array('lien-he'));
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
	
	$lienhe = $d->rawQueryOne("select content$lang from #_static where type = ? limit 0,1",array('lienhe'));
	$hethongcuahang = $cache->get("select name$lang, address, id, map_iframe from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('he-thong-cua-hang'), 'result', 7200);

	/* Ảnh tư vấn đầu trang: fallback đóng gói chỉ xuất hiện khi type chưa từng
	   được cấu hình. Khi Admin đã tạo record, trạng thái Hiển thị quyết định
	   hoàn toàn việc có render ảnh hay không. */
	$contactVisualRows = $cache->get("select name$lang, desc$lang, link, photo, status from #_photo where type = ? order by numb,id desc", array('contact-visual'), 'result', 7200);
	$contactVisual = array();
	foreach($contactVisualRows as $contactVisualRow)
	{
		if(empty($contactVisualRow['photo']) || strpos(','.(string)$contactVisualRow['status'].',', ',hienthi,') === false) continue;
		$contactVisual = array(
			'name' => !empty($contactVisualRow['name'.$lang]) ? $contactVisualRow['name'.$lang] : 'Tư vấn đúng thiết bị, đúng nhu cầu sử dụng',
			'desc' => !empty($contactVisualRow['desc'.$lang]) ? trim(strip_tags(htmlspecialchars_decode($contactVisualRow['desc'.$lang], ENT_QUOTES))) : '',
			'link' => !empty($contactVisualRow['link']) ? $contactVisualRow['link'] : '#yeu-cau-tu-van',
			'photo' => $contactVisualRow['photo'],
			'upload' => UPLOAD_PHOTO_L
		);
		break;
	}
	if(!$contactVisualRows)
	{
		$contactVisual = array(
			'name' => 'Tư vấn đúng thiết bị, đúng nhu cầu sử dụng',
			'desc' => 'Trao đổi cùng đội ngũ Phúc Thịnh Solar để chọn cấu hình phù hợp cho gia đình, cửa hàng hoặc công trình.',
			'link' => '#yeu-cau-tu-van',
			'photo' => 'contact-consultation.jpg',
			'upload' => 'assets/images/site-visuals/'
		);
	}

	/* breadCrumbs */
	if(!empty($titleMain)) $breadcr->set($com,$titleMain);
	$breadcrumbs = $breadcr->get();
?>
