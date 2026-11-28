# Authentication và RBAC hiện hành

Source 5c35860f, 26/11/2026. Admin Filament tại /admin, guard admin và Login class trong [AdminPanelProvider](../../backend/app/Providers/Filament/AdminPanelProvider.php). [AdminUserProvider](../../backend/app/Auth/AdminUserProvider.php) quản lý xác thực; [PasswordVerifier](../../backend/app/Auth/PasswordVerifier.php) hỗ trợ password_verify và kiểm tương thích hash legacy, needsUpgrade/rehashPasswordIfRequired. Không ghi secret/salt/hash vào tài liệu.

[User model](../../backend/app/Models/User.php): canAccessPanel kiểm is_active; hasPermission cho owner hoặc permission được cấp; canPerform đòi active, action có area hợp lệ, quyền area và quyền action (owner được miễn kiểm grant cụ thể). Xem constants trong User và từng Filament Resource/action, không suy quyền từ tên menu. ManualPayment kiểm orders_payment tại service. Kiểm cả backend và UI khi thay đổi quyền.

Public API không dùng chung mặc định quyền Admin: nhóm cart/checkout có session middleware; preview signed và throttle; webhook có route riêng, đọc controller trước khi sửa bảo vệ. Xem [API](../04-api/overview.md).

Test: AdminLoginTest, AdminScreensTest, AdminQuickHandlingTest, BankTransferTest; negative case inactive, thiếu area/action, URL trực tiếp, session hết hạn. Không bật bypass trên môi trường thật. Lịch sử: git show 5c35860f:docs/05-security/auth-rbac.md.
