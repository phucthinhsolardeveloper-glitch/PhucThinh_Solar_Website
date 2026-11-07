<?php

// Settings shared with the PHP-era application. Values come from its libraries/config.php and stay out of Git.
return [
    'admin_password' => [
        // Old admin hashes are md5(secret . password . salt); newer ones are password_hash().
        'secret' => env('LEGACY_ADMIN_SECRET', ''),
        'salt' => env('LEGACY_ADMIN_SALT', ''),
    ],
    // Folder holding upload/product etc. Images are checked on disk, as productImageResolve() does.
    'upload_path' => env('LEGACY_UPLOAD_PATH', '/legacy/upload'),

    // The PHP app inside the local Docker network, used only by the parity report.
    'api_base' => env('LEGACY_API_BASE', 'http://pts-php81-local'),

    // Role 3 bypasses the permission table, exactly as adminPermissionGranted() in the PHP admin.
    'super_admin_role' => 3,
];
