# F10 NEWS — local read-only migration

## Scope

- PHP public contract mới: `api/pts_v1_news.php` và `libraries/pts_public_news_api.php`.
- Nuxt chỉ có preview nội bộ tại `/migration-news-preview/`; public `/tin-tuc/` vẫn do PHP phục vụ.
- API chỉ nhận `GET`, query strict `q`, `p`, `category`; page size cố định 6.

## Parity checkpoints

- Bài viết chỉ nhận `type=tin-tuc`, `hienthi` và `slugvi` hợp lệ.
- Category chỉ xuất hiện khi có bài publish; category không tồn tại trả `404`.
- Featured chỉ chạy ở hub không lọc/search, tối đa 3 bài `newshub`; các ID featured bị loại khỏi mọi trang pagination của hub đó.
- Search trim tối đa 120 ký tự và escape `!`, `%`, `_` trong LIKE.
- Intro/CTA CMS đã cấu hình nhưng hidden trả `null`; khi chưa có record, DTO dùng fallback giống PHP. Không đưa rich HTML vào DTO.
- URL bài và category dùng bare slug do PHP resolver hiện hành sở hữu; không thêm public Nuxt route.

## Read-only/security boundary

- Nitro bridge chỉ gọi fixed `phpOrigin` tới `/api/pts_v1_news.php`, không forward cookie hoặc upstream do client cung cấp.
- Preview không có form mutation, giá, cart, CSRF hoặc nội dung bài chi tiết; CTA chỉ dẫn tới route liên hệ PHP hiện có.
- Runtime HTML sanitizer/rich-text article detail và PHP Docker verification là blocker của coordinator, không giả lập trong preview.

## Verification

- Agent chạy `node --test tests/*.test.mjs` và PHP lint/contract khi runtime sandbox có PHP.
- Không chạy npm install/ci, browser, build, Docker, push, merge hoặc deploy.
