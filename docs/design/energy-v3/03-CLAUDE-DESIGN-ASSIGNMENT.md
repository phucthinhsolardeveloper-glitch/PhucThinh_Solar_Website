# 03 — Claude Frontend Design Assignment

## Mission
Be the art director and interactive frontend designer for "PTS Solar Precision — Luminous Engineering V3" within the dedicated worktree. The owner likes the current `http://localhost:3122/experience-energy-v2` especially its spacious stage, spatial perspective, glowing gold energy flow, equipment inspector, and technical depth. The requested improvement: light sections should gain subtle engineering grid, and switching DAY/NIGHT/BACKUP should *visibly change the atmospheric lighting of the whole scene*, not just text and lines.

## Required context to read
1. This folder's `00-README.md`, `01-SYSTEM-DIRECTION.md`, `02-PAGE-ROLLOUT.md`, `04-QUALITY-AND-RELEASE.md`, `tasks.yaml`.
2. Read-only reference `/home/admin01/orca/workspaces/Web_PTS_PHP/pts-uiux-wave1-review/frontend/app/components/experience/EnergySystemExplorerV2.vue` and `frontend/app/pages/experience-energy-v2.vue`. Do not modify these files.
3. Existing study `/home/admin01/orca/workspaces/Web_PTS_PHP/pts-20261102-claude-design-study/docs/design/claude-design-study/CONCEPTS.md`, plus staging screenshots in its evidence folder.
4. Brand tokens `frontend/app/assets/css/main.css`, `https://staging.phucthinhsolar.com/`, existing public components.

## Mandatory Claude Design skill
Invoke official Anthropic `frontend-design` plugin via `claude --plugin-dir .../plugins/frontend-design`; apply the loaded skill deliberately, not generic CSS code generation.

## Requested outputs
A. `CLAUDE-ENERGY-V3-CRITIQUE.md`: actionable critique of V2 design (hierarchy, ambient light, motion semantics, 2.5D, scene size, inspector, mobile, a11y, code organization), with severity and evidence from source.
B. `CLAUDE-ART-DIRECTIONS.md`: three distinct style directions with desktop/mobile wireframes: 1) Precision Paper, 2) Spatial Atelier, 3) Living System. Use staging palette exactly; decide a recommended merged approach and what to reject.
C. **Working local Nuxt R&D page** `frontend/app/pages/experience-energy-v3.vue` plus new lab-only components under `frontend/app/components/experience-v3/`. It must demonstrate both selected **white background grid** and **mode-reactive spatial lighting**. At least 3 controls Day/Night/Backup, visible sky/light/home changes and flow routing. Prefer CSS/SVG over new dependency; no copied fake data.
D. `CLAUDE-HANDOFF.md`: component map and follow-up tasks to Codex, known issues, exact local preview commands and QA outcomes.

## Prototype requirements
- Brand colors locked: #0A2251, #061537, #C9962E, #E0B654; Be Vietnam Pro. Header exception never edited.
- Distinct visible DAY sunshine brighter scene; NIGHT dark scene with warm house interior, PV faded; BACKUP dark isolated scene and priority load emphasis. Explain simulated-only semantics, not real telemetry.
- White grid subtle 32/40px + edge fade, only on demo section; no global body texture.
- No production header/footer/home modifications. Local-only demo route noindex,nofollow,noarchive and not linked from sitemap.
- Respect keyboard/touch/mobile and `prefers-reduced-motion`; native scrolling; useful fallback if SVG disabled.
- No new packages / build runtime services / external assets without permission. CSS/inline SVG geometry is allowed. No fake PTS customer images, power numbers, ROI, device models.
- Do not touch `frontend/app/assets/css/main.css`, shared tokens, `deploy/vps`, public existing pages, PHP API, CMS/DB or any other worktree.
- Keep output ready for screenshot comparison at widths 320/390/768/1440.
- **Do NOT push, merge, deploy or modify staging/production.**

## Collaboration
Claude = concept, critique, first prototype. Codex = refine domain-state graph, component extraction and integration only after Claude output reviewed. QA agent = independent browser validation. Orca integrator owns shared tokens and future page rollout. No blanket full-site code rewrite.

## Definition of done
Documents and working prototype present, semantic mode changes reproducible, no console errors, no horizontal overflow on 390, noindex in SSR, reduced-motion functioning, clear handoff. Unknowns explicitly listed; don't claim QA/device pass without run evidence.
