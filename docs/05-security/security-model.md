# Security baseline

## P0

- `phucthinhsolar_com.sql`, `codientumanhdat.com.zip`, `logs/*.txt` trong webroot và production trả HTTP 200. Dump chứa PII và auth hashes.

## P1

- Admin mới dùng `password_hash()`; hash MD5 cũ chỉ còn nhánh tương thích và
  được nâng tự động sau lần đăng nhập hợp lệ đầu tiên.
- File-manager `component.php` legacy chỉ còn stub 404, không giữ credential
  hoặc implementation chết trong source; credential cũ phải được rotate ngoài
  source nếu từng dùng trên môi trường thật.
- CKFinder license không còn hard-code; production phải cấp qua constant trong
  config ngoài Git hoặc biến môi trường và không ghi giá trị vào log/artifact.
- RBAC granular đã bật trong source; vẫn phải kiểm chứng bằng tài khoản nghiệp
  vụ trên staging/hosting với `debug-developer=false`.
- CSRF đã có cho form POST Admin, controller save/delete/upload/sync/create, các API mutation đang dùng, checkout và review. Request đọc không yêu cầu token; login giữ cơ chế xác thực riêng.
- Một số SQL ghép input đã `htmlspecialchars` nhưng không bind, ví dụ brand/origin filter; blacklist `AntiSQLInjection` không thay thế parameterization.

## P2

- SMTP đã bắt buộc verify peer/name và từ chối self-signed; certificate, CA và
  gửi thư thật vẫn cần xác minh trên hosting.
- HTML CMS được decode/render trực tiếp; quyền CMS compromise có thể dẫn tới stored XSS/script injection.
- Upload contact cho phép archive/office file; cần MIME/size/storage review.
- Logs public và permission file 0644 làm tăng blast radius nếu web config sai.

## P3/quality

- PHP 7.4 và nhiều library legacy đã hết vòng đời hỗ trợ.
- Không có automated security/dependency scanning.

Tìm kiếm baseline không xác nhận webshell trong vùng upload; đây không phải full penetration test. Không ghi secret/PII cụ thể vào tài liệu.
