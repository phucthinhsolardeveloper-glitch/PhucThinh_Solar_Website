# Layout Lab — browser QA and visual review
Date: 2026-11-03
Target: docs/15-design-delivery/layout-lab.html (reference-only).

## Functional/browser result
PASS in Chromium via Playwright for 5 viewport widths: 320, 390, 768, 1440 and 1920.
For each width, tested three layouts: immersive/bleed, editorial/split, architectural/frame = **15 combinations**.
At all 15: 0 horizontal overflow; hero image decoded to naturalWidth=1586; 1 H1, expected active layout state.
Other verified interactions: gallery switching, aria-pressed updates, lightbox opening, Escape closes, mobile menu expanded/collapsed, shade range value, header auto-solid after scrolling, header solid manual control, motion toggle. No JS console/page errors in the automated check.
Reduced-motion was used for multi-viewport screenshot collection. In no-preference browser, motion toggle was tested manually through state assertions.

## Screenshot evidence
- evidence/layout-bleed-1440.jpg + layout-bleed-390.jpg
- evidence/layout-editorial-1440.jpg + layout-editorial-390.jpg
- evidence/layout-frame-1440.jpg + layout-frame-390.jpg
Screenshots are full-page JPEG at fixed viewport widths and the same local image fixtures. The reviewer also examined first-fold screenshots of each desktop layout and the bleed mobile first fold.

## Visual interpretation
- Bleed: photograph genuinely runs behind transparent header. White hero text remains visibly separated from media by left/top/bottom dark overlays, and hierarchy is strong.
- Editorial: calm white reading canvas with image on right; desktop header is on a solid surface, no image-under-text confusion.
- Frame: visually distinct window-like media presentation; sensible balance with text.
- Mobile bleed: headline, action and header overlay are legible; composition remains image-led but navigation fits without overflow.
- Gallery/typography and section rhythm are still design *study*, not application parity and not a claim of production visual acceptance.
- Product/engineering content intentionally uses explanatory labels rather than fabricated project facts.

## Finding and repair
First QA found horizontal overflow 65px at 320px. Cause was a 4:3 media box with min-height:280px whose min-width had to exceed the 320px viewport's content width. Replaced with width:100%, min-height:0 and aspect-ratio:4/3 on mobile. Re-ran all 15 layout/viewport combinations: PASS.

## Pending before any production adoption
Contrast measurement against final approved images; user review of visual direction; test on physical phones and keyboard/screen readers; real CMS media, loading/failure state QA; actual site-header variant design and integration regression; performance and Core Web Vitals measurements. No full 3D/WebGL is included in this lab.

## Re-run the review
On a machine with the project's Playwright dependency installed:
`node docs/15-design-delivery/qa-layout-lab.cjs`
The script checks 320,390,768,1440,1920 at all three hero layouts, tests interaction states and regenerates six full-page JPG screenshots after warming lazy images by scrolling. It is a reference smoke, not a production acceptance suite.
