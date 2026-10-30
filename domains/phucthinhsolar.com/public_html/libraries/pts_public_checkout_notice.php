<?php
/** One-time, plain-text projection of the legacy checkout's session flash. */

function ptsCheckoutNoticeText($value, $maxLength)
{
    if(!is_scalar($value)) return '';
    $value = strip_tags((string)$value);
    $value = trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value));
    if(function_exists('mb_substr')) return mb_substr($value, 0, $maxLength, 'UTF-8');
    return substr($value, 0, $maxLength);
}

function ptsPublicCheckoutNoticePayload(array &$session)
{
    $flash = isset($session['flash']) && is_array($session['flash']) ? $session['flash'] : array();
    $messages = array();
    $notice = null;
    $form = array();

    $encodedMessage = isset($flash['message']) && is_scalar($flash['message']) ? (string)$flash['message'] : '';
    if($encodedMessage !== '')
    {
        $decodedMessage = base64_decode($encodedMessage, true);
        $messageData = $decodedMessage !== false ? json_decode($decodedMessage, true) : null;
        if(is_array($messageData) && isset($messageData['status']) && $messageData['status'] === 'danger' && isset($messageData['messages']) && is_array($messageData['messages']))
        {
            foreach(array_slice($messageData['messages'], 0, 8) as $message)
            {
                $message = ptsCheckoutNoticeText($message, 240);
                if($message !== '') $messages[] = $message;
            }
        }
    }

    $encodedToast = isset($flash['toast']) && is_scalar($flash['toast']) ? (string)$flash['toast'] : '';
    if($encodedToast !== '')
    {
        $toastData = json_decode($encodedToast, true);
        if(is_array($toastData) && in_array(isset($toastData['status']) ? $toastData['status'] : '', array('error', 'success'), true))
        {
            $toastText = ptsCheckoutNoticeText(isset($toastData['text']) ? $toastData['text'] : '', 500);
            if($toastText !== '') $notice = array('status' => $toastData['status'], 'text' => $toastText);
        }
    }

    $limits = array(
        'fullname' => 150,
        'phone' => 30,
        'email' => 190,
        'city' => 12,
        'ward' => 12,
        'address' => 255,
        'requirements' => 1000,
        'payments' => 12
    );
    foreach($limits as $key => $limit)
    {
        if(!array_key_exists($key, $flash) || !is_scalar($flash[$key])) continue;
        $value = ptsCheckoutNoticeText($flash[$key], $limit);
        if($value !== '') $form[$key] = $value;
    }

    foreach(array_merge(array('message', 'toast'), array_keys($limits)) as $key) unset($session['flash'][$key]);
    if(isset($session['flash']) && is_array($session['flash']) && !$session['flash']) unset($session['flash']);

    return array(
        'schemaVersion' => 1,
        'locale' => 'vi',
        'messages' => $messages,
        'toast' => $notice,
        'form' => $form ? $form : null
    );
}
