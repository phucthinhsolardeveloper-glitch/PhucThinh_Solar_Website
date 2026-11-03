---
name: pts-layout-selector
description: Select and critique concrete UI layouts and motion for PTS Solar and other Vue/web applications before implementation. Use for page redesigns, visual direction decisions, creative exploration, motion/3D choices, or requests to avoid generic AI-looking UI.
---

# Layout Direction Selector — design decisions before code

Use when a design outcome is requested, **not** as a default for every backend change.

## Read authoritative project context
1. Read AGENTS.md and latest owner-approved design ADR. Do not silently override an existing parity/protected-homepage rule.
2. If working on PTS Solar read:
   - docs/15-design-delivery/00-START-HERE.md
   - docs/15-design-delivery/02-PTS-VISUAL-LANGUAGE.md
   - docs/15-design-delivery/03-VISUAL-ACCEPTANCE.md
   - docs/15-design-delivery/08-DESIGN-CATALOG.md
   - docs/15-design-delivery/09-TOOLKIT-INSTALL.md
   - docs/02-development/public-ui-ux-design-system.md
3. For another project, KEEP its own branding/UX/data rules; use PTS catalog only as a taxonomy of compositions, not as its color theme.

## Before code
- State the user job, route or screen, content and media available, and what is protected.
- Do not propose more alternatives when the owner has already selected a direction. Continue implementing and improving that one.
- Otherwise use docs/15-design-delivery/design-picker.html to choose one primary composition and max three effects with clear purpose. layout-lab.html contains functioning demos of three hero compositions. reference.html contains energy/editorial examples.
- In a new project without these local files, use the same taxonomy: full-bleed, editorial split, framed image, field journal, cinematic, technical blueprint, product atelier, catalog filter rail, workspace, 2.5D energy, and real WebGL scene.
- For proposed options, vary hierarchy/flow/media scale, not just color/gradient or font. Identify at least one original solar-specific visual choice for PTS.
- Ask owner to approve a screenshot/preview before changing a protected shell/global theme.

## Skill/tool routing (already installed in company WSL)
- frontend-design: design art direction.
- ui-ux-pro-max: reference style/typography/layout/effects.
- design-system: token and reusable components.
- brand: enforce brand continuity.
- banner-design: hero/media composition when meaningful.
- web-design-guidelines: independent UX/a11y review.
- Nuxt UI + Tailwind + motion-v already available at PTS. Do not install React/shadcn just for a component.
- For real 3D, distinguish CSS/SVG 2.5D from WebGL. @tresjs/nuxt + Three.js or GSAP requires a separately approved scoped experiment and performance/accessible static fallback.

## Concrete deliverable
1. A design decision brief with the chosen composition, why, media provenance, selected effects and acceptance criteria.
2. A working route or isolated prototype, with desktop 1440px and mobile 390px screenshot after actual scrolling and image loading.
3. A critique with 3–5 specific observations from the render; repair and recapture.
4. An explicit approval request / reviewer decision, not self-declared DONE.
5. GitHub Issue/PR links when implemented; maintain source and integration status.

## Reject immediately
- Generic all-caps eyebrow, three repeated gradient cards, imaginary KPI, fake case studies, unrelated stock visual used as evidence, inaccessible CTA, poor 320px layout, blank lazy images, broken or hidden mobile navigation.
- Three.js 3D claim without a genuine renderer and interactive scene.
- Repeated plans without a usable preview.
- A feature presented as complete solely on HTTP 200, typecheck or build PASS.

## Source workflow
GitHub Issue → direction approved → scoped worktree if parallel → build → browser capture → visual critique → PR review → integrated preview → owner acceptance → safe worktree close. Orca and Task Queue are optional, not required.
