# Nhãn "Mới" trong Admin

Tính năng mới thêm vào Admin hiện một nhãn nhỏ **Mới** lần đầu mỗi tài khoản nhìn thấy. Nhãn mất khi tài khoản đó mở trang chứa tính năng,
hoặc sau 45 ngày nếu không bao giờ mở. Trạng thái "đã thấy" lưu theo từng tài khoản (`users.seen_features`), nên người khác vẫn thấy nhãn của họ.

## Thêm nhãn cho tính năng mới

1. Mở `backend/app/Filament/Support/NewFeatures.php`, thêm một dòng vào `FEATURES`: khóa, ngày ra mắt (`since`) và mẫu đường dẫn Admin mà việc
   mở trang đó được tính là "đã thấy" (`visit`).
2. Đặt nhãn ở nơi người dùng nhìn:
   - Mục menu: trong Resource/Page thêm `getNavigationBadge()` trả `NewFeatures::badge('khoa')` và `getNavigationBadgeColor()` trả `'warning'`.
   - Ô nhập hoặc cột: `->label(NewFeatures::label('Tên hiển thị', 'khoa'))`.
3. Không cần sửa gì khác: middleware `MarkNewFeaturesSeen` ghi nhận lượt mở trang sau khi trang đã dựng xong, nên nhãn vẫn hiện trọn lần mở đầu tiên.

Đang dùng: `kho-chi-nhanh`, `kho-san-pham`, `kho-don-hang`, `mau-email` (ra mắt 19/11/2026).
