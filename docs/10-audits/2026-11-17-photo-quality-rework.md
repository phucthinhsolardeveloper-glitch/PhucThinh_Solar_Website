# Nâng chất lượng ảnh sản phẩm · 17/11/2026

## Tình trạng đã áp vào production

- Thay 2 avatar ORIANA #510, #513 bằng PNG 1600x1600 có alpha, dùng ảnh nguyên bản theo bảng giá; DB03-2 giữ cả 3 góc nhìn nhờ tách nền flood-white.
- Thay avatar và hai ảnh gallery cho ba model SolaX #808, #809, #810 bằng PNG 1600x1600, bảo toàn alpha gốc từ chính hãng.
- **Không AI sinh lại hình dáng/chi tiết.** Không thay giá, thông số, ảnh Shopping, slug, danh mục hoặc nội dung khác.
- File gốc JPG còn nguyên trên server; backup 5 row + gallery: `docs/10-audits/2026-11-17-photo-qav2-five-preapply.json`.
- Tên ảnh mới chứa `alpha-qav2-20261117`; manifest và SHA-256: `backend/resources/product-gallery/2026-11-17/qa-transparent-rework/approved-five/approved-five.json`.

## Chất lượng nguồn / hạn chế

- File PNG 1600x1600 có thể nét hơn nhẹ ở viền nhờ Lanczos + unsharp mức nhẹ nhưng **không có nghĩa** ảnh native gốc đạt 1600px.
- Ảnh SolaX 516px gốc; ảnh ORIANA DB03-2 970px, BL120W 889px. Mọi text/nhãn nhỏ không được tái tạo bằng AI tránh bịa sai chi tiết.
- Ảnh DB03-2 dùng ISNet đã từng làm mất cạnh thứ ba -> bản hiện hành dùng flood-white, QA đủ ba góc.
- Nguồn SolaX: https://vn.solaxpower.com/products/x1-lite-lv.html (hình minh họa dòng 8/10/12 kW).
- Đã tách nền trên các ảnh thử YS-80W, HB01-60W, DB-16300W, PL-0307 nhưng **chưa thay bản production** vì ảnh native quá nhỏ / còn artifact; cần nguồn chính thức tốt hơn.

## Vận hành / hậu kiểm

1. Re-run: `php scripts/content/audit-product-media-readonly.php` trên container backend production.
2. QA PNG alpha `getextrema==(0,255)` + public WebP alpha, không chỉ nhìn phần mở rộng PNG.
3. QA API Laravel và Nuxt, chú ý Nuxt edge cache `s-maxage=60, stale-while-revalidate` có thể tạm trả đường dẫn cũ vài chục giây.
4. Browser check desktop 1440, mobile 390, ảnh full/gallery load và không tràn ngang.
5. Nếu không phù hợp, rollback theo `preapply.json`; ảnh cũ không bị xóa.
6. Từ nay không coi các ảnh nhân tạo 1600px là "nét thật" nếu nguồn quá nhỏ; đặt trạng thái `SOURCE_RESOLUTION_LIMITED` cho tới khi có ảnh hãng chất lượng cao.

## Kết quả QA production lúc 10:50 (GMT+7)

- Bản mới `alpha-qav2` đã áp cho 5 sản phẩm: ORIANA #510, #513; SolaX #808, #809, #810. Đối chiếu DB, tất cả file PNG có SHA-256 đúng manifest.
- Laravel API từng mã trả đường dẫn PNG mới. Nuxt public API đã làm mới dần theo cache `s-maxage=60, stale-while-revalidate`, lần kiểm tra cuối có URL mới.
- Thumbnail `/thumbs/960x960x3/...png.webp` xác minh ảnh RGBA và alpha `(0,255)` cho cả hai mã ORIANA, cùng bộ ảnh dùng chung cho ba SolaX.
- Playwright production: kiểm tra 4 trang sản phẩm đại diện ở desktop 1440 px và mobile 390 px: 8/8 trường hợp HTTP 200, ảnh chính mới tải được, 0 JavaScript error, 0 tràn ngang, không còn ảnh JPG cũ trong DOM. Kiểm tra desktop SolaX có đủ 3 thumbnail PNG mới tải được.
- Ảnh của 4 mã #519/#522/#526/#687 vẫn là JPG cũ trên production; có bản alpha QA staging nhưng giữ lại do chưa đạt yêu cầu độ nét nguồn.
- Chưa động vào các sản phẩm khác, chưa đổi metadata ảnh Shopping, chưa chuẩn hóa specs, và chưa commit/deploy source code runtime. Các script/tài liệu QA đang ở working tree trên máy công ty.
