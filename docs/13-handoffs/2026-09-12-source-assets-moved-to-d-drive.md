# Bàn giao di chuyển asset nguồn ra ổ D ngày 2026-09-12

## Mục đích

Các bộ ảnh gốc và hồ sơ nhập liệu không phải runtime asset, không được đóng gói
vào webroot/release. Chúng đã được chuyển khỏi workspace WSL để giảm dung lượng
project nhưng vẫn giữ bản nguồn phục vụ đối chiếu, nhập lại và rollback.

## Vị trí mới

- Windows: `D:\LinhTinh01\ĐÈN ORIANA\`
- WSL: `/mnt/d/LinhTinh01/ĐÈN ORIANA/`
- Windows: `D:\LinhTinh01\artifacts\`
- WSL: `/mnt/d/LinhTinh01/artifacts/`
- Windows: `D:\LinhTinh01\design-assets\`
- WSL: `/mnt/d/LinhTinh01/design-assets/`
- Bộ `ảnh nlmt` đã được chuyển trước đó vào `D:\LinhTinh01\ảnh nlmt\`.

Ba thư mục `ĐÈN ORIANA/`, `artifacts/` và `design-assets/` ở root project đã
được xóa sau khi bản sao trên ổ D khớp đủ số file, tổng byte và checksum nội
dung.

| Bộ nguồn | File | Tổng byte | SHA-256 tổng theo đường dẫn tương đối + nội dung |
|---|---:|---:|---|
| `ĐÈN ORIANA` | 103 | 266.465.838 | `aaa274d06c4730dfce866750acf9ad34eab4458bac1efef4b4c86b8b800495b4` |
| `artifacts` | 55 | 57.834.381 | `83a1e4a8ee25280a1b3dadb7a72887068ece55c61cec7878eddd64ca9c115325` |
| `design-assets` | 13 | 6.375.864 | `41da11740ab6747e49445d743c624bd386c67bb015e4db718fc426e166fd98ea` |

## Giải thích dữ liệu ORIANA

Sản phẩm ORIANA đã có trong DB. Ở cả local và smoke/demo, phạm vi ID 467–536
hiện còn 67 sản phẩm và cả 67 đều công khai; 42 sản phẩm có ảnh đại diện, 25
sản phẩm chưa có ảnh phù hợp. Ba ID không còn tồn tại là 531, 532 và 534.

Bộ nguồn ORIANA đã được importer xử lý trước đây thành 101 ảnh đại diện và 56
ảnh gallery, tổng 157 file `oriana-product-*.jpg`. Cả local và smoke/demo có đủ
157 file, checksum manifest giống nhau và không có DB reference nào thiếu file.
Ảnh upload đã được resize/chuẩn hóa JPEG và đổi tên, vì vậy không được dùng MD5
byte-for-byte với ảnh nhà cung cấp để kết luận rằng ảnh chưa nhập.

Các ảnh trong thư mục nguồn không khớp 25 sản phẩm đang trống. Riêng thư mục
`DB-OT1000W` chứa bốn ảnh thực tế trùng ảnh DB60 nên importer cố ý không gán
cho sản phẩm DB-OT1000.

## Các bộ ảnh khác đã xác minh

- 10 ảnh danh mục trong `transparent-clean/` khớp byte-for-byte với 10 file
  `featured-category-*-v2.png` đang được DB local/demo sử dụng.
- 7 ảnh chính sách và 4 ảnh quy trình khớp byte-for-byte với file trong upload
  của cả local và demo.
- `generated-test-assets` chỉ còn một banner được DB tham chiếu; bản runtime
  đã nằm trong upload. Các file còn lại là nguồn test, không phải đường dẫn mà
  website phục vụ trực tiếp.
- Hai container local và smoke/demo không chứa bản sao thư mục nguồn
  `ĐÈN ORIANA` hoặc `artifacts`; chúng chỉ chứa runtime asset trong `upload`.

Sau di chuyển đã kiểm tra 178 DB media reference trọng yếu ở mỗi môi trường
(157 ORIANA + 10 danh mục + 7 chính sách + 4 quy trình): thiếu 0 file. Trang
chủ, trang sản phẩm, danh mục đèn và trang chính sách mẫu đều trả HTTP 200 ở
cả local và smoke/demo.

## Sử dụng về sau và rollback

Khi đóng gói/import ORIANA, truyền đường dẫn nguồn mới thay vì dựa vào mặc định
ở root repo:

```text
./scripts/package-oriana-media.sh "/mnt/d/LinhTinh01/ĐÈN ORIANA"
```

Importer chạy trong Docker cần copy hoặc mount thư mục nguồn trên ổ D vào
container rồi mới dry-run. Không import thẳng DB và không áp lên production
nếu chưa backup và chưa có xác nhận riêng.

Nếu cần hoàn nguyên bố cục workspace, copy hai thư mục từ ổ D về đúng root
project rồi đối chiếu lại các checksum trên. Việc di chuyển này không thay đổi
DB, `public_html/upload`, container volume hoặc production.
