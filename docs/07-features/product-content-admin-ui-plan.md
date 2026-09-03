# Quy chuẩn nội dung sản phẩm và kế hoạch nối lại Admin–UI

Ngày lập: 2026-09-03. Phạm vi: source và Docker local; chưa thay đổi dữ liệu hoặc production.

## 1. Kết luận hiện trạng

Trang chi tiết hiện lấy nội dung từ nhiều nguồn nhưng cách nhập chưa thống nhất:

| Dữ liệu Admin | Consumer public | Trạng thái |
|---|---|---|
| `descvi` | Tóm tắt cạnh ảnh/CTA | Đang dùng, nhưng là CKEditor tự do |
| `contentvi` | Tab “Mô tả sản phẩm” | Đang dùng, hoàn toàn tự do nên mỗi sản phẩm một bố cục |
| 19 trường chuẩn + `additional_specs` | Bảng “Thông số kỹ thuật” | Đang dùng tốt, có fallback HTML legacy |
| `video_url` | Tab video YouTube privacy-enhanced | Đang dùng |
| `certificatevi` | Tab chứng nhận | Đang dùng, nhưng nội dung tự do |
| `chinhsachmuahangvi` | Tab chính sách mua hàng | Đang dùng nhưng nhập lặp từng sản phẩm, dễ lệch chính sách chung |
| `thongsokythuatvi` | Fallback bảng thông số cũ | Chỉ cần cho dữ liệu legacy; không nên nhập mới khi đã có thông số cấu trúc |
| `photo`, gallery, `photo2` | Ảnh chính/gallery/ảnh hover card | Đang dùng |
| size/color/tags | Source còn hỗ trợ | Đang tắt trong config sản phẩm hiện tại; không bật lại nếu chưa có use case và regression UI |

Vấn đề chính không phải thiếu editor mà là thiếu mô hình nội dung. CKEditor cho phép tự dàn trang khiến font, khoảng cách, bảng và ảnh khác nhau giữa sản phẩm; đồng thời các field mới đã nối ra UI nhưng nằm rời rạc nên người nhập vẫn có xu hướng dồn mọi thứ vào `contentvi`.

## 2. Quy chuẩn đề xuất cho mọi sản phẩm

Một sản phẩm dùng cùng thứ tự khối, khối không có dữ liệu thì tự ẩn:

1. **Tóm tắt bán hàng** — 3–5 ý ngắn, dùng ở cạnh ảnh; lấy từ `descvi`.
2. **Điểm nổi bật** — tối đa 6 lợi ích, mỗi ý gồm tiêu đề ngắn + giải thích một câu.
3. **Ứng dụng phù hợp** — các bối cảnh sử dụng; không lặp thông số.
4. **Mô tả chi tiết** — nội dung diễn giải có heading/đoạn/ảnh nhưng không tự custom màu, font, bảng hoặc layout.
5. **Thông số kỹ thuật** — sinh tự động từ 19 field chuẩn và `additional_specs`.
6. **Bộ sản phẩm / hướng dẫn lắp đặt** — ưu tiên field cấu trúc, chỉ hiện khi có dữ liệu.
7. **Video thực tế** và **Chứng nhận** — tự hiện theo dữ liệu.
8. **Chính sách mua hàng** — lấy từ nội dung chính sách dùng chung; chỉ cho override theo sản phẩm khi thật sự khác.

Quy tắc biên tập:

- Không nhập tên sản phẩm lần nữa làm H1 trong nội dung; template đã có H1.
- Heading bên trong bắt đầu từ H2; không đặt màu/font/size inline.
- Ảnh nội dung có alt, không nhúng base64, không ép chiều rộng pixel; UI chịu trách nhiệm responsive.
- Không tạo bảng thông số bằng CKEditor. Thông số chuẩn nhập đúng field, thông số lạ nhập `Tên | Giá trị`.
- Không copy chính sách bảo hành/giao hàng vào mô tả nếu đã có field hoặc chính sách chung.
- Nội dung thiếu khối vẫn render gọn; không chèn placeholder rỗng.

## 3. Mô hình kế thừa an toàn

Không chuyển đổi tự động 445 sản phẩm hiện có. Giữ nguyên `contentvi`, `descvi`, `thongsokythuatvi` làm fallback và bổ sung dữ liệu cấu trúc theo kiểu tăng dần:

- Sản phẩm mới dùng trình nhập theo khối chuẩn.
- Sản phẩm cũ chưa chuẩn hóa tiếp tục render HTML hiện tại.
- Khi một sản phẩm có dữ liệu khối chuẩn, UI ưu tiên render khối chuẩn; phần nội dung legacy chỉ dùng ở khối “Mô tả chi tiết”, không nhân đôi thông số/chính sách.
- Có preview trước khi lưu và chỉ báo “đã chuẩn hóa/chưa chuẩn hóa” ở danh sách Admin.
- Excel tiếp tục tương thích cột cũ; thêm cột mới theo batch sau khi CRUD và UI đã ổn định.

Không nên lưu toàn bộ layout thành một blob HTML mới. Dữ liệu nên tách khỏi cách trình bày để sau này đổi UI một lần cho toàn bộ sản phẩm.

## 4. Phân nhóm công suất

Hiện tại `allpage.php` truy vấn từng giá trị `DISTINCT cong_suat`; sidebar gửi danh sách số và `sources/product.php` lọc bằng `IN`. Vì vậy xuất hiện 0W, 2W và quá nhiều lựa chọn lẻ.

Đổi sang ba nhóm cố định:

- **Dưới 60W**: `cong_suat > 0 AND cong_suat < 60`.
- **Từ 60W đến 200W**: `cong_suat >= 60 AND cong_suat <= 200`.
- **Trên 200W**: `cong_suat > 200`.

`0`/`NULL` là thiếu dữ liệu, không xuất hiện trong bộ lọc. UI gửi khóa allowlist (`under-60`, `60-200`, `over-200`), backend ánh xạ sang điều kiện parameterized/cố định; không nhận biểu thức SQL từ request. Trong giai đoạn chuyển tiếp có thể vẫn đọc URL số cũ để không làm hỏng link đã chia sẻ, nhưng header/sidebar mới chỉ sinh URL nhóm.

## 5. Số dòng trên trang Admin

Danh sách sản phẩm đang hard-code `$perPage = 10`. Bổ sung select **10 / 20 / 50 / 100 dòng** cạnh phân trang:

- Request dùng `per_page`, chỉ nhận đúng bốn giá trị allowlist; mặc định 20 (hoặc giữ 10 trong lần đầu nếu muốn không đổi thói quen).
- Khi đổi số dòng, đặt lại `p=1`, giữ danh mục/hãng/từ khóa/cảnh báo dữ liệu hiện tại.
- `per_page` phải được giữ trong link phân trang, quay lại từ form sửa, nhập/xuất và các redirect CRUD.
- Bulk “Trang này” dùng đúng số dòng đang hiển thị; bulk “Toàn bộ kết quả” vẫn dựa trên tổng bộ lọc, không phụ thuộc `per_page`.
- Chưa áp dụng toàn Admin trong batch đầu. Làm và test ở danh sách sản phẩm trước, sau đó mới trích helper dùng chung cho order/news/contact nếu hành vi ổn định.

## 6. Các batch triển khai

### Batch 1 — Bộ lọc W và phân trang sản phẩm

- Thay danh sách W rời rạc bằng ba khoảng ở sidebar và header quick filter.
- Thêm allowlist `per_page` và select 10/20/50/100.
- Test boundary 0, 2, 59.9, 60, 200, 200.1; test kết hợp brand/category/search và AJAX.
- Test URL/redirect/pagination giữ filter, số dòng và bulk scope.

### Batch 2 — Renderer nội dung chuẩn, chưa đổi schema

- Tạo CSS/renderer chung cho typography, heading, ảnh, list và khoảng cách trong mô tả.
- Sanitize/normalize output để HTML cũ không phá layout; không sửa nội dung DB.
- Ẩn tab rỗng, không hiện “Chính sách mua hàng” rỗng.
- Thêm visual baseline desktop/mobile cho sản phẩm nội dung ngắn, dài và legacy.

### Batch 3 — Trình nhập nội dung theo khối

- Bổ sung các field cấu trúc “Điểm nổi bật”, “Ứng dụng”, “Hướng dẫn”; có preview.
- Giảm vai trò CKEditor: chỉ dùng cho phần diễn giải/ảnh, không dùng để tạo layout.
- Thêm chỉ báo độ đầy đủ và trạng thái chuẩn hóa trong Admin/Excel preview.
- Migration chỉ thêm cột/JSON nullable, idempotent; không backfill hoặc ghi đè tự động.

### Batch 4 — Chính sách dùng chung và audit tính năng legacy

- Nối tab chính sách sản phẩm tới 5 trang/chính sách chung sau khi nội dung được chủ project chốt.
- Cho phép override có nhãn rõ ràng; dữ liệu per-product hiện tại vẫn là fallback.
- Đo usage DB + DOM/network cho size/color/tags, `photo2`, gallery và các module media trước khi quyết định giữ, nối UI, archive hay tiếp tục tắt.

## 7. Tiêu chí đạt và rollback

- Không thay slug/URL sản phẩm, trạng thái hiển thị, ảnh hoặc dữ liệu hiện có.
- Không có khối/tab trống, HTML tràn ngang, inline style làm lệch theme hoặc nội dung bị lặp.
- Công suất biên vào đúng một nhóm; 0/NULL không hiện.
- Số bản ghi mỗi trang đúng lựa chọn, filter và bulk action không đổi phạm vi ngoài ý muốn.
- PHP 7.4 lint, Admin readiness, public smoke, Playwright desktop/mobile và `predeploy-local.sh` đều PASS.
- Mỗi batch là một commit độc lập. Rollback source bằng revert commit; migration mới chỉ thêm nullable field và không được DROP trong cùng đợt rollback nếu đã có dữ liệu người dùng.
