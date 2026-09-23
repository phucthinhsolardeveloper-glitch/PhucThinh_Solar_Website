<?php
    if(!defined('SOURCES')) die("Error");

    /* Query allpage */
    $favicon = $cache->get("select photo from #_photo where type = ? and act = ? and find_in_set('hienthi',status) limit 0,1", array('favicon','photo_static'), 'fetch', 7200);
    $logo = $cache->get("select id, photo, options from #_photo where type = ? and act = ? limit 0,1", array('logo','photo_static'), 'fetch', 7200);
    $logof = $cache->get("select id, photo, options from #_photo where type = ? and act = ? limit 0,1", array('logo-footer','photo_static'), 'fetch', 7200);
    $brand = $cache->get("select name$lang, slugvi, slugen, id, photo from #_product_brand where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'), 'result', 7200);
    $congSuatList = $cache->get("select distinct cong_suat from #_product where type = ? and cong_suat is not null and find_in_set('hienthi',status) order by cong_suat asc", array('san-pham'), 'result', 7200);
    $bct = $cache->get("select photo, link from #_photo where type = ? and act = ? and find_in_set('hienthi',status) limit 0,1", array('bct','photo_static'), 'fetch', 7200);
    // $slogan = $cache->get("select name$lang from #_static where type = ? limit 0,1", array('slogan'), 'fetch', 7200);
    $slider = $cache->get("select name$lang, photo, link, show_stt, video from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('slide'), 'result', 7200);
    $social = $cache->get("select name$lang, photo, link from #_photo where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('social'), 'result', 7200);
    $splist = $cache->get("select pl.name$lang, pl.slugvi, pl.slugen, pl.id, pl.photo from #_product_list pl where pl.type = ? and find_in_set('hienthi',pl.status) and exists (select 1 from #_product p where p.id_list = pl.id and p.type = ? and find_in_set('hienthi',p.status)) order by pl.numb,pl.id desc", array('san-pham','san-pham'), 'result', 7200);
    /* Ẩn danh mục rỗng khỏi điều hướng, cùng quy tắc đang áp cho $splist ở trên:
       menu không nên dẫn tới trang danh mục không có bài nào. */
    $gplist = $cache->get("select nl.namevi, nl.nameen, nl.slugvi, nl.slugen, nl.id, nl.photo from #_news_list nl where nl.type = ? and find_in_set('hienthi',nl.status) and exists (select 1 from #_news n where n.id_list = nl.id and n.type = ? and find_in_set('hienthi',n.status)) order by nl.numb,nl.id desc", array('giai-phap','giai-phap'), 'result', 7200);
    /* Menu đi thẳng tới bài Giải pháp. Các nhóm news_list vẫn giữ URL legacy
       nhưng không còn tạo thêm một bước click trong điều hướng công khai. */
    $solutionMenuLinks = array();
    $solutionMenuRows = $cache->get("select id,namevi,nameen,title_indexvi,title_indexen,slugvi,slugen,status from #_news where type = ? order by numb,id desc", array('giai-phap'), 'result', 7200);
    $solutionMenuHasSelection = false;
    foreach($solutionMenuRows as $solutionMenuRow)
        if(in_array('solutionhub', explode(',', $solutionMenuRow['status']), true)) $solutionMenuHasSelection = true;
    foreach($solutionMenuRows as $solutionMenuRow)
    {
        $solutionMenuStatuses = explode(',', $solutionMenuRow['status']);
        if(!in_array('hienthi', $solutionMenuStatuses, true)) continue;
        if($solutionMenuHasSelection && !in_array('solutionhub', $solutionMenuStatuses, true)) continue;
        $solutionMenuLinks[] = $solutionMenuRow;
    }
    $ttlist = $cache->get("select nl.name$lang, nl.slugvi, nl.slugen, nl.id from #_news_list nl where nl.type = ? and find_in_set('hienthi',nl.status) and exists (select 1 from #_news n where n.id_list = nl.id and n.type = ? and find_in_set('hienthi',n.status)) order by nl.numb,nl.id desc", array('tin-tuc','tin-tuc'), 'result', 7200);
    $dalist = $cache->get("select nl.name$lang, nl.slugvi, nl.slugen, nl.id, nl.photo from #_news_list nl where nl.type = ? and find_in_set('hienthi',nl.status) and exists (select 1 from #_news n where n.id_list = nl.id and n.type = ? and find_in_set('hienthi',n.status)) order by nl.numb,nl.id desc", array('du-an','du-an'), 'result', 7200);
    $groupMenuRows = function($rows, $parentKey) {
        $grouped = array();
        foreach($rows as $row) $grouped[(int)$row[$parentKey]][] = $row;
        return $grouped;
    };
    $productMenuCats = $groupMenuRows($cache->get("select pc.name$lang, pc.slugvi, pc.slugen, pc.id, pc.id_list, pl.slugvi list_slugvi, pl.slugen list_slugen from #_product_cat pc join #_product_list pl on pl.id = pc.id_list where pc.type = ? and find_in_set('hienthi',pc.status) and exists (select 1 from #_product p where p.id_cat = pc.id and p.type = ? and find_in_set('hienthi',p.status)) order by pc.numb,pc.id desc", array('san-pham','san-pham'), 'result', 7200), 'id_list');
    $productMenuItems = $groupMenuRows($cache->get("select pi.name$lang, pi.slugvi, pi.slugen, pi.id, pi.id_cat, pc.slugvi cat_slugvi, pc.slugen cat_slugen, pl.slugvi list_slugvi, pl.slugen list_slugen from #_product_item pi join #_product_cat pc on pc.id=pi.id_cat join #_product_list pl on pl.id=pi.id_list where pi.type = ? and find_in_set('hienthi',pi.status) and exists (select 1 from #_product p where p.id_item = pi.id and p.type = ? and find_in_set('hienthi',p.status)) order by pi.numb,pi.id desc", array('san-pham','san-pham'), 'result', 7200), 'id_cat');
    $productMenuSubs = $groupMenuRows($cache->get("select ps.name$lang, ps.slugvi, ps.slugen, ps.id, ps.id_item, pi.slugvi item_slugvi, pi.slugen item_slugen, pc.slugvi cat_slugvi, pc.slugen cat_slugen, pl.slugvi list_slugvi, pl.slugen list_slugen from #_product_sub ps join #_product_item pi on pi.id=ps.id_item join #_product_cat pc on pc.id=ps.id_cat join #_product_list pl on pl.id=ps.id_list where ps.type = ? and find_in_set('hienthi',ps.status) and exists (select 1 from #_product p where p.id_sub = ps.id and p.type = ? and find_in_set('hienthi',p.status)) order by ps.numb,ps.id desc", array('san-pham','san-pham'), 'result', 7200), 'id_item');
    $newsMenuCats = $groupMenuRows($cache->get("select name$lang, slugvi, slugen, id, id_list from #_news_cat where type in (?, ?) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap', 'tin-tuc'), 'result', 7200), 'id_list');
    $newsMenuItems = $groupMenuRows($cache->get("select name$lang, slugvi, slugen, id, id_cat from #_news_item where type in (?, ?) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap', 'tin-tuc'), 'result', 7200), 'id_cat');
    $newsMenuSubs = $groupMenuRows($cache->get("select name$lang, slugvi, slugen, id, id_item from #_news_sub where type in (?, ?) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap', 'tin-tuc'), 'result', 7200), 'id_item');
    // $bct = $cache->get("select name$lang, content$lang from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('bct'), 'fetch', 7200);
    // $tagsProduct = $cache->get("select name$lang, slugvi, slugen, id from #_tags where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'), 'result', 7200);
    // $tagsNews = $cache->get("select name$lang, slugvi, slugen, id from #_tags where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('tin-tuc'), 'result', 7200);
    $policy = $cache->get("select name$lang, slugvi, slugen, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('chinh-sach'), 'result', 7200);
    /* Dùng chung 1 nguồn với trang Liên hệ (was 'he-thong-dai-ly', tách rời,
       phải sửa 2 nơi mỗi lần đổi địa chỉ chi nhánh - gộp lại 2026-09-10). */
    /* Thứ tự hiển thị thống nhất ở footer và các khu vực dùng hệ thống điểm giao
       dịch: Trụ sở -> Kho Gia Lai -> các kho còn lại. Phần còn lại vẫn tôn trọng
       numb/id để Admin tiếp tục điều khiển thứ tự sau hai mục ưu tiên. */
    $cn = $cache->get("select name$lang, address, id, photo, ggmap from #_news where type = ? and find_in_set('hienthi',status) order by case when namevi like 'Trụ sở%' then 0 when namevi like 'Kho Gia Lai%' then 1 else 2 end, numb,id desc", array('he-thong-cua-hang'), 'result', 7200);
    $footer = $cache->get("select name$lang, content$lang,photo from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('footer'), 'fetch', 7200);
    $city = $cache->get("select name, id from #_city where find_in_set('hienthi', status) order by numb asc, id asc", null, 'result', 7200);
    /* Get statistic */
    $counter = $statistic->getCounter();
    $online = $statistic->getOnline();

    /* Chuẩn hoá input form public trước khi kiểm tra/lưu. Không nhận array ở
       vị trí scalar và giới hạn độ dài để tránh dữ liệu quá cỡ vào DB/email. */
    $formLeadValue = function($input, $key, $maxLength) {
        if(!is_array($input) || !isset($input[$key]) || !is_scalar($input[$key])) return '';
        $value = trim((string)$input[$key]);
        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength, 'UTF-8') : substr($value, 0, $maxLength);
    };

    /* Newsletter */
    if(!empty($_POST['submit-newsletter']))
    {
        if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : '')) $func->transfer("Phiên đăng ký không hợp lệ. Vui lòng tải lại trang.", $func->getPageURL(), false);
        $publicIp = formClientIp($_SERVER, isset($config['login']['trusted-proxies']) ? $config['login']['trusted-proxies'] : array());
        if(!formPersistentRateLimit($d, 'newsletter-consulting', $publicIp, 5, 600)) $func->transfer("Bạn đăng ký quá nhanh. Vui lòng thử lại sau.", $func->getPageURL(), false);
        $responseCaptcha = isset($_POST['recaptcha_response_newsletter']) ? $_POST['recaptcha_response_newsletter'] : '';
        $resultCaptcha = $func->checkRecaptcha($responseCaptcha);
        $scoreCaptcha = (!empty($resultCaptcha['score'])) ? $resultCaptcha['score'] : 0;
        $actionCaptcha = (!empty($resultCaptcha['action'])) ? $resultCaptcha['action'] : '';
        $testCaptcha = (!empty($resultCaptcha['test'])) ? $resultCaptcha['test'] : false;
        $dataNewsletterInput = (!empty($_POST['dataNewsletter']) && is_array($_POST['dataNewsletter'])) ? $_POST['dataNewsletter'] : array();
        $dataNewsletter = array(
            'fullname' => $formLeadValue($dataNewsletterInput, 'fullname', 100),
            'phone' => $formLeadValue($dataNewsletterInput, 'phone', 20),
            'email' => $formLeadValue($dataNewsletterInput, 'email', 150),
            'loaihinh' => $formLeadValue($dataNewsletterInput, 'loaihinh', 180),
            'congsuat' => $formLeadValue($dataNewsletterInput, 'congsuat', 255),
            'address' => $formLeadValue($dataNewsletterInput, 'address', 180),
        );
        $leadErrors = array();
        if($dataNewsletter['fullname'] === '') $leadErrors[] = 'Vui lòng nhập họ và tên';
        if($dataNewsletter['phone'] === '' || !$func->isPhone($dataNewsletter['phone'])) $leadErrors[] = 'Số điện thoại không hợp lệ';
        if($dataNewsletter['email'] === '' || !$func->isEmail($dataNewsletter['email'])) $leadErrors[] = 'Email không hợp lệ';
        if($dataNewsletter['loaihinh'] === '') $leadErrors[] = 'Vui lòng chọn đối tượng tư vấn';
        if($dataNewsletter['congsuat'] === '') $leadErrors[] = 'Vui lòng nhập nhu cầu sản phẩm';
        if($dataNewsletter['address'] === '') $leadErrors[] = 'Vui lòng chọn tỉnh/thành phố';
        if($leadErrors) $func->transfer(implode('. ', $leadErrors).'.', $func->getPageURL(), false);

        /* Save data */
        if(($scoreCaptcha >= 0.5 && $actionCaptcha == 'Newsletter') || $testCaptcha == true)
        {
            $data = array();
            $data['fullname'] = htmlspecialchars($dataNewsletter['fullname']);
            $data['phone'] = htmlspecialchars($dataNewsletter['phone']);
            $data['email'] = htmlspecialchars($dataNewsletter['email']);
            $data['loaihinh'] = htmlspecialchars($dataNewsletter['loaihinh']);
            $data['congsuat'] = htmlspecialchars($dataNewsletter['congsuat']);
            $data['address'] = htmlspecialchars($dataNewsletter['address']);
            $data['date_created'] = time();
            $data['type'] = 'dangkynhantin';

            if($d->insert('newsletter',$data))
            {
                /* Dữ liệu đã được lưu trước; SMTP lỗi không được làm mất yêu cầu. */
                $emailer->sendAdminNotification(
                    'Yêu cầu tư vấn mới từ '.$setting['name'.$lang],
                    array(
                        'Họ tên' => $dataNewsletter['fullname'],
                        'Điện thoại' => $dataNewsletter['phone'],
                        'Email' => $dataNewsletter['email'],
                        'Đối tượng tư vấn' => $dataNewsletter['loaihinh'],
                        'Nhu cầu' => $dataNewsletter['congsuat'],
                        'Tỉnh/Thành phố' => $dataNewsletter['address']
                    ),
                    $dataNewsletter['email'],
                    $dataNewsletter['fullname'],
                    'consult_admin'
                );
                $func->transfer("Đăng ký tư vấn thành công. Chúng tôi sẽ liên hệ với bạn sớm.", $func->getPageURL());
            }
            else
            {
                $func->transfer("Đăng ký tư vấn thất bại. Vui lòng thử lại sau.", $func->getPageURL(), false);
            }
        }
        else
        {
            $func->transfer("Đăng ký tư vấn thất bại. Vui lòng thử lại sau.", $func->getPageURL(), false);
        }
    }

    if(!empty($_POST['submit-newsletter2']))
    {
        if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : '')) $func->transfer("Phiên đăng ký không hợp lệ. Vui lòng tải lại trang.", $func->getPageURL(), false);
        $publicIp = formClientIp($_SERVER, isset($config['login']['trusted-proxies']) ? $config['login']['trusted-proxies'] : array());
        if(!formPersistentRateLimit($d, 'newsletter-dealer', $publicIp, 5, 600)) $func->transfer("Bạn đăng ký quá nhanh. Vui lòng thử lại sau.", $func->getPageURL(), false);
        $responseCaptcha = isset($_POST['recaptcha_response_newsletter2']) ? $_POST['recaptcha_response_newsletter2'] : '';
        $resultCaptcha = $func->checkRecaptcha($responseCaptcha);
        $scoreCaptcha = (!empty($resultCaptcha['score'])) ? $resultCaptcha['score'] : 0;
        $actionCaptcha = (!empty($resultCaptcha['action'])) ? $resultCaptcha['action'] : '';
        $testCaptcha = (!empty($resultCaptcha['test'])) ? $resultCaptcha['test'] : false;
        $dataNewsletterInput = (!empty($_POST['dataNewsletter']) && is_array($_POST['dataNewsletter'])) ? $_POST['dataNewsletter'] : array();
        $dataNewsletter = array(
            'fullname' => $formLeadValue($dataNewsletterInput, 'fullname', 100),
            'phone' => $formLeadValue($dataNewsletterInput, 'phone', 20),
            'email' => $formLeadValue($dataNewsletterInput, 'email', 150),
            'address' => $formLeadValue($dataNewsletterInput, 'address', 180),
            'company' => $formLeadValue($dataNewsletterInput, 'company', 180),
        );
        $leadErrors = array();
        if($dataNewsletter['fullname'] === '') $leadErrors[] = 'Vui lòng nhập họ và tên';
        if($dataNewsletter['phone'] === '' || !$func->isPhone($dataNewsletter['phone'])) $leadErrors[] = 'Số điện thoại không hợp lệ';
        if($dataNewsletter['email'] === '' || !$func->isEmail($dataNewsletter['email'])) $leadErrors[] = 'Email không hợp lệ';
        if($dataNewsletter['address'] === '' || $dataNewsletter['address'] === 'Tỉnh thành') $leadErrors[] = 'Vui lòng chọn tỉnh/thành phố';
        if($dataNewsletter['company'] === '') $leadErrors[] = 'Vui lòng nhập tên doanh nghiệp/cửa hàng';
        if($leadErrors) $func->transfer(implode('. ', $leadErrors).'.', $func->getPageURL(), false);

        /* Save data */
        if(($scoreCaptcha >= 0.5 && $actionCaptcha == 'Newsletter2') || $testCaptcha == true)
        {
            $data = array();
            $data['fullname'] = htmlspecialchars($dataNewsletter['fullname']);
            $data['phone'] = htmlspecialchars($dataNewsletter['phone']);
            $data['email'] = htmlspecialchars($dataNewsletter['email']);
            $data['address'] = htmlspecialchars($dataNewsletter['address']);
            $data['company'] = htmlspecialchars($dataNewsletter['company']);
            $data['date_created'] = time();
            $data['type'] = 'dangkynhantin2';

            if($d->insert('newsletter',$data))
            {
                /* Dữ liệu đã được lưu trước; SMTP lỗi không được làm mất yêu cầu. */
                $emailer->sendAdminNotification(
                    'Đăng ký đại lý mới từ '.$setting['name'.$lang],
                    array(
                        'Họ tên' => $dataNewsletter['fullname'],
                        'Điện thoại' => $dataNewsletter['phone'],
                        'Email' => $dataNewsletter['email'],
                        'Tỉnh/Thành phố' => $dataNewsletter['address'],
                        'Doanh nghiệp/Cửa hàng' => $dataNewsletter['company']
                    ),
                    $dataNewsletter['email'],
                    $dataNewsletter['fullname'],
                    'dealer_admin'
                );
                $func->transfer("Đăng ký đại lý thành công. Chúng tôi sẽ liên hệ với bạn sớm.", $func->getPageURL());
            }
            else
            {
                $func->transfer("Đăng ký đại lý thất bại. Vui lòng thử lại sau.", $func->getPageURL(), false);
            }
        }
        else
        {
            $func->transfer("Đăng ký đại lý thất bại. Vui lòng thử lại sau.", $func->getPageURL(), false);
        }
    }

    /* Liên hệ (form dùng chung: trang lien-he và popup toàn site) */
    if(!empty($_POST['submit-contact']))
    {
        if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : '')) $func->transfer("Phiên gửi liên hệ không hợp lệ. Vui lòng tải lại trang.", $func->getPageURL(), false);
        $publicIp = formClientIp($_SERVER, isset($config['login']['trusted-proxies']) ? $config['login']['trusted-proxies'] : array());
        if(!formPersistentRateLimit($d, 'contact', $publicIp, 5, 600)) $func->transfer("Bạn gửi liên hệ quá nhanh. Vui lòng thử lại sau.", $func->getPageURL(), false);
        $responseCaptcha = isset($_POST['recaptcha_response_contact']) ? $_POST['recaptcha_response_contact'] : '';
        $resultCaptcha = $func->checkRecaptcha($responseCaptcha);
        $scoreCaptcha = (!empty($resultCaptcha['score'])) ? $resultCaptcha['score'] : 0;
        $actionCaptcha = (!empty($resultCaptcha['action'])) ? $resultCaptcha['action'] : '';
        $testCaptcha = (!empty($resultCaptcha['test'])) ? $resultCaptcha['test'] : false;
        $dataContactInput = (!empty($_POST['dataContact']) && is_array($_POST['dataContact'])) ? $_POST['dataContact'] : array();
        $dataContact = array(
            'fullname' => $formLeadValue($dataContactInput, 'fullname', 100),
            'phone' => $formLeadValue($dataContactInput, 'phone', 20),
            'email' => $formLeadValue($dataContactInput, 'email', 150),
            'address' => $formLeadValue($dataContactInput, 'address', 255),
            'subject' => $formLeadValue($dataContactInput, 'subject', 180),
            'content' => $formLeadValue($dataContactInput, 'content', 5000),
        );

        /* Valid data */
        $contactResponse = array();
        if(empty($dataContact['fullname'])) $contactResponse['messages'][] = 'Họ tên không được trống';
        if(empty($dataContact['phone'])) $contactResponse['messages'][] = 'Số điện thoại không được trống';
        if(!empty($dataContact['phone']) && !$func->isPhone($dataContact['phone'])) $contactResponse['messages'][] = 'Số điện thoại không hợp lệ';
        /* Email và Tiêu đề không bắt buộc nữa - khách chỉ cần để lại tên/SĐT/nội dung
           là đủ để PTS gọi lại, giảm friction so với form hành chính (audit UX 2026-09-08). */
        if(!empty($dataContact['email']) && !$func->isEmail($dataContact['email'])) $contactResponse['messages'][] = 'Email không hợp lệ';
        if(empty($dataContact['content'])) $contactResponse['messages'][] = 'Nội dung không được trống';
        if(empty($dataContact['subject'])) $dataContact['subject'] = 'Yêu cầu tư vấn từ website';

        if(!empty($contactResponse))
        {
            /* Flash data */
            foreach($dataContact as $k => $v) if(!empty($v)) $flash->set($k, $v);

            /* Hiển thị lỗi bằng cùng toast với kết quả thành công. Template cũ
               đọc flash message bên trong một HTML comment nên người gửi không
               nhìn thấy bất kỳ phản hồi nào. */
            $func->transfer(implode('. ', $contactResponse['messages']).'.', $func->getPageURL(), false);
        }

        /* Save data */
        if(($scoreCaptcha >= 0.5 && $actionCaptcha == 'contact') || $testCaptcha == true)
        {
            $data = array();
            $data['fullname'] = htmlspecialchars($dataContact['fullname']);
            $data['email'] = htmlspecialchars($dataContact['email']);
            $data['phone'] = htmlspecialchars($dataContact['phone']);
            $data['address'] = isset($dataContact['address']) ? htmlspecialchars($dataContact['address']) : '';
            $data['subject'] = htmlspecialchars($dataContact['subject']);
            $data['content'] = htmlspecialchars($dataContact['content']);
            $data['date_created'] = time();
            $data['numb'] = 1;

            if($d->insert('contact',$data))
            {
                $id_insert = $d->getLastInsertId();

                if($func->hasFile("file_attach"))
                {
                    $fileUpdate = array();
                    $file_name = $func->uploadName($_FILES['file_attach']["name"]);

                    if($file_attach = $func->uploadFile("file_attach", '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.jpeg|.png|.gif', UPLOAD_FILE_L, $file_name))
                    {
                        $fileUpdate['file_attach'] = $file_attach;
                        $d->where('id', $id_insert);
                        $d->update('contact', $fileUpdate);
                        unset($fileUpdate);
                    }
                }
            }
            else
            {
                $func->transfer("Gửi liên hệ thất bại. Vui lòng thử lại sau.", $func->getPageURL(), false);
            }

            /* Gán giá trị gửi email */
            $strThongtin = '';
            $emailer->set('tennguoigui',$data['fullname']);
            $emailer->set('emailnguoigui',$data['email']);
            $emailer->set('dienthoainguoigui',$data['phone']);
            $emailer->set('diachinguoigui',$data['address']);
            $emailer->set('tieudelienhe',$data['subject']);
            $emailer->set('noidunglienhe',$data['content']);
            if($emailer->get('tennguoigui')) $strThongtin .= '<span style="text-transform:capitalize">'.$emailer->get('tennguoigui').'</span><br>';
            if($emailer->get('emailnguoigui')) $strThongtin .= '<a href="mailto:'.$emailer->get('emailnguoigui').'" target="_blank">'.$emailer->get('emailnguoigui').'</a><br>';
            if($emailer->get('diachinguoigui')) $strThongtin .= ''.$emailer->get('diachinguoigui').'<br>';
            if($emailer->get('dienthoainguoigui')) $strThongtin .= 'Tel: '.$emailer->get('dienthoainguoigui').'';
            $emailer->set('thongtin',$strThongtin);

            /* Defaults attributes email */
            $emailDefaultAttrs = $emailer->defaultAttrs();

            /* Variables email */
            $emailVars = array('{emailTitleSender}', '{emailInfoSender}', '{emailSubjectSender}', '{emailContentSender}');
            $emailVars = $emailer->addAttrs($emailVars, $emailDefaultAttrs['vars']);

            /* Values email */
            $emailVals = array($emailer->get('tennguoigui'), $emailer->get('thongtin'), $emailer->get('tieudelienhe'), $emailer->get('noidunglienhe'));
            $emailVals = $emailer->addAttrs($emailVals, $emailDefaultAttrs['vals']);

            /* Send email admin */
            $arrayEmail = null;
            $mailValues = array(
				'contact_subject' => $emailer->get('tieudelienhe'),
				'customer_name' => $emailer->get('tennguoigui'),
				'customer_phone' => $emailer->get('dienthoainguoigui')
			);
            $subject = $emailer->templateSubject('contact_admin', $mailValues, "Thư liên hệ từ ".$setting['name'.$lang]);
            $message = str_replace($emailVars, $emailVals, $emailer->markdown('contact/admin'));
            $file = 'file_attach';

            if($emailer->send("admin", $arrayEmail, $subject, $message, $file, array(
                'email' => $emailer->get('emailnguoigui'),
                'name' => $emailer->get('tennguoigui')
            )))
            {
                /* Send email customer */
                $arrayEmail = array("dataEmail" => array("name" => $emailer->get('tennguoigui'), "email" => $emailer->get('emailnguoigui')));
                $subject = $emailer->templateSubject('contact_customer', $mailValues, "Phúc Thịnh Solar đã nhận được yêu cầu của Quý khách");
                $message = str_replace($emailVars, $emailVals, $emailer->markdown('contact/customer'));
                $file = 'file_attach';

                $emailer->send("customer", $arrayEmail, $subject, $message, $file);
            }

            /* Bản ghi đã lưu thành công là kết quả chính. Không để lỗi gửi mail
               phụ làm request rơi xuống cuối trang mà không có thông báo. */
            $func->transfer("Gửi liên hệ thành công. Chúng tôi sẽ liên hệ với bạn sớm.", $func->getPageURL());
        }
        else
        {
            $func->transfer("Gửi liên hệ thất bại. Vui lòng thử lại sau", $func->getPageURL(), false);
        }
    }
?>
