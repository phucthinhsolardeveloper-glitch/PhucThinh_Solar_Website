# PTS SOLAR VISUAL LANGUAGE — design decisions for implementation
Status: reference art direction for explicitly scoped redesign. Existing public UI/UX contract governs shared tokens, production behavior and accessibility.

## 1. Personality and design thesis
Identity: PRECISION ENERGY / EDITORIAL ENGINEERING.
A serious engineering and energy showroom: luminous natural light, precise alignments, rigorous documentation, and rare cinematic scenes that explain a real concept.
Aim for quiet confidence, not dashboard cosplay, startup gradients, empty luxury minimalism or giant card stacks.
The product remains commerce and consultation, not a decorative landing page.

## 2. Approved anchors and tokens
Typeface: Be Vietnam Pro. Preserve existing font and typography-system.css; no per-route replacement.
- Navy #0A2251 (headings/interactive selection); navy deep #061537 (stage backgrounds)
- Gold #C9962E and gold-light #E0B654 (measured emphasis/CTA; follow existing gold gradient)
- White #FFFFFF; cool surface #F8FAFC; gray ink #64748B; border #E5E7EB only where functional.
- Header uses its approved token exceptions. Do not normalize header by force.
- Text contrast >= 4.5:1, large text >= 3:1; control boundaries and focus >= 3:1.
- No purple neon. Avoid generic centered headline + 3 cloned cards + gradient background.
- Corner radius mostly 0–5px, pills only when function requires. No global card shadow.

## 3. Layout grammar
CANVAS A / PRECISION PAPER: white/off-white, clear section hierarchy, very subtle 32px engineering grid only inside relevant background sections; never beneath dense text/forms.
CANVAS B / SPATIAL ENERGY: deep navy lighting pocket, separated from the reading surface, for the system explanation or a special hero.
CANVAS C / SOLAR CINEMA: immersive REAL project/photo or product media, with clear overlay and next action; no fake case studies.
CANVAS D / PRODUCT ATELIER: product image contain 1:1, detailed specifications and filter/CTA near task.
CANVAS E / FIELD JOURNAL: verified article/project information with captions, chapter rail, diagrams and calm reading rhythm.
CANVAS F / CONNECTED JOURNEY: progressive path from need -> system -> product/quote; not an automatic generic stepper.
A page combines 2–3 relevant canvases, NOT all six. Use different rhythms across adjacent sections: wide visual -> narrow explanatory -> asymmetrical detail -> focused action.

## 4. Reference component grammar
PAGE INTRO: exactly one H1, concrete supporting text and optional relevant action, no fake eyebrow. Heading directly names the content.
MEDIA FRAME: fixed intrinsic ratio and real source; project 4:3, news 16:9, product 1:1 contain, campaign per Admin contract. Empty images get stable purposeful fallback, not huge invisible gutters.
SECTION: 64px desktop / 48px mobile spacing default; one gutter owner, reuse pts-container.
CHAPTER NAV: useful for 3+ genuine content chapters; anchor states with keyboard and scroll-margin-top.
DATA STRIP: only real CMS metrics with units/source; absent data means honest absence, not invented value.
CTA: primary gold gradient on navy text; secondary text/link; one primary action per major reading context.
FORM: readable labels, loading/validation/error/success, mobile safe area.
CARD: no nested shells; typography and image do the hierarchy; hover only communicates action.
ICON: Nuxt UI/Lucide for generic actions, bespoke inline SVG only for genuinely unique system depiction.

## 5. Exact page-family choices
HOME: preserve existing approved header/footer/home composition; explore ONE alternate signature energy insert without destroying commerce pathways. No arbitrary homepage takeover.
ABOUT: Architectural Editorial + documentary evidence; real team/office/certifications, meaningful milestones only with evidence.
SOLUTIONS HUB/DETAIL: Connected Journey + Spatial Energy; select need/mode then explain PV, inverter, storage, grid/load with correct meaning. Keep links to actual categories.
PROJECTS: Solar Cinema + Field Journal; authentic photo, meaningful case study metadata, chapter layout; don't fabricate equipment or gallery.
CATALOG/SEARCH: Product Atelier + Engineering Instrument; filter, chips, taxonomy, sorting, URL query and accessible drawer above decoration.
PDP: Product Atelier + Technical Journal; specs, real PDFs and gallery, responsive purchase/quote CTA; no fictional compatibility.
KNOWLEDGE/NEWS: Field Journal; actual reading experience, TOC when useful, context diagrams and related genuine entities.
DEALER/CONTACT: Precision Paper; short path to trust and form success, restrained mood and no decorative 3D.
CART/CHECKOUT: transaction-first; stable keyboard, price and error behavior; avoid cinematic noise.

## 6. Motion levels
LEVEL 0 / static: content available if JavaScript fails and under reduced-motion.
LEVEL 1 / micro: 120–240ms state/focus/hover.
LEVEL 2 / storytelling: 300–650ms isolated reveal/chapter, never default reveal on every card.
LEVEL 3 / interactive: deliberate user-controlled scene, mode switch and camera movement; stop/disable animation when hidden or reduced motion.
Avoid wheel hijacking, full-page pinned scrolling, mandatory autoplay audio and high motion near text/form controls.
Use Motion for Vue already in project. GSAP only if complex synchronized timelines are justified by a specific experience. Three.js/TresJS only in a scoped 3D pilot with performance budget and fallback.

## 7. 3D scene contract — no fake energy telemetry
A real 3D scene requires camera, geometry, materials, lights and interaction with GPU/WebGL or equivalent. CSS 2.5D must be labeled 2.5D, not Three.js.
DAY: sunlight and PV generation conditional on scenario; potential load and charge paths.
NIGHT: PV inactive, battery/grid supply only if scenario provides them.
BACKUP: grid isolated, battery + supported backup topology supply priority load ONLY when equipped; mode disabled if no eligible battery.
Each state updates scene lighting, paths AND explanatory inspector; show 'Minh họa nguyên lý, không phải dữ liệu vận hành thực tế.'
Do not invent power, energy, savings, production or hardware compatibility. Include reduced-motion/static diagram fallback and low-end mobile fallback.

## 8. Anti-AI-slop quality floor
Never add decorative ALL CAPS eyebrow/kicker, arbitrary 01/02 numbering, meaningless tiny diagrams, fake KPI, 3 identical gradient cards, massive empty whitespace, unrelated stock imagery, shiny glassmorphism or purple neon.
Only number a true ordered sequence or real project chapter.
Never let a CMS data gap be masked with fake media. A design should remain intentional with 0, 1 or many records.
At least one original visual decision per representative page that is specific to the solar subject (media angle, energy topology, material story, filter path), NOT just changing background colors.
A beautiful static mock without usable flow is RESEARCH, not DONE.

## 9. Visual-quality challenge
Before coding: produce 2–3 distinct compositions only when direction is not yet selected; compare real structure, information architecture and user task, not palettes.
After coding: compare first fold, mid content, end CTA, and mobile journey against baseline; check typography rhythm, alignment, imagery, negative space, information density, contrast and interaction.
A reviewer must be able to name what is BETTER, what is WORSE and what to change next with a screenshot reference.

## 10. Avoid contradictory governance
This is a creative implementation SPEC, not an automatic supersession of ADR 0009. For migration tasks: retain visual parity. For a scoped creative redesign: ask owner to approve direction and record a new ADR or issue decision before visual deviation reaches a shared target.
