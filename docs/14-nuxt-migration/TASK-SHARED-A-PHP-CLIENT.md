# CODEX A — S2 | Shared DTO validators, NO NITRO EDITS
Base branch: feature/pts-nuxt4-migration. Worktree: /home/admin01/src/pts-nuxt-shared-data-20261026.
Quan trọng: Codex người dùng đang chạy trên /home/admin01/src/pts-nuxt4-parity-qa hiện ĐANG SỬA TOÀN BỘ 13 Nitro adapters và tạo frontend/server/utils/migration-api.ts. KHÔNG sửa hoặc tạo frontend/server/**, KHÔNG thay đổi tập tin của Codex kia!
Read AGENTS.md, docs/14-nuxt-migration/SHARED_FIRST_EXECUTION_PLAN_20261026.md, ROUTE_AND_WORKSTREAM_MATRIX.md. So sánh DTO validators public-about.ts, public-projects.ts, public-contact.ts trước khi code.
Mục tiêu: tái sử dụng URL/media/text/record/slug/pagination validation mà giữ nguyên dữ liệu PHP, DTO schemaVersion=1 và các phép kiểm hiện có.

FILE OWNER ONLY
- NEW frontend/shared/contracts/core.ts
- NEW frontend/tests/shared-data-*.test.mjs
- UPDATE frontend/shared/contracts/public-about.ts, public-projects.ts, public-contact.ts ONLY.
- Optional scoped docs under docs/14-nuxt-migration/TASK-SHARED-A-DTO.md.

Không sửa frontend/server/**, PHP, components/pages/layout, frontend/test migration-parity, tools/visual-tests, Nuxt config, existing API response, CMS/DB, master, production.
Xây common primitives có semantics chặt (relative public URL, slug safe, image allowlist) KHÔNG nới whitelist. Migrating exactly 3 validators to new primitives. Nếu một validator có luật đặc thù thì giữ nguyên ở validator đó.
Tests: malicious URLs (javascript, data, //, .., Unicode control, svg), absent/hidden/empty CMS variants, invalid numeric paging, official sample payloads local. Node tests + typecheck; compare data real GET PHP 8088 without DB writes. Hãy để coordinator serialize build/Chromium.
GATE: PASS regression cho 3 validators, không thay đổi shape/field names, no new accepted dangerous URL, no extraneous dependencies. Commit nhỏ, báo file/test SHA, KHÔNG merge/push/deploy.