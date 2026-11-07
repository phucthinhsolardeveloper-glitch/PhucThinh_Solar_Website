<?php

namespace App\Domain\Reviews;

use App\Domain\Leads\LeadSubmission;
use App\Support\FormToken;
use App\Support\SiteSettings;
use App\Support\Text;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Product review from the product page (api/pts_v1_review.php + Comments::add): stars, text, up to
 * three photos and one video with poster. Reviews wait for Admin approval (status "pending"); a matching
 * order code (same product, phone and email) adds the "verified-purchase" badge.
 */
class ReviewSubmission
{
    private const PHOTO_TYPES = ['jpg', 'jpeg', 'png'];

    private const VIDEO_TYPES = ['mp4', 'mkv'];

    private const VIDEO_MAX_BYTES = 100 * 1024 * 1024;

    public function __construct(private readonly SiteSettings $settings) {}

    /** @return array{0: int, 1: array<string, mixed>} */
    public function handle(Request $request): array
    {
        $multipart = str_starts_with(strtolower((string) $request->header('Content-Type')), 'multipart/form-data');
        $body = $multipart ? $request->post() : json_decode($request->getContent(), true);
        if (! is_array($body)) {
            return [400, ['errors' => ['Dữ liệu không hợp lệ.']]];
        }
        if (! FormToken::valid($body['csrfToken'] ?? $body['form_csrf'] ?? null)) {
            return [419, ['errors' => ['Phiên gửi nhận xét không hợp lệ.']]];
        }
        $rateKey = 'form:review|'.$request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return [429, ['errors' => ['Bạn thao tác quá nhanh. Vui lòng thử lại sau.']]];
        }
        RateLimiter::hit($rateKey, 600);

        $input = $multipart && is_array($body['dataReview'] ?? null) ? $body['dataReview'] : $body;
        $productId = (int) (is_scalar($input['productId'] ?? $input['id_variant'] ?? null) ? ($input['productId'] ?? $input['id_variant']) : 0);
        $db = DB::connection();
        if ($productId <= 0 || ! $db->table('products')->where('id', $productId)->where('is_visible', true)->exists()) {
            return [404, ['errors' => ['Sản phẩm không tồn tại.']]];
        }
        $text = fn (string $key, int $max) => is_scalar($input[$key] ?? null) ? mb_substr(trim((string) $input[$key]), 0, $max) : '';
        $star = is_scalar($input['star'] ?? null) ? (int) $input['star'] : 0;
        if ($star < 1 || $star > 5) {
            return [422, ['errors' => ['Chưa chọn đánh giá sao.']]];
        }
        $data = ['title' => $text('title', 180), 'content' => $text('content', 5000), 'fullname' => $text('fullname', 100), 'phone' => $text('phone', 20), 'email' => $text('email', 150)];
        $orderCode = $text('orderCode', 40);
        $requireOrder = (string) ($this->settings->options()['review_require_order_code'] ?? '') === '1';
        $photos = array_slice(array_values(array_filter((array) $request->file('review-file-photo', []), fn ($f) => $f instanceof UploadedFile && $f->isValid())), 0, 4);
        $video = $request->file('review-file-video');
        $poster = $request->file('review-poster-video');

        $errors = [];
        if ($data['title'] === '') {
            $errors[] = 'Chưa nhập tiêu đề đánh giá';
        }
        if ($data['content'] === '') {
            $errors[] = 'Chưa nhập nội dung đánh giá';
        }
        if ($data['fullname'] === '') {
            $errors[] = 'Chưa nhập họ tên liên hệ';
        }
        if ($data['phone'] === '') {
            $errors[] = 'Chưa nhập số điện thoại liên hệ';
        } elseif (! LeadSubmission::isPhone($data['phone'])) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
        if ($requireOrder && $data['email'] === '') {
            $errors[] = 'Chưa nhập email liên hệ';
        }
        if ($data['email'] !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }
        $verified = false;
        if ($orderCode === '') {
            if ($requireOrder) {
                $errors[] = 'Chưa nhập mã đơn hàng';
            }
        } else {
            $order = $db->selectOne('select o.phone, o.email from orders o inner join order_items i on i.order_id = o.id where o.code = ? and i.product_id = ? limit 0,1', [$orderCode, $productId]);
            $digits = fn ($v) => (string) preg_replace('/[^0-9]/', '', (string) $v);
            $verified = $order && $digits($data['phone']) !== '' && $digits($data['phone']) === $digits((string) $order->phone)
                && $data['email'] !== '' && strtolower($data['email']) === strtolower(trim((string) $order->email));
            if (! $verified && $requireOrder) {
                $errors[] = 'Không tìm thấy đơn hàng phù hợp với sản phẩm và thông tin liên hệ';
            }
        }
        if (count($photos) > 3) {
            $errors[] = 'Hình ảnh không được vượt quá 3 hình';
        }
        foreach ($photos as $photo) {
            if (! self::isImage($photo)) {
                $errors[] = 'Hình ảnh chỉ nhận định dạng JPG hoặc PNG';
                break;
            }
        }
        $hasVideo = $video instanceof UploadedFile && $video->isValid();
        $hasPoster = $poster instanceof UploadedFile && $poster->isValid();
        if ($hasVideo && ! $hasPoster) {
            $errors[] = 'Hình đại diện video không được trống';
        }
        if (! $hasVideo && $hasPoster) {
            $errors[] = 'Tập tin video không được trống';
        }
        if ($hasVideo && ! in_array(strtolower($video->getClientOriginalExtension()), self::VIDEO_TYPES, true)) {
            $errors[] = 'Chi cho phép tập tin video với định dạng: '.implode(',', self::VIDEO_TYPES);
        }
        if ($hasVideo && $video->getSize() > self::VIDEO_MAX_BYTES) {
            $errors[] = 'Tập tin video không được vượt quá 100 MB';
        }
        if ($hasPoster && ! self::isImage($poster)) {
            $errors[] = 'Hình đại diện video chỉ nhận định dạng JPG hoặc PNG';
        }
        if ($errors) {
            return [422, ['errors' => $errors]];
        }

        // Waits for Admin approval before it shows on the product page.
        $row = ['product_id' => $productId, 'stars' => $star, 'title' => strip_tags($data['title']), 'content' => strip_tags($data['content']), 'name' => strip_tags($data['fullname']),
            'phone' => $data['phone'], 'email' => $data['email'] ?: null, 'verified_purchase' => $verified, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()];
        try {
            $db->transaction(function () use ($db, $row, $photos, $hasVideo, $video, $poster) {
                $id = (int) $db->table('reviews')->insertGetId($row);
                foreach ($photos as $photo) {
                    if (($name = self::store($photo, 'photo')) !== null) {
                        $db->table('review_media')->insert(['review_id' => $id, 'kind' => 'photo', 'path' => $name, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
                if ($hasVideo) {
                    $db->table('review_media')->insert(['review_id' => $id, 'kind' => 'video', 'path' => self::store($video, 'video') ?? '', 'poster' => self::store($poster, 'photo'), 'created_at' => now(), 'updated_at' => now()]);
                }
            });
        } catch (Throwable $e) {
            Log::error('Review save failed', ['error' => $e->getMessage()]);

            return [500, ['errors' => ['Gửi đánh giá thất bại. Vui lòng thử lại sau.']]];
        }

        return [200, ['ok' => true, 'message' => 'Đã nhận đánh giá. Nội dung sẽ hiển thị sau khi được duyệt.']];
    }

    /** Real JPEG or PNG content, whatever the file name claims. */
    private static function isImage(UploadedFile $file): bool
    {
        $info = @getimagesize($file->getRealPath());

        return is_array($info) && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) && in_array(strtolower($file->getClientOriginalExtension()), self::PHOTO_TYPES, true);
    }

    /** Saves under upload/<folder>/ with the PHP naming (slug of the name, dash, 4 digits). */
    private static function store(UploadedFile $file, string $folder): ?string
    {
        $name = Text::changeTitle(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $name .= '-'.random_int(1000, 9999).'.'.strtolower($file->getClientOriginalExtension());

        return Storage::disk('uploads')->putFileAs($folder, $file, $name) ? $name : null;
    }
}
