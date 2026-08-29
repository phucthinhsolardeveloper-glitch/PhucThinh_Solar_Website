# Deployment

Đã xác nhận mô hình DirectAdmin account backup và webroot `domains/phucthinhsolar.com/public_html`. Không có deploy script, CI/CD, Docker, vhost/IaC hoặc release metadata: `DEPLOYMENT NEEDS VERIFICATION`.

Production được chủ project cung cấp: 2 vCPU, khoảng 3.7 GB RAM, không swap, Apache/PHP 7.4.29, MariaDB 10.4.24, SSL Let's Encrypt. Header/proxy chain cần xác minh.

Quy trình an toàn đề xuất: tạo staging subdomain/database -> clone source/upload/data -> Git baseline -> triển khai file diff + migration riêng -> smoke test -> backup production -> maintenance/cutover -> clear targeted cache -> smoke/monitor -> rollback file+DB nếu lỗi. Không upload nguyên dump staging đè production.

Checklist Go/No-Go chi tiết: [Báo cáo sẵn sàng đưa website lên hosting — 2026-08-29](hosting-readiness-2026-08-29.md).
