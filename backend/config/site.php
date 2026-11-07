<?php

// Site-wide settings that come from the environment, never from the database.
return [
    'admin_password' => [
        // Accounts carried over from the old admin keep md5(secret . password . salt) hashes until
        // their first sign-in, which replaces them with bcrypt.
        'secret' => env('ADMIN_PASSWORD_SECRET', ''),
        'salt' => env('ADMIN_PASSWORD_SALT', ''),
    ],
    // Uploaded media (product, news, banners…) and the stock pictures and documents.
    'upload_path' => env('UPLOAD_PATH', '/srv/upload'),
    'assets_path' => env('ASSETS_PATH', '/srv/assets'),

    // Google reCAPTCHA v3 for public forms. Off locally.
    'recaptcha' => [
        'active' => (bool) env('RECAPTCHA_ACTIVE', false),
        'site_key' => env('RECAPTCHA_SITE_KEY', ''),
        'secret' => env('RECAPTCHA_SECRET', ''),
        'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
    ],

    // Checkout asks for a delivery address when true.
    'order_shipping' => (bool) env('ORDER_SHIPPING', true),
];
