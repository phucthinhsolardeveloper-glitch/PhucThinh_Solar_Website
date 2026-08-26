# Initial Handover Audit

## Repository State

DirectAdmin account backup, khoảng 2.2 GB webroot, không Git/docs/test/CI. Source PHP chính nằm trong `domains/phucthinhsolar.com/public_html`.

## Architecture Summary

PHP monolith server-rendered + file-based AJAX + CMS riêng, cùng MariaDB/filesystem. Front controller/router/source/template, không service/repository layer.

## Working Areas

Production homepage/category; routing/template/SEO; catalogue/content/project/contact code paths.

## Partial Areas

Commerce, member, review, RBAC, filtering, OneSignal và backup automation đều có code hoặc artifact nhưng tắt/chưa xác minh đầy đủ.

## Broken Areas

Admin menu/config mismatch; stale foreign-domain `index.html`; data mẫu/duplicate; order success thiếu confirmation page.

## Unknown Areas

Deploy owner/runbook, backup retention/restore, web server chain, SMTP/reCAPTCHA health, module usage, legacy asset ownership.

## Security Risks

- P0: public SQL dump/archive/logs verified HTTP 200.
- P1: legacy password hash, secrets in config/data, RBAC disabled, no app-wide CSRF, dynamic SQL input.
- P2: weak SMTP TLS verification, CMS HTML/script injection surface, broad upload types.
- P3: EOL runtime/dependencies, no automated scanning.

## Data Risks

MyISAM without FK/transactions, public PII dump, local filesystem uploads, unclear off-site backup, legacy/duplicate data.

## Deployment Risks

No reproducible infrastructure/release/rollback; production appears manually managed through DirectAdmin.

## Testing Gaps

No app tests/CI. PHP lint could not run because discovery host lacks PHP CLI. Authenticated admin, mail and commerce flows remain unverified.

## Documentation Gaps

Resolved initially by this bootstrap. Runtime credentials, ownership, RTO/RPO and actual business rules still require stakeholder verification.

## Technical Debt

Global variables, mixed query/business/view responsibilities, copied dependencies, inconsistent feature flags, generic routing and HTML-fragment APIs.

## Recommended First Actions

1. Remove public access to dump/archive/logs, move outside webroot, rotate DB/admin/SMTP/API secrets and inspect access logs.
2. Create verified off-server backup and staging restore.
3. Initialize private Git baseline excluding secrets/data/uploads.
4. Fix admin config mismatch; enable RBAC only after role tests.
5. Add smoke tests and confirmation/conversion page before ads.
6. Port React design into PHP Theme V2 while preserving URLs/backend.
7. Plan PHP/library upgrade after baseline tests.
