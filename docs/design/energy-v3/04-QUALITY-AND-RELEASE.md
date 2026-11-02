# 04 — Quality, critique, prototype and release gates

## Visual & interaction acceptance
1. **Same brand:** compare screenshot with staging; no purple/new brand colors/font mismatch; gold CTA priority preserved.
2. **Light grid:** visible as a subtle technical rhythm on select section, not behind body text or forms; no moiré/flicker after scroll/resize.
3. **Day:** sky bright enough to communicate daylight (without low-contrast text), sun illuminates roof/PV, house is naturally lit; flow PV→inverter→load; optional battery charging only under modeled scenario.
4. **Night:** clearly different sky; PV faded; house has warm window glow; battery/grid source indicated correctly; route and explanation update.
5. **Backup:** scene visually separates grid; highlights priority load, disables invalid modes without battery or supported inverter; no guarantee that all solar systems power homes during an outage.
6. **Interaction:** mode select updates scene, path, inspector and controls together; selected node via mouse/touch/keyboard; early-click hydration and rapid switching checked.
7. **Reduced motion:** no light pulses while preserving static path arrows/captions; media fallback.
8. **Mobile:** 320/375/390/412/768; stage readable without horizontal scrolling, large controls >=44px, scene nodes accessible via fallback; no sticky CTA collision.
9. **Errors:** disabled unsupported modes, missing content, no-JS/SSR meaningful fallback, no fake telemetry or unapproved images.
10. **Architecture:** lab-only scoped CSS, no root CSS/production changes, no new dependency needed for V0.

## Quantitative QA matrix
- Playwright screenshots at 320, 390, 768, 1440 and optionally 1920; same conditions compare before/after.
- Mode/state assert `aria-pressed`/selected logic, heading/caption and scene class; screenshot difference between DAY/NIGHT/BACKUP must be visually apparent (not just a tiny text change). Quantitative image-diff threshold only after baseline review.
- Scroll width <= viewport width; no horizontal overflow.
- Browser `pageerror` zero; record failed network requests; keyboard tab order and screen-reader labels.
- `prefers-reduced-motion`: no infinite energy path animation; static education remains.
- LCP/INP/CLS: production good goals LCP p75 <=2.5s, INP p75 <=200ms, CLS p75 <=0.1; local lab measurements are proxies, not real-user-field evidence.
- Inspect SVG filters, large gradient layers, expensive effects under CPU throttle/low-end Android; avoid continuous repaint outside scene; memory after unmount.
- QA report labels VERIFIED / SOURCE-ONLY / NOT TESTED, with screenshots+commands+date.

## Concept selection rubric (100)
Visual distinction & brand scale: 25.
Functional comprehension/decision support: 20.
Technical truth: 20.
Mobile/accessibility: 15.
Performance/motion: 10.
Maintenance/reusability: 10.
>=80 is eligible for integration proposal, NOT a guarantee.

## Production release gate
Separate owner approval and scoped integration PR; audit current master and WIP before cherry-pick, update design contract; preserve canonical URLs, API contracts, SEO, CMS content, checkout, responsive. Regression QA and staged rollout with rollback; no direct VPS changes from design agents.

## Safety/stop conditions
Any unexpected edit to SiteHeader/Footer, production pages, API/PHP, deploy, master or staging: stop and report. Any false performance/energy figures: stop. Inaccessible scene on 320px: fix before promotion. Any slow/unstable animation: replace with simplified 2D or static fallback.
