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
| Mô tả trang thương hiệu | Cả 22 trang đều có mô tả; **13 trang không có sản phẩm nào** nhưng vẫn cho index | Chủ website chọn cho Google bỏ qua: trang trống `noindex` và ra khỏi sitemap, tự trở lại khi có sản phẩm | Đạt (kiểm một trang trống và một trang có hàng) |
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

## Sửa trong Admin (chủ website thực hiện; code không đụng dữ liệu thật)

| Việc | Ở đâu | Sửa | Kiểm lại |
|---|---|---|---|
| Liên kết Blue Carbon 1 | Admin → Bài viết → "Được gì khi hợp tác cùng Phúc Thịnh Solar" → chữ **đèn Blue Carbon** | Địa chỉ cũ `…/danh-muc-san-pham/den-nlmt/blue-carbon/` → `/thuong-hieu/blue-carbon/` | Bấm trên web, tới thẳng trang hãng |
| Liên kết Blue Carbon 2 | Admin → Bài viết → "Đèn năng lượng mặt trời Blue Carbon cho dự án" → chữ **xem đèn Blue Carbon** | như trên; nếu còn liên kết `…/tags-san-pham/blue-carbon/` (trả 404) thì sửa cùng địa chỉ | Như trên |
| Lỗi gõ | Admin → Nội dung các trang → trang Đại lý, đoạn mở đầu | "Mặt Tròi" → "Mặt Trời" | Mở `/dai-ly/` |
| Câu cũ | Cùng đoạn | "Năm 2021, doanh thu ngành đèn NLMT…": giữ nếu có nguồn, không thì bỏ | |

Lỗi gõ nằm trong dữ liệu web thật, không có trong mã nguồn và không có trong bản sao database trên máy.
Đã diễn tập hai sửa đổi liên kết trên database thử: cả hai bài trỏ thẳng `/thuong-hieu/blue-carbon/` (trả 200, không chuyển hướng).

## Đề xuất tiêu đề và mô tả (chủ website duyệt rồi dán)

Chỉ dùng tên, mã, loại sản phẩm và việc khách làm được trên trang. **Không đưa công suất, lumen, pin, hiệu suất, bảo hành** vào đoạn mới khi chưa đối chiếu datasheet.
Số liệu Search Console 01–23/11 dùng để chọn trang; sửa chữ chưa chứng minh được sẽ tăng tỷ lệ bấm, xem lại sau 4 tuần.

| Trang (hiển thị / bấm) | Tiêu đề SEO | Mô tả SEO |
|---|---|---|
| Sharp NU-JD550L (445 / 12) | Giữ | Tấm pin Sharp NU-JD550L loại mono. Xem giá, thông số theo model và gửi yêu cầu tư vấn cho mái nhà, nhà xưởng tại Phúc Thịnh Solar. |
| Đèn đường NLMT, danh mục đèn (207 / 4) | Đèn năng lượng mặt trời: đèn pha, đèn đường, sân vườn | Để trống: trang tự mô tả bằng số sản phẩm, nhóm và thương hiệu thật |
| Danh mục tấm pin (187 / 4) | Tấm pin năng lượng mặt trời LONGi, Astronergy, Sharp | Để trống như trên |
| ORIANA DB01-1 (161 / 13) | Giữ | Đèn đường năng lượng mặt trời ORIANA DB01-1 cho đường nội bộ, công viên. Xem cấu hình theo model và nhận báo giá tại Phúc Thịnh Solar. |
| Đại lý (96 / 3) | Đăng ký đại lý đèn năng lượng mặt trời \| Phúc Thịnh Solar | Giữ |
| ORIANA DB02-1 (34 / 5) | Giữ | Đèn đường năng lượng mặt trời ORIANA DB02-1 cho khuôn viên xưởng, đường nội bộ. Xem cấu hình theo model và nhận báo giá tại Phúc Thịnh Solar. |
| Canadian 720Wp (163 / 8) | Đang ẩn theo ý chủ website | Không viết |

Mô tả sản phẩm hiện có của ORIANA và Sharp đang nêu thông số và bảo hành; việc xác minh các số đó vẫn mở.

## Hai chuyên mục giải pháp

`/ho-gia-dinh` và `/doanh-nghiep` mỗi trang khoảng 140 chữ, chỉ có đúng một thẻ giải pháp đã hiện ở `/giai-phap/`. Thêm liên kết sẽ trái quy tắc đã chốt (nhóm một nội dung thì đi thẳng vào giải pháp), nên **không thêm**.
Đề xuất: cho `noindex` và ra khỏi sitemap khi nhóm chỉ có một giải pháp (như trang thương hiệu trống). Chờ chủ website quyết; chưa làm.

## Crawl lại bản trên máy sau mọi sửa đổi (`13bced76`)

457/457 trang trong sitemap trả 200 (ít hơn 468 đúng bằng 11 trang thương hiệu trống của bản sao dữ liệu). 0 liên kết qua chuyển hướng, 0 liên kết hỏng, 0 liên kết không tên; chỉ `?page=1` canonical về trang gốc; còn 2 trang mồ côi nêu trên.
Con số "còn 1 liên kết trong nội dung bài" ở bảng đầu là trước khi diễn tập sửa hai bài.

## Cần chủ website

1. Ba nhận xét ở hai sản phẩm đèn: chủ website xác nhận ngày 26/11 là **khách thật**. Giữ nguyên, không còn việc.
2. Bốn tấm pin trả 410 đang **ẩn khỏi website** (bật "Hiển thị" trong Admin là sống lại, đúng địa chỉ cũ). Còn bán thì thêm ảnh, kiểm giá rồi hiện; ngừng bán thì không cần làm gì.
3. 13 thương hiệu chưa có sản phẩm: **đã quyết 26/11**, cho Google bỏ qua; đã làm trên nhánh, có hiệu lực khi phát hành.
4. Sửa hai liên kết và lỗi gõ theo bảng "Sửa trong Admin".
5. `/ho-gia-dinh`, `/doanh-nghiep`: đồng ý cho Google bỏ qua đến khi mỗi nhóm có từ hai giải pháp, hay viết thêm nội dung riêng.
6. HSTS: nếu đồng ý, thêm ở Caddy ngoài cùng `Strict-Transport-Security "max-age=86400"`, theo dõi một tuần rồi mới tăng; chưa dùng `includeSubDomains`, `preload`. Trình duyệt nhớ giá trị này nên khó rút nhanh.
7. Địa chỉ: **đã xác nhận 26/11**. Trụ sở 424 Trường Chinh (website đúng); 403 Trần Xuân Soạn là kho. [Trang ENF](https://www.enfsolar.com/phuc-thinh-solar) đang ghi địa chỉ kho làm địa chỉ công ty; muốn khớp thì chủ website sửa tại ENF.
