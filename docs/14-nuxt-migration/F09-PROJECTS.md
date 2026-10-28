# F09 — Projects PHP API + Nuxt preview

## Source map

- PHP hub: `sources/project_hub.php`, `sources/news.php`, `templates/news/project_tpl.php`.
- Public API: `api/pts_v1_projects.php` + pure DTO `libraries/pts_public_projects_api.php`.
- Nuxt preview: `/_migration/projects` and `/migration-projects-preview`.

## Contract

GET-only. Query được allow-list gồm `category`, `page`, `per_page`; query sai trả `400 BAD_QUERY`, category CMS không tồn tại trả `404 CATEGORY_NOT_FOUND`, method khác trả `405 METHOD_NOT_ALLOWED`, lỗi runtime trả `500 INTERNAL_ERROR` không lộ SQL/secret.

Payload chỉ chứa record `hienthi`, thứ tự PHP `numb,id desc`, ảnh upload đã kiểm tra tên file tồn tại, canonical URL và dữ liệu text đã hạ về plain text. Trang gốc/trang 1 tách record `projecthub` đầu tiên (nếu không có thì record đầu tiên) thành `featured`, cùng semantics với `project_hub.php`. `project-cta` chỉ xuất hiện khi record CMS đang hiển thị; không fallback CTA/ảnh hoặc số liệu giả.

Nuxt preview dùng header/footer từ `/_migration/site`, có noindex và giữ dữ liệu rỗng an toàn khi PHP upstream offline. Không thay route public `/du-an-da-thuc-hien/` và không thay đổi PHP source/template hiện có.

## QA / gaps

- Đã thêm test DTO Node và test contract PHP deterministic; PHP test cần chạy ngoài sandbox có PHP + local CMS config/DB.
- Chưa chạy browser/screenshot/build/Docker theo giới hạn task. Coordinator cần kiểm tra SSR, responsive 360/390/768/1366/1920, canonical legacy links và parity dữ liệu live.
- Detail project, gallery, proof fields, related products và SEO DB metadata chưa được migrate trong slice hub preview; public PHP vẫn là authority.
