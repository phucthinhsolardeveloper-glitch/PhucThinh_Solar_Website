# ARTICLE-107 — HOLD, không tạo bản bài public

- **URL hiện tại:** `/den-nang-luong-mat-troi-nhung-ly-do-ban-nen-chon/` (live, http 200 theo snapshot 2026-11-16).
- **Trùng với:** ARTICLE-102 `/den-nang-luong-mat-troi-nhung-ly-do-nen-chon/`.

## Lý do không xuất bản độc lập

1. Nội dung live của 107 trùng hoàn toàn với 102: chung toàn bộ đoạn văn, cùng số liệu, cùng CTA; chỉ khác chữ "bạn" trong title và thẻ heading H3/H4. Kiểm tra trước đó (`legacy-public/voice-knowledge.md`) ghi SequenceMatcher 1.000. Lần này tôi chỉ so readable_text bằng mắt, chưa đo lại.
2. Cùng search intent ("vì sao nên chọn đèn năng lượng mặt trời"). Viết thêm một bản khác cho 107 chỉ để khác chữ sẽ tạo cannibalization với 102, đúng hard blocker trong `content-ops-v1-2026-11-16.md` §4.
3. 102 đã có bản nháp mới ở `revisions/ARTICLE-102.md`; bản đó phục vụ đúng intent này.
4. `seo.title` live của 107 đang là title của 102 ("…NHỮNG LÝ DO NÊN CHỌN!"), cho thấy hai bài vốn là một bài bị nhân đôi.

## Việc cần owner/SEO quyết định (không tự làm)

- So GSC (clicks, impressions, query, vị trí; tối thiểu 3 tháng), backlink và chuyển đổi của hai URL.
- Chọn URL canonical; nếu không có chênh lệch đáng kể, 102 là ứng viên (slug ngắn hơn, đã có bản nháp).
- Sau khi duyệt: 301 URL còn lại về URL canonical, cập nhật internal link và sitemap, theo dõi 404.
- Trước quyết định: không unpublish, không redirect, không sync DB, không đặt link nội bộ trỏ tới 107.
