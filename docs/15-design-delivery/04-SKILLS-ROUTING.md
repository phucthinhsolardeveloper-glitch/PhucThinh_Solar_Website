# SKILLS, TOOLS & MODEL ROUTING — practical matrix

## Current computer evidence (checked 2026-11-03)
- Claude local frontend-design skill exists at ~/.claude/skills/frontend-design/SKILL.md.
- Official Claude marketplace source exists; installation does not prove active plugin invocation.
- Shared agent skills include find-skills, computer-use, orchestration and orca-cli.
- Codex ~/.codex/skills has no verified custom UI/UX skill at audit time.
- Playwright visual tests exist under tools/visual-tests.
- Frontend is Nuxt 4 + Vue 3 + Nuxt UI 4 + Tailwind CSS 4 + Motion for Vue; Three.js/GSAP/TresJS not present as direct project dependencies at that point.
- Verify actual capability and authentication per running CLI session. Do not assume a marketplace copy means enabled.

## Match tools to jobs (no redundant installs)
CREATIVE DIRECTION: Claude Code with frontend-design; read site reference + Atlas; reference images and screenshots; produce 2–3 genuinely different spatial directions before code if not already selected.
UI IMPLEMENTATION: Codex or Claude using existing Nuxt UI controls, Vue components, Tailwind tokens and motion-v. Prefer stable HTML semantics over replacing everything with components.
BROWSER REVIEW: existing Playwright + independent critique by another agent. Screenshots and interactions are mandatory for visible design.
TECHNICAL DESIGN: explain real solar topology and constraints from verified data; do not pretend AI is an electrical engineer.
3D EXPERIMENT: scoped TresJS/Three.js spike only when accepted by owner and quality/weight budgets warrant it; static 2.5D fallback. Avoid loading a global graphics runtime on all public routes.
VISUAL ASSETS: real company photos for projects and capability, product manufacturer assets for products; generated diagrams only labeled as illustrative.
GITHUB: Issue -> branch -> PR -> CI -> visual review -> merge. Orca / Task Queue are OPTIONAL runners.

## Skills a future team may author AFTER one successful pilot
A. delivery-from-issue: load issue, establish constraints, implement, test, update PR and handoff.
B. pts-visual-critique: use design contract, capture first/mid/end, score 6 dimensions and reject generic AI layout.
C. energy-scene-qa: check DAY/NIGHT/BACKUP topology + accessibility + static fallback.
These are WORKFLOW recipes, not an excuse to install 30 different plugins.

## Cost policy
Use strong model for ambiguous architecture, visual art direction, integration conflicts and hard debugging. Use mid-range/fast model for bounded edits and routine tests. Lower model costs are not a win if repeated rework consumes more time.
Maximize completed approved features per review cycle, not tokens, sessions or commits.

## Context compactness
Always supply selected issue, target screenshots, approved style reference, code entrypoint, acceptance and protected files. Do NOT dump every past conversation or all design theories into a coding prompt. Maintain decision links in issue/ADR.

## Hand-off contract
Designer -> implementer: exact composition, proportions, content states, media source and interaction, accepted/rejected references.
Implementer -> reviewer: working URL, SHA, annotated before/after, state/viewport matrix, known gaps and tests.
Reviewer -> implementer: at most 5 concrete corrections with region, current vs expected and priority.
Integrator -> owner: one inspected unified preview and clear release decision.
