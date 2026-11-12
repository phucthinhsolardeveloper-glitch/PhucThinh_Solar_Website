# PTS content quality round 2 — QA 2026-11-12

## Baseline và trạng thái

Snapshot `/home/admin01/orca/artifacts/PTS-CONTENT-REWORK-BASELINE-20261112.json` ghi nhận 39 Post: 33 article (12 public, 21 hidden) và 6 solution (5 public, 1 hidden). Tại thời điểm QA độc lập, các nguồn staged bắt buộc `backend/resources/editorial/2026-11-12/legacy-public/ARTICLE-{100..111}.md` và `solutions/SOLUTION-{61,132..135}.md` chưa tồn tại; 21 bài Markdown mới của writer cũng chưa phải đầu vào của đợt này.

Vì vậy chưa phân loại SOLID/NEEDS_CHANGES/HOLD_PUBLICATION cho nội dung đã chỉnh sửa: chưa có artifact để kiểm tra reader intent, ngày/nguồn, SEO, cấu trúc, hình ảnh, liên kết và claim. Tạm thời mọi mục rework là `HOLD_PUBLICATION` (evidence: staged source missing), lợi ích người dùng chưa thể xác nhận mà không suy đoán. Cần chạy lại QA sau khi worker outputs và proposal đã vào đúng thư mục; không đánh dấu hoàn tất trước checkpoint đó.

QA độc lập đã bổ sung các phát hiện trên bản public cũ: post 102 và 107 trùng body 100% với nhau (không phải với post 100); cả 12/12 bài public cũ chưa có outbound source link trong body; các title có nhiều chữ hoa; claim “độc quyền” ở 100/101/109 cần bằng chứng quyền; quyền ảnh/source credit chưa đối chiếu. Đây là gate `HOLD_PUBLICATION`, không được “chữa” bằng cách tự thêm mục Nguồn tham khảo khi chưa xác minh. Menu label local của solution id 133 đã là `Pin lưu trữ`; cần kiểm tra lại source-of-truth và clickable menu sau merge.

Các điểm bắt buộc khi review: giữ `<img>`/hyperlink và credit nguồn; giữ URL, title, ngày, category, cover, product links; không dùng ảnh không rõ license; rà SHARP/Blue Carbon “độc quyền”, loại claim TOP1 không có nguồn, và không trình bày tin chính sách lịch sử như chính sách hiện hành. Với slug `he-thong-luu-tru-bess`, nav title phải là `Pin lưu trữ`; không đổi slug hoặc `posts.title`, mọi menu entry vẫn clickable.

Draft có frontmatter hoặc mục `Ghi chú biên tập`/`Nguồn tham khảo cần gắn khi xuất bản` không được coi là quyền xuất bản: engine bắt buộc status approved tường minh, từ chối `hold`, `merge_candidate`, `Không xuất bản độc lập`, và từ chối mọi internal QA heading. Nguồn và ảnh phải có cờ xác minh explicit trong proposal; proposal corporate dùng `id` và `keywords` được parse riêng, không suy diễn quyền apply từ việc file draft tồn tại.

## Local sync engine

Đã thêm `pts:content-rework-apply`. Lệnh mặc định dry-run, đọc đúng baseline `records`, chỉ nhận các path source cố định cho 17 post trong phạm vi, hỗ trợ `--ids` whitelist để fail-closed theo nhóm được chọn và báo rõ các mục bị skip; HTML raw được lọc an toàn trước Markdown render, yêu cầu proposal summary/status/source/image evidence, kiểm tra id/kind/slug/visibility và hash body/summary/nav_title (cùng SEO hash nếu snapshot có). ID 133 bị skip bắt buộc vì nav title đã đổi sau baseline; cần tái baseline trước khi refresh. `--apply` bị chặn ngoài local/testing; post visible còn cần `--allow-visible`; trước transaction ghi backup private, transaction lock/recheck, không sửa metadata ngoài body/summary/nav_title/SEO description và không đổi visibility.

## QA gate tiếp theo

1. Worker đặt Markdown đúng path và proposal JSON approved ở đường dẫn tuyệt đối.
2. Chạy dry-run, kiểm tra report từng bài và ảnh/link/claim.
3. Chỉ operator local chạy apply với `--allow-visible` sau gate; chạy lại lần hai phải là NOOP.
