<?php

namespace App\Domain\Routing;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Content\PostVisibility;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * What the website does with a record right now, in one word staff can trust ("Trên website" column).
 * The switch alone ("Hiển thị") is not the answer: a scheduled post, a category without products or a product
 * under a hidden category are switched on but not (fully) on the site.
 */
final class PublicStatus
{
    public const LIVE = 'live';

    public const SCHEDULED = 'scheduled';

    public const HIDDEN = 'hidden';

    public const UNREACHABLE = 'unreachable';

    public const LABELS = [self::LIVE => 'Đang hiện', self::SCHEDULED => 'Hẹn giờ đăng', self::HIDDEN => 'Đang ẩn', self::UNREACHABLE => 'Bật nhưng chưa thấy'];

    public const COLORS = [self::LIVE => 'success', self::SCHEDULED => 'info', self::HIDDEN => 'gray', self::UNREACHABLE => 'warning'];

    /** @return array{key: string, label: string, color: string, reason: string} */
    public static function of(Model $record): array
    {
        [$key, $reason] = self::resolve($record);
        $label = self::LABELS[$key];
        if ($key === self::SCHEDULED && $record instanceof Post && $record->published_at) {
            $label .= ' '.Carbon::parse($record->published_at)->format('H:i d/m');
        }

        return ['key' => $key, 'label' => $label, 'color' => self::COLORS[$key], 'reason' => $reason];
    }

    /** @return array{0: string, 1: string} */
    private static function resolve(Model $record): array
    {
        if (array_key_exists('is_visible', $record->getAttributes()) && ! $record->getAttribute('is_visible')) {
            return [self::HIDDEN, 'Công tắc hiển thị đang tắt.'];
        }
        if ($record instanceof Post && PostVisibility::isScheduled($record)) {
            return [self::SCHEDULED, 'Tự hiện trên website khi đến ngày đăng.'];
        }
        if ($record instanceof Category && ! app(CategoryTree::class)->hasVisibleProducts((int) $record->getKey())) {
            return [self::UNREACHABLE, 'Danh mục chưa có sản phẩm nào đang hiện nên website báo không tìm thấy.'];
        }
        if ($record instanceof Product) {
            if (! $record->category_id) {
                return [self::UNREACHABLE, 'Chưa gắn danh mục: chỉ vào được bằng đường link trực tiếp.'];
            }
            foreach (app(CategoryTree::class)->chain((int) $record->category_id) as $node) {
                if (! $node['is_visible']) {
                    return [self::UNREACHABLE, 'Danh mục "'.$node['name'].'" đang ẩn: sản phẩm không có trong danh sách, chỉ vào được bằng link.'];
                }
            }
        }

        return [self::LIVE, PublicUrls::path($record) !== null ? 'Bấm để mở trang công khai.' : 'Đang được dùng trên website.'];
    }
}
