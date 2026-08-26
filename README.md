# Phúc Thịnh Solar — PHP website handover

Bản bàn giao của `phucthinhsolar.com`: storefront PHP, CMS riêng, MariaDB và backup DirectAdmin.

## Trạng thái nhanh

- Entry: `domains/phucthinhsolar.com/public_html/index.php`; CMS: `public_html/administrator/`.
- PHP server-rendered tự xây dựng, không phải WordPress. Stack production được bàn giao: PHP 7.4.29, MariaDB 10.4.24, DirectAdmin.
- Không có test ứng dụng, CI/CD, Docker, migration framework hoặc Git metadata.
- P0: SQL dump, archive và log hiện tải công khai từ webroot. Xem `docs/10-audits/initial-handover-audit.md`.

```text
HTTP -> .htaccess -> index.php -> router.php -> sources/*.php
     -> PDODb/MariaDB -> templates/*_tpl.php -> HTML
```

## Bắt đầu

Đọc `AGENTS.md`, `docs/README.md`, `docs/00-project/fact-map.md`, rồi `docs/10-audits/initial-handover-audit.md`. Local setup chưa được chạy vì môi trường khảo sát thiếu PHP CLI/database; xem `docs/02-development/local-setup.md`.
