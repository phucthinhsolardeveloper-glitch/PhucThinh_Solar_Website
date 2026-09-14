<?php
	if(!defined('SOURCES')) die("Error");
	require_once LIBRARIES.'product_projects.php';
    if($type === 'tin-tuc') $getPage = isset($_GET['p']) && is_scalar($_GET['p']) ? max(1, min(1000000, (int)$_GET['p'])) : 1;

	/* Tên tác giả thật (tài khoản đã tạo bài) thay vì chữ cứng giống nhau cho
	   mọi bài. Bài cũ/id_user=0 vẫn hiện tên mặc định như trước. */
	function newsAuthorName($d, $idUser)
	{
		static $cache = array();
		$idUser = (int) $idUser;
		if($idUser <= 0) return 'Phúc Thịnh Solar';
		if(!array_key_exists($idUser, $cache))
		{
			$row = $d->rawQueryOne("select fullname from #_user where id = ? limit 0,1", array($idUser));
			$cache[$idUser] = (!empty($row['fullname'])) ? $row['fullname'] : 'Phúc Thịnh Solar';
		}
		return $cache[$idUser];
	}

	@$id = htmlspecialchars($_GET['id']);
	@$idl = htmlspecialchars($_GET['idl']);
	@$idc = htmlspecialchars($_GET['idc']);
	@$idi = htmlspecialchars($_GET['idi']);
	@$ids = htmlspecialchars($_GET['ids']);

	if($id!='')
	{
		/* Lấy bài viết detail */
		$rowDetail = $d->rawQueryOne("select * from #_news where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($id,$type));

		/* Cập nhật lượt xem */
		$views = array();
		$views['view'] = $rowDetail['view'] + 1;
		$d->where('id',$rowDetail['id']);
		$d->update('news',$views);

		/* Lấy cấp 1 */
		$newsList = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_list where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($rowDetail['id_list'],$type));

		/* Lấy cấp 2 */
		$newsCat = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_cat where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($rowDetail['id_cat'],$type));

		/* Lấy cấp 3 */
		$newsItem = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_item where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($rowDetail['id_item'],$type));

		/* Lấy cấp 4 */
		$newsSub = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_sub where id = ? and type = ? and find_in_set('hienthi',status) limit 0,1",array($rowDetail['id_sub'],$type));
		$rowDetailPhoto = $d->rawQuery("select photo from #_gallery where id_parent = ? and com='news' and type = ? and kind='man' and val = ? and find_in_set('hienthi',status) order by numb,id desc",array($rowDetail['id'],$type,$type));
		$rowDetailVideo = $d->rawQuery("select photo, name$lang, link_video from #_gallery where id_parent = ? and com='news' and type = ? and kind='man' and val = ? and find_in_set('hienthi',status) order by numb,id desc",array($rowDetail['id'],$type,'video'));
		$projectRelatedProducts = array();
		if($type === 'du-an')
		{
			$projectRelatedProducts = $d->rawQuery("select p.id, p.name$lang, p.slugvi, p.slugen, p.photo, p.code from #_product_project pp inner join #_product p on p.id = pp.id_product where pp.id_project = ? and find_in_set('hienthi',pp.status) and p.type = ? and find_in_set('hienthi',p.status) order by pp.numb,pp.id", array($rowDetail['id'], 'san-pham'));
		}
		/* Lấy bài viết cùng loại */
		$where = "";
		$where = "id <> ? and id_list = ? and type = ? and find_in_set('hienthi',status)";
		$params = array($id,$rowDetail['id_list'],$type);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select id, id_user, name$lang, slugvi, slugen, photo, date_created, desc$lang, email, phone, address from #_news where $where order by numb,id desc $limit";
		$news = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news where $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* SEO */
		$seoDB = $seo->getOnDB($rowDetail['id'],'news','man',$rowDetail['type']);
		$seo->set('h1',$rowDetail['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$rowDetail['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($rowDetail['options'])) ? json_decode($rowDetail['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $rowDetail['photo']))
		{
			$imgJson = $func->getImgSize($rowDetail['photo'],UPLOAD_NEWS_L.$rowDetail['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'news',$rowDetail['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_NEWS_L.$rowDetail['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($newsList)) $breadcr->set($newsList[$sluglang],$newsList['name'.$lang]);
		if(!empty($newsCat)) $breadcr->set($newsCat[$sluglang],$newsCat['name'.$lang]);
		if(!empty($newsItem)) $breadcr->set($newsItem[$sluglang],$newsItem['name'.$lang]);
		if(!empty($newsSub)) $breadcr->set($newsSub[$sluglang],$newsSub['name'.$lang]);
		$breadcr->set($rowDetail[$sluglang],$rowDetail['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($idl!='')
	{
		/* Lấy cấp 1 detail */
		$newsList = $d->rawQueryOne("select id, name$lang, desc$lang, slugvi, slugen, type, photo, options from #_news_list where id = ? and type = ? limit 0,1",array($idl,$type));

		/* SEO */
		$titleCate = $newsList['name'.$lang];
		$seoDB = $seo->getOnDB($newsList['id'],'news','man_list',$newsList['type']);
		$seo->set('h1',$newsList['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$newsList['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($newsList['options'])) ? json_decode($newsList['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $newsList['photo']))
		{
			$imgJson = $func->getImgSize($newsList['photo'],UPLOAD_NEWS_L.$newsList['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'news_list',$newsList['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_NEWS_L.$newsList['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* Lấy bài viết */
		$where = "";
		$where = "id_list = ? and type = ? and find_in_set('hienthi',status)";
		$params = array($idl,$type);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		/* status thêm để project_hub.php đọc cờ "projecthub" (dự án nổi bật
		   trang Dự án) - chỉ nhánh du-an dùng, các type khác bỏ qua cột này. */
		$sql = "select id, id_list, id_user, name$lang, slugvi, slugen, photo, date_created, desc$lang, email, phone, address, tenkhachhang, ngayhopdong, status from #_news where $where order by numb,id desc $limit";
		$news = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news where $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($newsList)) $breadcr->set($newsList[$sluglang],$newsList['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($idc!='')
	{
		/* Lấy cấp 2 detail */
		$newsCat = $d->rawQueryOne("select id, id_list, name$lang, slugvi, slugen, type, photo, options from #_news_cat where id = ? and type = ? limit 0,1",array($idc,$type));

		/* Lấy cấp 1 */
		$newsList = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_list where id = ? and type = ? limit 0,1",array($newsCat['id_list'],$type));
		
		/* Lấy bài viết */
		$where = "";
		$where = "id_cat = ? and type = ? and find_in_set('hienthi',status)";
		$params = array($idc,$type);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select id, id_user, name$lang, slugvi, slugen, photo, date_created, desc$lang, email, phone, address from #_news where $where order by numb,id desc $limit";
		$news = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news where $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* SEO */
		$titleCate = $newsCat['name'.$lang];
		$seoDB = $seo->getOnDB($newsCat['id'],'news','man_cat',$newsCat['type']);
		$seo->set('h1',$newsCat['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$newsCat['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($newsCat['options'])) ? json_decode($newsCat['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $newsCat['photo']))
		{
			$imgJson = $func->getImgSize($newsCat['photo'],UPLOAD_NEWS_L.$newsCat['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'news_cat',$newsCat['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_NEWS_L.$newsCat['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($newsList)) $breadcr->set($newsList[$sluglang],$newsList['name'.$lang]);
		if(!empty($newsCat)) $breadcr->set($newsCat[$sluglang],$newsCat['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($idi!='')
	{
		/* Lấy cấp 3 detail */
		$newsItem = $d->rawQueryOne("select id, id_list, id_cat, name$lang, slugvi, slugen, type, photo, options from #_news_item where id = ? and type = ? limit 0,1",array($idi,$type));

		/* Lấy cấp 1 */
		$newsList = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_list where id = ? and type = ? limit 0,1",array($newsItem['id_list'],$type));

		/* Lấy cấp 2 */
		$newsCat = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_cat where id_list = ? and id = ? and type = ? limit 0,1",array($newsItem['id_list'],$newsItem['id_cat'],$type));

		/* Lấy bài viết */
		$where = "";
		$where = "id_item = ? and type = ? and find_in_set('hienthi',status)";
		$params = array($idi,$type);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select id, id_user, name$lang, slugvi, slugen, photo, date_created, desc$lang, email, phone, address from #_news where $where order by numb,id desc $limit";
		$news = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news where $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* SEO */
		$titleCate = $newsItem['name'.$lang];
		$seoDB = $seo->getOnDB($newsItem['id'],'news','man_item',$newsItem['type']);
		$seo->set('h1',$newsItem['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$newsItem['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($newsItem['options'])) ? json_decode($newsItem['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $newsItem['photo']))
		{
			$imgJson = $func->getImgSize($newsItem['photo'],UPLOAD_NEWS_L.$newsItem['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'news_item',$newsItem['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_NEWS_L.$newsItem['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($newsList)) $breadcr->set($newsList[$sluglang],$newsList['name'.$lang]);
		if(!empty($newsCat)) $breadcr->set($newsCat[$sluglang],$newsCat['name'.$lang]);
		if(!empty($newsItem)) $breadcr->set($newsItem[$sluglang],$newsItem['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else if($ids!='')
	{
		/* Lấy cấp 4 */
		$newsSub = $d->rawQueryOne("select id, id_list, id_cat, id_item, name$lang, slugvi, slugen, type, photo, options from #_news_sub where id = ? and type = ? limit 0,1",array($ids,$type));

		/* Lấy cấp 1 */
		$newsList = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_list where id = ? and type = ? limit 0,1",array($newsSub['id_list'],$type));

		/* Lấy cấp 2 */
		$newsCat = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_cat where id_list = ? and id = ? and type = ? limit 0,1",array($newsSub['id_list'],$newsSub['id_cat'],$type));

		/* Lấy cấp 3 */
		$newsItem = $d->rawQueryOne("select id, name$lang, slugvi, slugen from #_news_item where id_list = ? and id_cat = ? and id = ? and type = ? limit 0,1",array($newsSub['id_list'],$newsSub['id_cat'],$newsSub['id_item'],$type));

		/* Lấy bài viết */
		$where = "";
		$where = "id_sub = ? and type = ? and find_in_set('hienthi',status)";
		$params = array($ids,$type);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select id, id_user, name$lang, slugvi, slugen, photo, date_created, desc$lang, email, phone, address from #_news where $where order by numb,id desc $limit";
		$news = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news where $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* SEO */
		$titleCate = $newsSub['name'.$lang];
		$seoDB = $seo->getOnDB($newsSub['id'],'news','man_sub',$newsSub['type']);
		$seo->set('h1',$newsSub['name'.$lang]);
		if(!empty($seoDB['title'.$seolang])) $seo->set('title',$seoDB['title'.$seolang]);
		else $seo->set('title',$newsSub['name'.$lang]);
		if(!empty($seoDB['keywords'.$seolang])) $seo->set('keywords',$seoDB['keywords'.$seolang]);
		if(!empty($seoDB['description'.$seolang])) $seo->set('description',$seoDB['description'.$seolang]);
		$seo->set('url',$func->getPageURL());
		$imgJson = (!empty($newsSub['options'])) ? json_decode($newsSub['options'],true) : null;
		if(empty($imgJson) || ($imgJson['p'] != $newsSub['photo']))
		{
			$imgJson = $func->getImgSize($newsSub['photo'],UPLOAD_NEWS_L.$newsSub['photo']);
			$seo->updateSeoDB(json_encode($imgJson),'news_sub',$newsSub['id']);
		}
		if(!empty($imgJson))
		{
			$seo->set('photo',$configBase.THUMBS.'/'.$imgJson['w'].'x'.$imgJson['h'].'x2/'.UPLOAD_NEWS_L.$newsSub['photo']);
			$seo->set('photo:width',$imgJson['w']);
			$seo->set('photo:height',$imgJson['h']);
			$seo->set('photo:type',$imgJson['m']);
		}

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		if(!empty($newsList)) $breadcr->set($newsList[$sluglang],$newsList['name'.$lang]);
		if(!empty($newsCat)) $breadcr->set($newsCat[$sluglang],$newsCat['name'.$lang]);
		if(!empty($newsItem)) $breadcr->set($newsItem[$sluglang],$newsItem['name'.$lang]);
		if(!empty($newsSub)) $breadcr->set($newsSub[$sluglang],$newsSub['name'.$lang]);
		$breadcrumbs = $breadcr->get();
	}
	else
	{
		/* SEO */
		$seopage = $d->rawQueryOne("select * from #_seopage where type = ? limit 0,1",array($type));
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

		/* Lấy tất cả bài viết */
		$where = "";
		$where = "type = ? and find_in_set('hienthi',status)";
		$params = array($type);

		$curPage = $getPage;
		$perPage = 12;
		$startpoint = ($curPage * $perPage) - $perPage;
		$limit = " limit ".$startpoint.",".$perPage;
		/* status thêm để project_hub.php đọc cờ "projecthub" (dự án nổi bật
		   trang Dự án) - chỉ nhánh du-an dùng, các type khác bỏ qua cột này. */
		$sql = "select id, id_list, id_user, name$lang, slugvi, slugen, photo, date_created, desc$lang, email, phone, address, tenkhachhang, ngayhopdong, status from #_news where $where order by numb,id desc $limit";
		$news = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_news where $where order by numb,id desc";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = $func->getCurrentPageURL();
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		/* breadCrumbs */
		if(!empty($titleMain)) $breadcr->set($com,$titleMain);
		$breadcrumbs = $breadcr->get();
	}
    if($type === 'tin-tuc') require SOURCES.'news_hub.php';
    if($type === 'giai-phap' && ($template === 'news/service_list' || $template === 'news/service')) require SOURCES.'solution_hub.php';
    if($type === 'du-an' && ($template === 'news/project' || $template === 'news/project_detail')) require SOURCES.'project_hub.php';
?>
