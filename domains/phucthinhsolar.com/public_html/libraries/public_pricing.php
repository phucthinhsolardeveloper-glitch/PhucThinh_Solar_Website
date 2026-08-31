<?php
if(!defined('PUBLIC_PRODUCT_PRICES')) define('PUBLIC_PRODUCT_PRICES', false);

function publicProductPricesVisible()
{
    return PUBLIC_PRODUCT_PRICES === true;
}

function publicProductRequiresQuote($product = array())
{
    return !publicProductPricesVisible() || (empty($product['regular_price']) && empty($product['sale_price']));
}
