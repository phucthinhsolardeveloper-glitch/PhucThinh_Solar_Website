# CMS: rà soát và cải thiện luồng thao tác (26/11/2026)

Base `5c35860f`, nhánh `feat/pts-cms-workflow-20261126`. Giữ Laravel + Filament,
không migration mới, không package mới, không đổi URL, quyền hay dữ liệu. Chưa
push, chưa deploy.

## 1. Kết luận

Chức năng đã đủ; phần khó dùng nằm ở **đường nối giữa các màn**. Đợt này làm ba
việc.

### 1.1 Link nhảy tới danh sách đã lọc bị bỏ qua (lỗi)

Filament v5.11.1 đọc tham số `filters` và `search`. Admin lại tạo link
`?tableFilters[...]` và `?tableSearch=`, nên danh sách mở ra không lọc. Ảnh hưởng:
Việc cần làm, khối Cần xử lý và ô số trên Bảng điều khiển, kết quả tìm nhanh
Liên hệ, chuông thông báo và nút trong email nội bộ (liên hệ, đánh giá), nút xem
lịch sử bản nháp. Đã đổi toàn bộ; có test chặn tên cũ quay lại.
Link trong email đã gửi trước đây vẫn mở danh sách không lọc như trước.

`CatalogHealth` mục "Đường dẫn trùng" trả cả ID của danh mục, thương hiệu, bài
viết, album, nhưng Việc cần làm, Bảng điều khiển và Chất lượng dữ liệu mở các ID
đó như **sản phẩm** (mở nhầm sản phẩm khác hoặc 404). Nay chỉ dòng thuộc bảng sản
phẩm mới có link sản phẩm.

### 1.2 Bố cục trang chỉ ra nguồn nội dung của từng khối

Câu cũ nói chữ và ảnh trong khối sửa ở "Nội dung các trang". Điều đó sai với phần
lớn khối. Đối chiếu `HomeAssembler`, `AboutAssembler` và component Nuxt:

| Khối | Nguồn thật |
|---|---|
| Ảnh đầu trang, Chiến dịch | Ảnh theo vị trí |
| Danh mục thiết bị | Danh mục nổi bật + dòng `home.energy_chain` |
| "Chuỗi năng lượng (minh họa)" | Cảnh 3D cố định, **không** đọc dòng `home.energy_chain` |
| Giải pháp | Giải pháp nổi bật khớp tên 4 ô cố định |
| Quy trình, Giới thiệu ngắn | Nội dung các trang |
| Đăng ký đại lý | Chữ cố định trong code |
| Liên hệ (Trang chủ) | Thông tin website |
| Giới thiệu: Về Phúc Thịnh | `about.history`; tiêu đề lớn và ảnh cố định |
| Giới thiệu: Hành trình | Chữ cố định trong `AboutHistory.vue`; chỉ thay được ảnh showroom |
| Con người, Vì sao chọn, Tầm nhìn | Nội dung các trang |
| Thương hiệu, Chứng nhận, Liên hệ | Thương hiệu; Ảnh theo vị trí; Thông tin website |

Dưới mỗi khối nay có câu mô tả nguồn, link mở đúng danh sách đã lọc (tab mới, để
không mất thay đổi bố cục chưa lưu) và "Xem trên trang đang chạy" (chỉ khi bản
bố cục đã xuất bản có khối đó). Người thiếu quyền thấy tên nguồn và dòng "tài
khoản chưa có quyền". Đầu trang có SEO của trang (quyền `settings`) và Trang đang
chạy. "Nội dung các trang" và "Ảnh theo vị trí" có nút quay về Bố cục trang; tạo
mới từ danh sách đã lọc sẽ chọn sẵn phần/vị trí. "Xem trên website" của dòng
`home.energy_chain` nay nhảy tới khối Danh mục thiết bị.

Không làm preview giả: không dựng lại giao diện trong Admin. Xem trước bản nháp
vẫn là link ký 15 phút sẵn có và chỉ phản ánh **bố cục**; sửa nội dung có hiệu
lực ngay trên website.

Bảng nguồn nằm ở `app/Filament/Support/PageSectionSources.php`. **Đổi assembler
hoặc component thì phải sửa bảng này**; test chỉ bảo đảm khối nào cũng có mô tả,
không tự phát hiện mô tả đã lỗi thời.

### 1.3 Lỗi sản phẩm: từ cảnh báo tới danh sách sửa được

- Danh sách Sản phẩm thêm bộ lọc "Lỗi trên website", dùng đúng `CatalogHealth`
  (từng lỗi hoặc "Mọi lỗi nghiêm trọng"). Không thêm điểm số.
- Chất lượng dữ liệu (trước chỉ liệt kê 25 mục mỗi lỗi) và khối Cần xử lý (4 mục)
  có link tới danh sách đã lọc, tại đó dùng được sửa nhanh và thao tác hàng loạt.
- Việc cần làm: mỗi sản phẩm một dòng gộp các lỗi (trước mỗi lỗi một dòng); quá
  10 thì có "Xem cả N mục" (trước không có đường xem phần còn lại). Đánh giá chờ
  duyệt mở danh sách lọc "chờ duyệt".
- Danh sách Sản phẩm có nút Chất lượng dữ liệu.

## 2. Trước và sau

Đếm theo số lần bấm hoặc chọn, suy từ giao diện; chưa đo với người dùng thật.

| Việc | Trước | Sau |
|---|---|---|
| Từ Bố cục trang tới các dòng của khối Quy trình | Mở menu Nội dung các trang, mở bộ lọc, chọn phần trong 28 lựa chọn; phải tự biết khối nào đọc phần nào | 1 lần bấm |
| Từ "Xem cả N liên hệ mới" tới đúng danh sách | Mở ra mọi liên hệ, phải lọc tay | 1 lần bấm |
| Mở mọi sản phẩm dính một lỗi quá 25 mục | Không có danh sách đầy đủ | 1 lần bấm |

## 3. Kiểm thử

- Test mới `AdminWorkflowLinksTest`: 6 pass, 56 assertion.
- Toàn bộ backend (DB `pts_app_test`, mail `array`): 566 pass, 2 skip, 7 fail. Cả
  7 do `Class "Dompdf\Dompdf" not found`: `vendor` của checkout master chưa cài
  package đã có trong `composer.lock`. Chạy lại `OrderSheetMailTest` và
  `MailDesignTest` với `dompdf` mượn từ image candidate local: 18/18 pass.
- Pint: 7 lỗi style, đều ở file không sửa trong nhánh này.
- PHPStan: 17 lỗi, trùng hoàn toàn với base (so bằng diff, bỏ số dòng).
- Trình duyệt (Chromium, DB test): Bố cục trang 9 khối, 17 link; link Quy trình ra
  đúng 5 dòng có chip lọc; "Xem cả" sản phẩm lỗi áp đúng bộ lọc; 390px không cuộn
  ngang; không có lỗi JavaScript hay HTTP 500.
- Không chạy: test và typecheck frontend, `pts:snapshot` (không sửa frontend hay
  API). Chưa bấm thử trên trình duyệt: Bảng điều khiển, chuông, email, tài khoản
  editor (quyền được kiểm bằng Livewire test). Chưa mở trang công khai Nuxt để
  kiểm vị trí các anchor; id được đối chiếu trong source.

Tác động môi trường local: áp hai migration sẵn có ngày 26/11 vào `pts_app_test`.
Lần dựng server QA đầu dùng `php artisan serve`, lệnh này không truyền
`DB_DATABASE` xuống tiến trình web nên server đó trỏ DB dev mặc định; chỉ có tải
trang đăng nhập và đăng nhập thất bại, có thể để lại dòng session ẩn danh
(`session.driver = database`). Không đụng production.

## 4. Bản đồ màn Admin

Phân loại: **Giữ**, **Cải thiện**, **Gộp** (đề xuất, chưa làm), **Ẩn** (đề xuất),
**Bỏ**. Đợt này không ẩn, gộp hay bỏ màn nào. Không có màn nào đủ bằng chứng để
**Bỏ**. Quyết định ẩn/gộp cần xem số dòng và thao tác thật trên production.
P1 là làm trước.

### 28 Resource

| Resource (quyền) | Dùng ở đâu | Phân loại | Ghi chú / ưu tiên |
|---|---|---|---|
| Sản phẩm (`catalog`) | API danh mục, PDP, feed | Cải thiện (đã làm một phần) | P1: `sales_readiness` và `CatalogHealth` là hai bộ lọc gần nhau, cần đặt tên rõ khi dùng thật |
| Danh mục, Thương hiệu (`catalog`) | Menu, danh mục, trang hãng, sitemap | Giữ | |
| Khối mô tả (`catalog`) | `ProductContentResolver` | Giữ | |
| Khung khuyến mãi (`catalog`) | `PromoFrames`, thẻ sản phẩm | Giữ | |
| Bài viết, Giải pháp, Dự án, Chính sách (`content`, `PostResource`) | API tin, giải pháp, dự án, footer | Giữ | Chung một lớp, khác loại nội dung |
| Nhóm bài (`content`) | Menu Kiến thức, listing, sitemap | Giữ | |
| Bản nháp & lịch sử (`content`) | Luồng duyệt/xuất bản | Giữ | Link lịch sử đã sửa |
| Kế hoạch bài đăng (`content`) | Lệnh import kế hoạch, Lịch biên tập | Gộp / Ẩn khi xong kế hoạch | P3: xem bảng trang bên dưới |
| Nội dung các trang (`content`) | Assembler trang chủ, giới thiệu, đại lý, giải pháp, tin, dự án | Cải thiện (đã làm một phần) | P1: chỉ Trang chủ và Giới thiệu có bản đồ nguồn |
| Ảnh theo vị trí (`media`) | Logo, hero, chiến dịch, chứng nhận | Cải thiện (đã làm một phần) | Cần quyền `media`, người chỉ có `content` không sửa được ảnh khối |
| Thư viện ảnh, Video (`media`) | Route album, PDP/tin, sitemap | Giữ | Chưa đo mức dùng |
| Kho media (`media`) | `MediaInventory`, cảnh báo ảnh mất | Giữ | Link "ảnh mất" đã sửa |
| Đơn hàng, Thanh toán, Tỉnh, Phường/phí (`orders`) | Checkout, vận chuyển | Giữ | Ngoài phạm vi |
| Liên hệ (`leads`) | Form công khai, CRM | Giữ | Chỉ sửa link |
| Đánh giá (`reviews`) | PDP | Giữ | Chỉ sửa link |
| SEO các trang (`settings`) | Assembler từng trang | Giữ, đã nối từ Bố cục trang | P2: người biên tập `content` không có quyền |
| Chuyển hướng (`settings`) | `Domain/Routing/Redirects` | Giữ | |
| Cửa hàng (`settings`) | Footer, Liên hệ, kho | Giữ | |
| Tài khoản (chủ website) | Đăng nhập, quyền | Giữ | |
| Nhật ký thay đổi | `Domain/Audit` | Giữ | |

### 10 Page

| Page | Phân loại | Ghi chú |
|---|---|---|
| Đăng nhập | Giữ | |
| Việc cần làm | Cải thiện (đã làm) | P2: xem mục 5 |
| Chất lượng dữ liệu | Cải thiện (đã làm) | Mục "thiếu thông số chính" chưa có bộ lọc danh sách |
| Bố cục trang | Cải thiện (đã làm) | |
| Nhập / xuất Excel | Giữ | Đã có nút từ danh sách Sản phẩm |
| Lịch biên tập | Gộp | P3: đọc cả `EditorialPlanItem`; ứng viên gộp với Kế hoạch bài đăng thành hai chế độ xem |
| Thông tin website, Mẫu email, Tích hợp | Giữ | |
| Công cụ ước tính | Giữ | Tắt mặc định theo chủ website |

### 8 Widget

| Widget | Phân loại | Ghi chú |
|---|---|---|
| Cần xử lý | Cải thiện (đã làm) | P2: trùng nhiều với Việc cần làm; nên dùng chung nguồn `WorkQueue`. Dòng email/CRM lỗi chưa có nơi xử lý |
| KPI | Giữ | Link đã sửa |
| Liên hệ, Doanh số, Bán chạy | Giữ | Kiểm quyền theo `leads`/`orders` |
| Lượt truy cập, Trang xem nhiều, Nguồn khách | Giữ | Source không thấy kiểm quyền riêng; xác nhận có chủ ý |

## 5. Việc còn lại

| Ưu tiên | Việc | Tiêu chí nghiệm thu | Dữ liệu / quyền cần |
|---|---|---|---|
| P1 | Bản đồ nguồn cho Đại lý, Giải pháp, Tin tức, Dự án, trang sản phẩm | Từ danh sách Nội dung các trang, mọi phần cho biết trang hiển thị và quy tắc giới hạn dòng; mỗi câu được đối chiếu với assembler và component | Quyền hiện có |
| P1 | Cho chủ website thử 3 việc: đổi bước Quy trình, thay ảnh chiến dịch, sửa sản phẩm giá sai | 3/3 tự làm được, không hỏi đường, mỗi việc tối đa 3 lần bấm tới đúng form | 20 phút trên bản staging |
| P2 | Link "Xem cả" khớp đúng mục đếm | "Chờ đối soát thu hộ", "xác nhận quá 24 giờ", "khách báo chuyển khoản" mở đúng số bản ghi đã đếm; chế độ "Của tôi" giữ nguyên khi bấm | Sửa bộ lọc `OrderResource`: **hoãn** để tránh đụng nhánh đơn hàng/tài chính đang chạy |
| P2 | Người biên tập `content` thiếu `media`/`settings` | Chủ website quyết cấp quyền hay giữ nguyên; nếu giữ, không có link dẫn tới 403 | Quyết định của chủ website |
| P2 | Gộp nguồn Cần xử lý và Việc cần làm | Cùng một số cho cùng một loại việc | Không cần dữ liệu mới |
| P3 | Gộp Lịch biên tập và Kế hoạch bài đăng | Một mục menu, URL cũ vẫn mở được | Biết kế hoạch 180 bài còn dùng không |
| P3 | Cho sửa chữ cố định (khối Đại lý trang chủ, tiêu đề Giới thiệu) | Chỉ làm khi chủ website cần đổi; có fallback bằng chữ hiện tại | Yêu cầu nội dung |

`docs/07-features/admin-guide.md` vẫn mô tả Admin PHP cũ, không dùng làm căn cứ
cho Admin hiện tại; nên viết lại hoặc đánh dấu lịch sử trong đợt rà docs.

## 6. Rollback

Chỉ có thay đổi code backend. `git revert` commit là đủ; không có dữ liệu hay
schema cần hoàn tác. Khi deploy cần `view:clear` vì có sửa Blade.
