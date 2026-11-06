# Kiểm toán "mùi AI" theo ANTI_GENERIC_AI_DESIGN_POLICY — 2026-11-06

Phạm vi: `/`, `/gioi-thieu/`, `/giai-phap/`, `/san-pham/`, `/tin-tuc/`, `/lien-he/`, `/dai-ly/`, `/du-an-da-thuc-hien/` tại 1440px, kèm quét mã nguồn. Chưa chạy đủ 768/390 cho toàn bộ các trang. Header, footer và bố cục trang chủ được giữ theo định hướng đã thống nhất. Mỗi dấu hiệu chỉ là review trigger, không tự động là lỗi.

Tự đánh giá theo thang 100 điểm của chính sách: khoảng 40, nhóm "cần xử lý theo nhóm trang". Đây là nhận định, không phải phép đo.

## Đã sửa

| Mã | Vấn đề | Bằng chứng | Xử lý |
|---|---|---|---|
| M09 | Ô navy trống trên trang chủ và trang khác ở localhost | `/thumbs/.../assets/...` trả 404 vì bridge chặn; production trả 200 | Bản xem thử đọc ảnh từ repo và thu nhỏ |
| J02 | Thẻ không bấm được vẫn nhấc lên khi rê chuột | `.eco-card`, `.option-card`, `.check-list li`, `.dealer-benefit` | Bỏ hiệu ứng nhấc |
| J09 | Vòng nhấp nháy vô hạn quanh icon gọi | `PublicCtaBanner` | Bỏ |
| K04, M09, K09 | 3D thiếu zoom, thiếu toàn màn hình trên điện thoại, nặng | `src/main.js`, `src/static-layer.js` | Xem `14-LIVING-ENERGY-3D-INTEGRATION.md` |

## Cần chủ dự án xác minh (P0: độ thật)

| Mã | Route | Nội dung | Nguồn | Việc cần làm |
|---|---|---|---|---|
| N01 | `/dai-ly/` | Các chỉ số "5+", "50+", "5000+" | CMS (`/_migration/dealer`, `stats`) | Xác nhận có nguồn, hoặc gỡ |
| N04 | `/dai-ly/` | Phản hồi có tên, ví dụ "Ông Nguyễn Bá Duy" | CMS (`testimonials`) | Xác nhận là khách thật và được phép đăng |
| N05 | `/gioi-thieu/` | Mục "Chứng nhận & năng lực" | CMS (`about.certificates`) | Xác nhận còn hiệu lực và đúng đơn vị cấp |

Chưa tự xóa vì đây là nội dung trong CMS, không phải chữ viết tay trong mã.

## Cần quyết định, chưa sửa

| Ưu tiên | Mã | Vấn đề | Bằng chứng | Hướng xử lý |
|---|---|---|---|---|
| P1 | D07, I01 | Các trang con cùng công thức: masthead navy, các khối thẻ, băng CTA navy, footer | `PublicCtaBanner` dùng ở 7 file; ảnh chụp | Mỗi loại trang một cấu trúc riêng, bắt đầu từ một route |
| P1 | E05, A03 | Glass và quầng sáng dùng dày | 49 chỗ glass trong 21 file; 28 radial-gradient trong 14 file | Giữ trên ảnh; bỏ khi không cần phân lớp |
| P2 | F03, E07 | Icon trong ô vuông và mũi tên ở khắp nơi | 43 chỗ icon box; 83 chỗ mũi tên trong 46 file | Bỏ icon chỉ để trang trí; mũi tên chỉ cho link điều hướng |
| P2 | E04 | Viền vàng bên trái làm trang trí | 18 chỗ trong 13 file | Giữ cho trích dẫn; bỏ ở khối thường |
| P2 | D03 | Dealer và Solutions lặp "tiêu đề + mô tả + lưới thẻ" | Ảnh chụp | Dùng sơ đồ hoặc bảng so sánh khi nội dung cho phép |
| P3 | C01, C04 | Trang chủ: H1 căn giữa, viết hoa, 2 CTA | `HomeHero.vue` | Trang chủ đang được giữ, chỉ sửa khi có yêu cầu |
| P3 | J09 | Animation vô hạn ở `HomeDealer` | `HomeDealer.vue:101-111` | Chỉ chạy khi nằm trong khung nhìn |

Không tính là lỗi: gradient vàng (`--pts-gold-gradient` là quy định thương hiệu tại tài liệu 12); không phát hiện eyebrow viết hoa giãn chữ trong nội dung `main` của 8 trang.

## Ba hướng cho route thử nghiệm `/san-pham/`

1. **Tinh chỉnh an toàn:** giữ bố cục, bỏ masthead navy và thẻ thừa, tăng mật độ. Rủi ro thấp, khác biệt vừa phải.
2. **Bàn làm việc kỹ thuật (đề xuất):** nền giấy kẻ lưới, lọc dạng cột thông số, chuyển đổi lưới/bảng so sánh, hiện công suất và chuẩn trên từng dòng. Có nét riêng của ngành và giúp chọn sản phẩm, nhưng cần đủ dữ liệu thông số.
3. **Danh mục theo tình huống:** vào bằng nhu cầu (mất điện, đường phố, mái nhà) rồi mới tới sản phẩm. Khác biệt lớn nhất, nhưng dễ trùng `/giai-phap/` và cần phân loại lại danh mục.

## Mobile: tiêu chí và kết quả (390×844, đo trên bản build)

Tiêu chí: số màn hình phải lướt; số trường bắt buộc trước khi gửi được form; nội dung có bị thanh nổi che không; nội dung cùng cấp có xếp dọc vô ích không; desktop có giữ nguyên không.

Quy ước kỹ thuật cho route mới:
- `data-fold="300|420|560"` trên phần tử dài: bản điện thoại gấp lại kèm nút "Xem thêm"; nội dung vẫn nằm trong HTML. Thêm `data-fold-dark` khi nền tối.
- `.m-rail`: các mục cùng cấp vuốt ngang trên điện thoại. `.m-chips`: lựa chọn ngắn vuốt ngang.
- Form điện thoại chỉ hỏi nhu cầu, tên, số điện thoại trước. Trường còn lại nằm sau một nút.
- Không dùng thanh tab kèm nút giữa nổi. Điều hướng là đảo nổi `MobileTabBar`, Zalo đứng cạnh đảo.

## Khung, chuyển trang và kính lỏng

- Header, footer và dock thuộc `app.vue`, sống qua các lần chuyển trang. Trang mới không tự thêm `SiteHeader`/`SiteFooter`; chỉ `error.vue` và trang `layout: 'migration'` có khung riêng.
- Không dùng `pageTransition` toàn cục và không dùng `mode="out-in"` có mờ về 0 cho khối lớn: chúng làm lộ nền trắng giữa hai trạng thái. Nội dung cũ giữ nguyên tới khi nội dung mới sẵn sàng (xem `migration-policy-preview.vue`). Đổi ảnh phải giải mã trước (xem `ProductPreviewGallery.vue`).
- `plugins/fetch-cache.client.ts` lưu đệm DTO công khai 120 giây ở tầng `fetch`. Không thêm route có phiên, giỏ hàng hoặc form vào danh sách.
- Kính chỉ dành cho lớp nổi trên nội dung: `.m-glass` (navy) và `.m-glass-light` (sáng). Nội dung và thẻ sản phẩm giữ nền đặc.

