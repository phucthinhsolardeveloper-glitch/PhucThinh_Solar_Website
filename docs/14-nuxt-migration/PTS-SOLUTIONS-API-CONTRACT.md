# PTS read-only solutions API contract

Endpoint nội bộ cho Phase A: `GET /api/pts_v1_solutions.php`. Endpoint này
không thay đổi route public PHP và không thực hiện mutation.

## Response

Response JSON có `schemaVersion: 1`, `locale: "vi"`, `path: "/giai-phap/"` và
các trường public sau:

- `title`: tiêu đề fallback của landing hoặc tiêu đề `solution-intro` đang hiển thị.
- `intro`: object hoặc `null` khi toàn bộ record `solution-intro` bị ẩn. Object gồm
  `eyebrow`, `title`, `description`, `image` và các link `consult`/`explore`.
- `cards`: các solution đã qua đúng `solutionHubSelect($rows, 'solutionhub')`, có
  `title`, `description`, `slug`, canonical `url`, `image` và `actionLabel`.
- `equipment`: tối đa bốn product-list đã qua đúng featured/published rule, có
  `name`, `description`, canonical category `url` và `image`.
- `allProductsUrl`: `/san-pham/`.
- `labels`: nhãn section public (`choices`, `equipment`, `allProducts`, `empty`,
  `consult`, `phone`) lấy từ source locale `vi`.
- `cta`: object hoặc `null` khi toàn bộ record `solution-cta` bị ẩn. Object gồm
  `title`, `description`, `buttonLabel`, `image`, link `consult` và `hotline`.
  `hotline` là `null` khi CMS không có hotline hợp lệ.
- `editorial`: hiện có `rendered: false` và lý do defer. `contentvi/contenten`
  không được đưa qua API vì chưa có sanitizer/allowlist gate cho rich text.

Chuỗi `image` là đường dẫn root-relative trong namespace `/upload/` hoặc
`/assets/`, đã được kiểm tra basename, extension và file tồn tại trong upload;
chuỗi rỗng nghĩa là không có ảnh upload hợp lệ.
Fallback ảnh dùng đúng map và fallback CTA của `sources/solution_hub.php`.

## Visibility and edge cases

- Query đọc cả record ẩn để trạng thái ẩn không làm fallback hoặc record khác
  tự hồi sinh. Chỉ record `hienthi` được xuất; nếu có record gắn
  `solutionhub`, chỉ các record vừa `hienthi` vừa `solutionhub` được chọn.
- Card không có slug `vi` bị bỏ qua, và URL solution luôn giữ dạng
  `/giai-phap/{slug}/` theo slug đã được source resolve. Không tự tạo
  solution/product từ dữ liệu thiếu.
- `solutionHubField`/`solutionHubText` giữ fallback ngôn ngữ hiện có nhưng DTO
  chỉ chạy locale `vi`; text dài được hạ về plain text, không xuất HTML.
- `intro`/`cta` không có record nào thì dùng fallback UI tĩnh đã có trong source;
  có record nhưng bị ẩn thì trả `null`, không dùng fallback.
- Endpoint chỉ nhận GET, trả `405 METHOD_NOT_ALLOWED` cho method khác và
  `500 INTERNAL_ERROR` cho lỗi runtime; không trả raw exception, config hoặc DB row.
