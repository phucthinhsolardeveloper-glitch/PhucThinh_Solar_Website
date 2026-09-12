<?php
	if(!defined('SOURCES')) die("Error");
	require_once LIBRARIES.'product_sort.php';

	$popup = $cache->get("select id, date_updated, name$lang, photo, link from #_photo where type = ? and act = ? and find_in_set('hienthi',status) limit 0,1", array('popup','photo_static'), 'fetch', 7200);
    $adv = $cache->get("select name$lang, desc$lang, photo, link from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('adv'), 'result', 7200);
    
    // pl.status thêm để tách 4 danh mục "chủ lực" (find_in_set('major',...))
    // hiện card lớn khỏi 6 danh mục còn lại (card nhỏ) ở khối "Hệ sinh thái
    // sản phẩm" trang chủ - 2026-09-12, không đổi điều kiện lọc/sắp xếp.
    $splistnb = $cache->get("select pl.name$lang, pl.slugvi, pl.slugen, pl.id, pl.photo, pl.status from #_product_list pl where pl.type = ? and find_in_set('noibat',pl.status) and find_in_set('hienthi',pl.status) and exists (select 1 from #_product p where p.id_list = pl.id and p.type = ? and find_in_set('hienthi',p.status)) order by pl.numb,pl.id desc", array('san-pham','san-pham'), 'result', 7200);
    $newsnb = $cache->get("select name$lang, slugvi, slugen, desc$lang, date_created, id, photo from #_news where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc limit 0,6", array('tin-tuc'), 'result', 7200);
    $solieu = $cache->get("select name$lang, solieu, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('so-lieu-thong-ke'), 'result', 7200);
    // loi ich
    $loiich = $cache->get("select name$lang, desc$lang, id, photo, photo1 from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('loi-ich'), 'result', 7200);
    $loiich_title = $cache->get("select name$lang, desc$lang,photo from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('loi-ich'), 'fetch', 7200);    
    
    // quy trinh
    $quytrinh = $cache->get("select name$lang, solieu, id, photo, desc$lang from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('quy-trinh'), 'result', 7200);
    /* Không tải lại danh sách "Hoạt động dự án" ở cuối trang chủ: dữ liệu type
       du-an đã được lấy theo nhóm trong $featuredProjects và hiển thị ở khối
       "Dự án đã thực hiện" bên dưới. Trang danh sách dự án vẫn giữ nguyên. */
    $testimonial = $cache->get("select name$lang, desc$lang, content$lang, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc limit 0,4", array('phan-hoi-dai-ly'), 'result', 7200);
    // $videonb = $cache->get("select id from #_photo where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status)", array('video'), 'result', 7200);
    $partner = $cache->get("select name$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('doitac'), 'result', 7200);
    /* Chứng nhận uỷ quyền cho khối tin cậy ở hero; dùng chung nguồn với trang Về chúng tôi nên Admin chỉ cần quản lý một chỗ. */
    $chungnhan = $cache->get("select name$lang, link, photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb, id desc", array('chungnhan'), 'result', 7200);
    /* Ảnh nền Hero (chế độ 'text') - tuỳ chọn, quản lý qua Admin > Ảnh nền Hero trang chủ.
       Nhiều ảnh thì slide.php tự crossfade; không có ảnh nào thì dùng nền gradient mặc định. */
    $heroBgList = $cache->get("select photo from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('home-hero-bg'), 'result', 7200);
    $tieuchi = $cache->get("select name$lang, slugvi, slugen, desc$lang, date_created, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('tieu-chi'), 'result', 7200);
    $solutionTabs = array();
    $solutionRows = $cache->get("select name$lang, title_index$lang, slugvi, slugen, desc$lang, date_created, id, id_list, photo1 from #_news where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap'), 'result', 7200);
    foreach($solutionRows as $solutionRow) $solutionTabs[(int)$solutionRow['id_list']][] = $solutionRow;

    $solutionGallery = array();
    $solutionGalleryRows = $cache->get("select g.photo, g.id, g.id_parent, g.solieu$lang, g.donvi$lang, g.name$lang from #_gallery g inner join #_news n on n.id = g.id_parent where g.com = ? and g.type = ? and g.kind = ? and g.val = ? and find_in_set('hienthi',g.status) and n.type = ? and find_in_set('noibat',n.status) and find_in_set('hienthi',n.status) order by g.numb,g.id desc", array('news', 'giai-phap', 'man', 'thongtinphu', 'giai-phap'), 'result', 7200);
    foreach($solutionGalleryRows as $solutionGalleryRow)
    {
        $solutionId = (int)$solutionGalleryRow['id_parent'];
        if(!isset($solutionGallery[$solutionId]) || count($solutionGallery[$solutionId]) < 4) $solutionGallery[$solutionId][] = $solutionGalleryRow;
    }

    $featuredProjects = array();
    $featuredProjectRows = $cache->get("select name$lang, address, ngayhopdong, tenkhachhang, slugvi, slugen, desc$lang, date_created, id, id_list, photo from #_news where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('du-an'), 'result', 7200);
    foreach($featuredProjectRows as $featuredProjectRow) $featuredProjects[(int)$featuredProjectRow['id_list']][] = $featuredProjectRow;
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
