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
    $splist = $cache->get("select name$lang, slugvi, slugen, id, photo from #_product_list where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'), 'result', 7200);
    $gplist = $cache->get("select name$lang, slugvi, slugen, id, photo from #_news_list where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap'), 'result', 7200);
    $ttlist = $cache->get("select name$lang, slugvi, slugen, id from #_news_list where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('tin-tuc'), 'result', 7200);
    $dalist = $cache->get("select name$lang, slugvi, slugen, id, photo from #_news_list where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('du-an'), 'result', 7200);
    $groupMenuRows = function($rows, $parentKey) {
        $grouped = array();
        foreach($rows as $row) $grouped[(int)$row[$parentKey]][] = $row;
        return $grouped;
    };
    $productMenuCats = $groupMenuRows($cache->get("select name$lang, slugvi, slugen, id, id_list from #_product_cat where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'), 'result', 7200), 'id_list');
    $productMenuItems = $groupMenuRows($cache->get("select name$lang, slugvi, slugen, id, id_cat from #_product_item where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'), 'result', 7200), 'id_cat');
    $productMenuSubs = $groupMenuRows($cache->get("select name$lang, slugvi, slugen, id, id_item from #_product_sub where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'), 'result', 7200), 'id_item');
    $newsMenuCats = $groupMenuRows($cache->get("select name$lang, slugvi, slugen, id, id_list from #_news_cat where type in (?, ?) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap', 'tin-tuc'), 'result', 7200), 'id_list');
    $newsMenuItems = $groupMenuRows($cache->get("select name$lang, slugvi, slugen, id, id_cat from #_news_item where type in (?, ?) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap', 'tin-tuc'), 'result', 7200), 'id_cat');
    $newsMenuSubs = $groupMenuRows($cache->get("select name$lang, slugvi, slugen, id, id_item from #_news_sub where type in (?, ?) and find_in_set('hienthi',status) order by numb,id desc", array('giai-phap', 'tin-tuc'), 'result', 7200), 'id_item');
    // $bct = $cache->get("select name$lang, content$lang from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('bct'), 'fetch', 7200);
    // $tagsProduct = $cache->get("select name$lang, slugvi, slugen, id from #_tags where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('san-pham'), 'result', 7200);
    // $tagsNews = $cache->get("select name$lang, slugvi, slugen, id from #_tags where type = ? and find_in_set('noibat',status) and find_in_set('hienthi',status) order by numb,id desc", array('tin-tuc'), 'result', 7200);
    $policy = $cache->get("select name$lang, slugvi, slugen, id, photo from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('chinh-sach'), 'result', 7200);
    $cn = $cache->get("select name$lang, address, id, photo, ggmap from #_news where type = ? and find_in_set('hienthi',status) order by numb,id desc", array('he-thong-dai-ly'), 'result', 7200);
    $footer = $cache->get("select name$lang, content$lang,photo from #_static where type = ? and find_in_set('hienthi',status) limit 0,1", array('footer'), 'fetch', 7200);
    $city = $cache->get("select name, id from #_city order by id asc", null, 'result', 7200);
    /* Get statistic */
    $counter = $statistic->getCounter();
    $online = $statistic->getOnline();

    /* Newsletter */
    if(!empty($_POST['submit-newsletter']))
    {
        if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : '')) $func->transfer("Phiên đăng ký không hợp lệ. Vui lòng tải lại trang.", $configBase, false);
        $publicIp = formClientIp($_SERVER, isset($config['login']['trusted-proxies']) ? $config['login']['trusted-proxies'] : array());
        if(!formPersistentRateLimit($d, 'newsletter-consulting', $publicIp, 5, 600)) $func->transfer("Bạn đăng ký quá nhanh. Vui lòng thử lại sau.", $configBase, false);
        $responseCaptcha = $_POST['recaptcha_response_newsletter'];
        $resultCaptcha = $func->checkRecaptcha($responseCaptcha);
        $scoreCaptcha = (!empty($resultCaptcha['score'])) ? $resultCaptcha['score'] : 0;
        $actionCaptcha = (!empty($resultCaptcha['action'])) ? $resultCaptcha['action'] : '';
        $testCaptcha = (!empty($resultCaptcha['test'])) ? $resultCaptcha['test'] : false;
        $dataNewsletter = (!empty($_POST['dataNewsletter'])) ? $_POST['dataNewsletter'] : null;

        $error = $flash->get('error');

        if(!empty($error))
        {
            $func->transfer($error, $configBase, false);
        }

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
                $func->transfer("Đăng ký tư vấn thành công. Chúng tôi sẽ liên hệ với bạn sớm.", $configBase);
            }
            else
            {
                $func->transfer("Đăng ký tư vấn thất bại. Vui lòng thử lại sau.", $configBase, false);
            }
        }
        else
        {
            $func->transfer("Đăng ký tư vấn thất bại. Vui lòng thử lại sau.", $configBase, false);
        }
    }

    if(!empty($_POST['submit-newsletter2']))
    {
        if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : '')) $func->transfer("Phiên đăng ký không hợp lệ. Vui lòng tải lại trang.", $configBase, false);
        $publicIp = formClientIp($_SERVER, isset($config['login']['trusted-proxies']) ? $config['login']['trusted-proxies'] : array());
        if(!formPersistentRateLimit($d, 'newsletter-dealer', $publicIp, 5, 600)) $func->transfer("Bạn đăng ký quá nhanh. Vui lòng thử lại sau.", $configBase, false);
        $responseCaptcha = $_POST['recaptcha_response_newsletter2'];
        $resultCaptcha = $func->checkRecaptcha($responseCaptcha);
        $scoreCaptcha = (!empty($resultCaptcha['score'])) ? $resultCaptcha['score'] : 0;
        $actionCaptcha = (!empty($resultCaptcha['action'])) ? $resultCaptcha['action'] : '';
        $testCaptcha = (!empty($resultCaptcha['test'])) ? $resultCaptcha['test'] : false;
        $dataNewsletter = (!empty($_POST['dataNewsletter'])) ? $_POST['dataNewsletter'] : null;

        $error = $flash->get('error');

        if(!empty($error))
        {
            $func->transfer($error, $configBase, false);
        }

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
                $func->transfer("Đăng ký đại lý thành công. Chúng tôi sẽ liên hệ với bạn sớm.", $configBase);
            }
            else
            {
                $func->transfer("Đăng ký đại lý thất bại. Vui lòng thử lại sau.", $configBase, false);
            }
        }
        else
        {
            $func->transfer("Đăng ký đại lý thất bại. Vui lòng thử lại sau.", $configBase, false);
        }
    }
?>
