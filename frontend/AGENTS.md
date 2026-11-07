# Frontend AI contract — outcome-driven design delivery

This nested file supplements, and does not supersede, root AGENTS.md and the approved PTS business/data/security rules.

For changes to PTS public frontend, before coding read:
0. `docs/02-development/nuxt-storefront-guide.md` — where code lives, checks, owner-approved UI rules (section 4) and known traps (section 5); and `docs/11-workflows/owner-feedback-loop.md` for the working loop.
1. `docs/15-design-delivery/00-START-HERE.md` — authority and delivery flow.
2. `docs/15-design-delivery/12-PTS-DESIGN-LANGUAGE-2026-11.md` — current visual language, tokens, compositions and motion.
3. `docs/02-development/public-ui-ux-design-system.md` — durable product, data, URL/SEO, responsive, media and accessibility contracts. Its older visual prescriptions are superseded by document 12.
4. `docs/15-design-delivery/03-VISUAL-ACCEPTANCE.md` — visual evidence and acceptance.

MIGRATION PARITY issue: preserve the PHP visual baseline.
CREATIVE REDESIGN issue: use the selected direction in the task or current approved design language. Keep functional/data/SEO/commerce invariants; visual deviation is expected when the brief calls for redesign and must be reviewed against before/after evidence. A noindex prototype is only required when the task is research/prototyping rather than implementing a scoped route.
Homepage/header/footer content and business paths are protected from accidental removal. Redesign their presentation only when the task includes that scope.
Use real CMS/approved media and avoid fabricated badges, reviews, projects or statistics.

For all visible UI: baseline screenshot -> selected direction -> code -> live preview -> scroll/lazy-media screenshot -> critique -> iterate -> PR -> integration preview -> owner/design review.
Check at 320/390/768/1440, focus/reduced-motion, empty/loading/error, overlays, mobile CTA and real media.
Build/typecheck PASS is necessary but not evidence of beautiful design.
Do not open another task simply to fix the same PR. Work until accepted, then archive worktree safely.
