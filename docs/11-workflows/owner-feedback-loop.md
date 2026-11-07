# Vòng làm việc với chủ website

Cập nhật: 2026-11-07. Quy trình chuẩn cho việc giao diện: chủ website gửi ảnh chụp
kèm vài dòng, agent sửa trên local, chỉ deploy khi được bảo. Kiến thức kỹ thuật nằm ở
[nuxt-storefront-guide.md](../02-development/nuxt-storefront-guide.md).

## 1. Một vòng sửa

| Bước | Làm gì | Không làm |
|---|---|---|
| Nhận | Xác định trang, độ rộng màn hình, trạng thái trong ảnh. "Kiểm tra tương tự" nghĩa là rà toàn site cùng loại lỗi | Đoán từ một ảnh rồi sửa đúng một chỗ |
| Tái hiện | Viết hoặc mở rộng script trong `tools/visual-tests/audit/` để đo ra lỗi | Kết luận chỉ bằng đọc code |
| Sửa | Sửa tận gốc ở chỗ dùng chung, rồi rà các component cùng kiểu | Vá riêng từng trang |
| Kiểm | Typecheck, `npm test`, build lại preview 3100, script vừa viết, `site-audit`, `test:nuxt` | Báo xong khi chưa build lại preview |
| Báo | Tiếng Việt, ngắn: đã làm, đã đo, chưa kiểm được gì, cần chủ quyết gì | Gọi việc là xong khi chưa nhìn ảnh chụp thật |
| Ghi | Lưu kiến thức đúng chỗ, xem mục 3 | Để kiến thức chỉ nằm trong cuộc trò chuyện |

Tin nhắn mới đến khi đang chạy test: sửa mã nguồn được, nhưng chờ bộ test chạy xong
mới build lại, để không hỏng kết quả.

## 2. Ranh giới không vượt

- Không deploy, không sửa dữ liệu production, không đổi thông tin đăng nhập khi
  chủ website chưa nói rõ.
- Không gửi form, đơn hàng hay yêu cầu thật khi test. Script chỉ mở và đo.
- Không tự bịa số liệu, dự án hay dữ kiện công ty. Bản đọc giọng nói của chủ website
  có thể sai chữ: sửa chính tả được, còn chỗ đổi nghĩa phải liệt kê để chủ xác nhận.
- Không xóa script đo. Script mới để trong `tools/visual-tests/audit/` và thêm một
  dòng vào README ở đó.

## 3. Kiến thức lưu ở đâu

| Loại | Nơi lưu |
|---|---|
| Quy tắc giao diện chủ website vừa chốt | Mục 4 của `nuxt-storefront-guide.md` |
| Cạm bẫy kỹ thuật đã gặp | Mục 5 của `nuxt-storefront-guide.md` |
| Script đo mới | `tools/visual-tests/audit/` và README trong đó |
| Lần deploy, kể cả lần dừng giữa chừng | `docs/06-operations/deployment-log.md` |
| Đợt rà tài liệu | Lịch sử trong `docs/06-operations/documentation-maintenance.md` và `docs/CHANGELOG.md` |
| Bài học dùng được cho project khác | Skill tương ứng (`owner-led-delivery`, `ui-consistency-audit`, `mobile-app-feel`, `content-density-ux`). Không đưa dữ liệu khách, credential hay tên nhân viên vào |

## 4. Cuối mỗi đợt

1. Chạy đủ bộ kiểm ở mục 1 và ghi kết quả thật, gồm cả test chập chờn.
2. Cập nhật mục 4 và 5 của sổ tay storefront nếu có quy tắc hoặc cạm bẫy mới.
3. Liệt kê việc còn chờ chủ quyết, như nội dung cần xác nhận hay deploy.
4. Commit chỉ khi chủ yêu cầu hoặc khi chuẩn bị deploy, trên nhánh tính năng, rồi
   gộp vào `master` lúc deploy.
