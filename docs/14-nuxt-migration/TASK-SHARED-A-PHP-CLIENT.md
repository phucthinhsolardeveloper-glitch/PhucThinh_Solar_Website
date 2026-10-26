# CODEX A — S1 + S2 | Nitro PHP Client + DTO primitives
Base branch: feature/pts-nuxt4-migration. Không dùng worktree đang có của người dùng: pts-nuxt4-parity-qa.
Đọc AGENTS.md, docs/14-nuxt-migration/SHARED_FIRST_EXECUTION_PLAN_20261026.md, AGENT_EXECUTION.md. Trước khi viết code: kiểm tra Codex QA có đang sửa cùng file/tiểu mục không; nếu có, không chỉnh phần trùng và báo reviewer.
Mục tiêu: bỏ code lặp trên 13 Nitro adapters với một GET-only PHP client; chuẩn hóa public DTO validators. Thí điểm đúng ba endpoint SITE, ABOUT, PROJECTS, GIỮ NGUYÊN response.
Ownership ONLY:
- NEW frontend/server/utils/php-public-client.ts
- NEW frontend/shared/contracts/core.ts
- NEW frontend/tests/shared-data-*.test.mjs
- UPDATE frontend/server/routes/_migration/site.get.ts, about.get.ts, projects.get.ts
- (optional, scoped) DTO isPublicSitePayload/isPublicAboutPayload/isPublicProjectsPayload validators; không thay shape/schemaVersion.
Cấm sửa PHP, app/pages, components, CSS, Nuxt config, Caddy, QA tests đã giao Codex khác, master/prod.
Trước code: so sánh 3 adapter hiện tại, kiểm chứng private runtimeConfig.phpOrigin, query whitelist và no cookie forwarding, status 400/404/405/502, no-store. Không cho truyền arbitrary URLs. Reuse shared primitives không làm rộng whitelist.
Tests: unit malicious query/path/header, timeout, invalid schema, upstream error/404, hidden/unpublished; direct GET local PHP 8088 fixture; npm test, npm run typecheck; coordinator thực hiện 1 build chung sau merge. Không npm ci/Chromium khi Codex QA đang chạy nặng.
Acceptance: output JSON byte/semantics tương đương trước, không thay API/data; 3 routes dùng shared client, test pass, file list/discrepancy/rollback doc. Commit nhỏ. KHÔNG merge, push, production deploy; giao SHA cho reviewer.