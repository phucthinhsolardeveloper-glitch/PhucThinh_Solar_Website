# Living Energy 3D trên trang chủ

Trạng thái: tích hợp vào Nuxt, chưa deploy production.

## Vị trí và trải nghiệm

Trang chủ đặt không gian 3D tràn viền sau danh mục sản phẩm và trước campaign.
Không có tiêu đề hoặc vỏ card hiển thị quanh mô hình. Chạm hoặc nhấp vào cảnh
để hiện đồng hồ, bốn kịch bản, hành trình bốn bước và điều khiển; nút biểu tượng cũng mở các công cụ
cho người dùng bàn phím. `EnergyScene3DExperience.vue` nhúng viewer tĩnh tại
`frontend/public/pts-living-energy/`. Mô tả giới hạn mô hình vẫn có cho trình đọc màn hình.

Chế độ `?embed=1` giữ mô hình, bốn kịch bản PV ban ngày / pin lưu trữ / điện lưới
/ mất điện, thao tác xoay và khám phá thiết bị. Đồng hồ mặc định đi theo giờ
Pleiku, ghi rõ ánh sáng chỉ là mô phỏng; người dùng có thể chuyển sang tua giờ.
Phần kể chuyện cuộn và chrome của viewer độc lập được ẩn để tránh cuộn lồng.
Cuộn chuột trên cảnh tiếp tục cuộn trang, không bị thao tác zoom giữ lại.

Khung 3D hiện poster trước. Iframe chỉ được nạp khi section ở gần viewport và trang
đã ngừng cuộn; poster được giữ đến khi viewer gửi `pts-living-energy:ready`. Thiết bị
RAM ≤ 2GB, ≤ 2 nhân hoặc bật tiết kiệm dữ liệu giữ poster và có nút "Xem mô hình 3D".

Điều khiển của bản nhúng là các nút gốc, được gom vào một thanh gọn nằm trong sân khấu 3D (`.embed-dock`) và luôn hiển thị. Bóng đổ và dòng năng lượng được bật; bộ điều tốc hạ độ phân giải rồi tắt dòng chuyển động nếu máy không giữ nổi nhịp.

Iframe dùng app `baseURL` để hoạt động dưới subpath. IntersectionObserver của trang
chủ gửi visibility cùng origin cho viewer; renderer dừng khi sân khấu ra khỏi
viewport hoặc tab ẩn và tiếp tục khi người dùng quay lại. Bản nhúng tách lớp (`src/static-layer.js`): phần đứng yên được vẽ một lần vào ảnh có MSAA 4x, độ nét 1.25–2x và giữ cả màu lẫn độ sâu; mỗi khung chỉ vẽ dòng năng lượng đang chuyển động lên trên, khoảng 42 draw call. Chỉ khi kéo xoay mới vẽ lại toàn cảnh; GPU chậm thì hạ tối thiểu 0.8x trong lúc kéo và trở về ảnh nét ngay khi dừng. Không dùng `PointLight` và `backdrop-filter`, kính không qua pass transmission. Trang cha
báo `pts-living-energy:scrolling` để viewer nhường luồng chính khi cuộn; wheel không đánh
thức vòng vẽ. Shader được biên dịch bằng `compileAsync` trước khi hiện. Mesh tĩnh được gộp
sau khi đổi sang Float32 vì GLB đã quantize; không gọi `applyMatrix4` trực tiếp trên
geometry gốc. Các mesh trang trí tĩnh cùng vật liệu được gộp
trong runtime; đo local toàn cảnh khoảng 280 draw call (trước đó 358).
Trải nghiệm tuân theo `prefers-reduced-motion`; asset font
dùng bộ Be Vietnam Pro self-host sẵn trong app.

## Dữ liệu và giới hạn

Đây là mô hình nhà minh họa, không phải công trình khách hàng, mẫu sản phẩm hay
digital twin đã hiệu chuẩn. Không hiển thị công suất, sản lượng, hiệu suất, tiết
kiệm hoặc dữ liệu telemetry. Sơ đồ backup chỉ minh họa tải ưu tiên với inverter
và cấu hình tương thích; không đại diện thiết kế đấu nối thực tế. Các giả định
góc nắng/hướng mô hình trong viewer không được trình bày như dữ liệu vị trí thật.

## Runtime và phát hành

Nuxt chỉ phục vụ runtime cần thiết: viewer, mã Three.js/addons, GLB, poster mặc
định, route contract tối giản và giấy phép MIT của Three.js. Không đưa file
Blender, GLB nguồn thô, ảnh QA hay metadata provenance workstation vào `public`.
Khi thay model hoặc viewer, cập nhật đồng bộ GLB, route contract, poster, giấy
phép nếu cần và giữ đường dẫn tĩnh tương đối để hỗ trợ `app.baseURL`.

### Ngân sách tải (đo 2026-11-06)

| Asset | Trước | Sau |
| --- | --- | --- |
| `pts-technical-v05.glb` | 4.1 MB | 1.68 MB (meshopt, gzip ~1.1 MB) |
| poster | `pts-v05-day.png` 1.9 MB | `pts-v05-day.webp` 60 KB, cùng kích thước 1560×1020 |
| `vendor/three.core.js` + `three.module.js` | 2.1 MB | minify bằng esbuild 0.28.2 (~0.8 MB) |

Tổng byte tải lần đầu trong phép đo local: 8.1 MB → 2.95 MB. Phép đo dùng
Chromium headless với SwiftShader, nên chỉ so sánh tương đối; chưa đo trên GPU thật.

Cách tái tạo GLB nén: dùng `living-energy-compress-glb.cjs` cạnh tài liệu này
(chạy ngoài repo, xem hướng dẫn trong đầu file). Không dùng trực tiếp
`gltf-transform optimize`/`meshopt` CLI: bước quantize tách 33 node `PHOTON__*` đang
được animate thành node con không tên, làm lệch định tuyến theo tên và khung hình
mặc định của cảnh. Script gộp lại các node đó và từ chối ghi file nếu tập tên node
đổi. Khi đổi GLB, tăng `v=` trong `EnergyScene3DExperience.vue` và `index.html`.

Bản minify three.js: `esbuild <file> --minify --format=esm --legal-comments=inline`;
đã so sánh cảnh render và debug state với bản gốc, không lỗi console.

Trước deploy production cần review visual responsive trên 320/390/768/1440px và
đánh giá GPU/độ tải trên thiết bị Android cấu hình thấp. Tài liệu handoff hiện có
ghi nhận kiểm tra Chrome desktop/mobile giả lập; GPU điện thoại thực và topology
điện chính xác theo từng nhà sản xuất vẫn chưa được xác minh. Production chưa
được thay đổi bởi lần tích hợp này.
