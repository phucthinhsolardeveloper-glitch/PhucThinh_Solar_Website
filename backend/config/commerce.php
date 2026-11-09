<?php

// Online payment for checkout. Off unless set. Shipping by Nhất Tín lives in config/shipping.php.
return [
    'vnpay' => [
        'active' => env('VNPAY_ACTIVE') === '1',
        'tmn_code' => env('VNPAY_TMN_CODE', ''),
        'hash_secret' => env('VNPAY_HASH_SECRET', ''),
        'endpoint' => env('VNPAY_ENDPOINT', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'return_url' => env('VNPAY_RETURN_URL', ''),
        'ipn_url' => env('VNPAY_IPN_URL', ''),
    ],
];
