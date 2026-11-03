# Energy V3 — independent browser QA results (2026-11-02)

## Scope and status
**PASS for prototype smoke / functional lighting validation.** This is NOT production approval, not a real-device test, and not a technical validation of electrical operations.
URL: `http://127.0.0.1:3198/experience-energy-v3` from the WSL preview on PC DESKTOP-8IC4AGO. Branch `feature/pts-energy-v3-design-system-20261102`.

## Verified (not agent claims)
- Route returned **HTTP 200** from Nuxt SSR, and rendered HTML contained robots `noindex, nofollow, noarchive`.
- `vue/compiler-sfc` parse + script/template compilation checks **5/5 PASS; 0 errors**, including new page + four components.
- `npm run typecheck` using Node memory cap 1050 MB **PASS, exit 0** on same worktree (Nuxt 4.6.3).
- Chromium / Playwright at **1440x900, 390x844, 320x760**:
  - DAY, NIGHT, BACKUP modes **9/9 PASS** (mode class and stage opacity updated; zero page errors; responsive document scrollWidth equals viewport width).
  - DAY sky rect opacity 1 and other sky layers 0; NIGHT rect opacity ~0.98 after transition; BACKUP rect opacity ~0.98 after transition.
  - All mode pages have `noindex`, no recorded console/page errors or horizontal overflow.
  - Clicking the visible `Có pin lưu trữ` switch disables Backup in all three viewports **3/3 PASS**.
  - `prefers-reduced-motion: reduce` probe on active flow: computed `animation-name: none` **PASS**.
- Screenshot evidence in `docs/design/energy-v3/evidence/`: `v3-{1440,390,320}-{day,night,backup}.png`, 9 files.

## Test harness correction
First Playwright run failed when attempting `locator.uncheck()` on a visually hidden native checkbox used beneath a custom switch. This was a **test locator/action mismatch**, not evidence the UI failed. Re-ran using a real user interaction (clicking visible `Có pin lưu trữ` label); rerun **FAILED 0, exit 0** across all widths and modes.

## Remaining validation
- Screenshot aesthetics still need **human visual approval**: quality of PV roof/house 2.5D geometry, contrast and typography, grid subtlety, and transition mood vs V2. Automated mode assertions do not prove the design is beautiful.
- Physical Android/iOS browser + NVDA/VoiceOver, tab sequence, focus, touch target manual assessment not yet executed.
- No measured real-user LCP/INP/CLS; do not call performance validated from HTTP success or a smoke run.
- Technical SME must approve illustrated energy topology and backup assumptions before public use.
- Existing business pages, CMS/APIs, Staging, VPS, Production remain unchanged by this prototype.

## Environment note
The old assistant-owned 3197 design-study dev process was specifically stopped to free 1.1 GiB for the V3 preview; **3122 Energy V2 and other project dev servers were left running**. V3 is at 3198. The V3 `frontend/node_modules` is a local symlink to already-installed modules from the main project, not a new npm install; do not stage the symlink.

## Decision
**GO to art-direction review and engineering validation; NOT GO to production**. Next: compare V2/V3 visual captures, preserve richer V2 architectural detail where it improves realism, and polish light-grid foundation before selecting Home/Solutions/PDP pilots.
