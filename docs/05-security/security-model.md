# Security baseline

## P0

- `phucthinhsolar_com.sql`, `codientumanhdat.com.zip`, `logs/*.txt` trong webroot và production trả HTTP 200. Dump chứa PII và auth hashes.

## P1

- Admin password MD5 với salt tĩnh; credential/config secret nằm trong code/DB option.
- RBAC granular đang tắt.
- CSRF đã có cho các API Admin chính, upload, filer, xóa/lưu đơn, action xóa legacy, checkout và review; các mutation save/update legacy còn lại chưa được phủ app-wide.
- Một số SQL ghép input đã `htmlspecialchars` nhưng không bind, ví dụ brand/origin filter; blacklist `AntiSQLInjection` không thay thế parameterization.

## P2

- SMTP cho phép self-signed và tắt verify peer/name.
- HTML CMS được decode/render trực tiếp; quyền CMS compromise có thể dẫn tới stored XSS/script injection.
- Upload contact cho phép archive/office file; cần MIME/size/storage review.
- Logs public và permission file 0644 làm tăng blast radius nếu web config sai.

## P3/quality

- PHP 7.4 và nhiều library legacy đã hết vòng đời hỗ trợ.
- Không có automated security/dependency scanning.

Tìm kiếm baseline không xác nhận webshell trong vùng upload; đây không phải full penetration test. Không ghi secret/PII cụ thể vào tài liệu.
