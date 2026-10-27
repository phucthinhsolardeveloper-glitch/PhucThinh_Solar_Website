<?php
/** CSRF-protected text review mutation for the Nuxt PDP. */
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

function ptsReviewJson($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')
{
    header('Allow: POST');
    ptsReviewJson(405,array('errors'=>array('Phương thức request không hợp lệ.')));
}
$body=json_decode(file_get_contents('php://input'),true);
if(!is_array($body)) ptsReviewJson(400,array('errors'=>array('Dữ liệu không hợp lệ.')));
$csrf=isset($body['csrfToken']) && is_scalar($body['csrfToken']) ? (string)$body['csrfToken'] : '';
if(!formCsrfValid($csrf)) ptsReviewJson(419,array('errors'=>array('Phiên gửi nhận xét không hợp lệ.')));
$publicIp=formClientIp($_SERVER,isset($config['login']['trusted-proxies'])?$config['login']['trusted-proxies']:array());
if(!formPersistentRateLimit($d,'review',$publicIp,5,600)) ptsReviewJson(429,array('errors'=>array('Bạn thao tác quá nhanh. Vui lòng thử lại sau.')));

$productId=isset($body['productId']) && is_scalar($body['productId']) ? (int)$body['productId'] : 0;
$product=$productId>0 ? $d->rawQueryOne("select id from #_product where id=? and type=? and find_in_set('hienthi',status) limit 1",array($productId,'san-pham')) : array();
if(empty($product['id'])) ptsReviewJson(404,array('errors'=>array('Sản phẩm không tồn tại.')));

$text=function($key,$max) use ($body) {
    if(!isset($body[$key]) || !is_scalar($body[$key])) return '';
    $value=trim((string)$body[$key]);
    return function_exists('mb_substr') ? mb_substr($value,0,$max,'UTF-8') : substr($value,0,$max);
};
$star=isset($body['star']) && is_scalar($body['star']) ? (int)$body['star'] : 0;
if($star<1 || $star>5) ptsReviewJson(422,array('errors'=>array('Chưa chọn đánh giá sao.')));

$_POST=array(
    'dataReview'=>array(
        'id_variant'=>$productId,
        'type'=>'san-pham',
        'star'=>$star,
        'title'=>$text('title',180),
        'content'=>$text('content',5000),
        'fullname'=>$text('fullname',100),
        'phone'=>$text('phone',20),
        'email'=>$text('email',150),
        'order_code'=>$text('orderCode',40)
    )
);
$comment=new Comments($d,$func);
$result=$comment->add();
$decoded=json_decode($result,true);
if(!is_array($decoded)) ptsReviewJson(500,array('errors'=>array('Phản hồi nhận xét không hợp lệ.')));
if(!empty($decoded['errors'])) ptsReviewJson(422,$decoded);
ptsReviewJson(200,array('ok'=>true,'message'=>'Đã nhận đánh giá. Nội dung sẽ hiển thị sau khi được duyệt.'));
