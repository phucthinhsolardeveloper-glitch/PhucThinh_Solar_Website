# PTS Solar Precision — Luminous Engineering V3
Date: 2026-11-02 | Status: design exploration, not production approved.
Branch: feature/pts-energy-v3-design-system-20261102, base 17242cfc.
Source of inspiration: read-only EnergySystemExplorerV2.vue in worktree pts-uiux-wave1-review.

Purpose: extend Energy V2's spatial energy stage and PTS identity into a coherent visual language across public Nuxt pages without rendering the entire site dark or compromising buying/reading flows. The visual direction is not constrained by legacy PHP visual parity, but must preserve business semantics, SEO, source data, access, and production safety.

Documents:
- 01-SYSTEM-DIRECTION.md — art direction, white grid, lighting states, reusable patterns and design anti-patterns.
- 02-PAGE-ROLLOUT.md — per-page plan, implementation waves and ownership.
- 03-CLAUDE-DESIGN-ASSIGNMENT.md — Claude Frontend Design exact scope, deliverables, critique and design studio.
- 04-QUALITY-AND-RELEASE.md — acceptance, browser QA, performance, review gates.
- tasks.yaml — lane-specific backlog and dependencies.

Verified:
- staging HTTP 200; brand PTS navy #0A2251, deep #061537, gold #C9962E, light gold #E0B654, Be Vietnam Pro.
- Header exception #C69A45 remains unchanged.
- Nuxt 4/Vue3/Tailwind4/NuxtUI4/motion-v present.
- Energy V2 source has five nodes and 3 modes; its static stage-sky gradient is not fully changed by day/night/backup. Day/night currently recolor specific SVG elements.
- V2 previously passed desktop 1440/mobile 390 mode smoke after hydration; no complete device/performance/accessibility audit.
- master and wave1 worktree have uncommitted changes. DO NOT edit those checkouts.

Allowed for study: this worktree docs/design/energy-v3/**, new experiment route frontend/app/pages/experience-energy-v3.vue, and new lab-only components frontend/app/components/experience-v3/**.
Not allowed without new review: master, wave1, staging, production, SiteHeader/Footer, public homepage, shared CSS/tokens, API/PHP, CMS/DB, deploy/vps, push/merge.

Use local noindex preview only. A prototype passing QA is NOT release approval.
