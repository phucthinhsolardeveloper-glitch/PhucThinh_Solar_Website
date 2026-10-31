# Re-audit 02 — Tokens, surfaces, icons and motion

> **Cập nhật remediation 2026-10-30:** trạng thái code-only bên dưới là baseline trước fix. Dark CSS đã được gỡ khỏi light-only storefront; News/Project/equipment surfaces được làm phẳng; header reduced-motion được browser-verified; Be Vietnam Pro và scale body đã được đo ở 390/1440/1920px; `hdr-*` được ghi nhận là semantic exception theo PHP. Spacing scale và quy ước thống nhất icon vẫn là enhancement/consistency work, chưa có lỗi release-blocking được xác nhận. Chi tiết: [REMEDIATION-STATUS-20261030.md](REMEDIATION-STATUS-20261030.md).

Date: 2026-10-30  
Scope: source-level comparison of legacy PHP public CSS/templates and current Nuxt/Tailwind v4 public components.  
Evidence label: **CODE** means confirmed from checked-in source/config; **RUNTIME** means reproduced in a rendered page. No browser/runtime inspection was available for this audit, so all findings below are CODE evidence and runtime reproduction is unverified.

## Baseline and limits

- Repository instructions and `docs/02-development/public-ui-ux-design-system.md` were read. The contract calls for one token set, Be Vietnam Pro, light-only public mode, flat surfaces, 2–5px functional radii, 64/48px section spacing, and reduced-motion support.
- The checkout is dirty: `frontend/app/assets/css/main.css`, `frontend/app/components/SiteHeader.vue`, `frontend/nuxt.config.ts`, and several docs are modified. Relevant header breakpoint adjustments are uncommitted WIP; this audit does not attribute them to committed `master`. Most CSS/token declarations reviewed below are present in `HEAD`; `main.css` working diff is mainly comments and breakpoint documentation, and its token implementation was also compared against `HEAD`.
- PHP is treated as the legacy implementation currently used for visual parity. CSS inclusion order is explicit in `domains/phucthinhsolar.com/public_html/templates/layout/css.php:6-105`.

## Findings

### TOK-01 — Brand and header palette have two simultaneous sources

- **Severity:** P2
- **Route/component:** Shared header, CTA buttons, catalog menu; all public routes
- **Expected vs actual:** A single approved brand palette should own colors. Nuxt exposes approved navy/gold tokens and a separate `hdr-*` palette; the PHP header-v3 also uses the latter palette while shared tokens retain the former. This is a confirmed source-level split, although matching Nuxt to the PHP header is a purposeful parity choice rather than accidental migration drift.
- **Evidence (CODE):** PHP shared tokens set gold `#c9962e`, navy `#0a2251` at `domains/phucthinhsolar.com/public_html/assets/css/design-tokens.css:27-38`. PHP `header-v3.css` uses header gold `#c69a45`, ink `#10243a`, deep gold `#8a6526` for consultation and search controls (`header-v3.css:54-64`). Nuxt `main.css` defines both token families at `frontend/app/assets/css/main.css:88-115` and `:117-157`; `SiteHeader.vue` uses `hdr-gold` / `hdr-ink` for nav/CTA at lines 541-545, 658-663. The source itself documents the unresolved split at `main.css:117-139`.
- **Runtime verification:** Not verified. Code values establish divergence; pixel parity was not re-measured in this audit.
- **Root cause:** PHP's late header stylesheet has a purposeful production palette that bypasses shared `--pts-*`; Nuxt replicated that palette to avoid a visible route-boundary change. No final token-owner decision is recorded in implementation.
- **Fix recommendation:** Resolve whether the shared token should change or header palette should be an approved named semantic exception, then encode that decision once and test PHP/Nuxt computed colors side by side. Avoid silently normalizing the Nuxt header because that would alter intentional parity.
- **Release blocker:** NO

### TOK-02 — Global light-only contract is undermined by dark-mode CSS fallback

- **Severity:** P2
- **Route/component:** Global public shell and any route receiving `.dark`
- **Expected vs actual:** Public storefront should remain light-only until all CMS content/routes pass dark-theme contrast. Nuxt config prefers light and uses a fresh storage key, but global reset CSS still defines a complete dark body background/text and numerous explicit dark overrides; this is a latent alternate visual system if `.dark` is applied.
- **Evidence (CODE):** Contract: `docs/02-development/public-ui-ux-design-system.md` §3.1 says keep dark switch hidden and do not reload an old dark choice. `frontend/nuxt.config.ts:12-19` configures light preference/storage. Yet `frontend/app/assets/css/tokens.css:3-6` styles `html.dark` and `body` navy; lines 9-57 add dark utility remapping and surface exceptions. These styles have no matching PHP public mode.
- **Runtime verification:** Not verified; no user-facing toggle was found in reviewed header, but class injection/local preference behavior was not exercised.
- **Root cause:** Legacy dark-mode compatibility rules remain in the Nuxt global reset despite the current light-only product contract.
- **Fix recommendation:** Remove/disable dark-mode rules while light-only is the product contract, or gate them behind the future approved complete theme implementation. Verify fresh and previously used browser profiles.
- **Release blocker:** NO

### TOK-03 — Nuxt component surfaces and motion reintroduce decoration removed from PHP

- **Severity:** P2
- **Route/component:** Header catalog mega-menu; solution equipment cards; news/project cards; editorial sharing and floating contact
- **Expected vs actual:** The approved system uses flat content surfaces, reserved shadow/radius for functionally floating UI, and minimal transitions. PHP's late `flat-surface-system.css` strips card borders/background/shadows for products, news, projects and related content (`flat-surface-system.css:21-59,103-118,156-170`), while preserving dropdown/popover shadows. Nuxt has many card lift/scale, image zoom, borders and shadows on ordinary content cards, beyond the approved surface treatment.
- **Evidence (CODE):** Nuxt catalog menu is an appropriate floating panel, but uses 12px radius (`SiteHeader.vue` template around lines 675-700; PHP catalog panel uses 12px in `header-v3.css:165-169`, so this is intentional parity). Ordinary Nuxt content uses borders/shadows and lift/zoom: `SolutionsEquipment.vue:20-33`; `NewsCard.vue:40,57,85,98`; `DealerSections.vue:52`; `EditorialShare.vue:86,130`; PHP flat rules cited above remove analogous decoration. PHP also has legacy hover transforms at `theme-v2.css:37-38`, subsequently flattened by late CSS; Nuxt equivalents do not have a final shared flattening layer.
- **Runtime verification:** Not verified. Static utility/class declarations are confirmed; the result of utility ordering and responsive states was not rendered.
- **Root cause:** Tailwind utilities are authored locally in Vue components without a Nuxt equivalent of the PHP final surface override; purposeful motion for product/media affordance is mixed with generic card elevation.
- **Fix recommendation:** Keep purposeful overlay/gallery/control elevation. Align ordinary news, project, product and editorial cards to flat PHP surfaces; retain only small functional image/control boundaries and a restrained text/color hover. Add a shared visual review by matching the same PHP and Nuxt route screenshots before cutover.
- **Release blocker:** NO (parity gate should remain open for affected routes)

### TOK-04 — Reduced-motion treatment is incomplete and differs from PHP behavior

- **Severity:** P2
- **Route/component:** Global scroll, header hide/reveal, card/image hover, modal/gallery and floating contact
- **Expected vs actual:** The system should honor `prefers-reduced-motion` for all non-essential movement. Nuxt's global rule reduces animation/transition duration and scroll, and floating contact has local motion overrides, but the header scroll hide/reveal transform and several component transforms/scale utilities lack explicit motion-reduce behavior; CSS `transition-duration: .01ms` still leaves transform state changes and doesn't suppress continuous JS-driven movement. PHP header explicitly disables all header transitions for reduced motion.
- **Evidence (CODE):** Nuxt global handling is `frontend/app/assets/css/main.css:217-237`; header transform/transition is `SiteHeader.vue:483` and hamburger animation lines 512-514, with scroll hide/reveal driven by script in the same component. Floating contact explicitly opts out at `SiteFloatingContact.vue:70,93,111-115`; several content-card transforms are in `NewsCard.vue:40,57`, `SolutionsEquipment.vue:25`, `DealerOverview.vue:41`. PHP header has explicit `@media(prefers-reduced-motion:reduce){...transition:none!important}` in `header-v3.css:155`; PHP shared scroll behavior is configured in `theme-v2.css:49` (no reduced-motion override found in reviewed theme CSS).
- **Runtime verification:** Not verified with OS/browser reduced-motion enabled.
- **Root cause:** Global CSS duration reduction does not cover JS-driven header movement semantically, and component-level motion is inconsistent about `motion-reduce` variants.
- **Fix recommendation:** Respect reduced motion in the header scroll-hide behavior itself (keep header visible or switch without movement), and apply a consistent reduced-motion policy to content transforms. Verify with browser emulation and keyboard navigation.
- **Release blocker:** NO

### TOK-05 — Typography token parity is implemented in code but runtime match remains unverified

- **Severity:** P3
- **Route/component:** Global public typography
- **Expected vs actual:** PHP loads and uses self-hosted Be Vietnam Pro; Nuxt now declares local font-face subsets and the same family stack, removing the former system-font fallback gap. This is purposeful migration alignment, not a confirmed defect. Runtime parity remains unproven because font file loading/rendered metrics were not inspected here.
- **Evidence (CODE):** PHP loads `css/font-be-vietnam-pro.css` early in `templates/layout/css.php:8` and `design-tokens.css:27-28` declares the stack. Nuxt declares Be Vietnam Pro local WOFF2 faces at `frontend/app/assets/css/main.css:77-86`, maps `--font-sans` at `:169-170`, and assigns body font at `:269-271`. Nuxt font assets are under `frontend/public/fonts/be-vietnam-pro/` (source path referenced by CSS).
- **Runtime verification:** Not verified (network/font-face status and actual text metrics not inspected).
- **Root cause:** Prior Nuxt font declaration did not load font faces; current WIP/implementation fixes that source-level gap, but runtime confirmation is outstanding.
- **Fix recommendation:** In the parity screenshot pass, confirm Be Vietnam Pro Vietnamese and Latin subsets load successfully and compare heading/body line breaks at approved viewports. No source change recommended without runtime evidence.
- **Release blocker:** NO

### TOK-06 — Icon implementation intentionally changes library but risks stroke/fill inconsistency

- **Severity:** P3
- **Route/component:** Shared header/footer and public cards
- **Expected vs actual:** PHP relies on Font Awesome 5 (including theme Font Awesome and `assets/css/fontawesome512/all.css`); Nuxt uses mostly hand-authored inline SVGs. Moving to self-contained SVG avoids a legacy font dependency and is a purposeful implementation change. However, no common icon component/weight contract is evident, so stroke widths and filled/outline treatment vary by component.
- **Evidence (CODE):** PHP CSS loader adds Font Awesome 5 at `templates/layout/css.php:23-26`; header templates use `fas` icons at `templates/layout/header.php:33-38,84-96`. Nuxt header uses inline SVG variants, e.g. `SiteHeader.vue:655,713,722,774`; footer uses filled inline SVG icons in `SiteFooter.vue:73,144,148`. Other components declare their own stroke widths (e.g. `ProjectsCta.vue:33-35`).
- **Runtime verification:** Not verified; no glyph fallback/missing icon check performed.
- **Root cause:** Icon migration is component-local rather than governed by a shared icon set/style.
- **Fix recommendation:** Define a small shared icon source or visual conventions for stroke/fill and default sizes; compare header/catalog/footer icons against the PHP screenshots. Keep semantic differences where filled logos/social marks need them.
- **Release blocker:** NO

## Summary

No P0/P1 issue is confirmed from this source review. Highest-value parity work is deciding the brand/header token ownership (TOK-01), removing latent dark mode while the public system is light-only (TOK-02), flattening ordinary Nuxt content cards where PHP already has a final override (TOK-03), and checking reduced-motion behavior in the actual browser (TOK-04). All findings are code-based; no runtime reproduction is claimed.
