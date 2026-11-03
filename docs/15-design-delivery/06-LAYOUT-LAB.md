# PTS Layout Lab — patterns, interaction recipes and scope
Status: DESIGN REFERENCE — not owner-approved sitewide redesign; no production header/footer changes.
Updated 2026-11-03.

## Open
- Offline: open docs/15-design-delivery/layout-lab.html in a browser on the company PC.
- Assets, CSS and JS are local to this folder. The demo does not call an API or use Vue, CDN, cookie or telemetry.
- Compare it with docs/15-design-delivery/reference.html, which shows the separate energy 2.5D reference.

## 1. Three contrastive hero compositions
| Pattern | Image/header behavior | Best fit | Do not use |
|---|---|---|---|
| Immersive / Full-bleed | Responsive photo fills the hero *behind* the transparent site header; strong top/left and bottom gradient, white text, optional solid header and auto-solid when scrolled below hero | Approved special campaign section or a dramatic project/solution page | As a global header replacement, for every route, or when there is no trustworthy media |
| Editorial Split | Calm light canvas on left; framed photographic half on right; solid navy-on-white header | About, solutions, technical overview, field editorial | Dense catalog results, checkout and long text over live footage |
| Architectural Frame | Open light canvas and a purposeful image window with a geometric boundary; solid header | Project cases, knowledge visuals, product context, engineering documentation | When the frame crops critical data, or when picture quality is insufficient |

The same H1, summary and action are deliberately retained when switching directions so reviewers compare composition rather than copywriting.

## 2. Components and layout grammar
- Website header is a VISUAL REFERENCE, not the actual shared SiteHeader. Moving it above the image requires an explicitly approved page-level special mode, reliable contrast and a fallback; do not redesign the production header.
- Use one heading owner (one H1), one gutter owner, real information architecture and a next step.
- Full-bleed is a deliberate composition break. Return to a quiet light reading surface on the following section.
- Editorial media can expand to the edge of its column, but supporting data must still read well at 320px.
- Architectural frames must have a predictable intrinsic aspect ratio and focal position.
- Media fallbacks must be visible and purposeful; do not leave card-sized blank holes.
- Avoid all-caps fake eyebrows, decorative metrics, matching template cards and scroll-hijacking.

## 3. Interaction recipes already working in layout-lab.js
| Demo control | Behavior | Production adaptation |
|---|---|---|
| 3 layout switches | Keep content constant; change only structure, imagery and CSS responsive rules | Design review comparison, not a public-site theme switch |
| Header transparent/solid toggle | Demonstrate contrast over bright/dark source images; auto-solid when hero exits | Per approved hero composition, with measured contrast and focus |
| Motion toggle | Turn nonessential transitions off; respect reduced motion OS setting | Use existing Motion for Vue and avoid universal scroll reveals |
| Media gallery switcher | Change media, title, description, image alt and aria-pressed state | Drive from real Admin media, not hardcoded demo records |
| Image lightbox | Native dialog; Escape/backdrop/close, focus handling provided by browser | Use Nuxt UI dialog where app needs shared overlay/scroll locking |
| Mobile menu | Toggle aria-expanded; close after navigation or Escape | Preserve real public-site navigation and accessible disclosure |
| Shade experiment | Adjust overlay darkness from 58%–92% | Review aid only: production requires image-specific WCAG contrast proof |

## 4. Asset provenance and safety
assets/hero-rooftop.webp, hero-solar-farm.webp, hero-home.jpg, story-inverter.webp and story-lighting.webp are COPIES from frontend/public/assets/images/site-visuals and pdp-usecase-v2 in the user's own repository, intended only as visual examples. No claim that they document real PTS clients/projects, technical capacity, equipment or savings.
Do not migrate these files as live project evidence. When implementing an actual page, use verified CMS imagery, correct crop/focal point and a robust fallback.

## 5. Conversion from reference to real Nuxt page
1. Select a single direction by owner-approved Issue decision; keep reference screenshots and decision rationale.
2. Extract *the layout* into a scoped Vue component; do not paste the entire demo site's header/footer, mock content or custom CSS reset into app.
3. Reuse Nuxt UI/PTS tokens, real DTO contracts and existing SiteHeader/SiteFooter; add an authorized overlay-header variant only if a specific route requires one.
4. Bind media, CTA, navigation, filters, forms and rich text to real CMS/API; preserve routes, SEO, loading, errors and security.
5. Use Motion for Vue for states. Add Three.js/TresJS only in a separately approved real 3D experiment.
6. Screenshot after scroll at 320,390,768,1440,1920; independently critique. Merge only when integration preview passes and owner/design reviewer approves.

## 6. Next visual studies (not automatic implementation tasks)
- Project Field Journal: high-quality photography rail, sticky chapter rail on desktop, compact anchor navigation on mobile, honest empty-data treatment.
- Product Engineering Atelier: side-by-side spec comparison from verified common attributes; responsive gallery and datasheet inspection.
- Energy Stage 3D: a physically meaningful day/night/backup topology with WebGL scene + static accessible fallback, performance budget and SME validation.
- Contact Conversion: quiet editorial entry with map, accessible form flow and transparent submit/error states.
A new exploration becomes a GitHub coding issue only after a selected visual direction, outcome and ownership.

## 7. Design-specific acceptance
The page must be better in a concrete way: clarity, aesthetic rhythm, information hierarchy, mobile task completion, media fidelity and measured interaction. Passing typecheck, 200 status and browser QA does not by itself approve visual quality.
See 03-VISUAL-ACCEPTANCE.md and the screenshot evidence in evidence/.
