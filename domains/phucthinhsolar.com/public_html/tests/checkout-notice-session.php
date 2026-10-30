<?php
require_once __DIR__.'/../libraries/pts_public_checkout_notice.php';

function checkoutNoticeExpect($condition, $message)
{
    if(!$condition) throw new RuntimeException($message);
}

$session = array('flash' => array(
    'message' => base64_encode(json_encode(array('status'=>'danger', 'messages'=>array('Email không hợp lệ')), JSON_UNESCAPED_UNICODE)),
    'toast' => json_encode(array('status'=>'error', 'text'=>'Phiên đặt hàng không hợp lệ.'), JSON_UNESCAPED_UNICODE),
    'fullname' => 'Nguyễn Văn A',
    'email' => 'not-an-email',
    'city' => '3',
    'ward' => '18',
    'payments' => '-1',
    'unrelated' => 'keep'
));

$first = ptsPublicCheckoutNoticePayload($session);
checkoutNoticeExpect($first['messages'] === array('Email không hợp lệ'), 'first read should return validation messages');
checkoutNoticeExpect($first['toast'] === array('status'=>'error', 'text'=>'Phiên đặt hàng không hợp lệ.'), 'first read should return the transfer notice');
checkoutNoticeExpect($first['form']['fullname'] === 'Nguyễn Văn A' && $first['form']['payments'] === '-1', 'first read should return the allowlisted checkout draft');
checkoutNoticeExpect(isset($session['flash']['unrelated']) && !isset($session['flash']['message']) && !isset($session['flash']['toast']), 'consumption should remove only checkout flash keys');

$second = ptsPublicCheckoutNoticePayload($session);
checkoutNoticeExpect($second['messages'] === array() && $second['toast'] === null && $second['form'] === null, 'second read must not repeat a consumed notice');

echo "checkout notice session test PASS\n";
