# Xác minh route category tin tức và giải pháp — 2026-10-29

Phạm vi: route public một cấp, API GET chỉ đọc, Nuxt route resolver và fixture
visual. Không sửa `libraries/router.php`, Admin, cấu hình production hoặc bật
feature flag.

## Category đã xác minh

| Nhóm PHP | Slug | CMS | Bài public có slug | GET PHP | Canonical PHP |
| --- | --- | --- | ---: | ---: | --- |
| `tin-tuc` | `tin-moi-nhat` | `news_list`, visible | 1 | 200 | `/tin-moi-nhat` |
| `tin-tuc` | `tin-truyen-thong` | `news_list`, visible | 4 | 200 | `/tin-truyen-thong` |
| `tin-tuc` | `kien-thuc-san-pham` | `news_list`, visible | 7 | 200 | `/kien-thuc-san-pham` |
| `giai-phap` | `doanh-nghiep` | `news_list`, visible | 1 | 200 | `/doanh-nghiep` |
| `giai-phap` | `ho-gia-dinh` | `news_list`, visible | 1 | 200 | `/ho-gia-dinh` |

Đối chiếu trực tiếp `http://localhost:8080/<slug>` ngày 2026-10-29: PHP trả
HTTP 200, canonical không có dấu `/` cuối và H1 là tên category CMS. Các query
đọc-only trên DB local và DTO GET tại `http://localhost:8088/api/pts_v1_news.php`
đã xác nhận đúng danh sách tin. DTO solutions trước thay đổi chỉ có landing
page; endpoint được mở rộng bằng query `category` để trả projection category,
không đổi behavior khi không truyền query.

PHP template evidence: `sources/news.php` chọn `news_list` theo `idl`; tin tức
được đưa qua `news_hub.php`, giải pháp dùng `service_tpl.php`. Template giải
pháp dùng tên/mô tả nhóm cùng các bài visible có `id_list` tương ứng và giữ
liên kết đại diện tới bài giải pháp nếu có.

## NEEDS VERIFICATION

| Slug | Bằng chứng | Quyết định hiện tại |
| --- | --- | --- |
| `tin-tuyen-dung` | `news_list` đang visible và PHP trả 200, nhưng DB có 0 bài `tin-tuc` visible có slug; endpoint `pts_v1_news.php` loại nhóm này khỏi danh mục public. | Không thêm route Nuxt hoặc fixture render. Cần xác nhận chính sách trang category rỗng và indexability trước khi mở rộng DTO. |

## Gate

- Bare slug resolver giữ thứ tự `publicRouteResolveSlug()` và chỉ phân loại category khi có bài visible với slug dùng được.
- Route Nuxt dùng `newsPublicRouteEnabled`/`solutionsPublicRouteEnabled`; cả hai vẫn `false` mặc định trong `nuxt.config.ts`.
- Category DTO lấy dữ liệu từ API GET, canonical path được kiểm tra theo slug; query slug không tồn tại trả 404.
- Visual manifest chỉ thêm năm slug đã xác nhận ở bảng trên; `tin-tuyen-dung` nằm riêng ở `manualReviewRoutes` với trạng thái `NEEDS VERIFICATION`. Đây là category thật đang rỗng, không phải negative hidden-article fixture.
