<?php  
	if(!defined('SOURCES')) die("Error");

	// $popup = $cache->get("select name$lang, photo, link from #_photo where type = ? and act = ? and find_in_set('hienthi',status) limit 0,1", array('popup','photo_static'), 'fetch', 7200);
    $adv = $cache->get("select name$lang, desc$lang, photo, link from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('adv'), 'result', 7200);
    
    $gtcl = $cache->get("select name$lang, photo, link, show_stt, video from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('gia-tri-cot-loi'), 'result', 7200);
    $probc = $cache->get("select id from #_product where type = ? and find_in_set('hienthi',status) and find_in_set('banchay',status)", array('san-pham'), 'result', 7200);
    $splistnb = $cache->get("select name$lang, slugvi, slugen, id, photo from #_product_list where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'), 'result', 7200);
    $newsnb = $cache->get("select name$lang, slugvi, slugen, desc$lang, date_created, id, photo from #_news where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('tin-tuc'), 'result', 7200);
    $solieu = $cache->get("select name$lang, solieu, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('so-lieu-thong-ke'), 'result', 7200);
    // loi ich
    $loiich = $cache->get("select name$lang, desc$lang, id, photo, photo1 from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('loi-ich'), 'result', 7200);
    $loiich_title = $cache->get("select name$lang, desc$lang,photo from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('loi-ich'), 'fetch', 7200);    
    
    // quy trinh
    $quytrinh = $cache->get("select name$lang, solieu, id, photo, desc$lang from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('quy-trinh'), 'result', 7200);
    $dahoatdong = $cache->get("select name$lang, slugvi, slugen, desc$lang, date_created, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by date_created desc limit 0,4", array('du-an'), 'result', 7200);
    $testimonial = $cache->get("select name$lang, desc$lang, content$lang, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('phan-hoi-dai-ly'), 'result', 7200);
    // $videonb = $cache->get("select id from #_photo where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status)", array('video'), 'result', 7200);
    $partner = $cache->get("select name$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('doitac'), 'result', 7200);
    $about = $cache->get("select name$lang, desc$lang,photo from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('gioi-thieu'), 'fetch', 7200);
    $doitaccongnghe = $cache->get("select name$lang, desc$lang,photo, namkinhnghiem from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('doi-tac-cong-nghe'), 'fetch', 7200);
    $tieuchi = $cache->get("select name$lang, slugvi, slugen, desc$lang, date_created, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('tieu-chi'), 'result', 7200);
    /* SEO */
    $seoDB = $seo->getOnDB(0,'setting','update','setting');
    if(!empty($seoDB['title'.$seolang])) $seo->set('h1',$seoDB['title'.$seolang]);
    if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
    if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
    if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
    $seo->set('url',$func->getPageURL());
    $imgJson = (!empty($logo['options'])) ? json_decode($logo['options'],true) : null;
    if(empty($imgJson) || ($imgJson['p'] != $logo['photo']))
    {
        $imgJson = $func->getImgSize($logo['photo'],UPLOAD_PHOTO_L.$logo['photo']);
        $seo->updateSeoDB(json_encode($imgJson),'photo',$logo['id']);
    }
    if(!empty($imgJson))
    {
        $seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_PHOTO_L.$logo['photo']);
        $seo->set('photo:width',$imgJson['w']);
        $seo->set('photo:height',$imgJson['h']);
        $seo->set('photo:type',$imgJson['m']);
    }
?>