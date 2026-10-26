# F10D — News detail migration checkpoint

API detail mới là projection GET-only từ bản ghi `#_news` thật với `type=tin-tuc` và `hienthi`. Slug được kiểm tra trước bootstrap rồi truy vấn bằng placeholder; canonical public giữ dạng `/{slug}/`, khớp resolver/router hiện hành. API không cập nhật `view`, khác legacy `sources/news.php`, để bảo đảm read-only.

Nội dung CKEditor chỉ trả về plain text đã bỏ tag/HTML entity trong trường `content`; `richTextDeferred=true` ghi rõ giới hạn fidelity, preview không dùng `v-html`. Ảnh chỉ nhận basename extension cho phép từ upload news; video chỉ nhận URL HTTPS YouTube/Vimeo. Author chỉ có display name từ `#_user.fullname`, fallback là nhãn public hiện hữu; không trả email, phone hoặc address. Gallery, video và related đều lọc published.

Preview nội bộ `/migration-news-detail-preview?slug=...` dùng SiteHeader/Footer, noindex, dữ liệu CMS thật và link về canonical PHP. Đây chưa phải public Nuxt route và chưa có SEO parity claim.

Coordinator cần chạy PHP lint/contract và HTTP DB fixture trong Docker local. Node test nhẹ: `node --test frontend/tests/news-detail-contract.test.mjs` hoặc `npm test` tại frontend; không chạy build/browser/Docker trong worktree này.
