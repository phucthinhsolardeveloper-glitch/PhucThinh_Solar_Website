# PTS Design Atlas — Layout & Motion Catalog v1.1
Updated 2026-11-03 · Design exploration only · **Do not deploy as production UI automatically.**

## Purpose
The design catalog is a **decision system**, not a set of ten alternative themes to deploy together. Use it when the owner has not yet selected a visual direction. Always follow the latest approved PTS public UI contract, protect the actual header/footer/Home layout unless specifically authorized, and distinguish **visual prototype** from **real site feature**.

Open **design-picker.html** for the interactive catalog (12 representative layouts, effect combinations, page recommendations, comparison, and copyable GitHub issue brief). Open **layout-lab.html** for three working hero compositions (Full-bleed, Editorial Split, Architectural Frame). Open **reference.html** for Precision Paper, Energy 2.5D, Solar Cinema and Product Atelier examples.

## A. Layout taxonomy — choose by purpose, not by random trend

### 1. Hero and page opening
| Pattern | Visual anatomy | Good use | Hazard |
|---|---|---|---|
| Immersive Full-bleed | 100vw media from top; transparent header above it; contrast guard and legible CTA | Approved cinematic project/solution special pages | Do not change global header or use images with unreliable contrast |
| Editorial Split | Text and photo occupy deliberate unequal columns | About, solutions, technical explanation | Can become old-fashioned if centered and repeated identically |
| Architectural Frame | Media in a strong inset window, surrounding calm white surface | Engineering case study, technical journal | Frame may reduce image impact on narrow devices |
| Cinematic Cover | Huge verified image, restrained chapter narrative and captions | Real projects, field story | Image must be authentic, not generated proof |
| Typographic Hero | Title does the visual work, tiny media supporting the content | Reading, technical topic | Don't substitute giant type for meaningful substance |
| Photo Collage | Cropped asymmetrical tiles with one primary photo | About, projects when media is varied | Too many equal mosaic boxes flatten hierarchy |
| Video Background Hero | Compressed loop + poster fallback + controls | Special campaign, when quality media exists | Autoplay motion, bandwidth, accessibility and copyright |

### 2. Editorial / information layout
| Pattern | Composition | Best use | Hazard |
|---|---|---|---|
| Precision Paper | Strong alignment, white space, editorial typography | About, Knowledge, Contact | Don't fake tiny labels/eyebrows or hide CTA |
| Field Journal | Large photos, source captions, chapter rail | Project details, knowledge | Evidence must be verified |
| Sticky Chapter | Side navigation anchored to actual sections | Long solutions, guides | Avoid unnecessary pinning on mobile |
| Zigzag Narrative | Alternating media/text with different proportions | Solutions, process and applications | Repetitive 50/50 alternation reads as AI template |
| Magazine Mosaic | A dominant story and smaller supporting stories | News/Knowledge | Hard to scan if all stories equal prominence |
| Evidence Narrative | Case evidence, real metrics, photos, source link | Projects, About | Never invent capacity or savings |
| Technical Blueprint | Diagram plus inspector and actual data | Inverter/PV/backup explanation | Schematic must be technically correct |
| Diagrammatic Stage | Diagram focus, hover/select inspect panels | Solution detail, systems | Needs keyboard and mobile alternatives |
| Connected Journey | Choice -> explanation -> product/contact | Solutions, dealer onboarding | Must answer a real user decision, not generic 1-2-3 decoration |

### 3. Commerce and tool layouts
| Pattern | Composition | Best use | Hazard |
|---|---|---|---|
| Product Atelier | 1:1 contain hero product, real specs and CTA | PDP, product feature | Avoid fake model number/compatibility |
| Engineering Comparison | Side-by-side real attributes, differences, evidence | PDP & comparison | Only compare like-for-like verified specs |
| Catalog Filter Rail | Faceted filter + structured grid + query state | Catalog/Category/Search | Don't overdesign filters; preserve discoverability |
| Layered Gallery | Feature image with ordered variants and fullscreen | PDP, project portfolio | Must be keyboard/touch accessible |
| Bento Instrument | Unequal functional panels and compact data | ZCRM dashboard, admin widgets | No decorative useless cards |
| Workspace Split | List/details/resizable or stacked mobile panel | CRM/messages | Keep keyboard, permission and loading states |

### 4. Signature experiences
| Pattern | Composition | Best use | Hazard |
|---|---|---|---|
| Spatial Energy 2.5D | Layered SVG/CSS house/battery/energy paths | Solutions interactive explainer | Must be labeled 2.5D, not WebGL |
| Real 3D Stage | WebGL geometry, lighting, camera, controls, inspector | One signature Energy Lab / selected page | Needs fallback, performance budget and validated energy behavior |
| Cinematic Sequence | Chapter-based media/storyline, controlled reveals | Project story, special presentation | Avoid hijacking scroll and hiding text until animation |
| Scroll-linked Chapter | Media changes by document progress | Educational tutorial, product internals | Mobile and reduced-motion must remain functional |
| Contrast Media Wall | Light editorial content next to a deliberate dark media pocket | About/solution | Don't paint everything navy or introduce gratuitous gradients |

## B. Motion/effect catalog — effects must serve information
| Effect | Tier | Preferred tool | Outcome and caveat |
|---|---|---|---|
| Transparent header -> solid on scroll | UI | CSS/observer | Sense of immersion; per-route opt-in, contrast checked |
| Hover focus/underline/indicator | UI | CSS / Motion-v | Affordance, ~120–240ms, don't animate layout |
| Gallery image crossfade | UI | Motion-v | Confirm selected content changes |
| Shared-element transitions | UI+ | Motion-v layoutId | Connect thumbnails to detail; stable keys |
| Modal/lightbox/sheet | UI | Nuxt UI + Motion-v | Reveal detail; keyboard/focus essential |
| Image clip-path reveal | Story | CSS/Motion-v | Introduce hero image; avoid masking SEO copy |
| Staggered text with restraint | Story | Motion-v | One deliberate moment, not every heading |
| Subtle image parallax | Story | Motion-v/useScroll | Depth; keep small amplitude and fallback |
| Sticky chapter progress | Story | CSS + observer / Motion-v | Reading orientation in long story |
| Horizontal gallery rail | Story | native scroll-snap | Touch-first media browse; no wheel hijack |
| Before/after media slider | Utility | input/drag component | Real comparison; keyboard alternative |
| Tabbed technical inspector | Utility | Vue + Motion-v | Show real topology/component data |
| Day/Night/Backup energy flow | System | SVG/Motion-v | Teach topology only; no invented telemetry |
| Scroll scrubbed cinematic chapter | Special | GSAP ScrollTrigger when needed | Multiple synchronized events, pin only if justified |
| Video-on-scroll / image sequence | Special | GSAP/requestAnimationFrame | Heavy; respect reduced motion and mobile data |
| Shader/image distortion | Experimental | Three.js custom shader | High-risk design novelty; avoid on core storefront |
| Real 3D house/system explorer | Signature | TresJS / Three.js | Only if story/information justify GPU cost |
| Magnetic cursor, smooth scroll takeover | Avoid default | none | Often harms usability/low-end performance |

## C. Four tool tiers (don't install all as runtime dependencies)
- **Tier 0** HTML/CSS + Nuxt UI + Lucide. Already present for almost all layout and functional UX.
- **Tier 1** Motion for Vue \`motion-v\`: already in Nuxt package.json. Prefer for interaction states, shared element, layout and modest scroll motion.
- **Tier 2** GSAP/ScrollTrigger: add as scoped dependency **only** when the selected scene needs multi-step synchronized scroll timeline. Not needed for every page.
- **Tier 3** TresJS + Three.js: add ONLY to an approved experimental page or feature branch for true interactive 3D; use a static diagram fallback. Do not register global 3D runtime for all routes.
- **Note:** installing an agent *skill* does NOT install GSAP/TresJS or create a live 3D scene.

## D. Agent skill map (globally installed on PC WSL)
- Anthropic \`frontend-design\` (Claude existing; installed for Codex)
- Nextlevelbuilder \`ui-ux-pro-max\` for searchable styles/palettes/fonts/GSAP presets
- Nextlevelbuilder \`design-system\` for tokens and components
- Nextlevelbuilder \`brand\` for brand continuity
- Nextlevelbuilder \`banner-design\` for hero media/banner composition
- Vercel \`web-design-guidelines\` for accessibility / implementation critique
- Project-local \`layout-direction-selector\` (this repo) for selection workflow; can be globally installed for both agents.
Skills are starting knowledge, **not approvals**. One design lead creates alternatives, owner chooses, implementer builds, independent reviewer evaluates actual render.

## E. Recommended combinations (max 1 hero + 1–2 supporting patterns + 1 special effect)
| Page | Hero | Supporting patterns | Effects |
|---|---|---|---|
| Home | Existing approved hero, optional selective Full-bleed section | Precision Paper + Product Atelier | Subtle transition, Energy insert only with approval |
| About | Editorial Split or Architectural Frame | Field Journal + Evidence Narrative | Image reveal + chapter progress |
| Solutions | Spatial Energy or Editorial Split | Connected Journey + Technical Blueprint | Energy mode switch + optional inspector |
| Projects | Cinematic Cover or Full-bleed when authentic media exists | Field Journal + Evidence Narrative | Sticky chapter + gallery lightbox |
| Catalog | Compact Typographic/Technical intro | Catalog Filter Rail + Product Atelier | Filter state transitions, native gallery |
| PDP | Product Atelier | Engineering Comparison + Layered Gallery | Shared image state, tabbed specs |
| Knowledge | Precision Paper | Magazine Mosaic / Field Journal | TOC progress, tiny reveal |
| Dealer/Contact | Architectural Frame or Precision Paper | Connected Journey / form | Input focus and form success; no 3D |
| ZCRM | Workspace Split | Bento Instrument | Functional disclosure and layout transitions |

## F. Selection decision
1. Pick the **page and user job**, real content inventory and one dominant composition.
2. Choose max 2–3 effects that clarify behavior, not decoration.
3. Preview 2–3 genuinely different compositions **only if a direction is not selected**. Do not do three color variants.
4. Owner approves a visual reference/mock or prototype with responsive 1440px and 390px images.
5. Create GitHub Issue from picker export. Design contract -> code -> browser screenshot -> critique -> changes -> PR -> integration preview -> acceptance.
6. Do not claim DONE from a generated plan, CSS file, typecheck PASS or HTTP 200 alone.

## G. Design references
- [Godly curated design](https://godly.design/sites/) — modern compositions
- [Awwwards](https://www.awwwards.com/) — experimental and cinematic
- [Codrops](https://tympanus.net/codrops/) — interactive experiments, code approaches
- [Motion for Vue](https://motion.dev/docs/vue) — production Vue interaction
- [GSAP ScrollTrigger](https://gsap.com/docs/v3/Plugins/ScrollTrigger/) — advanced timelines
- [TresJS Nuxt](https://docs.tresjs.org/getting-started/installation) — native Vue/Nuxt 3D integration
- [Vercel web design skill](https://github.com/vercel-labs/agent-skills) — audits

## H. Upgrade rule
New patterns belong in this *catalog* first; only approved directions become implementation Issues. A new style does not warrant a new branch until chosen for an outcome. Existing reference labs and approved production components are preserved.
