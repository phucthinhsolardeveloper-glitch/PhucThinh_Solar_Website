<?php

// Settings shared with the PHP-era application. Values come from its libraries/config.php and stay out of Git.
return [
    'admin_password' => [
        // Old admin hashes are md5(secret . password . salt); newer ones are password_hash().
        'secret' => env('LEGACY_ADMIN_SECRET', ''),
        'salt' => env('LEGACY_ADMIN_SALT', ''),
    ],
    // Role 3 bypasses the permission table, exactly as adminPermissionGranted() in the PHP admin.
    'super_admin_role' => 3,
];
