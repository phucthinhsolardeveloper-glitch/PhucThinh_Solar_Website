# Testing

Không tìm thấy app unit/integration/E2E tests hoặc CI. Test files hiện diện chỉ thuộc vendored libraries.

- Production homepage/category HTTP: PASS.
- Public sensitive URL checks: REAL_BUG (HTTP 200).
- PHP lint: ENVIRONMENT_FAILURE do thiếu PHP CLI.
- CMS authenticated flow, contact/order email, member flow: NEEDS VERIFICATION.

Baseline đề xuất: syntax check; HTTP smoke; form success/failure/duplicate; auth/permission; category -> product -> cart -> checkout -> confirmation; responsive; SEO; backup/rollback rehearsal trên staging.
