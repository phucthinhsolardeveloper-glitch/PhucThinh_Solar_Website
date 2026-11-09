<?php

namespace App\Domain\Catalog\Bulk;

/**
 * The product workbook, one column per entry: label, kind, target and limits.
 * "old" lists the headers of files already issued by the previous site, which must keep importing.
 */
final class BulkColumns
{
    public const CLEAR = '__CLEAR__';

    public const HIDE_SECTIONS = '[ẩn]';

    /** @return array<string, array{label: string, kind: string, field?: string, max?: int, limit?: int, required?: bool, locked?: bool, wide?: bool, old?: list<string>, import_only?: bool}> */
    public static function all(): array
    {
        return [
            'action' => ['label' => 'Thao tác', 'kind' => 'control'],
            'id' => ['label' => 'ID sản phẩm', 'kind' => 'control', 'locked' => true, 'old' => ['id', 'ID']],
            'expected_updated_at' => ['label' => 'Mốc cập nhật', 'kind' => 'control', 'import_only' => true],
            'code' => ['label' => 'Mã sản phẩm', 'kind' => 'text', 'field' => 'code', 'max' => 30, 'required' => true],
            'name' => ['label' => 'Tên sản phẩm', 'kind' => 'text', 'field' => 'name', 'max' => 255, 'required' => true, 'old' => ['namevi']],
            'slug' => ['label' => 'Đường dẫn', 'kind' => 'slug', 'field' => 'slug', 'max' => 200, 'old' => ['slugvi']],
            'category' => ['label' => 'Danh mục', 'kind' => 'category', 'field' => 'category_id', 'required' => true, 'old' => ['_cat_name', 'Danh mục cấp 2']],
            'category_parent' => ['label' => 'Danh mục cấp 1', 'kind' => 'control', 'import_only' => true, 'old' => ['_list_name']],
            'category_id' => ['label' => 'ID danh mục', 'kind' => 'control', 'import_only' => true, 'old' => ['id_cat', 'ID danh mục cấp 2']],
            'category_parent_id' => ['label' => 'ID danh mục cấp 1', 'kind' => 'control', 'import_only' => true, 'old' => ['id_list']],
            'brand' => ['label' => 'Thương hiệu', 'kind' => 'brand', 'field' => 'brand_id', 'old' => ['_brand_name']],
            'brand_id' => ['label' => 'ID thương hiệu', 'kind' => 'control', 'import_only' => true, 'old' => ['id_brand']],
            'pdp_profile' => ['label' => 'Kiểu trang sản phẩm', 'kind' => 'profile', 'field' => 'pdp_profile', 'old' => ['Profile trang chi tiết']],
            'power_watts' => ['label' => 'Công suất (W)', 'kind' => 'decimal', 'field' => 'power_watts', 'old' => ['cong_suat']],
            'regular_price' => ['label' => 'Giá gốc', 'kind' => 'money', 'field' => 'regular_price', 'old' => ['Giá bán']],
            'sale_price' => ['label' => 'Giá khuyến mãi', 'kind' => 'money', 'field' => 'sale_price', 'old' => ['Giá bán/khuyến mãi']],
            'status' => ['label' => 'Tình trạng website', 'kind' => 'control', 'import_only' => true],
            'is_visible' => ['label' => 'Hiển thị (1/0)', 'kind' => 'flag', 'field' => 'is_visible'],
            'is_bestseller' => ['label' => 'Bán chạy (1/0)', 'kind' => 'flag', 'field' => 'is_bestseller', 'old' => ['is_featured']],
            'quote_only' => ['label' => 'Liên hệ báo giá (1/0)', 'kind' => 'flag', 'field' => 'quote_only', 'old' => ['requires_quote']],
            'sort_order' => ['label' => 'Thứ tự', 'kind' => 'integer', 'field' => 'sort_order', 'old' => ['numb']],
            'summary' => ['label' => 'Mô tả ngắn', 'kind' => 'multiline', 'field' => 'summary', 'max' => 2000, 'wide' => true, 'old' => ['descvi']],
            'body' => ['label' => 'Ghi chú tư vấn (không hiện trên trang)', 'kind' => 'html', 'field' => 'body', 'wide' => true, 'old' => ['contentvi', 'Nội dung']],
            'highlights' => ['label' => 'Điểm nổi bật (Tiêu đề | Giải thích)', 'kind' => 'pairs', 'field' => 'highlights', 'limit' => 6, 'wide' => true, 'old' => ['highlightsvi']],
            'applications' => ['label' => 'Ứng dụng phù hợp (mỗi dòng một ý)', 'kind' => 'lines', 'field' => 'applications', 'limit' => 8, 'wide' => true, 'old' => ['applicationsvi']],
            'installation' => ['label' => 'Bộ sản phẩm / hướng dẫn (mỗi dòng một ý)', 'kind' => 'lines', 'field' => 'installation', 'limit' => 10, 'wide' => true, 'old' => ['installationvi']],
            'sections' => ['label' => 'Mô tả dài/FAQ — ## tiêu đề, - danh sách, [faq] Hỏi | Đáp', 'kind' => 'sections', 'wide' => true, 'old' => ['sectionsvi']],
            'purchase_policy' => ['label' => 'Chính sách mua hàng', 'kind' => 'html', 'field' => 'purchase_policy', 'wide' => true, 'old' => ['chinhsachmuahangvi']],
            'video_url' => ['label' => 'Video YouTube', 'kind' => 'youtube', 'field' => 'video_url', 'max' => 500],
            'light_source' => ['label' => 'Nguồn sáng', 'kind' => 'text', 'field' => 'light_source', 'max' => 255, 'old' => ['Nguồn sáng / quang thông']],
            'lamp_dimensions' => ['label' => 'Kích thước đèn', 'kind' => 'text', 'field' => 'lamp_dimensions', 'max' => 150],
            'color_temperature' => ['label' => 'Nhiệt độ màu (CCT)', 'kind' => 'text', 'field' => 'color_temperature', 'max' => 100, 'old' => ['Nhiệt độ màu']],
            'light_color' => ['label' => 'Màu ánh sáng', 'kind' => 'text', 'field' => 'light_color', 'max' => 100],
            'battery_capacity' => ['label' => 'Dung lượng pin', 'kind' => 'text', 'field' => 'battery_capacity', 'max' => 100],
            'panel_power' => ['label' => 'Công suất tấm pin', 'kind' => 'text', 'field' => 'panel_power', 'max' => 100],
            'material' => ['label' => 'Chất liệu', 'kind' => 'text', 'field' => 'material', 'max' => 255],
            'charging_time' => ['label' => 'Thời gian sạc', 'kind' => 'text', 'field' => 'charging_time', 'max' => 100],
            'lighting_time' => ['label' => 'Thời gian chiếu sáng', 'kind' => 'text', 'field' => 'lighting_time', 'max' => 100],
            'waterproof_rating' => ['label' => 'Chuẩn chống nước', 'kind' => 'text', 'field' => 'waterproof_rating', 'max' => 50, 'old' => ['Chống nước']],
            'led_chip' => ['label' => 'Chip LED', 'kind' => 'text', 'field' => 'led_chip', 'max' => 100],
            'lighting_area' => ['label' => 'Diện tích chiếu sáng', 'kind' => 'text', 'field' => 'lighting_area', 'max' => 100],
            'installation_height' => ['label' => 'Độ cao lắp đặt', 'kind' => 'text', 'field' => 'installation_height', 'max' => 100],
            'panel_dimensions' => ['label' => 'Kích thước tấm pin', 'kind' => 'text', 'field' => 'panel_dimensions', 'max' => 150],
            'package_contents' => ['label' => 'Bộ sản phẩm gồm', 'kind' => 'multiline', 'field' => 'package_contents', 'max' => 5000],
            'carton_quantity' => ['label' => 'Số lượng / thùng', 'kind' => 'text', 'field' => 'carton_quantity', 'max' => 100],
            'carton_dimensions' => ['label' => 'Kích thước thùng', 'kind' => 'text', 'field' => 'carton_dimensions', 'max' => 150],
            'weight' => ['label' => 'Cân nặng (gram)', 'kind' => 'text', 'field' => 'weight', 'max' => 100, 'old' => ['Trọng lượng']],
            'warranty' => ['label' => 'Bảo hành', 'kind' => 'text', 'field' => 'warranty', 'max' => 150],
            'additional_specs' => ['label' => 'Thông số khác (Mã | Tên | Giá trị)', 'kind' => 'specs', 'field' => 'additional_specs', 'wide' => true, 'old' => ['Thông số khác (Tên | Giá trị)']],
            'image' => ['label' => 'Ảnh chính', 'kind' => 'image', 'field' => 'image', 'max' => 255],
            'image_secondary' => ['label' => 'Ảnh phụ', 'kind' => 'image', 'field' => 'image_secondary', 'max' => 255],
            'feed_image' => ['label' => 'Ảnh Google Shopping', 'kind' => 'feed_image', 'field' => 'feed_image', 'max' => 255, 'old' => ['photo_feed', 'Tên file ảnh Shopping/quảng cáo - nền sạch, không logo/chữ (đã upload sẵn qua Quản lý file)']],
            'gallery' => ['label' => 'Thư viện ảnh (mỗi dòng một tên file)', 'kind' => 'gallery', 'wide' => true, 'old' => ['Thư viện ảnh']],
            'seo_title' => ['label' => 'Tiêu đề SEO', 'kind' => 'seo', 'field' => 'title', 'max' => 255],
            'seo_description' => ['label' => 'Mô tả SEO', 'kind' => 'seo', 'field' => 'description', 'max' => 500, 'wide' => true],
            'seo_keywords' => ['label' => 'Từ khóa SEO', 'kind' => 'seo', 'field' => 'keywords', 'max' => 1000],
            // Columns of older files that no longer do anything; accepted so those files still open.
            'ignored_photo' => ['label' => 'Tên ảnh hiện tại (chỉ đọc)', 'kind' => 'ignored', 'import_only' => true, 'old' => ['photo']],
            'ignored_promo' => ['label' => 'Khuyến mãi/Sự kiện (1/0)', 'kind' => 'ignored', 'import_only' => true, 'old' => ['is_promo']],
            'ignored_frame' => ['label' => 'Tên file khung khuyến mãi PNG (đã upload sẵn qua Quản lý file)', 'kind' => 'ignored', 'import_only' => true, 'old' => ['photo_khuyenmai']],
            'ignored_certificate' => ['label' => 'Chứng nhận/kiểm nghiệm', 'kind' => 'ignored', 'import_only' => true, 'old' => ['certificatevi']],
        ];
    }

    /** @return array<string, array<string, mixed>> columns written to an exported file */
    public static function exported(): array
    {
        return array_filter(self::all(), fn ($column) => empty($column['import_only']));
    }

    /** @return array<string, string> normalised header => column key */
    public static function headerMap(): array
    {
        $map = [];
        foreach (self::all() as $key => $column) {
            foreach ([$key, $column['label'], ...($column['old'] ?? [])] as $header) {
                $map[self::header((string) $header)] = $key;
            }
        }

        return $map;
    }

    public static function header(mixed $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $value))), 'UTF-8');
    }
}
