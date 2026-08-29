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
