# Debugging workflow

1. Ghi evidence tối thiểu: URL/method/input/viewport/time, user role/session
   state và request/call ID nếu có; không ghi secret, cookie, token hoặc PII.
2. Reproduce trên local/staging cô lập trước; nếu không reproduce được, ghi
   `NEEDS VERIFICATION`, không đoán nguyên nhân.
3. Xác định layer: browser → router/.htaccess → source/template/API →
   middleware/auth → DB/cache/storage/provider.
4. So expected với code, contract, schema và runtime; tạo minimal failing
   test/query, không tin comment hoặc workaround cũ.
5. Phân loại `PRE_EXISTING`, `REGRESSION`, `ENVIRONMENT`, `REAL_BUG` hoặc
   `UNKNOWN`; sửa ở owner module và giữ scope nhỏ nhất.
6. Chạy regression cho success, failure, permission, empty/invalid input và
   side effect; kiểm log/DB/cache không có ghi ngoài ý muốn.
7. Review diff, cập nhật docs/known issue và ghi rollback point.

Không bật `display_errors` trên production và không copy log chứa PII/secrets
vào issue/chat. Nếu bug liên quan data, dùng bản clone tối thiểu đã ẩn danh.
