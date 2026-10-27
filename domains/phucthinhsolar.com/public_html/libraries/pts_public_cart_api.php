<?php
/** Safe read-only DTO helpers for the session cart consumed by Nuxt. */

function ptsCartText($value)
{
    if(!is_scalar($value)) return '';
    return trim(preg_replace('/\s+/u', ' ', strip_tags(htmlspecialchars_decode((string)$value, ENT_QUOTES))));
}

function ptsCartSlug($value)
{
    $value = is_scalar($value) ? trim((string)$value, " /\t\n\r\0\x0B") : '';
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) ? $value : '';
}

function ptsCartMoney($value)
{
    if(!is_scalar($value) || !is_numeric($value)) return null;
    $number = (float)$value;
    if(!is_finite($number) || $number < 0 || $number > PHP_INT_MAX) return null;
    return (int)round($number);
}

function ptsCartImageUrl($value, $root = '', $uploadDirectory = 'upload/product/')
{
    $value = is_scalar($value) ? trim((string)$value) : '';
    if($value === '' || basename($value) !== $value || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    $directory = trim((string)$uploadDirectory, '/\\');
    if($root !== '' && !is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$value)) return '';
    return '/'.trim($directory, '/').'/'.$value;
}

function ptsCartPricingDto(array $product, $pricesVisible)
{
    $regular = ptsCartMoney(isset($product['regular_price']) ? $product['regular_price'] : null);
    $sale = ptsCartMoney(isset($product['sale_price']) ? $product['sale_price'] : null);
    $statuses = array_filter(array_map('trim', explode(',', isset($product['status']) ? (string)$product['status'] : '')));
    $mode = $pricesVisible && !in_array('baogia', $statuses, true) && ($regular !== null || $sale !== null) ? 'price' : 'quote';
    return array(
        'mode' => $mode,
        'currency' => 'VND',
        'regularPrice' => $mode === 'price' ? $regular : null,
        'salePrice' => $mode === 'price' ? $sale : null
    );
}

function ptsCartItemDto(array $cartItem, array $product, $colorLabel = '', $sizeLabel = '', $root = '', $uploadDirectory = 'upload/product/', $pricesVisible = false)
{
    $id = isset($product['id']) ? (int)$product['id'] : 0;
    $slug = ptsCartSlug(isset($product['slugvi']) ? $product['slugvi'] : '');
    $name = ptsCartText(isset($product['namevi']) ? $product['namevi'] : '');
    $quantity = isset($cartItem['qty']) ? max(1, min(999, (int)$cartItem['qty'])) : 1;
    $code = isset($cartItem['code']) && is_scalar($cartItem['code']) ? trim((string)$cartItem['code']) : '';
    if($id <= 0 || $slug === '' || $name === '' || !preg_match('/^[a-f0-9]{32}$/', $code)) return null;

    $pricing = ptsCartPricingDto($product, $pricesVisible);
    $unitPrice = null;
    $lineTotal = null;
    if($pricing['mode'] === 'price')
    {
        $unitPrice = $pricing['salePrice'] !== null && $pricing['salePrice'] > 0 ? $pricing['salePrice'] : $pricing['regularPrice'];
        if($unitPrice !== null) $lineTotal = $unitPrice * $quantity;
    }

    return array(
        'code' => $code,
        'productId' => $id,
        'name' => $name,
        'slug' => $slug,
        'url' => '/san-pham/'.$slug,
        'image' => ptsCartImageUrl(isset($product['photo']) ? $product['photo'] : '', $root, $uploadDirectory),
        'quantity' => $quantity,
        'color' => ptsCartText($colorLabel),
        'size' => ptsCartText($sizeLabel),
        'pricing' => $pricing,
        'unitPrice' => $unitPrice,
        'lineTotal' => $lineTotal
    );
}

function ptsCartPayload(array $items)
{
    $quantityCount = 0;
    $subtotal = 0;
    $hasQuoteItem = false;
    foreach($items as $item)
    {
        $quantityCount += isset($item['quantity']) ? (int)$item['quantity'] : 0;
        if(!isset($item['lineTotal']) || $item['lineTotal'] === null) $hasQuoteItem = true;
        else $subtotal += (int)$item['lineTotal'];
    }

    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'currency' => 'VND',
        'count' => count($items),
        'quantityCount' => $quantityCount,
        'items' => array_values($items),
        'subtotal' => $hasQuoteItem ? null : $subtotal,
        'canCheckout' => !$hasQuoteItem && count($items) > 0
    );
}
