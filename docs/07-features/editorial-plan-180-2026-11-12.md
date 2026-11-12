# Phúc Thịnh Solar — Kế hoạch 180 bài viết / 60 ngày

## Trạng thái triển khai

- **Phạm vi:** 13/11/2026 – 11/01/2027, 3 khung giờ/ngày: **08:00, 12:00, 19:30** (Asia/Ho_Chi_Minh).
- **Số đầu mục:** 180. Gồm **164 bài Kiến thức sản phẩm** và **16 bài Tin mới nhất**; không tự tạo tin truyền thông doanh nghiệp nếu không có hoạt động đã xác nhận.
- **Đã biên tập đủ thân bài và liên kết lịch:** 21 bài (PTS-KT-001..021). Các bài đều **is_visible=false**, dù có giờ dự kiến trong `published_at`; không công khai chỉ vì chạy bộ nhập.
- **Đang ở mức ý tưởng/nghiên cứu:** 159 bài (PTS-KT-022..180). Có tiêu đề, góc viết, nguồn tham khảo đầu vào, brief ảnh, khung giờ, trạng thái và bìa đồ họa thương hiệu; **chưa phải bài hoàn chỉnh**.
- **Hình:** 3 ảnh bìa bản gốc cho các bài đầu + 177 hình bìa đồ họa WebP 1600×900 từ script. Hình đồ họa có **logo dài chính thức** của Phúc Thịnh Solar; logo đứng được lưu làm tài nguyên khi dùng cho bố cục khác.
- **Source:** `backend/resources/editorial/2026-11-12/calendar-180.csv` (nguồn danh mục 180 chủ đề đã thống nhất, ngày đã chuyển về lịch hiện hành).
- **Admin:** `/admin/lich-bien-tap` để xem theo tháng; `/admin/editorial-plan-items` để tìm kiếm/lọc và chỉnh đầu mục.
- **Môi trường:** chỉ áp dụng lên database local; production chưa triển khai, chưa công khai bài.

## Quy trình vận hành cho 2–3 bài/ngày

1. Mở **Kế hoạch bài đăng**, chọn ngày, xem nguồn chính, dàn ý và hình bìa đề xuất.
2. Đối chiếu 1–2 nguồn gốc (đặc biệt dữ liệu hãng, luật và tin mới). Soạn nội dung đầy đủ và chọn ảnh: bìa đồ họa là bản concept; bài kỹ thuật cần có hình minh họa đúng cấu tạo hoặc sơ đồ được kiểm tra.
3. Gắn bài CMS thật qua trường **Bài viết trong Admin** hoặc tạo bản nháp. Nếu sửa ngày một đầu mục đã liên kết bài **đang ẩn**, trường ngày đăng của bài cũng được cập nhật.
4. Kiểm tra H2/H3, SEO, ảnh responsive, chú thích nguồn, link tới sản phẩm đúng danh mục và tình trạng bản quyền ảnh. Chuyển trạng thái kế hoạch từ `idea` → `research` → `writing` → `review` → `ready`.
5. Người có quyền **content_publish** duyệt và bật hiển thị cho bài đã đủ nội dung. Nếu ngày giờ chưa đến, API sẽ chỉ mở công khai vào thời điểm hẹn; nếu thời gian đã qua, sẽ công khai ngay sau duyệt.
6. Kiểm tra ảnh, canonical, sitemap, bài liên quan và hiệu năng sau khi công khai. Hằng tuần nhìn lại số liệu SEO/GA4 và làm mới bài kiến thức cũ trước khi tạo nhiều bài tương tự.

## Lưu ý nội dung

- Chuyên mục **Tin mới nhất** dùng số liệu và thông báo có ngày; kiểm tra lại sát ngày lên bài. Không đăng lại tin cũ như tin vừa xảy ra.
- **Tin truyền thông** chỉ lấy từ sự kiện, dự án, hoạt động được Phúc Thịnh Solar xác minh; không biến tin nghiên cứu quốc tế thành tin công ty.
- Bài về LONGi, Canadian Solar, JA Solar, inverter và pin lưu trữ phải đối chiếu đúng **mã model / phiên bản datasheet**; không copy thông số giữa các dòng.
- Bìa `pts-plan-NNN.webp` là đồ họa gợi ý có logo công ty, **không phải ảnh thực tế công trình hoặc hàng hóa đã kiểm chứng**. Trước khi lên bài cụ thể nên ưu tiên ảnh riêng của hãng có quyền sử dụng, ảnh chụp thực tế hoặc sơ đồ tự thiết kế.
- Khi sử dụng stock Pexels, phải lưu nguồn/tác giả/điều khoản và không gắn logo gây hiểu nhầm nguồn gốc công trình. Thông tin ảnh 3 bài đầu lưu trong `backend/resources/editorial/2026-11-11/photo-sources.json`.
- `calendar-180.csv` chứa URL nguồn làm **đầu mối nghiên cứu**, chưa phải xác nhận 180 URL đều còn sống hoặc dữ kiện đã được kiểm chứng.

## Source và quy trình deploy

**Thành phần:**

- Migration: `backend/database/migrations/2026_11_12_000100_create_editorial_plan_items_table.php`.
- Model: `backend/app/Models/EditorialPlanItem.php`.
- Admin: `backend/app/Filament/Resources/EditorialPlanItems` và `backend/app/Filament/Pages/EditorialCalendarPage.php`.
- Bộ nhập: `backend/app/Console/Commands/EditorialCalendarImport.php` (`pts:editorial-calendar`).
- Dữ liệu: `backend/resources/editorial/2026-11-12/calendar-180.csv`, `written-articles.json`, `articles/`.
- Media: `frontend/public/assets/images/editorial/pts-plan-004.webp` đến `pts-plan-180.webp`; logo chính thức trong `.../editorial/brand/`.
- Script tái tạo bìa: `node scripts/content/generate-editorial-plan-covers.mjs`.
- Script post-deploy: `deploy/laravel13/scripts/release-editorial-content.sh` (đã nối cả bộ cũ và lịch mới).

**Local (đã áp dụng):**

```bash
docker exec pts-laravel13-local php artisan pts:editorial-calendar
docker exec pts-laravel13-local php artisan pts:editorial-calendar --apply
```

**Production (chỉ chạy sau backup DB/upload và deploy image đã duyệt):**

```bash
# Xem trước — không ghi dữ liệu
bash deploy/laravel13/scripts/release-editorial-content.sh --preview
# Sau khi XÁC NHẬN backup, script sẽ chạy migration riêng, nhập 3 bài cũ,
# 18 bài bổ sung, 180 ô lịch và 180 ảnh bìa vào upload/news:
PTS_EDITORIAL_BACKUP_VERIFIED=1 bash deploy/laravel13/scripts/release-editorial-content.sh --apply
```

Không chạy lệnh này ngoài VPS/container đúng của project. Chỉ dùng khi đã backup hợp lệ và kiểm tra image release chứa đủ file CSV, Markdown và WebP.

**An toàn/lặp lại:**

- Import theo `plan_key` (PTS-KT-001..180), không tạo bản ghi trùng và không ghi đè lịch/ghi chú đã sửa trong Admin.
- Nội dung mới chỉ tạo Post khi có file Markdown đầy đủ, không tạo Post rỗng từ 159 ý tưởng còn lại.
- Bài mới giữ trạng thái ẩn dù có `published_at`; chuyển giờ trong kế hoạch không làm bật bài.
- Ảnh mới ở local được Laravel phục vụ qua `/upload/news/` từ nguồn tĩnh khi volume upload cũ chỉ đọc; production nhập vào upload dùng checksum để tránh ghi đè file khác.
- Nếu backup hoặc migration fail, **không tự động xuất bản**; rollback database và ảnh là bước riêng, không nhầm với rollback image Docker.

## Checklist kiểm duyệt trước khi cho xuất bản

- [ ] Bài đã có nội dung gốc tiếng Việt, giải thích rõ, không copy nguyên văn báo nước ngoài.
- [ ] Nguồn ngoài đã kiểm chứng; ngày phát hành nguồn chính xác; thông số có datasheet đúng mã.
- [ ] Ảnh bìa/logo/alt/chú thích đúng và không giả làm ảnh công trình công ty.
- [ ] Bài có mô tả SEO, heading đúng, link nội bộ phù hợp, không trùng với bài cũ.
- [ ] Người có quyền xuất bản kiểm tra và bật hiển thị đúng thời gian.
- [ ] Kiểm tra trang thực tế và hình ảnh sau khi công khai.

## Ảnh minh họa và ghi nguồn ngắn (12/11/2026)

- Hiện đã có 21 bản nháp đầy đủ; 159 đầu mục còn là ý tưởng. PTS-KT-010 đến PTS-KT-015 có 1 infographic riêng trong thân bài.
- Dưới ảnh bìa chỉ hiện một dòng: **Ảnh: Tác giả / Pexels** hoặc **Hình minh họa: Phúc Thịnh Solar**. Ảnh bên ngoài liên kết đến trang nguồn.
- Danh mục ghi nguồn: `backend/resources/editorial/2026-11-12/image-credits.json`. Các hình tự thiết kế dùng logo dài chính thức và không giả làm công trình thật.
- Ảnh đèn đường cũ được thay bằng phối hợp cảnh nhiều đèn và ảnh có bối cảnh sáng rõ; một ảnh sân vườn tối được giữ như ngoại lệ.
- `pts:editorial-calendar --apply` kiểm tra ảnh minh họa trong Markdown và chép sang upload khi deploy; bản local dùng nguồn Git-managed nếu volume upload đang chỉ đọc.

## Bản cập nhật chọn ảnh 12/11/2026

Đã chuyển 12 bài PTS-KT-004..015 từ bìa concept đồng dạng sang **11 ảnh chụp thật được tuyển chọn, 1 infographic kỹ thuật**. Ảnh đã được áp dụng vào 12 bản nháp ẩn trên local; không làm thay đổi trạng thái xuất bản. Các ảnh concept `pts-plan-*.webp` của 159 ý tưởng chưa viết chỉ phục vụ kế hoạch, không mặc định dùng làm bìa đăng bài.

Quy chuẩn hình ảnh mới: `docs/07-features/editorial-photo-first-guidelines.md`.

- Metadata nguồn và giấy phép: `backend/resources/editorial/2026-11-12/photo-cover-selections.json` và `image-credits.json`.
- Ảnh bìa mới: `frontend/public/assets/images/editorial/pts-photo-*.webp`.
- Script tạo lại: `node scripts/content/prepare-editorial-photo-covers.mjs`.
- Import `pts:editorial-calendar --apply` chỉ nâng cấp bìa generic trên bản nháp **chưa hiển thị** và chỉ khi chưa bị người biên tập sửa; bước deploy import hiện tại đã bao gồm media mới.

## Tiến độ Orca 12/11/2026: 21 bản nháp, sản phẩm liên quan

- PTS-KT-016..021 đã biên tập nội dung và SEO, có 2–3 nguồn tham khảo/bài. Ảnh bìa của sáu bài còn ở trạng thái needs_photo; bìa hiện tại là concept, chưa phải ảnh được duyệt.
- Đã loại lời nhắc làm infographic khỏi nội dung hiển thị PTS-KT-010..021; gợi ý ảnh riêng của 016..021 được lưu trong manifest hình.
- Công cụ liên kết sản phẩm đúng chủ đề (tối đa 4) có dry-run, chỉ áp dụng vào bản nháp ẩn chưa có lựa chọn thủ công; không gắn sản phẩm cho bài chỉ nói về thị trường chung. Mapping được lưu ở related-products.json, báo cáo ở related-products-report.md.
- Nút xem bài ẩn sử dụng signed preview có hạn, không bật công khai bài.
- Validate batch 010..015 và 016..021 trước mỗi lần release; không bỏ qua khâu duyệt ảnh.
- Đây là source master local; chưa có bước push hay deploy production.
