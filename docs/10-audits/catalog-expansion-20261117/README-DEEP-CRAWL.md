# CRAWL SÂU CATALOGUE PHÚC THỊNH SOLAR — 17/11/2026

## Phạm vi, trạng thái
- Truy cập **chỉ đọc** nguồn hãng. Không tạo/đổi/xóa sản phẩm trong Laravel CMS, không sửa production, không thay giá hoặc feed Merchant.
- Cơ sở đối chiếu: workbook xuất từ CMS `san-pham-20261117-113100.xlsx` (399 sản phẩm; 353 công khai, 46 ẩn) đã được người dùng cung cấp trong ChatGPT. **Chỉ snapshot 353 sản phẩm công khai được đưa vào các script WSL**, nên trước khi nhập phải tiếp tục so với 46 sản phẩm ẩn trong file Excel.
- Hướng nghiên cứu: 260 dòng/model/series từ 19 hãng trong file cũ, sau đó bổ sung 249 **URL trang sản phẩm mới không trùng URL đã có**, có khả năng trùng model/series nhưng khác URL. Không cộng 249 thành 249 SKU mới.

## Số liệu thực chạy
| Phép đo | Giá trị | Ý nghĩa |
|---|---:|---|
| Candidate research gốc | 260 | Family/model, chưa xác minh tất cả biến thể |
| Trang chi tiết gốc đọc được | 99/99 | HTTP 200 |
| Bảng HTML gốc | 382 | Có thể gồm bảng quảng bá, không chỉ specs |
| Dòng trích HTML gốc | 1.798 | Evidence-only |
| PDF link tìm thấy ở 99 trang | 159 | Link ứng viên, có trùng |
| PDF thử tải/đọc | 70/55 thành công | 15 thất bại/không parse được |
| Dòng PDF evidence | 1.235 | Layout text cần gắn model |
| Ảnh URL gốc | 128 | Chưa kiểm quyền/đúng model |
| URL mới phát hiện (7 chỉ mục) | 249 | Đối chiếu URL, không phải SKU độc lập |
| Trang mới đọc được | 245/249 | HTTP 200 |
| Dòng trích HTML mới | 3.919 | Evidence-only |
| PDF link mới | 785 | Có thể trùng/không phải datasheet |
| URL ảnh mới | 444 | Chưa kiểm QA |
| Huawei per-model | 509 dòng | 4 trang specs HTTP 200 / 1 trang 404; 10 model headings |
| Tổng evidence rows | **7.461** | 1.798 + 1.235 + 3.919 + 509; **chưa khử trùng/QA** |
| Model key có heading rõ | 217 | 200 Deye + 10 Huawei + 7 JINDIAN |
| Dòng gắn model từ bảng | 3.780 | Trích xuất, chưa duyệt |
| Model bị xung đột spec | 3 | STOP-SHIP đến khi đối chiếu |
| SKU đã import | **0** | Không thay đổi CMS hoặc production |

**Không sử dụng đếm 7.461 là số thông số đã xác minh.** Phần lớn là dữ liệu thô; các bảng có nhiều model hoặc PDF có thể gây nhầm cột. Tập 217 model key chưa hoàn tất kiểm tra vùng/quyền phân phối/khả năng cung cấp/ảnh.

## File bàn giao

| File | Nội dung |
|---|---|
| `public-products-snapshot.json` | 353 sản phẩm công khai tại thời điểm audit |
| `public-products-inventory.csv` | 353 sản phẩm tóm tắt |
| `manufacturer-seeds.json` | 215 ứng viên nghiên cứu ban đầu |
| `manufacturer-extra-models-20261117.json` | 45 dòng bổ sung thành 260 |
| `official-family-detail-links.csv` | URL trang hãng cho bộ nghiên cứu ban đầu |
| `deep-candidates-260.csv` | Tình trạng tải nguồn/độ phủ specs của 260 dòng |
| `deep-extracted-specs.csv` | 3.033 dòng raw HTML + PDF |
| `deep-document-evidence.csv` | URL PDF, trạng thái tải, hash, số trang |
| `deep-image-evidence.csv` | URL ảnh ứng viên — chưa có quyền dùng |
| `deep-raw-html-tables.json` | Bản bảng HTML nguyên cấu trúc cho kiểm toán |
| `deep-pdf-evidence.json` | Metadata PDF và đoạn trích có truy vết |
| `more-official-product-links.csv` | 249 URL bổ sung sau so URL |
| `new-deep-official-pages.csv` | Metadata 249 trang bổ sung |
| `new-deep-extracted-specs.csv` | 3.919 dòng specs HTML mới |
| `new-deep-html-tables.json` | Bảng HTML mới để kiểm tra cột |
| `new-deep-pdf-urls.csv` | 785 link PDF ứng viên |
| `new-deep-image-urls.csv` | 444 link ảnh ứng viên |
| `huawei-model-level-specs.csv` | 509 thông số bám cột model từ Huawei |
| `normalized-models-staging.csv` | 217 khóa model, trạng thái ẩn/chưa duyệt |
| `normalized-model-specifications.csv` | 3.780 dòng specs gắn model, chỉ nghiên cứu |
| `normalized-models-summary.json` | Báo cáo các xung đột và overlap |
| `*-summary.json` | Biên bản số liệu thực chạy |

## Một số nguồn chính thức đã kiểm tra

- Huawei SUN2000-MB0: https://solar.huawei.com/en/products/SUN2000-12-15-17-20-25K-MB0/specs/
- Huawei SUN5000-MB0: https://solar.huawei.com/en/products/SUN5000-Series/SUN5000-17-25K-MB0-specs/
- Huawei SUN5000-MAP0: https://solar.huawei.com/en/products/SUN5000-Series/SUN5000-8-12K-MAP0-specs/
- Huawei Dongle WLAN-FE: https://solar.huawei.com/en/products/SmartDongle-WLAN-FE/specs/
- Deye inverter sản phẩm: https://vi.deyeinverter.com/product/
- Growatt chính hãng: https://en.growatt.com/products
- SolaX chính hãng: https://vn.solaxpower.com/products.html
- SOFAR: https://www.sofarsolar.com/product/index.html
- Hober HSPH11KH: https://hobersolarpump.com/products/solar-pumping-inverter/hsph11kh/
- GoodWe ESA datasheet VN G10: https://vn.goodwe.com/Ftp/Downloads/Datasheet/VN/GW_ESA-261kWh_Datasheet-VN.pdf

**Ví dụ QA đã đối chiếu Huawei:** SUN2000-12K-MB0 công suất AC 12kW, PV khuyến nghị tối đa 18kWp, hiệu suất tối đa 98,4%, 2 MPPT, 1100 Vdc tối đa; SUN2000-25K-MB0 25kW, PV 37,5kWp, hiệu suất 98,4%, 2 MPPT, 1100 Vdc tối đa. Dữ liệu nằm trong `huawei-model-level-specs.csv`.

## 3 xung đột cần chặn nhập

- Deye `Mặt trời-14K-SG05LP3 -Eu-SM2`: khác biệt số chuỗi MPPT, dòng PV, trọng lượng giữa các trang/bảng (có thể là khác phiên bản).
- Deye `Sun-BK80-2.56KWH-EU-AM4-18L`: khác kích thước/giao tiếp/điều kiện nhiệt.
- Deye `Sun-Bk160-2.0kwh-Eu-AM2`: khác kích thước/giao tiếp.

Giữ toàn bộ giá trị và nguồn trong `normalized-model-specifications.csv` (`qa_status=CONFLICTING_VALUES`). Không được chọn một giá trị ngẫu nhiên, nhất là kích thước/điện áp/bảo vệ.

## Cổng chặn dữ liệu trước khi nhập CMS

1. Khớp chính xác **brand + model code + revision/region/variant** với datasheet, đặc biệt với group có nhiều model và tiêu đề HTML bị dịch máy.
2. So trùng **399 dòng** trong Excel mới nhất, không chỉ 353 visible. Nếu trùng thì đề xuất cập nhật có kiểm soát, **không tạo bản ghi mới**.
3. Kiểm rõ hãng và quyền sử dụng ảnh. OG-image có thể chỉ là logo chung. 572 URL ảnh tìm thấy **không đồng nghĩa 572 ảnh hợp lệ**.
4. Kiểm tra PDF đúng chính xác mã, revision, datasheet vs manual/marketing PDF. Trên site khác phiên bản/vùng: phân cột theo revision.
5. Nội dung tiếng Việt phải có ứng dụng phù hợp, hướng dẫn lựa chọn, phụ kiện tương thích, nguồn tài liệu, không điền thông số suy đoán.
6. Trạng thái sản phẩm catalogue-only **không có giả định giá/tồn kho**, không cho mua ở backend, không có `InStock` trong JSON-LD, không vào Merchant Center.
7. Sau QA: tạo bản nháp `is_visible=false`, `quote_only=true` hoặc trạng thái commerce mới; chỉ bật hiển thị khi có người phê duyệt.

## Các lệnh tái chạy (có truy cập internet)

```bash
cd /home/admin01/src/Web_PTS_PHP
python3 scripts/content/crawl-official-catalogue-20261117.py
python3 scripts/content/enrich-official-catalogue-20261117.py
python3 scripts/content/crawl-official-specs-deep-20261117.py
python3 scripts/content/discover-more-official-products-20261117.py
python3 scripts/content/crawl-new-official-pages-20261117.py
python3 scripts/content/crawl-huawei-variant-specs-20261117.py
python3 scripts/content/build-catalogue-staging-models-20261117.py
```

Script không có lệnh migrate, thay đổi SQL, import CMS, deploy hay xử lý media production. Phụ thuộc BeautifulSoup và pypdf được cài **tách biệt** ở `/home/admin01/.cache/pts-catalog-crawl-libs`.

Crawl không vượt qua trang trả 403, không dùng key/secret, không giả định kết quả HTTP 200 là chứng nhận sản phẩm. Quan sát tại 17/11/2026, không tự coi là catalogue vĩnh viễn.
