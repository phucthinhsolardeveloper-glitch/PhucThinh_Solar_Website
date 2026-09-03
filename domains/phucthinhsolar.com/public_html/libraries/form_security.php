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

    function checkoutIdempotencyToken()
    {
        if(empty($_SESSION['checkout_idempotency_token']) || !preg_match('/^[a-f0-9]{64}$/', (string)$_SESSION['checkout_idempotency_token']))
            $_SESSION['checkout_idempotency_token'] = bin2hex(random_bytes(32));
        return $_SESSION['checkout_idempotency_token'];
    }

    function checkoutIdempotencyTokenValid($token)
    {
        return checkoutIdempotencyTokenFormatValid($token)
            && hash_equals(checkoutIdempotencyToken(), $token);
    }

    function checkoutIdempotencyTokenFormatValid($token)
    {
        return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    function checkoutRotateIdempotencyToken()
    {
        unset($_SESSION['checkout_idempotency_token']);
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

    function formClientIp($server, $trustedProxies = array())
    {
        $remote = isset($server['REMOTE_ADDR']) && filter_var($server['REMOTE_ADDR'], FILTER_VALIDATE_IP) ? $server['REMOTE_ADDR'] : '0.0.0.0';
        if(in_array($remote, $trustedProxies, true) && !empty($server['HTTP_X_FORWARDED_FOR']))
        {
            $forwarded = trim(explode(',', $server['HTTP_X_FORWARDED_FOR'])[0]);
            if(filter_var($forwarded, FILTER_VALIDATE_IP)) return $forwarded;
        }
        return $remote;
    }

    function formPersistentRateLimit($d, $form, $ip, $limit, $seconds, $now = null)
    {
        $now = $now === null ? time() : (int)$now;
        $limit = max(1, (int)$limit);
        $seconds = max(1, (int)$seconds);
        $key = 'form:'.sha1(strtolower(trim((string)$form)).'|'.(string)$ip);
        $row = $d->rawQueryOne("select id, login_attempts, attempt_time from #_user_limit where login_ip = ? order by id desc limit 1", array($key));
        $attempts = !empty($row['id']) && (int)$row['attempt_time'] > $now - $seconds ? (int)$row['login_attempts'] : 0;
        if($attempts >= $limit) return false;
        $attempts++;
        if(!empty($row['id'])) $d->rawQuery("update #_user_limit set login_attempts = ?, attempt_time = ?, locked_time = 0 where id = ?", array($attempts, $now, (int)$row['id']));
        else $d->rawQuery("insert into #_user_limit (login_ip, login_attempts, attempt_time, locked_time) values (?,?,?,0)", array($key, $attempts, $now));
        return true;
    }
}
