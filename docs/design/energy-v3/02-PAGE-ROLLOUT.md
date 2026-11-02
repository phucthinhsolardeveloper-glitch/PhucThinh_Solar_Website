# 02 — Page Rollout / Source-aware implementation plan

## Principle
Design V3 is an evolution of PTS energy/engineering identity. Different pages have different jobs. White-grid and luminous scene are *patterns*, not a template pasted everywhere. The first implementation must be isolated on an experience route; no production/staging merge until later acceptance.

| Page group / routes | Visual concept | Function to add or improve | Priority | Proposed scope |
|---|---|---|---|---|
| **Energy V3 lab** `/experience-energy-v3` local | responsive day/night/backup spatial-stage, brand scene | full mood lighting, device inspector, meaningful route states | P0 | New lab-only page/components |
| **Solutions** `/giai-phap`, `/giai-phap/[slug]` | dark spatial scene within bright editorial narrative | pick day/night/backup; energy role, link to relevant category; no fictional model compatibility | P0/P1 | 1 representative Solution Detail, then hub |
| **Home** `/` | bright editorial + subtle selective grid, one signature energy window | narrative with real imagery, scenario jump and context CTA | P1 | Preview only after V3 lab accepted; protect current homepage |
| **PDP** `/san-pham/[slug]` | light technical hero + small system location diagram | specs, gallery, docs, real use-case context, mobile CTA | P1 | 1 representative inverter/storage PDP |
| **Catalog** `/san-pham`, categories | white precision-grid vignette + category stage | intent-based discovery, comparison, filters/URL preserved | P1 | 1 catalog subcategory, not rewrite filters |
| **Projects** project hub/detail | architectural photography + technical project story | verified system chapter, scrollspy and media gallery, no fake project shots | P1/P2 | 1 project with media provenance |
| **About** `/gioi-thieu` | bright corporate editorial + measured technical axes | timeline, certificates, asset proof, mobile nav auto-reveal | P2 | Current content plus improved storytelling |
| **News/Knowledge** `/tin-tuc` | white reading canvas; grid only cover/lab callouts | table of contents, diagrams, related genuine products | P2 | readability and UX first |
| **Dealer/Partner** `/dai-ly` | clean premium partnership editorial | application flow, proof, partner info | P2 | future |
| **Contact, Cart, Checkout, Support** | clean white, no decorative grid in forms | form/error/empty states, CSRF/canonical data correctness | P2 | stability-first, not cinematic |
| **Media, Brands, Careers** | niche editorial template with relevant story | provenance and navigation | P3 | after core designs |

## Wave plan & dependencies

### W0 — Baseline & Claude Design
Read staging baseline desktop/mobile, V2 source and existing Claude concept. Three art directions + inspectable prototype. Owner: Claude Frontend Design. Do not modify source V2 in wave1.

### W1 — Foundation lab
One `GridSurface` with variant size+mask, one sectional axis, one enhanced energy-stage lighting experiment. Local Nuxt preview only. Owner: Codex implementing selected Claude design. Test 320/390/768/1440; no global CSS edits.

### W2 — Energy scene behavioral V3
Extract visual state map from domain graph. Three modes affect sky/house/PV/grid and inspector. Add clickable scene nodes and keyboard fallback. Valid electrical semantics reviewed by SME. Owner: Codex energy lane + QA independent.

### W3 — Page pilots (independent)
Solutions detail high-priority; Home limited alternate section in preview; one PDP system-context inset. Different file owners per worktree; avoid same shared CSS. Produce side-by-side screenshots and usability notes. Owner: 2 Codex agents and Claude critique, integration owner.

### W4 — Design system consolidation
Only after pilot is accepted: codify grid/token/layout/motion patterns, revise old visual-parity rule scope; promote reusable components and update design docs. Owner: integration agent.

### W5 — Further pages, controlled release
Projects, Catalog, About, News, Dealer; only from approved patterns. Functional contracts/SEO/commerce stay validated, each page release reviewed separately.

## Integration constraints
- Do not change currently running staging/prod; no Caddy/VPS changes.
- HMR via Nuxt dev only, no unnecessary Docker rebuild.
- Reuse existing Nuxt UI/Motion; GSAP/TresJS are R&D spikes only after proving value.
- Any overlapping file change must be reviewed by integrator. Worktree naming is not ownership proof; check `git status`.
- Check existing WIP in `pts-uiux-wave1-review` before any future reuse/cherry-pick. Never copy V2 untracked source into main without owner sign-off and commit history.

## Art direction deliverables per pilot
Desktop 1440 screenshot, mobile 390 screenshot, interaction recording or browser test, component tree, performance notes, real data and asset provenance. Distinguish actual component code from a visual mockup.
