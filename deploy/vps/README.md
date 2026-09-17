# Bộ triển khai VPS Phúc Thịnh Solar

Hạ tầng chuẩn cho Ubuntu 24.04, dùng được cho CloudFly hiện tại và lần chuyển
VPS sau. Runbook đầy đủ: `docs/06-operations/vps-cloudfly-runbook.md`.

```text
Internet -> Cloud Firewall -> Caddy :80/:443 -> Apache/PHP 7.4 -> MariaDB 10.11
                                         |             |
                                    shared media    private network
```

- Chỉ proxy public cổng 80/443; MariaDB không publish cổng host.
- Release code là Docker image bất biến và rollback bằng image trước.
- DB, upload, cache và secrets nằm ngoài image release.
- Full backup mỗi ngày; DB backup mỗi 6 giờ; có restore rehearsal độc lập.
- Migration database không chạy tự động cùng deploy code.

Các script cố ý không tự đổi SSH, Cloud Firewall hay DNS. Ba thao tác đó có
thể làm mất kết nối và phải được xác nhận bằng phiên quản trị thứ hai.
