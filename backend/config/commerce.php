<?php

// Online payment and carriers for checkout (integrations in the PHP libraries/config.php). All off unless set.
return [
    'vnpay' => [
        'active' => env('VNPAY_ACTIVE') === '1',
        'tmn_code' => env('VNPAY_TMN_CODE', ''),
        'hash_secret' => env('VNPAY_HASH_SECRET', ''),
        'endpoint' => env('VNPAY_ENDPOINT', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'return_url' => env('VNPAY_RETURN_URL', ''),
        'ipn_url' => env('VNPAY_IPN_URL', ''),
    ],
    // Carriers are listed in quotes with their readiness; prices still come from the ward table until each adapter is mapped.
    'carriers' => [
        'ghn' => ['label' => 'GHN', 'active' => env('SHIPPING_GHN_ACTIVE') === '1', 'required' => ['token' => env('SHIPPING_GHN_TOKEN', ''), 'shop_id' => env('SHIPPING_GHN_SHOP_ID', ''), 'endpoint' => env('SHIPPING_GHN_ENDPOINT', 'https://online-gateway.ghn.vn/shiip/public-api')]],
        'ghtk' => ['label' => 'GHTK', 'active' => env('SHIPPING_GHTK_ACTIVE') === '1', 'required' => ['token' => env('SHIPPING_GHTK_TOKEN', ''), 'endpoint' => env('SHIPPING_GHTK_ENDPOINT', 'https://services.giaohangtietkiem.vn')]],
        'viettelpost' => ['label' => 'Viettel Post', 'active' => env('SHIPPING_VTP_ACTIVE') === '1', 'required' => ['username' => env('SHIPPING_VTP_USERNAME', ''), 'password' => env('SHIPPING_VTP_PASSWORD', ''), 'partner_id' => env('SHIPPING_VTP_PARTNER_ID', ''), 'endpoint' => env('SHIPPING_VTP_ENDPOINT', 'https://partner.viettelpost.vn')]],
        'nhattin' => ['label' => 'Nhất Tín', 'active' => env('SHIPPING_NHATTIN_ACTIVE') === '1', 'required' => ['username' => env('SHIPPING_NHATTIN_USERNAME', ''), 'password' => env('SHIPPING_NHATTIN_PASSWORD', ''), 'partner_id' => env('SHIPPING_NHATTIN_PARTNER_ID', ''), 'endpoint' => env('SHIPPING_NHATTIN_ENDPOINT', 'https://api.ntlogistics.vn')]],
    ],
];
