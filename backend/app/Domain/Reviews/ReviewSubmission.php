<?php

namespace App\Domain\Reviews;

use App\Domain\Admin\AdminBell;
use App\Domain\Leads\LeadSubmission;
use App\Filament\Resources\Reviews\ReviewResource;
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
 * Product review from the product page: stars, text, up to
 * three photos and one video with poster. Reviews wait for Admin approval (status "pending"); a matching
 * order code (same product, phone and email) adds the "verified-purchase" badge.
 */
class ReviewSubmission
{
    private const VIDEO_TYPES = ['mp4', 'mkv'];

    private const PHOTO_MAX_BYTES = 30 * 1024 * 1024;

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
        $photos = array_values((array) $request->file('review-file-photo', []));
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
            if (! $photo instanceof UploadedFile || ! $photo->isValid() || ! self::isImage($photo)) {
                $errors[] = 'Hình ảnh phải là JPG/PNG hợp lệ, tối đa 30 MB mỗi tệp';
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
        if ($video !== null && (! $hasVideo || ! self::isVideo($video))) {
            $errors[] = 'Video phải là MP4/MKV hợp lệ và không vượt quá 100 MB';
        }
        if ($poster !== null && (! $hasPoster || ! self::isImage($poster))) {
            $errors[] = 'Hình đại diện video phải là JPG/PNG hợp lệ, tối đa 30 MB';
        }
        if ($errors) {
            return [422, ['errors' => $errors]];
        }

        // Waits for Admin approval before it shows on the product page.
        $row = ['product_id' => $productId, 'stars' => $star, 'title' => strip_tags($data['title']), 'content' => strip_tags($data['content']), 'name' => strip_tags($data['fullname']),
            'phone' => $data['phone'], 'email' => $data['email'] ?: null, 'verified_purchase' => $verified, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()];
        $uploads = Storage::disk('uploads');
        $batch = '.review-staging/'.bin2hex(random_bytes(12));
        $staged = [];
        $media = [];
        $promoted = [];
        try {
            foreach ($photos as $photo) {
                $file = self::stage($photo, 'photo', $batch);
                $staged[] = $file;
                $media[] = ['kind' => 'photo', 'path' => $file['name'], 'poster' => null];
            }
            if ($hasVideo) {
                $clip = self::stage($video, 'video', $batch);
                $staged[] = $clip;
                $cover = self::stage($poster, 'photo', $batch);
                $staged[] = $cover;
                $media[] = ['kind' => 'video', 'path' => $clip['name'], 'poster' => $cover['name']];
            }
            $db->transaction(function () use ($db, $row, $uploads, $batch, $staged, $media, &$promoted) {
                $id = (int) $db->table('reviews')->insertGetId($row);
                foreach ($staged as $file) {
                    if ($uploads->exists($file['target']) || ! $uploads->move($batch.'/'.$file['name'], $file['target'])) {
                        throw new \RuntimeException('Review media promotion failed');
                    }
                    $promoted[] = $file['target'];
                }
                foreach ($media as $file) {
                    $db->table('review_media')->insert($file + ['review_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
                }
            });
        } catch (Throwable $e) {
            $uploads->delete($promoted);
            $uploads->deleteDirectory($batch);
            Log::error('Review save failed', ['type' => $e::class]);

            return [500, ['errors' => ['Gửi đánh giá thất bại. Vui lòng thử lại sau.']]];
        }
        $uploads->deleteDirectory($batch);
        $productName = (string) DB::table('products')->where('id', $productId)->value('name');
        AdminBell::ring('reviews', 'review:'.$productId.':'.md5($row['name'].$row['created_at']), 'Đánh giá mới chờ duyệt: '.$star.'★', $row['name'].' · '.mb_strimwidth($productName, 0, 90, '…'),
            ReviewResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'pending']]], isAbsolute: false, panel: 'admin'), 'heroicon-o-star', 'warning');

        return [200, ['ok' => true, 'message' => 'Đã nhận đánh giá. Nội dung sẽ hiển thị sau khi được duyệt.']];
    }

    /** Real JPEG or PNG content, whatever the file name claims. */
    private static function isImage(UploadedFile $file): bool
    {
        if ($file->getSize() <= 0 || $file->getSize() > self::PHOTO_MAX_BYTES) {
            return false;
        }
        $info = @getimagesize($file->getRealPath());
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        return is_array($info) && $info[0] > 0 && $info[1] > 0 && $info[0] * $info[1] <= 40_000_000
            && (($info[2] === IMAGETYPE_JPEG && $mime === 'image/jpeg' && in_array($extension, ['jpg', 'jpeg'], true))
                || ($info[2] === IMAGETYPE_PNG && $mime === 'image/png' && $extension === 'png'));
    }

    /** Fileinfo plus container header rejects a text file renamed to .mp4 or .mkv. */
    private static function isVideo(UploadedFile $file): bool
    {
        if ($file->getSize() < 16 || $file->getSize() > self::VIDEO_MAX_BYTES) {
            return false;
        }
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::VIDEO_TYPES, true)) {
            return false;
        }
        $handle = @fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            return false;
        }
        try {
            $header = (string) fread($handle, 4096);
            if ($extension === 'mkv') {
                return in_array($file->getMimeType(), ['video/x-matroska', 'application/x-matroska'], true)
                    && str_starts_with($header, "\x1a\x45\xdf\xa3") && str_contains($header, 'matroska') && str_contains($header, "\x18\x53\x80\x67");
            }
            if (! in_array($file->getMimeType(), ['video/mp4', 'application/mp4'], true) || substr($header, 4, 4) !== 'ftyp') {
                return false;
            }

            return self::hasMp4MediaBoxes($handle, (int) $file->getSize());
        } finally {
            fclose($handle);
        }
    }

    /** Read box headers without loading a 100 MB video into PHP memory. */
    private static function hasMp4MediaBoxes($handle, int $size): bool
    {
        $offset = 0;
        $boxes = 0;
        $hasMovie = $hasMedia = false;
        while ($offset + 8 <= $size) {
            if (++$boxes > 1024) {
                return false;
            }
            if (fseek($handle, $offset) !== 0) {
                return false;
            }
            $box = (string) fread($handle, 16);
            if (strlen($box) < 8) {
                return false;
            }
            $length = (int) unpack('N', substr($box, 0, 4))[1];
            $type = substr($box, 4, 4);
            if ($offset === 0 && $type !== 'ftyp') {
                return false;
            }
            if ($length === 1) {
                if (strlen($box) < 16) {
                    return false;
                }
                $parts = unpack('N2', substr($box, 8, 8));
                $length = ((int) $parts[1] << 32) | (int) $parts[2];
            } elseif ($length === 0) {
                $length = $size - $offset;
            }
            if ($length < ($offset === 0 ? 16 : 8) || $offset + $length > $size) {
                return false;
            }
            $hasMovie = $hasMovie || $type === 'moov';
            $hasMedia = $hasMedia || $type === 'mdat';
            $offset += $length;
        }

        return $offset === $size && $hasMovie && $hasMedia;
    }

    /** Keep uncommitted review uploads outside public paths until the DB transaction can save them. */
    private static function stage(UploadedFile $file, string $folder, string $batch): array
    {
        $name = mb_substr(Text::changeTitle(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file', 0, 70);
        $name .= '-'.bin2hex(random_bytes(8)).'.'.strtolower($file->getClientOriginalExtension());
        if (! Storage::disk('uploads')->putFileAs($batch, $file, $name)) {
            throw new \RuntimeException('Review media staging failed');
        }

        return ['name' => $name, 'target' => $folder.'/'.$name];
    }
}
