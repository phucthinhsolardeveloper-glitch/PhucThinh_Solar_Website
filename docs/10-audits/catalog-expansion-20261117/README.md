# Audit & Kế hoạch mở rộng catalogue Phúc Thịnh Solar — 17/11/2026

**Trạng thái: nghiên cứu + crawl read-only trên máy công ty; CHƯA nhập DB, CHƯA đổi CMS, CHƯA sửa production.**
**Source:** `/home/admin01/src/Web_PTS_PHP`; frontend Nuxt 4/Vue 3, backend Laravel 13/Filament/MariaDB.
**Nguồn kiểm tra website:** public API `https://phucthinhsolar.com/_api/catalog?per_page=48&page=N`. Không dùng chính website PTS để xác minh thông số kỹ thuật của hãng.

## 1. Kiểm kê production

- API catalogue tại thời điểm audit: **353 sản phẩm đang công khai**, 353 ID không trùng.
- **346** sản phẩm hiển thị giá; **7** chỉ báo giá. Không suy ra số tồn kho hay tổng bản ghi DB từ API này.
- Theo danh mục: đèn NLMT **203**, phụ kiện **59**, inverter **27**, bơm NLMT **24**, tấm pin **23**, pin lưu trữ **10**, quạt **7**.
- Theo 10 hãng xuất hiện trong bộ lọc API: ORIANA **138**, BLUE CARBON **79**, SolaX **40**, JINDIAN **31**, EPSIVO **16**, Hober **8**, LONGi **7**, Astronergy **5**, Canadian Solar **3**, AESOLAR **1**. Tổng **328**; **25** bản ghi khác không nằm trong 10 hãng này (có thể thuộc hãng bổ sung/không gán; cần đối chiếu DB, không khẳng định là không gắn hãng).

**Khoảng trống:** inverter và battery/ESS cực ít hãng công khai; thêm thiết bị cốt lõi tạo giá trị hơn bổ sung thêm hàng trăm mã đèn. Ưu tiên thêm dòng hybrid, on-grid, backup, ESS C&I, sạc EV, EMS, optimizer, gateway, solar pump equipment.

## 2. Kết quả nghiên cứu/crawl

- `manufacturer-seeds.json`: **215 dòng/nhóm model** từ **19 thương hiệu** có trang nguồn đề xuất, trong đó 14 hãng đợt đầu; 5 hãng bổ sung Hober, Sharp, TCL Solar, JINDIAN, BLUE CARBON.
- **ORIANA** (hãng thứ 20 trong ảnh): chưa xác thực được catalogue do chính hãng vận hành; không dùng nhà bán lẻ thứ ba làm nguồn xác nhận thông số. Tiếp tục xử lý với datasheet của nhà cung cấp có quyền cấp.
- `official-family-candidates.csv`: **135 dòng khớp tên trong văn bản trang chỉ mục hãng** và **80 dòng cần kiểm tra lại**; đã crawl được **15/19** chỉ mục qua WSL trong lần chạy gần nhất.
- `official-family-detail-links.csv`: **99/215** dòng có URL chi tiết khớp từ anchor chính hãng, còn lại chưa tìm được đường dẫn tương ứng.
- `official-detail-page-samples.json`: đã mở **22/22 trang mẫu trả HTTP 200**, **9/22** có ít nhất một URL có hình thức đường dẫn PDF/tài liệu, **12/22** có URL ảnh Open Graph (nhiều là LOGO, không được coi là ảnh sản phẩm). Tất cả cần kiểm tra nội dung thật, phiên bản, ảnh, quyền sử dụng trước khi sản xuất trang sản phẩm.
- `official-page-fetch-evidence.json` / `enrichment-run-log.json` ghi lỗi HTTP/SSL/timeout; Astronergy và Sigenergy bị 403, Sharp SSL error, Sungrow timeout/404 ở endpoint ban đầu. Các hãng vẫn có dấu vết qua nghiên cứu web nhưng **không đánh đồng với crawl thành công**.

**Giới hạn đặc biệt:** Đây là tập **family/series**, nhiều chuỗi chứa nhiều SKU công suất khác nhau (ví dụ `SUN2000-5/6/8/10/12K-MAP0`). Không tự tách biến thể nếu chưa có datasheet chính hãng và mã định danh đầy đủ. Kết quả so tên không đủ kết luận một dòng sản phẩm mới hay chưa có trên PTS, cần so theo `brand + manufacturer_model + version/region`.

## 3. Danh sách 20 hãng và hướng bổ sung

| Hãng trong ảnh | Hướng nghiên cứu chính |
|---|---|
| Sungrow | String inverter 1/3 pha, hybrid SH, SBR/SBH battery, C&I ESS, EV charger |
| Sharp | Tấm pin, battery, power conditioner; **model từ thị trường Nhật, phải xác minh Việt Nam** |
| Huawei | SUN2000 inverter, LUNA2000, optimizer, SmartGuard, charger, C&I ESS |
| Growatt | MIN/MOD/MID inverter, ARK/APX, MINA/MODA, WIT/RISE ESS |
| GoodWe | DNS/SDT grid, ES/ET hybrid, Lynx batteries, ESA C&I ESS, EV charger |
| SOFAR | PV inverter, ESI/HYD storage inverter, BTS/SF/GTX batteries, PowerMagic |
| Anker SOLIX | X1 storage, Solarbank, micro PV modules; **ràng buộc thị trường EU/US cần xác minh** |
| Deye | SUN hybrid LV/HV 1/3 pha, grid, off-grid, batteries/ESS |
| Sigenergy | SigenStor, SigenStack/Cube, inverter, EV DC/AC, gateway/EMS |
| Hober | Solar pump inverter theo model HSPH và hệ bơm; tham chiếu duty point bơm |
| EPSIVO | Solar pump drive IP65, all-in-one inverter/battery |
| SolaX | X1/X3 hybrid, X-ESS, TRENE/AELIO, battery, EV charger, accessories |
| TCL Solar | T Class/T5/E Class panels, X1/X5/W10 storage, Energy Manager |
| Astronergy | ASTRO N7/N8 modules; tách model và công suất từ datasheet |
| AESOLAR | Meteor/Comet modules, kính đôi; datasheet và region |
| Canadian Solar | TOPBiHiKu/HiKu series, cell/power variants; có nguy cơ trùng |
| LONGi | Hi-MO/X series, LR7/LR8; cần lọc trùng theo mã LR cụ thể |
| ORIANA | Đèn/quạt đang nhiều; yêu cầu nhà cung cấp gửi catalogue mới có xác thực |
| BLUE CARBON | Đèn công trình, ESS, hệ PV-storage; hiện nhiều sản phẩm đèn |
| JINDIAN | Đèn pha/đường/sân vườn, mã JD-88L, JD-9300, JD-FX... |

## 4. Ví dụ trang hãng đã truy xuất

| Hãng | Model / dòng | URL nguồn |
|---|---|---|
| Deye | SUN-5/6/8/10/12/15/20/25K-SG01HP3-EU-AM2 (nhiều biến thể) | https://vi.deyeinverter.com/product/three-phase-high-voltage-hybrid-inverter/sun5-6-8-10-12-15-20ksg01hp3euam2-520kw-three-phase-2-mppt-hybrid-inverter-high-voltage-battery.html |
| GoodWe | ESA 125kW/261kWh: có model GW125/261-ESA-LCN-G10 và G11, trang hãng có khác biệt IP quảng cáo với bảng thông số; **chặn xuất bản trước khi đối chiếu datasheet** | https://vn.goodwe.com/esa-125kw-261kwh |
| Hober | HSPH11KH, 11kW solar pump inverter | https://hobersolarpump.com/products/solar-pumping-inverter/hsph11kh/ |
| Hober | HSPH11KL, 11kW solar pump inverter | https://hobersolarpump.com/products/solar-pumping-inverter/hsph11kl/ |
| Huawei | SUN2000-12/15/17/20/25K-MB0 (cần tách model) | https://solar.huawei.com/en/products/sun2000-12-15-17-20-25k-mb0/ |
| Growatt | APX HV Battery | https://en.growatt.com/products/apx-hv-battery |
| SolaX | X1-HYB LV | https://vn.solaxpower.com/products/x1-hyb-lv.html |
| TCL Solar | W10 ESS C&I | https://www.tclsolar.com/products/tcl-w10 |
| SOFAR | BTS E5-E20-DS5 battery | https://www.sofarsolar.com/product/residentialstorage/battery/BTS%20E5-E20-DS5.html |
| JINDIAN | JD-9300 | https://www.zsjdtyn.com/jd-9300.html |

**Lưu ý phân loại:** trang sản phẩm hãng chứng minh sự tồn tại của *dòng/model*, **không chứng minh công ty phân phối, còn hàng, có bảo hành tại Việt Nam, có quyền sử dụng ảnh, hay giá đang bán**.

## 5. CMS: thiết kế đề xuất (chưa triển khai code)

Tách 3 trục độc lập, không chỉ dùng `quote_only`:
1. `is_visible`: hiện/ẩn public; giữ như hiện tại. Mặc định import mới ở `false`.
2. `commercial_status`: `selling` (giá được phép bán), `quotation` (thương mại theo báo giá), `catalogue` (trưng bày tham khảo, **chưa xác nhận phân phối**).
3. `verification_status`: `research` -> `official_source_found` -> `datasheet_verified` -> `media_qa_passed` -> `approved_for_public` (phải có người duyệt).

Cần giữ `quote_only` tương thích trong giai đoạn chuyển đổi. Không dùng `commercial_status=catalogue` để thể hiện hàng sắp về, hết hàng hay phân phối theo khu vực; các trạng thái đó là trục khác.

**Các cột thêm nên cân nhắc:**
- `manufacturer_model`, `model_variant`, `model_region`, `source_url`, `datasheet_url`, `datasheet_revision`, `source_checked_at`
- `commercial_status`, `verification_status`, `reviewed_by`, `reviewed_at`
- `image_source_url`, `image_licensing_status`, `manufacturer_distributor_status` (nếu có chứng cứ)
- Mở rộng trên model/document tables riêng nếu một SKU nhiều revision/datasheets/images.

**Hành vi public:** card/PDP dùng chung layout. Với `catalogue`:
- Hiện đầy đủ thông số đã xác minh, ảnh QA-pass, ứng dụng, datasheet, so sánh, sản phẩm tương đương.
- Không có giá giả / số lượng / mua ngay / thêm giỏ; CTA **"Liên hệ tư vấn sản phẩm"** và ghi rõ nhỏ gọn **"Khả năng cung cấp và cấu hình sẽ được xác nhận khi tư vấn"**.
- Form lead nhận `product_id`, mã hãng, `commercial_status`, nguồn/UTM; gán đúng sale.
- Không tự hứa `còn hàng`, `bảo hành chính hãng` hoặc `đại lý ủy quyền` khi chưa có bằng chứng.

**Guard bắt buộc:**
- `backend/app/Domain/Catalog/MerchantFeed.php`: loại tuyệt đối `catalogue`; code hiện hardcode `in_stock` cho mọi sản phẩm vào feed.
- `frontend/app/composables/useProductDetailData.ts`: chỉ sinh JSON-LD `Offer` / `https://schema.org/InStock` khi thật sự có giao dịch có thể đáp ứng; giữ Product schema mà không làm giàu offer sai cho catalogue.
- `backend/app/Domain/Commerce/Cart.php` và luồng checkout: khóa catalogue **từ server** dù request giả mạo, không chỉ ẩn nút FE.
- Catalog DTO, card, PDP, compare, related, search, category counts, sitemap SEO và preview phải đồng bộ type/status.
- Google feed đang dựa vào `quote_only` và giá; bổ sung guard rõ ràng theo `commercial_status` tránh sản phẩm catalogue vô tình lọt qua khi nhân viên điền giá.
- Build test read-only; không sửa production trước approval/QA.

## 6. Thứ tự nhập và quy tắc kiểm định

**Wave A (ưu tiên cao):** 20–30 sản phẩm từ Sungrow, Huawei, GoodWe, Growatt, Deye, SOFAR, Sigenergy; chia hybrid/on-grid/battery/ESS/phụ kiện. Mỗi mã cần model riêng chứ không đăng một dòng đa công suất thành một SKU bán.
**Wave B:** tấm pin LONGi, Canadian Solar, Astronergy, AESOLAR, Sharp, TCL Solar; lọc trùng mã thực, datasheet power & kích thước.
**Wave C:** Hober + EPSIVO bơm và inverter bơm; mô tả theo yêu cầu lưu lượng/cột áp, không lẫn inverter bơm với bơm.
**Wave D:** JINDIAN, BLUE CARBON, ORIANA: chỉ bổ sung những model khác công năng/thiết kế thực; ưu tiên ảnh gốc và thông số thực.

**Cổng QA 100 điểm (đề xuất):**
- Mã hãng đầy đủ, đúng biến thể/dòng và không trùng: 25
- Datasheet chính hãng/phiên bản/thị trường, kiểm tra thông số: 25
- Ảnh đúng sản phẩm, đủ độ phân giải, quyền dùng rõ, không nhầm logo: 20
- Nội dung tiếng Việt hữu ích (ứng dụng, so sánh, tương thích, SEO): 15
- Kiểm thử CMS/CTA/lead/merchant/cart/schema.org/mobile: 15

**PASS ≥90, không có lỗi nghiêm trọng**. Nếu sai model, ảnh sai mã, nguồn không đáng tin hoặc giá/tồn kho bị thể hiện sai: STOP-SHIP dù điểm cao.

## 7. File, lệnh và an toàn

### Dữ liệu
- `public-products-snapshot.json`: snapshot 353 sản phẩm công khai.
- `public-products-inventory.csv`: 353 dòng từ API.
- `manufacturer-seeds.json`: 215 dòng sơ tuyển của 19 hãng.
- `official-family-candidates.csv`: kết quả match tại trang chỉ mục.
- `official-family-detail-links.csv`: URL chi tiết cho 99 dòng.
- `official-detail-page-samples.json`: metadata + PDF/image candidate từ 22 trang mẫu.
- `manufacturer-media-qa-queue.csv`: hàng đợi đối chiếu ảnh Open Graph (có thể là logo chung), PDF và quyền sử dụng.
- `official-page-fetch-evidence.json`, `crawl-run-log.json`, `enrichment-run-log.json`: log trạng thái.
### Chạy lại (từ repo root)
```bash
python3 -m py_compile scripts/content/crawl-official-catalogue-20261117.py \
    scripts/content/enrich-official-catalogue-20261117.py
python3 scripts/content/crawl-official-catalogue-20261117.py
python3 scripts/content/enrich-official-catalogue-20261117.py
```
Scripts chỉ GET các nguồn hãng được cấu hình; **không viết database, không nhập sản phẩm, không thay giá, không tải ảnh vào production**.

## 8. Tiêu chí hoàn thành triển khai sau này

- Chốt chính sách thương mại/nguồn thông tin và người duyệt.
- Migrate CMS + backend DTO với default ngược tương thích, không làm thay đổi 353 sản phẩm hiện có.
- Test ít nhất 3 loại: selling (giữ logic mua), quotation (báo giá), catalogue (lead-only, không cart, không feed, không false in_stock).
- Import idempotent theo khóa hãng + mã model + biến thể; dry-run tạo báo cáo conflicts trước khi ghi.
- QA model/datasheet/media/SEO/lead trước khi bật `is_visible`.
- Smoke test local/staging rồi deploy có snapshot backup và rollback; production chỉ khi được chủ project chấp thuận.

**Mức hoàn thành hiện tại:** đã kiểm kê website, crawl sơ tuyển và xác lập các nguồn/URL mẫu; **chưa** xác minh đầy đủ 215 biến thể SKU, **chưa** download + QA ảnh, **chưa** tạo 215 sản phẩm trong CMS, **chưa** chạy migration, **chưa** triển khai.
