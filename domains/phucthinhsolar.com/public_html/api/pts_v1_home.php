<?php
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { header('Allow: GET'); header('Content-Type: application/json; charset=UTF-8'); http_response_code(405); echo '{"error":{"code":"METHOD_NOT_ALLOWED"}}'; exit; }
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
try {
    require_once __DIR__.'/config.php';
    require_once LIBRARIES.'pts_public_home_api.php';
    require_once LIBRARIES.'pts_public_seo_api.php';
    $load = static function($sql, array $params) use ($cache) { $rows = $cache->get($sql, $params, 'result', 7200); return is_array($rows) ? $rows : array(); };
    $seoRow = $d->rawQueryOne('select titlevi,descriptionvi,keywordsvi from #_seo where com=? and act=? and type=? limit 1', array('setting','update','setting'));
    $logo = $d->rawQueryOne('select photo from #_photo where type=? and act=? limit 0,1', array('logo','photo_static'));
    $seo = ptsPublicSeoDto(is_array($seoRow) ? $seoRow : array(), ROOT, UPLOAD_PHOTO_L, $logo['photo'] ?? '');
    $lang = 'vi';
    $categories = $load('select id,namevi,slugvi,status,photo from #_product_list where type=? and find_in_set(\'noibat\',status) and find_in_set(\'hienthi\',status) and exists (select 1 from #_product p where p.id_list=#_product_list.id and p.type=? and find_in_set(\'hienthi\',p.status)) order by numb,id desc', array('san-pham','san-pham'));
    $majorCategories = array(); $otherCategories = array(); foreach($categories as $category) { if(strpos(','.(string)($category['status'] ?? '').',', ',major,') !== false) $majorCategories[] = $category; else $otherCategories[] = $category; } $categories = array_merge($majorCategories, $otherCategories);
    $categoryFallbacks = array('den-nlmt'=>'home-street-lighting-v2.png','tam-pin'=>'category-panels-user-2026.jpg','bien-tan'=>'category-inverter-user-2026.jpg','inverter'=>'category-inverter-user-2026.jpg','pin-luu-tru'=>'category-storage-user-2026.jpg','luu-tru'=>'category-storage-user-2026.jpg','bom'=>'category-pump-user-2026.jpg');
    $categoryOut = array(); foreach($categories as $row) { $slug = ptsHomeText($row['slugvi'] ?? ''); if($slug === '') continue; $image = ptsHomePath($row['photo'] ?? '', UPLOAD_PRODUCT_L); if($image === '') foreach($categoryFallbacks as $needle=>$filename) if(strpos($slug, $needle) !== false) { $image = ptsHomePath($filename, 'assets/images/site-visuals/'); break; } $categoryOut[] = array('id' => (int)$row['id'], 'name' => ptsHomeText($row['namevi'] ?? ''), 'slug' => $slug, 'url' => '/danh-muc-san-pham/'.$slug.'/', 'major' => strpos(','.($row['status'] ?? '').',', ',major,') !== false, 'image' => $image); }

    $heroRows = $load('select namevi,descvi,photo,link,status from #_photo where type=? order by numb,id desc', array('hero-carousel'));
    $heroDefaults = array(array('key'=>'street-light','name'=>'Đèn đường năng lượng mặt trời','desc'=>'Chiếu sáng đường nội bộ, khu dân cư và công trình.','photo'=>'home-hero-street-lighting-v1.webp'), array('key'=>'inverter','name'=>'Biến tần năng lượng mặt trời','desc'=>'Thiết bị chuyển đổi và quản lý nguồn điện.','photo'=>'home-hero-inverter-v1.webp'), array('key'=>'panel','name'=>'Tấm pin năng lượng mặt trời','desc'=>'Thiết bị quang điện cho hệ thống năng lượng mặt trời.','photo'=>'home-hero-solar-panels-v1.webp'), array('key'=>'storage','name'=>'Pin lưu trữ','desc'=>'Chủ động lưu trữ điện theo nhu cầu sử dụng.','photo'=>'home-hero-battery-storage-v1.webp'));
    $heroEnabled = array(); foreach($heroRows as $candidate) if(ptsHomeRowVisible($candidate) && !empty($candidate['photo'])) $heroEnabled[] = $candidate;
    $hero = array(); foreach($heroDefaults as $i => $default) { $row = $heroEnabled[$i] ?? array(); if($heroRows && !$row) continue; $photo = $row ? ptsHomePath($row['photo'], UPLOAD_PHOTO_L) : ptsHomePath($default['photo'], 'assets/images/site-visuals/'); if($photo === '') continue; $hero[] = array('key'=>$default['key'],'name'=>ptsHomeText($row['namevi'] ?? '') ?: $default['name'],'description'=>ptsHomeText($row['descvi'] ?? '') ?: $default['desc'],'image'=>$photo,'href'=>ptsHomeUrl($row['link'] ?? '', '/san-pham/')); }

    $campaignRows = $load('select namevi,descvi,photo,link,status from #_photo where type=? order by numb,id desc', array('home-campaign')); $campaign = array(); foreach($campaignRows as $row) { if(!ptsHomeRowVisible($row) || empty($row['photo'])) continue; $image = ptsHomePath($row['photo'], UPLOAD_PHOTO_L); if($image === '') continue; $campaign[] = array('name'=>ptsHomeText($row['namevi'] ?? '') ?: 'Giải pháp năng lượng','description'=>ptsHomeText($row['descvi'] ?? ''),'image'=>$image,'href'=>ptsHomeUrl($row['link'] ?? '', '/san-pham/')); if(count($campaign) >= 3) break; }

    $solutionRows = $load('select namevi,title_indexvi,slugvi,descvi,photo1,status from #_news where type=? and find_in_set(\'noibat\',status) and find_in_set(\'hienthi\',status) order by numb,id desc', array('giai-phap'));
    $solutionFallbacks = array(
        array('label'=>'Doanh nghiệp và nhà xưởng','needles'=>array('doanh nghiệp','doanh nghiep','nhà xưởng','nha xuong'),'name'=>'Điện mặt trời cho doanh nghiệp','description'=>'Đề xuất phương án theo nhu cầu vận hành, đặc điểm mái và điều kiện lắp đặt tại cơ sở.','image'=>'solution-bess-v2.jpg','href'=>'/giai-phap/dien-mat-troi-doanh-nghiep/'),
        array('label'=>'Gia đình','needles'=>array('gia đình','gia dinh','hộ gia đình','ho gia dinh'),'name'=>'Điện mặt trời cho gia đình','description'=>'Tư vấn hệ thống theo nhu cầu dùng điện, diện tích mái và nhu cầu lưu trữ của gia đình.','image'=>'solution-home-solar-v2.jpg','href'=>'/giai-phap/dien-mat-troi-ho-gia-dinh/'),
        array('label'=>'Chiếu sáng công trình','needles'=>array('chiếu sáng','chieu sang','đèn','den'),'name'=>'Chiếu sáng đường và công trình','description'=>'Tư vấn lựa chọn thiết bị và phương án bố trí đèn theo đặc điểm từng khu vực.','image'=>'home-solution-construction-v1.png','href'=>'/giai-phap/'),
        array('label'=>'Nông nghiệp','needles'=>array('nông nghiệp','nong nghiep','bơm','bom'),'name'=>'Bơm nước năng lượng mặt trời','description'=>'Tư vấn hệ thống theo nguồn nước, nhu cầu tưới tiêu và điều kiện sử dụng.','image'=>'solution-solar-pump-v2.jpg','href'=>'/giai-phap/'),
    );
    $solutions = array();
    foreach($solutionFallbacks as $i=>$fallback) {
        $row = array();
        foreach($solutionRows as $candidate) {
            $search = mb_strtolower(ptsHomeText(($candidate['namevi'] ?? '').' '.($candidate['title_indexvi'] ?? '')), 'UTF-8');
            foreach($fallback['needles'] as $needle) {
                if(strpos($search, mb_strtolower($needle, 'UTF-8')) !== false) { $row = $candidate; break 2; }
            }
        }
        $image = $i === 2 ? ptsHomePath($fallback['image'],'assets/images/site-visuals/') : ptsHomePath($row['photo1'] ?? '', UPLOAD_NEWS_L);
        if($image === '') $image = ptsHomePath($fallback['image'],'assets/images/site-visuals/');
        if($image === '') continue;
        $solutions[] = array('label'=>$fallback['label'],'name'=>ptsHomeText($row['namevi'] ?? '') ?: $fallback['name'],'description'=>ptsHomeText($row['descvi'] ?? '') ?: $fallback['description'],'image'=>$image,'href'=>ptsHomeUrl($row['slugvi'] ?? '' ? 'giai-phap/'.$row['slugvi'].'/' : $fallback['href']));
    }

    $processRows = $load('select namevi,solieu,descvi from #_news where type=? and find_in_set(\'hienthi\',status) order by numb,id desc limit 0,5', array('quy-trinh')); $processDefaults = array(array('01','Tư vấn & khảo sát','Lắng nghe nhu cầu, khảo sát hiện trạng và điều kiện lắp đặt.'),array('02','Thiết kế & báo giá','Xây dựng phương án kỹ thuật và lựa chọn thiết bị phù hợp.'),array('03','Thi công & lắp đặt','Triển khai theo phương án đã thống nhất, chú trọng an toàn và chất lượng.'),array('04','Nghiệm thu & bàn giao','Kiểm tra, chạy thử, nghiệm thu và hướng dẫn sử dụng.'),array('05','Bảo hành & đồng hành','Tiếp nhận yêu cầu hỗ trợ và cung cấp dịch vụ theo chính sách.')); $process = array(); foreach($processDefaults as $i=>$fallback) { $row = $processRows[$i] ?? array(); $process[] = array('number'=>ptsHomeText($row['solieu'] ?? '') ?: $fallback[0],'name'=>ptsHomeText($row['namevi'] ?? '') ?: $fallback[1],'description'=>ptsHomeText($row['descvi'] ?? '') ?: $fallback[2]); }

    $aboutRows = $load('select namevi,descvi,content1vi,status from #_news where type=? order by numb,id desc', array('home-about')); $aboutRow = array(); foreach($aboutRows as $row) if(ptsHomeRowVisible($row)) { $aboutRow=$row; break; }
    $aboutConfigured = count($aboutRows) > 0;
    $aboutVisible = !empty($aboutRow);
    $defaultAboutPoints = array(
        array('label'=>'Sản phẩm chính hãng','description'=>'Thông tin thương hiệu, thông số kỹ thuật và chính sách bảo hành rõ ràng.'),
        array('label'=>'Tư vấn và thi công','description'=>'Đề xuất thiết bị và phương án lắp đặt phù hợp với điều kiện thực tế của từng công trình.'),
        array('label'=>'Hỗ trợ sau bàn giao','description'=>'Hướng dẫn sử dụng, tiếp nhận yêu cầu bảo hành và hỗ trợ kỹ thuật trong quá trình vận hành.'),
        array('label'=>'Hệ thống kho ba khu vực','description'=>'Kho tại TP.HCM, Đà Nẵng và Gia Lai, hỗ trợ giao hàng toàn quốc.'),
    );
    $aboutPointRows = $load('select namevi,descvi,photo,status from #_news where type=? order by numb,id desc', array('home-about-point')); $aboutPoints = array();
    foreach($aboutPointRows as $row) {
        if(!ptsHomeRowVisible($row) || count($aboutPoints) >= 4) continue;
        $fallback = $defaultAboutPoints[count($aboutPoints)] ?? array('label'=>'','description'=>'');
        $aboutPoints[] = array('label'=>ptsHomeText($row['namevi'] ?? ''),'description'=>ptsHomeText($row['descvi'] ?? '') ?: $fallback['description'],'image'=>ptsHomePath($row['photo'] ?? '',UPLOAD_NEWS_L));
    }
    if(!$aboutPoints) foreach($defaultAboutPoints as $point) $aboutPoints[] = $point + array('image'=>'');
    $about = array('configured'=>$aboutConfigured,'visible'=>$aboutVisible,'title'=>ptsHomeText($aboutRow['namevi'] ?? '') ?: 'Cung cấp thiết bị và thi công năng lượng mặt trời','description'=>ptsHomeText($aboutRow['descvi'] ?? '') ?: 'Phúc Thịnh Solar cung cấp đèn năng lượng mặt trời, tấm pin, biến tần, pin lưu trữ và thiết bị bơm nước.','href'=>'/gioi-thieu/','action'=>ptsHomeText($aboutRow['content1vi'] ?? '') ?: 'Tìm hiểu về Phúc Thịnh Solar','points'=>$aboutPoints);
    $options = is_array($optsetting ?? null) ? $optsetting : array(); $contact = array('hotline'=>ptsHomeText($options['hotline'] ?? ''),'email'=>ptsHomeText($options['email'] ?? ''),'address'=>ptsHomeText($options['address'] ?? ''),'workingHours'=>ptsHomeText($options['thoigianlamviec'] ?? '')); $productGroups = array(); foreach($categories as $category) { if(count($productGroups)>=3) break; $rows=$load('select id,namevi,slugvi,photo from #_product where type=? and id_list=? and photo<>? and find_in_set(\'hienthi\',status)'.publicProductDefaultOrder().' limit 0,10',array('san-pham',(int)$category['id'],'')); $products=array(); foreach($rows as $row) { $image=ptsHomePath($row['photo'] ?? '',UPLOAD_PRODUCT_L); if($image==='') continue; $products[]=array('id'=>(int)$row['id'],'name'=>ptsHomeText($row['namevi'] ?? ''),'slug'=>ptsHomeText($row['slugvi'] ?? ''),'href'=>ptsHomeUrl('san-pham/'.($row['slugvi'] ?? '').'/','/san-pham/'),'image'=>$image); } if($products) $productGroups[]=array('categoryId'=>(int)$category['id'],'categoryName'=>ptsHomeText($category['namevi'] ?? ''),'categoryUrl'=>'/danh-muc-san-pham/'.ptsHomeText($category['slugvi'] ?? '').'/','products'=>$products); }
    $energyRows = $load('select namevi,title_indexvi,descvi,solieu,photo from #_news where type=? and find_in_set(\'hienthi\',status) order by numb,id desc limit 0,5', array('home-energy-chain'));
    $popupRow = $d->rawQueryOne('select id,date_updated,namevi,photo,link from #_photo where type=? and act=? and find_in_set(\'hienthi\',status) limit 0,1', array('popup','photo_static'));
    if(function_exists('closeSessionWrite')) closeSessionWrite(); echo json_encode(ptsPublicHomePayload(array('popup'=>is_array($popupRow) ? $popupRow : null,'energyChain'=>$energyRows,'seo'=>$seo,'hero'=>$hero,'categories'=>$categoryOut,'campaigns'=>$campaign,'campaignsConfigured'=>count($campaignRows)>0,'solutions'=>$solutions,'process'=>$process,'dealer'=>array('title'=>'ĐĂNG KÝ ĐẠI LÝ','description'=>'Cùng phát triển kinh doanh thiết bị năng lượng mặt trời với nguồn hàng đa dạng, chính sách hợp tác rõ ràng và sự đồng hành của đội ngũ Phúc Thịnh Solar.','href'=>'/dai-ly/'),'about'=>$about,'contact'=>$contact,'productGroups'=>$productGroups)),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $e) { error_log('pts_v1_home: '.$e->getMessage()); http_response_code(500); echo '{"error":{"code":"INTERNAL_ERROR"}}'; }
