<?php
if(!defined('PUBLIC_PRODUCT_PRICES')) define('PUBLIC_PRODUCT_PRICES', false);

function publicProductPricesVisible()
{
    global $optsetting;

    if(isset($optsetting['public_product_prices']))
    {
        return (string)$optsetting['public_product_prices'] === '1';
    }

    return PUBLIC_PRODUCT_PRICES === true;
}

function publicProductRequiresQuote($product = array())
{
    return !publicProductPricesVisible() || (empty($product['regular_price']) && empty($product['sale_price']));
}
