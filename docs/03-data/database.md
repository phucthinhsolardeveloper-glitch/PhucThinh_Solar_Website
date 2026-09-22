# Database

Cập nhật: 2026-09-22. Số liệu bảng đo trực tiếp trên DB local và trên dump
production ngày 2026-09-17 (phpMyAdmin, MariaDB 10.4.24).

Tài liệu này thay bản cũ nói "48 bảng, toàn bộ MyISAM" — số đó đã lỗi thời.

---

## 1. Tổng quan

| Chỉ số | Giá trị |
|---|---|
| Số bảng | **54**, prefix `table_` |
| Engine | **47 MyISAM + 7 InnoDB** |
| Charset | `utf8mb4` toàn bộ |
| Foreign key | **không có** |
| Server cũ | MariaDB 10.4.24 (EOL) |
| Server VPS mới | MariaDB 10.11 LTS |

Chuyển sang 10.11 bằng **logical dump vào DB rỗng**. Không gắn trực tiếp data
directory của 10.4 vào container 10.11.

---

## 2. Số dòng thật (dump production 2026-09-17)

| Bảng | Dòng | Ghi chú |
|---|---:|---|
| `table_counter` | 99.180 | đếm lượt truy cập — dữ liệu dẫn xuất, chiếm phần lớn kích thước dump |
| `table_ward` | 10.599 | danh mục địa giới |
| `table_district` | 705 | danh mục địa giới |
| `table_seo` | 533 | |
| `table_product` | **417** | 415 đang hiển thị |
| `table_permission` | 379 | |
| `table_content_section` | 301 | khối mô tả sản phẩm dùng chung |
| `table_gallery` | 259 | |
| `table_news` | 88 | gồm mọi loại nội dung phân biệt bằng cột `type` |
| `table_user` | 1 | |
| `table_order`, `table_order_detail` | **0** | chưa có đơn hàng nào |
| `table_contact` | **0** | chưa có lượt gửi form nào |
| `table_member` | **0** | chưa có thành viên nào |

Ba bảng cuối bằng 0 là **sự thật của production**, không phải dump bị thiếu.
Website hiện hoạt động như catalogue/báo giá; liên hệ đi qua điện thoại/Zalo.
Trước khi bật thương mại thật phải kiểm lại đường ghi checkout — xem
[checkout-write-path.md](checkout-write-path.md).

`table_counter` 99.180 dòng là dữ liệu dẫn xuất. Có thể cân nhắc truncate để
dump nhẹ đi đáng kể, **nhưng phải hỏi chủ project trước** vì số liệu thống kê
trên trang đọc từ đây.

---

## 3. Nhóm bảng

| Nhóm | Bảng |
|---|---|
| Catalogue | `product`, `product_list`, `product_cat`, `product_item`, `product_sub`, `product_brand`, `product_sale`, `product_tags`, `product_project`, `product_campaign`, `product_campaign_scope`, `color`, `size`, `gallery`, `photo` |
| Nội dung | `news`, `news_cat`, `news_list`, `news_item`, `news_sub`, `news_tags`, `static`, `content_section`, `tags`, `seo`, `seopage`, `setting`, `setting_sort`, `url_redirect`, `lang` |
| Thương mại | `order`, `order_detail`, `order_status` |
| Người dùng / RBAC | `user`, `member`, `permission`, `permission_group`, `user_limit`, `user_log`, `user_online` |
| Tương tác | `comment`, `comment_photo`, `comment_video`, `contact`, `newsletter` |
| Hạ tầng | `city`, `district`, `ward`, `counter`, `sortable`, `sortable_detail`, `sortable_index`, `excel`, `pushonesignal` |

---

## 4. Ba quy ước phải biết

### 4.1 `status` là chuỗi nhiều cờ

Không phải enum. Giá trị thật: `"noibat,hienthi"`.

```sql
-- Đúng
where find_in_set('hienthi', status)
-- Sai: bỏ sót mọi dòng có nhiều cờ
where status = 'hienthi'
```

### 4.2 Đa ngữ bằng hậu tố cột

`namevi`/`nameen`, `descvi`/`descen`, `contentvi`/`contenten`,
`blocksvi`/`blocksen`. Code dùng `"name".$lang`. Fallback:
`coalesce(nullif(namevi,''), nameen)`.

### 4.3 `type` phân biệt loại nội dung trong cùng một bảng

`table_news` chứa **24 loại** (`tin-tuc`, `du-an`, `giai-phap`,
`he-thong-cua-hang`, `home-about`, `project-benefit`, ...). `table_static` chứa
12 loại. Loại nào có màn hình Admin là do khai báo trong
`libraries/type/config-type-*.php` — xem
[admin-guide.md](../07-features/admin-guide.md).

Hệ quả khi viết query: **luôn lọc theo `type`**, nếu không sẽ lấy lẫn nội dung
của trang khác.

---

## 5. Không có foreign key — hệ quả thực tế

Quan hệ chỉ là logic trong code (`order_detail.id_order → order.id`, phân cấp
sản phẩm, `gallery.id_parent`, permission group). DB **không** enforce.

- Xoá bản ghi cha có thể để lại dữ liệu mồ côi; phải xử lý bằng tay trong code.
- Không có `ON DELETE CASCADE`.
- 47 bảng MyISAM **không có transaction**: một luồng ghi nhiều bảng mà lỗi giữa
  đường sẽ để lại trạng thái nửa vời. Đường ghi checkout phải tự bù trừ — xem
  [checkout-write-path.md](checkout-write-path.md).

**Rủi ro MyISAM cần biết:** không transaction, không crash recovery, khoá ở mức
bảng. Chuyển sang VPS là thời điểm sạch nhất để cân nhắc đổi sang InnoDB (đang
restore vào DB rỗng), nhưng đây là **thay đổi có rủi ro riêng** và phải được
chủ project quyết, không tự làm kèm trong đợt chuyển host.

---

## 6. Migration

Không có framework tự động. Migration gồm **49 file SQL/PHP đánh số** trong
`migrations/`, chạy tay. Migration PHP phải mặc định dry-run và chỉ ghi khi có
tham số áp dụng rõ ràng.

Quy tắc:

1. Đánh số tăng dần, không sửa file đã áp dụng.
2. Idempotent: `ADD COLUMN IF NOT EXISTS`, `CREATE TABLE IF NOT EXISTS`.
3. Ghi câu rollback trong comment.
4. Khai báo vào `migrations/000_preflight_production.php` để kiểm được từ xa.
5. **Deploy migration trước code.** Query SELECT cột chưa tồn tại **lỗi DB**,
   không trả rỗng.
6. Không sửa dump canonical để làm cơ chế deploy.

Ví dụ đúng (`036_add_product_photo_feed.sql`):

```sql
ALTER TABLE `table_product`
    ADD COLUMN IF NOT EXISTS `photo_feed` VARCHAR(255) NULL DEFAULT NULL AFTER `photo_khuyenmai`;
-- Rollback: ALTER TABLE `table_product` DROP COLUMN `photo_feed`;
```

### Kiểm tra production còn thiếu migration nào

Chỉ đọc, không ghi:

```bash
# Trên VPS
web=$(docker compose ps -q web)
docker cp releases/<commit>/migrations/000_preflight_production.php "$web":/tmp/
docker exec "$web" php /tmp/000_preflight_production.php
```

Kết quả 2026-09-17 sau khi restore dữ liệu production lên VPS: **READY** — toàn
bộ 17 migration schema đã áp dụng, gồm `036 photo_feed`.

Các migration dữ liệu/PHP (002, 005, 006, 014, 015, 018, 019, 020, 023, 024,
027, 028) **không suy ra được bằng schema** — phải chạy dry-run và đọc kết quả.

---

## 7. Truy vấn từ code

Luôn qua `$d` (`libraries/class/class.PDODb.php`), `#_` là prefix placeholder.
Luôn dùng `?`, không nối chuỗi. Ví dụ và các helper xem
[codebase-guide § 4](../01-architecture/codebase-guide.md).

---

## 8. An toàn dữ liệu

Trước mọi thay đổi schema hoặc dữ liệu hàng loạt: export DB, snapshot
`upload/`, lưu checksum, để **ngoài** webroot và **ngoài** server, ghi lại thời
điểm và người chịu trách nhiệm restore. Không thử restore trên production.

Chi tiết: [data-safety.md](data-safety.md),
[backup-restore.md](../06-operations/backup-restore.md),
[vps-operations § 5](../06-operations/vps-operations.md).
