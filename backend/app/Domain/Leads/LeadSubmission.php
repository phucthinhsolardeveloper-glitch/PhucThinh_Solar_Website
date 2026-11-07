<?php

namespace App\Domain\Leads;

use App\Domain\Catalog\CategoryTree;
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
 * Every request is one row in leads (kind contact / consultation / dealer) for the Admin to follow up.
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
        $saved = $this->insert(['kind' => 'contact', 'name' => $data['fullname'], 'phone' => $data['phone'], 'email' => $data['email'] ?: null, 'address' => $data['address'] ?: null,
            'subject' => $data['subject'], 'message' => $data['content']]);
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
        $db = DB::connection();
        $product = $productId > 0 ? (array) ($db->selectOne("select id, name as namevi, coalesce(pdp_profile,'') as pdp_profile, category_id from products where id=? and is_visible=1 limit 1", [$productId]) ?? []) : [];
        if ($product) {
            $root = $product['category_id'] ? app(CategoryTree::class)->atDepth((int) $product['category_id'], 1) : null;
            $product['list_slugvi'] = $root['slug'] ?? '';
            $product['list_pdp_profile'] = $root['pdp_profile'] ?? '';
        }
        $profileKey = $product ? $this->specs->profileKey(['pdp_profile' => $product['pdp_profile']], ['pdp_profile' => $product['list_pdp_profile'], 'slugvi' => $product['list_slugvi']]) : 'general';
        $needs = (array) ($this->specs->profile($profileKey)['needs'] ?? []);
        $place = $cityId > 0 && $wardId > 0 ? (array) ($db->selectOne('select p.name city_name, w.name ward_name from wards w inner join provinces p on p.id=w.province_id where w.id=? and w.province_id=? and w.is_visible=1 and p.is_visible=1 limit 1', [$wardId, $cityId]) ?? []) : [];

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
        $saved = $this->insert(['kind' => 'consultation', 'name' => $data['fullname'], 'phone' => $data['phone'], 'email' => $data['email'] ?: null, 'audience' => $data['loaihinh'] ?: null,
            'quantity' => $data['congsuat'] ?: null, 'address' => $address ?: null, 'product_id' => (int) $product['id'], 'profile_key' => $profileKey, 'need_code' => $data['need_code'] ?: null,
            'message' => $data['details'] ?: null, 'province_id' => $cityId ?: null, 'ward_id' => $wardId ?: null]);
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
        $db = DB::connection();
        $city = [];
        if ((int) $data['city_id'] > 0) {
            $city = (array) ($db->selectOne('select id,name from provinces where id=? and is_visible=1 limit 1', [(int) $data['city_id']]) ?? []);
        } elseif ($data['address'] !== '') {
            // Older forms send the province name instead of its id.
            $city = (array) ($db->selectOne('select id,name from provinces where name=? and is_visible=1 limit 1', [$data['address']]) ?? []);
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
        $saved = $this->insert(['kind' => 'dealer', 'name' => $data['fullname'], 'phone' => $data['phone'], 'email' => $data['email'], 'address' => $cityName,
            'province_id' => (int) $city['id'], 'company' => $data['company']]);
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
        if (! config('site.recaptcha.active')) {
            return true;
        }
        if (! is_scalar($token) || (string) $token === '') {
            return false;
        }
        try {
            $result = Http::asForm()->timeout(8)->post((string) config('site.recaptcha.verify_url'), ['secret' => config('site.recaptcha.secret'), 'response' => (string) $token])->json();
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

    private function insert(array $row): bool
    {
        try {
            return DB::table('leads')->insert($row + ['status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
        } catch (Throwable $e) {
            Log::error('Lead insert failed', ['error' => $e->getMessage()]);

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
