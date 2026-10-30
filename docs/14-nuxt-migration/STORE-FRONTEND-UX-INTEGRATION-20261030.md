# Nuxt storefront UX integration — 2026-10-30

## Integrated changes

- Consultation and dealer dialogs share a global Nuxt UI modal controller and
  use the PHP lead form security metadata and same-origin PHP mutation route.
- Dealer registration submits the selected CMS city ID. PHP validates that ID
  against an active city record and stores its canonical name. Older callers
  sending a city label remain supported only when it resolves to an active CMS
  city.
- Floating contact exposes direct Zalo and telephone links, plus a back-to-top
  action after scrolling. Zalo is taken from the validated CMS setting/link; it
  is never inferred from the hotline number. Ping animation, button transitions
  and programmatic scroll respect reduced-motion preferences.
- Editorial TOC scrollspy is shared by solutions, news and the policy preview.
  It uses passive scroll events plus `requestAnimationFrame`, offsets headings
  below the header, moves keyboard focus to the selected heading, and resumes
  tracking when the visitor scrolls by keyboard, wheel or touch.
- Scrollbar styling is limited to the existing shared CSS tokens and keeps the
  public light-mode preference.

## Verification and limits

- Frontend Node suite: 408/408 passed; Nuxt typecheck passed; production build
  passed on the integration candidate.
- PHP syntax check passed for `api/pts_v1_lead_form.php`.
- No lead form POST was sent: that operation writes a lead and consumes the
  PHP rate limit. Forms require the same-origin Caddy front door to route
  `/api/pts_v1_lead_form.php` to PHP. Opening the standalone Nuxt container on
  `:3101` does not provide that POST route; a successful GET/shell smoke test
  does not prove form submission.
- Gemini's summary said the scrollspy uses `IntersectionObserver`; the actual
  implementation uses scroll events and `requestAnimationFrame`.
- Browser-level measurement of floating-widget overlap at 320px and header
  overflow at 821px/1321px remains unverified; the source-contract tests do not
  measure rendered geometry.

The integration retains the existing PHP backend as the authority for lead
validation, CSRF, reCAPTCHA, rate limiting, persistence and email delivery.
No production files, services, DNS, database rows or persistent data were
changed during this integration.
