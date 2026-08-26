<?php 
	if(!defined('SOURCES')) die("Error");

    $tomtat = $cache->get("select name$lang, desc$lang,photo from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('tom-tat-gioi-thieu'), 'fetch', 7200);
    $lichsu = $cache->get("select name$lang, content$lang, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('lich-su'), 'result', 7200);

    $sumenh_title = $cache->get("select name$lang, desc$lang, content$lang,photo from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('su-menh'), 'fetch', 7200);
    $sumenh = $cache->get("select name$lang, content$lang, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('su-menh'), 'result', 7200);
	
	$doitacgioithieu = $cache->get("select name$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('doitacgioithieu'), 'result', 7200);
	$partner = $cache->get("select name$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('doitac'), 'result', 7200);

	// why
	$lydo = $cache->get("select name$lang, content$lang, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('ly-do-chon-chung-toi'), 'result', 7200);

	$ttlh = $cache->get("select name$lang, desc$lang, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('thong-tin-lien-he'), 'result', 7200);
	$photo_about = $cache->get("select id, photo, options from #_photo where type = ? and act = ? and find_in_set('hienthi',status) limit 0,1", array('hinh-anh-gioi-thieu','photo_static'), 'fetch', 7200);
	$partner = $cache->get("select name$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('doitac'), 'result', 7200);
	$chungnhan = $cache->get("select name$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('chungnhan'), 'result', 7200);
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