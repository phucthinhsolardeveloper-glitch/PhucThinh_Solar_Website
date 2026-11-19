# Nhật ký triển khai production

Mỗi lần thay đổi production phải thêm một mục mới, không ghi credential, token,
PII hoặc nội dung bí mật. Nếu deploy chỉ thay docs không rebuild image thì ghi rõ
`docs-only` và lý do không cần runtime deploy.

## 2026-11-17 — dữ liệu: ảnh đại diện concept mới cho 63/72 đèn pha, sửa thông số BCT-WW6.0, ảnh Shopping thiếu

- Phạm vi: chỉ dữ liệu và file ảnh trên production, không đổi code. Backup DB `20261117T100753Z-db` (sửa thông số) và `20261117T103557Z-db` (ảnh đại diện).
- Ảnh đại diện: bìa theo mẫu `1.png` của chủ website, dựng bằng mã (nền lấy từ chính mẫu đã xóa chữ, sản phẩm ghép nguyên pixel từ ảnh Shopping nền trắng, chữ/số lấy từ thông số). 63 sản phẩm đèn pha có `products.image = <mã>-bia-<hash>.jpg`; ảnh cũ giữ nguyên trên máy chủ. Các sản phẩm này đồng thời `sort_order - 10000` nên đứng đầu trong danh mục; thứ tự theo bảng giá trong nhóm không đổi. Rollback: `state/noi-dung-20261117/avatar-rollback-*.json` (image, feed_image, sort_order cũ), nhật ký `admin_audit_events` action `product.avatar_replace_concept3`.
- **Cập nhật 17/11 (lượt 5):** dựng lại ảnh đại diện với sản phẩm phóng to (va chạm theo mặt nạ alpha, bbox rộng trung vị ~964 px thay vì ~875) và bỏ phụ kiện rời khi tách được; thay 62 mã (backup `20261117T114057Z-db`, file bìa cũ không còn dùng đã xóa, 0 file mất). Còn giữ ảnh cũ: BCT-WW5.0-MONO và DB60100 (bản dựng lượt 5 vẫn lấy poster/hộp in chữ), JD-T100/T200/T300 (nguồn có tay người hoặc logo BOLAR), DB3-PRO ×4 (nhãn GERINA); JD-6128 giữ bản lượt 4. Một số mã ORIANA/BCT-WR2.0 vẫn còn phụ kiện chạm khay đỡ (xóa sẽ cụt khung).
- Chưa làm (giữ ảnh cũ, 9 mã): BCT-WW5.0-MONO (ảnh nguồn là poster), JD-T100/T200/T300 (khung chữ, chú thích kích thước), DB60100 (có thùng hộp in chữ), DB3-PRO-200W/400W/800W/1000W (đèn trong ảnh gốc mang nhãn GERINA, sai hãng).
- Ảnh Google Shopping: 6 mã trống hoặc mất file (BCT-WR1.0, BCT-WR4.0, BCT-WW5.0, BCT-WW5.0-MONO, JD-18200, JD-18300) được gán ảnh nền trắng `*-shopping-trang.jpg` làm phẳng từ ảnh sản phẩm cũ, để feed không dùng bìa có chữ.
- Thông số: đối chiếu 72 đèn pha với bảng giá hiện hành, chỉ lệch BCT-WW6.0 (tấm pin 5V/150W (MONO) → 5V/120W) và BCT-WW6.0-MONO (5V/30W → 5V/150W (MONO), kèm sửa phần "điểm nổi bật" chép nhầm 1800Lm/20Ah/IP66). Rollback `state/noi-dung-20261117/ww6-rollback.json`.
- Còn nghi vấn chưa sửa: DB60100 kích thước đèn ghi ×600 mm (nhiều khả năng 60); JD-8500L bảng thông số cũ ghi 60Ah/75W trái với 48Ah/70W; mô tả dài của JD-8860L và JD-8200L nhắc nhầm tên sản phẩm khác.
- Kiểm: DB 63/63 có ảnh mới, file đủ; trang danh mục đèn pha hiển thị ảnh mới đầu danh sách; trang chi tiết og:image là bìa mới; feed Shopping dùng ảnh nền trắng. Ảnh ORIANA còn lẫn phụ kiện (ốc vít, pin AA, dây) vì ảnh nguồn là ảnh bộ sản phẩm; để xử lý ở lượt sau.

## 2026-11-17 — dữ liệu: ảnh đại diện concept cho toàn bộ danh mục (lượt 6), 258/330 sản phẩm

- Backup DB `20261117T121959Z` (đèn), `T123758Z` (biến tần/lưu trữ), `T123807Z` (đo lường), `T123818Z` (tấm pin/quạt/bơm). Ghi `products.image` = `<mã>-bia-<hash>.jpg`, `sort_order - 10000` (lên đầu trong danh mục), ảnh Shopping: mã chưa có hoặc mất file được gán ảnh cũ (JPG) hoặc bản làm phẳng nền trắng `*-shopping-trang.jpg`. Rollback `state/noi-dung-20261117/avatar-rollback-*.json`; ảnh cũ giữ nguyên.
- Đã lên: 115 đèn (đường, trụ cổng, treo tường, sân vườn, ốp trần, UFO), 37 biến tần/ESS/pin, 3 thiết bị đo lường SolaX, 40 tấm pin/quạt/biến tần bơm. Cộng đèn pha lượt trước = 258.
- Chưa lên (72): 9 đèn pha (nguồn có tay người/logo BOLAR/nhãn GERINA/poster), 5 đèn (DB02-3, DB-OT1000, JDL-OT400 ảnh nền cỏ có chú thích; DB-16400W ảnh có hộp in chữ; DB01-ECO phụ kiện lẫn), 36 bộ sạc MPPT/PWM Blue Carbon (ảnh mang logo LDSOLAR), 10 mã tấm pin/bơm (ảnh ghép EPV230, ảnh 630W dùng cho 610W), 13 sản phẩm không có mã.
- Kiểm: 0 file ảnh mất, 0 ảnh bìa trùng ảnh Shopping, danh mục đèn đường/hòa lưới/tấm pin hiển thị ảnh mới, feed Shopping không dùng ảnh bìa.

## 2026-11-17 — dữ liệu: ảnh đại diện concept lượt 7 (+14, tổng 272/330)

- Backup DB `20261117T124608Z`. Thêm 11 sản phẩm không có mã (khóa bằng id: đèn cắm cỏ, cáp THIPHA ×3, quạt Blue Fan, băng keo, keo foam, ống ruột gà ×3, cọc tiếp địa) và 3 đèn từng bị loại (DB02-3, DB-OT1000, JDL-OT400, nay có bản tách sạch). Giữ ảnh cũ: id-361 PV Module SHARP 395 Wp (dữ liệu công suất nghi sai: 395W trên tấm 500×340 mm) và id-375 kẹp biên (thanh mã chỉ ghi "35"), chờ xác nhận.
- Còn ảnh cũ: 36 bộ sạc Blue Carbon (ảnh mang logo LDSOLAR), 9 đèn pha, 2 đèn (DB-16400W, DB01-ECO), 10 mã tấm pin/biến tần bơm (ảnh ghép EPV230, ảnh 630W cho mã 610W) và 2 sản phẩm cần xác nhận ở trên.

## 2026-11-17 — dữ liệu: ảnh đại diện concept lượt 8 (+34, tổng 306/330)

- Backup DB `20261117T132138Z`. Thêm 32 bộ sạc MPPT/PWM Blue Carbon (ảnh thiết bị mang logo LDSOLAR của nhà sản xuất, chủ website chấp nhận dùng đúng ảnh đang hiển thị, ghép nguyên pixel), tấm pin SHARP (id 361, bỏ dòng công suất vì dữ liệu nghi sai), kẹp biên (id 375). Kiểm: 0 file mất, 0 ảnh bìa trùng ảnh Shopping.
- Còn ảnh cũ (24): JD-T100/T200/T300 (tay người/logo BOLAR), DB3-PRO ×4 (nhãn GERINA), DB60100, DB01-ECO, DB-16400W, BCT-WW5.0-MONO, ASTRO N7 610W (chỉ có ảnh 630W), EPV230 ×7 và MPPT-TD2310TU/TD2410TU ×4 (ảnh ghép nhiều model), DAY-BCT-WW1-5M (dây nối). Cần ảnh sạch từng mã từ nhà cung cấp.

## 2026-11-17 — dữ liệu: ảnh đại diện concept lượt 9 (+23, tổng 329/330)

- Backup DB `20261117T134930Z` và lần ghi sau. Thêm 15 mã (ASTRO N7 610W dùng ảnh 630W cùng dòng, dây nối BCT-WW1.0, DB-16400W, DB01-ECO, EPV230 ×7 dùng một thiết bị đại diện, MPPT-TD2310TU/2410TU ×4 dùng ảnh thiết bị nhãn TD3210) và 8 đèn pha (BCT-WW5.0-MONO dùng ảnh đèn cùng dòng WW5.0, DB60100 bỏ hộp in chữ, DB3-PRO ×4 giữ nguyên nhãn trên máy, JD-T100/T200 đã xóa bàn tay bằng sửa ảnh trong vùng tay theo duyệt của chủ website). Kiểm: 0 file mất, 0 ảnh bìa trùng ảnh Shopping.
- Cập nhật cuối: JD-T200 được dựng lại và JD-T300 được thêm (ảnh xóa tay lần 3, đèn/tấm pin sai khác 0 ngoài vùng tay; cutout tấm pin tự vá để giữ khung kính trắng). Toàn bộ 330/330 sản phẩm hiển thị đã có ảnh đại diện concept.
- Lưu ý dữ liệu: DB60100 kích thước đèn "245 × 198 × 600 mm" (nghi 60), SHARP 395 Wp (nghi sai công suất).

## 2026-11-17 — ảnh avatar cân đối lề (329 mã), sửa nút so sánh (0e4f6b7a), ảnh phụ dùng chung cho 72 đèn pha

- Ảnh đại diện: dựng lại 329 ảnh với lề phải/đáy tối thiểu 58 px (trước ~31 px, sản phẩm dính góc), bằng đúng bộ dựng đã duyệt; thay trên production (backup `20261117T153249Z-db`). JD-6128 chưa dựng lại.
- Frontend `0e4f6b7a` (Nuxt only; rollback `releases/rollback-0e4f6b7a/stack.env.before`): nút "So sánh" trên thẻ sản phẩm hạ z-index dưới thanh lọc dính và đặt ở góc dưới phải khung ảnh (trước: z-index 20 nổi trên thanh dính, che tiêu đề ảnh bìa).
- Ảnh phụ dùng chung (backup `20261117T163744Z-db`): thư viện ảnh của 72 đèn pha thêm 180 dòng — 02 "Chiếu sáng linh hoạt ngoài trời" (dùng chung), 07 "An tâm lựa chọn" (2 bản theo bảo hành 2/5 năm), 08 giấy ủy quyền (Blue Carbon, JINDIAN; ORIANA chưa có giấy). Slot cố định theo sort_order (1=02, 2=03, 3=04, 4=05, 5=06, 6=07, 7=08); ảnh cũ dời +100. Rollback `state/noi-dung-20261117/phu-rollback-*.json`. Lưu ý: giấy ủy quyền ghi hạn đã qua (Blue Carbon 09/09/2025, JINDIAN 30/12/2024).

## 2026-11-18 — ảnh phụ 06 "Cấu hình kỹ thuật" cho 64 đèn pha

- Backup DB `20261117T171254Z-db`; thêm 64 dòng `product_images` (slot sort_order 5, file `p2-06-<hash>.jpg`), ảnh dựng bằng mã từ nền mẫu 6 + sản phẩm thật + 8 ô số liệu lấy nguyên văn từ dữ liệu từng mã. Rollback `state/noi-dung-20261117/phu-rollback-*.json`.
- Chưa làm (không tách nền sạch): BCT-WW5.0-MONO, JD-T100, JD-6128, DB60100, DB3-PRO-200W/400W/800W/1000W.

## 2026-11-19 (chiều, muộn) — căn giữa khối thẻ ít hơn số cột (release 5887a972, Nuxt only)

- Khối "Chọn biến tần đúng nhu cầu" (và các khối thẻ khác) khai báo lưới 3 cột cố định, nên 1–2 mục bị dồn trái. Không có thành phần dùng chung: mỗi nơi tự viết lưới. Thêm lớp CSS chung `pts-cg` (+ `pts-cg-801`, `pts-cg-861`) trong `assets/css/main.css`: từ 768 px trở lên dùng flex căn giữa, 2 cột ≥768, 3 cột ≥1200; điện thoại giữ dải cuộn/1 cột cũ.
- Áp cho: ứng dụng ở trang danh mục (CatalogPage), "Ứng dụng" và "Thông tin cần biết" ở trang sản phẩm, chiến dịch ở trang chủ, ba khối ở trang Giới thiệu. Đo trên web thật: biến tần (2 mục) cách mép trái/phải 233/233 px; các khối 3 mục lệch 0/0; không tràn ngang.
- Khối mới có số mục thay đổi nên dùng `pts-cg` thay vì `grid-cols-3` cố định.

## 2026-11-19 (chiều) — sửa nút cây danh mục + dải ảnh sản phẩm, bật công cụ ước tính, cây bút cho 11 bảng CMS (release 65b334b6)

- **Nút "Mở hết / Thu gọn hết" ở Danh mục không chạy**: trong hàm gắn vào nút, `this.$el` là chính cái nút (không phải cả cây) nên `querySelectorAll('.pts-tree-item')` rỗng; thêm nữa `Alpine.$data` không tồn tại trong Alpine đi kèm Livewire. Sửa: dùng `this.$root` và `Alpine.evaluate(item, 'open = …')`. Kiểm bằng trang thử Playwright (gập/mở đúng). File: `resources/views/filament/resources/categories/tree.blade.php`.
- **Dải ảnh nhỏ trên trang sản phẩm bị che ảnh cuối, bấm không tự cuộn**: `ProductDetailGallery.vue` thêm `thumbStrip` + `data-thumb`, cuộn ngang tới ảnh được chọn (đo trên web thật: bấm ảnh cuối → scrollLeft 302/302, ảnh chọn hiện đủ).
- **Công cụ ước tính điện mặt trời bật** (`settings.solar_estimator.enabled=true`, hoàn tác: `estimator-rollback.json`). Giá/bảng giá điện trong cấu hình vẫn là giá trị mặc định chờ chủ xác nhận (10,5 tr/kWp hộ, 9 tr/kWp doanh nghiệp, hybrid +3,5 tr/kWp, pin 6,5 tr/kWh, VAT 8%, biểu giá hộ 5 bậc 1.901…3.844 đ). Chỉ hiện ở trang chi tiết Giải pháp, chưa có ở menu/trang chủ.
- **Cây bút (`?sua=1`) thêm cho**: Thương hiệu, Banner, Video, Album, Chi nhánh, Tỉnh/TP, Chuyển hướng, SEO các trang, Nội dung các trang, Liên hệ (tình trạng + hẹn gọi lại, qua `LeadTriage` có nhật ký). Helper chung `Support/InlineEdit`. Lưu ý lỗi đã gặp khi làm: hai cột cùng tên (`status`, `follow_up_at`) làm Filament tìm trúng cột cũ không sửa được → chỉ hiện cột cũ khi không ở chế độ sửa. Không thêm cho Danh mục (cột tên nhiều dòng, đã có cây sửa riêng), Đơn hàng, Người dùng, Phương thức thanh toán.
- Trang "Khối mô tả" và "Bố cục trang" thêm dòng hướng dẫn đầu trang.

## 2026-11-19 — cải thiện lần cuối theo kỹ năng nội dung, đăng 144/153/155/156

- Dùng kỹ năng `content-density-ux` (đo trước: số từ/màn hình, đoạn ≥35 từ trên 390 px) và `searchfit-seo:on-page-seo` (liên kết nội bộ 3–5/bài, độ dài tiêu đề và mô tả). Đo trên web thật: đoạn dài từ 35 từ trở lên **132 → 33** trên 28 bài; từ/màn hình ~111 → 108 (tách đoạn không giảm số từ).
- Viết lại tay 141 và 143 (trước yếu), gọn 104. Tách đoạn >35 từ ở mọi bài (không đổi chữ). Thêm mục "Đọc thêm" 2–4 liên kết nội bộ cho mỗi bài (bài liên quan + danh mục/thương hiệu), đã kiểm: 27 liên kết duy nhất, 0 liên kết chết; sửa liên kết chết cũ `/tags-san-pham/blue-carbon/` ở 106.
- Mô tả SEO các bài 146/148/150/156 kéo lên ~140 ký tự. Tiêu đề 101/109 rút gọn (hoàn tác: `titles2-rollback.json`).
- Áp `voice-v6/` (32 bài, backup DB `20261119T011931Z-db`). Câu mở đầu 144/153/104/151 sửa cho tự đủ nghĩa (không còn "Có." / "Không phải lúc nào.").
- Đăng 144 và 153 (đã qua `PublishChecks`). 155 và 156 đăng ngay thay vì chờ 12:00 / 19:30 để các bài khác không trỏ tới trang 404. Hiện 32 bài, ẩn 1 (107, đã 301).
- Checklist cho chủ website: `output/xem-truoc/checklist.html` (có liên kết, đánh giá, ô Giữ/Cần sửa/Ẩn), script `tools/editorial-voice/build-checklist.py`.

## 2026-11-18 (tối, muộn) — rà soát chất lượng bài, gỡ bài kém, sửa 147, gỡ hình minh hoạ vô ích, đổi ảnh bìa

- Chủ website: bài IEA 147 "giống viết cho bọn ngu đọc", "không muốn content chất lượng kém lên web". Nguyên nhân thật: quá nhiều câu "đây không phải… / không thay… / không cho biết…", câu thanh minh vô ích ("ảnh bìa không phải nhân sự công ty"), giải thích điều hiển nhiên, lặp ngày/số, bài dài lặp ý; công cụ chấm không đo được các lỗi này (đã bổ sung luật, sổ tay mục 12). Người duyệt (Claude) chưa đọc 147 trước khi đăng: đã nhận lỗi.
- Gỡ (ẩn) ngay: 144 (đoạn lủng củng nêu Gia Lai/Tây Nguyên không nguồn), 153 (dài ~1.000 từ, dồn thuật ngữ, rào đón; cần viết lại hẳn), 107 (trùng 102, đã 301). Tạm ẩn rồi bật lại sau khi đọc và sửa: 151, 154, 155, 156.
- Viết lại 147 thành bản tin 260 từ (từ 691), bỏ đoạn lặp ngày/số. Gỡ 6 hình "explainer" gần như rỗng khỏi 145–150 (hình 600/620/650/720 Wp còn gây hiểu lầm vì cột không tỷ lệ). Giữ hai infographic số liệu thật (global-pv-2026, panel-650w).
- Vòng 4 (Codex, mục 12 sổ tay): 103, 137, 139, 141, 146, 148, 150, 151, 152, 154, 155, 156 đã áp (`voice-v4/`, backup DB `20261118T115749Z-db`, backup dòng bài trong `state/noi-dung-20261117/bai-viet-20261118/`). Sửa tay thêm: bỏ ví dụ "vòi nước" ở 139, bỏ câu rỗng ở 150, gộp Voc/Vmp/Isc/Imp thành bảng ở 155, bỏ câu lặp ở 137.
- Ảnh bìa: 102/104/111 trước là cùng một tranh hoạt hình đường phố; 110 có poster "TOP 1"; 149 dùng ảnh explainer. Đổi sang ảnh đèn thật (`pts-cover-den-01/02/03.webp`, `pts-cover-chip-led.webp` trong `/srv/upload/news/`) và `pts-photo-009.webp` cho 149. Hoàn tác: `covers-rollback.json`.
- Kiểm tra trên web thật: 27 bài đang hiện, 0 lỗi JS, không vỡ ảnh/tràn ngang. Đang hiện 30 bài (3 hẹn giờ 154–156).
- Còn ẩn, cần viết lại thật sự: 144, 153.

## 2026-11-18 (tối) — đăng 13 bài hẹn giờ, đổi tiêu đề, gộp 107, cây bút cho bảng bài viết, cookie dự phòng (release 496cb883)

- **13 bài 144–156**: áp nội dung viết lại (`pts:content-batch-patch`, backup DB `20261118T113036Z-db`, backup dòng bài `…/state/noi-dung-20261117/bai-viet-20261118/`), chạy `PublishChecks->post` (13/13 không lỗi) rồi bật `is_visible`. Giữ nguyên `published_at` theo lịch: 144–152 đã hiện (HTTP 200, 0 lỗi), 153 (19:30 18/11), 154–155 (19/11), 156 (19:30 19/11) tự lên đúng giờ (đến giờ mới hết 404).
- **Tiêu đề** 10 bài (100–102, 104–106, 108–111) đổi sang chữ thường, đường dẫn giữ nguyên; cập nhật cả `seo_metas.title` khi có. **107** ẩn + 301 `/den-nang-luong-mat-troi-nhung-ly-do-ban-nen-chon/` → `/den-nang-luong-mat-troi-nhung-ly-do-nen-chon/` (nguồn `manual`), không còn trong sitemap. Hoàn tác: `state/noi-dung-20261117/bai-viet-20261118/titles-107-rollback.json`, backup DB `20261118T113138Z-db`.
- **Cây bút** thêm cho danh sách Bài viết/Giải pháp/Dự án/Chính sách (`HasInlineEditMode`, `?sua=1`): Hiển thị (qua `PublishChecks`) và Ngày đăng (ngày/tháng/năm giờ:phút); không cho sửa tiêu đề/slug; chỉ người có quyền đăng. ListProducts dùng chung trait. Phí giao theo phường/xã đã có sẵn sửa trực tiếp; bảng SEO trang chỉ 11 dòng nên không thêm.
- **Cookie dự phòng**: lựa chọn lưu cả localStorage lẫn cookie `pts_consent` (180 ngày); script consent mặc định đọc cả hai. Đã kiểm: xóa localStorage vẫn nhớ nhờ cookie; GA4 vẫn nhận page_view/view_item/add_to_cart/begin_checkout.
- Lệnh `pts:content-batch-patch` nay nằm trong ảnh backend (lần trước phải nạp tay qua `/tmp/runpatch.php`).
- Sự cố đã xử lý: phiên bị ngắt giữa lúc phát hành 071d5445→ddf18515 làm tệp ảnh gửi lên máy chủ rỗng (20 byte) trong khi `stack.env` đã trỏ sang ảnh chưa tồn tại; đã hoàn lại cấu hình trước khi container bị tạo lại, không gián đoạn. Từ nay kiểm kích thước tệp (> 100 MB) trước khi đổi cấu hình.
- Docker Desktop ở `%LOCALAPPDATA%\Programs\DockerDesktop\Docker Desktop.exe` (không có dấu cách trong tên thư mục).

## 2026-11-18 — viết lại giọng văn 19 bài đang hiện (dữ liệu, không đổi code)

- Quy trình: sổ tay giọng văn `docs/07-features/editorial-voice-guide-20261118.md` + bài mẫu và công cụ chấm `tools/editorial-voice/` (`doc-giong.py` bắt câu dài, câu ra lệnh, rào chắn, từ cứng, ghi chú nội bộ lọt vào bài, số liệu/liên kết lệch). Codex (gpt-6-luna) viết, Claude đọc bằng mắt và trả lại 3 vòng; báo cáo từng lô ở `backend/resources/editorial/2026-11-18/bao-cao-*.md`.
- Đăng bằng `pts:content-batch-patch` (có cổng duyệt, sao lưu nguyên dòng posts/seo_metas, kiểm hash baseline). Lệnh chưa nằm trong ảnh production nên chép tạm vào container và nạp qua `/tmp/runpatch.php` (ảnh chạy khóa danh sách lớp). `--approval-ref=chat-chu-website-2026-11-18-viet-lai-het-bai-hien-co`; reviewer ghi là chủ website; cờ `sources_verified`/`images_verified` đặt true vì ảnh và liên kết nguồn của từng bài được giữ nguyên so với bài đang đăng (đã kiểm tự động).
- Đăng thử 139 trước (xem trên website thật), rồi 18 bài còn lại: 100–106, 108–111, 136–138, 140–143. Backup DB `20261118T105244Z-db`, `20261118T105433Z-db`; backup dòng bài: `/var/www/html/storage/app/private/content-batch-patch/20261118-175246-*.json` và `20261118-175435-*.json` trong container backend (mất khi tạo lại container: sao ra ngoài nếu cần giữ lâu).
- Kiểm trên website thật: 19/19 trang 200, không vỡ ảnh, không tràn ngang trên điện thoại, 0 lỗi JS.
- 107 trùng thân bài 102: chưa đổi, chờ chủ website quyết gộp (301) hay viết góc khác. Các nháp ẩn 144–156 đã viết lại giọng văn nhưng CHƯA áp vào DB (chờ duyệt kỹ thuật): `backend/resources/editorial/2026-11-18/voice-drafts-hidden/`.

## 2026-11-18 — thông báo cookie + consent mode, mô tả SEO từng trang, ảnh phụ feed (release 8a768306)

- Backup `20261118T084807Z-db`. Backend + Nuxt (8a768306). Không migration. Hoàn tác code: `releases/rollback-8a768306/stack.env.before`.
- Dữ liệu `seo_metas` (9 trang cố định: san-pham, tin-tuc, gioi-thieu, giai-phap, du-an, dai-ly, lien-he, thu-vien-anh, video) trước đây dùng chung một đoạn 431 ký tự "ngọn cờ đầu…" hoặc chỉ 5–12 ký tự; đã viết riêng mỗi trang (≤ 160 ký tự). 3 thương hiệu 19–21 để trống title/description để dùng câu mặc định có số mẫu. Hoàn tác: `state/noi-dung-20261117/seo-metas-rollback-20261118.json`. Nuxt/Laravel giữ bản cũ vài phút (đã làm mới).
- `/thuong-hieu/` có title/description riêng (trước dùng của /san-pham/).
- Ảnh `alt=""` còn lại trên các trang là ảnh trang trí có chủ đích, không sửa.
- Thông báo cookie (`CookieNotice.vue`): đo lường mặc định bật, "Từ chối" ghi `localStorage pts-consent=denied` và gọi `gtag('consent','update')`; script `pts-gtag-bridge` đặt `consent default` theo lựa chọn đã lưu. Thẻ pixel Meta/TikTok/Ads thêm trong GTM phải bật "Additional consent: ad_storage" để tôn trọng lựa chọn.
- Merchant feed: thêm `additional_image_link` (tối đa 5 ảnh THẬT, sort_order < 100, ≥ 500 px; ảnh poster p2 không đưa): 41/315 sản phẩm có ảnh phụ. Chưa đổi `availability`/`identifier_exists`; 8 mã chưa có hãng (id 381–384, 391–396) chờ chủ website.
- Dọn VPS: xóa tarball các release cũ (giữ 3 bản gần nhất), image candidate cũ, build cache. Đĩa 96% → 84%.

## 2026-11-18 — SEO chỉ mục trang 2+, sitemap, purchase có items, số liệu Admin (release 95b79a52 + 42a8feb7)

- Backup `20261118T083622Z-db`. Không có migration. Backend + Nuxt (95b79a52), backend (42a8feb7). Hoàn tác: `releases/rollback-<sha>/stack.env.before`.
- Danh mục `?page=N`/`?p=N`: `index, follow`, canonical riêng `?page=N` (trước: `noindex, nofollow` + canonical về trang 1). Lọc/sắp xếp/`per_page` vẫn noindex về trang sạch. `canonicalPublicUrl` chỉ cho phép đúng một `?page=N`/`?p=N`.
- Sitemap chỉ liệt kê chuyên mục bài viết có bài đang hiện (loại `/tin-tuyen-dung`, vốn noindex).
- Trang cảm ơn trả thêm `order.items` (id, tên, đơn giá, số lượng; không có dữ liệu khách) để sự kiện `purchase` gửi `items` cho GA4.
- Thống kê Admin: đo chéo với nhật ký Caddy cùng khung giờ (76 lượt/9 người so với 83 beacon/8 IP): khớp. Sửa `CountedOrders`: đơn VNPay chưa trả tiền không tính vào doanh số/đơn chờ/sản phẩm bán chạy/báo cáo tuần. Nhãn "người xem" đổi thành "lượt khách ghé (mỗi người tính một lần mỗi ngày)".
- Image backend lần này dựng lại từ Dockerfile nên Apache `StartServers/MinSpare/MaxSpare = 16/16/32` đã nằm trong image (không còn phụ thuộc chỉnh tay).
- Chưa làm (cần chủ website quyết định): `availability` luôn `in_stock` và `identifier_exists=no` ở Merchant feed, vì bảng sản phẩm không có cột tồn kho/MPN/GTIN.

## 2026-11-18 — cầu gtag() để sự kiện mua hàng tới GA4 qua hộp GTM (release 25a3f135, Nuxt only)

- Sau khi chuyển sang `GTM-52JWR8Q7` và bỏ gtag trực tiếp, GA4 chỉ nhận `page_view` và `user_engagement` (đo lại trên trang thật): `pushDataLayer` vẫn gọi `gtag('event', ...)` nhưng `window.gtag` không còn tồn tại. Đã thêm script `pts-gtag-bridge` trong `app.vue` (khi có GTM): `window.gtag` đẩy lệnh vào `dataLayer` để Google tag trong hộp gửi đi.
- Đo lại: GA4 nhận `page_view`, `view_item`, `add_to_cart`, `begin_checkout` (không cần tạo thẻ trong GTM). Lưu ý đính chính: lần kiểm trước chỉ đọc phần địa chỉ của các lượt gửi GA4 nên kết luận "sự kiện không tới GA4" khi dùng gtag trực tiếp có thể sai; không ảnh hưởng hiện tại.
- Khi thêm pixel Meta/TikTok/Ads trong GTM, dùng trigger Custom Event theo các tên sự kiện dataLayer (`view_item`, `add_to_cart`, `begin_checkout`, `purchase`, `generate_lead`, `click_call`, `click_zalo`); KHÔNG thêm thẻ GA4 Event trùng.

## 2026-11-18 — chuyển website sang hộp GTM của chủ website (dữ liệu, không đổi code)

- Backup `20261118T080725Z-db`. Bảng `settings`: `gtm_id` `GTM-57TJSRVM` (hộp trống, không rõ chủ) → `GTM-52JWR8Q7` (tài khoản GTM "PTS Solar - Production Web" của chủ website, đã có thẻ Google tag cho `G-8002FNNPR9`); `ga4_id` `G-8002FNNPR9` → rỗng để GA4 chỉ nạp qua GTM (tránh gtag trực tiếp + thẻ trong hộp cùng gửi page_view). Hoàn tác: `state/noi-dung-20261117/gtm-ga4-rollback.json`.
- Kiểm trên trang thật: chỉ nạp `gtm.js?id=GTM-52JWR8Q7`, đúng 1 hit `page_view` tới `G-8002FNNPR9`.
- Việc kế (làm trong GTM, không cần code): tạo thẻ GA4 Event đọc dataLayer cho view_item, add_to_cart, begin_checkout, purchase, generate_lead, click gọi/Zalo; rồi thêm Meta/TikTok/Google Ads/Zalo khi có ID.

## 2026-11-18 — 3D nét hơn, dòng điện trả về hạt gốc có quầng, nén GLB (release cc0bb16f, Nuxt only)

- Caddy (`caddy reload`; bản cũ `Caddyfile.bak-glb-20261118`): thêm khối `encode zstd gzip` với `match header Content-Type model/gltf-binary*` vì `.glb` không thuộc danh sách kiểu nén mặc định: `pts-technical-v05.glb` 1.64 MB → ~1.16 MB khi tải (lossless).
- Frontend `cc0bb16f` (backup `20261118T075128Z-db`, rollback `releases/rollback-cc0bb16f/stack.env.before`), `main.js?v=20261118-4`, `static-layer.js?v=20261118-4` (import có `?v` vì cache 7 ngày): (1) hoàn tác xung điện đường cong (lộ qua vật thể, chủ website thấy xấu), trả hạt sáng gốc kèm quầng sáng mềm và đuôi mờ, có che khuất đúng; (2) các thành phần bầu trời (mặt trời, mây, trăng, sao, bụi sáng) mờ dần khi camera xoay về cùng phía để không chói che mô hình khi xoay 360°; (3) nét hơn: anisotropy tối đa 8 cho mọi texture, ảnh tĩnh siêu lấy mẫu ≥ 1.5× (trước 1.25× ở màn 100%), bộ lọc làm nét nhẹ (unsharp mask) khi hiển thị ảnh tĩnh.
- Kiểm cục bộ: xoay 4 góc, 0 lỗi JS; kéo xoay trên trang thật vẫn chạy.

## 2026-11-18 — 3D: bầu trời sống + dòng điện mượt; mã theo dõi tải sau (release e7c5f36f, Nuxt only)

- Frontend `e7c5f36f` (backup `20261118T073451Z-db`, rollback `releases/rollback-e7c5f36f/stack.env.before`), gồm: (1) cảnh 3D `pts-living-energy/src/main.js` (`?v=20261118-3`): mây mềm trôi, bụi sáng dọc tia nắng, lấp lánh trên tấm pin ban ngày; trăng, hai lớp sao nhấp nháy ban đêm; (2) xung điện phát sáng chạy theo đường cong làm mượt từ chính đường animation của từng tuyến, thay ba quả cầu nhỏ (cầu cũ thu nhỏ về 0, vẫn còn trong file GLB); (3) `app.vue`: GTM và gtag.js chỉ tải khi trang xong và rảnh (tối đa 4 s) hoặc khi khách tương tác lần đầu; `gtag('config')` vẫn chạy ngay, sự kiện đẩy sớm xếp hàng trong dataLayer.
- Kiểm trang thật: vẫn đúng 1 lần `page_view` cho `G-8002FNNPR9` (không đếm trùng); container GTM `GTM-57TJSRVM` hiện không có thẻ nào (chỉ biến). Điện thoại 4G chậm giả lập: load 17.2 s → 10.3 s, 3.3 MB → 1.9 MB trước khi load; laptop ~0.8 s.
- Sự kiện dataLayer sẵn có để gắn Meta/TikTok Pixel qua GTM: `view_item`, `add_to_cart`, `begin_checkout`, `purchase`, `generate_lead`, `click` liên kết.

## 2026-11-18 — tối ưu tốc độ, mặt trời trong cảnh 3D, logo hãng (release cd9c56b1, Nuxt only)

- Đo trước khi sửa: ảnh/logo đi qua Apache chạy PHP; Apache chỉ giữ 5–10 worker rỗi nên khi trang xin vài chục ảnh cùng lúc thì fork ~1 worker/giây, logo hãng về cách nhau ~1 giây (có logo mất 2–4 s); `/upload/*` và `/thumbs/*` trả `Cache-Control: no-cache` nên lần xem nào cũng hỏi lại; `/assets`, `/fonts`, `/pts-living-energy` không có chỉ thị lưu đệm; cả 4 ảnh hero tải cùng lúc (Chrome tải ảnh lazy nằm trong khung nhìn dù opacity 0).
- Caddy ngoài cùng (`/srv/phucthinhsolar/Caddyfile`, tải lại bằng `caddy reload`, không gián đoạn; bản cũ `Caddyfile.bak-cache-20261118`): thêm `Cache-Control` ghi đè header upstream: `/upload/*`, `/thumbs/*` 14 ngày + stale-while-revalidate 1 ngày; `/assets/*`, `/pts-living-energy/*` 7 ngày; `/fonts/be-vietnam-pro/*` 1 năm immutable. Lưu ý: file không đổi tên (ví dụ `src/power-map.js` do `main.js` import) sẽ bị giữ tối đa 7 ngày; khi sửa phải đổi tên hoặc tăng `?v=`.
- Apache trong container backend (graceful, không gián đoạn): StartServers 16, MinSpareServers 16, MaxSpareServers 32. Mất khi container được tạo lại; đã ghi vào `deploy/laravel13/backend.Dockerfile` để bản backend kế tiếp có sẵn.
- Frontend `64512e7c` + `cd9c56b1` (backup `20261118T071455Z-db`, rollback `releases/rollback-cd9c56b1/stack.env.before`): hero chỉ tải slide đầu, slide kế sau khi trang tải xong, tự chạy sau khi `load`; `sizes` điện thoại 150vw; logo hãng ở đầu trang danh mục `loading="eager"`; cảnh 3D thêm mặt trời (sprite quầng sáng + tia xoay chậm, đặt tại điểm tia nắng xuất phát, chỉ hiện ban ngày), tăng `?v=20261118-1`.
- Kết quả đo trang thật: desktop load ~1.3 s, LCP ~0.45–0.6 s; trước tải chỉ còn 1 ảnh hero; 0/160 yêu cầu thiếu lưu đệm; logo hãng 60–220 ms mỗi cái.
- Chưa làm: GTM và gtag.js cùng tải (2 × ~180 KB, container GTM có vẻ cũng chứa cấu hình GA4, nên nghi tính trùng); file 3D `pts-technical-v05.glb` 1.6 MB; ảnh Shopping/ảnh sản phẩm lớn.

## 2026-11-18 — bảng thông số kỹ thuật dễ đọc hơn (release 35b2ca9a, Nuxt only) và sửa "Blue CarBon"

- Frontend `35b2ca9a` (rollback `releases/rollback-35b2ca9a/stack.env.before`): `ProductDetailSpecs.vue` tách ô kiểu "- A. - B." hoặc "A; B" (dài > 50 ký tự) thành danh sách có chấm vàng; cột nhãn 38% / giá trị 62% trên máy tính; giá trị dài giảm độ đậm. Sao chép và tìm vẫn dùng nội dung gốc. 515 test qua.
- Dữ liệu (backup `20261118T064518Z-db`): `products.additional_specs` đổi "Blue CarBon" → "Blue Carbon" ở 11 sản phẩm. Hoàn tác `state/noi-dung-20261117/carbon-rollback.json`. Chưa đổi `table_product` (bảng cũ) và 1 bài `posts`/`table_news` cũng có chữ này.
- Kiểm trên trang thật: dòng "Nâng cấp" hiện 4 gạch đầu dòng, 0 lỗi JS, 0 lỗi nhật ký.

## 2026-11-18 — hero banner nét hơn (release 7756baf1, Nuxt only)

- Nguyên nhân mờ: trang yêu cầu `/thumbs/1600x720x1` (cắt 2.22:1) rồi trình duyệt phóng ×1.25 (màn 1440×900) đến ×2.5 (retina) để phủ khung ~1.6:1. Ảnh cũ cũng bị vậy.
- Backup `20261118T062729Z-db`: 4 ảnh hero dựng lại ở 2880×1800 (16:10, WebP q80, 0.9–1.3 MB, tên `hero-*-20261118b.webp`), ít phải cắt hai bên hơn; banners 137–140 trỏ sang file mới. Hoàn tác: `state/noi-dung-20261117/hero-ban-2-rollback.json` (về bản `...20261118.webp`) hoặc `hero-anh-cu-rollback.json` (về ảnh `20260924`).
- Frontend `7756baf1` (backup `20261118T062918Z-db`, rollback `releases/rollback-7756baf1/stack.env.before`): `HomeHero.vue` yêu cầu rendition 16:10 ở 960/1600/2000 px (giới hạn `/thumbs`) và file gốc 2880 px làm ứng viên lớn nhất, `sizes="(max-width: 640px) 200vw, 100vw"` cho điện thoại; test hợp đồng ảnh cập nhật (515 test + typecheck qua).
- Đo trên trang thật (độ phóng so với điểm ảnh màn hình cần, 1.0 = nét tuyệt đối): 1440×900 ×1: 0.9; retina 1440×900 ×2: 1.0; FullHD: 0.96; QHD: 0.89; laptop 4K 1728×1117 ×2: 1.24; điện thoại ×3: 1.41. Trước đây: 1.25 / 2.5 / ~1.7 / ~2.3 / ~3.1 / ~3.5.

## 2026-11-18 — thay 4 ảnh hero banner trang chủ

- Backup `20261118T062329Z-db`. Hero là dữ liệu bảng `banners` (placement `hero-carousel`, id 137–140), không phải code: chỉ đổi cột `image` sang 4 file mới trong `upload/photo/` (WebP 2400×1080, tỉ lệ 2.22:1 như ảnh cũ, 430–720 KB): `hero-mai-xuong-20261118.webp` (mái xưởng), `hero-dien-gio-xanh-20261118.webp`, `hero-binh-minh-20261118.webp`, `hero-toan-canh-20261118.webp`. Nguồn: bộ ảnh Pexels chủ website chọn (`~/refs/pts-hero-20261118/originals`, ảnh 05, 02, 01, 07), cắt từ bản gốc 4K–9K. Ảnh 07 cắt bỏ cột tuabin lớn bên trái để không đè tiêu đề.
- Rollback: `state/noi-dung-20261117/hero-anh-cu-rollback.json` (id → tên file cũ `hero-*-20260924.webp`, còn nguyên trên đĩa).
- Kiểm trang thật máy tính và điện thoại: 4 slide đúng ảnh mới, 0 lỗi JS. Lưu ý: chữ mô tả nhỏ góc dưới phải (màu xanh đậm, quầng trắng) khó đọc hơn trên nền tấm pin tối của ảnh mới; chưa sửa code.
- Giấy phép: Pexels License; không phải ảnh công trình của Phúc Thịnh Solar.

## 2026-11-18 — ảnh VIP1 dùng chung xuống cuối thư viện

- Backup `20261118T055953Z-db`: ảnh VIP1 dùng chung (cùng một file ở ≥ 2 sản phẩm) đổi sort_order 600+ nên đứng sau cả ảnh concept `p2-0N` (500+N); ảnh thật 1.., VIP1 riêng từng mã 100... 437 dòng, 44 sản phẩm, không ẩn/xóa. Hoàn tác: `state/noi-dung-20261117/sapxep-chung-cuoi-rollback.json`. Script `sap-xep-anh.php`.
- Kiểm tra trước khi dọn: ảnh Shopping (`feed_image`, 330 sản phẩm) là ảnh nền trắng `*-clean-v1.png`, khác ảnh đại diện; không sản phẩm nào dùng file `p2-*` làm ảnh đại diện hay Shopping. 482 file `p2-*` cũ không dòng nào dùng nhưng KHÔNG xóa (theo chủ website).

## 2026-11-18 — ảnh 08 giấy ủy quyền cho 4 nhóm, ẩn "Chứng nhận & ủy quyền" trống của ORIANA

- Backup `20261118T054202Z-db`: thêm ảnh 08 (khung giấy ủy quyền Blue Carbon/JINDIAN, giữ nguyên theo chủ website dù ghi hạn cũ) cho 76 sản phẩm của đèn đường, đèn khác, tấm pin/phụ kiện, bộ sạc/inverter. Hãng chưa có giấy (ORIANA, SolaX, LONGi, EPSIVO…) không có ảnh 08.
- Backup `20261118T054211Z-db`: ẩn (`is_visible=0`) 11 ảnh VIP1 "CHỨNG NHẬN & ỦY QUYỀN" chỉ có khung trống của ORIANA (`vip1-01-016`, `02-030`, `03-043`, `04-057`, `05-067`, `06-077`, `12-135`, `13-149`, `16-182`, `18-196`, `19-210`), 29 dòng. Hoàn tác: `state/noi-dung-20261117/an-chung-nhan-trong-rollback.json`.
- Ẩn thêm 14 ảnh VIP1 chứng nhận (có giấy thật) ở sản phẩm đã có ảnh 08 mới để khỏi trùng. Hoàn tác: `an-chung-nhan-trung-rollback.json`.

## 2026-11-18 — release e6f76da2 (Nuxt only): khối video ngay dưới mô tả sản phẩm; dọn ảnh trùng

- Frontend `5182832b` (đi kèm `e6f76da2`): khối "Video sản phẩm & lắp đặt" chuyển từ gần cuối trang lên ngay dưới "Xem mô tả chi tiết"; nút "Nhận tài liệu & video" nền vàng; khi sản phẩm có video thật thì có mục "Video" trên thanh điều hướng và liên kết "▶ Xem video" cạnh mã. Component `ProductVideoBlock.vue`; 515 test + typecheck qua.
- Backup DB `20261118T050900Z-db`. Chỉ thay Nuxt (`0e4f6b7a-candidate` → `e6f76da2-candidate`); backend giữ `3fb268fe-candidate`. Rollback: `releases/rollback-e6f76da2/stack.env.before`. Image build bằng `docker compose build nuxt` (Docker Desktop khởi động lại trước đó).
- Sau đổi: `/`, trang sản phẩm, `/health`, `/api/v1/site`, `/google-shopping.xml` đều 200; trang thật máy tính và điện thoại đều có thứ tự mô tả → video → thông số → bảo hành, 0 lỗi JS.
- Quét ảnh trùng trên 318 sản phẩm (backup `20261118T050402Z-db`): không có đường dẫn lặp, không ảnh nào giống ảnh đại diện, không file thiếu; 2 ảnh trùng nội dung ở sản phẩm #325 (khác tên file) đã ẩn (dòng 710, 711; hoàn tác `state/noi-dung-20261117/an-trung-325-rollback.json`). 482 file `p2-*` cũ không còn dòng nào dùng (ảnh đã thay bản mới) vẫn nằm trên đĩa, chưa xóa.

## 2026-11-18 — chỉnh ảnh 02 và 04 theo góp ý chủ website

- Backup `20261118T045754Z-db`: ảnh 04 "Nạp năng lượng" của 190 sản phẩm (đèn pha, đèn đường, đèn khác, quạt) thay bằng hình minh họa dùng chung (không dùng ảnh sản phẩm lặp, thẻ thông số vẫn riêng từng mã); ảnh 02 của đèn đường làm lại với nhãn cảnh đúng đối tượng (đường nội bộ/công viên, bãi đỗ xe, mặt tiền công trình, khu công cộng); PL-0307 làm nét lại (nguồn ảnh duy nhất quá mềm) ở ảnh 03/06/07. Đổi 243 dòng, không thêm/xóa.
- Ảnh 02 "Ứng dụng" chỉ còn cho đèn pha (72) và đèn đường (50). Với đèn khác, tấm pin/phụ kiện, bộ sạc/inverter, 195 ảnh 02 bị ẨN (`is_visible=0`, không xóa): bốn ảnh cảnh không hợp loại hàng và ảnh lặp. Hoàn tác: `state/noi-dung-20261117/an02-rollback-1158.json` (danh sách id dòng, đặt lại `is_visible=1`). Script `an-02.php`.

## 2026-11-18 — ảnh phụ concept cho 4 nhóm sản phẩm khác (đèn đường, đèn khác, tấm pin/phụ kiện, bộ sạc/inverter), 245 sản phẩm

- Backup `20261118T040901Z-db`; một lượt `ghi-phu.php`: thêm 1141 dòng `product_images`, không đổi/dời/xóa dòng cũ. Ảnh `p2-0N` có sort_order 500+N nên luôn đứng sau ảnh thật và `vip1-` (kiểm lại bằng `sap-xep-anh.php`: 0 dòng cần đổi).
- Theo loại ảnh: 02/03/07 mỗi loại 245 mã, 04: 118, 05: 57, 06: 233 (cộng với đèn pha, tổng p2-02/03/07 = 317 sản phẩm). Ảnh nào chưa dựng hoặc thiếu dữ liệu thì KHÔNG đưa lên (xem `output/<nhóm>/ngoai-le.md`).
- Không đưa ảnh 08 (giấy ủy quyền Blue Carbon hạn 09/09/2025, JINDIAN hạn 30/12/2024 đã quá hạn) cho 4 nhóm này; chờ chủ website quyết.
- Bộ sạc/inverter: ảnh 06 tách ô gộp "MPPT/Cấu hình/Thông tin" thành ô có nhãn rõ (chỉ tách từ chuỗi dữ liệu gốc); ảnh 04 chữ lớn là thông số pin thật.
- Chưa có ảnh: 11 mã PWM-SD (ảnh nguồn là hộp in sẵn chữ model khác), `id-354`, `DAY-BCT-WW1-5M`.

## 2026-11-18 — phóng to ảnh 02/04/05 đèn pha và sắp lại thứ tự thư viện ảnh (ảnh thật trước, ảnh concept cuối)

- Backup `20261118T012830Z-db`: đổi 216 dòng `product_images` (ảnh 02, 04, 05 của 72 đèn pha dựng lại với sản phẩm to hơn theo khoanh đỏ của chủ website).
- Backup `20261118T015658Z-db`: sắp lại thứ tự 85 sản phẩm (1043 dòng, không ẩn/xóa ảnh nào): ảnh thật (không phải `vip1-`/`p2-`) sort_order 1.., ảnh `vip1-` riêng từng mã 100.., `vip1-` dùng chung 200.., ảnh concept `p2-0N` = 500+N (luôn sau ảnh thật). Rollback `state/noi-dung-20261117/sapxep-rollback-0857.json`. Script `sap-xep-anh.php` chạy lại được (idempotent); `ghi-phu.php` nay ghi ảnh `p2-` với sort_order 500+N.

## 2026-11-18 — bộ ảnh phụ đèn pha hoàn chỉnh (02, 03, 04, 05, 06, 07) cho 72 mã

- Backup DB `20261118T001153Z-db`; một lượt ghi `product_images` cho 72 đèn pha: thêm 160 dòng, đổi 168 dòng (không xóa, không dời). Rollback `state/noi-dung-20261117/phu-rollback-0711.json`.
- 02 và 07 thay ảnh dùng chung bằng ảnh theo từng mã (sản phẩm thật đứng ở chỗ trống của mẫu; 07 lấy "BẢO HÀNH" theo dữ liệu); 03 "Đặc điểm nhận diện" và 04 "Nạp năng lượng" thêm mới 72 mã (04 lấy 4 dòng + thời gian sạc/chiếu từ dữ liệu); 05 đổi 24 mã (nhãn đèn quá dài từng bị cắt chữ, nay tách 2 dòng) và thêm 8 mã mới; 06 thêm 8 mã mới.
- 8 mã mới (JD-6128, BCT-WW5.0-MONO, JD-T100, DB60100, DB3-PRO ×4) dựng được nhờ nguồn `v9-input`; điểm chỉ ảnh 05 của 8 mã này là ước lượng.
- Kiểm: 72 mã đủ 02–07, ORIANA không có 08 (chưa có giấy chứng nhận), không thiếu file trên server, ảnh trả 200.
- Chưa làm: các danh mục khác; ẩn ảnh cũ "Chứng nhận & ủy quyền" (nhóm `vip1-12-*`) trên ORIANA (chờ chủ website quyết định).

## 2026-11-18 — ảnh phụ 05 "Bộ sản phẩm gồm" cho 64 đèn pha

- Backup DB (lần ghi ảnh 05); thêm 64 dòng `product_images` slot sort_order 4 (`p2-05-<hash>.jpg`): nền mẫu 5 đã xóa chữ, sản phẩm thật trên bục, `package_contents` nguyên văn, nhãn đèn/tấm pin/remote có đường chấm trỏ đúng vật (điểm chỉ ghi tay theo từng mã, nhãn remote chỉ khi ảnh có remote). Rollback `state/noi-dung-20261117/phu-rollback-*.json`.
- Chưa làm: 8 mã không tách nền (BCT-WW5.0-MONO, JD-T100, JD-6128, DB60100, DB3-PRO ×4).

## 2026-11-17 — release 3fb268fe: một ô kéo thả nhiều ảnh cho thư viện ảnh sản phẩm và album (Admin)

- Runtime source: commit `3fb268fef6d00ef4c5296fff5510ed1bf901c4c1` (đã push). Image backend `sha256:818e4f265edd264e245899c78774becb453377b0f6fcf6147e4c7009f4079250`,
  gói `/srv/pts-laravel13/releases/3fb268fe/` SHA-256 khớp, ID image trên VPS khớp manifest. Build từ worktree sạch (không chứa `ContentBatchPatch.php`). Nuxt giữ `a66efc27-candidate`.
- Phạm vi: `Fields::bulkImages()` thêm ô "Kéo thả nhiều ảnh vào đây" phía trên danh sách thư viện ảnh ở Sản phẩm và Album: mỗi tệp lưu ngay và thêm thành một dòng thư viện (hiển thị, không chú thích),
  ô tự dọn, không thêm trùng ảnh đã có; trước đó phải bấm "Thêm ảnh" cho từng ảnh. Chỉ đổi Admin, trang khách không đổi.
- Kiểm: backend 414 pass/1 skip, Pint sạch; trình duyệt thật trên Admin local (tài khoản thử đã xóa): 5 tệp một lần thêm 5 dòng, lô thứ hai 2 tệp thêm 2 dòng, Lưu được. PHPStan chỉ báo 2 lỗi ở
  `ContentBatchPatch.php` (file chưa commit của việc khác, không nằm trong image).
- Backup `20261117T082538Z-db`; thay backend, worker, scheduler; HTTPS 6 đường chính 200, mã mới có trong image, log 3 phút 0 lỗi. Chưa kiểm trên Admin production (không dùng tài khoản thật).
- Rollback: `releases/rollback-3fb268fe/stack.env.before` (backend `a66efc27-candidate`).

## 2026-11-17 — dữ liệu: thứ tự hãng và danh mục đồng bộ với bảng giá (không đổi code)

- Mọi danh sách hãng (bộ lọc "Thương hiệu", dải logo ở trang sản phẩm, trang hãng, tìm kiếm, trang giới thiệu) lấy từ `brands.sort_order` rồi `id` giảm dần; dải logo trang sản phẩm là hãng có
  sản phẩm trước rồi hãng chưa có sản phẩm. Đặt lại 22 hãng: BLUE CARBON 1, ORIANA 2, SolaX 3, JINDIAN 4, EPSIVO 5, Hober 6 (theo số file bảng giá), rồi hãng tấm pin theo bảng giá
  (LONGi, Canadian Solar, AESOLAR, Astronergy, TCL Solar), rồi các hãng chưa có sản phẩm theo thứ tự dải logo cũ. Backup `20261117T071156Z-db`; rollback `state/noi-dung-20261117/rollback-thu-tu-hang.json`.
- Danh mục con trước đó cùng `sort_order = 1` nên sidebar hiện ngược (Sân Vườn, Ốp Trần, UFO, Đường, Pha) trong khi sản phẩm xếp theo bảng cứng của chủ site (Pha, Đường, Sân vườn, UFO, Ốp trần;
  `CatalogFilters::defaultOrder`). Đặt lại 10 danh mục: đèn theo thứ tự đó; Hybrid trước Hòa lưới, Pin/Battery → BMS/Rack → ESS, PWM trước MPPT (đúng bảng giá). Backup `20261117T071300Z-db`;
  rollback `state/noi-dung-20261117/rollback-thu-tu-danh-muc.json`. Kiểm qua API catalog và chụp trang thật: sidebar, dải logo, danh mục đều khớp.
- Thứ tự cứng trong code (`defaultOrder`) khớp với dữ liệu; nếu sau này đổi thứ tự danh mục đèn trong Admin, cần đổi cả bảng đó.

## 2026-11-17 — dữ liệu: thứ tự toàn bộ sản phẩm theo 8 bảng giá (không đổi code)

- Mở rộng đợt JINDIAN cho cả 399 sản phẩm: `products.sort_order = số file × 1000 + thứ tự đầu mục trong bảng giá` (1 BLUE CARBON, 2 ORIANA và SolaX inverter,
  3 JINDIAN và biến tần bơm, 4 đèn sân vườn và pin Blue Carbon/MPPT, bảng giá tấm pin không số = 8). Sản phẩm không có trong bảng giá nào = 9000 + sort cũ (xuống cuối
  danh mục, giữ thứ tự cũ). Nguồn là sheet đang mở của từng file; khớp mã bằng ranh giới (DB-7100 khác DB-71000), 374/399 khớp tự động, 8 mã gán tay
  (trụ 6m ba cỡ, M25, hai tấm pin ORIANA, hai AELIO P60B200), 25 sản phẩm không có trong bảng giá (cáp, ruột gà, băng keo, quạt JD, ốp trần JD-L400, Sharp, LONGI450...).
- 399 dòng audit `product.sort_order_price_list`; backup `20261117T070820Z-db`; rollback `state/noi-dung-20261117/rollback-thu-tu-toan-bo.json` (id, mã, sort_order cũ).
  Kiểm qua API catalog 28 danh mục: đúng thứ tự bảng giá; chỗ lệch 1 cặp ở vài danh mục là do sản phẩm chưa có ảnh luôn xuống cuối (quy tắc sẵn có).

## 2026-11-17 — dữ liệu: thứ tự sản phẩm JINDIAN theo bảng giá (không đổi code)

- Nguồn thứ tự: `Bảng giá/3. BẢNG GIÁ JD 29.05.xlsx`, sheet đang mở "Bảng giá JINDIAN in" (có JD-V100–V500). Danh sách chỉ xếp theo `products.sort_order`
  (rồi `id` giảm dần; sản phẩm không ảnh luôn xuống cuối) nên chỉ đổi cột này. Quy ước dải: hãng thứ 3 theo số thứ tự file bảng giá = dải 300+
  (BLUE CARBON 0/1 và ORIANA 2 giữ nguyên, nên danh sách hiện là BLUE CARBON → ORIANA → JINDIAN).
- 36 sản phẩm: 21 đèn pha, 11 đèn đường (gồm #347 JD-L200NEW chưa gán hãng), 4 cần đèn (đang ẩn); `sort_order` 301–336 theo đúng thứ tự bảng giá,
  bản ánh sáng vàng (-VANG) ngay sau bản trắng. Ghi có điều kiện (chỉ đổi khi giá trị cũ còn nguyên), 36 dòng audit `product.sort_order_price_list`.
- Backup `20261117T070154Z-db`; rollback `state/noi-dung-20261117/rollback-thu-tu-jindian.json` (id, mã, sort_order cũ). Kiểm qua API catalog: đèn pha và đèn đường,
  JINDIAN đúng thứ tự bảng giá, đèn pha thành ba khối BLUE CARBON → ORIANA → JINDIAN.
- Chưa làm: BLUE CARBON và ORIANA (sắp theo bảng giá của hãng); các mã ORIANA nằm trong file JD (đèn trụ cổng, treo tường, UFO, ốp trần) thuộc bước ORIANA.

## 2026-11-17 — release a66efc27: mô tả đóng mặc định, bỏ zoom rê chuột, đèn đủ 4 chỉ số đầu trang; ảnh bộ VIP1

- Runtime source: commit `a66efc277a49abb44a1c2ffdd6224302352266b2` (đã push). Image backend
  `sha256:d028fd408bba7f71318c0518ddf7bcab02d057fbe5ae701e16ed689758ff08e5`, Nuxt
  `sha256:498d35efb9fb16dfb9e6512c50962313820c3c4a5295864f4b1fac0a5d6f5e57`; gói `/srv/pts-laravel13/releases/a66efc27/`
  SHA-256 khớp, ID image trên VPS khớp manifest. Build từ worktree sạch `pts-release-e5fb18cd` (checkout đúng commit); image không chứa
  `ContentBatchPatch.php` hay `resources/product-gallery` của việc khác.
- Phạm vi code: khối "Xem mô tả chi tiết" đóng mặc định (một trạng thái `descriptionOpen` điều khiển cả nút trong khối trên máy tính và nút
  "Xem toàn bộ mô tả" trên điện thoại; chip Tổng quan mới mở mô tả); bỏ hiệu ứng zoom khi rê chuột trên ảnh chính (bấm vẫn mở ảnh lớn, gợi ý
  "Bấm để xem ảnh lớn"); đèn thiếu quang thông/chiều cao dùng thời gian chiếu sáng, tấm pin, chuẩn IP để đủ chỉ số đầu trang
  (203/203 sản phẩm đèn có 4 chỉ số, trước đó 111 sản phẩm chỉ có 2).
- Kiểm trước: backend 413 pass/1 skip, PHPStan 0, Pint sạch; frontend 515 pass, typecheck 0; ProductSpecs bản mới chạy thử chỉ đọc trên container
  production với 353 sản phẩm hiển thị, không lỗi. Bản Nuxt mới còn chạy thử ở local (cổng riêng, API thật) trên máy tính và điện thoại.
- SỰ CỐ trong lúc phát hành: bản đầu `e5fb18cd` đóng mô tả mặc định nhưng trang điện thoại ẩn nút trong khối và chỉ dùng nút bên dưới, nút đó chỉ
  đổi chiều cao khung cắt, nên khách điện thoại không mở được mô tả. Phát hiện bằng kiểm trình duyệt ngay sau khi đổi image; Nuxt được trả
  về `748efd01-candidate` trong vài phút (backend giữ bản mới), rồi sửa ở `a66efc27` và chạy thử local trước khi đổi lại. Bài học: đổi hành vi
  mặc định của khối đã có logic riêng cho điện thoại thì phải chạy thử điện thoại trước khi đổi image.
- Lỗi thao tác: lệnh compose đầu thiếu `-p pts-laravel13` nên không thay được container và tạo ba mạng thừa `pts_laravel13_candidate_*`; đã xác minh
  không có container gắn vào và xóa. Từ nay luôn dùng `docker compose -p pts-laravel13 -f docker-compose.yml -f docker-compose.production.yml --env-file runtime/stack.env`.
- Dữ liệu cùng ngày (không đổi code): nội dung 325 sản phẩm (lô 04–28) và meta SEO 54 sản phẩm (rollback
  `state/noi-dung-20261117/rollback-lo-04-28.json`, `rollback-seo-54.json`, `rollback-hype-5.json`); bộ ảnh VIP1 cho 43 sản phẩm đèn (avatar =
  ảnh số nhỏ nhất, thư viện = phần còn lại, ảnh Shopping giữ nguyên; ảnh thư viện cũ chỉ ẩn; rollback
  `state/noi-dung-20261117/rollback-anh-vip1.json`).
- Backup trước khi đổi image: `20261117T060136Z-db` và `20261117T063601Z-db`. Trên VPS chỉ thay backend, Nuxt, worker, scheduler; DB, Caddy, ZCRM, training giữ nguyên.
  Sau đổi: HTTPS `/`, trang sản phẩm, `/health`, `/api/v1/site`, `/google-shopping.xml` đều 200; trên trang thật, máy tính và điện thoại đều có mô tả đóng lúc đầu, mở/đóng
  được, rê chuột không phóng to, 4 chỉ số, 0 lỗi JS; log 3 phút sau: 0 lỗi ở bốn dịch vụ.
- Rollback: `releases/rollback-a66efc27/stack.env.before` (backend `e5fb18cd-candidate`, Nuxt `748efd01-candidate`) hoặc `releases/rollback-e5fb18cd/stack.env.before`
  (cả hai `748efd01-candidate`); image cũ còn trên VPS.
- Rủi ro còn lại: baseline API (`product-detail`...) chưa lưu lại sau đợt ảnh và nội dung; lượt 2 viết lại mô tả bằng Codex chưa chạy (Codex hết hạn mức).

## 2026-11-17 — release 748efd01: "Chuẩn IP" thay "cấp bảo vệ"; dữ liệu thông số và mô tả

- Runtime source: commit `748efd0154f188665437a041d9b5e34c2dd3a839` (đã push). Image backend
  `sha256:3c31838d5e928621b5e17280686edc0e47ba2971e404024c5b371fedc8366e79`, Nuxt
  `sha256:1d918f6c5caf9918017aeac90310d49fe5d02bb2930d68b5c2a4c51e93621213`; gói `/srv/pts-laravel13/releases/748efd01/`
  SHA-256 khớp, ID image trên VPS khớp manifest. Build từ worktree sạch đúng commit (thư mục master có file chưa commit
  của việc khác, không được đóng vào image; đã kiểm `ContentBatchPatch.php` không có trong image).
- Phạm vi code: bảng thông số đèn tách "Kích thước" và "Chuẩn IP"; nhãn `waterproof_rating` mọi profile là "Chuẩn IP";
  dòng nhập tay "Cấp bảo vệ"/"IP rate" hiển thị "Chuẩn IP"; ô trống không còn che dòng nhập tay. Codex review chéo:
  lần 1 NO-GO (dòng nhập tay bị che), lần 2 GO, các điểm "nên sửa" đã sửa. Backend 405 pass/1 skip, Pint, PHPStan sạch.
- Dữ liệu (không đổi code, trước release): `pts:spec-keys --apply` gắn ô thông số cho 265 sản phẩm (265 dòng audit
  `product.spec_keys` kèm giá trị cũ; backup `20261117T014351Z-db`); 49 sản phẩm Blue Carbon không phải đèn chuyển mô tả
  sang kế thừa danh mục (content_sections mode inherit/category; audit `product.content_inherit_category`; backup
  `20261117T014545Z-db`); gỡ 4 ảnh gallery SolaX sai concept (audit `product.gallery_remove`; file ở
  `state/removed-solax-spec-cards-20261117/`; backup `20261117T013643Z-db`).
- Checkpoint bỏ qua có chủ ý: không dựng candidate cô lập với DB phục hồi (không migration, thay đổi chỉ ở nhãn bảng thông số);
  thay bằng bộ test đầy đủ và kiểm trực tiếp sau khi đổi image, rollback image sẵn trên VPS.
- Trên VPS chỉ thay backend, Nuxt, worker, scheduler; DB, Caddy, ZCRM, training giữ nguyên. Backup trước đổi image:
  `20261117T020243Z-db`. HTTPS 9 route chính 200; DB02-1 hiện "Kích thước 680 × 273 mm" và "Chuẩn IP IP66"; log 3 phút
  sau: 0 lỗi ở backend/Nuxt/worker. Không còn nhãn "cấp bảo vệ" trong product-detail.
- Baseline API cập nhật từ production: product-detail, product-extras, catalog, brand-detail, home, search (ảnh chuẩn hóa
  16/11, nhãn IP, thông số gắn ô). Không lưu news, news-detail, site: lệch do bài và GA4 của việc khác.
- Rollback: `releases/rollback-748efd01/stack.env.before` + image `e94a3f71-candidate` còn trên VPS; dữ liệu theo audit/backup ở trên.

## 2026-11-16 — dọn dung lượng VPS và cập nhật chính sách backup

- Theo checkpoint vận hành VPS: mức sử dụng đĩa giảm từ 94% xuống 79%. Đã xóa
  9 full backup PTS cũ, backup/ảnh PHP cũ, 2 bản build Laravel cũ và build cache
  cũ hơn 72 giờ. Không đụng Zalo CRM, sales-training hoặc MCP staging.
- Stack website đang chạy là `/srv/pts-laravel13`; thao tác/backup website không
  dùng stack PHP cũ `/srv/phucthinhsolar`. Backup ứng dụng hiện nằm trong
  `/srv/pts-laravel13/backups/`; backup DB trước khi cập nhật ảnh là
  `/srv/pts-laravel13/backups/20261116T095908Z-db`.
- Thay đổi source trong nhánh vận hành: full backup mặc định giữ 2 bản mới nhất
  (`BACKUP_KEEP_FULL`, tối thiểu 1); DB-only tiếp tục theo
  `BACKUP_RETENTION_DAYS` (mặc định 7 ngày). Bản vừa tạo được bảo vệ. Test script
  bằng thư mục giả tạo 5 full + 10 DB xác nhận giữ 2 full mới nhất, giữ DB trong
  cửa sổ 7 ngày, xóa DB cũ hơn, bảo vệ bản mới và không chạm thư mục ngoài mẫu.
- Ảnh đại diện sản phẩm (dữ liệu, không đổi code): 167 sản phẩm đổi `products.image`
  sang bản chuẩn 1600 px `*-chuan1600.jpg` (căn giữa, cạnh dài sản phẩm 80%/72%
  khung). File cũ giữ nguyên trong `upload/product/`; UPDATE có điều kiện ảnh cũ,
  trong một transaction, 167/167 dòng; 1 dòng `admin_audit_events`
  `product.image_standardise`. Rollback: CSV
  `/srv/pts-laravel13/state/hero-1600-20261116/rollback-anh-dai-dien-20261116.csv`
  (id, ảnh cũ, ảnh mới) + backup DB `20261116T095908Z-db`.
- Script backup: `backup.sh` + `backup-retention.sh` cài vào
  `/srv/pts-laravel13/scripts/` (systemd gọi bản ở đó; không đi qua image). Bản
  `backup.sh` cũ lưu cạnh với hậu tố `.bak-20261116`. Nếu thiếu
  `backup-retention.sh`, `backup.sh` quay về xoay theo ngày như trước.
- Rollback: chép lại `backup.sh.bak-20261116`; ảnh theo CSV ở trên; file đã dọn
  chỉ phục hồi được từ snapshot CloudFly.

## 2026-11-13 — release e94a3f71: feed dùng ảnh chính, chữ Giải pháp rút gọn, gom bài nháp

- Runtime source: commit `e94a3f718f74d3a82a4b74bee349d115eb1c4084` (đã push `origin/master`). Image backend
  `sha256:00a475ebd43bb8c9fe951004ecbe3740f7dcd69809fb81c44ff56f2fb2e3c051`, Nuxt
  `sha256:839d6eeb5ddc3d210bb89e4d670530803ff1d2a125ff30ad6530d9417f15def9`; gói
  `/srv/pts-laravel13/releases/e94a3f71/` SHA-256 khớp, ID image trên VPS khớp manifest.
- Phạm vi: feed `/google-shopping.xml` dùng ảnh chính (từ 500 px) khi sản phẩm chưa có ảnh Shopping (170 → 304 sản phẩm);
  chữ CTA, phần giới thiệu và 5 tóm tắt giải pháp rút gọn (chủ website duyệt bảng so sánh); gom vào master code và 3 bài
  nháp ẩn PTS-KT-022..024 từ ba worktree biên tập (chưa nhập vào website).
- Migration: `2026_11_13_000200_shorten_solutions_copy` (chỉ thay dòng còn đúng chữ cũ của production, băm sau khi bỏ thẻ và
  gộp khoảng trắng; chữ chủ website tự sửa sẽ được giữ). Đã chạy, chạy lại không đổi gì.
- Backup trước thay đổi: `/srv/pts-laravel13/backups/20261113T032333Z-full` (checksum đạt). Rollback: image
  `f62158bb-candidate` còn trên VPS; env và ID cũ ở `/srv/pts-laravel13/releases/rollback-e94a3f71/`.
- Candidate cô lập với DB phục hồi từ backup production: mọi API đồng nhất trừ đúng chữ Giải pháp (chủ ý) và nội dung tin
  chủ website đã đăng sau lần đối chiếu trước; backend 403 pass/1 skip, PHPStan 0; frontend 515 pass. Baseline `home`, `news`,
  `news-detail`, `solution-detail`, `solutions` cập nhật ở commit kế tiếp.
- Trên VPS chỉ thay backend, Nuxt, worker, scheduler; DB, Caddy biên, ZCRM, training giữ nguyên. HTTPS 11 route chính 200,
  `/lienhe` và `/tru-den` 301 đúng đích, feed 304 mục, tiêu đề CTA mới hiển thị. Log 4 phút sau: 0 lỗi ở bốn dịch vụ; proxy
  2 dòng 5xx đúng lúc thay container.
- Rủi ro còn lại: chưa nhập mã GA4; 3 bài nháp chưa import; Google có thể từ chối riêng từng sản phẩm dùng ảnh chính có chữ hoặc
  logo; backup chỉ nằm trên VPS.

## 2026-11-13 — release f62158bb: SEO/Merchant, mobile, tốc độ, GA4

- Runtime source: commit `f62158bb9b68bd649ee7306adccc23f4cbe5f764` đã push `origin/master`. Image backend
  `sha256:0957e9ed2651e0b97e4db56510f95af0772a9cf13dce26da0e17082c863cb6e8`, Nuxt
  `sha256:ea1501bb9f1e3b416006fec1e26344987bc3b8264d6b7e19e8765c2ecd496810`. Gói
  `/srv/pts-laravel13/releases/f62158bb/pts-release-f62158bb-images.tar.gz` (~408 MiB), SHA-256 khớp máy build và VPS,
  ID image trên VPS khớp manifest.
- Phạm vi: Offer `availability` + `seller`, BreadcrumbList theo danh mục, `og:type=product`, 30 trang thiếu meta
  description có mô tả tự sinh, title chỉ thêm hậu tố thương hiệu khi tổng ≤ 65 ký tự; chuyển trang mobile cuộn sau khi
  trang mới sẵn sàng, header không trượt, khối đầu danh mục giữ chiều cao; H1 trang chủ hiện ngay khung đầu (LCP);
  chuyển hướng 21 link cũ còn 404 + `/tru-den` một bước; lối tắt giải pháp bỏ danh mục không có sản phẩm; ô Admin
  "Mã đo Google Analytics 4" (trống mặc định, chưa bật).
- Migration: một cái (`2026_11_13_000100`, thêm 21 dòng `redirects`, chạy lại an toàn). Không đổi bảng nào khác.
- Backup trước thay đổi: `/srv/pts-laravel13/backups/20261113T025609Z-full` (6/6 checksum đạt). Rollback: image cũ
  `83a74afa-candidate` còn trên VPS, env và ID cũ ở `/srv/pts-laravel13/releases/rollback-f62158bb/`.
- Candidate cô lập (DB phục hồi từ `20261112T231500Z-db` của production): `verify-candidate.sh` mọi API đồng nhất trừ
  đúng các chỗ chủ ý (seo.title 253 sản phẩm, 5 slug cũ chuyển hướng, `ga4Id`, equipment bỏ Trụ đèn); backend 402
  pass/1 skip, PHPStan 0; frontend 515 pass, typecheck 0. Baseline 5 API đã cập nhật vào Git ở commit tiếp theo.
- Trên VPS: chỉ thay backend, Nuxt, worker, scheduler của stack `pts-laravel13`; DB, Caddy biên, ZCRM, training giữ
  nguyên. HTTPS 14 route chính trả 200, 5 link cũ trả 301 đúng đích, feed 170 sản phẩm, JSON-LD có InStock và 5 bước
  breadcrumb. Log 6 phút sau: backend/Nuxt/worker/scheduler 0 lỗi; proxy 2 dòng lỗi DNS đúng lúc thay container.
- Đo điện thoại giả lập (4x CPU, ~4G) trang chủ: LCP 3,7 s → 1,1 s.
- Rủi ro còn lại: chưa nhập mã GA4 (cần chủ website xác nhận mã); backup chỉ nằm trên VPS; JavaScript khoảng 430 KB
  (TBT cao trên máy yếu).

## 2026-11-12 — release 83a74afa: nội dung biên tập, danh mục, Admin và lịch bài đăng

- Runtime source: commit `83a74afa998fa5cfbc6e1945044cf7466fee99f8` đã push lên `origin/master`.
  Image backend `sha256:bfc92a71065a4f30a73c7e591a6c0955829eefefc0925280986f02a52b72c6e2`,
  Nuxt `sha256:03b0594d0c65613127f9fa972f5fd251e7d8ea94e6008212917ddeb9ec3217a3`.
  Gói `/srv/pts-laravel13/releases/83a74afa/pts-release-83a74afa-images.tar.gz`
  (~408 MiB) đã kiểm SHA-256 và đối chiếu image ID giữa máy build/VPS.
- Backup full trước migration/import: `/srv/pts-laravel13/backups/20261112T094236Z-full`,
  checksum 6/6 file đạt. Giữ image release `d2c529e0` và bản env/image ID trước đổi ở
  `/srv/pts-laravel13/releases/rollback-83a74afa/`. Không phục hồi DB theo quán tính.
- Candidate cô lập phục hồi DB production (399 sản phẩm, 29 bài cũ), chạy thành công 6 migration
  `002000`, `2026_11_11_000100`, `000200`, `000300`, `2026_11_12_000100`, `000300`.
  Backend Laravel **399 pass / 1 skip**, 2.785 assertions; Pint 455 file, PHPStan 0 lỗi;
  frontend 513/513 test, Nuxt typecheck và production build đạt.
  `verify-candidate.sh` hoàn tất sau khi cập nhật 7 snapshot có thay đổi đã kiểm duyệt;
  site-audit 0 lỗi trên 20 route × 4 viewport; shop-flow đạt, không tạo đơn.
- Trên VPS: đã áp đủ 6 migration và chỉ thay backend, Nuxt, worker, scheduler thuộc stack
  `pts-laravel13`. MariaDB volume, Caddy biên, ZCRM và training được giữ nguyên.
  Kiểm HTTPS `/`, `/tin-tuc/`, `/san-pham/`, `/admin/login`, `/api/v1/site`,
  sitemap, feed và ảnh bài viết trả 200; Admin plan/calendar trả 302 về đăng nhập đúng phân quyền.
  Các service mới healthy; scan log ngắn sau deploy: 0 fatal/uncaught/SQLSTATE/connection-refused.
- Nội dung versioned: `release-editorial-content.sh --apply` hoàn tất sau backup và
  diễn tập clone DB. Ba bài hiện hữu được cập nhật có guard, thay 5 bìa và bỏ 2 banner lỗi thời;
  tạo thêm 18 bản thảo ẩn. Tổng sau release: **50 bài (28 đang hiển thị)**, **399 sản phẩm**,
  **180 mục kế hoạch từ 13/11/2026 đến 11/01/2027**, **21 liên kết bài**, **0 bìa thiếu**.
  Lịch biên tập không tự công khai; việc duyệt bài, gán sản phẩm và chọn ngày đăng vẫn thuộc Admin.
- Snapshot API đã đạt đầy đủ **trước khi import nội dung**. Sau khi thay dữ liệu bài viết có chủ đích,
  snapshot `news` và `news-detail` cũ của image đang chạy sẽ lệch về nội dung/bìa.
  Baseline sau import được cập nhật vào Git trong một commit dữ liệu/tài liệu kế tiếp; nó có hiệu lực
  trên runtime khi xây image release tiếp theo, **không tự động sửa image đã deploy**.
- Rủi ro còn lại: backup full vẫn chỉ ở VPS, chưa có bản sao off-host riêng;
  chưa chạy audit trình duyệt có đăng nhập cho mọi màn Admin trên chính production.
  Không xóa ảnh/media cũ hoặc backup rollback.

## 2026-11-10 — category tree + Admin updates (d2c529e0)

- Commit/image: `d2c529e03111cc3e7fceeef07f1e384951b5295c`; backend image ID
  `sha256:ec8f378c5b776ed5dc9843d99522b2ae61c2e47173f3f7d1bd33ca898e811c96`,
  Nuxt image ID `sha256:84f213156edf430e5cb69b8dbc0381154d576f486be0041b3e316059e12d4343`.
  Exact images were built and verified on candidate, transferred as
  `/srv/pts-laravel13/releases/pts-release-d2c529e0-images.tar.gz` (SHA-256
  `21408e94a7e6062ec822eda0e647ab8a66a9ff20d064be8444b6a9d9bf9cbbf0`), then
  loaded on the VPS. Commit `d2c529e0` is pushed to `origin/master`.
- Backup before migration: `/srv/pts-laravel13/backups/20261110T085016Z-full`;
  all 6 files passed `SHA256SUMS`. Rollback environment copy and previous image
  IDs are in `/srv/pts-laravel13/releases/rollback-d2c529e0/`.
- Applied migrations `001700`, `001800`, `001900` (batch 2). Total products
  stayed at 399, orphan products 0, and no garden-light product remains in its
  old parent. Counts are 53 gate lights, 9 wall lights, 5 garden stake lights,
  and 1 lawn light. Two old category URLs now return 301 to their new paths.
- Candidate rehearsal restored the pre-deploy production DB backup into an
  isolated DB, applied all three migrations, matched 1,754/1,754 API snapshot
  cases after refreshing only the seven affected category snapshots, and passed
  `verify-candidate.sh`. `shop-flow` passed without submitting an order.
- Gates: Laravel 293 passed/1 skipped/1,842 assertions; Pint 392 files; PHPStan
  0 errors; Nuxt 500/500 tests, typecheck and production build passed. Admin
  browser audit opened 94 desktop/mobile/deep screens with no server or JS
  errors; it reported a 24 px table overflow on desktop Products, while the row
  actions remained visible. This small Admin layout issue is a follow-up.
- Production after deploy: backend/Nuxt healthy; worker and scheduler running;
  recent Laravel error count 0. Home, Admin login, site API, four new category
  pages and four representative product pages returned 200; old garden and
  clamp paths returned 301; sitemap lists all four garden-light child paths.
  Caddy, MariaDB, training and ZCRM were not replaced or reconfigured.
- Rollback point: previous backend/Nuxt image tags are `f5aac548-candidate`;
  restore the saved `stack.env.before` and recreate only backend, Nuxt, worker
  and scheduler. The data-only category reshuffle is forward-compatible; do not
  restore the DB backup blindly after new orders or Admin writes.
- Remaining operational risk: this backup is on the VPS only; a separate
  off-host copy and a full Caddy route/DB restore rollback rehearsal remain
  outstanding. The site is live on the new release.

## 2026-11-10 — dọn môi trường sau cutover, backup chuyển sang stack mới

- Chủ website duyệt từng mục trước khi xoá. Bản lưu của site PHP cũ đã tải về
  máy chủ sở hữu (`D:\ProjectBackup\phucthinhsolar-php-pre-laravel-20261110`,
  1,1 GB, `sha256sum -c` khớp 5/5 file) gồm dump DB 10:44, dump DB cuối lúc
  11:34, `upload.tar.gz` (1.470 file), cấu hình stack và Dockerfile cũ.
- Đã xoá: môi trường test `phucthinhsolar-staging` (4 container, 3 volume,
  `/srv/phucthinhsolar-staging`, cổng nội bộ 18080/18443); 3 container PHP cũ
  `web`/`nuxt`/`db` và volume `phucthinhsolar_db_data`; dữ liệu ứng dụng cũ
  (`shared`, `releases`, `incoming`); 53/57 thư mục backup cũ (giữ 4 bản gần
  nhất, trong đó có bản ngay trước cutover); 8 tag ảnh PHP cũ; 13,36 GB build
  cache; 4 volume mồ côi (datadir MariaDB rỗng).
- Giữ lại có chủ đích: `phucthinhsolar-proxy-1` (Caddy biên, cổng 80/443 và
  chứng chỉ TLS của cả 3 tên miền) — `docker-compose.yml` của project
  `phucthinhsolar` nay chỉ còn service `proxy`, bản đầy đủ ở
  `docker-compose.yml.pre-laravel-20261110`. Giữ ảnh `2d151211971a` (bản PHP
  chạy cuối) và `9ad585a6ece0` (bản trước đó), thư mục `secrets/` và `state/`.
- Backup: hai timer cũ (`phucthinhsolar-backup`, `phucthinhsolar-db-backup`) đã
  tắt và gỡ unit vì DB chúng dump không còn. Thay bằng
  `pts-laravel13-backup.timer` (full 02:40 hằng ngày) và
  `pts-laravel13-db-backup.timer` (DB mỗi 6 giờ, phút 15), chạy
  `deploy/laravel13/scripts/backup.sh`, giữ 7 ngày. Đã chạy thử một bản full:
  `/srv/pts-laravel13/backups/20261110T043603Z-full`, checksum tự kiểm khớp.
- **Backup vẫn chỉ nằm trên chính VPS** — chưa có bản sao ngoài máy chủ. Script
  in cảnh báo này mỗi lần chạy.
- Đĩa: 95 GB → 54 GB đã dùng (83% → 47%). Cổng còn mở: 22, 80, 443 ra ngoài;
  18085 (kiểm tra nội bộ stack Laravel), 5544 và 6381 (ZCRM) chỉ trên 127.0.0.1.
- Kiểm tra sau mỗi bước xoá và lần cuối: 469/469 URL sitemap 200, `/admin/login`,
  feed, API 200, `zcrm` và `training` 200, `www` 301.

## 2026-11-10 — chuyển website sang Laravel 13 + Nuxt (f5aac548)

- Commit/image: `f5aac548` / `pts-laravel13-backend:f5aac548-candidate`,
  `pts-laravel13-nuxt:f5aac548-candidate`. Stack mới `pts-laravel13` (6 container).
  Stack PHP cũ `phucthinhsolar` vẫn chạy nguyên để rollback.
- Phạm vi: toàn bộ website. Caddy biên đưa `/api/v1/*`, `/admin*`, `/livewire*`,
  `/upload/*`, `/thumbs/*`, `/sitemap.xml`, `/google-shopping.xml` sang Laravel;
  mọi trang còn lại sang Nuxt.
- Backup trước cutover: `/srv/phucthinhsolar/backups/20261110T034425Z-full`.
  Caddyfile cũ giữ ở `/srv/phucthinhsolar/Caddyfile.pre-laravel-20261110T034425Z`.
- Dữ liệu nhập lại từ bản PHP: 399 sản phẩm, 29 bài, 42 banner, 469 mục SEO.
  Sau đó `migrate --force`, `pts:normalize-specs --apply` (còn 0 sản phẩm cần
  chuẩn hóa), `cache:clear`, `settings.gtm_id = GTM-57TJSRVM`.
- Sự cố 1 — site 503 từ 11:07 đến 11:12: `/srv/phucthinhsolar/Caddyfile` bị thay
  bằng file khác inode, nên bind mount trong container vẫn giữ file cũ và
  `caddy reload` chỉ nạp lại chính file cũ đó. Sửa bằng
  `docker restart phucthinhsolar-proxy-1` để Docker gắn lại đúng file trên host.
- Sự cố 2 — mail form liên hệ không gửi được: container `worker` chỉ nằm trên
  mạng `data` (`internal: true`) nên không phân giải được tên máy chủ SMTP
  (`getaddrinfo ... Temporary failure in name resolution`). Backend không lộ lỗi
  này vì nó nằm cả trên `edge` và chỉ xếp mail vào hàng đợi. Sửa ở `e126fe41`:
  worker thêm mạng `egress`, tạo lại container; mail đang chờ gửi xong lúc
  11:21:29, `jobs=0 failed=0`.
- Kiểm tra sau deploy: 469/469 URL trong sitemap trả 200; `/admin/login` 200 và
  tài nguyên Filament 200; `POST livewire-*/update` trả 419 (đúng, thiếu CSRF);
  `/google-shopping.xml` 170 sản phẩm; ảnh `/thumbs/*` 200; `www` 301 về
  non-www; `zcrm` và `training` 200; trang chủ có `GTM-57TJSRVM`; chứng chỉ TLS
  hạn 16/12/2026; log Laravel không có ERROR; cổng 18085 chỉ mở trên 127.0.0.1.
- Một lead kiểm tra đã gửi qua form thật để xác minh đường mail — trái quy tắc
  "không gửi lead thật khi test" trong `docs/16-laravel-migration/HANDOFF.md`.
  Bản ghi lead và outbox đã xóa ngay sau khi xác minh, nhưng hộp thư nhận thông
  báo của Admin có một email tiêu đề "Kiểm tra sau cutover 10/11".
- Rollback: `cp /srv/phucthinhsolar/Caddyfile.pre-laravel-20261110T034425Z
  /srv/phucthinhsolar/Caddyfile && docker restart phucthinhsolar-proxy-1`. Stack
  PHP cũ và DB cũ còn nguyên; DB mới tách riêng nên không cần rollback dữ liệu.
- Rủi ro còn lại: công cụ ước tính đang tắt (trang 404 đúng thiết kế, chủ website
  bật trong Admin khi duyệt đơn giá); chưa có Consent Mode v2; đĩa VPS dùng 83%.

## 2026-11-07 — bản vá giao diện điện thoại (2d151211)

- Commit/image: `2d151211` / `phucthinhsolar-web:2d151211971a`, `phucthinhsolar-nuxt:2d151211971a`. AUTO chọn FAST.
- Phạm vi: thẻ "Đã thêm vào giỏ" mở phía trên dock, "Xem thêm" không đè thanh mua,
  thẻ Tầm nhìn – Sứ mệnh không bị ẩn, thẻ trang chủ không giãn chữ.
- Backup DB trước deploy; HTTP smoke, Playwright production 18/18, log scan PASS.
  Không có 503.

## 2026-11-07 — logo đối tác, bộ lọc chọn nhiều, hotline kỹ thuật (9ad585a6)

- Commit/image: `9ad585a6` / `phucthinhsolar-web:9ad585a6ece0`, `phucthinhsolar-nuxt:9ad585a6ece0`. SAFE.
- Phạm vi: 20 logo đối tác (Giới thiệu và đầu trang Sản phẩm), drawer bộ lọc
  giữ mở khi chọn nhiều, banner giải pháp trên điện thoại, migration 077 và 078.
- Dữ liệu production, có backup DB ngay trước mỗi lần ghi:
  - 077 thêm 9 thương hiệu (Sigenergy, Deye, Anker SOLIX, SOFAR, GoodWe, Growatt,
    Huawei, Sharp, Sungrow); backup `backups/20261107T095551Z-db`.
  - 078 đặt `options.hotline_technical = 0945 536 899` để footer hiện thẻ
    "Hỗ trợ kỹ thuật"; backup `backups/20261107T100339Z-db`.
- Sự cố: sau khi đổi container, Caddy không phân giải được `nuxt` trong lúc tạo
  lại và trả 503 khoảng 1 phút (10:28–10:29 UTC) đến lượt health check kế tiếp.
  Bước verify của script rơi đúng khoảng đó nên báo FAIL. Đã tự hồi phục, không
  rollback.
- Kiểm tra sau đó (chạy tay thay phần script chưa chạy): HTTP smoke 7 route 200;
  Playwright production 18/18; log web/nuxt 3 phút không có error; logo và bộ lọc
  đo trên site thật đúng.
- Rollback point: `scripts/rollback-release.sh` trên VPS (release trước `a8632eed`).

## 2026-11-07 — giao diện tổng (a8632eed)

- Commit/image: `a8632eed` / `phucthinhsolar-web:a8632eed4d87`. SAFE, PASS toàn bộ:
  predeploy, Playwright 248 pass, full backup, Playwright production 18/18, log
  scan 0 lỗi.

## 2026-11-07 — deploy dừng ở predeploy, production không đổi

- Lệnh: `scripts/deploy-production.sh --auto --confirm-production`, chế độ SAFE.
- Lần 1 dừng ở `git diff --check`: lỗi khoảng trắng, và thư viện 3D vendored
  cần thuộc tính `-whitespace` trong `.gitattributes`. Đã sửa và commit.
- Lần 2 dừng ở predeploy: `scripts/test-admin-crud-lifecycle.php` báo FAIL phần
  Nuxt search/news, vì container `pts-nuxt-service-rehearsal` chưa chạy.
- Production giữ image `phucthinhsolar-web:04dde592c441`. Không có backup hay
  migration nào được chạy.
- Lần sau: bật container diễn tập trước, theo mục 6 của
  `docs/02-development/nuxt-storefront-guide.md`.

## 2026-11-05 — đồng bộ production theo master sau khi gom nhánh

- Release PHP/Nuxt: `c62ba98b1de8` (`master`, commit
  `c62ba98b1de886c90cd9094c7217c6714554be8c`). Production trước đó là
  `263faa84286e`; nhánh Codex và nhánh film-pilot đã được hợp nhất vào master
  trước release. Phạm vi runtime mới gồm radius chip bộ lọc dự án và bundle
  preview Solar Cinema; không có migration.
- Backup SAFE trước deploy: `20261105T144623Z-full`, checksum đạt; chứa database
  và `shared/upload`. Backup chỉ nằm trên VPS do chưa có hook off-site. Không
  xóa upload, volume database hoặc backup cũ. Cache tái sinh đã xóa: 72 page +
  2 asset.
- Local predeploy PASS; full Playwright: 248 pass, 42 skip, 0 fail. PHP/Nuxt
  build và healthcheck PASS. Schema preflight `READY`, không chạy SQL migration.
- HTTP smoke 5 route trả 200; browser QA production bổ sung kiểm tra 8 route
  chính trên desktop và mobile: 16/16 pass, không lỗi JavaScript, ảnh hỏng hoặc
  tràn ngang. PDP mẫu trả 200, ảnh 960px tải đủ và Product JSON-LD có dữ liệu
  sản phẩm hợp lệ. `/migration-solar-cinema-preview/` vẫn trả 404 có chủ ý:
  Caddy loại `/migration-*` khỏi proxy Nuxt để các preview không thành trang
  public; pilot chưa được tích hợp vào section CMS.
- **Cổng Playwright production của wrapper chưa đạt:** wrapper thoát mã 1 sau
  khi image đã active vì `mobile-commerce-experience.spec.js` và
  `typography-system.spec.js` tìm selector PHP cũ (`.right-pro-detail`,
  `.pts-content-cards`, `.pts-home-category-copy`). Chạy `public-pages.spec.js`
  riêng cho 16 kiểm tra trang desktop/mobile đều đạt; hai lần chạy JSON-LD
  legacy cũng không tìm được selector PHP `.product-card .product-item`, nhưng
  schema được xác minh thủ công trên DOM Nuxt. Cần thay production gate bằng
  selector/flow Nuxt trước release tiếp theo; không coi exit 1 của wrapper là
  một lượt deploy có toàn bộ cổng tự động PASS.
- Không có Caddy 503 trong đợt này. PHP/Nuxt hiện healthy; image rollback được
  lưu ở `state/previous-image` và `state/previous-nuxt-image` là
  `263faa84286e`; rollback code bằng
  `/srv/phucthinhsolar/scripts/rollback-release.sh`. Sau full backup, disk VPS
  còn khoảng 4.8 GB (96% đã dùng); không prune image/cache trong lần release.

## 2026-11-05 — cân lại giỏ hàng, checkout và tải ảnh PDP

- Release production: PHP/Nuxt `263faa84286e`. Cột tóm tắt commerce rộng
  480px ở desktop; card dùng radius 10px; checkout ba cột trường thông tin ở
  viewport rất rộng; giá sản phẩm nằm dưới tên để dễ đọc. Ảnh chính PDP dùng
  rendition 960px, preload sớm và xử lý lỗi theo URL thumbnail thực tế.
- Trước deploy: 509/509 test, typecheck, build và smoke PASS. Browser local
  với sản phẩm CMS thật tại 390/1440/2048 không tràn ngang hay page error;
  ảnh PDP mẫu 960px là 29 KB so với bản 1600px 63 KB.
- Backup DB `20261105T135137Z-db`; xóa 115 page cache và 2 asset cache tái
  sinh, không migration, không chạm upload/DB volume. Rollback image PHP/Nuxt
  `b8c86f162ed4` qua `/srv/phucthinhsolar/scripts/rollback-release.sh`.
- Deploy script dừng ở verify đầu do Caddy tạm báo 503 khi upstream Nuxt đổi
  IP. Sau khi upstream phục hồi, verify-stack và schema preflight READY; browser
  production tại 390/2048 xác nhận PDP, cart, checkout không lỗi JavaScript,
  không tràn ngang. Log 1 phút sau ổn định không có lỗi mới. Cần xử lý khoảng
  503 ngắn trong quy trình đổi image ở đợt hạ tầng riêng.

## 2026-11-05 — video CTA Giải pháp và hợp nhất release

- Release production: PHP/Nuxt `b8c86f162ed4`. Gộp commit video CTA của nhánh
  Claude vào nhánh hotfix đã chạy production; các nhánh audit và visual tương
  đương đã được dọn sau khi kiểm tra quan hệ commit/patch. Giữ nhánh film pilot
  riêng vì đó là trang thử nghiệm chưa thuộc release.
- Trước deploy: 509/509 unit test, typecheck, build và smoke PASS. Backup FAST
  database: `20261105T130611Z-db`; không migration, không đổi DNS, không xóa
  upload/DB volume. Cache tái sinh đã xóa: 91 page và 2 asset.
- Script deploy dừng tại bước verify đầu vì Caddy tạm báo `no upstreams
  available` khi container Nuxt đổi IP. Sau khi active health checker báo
  `host is up`, verify-stack và schema preflight chỉ đọc PASS. Sáu route chính
  trả 200; video MP4 trả 206 và đúng byte range. Browser production ở 390/1440
  không lỗi JavaScript hoặc tràn ngang; mobile dùng poster, desktop có video.
  PHP/Nuxt healthy và log 2 phút sau ổn định không có lỗi mới.
- Rollback code: `state/previous-image` và `state/previous-nuxt-image` trỏ về
  `5c4fc0728392`; dùng `/srv/phucthinhsolar/scripts/rollback-release.sh`.
  Backup hiện nằm trên VPS; hook off-site riêng chưa được cài.
- Ghi nhận: script cần đợi Caddy active health chuyển upstream sang `up` trước
  HTTP verify để tránh báo fail tạm thời. Bộ Playwright production cũ vẫn dùng
  selector template PHP, cần chuyển sang Nuxt ở đợt bảo trì test riêng.

## 2026-11-05 — Nuxt storefront fixes và cutout sân khấu Giải pháp

- Hai release liên tiếp: `4f241fed92f9` rồi follow-up `5c4fc0728392`; trạng
  thái production cuối cùng là PHP/Nuxt `5c4fc0728392`.
- Phạm vi: đồng bộ icon registry, ổn định phản hồi số lượng giỏ, sửa cache bài
  tin và PHP layout hook tùy chọn; bỏ ảnh bối cảnh khỏi stage thiết bị. Follow-up
  resolve URL alias Giải pháp qua `productCategoryTree`, để danh mục “Đèn sân
  vườn” lấy đúng ba sản phẩm CMS có PNG alpha; “Trụ đèn” không có sản phẩm nên
  giữ placeholder.
- Backup full trước release: `20261105T120925Z-full` và
  `20261105T124008Z-full`; không migration, không đổi DNS.
- Kiểm tra local ở follow-up `5c4fc0728392`: `npm test` 505/505, typecheck,
  build và `NITRO_SMOKE_MOCK_PHP=1 npm run smoke` đều PASS. Full browser suite
  trên release `4f241fed92f9`: 248 pass, 42 skip, 0 fail; follow-up được kiểm
  tra bằng browser production theo luồng riêng bên dưới.
- Kiểm tra production: PHP/Nuxt healthy; schema preflight read-only READY; các
  route `/`, `/san-pham/`, PDP, `/gio-hang/`, `/tin-tuc/` và `/giai-phap/` HTTP
  200; log web/Nuxt/proxy không có lỗi mới. Browser QA riêng `/giai-phap/` tại
  320/390/768/1440: không tràn ngang, không page error, không ảnh nền phong cảnh;
  chọn “Đèn sân vườn” đổi sang V600 ORIANA, T600 ORIANA và WL-0412, ảnh tải đủ và
  kiểm tra mẫu alpha cho thấy nền trong suốt.
- Production subset `mobile-commerce-experience.spec.js` và
  `typography-system.spec.js` còn selector của template PHP cũ nên không dùng
  làm gate Nuxt: các section/catalog/heading hiện có nhưng class legacy không
  tồn tại. Cần cập nhật selector trong lượt test-maintenance riêng; kết quả này
  không thay thế browser QA production nêu trên.
- Cache tái sinh đã xóa theo deploy script: release đầu 93 page + 11 asset;
  follow-up 104 page + 2 asset. Không chạm upload/DB volume.
- Rollback code hiện qua `/srv/phucthinhsolar/scripts/rollback-release.sh`;
  image trước đó được ghi trong `state/previous-image` và
  `state/previous-nuxt-image`. Backup hiện chỉ lưu trên VPS; script cảnh báo
  chưa có hook off-site riêng.

## 2026-10-09 — khôi phục nội dung tư vấn toàn bộ catalog

- Commit/image: `42db406ce85f` / `phucthinhsolar-web:42db406ce85f`; deploy `SAFE`.
- Phạm vi: giữ khối thẻ nhu cầu và FAQ trước CTA khi dữ liệu Admin thiếu; đồng
  bộ nội dung cho toàn bộ danh mục cha/con bằng migrations 063, 065, 067–071 và
  073; bổ sung 26 ảnh thẻ vào shared upload.
- Backup trước thay đổi: `20261008T155253Z-full` và backup SAFE
  `20261008T161208Z-full`.
- Kiểm tra: 248 Playwright local đạt, 42 skipped; 9 Playwright production đạt,
  5 skipped; audit sitemap đạt 31/31 URL danh mục, 0 lỗi; 26/26 ảnh HTTP 200.
- Production: web/database healthy; log web/proxy không có Fatal, Warning hoặc
  HTTP 5xx mới sau deploy.
- Rollback code: `/srv/phucthinhsolar/scripts/rollback-release.sh`; rollback dữ
  liệu dùng full backup trước migration sau khi xác nhận phạm vi cần phục hồi.
- Hai danh mục con không có row nội dung riêng (`Đèn cắm cỏ`, `Kẹp tấm pin`)
  kế thừa block từ danh mục cha và đã được kiểm tra trực tiếp.

## 2026-09-30 — rà soát lớp backup

- Trạng thái production không thay đổi; đây là cập nhật tài liệu (`docs-only`).
- Backup ứng dụng trên VPS vẫn có timer DB/full backup và các bản backup local có
  checksum.
- CloudFly automatic backup/snapshot là lớp khôi phục toàn VPS đang có theo gói
  hạ tầng hiện hành; cần kiểm tra lịch, retention và restore trên CloudFly Console
  theo định kỳ.
- `/usr/local/sbin/pts-backup-offsite` chưa phải là thành phần bắt buộc của
  baseline hiện tại. Nếu chưa cài hook này, cảnh báo của script chỉ có nghĩa là
  chưa có thêm một bản copy sang nhà cung cấp/object storage độc lập ngoài CloudFly.
- Không cleanup, không thay đổi backup và không thay đổi production trong lần rà
  soát này.

## 2026-09-29 — migration và vận hành sau deploy

- Commit/image đang chạy: `14fdb2672665` / `phucthinhsolar-web:14fdb2672665`.
- Phạm vi: migration 058–060, kiểm tra route, backup retention và dọn Docker
  build cache.
- Migration đã xác minh: payment/shipping tables; payment/carrier columns trên
  `table_order`; project location/date columns trên `table_news`; `weight` trên
  `table_product`.
- Backup ứng dụng giữ lại: `20260929T013137Z-full` và
  `20260929T012822Z-full`.
- Docker build cache: đã kiểm tra và prune phần không dùng; không xóa container,
  volume hoặc image đang chạy.
- Kiểm tra sau thay đổi: stack healthy, các route public chính HTTP 200, không có
  log web/proxy lỗi mới tại thời điểm kiểm tra.
- Rollback point: image trước đó được lưu qua `state/previous-image`; không tự
  rollback DB.

## 2026-11-19 (tối) — vuốt mở menu, thẻ kính chú thích hero, nút lên đầu trang, sửa thanh mua (release 8c85a6e4, Nuxt only)

- Mode: Nuxt only (backend giữ `65b334b6`). Backup DB `20261119T043006Z-db`. Rollback: `releases/rollback-8c85a6e4/stack.env.before` (Nuxt `5887a972`). Gói `releases/8c85a6e4/nuxt-8c85a6e4.tar.gz` 172 MB, sha256 khớp.
- Lệnh `up` phải có `-p pts-laravel13` (tên project thật của container; `docker-compose.yml` khai `pts_laravel13_candidate` nên thiếu cờ sẽ báo trùng tên container).
- Vuốt phải mở menu, vuốt trái đóng (điện thoại và máy tính bảng, <= 1320 px, cảm ứng; loại trừ ô nhập, dải cuộn ngang, ảnh/carousel, hộp thoại). Nút lên đầu trang hiện trên điện thoại, dạng kính `.m-lg`. Vùng chạm 44 px cho nút so sánh/chia sẻ/"Xem thêm", hai dòng 13 px lên 14 px.
- Thẻ chú thích hero là một thẻ cho mọi màn hình, có thanh tiến trình bấm chuyển ảnh; bỏ cặp nút `<` `01/04` `>`. Điện thoại giữ kính sáng, máy tính bảng dùng bố cục điện thoại với nền navy, PC navy 72% không blur (ảnh nền chuyển động liên tục, blur sống làm vẽ lại mỗi khung hình).
- Sửa lỗi có sẵn: `.pdp-sheet { z-index: 1 }` nhốt thanh mua cố định trong lớp xếp chồng thấp nên thanh mục (z 20) và nút so sánh vẽ đè lên thanh mua trên điện thoại; bỏ z-index đó.
- Không đổi màu hay độ đục kính của header, thanh dưới, hộp tìm kiếm (đã thử navy đậm, navy nhạt, kính trắng và bị chủ website bác; bản thử ở nhánh `backup/glass-experiment-20261119`).
- Kiểm trên web thật: `/`, `/san-pham/`, `/gioi-thieu/`, `/lien-he/`, `/gio-hang/`, `/dai-ly/`, trang sản phẩm, `/robots.txt` đều 200; cảm ứng thật: vuốt phải mở menu, vuốt trái đóng (5/6 vị trí, trừ vùng ô tìm kiếm); thanh mua nằm trên thanh mục; 0 lỗi JS. Test frontend 518/518, typecheck 0.
- Chưa kiểm trên thiết bị thật (iPhone Safari): trình duyệt giả lập không thay được.

## Mẫu ghi cho lần tiếp theo

```text
Ngày/giờ:
Commit/image:
Mode: AUTO | SAFE | FAST | docs-only
Phạm vi thay đổi:
Migration:
Backup trước deploy:
Smoke/HTTP/Playwright:
Log scan:
Rollback point:
Rủi ro còn lại:
```
