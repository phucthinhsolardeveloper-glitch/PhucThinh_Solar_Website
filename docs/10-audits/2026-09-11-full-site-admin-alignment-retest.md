# Rà soát cập nhật toàn website và đối chiếu Admin — 2026-09-11

## Mục đích và phạm vi

Tài liệu này là bản cập nhật cho đợt rà soát ngày 2026-09-05, sau các thay đổi
giao diện, Hero, chính sách, danh mục sản phẩm, nhận xét và ảnh Shopping trong
ngày 2026-09-11.

- Đối chiếu GUI public với chức năng Admin, route, source, cấu hình type, schema
  và dữ liệu MariaDB local.
- Chạy lại regression PHP, quét màn Admin, kiểm tra route, form và Playwright ở
  desktop/mobile.
- Chỉ đọc source và dữ liệu, chạy fixture local có cleanup, rồi cập nhật tài
  liệu. Không sửa logic website, không ghi production và không xóa dữ liệu.
- Snapshot chính: `2026-09-11 20:51 +07`, HEAD `00ce647`.
- Trong lúc rà soát, `assets/css/theme-v2.css` và `templates/layout/slide.php`
  tiếp tục được chỉnh đồng thời rồi được commit thành `00ce647`. Hai file đã
  được kiểm tra lại sau commit; audit không ghi đè thay đổi của Claude/chủ
  project.

## Kết luận điều hành

Website local vẫn hoạt động tương đối ổn: 113/113 màn Admin quét được, 500 slug
public resolve được, các luồng form public chính đạt và 64 bài Playwright hiện
có đạt. Cây danh mục sản phẩm đang đúng 10 nhóm, không còn `Đèn AC` hay `Hệ
thống điện` trong dữ liệu đang hoạt động.

Tuy nhiên bản hiện tại **chưa đủ điều kiện deploy** vì bốn nhóm rủi ro chính:

1. Migration `036_add_product_photo_feed.sql` chưa được đưa vào pre-deploy hoặc
   kiểm tra schema, trong khi source Admin đã query cột `photo_feed`.
2. Full pre-deploy không dựng lại được image local vì metadata kho Debian
   `bullseye-security` hết hạn; do đó chưa có một lần build sạch và test xanh.
3. Hero mới công bố nhiều cam kết/số liệu chưa có nguồn xác minh: `10.000+`,
   `500+`, `5+`, tiết kiệm đến `70%`, “hỗ trợ trọn đời”, “giảm hàng nghìn tấn
   CO₂” và “bảo mật tuyệt đối”. Đây là blocker nội dung trước khi public.
4. Bảy ảnh chính sách có trong volume upload của Docker và DB đang trỏ tới,
   nhưng không có trong `public_html/upload/news` của workspace. Release chỉ
   đóng gói source có thể làm mất asset.

Ngoài ra có ba regression đang đỏ do test/kỳ vọng chưa được cập nhật và hai lỗi
giao diện độc lập: hộp tư vấn sidebar danh mục rỗng đã biến mất ở desktop, sáu
icon trang “Về chúng tôi” tải lớn hơn mức cần thiết.

## Trạng thái thay đổi so với báo cáo 2026-09-05

### Danh mục sản phẩm

DB local hiện có đúng 10 danh mục cấp 1 đang bật và nổi bật:

1. Đèn năng lượng mặt trời.
2. Biến tần (Inverter).
3. Tấm pin năng lượng mặt trời.
4. Trụ đèn & phụ kiện.
5. Đèn sân vườn.
6. Quạt năng lượng mặt trời.
7. Hệ thống bơm năng lượng mặt trời.
8. Thiết bị & phụ kiện điện mặt trời.
9. Hệ khung & phụ kiện tấm pin.
10. Pin lưu trữ.

Không có record hoạt động tên `Đèn AC` hoặc `Hệ thống điện`. Playwright xác nhận
trang chủ expose đủ các danh mục nổi bật và đổi grid sản phẩm được ở cả desktop
lẫn mobile. Không cần khôi phục hai mục cũ.

### Snapshot dữ liệu sản phẩm

| Chỉ số | 2026-09-05 | 2026-09-11 | Nhận định |
|---|---:|---:|---|
| Tổng sản phẩm `san-pham` | 443 | 417 | Dữ liệu đã thay đổi; không dùng số cũ để lập kế hoạch import. |
| Có ảnh chính | 80 | 194 | Cải thiện rõ, vẫn còn 223 sản phẩm thiếu ảnh chính. |
| Có mô tả ngắn | 3 | 213 | Đã được bổ sung đáng kể. |
| Có nội dung chi tiết | 77 | 53 | Giảm; cần xác minh do dọn dữ liệu, import hay chuyển sang nội dung cấu trúc. |
| Có SEO title | 80 | 62 | Giảm; cần báo cáo theo ID trước khi backfill. |
| Có SEO description | 73 | 56 | Giảm; không tự ghi đè nội dung đã biên tập. |
| Có ảnh Shopping `photo_feed` | Chưa có cột | 0 | Chức năng đã có nhưng chưa được vận hành. |

Các số trên là snapshot local, không phải số production.

### Chính sách

Hiện có bảy chính sách public. Kiểm tra thực tế cho kết quả:

- 7/7 slug mới trả HTTP 200 và có tiêu đề nội dung.
- 5/5 slug cũ được kiểm tra trả HTTP 301 sang URL mới.
- Footer trỏ tới bảy slug hiện hành.
- Ảnh cover bị ẩn có chủ ý trên trang chi tiết chính sách.

Admin hiện đã có trường upload ảnh cho type `chinh-sach` với kích thước cấu
hình 1000x560. Đây là thay đổi đúng so với mong muốn có ảnh minh họa, nhưng
public hiện không có consumer cho ảnh này: trang chi tiết cố ý ẩn cover, footer
và liên kết chính sách chỉ dùng tên/slug. Vì vậy Admin đang cho nhập một dữ liệu
chưa tạo ra giá trị hiển thị.

Bảy filename DB đang dùng đều có trong volume `/var/www/html/upload/news` của
container, nhưng đều thiếu trong thư mục upload tương ứng của workspace:

- `chinh-sach-bao-hanh.png`
- `chinh-sach-mua-ban.png`
- `chinh-sach-doi-tra.png`
- `chinh-sach-van-chuyen.png`
- `chinh-sach-bao-mat-thong-tin.png`
- `tiep-nhan-va-giai-quyet-khieu-nai.png`
- `dieu-kien-giao-dich-chung.png`

Cần chốt ảnh sẽ dùng ở card/list/hero phụ nào. Nếu vẫn không hiển thị ở đâu,
không nên bắt nhân viên Admin duy trì trường ảnh chỉ để lưu trữ.

### Hero trang chủ

Admin đã quản lý được cấp ngoài cùng:

- `Banner slide ảnh/video`, dùng module Slideshow.
- `Hero chữ`, dùng ảnh nền từ module `home-hero-bg`.

DB local đang chọn `home_hero_mode=text`. Bên trong Hero chữ lại render sẵn hai
layout và cho **khách truy cập** bấm nút icon đổi layout; lựa chọn được lưu bằng
`localStorage`. Layout 2 có form tư vấn và là mặc định. Layout 1 vẫn tồn tại đầy
đủ trong DOM.

Kiểm tra trình duyệt riêng ở desktop/mobile đã xác nhận:

- Chỉ một trong hai panel Hero hiển thị tại một thời điểm.
- Layout 2 là mặc định trong phiên mới.
- Nút đổi layout hoạt động và lựa chọn layout 1 còn giữ sau reload.
- Form Hero có đúng một CSRF token, một trường reCAPTCHA riêng, họ tên và số
  điện thoại bắt buộc; field name khớp handler contact hiện có.
- Không có `id` trùng, JavaScript page error, request lỗi HTTP hoặc tràn ngang
  trong bài kiểm tra.

Điểm chưa logic:

- Admin chỉ gọi chung là “Hero chữ (mặc định cũ)” và không cho chủ site chọn
  layout 1/2. Quyền chọn thiết kế lại được đưa cho mọi khách truy cập.
- Không có mục Admin cho headline, benefit, CTA, số liệu hoặc cam kết; tất cả
  đang hard-code trong template.
- Không có analytics/experiment ID để biết layout nào tạo lead tốt hơn. Đây
  không phải A/B test đo lường được, chỉ là hai giao diện cùng tồn tại.
- Nội dung quảng cáo chưa được xác minh như đã nêu ở phần blocker.
- Popup quảng cáo có thể che nút đổi Hero cho tới khi người dùng đóng popup;
  đây là hành vi modal hợp lệ, nhưng bài test tương tác phải đóng popup trước.

Khuyến nghị chỉ public một layout đã được chủ doanh nghiệp duyệt. Nếu thật sự
muốn A/B test thì server phải phân nhóm ổn định, gắn analytics và không để nút
đổi giao diện kỹ thuật lộ cho khách.

### Quy trình chuyên nghiệp

Phần quy trình đang đúng mối quan hệ Admin/public:

- Source public đọc tối đa bốn record type `quy-trinh`.
- Admin type này có tên, mô tả CKEditor, ảnh, trạng thái và thứ tự.
- Template trang chủ dùng đúng ảnh từng record và số thứ tự 01–04.

Đây không phải nội dung hard-code; nhân viên có thể thay bốn ảnh quy trình từ
Admin. Bài test trang chủ không phát hiện ảnh mờ hoặc request ảnh lỗi.

### Ảnh Shopping/quảng cáo

Source mới đã có:

- Cột `table_product.photo_feed` qua migration 036.
- Upload/xóa ảnh riêng trên form sản phẩm.
- Cột tương ứng trong bulk import/export.
- Nút xuất CSV Shopping, ưu tiên `photo_feed` rồi fallback về ảnh chính.

Hiện trạng vận hành:

- Schema local có cột `photo_feed`.
- 0/417 sản phẩm có dữ liệu ở cột này.
- Chưa có regression riêng cho migration, upload/xóa, fallback và nội dung CSV.
- Pre-deploy chưa copy/lint/check migration 036 và chưa assert cột tồn tại.
- Fallback về ảnh chính giúp CSV không trống nhưng không chứng minh ảnh đáp ứng
  yêu cầu “không logo, không text”. Hệ thống cũng chưa tự kiểm tra nền sạch,
  logo hay chữ trong ảnh.
- CSV hiện là file hỗ trợ nội bộ sáu cột; chưa có bằng chứng trong bộ test rằng
  nó đã được nhập thử thành công vào nền tảng quảng cáo đích.

Đây là tính năng Admin có giao diện nhưng chưa hoàn tất chu trình vận hành.

### Nhận xét sản phẩm

Admin mới có tùy chọn `review_require_order_code`, nhưng key này đang **unset**
trong DB local và code hiểu unset là tắt. Kết quả là nhận xét không có đơn hàng
hợp lệ vẫn được tiếp nhận vào hàng chờ Admin, thay vì bị từ chối; nhận xét không
tự public trước khi duyệt.

Regression cũ luôn giả định bắt buộc mã đơn nên đỏ ba assertion. Cần chốt chính
sách kinh doanh:

- Nếu chỉ nhận người đã mua: đặt option bằng `1`, giữ verified-purchase và cập
  nhật test cả trường hợp bật/tắt.
- Nếu cho mọi người nhận xét: đặt option rõ bằng `0`, chống spam/rate limit và
  không gắn cờ verified-purchase cho review chưa xác minh.

Không nên tiếp tục dựa vào trạng thái `unset`, vì môi trường mới có thể thay đổi
hành vi chỉ do thiếu key cấu hình.

### Quyền Admin

Danh mục quyền live có 197 quyền. Nhóm `Nhóm quyền cấp cao` phủ đủ cả 197 quyền,
nhưng còn bốn dòng lỗi thời của module đã bỏ/gộp:

- `news_add_he-thong-dai-ly`
- `news_delete_he-thong-dai-ly`
- `news_edit_he-thong-dai-ly`
- `news_man_he-thong-dai-ly`

Bốn quyền này không mở thêm chức năng vì không còn khớp catalogue, nhưng làm số
liệu quyền sai và gây hiểu nhầm. Chưa xóa trực tiếp DB. Cách an toàn là backup
nhóm quyền, mở nhóm trong Admin, chọn lại phạm vi đúng và lưu để source hiện có
tự ghi lại danh sách sạch.

## Ma trận đối chiếu GUI và Admin

| Khu vực public | Nguồn quản trị | Trạng thái | Hành động |
|---|---|---|---|
| 10 ô danh mục trang chủ | Danh mục sản phẩm + ảnh danh mục | Khớp, test chuyển grid đạt | Giữ 10 mục; không khôi phục AC/Hệ thống điện. |
| Quy trình 01–04 | News type `quy-trinh` | Khớp | Thay ảnh/nội dung qua Admin. |
| Slideshow/ảnh nền Hero | Slideshow, `home-hero-bg`, setting | Khớp phần media/chế độ | Bổ sung quyết định layout, không để khách chọn bản kỹ thuật. |
| Copy, benefit, số liệu Hero | Không có Admin; hard-code | Thiếu quản trị và chưa xác minh | Duyệt nội dung; tối thiểu có công tắc ẩn số liệu. |
| Form tư vấn Hero | Contact handler + Admin liên hệ | Khớp kỹ thuật | Thêm regression submission riêng cho form Hero. |
| Chính sách dạng text | News type `chinh-sach` | Khớp; 7 route đạt | Giữ redirect cũ và nội dung/SEO. |
| Ảnh chính sách | Admin có upload, public cố ý không render | Admin dư so với consumer hiện tại | Chốt nơi hiển thị hoặc bỏ yêu cầu nhập ảnh. |
| Review sản phẩm | Admin duyệt + setting bắt mã đơn | Có nhưng default mơ hồ | Set rõ 0/1 và test hai mode. |
| Ảnh Shopping/CSV | Form sản phẩm + bulk + export | Có GUI, chưa có dữ liệu/test/release gate | Hoàn tất migration, test và quy trình nhập ảnh sạch. |
| Hộp tư vấn sidebar danh mục | Không còn markup, CSS/test vẫn còn | Lệch kỳ vọng GUI | Khôi phục CTA hoặc xóa kỳ vọng sau quyết định UX. |
| Media/type legacy ghi ở audit 05/09 | Có Admin hoặc dữ liệu nhưng chưa rõ consumer | Chưa đủ bằng chứng để xóa | Tiếp tục giữ, audit staging trước archive. |

## Kết quả test cập nhật

### Đạt

- PHP 7.4 lint: 6 file PHP thay đổi gần đây đạt, gồm route/category helper,
  policy detail/config, About, Index và Hero template.
- Node syntax: 3 file JavaScript public/Admin đạt.
- `git diff --check`: đạt.
- Public route resolver: 500 slug đạt.
- Admin screen sweep: 113/113 màn đạt; không có error, warning hoặc blank đáng
  ngờ.
- Public form submissions: 19 pass, 0 fail.
- Product bulk regression: đạt bộ assertion hiện có.
- Batch 32 regression PHP: 29 script đạt, 3 script đỏ được phân loại bên dưới.
- Full Playwright hiện có: 64 pass, 4 fail, 10 skip, 6 chưa chạy trong 84 test.
- Bài audit trình duyệt bổ sung: 4/4 đạt ở desktop/mobile, gồm 7 route chính
  sách, 5 redirect cũ và hợp đồng Hero.
- Sau khi thay đổi Hero đồng thời được chốt ở commit `00ce647`, bài smoke riêng
  cho hai layout Hero được chạy lại và đạt 2/2 desktop/mobile.

### Đỏ và phân loại

1. `test-admin-readiness.php`: test cũ yêu cầu Chính sách không có field ảnh,
   trong khi cấu hình mới cố ý bật ảnh. Đây là **test drift**, đồng thời lộ ra
   câu hỏi ảnh chính sách đang không có consumer public.
2. `test-public-lead-and-review.php`: 3 assertion đỏ vì test giả định bắt buộc
   mã đơn nhưng option local đang unset/off. Đây là **test drift + quyết định
   sản phẩm chưa chốt**, không phải lỗi tự public review.
3. `test-permission-catalogue.php`: lỗi thật về dữ liệu local do bốn quyền
   `he-thong-dai-ly` lỗi thời.
4. Playwright desktop danh mục rỗng: không tìm thấy `.sidebar-consult-box`.
   Markup không còn trong source nhưng CSS và test vẫn giữ. Cần quyết định CTA
   này phải tồn tại hay test/CSS đã lỗi thời.
5. Playwright ảnh trang “Về chúng tôi”: sáu icon file 153–250px chỉ hiển thị
   38px, tỷ lệ tải dư 4,0–6,6 lần. Đây là lỗi tối ưu tài nguyên, không làm vỡ
   trang.
6. Visual baseline trang chủ desktop/mobile lệch 62% và 59% vì Hero mới. Không
   cập nhật snapshot cho đến khi thiết kế và nội dung được duyệt; nếu cập nhật
   sớm sẽ che mất thay đổi chưa chốt.

### Full pre-deploy

`LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh` chưa đi đến bước regression.
Docker build dừng tại `apt-get update` vì Release metadata của Debian
`bullseye-security` bị báo hết hạn. Đây là lỗi khả năng tái dựng môi trường,
không phải bằng chứng source website sai; nhưng trạng thái release vẫn là
**không đạt** vì chưa có build sạch.

Script pre-deploy cũng chưa bao phủ một số file thay đổi mới như policy detail,
config policy, About, Hero template và JavaScript home, đồng thời chưa biết đến
migration 036. Các file đã được lint thủ công trong audit này, nhưng phải đưa
vào pipeline để lần sau không phụ thuộc kiểm tra tay.

## Checklist triển khai đề xuất

### P0 — Chặn deploy

- [ ] Chốt/đóng băng commit release từ `00ce647` hoặc commit mới hơn đã được
  review; không tiếp tục đổi giao diện trong lúc chạy release gate.
- [ ] Sửa khả năng build Docker với nguồn Debian còn hiệu lực, sau đó chạy lại
  full pre-deploy từ image sạch.
- [ ] Đưa migration 036 vào manifest/preflight, assert cột `photo_feed` trước
  khi source Admin query; thử trên bản sao DB trước production.
- [ ] Đồng bộ đủ bảy ảnh chính sách vào shared upload/release asset theo đúng
  cơ chế deploy, kèm kiểm tra filename; không chỉ copy vào container tạm.
- [ ] Chủ doanh nghiệp xác minh hoặc yêu cầu ẩn toàn bộ số liệu/cam kết trên
  Hero. Không dùng số ước lượng để làm đẹp.
- [ ] Chạy lại 32 regression PHP, Playwright và full pre-deploy cho tới khi các
  blocker thật hết đỏ.

### P1 — Logic Admin/public và regression

- [ ] Chốt một Hero public. Nếu layout 2 thắng, bỏ nút đổi bản dành cho khách;
  nếu cần A/B test, bổ sung phân nhóm và analytics trước.
- [ ] Sửa nhãn/hướng dẫn Hero trong Admin cho đúng hành vi thực tế.
- [ ] Đưa headline, CTA và từng số liệu Hero vào cấu hình có công tắc hiển thị,
  hoặc giữ hard-code nhưng chỉ sau khi nội dung được ký duyệt.
- [ ] Cập nhật `test-admin-readiness.php` theo quyết định cuối về ảnh chính
  sách; test cả việc field xuất hiện và upload đúng nếu giữ tính năng.
- [ ] Chốt nơi public dùng ảnh chính sách. Nếu dùng card/list, test ảnh và alt;
  nếu không dùng, không bắt Admin nhập ảnh.
- [ ] Set rõ `review_require_order_code=0/1`; sửa regression kiểm tra cả hai mode
  thay vì phụ thuộc default unset.
- [ ] Làm sạch bốn quyền cũ qua màn nhóm quyền sau khi backup; chạy lại
  permission catalogue.
- [ ] Chốt lại hộp tư vấn sidebar danh mục: khôi phục nếu vẫn là entry point đã
  duyệt, hoặc xóa CSS/test dư nếu chủ ý thiết kế đã đổi.
- [ ] Tạo ảnh Shopping sạch cho sản phẩm ưu tiên, không coi ảnh chính fallback
  là đã đáp ứng yêu cầu quảng cáo.
- [ ] Thêm regression cho migration 036, upload/remove, bulk, auth, CSV escaping,
  fallback và URL ảnh; import thử file vào nền tảng quảng cáo staging.
- [ ] Tối ưu sáu icon trang “Về chúng tôi” hoặc phục vụ đúng kích thước.
- [ ] Chỉ cập nhật visual baseline sau khi Hero và nội dung được duyệt bằng mắt.

### P2 — Tối ưu vận hành

- [ ] Bổ sung dashboard/filter cho 223 sản phẩm thiếu ảnh và 417 sản phẩm chưa
  có ảnh Shopping; ưu tiên theo trạng thái hiển thị/doanh thu thay vì sửa dàn
  trải.
- [ ] Điều tra vì sao content/SEO giảm so với 05/09 bằng báo cáo ID dry-run;
  không backfill hoặc xóa tự động.
- [ ] Gắn completeness cho ảnh chính, ảnh Shopping, mô tả, thông số và SEO.
- [ ] Tiếp tục audit các media/type legacy trước khi archive; giữ nguyên upload
  cho tới khi có backup, owner và thời hạn rollback.
- [ ] Thêm route policy/redirect và Hero form vào bộ regression chính thức để
  không cần bài test tạm thời.

## Tiêu chí cho phép deploy

Chỉ coi bản này sẵn sàng khi đồng thời thỏa:

1. Working tree/commit cần deploy được xác định rõ, không lấy nhầm file đang sửa.
2. Image Docker dựng sạch; full pre-deploy chạy hết và xanh.
3. Migration 036 có preflight, backup và thử restore/rollback trên local hoặc
   staging.
4. Asset upload có manifest hoặc quy trình shared-volume rõ, không thiếu bảy
   ảnh DB đang tham chiếu.
5. Số liệu/cam kết Hero đã được chủ doanh nghiệp xác minh hoặc đã ẩn.
6. Ba regression PHP đỏ được cập nhật/xử lý, hai lỗi Playwright độc lập được
   chốt, baseline chỉ đổi sau phê duyệt hình ảnh.
7. Commerce smoke test vẫn đi qua category → product → cart → checkout →
   confirmation trên PHP mục tiêu.

## Deploy và rollback

- Chưa deploy và chưa chạy SQL production trong đợt rà soát này.
- Trước deploy: backup DB, `upload/`, setting Hero, nhóm quyền và các row chính
  sách; ghi lại commit/source package cùng migration đã chạy.
- Rollback source: quay về commit release trước; không xóa asset upload trong
  cùng thao tác.
- Rollback migration 036: ưu tiên để cột nullable tồn tại vì không gây ảnh hưởng
  source cũ. Chỉ `DROP COLUMN` sau khi chắc chắn không có ảnh Shopping thật và
  đã backup mapping filename.
- Rollback Hero nhanh: chuyển setting về chế độ đã xác nhận ổn định hoặc revert
  riêng commit Hero; giữ ảnh cũ để có thể phục hồi.
- Rollback quyền: phục hồi snapshot nhóm quyền, không viết SQL xóa hàng loạt
  nếu chưa đối chiếu catalogue trên môi trường đích.

## File thay đổi bởi lần rà soát này

- `docs/10-audits/2026-09-11-full-site-admin-alignment-retest.md`: báo cáo này.
- `docs/10-audits/2026-09-05-admin-utilization-audit.md`: thêm liên kết tới bản
  cập nhật; không sửa kết luận lịch sử.
