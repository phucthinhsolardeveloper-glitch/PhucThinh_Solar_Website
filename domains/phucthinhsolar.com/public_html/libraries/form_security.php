<?php
if(!function_exists('formCsrfToken'))
{
    function formCsrfToken()
    {
        if(empty($_SESSION['form_csrf_token'])) $_SESSION['form_csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['form_csrf_token'];
    }

    function formCsrfValid($token)
    {
        return is_string($token) && $token !== '' && hash_equals(formCsrfToken(), $token);
    }

    function formRateLimit($key, $limit, $seconds)
    {
        $now = time();
        $bucket = !empty($_SESSION['form_rate_limit'][$key]) && is_array($_SESSION['form_rate_limit'][$key]) ? $_SESSION['form_rate_limit'][$key] : array();
        $bucket = array_values(array_filter($bucket, function($time) use ($now, $seconds) { return $time > $now - $seconds; }));
        if(count($bucket) >= $limit) { $_SESSION['form_rate_limit'][$key] = $bucket; return false; }
        $bucket[] = $now;
        $_SESSION['form_rate_limit'][$key] = $bucket;
        return true;
    }
}
