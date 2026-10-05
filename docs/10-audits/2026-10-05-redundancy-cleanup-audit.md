# Audit dữ liệu và tính năng dư thừa — 2026-10-05

## Kết luận ngắn

Kho dự án đang phình chủ yếu do lịch sử release/backup và cache build, không phải do
toàn bộ asset public đều dư. Chưa xóa dữ liệu trong đợt audit này. Các mục có thể
dọn phải đi theo từng nhóm, giữ current/previous/rollback và không đụng dữ liệu
persistent.

## Đã kiểm kê

- Local workspace khoảng 6,4 GB: `backup/` khoảng 4,2 GB, trong đó
  `backup/releases/` khoảng 3,2 GB; Docker build cache khoảng 13,35 GB.
- VPS stack khoảng 25 GB: `backups/` khoảng 15 GB, `releases/` khoảng 4,4 GB,
  `incoming/` khoảng 4,5 GB, `shared/` khoảng 1,5 GB.
- Production hiện dùng release `2d76b4c5d703`; release trước đó
  `9c549ff8f46f` là rollback trực tiếp. Upload production đã đối chiếu 1.276 file
  với bản backup gần nhất; không đưa upload vào cleanup.
- Local source sạch tại thời điểm audit; audit không sửa code, DB hoặc upload.

## Checkpoint cleanup đã thực hiện

- Đã xóa đúng 13 bundle release staging cũ trong VPS
  `/srv/phucthinhsolar/incoming/` vì đã có release tương ứng trong
  `/srv/phucthinhsolar/releases/`.
- Dung lượng `incoming/` giảm từ khoảng 4,5 GB xuống 1,9 GB.
- Giữ lại bundle current `20261005T023246Z-2d76b4c5d703`, previous
  `20261003T010056Z-9c549ff8f46f`, hai thư mục migration và `data-patches`.
- Sau cleanup: web container vẫn `healthy`, HTTP production trả `200`; không
  chạm database, `shared/upload`, backup hoặc release đang chạy.
- Đã dọn release archive cũ trên VPS: `releases/` giảm từ khoảng 4,4 GB xuống
  992 MB; giữ lại đúng current, previous và rollback `e11323a6a3dd`.
- Đã dọn 8 release archive local đã có bản tương ứng và 4 archive đóng gói dở
  không có `RELEASE.txt`; `backup/releases/` giảm từ khoảng 1,5 GB xuống 644 MB.
- Đã xác minh checksum 2 backup full và 2 backup DB gần nhất trên VPS, sau đó
  dọn 39 backup `*-full`/`*-db` ngoài retention. `backups/` giảm từ khoảng
  15 GB xuống 2,2 GB; giữ nguyên archive runtime-images 420 MB.
- Backup local `product-metadata-cleanup` khoảng 1,1 GB còn giữ vì chứa dump DB
  và upload tar, chưa đủ căn cứ để coi là rác. Artifact kiểm thử/candidate local
  cũng chưa xóa vì có thể còn phục vụ các đợt kiểm tra hoặc import ảnh.

## Sự cố SEO/nội dung ngoài thương hiệu đã xử lý

- Phát hiện `public_html/index.html` là trang mặc định cũ chứa
  `kimthienbao.mrgiang.net`; đây là nguồn khiến URL `/index.html` có thể bị công
  cụ tìm kiếm lập chỉ mục ngoài nội dung Phúc Thịnh Solar.
- Đã xóa file khỏi source và production bằng release `35f06aaab381`, đồng thời
  thêm guard trong `scripts/predeploy-local.sh` để chặn file này tái xuất hiện.
- Xác minh production: `/`, `/index.html`, `/index.php`, `/san-pham/`,
  `/robots.txt` và `/sitemap.xml` không còn marker ngoại thương hiệu; `/index.html`
  đã về URL chuẩn `/`.

## Ứng viên dọn theo mức độ chắc chắn

### Có thể dọn sau khi chốt retention

- Docker build cache không còn được image/container tham chiếu.
- Artifact kiểm thử local như `.playwright-mcp` và thư mục candidate đã hoàn tất
  kiểm tra, nếu không còn phiên làm việc cần giữ.
- Release bundle cũ trong VPS `incoming/` sau khi đối chiếu với release đã cài và
  giữ lại bundle current/previous/rollback cần thiết.
- Release archive local/VPS cũ, chỉ giữ current, previous và tối đa một rollback
  đã kiểm tra được.

### Cần xác nhận/đối chiếu trước khi dọn

- Backup full/db cũ: script hiện đặt retention mặc định 14 ngày, trong khi script
  cleanup một lần có chính sách giữ 1 full + 1 db. Đây là quyết định vận hành,
  không tự ý rút retention.
- `backup/local/product-metadata-cleanup/20261002-111006` khoảng 1,1 GB: cần
  kiểm tra manifest, checksum và khả năng khôi phục trước khi archive/xóa.
- `public_html/unused/`: hiện không có tham chiếu source rõ ràng, nhưng cần xác
  nhận không phải đầu vào thủ công trước khi loại khỏi release.
- `assets/css/jssor.css` và `assets/js/jssor.slider.min.js`: không còn call-site
  public trong static scan; cần xóa cùng một commit và chạy full visual test,
  không xóa riêng một file.
- Các file/font trùng giữa `assets/themes/local`,
  `assets/wp-content/themes/local` và các thư mục vendor: chưa được xóa vì có
  tham chiếu path tương đối và URL WordPress legacy.

## Không được coi là rác ở đợt này

- `shared/upload`, database volume và ảnh/thumb đang được DB hoặc URL public
  tham chiếu.
- `assets/wp-content/uploads`: vẫn là fallback cho URL media WordPress cũ.
- `assets/photobox`, `fotorama`, `magiczoomplus`, `owlcarousel2`, `slick`:
  layout/template hoặc predeploy guard còn dùng.
- `assets/wp-content/themes/local`: layout hiện vẫn nạp một phần JS/CSS từ cây này.
- CKEditor/CKFinder/elFinder: là các luồng Admin khác nhau; không gộp/xóa chỉ vì
  cùng là file manager/editor.

## Thứ tự cleanup an toàn đề xuất

1. Chốt chính sách giữ backup và rollback.
2. Lập manifest current/previous/rollback, checksum và tham chiếu release.
3. Dọn cache build/artifact local không ảnh hưởng runtime.
4. Dọn incoming/release archive cụ thể trên VPS; kiểm tra health và rollback point.
5. Tách/xóa Jssor và `unused` bằng commit riêng nếu visual regression đạt.
6. Chỉ sau đó mới cân nhắc hợp nhất asset trùng hoặc xử lý legacy media theo
   manifest URL, không cleanup hàng loạt.

## Rủi ro còn lại

Static scan không chứng minh được mọi consumer sinh từ DB, AJAX, CSS `url()` hoặc
URL legacy. Vì vậy mọi xóa asset public phải kèm HTML/DOM/network inventory,
Playwright desktop/mobile và smoke test product/gallery/admin.
