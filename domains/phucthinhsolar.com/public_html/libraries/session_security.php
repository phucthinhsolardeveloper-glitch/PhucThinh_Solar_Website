<?php
if(!function_exists('startSecureSession'))
{
    function requestIsHttps()
    {
        return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) === 'https');
    }

    function startSecureSession()
    {
        if(session_status() === PHP_SESSION_ACTIVE) return;
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path' => '/',
            'secure' => requestIsHttps(),
            'httponly' => true,
            'samesite' => 'Lax'
        ));
        session_start();
    }
}
