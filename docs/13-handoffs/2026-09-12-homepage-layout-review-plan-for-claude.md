# Bàn giao Claude: thẩm định kế hoạch tái bố cục trang chủ — 2026-09-12

## Trạng thái và phạm vi

Tài liệu này giao cho Claude nhiệm vụ **đọc, kiểm tra và phản biện kế hoạch**
trước khi chủ project quyết định triển khai. Trong vòng thẩm định này:

- Không sửa PHP, CSS, JavaScript, database hoặc ảnh đang chạy.
- Không deploy local/demo/production.
- Không xóa section, asset hoặc dữ liệu cũ.
- Không mặc định prototype là thiết kế cuối cùng phải sao chép nguyên bản.
- Báo cáo lại các điểm đúng, điểm sai, phần thiếu và rủi ro; chỉ lập kế hoạch
  triển khai chi tiết sau khi đã đối chiếu source và Admin.

Mục tiêu kinh doanh là lấy **cách tổ chức nội dung và nhịp bố cục** của mẫu mới,
nhưng vẫn giữ nhận diện hiện tại của Phúc Thịnh Solar: logo, xanh navy, vàng,
trắng, nội dung thật, ảnh thật và các chức năng hiện có.

## Tài liệu và ảnh cần đối chiếu

### Hai ảnh chụp toàn trang

- Trang chủ hiện tại:
  `/mnt/c/Users/ADMIN/Downloads/screencapture-phucthinhsolar-2026-09-12-13_34_16.png`
- Prototype/trang mẫu:
  `/mnt/c/Users/ADMIN/Downloads/screencapture-phuc-thinh-solar-trang-chu-moi-phucthinhsolar-chatgpt-site-2026-09-12-13_33_02.png`

### Prototype bàn giao

- Thư mục:
  `/mnt/d/Docs/00_TaiLieu/File_ProductData_PTS/WorkPhase01/Phuc-Thinh-Solar-Trang-Chu-Moi`
- Đọc tối thiểu `BAN-GIAO.md`, HTML trang chủ, CSS và JavaScript liên quan.

### Tài liệu liên quan trong repo

- `docs/13-handoffs/2026-09-11-homepage-hero-review-implementation-plan.md`
- `docs/10-audits/2026-09-11-full-site-admin-alignment-retest.md`
- `docs/13-handoffs/2026-09-09-category-restructure-and-mobile-audit.md`

Nếu source hiện tại chứng minh một nhận định trong tài liệu này đã cũ, Claude
phải ghi rõ bằng chứng và đề xuất cập nhật tài liệu, không làm theo nhận định cũ.

## Kết luận sơ bộ để Claude thẩm định

Không cần xây lại trang chủ từ đầu. Dự kiến có thể giữ khoảng 75–85% dữ liệu,
tính năng và luồng quản trị hiện có. Công việc chính là:

1. Sắp xếp lại thứ tự các section.
2. Thay đổi cách render và mật độ nội dung.
3. Gộp hoặc ẩn các section lặp ý; chưa xóa dữ liệu nguồn.
4. Nâng cấp riêng mô hình dữ liệu Hero nếu cấu trúc slide hiện tại không đủ.
5. Giữ đủ 10 danh mục sản phẩm, sử dụng ảnh thật theo yêu cầu của chủ project.

Ảnh chụp ngày 12/09 cho thấy trang hiện tại cao khoảng 9.436 px, còn prototype
khoảng 6.752 px. Trang hiện tại dài hơn prototype khoảng 40%; mục tiêu không
phải ép theo đúng số pixel mà là loại bỏ sự lặp nội dung và tạo hành trình rõ.

## Nguyên tắc thiết kế bắt buộc

1. Chỉ lấy **bố cục và cách dẫn dắt** từ prototype.
2. Không thay bộ nhận diện bằng màu sắc/font/phong cách của prototype.
3. Giữ logo và bảng màu xanh navy – vàng – trắng hiện tại.
4. Ưu tiên ảnh công trình, nhân sự và sản phẩm thật; không dùng ảnh chứa chữ
   được dựng sẵn làm nội dung HTML.
5. Không tự giảm 10 danh mục xuống còn 4. Có thể làm nổi bật 4 nhóm chủ lực,
   nhưng khách vẫn phải thấy hoặc truy cập rõ 6 nhóm còn lại.
6. Không công bố số liệu, chứng nhận hoặc quan hệ “nhà phân phối ủy quyền” khi
   chưa có bằng chứng được chủ doanh nghiệp xác nhận.
7. Không thay URL/slug public trong công việc bố cục.
8. Không tạo form lead mới nếu form/modal hiện có có thể tái sử dụng an toàn.
9. Không để cùng một form tư vấn dài xuất hiện nhiều lần trên trang.
10. Ưu tiên grid/card tĩnh; chỉ dùng slider khi có lý do rõ ràng.

## Kiến trúc nội dung đề xuất

Thứ tự dưới đây là phương án để Claude kiểm tra tính khả thi, chưa phải lệnh
triển khai:

1. Banner chiến dịch tùy chọn.
2. Header hiện tại được tinh gọn.
3. Hero editorial slider: nội dung HTML bên trái, ảnh thật bên phải.
4. Bốn lối vào theo nhu cầu khách hàng.
5. Giới thiệu ngắn về Phúc Thịnh Solar.
6. Hệ sinh thái 10 danh mục: 4 chủ lực + 6 bổ sung.
7. Giải pháp theo nhu cầu dạng tab + ảnh/nội dung lớn.
8. Quy trình chuyên nghiệp gồm đúng 4 bước.
9. Hai hoặc ba dự án tiêu biểu.
10. Thương hiệu đã xác minh + ba lối vào chính sách/dịch vụ.
11. CTA dành cho đại lý/đối tác.
12. Ba bài kiến thức tiêu biểu.
13. Khối liên hệ gồm thông tin + một form đầy đủ.
14. Footer hiện tại được tinh gọn nhưng giữ thông tin pháp lý.

## Ma trận giữ, đổi, gộp và ẩn

| Khu vực hiện tại | Quyết định sơ bộ | Cách tận dụng | Việc Claude phải xác minh |
|---|---|---|---|
| Banner chiến dịch `banner-top` | Giữ | Tách biệt với Hero, chỉ hiện khi được bật | Kích thước desktop/mobile, fallback và cơ chế Admin |
| Header, menu, hotline, tìm kiếm, giỏ hàng | Giữ và tinh gọn | Không đổi chức năng hoặc URL | Template/CSS nào đang điều khiển desktop/mobile; tránh tái phát lỗi responsive |
| Hero hiện tại | Đổi mạnh | Giữ cơ chế bật/tắt và asset cũ để rollback | `home_hero_mode`, `slide`, `home-hero-bg`, schema/options và Admin hiện có |
| Form tư vấn trong Hero | Chuyển vị trí | CTA Hero mở modal hoặc cuộn tới form cuối trang | Form action, field, CSRF, CAPTCHA và tracking hiện tại |
| “Bạn đang cần gì?” | Gộp thành 4 lối vào dưới Hero | Tái sử dụng link/modal hiện có | Bốn nhu cầu đúng và đích đến thực tế của từng card |
| Giới thiệu công ty | Giữ, rút gọn | Tái sử dụng copy và route “Về chúng tôi” | Nội dung nào đang hard-code, nội dung nào có Admin |
| 10 danh mục | Giữ đủ 10, đổi layout | 4 card chủ lực + 6 card bổ sung | Cơ chế `noibat`, `numb`, ảnh thật, tình trạng danh mục rỗng |
| Card sản phẩm nổi bật cạnh danh mục | Tách hoặc ẩn | Chỉ giữ nếu có tiêu chí sản phẩm nổi bật thật | Nguồn API/AJAX, cờ nổi bật và khả năng Admin vận hành |
| Dải lợi ích mua hàng | Gộp | Có thể đưa cạnh thương hiệu/chính sách | Tránh lặp với lợi ích giải pháp và footer |
| Giới thiệu có ảnh lớn | Rút gọn | Chỉ giữ ảnh nếu là ảnh thật và có giá trị | Nguồn ảnh, quyền dùng và chất lượng crop |
| Giải pháp | Giữ, đổi cách render | Tận dụng tab, gallery và dữ liệu đang có | Empty state, số lượng tab, nội dung mobile và liên kết CTA |
| Sơ đồ lợi ích dạng vòng tròn | Ẩn khỏi trang chủ | Nội dung có thể gộp vào giải pháp/quy trình | Có route nào khác cần component; không xóa source/dữ liệu |
| Khối số liệu | Ẩn nếu chưa xác minh | Chỉ hiện item đã được duyệt | Nguồn Admin và bằng chứng cho từng số liệu |
| Dự án carousel lớn | Rút còn 2–3 dự án | Tái sử dụng dự án nổi bật và ảnh thật | Tiêu chí nổi bật, watermark, ảnh thiếu và route “Xem tất cả” |
| Quy trình dạng tab | Đổi thành 4 bước trực tiếp | Tái sử dụng `$quytrinh` và ảnh đã có | Đúng 4 bước, thứ tự Admin, ảnh desktop/mobile |
| Hai danh sách tin dài | Rút còn 3 card | Tái sử dụng tin nổi bật | Loại tin, thứ tự, ảnh fallback và ngày đăng |
| Logo đối tác | Giữ một nơi | Dải logo gọn, chỉ dùng thương hiệu đã xác minh | Section hiện tại đang bị ẩn/gộp ở đâu; nhãn quan hệ chính xác |
| Chính sách | Giữ 3 lối vào | Card nhỏ trên home, ảnh lớn dùng tại trang chính sách | Route còn hoạt động, nội dung Admin và ảnh tương ứng |
| CTA đại lý/đối tác | Làm rõ | Tái sử dụng modal/route hiện có | Backend nhận lead, quyền truy cập và chống spam |
| Footer | Giữ và tinh gọn | Giữ pháp lý, BCT, chính sách, giờ làm việc | Dữ liệu chính thức và hiển thị mobile |

## Nguồn dữ liệu hiện có đã phát hiện — cần Claude xác nhận lại

Trong `domains/phucthinhsolar.com/public_html/sources/index.php` hiện đã có:

- `$splistnb`: danh mục sản phẩm nổi bật có sản phẩm đang hiển thị.
- `$newsnb`: tin tức nổi bật.
- `$solieu`: số liệu thống kê.
- `$loiich` và `$loiich_title`: lợi ích.
- `$quytrinh`: bốn bước quy trình.
- `$dahoatdong`: dự án/hoạt động.
- `$testimonial`: phản hồi đại lý.
- `$partner`: đối tác.
- `$chungnhan`: chứng nhận.
- `$heroBgList`: ảnh nền Hero.
- `$solutionRows`, `$solutionTabs`, `$solutionGallery`: dữ liệu giải pháp.
- `$featuredProjects`: dự án nổi bật theo nhóm.

Claude cần lần theo từng biến tới:

1. Bảng và `type` trong database.
2. Màn hình Admin đang quản lý tương ứng.
3. Cờ `hienthi`, `noibat`, thứ tự `numb` và giới hạn số lượng.
4. Template đang render.
5. Trạng thái khi dữ liệu rỗng.
6. Cache và thời gian cache có thể làm người quản trị tưởng thay đổi không có
   hiệu lực.

Không kết luận “Admin đã hỗ trợ” chỉ vì có query frontend. Phải tìm được route,
source và màn hình Admin thực tế.

## Phần Hero cần thẩm định kỹ

Prototype yêu cầu mỗi slide có thể gồm:

- Ảnh desktop.
- Ảnh mobile.
- Nhãn nhỏ/eyebrow.
- Tiêu đề.
- Mô tả.
- CTA chính: nhãn + URL/hành động.
- CTA phụ: nhãn + URL/hành động.
- Thứ tự và trạng thái.

Cơ chế `slide` hiện tại được nhận định mới chỉ có một ảnh/video, tên và link;
`home-hero-bg` chỉ quản lý ảnh nền. Claude phải kiểm tra lại nhận định này và
đề xuất một trong hai phương án:

### Phương án ưu tiên

Mở rộng record slide hiện có bằng `options` JSON nếu schema và helper hiện tại
hỗ trợ an toàn. Ưu điểm là tránh tạo table mới và tận dụng màn hình ảnh hiện có.

### Phương án tối thiểu

Giữ nội dung Hero ổn định trong setting/template, chỉ cho Admin đổi ảnh và bật
tắt slide. Chỉ chọn khi nội dung hiếm khi thay đổi và chủ project đồng ý.

Claude không được tự tạo một CMS/DB table mới trước khi chứng minh hai phương
án trên không đáp ứng được.

## Cách xử lý 10 danh mục

Đây là yêu cầu đã được chốt trước đó: có 10 danh mục cấp 1 và ảnh trang chủ phải
là ảnh thật, không phải icon minh họa.

Phương án đề nghị:

- Bốn danh mục chủ lực là card lớn theo tinh thần prototype.
- Sáu danh mục còn lại vẫn hiển thị rõ bằng card nhỏ hơn hoặc một hàng kế tiếp.
- Mobile dùng lưới hai cột hoặc kéo ngang có chỉ báo rõ; Claude phải đề xuất dựa
  trên khả năng đọc, không chọn slider chỉ để tiết kiệm chiều cao.
- Không trộn card danh mục với card “sản phẩm bán chạy” trong cùng một lưới.
- Nếu dùng `noibat` để chọn bốn nhóm chủ lực, xác minh việc đó không làm mất sáu
  danh mục khỏi các menu/trang khác.

Claude cần báo cáo danh sách chính xác 10 danh mục đang bật và danh mục nào đang
được đánh dấu nổi bật. Không sửa dữ liệu trong vòng thẩm định.

## Nội dung nên ẩn/gộp, không xóa

Nếu source và giao diện hiện tại khớp nhận định, đề nghị ẩn khỏi trang chủ:

- Form dài nằm trực tiếp trong Hero.
- Bộ chuyển layout Hero dành cho khách truy cập nếu vẫn còn.
- Sơ đồ lợi ích dạng vòng tròn.
- Số liệu doanh nghiệp chưa có bằng chứng.
- Carousel dự án quá dày.
- Danh sách tin tức dài và lặp.
- Nội dung lợi ích được nhắc lại ở nhiều section.

Ẩn khỏi trang chủ không đồng nghĩa xóa code, record hoặc asset. Trước khi triển
khai, Claude phải chỉ ra cơ chế rollback cho từng khối.

## Kế hoạch triển khai dự kiến sau khi được duyệt

### Giai đoạn 0 — Thẩm định của Claude

- Đọc các tài liệu và prototype đã nêu.
- Chụp/đo hiện trạng local ở desktop, tablet và mobile.
- Lập bản đồ section → source → query → Admin → route public.
- Xác nhận các phần tái sử dụng được và phần thật sự thiếu.
- Báo cáo mâu thuẫn giữa screenshot, tài liệu và source hiện tại.
- Đề xuất phạm vi file cụ thể, rủi ro và rollback.
- Dừng để chủ project duyệt; không triển khai.

### Giai đoạn 1 — Chốt nội dung và dữ liệu

- Chốt 3–4 slide Hero, copy, CTA và ảnh desktop/mobile.
- Chốt bốn lối vào theo nhu cầu.
- Chốt bốn danh mục chủ lực trong tổng số 10.
- Xác minh số liệu, thương hiệu, chứng nhận và dự án.
- Chọn ba bài kiến thức và ba link chính sách.

### Giai đoạn 2 — Xây nền Hero và Admin

- Bổ sung cấu trúc dữ liệu tối thiểu cần thiết.
- Giữ chế độ cũ có thể rollback.
- Không thay action/CSRF/backend của form hiện có.
- Có fallback khi thiếu ảnh mobile hoặc thiếu slide.

### Giai đoạn 3 — Sắp xếp lại các section

- Tái sử dụng query và dữ liệu hiện có.
- Tách component trình bày khỏi nội dung khi hợp lý.
- Namespace CSS mới để giảm va chạm legacy.
- Không cleanup/refactor ngoài phạm vi trang chủ.

### Giai đoạn 4 — Responsive, hiệu năng và accessibility

- Kiểm tra 360, 390/412, 768, 1024, 1366 và 1920 px.
- Kiểm tra keyboard focus, nút, tab, slider và reduced motion.
- Tạo ảnh WebP/AVIF hợp lý; không tải ảnh desktop lớn cho mobile nếu có thể tránh.
- Tránh layout shift bằng kích thước/ratio ảnh rõ ràng.
- Hero phải giữ được vùng chữ đọc rõ khi crop.

### Giai đoạn 5 — Kiểm thử và nghiệm thu

- Visual test trang chủ desktop/mobile.
- Kiểm tra banner chiến dịch bật và tắt.
- Kiểm tra menu, tìm kiếm, giỏ hàng và nút nổi.
- Kiểm tra CTA tư vấn/đại lý và form thật.
- Commerce smoke test: danh mục → sản phẩm → giỏ hàng → checkout → xác nhận.
- Kiểm tra route dự án, bài viết và ba chính sách.
- So sánh Lighthouse/Web Vitals trước và sau trong cùng điều kiện.
- Kiểm tra dữ liệu rỗng, ảnh lỗi và cache.

### Giai đoạn 6 — Demo, duyệt và deploy

- Chỉ đưa lên demo sau khi local đạt tiêu chí.
- Chủ project duyệt desktop và điện thoại thật.
- Backup file/DB liên quan trước deploy.
- Deploy production chỉ khi được phép rõ ràng.
- Sau deploy đọc lại production và chạy smoke test; không chỉ tin thao tác upload.

## Tiêu chí nghiệm thu tổng thể

### Nội dung và bố cục

- Trang nhìn rõ là thương hiệu Phúc Thịnh Solar hiện tại, không phải bản sao của
  prototype.
- Hero nói rõ sản phẩm/giải pháp chủ lực và có CTA tư vấn trực tiếp.
- Trên trang chỉ có một form tư vấn đầy đủ.
- Bốn hành trình khách hàng có đích đến đúng.
- Có đủ 10 danh mục và tất cả dùng ảnh thật được duyệt.
- Giải pháp, quy trình, dự án, chính sách và kiến thức không lặp thông điệp.
- Quy trình có đúng bốn bước và đọc được mà không cần tương tác bắt buộc.
- Chỉ hiển thị số liệu/chứng nhận/quan hệ đối tác đã được xác minh.

### Chức năng

- Không đổi URL/slug public.
- Header, menu, tìm kiếm, giỏ hàng và checkout không bị regression.
- Form tư vấn/đại lý giữ kiểm tra session/quyền/CSRF/CAPTCHA hiện có tương ứng.
- Admin vẫn kiểm soát được trạng thái, thứ tự và nội dung thường xuyên thay đổi.
- Có empty state an toàn khi thiếu nội dung hoặc ảnh.

### Responsive và chất lượng

- Không dồn trái, tràn ngang, che nội dung hoặc xuất hiện khoảng trắng bất thường.
- Nội dung quan trọng không bị crop trên mobile.
- Không có nhiều slider tự chạy cùng lúc.
- Ảnh đầu trang được tối ưu và không làm tốc độ giảm so với baseline.
- CTA và form dùng được bằng bàn phím, có focus rõ và nhãn đúng.

## Yêu cầu đầu ra của Claude trong vòng thẩm định

Claude hãy trả về một báo cáo tiếng Việt gồm đúng các phần sau:

1. **Kết luận khả thi:** phần trăm/tỷ lệ tái sử dụng kèm căn cứ, không ước lượng
   chung chung.
2. **Bản đồ hiện trạng:** từng section, file template, source/query, Admin và
   dữ liệu liên quan.
3. **Đối chiếu prototype:** điểm nên học, điểm không nên sao chép và lý do.
4. **Danh sách giữ/đổi/gộp/ẩn:** nêu tác động và rollback.
5. **Khoảng trống Admin:** phần nào frontend cần nhưng Admin chưa quản lý; phần
   nào Admin có nhưng frontend không dùng.
6. **Danh sách chính xác 10 danh mục:** trạng thái, thứ tự, nổi bật và số sản phẩm.
7. **Phương án Hero:** so sánh mở rộng `slide`, dùng `options` và setting; đề
   xuất một phương án có lý do.
8. **Phạm vi file dự kiến:** file nào cần sửa và vì sao; chưa sửa file.
9. **Rủi ro:** dữ liệu, responsive, hiệu năng, SEO, form, cache và legacy CSS/JS.
10. **Kế hoạch test và rollback:** đủ local → demo → production.
11. **Các quyết định cần chủ project duyệt:** chỉ liệt kê điểm thực sự ảnh hưởng
    đến nội dung hoặc phạm vi.

Mỗi kết luận kỹ thuật phải kèm đường dẫn file, dòng hoặc truy vấn/bằng chứng phù
hợp. Nếu phát hiện lỗi ngoài phạm vi, ghi vào “Theo dõi sau”, không tự sửa.

## Những điều Claude chưa được tự quyết

- Không tự giảm hoặc đổi tên 10 danh mục.
- Không tự chọn số liệu doanh nghiệp để công bố.
- Không tự gắn nhãn “bán chạy”, “ủy quyền” hoặc “đối tác chiến lược”.
- Không tự đổi logo, bảng màu, font thương hiệu hoặc URL public.
- Không tự xóa section, ảnh, code legacy hoặc record DB.
- Không tự deploy hay import database.
- Không bắt đầu implementation sau báo cáo nếu chủ project chưa duyệt.

## Rollback của chính tài liệu này

Lượt hiện tại chỉ thêm file kế hoạch trong `docs/13-handoffs`; không tác động
runtime. Nếu không dùng, có thể bỏ riêng tài liệu này mà không ảnh hưởng website.
