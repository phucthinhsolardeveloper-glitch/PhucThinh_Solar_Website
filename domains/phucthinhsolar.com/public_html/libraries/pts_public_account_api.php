<?php
/** Read-only, session-scoped DTO for the Nuxt customer-account screens. */

function ptsAccountText($value)
{
    if(!is_scalar($value)) return '';
    $text = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
}

function ptsAccountFlash($flash)
{
    $encoded = $flash->get('message');
    $message = array();
    if(is_string($encoded) && $encoded !== '')
    {
        $decoded = json_decode(base64_decode($encoded, true), true);
        if(is_array($decoded) && isset($decoded['messages']) && is_array($decoded['messages']))
        {
            foreach(array_slice($decoded['messages'], 0, 8) as $line)
            {
                $line = ptsAccountText($line);
                if($line !== '') $message[] = $line;
            }
        }
    }
    $toastValue = $flash->get('toast');
    if(is_string($toastValue) && $toastValue !== '')
    {
        $toast = json_decode($toastValue, true);
        if(is_array($toast) && isset($toast['text']))
        {
            $text = ptsAccountText($toast['text']);
            if($text !== '' && !in_array($text, $message, true)) $message[] = $text;
        }
    }
    $fields = array();
    foreach(array('fullname','username','gender','birthday','email','phone','address') as $key)
    {
        $value = $flash->get($key);
        if(is_scalar($value)) $fields[$key] = ptsAccountText($value);
    }
    return array('messages'=>$message, 'fields'=>$fields);
}

function ptsAccountOrderSummary(array $row)
{
    return array(
        'id'=>isset($row['id']) ? (int)$row['id'] : 0,
        'code'=>ptsAccountText($row['code'] ?? ''),
        'date'=>ptsAccountText($row['date_created'] ?? $row['createdAt'] ?? ''),
        'name'=>ptsAccountText($row['fullname'] ?? ''),
        'phone'=>ptsAccountText($row['phone'] ?? ''),
        'total'=>isset($row['total_price']) && is_numeric($row['total_price']) ? max(0, (int)round((float)$row['total_price'])) : 0,
        'paid'=>isset($row['payment_status']) && (int)$row['payment_status'] === 1,
        'status'=>ptsAccountText($row['order_status_name'] ?? $row['status'] ?? ''),
        'returnRequested'=>!empty($row['return_requested'])
    );
}

function ptsAccountPayload($d, $action, $id, $query, $flash, $csrfToken)
{
    $session = isset($_SESSION['signin']) && is_array($_SESSION['signin']) ? $_SESSION['signin'] : array();
    $memberId = !empty($session['active']) && !empty($session['id']) ? (int)$session['id'] : 0;
    $payload = array(
        'schemaVersion'=>1,
        'action'=>$action,
        'authenticated'=>$memberId > 0,
        'csrfToken'=>$csrfToken,
        'messages'=>$flash['messages'],
        'fields'=>(object)$flash['fields'],
        'member'=>null,
        'orders'=>array(),
        'order'=>null,
        'returns'=>array()
    );

    if($action === 'activate')
    {
        $row = $id > 0 ? $d->rawQueryOne("select id,status from #_member where id=? limit 1", array($id)) : array();
        $payload['activation'] = array('available'=>!empty($row['id']) && strpos((string)($row['status'] ?? ''), 'hienthi') === false);
        return $payload;
    }

    if($action === 'login' || $action === 'register' || $action === 'forgot') return $payload;
    if($memberId <= 0) return $payload;

    if($action === 'profile')
    {
        $row = $d->rawQueryOne("select fullname,username,gender,birthday,email,phone,address from #_member where id=? limit 1", array($memberId));
        if(!empty($row))
        {
            $row['birthday'] = !empty($row['birthday']) ? date('d/m/Y', (int)$row['birthday']) : '';
            foreach($row as $key=>$value) $row[$key] = ptsAccountText($value);
            $payload['member'] = $row;
        }
        return $payload;
    }

    if($action === 'orders' || $action === 'order-detail' || $action === 'cancellations')
    {
        $sql = "select o.id,o.code,o.date_created,o.fullname,o.phone,o.total_price,o.payment_status,o.order_status,os.namevi as order_status_name,exists(select 1 from #_order_returns r where r.id_order=o.id and r.id_user=o.id_user) as return_requested from #_order o left join #_order_status os on os.id=o.order_status where o.id_user=?";
        $params = array($memberId);
        if($action === 'cancellations') { $sql .= ' and order_status=?'; $params[] = 4; }
        $sql .= ' order by o.id desc limit 100';
        $rows = (array)$d->rawQuery($sql, $params);
        foreach($rows as $row) if(is_array($row)) $payload['orders'][] = ptsAccountOrderSummary($row);

        if($action === 'order-detail' && $id > 0)
        {
            $order = $d->rawQueryOne("select o.id,o.code,o.date_created,o.fullname,o.phone,o.total_price,o.payment_status,o.order_status,os.namevi as order_status_name,exists(select 1 from #_order_returns r where r.id_order=o.id and r.id_user=o.id_user) as return_requested from #_order o left join #_order_status os on os.id=o.order_status where o.id=? and o.id_user=? limit 1", array($id, $memberId));
            if(!empty($order['id']))
            {
                $payload['order'] = ptsAccountOrderSummary($order);
                $details = (array)$d->rawQuery("select name,photo,code,regular_price,sale_price,quantity,color,size from #_order_detail where id_order=? order by id asc", array((int)$order['id']));
                if(!$details) $details = (array)$d->rawQuery("select name,photo,code,regular_price,sale_price,quantity,color,size from #_order_details where id_order=? order by id asc", array((int)$order['id']));
                $payload['order']['items'] = array();
                foreach($details as $detail)
                {
                    if(!is_array($detail)) continue;
                    $quantity = isset($detail['quantity']) ? max(0, (int)$detail['quantity']) : 0;
                    $price = isset($detail['sale_price']) && (float)$detail['sale_price'] > 0 ? (float)$detail['sale_price'] : (float)($detail['regular_price'] ?? 0);
                    $payload['order']['items'][] = array(
                        'name'=>ptsAccountText($detail['name'] ?? ''),
                        'image'=>preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', (string)($detail['photo'] ?? '')) ? '/upload/product/'.$detail['photo'] : '',
                        'code'=>ptsAccountText($detail['code'] ?? ''),
                        'quantity'=>$quantity,
                        'lineTotal'=>max(0, (int)round($price * $quantity)),
                        'color'=>ptsAccountText($detail['color'] ?? ''),
                        'size'=>ptsAccountText($detail['size'] ?? '')
                    );
                }
            }
        }
        return $payload;
    }

    if($action === 'returns')
    {
        $rows = (array)$d->rawQuery("select id,code,createdAt,total_price,status from #_order_returns where id_user=? order by id desc limit 100", array($memberId));
        foreach($rows as $row)
        {
            if(!is_array($row)) continue;
            $payload['returns'][] = array(
                'id'=>(int)($row['id'] ?? 0),
                'code'=>ptsAccountText($row['code'] ?? ''),
                'date'=>ptsAccountText($row['createdAt'] ?? ''),
                'total'=>isset($row['total_price']) && is_numeric($row['total_price']) ? max(0, (int)round((float)$row['total_price'])) : 0,
                'status'=>'Đã trả'
            );
        }
    }
    return $payload;
}
