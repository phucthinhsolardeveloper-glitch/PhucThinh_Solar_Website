<?php

// Nhất Tín Logistics. mock answers locally and calls nobody; sandbox calls only the sandbox host;
// production stays locked unless both NTL_MODE=production and NTL_PRODUCTION_UNLOCKED=1 are set on the server.
return [
    'nhattin' => [
        'mode' => env('NTL_MODE', 'mock'),
        // NTL_SANDBOX_URL only exists for the local rehearsal simulator (tools/sandbox-sim); production is fixed.
        'hosts' => ['sandbox' => env('NTL_SANDBOX_URL', 'https://apisandbox.ntlogistics.vn'), 'production' => 'https://apiws.ntlogistics.vn'],
        'production_unlocked' => env('NTL_PRODUCTION_UNLOCKED') === '1',
        // Show the Nhất Tín fee at checkout (otherwise the area fee table, or staff confirm).
        'checkout_quotes' => env('NTL_CHECKOUT_QUOTES', '1') === '1',
        'write_enabled' => env('NTL_WRITE_OPERATIONS_ENABLED') === '1',
        'username' => env('NTL_USERNAME', ''),
        'password' => env('NTL_PASSWORD', ''),
        'partner_id' => env('NTL_PARTNER_ID', ''),
        'webhook_token' => env('NTL_WEBHOOK_TOKEN', ''),
        'timeout' => (int) env('NTL_HTTP_TIMEOUT_SECONDS', 10),
        // Our province/ward codes come from the 2025 list; set to 1 only after checking them against Nhất Tín's own list.
        'locations_verified' => env('NTL_LOCATION_CODES_VERIFIED') === '1',
        // Services and who pays the freight, from Nhất Tín's master data; a code the API returns that is not here is kept raw.
        'services' => ['91' => 'Tiết kiệm', '90' => 'Chuyển phát nhanh', '81' => 'Hỏa tốc', '21' => 'MES'],
        'payers' => ['10' => 'Công ty trả ngay', '11' => 'Công ty trả sau', '20' => 'Người nhận trả'],
    ],
];
