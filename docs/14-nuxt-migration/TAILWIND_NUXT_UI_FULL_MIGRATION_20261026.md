# PTS Nuxt 4 — DECISION: full Tailwind v4 + Nuxt UI v4 frontend conversion
Decision date 2026-10-26. Status: APPROVED DESIGN DIRECTION BY OWNER; no code/dependencies installed by this planning document.

## Supersedes previous UI recommendation
This document supersedes the optional Tailwind spike / do-not-mass-convert CSS direction in FAST_TRACK_UI_ARCHITECTURE_20261026.md and the CSS-only migration preference in older reports. Owner explicitly wants to convert the Nuxt frontend to Tailwind v4 and use Nuxt UI v4 throughout the project rather than keeping the old page-preview CSS stack long term. PHP production stays unchanged.
Do not confuse this with converting original PHP templates' CSS in the live legacy website; PHP remains operational until staged public cutover. All Nuxt preview page styling IS in scope for conversion.

## Target technology (single stack)
- Nuxt 4 (SSR) + Vue 3 + TypeScript.
- Tailwind CSS v4 for global theme tokens, layout, responsive, typography, spacing, utility styles, transitions.
- Nuxt UI v4 for interactive and accessible controls: UButton, UInput, USelect/SelectMenu, UDropdownMenu, UModal, UDrawer, UTabs, UAccordion, UBadge, UTooltip etc where semantics fit. Use Nuxt UI app provider in app.vue when needed for overlays, appropriate Nuxt UI config in Nuxt config and app.config.ts.
- Native Vue components for custom page content, hero, galleries, complex merchandising, project case studies and Apple-like but subtle motion. No obligation to render every DOM element with a Nuxt UI component.
- A single theme: company navy + gold, slight 4–8 px corners, restrained surfaces, structured typography, strong focus visibility, reduced motion, predictable mobile touch. Theme source from approved PTS branding and real PHP/site design, not fabricated values.
- main.css remains for @import "tailwindcss"; @import "@nuxt/ui"; @theme and essential global rules. Small intentional CSS for exceptional keyframes/third-party rich text is permitted, but no retained *page-preview legacy stylesheet dependency* or layered override-patches after conversion. Remove dead CSS and unused packages after parity checks.
- Follow current @nuxt/ui v4 documentation. Nuxt UI already includes Tailwind integration; avoid installing incompatible legacy @nuxtjs/tailwindcss or duplicating competing CSS pipelines. Verify actual installed versions with package lock and build. Use documented Nuxt UI 4 components, not v3 API guesses.
Official docs: https://ui.nuxt.com/ and https://tailwindcss.com/docs/installation/using-vite

## Important constraints
- **Do not force all routes into one page layout.** Globally share header, footer, menu, brand theme & necessary composables; page-specific hero, breadcrumb position, section arrangement and micro-interactions remain separately authored.
- User explicitly authorizes redesign/improvement while switching to Nuxt/Tailwind, but still maintain approved navy/gold and company function: no arbitrary structural data loss or UI-template homogenization.
- Backend PHP/MariaDB are authoritative for CMS, product status, category/filter/search/sort/pricing/discounts, cart/session/checkout, protected forms/CSRF and admin. UI tools must NOT invent data/business logic.
- Phase goal is **complete frontend conversion**, not accumulation of 16 noindex pages. Need working end-to-end GET/SSR/public route UX on local hybrid before production.
- Secure CMS CKEditor HTML: integrate reviewed sanitizer commit b856784 separately, never pass raw CMS HTML into Vue.
- All changes in dedicated Git worktrees. No production push/deploy, master or Stitch edits without separate approval.
- QA: visual screenshots/DOM/interactions on 390/768/1440 for reference PHP vs Nuxt; allowed redesign deviations documented and accepted. SSR noindex preview, hydration, zero console/pageerror/overflow, 404/301/canonical and all links checked.
- WSL RAM allocation ~7.8 GiB, swap often full: maximum 2 coding Codex concurrently; ONE build or Chromium session at a time; avoid simultaneous npm install/build and Docker.

## Execution sequence (do not create worktree-per-page)
### STEP 0 — Freeze existing visual work in progress
Active child: /home/admin01/src/pts-nuxt-visual-sprint-20261026. Has uncommitted Home/About *.vue and old-CSS visual parity overrides. This agent must finish or checkpoint its files safely; then STOP adding new legacy CSS. Do not discard its useful screenshot/layout/CMS findings. Base subsequent migration branches on a clean reviewed integration commit. The child CSS can be *reference only* and removed once Tailwind version passes visuals. PHP/Nuxt local 8080/8088/3100 unchanged.

### CODEX 1 — Foundation + dependency integration (FIRST MERGE GATE)
Own ONLY:
  frontend/package.json, package-lock.json, frontend/nuxt.config.ts,
  frontend/app/app.vue, frontend/app/app.config.ts,
  frontend/app/assets/css/main.css (new), docs/14-nuxt-migration/TAILWIND_*.
Tasks:
  Install/configure @nuxt/ui v4 + tailwindcss v4 per Nuxt UI docs; minimal correct provider/module CSS setup.
  Map actual brand palette, font, radius, spacing and semantic colors into CSS-first Tailwind v4 and Nuxt UI app config.
  Style a small isolated /migration-ui-lab-preview route (new only) showing Button, Menu, Drawer, Modal, Tabs, Input, Select and mobile/keyboard behaviors.
  Assess Tailwind CSS Preflight influence and avoid breaking active PHP/Nuxt UI during dual-stack bridge; document deliberate temporary CSS coexistence, ensure no duplicate CSS imports.
  Run npm test, typecheck, build and a small Chromium QA serially; commit. Do not rewrite Home/About or any other route in this step.
Definition of done: Nuxt UI/Tailwind load in SSR, UI components work, theme consistent, old preview routes still load, no hidden plugin conflicts.

### CODEX 2 — Global shell & Home + About, visual-first (AFTER STEP 1)
Own frontend/app/components/SiteHeader.vue, SiteFooter.vue, SiteBreadcrumb.vue,
frontend/app/components/layout/**, frontend/app/components/home/** and about/**,
frontend/app/pages/migration-{home,about}-preview.vue,
CSS currently used by those two pages & shell, related tests.
Convert HTML styling/classes and interactions to Tailwind v4, Nuxt UI controls selectively. Preserve the polished PHP layout as the visual baseline, may improve microinteractions without changing navigation meaning or content. Integrate Visual Sprint useful changes rather than re-create. Move away from redundant home-visual-fixes.css/home-visual-parity.css/about-visual-parity.css override layers; delete/import-disable after new screenshot PASS. Mobile nav/dropdowns keyboard/touch validated.
Do NOT change PHP API, contracts, editorial, commerce, main.css, nuxt.config.ts.

### CODEX 3 — Commerce product family (PARALLEL WITH CODEX 2 ON DISJOINT FILES AFTER STEP 1)
Own frontend/app/components/catalog/**, product-preview/**,
frontend/app/pages/migration-{catalog,product}-preview.vue,
frontend/app/assets/css/{catalog-preview,product-detail-preview}.css, related UI tests.
Convert all styling to Tailwind v4; use UInput/USelect/UButton etc for filter/quote controls. No fake prices: if backend contract lacks filters/pricing, label blocker and collaborate with a separate PHP API-owner AFTER UI base is stable, never duplicate PHP pricing in Vue.
No edits to Header/Footer/layout/theme/Home/About/News/Projects/solutions, PHP APIs or backend. QA catalog -> PDP -> PHP cart handoff (cart stays PHP).

### CODEX 4 — Editorial + Corporate pages (FOLLOW-UP BATCH, split by ownership if resource permits)
Owner Editorial: frontend/app/components/{news,news-detail,projects,project-detail,solutions,solution-detail}/**, related pages/CSS, editorial renderer (after sanitized rich-text review).
Owner Corporate: frontend/app/components/{contact,dealer}/**, related pages/CSS; About already done by CodeX 2. Legacy forms remain PHP with CSRF. Make each content family tailored; don't impose generic hero.
Use Nuxt UI cards, inputs, tabs, accordions etc only when semantically appropriate; Tailwind classes for overall design; remove legacy CSS imports after page passes.
Do not touch shared foundation files or each other's folders.

### CODEX 5 — Hybrid/SEO/behavior audit + integration (AFTER sufficient real routes)
One local origin reverse proxy route allowlist; unported endpoints including /api, /upload, /thumbs, /administrator, /account, cart and POST remain PHP. Full route matrix + 301/404/canonical/robots/schema, media, SSR, data, performance and rollback. Never change production VPS without owner GO.
Integrator merges only clean disjoint changes with tests, performs ONE build/browser QA per batch, removes merged worktree if explicitly accepted.

## Done means
- Tailwind v4 and Nuxt UI v4 are the consistent long-term styling and interactive-component standard for every Nuxt frontend route.
- No page-preview legacy CSS stylesheets remain imported as the primary styling implementation; minimal global @theme and exceptional CSS are intentional and documented.
- Desktop/tablet/mobile look polished, brand-consistent, each page independently designed, real CMS/data and interactions work.
- No errors, missing sections, broken images, duplicate header/footer or fake pricing; no regression to backend PHP.
- End-to-end functional flows and SEO/local hybrid route readiness have evidence; do not equate 200 preview or screenshot alone with launch readiness.

## Immediate user-to-Codex coordination
1. Tell active Visual Sprint to checkpoint/commit safely and stop producing CSS override files, pass screenshot artifacts & missing-gap notes.
2. Execute Codex 1 foundation task in independent worktree from latest integrated SHA; only this agent may edit package/config/theme/app.
3. Once integrated, assign Codex 2 and Codex 3 in parallel (2 agents max), then editorial/corporate batches.
4. Review/merge sanitizer b856784 independently before any rich-text page consumes it.
5. End each batch with one integrator and real browser QA, not multiple simultaneous build agents.
