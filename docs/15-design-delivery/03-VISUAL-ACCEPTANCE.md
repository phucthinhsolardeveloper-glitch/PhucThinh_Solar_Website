# DESIGN REVIEW, QA & RELEASE GATES — v1

## 1. Design acceptance is a separate gate
Compiling source, 200 responses, CI PASS, a good Lighthouse result and pretty screenshots DO NOT individually imply that UI quality is sufficient. A human owner or explicitly delegated design reviewer must accept the visual direction.

## 2. Gate D0 — brief (before code)
- Name target screen/route and primary user task.
- Show existing screenshots of current experience and real content/asset inventory.
- Choose direction from 02-PTS-VISUAL-LANGUAGE. Multiple concepts only if no choice has been made.
- Confirm allowed visual deviations, protected shell and data contracts.
- Output: a selected direction with 3–5 visual rules and 2 rejection conditions.

## 3. Gate D1 — working first fold
- Browser URL serves a rendered usable screen with real data or clearly labeled fixture.
- Check hero/composition at 1440px and 390px with viewport capture (not only full-page).
- Hierarchy, brand, photography/illustration, CTA, contrast and no invented labels.
- Developer and designer have independently commented on latest screenshot.
- Output: first fold design approved to continue; otherwise one scoped correction.

## 4. Gate D2 — complete journey
- Capture first fold, mid-scroll and final CTA at 320/390/768/1440; add 1920 for public pages.
- Exercise real scrolling to force lazy images and deferred animation; wait for image complete or report failures.
- Inspect 0/1/many records, empty/error/loading states, text wrapping and real CMS content.
- Check mobile overlays: hotline, sticky CTA, filter drawer, forms, safe-area, focus order.
- Functional: navigation, query filters, modals, product links, existing price mode and form behaviors.
- Technical: zero unexpected console/page error, no horizontal overflow, typecheck and targeted tests; no skipped key checks.
- Performance: collect practical load/interaction findings, do not declare real-user Core Web Vitals from unit tests.
- A11y: keyboard, visible focus, labels, target size, semantic headings, contrast and reduced motion.
- Output: screenshot artifacts named route, viewport, state and commit SHA.

## 5. Gate D3 — integration
- Branch reviewed for scope and safety. No accidental global CSS, Caddy, API schema, data mutation or production edits.
- Integration preview loads the ACTUAL merged output, not only isolated worktree.
- Visual compare across header, footer, neighboring page patterns and responsive matrix.
- Re-run relevant tests after merge. Maintain rollback point.
- PR reviewer explicitly marks APPROVE/CHANGES REQUESTED/REJECT and explains why.
- Task only DONE after integration, linked issue and cleanup decision recorded.

## 6. Weighted quality rubric (100 points, internal comparison only)
30 — Visual hierarchy, proportion, composition originality and responsive aesthetic.
20 — Correct user journey, clear next action and content usefulness.
15 — Brand consistency and typography/layout system coherence.
15 — Real CMS fidelity, media provenance, technical evidence and safe empty states.
10 — Interaction, motion purpose, keyboard and a11y.
10 — Performance, rendering stability, no overflow/regressions.
A practical target is >=85 with NO critical defect; numerical score never overrules a severe UX or functional failure. A design reviewer must provide concrete evidence, not only adjectives.

## 7. Immediate rejection conditions
- Missing/broken meaningful image, or lazy image not verified after scroll.
- Empty tall placeholder silently occupies the place of project images.
- CTA/hotline obscures text or submit button on mobile.
- Content invented or factually unsupported.
- A long scroll section is blank, repetitive, unreadable or visually detached.
- Horizontal overflow or inaccessible primary interaction.
- Existing functional path/SEO/commerce breaks.
- Prototype uses mock screenshots but is presented as real finished route.
- Three design options differ only in colors or shadows.
- A 3D claim is based only on gradients/SVG/CSS without honest labeling.

## 8. Screenshot discipline
Capture at fixed viewport with consistent browser locale (Vietnamese, disable auto translation), fresh session, same data and scroll position.
Do NOT compare full-page images when lazy media are never scrolled into view. Use staged scroll to warm media then capture.
For every screenshot give: route; branch; SHA; timestamp; viewport; data source (CMS / fixture); state and errors. Distinguish after-implementation renders from concept mockups.

## 9. Review worksheet
Feature / Issue:
Selected direction:
Baseline screenshot:
Latest preview and SHA:
Design score (30):
User journey score (20):
Brand score (15):
Factual/media score (15):
Interaction/a11y score (10):
Performance score (10):
P0/P1 issues (screenshots, exact affected regions):
Next change (single coherent batch):
Decision: APPROVE / REVISE / REJECT
Integrator / target branch:
Issue closure and worktree archive decision:

## 10. No 'false green'
If an isolated preview is HTTP 200 but master preview is 502, mark isolated PASS and integration BLOCKED.
If unit tests pass but screenshot contains empty cards, mark functional PASS and visual FAIL/PENDING.
If owner has approved aesthetic but backend fixture is missing, mark visual APPROVED and data/integration PENDING.
