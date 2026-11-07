# PTS Solar — Claude Migration Master Spec
## Legacy PHP 8.1 → Laravel 13 + PHP 8.4 + Filament 5 + Nuxt 4

Ngày cập nhật: 07/11/2026
Mục đích: tài liệu nguồn để Claude Code/Codex triển khai migration backend thật nhanh nhưng có kiểm soát.
Trạng thái: SPEC / IMPLEMENTATION BLUEPRINT — chưa phải bằng chứng đã triển khai.
Repository: /home/admin01/src/Web_PTS_PHP
Source baseline được audit: master = origin/master = a8632eed4d87f2b153c515800fcc89c955ff89c1

Quyết định kiến trúc:
- Giữ Nuxt 4/Vue 3 làm public frontend.
- Thay backend PHP tùy biến bằng Laravel 13 chạy PHP 8.4.
- Dùng Filament 5 làm nền quản trị và xây PTS Visual Studio trên Filament.
- Giữ MariaDB/InnoDB.
- Không chuyển NestJS/PostgreSQL/microservices trong cùng đợt.
- Redis, Horizon, Octane/FrankenPHP, object storage chỉ thêm khi có bằng chứng cần.

Định nghĩa “chuyển nhanh”:
Phân tích nghiệp vụ và kỹ thuật đủ sâu để giảm rework, khóa contract, chia ownership rõ, chạy nhiều agent song song, tự động hóa parity/QA. Không được hiểu là bỏ test, dịch nguyên file PHP sang Laravel, đổi schema ồ ạt hay cutover thiếu rollback.

---

# 0. HƯỚNG DẪN BẮT BUỘC CHO CLAUDE CODE

Claude phải đọc theo thứ tự trước khi sửa code:

1. AGENTS.md
2. docs/SO-TAY.md
3. docs/12-ai/context-map.md
4. tài liệu này
5. docs/01-architecture/codebase-guide.md
6. docs/03-data/database.md
7. docs/05-security/auth-rbac.md
8. docs/07-features/admin-guide.md
9. docs/02-development/nuxt-storefront-guide.md
10. docs/02-development/testing.md
11. docs/10-audits/2026-11-05-backend-frontend-admin-audit.md
12. frontend/shared/contracts/public-*.ts
13. frontend/server/routes/_migration/*

## 0.1. Các lệnh cấm trong migration

- Không sửa production khi chưa được chủ project cho phép rõ.
- Không chạy migration schema trên production chỉ vì code đã sẵn sàng.
- Không ghi test vào DB local dùng chung nếu test có mutation.
- Không dual-write cùng một domain từ PHP và Laravel.
- Không cho nhiều agent cùng sở hữu backend/database/migrations.
- Không đổi API contract Nuxt để làm test Laravel pass.
- Không đổi MariaDB sang PostgreSQL trong migration này.
- Không đổi toàn bộ tên bảng/cột chỉ để “đẹp”.
- Không di chuyển/rename toàn bộ shared/upload trong cùng đợt.
- Không đưa Redis/Octane/Kafka/microservices vào foundation khi chưa có benchmark hoặc nhu cầu thật.
- Không tự reset password Admin hoặc bỏ legacy verifier.
- Không dùng production data có PII làm fixture commit vào Git.

## 0.2. Nguyên tắc bằng chứng

Ưu tiên nguồn sự thật:
runtime thực tế > test thực tế > source code > schema > docs > suy luận

Khi tài liệu và code mâu thuẫn, ghi NEEDS VERIFICATION, đo lại và cập nhật tài liệu. Không chọn kết luận thuận tiện nhất.

---

# 1. SNAPSHOT HIỆN TẠI SAU ĐỢT NÂNG CẤP GIAO DIỆN

## 1.1. Git

- master sạch và đồng bộ origin/master tại a8632eed.
- Nhánh feature/mobile-liquid-glass-ux-20261106 đã được merge; branch và master cùng trỏ a8632eed.
- Worktree hiện còn pts-release-prod detached ở commit cũ phục vụ release/rollback.
- So với 3e2127a5, đợt nâng cấp mới chủ yếu là Nuxt/UI/mobile/search/3D/test.
- Trong api/, libraries/, administrator/, migrations/, phần backend nghiệp vụ gần như không đổi; chỉ pts_v1_home.php và pts_v1_about.php có thay đổi nội dung/contract nhỏ.

Kết luận:
Đây là baseline tốt để tách lane Laravel vì frontend mới đã merge, còn business logic PHP vẫn ổn định để dùng làm executable specification.

## 1.2. Production khác source baseline

Nhật ký 07/11 cho biết deploy a8632eed chưa lên production vì predeploy dừng ở test-admin-crud-lifecycle.php khi container pts-nuxt-service-rehearsal chưa chạy.
Production vẫn ở image 04dde592c441 theo deployment log.
Sau audit, container rehearsal đã được bật và healthy, nhưng chưa coi đó là bằng chứng production đã cập nhật.

Phải tách:
SOURCE BASELINE = a8632eed
PRODUCTION RUNTIME BASELINE = image ghi trong deployment log / runtime được xác minh khi cần

Migration Laravel lấy source baseline cố định để code. Production chỉ dùng để xác minh hành vi/rollback, không được dùng lẫn với HEAD source.

## 1.3. Database local đã xác minh

- 59 bảng, 59/59 InnoDB.
- Kích thước khoảng 19.28 MB local.
- Chỉ có 1 foreign key thật: table_product_campaign_scope.id_campaign -> table_product_campaign.id.
- Direct count tại 07/11:
  - table_product = 399
  - table_news = 82
  - table_content_section = 462
  - table_order = 0
  - table_contact = 2
- table_content_section:
  - product = 413
  - cat = 27
  - brand = 13
  - list = 9
- table_news đang có 19 type có dữ liệu.

Lưu ý: số liệu local không được xem là production count.

## 1.4. Runtime/stack hiện tại

- Legacy runtime local: PHP 8.1.x.
- Nuxt: 4.x, Vue 3, TypeScript strict, Nuxt UI 4, Tailwind 4.
- Production hiện là Caddy + Nuxt + PHP web + MariaDB.
- Caddy đang giữ /api/*, /administrator*, upload/thumbs và một số legacy path ở PHP.
- Nuxt vẫn dùng các Nitro bridge /_migration/* để gọi PHP DTO.

---

# 2. TẠI SAO CHỌN LARAVEL 13 + FILAMENT 5

## 2.1. Laravel

Laravel 13 yêu cầu PHP >= 8.3. Target migration là PHP 8.4 để giảm số biến thay đổi; PHP 8.5 có thể benchmark sau khi parity hoàn tất.

Không bê PHPExcel/legacy libraries sang Laravel; chỉ chuyển nghiệp vụ cần thiết sang package hiện đại.

Laravel phù hợp hơn NestJS cho PTS vì:
- business logic hiện có bằng PHP, giảm chi phí chuyển ngôn ngữ;
- Laravel có Eloquent/Query Builder, validation, policies, queues, cache, mail, storage, scheduler, testing;
- Filament 5 giải quyết phần lớn Admin CRUD/workflow nhanh;
- Laravel Boost hỗ trợ Claude Code/Codex đọc schema, logs, routes và docs đúng version;
- Nuxt không yêu cầu backend phải Node.js.

## 2.2. Filament 5

Filament 5 là nền quản trị chính.
Không dùng Statamic song song.
Không dùng page-builder plugin cộng đồng làm nguồn dữ liệu lõi.

Filament phụ trách:
- CRUD nghiệp vụ;
- bảng, filter, search, saved views;
- bulk actions;
- forms động theo loại sản phẩm;
- permissions/policies;
- media picker;
- dashboard/monitoring;
- Content Studio;
- PTS Visual Studio.

## 2.3. PTS Visual Studio

Visual Studio là lớp CMS riêng xây trên Filament, không phải một CMS thứ hai.

Mục tiêu:
Section tree | Nuxt live preview | Inspector

Cho phép:
- sửa text/ảnh/video/CTA;
- bật/tắt section;
- sắp xếp các section được phép;
- desktop/mobile preview bằng chính Nuxt component;
- draft/publish;
- revision/diff/rollback;
- audit ai sửa gì;
- field constraints theo design system.

Không cho Admin nhập CSS/JS tùy ý hoặc phá layout production.

---

# 3. KIẾN TRÚC ĐÍCH

Browser / crawler
  -> Caddy
     -> Nuxt 4 public SSR/UI
     -> Laravel 13 PHP 8.4
        -> Filament 5
        -> PTS Visual Studio
        -> Domain services
        -> Auth/Policies
        -> Jobs/Notifications
        -> MariaDB InnoDB
        -> shared/upload qua Storage adapter

## 3.1. Không dùng microservice

Target là modular monolith. Một Laravel app, một deployment unit, boundary rõ bên trong.

Đề xuất cấu trúc:

backend/
  app/
    Domain/
      Catalog/
      Content/
      Projects/
      Media/
      SEO/
      Leads/
      Commerce/
      Identity/
      Settings/
      Integrations/
    Application/
      Actions/
      Queries/
      DTOs/
    Infrastructure/
      Legacy/
      External/
    Http/
      Controllers/Api/V1/
      Requests/
      Resources/
    Filament/

Không tạo Repository class cho mọi model theo máy móc.
Chỉ abstraction nơi thực sự có legacy mapping, external gateway hoặc query phức tạp.

---

# 4. CHIẾN LƯỢC DATABASE: COMPATIBILITY TRƯỚC, NORMALIZE SAU

## 4.1. Phase Compatibility

Laravel đọc/ghi schema hiện tại qua model/adapters.
Không rename bảng/cột hàng loạt.

Legacy conventions phải được encapsulate:
- status = hienthi,noibat -> StatusFlags value object / scope.
- namevi/nameen, descvi/descen -> LocalizedText accessor/DTO.
- date_created/date_updated integer -> legacy timestamp casts.
- options/additional_specs -> typed parser/cast có validation.
- zero IDs như id_cat=0 -> nullable domain relation ở service layer, không tự sửa DB trong compatibility phase.

## 4.2. Product table: rủi ro chính

table_product là bảng rộng, chứa taxonomy IDs, media, nội dung song ngữ, giá và nhiều field kỹ thuật.

Nhóm field quan trọng:
- id_list, id_item, id_cat, id_sub, id_brand;
- photo, photo_khuyenmai, photo_feed;
- slugvi/en, namevi/en, contentvi/en, descvi/en;
- regular_price, sale_price đang là DOUBLE;
- cong_suat DECIMAL;
- battery_capacity, panel_power, waterproof_rating, installation_height và các specs khác;
- additional_specs, package_contents, warranty;
- status, type, pdp_profile;
- timestamps integer.

Không biến toàn bộ bảng này thành một Eloquent model béo.
Tách read/write logic vào services và typed DTO.

## 4.3. Content inheritance phải giữ nguyên

table_content_section có:
- scope = product | brand | list | cat
- mode
- inherit_source
- blocksvi/blocksen
- status

Hiện có 462 record.

Laravel phải có ProductContentResolver và tests riêng cho:
- own;
- inherit;
- both/stack;
- hide;
- fallback product -> brand/cat/list theo behavior legacy;
- hidden source;
- missing source;
- bilingual fallback.

Không cho Filament/public controller đọc bảng này trực tiếp rồi tự ghép logic.

## 4.4. table_news không tách ngay

table_news đang multiplex nhiều loại nội dung:
tin-tuc, giai-phap, du-an, chinh-sach, blocks marketing...

Không split vật lý ngay trong phase migration.

Tạo query/domain wrappers:
- NewsArticle
- Solution
- Project
- Policy
- ContentBlockRecord

Cùng map vào legacy table trong phase compatibility.
Sau cutover mới quyết định tách bảng nếu lợi ích đủ lớn.

## 4.5. Money

Legacy có DOUBLE ở product/order/order_detail.
Không đổi âm thầm trong phase parity.

Tạo Money value object với VND integer semantics ở domain layer.
Adapter đọc legacy DOUBLE và normalize/validate.

Sau khi commerce thuộc Laravel hoàn toàn, migration riêng chuyển sang DECIMAL(...,0) hoặc integer VND sau audit dữ liệu và integration.

## 4.6. Foreign keys

Không thêm hàng loạt FK trước khi chạy orphan audit.

Thứ tự:
1. inventory relationship logic;
2. orphan report;
3. fix/confirm orphan policy;
4. unique/index constraints;
5. FK theo domain đã cutover;
6. ON DELETE/UPDATE rule phải được quyết định rõ.

---

# 5. DOMAIN MAP VÀ SOURCE OF TRUTH

## 5.1. Catalog

Nguồn legacy chính:
- administrator/sources/product.php (~3781 dòng);
- administrator/sources/product_bulk.php (~1717 dòng);
- product config trong libraries/type/;
- libraries/product_*;
- api/pts_v1_catalog.php;
- api/pts_v1_product_detail.php;
- api/pts_v1_product_extras.php;
- api/pts_v1_brand_detail.php;
- api/pts_v1_search.php;
- tables product/list/cat/brand/gallery/document/project/campaign/content_section/seo.

Target services:
- CatalogQueryService
- ProductDetailService
- ProductWriteService
- ProductPricingService
- ProductSpecificationService
- ProductContentResolver
- ProductMediaService
- ProductDocumentService
- ProductSeoService
- CategoryTreeService
- BrandService

## 5.2. Content

Nguồn:
- administrator/sources/news.php (~2217 dòng);
- static.php, photo.php, gallery.php;
- libraries/type/config-type-news.php;
- config-type-static.php, config-type-photo.php;
- public home/about/news/solution APIs.

Target:
- ContentQueryService
- ContentWriteService
- ArticleService
- SolutionService
- PageBlockService
- SiteSettingsService
- ContentRevisionService
- PublishService

## 5.3. Projects

Hiện project dùng table_news type du-an + project-specific columns + gallery/product links.

Target:
- ProjectService
- ProjectGalleryService
- ProjectProductLinkService
- ProjectSeoService

Không ép project sang bảng mới trước parity.

## 5.4. SEO/Routes

Target domain riêng:
- SeoService
- CanonicalService
- RedirectService
- RouteResolver
- StructuredDataFactory
- SitemapService

Phải giữ URL/slug/canonical/redirect/robots/JSON-LD/breadcrumb.
SEO parity là release gate độc lập, không gộp chung UI test.

## 5.5. Media

Giai đoạn đầu giữ đường dẫn vật lý và URL legacy.

Tạo Storage disk/adapters:
- legacy_upload
- legacy_thumbs

Tạo metadata mới chỉ khi cần:
MediaAsset gồm path, mime, width, height, size/hash, alt/caption, usage references, created_by.

Không di chuyển mass file trong phase backend parity.

## 5.6. Leads/Contact/Dealer

Nguồn:
- table_contact;
- form contact/dealer/lead APIs;
- email logic hiện có.

Target:
- LeadService
- ContactRequestService
- DealerApplicationService
- LeadNotificationService
- Spam/RateLimit policy

table_contact hiện có fields fullname/email/phone/file/subject/content/notes/address/status/timestamps.

## 5.7. Identity/RBAC

Legacy table_user:
- id_permission
- username/password
- login_session
- user_token
- status
- role
- secret_key
- profile fields

table_permission:
- id_permission_group
- permission

table_permission_group:
- name
- status
- timestamps

Target:
- AdminUser
- Role/Permission adapter
- LegacyPasswordVerifier
- Laravel Policies/Gates
- LoginRateLimiter
- Session/Auth service

Không reset password hàng loạt.
Hash legacy MD5 chỉ được verify trong compatibility branch; đăng nhập thành công phải rehash sang password hash hiện đại.
Existing password_hash account phải tiếp tục hoạt động.

## 5.8. Commerce

Legacy table_order có:
- unique checkout_token;
- payment method/ref/status;
- carrier code/order/status;
- temp/total/ship price DOUBLE;
- city/district/ward;
- requirements/notes/status.

Business rule hiện hành:
đơn mới chỉ dùng Tỉnh/TP -> Phường/Xã; district = 0.

Target:
- CartService
- CheckoutService
- CreateOrderAction
- IdempotencyService
- ShippingQuoteService
- PaymentService
- VNPayGateway
- CarrierGateway interfaces
- OrderStateService

Commerce chuyển cuối vì session, money, transaction, idempotency, gateway signature và state cùng tồn tại.

---

# 6. API MIGRATION — CONTRACT FIRST

## 6.1. Không thiết kế API mới trong phase 1

Nuxt có các frontend/shared/contracts/public-*.ts và validator chặt.
Laravel v1 phải trả cùng schemaVersion: 1 và cùng semantics.

Nhóm read API cần parity:
- site
- home
- catalog
- brand-detail
- product-detail
- product-extras
- search
- solutions
- solution-detail
- projects
- project-detail
- news
- news-detail
- policy-detail
- about
- dealer
- contact
- gallery
- gallery-detail
- video
- public-route-resolve
- checkout-notice
- checkout-wards
- order-thanks read

Session/write paths xử lý riêng.

## 6.2. Trạng thái write bridge hiện tại đáng lưu ý

- cart.post.ts đang forward PHP session mutation.
- shipping-quote.post.ts đang forward PHP.
- checkout.post.ts trong migration preview cố ý trả 410.
- lead-form.post.ts cố ý không nhận POST trong bridge đó.
- review.post.ts trả 410.

Kết luận:
read migration có thể hoàn thành/cutover trước writes mà không cần giả định Nuxt hiện đã sở hữu mọi mutation.

## 6.3. Golden master tests

Mỗi endpoint read phải có fixture matrix:

input/query
-> legacy HTTP status
-> legacy normalized JSON
-> Laravel HTTP status
-> Laravel normalized JSON
-> semantic diff

Normalize chỉ các field nondeterministic được khai báo trước.
Không xóa field khác khỏi diff vì khó match.

## 6.4. Shadow parity

Cho GET/read-only có thể dùng:

Nuxt authoritative = legacy PHP
Nuxt shadow = Laravel

Laravel response không trả cho user, chỉ compare/log metric.
Tuyệt đối không shadow mutation POST/PUT/DELETE.

Diff cần report theo JSON path, ví dụ:
- product.pricing.visibility
- content.blocks[3]
- seo.canonical.url
- documents[1].url

---

# 7. FILAMENT ADMIN MIGRATION

## 7.1. Coexistence path

Trong migration:
- /administrator -> legacy PHP
- /admin-next -> Filament staging/internal

Sau Admin parity:
- /administrator -> Laravel/Filament
- legacy admin -> rollback-only/private route trong thời gian ngắn

## 7.2. Product Studio đầu tiên

Resource không được là form 100 field một cột.
Tách tabs/sections:

1. Tổng quan
2. Taxonomy/Brand
3. Media
4. Giá & trạng thái bán
5. Thông số kỹ thuật theo pdp_profile/loại
6. Nội dung PDP + inheritance status
7. Applications/highlights
8. Documents
9. SEO
10. Related projects/campaign
11. Audit/history

Danh sách phải có search, filter, configurable columns, bulk actions có permission và confirmation.
Không hiển thị technical ID làm primary label.

## 7.3. Content Studio

Không đưa mọi content type vào một màn hình chung khó hiểu.

Map theo nghiệp vụ:
- Tin/Kiến thức
- Giải pháp
- Dự án
- Chính sách
- Home blocks
- About blocks
- Dealer blocks
- Global settings/navigation/footer

config-type-* legacy là nguồn inventory field/permission/validation, không phải architecture đích.

## 7.4. Visual Preview

Preview phải render bằng Nuxt thật, không dựng lại HTML trong Filament.

Đề xuất:
1. Admin lưu draft/revision.
2. Laravel phát signed preview token ngắn hạn.
3. Nuxt preview route fetch draft bằng token.
4. Filament nhúng preview cùng origin hoặc iframe có CSP/frame policy rõ.
5. Publish atomically đổi active revision rồi invalidates cache.

---

# 8. TEST VÀ QA TRƯỚC KHI MIGRATION CHẠY NHANH

## 8.1. Sửa test harness trước

Deploy 07/11 fail vì predeploy cần pts-nuxt-service-rehearsal nhưng container chưa chạy.
Hiện container đã healthy, nhưng pipeline không nên phụ thuộc việc con người nhớ bật.

Task nền tảng:

predeploy detects rehearsal
-> start/reuse compose service
-> healthcheck
-> run integration tests
-> retain/reuse or cleanup deterministically

Nếu không làm, nhiều agent sẽ tạo false-fail và giảm tốc độ thay vì tăng tốc độ.

## 8.2. Test pyramid mới

Unit:
- Value Objects
- parsers
- content resolver
- price logic

Integration:
- Laravel + clone DB
- model/query/service

Contract:
- PHP vs Laravel DTO
- Nuxt TS validators

Feature:
- auth/policies/admin CRUD

Browser:
- Nuxt public journey
- Filament admin journey

Security:
- auth
- CSRF
- upload
- RBAC
- rate limit
- direct API authorization

Performance:
- p50/p95/p99
- query count
- DB time
- memory

## 8.3. QA agent độc lập

Agent viết implementation không được là agent duy nhất chứng nhận Definition of Done.
QA lane phải đọc legacy behavior và viết adversarial cases riêng.

---

# 9. BRANCH / WORKTREE / OWNERSHIP

## 9.1. Integration branch

Khi bắt đầu thật:

base: a8632eed
hoặc SHA mới được owner chốt tại thời điểm start

branch:
migration/laravel13-integration

Không code trực tiếp trên master.

## 9.2. Lanes

- migration/laravel13-foundation
- migration/laravel13-db-compat
- migration/laravel13-catalog
- migration/laravel13-content
- migration/laravel13-admin
- migration/laravel13-security
- migration/laravel13-commerce
- migration/laravel13-qa

Mỗi lane có worktree riêng.

## 9.3. Single-owner files

| Area | Owner |
| --- | --- |
| backend/database/migrations | DB owner |
| shared API contracts | Architect/contract owner |
| base domain abstractions | Architect |
| auth/policies | Security |
| Product/Catalog | Catalog |
| Content/CMS | Content |
| Filament shell/theme | Admin |
| Checkout/payment | Commerce |
| deploy/Caddy | DevOps/integrator |

Không merge agent output trực tiếp vào master.
Merge vào integration branch sau gate của module.

---

# 10. PHASE TRIỂN KHAI CHI TIẾT

## Phase 0 — Freeze & Inventory

Mục tiêu:
không để agents code trên baseline đang trôi.

Deliverables:
- Git SHA baseline + diff status;
- worktree inventory;
- schema dump/fingerprint;
- DB clone riêng cho migration;
- API route/contract inventory;
- legacy admin capability matrix;
- source-file-to-domain map;
- current test baseline;
- production/source divergence note;
- media path inventory;
- secrets excluded.

Gate P0:
tất cả artifacts có timestamp và SHA; no production mutation.

## Phase 1 — Laravel Foundation

Tạo backend/ trong cùng monorepo.

Target:
- Laravel 13;
- PHP 8.4 container;
- Composer lock;
- health endpoint;
- .env.example không secret;
- MariaDB clone connection;
- Pest/PHPUnit;
- Pint + Larastan/PHPStan;
- Filament 5 boot ở /admin-next local;
- Laravel Boost dev-only;
- basic log/error handling;
- Docker compose overlay/service không phá stack cũ.

Không thêm Redis/Octane ở phase này.

Gate P1:
- app boot;
- health 200;
- DB read-only query thành công;
- Filament login skeleton local;
- test/static analysis chạy ổn;
- legacy PHP/Nuxt stack không bị thay.

## Phase 2 — Legacy Compatibility Layer

Tạo Eloquent models/adapters cho nhóm bảng cần trước, không phải 59 model vô nghĩa một lượt.

Priority models:
- Product/List/Category/Brand
- Gallery/Photo/Documents
- ContentSection/Static/News
- Seo/Redirect/Settings
- User/Permission/PermissionGroup
- Contact
- Order/OrderDetail/PaymentTransaction

Tạo value objects/casts/scopes:
- StatusFlags
- LocalizedText
- LegacyTimestamp
- Money
- MediaPath
- ContentBlock parser

Gate P2:
Laravel đọc cùng record/relationship logic như legacy trên fixture clone, không đổi schema.

## Phase 3 — Product Vertical Slice

Đây là reference implementation bắt buộc trước khi fan-out quá mạnh.

Phạm vi end-to-end:
DB -> Models/Queries -> Domain Services -> API Resource -> Nuxt contract
                                     -> Filament Product Studio

Phải bao phủ:
- category tree;
- brand;
- media fallback;
- pricing/quote visibility;
- specs/decision metrics;
- content inheritance;
- applications/highlights;
- documents;
- canonical/SEO;
- related project/campaign;
- Admin edit + permission;
- cache behavior.

Gate P3:
golden parity với PHP trên representative fixtures + Product Admin CRUD trên clone DB + Nuxt renders không đổi contract.

## Phase 4 — Parallel Read API Migration

Sau P3, chia agents:

Lane A:
Site/Home/About/Dealer/Contact

Lane B:
Catalog/Brand/Search

Lane C:
Solutions

Lane D:
Projects

Lane E:
News/Policy/Gallery/Video/RouteResolver

Mỗi lane phải dùng common DTO conventions từ Product reference implementation.
Không tự invent architecture khác.

Gate P4:
toàn bộ read contracts pass TS validators và semantic diff.

## Phase 5 — Filament Admin Parity

Chuyển các capability đang dùng thật:
- products + bulk;
- categories/brands;
- content/news/static;
- projects/solutions;
- media/gallery/photo;
- SEO;
- settings/navigation;
- users/RBAC;
- contacts/leads;
- integrations;
- orders khi commerce tới phase tương ứng;
- import/export cần migrate package/flow hiện đại, không copy PHPExcel.

Không cần port các màn đã được audit là dead/hidden nếu không còn consumer.

Gate P5:
Admin capability matrix có trạng thái PARITY, REPLACED BETTER, hoặc INTENTIONALLY RETIRED với evidence.

## Phase 6 — PTS Visual Studio

Tạo structured block schemas thay vì raw HTML page builder.

Cần:
- Page/Section definitions;
- draft revision storage;
- preview token;
- Nuxt preview;
- publish transaction;
- audit trail;
- rollback;
- layout constraints;
- media references;
- cache invalidation.

Gate P6:
editor thay đổi draft không ảnh hưởng public; preview chính xác; publish/rollback deterministic.

## Phase 7 — Auth/RBAC Cutover

- map legacy users/permissions;
- verifier cho hash legacy;
- rehash on successful login;
- Laravel Policies/Gates;
- session/CSRF/rate-limit;
- file upload permission;
- read-only vs edit roles;
- direct endpoint tests.

Gate P7:
permission matrix từ legacy được replay và denial tests pass.

## Phase 8 — Low-risk Writes

Thứ tự:
1. Contact
2. Dealer/Lead
3. newsletter/review nếu còn active
4. Admin CRUD domain đã parity

Mỗi domain chỉ chuyển writer một lần.
Có feature flag/route switch và rollback.

## Phase 9 — Cart/Shipping

- Laravel session/cart representation;
- cart mutation contract;
- CSRF;
- quantity validation;
- product availability/quote-only rules;
- local shipping quote;
- address rule Province/City -> Ward only;
- optional adapter nếu cần preserve existing PHP cart session.

Gate P9:
identical cart totals/state transitions trên fixtures, no dual-write.

## Phase 10 — Checkout/Payment

Phải preserve:
- transaction boundary;
- unique checkout idempotency token;
- order + detail atomicity;
- shipping fields;
- payment transaction log;
- VNPay signature verification;
- callback idempotency;
- status transitions;
- error rollback.

Không simplify logic chỉ vì local order count hiện 0.

Gate P10:
integration tests + failure injection + duplicate callback tests + DB integrity.

## Phase 11 — Schema Modernization

Chỉ sau khi Laravel là writer duy nhất của domain tương ứng.

Priority:
1. orphan audit
2. unique constraints
3. FKs
4. money types
5. timestamp normalization
6. status flags
7. media relations
8. taxonomy/content split nếu có lợi ích đo được

Mỗi migration có preflight, backup requirement, rollback/forward-fix plan.

## Phase 12 — Performance/Observability

Đo trên cùng machine/DB:
- p50/p95/p99
- query count
- DB time
- memory/request
- CPU
- response bytes
- cache hit/miss
- 5xx/error rate

Legacy DTO local từng rất nhanh.
Laravel không được tuyên bố nhanh hơn nếu chưa benchmark cùng điều kiện.

Nếu cần mới đánh giá Redis/Horizon/Octane/FrankenPHP.

## Phase 13 — Shadow/Staging

Read-only GET:
legacy authoritative
laravel shadow

Sau parity cao:
laravel authoritative on staging
legacy fallback

Chạy Nuxt Playwright, SEO, admin, security, performance và backup/restore rehearsal.

## Phase 14 — Production Cutover theo domain

Không big-bang.

Đề xuất:
Wave 1: site/home/catalog/product reads
Wave 2: content/solutions/projects/news
Wave 3: Admin writer
Wave 4: leads/forms
Wave 5: cart/shipping
Wave 6: checkout/payment

Mỗi wave có owner, metrics, rollback switch, log scan và thời gian quan sát.

---

# 11. DEFINITION OF DONE THEO DOMAIN

## Product/Catalog

Không được gọi done chỉ vì endpoint 200.

Phải đúng:
- product identity;
- taxonomy hierarchy;
- brand;
- sort/filter/search;
- media fallback;
- pricing/quote policy;
- specs;
- content inheritance;
- documents;
- applications/highlights;
- SEO/canonical;
- Admin editing;
- RBAC;
- cache invalidation;
- Nuxt rendering.

## Content

- type mapping;
- visibility/order;
- slug/canonical;
- richtext sanitation;
- linked media;
- related products/projects;
- draft/publish;
- version history;
- Admin role permissions;
- public parity.

## Identity

- existing accounts login;
- legacy hash migration;
- active/inactive rules;
- concurrent/session behavior as approved;
- permission matrix;
- direct URL/API denial;
- CSRF/rate limit;
- audit log.

## Commerce

- calculation;
- transaction;
- idempotency;
- validation;
- order snapshots;
- shipping;
- payment signature;
- callback retries;
- failure rollback;
- Admin visibility.

---

# 12. PERFORMANCE VÀ CACHE STRATEGY

Giai đoạn đầu để Nuxt SWR/read cache tiếp tục giữ vai trò chính như hiện tại.
Laravel không thêm cache tầng hai phức tạp ngay để tránh double-stale.

Sau khi CMS publish hoàn chỉnh:

publish event
-> Laravel domain event
-> invalidate backend cache nếu có
-> Nuxt revalidation/purge mechanism

Không cache:
- session/cart;
- checkout;
- preview drafts;
- free-text search với key không bounded nếu chưa thiết kế;
- authenticated Admin responses.

---

# 13. DEPLOYMENT TARGET

Trong coexistence, thêm service Laravel nhưng không thay PHP ngay.

Services:
- proxy
- nuxt
- legacy-web
- laravel-next
- db

Nuxt gọi Laravel qua internal Docker network cho các route đã chuyển.
Không cần public expose toàn bộ Laravel API trong phase đầu.

Final target:
Caddy -> Nuxt public
Caddy -> Laravel /administrator và API/webhook cần public
Nuxt -> Laravel internal API
Laravel -> MariaDB

Production HTTP runtime của Laravel như Apache/FPM/FrankenPHP phải được quyết định sau local/staging benchmark.
Không thay architecture chỉ để theo trend.

---

# 14. RISK REGISTER

| Risk | Impact | Control |
| --- | --- | --- |
| Source baseline tiếp tục thay đổi | agents lệch contract | freeze SHA + controlled sync checkpoints |
| Production chưa cùng HEAD | parity nhầm | tách source vs runtime baseline |
| status CSV | query/visibility sai | StatusFlags + tests |
| content inheritance | PDP nội dung âm thầm sai | dedicated resolver + fixtures |
| gần như không FK | orphan/integrity | audit trước constraints |
| DOUBLE money | rounding | Money value object, migrate type sau |
| legacy password | lockout Admin | verifier + rehash on login |
| media path legacy | broken SEO/images | storage adapter, no mass move |
| PHPExcel/old libs | PHP 8.4 incompatibility | do not port; replace flow |
| tests phụ thuộc rehearsal service | false fail | automate service lifecycle |
| agents sửa chung migration/contracts | merge/rework | single ownership |
| self-authored tests | false confidence | independent QA lane |
| dual-write | data divergence | one writer/domain |
| SEO regression | organic traffic loss | dedicated SEO parity gate |
| payment simplification | financial error | late phase + failure injection |

---

# 15. AGENT ORCHESTRATION ĐỂ NHANH MÀ KHÔNG ẨU

## Architect/Integrator

Không làm feature hàng ngày.
Chịu trách nhiệm:
- baseline;
- dependency graph;
- domain boundaries;
- API conventions;
- merge order;
- ADRs;
- gate decisions;
- production release.

## DB Compatibility Agent

- schema inventory;
- legacy models/casts;
- fixture clone;
- orphan reports;
- migration ownership.

## Catalog Agent

- product/catalog/search/brand;
- Product Studio;
- product parity.

## Content/CMS Agent

- news/solutions/projects/static blocks;
- Visual Studio;
- revision/publish.

## Security Agent

- auth/password/RBAC/session/upload security.

## Commerce Agent

- cart/shipping/checkout/payment only after prerequisites.

## QA Agent

- golden master;
- adversarial tests;
- Playwright;
- performance/security;
- không sửa implementation chỉ để làm test pass nếu chưa review nguyên nhân.

---

# 16. TIẾN ĐỘ MỤC TIÊU KHI DÙNG NHIỀU AGENT MẠNH

Đây là target planning, không phải cam kết bỏ gate.

| Mốc | Target |
| --- | --- |
| Ngày 1 | Freeze baseline, DB clone, Laravel/Filament/Boost boot |
| Ngày 2–3 | Compatibility layer + Product vertical slice |
| Ngày 3–5 | Parallel public GET APIs + Product Admin |
| Ngày 4–7 | Content/Projects/SEO/Admin parity |
| Ngày 5–9 | Visual Studio + Auth/RBAC + low-risk writes |
| Ngày 7–12 | Cart/shipping/commerce + full staging QA |
| Ngày 10–14 | Production candidate nếu tất cả gates đạt |

Không kéo dài chỉ vì estimate cũ.
Nhưng cũng không ép cutover theo ngày nếu parity/security/write correctness chưa đạt.

---

# 17. FIRST EXECUTION CHECKLIST CHO CLAUDE

Khi owner ra lệnh “bắt đầu migration”, Claude phải làm theo thứ tự:

1. Re-read AGENTS.md + tài liệu này.
2. Xác minh git status, HEAD, origin/master, worktrees.
3. Nếu HEAD khác baseline ghi trong tài liệu, tạo delta report trước khi code.
4. Xác minh rehearsal container/test harness.
5. Chụp schema fingerprint và direct counts.
6. Tạo DB clone dedicated cho Laravel migration.
7. Tạo integration branch/worktree từ SHA được owner chốt.
8. Tạo backend/ Laravel 13 + PHP 8.4.
9. Cài Filament 5 và Laravel Boost dev-only.
10. Chạy test/bootstrap gate.
11. Không làm hàng chục modules ngay: hoàn thiện Product vertical slice trước.
12. Sau Product P3 pass mới fan-out các lane đọc song song.
13. Không deploy production cho tới staging/cutover gate.
14. Mỗi milestone báo: commit, files, tests, DB effect, production effect, rollback point, known gaps.

---

# 18. QUYẾT ĐỊNH ĐÃ KHÓA

- Frontend: Nuxt 4 + Vue 3 + Nuxt UI/Tailwind hiện tại.
- Backend: Laravel 13.
- Runtime target: PHP 8.4 trong migration; 8.5 đánh giá sau.
- Admin: Filament 5.
- CMS: PTS Visual Studio xây trên Filament, Nuxt live preview.
- Database: MariaDB/InnoDB giữ nguyên trong đợt này.
- Architecture: modular monolith.
- API: giữ schema v1 để parity; v2 chỉ sau migration.
- Media: giữ path/file hiện tại qua adapter trước.
- Cache: giữ đơn giản, tránh double cache.
- Redis/Horizon/Octane: deferred until evidence.
- NestJS/PostgreSQL/microservices: out of scope.
- Migration strategy: strangler/domain cutover, one writer per domain, shadow GET only.
- Primary speed strategy: contract-first + parallel agents + independent QA.

---

# 19. DEFINITION OF SUCCESS TOÀN DỰ ÁN

Migration chỉ hoàn thành khi:

1. Nuxt public không còn phụ thuộc business logic legacy PHP.
2. Laravel phục vụ đầy đủ contract public cần thiết.
3. Filament/PTS Studio thay thế các capability Admin đang dùng thật.
4. Auth/RBAC không giảm mức bảo vệ.
5. Media/SEO URL không mất.
6. Contact/leads/commerce quan trọng có transaction/idempotency đúng.
7. Không có dual writer.
8. Database integrity được đo và constraints thêm có kiểm soát.
9. Test suites Nuxt + Laravel + Admin + Playwright + security pass.
10. Benchmark không cho thấy regression không chấp nhận được.
11. Backup/restore và rollback rehearsal thành công.
12. Production cutover có metrics/logs và rollback switch rõ.
13. Legacy PHP chỉ bị retire sau khi domain cuối cùng đạt parity và thời gian quan sát ổn định.

---

# 20. GHI CHÚ CHO CLAUDE VỀ TỐC ĐỘ

Chủ project muốn triển khai nhanh và sẵn sàng dùng agent mạnh chạy liên tục.
Điều đó cho phép parallelize implementation rất mạnh, nhưng không được lấy tốc độ sinh code làm thước đo hoàn thành.

Tối ưu đúng:
phân tích sâu một lần
-> giảm ambiguity
-> khóa contract
-> ownership rõ
-> nhiều agent song song
-> tự động parity
-> merge theo gate
-> cutover theo domain

Không tối ưu bằng:
bỏ test
-> copy nguyên legacy
-> rewrite schema cùng lúc
-> merge tất cả agents
-> big-bang production

Khi có lựa chọn giữa “viết thêm code ngay” và “làm rõ một ambiguity có thể gây rework nhiều module”, ưu tiên làm rõ ambiguity trước.

Đây chính là cách chuyển nhanh nhất ở quy mô này.

---

# 21. NGUỒN TÀI LIỆU HIỆN HÀNH

Laravel 13:
https://laravel.com/framework/docs

Laravel deployment requirements:
https://laravel.com/framework/docs/deployment

Laravel Boost:
https://laravel.com/framework/docs/boost

Filament 5 installation:
https://filamentphp.com/docs/5.x/introduction/installation

Filament 5 forms:
https://filamentphp.com/docs/5.x/forms/overview

Lưu ý:
Claude nên dùng Laravel Boost Search Docs theo versions thực tế trong composer.lock thay vì dựa vào memory về API framework/package.

---

# 22. KẾT LUẬN KIẾN TRÚC

Migration không phải project “viết lại website”.
Nuxt mới giữ nguyên vai trò public frontend.

Nhiệm vụ là thay lớp:
Nuxt -> PHP legacy business/backend

bằng:
Nuxt -> Laravel 13 business/backend

và thay:
Legacy PHP Admin

bằng:
Filament 5 + PTS Visual Studio.

Legacy PHP phải được xem là executable specification cho đến khi từng domain được chứng minh parity.

Mục tiêu cuối:
một backend Laravel modular, có test, contract rõ, Admin trực quan, database có integrity tốt hơn và tốc độ phát triển lâu dài cao hơn — trong khi URL, SEO, data, nghiệp vụ và trải nghiệm Nuxt hiện tại được bảo toàn.
