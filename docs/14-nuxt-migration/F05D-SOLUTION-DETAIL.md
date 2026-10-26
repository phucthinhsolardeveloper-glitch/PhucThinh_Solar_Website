# F05D — solution detail PHP API + Nuxt preview

## Đã triển khai

- Thêm API PHP read-only `GET /api/pts_v1_solution_detail.php?slug=...` với kiểm tra query, slug canonical, `hienthi`, lỗi 405/400/404 và JSON không lộ exception.
- Dữ liệu lấy từ bài `news` loại `giai-phap`, gallery `news`, navigation `solutionhub` và product-list published/featured. Ảnh chỉ được trả khi basename, extension và file upload tồn tại.
- Nội dung CKEditor không render HTML. Stage 1 chỉ xuất paragraph text đã loại tag/script, ảnh hero và gallery; `editorial.rendered=false` ghi rõ rich text/TOC còn trì hoãn tới sanitizer allowlist.
- Thêm contract TypeScript, Nitro GET bridge và preview noindex `/migration-solution-detail-preview?slug=...`. Navigation/CTA giữ link legacy PHP; không tạo public Nuxt route.

## Parity gap và kiểm thử

Preview chưa migrate CKEditor HTML, mục lục, modal Bootstrap và các hành vi legacy gallery; không có pricing hay claim thiết bị ngoài CMS. Coordinator cần chạy PHP Docker QA với fixture CMS thật, Nuxt typecheck/build và HTTP/browser QA theo quy trình tích hợp. Không chạy Docker/build/browser trong task này.

Đã thêm helper test PHP và Node contract test. Nếu môi trường không có PHP CLI, PHP test để coordinator chạy trong PHP Docker.
