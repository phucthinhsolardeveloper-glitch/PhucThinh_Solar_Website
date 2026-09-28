<?php
/* Shared, credential-free integration primitives. Provider-specific calls stay
   server-side; disabled providers must never make checkout unavailable. */

if(!function_exists('commerceIntegrationConfig'))
{
    function commerceIntegrationConfig($config)
    {
        return isset($config['integrations']) && is_array($config['integrations']) ? $config['integrations'] : array();
    }
}

if(!function_exists('commerceIntegrationJson'))
{
    function commerceIntegrationJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

if(!function_exists('commerceIntegrationHttpJson'))
{
    function commerceIntegrationHttpJson($url, $method = 'GET', array $headers = array(), $body = null, $timeout = 20)
    {
        if(!function_exists('curl_init') || !filter_var($url, FILTER_VALIDATE_URL)) return array('ok' => false, 'status' => 0, 'body' => array(), 'raw' => '', 'error' => 'HTTP client unavailable or URL invalid');
        $ch = curl_init($url);
        $method = strtoupper($method);
        $encoded = $body === null ? null : (is_string($body) ? $body : commerceIntegrationJson($body));
        if($method === 'POST') curl_setopt($ch, CURLOPT_POST, true);
        elseif($method !== 'GET') curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if($encoded !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded); $headers[] = 'Content-Type: application/json'; }
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => $timeout, CURLOPT_HTTPHEADER => $headers));
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return array('ok' => $error === '' && $status >= 200 && $status < 300, 'status' => $status, 'body' => is_array($decoded) ? $decoded : array(), 'raw' => (string)$raw, 'error' => $error);
    }
}

if(!function_exists('commerceVnpayConfigured'))
{
    function commerceVnpayConfigured(array $config)
    {
        return !empty($config['active']) && trim((string)$config['tmn_code']) !== '' && trim((string)$config['hash_secret']) !== '' && filter_var($config['endpoint'], FILTER_VALIDATE_URL) && filter_var($config['return_url'], FILTER_VALIDATE_URL) && filter_var($config['ipn_url'], FILTER_VALIDATE_URL);
    }
}

if(!function_exists('commerceVnpayCreateUrl'))
{
    function commerceVnpayCreateUrl(array $config, $orderCode, $amount, $orderInfo, $ipAddress)
    {
        if(!commerceVnpayConfigured($config)) return array('ok' => false, 'error' => 'VNPay chưa được cấu hình đầy đủ.');
        $params = array('vnp_Version'=>'2.1.0', 'vnp_Command'=>'pay', 'vnp_TmnCode'=>(string)$config['tmn_code'], 'vnp_Amount'=>(string)round((float)$amount * 100), 'vnp_CurrCode'=>'VND', 'vnp_TxnRef'=>(string)$orderCode, 'vnp_OrderInfo'=>trim((string)$orderInfo), 'vnp_OrderType'=>'other', 'vnp_Locale'=>'vn', 'vnp_ReturnUrl'=>(string)$config['return_url'], 'vnp_IpAddr'=>(string)$ipAddress, 'vnp_CreateDate'=>date('YmdHis'));
        ksort($params);
        $query = array();
        /* VNPay signs the RFC1738/query-string form (space becomes +), not the
           RFC3986 rawurlencode form. Keep signing and redirect serialization
           identical. */
        foreach($params as $key => $value) $query[] = urlencode($key).'='.urlencode($value);
        $queryString = implode('&', $query);
        $signature = hash_hmac('sha512', $queryString, (string)$config['hash_secret']);
        return array('ok' => true, 'url' => rtrim($config['endpoint'], '?').'?' . $queryString.'&vnp_SecureHash='.$signature);
    }
}

if(!function_exists('commerceVnpayVerify'))
{
    function commerceVnpayVerify(array $config, array $input)
    {
        if(empty($config['hash_secret']) || empty($input['vnp_SecureHash'])) return false;
        $hash = $input['vnp_SecureHash']; unset($input['vnp_SecureHash'], $input['vnp_SecureHashType']);
        $params = array(); foreach($input as $key => $value) if(strpos($key, 'vnp_') === 0 && $value !== '') $params[$key] = $value;
        ksort($params); $query = array(); foreach($params as $key => $value) $query[] = urlencode($key).'='.urlencode($value);
        return hash_equals(strtolower($hash), strtolower(hash_hmac('sha512', implode('&', $query), (string)$config['hash_secret'])));
    }
}

if(!function_exists('commerceShippingProviders'))
{
    function commerceShippingProviders(array $config)
    {
        $providers = array();
        foreach(commerceShippingProviderDefinitions() as $code => $definition)
        {
            $row = isset($config[$code]) && is_array($config[$code]) ? $config[$code] : array();
            $missing = array();
            foreach($definition['required'] as $key)
                if(trim((string)($row[$key] ?? '')) === '') $missing[] = $key;
            $providers[$code] = array(
                'code' => $code,
                'label' => $definition['label'],
                'active' => !empty($row['active']),
                'configured' => !empty($row['active']) && empty($missing),
                'missing' => $missing,
                'capabilities' => $definition['capabilities'],
                'adapter' => $definition['adapter']
            );
        }
        return $providers;
    }
}

if(!function_exists('commerceShippingProviderDefinitions'))
{
    function commerceShippingProviderDefinitions()
    {
        return array(
            'ghn' => array('label'=>'GHN', 'required'=>array('token','shop_id','endpoint'), 'capabilities'=>array('quote','create','tracking','webhook'), 'adapter'=>'ghn'),
            'ghtk' => array('label'=>'GHTK', 'required'=>array('token','endpoint'), 'capabilities'=>array('quote','create','tracking','webhook'), 'adapter'=>'ghtk'),
            'viettelpost' => array('label'=>'Viettel Post', 'required'=>array('username','password','partner_id','endpoint'), 'capabilities'=>array('quote','create','tracking','webhook'), 'adapter'=>'viettelpost'),
            'nhattin' => array('label'=>'Nhất Tín', 'required'=>array('username','password','partner_id','endpoint'), 'capabilities'=>array('quote','create','tracking','webhook'), 'adapter'=>'nhattin')
        );
    }
}

if(!function_exists('commerceShippingBulkyCategoryIds'))
{
    /* Đèn đường (id_cat 20) và cả nhóm Trụ đèn (id_list 27) là hàng cồng
       kềnh/nặng - thực tế đang gửi nhà xe hoặc Nhất Tín báo giá thủ công
       theo cân nặng/kích thước, KHÔNG áp giá cố định theo khu vực như hàng
       nhỏ gọn (xác nhận với sếp 2026-09-26). Chưa có cột phân loại "cồng
       kềnh" riêng nên khai whitelist id_cat/id_list tay - cập nhật nếu thêm
       danh mục hàng nặng khác (vd tấm pin khổ lớn, tủ điện). */
    function commerceShippingBulkyCategoryIds()
    {
        return array('cat' => array(20), 'list' => array(27));
    }

    function commerceShippingIsBulkyProduct($product)
    {
        if(empty($product)) return false;
        $bulky = commerceShippingBulkyCategoryIds();
        if(in_array((int)(isset($product['id_cat']) ? $product['id_cat'] : 0), $bulky['cat'], true)) return true;
        if(in_array((int)(isset($product['id_list']) ? $product['id_list'] : 0), $bulky['list'], true)) return true;
        return false;
    }
}

if(!function_exists('commerceShippingLegacyQuote'))
{
    function commerceShippingLegacyQuote($d, $cityId, $wardId)
    {
        $cityId = (int)$cityId;
        $wardId = (int)$wardId;
        if($cityId <= 0 || $wardId <= 0) return array('ok'=>false, 'error'=>'Vui lòng chọn tỉnh/thành phố và phường/xã.');
        $place = $d->rawQueryOne(
            "select c.id as city_id, c.name as city_name, w.id as ward_id, w.name as ward_name, w.code as ward_code, w.ship_price
             from #_ward w inner join #_city c on c.id = w.id_city
             where w.id = ? and w.id_city = ? and w.id_district = 0
               and find_in_set('hienthi', w.status) and find_in_set('hienthi', c.status) limit 0,1",
            array($wardId, $cityId)
        );
        if(empty($place)) return array('ok'=>false, 'error'=>'Phường/xã không thuộc tỉnh/thành phố đã chọn.');
        return array(
            'ok' => true,
            'priced' => max(0, (float)$place['ship_price']) > 0,
            'provider' => 'local',
            'label' => 'Phí theo bảng khu vực',
            'amount' => max(0, (float)$place['ship_price']),
            'currency' => 'VND',
            'quote_ref' => 'local-'.$place['city_id'].'-'.$place['ward_id'],
            'place' => $place,
            'source' => 'ward.ship_price'
        );
    }
}

if(!function_exists('commerceShippingQuote'))
{
    function commerceShippingQuote($d, array $config, $cityId, $wardId, $subtotal = 0)
    {
        $legacy = commerceShippingLegacyQuote($d, $cityId, $wardId);
        if(empty($legacy['ok'])) return array('ok'=>false, 'error'=>$legacy['error'], 'quotes'=>array(), 'providers'=>commerceShippingProviders($config));
        $providers = commerceShippingProviders($config);
        $quotes = array($legacy);
        foreach($providers as $provider)
        {
            $quotes[] = array(
                'ok' => false,
                'provider' => $provider['code'],
                'label' => $provider['label'],
                'status' => $provider['configured'] ? 'mapping_required' : ($provider['active'] ? 'missing_credentials' : 'disabled'),
                'amount' => null,
                'currency' => 'VND',
                'message' => $provider['configured'] ? 'Đã có credential, cần khai báo kho gửi/mã dịch vụ/quy cách kiện hàng.' : 'Chưa sẵn sàng gọi API.'
            );
        }
        return array('ok'=>true, 'subtotal'=>(float)$subtotal, 'quotes'=>$quotes, 'selected'=>$legacy, 'priced'=>!empty($legacy['priced']), 'providers'=>$providers);
    }
}
