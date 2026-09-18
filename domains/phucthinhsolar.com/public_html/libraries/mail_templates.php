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
                'subject' => '[Yêu cầu tư vấn mới] {customer_name} - {customer_phone}',
                'heading' => 'Phúc Thịnh Solar vừa nhận được yêu cầu tư vấn',
                'intro' => 'Khách hàng đã để lại thông tin và nhu cầu quan tâm trên website. Vui lòng kiểm tra nội dung bên dưới và chủ động liên hệ để tư vấn phương án phù hợp.',
                'closing' => 'Nếu khách hàng đã cung cấp email hợp lệ, anh/chị có thể bấm Trả lời để trao đổi trực tiếp.',
                'tokens' => array('{customer_name}', '{customer_phone}')
            ),
            'dealer_admin' => array(
                'label' => 'Thông báo đăng ký đại lý',
                'audience' => 'Nội bộ',
                'subject' => '[Đăng ký hợp tác đại lý] {company} - {customer_name}',
                'heading' => 'Phúc Thịnh Solar vừa nhận được đăng ký hợp tác',
                'intro' => 'Một doanh nghiệp hoặc cửa hàng đã gửi thông tin đăng ký trở thành đối tác của Phúc Thịnh Solar. Vui lòng kiểm tra hồ sơ và liên hệ để trao đổi chính sách phù hợp.',
                'closing' => 'Sau khi liên hệ, vui lòng cập nhật trạng thái xử lý trong Admin để đội ngũ thuận tiện theo dõi.',
                'tokens' => array('{company}', '{customer_name}', '{customer_phone}')
            ),
            'contact_admin' => array(
                'label' => 'Thông báo liên hệ',
                'audience' => 'Nội bộ',
                'subject' => '[Liên hệ mới] {contact_subject}',
                'heading' => 'Phúc Thịnh Solar vừa nhận được liên hệ mới',
                'intro' => 'Khách hàng đã gửi nội dung liên hệ qua website. Thông tin người gửi và yêu cầu chi tiết được trình bày bên dưới để bộ phận phụ trách tiếp nhận.',
                'closing' => 'Nếu địa chỉ email của khách hàng hợp lệ, anh/chị có thể bấm Trả lời để phản hồi trực tiếp.',
                'tokens' => array('{contact_subject}', '{customer_name}', '{customer_phone}')
            ),
            'contact_customer' => array(
                'label' => 'Xác nhận đã nhận liên hệ',
                'audience' => 'Khách hàng',
                'subject' => 'Phúc Thịnh Solar đã tiếp nhận yêu cầu của Quý khách',
                'heading' => 'Cảm ơn Quý khách đã tin tưởng Phúc Thịnh Solar',
                'intro' => 'Thông tin của Quý khách đã được ghi nhận và chuyển đến bộ phận phụ trách. Chúng tôi sẽ xem xét nhu cầu để hỗ trợ phương án phù hợp trong thời gian làm việc.',
                'closing' => 'Nếu cần hỗ trợ sớm hơn, Quý khách vui lòng liên hệ hotline được ghi ở cuối email.',
                'tokens' => array('{customer_name}', '{contact_subject}')
            ),
            'order_admin' => array(
                'label' => 'Thông báo đơn hàng',
                'audience' => 'Nội bộ',
                'subject' => '[Đơn hàng mới #{order_code}] {customer_name}',
                'heading' => 'Phúc Thịnh Solar vừa nhận được đơn hàng mới',
                'intro' => 'Vui lòng kiểm tra danh sách sản phẩm, thông tin giao nhận và nhu cầu bổ sung trước khi liên hệ xác nhận với khách hàng.',
                'closing' => 'Nếu email trong đơn hàng hợp lệ, anh/chị có thể bấm Trả lời để trao đổi trực tiếp với khách hàng.',
                'tokens' => array('{order_code}', '{customer_name}', '{customer_phone}')
            ),
            'order_customer' => array(
                'label' => 'Xác nhận đơn hàng',
                'audience' => 'Khách hàng',
                'subject' => 'Phúc Thịnh Solar đã tiếp nhận đơn hàng #{order_code}',
                'heading' => 'Cảm ơn Quý khách đã lựa chọn Phúc Thịnh Solar',
                'intro' => 'Đơn hàng #{order_code} đã được ghi nhận. Đội ngũ Phúc Thịnh Solar sẽ kiểm tra thông tin và liên hệ xác nhận trước khi giao hàng hoặc triển khai.',
                'closing' => 'Quý khách vui lòng lưu lại email này để thuận tiện đối chiếu khi cần hỗ trợ về đơn hàng.',
                'tokens' => array('{order_code}', '{customer_name}')
            ),
            'member_active' => array(
                'label' => 'Kích hoạt tài khoản thành viên',
                'audience' => 'Khách hàng (legacy)',
                'subject' => 'Hoàn tất đăng ký tài khoản Phúc Thịnh Solar',
                'heading' => 'Vui lòng kích hoạt tài khoản của Quý khách',
                'intro' => 'Tài khoản Phúc Thịnh Solar đã được tạo thành công. Quý khách vui lòng kiểm tra thông tin và chọn nút bên dưới để hoàn tất kích hoạt.',
                'closing' => 'Nếu không thực hiện đăng ký này, Quý khách có thể bỏ qua email và không cần thao tác thêm.',
                'tokens' => array('{customer_name}')
            ),
            'member_forgot' => array(
                'label' => 'Cấp lại mật khẩu thành viên',
                'audience' => 'Khách hàng (legacy)',
                'subject' => 'Mật khẩu tạm cho tài khoản Phúc Thịnh Solar',
                'heading' => 'Yêu cầu cấp lại mật khẩu đã được xử lý',
                'intro' => 'Phúc Thịnh Solar đã tạo mật khẩu tạm cho tài khoản của Quý khách theo yêu cầu vừa tiếp nhận.',
                'closing' => 'Để bảo vệ tài khoản, Quý khách vui lòng đăng nhập và thay đổi mật khẩu ngay sau lần truy cập đầu tiên.',
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
