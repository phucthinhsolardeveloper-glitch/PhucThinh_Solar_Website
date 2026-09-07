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

	/* breadCrumbs */
	if(!empty($titleMain)) $breadcr->set($com,$titleMain);
	$breadcrumbs = $breadcr->get();
?>
