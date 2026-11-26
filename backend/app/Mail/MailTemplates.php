<?php

namespace App\Mail;

use App\Support\SiteSettings;

/**
 * Wording of transactional emails. Layout stays in code; Admin may
 * override subject, heading, intro and closing as plain text (setting options "mail_templates").
 */
class MailTemplates
{
    public const LABELS = [
        'consult_admin' => 'Yêu cầu tư vấn',
        'dealer_admin' => 'Đăng ký đại lý',
        'contact_admin' => 'Liên hệ mới',
        'contact_customer' => 'Xác nhận gửi khách liên hệ',
        'order_admin' => 'Đơn hàng gửi quản trị',
        'order_customer' => 'Xác nhận gửi khách hàng',
        'order_confirmed' => 'Báo khách: đơn đã được xác nhận',
        'order_shipping' => 'Báo khách: đơn đang giao',
        'order_paid' => 'Báo khách: đã nhận thanh toán',
    ];

    private const FIELDS = ['subject' => 180, 'heading' => 180, 'intro' => 700, 'closing' => 500];

    /**
     * Small status chip above the heading, who reads the mail and the step the order tracker has reached (-1 = no
     * tracker); fixed by the kind of mail, not edited in Admin.
     */
    private const FRAME = [
        'consult_admin' => ['Yêu cầu tư vấn mới', 'staff'],
        'dealer_admin' => ['Đăng ký đại lý mới', 'staff'],
        'contact_admin' => ['Liên hệ mới', 'staff'],
        'contact_customer' => ['Đã tiếp nhận yêu cầu', 'customer'],
        'order_admin' => ['Đơn hàng mới', 'staff'],
        'order_customer' => ['Đã tiếp nhận đơn hàng', 'customer'],
        'order_confirmed' => ['Đơn hàng đã xác nhận', 'customer', 1],
        'order_shipping' => ['Đơn hàng đang giao', 'customer', 2],
        'order_paid' => ['Đã nhận thanh toán', 'customer', -1],
    ];

    private const DEFAULTS = [
        'consult_admin' => [
            'subject' => '[Yêu cầu tư vấn mới] {customer_name} - {customer_phone}',
            'heading' => 'Phúc Thịnh Solar vừa nhận được yêu cầu tư vấn',
            'intro' => 'Khách hàng đã để lại thông tin và nhu cầu quan tâm trên website. Vui lòng kiểm tra nội dung bên dưới và chủ động liên hệ để tư vấn phương án phù hợp.',
            'closing' => 'Nếu khách hàng đã cung cấp email hợp lệ, anh/chị có thể bấm Trả lời để trao đổi trực tiếp.',
        ],
        'dealer_admin' => [
            'subject' => '[Đăng ký hợp tác đại lý] {company} - {customer_name}',
            'heading' => 'Phúc Thịnh Solar vừa nhận được đăng ký hợp tác',
            'intro' => 'Một doanh nghiệp hoặc cửa hàng đã gửi thông tin đăng ký trở thành đối tác của Phúc Thịnh Solar. Vui lòng kiểm tra hồ sơ và liên hệ để trao đổi chính sách phù hợp.',
            'closing' => 'Sau khi liên hệ, vui lòng cập nhật trạng thái xử lý trong Admin để đội ngũ thuận tiện theo dõi.',
        ],
        'contact_admin' => [
            'subject' => '[Liên hệ mới] {contact_subject}',
            'heading' => 'Phúc Thịnh Solar vừa nhận được liên hệ mới',
            'intro' => 'Khách hàng đã gửi nội dung liên hệ qua website. Thông tin người gửi và yêu cầu chi tiết được trình bày bên dưới để bộ phận phụ trách tiếp nhận.',
            'closing' => 'Nếu địa chỉ email của khách hàng hợp lệ, anh/chị có thể bấm Trả lời để phản hồi trực tiếp.',
        ],
        'contact_customer' => [
            'subject' => 'Phúc Thịnh Solar đã tiếp nhận yêu cầu của Quý khách',
            'heading' => 'Cảm ơn Quý khách đã tin tưởng Phúc Thịnh Solar',
            'intro' => 'Thông tin của Quý khách đã được ghi nhận và chuyển đến bộ phận phụ trách. Chúng tôi sẽ xem xét nhu cầu để hỗ trợ phương án phù hợp trong thời gian làm việc.',
            'closing' => 'Nếu cần hỗ trợ sớm hơn, Quý khách vui lòng liên hệ hotline được ghi ở cuối email.',
        ],
        'order_admin' => [
            'subject' => '[Đơn hàng mới #{order_code}] {customer_name}',
            'heading' => 'Phúc Thịnh Solar vừa nhận được đơn hàng mới',
            'intro' => 'Vui lòng kiểm tra danh sách sản phẩm, thông tin giao nhận và nhu cầu bổ sung trước khi liên hệ xác nhận với khách hàng.',
            'closing' => 'Nếu email trong đơn hàng hợp lệ, anh/chị có thể bấm Trả lời để trao đổi trực tiếp với khách hàng.',
        ],
        'order_customer' => [
            'subject' => 'Phúc Thịnh Solar đã tiếp nhận đơn hàng #{order_code}',
            'heading' => 'Cảm ơn Quý khách đã lựa chọn Phúc Thịnh Solar',
            'intro' => 'Đơn hàng #{order_code} đã được ghi nhận. Đội ngũ Phúc Thịnh Solar sẽ kiểm tra thông tin và liên hệ xác nhận trước khi giao hàng hoặc triển khai.',
            'closing' => 'Quý khách vui lòng lưu lại email này để thuận tiện đối chiếu khi cần hỗ trợ về đơn hàng.',
        ],
        'order_confirmed' => [
            'subject' => 'Phúc Thịnh Solar đã xác nhận đơn hàng #{order_code}',
            'heading' => 'Đơn hàng của Quý khách đã được xác nhận',
            'intro' => 'Đơn hàng #{order_code} đã được xác nhận. Chúng tôi sẽ tiếp tục chuẩn bị hàng và liên hệ Quý khách khi cần thống nhất thời gian giao hoặc triển khai.',
            'closing' => 'Nếu thông tin dưới đây chưa đúng, Quý khách vui lòng gọi hotline được ghi trong email.',
        ],
        'order_shipping' => [
            'subject' => 'Đơn hàng #{order_code} đang được giao',
            'heading' => 'Đơn hàng của Quý khách đang được giao',
            'intro' => 'Đơn hàng #{order_code} đã chuyển sang bước giao hàng. Quý khách vui lòng giữ liên lạc qua số điện thoại đã đặt hàng.',
            'closing' => 'Khi cần đổi thời gian hoặc địa điểm nhận, Quý khách vui lòng gọi hotline được ghi trong email.',
        ],
        'order_paid' => [
            'subject' => 'Phúc Thịnh Solar đã ghi nhận thanh toán đơn hàng #{order_code}',
            'heading' => 'Chúng tôi đã ghi nhận thanh toán của Quý khách',
            'intro' => 'Khoản thanh toán cho đơn hàng #{order_code} đã được ghi nhận đủ theo tổng tiền bên dưới.',
            'closing' => 'Email này không thay hóa đơn giá trị gia tăng. Quý khách cần hóa đơn vui lòng liên hệ hotline được ghi trong email.',
        ],
    ];

    public function __construct(private readonly SiteSettings $settings) {}

    /** @return array{subject: string, heading: string, intro: string, closing: string} */
    public static function defaults(string $key): array
    {
        return self::DEFAULTS[$key];
    }

    public function copy(string $key, array $values, ?array $draft = null): array
    {
        $stored = $draft ?? ($this->settings->options()['mail_templates'][$key] ?? []);
        $copy = [];
        $replace = [];
        foreach ($values as $token => $value) {
            $replace['{'.trim((string) $token, "{} \t\n\r\0\x0B").'}'] = is_scalar($value) ? trim((string) $value) : '';
        }
        foreach (self::FIELDS as $field => $max) {
            $override = is_array($stored) && isset($stored[$field]) && is_scalar($stored[$field])
                ? mb_substr(trim(strip_tags((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', trim((string) $stored[$field])))), 0, $max) : '';
            $copy[$field] = strtr($override !== '' ? $override : self::DEFAULTS[$key][$field], $replace);
        }
        $copy['subject'] = trim((string) preg_replace('/[\r\n]+/', ' ', $copy['subject']));
        [$copy['badge'], $copy['audience']] = self::FRAME[$key];
        $copy['step'] = self::FRAME[$key][2] ?? 0;

        return $copy;
    }
}
