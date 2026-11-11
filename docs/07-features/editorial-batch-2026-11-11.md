# Editorial release 11/11/2026 — Phúc Thịnh Solar

## Trạng thái

- Source-controlled content batch: `backend/resources/editorial/2026-11-11/manifest.json` và 3 tệp Markdown tại `articles/`.
- Ảnh: **11 WebP 1600×900** trong `frontend/public/assets/images/editorial/`: 8 ảnh bìa nguồn Pexels và **3 infographic tự biên soạn** (sơ đồ hệ thống, thông số LONGi, biểu đồ IEA PVPS).
- Ledger ảnh Pexels: `backend/resources/editorial/2026-11-11/photo-sources.json`. File SVG gốc của infographic trong `backend/resources/editorial/2026-11-11/figures-src/` để sửa chính xác số liệu về sau.
- Logo nhận diện do công ty cung cấp được chuẩn hóa từ file PNG gốc: `frontend/public/assets/images/editorial/brand/logo-dai.png` (ngang) và `logo-vua.png` (bố cục đứng). **Ba infographic dùng logo dài đúng hình gốc**, nhúng vào SVG để không mất logo khi tái tạo. Script `scripts/content/generate-editorial-infographics.mjs` sinh ảnh WebP đồng bộ.
- Quy tắc thiết kế: ưu tiên logo dài trong header infographic và logo đứng khi có đủ chiều cao; không thay logo bằng chữ gõ hoặc AI tự dựng lại; không chèn logo lên ảnh Pexels theo cách gây hiểu nhầm ảnh thuộc công trình Phúc Thịnh Solar.
- Artisan command: `pts:editorial-batch`.
- Local DB đã được apply ngày 11/11/2026: **3 bài mới chưa hiển thị** (mỗi bài có 1 infographic trong thân bài), thay 5 ảnh bìa cũ và **loại bỏ 2 banner cũ trùng lặp ở cuối thân bài** theo hash đối chiếu. Các ảnh sản phẩm/chứng nhận ở bài khác không thay đổi.
- Production **CHƯA apply**. Source, DB local và production là ba lớp riêng.

## Danh sách bài mới

| Slug | Chuyên mục | Trạng thái nhập |
|---|---|---|
| `dien-mat-troi-hoat-dong-nhu-the-nao` | Kiến thức sản phẩm | Bản nháp, chưa công khai |
| `cach-doc-datasheet-tam-pin-nang-luong-mat-troi` | Kiến thức sản phẩm | Bản nháp, chưa công khai |
| `thi-truong-dien-mat-troi-toan-cau-2026-iea-pvps` | Tin mới nhất | Bản nháp, chưa công khai |

Bài về số liệu quốc tế phân biệt dữ liệu lắp đặt **2025** và báo cáo phát hành **2026**. Datasheet LONGi là ví dụ tham khảo, cần đối chiếu model/tài liệu hiện hành trước khi dùng làm tài liệu thiết kế.

## Ảnh cũ được thay

Bộ nhập thay ảnh của 5 bài kiến thức đèn NLMT khi và **chỉ khi** `posts.image` vẫn đúng tên ảnh đã kiểm tra trong manifest. Nếu ảnh đã được người khác sửa, bộ nhập bỏ qua; không ghi đè. Hai banner cũ trùng ở cuối bài `top-4-loi-ich...` và `4-luu-y...` chỉ bị loại khỏi HTML khi hash toàn thân bài còn khớp chính xác bản đã audit. Hai bài đèn liên quan chứng nhận/chip LED được giữ ảnh cũ vì các khẳng định hoặc hình sản phẩm đó cần bằng chứng đúng mã hàng, không thay bằng stock.

Ảnh Pexels dùng làm **minh họa chung**. Không chú thích ảnh stock là công trình, kỹ thuật viên, chứng nhận, sản phẩm thương mại chính xác của Phúc Thịnh Solar. Điều khoản: <https://www.pexels.com/license/>.

## Cơ chế media

- Local: volume `/srv/upload` cố ý read-only. Laravel `ProductMediaUrls` tìm ảnh `pts-*.webp` trong `/srv/assets/images/editorial/` nếu thiếu file dưới `/srv/upload/news/`; không sửa volume.
- Production: bộ nhập chép 11 ảnh vào `shared/upload/news` nếu chưa tồn tại, đối chiếu SHA-256 để không ghi đè ảnh cùng tên khác nội dung.
- Giữ URL public chuẩn `/upload/news/pts-*.webp`; website/SEO và admin tiếp tục dùng chung API.
- Không thay đổi các ảnh/tài liệu hãng hoặc ảnh công trình khác.
- Tệp trong `frontend/public/assets` phải được đóng gói theo release. File ảnh được lưu trong Git để deploy tái lập được.

## Triển khai trên local

```bash
cd /home/admin01/src/Web_PTS_PHP
docker exec pts-laravel13-local php artisan pts:editorial-batch
docker exec pts-laravel13-local php artisan pts:editorial-batch --apply
# Chỉ khi đồng bộ đúng bản nháp cũ chưa ai sửa (so hash):
docker exec pts-laravel13-local php artisan pts:editorial-batch --apply --refresh-unchanged-drafts
```

Chạy lại `--apply` không tạo bản ghi trùng, không ghi đè thân bài đã sửa trong CMS. `--refresh-unchanged-drafts` chỉ hoạt động khi bản nháp chưa công khai và hash nội dung cũ đúng với manifest. Hai banner cũ sẽ không bị xóa nếu nội dung đã được sửa sau khi audit. Không chạy `pts:import-legacy --fresh` để đưa content mới lên production.

## Quy trình phát hành production (PHẢI thực hiện sau deploy code)

1. Chỉ deploy từ commit release đã duyệt, chạy SAFE/AUTO theo runbook hiện hành; không push/deploy tự động chỉ để phát hành bài.
2. Chạy backup DB/upload đã kiểm tra và lưu điểm khôi phục phù hợp trước thay đổi dữ liệu.
3. Xác nhận container backend đang chạy image từ release chứa manifest + 11 ảnh.
4. Xem trước, kiểm tra số liệu và slug (không ghi DB):
   ```bash
   docker exec pts-laravel13-backend php artisan pts:editorial-batch
   ```
5. Sau khi kết quả dry-run đúng mới áp dụng:
   ```bash
   docker exec pts-laravel13-backend php artisan pts:editorial-batch --apply
   # Script an toàn để thao tác cùng lần deploy (không tự chạy chỉ vì thay image):
   bash deploy/laravel13/scripts/release-editorial-content.sh --preview
   # Chỉ sau khi đã xác nhận backup DB + upload hợp lệ:
   PTS_EDITORIAL_BACKUP_VERIFIED=1 bash deploy/laravel13/scripts/release-editorial-content.sh --apply
   ```
6. Kiểm tra 3 bài mới trong Admin ở trạng thái **ẩn**; kiểm tra 5 bài cũ trên website có cover HTTP 200, console không lỗi ảnh; soát SEO và ảnh chia sẻ.
7. Duyệt nội dung, liên kết sản phẩm nếu phù hợp, rồi xuất bản từng bài trong CMS. **Không bật hiển thị chỉ vì đã deploy.**

**Quan trọng:** Deploy code một mình **KHÔNG tự cập nhật database**; phải chạy bước 4–5 hoặc script release cùng đợt deploy để đưa nội dung/ảnh vào production. Đây là tác vụ **post-deploy bắt buộc** cho release này, nhưng không được tự phát hành bài. Wrapper chỉ chạy `--apply` khi có `PTS_EDITORIAL_BACKUP_VERIFIED=1` sau backup đã xác nhận.

## Kiểm tra và rollback

- Các test: `tests/Feature/EditorialBatchTest.php`, API news-detail, media fallback, màn admin article.
- Bất kỳ ảnh mới nào không trả 200 phải ngừng công khai bài; không chạy xóa volume để xử lý.
- Để đảo trạng thái nội dung production: ẩn 3 bài mới ở Admin và khôi phục 5 trường ảnh cũ theo manifest, nếu chưa có chỉnh sửa khác kể từ khi apply; không xóa bài đã chỉnh sửa.
- Không xóa ảnh khỏi `shared/upload` vì có thể đã được người khác tái sử dụng.
- Source rollback qua Git chỉ đổi code; rollback DB/ảnh phải thực hiện riêng, không giả định code rollback cũng rollback dữ liệu.

## Chất lượng trước khi công khai

- Xem bìa 16:9 trên desktop/mobile, kiểm tra object-fit không cắt chủ thể.
- Bài có H2/H3, mô tả SEO, nguồn xuất xứ và ngày nghiên cứu.
- Bài kỹ thuật kiểm tra lại thông số/văn bản có hiệu lực; không ghi số liệu chưa được hãng xác nhận.
- Kiểm tra link nội bộ và trang bài viết sau khi publish, sitemap/canonical nếu thay URL.
