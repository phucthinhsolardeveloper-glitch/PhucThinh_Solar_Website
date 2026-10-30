<?php
/** GET form security metadata + POST contact/dealer lead mutation for Nuxt. */
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

function ptsLeadJson($status, $payload)
{
    if((int)$status === 419)
        header('HTTP/1.1 419 Authentication Timeout', true);
    else
        http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function ptsLeadValue($input, $key, $max)
{
    if(!is_array($input) || !isset($input[$key]) || !is_scalar($input[$key])) return '';
    $value = trim((string)$input[$key]);
    return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if($method === 'GET')
{
    if(!empty($_GET)) ptsLeadJson(400, array('error'=>array('code'=>'BAD_QUERY')));
    $recaptcha = isset($config['googleAPI']['recaptcha']) && is_array($config['googleAPI']['recaptcha']) ? $config['googleAPI']['recaptcha'] : array();
    $cities = array();
    foreach((array)$d->rawQuery("select id,name from #_city where find_in_set('hienthi',status) order by numb asc,id asc") as $row)
    {
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        $name = isset($row['name']) && is_scalar($row['name']) ? trim(strip_tags((string)$row['name'])) : '';
        if($id > 0 && $name !== '') $cities[] = array('id'=>$id,'name'=>$name);
    }
    ptsLeadJson(200, array(
        'schemaVersion'=>1,
        'csrfToken'=>formCsrfToken(),
        'cities'=>$cities,
        'recaptcha'=>array(
            'active'=>!empty($recaptcha['active']),
            'siteKey'=>!empty($recaptcha['active']) && isset($recaptcha['sitekey']) ? (string)$recaptcha['sitekey'] : ''
        )
    ));
}
if($method !== 'POST')
{
    header('Allow: GET, POST');
    ptsLeadJson(405, array('error'=>array('code'=>'METHOD_NOT_ALLOWED')));
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if(!is_array($body)) ptsLeadJson(400, array('error'=>array('code'=>'BAD_BODY')));
$kind = isset($body['kind']) && is_scalar($body['kind']) ? trim((string)$body['kind']) : '';
if(!in_array($kind, array('contact','dealer','product'), true)) ptsLeadJson(400, array('error'=>array('code'=>'BAD_KIND')));
if(!formCsrfValid(isset($body['csrfToken']) && is_scalar($body['csrfToken']) ? (string)$body['csrfToken'] : '')) ptsLeadJson(419, array('error'=>array('code'=>'INVALID_SESSION','message'=>'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.')));

$publicIp = formClientIp($_SERVER, isset($config['login']['trusted-proxies']) ? $config['login']['trusted-proxies'] : array());
$rateKey = $kind === 'contact' ? 'contact' : ($kind === 'product' ? 'newsletter-consulting' : 'newsletter-dealer');
if(!formPersistentRateLimit($d, $rateKey, $publicIp, 5, 600)) ptsLeadJson(429, array('error'=>array('code'=>'RATE_LIMITED','message'=>'Bạn thao tác quá nhanh. Vui lòng thử lại sau.')));

$captchaToken = isset($body['recaptchaToken']) && is_scalar($body['recaptchaToken']) ? (string)$body['recaptchaToken'] : '';
$captcha = $func->checkRecaptcha($captchaToken);
$expectedAction = $kind === 'contact' ? 'contact' : ($kind === 'product' ? 'Newsletter' : 'Newsletter2');
$captchaOk = (!empty($captcha['test'])) || (!empty($captcha['score']) && (float)$captcha['score'] >= 0.5 && ($captcha['action'] ?? '') === $expectedAction);
if(!$captchaOk) ptsLeadJson(422, array('error'=>array('code'=>'CAPTCHA_FAILED','message'=>'Xác thực chống spam thất bại. Vui lòng thử lại.')));

$dataInput = isset($body['data']) && is_array($body['data']) ? $body['data'] : array();
if($kind === 'contact')
{
    $data = array(
        'fullname'=>ptsLeadValue($dataInput,'fullname',100),
        'phone'=>ptsLeadValue($dataInput,'phone',20),
        'email'=>ptsLeadValue($dataInput,'email',150),
        'address'=>ptsLeadValue($dataInput,'address',255),
        'subject'=>ptsLeadValue($dataInput,'subject',180),
        'content'=>ptsLeadValue($dataInput,'content',5000),
    );
    $errors = array();
    if($data['fullname'] === '') $errors[] = 'Họ tên không được trống';
    if($data['phone'] === '' || !$func->isPhone($data['phone'])) $errors[] = 'Số điện thoại không hợp lệ';
    if($data['email'] !== '' && !$func->isEmail($data['email'])) $errors[] = 'Email không hợp lệ';
    if($data['content'] === '') $errors[] = 'Nội dung không được trống';
    if($data['subject'] === '') $data['subject'] = 'Yêu cầu tư vấn từ website';
    if($errors) ptsLeadJson(422, array('error'=>array('code'=>'VALIDATION_FAILED','message'=>implode('. ',$errors).'.')));

    $insert = array(
        'fullname'=>htmlspecialchars($data['fullname']),
        'email'=>htmlspecialchars($data['email']),
        'phone'=>htmlspecialchars($data['phone']),
        'address'=>htmlspecialchars($data['address']),
        'subject'=>htmlspecialchars($data['subject']),
        'content'=>htmlspecialchars($data['content']),
        'date_created'=>time(),
        'numb'=>1,
    );
    if(!$d->insert('contact',$insert)) ptsLeadJson(500, array('error'=>array('code'=>'SAVE_FAILED','message'=>'Gửi liên hệ thất bại. Vui lòng thử lại sau.')));
    try {
        $emailer->sendAdminNotification(
            'Yêu cầu liên hệ mới từ '.$setting['name'.$lang],
            array('Họ tên'=>$data['fullname'],'Điện thoại'=>$data['phone'],'Email'=>$data['email'],'Địa chỉ'=>$data['address'],'Nhu cầu'=>$data['subject'],'Nội dung'=>$data['content']),
            $data['email'], $data['fullname'], 'contact_admin'
        );
    } catch(Throwable $e) {}
    ptsLeadJson(200, array('ok'=>true,'message'=>'Gửi yêu cầu tư vấn thành công. Chúng tôi sẽ liên hệ với bạn sớm.'));
}

if($kind === 'product')
{
    require_once LIBRARIES.'product_pdp_profiles.php';
    $data = array(
        'fullname'=>ptsLeadValue($dataInput,'fullname',100),
        'phone'=>ptsLeadValue($dataInput,'phone',20),
        'email'=>ptsLeadValue($dataInput,'email',150),
        'loaihinh'=>ptsLeadValue($dataInput,'loaihinh',180),
        'congsuat'=>ptsLeadValue($dataInput,'congsuat',255),
        'product_id'=>(int)ptsLeadValue($dataInput,'product_id',12),
        'profile_key'=>ptsLeadValue($dataInput,'profile_key',32),
        'need_code'=>ptsLeadValue($dataInput,'need_code',64),
        'details'=>ptsLeadValue($dataInput,'details',1000),
        'city_id'=>(int)ptsLeadValue($dataInput,'city_id',12),
        'ward_id'=>(int)ptsLeadValue($dataInput,'ward_id',12),
    );
    $product=$data['product_id']>0 ? $d->rawQueryOne(
        "select p.id,p.namevi,p.pdp_profile,pl.slugvi list_slugvi,pl.pdp_profile list_pdp_profile from #_product p left join #_product_list pl on pl.id=p.id_list where p.id=? and find_in_set('hienthi',p.status) and p.type=? limit 1",
        array($data['product_id'],'san-pham')
    ) : array();
    $profileKey=$product ? productPdpResolveProfile(
        array('pdp_profile'=>$product['pdp_profile']),
        array('pdp_profile'=>$product['list_pdp_profile'],'slugvi'=>$product['list_slugvi'])
    ) : 'general';
    $profile=productPdpProfile($profileKey);
    $place=($data['city_id']>0 && $data['ward_id']>0) ? $d->rawQueryOne(
        "select c.name city_name,w.name ward_name from #_ward w inner join #_city c on c.id=w.id_city where w.id=? and w.id_city=? and w.id_district=0 and find_in_set('hienthi',w.status) and find_in_set('hienthi',c.status) limit 1",
        array($data['ward_id'],$data['city_id'])
    ) : array();

    $errors=array();
    if($data['fullname']==='') $errors[]='Vui lòng nhập họ và tên';
    if($data['phone']==='' || !$func->isPhone($data['phone'])) $errors[]='Số điện thoại không hợp lệ';
    if($data['email']!=='' && !$func->isEmail($data['email'])) $errors[]='Email không hợp lệ';
    if(!$product) $errors[]='Sản phẩm cần tư vấn không hợp lệ';
    if($data['profile_key']!==$profileKey) $errors[]='Nhóm tư vấn không hợp lệ';
    if($data['need_code']!=='' && !isset($profile['needs'][$data['need_code']])) $errors[]='Nhu cầu tư vấn không hợp lệ';
    if(($data['city_id']>0 || $data['ward_id']>0) && !$place) $errors[]='Phường/Xã không thuộc Tỉnh/Thành phố đã chọn';
    if($errors) ptsLeadJson(422,array('error'=>array('code'=>'VALIDATION_FAILED','message'=>implode('. ',$errors).'.')));

    $address=$place ? $place['ward_name'].', '.$place['city_name'] : '';
    $needLabel=($data['need_code']!=='' && isset($profile['needs'][$data['need_code']])) ? $profile['needs'][$data['need_code']] : '';
    $insert=array(
        'fullname'=>htmlspecialchars($data['fullname']),
        'phone'=>htmlspecialchars($data['phone']),
        'email'=>htmlspecialchars($data['email']),
        'loaihinh'=>htmlspecialchars($data['loaihinh']),
        'congsuat'=>htmlspecialchars($data['congsuat']),
        'address'=>htmlspecialchars($address),
        'product_id'=>(int)$product['id'],
        'profile_key'=>$profileKey,
        'need_code'=>$data['need_code'],
        'details'=>htmlspecialchars($data['details']),
        'city_id'=>$data['city_id'],
        'ward_id'=>$data['ward_id'],
        'date_created'=>time(),
        'type'=>'dangkynhantin'
    );
    if(!$d->insert('newsletter',$insert)) ptsLeadJson(500,array('error'=>array('code'=>'SAVE_FAILED','message'=>'Đăng ký tư vấn thất bại. Vui lòng thử lại sau.')));
    try {
        $emailer->sendAdminNotification(
            'Yêu cầu tư vấn mới từ '.$setting['name'.$lang],
            array(
                'Họ tên'=>$data['fullname'],'Điện thoại'=>$data['phone'],'Email'=>$data['email'],
                'Đối tượng tư vấn'=>$data['loaihinh'],'Sản phẩm'=>$product['namevi'],'Nhu cầu'=>$needLabel,
                'Số lượng dự kiến'=>$data['congsuat'],'Thông tin thêm'=>$data['details'],'Địa chỉ'=>$address
            ),
            $data['email'],$data['fullname'],'consult_admin'
        );
    } catch(Throwable $e) {}
    ptsLeadJson(200,array('ok'=>true,'message'=>'Đăng ký tư vấn thành công. Chúng tôi sẽ liên hệ với bạn sớm.'));
}

$data = array(
    'fullname'=>ptsLeadValue($dataInput,'fullname',100),
    'phone'=>ptsLeadValue($dataInput,'phone',20),
    'email'=>ptsLeadValue($dataInput,'email',150),
    'city_id'=>(int)ptsLeadValue($dataInput,'city_id',12),
    'address'=>ptsLeadValue($dataInput,'address',180),
    'company'=>ptsLeadValue($dataInput,'company',180),
);
$city = array();
if($data['city_id'] > 0)
{
    $city = (array)$d->rawQueryOne(
        "select id,name from #_city where id=? and find_in_set('hienthi',status) limit 1",
        array($data['city_id'])
    );
}
elseif($data['address'] !== '')
{
    // Older first-party forms may still send the label; resolve it against the
    // same active city list exposed by the GET metadata endpoint.
    $city = (array)$d->rawQueryOne(
        "select id,name from #_city where name=? and find_in_set('hienthi',status) limit 1",
        array($data['address'])
    );
}
$errors = array();
if($data['fullname'] === '') $errors[] = 'Vui lòng nhập họ và tên';
if($data['phone'] === '' || !$func->isPhone($data['phone'])) $errors[] = 'Số điện thoại không hợp lệ';
if($data['email'] === '' || !$func->isEmail($data['email'])) $errors[] = 'Email không hợp lệ';
if(!$city || !isset($city['name']) || !is_scalar($city['name'])) $errors[] = 'Vui lòng chọn tỉnh/thành phố hợp lệ';
if($data['company'] === '') $errors[] = 'Vui lòng nhập tên doanh nghiệp/cửa hàng';
if($errors) ptsLeadJson(422, array('error'=>array('code'=>'VALIDATION_FAILED','message'=>implode('. ',$errors).'.')));

$cityName = trim(strip_tags((string)$city['name']));

$insert = array(
    'fullname'=>htmlspecialchars($data['fullname']),
    'phone'=>htmlspecialchars($data['phone']),
    'email'=>htmlspecialchars($data['email']),
    'address'=>htmlspecialchars($cityName),
    'company'=>htmlspecialchars($data['company']),
    'date_created'=>time(),
    'type'=>'dangkynhantin2',
);
if(!$d->insert('newsletter',$insert)) ptsLeadJson(500, array('error'=>array('code'=>'SAVE_FAILED','message'=>'Đăng ký đại lý thất bại. Vui lòng thử lại sau.')));
try {
    $emailer->sendAdminNotification(
        'Đăng ký đại lý mới từ '.$setting['name'.$lang],
        array('Họ tên'=>$data['fullname'],'Điện thoại'=>$data['phone'],'Email'=>$data['email'],'Tỉnh/Thành phố'=>$cityName,'Doanh nghiệp/Cửa hàng'=>$data['company']),
        $data['email'], $data['fullname'], 'dealer_admin'
    );
} catch(Throwable $e) {}
ptsLeadJson(200, array('ok'=>true,'message'=>'Đăng ký đại lý thành công. Chúng tôi sẽ liên hệ với bạn sớm.'));
