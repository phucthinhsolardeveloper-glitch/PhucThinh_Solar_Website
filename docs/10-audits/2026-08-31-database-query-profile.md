# Audit truy vấn runtime — 2026-08-31

## Phạm vi và phương pháp

Đo trên Docker local PHP 7.4/MariaDB 10.4 bằng general query log chỉ bật trong thời gian một request trang chủ, sau đó tắt ngay. Không thay đổi dữ liệu nghiệp vụ và không đo trên production.

## Kết quả menu

Baseline trang chủ phát sinh 77 query ứng dụng. Menu desktop và mobile cùng truy vấn lại từng nhánh danh mục sản phẩm, giải pháp và tin tức; các truy vấn con theo `id_list`, `id_cat` và `id_item` tạo N+1 và nhiều câu giống nhau chạy hai lần.

Các cấp `cat`, `item`, `sub` hiện được nạp thành sáu tập dữ liệu có cache, nhóm theo parent ID một lần rồi dùng chung cho hai menu. Request đối chứng còn 45 query, giảm 32 query (41,6%). Markup menu trước/sau giống nhau khi bỏ khác biệt whitespace; thứ tự vẫn dựa trên `numb,id desc` như truy vấn cũ.

PHP lint, full pre-deploy, security, Admin, Excel, public smoke và kiểm tra log đều đạt. Audit N+1 cho các block riêng của trang chủ, danh mục và Admin vẫn tiếp tục; chưa dùng kết quả này để kết luận toàn bộ hạng mục đã hoàn thành.

Đợt tiếp theo gom gallery của Giải pháp và dự án nổi bật theo tập dữ liệu, sau đó nhóm theo `id_parent`/`id_list`. Request trang chủ warm giảm tiếp từ 45 xuống 43 query; markup block Giải pháp trước/sau giống nhau. Các truy vấn template theo từng card/tab không còn chạy trong vòng lặp.

## Rollback

Hoàn nguyên thay đổi trong `sources/allpage.php`, `templates/layout/menu.php` và `templates/layout/mmenu.php`. Không cần rollback database.
