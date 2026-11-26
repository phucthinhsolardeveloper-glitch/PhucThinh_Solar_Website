# Đối chiếu báo cáo crawl SEO (26/11/2026)

Cách đo: chỉ gửi yêu cầu GET, mỗi địa chỉ một lần, tối đa ba yêu cầu cùng lúc.
**Web thật** (`498ab8f1`): 440 URL trong sitemap, thêm trang phân trang và đích liên kết nội bộ.
**Bản trên máy** (nhánh `feat/pts-commerce-growth-20261126`): sitemap 468 URL trên **bản sao database cũ hơn web thật**, nên số lượng sản phẩm và nội dung hai bên khác nhau; chỉ so hành vi của code.

| Mục báo cáo | Kiểm trên web thật | Xử lý | Bản trên máy |
|---|---|---|---|
| 440 trang trả 200 | Đúng: 440/440 | — | 468/468 |
| 2 sản phẩm có `aggregateRating` nhưng không thấy | 2 trang đều **hiện** "★ 5.0/5 (n nhận xét)" và nội dung nhận xét đã duyệt (mục thu gọn); số khớp mã: 1 và 2 | Giữ mã. Xem "Cần chủ website" mục 1 | Có test: chỉ ghi mã khi có nhận xét đã duyệt |
| 325 Offer thiếu `availability` | 325 Product, 275 Offer, 0 `availability` | Giữ: chưa có tồn kho thật | — |
| 50 sản phẩm báo giá không có Offer | Đúng 325 − 275 = 50 | Giữ: không có giá thì không có Offer là đúng | Có test |
| GTIN, MPN | 0 và 0 | Giữ: không có nguồn; SKU không thay MPN | — |
| Mô tả trang thương hiệu | Cả 22 trang đều có mô tả; **13 trang không có sản phẩm nào** nhưng vẫn cho index | Chờ chủ website (mục 3) | Không đổi |
| 7 liên kết nội bộ đi qua chuyển hướng | 10 đích: 3 `/category/…` (mọi trang), 3 `den-san-vuon` cũ, 1 `den-nlmt/blue-carbon`, 3 địa chỉ chuyên mục có dấu `/` cuối | Sửa menu, breadcrumb; thẻ và banner danh mục đi theo bảng chuyển hướng. Địa chỉ cũ vẫn chuyển hướng | Còn 1: liên kết gõ tay trong nội dung bài (mục 4) |
| Phân trang thương hiệu trỏ canonical về trang 1 | Đúng trên web thật (11 trang từ trang 2) | Đã sửa ở `cc5011b6`, chưa phát hành | Mọi trang từ 2 có canonical của chính nó |
| (Phát hiện thêm) Phân trang bài viết `?p=` | `noindex`, canonical về trang 1 | Sửa: canonical riêng, index; tìm kiếm và trang vượt cuối vẫn `noindex` | Đạt |
| 15% ảnh `alt` rỗng; 16 ảnh thiếu `alt` | 10.645 thẻ ảnh. **Không thẻ nào thiếu `alt`**: 16 thẻ viết `alt` không giá trị, tương đương `alt=""`. Alt rỗng là icon cạnh chữ, ảnh nền, logo trang trí. 0 liên kết không có tên đọc được | Không sửa | Như web thật |
| 9 trang mồ côi | Sau khi theo cả phân trang bài viết còn 5: thư viện ảnh, video, 2 chuyên mục giải pháp, `/du-an-ca-nhan`. 4 bài cũ đến được qua phân trang | Thư viện ảnh, video đang trống: bỏ khỏi sitemap và `noindex` đến khi có nội dung. Nút lọc dự án trỏ đúng địa chỉ trong sitemap. 2 chuyên mục giải pháp: mục 5 | Còn 2 chuyên mục giải pháp |
| 11 URL thiếu `lastmod` | 11 trang tĩnh | Giữ: không có mốc thay đổi đáng tin | — |
| HSTS, cache | HTTPS: **không có** `Strict-Transport-Security`; HTTP chuyển 308. `/_nuxt/` cache 1 năm; ảnh 14 ngày; Admin `no-store, private` | Không đổi cấu hình web thật. Đề xuất mục 6 | — |
| Tiêu đề dài, số chữ | Không phải giới hạn cứng | Không đổi hàng loạt | — |
| PageSpeed (429) | **NOT RUN**: máy không có Lighthouse; số lab cũng không thay số đo thật (CWV) | Xem báo cáo CWV trong Search Console | — |

Con số "22 trang thương hiệu" và "11 trang phân trang" là hai thứ khác nhau: 22 trang thương hiệu trong sitemap; 11 trang phân trang từ trang 2 của 4 thương hiệu.

## Sản phẩm đã gỡ đang có lượt tìm

Trả **410** trên web thật: `tam-pin-nang-luong-mat-troi-canadian-720wp` (Search Console 01–23/11: 163 lượt hiển thị, 8 lượt bấm; khoảng này gồm cả thời gian trước khi ẩn), `…canadian-620wp`, `…canadian-715wp-2-mat-kinh`, `…tcl-solar-720wp`.
Đọc database web thật ngày 26/11 (chỉ SELECT): sản phẩm không bị xóa, được **ẩn trong Admin ngày 17/11** (TCL khoảng 11:13, Canadian khoảng 15:13); cả bốn không có ảnh đại diện, có giá. Tấm pin đang ẩn 10/29, gồm thêm TCL 625Wp, 630Wp.

## Đề xuất chữ cho Admin (chủ website duyệt rồi dán; chỉ dùng số đang đăng trên trang)

| Trang | Trường | Đề xuất |
|---|---|---|
| Sharp NU-JD550L | Mô tả SEO | Tấm pin Sharp NU-JD550L 550W Mono, hiệu suất 21,29%, Voc 50,70V, kích thước 2.278 × 1.134 × 35 mm, nặng 27,5 kg. Xem giá tại Phúc Thịnh Solar. |
| ORIANA DB01-1 40W | Mô tả SEO | Đèn đường năng lượng mặt trời ORIANA DB01-1 40W: pin 3.2V/50Ah LiFePO4, tấm pin 6V/50W mono, quang thông 5200–5600 lumen. Nhận báo giá. |
| ORIANA DB02-1 50W | Mô tả SEO | Đèn đường năng lượng mặt trời ORIANA DB02-1 50W: pin 3.2V/80Ah, tấm pin mono 6V/90W, cho đường nội bộ và khuôn viên xưởng. Nhận báo giá. |
| Đại lý | Tiêu đề SEO | Đăng ký đại lý đèn năng lượng mặt trời \| Phúc Thịnh Solar |

Trang Đại lý còn chữ "Năm 2021", lỗi gõ "Mặt Tròi" và các số về chip, pin, chứng nhận cần chủ website xác nhận.
Trang danh mục chưa có mô tả SEO riêng sẽ tự mô tả bằng số sản phẩm, nhóm và thương hiệu thật.

## Cần chủ website

1. Ba nhận xét ở hai sản phẩm đèn: chủ website xác nhận ngày 26/11 là **khách thật**. Giữ nguyên, không còn việc.
2. Bốn tấm pin trả 410 đang **ẩn khỏi website** (bật "Hiển thị" trong Admin là sống lại, đúng địa chỉ cũ). Còn bán thì thêm ảnh, kiểm giá rồi hiện; ngừng bán thì không cần làm gì.
3. 13 thương hiệu chưa có sản phẩm (Canadian Solar, AESOLAR, TCL Solar, Sungrow, Huawei, Growatt, GoodWe, SOFAR, Anker SOLIX, Deye, Sigenergy, Leader Solar, Phúc Thịnh Solar): thêm sản phẩm, ẩn thương hiệu trong Admin, hoặc đồng ý cho tự `noindex` đến khi có hàng (cần xem lượt tìm trước).
4. Sửa liên kết trong hai bài `duoc-gi-khi-hop-tac-cung-phuc-thinh-solar` và `blue-carbon-den-nang-luong-mat-troi-cho-du-an` thành `/thuong-hieu/blue-carbon/`.
5. `/ho-gia-dinh`, `/doanh-nghiep` không trang nào dẫn tới (menu đi thẳng vào từng giải pháp, đúng thiết kế đã chốt): thêm lối vào ở trang Giải pháp hay bỏ khỏi chỉ mục.
6. HSTS: nếu đồng ý, thêm ở Caddy ngoài cùng `Strict-Transport-Security "max-age=86400"`, theo dõi một tuần rồi mới tăng; chưa dùng `includeSubDomains`, `preload`. Trình duyệt nhớ giá trị này nên khó rút nhanh.
7. Địa chỉ: nguồn ENF ghi 403 Trần Xuân Soạn, website ghi 424 Trường Chinh. Xác nhận địa chỉ đúng rồi tự sửa nơi sai; không đổi gì từ phía code.
