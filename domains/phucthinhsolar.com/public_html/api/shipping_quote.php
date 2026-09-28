<?php
/* Quote contract for checkout and future carrier adapters.
   This endpoint is read-only: it never creates a shipment or stores credentials. */
require_once __DIR__.'/config.php';
require_once LIBRARIES.'commerce_integrations.php';

header('Content-Type: application/json; charset=UTF-8');
if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo commerceIntegrationJson(array('ok'=>false, 'error'=>'Phương thức request không hợp lệ.'));
    exit();
}

$cityId = isset($_POST['city']) && is_scalar($_POST['city']) ? (int)$_POST['city'] : 0;
$wardId = isset($_POST['ward']) && is_scalar($_POST['ward']) ? (int)$_POST['ward'] : 0;
$subtotal = 0;
if(isset($_POST['subtotal']) && is_scalar($_POST['subtotal'])) $subtotal = max(0, (float)$_POST['subtotal']);
elseif(!empty($_SESSION['cart'])) $subtotal = (float)$cart->getOrderTotal();

$shippingConfig = isset($config['integrations']['shipping']) && is_array($config['integrations']['shipping']) ? $config['integrations']['shipping'] : array();
$result = commerceShippingQuote($d, $shippingConfig, $cityId, $wardId, $subtotal);
if(empty($result['ok'])) http_response_code(422);
echo commerceIntegrationJson($result);
?>
