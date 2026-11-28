# Kiểm thử hiện hành

Source 5c35860f; không còn dùng PHP 8.1/predeploy-local.sh làm cổng mặc định.

| Phạm vi | Lệnh/điểm vào |
|---|---|
| Backend behavior | php artisan test trong container local; [Feature/Unit](../../backend/tests) |
| Style / static | vendor/bin/pint --test; vendor/bin/phpstan analyse --memory-limit=1G (kiểm dependency đã cài trước) |
| API snapshots | php artisan pts:snapshot theo README; đọc command và target trước khi chạy |
| Frontend | npm test, npm run typecheck, npm run build trong frontend |
| Browser | [audit README](../../tools/visual-tests/audit/README.md); site-audit.cjs, shop-flow.cjs theo README khi Nuxt local chạy |
| Release | [build-candidate.sh](../../deploy/laravel13/build-candidate.sh), [verify-candidate.sh](../../deploy/laravel13/verify-candidate.sh), release log |

Chạy test vào DB test/local cô lập, không dùng production data. Với test tạo đơn/email/provider, kiểm sandbox/fake, không gửi thật. Test theo feature: CatalogApiTest, BankTransferTest, AdminScreensTest, AdminQuickHandlingTest, LeadSubmissionTest, CrmLeadForwardingTest, CategoryTreeEditorTest. Frontend test scripts được xác minh từ package.json.

Nghiệm thu: thay đổi payload phải qua contract + browser; quyền phải có negative test; checkout phải có duplicate, tiền lệch, trạng thái và audit; migration có backup/rollback. Ghi test thực sự chạy, không lấy số PASS của bản cũ làm bằng chứng release mới. Đợt sửa docs này không chạy full test/build.

Lịch sử: git show 5c35860f:docs/02-development/testing.md.
