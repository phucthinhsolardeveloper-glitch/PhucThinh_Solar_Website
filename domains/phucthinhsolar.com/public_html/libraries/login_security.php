<?php
if(!function_exists('adminLoginLimitDescriptors'))
{
    function adminLegacyPasswordHash($password, $secret, $salt)
    {
        return md5((string)$secret.(string)$password.(string)$salt);
    }

    function adminPasswordVerify($password, $storedHash, $secret, $salt)
    {
        $storedHash = (string)$storedHash;
        if($storedHash === '') return false;
        $info = password_get_info($storedHash);
        if(!empty($info['algo'])) return password_verify((string)$password, $storedHash);
        return hash_equals($storedHash, adminLegacyPasswordHash($password, $secret, $salt));
    }

    function adminPasswordNeedsUpgrade($storedHash)
    {
        $info = password_get_info((string)$storedHash);
        return empty($info['algo']) || password_needs_rehash((string)$storedHash, PASSWORD_DEFAULT);
    }

    function adminPasswordHash($password)
    {
        $hash = password_hash((string)$password, PASSWORD_DEFAULT);
        if(!is_string($hash) || $hash === '') throw new RuntimeException('Không thể tạo password hash');
        return $hash;
    }

    function adminSessionInvalid($row, $session, $tokenPresent, $now, $timeout)
    {
        if(empty($row['username']) || !isset($row['password'], $row['lastlogin'])) return true;
        $sessionHash = md5(sha1($row['password'].$row['username']));
        return empty($session['login_session'])
            || !hash_equals($sessionHash, (string)$session['login_session'])
            || ((int)$now - (int)$row['lastlogin']) > max(300, (int)$timeout)
            || !$tokenPresent;
    }

    function adminSessionHasConcurrentLogin($row, $session)
    {
        return !isset($row['user_token'], $session['login_token'])
            || !hash_equals((string)$row['user_token'], (string)$session['login_token']);
    }

    function adminSessionActivityDue($lastLogin, $now, $interval)
    {
        return (int)$lastLogin <= (int)$now - max(30, (int)$interval);
    }

    function adminTouchSessionActivity($d, $idUser, $lastLogin, $now, $interval = 60)
    {
        if(!adminSessionActivityDue($lastLogin, $now, $interval)) return false;
        $d->rawQuery(
            "update #_user set lastlogin = ? where id = ? and lastlogin = ?",
            array((int)$now, (int)$idUser, (int)$lastLogin)
        );
        return true;
    }

    function adminLoginClientIp($server, $trustedProxies = array())
    {
        $remote = isset($server['REMOTE_ADDR']) && filter_var($server['REMOTE_ADDR'], FILTER_VALIDATE_IP) ? $server['REMOTE_ADDR'] : '0.0.0.0';
        if(in_array($remote, $trustedProxies, true) && !empty($server['HTTP_X_FORWARDED_FOR']))
        {
            $forwarded = trim(explode(',', $server['HTTP_X_FORWARDED_FOR'])[0]);
            if(filter_var($forwarded, FILTER_VALIDATE_IP)) return $forwarded;
        }
        return $remote;
    }

    function adminLoginLimitDescriptors($ip, $username, $ipAttempts, $accountAttempts)
    {
        $result = array(array('key' => substr((string)$ip, 0, 50), 'max' => max(1, (int)$ipAttempts)));
        $username = strtolower(trim((string)$username));
        if($username !== '') $result[] = array('key' => 'user:'.sha1($username), 'max' => max(1, (int)$accountAttempts));
        return $result;
    }

    function adminLoginLimitState($d, $descriptors, $delayMinutes, $now)
    {
        $remainingLock = 0;
        foreach($descriptors as $descriptor)
        {
            $row = $d->rawQueryOne("select id, locked_time from #_user_limit where login_ip = ? order by id desc limit 1", array($descriptor['key']));
            if(!empty($row['locked_time']))
            {
                $remaining = ((int)$row['locked_time'] + max(1, (int)$delayMinutes) * 60) - (int)$now;
                if($remaining > 0) $remainingLock = max($remainingLock, $remaining);
                else $d->rawQuery("update #_user_limit set login_attempts = 0, attempt_time = ?, locked_time = 0 where id = ?", array((int)$now, (int)$row['id']));
            }
        }
        return max(0, $remainingLock);
    }

    function adminLoginLimitFailure($d, $descriptors, $now)
    {
        $remainingAttempts = null;
        foreach($descriptors as $descriptor)
        {
            $row = $d->rawQueryOne("select id, login_attempts, locked_time from #_user_limit where login_ip = ? order by id desc limit 1", array($descriptor['key']));
            $attempts = !empty($row['id']) ? (int)$row['login_attempts'] + 1 : 1;
            $lockedTime = $attempts >= $descriptor['max'] ? (int)$now : 0;
            if(!empty($row['id']))
                $d->rawQuery("update #_user_limit set login_attempts = ?, attempt_time = ?, locked_time = ? where id = ?", array($attempts, (int)$now, $lockedTime, (int)$row['id']));
            else
                $d->rawQuery("insert into #_user_limit (login_ip, login_attempts, attempt_time, locked_time) values (?,?,?,?)", array($descriptor['key'], $attempts, (int)$now, $lockedTime));
            $remaining = max(0, (int)$descriptor['max'] - $attempts);
            $remainingAttempts = $remainingAttempts === null ? $remaining : min($remainingAttempts, $remaining);
        }
        return (int)$remainingAttempts;
    }

    function adminLoginLimitReset($d, $descriptors, $now)
    {
        foreach($descriptors as $descriptor)
            $d->rawQuery("update #_user_limit set login_attempts = 0, attempt_time = ?, locked_time = 0 where login_ip = ?", array((int)$now, $descriptor['key']));
    }
}
