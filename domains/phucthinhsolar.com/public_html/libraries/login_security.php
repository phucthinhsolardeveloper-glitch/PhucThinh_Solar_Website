<?php
if(!function_exists('adminLoginLimitDescriptors'))
{
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
