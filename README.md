# Phúc Thịnh Solar — PHP website handover

Repository kế thừa của `phucthinhsolar.com`: storefront PHP server-rendered, CMS riêng và MariaDB. Đây không phải WordPress dù source còn nhiều asset WordPress cũ.

Mọi thay đổi nâng cấp hiện mới chạy trên Docker local. Không mặc định code trong Git đã được triển khai production.

## Kiến trúc nhanh

```text
HTTP -> public_html/.htaccess -> index.php -> libraries/router.php
     -> sources/*.php -> PDODb/MariaDB
     -> templates/*_tpl.php -> HTML
```

- Webroot: `domains/phucthinhsolar.com/public_html/`
- Frontend controller: `public_html/sources/`
- Frontend template: `public_html/templates/`
- API/AJAX: `public_html/api/`
- CMS: `public_html/administrator/`
- Cấu hình module: `public_html/libraries/config-type.php` và `libraries/type/`
- Migration thủ công: `migrations/`
- Tài liệu kỹ thuật: `docs/`

## Chạy local bằng Docker

Yêu cầu Docker Desktop/Docker Engine và Docker Compose.

```bash
docker compose -f docker-compose.local.yml up -d --build
docker compose -f docker-compose.local.yml ps
```

- Website: http://localhost:8080/
- CMS: http://localhost:8080/administrator/
- Web container: `pts-php74-local`
- Database container: `pts-mariadb-local`
- Database local: `phucthinhs_local`

Source được copy vào image, không bind mount. Sau khi sửa PHP/JS/CSS phải rebuild web:

```bash
docker compose -f docker-compose.local.yml build web
docker compose -f docker-compose.local.yml up -d web
```

Theo dõi lỗi:

```bash
docker compose -f docker-compose.local.yml logs -f web
docker compose -f docker-compose.local.yml logs -f db
```

Database được seed từ `phucthinhs_data-production-2026-08-26-before-ads.sql` khi volume được tạo lần đầu. Không chạy `down -v` hoặc import đè nếu chưa backup và xác nhận phạm vi dữ liệu.

## Migration local/production

Thứ tự hiện tại:

1. `001_add_product_cong_suat.sql`
2. `002_backfill_product_cong_suat.php`
3. `003_add_product_ads_content.sql`

Migration 003 bổ sung URL video YouTube, 8 thông số sản phẩm có cấu trúc và nội dung chứng nhận VI/EN. Luôn backup DB trước khi chạy migration production.

## Tính năng nâng cấp local

- Giá gốc, giá khuyến mãi và badge phần trăm giảm.
- Filter công suất và mức giá.
- Thêm giỏ, Mua ngay, checkout và trang cảm ơn.
- Review/ảnh feedback và xác minh khách đã mua.
- Quản trị đơn hàng và 5 trang chính sách.
- Video thực tế, thông số chi tiết và chứng nhận theo sản phẩm.

Trạng thái chi tiết: `docs/00-project/status.md`. Kế hoạch: `docs/ROADMAP.md`.

## Kiểm thử tối thiểu

Mỗi thay đổi commerce phải smoke test:

```text
category -> product -> add cart -> cart -> checkout -> thank-you -> admin order
```

Lint file PHP bằng đúng PHP 7.4 trong container. Kiểm tra cả desktop/mobile, AJAX filter, log PHP/Apache và dữ liệu DB sau test. Xóa hoặc hoàn nguyên toàn bộ dữ liệu kiểm thử giả.

## Production

Không upload/import trực tiếp khi chưa có phê duyệt. Trước deploy phải:

- Backup code và DB production.
- Đối chiếu config production, SMTP, PHP extensions và URL.
- Chạy migration theo thứ tự.
- Smoke test trên staging hoặc maintenance window.
- Chuẩn bị rollback bằng bản code/DB backup.

Security Review 2026-08-27 đang được hoãn theo yêu cầu chủ project; xem `docs/10-audits/2026-08-27-security-review.md`. Không coi nhánh hiện tại là production-ready trước khi xử lý và retest audit.

## Tài liệu nên đọc

1. `AGENTS.md`
2. `docs/README.md`
3. `docs/00-project/fact-map.md`
4. `docs/00-project/status.md`
5. `docs/02-development/local-setup.md`
6. `docs/06-operations/deployment.md`
7. `docs/13-handoffs/2026-08-26-codex-bugbot-fixes.md`
