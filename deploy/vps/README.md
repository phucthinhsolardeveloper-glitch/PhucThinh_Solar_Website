# Bộ triển khai VPS Phúc Thịnh Solar

Hạ tầng chuẩn cho Ubuntu 24.04, dùng được cho CloudFly hiện tại và lần chuyển
VPS sau. Runbook đầy đủ: `docs/06-operations/vps-cloudfly-runbook.md`.
Hướng dẫn dành cho chủ hệ thống, gồm cả vận hành hằng ngày và cứu hộ:
`docs/06-operations/owner-system-handbook.md`.

Topology dưới đây là cấu hình cutover đã chuẩn bị trong source. Máy production
chưa được deploy cấu hình này; trạng thái trên máy chỉ đổi sau release được review.

```text
Internet -> Cloud Firewall -> Caddy :80/:443 -> Nuxt SSR -> PHP 8.1 -> MariaDB 10.11
                                                  |          |
                                              public UI    Admin/API/media
```

- Chỉ proxy public cổng 80/443; MariaDB không publish cổng host.
- Giao diện public GET và SSR thuộc Nuxt; Caddy không fallback page, CSS hoặc JS
  public sang PHP. PHP giữ Admin, API nghiệp vụ, session, media transform và
  callback thanh toán.
- Release build hai Docker image bất biến (PHP + Nuxt), cập nhật Compose/Caddy
  cùng release và rollback cả hai image lẫn cấu hình.
- DB, upload, cache và secrets nằm ngoài image release.
- Full backup mỗi ngày; DB backup mỗi 6 giờ; có restore rehearsal độc lập.
- Migration database không chạy tự động cùng deploy code.

Các script cố ý không tự đổi SSH, Cloud Firewall hay DNS. Ba thao tác đó có
thể làm mất kết nối và phải được xác nhận bằng phiên quản trị thứ hai.
