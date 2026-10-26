# Codex task — PTS read-only solutions API (26 October 2026)

Scope: Phase A **exact PHP-to-Nuxt data migration**, backend-only, no redesign, no deployment. Branch `feature/pts-nuxt-api-solutions-20261026`. Base integration commit `764cb41`.

## Read first
- `AGENTS.md`
- `docs/14-nuxt-migration/{AGENT_EXECUTION,BASELINE,ROUTE_AND_WORKSTREAM_MATRIX}.md`
- `domains/phucthinhsolar.com/public_html/sources/solution_hub.php`
- `domains/phucthinhsolar.com/public_html/libraries/solution_hub.php`
- `domains/phucthinhsolar.com/public_html/templates/news/service_list_tpl.php`
- `domains/phucthinhsolar.com/public_html/api/config.php`
- existing `api/pts_v1_site.php` and `libraries/pts_public_site_api.php`.

## Deliverable
Implement a **read-only JSON endpoint for the published `/giai-phap/` page** following the established `pts_v1_site` pattern:
- PHP endpoint e.g. `api/pts_v1_solutions.php`, HTTP GET only, predictable error status, no raw secrets/HTML form mutations.
- PHP projection helper in `libraries/pts_public_solutions_api.php`, unit-testable as pure function (no DB requirement for DTO test).
- Preserve source's existing `solutionHubSelect` publish/featured selection rules, priority of `solution-intro`, `solution-cta`, cards, equipment, approved uploaded asset paths/fallback, and `vi` locale. Do not create standalone alternate business rules, and treat hidden admin content as hidden. Do not invent product/data.
- DTO should provide enough **structured** and safe public data for Nuxt to render the current solution landing (title, intro, cards, equipment, CTA, links, image paths); no irrelevant private fields. For editorial HTML: **never pass unsanitized arbitrary markup**; either sanitize with existing allowed logic and document, or explicitly keep editorial rich-text support deferred until a safe sanitizer gate.
- Respect canonical paths for published solutions, preserve `/giai-phap/{slug}/`.
- Tests under `scripts/`, cover visibility, data projection, omitted private fields, HTTP method check if feasible. Use PHP CLI lint/test via existing PHP Docker image or native php. If no runtime, at least php -l and report limitation honestly.
- Document contract schema/edge cases in `docs/14-nuxt-migration/`.

## Boundaries
- Only edit: `domains/phucthinhsolar.com/public_html/api/pts_v1_solutions.php`; new helper `libraries/pts_public_solutions_api.php`, new tests under `scripts/test-pts-public-solutions-*.php`, and scoped docs task/contract files.
- Do NOT edit existing PHP source/template/router, frontend/, Caddy, Docker, DB, master or another worktree.
- No shell commands that touch production. No git push/merge/worktree delete. Keep change small and independently tested.
- Commit on this feature branch **only if tests pass**. End report in Vietnamese with commit SHA, tests, limitations and next dependency.

Definition of done: valid PHP code, safe DTO, published selection parity, deterministic unit tests, documented schema and clean Git status after commit.
