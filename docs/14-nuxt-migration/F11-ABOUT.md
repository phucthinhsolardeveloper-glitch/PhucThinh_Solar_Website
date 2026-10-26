# F11 About — implementation handoff

## Scope

F11 adds a read-only PHP projection and an internal Nuxt SSR preview. It does
not claim or switch the public `/gioi-thieu/` route.

Ownership is limited to:

- `api/pts_v1_about.php`
- `libraries/pts_public_about_api.php`
- `scripts/test-pts-public-about-api.php`
- `frontend/shared/contracts/public-about.ts`
- `frontend/server/routes/_migration/about.get.ts`
- `frontend/app/pages/migration-about-preview.vue`
- `frontend/app/components/about/AboutPreviewPage.vue`
- `frontend/app/assets/css/about-preview.css`
- `frontend/tests/about-contract.test.mjs`

## Data contract

The PHP projection reads only published CMS rows used by
`sources/about.php`: `lich-su`, `su-menh`, `ly-do-chon-chung-toi`,
`about-team`, `about-principle`, `chungnhan`, `about-cta`, plus the existing
`adv` image fallback used for the team image. The legacy static blocks
`tom-tat-gioi-thieu` and static `su-menh` are not queried.

The DTO is plain text and safe media paths only. CKEditor HTML is stripped
before transport; no public price, form, CSRF flow, partner/certificate claim,
person statistic, or timeline year is invented. Invalid or missing images are
represented by an empty string, while sections follow the PHP render guards.
The CTA fallback remains copy-only when PHP would target `#ptsContactModal`,
because this preview does not implement a form or mutation flow.

## Verification

- `node --test frontend/tests/*.test.mjs`: PASS (8 files).
- PHP lint/helper test: not run; PHP is unavailable in this sandbox.
- PHP Docker/live CMS, Nuxt build, browser QA, deployment and public cutover:
  coordinator-owned and intentionally not run in this slice.

## Rollback

Remove the F11-owned files listed above, or revert the F11 commit after the
coordinator records its commit SHA. No production route, database, upload,
session, or deployment state was changed.
