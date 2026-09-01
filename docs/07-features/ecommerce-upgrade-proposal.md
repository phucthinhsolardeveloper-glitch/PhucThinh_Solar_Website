# Đề xuất nâng cấp hướng thương mại điện tử (tham chiếu Kitawa)

Phân tích trang đối thủ **Kitawa Solar Light** và đề xuất các hạng mục nâng cấp cho Phúc Thịnh Solar. Mỗi mục có ước lượng công sức, phụ thuộc và mức độ rủi ro để chủ project chọn ưu tiên.

Ngày lập: 2026-08-31. Trạng thái: chờ chủ project chốt phạm vi.

## 1. Điều Kitawa làm tốt

| # | Kitawa | Giá trị |
|---|--------|---------|
| a | Thanh tìm kiếm to, luôn hiển thị ở giữa header | Ưu tiên tìm sản phẩm → thúc đẩy mua |
| b | Dải lọc nhanh theo công suất ngay dưới ô tìm (60W…1000W) | Khách chọn nhanh loại đèn theo nhu cầu |
| c | Ô "Gọi mua hàng" + "Giỏ hàng (n sản phẩm)" nổi bật, tách khỏi menu | Kênh chốt đơn rõ ràng |
| d | Slot banner quảng cáo trong header (banner 2/9, đếm ngày…) | Đẩy sự kiện/khuyến mãi theo mùa |
| e | Theme sự kiện: dịp 2/9 sản phẩm có viền đỏ "flash sale", giá gạch, % giảm | Tạo cảm giác khẩn cấp, tăng chuyển đổi |
| f | "Danh mục sản phẩm" tô vàng, nổi hơn hẳn menu Giới thiệu/Hoạt động | Điều hướng khách vào catalog trước tiên |
| g | Khối "mẫu đèn công trình độc quyền" + hàng icon danh mục lớn, rõ | Khách thấy sản phẩm ngay, cảm giác chuyên nghiệp |
| h | Nhiều danh mục hiển thị ở trang chủ | Bao phủ nhu cầu, tăng đường vào sản phẩm |
| i | So sánh sản phẩm (compare) | Hỗ trợ quyết định mua với dòng có thông số |
| j | Mobile: footer hiển thị đầy đủ | Không giấu thông tin liên hệ/chính sách |

## 2. Hiện trạng Phúc Thịnh

- Header tối giản kiểu doanh nghiệp: hotline/email/giờ ở thanh trên; logo + menu + icon giỏ + tìm kiếm dạng nút bung. Không có lọc nhanh công suất, không có slot banner.
- Trang chủ đã có hàng icon danh mục nhưng nhỏ; số danh mục hiển thị bị giới hạn.
- Đang ở chế độ **báo giá** → không có giá/gạch giá/% giảm, nên "flash sale kiểu Kitawa" chỉ áp dụng khi bật giá công khai.
- Chưa có so sánh sản phẩm.
- Bộ lọc sidebar (Thương hiệu/Công suất/Loại SP) từng bị ẩn nhầm — **đã khôi phục** (commit `a1bfedc`); mức giá vẫn ẩn theo chế độ báo giá.
- Mobile footer **đã bỏ accordion**, hiển thị đủ (commit `8bd8262`).

## 3. Đề xuất theo ưu tiên

Trạng thái triển khai local: Nhóm A đã hoàn thành. A1/A2 cải thiện header; A3 hiển thị đủ 9 danh mục 5/3/2 cột với ảnh Admin/icon fallback; A4 cung cấp drawer bộ lọc mobile có hỗ trợ bàn phím, ARIA và tự đóng sau lọc AJAX. Mục đề xuất tiếp theo là B3.

### Nhóm A — Nhanh, rủi ro thấp, làm được ngay
| Mục | Mô tả | Công sức |
|---|---|---|
| A1 | **Dải lọc nhanh công suất** dưới thanh tìm ở header (60W…1000W) → link `/danh-muc-san-pham/den-nlmt/?is_congsuat=60`. Dữ liệu `$congSuatList` đã có. | ~2–3h |
| A2 | **Nổi bật "Danh mục sản phẩm"** + ô Gọi/Giỏ trong header; hạ nhẹ menu phụ. Chủ yếu CSS + markup nhỏ. | ~2h |
| A3 | **Trang chủ: hàng icon danh mục lớn hơn, hiện đủ danh mục** — đã hoàn thành local, không thêm query DB. | ~3h |
| A4 | **Bộ lọc mobile**: nút "Bộ lọc" mở sidebar dạng drawer trượt — đã hoàn thành local. | ~4h |

### Nhóm B — Trung bình, cần thống nhất thiết kế
| Mục | Mô tả | Công sức | Ghi chú |
|---|---|---|---|
| B1 | **Slot banner header** quản lý qua Admin: thêm loại ảnh `header-banner` trong `table_photo` + field Admin + vùng hiển thị responsive. | ~1 ngày | Cần thêm chỗ nhập ở Admin |
| B2 | **Công tắc chiến dịch** (giống công tắc giá): 1 khóa `active_campaign` + khoảng ngày trong `table_setting.options`; khi bật → hiện banner chiến dịch + accent "đang khuyến mãi" trên card. | ~1 ngày | Không phụ thuộc bật giá; badge "Khuyến mãi" thay cho %giảm khi đang báo giá |
| B3 | **So sánh sản phẩm**: checkbox "So sánh" trên card → thanh compare nổi → trang/popup bảng so sánh 19 trường thông số cấu trúc + giá/công suất; lưu `localStorage`. | ~1.5–2 ngày | Giá trị cao vì catalog đã có thông số cấu trúc |

### Nhóm C — Chờ bật giá công khai
| Mục | Mô tả |
|---|---|
| C1 | Card kiểu "flash sale": giá gạch, % giảm, badge FLASH SALE, viền chiến dịch. Cần `regular_price`/`sale_price` thật và bật "Hiển thị giá". |
| C2 | Đếm ngược thời gian khuyến mãi trên banner/card. |

## 4. Khuyến nghị

Làm **Nhóm A trọn gói** trước (1 ngày, rủi ro thấp, tác động thấy ngay: header bán hàng hơn, lọc nhanh, mobile lọc được, trang chủ nhiều sản phẩm hơn).

Sau đó chọn **B3 (So sánh sản phẩm)** vì tận dụng đúng thế mạnh dữ liệu thông số cấu trúc và không phụ thuộc chế độ giá.

**B1/B2 (banner + chiến dịch)** làm khi chốt được quy trình nhập liệu ở Admin và định dạng chiến dịch.

**Nhóm C** để dành tới khi công ty duyệt niêm yết giá.

## 5. Không nên làm

- Không bê nguyên header Kitawa: rủi ro vỡ layout toàn site, mất nhận diện thương hiệu vàng kim hiện có. Chỉ mượn ý tưởng (lọc nhanh, nổi bật catalog), giữ khung theme V2.
- Không thêm giá/gạch giá khi chưa bật chế độ "Hiển thị giá".
