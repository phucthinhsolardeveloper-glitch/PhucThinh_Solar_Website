# PTS DESIGN & DELIVERY STANDARD — v1.0
Status: WORKING STANDARD / owner review before changing approved site-wide ADR
Created: 2026-11-03
Maintainer: PTS owner; execution-neutral (Codex / Claude / GitHub / Orca optional)

## Purpose
Stop producing branches, reports and beautiful descriptions without a finished screen. This package has TWO separate layers:
1. Universal Delivery Protocol (01): reusable for PTS, Zalo CRM and future projects.
2. PTS Visual Language (02): brand-specific only for Phuc Thinh Solar public website.
The same delivery rules work for apps, backend APIs, documentation and other projects; do NOT copy PTS navy/gold into unrelated apps.

## Read order for every AI task
A. Project-specific AGENTS.md, existing ADR and business/data/security contracts
B. This index and 01-UNIVERSAL-DELIVERY.md
C. For PTS public UI only: 02-PTS-VISUAL-LANGUAGE.md and existing docs/02-development/public-ui-ux-design-system.md
D. For visible design: 03-VISUAL-ACCEPTANCE.md and reference.html
E. Use templates/ISSUE.md and templates/PR.md for handoff

## Decision hierarchy
- Latest explicit owner-approved decision and safety/business constraints prevail.
- Existing technical/data/SEO/accessibility contracts remain binding.
- This standard adds PROCESS and creative composition, not authority to overwrite old source.
- Migration/parity task: match production baseline.
- Explicit creative-redesign task: keep FUNCTIONAL / DATA / SEO parity, but deliberate visual deviation is allowed ONLY after a selected direction and before/after review.
- Homepage, header, footer: protected scope; changes only when issue explicitly permits and owner approves.
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

## Adoption without conflict
This package lives under docs/15-design-delivery/. Do not silently edit AGENTS.md or existing ADR while other workers have uncommitted changes. An integration owner should reconcile the conflicting 'full visual parity' rule by an owner-approved ADR, then add a pointer to this package in agent entrypoints. Do not replace existing policy wholesale.

## First pilot
W4-05 Projects: one selected Solar Cinema direction -> load images with actual scrolling -> fix issues -> compare screenshots -> reviewer approval -> integrate to preview. Reuse this proof-of-delivery recipe before expanding to new pages.

## Interactive layout exploration
- Open [Layout & Interaction Lab](layout-lab.html) for three real browser-rendered compositions: photographic image behind the header (Full-bleed), Editorial Split and Architectural Frame.
- Read [Layout Lab spec](06-LAYOUT-LAB.md) for component/interaction contracts, design eligibility and guidance to port a selected direction to Nuxt.
- Review [Layout Lab QA](07-LAYOUT-LAB-QA.md) and the evidence/layout-*.jpg captures. This is design-only, not a production or owner-approved header change.
- Current hero and header production contracts remain protected unless approved per route.
