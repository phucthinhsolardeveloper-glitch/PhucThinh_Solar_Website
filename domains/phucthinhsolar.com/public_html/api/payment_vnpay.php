<?php
/* VNPay return/IPN endpoint. It only changes payment state after signature,
   amount, provider and order reference are all verified. */
require_once __DIR__.'/../libraries/session_security.php';
startSecureSession();
define('LIBRARIES','../libraries/');
require_once LIBRARIES.'config.php';
require_once LIBRARIES.'commerce_integrations.php';
require_once LIBRARIES.'autoload.php';
new AutoLoad();
$d = new PDODb($config['database']);
$provider = isset($config['integrations']['payment']['vnpay']) ? $config['integrations']['payment']['vnpay'] : array();
$input = array(); foreach($_GET as $key => $value) if(is_scalar($value)) $input[$key] = (string)$value;
if(!commerceVnpayVerify($provider, $input)) { http_response_code(400); echo 'Invalid signature'; exit; }
$orderCode = isset($input['vnp_TxnRef']) ? trim($input['vnp_TxnRef']) : '';
$responseCode = isset($input['vnp_ResponseCode']) ? trim($input['vnp_ResponseCode']) : '';
$transactionNo = isset($input['vnp_TransactionNo']) ? trim($input['vnp_TransactionNo']) : '';
$order = $orderCode !== '' ? $d->rawQueryOne('select id,total_price from #_order where code = ? limit 1', array($orderCode)) : array();
$amount = isset($input['vnp_Amount']) ? ((float)$input['vnp_Amount'] / 100) : 0;
if(empty($order) || abs((float)$order['total_price'] - $amount) > 0.01) { http_response_code(400); echo 'Invalid order or amount'; exit; }
$now = time();
$status = $responseCode === '00' ? 'paid' : 'failed';
$payload = commerceIntegrationJson($input);
$existing = $d->rawQueryOne('select id,status from #_payment_transaction where provider = ? and transaction_ref = ? limit 1', array('vnpay', $orderCode));
if(empty($existing)) $d->insert('payment_transaction', array('id_order'=>(int)$order['id'], 'provider'=>'vnpay', 'transaction_ref'=>$orderCode, 'provider_transaction_ref'=>$transactionNo, 'amount'=>$amount, 'status'=>$status, 'payload_hash'=>hash('sha256',$payload), 'payload'=>$payload, 'created_at'=>$now, 'updated_at'=>$now));
else $d->rawQuery('update #_payment_transaction set status = ?, provider_transaction_ref = ?, payload_hash = ?, payload = ?, updated_at = ? where id = ?', array($status, $transactionNo, hash('sha256',$payload), $payload, $now, (int)$existing['id']));
if($status === 'paid') $d->rawQuery('update #_order set payment_status = 1, payment_method_code = ?, payment_transaction_ref = ? where id = ?', array('vnpay', $transactionNo, (int)$order['id']));
$isIpn = isset($_GET['mode']) && $_GET['mode'] === 'ipn';
if(!$isIpn)
{
    $thanksToken = '';
    if(!empty($_SESSION['order_thanks']) && is_array($_SESSION['order_thanks']))
    {
        foreach($_SESSION['order_thanks'] as $token => $orderSession)
            if(!empty($orderSession['code']) && (string)$orderSession['code'] === $orderCode) { $thanksToken = (string)$token; break; }
    }
    if($thanksToken !== '') { header('Location: ../cam-on/?token='.rawurlencode($thanksToken), true, 302); exit; }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Thanh toán</title><p>'.($status === 'paid' ? 'Thanh toán thành công' : 'Thanh toán chưa thành công').' cho đơn <strong>'.htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8').'</strong>.</p>'; exit;
}
header('Content-Type: application/json; charset=utf-8'); echo commerceIntegrationJson(array('RspCode'=>'00','Message'=>'Confirm Success')); exit;
