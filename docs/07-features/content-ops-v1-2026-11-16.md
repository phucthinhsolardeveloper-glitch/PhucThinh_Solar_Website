# Phúc Thịnh Solar — Content Marketing Operating System v1
Ngày: 2026-11-16 | Trạng thái: PILOT / READ-ONLY PRODUCTION | Chủ sở hữu phê duyệt: chưa ghi nhận

## 0. Mục tiêu và phạm vi
Tạo quy trình nghiên cứu -> brief -> biên tập -> QA -> CMS draft -> duyệt -> đo lường, tận dụng ChatGPT, plugin, Claude, Codex, Hyans MCP Bridge và Laravel/Nuxt hiện có. Không mua gói, tạo tool mới, tự công khai bài, sửa dữ liệu production hay tự đăng social trong pilot.

Kế thừa (không thay thế):
- docs/07-features/editorial-plan-180-2026-11-12.md (180 slot/60 ngày, không đồng nghĩa 180 bài sẵn sàng)
- docs/07-features/editorial-calendar-60-days-2026-11-13.md
- docs/07-features/editorial-voice-and-review-standard.md
- docs/07-features/editorial-photo-first-guidelines.md
- docs/07-features/pts-editorial-calendar-to-post-20261112.md
- backend/app/Domain/Content/PublishChecks.php
- backend/app/Domain/Content/PostReview.php

## 1. Thực trạng kiểm chứng 16/11/2026
- Repository chính: /home/admin01/src/Web_PTS_PHP (master; Laravel 13 + Nuxt 4).
- Nguồn lịch bài là backend/resources/editorial/2026-11-12/calendar-180.csv; không tạo backlog thứ hai.
- Theo tài liệu 12/11, 24 bài đầu có thể có bản nháp ẩn ở local; thông tin này là snapshot của tài liệu, KHÔNG coi là số liệu production hiện thời.
- ContentPulse có project phucthinhsolar.com, nhưng công cụ hiện trả existing_content=[], existing_ideas=[], gsc_data=[], published_articles=[]: chưa đủ để khẳng định kiểm tra trùng ý/hiệu suất trên website.
- GSC Wizard từ chối API vì trial/subscription; ưu tiên Search Console CSV xuất thủ công hoặc thử connector khác trước khi mua.
- PublishChecks hiện kiểm tra các điều kiện tối thiểu về title, slug, body, ảnh, summary/SEO description, category; KHÔNG phải đánh giá chất lượng SEO và nội dung.
- Audit ví dụ công khai /tin-tuc/8-ly-do-ban-nen-su-dung-den-nang-luong-mat-troi-ngay-bay-gio/: lỗi chính tả và khẳng định 'An toàn tuyệt đối 100%' cần chỉnh, xác minh ở bản cập nhật.

## 2. Hệ thống vai trò, không tạo nhiều người viết cùng lúc
A. Owner/editor-in-chief: duyệt chủ đề/giọng điệu/nguồn ảnh, chấp thuận publish.
B. ChatGPT: điều phối, tìm intent, cụm chủ đề, content brief, đối chiếu nhu cầu với GSC + phản hồi sales, nhận đánh giá hai reviewer, quyết định revisions; KHÔNG tự coi số liệu SEO ước đoán là thật.
C. SEO research plugin: thử OpenSEO / Ahrefs trong tài khoản được chủ sở hữu kết nối; đối chiếu volume/KD/khả năng SERP với Search Console, gắn ngày và nguồn; không có dữ liệu thì ghi 'chưa xác minh'.
D. ContentPulse: bổ trợ ideation và theo dõi nội dung chỉ sau khi ingest được các bài/queries thực; hiện chưa dùng để kết luận chống trùng.
E. Claude: viết/biên tập tiếng Việt thân thiện, giữ thuật ngữ chuẩn và nguồn theo brief, chống văn phong rập khuôn.
F. Codex: kiểm tra logic kỹ thuật, claim và dữ liệu có nguồn, slug/SEO schema, liên kết nội bộ & sản phẩm, lỗi HTML; kiểm tra độc lập với người viết; không giao Codex tự phê duyệt bài do chính nó tạo.
G. Hyans MCP Bridge: chuyển brief/Markdown/QA giữa ChatGPT và máy công ty; truy xuất nguồn repo; có thể sử dụng hội thoại agent. Không coi Bridge là hệ thống cấp quyền xuất bản.
H. CMS Laravel Admin: bản nháp ẩn, revision, người có quyền content_publish duyệt.
I. Marketing/sales/technical reviewer: xác thực kinh nghiệm thực tế, ảnh dự án, nhu cầu KH, model, bảo hành; không được tạo testimonial giả.

## 3. Quy trình 8 cổng (status không được tự nhảy)
S0 BACKLOG: Chỉ dùng calendar-180 hiện có; cập nhật trạng thái theo plan_key. Có keyword cluster, persona, funnel stage, ưu tiên, link bài trùng. Không chọn chủ đề chỉ để đủ số lượng.
S1 DISCOVERY: Thu thập GSC queries/pages 28 ngày và 3 tháng so sánh, SERP top pages, phản hồi ZCRM sales (ẩn thông tin cá nhân), FAQ sản phẩm, datasheet hãng và tin chính thức. Tách fact/quote/assumption. Output: evidence-register.
S2 BRIEF APPROVED: Một bài một search intent chính, đối tượng, câu hỏi cần trả lời trong 30 giây, giá trị mới, khác bài liên quan, 3-5 từ khóa phụ (không nhồi), CTA đúng funnel, yêu cầu ảnh và link sản phẩm. Phải so danh sách URL/slug cũ + backlog 180.
S3 RESEARCH VERIFIED: Kết luận chuyên môn có evidence URL, đơn vị, ngày, model, điều kiện; nếu không có nguồn thì bỏ hoặc nói 'giả định' rõ. Bảo hành, thông số, đơn giá và chính sách lấy từ nguồn hãng/chứng từ được duyệt; không lấy website công ty làm nguồn chứng minh claim.
S4 DRAFT: Claude (hoặc ChatGPT) viết theo brief, tự nhiên, cụ thể, khác cấu trúc theo từng loại bài. Nguồn cuối bài, đề xuất title/meta, H2/H3 hợp lý, image brief, internal links có giải thích. Chưa viết đủ thì để draft, không cho thành 'ready'.
S5 INDEPENDENT QA: Claude review voice/readability, Codex review technical/SEO/links/evidence, owner/team kỹ thuật duyệt claim quan trọng. Điều kiện FAIL giữ nguyên draft và gửi phản hồi actionable. Không tự duyệt bài mình viết.
S6 CMS READY: Ảnh nguồn/licence/credit/alt hợp lệ, sản phẩm liên quan thật và phù hợp, link nội bộ truy cập được, canonical, metadata, preview mobile, không ảnh placeholder, không claim tuyệt đối. Import chỉ tạo/giữ bản nháp ẩn; yêu cầu content_publish cho thao tác xuất bản.
S7 PUBLISH & OBSERVE: Owner duyệt xuất bản. Ghi ngày, URL, source, phiên bản, CTA/event tracking. Kiểm tra 200/canonical/indexability, sitemap; đợi dữ liệu 7/28/90 ngày, không coi indexation hay ranking là tức thì.
S8 REFRESH: So impressions, clicks, CTR, vị trí trung bình, engaged sessions, leads chất lượng và assisted conversions (khi tracking có), phân nhóm giữ / cập nhật / gộp / redirect. Không thay ngày bài chỉ vì SEO.

## 4. Hard blockers (dù điểm cao vẫn không công khai)
- Claim kỹ thuật không có nguồn, sai điều kiện, sai model, đơn vị, thiết bị không tương thích.
- Cam kết tuyệt đối '100% an toàn', 'chắc chắn hoàn vốn', 'tuổi thọ/bảo hành X năm' thiếu điều kiện.
- Tin về chính sách, giá điện, quy định chưa đối chiếu văn bản chính thức hiện hành.
- Copy nội dung bên khác, ảnh không rõ quyền sử dụng, gắn ảnh stock là công trình thật.
- Trùng intent/cannibalization với bài đang có mà chưa giải thích vai trò/hướng gộp.
- Bản thảo có placeholder, liên kết gãy chính, sản phẩm gán sai, CTA sai nhu cầu.
- Người viết hoặc AI tự công khai không có quyết định của người có quyền.

## 5. Scoring trước duyệt
Kế thừa rubric trong editorial-voice-and-review-standard.md: 6 tiêu chí x 0–5:
1. Dễ hiểu; 2. Hữu ích thực tế; 3. Tự nhiên; 4. Chính xác nguồn; 5. Khác biệt; 6. Bố cục.
Yêu cầu tổng >=24/30 và 3,4 >=4. Thêm các cổng độc lập PASS/FAIL: keyword intent & trùng bài; technical SEO; image/license; source claims; sản phẩm/CTA; preview. Điểm không thay thế fact-check.
Mẫu dùng: docs/07-features/content-brief-qa-template-v1-2026-11-16.md.

## 6. Tương tác plugin thực tế
- ĐANG DÙNG: Hyans MCP Bridge (file/agent), ContentPulse (ideation nhưng data rỗng), Firecrawl (trích xuất nguồn công khai), Canva (thiết kế chỉ khi ảnh đúng và có quyền), Metricool (social scheduling sau duyệt), Google Drive (review chia sẻ).
- THỬ: OpenSEO (GSC + SERP) hoặc Ahrefs (keyword gap); không thêm đồng thời nhiều công cụ trả phí chỉ để làm việc giống nhau.
- KHÔNG PHÙ HỢP CMS: WordPress/Webflow plugin không thể mặc nhiên đăng vào Laravel Filament custom.
- GSC Wizard đang yêu cầu đăng ký, không dùng như nguồn dữ liệu hoạt động.
- Chốt chi phí khi có log: thời gian nghiên cứu/viết/QA mỗi bài, sự cố claim, tỷ lệ bài duyệt ngay và lead mang lại.

## 7. Pilot 7 ngày, tránh bắn 3 bài/ngày vô tội vạ
Ngày 1: Lập inventory URL, plan_key, category, status, source và GSC CSV (nếu sẵn).
Ngày 2: Audit 10 bài cũ đại diện: 3 trùng nhau, 3 bài kỹ thuật, 2 bài bán hàng, 2 tin tức. Phân loại sửa/gộp/giữ.
Ngày 3: Lấy một bản nháp sẵn có (ví dụ PTS-KT-008), hoàn thành brief+evidence-register+QA; không tạo bài cùng intent mới.
Ngày 4: Biên tập + technical QA + duyệt media, gắn product phù hợp và kiểm tra preview.
Ngày 5: Owner chọn duyệt xuất bản 1 bài hoặc giữ ẩn, ghi chép lỗi thực tế.
Ngày 6: Chuyển 1 bài thành script social/post ngắn với CTA và UTM nhưng chưa tự lên lịch.
Ngày 7: Báo cáo time-to-draft, time-to-ready, tỉ lệ qua QC lần đầu, blockers, số việc thủ công, cost plugin. Quyết định cải tiến SOP trước khi tự làm tool.

Chỉ nâng lên 2–3 bài/ngày nếu nguồn, người kiểm duyệt, ảnh và chất lượng đáp ứng. Không cần đăng cho đủ slot.

## 8. Khi nào tự xây skill/tool?
1) Sau >=10 bài pilot mà lỗi lặp lại đều thuộc cùng một bước -> viết skill Markdown quy trình và checklist cho Claude/Codex.
2) Nếu dữ liệu nguồn buộc copy/paste mỗi lần và có API chính thức/quyền truy cập -> viết integration/MCP nhỏ theo nghiệp vụ.
3) Nếu CMS đang thiếu cổng kiểm chất lượng, thêm trường data/rule và tests vào Laravel nhưng chỉ sau thiết kế được duyệt.
4) Nếu plugin thiếu dữ liệu, kiểm tra cấu hình/sync và quyền trước khi kết luận sản phẩm không hiệu quả.
5) Không tạo CMS thứ hai: Laravel/PostRevision vẫn là source of truth trạng thái công khai.

## 9. KPI tối thiểu và nhật ký
- Mỗi bài: plan_key, owner, URL/slug, intent, cluster, persona, status, reviewer, blockers, source citations, media rights, product links, publish timestamp.
- Theo tuần: số bài đạt QC, tỉ lệ reject/rework, giờ công/bài, lỗi claim nguồn, bài trùng intent, leads từ organic, chất lượng lead.
- SEO: GSC clicks/impressions/CTR/position theo page+query, đo tách branded vs non-branded; GA4 organic landing/engagement/events tư vấn nếu tracking đã triển khai.
- Giữ baseline trước khi sửa; đánh giá theo thời gian, có ghi nhận thay đổi site/campaign, không quy công cho AI khi thiếu đối chứng.

## 10. Nguyên tắc bảo mật và kết nối
Chỉ trích xuất câu hỏi khách hàng ở dạng đã xóa PII. Không đưa credential từ .env, danh bạ hoặc bí mật CRM vào plugin nghiên cứu công khai. Tool chỉ được cấp quyền cần dùng. Không auto-publish; mọi bước gây thay đổi production cần approval rõ ràng.
