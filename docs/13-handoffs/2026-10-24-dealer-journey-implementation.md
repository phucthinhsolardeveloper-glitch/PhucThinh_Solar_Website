# Dealer UI first pass — QA-corrected 24/10/2026

## Accepted changes
- Nav order now reflects the visitor decision journey: Sản phẩm → Quyền lợi → Đăng ký; targets remain the existing anchor IDs.
- Added visible keyboard focus to dealer nav and form fields. All original data-driven content, full-width support band, form order, fields, CSRF and reCAPTCHA remain unchanged.
- Mobile keeps the registration form near the first fold so prospects can contact sales without a multi-screen scroll. No new business copy/claim is introduced.

## Rejected implementation after real browser simulation
The first agent patch moved pts-dl-support inside pts-dl-confidence-layout and the form to the bottom. On 390px public-content preview, form top moved from ~396px to ~5790px. At 1440px, support text/cards spilled beyond the left column into the registration card. This variant was reverted before commit. Evidence: artifacts/dealer-review-20261024 screenshots and review.json. Those after images document the rejected variant, not the final accepted changes.

## Content blockers
Hero's market opportunity wording, figures from 2021, and the 2021–2025 national program require Sales/Marketing approval. Do not invent replacement evidence. A stronger B2B proof-first design is blocked until those assets/copy are verified.

## Checks and delivery
Static source diff and node --check can run without PHP. Full PHP/local Playwright rendering with database fixtures still pending; do not claim release-ready. Do not merge or deploy prior to that QA.
