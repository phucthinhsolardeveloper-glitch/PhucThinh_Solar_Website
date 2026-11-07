# PTS DESIGN & DELIVERY STANDARD — v1.0
Status: ACTIVE DELIVERY STANDARD / owner-directed visual language alignment 2026-11-04
Created: 2026-11-03
Maintainer: PTS owner; execution-neutral (Codex / Claude / GitHub / Orca optional)

## Purpose
Stop producing branches, reports and beautiful descriptions without a finished screen. This package has TWO separate layers:
1. Universal Delivery Protocol (01): reusable for PTS, Zalo CRM and future projects.
2. PTS Visual Language (02): brand-specific only for Phuc Thinh Solar public website.
The same delivery rules work for apps, backend APIs, documentation and other projects; do NOT copy PTS navy/gold into unrelated apps.

## Current design language (2026-11)
For PTS public UI, [12-PTS-DESIGN-LANGUAGE-2026-11.md](12-PTS-DESIGN-LANGUAGE-2026-11.md) is the current visual authority. It supersedes older prescriptions about surface flatness, radius, glass, shadow, breadcrumb treatment and motion. `docs/02-development/public-ui-ux-design-system.md` remains binding for business/IA, CMS data, URL/SEO, media provenance, responsive behavior, accessibility and technical interaction contracts; where its older visual wording conflicts with document 12, document 12 governs the visual decision.

## Storefront rules already decided with the owner
The concrete, dated rules for the Nuxt storefront (selected states, hover, forms, copy, line breaks, rails) and the traps already met are kept in [../02-development/nuxt-storefront-guide.md](../02-development/nuxt-storefront-guide.md). They refine document 12 and apply site-wide.

## Read order for every AI task
A. Project-specific `AGENTS.md`, current owner direction and business/data/security contracts
B. This index and `01-UNIVERSAL-DELIVERY.md`
C. For PTS public UI: `12-PTS-DESIGN-LANGUAGE-2026-11.md` (visual authority), then `docs/02-development/public-ui-ux-design-system.md` (durable UX/data/accessibility contract)
D. For visible design: `03-VISUAL-ACCEPTANCE.md` and `reference.html`
E. Use `templates/ISSUE.md` and `templates/PR.md` for handoff

## Decision hierarchy
- Latest explicit owner instruction and safety/business constraints prevail.
- Current public visual authority: [12-PTS-DESIGN-LANGUAGE-2026-11.md](12-PTS-DESIGN-LANGUAGE-2026-11.md).
- Existing technical/data/SEO/accessibility contracts remain binding; older presentation prescriptions do not override the current visual authority.
- Use document 12 for current visual decisions and the public UI contract for
  durable product/behavior rules; older audits and ADR text do not regain visual
  authority merely because they predate this standard.
- Migration/parity task: match production baseline.
- Creative-redesign task: keep FUNCTIONAL / DATA / URL/SEO/accessibility parity while deliberately changing presentation to match the selected direction; review with before/after evidence.
- Homepage, header and footer business paths/content are protected from accidental
  removal; presentation changes are in scope when the task or owner direction
  explicitly includes them.
- A mock design is never evidence that a company, project, customer, statistic or device compatibility exists.
- Visual prototype != shipping-ready product.

## Definition of done
A task is only DONE when its result can be inspected, acceptance is met, QA evidence exists, changes have been integrated at the agreed target, and remaining work is explicitly tracked. No 'build PASS' shortcut.

## What the owner reviews
For each representative page/feature, receive ONE concise decision packet:
- what user problem it solves and chosen direction (why)
- actual preview URL and desktop/mobile screenshots of latest commit
- key before/after differences; known compromises
- interaction and data/SEO status; performance/a11y result
- PR/diff link or exact commit, status of integration
- clear call: APPROVE / REVISE / REJECT

## Adoption and conflict handling
The owner has directed the project to use the current visual language and align older guidance with it. Root and frontend `AGENTS.md`, the public UI/UX contract and affected ADRs must point to this document and state their remaining scope. Historical audit evidence stays historical; it must not be presented as a current visual requirement. Preserve valid CMS/data, URL/SEO, security, accessibility and commerce rules when replacing outdated presentation rules. Future changes to shared visual authority still require explicit owner direction and a recorded decision.

## First pilot
W4-05 Projects: one selected Solar Cinema direction -> load images with actual scrolling -> fix issues -> compare screenshots -> reviewer approval -> integrate to preview. Reuse this proof-of-delivery recipe before expanding to new pages.

## Interactive layout exploration
- Open [Layout & Interaction Lab](layout-lab.html) for three real browser-rendered compositions: photographic image behind the header (Full-bleed), Editorial Split and Architectural Frame.
- Read [Layout Lab spec](06-LAYOUT-LAB.md) for component/interaction contracts, design eligibility and guidance to port a selected direction to Nuxt.
- Review [Layout Lab QA](07-LAYOUT-LAB-QA.md) and the evidence/layout-*.jpg captures. This is design-only, not a production or owner-approved header change.
- Current hero and header production contracts remain protected unless approved per route.


## Design discovery and one-time skill installation
- Open [Design Picker](design-picker.html) to select among 12 layout compositions and 16 effects, compare alternatives and export a Markdown brief before coding.
- Read [Design Catalog](08-DESIGN-CATALOG.md) for the wider taxonomy and effect/runtime technology tiers.
- Read [Toolkit Installation](09-TOOLKIT-INSTALL.md) for the six curated agent skills verified on WSL, a portable installer, and the optional project-local selector skill.
- Run [Design Picker QA](qa-design-picker.cjs) with Node/Playwright to regenerate evidence/design-picker-*.jpg.
- Do not confuse a layout/interaction demo with a real CMS-backed or approved production page.
