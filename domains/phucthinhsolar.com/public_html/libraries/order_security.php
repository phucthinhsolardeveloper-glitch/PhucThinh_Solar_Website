<?php
if(!function_exists('prepareAdminOrderUpdate'))
{
    function prepareAdminOrderUpdate($d, $input, $paymentOptions)
    {
        $input = is_array($input) ? $input : array();
        $data = array();
        $errors = array();
        foreach(array('requirements', 'notes') as $field)
            if(array_key_exists($field, $input)) $data[$field] = htmlspecialchars(trim((string)$input[$field]), ENT_QUOTES, 'UTF-8');

        $status = isset($input['order_status']) ? (int)$input['order_status'] : 0;
        $statusRow = $status > 0 ? $d->rawQueryOne("select id from #_order_status where id = ? limit 0,1", array($status)) : array();
        if(empty($statusRow['id'])) $errors[] = 'Trạng thái đơn hàng không hợp lệ';
        else $data['order_status'] = $status;

        $payment = isset($input['payment_status']) ? (int)$input['payment_status'] : -1;
        if(!array_key_exists($payment, $paymentOptions)) $errors[] = 'Trạng thái thanh toán không hợp lệ';
        else $data['payment_status'] = $payment;

        return array('data' => $data, 'errors' => $errors);
    }
}

if(!function_exists('adminOrderExportFilter'))
{
    function adminOrderExportFilter($request, &$params)
    {
        $request = is_array($request) ? $request : array();
        if(!is_array($params)) $params = array();
        $where = '';

        $rawList = isset($request['listid']) && is_scalar($request['listid']) ? trim((string)$request['listid']) : '';
        if($rawList !== '')
        {
            $ids = array();
            $validList = true;
            foreach(explode(',', $rawList) as $rawId)
            {
                $rawId = trim($rawId);
                if(!preg_match('/^[1-9][0-9]*$/D', $rawId)) { $validList = false; break; }
                $ids[] = (int)$rawId;
            }
            $ids = array_values(array_unique($ids));
            if(!$validList || empty($ids) || count($ids) > 500) $where .= ' and 1=0';
            else
            {
                $where .= ' and id in ('.implode(',', array_fill(0, count($ids), '?')).')';
                foreach($ids as $id) $params[] = $id;
            }
        }

        foreach(array('order_status' => 'order_status', 'order_payment' => 'order_payment', 'id_city' => 'city', 'id_ward' => 'ward') as $requestKey => $column)
        {
            $value = isset($request[$requestKey]) && is_scalar($request[$requestKey]) ? (int)$request[$requestKey] : 0;
            if($value > 0)
            {
                $where .= ' and '.$column.' = ?';
                $params[] = $value;
            }
        }

        $orderDate = isset($request['order_date']) && is_scalar($request['order_date']) ? trim((string)$request['order_date']) : '';
        if($orderDate !== '')
        {
            $dateParts = explode(' - ', $orderDate);
            if(count($dateParts) === 2)
            {
                $dateFrom = strtotime(str_replace('/', '-', trim($dateParts[0])).' 00:00:00');
                $dateTo = strtotime(str_replace('/', '-', trim($dateParts[1])).' 23:59:59');
                if($dateFrom !== false && $dateTo !== false && $dateFrom <= $dateTo)
                {
                    $where .= ' and date_created >= ? and date_created <= ?';
                    $params[] = $dateFrom;
                    $params[] = $dateTo;
                }
            }
        }

        $rangePrice = isset($request['range_price']) && is_scalar($request['range_price']) ? trim((string)$request['range_price']) : '';
        if($rangePrice !== '')
        {
            $priceParts = explode(';', $rangePrice);
            if(count($priceParts) === 2 && is_numeric(trim($priceParts[0])) && is_numeric(trim($priceParts[1])))
            {
                $priceFrom = max(0, (float)trim($priceParts[0]));
                $priceTo = max($priceFrom, (float)trim($priceParts[1]));
                $where .= ' and total_price >= ? and total_price <= ?';
                $params[] = $priceFrom;
                $params[] = $priceTo;
            }
        }

        if(isset($request['keyword']))
        {
            $keywordClause = adminKeywordLikeClause($request['keyword'], array('fullname', 'email', 'phone', 'code'), $params);
            if($keywordClause !== '') $where .= ' and '.$keywordClause;
        }

        return $where;
    }
}
