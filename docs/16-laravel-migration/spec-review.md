# Phản biện tài liệu đặc tả Laravel 13

Ngày 2026-11-07. Đối chiếu [LARAVEL13-MIGRATION-CLAUDE-MASTER-SPEC.md](../12-ai/LARAVEL13-MIGRATION-CLAUDE-MASTER-SPEC.md)
với code, database local và runtime thật. Tài liệu đặc tả do một AI khác viết; file này ghi
chỗ đúng, chỗ sai và chỗ đã điều chỉnh. Khi hai tài liệu khác nhau, file này thắng cho đến
khi đặc tả được sửa.

## 1. Giữ nguyên (đã kiểm chứng)

| Nhận định trong đặc tả | Bằng chứng |
|---|---|
| Giữ Nuxt và MariaDB, chuyển backend PHP sang Laravel, Admin sang Filament | Hợp lý: business logic là PHP, 59 bảng đều InnoDB |
| Laravel 13 và Filament 5 có sẵn | Composer: `laravel/framework` 13.40.0, `filament/filament` 5.11.1 |
| Chỉ 1 khóa ngoại thật | `table_product_campaign_scope.id_campaign → table_product_campaign.id` |
| `table_content_section` 462 dòng: product 413, cat 27, brand 13, list 9 | Khớp `phase-0/schema.md` |
| Giá đang là DOUBLE | `table_product`, `table_order`, `table_order_detail`, `table_ward`, `table_gallery` |
| Đăng nhập Admin có verifier cũ và rehash | `libraries/login_security.php`: md5 có secret và salt, `password_verify`, `password_needs_rehash` |
| Trạng thái các bridge ghi | `checkout.post` 410, `review.post` 410, `lead-form.post` chỉ 405, `cart.post` và `shipping-quote.post` chuyển tiếp sang PHP |
| Đọc trước, ghi sau; không dual-write; không đổi contract Nuxt; golden master và so sánh song song cho GET | Đúng cách giảm rủi ro |

## 2. Sai hoặc đã lỗi thời

| Đặc tả ghi | Thực tế |
|---|---|
| Mốc source `a8632eed`, production `04dde592c441` | Production đang chạy `2d151211`. Nhánh chuyển đổi lấy `master` mới nhất và được đồng bộ bằng `scripts/sync-laravel-branch.sh` |
| Còn worktree `pts-release-prod` | Đã xóa, commit của nó nằm trong `master` |
| 8.1: lỗi deploy chỉ vì quên bật container diễn tập | Còn hai nguyên nhân nữa: `docker.exe` không nhận biến môi trường của WSL, và trỏ Nuxt diễn tập vào `pts-nuxt-api-bridge` làm hỏng bài kiểm tra sửa/xóa tin tức vì bộ nhớ đệm riêng. Phải trỏ vào `pts-php81-local` |
| Phase 1 bật "Laravel Boost dev-only" như một hạng mục trung tính | Skeleton Laravel 13 tự sinh `AGENTS.md` và `CLAUDE.md` của Boost, có lệnh tải và chạy script cài PHP từ internet. Đã thay bằng hợp đồng riêng của dự án trong `backend/` |

## 3. Thiếu hoặc tự mâu thuẫn

1. **Thứ tự phase mâu thuẫn với quy tắc không dual-write.** Phase 5 làm Filament ngang
   Admin cũ (sản phẩm, danh mục, nội dung...) trong khi Admin cũ vẫn chạy, còn Phase 8 mới
   cho Laravel ghi. Filament không có quyền ghi thì không thể ngang Admin cũ. Nếu có quyền
   ghi thì hai Admin cùng ghi một bảng. Điều chỉnh: chuyển **từng domain một**. Domain nào
   sang Filament thì màn hình Admin cũ của domain đó chuyển sang chỉ đọc trong cùng một lần
   phát hành, có cờ bật tắt để quay lại.
2. **Đăng nhập Filament ở Phase 1 tạo một kho người dùng thứ hai.** Phase 1 đòi "Filament
   login skeleton", còn ánh xạ `table_user` mãi Phase 7 mới làm. Điều chỉnh: Phase 1 chỉ
   dùng tài khoản tạm trong bảng riêng của Laravel, chỉ có trên database bản sao, và ghi rõ
   sẽ bỏ. Đọc `table_user` qua verifier cũ chuyển lên làm ngay sau nền móng, trước khi
   Filament quản lý bất kỳ domain nào.
3. **Bảng của Laravel lẫn với bảng legacy.** Đặc tả không nói Laravel đặt `users`,
   `sessions`, `cache`, `jobs`, `migrations` ở đâu. Điều chỉnh: hai kết nối. Kết nối
   `legacy` không có tiền tố, đọc bảng `table_*`. Kết nối mặc định có tiền tố `lv_` cho mọi
   bảng Laravel tự tạo, nhìn tên là biết bảng nào thuộc ai.
4. **Phát hiện bảo mật không có trong đặc tả.** `administrator/sources/user.php` vẫn lưu mật
   khẩu thành viên (`table_member`) bằng `md5()` không salt (dòng 1080 và 1101). Bảng này
   hiện 0 dòng ở local. Cần kiểm số dòng trên production (chỉ đọc) và đưa vào lane bảo mật.
   Không chuyển cách băm này sang Laravel.
5. **Số lane song song quá sức máy.** Đặc tả chia 8 lane, mỗi lane một worktree. Máy có
   7,7GB RAM và Docker Desktop đã sập khi 3 lane cùng build. Điều chỉnh: tối đa 2 lane chạy
   cùng lúc, chỉ một lane được build container tại một thời điểm.
6. **Ảnh thu nhỏ.** Nuxt đang dùng đường dẫn `/thumbs/WxHxM/...` do PHP tạo khi có yêu cầu.
   Mục Media chỉ nói "legacy_thumbs disk". Cần quyết định trước Phase 4 ai tạo ảnh thu nhỏ
   khi PHP không còn trả các API đọc.

## 4. Phase 1 theo điều chỉnh

- `backend/` chạy Laravel 13.40, PHP 8.4 trong container `pts-laravel13-local`, cổng `127.0.0.1:8090`.
- Kết nối mặc định vào `pts_laravel13_clone` với user riêng `pts_laravel`, tiền tố `lv_`.
  Kết nối `legacy` vào cùng database, không tiền tố, chỉ đọc trong Phase 1.
- `/health` trả trạng thái app và một truy vấn đọc `table_product`.
- Filament 5 ở `/admin-next`, tài khoản tạm trong `lv_users`, chỉ trên bản sao.
- Pest, Pint, Larastan chạy trong container.
