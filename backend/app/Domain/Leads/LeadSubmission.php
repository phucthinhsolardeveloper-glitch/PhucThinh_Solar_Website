<?php

namespace App\Domain\Leads;

use App\Domain\Catalog\ProductDetail\ProductSpecs;
use App\Mail\AdminNotification;
use App\Mail\MailTemplates;
use App\Support\FormToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * POST /api/v1/lead-form (api/pts_v1_lead_form.php POST branch): contact requests, product
 * consultations and dealer sign-ups. Rows go to the same tables the PHP Admin lists
 * (table_contact, table_newsletter), stored HTML-escaped as the Admin expects.
 */
class LeadSubmission
{
    private const KINDS = [
        'contact' => ['rate' => 'contact', 'captcha' => 'contact', 'mail' => 'contact_admin'],
        'product' => ['rate' => 'newsletter-consulting', 'captcha' => 'Newsletter', 'mail' => 'consult_admin'],
        'dealer' => ['rate' => 'newsletter-dealer', 'captcha' => 'Newsletter2', 'mail' => 'dealer_admin'],
    ];

    public function __construct(private readonly ProductSpecs $specs, private readonly MailTemplates $templates) {}

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(mixed $body, string $ip): array
    {
        if (! is_array($body)) {
            return [400, ['error' => ['code' => 'BAD_BODY']]];
        }
        $kind = isset($body['kind']) && is_scalar($body['kind']) ? trim((string) $body['kind']) : '';
        if (! isset(self::KINDS[$kind])) {
            return [400, ['error' => ['code' => 'BAD_KIND']]];
        }
        if (! FormToken::valid($body['csrfToken'] ?? null)) {
            return [419, ['error' => ['code' => 'INVALID_SESSION', 'message' => 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.']]];
        }
        $rateKey = 'form:'.self::KINDS[$kind]['rate'].'|'.$ip;
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return [429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau.']]];
        }
        RateLimiter::hit($rateKey, 600);
        if (! $this->captchaPassed($body['recaptchaToken'] ?? '', self::KINDS[$kind]['captcha'])) {
            return [422, ['error' => ['code' => 'CAPTCHA_FAILED', 'message' => 'Xác thực chống spam thất bại. Vui lòng thử lại.']]];
        }
        $input = is_array($body['data'] ?? null) ? $body['data'] : [];

        return match ($kind) {
            'contact' => $this->contact($input),
            'product' => $this->consultation($input),
            'dealer' => $this->dealer($input),
        };
    }

    private function contact(array $input): array
    {
        $data = self::values($input, ['fullname' => 100, 'phone' => 20, 'email' => 150, 'address' => 255, 'subject' => 180, 'content' => 5000]);
        $errors = [];
        if ($data['fullname'] === '') {
            $errors[] = 'Họ tên không được trống';
        }
        if (! self::isPhone($data['phone'])) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
        if ($data['email'] !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }
        if ($data['content'] === '') {
            $errors[] = 'Nội dung không được trống';
        }
        if ($errors) {
            return self::invalid($errors);
        }
        $data['subject'] = $data['subject'] ?: 'Yêu cầu tư vấn từ website';
        $saved = $this->insert('table_contact', self::escaped($data) + ['date_created' => time(), 'numb' => 1]);
        if (! $saved) {
            return [500, ['error' => ['code' => 'SAVE_FAILED', 'message' => 'Gửi liên hệ thất bại. Vui lòng thử lại sau.']]];
        }
        $this->notify('contact_admin', ['customer_name' => $data['fullname'], 'customer_phone' => $data['phone'], 'contact_subject' => $data['subject']],
            ['Họ tên' => $data['fullname'], 'Điện thoại' => $data['phone'], 'Email' => $data['email'], 'Địa chỉ' => $data['address'], 'Nhu cầu' => $data['subject'], 'Nội dung' => $data['content']],
            $data['email'], $data['fullname']);

        return [200, ['ok' => true, 'message' => 'Gửi yêu cầu tư vấn thành công. Chúng tôi sẽ liên hệ với bạn sớm.']];
    }

    private function consultation(array $input): array
    {
        $data = self::values($input, ['fullname' => 100, 'phone' => 20, 'email' => 150, 'loaihinh' => 180, 'congsuat' => 255, 'product_id' => 12, 'profile_key' => 32, 'need_code' => 64, 'details' => 1000, 'city_id' => 12, 'ward_id' => 12]);
        $productId = (int) $data['product_id'];
        $cityId = (int) $data['city_id'];
        $wardId = (int) $data['ward_id'];
        $db = DB::connection('legacy');
        $product = $productId > 0 ? (array) ($db->selectOne("select p.id,p.namevi,p.pdp_profile,pl.slugvi list_slugvi,pl.pdp_profile list_pdp_profile from table_product p left join table_product_list pl on pl.id=p.id_list where p.id=? and find_in_set('hienthi',p.status) and p.type='san-pham' limit 1", [$productId]) ?? []) : [];
        $profileKey = $product ? $this->specs->profileKey(['pdp_profile' => $product['pdp_profile']], ['pdp_profile' => $product['list_pdp_profile'], 'slugvi' => $product['list_slugvi']]) : 'general';
        $needs = (array) ($this->specs->profile($profileKey)['needs'] ?? []);
        $place = $cityId > 0 && $wardId > 0 ? (array) ($db->selectOne("select c.name city_name,w.name ward_name from table_ward w inner join table_city c on c.id=w.id_city where w.id=? and w.id_city=? and w.id_district=0 and find_in_set('hienthi',w.status) and find_in_set('hienthi',c.status) limit 1", [$wardId, $cityId]) ?? []) : [];

        $errors = [];
        if ($data['fullname'] === '') {
            $errors[] = 'Vui lòng nhập họ và tên';
        }
        if (! self::isPhone($data['phone'])) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
        if ($data['email'] !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }
        if (! $product) {
            $errors[] = 'Sản phẩm cần tư vấn không hợp lệ';
        }
        if ($data['profile_key'] !== $profileKey) {
            $errors[] = 'Nhóm tư vấn không hợp lệ';
        }
        if ($data['need_code'] !== '' && ! isset($needs[$data['need_code']])) {
            $errors[] = 'Nhu cầu tư vấn không hợp lệ';
        }
        if (($cityId > 0 || $wardId > 0) && ! $place) {
            $errors[] = 'Phường/Xã không thuộc Tỉnh/Thành phố đã chọn';
        }
        if ($errors) {
            return self::invalid($errors);
        }
        $address = $place ? $place['ward_name'].', '.$place['city_name'] : '';
        $needLabel = $data['need_code'] !== '' ? (string) $needs[$data['need_code']] : '';
        $saved = $this->insert('table_newsletter', self::escaped(['fullname' => $data['fullname'], 'phone' => $data['phone'], 'email' => $data['email'], 'loaihinh' => $data['loaihinh'],
            'congsuat' => $data['congsuat'], 'address' => $address]) + ['product_id' => (int) $product['id'], 'profile_key' => $profileKey, 'need_code' => $data['need_code'],
                'details' => htmlspecialchars($data['details']), 'city_id' => $cityId, 'ward_id' => $wardId, 'date_created' => time(), 'type' => 'dangkynhantin']);
        if (! $saved) {
            return [500, ['error' => ['code' => 'SAVE_FAILED', 'message' => 'Đăng ký tư vấn thất bại. Vui lòng thử lại sau.']]];
        }
        $this->notify('consult_admin', ['customer_name' => $data['fullname'], 'customer_phone' => $data['phone']], [
            'Họ tên' => $data['fullname'], 'Điện thoại' => $data['phone'], 'Email' => $data['email'], 'Đối tượng tư vấn' => $data['loaihinh'], 'Sản phẩm' => (string) $product['namevi'],
            'Nhu cầu' => $needLabel, 'Số lượng dự kiến' => $data['congsuat'], 'Thông tin thêm' => $data['details'], 'Địa chỉ' => $address,
        ], $data['email'], $data['fullname']);

        return [200, ['ok' => true, 'message' => 'Đăng ký tư vấn thành công. Chúng tôi sẽ liên hệ với bạn sớm.']];
    }

    private function dealer(array $input): array
    {
        $data = self::values($input, ['fullname' => 100, 'phone' => 20, 'email' => 150, 'city_id' => 12, 'address' => 180, 'company' => 180]);
        $db = DB::connection('legacy');
        $city = [];
        if ((int) $data['city_id'] > 0) {
            $city = (array) ($db->selectOne("select id,name from table_city where id=? and find_in_set('hienthi',status) limit 1", [(int) $data['city_id']]) ?? []);
        } elseif ($data['address'] !== '') {
            // Older forms send the province name instead of its id.
            $city = (array) ($db->selectOne("select id,name from table_city where name=? and find_in_set('hienthi',status) limit 1", [$data['address']]) ?? []);
        }
        $errors = [];
        if ($data['fullname'] === '') {
            $errors[] = 'Vui lòng nhập họ và tên';
        }
        if (! self::isPhone($data['phone'])) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }
        if (! isset($city['name']) || ! is_scalar($city['name'])) {
            $errors[] = 'Vui lòng chọn tỉnh/thành phố hợp lệ';
        }
        if ($data['company'] === '') {
            $errors[] = 'Vui lòng nhập tên doanh nghiệp/cửa hàng';
        }
        if ($errors) {
            return self::invalid($errors);
        }
        $cityName = trim(strip_tags((string) $city['name']));
        $saved = $this->insert('table_newsletter', self::escaped(['fullname' => $data['fullname'], 'phone' => $data['phone'], 'email' => $data['email'], 'address' => $cityName, 'company' => $data['company']])
            + ['date_created' => time(), 'type' => 'dangkynhantin2']);
        if (! $saved) {
            return [500, ['error' => ['code' => 'SAVE_FAILED', 'message' => 'Đăng ký đại lý thất bại. Vui lòng thử lại sau.']]];
        }
        $this->notify('dealer_admin', ['customer_name' => $data['fullname'], 'customer_phone' => $data['phone'], 'company' => $data['company']],
            ['Họ tên' => $data['fullname'], 'Điện thoại' => $data['phone'], 'Email' => $data['email'], 'Tỉnh/Thành phố' => $cityName, 'Doanh nghiệp/Cửa hàng' => $data['company']],
            $data['email'], $data['fullname']);

        return [200, ['ok' => true, 'message' => 'Đăng ký đại lý thành công. Chúng tôi sẽ liên hệ với bạn sớm.']];
    }

    /** Google reCAPTCHA v3 when switched on (score 0.5, matching action); always passes when off, as in PHP. */
    private function captchaPassed(mixed $token, string $action): bool
    {
        if (! config('legacy.recaptcha.active')) {
            return true;
        }
        if (! is_scalar($token) || (string) $token === '') {
            return false;
        }
        try {
            $result = Http::asForm()->timeout(8)->post((string) config('legacy.recaptcha.verify_url'), ['secret' => config('legacy.recaptcha.secret'), 'response' => (string) $token])->json();
        } catch (Throwable) {
            return false;
        }

        return is_array($result) && (float) ($result['score'] ?? 0) >= 0.5 && ($result['action'] ?? '') === $action;
    }

    /** A mail failure never loses the lead: the row is already saved and Admin lists it. */
    private function notify(string $template, array $values, array $fields, string $replyEmail, string $replyName): void
    {
        $to = (string) config('mail.admin_to');
        if ($to === '') {
            return;
        }
        try {
            Mail::to($to)->send(new AdminNotification($this->templates->copy($template, $values), $fields, $replyEmail, $replyName));
        } catch (Throwable $e) {
            Log::warning('Lead notification email failed', ['template' => $template]);
        }
    }

    private function insert(string $table, array $row): bool
    {
        try {
            return DB::connection('legacy')->table($table)->insert($row);
        } catch (Throwable $e) {
            Log::error('Lead insert failed', ['table' => $table, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** @return array<string, string> trimmed, length-capped scalar values (ptsLeadValue) */
    private static function values(array $input, array $limits): array
    {
        $out = [];
        foreach ($limits as $key => $max) {
            $out[$key] = isset($input[$key]) && is_scalar($input[$key]) ? mb_substr(trim((string) $input[$key]), 0, $max) : '';
        }

        return $out;
    }

    private static function escaped(array $values): array
    {
        return array_map(fn ($value) => htmlspecialchars((string) $value), $values);
    }

    /** Vietnamese mobile number, 10 digits (Functions::isPhone). */
    public static function isPhone(string $number): bool
    {
        $number = trim($number);

        return (bool) preg_match('/(03|05|07|08|09|01[2|6|8|9])+([0-9]{8})\b/', $number) && strlen($number) === 10;
    }

    private static function invalid(array $errors): array
    {
        return [422, ['error' => ['code' => 'VALIDATION_FAILED', 'message' => implode('. ', $errors).'.']]];
    }
}
