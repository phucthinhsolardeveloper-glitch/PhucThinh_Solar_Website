<?php
/**
 * Nội dung biên tập của email transactional.
 *
 * Layout HTML, dữ liệu nghiệp vụ và danh sách placeholder được giữ trong
 * source. Admin chỉ sửa các chuỗi văn bản plain-text đã allowlist; không nhận
 * HTML/PHP tùy ý để tránh phá layout hoặc chèn nội dung nguy hiểm vào email.
 */
if(!function_exists('ptsMailTemplateDefinitions'))
{
    function ptsMailTemplateDefinitions()
    {
        return array(
            'consult_admin' => array(
                'label' => 'Thông báo yêu cầu tư vấn',
                'audience' => 'Nội bộ',
                'subject' => '[Tư vấn mới] {customer_name} - {customer_phone}',
                'heading' => 'Yêu cầu tư vấn mới từ website',
                'intro' => 'Khách hàng vừa gửi yêu cầu tư vấn. Vui lòng kiểm tra nhu cầu và chủ động liên hệ trong thời gian sớm nhất.',
                'closing' => 'Nếu khách có cung cấp email, hãy bấm Trả lời để phản hồi trực tiếp cho khách.',
                'tokens' => array('{customer_name}', '{customer_phone}')
            ),
            'dealer_admin' => array(
                'label' => 'Thông báo đăng ký đại lý',
                'audience' => 'Nội bộ',
                'subject' => '[Đăng ký đại lý] {company} - {customer_name}',
                'heading' => 'Đăng ký hợp tác đại lý mới',
                'intro' => 'Một doanh nghiệp hoặc cửa hàng vừa gửi thông tin đăng ký hợp tác với Phúc Thịnh Solar.',
                'closing' => 'Vui lòng xác minh thông tin và cập nhật trạng thái xử lý trong Admin.',
                'tokens' => array('{company}', '{customer_name}', '{customer_phone}')
            ),
            'contact_admin' => array(
                'label' => 'Thông báo liên hệ',
                'audience' => 'Nội bộ',
                'subject' => '[Liên hệ mới] {contact_subject}',
                'heading' => 'Khách hàng vừa gửi liên hệ',
                'intro' => 'Website vừa tiếp nhận một nội dung liên hệ mới. Thông tin chi tiết được trình bày bên dưới.',
                'closing' => 'Hãy bấm Trả lời để phản hồi trực tiếp nếu khách đã cung cấp email hợp lệ.',
                'tokens' => array('{contact_subject}', '{customer_name}', '{customer_phone}')
            ),
            'contact_customer' => array(
                'label' => 'Xác nhận đã nhận liên hệ',
                'audience' => 'Khách hàng',
                'subject' => 'Phúc Thịnh Solar đã nhận được yêu cầu của Quý khách',
                'heading' => 'Cảm ơn Quý khách đã liên hệ',
                'intro' => 'Phúc Thịnh Solar đã tiếp nhận thông tin của Quý khách. Bộ phận phụ trách sẽ kiểm tra và phản hồi trong thời gian làm việc gần nhất.',
                'closing' => 'Nếu cần hỗ trợ gấp, Quý khách vui lòng gọi hotline của Phúc Thịnh Solar.',
                'tokens' => array('{customer_name}', '{contact_subject}')
            ),
            'order_admin' => array(
                'label' => 'Thông báo đơn hàng',
                'audience' => 'Nội bộ',
                'subject' => '[Đơn hàng mới #{order_code}] {customer_name}',
                'heading' => 'Website vừa tiếp nhận đơn hàng mới',
                'intro' => 'Vui lòng kiểm tra sản phẩm, thông tin giao nhận và liên hệ xác nhận với khách hàng.',
                'closing' => 'Bấm Trả lời để trao đổi trực tiếp với khách nếu email đơn hàng hợp lệ.',
                'tokens' => array('{order_code}', '{customer_name}', '{customer_phone}')
            ),
            'order_customer' => array(
                'label' => 'Xác nhận đơn hàng',
                'audience' => 'Khách hàng',
                'subject' => 'Phúc Thịnh Solar đã nhận đơn hàng #{order_code}',
                'heading' => 'Cảm ơn Quý khách đã đặt hàng',
                'intro' => 'Đơn hàng #{order_code} đã được ghi nhận. Phúc Thịnh Solar sẽ liên hệ xác nhận trước khi tiến hành giao hàng hoặc thi công.',
                'closing' => 'Quý khách vui lòng giữ lại email này để tiện đối chiếu thông tin đơn hàng.',
                'tokens' => array('{order_code}', '{customer_name}')
            ),
            'member_active' => array(
                'label' => 'Kích hoạt tài khoản thành viên',
                'audience' => 'Khách hàng (legacy)',
                'subject' => 'Kích hoạt tài khoản Phúc Thịnh Solar',
                'heading' => 'Hoàn tất đăng ký tài khoản',
                'intro' => 'Tài khoản của Quý khách đã được tạo. Vui lòng kiểm tra thông tin và sử dụng nút bên dưới để kích hoạt.',
                'closing' => 'Nếu Quý khách không thực hiện đăng ký này, vui lòng bỏ qua email.',
                'tokens' => array('{customer_name}')
            ),
            'member_forgot' => array(
                'label' => 'Cấp lại mật khẩu thành viên',
                'audience' => 'Khách hàng (legacy)',
                'subject' => 'Thông tin đăng nhập mới từ Phúc Thịnh Solar',
                'heading' => 'Yêu cầu cấp lại mật khẩu',
                'intro' => 'Phúc Thịnh Solar đã tiếp nhận yêu cầu cấp lại mật khẩu cho tài khoản của Quý khách.',
                'closing' => 'Vui lòng đăng nhập và đổi mật khẩu ngay sau khi truy cập tài khoản.',
                'tokens' => array('{customer_name}')
            )
        );
    }

    function ptsMailTemplateEditableFields()
    {
        return array('subject' => 180, 'heading' => 180, 'intro' => 700, 'closing' => 500);
    }

    function ptsMailTemplateCleanText($value, $maxLength)
    {
        $value = is_scalar($value) ? trim((string)$value) : '';
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim(strip_tags($value));
        if(function_exists('mb_substr')) $value = mb_substr($value, 0, (int)$maxLength, 'UTF-8');
        else $value = substr($value, 0, (int)$maxLength);
        return $value;
    }

    function ptsMailTemplateStored($settingOptions)
    {
        $definitions = ptsMailTemplateDefinitions();
        $stored = is_array($settingOptions) && !empty($settingOptions['mail_templates']) && is_array($settingOptions['mail_templates'])
            ? $settingOptions['mail_templates'] : array();
        $fields = ptsMailTemplateEditableFields();
        $resolved = array();
        foreach($definitions as $key => $definition)
        {
            $resolved[$key] = $definition;
            foreach($fields as $field => $maxLength)
            {
                if(isset($stored[$key][$field]))
                {
                    $candidate = ptsMailTemplateCleanText($stored[$key][$field], $maxLength);
                    if($candidate !== '') $resolved[$key][$field] = $candidate;
                }
            }
        }
        return $resolved;
    }

    function ptsMailTemplateCleanPosted($posted)
    {
        $definitions = ptsMailTemplateDefinitions();
        $fields = ptsMailTemplateEditableFields();
        $clean = array();
        $posted = is_array($posted) ? $posted : array();
        foreach($definitions as $key => $definition)
        {
            $clean[$key] = array();
            foreach($fields as $field => $maxLength)
            {
                $value = isset($posted[$key][$field]) ? ptsMailTemplateCleanText($posted[$key][$field], $maxLength) : '';
				if($field === 'subject') $value = trim(preg_replace('/[\r\n]+/', ' ', $value));
                $clean[$key][$field] = $value !== '' ? $value : $definition[$field];
            }
        }
        return $clean;
    }

    function ptsMailTemplateReplace($text, $values)
    {
        $replace = array();
        foreach((array)$values as $key => $value)
        {
            $token = '{'.trim((string)$key, "{} \t\n\r\0\x0B").'}';
            $replace[$token] = is_scalar($value) ? trim((string)$value) : '';
        }
        return strtr((string)$text, $replace);
    }

    function ptsMailTemplateResolvedCopy($settingOptions, $key, $values = array())
    {
        $all = ptsMailTemplateStored($settingOptions);
        if(!isset($all[$key])) return array();
        $copy = $all[$key];
        foreach(array('subject','heading','intro','closing') as $field)
            $copy[$field] = ptsMailTemplateReplace($copy[$field], $values);
        return $copy;
    }
}
?>
