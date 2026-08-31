# Audit truy vấn runtime — 2026-08-31

## Phạm vi và phương pháp

Đo trên Docker local PHP 7.4/MariaDB 10.4 bằng general query log chỉ bật trong thời gian một request trang chủ, sau đó tắt ngay. Không thay đổi dữ liệu nghiệp vụ và không đo trên production.

## Kết quả menu

Baseline trang chủ phát sinh 77 query ứng dụng. Menu desktop và mobile cùng truy vấn lại từng nhánh danh mục sản phẩm, giải pháp và tin tức; các truy vấn con theo `id_list`, `id_cat` và `id_item` tạo N+1 và nhiều câu giống nhau chạy hai lần.

Các cấp `cat`, `item`, `sub` hiện được nạp thành sáu tập dữ liệu có cache, nhóm theo parent ID một lần rồi dùng chung cho hai menu. Request đối chứng còn 45 query, giảm 32 query (41,6%). Markup menu trước/sau giống nhau khi bỏ khác biệt whitespace; thứ tự vẫn dựa trên `numb,id desc` như truy vấn cũ.

PHP lint, full pre-deploy, security, Admin, Excel, public smoke và kiểm tra log đều đạt. Audit N+1 cho các block riêng của trang chủ, danh mục và Admin vẫn tiếp tục; chưa dùng kết quả này để kết luận toàn bộ hạng mục đã hoàn thành.

Đợt tiếp theo gom gallery của Giải pháp và dự án nổi bật theo tập dữ liệu, sau đó nhóm theo `id_parent`/`id_list`. Request trang chủ warm giảm tiếp từ 45 xuống 43 query; markup block Giải pháp trước/sau giống nhau. Các truy vấn template theo từng card/tab không còn chạy trong vòng lặp.

Profiling bốn trang danh mục cho thấy Tin tức, Giải pháp và Dự án cùng ở mức 43 query, không phát sinh query theo từng card. Riêng `/san-pham/` chạy 51 query do chín nhóm sản phẩm thực hiện từng câu `select ... where id_list = ...` chỉ để xác định trạng thái rỗng. Thay bằng một truy vấn `distinct id_list` có cache đưa request về 43 query, giảm 8 query (15,7%); markup block danh mục đối chứng không đổi.

Danh sách Sản phẩm trong Admin phân trang 10 dòng nhưng trước đây gọi `totalByID()` và `newPost()` cho từng dòng, tạo tối đa 20 query đếm đánh giá. Source hiện gom cả hai số liệu bằng một query parameterized theo tập ID và `GROUP BY id_variant`. Đối chiếu với cách tính cũ trên 10 sản phẩm đại diện cho `mismatches = 0`; PHP lint, Admin readiness và full pre-deploy đều đạt. Các màn Admin còn lại không có truy vấn nằm trực tiếp trong vòng lặp danh sách qua static audit, nhưng vẫn cần profiling authenticated trên dataset staging trước khi kết luận hiệu năng hosting.

## Rollback

Hoàn nguyên thay đổi trong `sources/allpage.php`, `templates/layout/menu.php` và `templates/layout/mmenu.php`. Không cần rollback database.
