# Bàn giao Claude → Claude (2026-11-13): SEO, Merchant, bán hàng, mobile

Đọc trước: `AGENTS.md` → `docs/12-ai/context-map.md`. Báo cáo chủ website bằng tiếng Việt, không eyebrow label.

## Trạng thái

- Production (phucthinhsolar.com) đang chạy bản deploy 83a (`docs/06-operations/deployment-log.md`).
- **3 commit dưới đây CHƯA push, CHƯA deploy.** Chủ website chưa ra lệnh deploy. Hỏi trước khi push hoặc deploy.
- Các worktree `~/orca/workspaces/Web_PTS_PHP/pts-editorial-*` là việc bài viết của agent khác. Không đụng vào.

| Commit | Nội dung |
|---|---|
| d6f73ecd | Product JSON-LD: Offer có `availability: InStock` + `seller` (#organization); BreadcrumbList đủ chuỗi danh mục; `og:type=product`; 30 trang thiếu meta description (thương hiệu, chính sách, chuyên mục tin) có mô tả tự sinh |
| 7015f7a9 | Mobile: cuộn về đầu chờ `page:finish` (trước đây nhảy về đầu TRANG CŨ 0,5–1,5 s rồi mới đổi trang); header hiện lại không trượt; khối đầu trang danh mục giữ 156px, hàng chip luôn có (danh mục cấp 1 không có con thì hiện các danh mục cùng cấp), lưới sản phẩm luôn ở 505px; title chỉ thêm "\| Phúc Thịnh Solar" khi tổng ≤ 65 ký tự (Nuxt + Laravel) |
| b1911945 | Migration `2026_11_13_000100` thêm 21 redirect cho link cũ còn 404 (Trụ đèn → Đèn Đường, /lienhe, /index.php, các trang thanh toán…); trang Giải pháp bỏ lối tắt tới danh mục không có sản phẩm; ô **"Mã đo Google Analytics 4"** trong Admin › Thông tin website › Đo lường (gtag.js, chỉ chạy trên domain thật, các sự kiện bán hàng được chuyển tiếp qua `pushDataLayer`) |

Kiểm tra đã chạy: `php artisan test` 401 pass, 1 skip; phpstan 0 lỗi; `npm test` 515 pass; `nuxt typecheck` 0 lỗi; build OK. Đã chạy migration trên DB local.

## Phát hiện chính (đã báo chủ website, phải nói lại khi báo cáo)

1. **Lý do lớn nhất khiến số liệu truy cập "yếu"**: container GTM-57TJSRVM (mã duy nhất, giống web PHP cũ) **không có thẻ GA4 hay Google Ads nào**. Kiểm tra bằng `curl gtm.js?id=GTM-57TJSRVM`: chỉ có các biến mặc định. Vì vậy GA4 không nhận được lượt truy cập nào. Các trang HTML cũ lưu trong legacy có gtag `G-0HR6PEWZYX` (GA4 cũ) và `GTM-KK3SXTNX`.
   - Cần chủ website: (a) xác nhận property GA4 đang xem (có thể là G-0HR6PEWZYX); (b) sau khi deploy, nhập mã G- vào ô mới, **hoặc** thêm thẻ GA4 trong GTM. Chỉ chọn một cách để không đếm hai lần.
2. **SEO khi chuyển web**: URL được giữ gần như đủ. Đã thử 1.738 link cũ (lấy từ mọi slug công khai trong DB PHP cuối, bản dump ở `D:\ProjectBackup\phucthinhsolar-laravel-cutover-20261110\`): 1.704 OK, 32 lỗi 404 (đã sửa ở b1911945). Link nội bộ: 682 đích, 3 hỏng (đã sửa). Số trang cũ và mới khớp nhau (374 sản phẩm, 12 bài viết).
3. **Merchant Center**: Product JSON-LD đã có từ trước, báo cáo của ChatGPT nói "không có" là sai. Thứ thiếu là `availability` (đã sửa). Feed `https://phucthinhsolar.com/google-shopping.xml` đang chạy với 170 sản phẩm. Nên khai báo feed này bằng URL trong Merchant Center (nguồn sản phẩm → Tệp → link, tải hằng ngày). 185 sản phẩm không vào feed vì thiếu ảnh Shopping sạch (`feed_image`); 133 trong số đó có ảnh chính ≥500px.

## Đang chờ chủ website quyết

1. Deploy 3 commit trên (cần backup DB trước, chạy `migrate --force`). Sau deploy: Rich Results Test một trang sản phẩm, gửi lại sitemap trong Search Console, khai báo feed trong Merchant.
2. Mã GA4 (xem mục 1 ở trên).
3. 133 sản phẩm: tạm dùng ảnh chính làm ảnh feed, hay chờ bổ sung ảnh sạch.
4. **Rút gọn chữ trang Giải pháp**: bản nháp nằm trong câu trả lời cuối của phiên trước (CTA dùng chung "Chưa biết chọn giải pháp nào?" + mô tả 33 chữ, đoạn giới thiệu /giai-phap/ 49 chữ, 5 tóm tắt giải pháp 32–41 chữ). Chưa được duyệt, chưa ghi gì. Dữ liệu nằm ở `page_items` section `solutions.cta` và `posts.summary` (kind=solution). Đoạn giới thiệu trang tổng lấy từ SolutionsListing intro. Khi đã được duyệt, ghi bằng migration có điều kiện "chỉ thay khi chữ hiện tại đúng bản cũ" (chủ website đang tự sửa nội dung).
5. Thân mỗi trang giải pháp khoảng 1.000 chữ: rút gọn từng mục, hay gấp vào "Xem thêm".
6. 4 danh mục con của "Đèn Sân Vườn" (trụ cổng, treo tường, đèn nấm, cắm cỏ) đang hiện trên web nhưng 0 sản phẩm, khách bấm vào gặp trang trống: gắn sản phẩm hay ẩn đi.
7. 34 title SEO do CMS nhập vẫn dài hơn 60 ký tự; mô tả sản phẩm 45–67 chữ (373 sản phẩm), làm theo từng nhóm.

## Chưa làm được hoặc chưa chắc

- "Thanh nav nhích khi rời trang chủ": Chromium không tái hiện được khi đứng yên. Đã sửa hai nguyên nhân đo được (cuộn sớm, header trượt). Chưa thử trên Safari iPhone (máy không có WebKit). Nếu vẫn còn, xin chủ website video quay màn hình.
- `/tru-den` → `/danh-muc-san-pham/tru-den/` → Đèn Đường là 2 lần chuyển hướng (resolver slug gốc vẫn trỏ tới danh mục rỗng). Có thể rút còn 1.
- Wayback CDX bị lỗi 429, nên chưa có danh sách URL Google từng index ngoài các slug trong DB.

## Môi trường

- Docker: `/mnt/c/Users/ADMIN/AppData/Local/Programs/DockerDesktop/resources/bin/docker.exe` (luôn `< /dev/null`). Nếu gặp lỗi `accept4 failed 110` thì đổi `WSL_INTEROP` sang socket khác trong `/run/WSL/*_interop` (socket đang dùng được ghi ở `/tmp/claude-1000/wsl-sock`).
- Nuxt local: `cd frontend && NUXT_LOCAL_API_PROXY=1 NITRO_HOST=0.0.0.0 NITRO_PORT=3110 NUXT_API_ORIGIN=http://127.0.0.1:8090 node .output/server/index.mjs` (đang chạy bản build mới nhất).
- Classifier chặn lệnh đọc DB production qua ssh. Dùng DB local (bản sao) hoặc API công khai.
- Script đo dùng lại được: `tools/visual-tests/audit/seo-20261113/` (README trong đó).
