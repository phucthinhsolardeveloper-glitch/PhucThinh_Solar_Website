# Design Picker QA — 2026-11-03

## What was tested
- Offline demo: docs/15-design-delivery/design-picker.html
- 6 widths: 320, 390, 768, 1024, 1440, 1920
- 12 layout cards (rendered thumbnail compositions) and 16 selectable motion/effect ideas.
- At every width: exactly 1 H1, 0 horizontal overflow, 0 browser console/page errors, representative images loaded.
- Selection recommends a layout for 6 page types (Projects, Home, Solutions, PDP, CRM, Knowledge).
- Category filters: cinematic, editorial, technical, commerce, experimental.
- Selected layout updates the larger illustrative preview, risk, purpose, suggested tool and exported Markdown issue brief.
- Compare list limits to three active patterns; users can remove one and compare another.
- Effect selection limits to 3; fourth selection is rejected with accessible feedback.
- Live brief reflects page, layout, selected effects, goal and constraints.
- Screenshots evidence/design-picker-1440.jpg and evidence/design-picker-390.jpg generated after returning to top of page, with reduced motion.

## Browser checks
Script: node docs/15-design-delivery/qa-design-picker.cjs
Last result: PASS (6 screen sizes, 12 layout cards, 16 effects, page/filter/preview/compare/brief states; no overflow / page errors).

## Scope limitation
This is a design **selection tool**, not a site redesign. The twelve thumbnails and CSS variants are sufficient for initial comparison but not twelve finished CMS-backed pages. The live layout-lab.html provides actual interactive hero demos for Full-bleed, Editorial Split and Architectural Frame; reference.html provides more energy/editorial samples.
GSAP and TresJS were not installed into the website; the design catalog only recommends them when a specific task justifies the added runtime cost.
Project photography and product media used in thumbnails are labelled reference-only; they are not claims of authenticated PTS customer projects or product capability.

## Before promoting any direction to production
- Owner selects a visual direction and approves a design brief or GitHub Issue.
- An implementer builds a real Nuxt route/section using approved CMS assets, real data and unchanged business behavior/SEO.
- Reviewer checks first/mid/end screenshot at 390 and 1440 after scrolling/warming lazy images; 320 responsive; browser functionality, reduced-motion, keyboard/focus and actual performance.
- Dedicated integration PR and review; production deployment remains separate.

## Skill install verification
Global curated skill pack installed for Claude Code and Codex on company WSL: frontend-design, ui-ux-pro-max, design-system, brand, banner-design, web-design-guidelines. The local pts-layout-selector also resolved at both ~/.agents/skills/pts-layout-selector and ~/.claude/skills/pts-layout-selector.
Existing Claude frontend-design skill was not overwritten; no Nuxt runtime dependency was added.
