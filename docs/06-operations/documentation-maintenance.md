# Lịch rà soát và bảo trì tài liệu

Tài liệu là một phần của sản phẩm vận hành. Không cần ghi mọi thay đổi nhỏ theo
thời gian thực, nhưng phải cập nhật khi kiến thức đã trở thành quy tắc, workflow,
kiến trúc hoặc hành vi ổn định của project.

## Nhịp rà soát

- **Sau milestone lớn:** rà soát ngay khi hoàn tất tính năng lớn, migration,
  thay đổi schema, thay đổi deploy/rollback, bảo mật, backup hoặc cấu trúc thư mục.
- **Hàng tháng:** rà soát nhanh runbook vận hành, route/chức năng chính, mode
  deploy, retention backup/release/image, timer và các liên kết tài liệu.
- **Hàng quý:** rà soát sâu toàn bộ docs liên quan đến kiến trúc, quyền hạn,
  dữ liệu, restore/rollback và checklist release; đánh dấu tài liệu lỗi thời,
  gộp tài liệu trùng và cập nhật owner/assumption.
- **Trước release lớn:** không đóng release nếu docs vẫn mô tả workflow cũ hoặc
  thiếu hướng dẫn rollback/khôi phục tương ứng.

## Cách thực hiện

1. Đọc thay đổi thực tế trong code, script, schema/config và output vận hành.
2. Tìm các docs có lệnh, đường dẫn, tên bảng, mode deploy hoặc giả định liên quan.
3. Cập nhật runbook/README trước; ghi quyết định và kiến thức mới vào lịch sử.
4. Chạy `git diff --check`, kiểm tra link/path quan trọng và ghi rủi ro còn lại.

Không đưa credential, token, PII hoặc log chứa dữ liệu nhạy cảm vào docs.

Các file trong `docs/13-handoffs/`, audit có ngày tháng và các mục lịch sử trong
`docs/00-project/status.md` được giữ để truy nguyên quyết định cũ. Khi rà soát,
không sửa ngược sự kiện lịch sử; nếu trạng thái hiện tại khác, thêm mục hiện tại
và liên kết về runbook đang có hiệu lực.

## Lịch sử rà soát

### 2026-10-31 — Nuxt migration branch và resolver checkpoint

- Đối chiếu refs sau `git fetch`: remote feature Nuxt đã nằm trong `master`, một
  worktree còn lại; local `master` ahead `origin/master` 102 commit. Không push,
  merge hay dọn WIP.
- Cập nhật trạng thái hiện hành tại
  `docs/audits/pts-nuxt/REMEDIATION-STATUS-20261031.md`: 27 preview routes có
  noindex route rule và SSR headers; resolver news/project chạy trên local Nuxt
  + PHP bridge, với PHP source lint/hash khớp checkout.
- Đổi 23 page từ import tĩnh middleware sang tên middleware trong `definePageMeta`;
  29 test route/SEO và toàn bộ frontend test (468/468), typecheck, production
  build đều đạt. Cảnh báo `INEFFECTIVE_DYNAMIC_IMPORT` đã hết.
- Nitro build được smoke qua Caddy QA harness tạm với public SSR, hai alias, hai
  slug động, API, media thật, noindex và đường dẫn không tồn tại. Container/config
  tạm đã dừng/xóa; harness không thay production Caddy/Compose.
- Frontend full test đạt 468/468. Compose config không có env file báo thiếu biến
  deploy; cùng lệnh dùng `deploy/vps/stack.env.example` parse thành công. File mẫu
  chỉ xác minh interpolation/schema, không chứng minh runtime secrets hay image;
  thư mục secrets checkout chỉ có README. Không điền secret giả hoặc khởi động
  service.
- Rerun local CMS path bằng PHP bridge thật: 7 category, catalog tổng 372 sản
  phẩm, danh mục `den-nlmt` có 203; resolver và PDP JINDIAN 500W trả đúng
  canonical, slug lạ 404. Category/PDP SSR đạt ở viewport 390/1440, locale vi,
  không lỗi page hoặc tràn ngang. Đây là bằng chứng local, không đóng release
  edge hoặc commerce gate.
- Nâng lock Nuxt lên 4.6.3 để nhận bản vá dot-segment path bypass; test 468/468,
  typecheck, Docker image build và local Caddy route/security smoke đều đạt. Ba
  encoded dot-segment requests trả 404. `npm audit --omit=dev` còn 14 advisory
  transitive; không dùng `--force` vì npm đề xuất downgrade Nuxt xuống 4.1.3.
  Runtime image chỉ mang `.output`, manifest không khai báo các package build
  chain bị báo; đây chưa phải image SBOM/vulnerability scan đầy đủ.
  API-only PHP bridge chặn `/thumbs` và `/assets`; QA tiếp theo dùng PHP media
  probe cùng image với webroot/upload/thumbs read-only, tmpfs cache/log và mạng
  riêng không có DB. Caddy edge trả 200 đúng MIME cho thumbnail wordmark, site
  visuals PNG/WebP, upload gốc và thông báo Bộ Công Thương. Còn một thumbnail
  emblem footer `520x426x3` chưa có trong local volume; original 200 nhưng sinh
  cache lần đầu cần DB, nên giữ gate đó mở. Không sửa DB/volume. Caddy/Nuxt/media
  QA containers/network đã dừng; giữ rollback image cũ local, không đổi
  production.
- Giữ **NO-GO** cutover: chưa smoke bằng production Caddy/Compose trên release
  image, chưa có release checksum, commerce fixtures/session flow hoặc rollback
  rehearsal. Báo cáo Caddy cũ vẫn là bằng chứng lịch sử riêng.

### 2026-10-30 — Nuxt public routes và audit icon/tương tác

- Cập nhật chuẩn icon trong `docs/02-development/public-ui-ux-design-system.md`:
  `UIcon` + Lucide cục bộ cho icon phổ thông; giữ inline SVG thiết kế riêng và
  icon ảnh do Admin/CMS quản lý. Không dùng Font Awesome khi frontend không nạp
  đúng asset của thư viện.
- Ghi audit inventory icon, feature toggles và tương tác tại
  `docs/14-nuxt-migration/ICON-INTERACTION-UI-AUDIT-20261030.md`. P1 fallback
  Font Awesome đã được thay bằng Nuxt UI; các gap header/modal nền và viewport
  320px còn mở, tách riêng để không làm lệch phần header đang được giữ nguyên.
- Các cờ sở hữu route Nuxt đã bị loại khỏi source/config; cờ hiển thị section
  trong CMS và SEO indexability vẫn là hai trách nhiệm độc lập. Nuxt được build
  và cập nhật vào container local rehearsal `127.0.0.1:3101`; smoke GET 33/33,
  trong đó 4 alias redirect canonical. Không đổi production, Caddy hay dữ liệu.
- `npm test` 396/396, typecheck và build PASS. WIP vẫn ở working tree, chưa
  commit/push/merge; rollback image được giữ bằng tag
  `rollback-before-icon-interaction-audit-20261030`.

### 2026-10-29 — Nuxt checkout handoff giữ PHP làm order owner

- Ghi trạng thái hiện tại trong `docs/14-nuxt-migration/NEXT-EXECUTION-PLAN.md`
  và route supplement: Nuxt checkout form gated riêng, gửi native POST trực tiếp
  tới PHP `phpPublicOrigin` tại `/gio-hang` (không có slash cuối) để relative
  redirect `gio-hang/` và `cam-on/` resolve từ root; Nitro POST checkout tiếp
  tục 410 và PHP giữ toàn bộ transaction/security/payment.
- Caddy local-full-nuxt có POST matcher cho `/gio-hang`; checkout flag mặc định
  OFF. Không đổi local runtime, production route, DB hoặc tạo đơn.
- PHP validation flash được đọc một lần qua endpoint riêng chỉ gọi từ Nuxt
  CartPage; form draft được giữ trong Nuxt memory state và xóa sau hydration.
  SiteHeader không gọi endpoint này nên không thể tiêu thụ flash trước CartPage.

### 2026-10-29 — UI acceptance, route fallback và nested-route audit

- Bổ sung kết quả theo từng route/section và phân biệt code gate với visual gate
  tại `docs/14-nuxt-migration/UI-ACCEPTANCE-AUDIT-20261028.md`.
- Ghi nhận ba route regressions được phát hiện khi GET build cô lập: nested
  solution detail bị parent hub che; trailing slash category bị catch-all param
  làm rơi về catalog root; bare-slug route gọi classifier dù mọi public detail
  flag OFF. Source đã sửa và smoke lại.
- Cập nhật fallback query/filter PHP, quantity 1–999, empty-state spacing,
  mobile typography và hit areas theo design system.
- Bổ sung audit source lần hai: Projects pagination, query-preserving fallback
  cho News/Projects/Gallery/Video, SSR TOC solution, catalog 404, guard sản phẩm
  ẩn khi thêm cart và giữ session khi quote-only. Frontend suite hiện 274/274,
  typecheck/build PASS; PHP/DB/TLS visual runtime vẫn chưa sẵn sàng.
- Không thể ký visual parity: PHP/API `8080/8088` không listen; không có ảnh mới
  để review. Feature flags giữ OFF; không đổi production, container, DB hoặc
  Caddy. Chi tiết checkpoint nằm trong `NEXT-EXECUTION-PLAN.md`.

### 2026-10-29 — xử lý audit form, media, canonical và bridge security

- Đóng thêm lỗi source-backed ở Contact/Dealer retry, ảnh CTA About, path media
  equipment solution và redirect handling của PHP bridge; cập nhật regression
  tests tương ứng.
- Sửa PHP canonical brand/policy theo public route manifest; thêm test mock chỉ
  đọc. PHP CLI chưa có nên lint/test PHP vẫn chờ runtime phù hợp.
- Rebuild frontend source: 278/278 tests, typecheck và build PASS; smoke 26/26
  public fallback route PASS trên build server cô lập, process đã dừng.
- Ghi rõ production `/product/` 404 và canonical brand/policy lệch integration;
  không thay production. Visual review vẫn chờ PHP/API local dù Chromium đã có.
  Feature flags OFF, không sửa DB/container/Caddy/volume, không push/deploy.
  Chi tiết bằng chứng trong `UI-ACCEPTANCE-AUDIT-20261028.md`.

### 2026-10-28 — UI acceptance audit và checkout preview an toàn

- Ghi bảng trạng thái source cho toàn bộ 26 route trong manifest tại
  `docs/14-nuxt-migration/UI-ACCEPTANCE-AUDIT-20261028.md`; phân biệt code fix,
  visual pending và dữ liệu cần xác minh trực tiếp.
- Cập nhật checkpoint tích hợp: frontend tests/typecheck/build xanh, nhưng PHP
  preview/API offline nên không có visual sign-off mới; feature flags giữ OFF.
- Ghi rõ checkout preview chỉ đọc, POST bị khóa HTTP 410 và không thể tạo đơn;
  đánh dấu nội dung checkout cũ trong handoff Batch B là lịch sử.
- Các giá trị Dealer/About/Policy và HTTP alias redirects vẫn cần kiểm tra khi
  local PHP/CMS hoạt động; không suy đoán dữ liệu.

### 2026-10-28 — cập nhật checkpoint tích hợp Nuxt và dealer preview

- Đánh dấu handoff Batch B và route matrix cũ là snapshot lịch sử; cập nhật
  nguồn trạng thái hiện tại tại `docs/14-nuxt-migration/NEXT-EXECUTION-PLAN.md`.
- Ghi rõ dealer DTO lấy intro/overview từ hai field CMS riêng, rich text qua
  sanitizer, và chỉ lấy thống kê đang hiển thị.
- Ghi lần crawl R18 bị loại vì PHP preview offline; tránh coi `ERR`/502 là gate
  PHP/Nuxt và nhắc chạy lại sau khi nguồn local được phục hồi.
- Mở rộng quy tắc `hrefTargetAudit`: bỏ qua mutation query và dừng trước redirect
  tới mutation target; tests local phủ hai trường hợp.

### 2026-10-28 — khôi phục bố cục trang chủ product-first cho Nuxt

- Đối chiếu operating contract với Nuxt homepage preview; ghi nhận ADR 0006 đã
  lệch yêu cầu hiện hành về Hero và các khối nội dung dài.
- Cập nhật UI/UX system, đánh dấu ADR 0006 superseded ở phần bố cục trang chủ và
  thêm ADR 0008. Nuxt preview mở đầu bằng danh mục/sản phẩm; không thay public
  route flag, PHP production hoặc dữ liệu CMS.

### 2026-10-27 — chuẩn hóa workflow bằng evidence và handoff

- Bổ sung discovery/ownership, acceptance negative path, verification ladder và
  phân loại failure vào workflow website.
- Chuẩn hóa bằng chứng điều phối agent `DISPATCHED/EXECUTED/VERIFIED` và format
  handoff `DONE/VERIFIED/REMAINING/KNOWN ISSUES/RELEVANT FILES/NEXT`.
- Tách build/regression khỏi browser, integration, security và UAT; không dùng
  một lớp kiểm tra để thay cho lớp khác.

### 2026-10-04 — tối ưu tiếp public và Admin local

- Bổ sung số đo bốn route public, nguyên nhân bundle thừa Jssor/Bootstrap 4.0,
  ảnh banner đầu lazy và nút lưu Admin chờ `window.load` vào
  [audit hiệu năng](../10-audits/2026-10-03-website-admin-performance.md).
- Chỉ loại asset được xác minh không dùng hoặc bị ghi đè; giữ hai bản Bootstrap
  còn lại cho đến khi kiểm chứng đầy đủ tương thích Popper/dropdown.

### 2026-10-03 — đo hiệu năng website và Admin local

- Ghi baseline trình duyệt, truy vấn Admin, nguyên nhân ảnh gốc ở danh sách
  sản phẩm, tải thư viện theo màn và hiệu ứng đóng mega-menu tại
  [audit hiệu năng](../10-audits/2026-10-03-website-admin-performance.md).
- Xác nhận bài học từ đợt tách asset công khai trước: chỉ tối ưu theo route
  sau khi có regression tương tác và visual; giữ các bundle hiện hành cho
  những route chưa được kiểm chứng.

### 2026-09-29 — retention, backup và deploy workflow

- Ghi nhận vai trò của `incoming/`, `releases/`, `shared/` và chính sách giữ 1–2
  backup ứng dụng gần nhất.
- Phân biệt backup ứng dụng với backup/snapshot VPS CloudFly.
- Ghi quy tắc dọn Docker build cache/image an toàn và rollback candidate.
- Bổ sung `AUTO` deploy, deployment log và quy trình rà soát định kỳ.
- Chuẩn hóa quy tắc phối hợp dùng chung trong `AGENTS.md`: phân loại rủi ro,
  checkpoint tác vụ dài, cleanup có kiểm soát và format báo cáo để agent khác
  không phải thiết lập lại quy trình.
- Rà soát comment source: loại bỏ lời hội thoại, câu trích dẫn và cách kể lại
  phản hồi cá nhân; giữ lại rationale kỹ thuật, invariant và mốc kiểm chứng.
- Thêm tài liệu bàn giao quy trình cho AI ở project khác tại
  `docs/12-ai/codex-project-handoff.md`; phân biệt rõ rule có thể chia sẻ với
  skill phụ thuộc môi trường.
