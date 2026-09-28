<?php
if(!defined('SOURCES')) die('Error');
require_once LIBRARIES.'commerce_integrations.php';

/* Màn hình read-only ở giai đoạn credential chưa được cấp. Không render secret,
   không cho Admin sửa env từ database và không gọi API nhà cung cấp tại đây. */
$integrationConfig = commerceIntegrationConfig($config);
$integrationPayments = array();
$vnpayConfig = isset($integrationConfig['payment']['vnpay']) ? $integrationConfig['payment']['vnpay'] : array();
$integrationPayments[] = array('code'=>'vnpay', 'label'=>'VNPay', 'active'=>!empty($vnpayConfig['active']), 'configured'=>commerceVnpayConfigured($vnpayConfig), 'requirements'=>'VNPAY_TMN_CODE · VNPAY_HASH_SECRET · Return/IPN URL');
$integrationShipping = commerceShippingProviders(isset($integrationConfig['shipping']) ? $integrationConfig['shipping'] : array());
$integrationTables = array('payment_transaction'=>false, 'shipping_shipment'=>false);
foreach(array_keys($integrationTables) as $table)
{
    try { $row = $d->rawQueryOne("select count(*) as total from #_{$table}"); $integrationTables[$table] = (int)$row['total']; }
    catch(Throwable $e) { $integrationTables[$table] = null; }
}
$template = 'integrations/man';
