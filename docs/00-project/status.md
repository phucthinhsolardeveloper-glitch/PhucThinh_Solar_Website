# Current status

## WORKING

- Production homepage/category HTTP 200; server-rendered routing/templates/SEO.
- Product, news, project, static and contact data paths.

## PARTIAL

- Commerce, reviews, member và RBAC có code/schema nhưng các cờ CMS tương ứng đang tắt hoặc chưa smoke test.
- Filter chỉ có brand/sort; backup có snapshot bàn giao nhưng chưa biết schedule/restore.

## BROKEN / inconsistent

- Menu admin tham chiếu `tom-tat-gioi-thieu` trong khi module bị comment, có nguy cơ undefined/null offset.
- Dump có dữ liệu mẫu/legacy và sản phẩm nhân bản; cần data audit.
- `index.html` là placeholder domain khác nhưng `DirectoryIndex index.php` đang che nó.

## MISSING

- App tests, CI/CD, migrations, `.env.example`, health check, deploy/rollback runbook, thank-you page.

## NEEDS VERIFICATION

- PHP extensions/ini, SMTP/reCAPTCHA, system cron, module usage, off-server backup, legacy assets.
