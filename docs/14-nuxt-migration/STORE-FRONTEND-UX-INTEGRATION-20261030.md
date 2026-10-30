# Nuxt storefront UX integration — 2026-10-30

## Integrated changes

- Consultation and dealer dialogs share a global Nuxt UI modal controller and
  use the PHP lead form security metadata and same-origin PHP mutation route.
- The homepage `Đăng ký làm đại lý` action opens the dealer dialog; the separate
  `Tư vấn chính sách đại lý` action keeps its existing contact-form path.
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
  PHP rate limit. The standalone Nuxt service on `:3101` does not route
  `/api/pts_v1_lead_form.php`; the local full-site front door on `:8091` routes
  that path to PHP and other pages to the Nuxt service. That Caddy container is
  a separate local rehearsal process, not part of `docker-compose.nuxt-service.yml`.
- Gemini's summary said the scrollspy uses `IntersectionObserver`; the actual
  implementation uses scroll events and `requestAnimationFrame`.
- Browser geometry was checked at mobile 320px and desktop/tablet breakpoints;
  social links reserve space for the floating contact controls. The header and
  document had no horizontal overflow at the tested widths.

The integration retains the existing PHP backend as the authority for lead
validation, CSRF, reCAPTCHA, rate limiting, persistence and email delivery.
No production files, services, DNS, database rows or persistent data were
changed during this integration.
