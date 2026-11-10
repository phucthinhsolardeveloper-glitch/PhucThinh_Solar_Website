# PTS SOLAR ROOF STUDIO — BLUEPRINT v1.0

**Ngày:** 05/11/2026  
**Trạng thái:** KẾ HOẠCH / CHƯA TRIỂN KHAI  
**Phạm vi:** Solar Roof Configurator cho website Phúc Thịnh Solar; chuẩn bị hợp đồng tích hợp Energy Estimator; PTS 3D Brand Signature là dự án tiếp theo, KHÔNG nằm trong phạm vi triển khai hiện tại.  
**Mục tiêu tài liệu:** một nguồn duy nhất để giao nhiệm vụ, không quyết định lại schema, tổ chức UI, trách nhiệm module và tiêu chí nghiệm thu.  
**Quy tắc:** chưa sửa website, Blender, VPS, staging hoặc production. Nội dung code bên dưới là *hợp đồng thiết kế*, không phải code đã chạy.

## 0. Chốt nguyên tắc và thứ tự

1. Energy Digital Twin là lane riêng đang có owner khác. Không sửa, không save đè, không lấy bản đang phát triển làm dependency bắt buộc.
2. Roof Studio là sản phẩm tương tác: một cấu hình `RoofProjectV1` → sơ đồ 2D, 3D, bố trí module PV, mô phỏng sản lượng và báo cáo đều dùng **cùng nguồn dữ liệu**.
3. Blender làm asset tĩnh/dựng sẵn; mesh mái, tấm pin lặp, bố trí và camera do Three.js/TresJS chạy theo tham số ở browser.
4. Geometry/Layout Engine là logic thuần, độc lập Three.js và độc lập Energy Estimator. Không suy ra kết quả tính toán từ vị trí của mesh trên màn hình.
5. Estimator là adapter thay thế được: cơ sở dữ liệu PV/hiệu năng ở một module riêng; PVGIS 5.3 làm nguồn năng lượng ban đầu, không tính tiền/hoàn vốn thiếu giả định.
6. Nuxt hiện tại là chủ dự án: `Web_PTS_PHP/frontend/`, **không** dùng repo Nuxt migration cũ và **không** làm landing website riêng. Không sửa header/footer/home nếu không được phê duyệt.
7. Tài liệu này là *spec v1*; khi triển khai các task chỉ được thêm quyết định dưới mục “Decision log” và cập nhật lại chính tài liệu, không sinh v1-final-v2.

## 1. Kiểm kê nguồn có sẵn (đã đọc chỉ-đọc ngày 05/11/2026)

### 1.1 Máy công ty
- Repo: `/home/admin01/src/Web_PTS_PHP` — nhánh `master` tại thời điểm rà soát; **không chỉnh**.
- Nuxt source: `frontend/app/`, server routes: `frontend/server/routes/`, shared contracts: `frontend/shared/contracts/`.
- Dependencies `frontend/package.json`: `nuxt ^4.6.0`, `vue ^3.5.0`, `@nuxt/ui ^4.12.0`, `motion-v ^2.9.0`, `tailwindcss ^4.3.3`, TypeScript ~5.9.3.
- **Chưa thấy `three`, `@tresjs/nuxt` trong package.json**. Đây là dependency đề xuất khi bắt đầu code, không được hiểu là đã cài.
- Experience-V3 có: `frontend/app/components/experience-v3/EnergyStageSection.vue`, `EnergySceneStage.vue`, `EnergyInspector.vue`, `EnergyModeSwitch.vue` và `frontend/app/pages/experience-energy-v3.vue`.
- Design system hiện hữu: `frontend/app/assets/css/tokens.css`, `docs/15-design-delivery/12-PTS-DESIGN-LANGUAGE-2026-11.md` (đối chiếu đầy đủ trước UI PR).
- Quy tắc FE hiện hành: HMR `npm run dev` port 3100 cho preview; backend PHP/CMS giữ nguyên; không rebuild Docker sau từng sửa giao diện.

### 1.2 Máy Thy
- Blender 5.2 LTS, MCP for Blender đã được cài trước đó; đường dẫn add-on `C:\Users\Work\AppData\Roaming\Blender Foundation\Blender\5.2\scripts\addons\blender_mcp.py`.
- Nguồn đã khảo sát: `C:\Users\Work\Documents\PTS-Solar-3D\energy-digital-twin-v05\`.
- Nguồn Blender chính nhóm Energy: `PTS_Technical_Realism_V05_Final.blend`; GLB web `models/PTS_Technical_Realism_V05_Final.glb`.
- `README_V05.md` quy định giữ **một Blender source** cho lane Energy; `docs/3D_PRODUCTION_PLAYBOOK.md` quy định QA checkpoints; `PTS_V05_Scene_Contract.json` ghi chi tiết các hotspot, scenario và tên object.
- `QA_V05_Report.json`: 18/18 hạng mục kiểm tra scene PASS; GLB ~3.12 MB, 421 nodes, 407 meshes, 42 materials, 33 animations. **Chưa đạt production-ready**; cảnh báo cần kiểm thử GPU mobile và kỹ thuật thiết bị thật.
- Có sẵn 4 tình huống day/night/backup, animation điện AC/DC, tủ, inverter, battery và hotspot; đây là mô hình *minh họa nguyên lý*, không phải thiết kế điện hay mái biến hình.
- Plugin/workflow đã trao đổi: Bool Tool, Sun Position, Node Wrangler, glTF Transform CLI, HDRI/PBR CC0. Kiểm kê và xác minh phiên bản trước task asset thực tế; không coi mọi plugin đang hoạt động chỉ vì đã cài trước đây.

### 1.3 Quy tắc tái sử dụng
| Nguồn | Tái sử dụng | Không làm |
|---|---|---|
| `tokens.css`, design language | Màu, typography, spacing, button, layout rules | Tạo global design system khác |
| Energy-V3 frontend | Ý tưởng stage/inspector và visual grammar | Import nguyên component nếu dính logic day/night/backup |
| V05 GLB | Tham khảo tỷ lệ, nguyên lý, chất liệu, có thể trích asset nhỏ sau khi owner chốt | Nhúng nguyên GLB 407 mesh vào Roof Studio hoặc sửa file V05 |
| Energy `Scene_Contract` | Học naming/hotspot/source contract | Dùng chung `FLOW__` cho năng lượng tính toán Roof Studio |
| Blender 5.2 + MCP | Dựng asset gốc, xuất GLB, render preview | Dùng Blender làm geometry engine runtime trong trình duyệt |
| PHP CMS | Tham chiếu SKU/datasheet đã xác minh, nhận lead sau này | Truy cập DB trực tiếp từ Nuxt hoặc tạo sản phẩm giả |

## 2. Product brief và phân vai

### 2.1 Persona
- **Khách hàng (Quick)**: trong 2–5 phút có dự kiến diện tích, số tấm, kWp, kWh/năm; dễ hiểu, không bị đòi nhiều thông tin kỹ thuật.
- **Sale (Advanced)**: dựng bản tư vấn nhanh, lưu cấu hình và gửi kỹ sư để thẩm định; không hứa giá/hoàn vốn khi chưa có bảng dữ liệu duyệt.
- **Kỹ thuật**: kiểm tra từng mặt mái, khoảng lùi, vật cản, hướng/dốc, model module, độ tin cậy estimate; đánh dấu “cần khảo sát” khi chưa có bản vẽ thực.
- **Quản trị**: quản lý danh mục sản phẩm/datasheet, giả định, phiên bản algorithm, quyền xuất báo cáo; không nhập ID kỹ thuật thủ công trong UI.

### 2.2 Phạm vi MVP cứng
**Có:** chọn bốn *preset* (nhà mái bằng, nhà mái 2 dốc, nhà mái 1 dốc, xưởng mái tôn); ba primitive hình học (flat, mono, gable); chỉnh kích thước, góc dốc, hướng; một vùng cấm hình chữ nhật/đa giác đơn giản; chọn module PV từ catalog đã xác minh hoặc module DEMO được gắn nhãn; tự bố trí panel, xem 2D/3D, điều chỉnh từng panel, kWp, 12 tháng sản lượng, cache PVGIS, lưu/đọc lại bản nháp tại client; copy summary.
**Không có trong MVP:** tự nhận diện mái từ satellite, scan LiDAR, định vị GPS bắt buộc, structural engineering, điện một sợi/chuỗi DC chi tiết, dây/đầu nối thật, bóng đổ chính xác theo giờ, tự tính tối ưu kinh tế ROI, hệ lưu trữ/backup, chốt giá, đặt hàng, tích hợp ZCRM bắt buộc, AI tạo mái không kiểm duyệt.

### 2.3 Bản mở rộng sau MVP
- V1.1: nhiều mặt mái tùy ý; hỗ trợ polygon mái chữ L/chữ U, hip roof; có thư viện chướng ngại vật, xoay nhóm.
- V1.2: chế độ chuyên gia với lối đi/độ nghiêng giá đỡ/điều kiện kỹ thuật; import CSV/DXF có kiểm tra bản quyền và định dạng.
- V2: analysis shading theo địa hình/đối tượng đã đo, điện và tài chính được duyệt, lưu backend & chia sẻ token, kết nối CRM.

**Phân biệt quan trọng:** “mái tôn công nghiệp” là loại *công trình/vật liệu*, không phải topology hình học thứ tư. Preset này dùng cùng primitive mono/gable, material `METAL` và đặc tính kích thước riêng. Không duplicate engine.

## 3. Trải nghiệm & thông tin kiến trúc

### 3.1 Hai chế độ chung project
- `quick`: 5 bước: Địa điểm → Loại mái → Kích thước & hướng → Tấm pin → Kết quả.
- `advanced`: cùng `RoofProjectV1`, thêm inspector của từng facet; vật cản; panel thủ công; lưới đo, ràng buộc/validation.
- Chuyển Quick ↔ Advanced không đổi đơn vị và không xóa dữ liệu; các panel manual lock được giữ nguyên hoặc yêu cầu người dùng xác nhận nếu regenerate thay đổi.

### 3.2 Wireflow — Quick
```
Nhập route /giai-phap/thiet-ke-mai-dien-mat-troi (đề xuất, chưa chốt slug)
  ↓ Intro + 'Bắt đầu thiết kế'
B1 Chọn tỉnh/thành hoặc tọa độ (không bắt buộc địa chỉ nhà)
  ↓ Lưu `site.lat/lon`, timezone=Asia/Ho_Chi_Minh, locationSource
B2 Chọn preset mái (flat, gable, mono, industrial)
  ↓ Tạo facet(s) mặc định từ preset
B3 Nhập kích thước và độ dốc/hướng (2D/3D realtime)
  ↓ Validate → Build facets → Compute usable regions
B4 Chọn panel demo/verified + portrait/landscape + auto-pack
  ↓ Deterministic layout → count + capacity kWp
B5 Kết quả
  ├─ Đủ input → yêu cầu estimate PVGIS qua backend → 12 tháng + tổng năm
  ├─ API lỗi → vẫn hiển thị bố trí/kWp, báo 'chưa có ước tính sản lượng'
  ├─ Không đặt vừa 1 tấm → hiện 'Không đủ vùng lắp đặt', đề xuất chỉnh thông số
  └─ Lưu bản nháp → xuất tóm tắt / gửi tư vấn (lead tùy duyệt)
```

### 3.3 Wireflow — Advanced
```
Bản nháp → Chế độ nâng cao
  ↓ Chọn facet trên mô hình hoặc danh sách facet
Inspector: chiều dài/rộng/dốc/hướng, đổi loại vật liệu, chọn vùng cấm
  ↓ Edit obstacle: select → drag/resize → preview valid/invalid → commit
Layout: auto-fill từng facet hoặc toàn mái
  ↓ Panel manual: select / lock / rotate portrait↔landscape / remove / duplicate when valid
  ↓ Geometry/Layout Validation → invalid highlight + explicit reason
  ↓ Estimate invalidated khi thay đổi layout/azimuth/tilt/power → gọi lại có debounce server
  ↓ Snapshot report và danh sách mặt mái
  ↓ Sales/engineer review: status `NEEDS_SITE_SURVEY`
```

### 3.4 Trạng thái và UX lỗi
- `EMPTY`: chưa nhập đủ dữ liệu; CTA hướng dẫn.
- `EDITING`: mô hình thay đổi tức thì, chỉ tính geometry nội bộ.
- `LAYOUT_READY`: số tấm/kWp tính xong nhưng chưa có sản lượng.
- `ESTIMATING`: spinner chỉ ở khu kết quả kWh, không khóa camera/2D.
- `ESTIMATED`: có estimate trùng `configHash` hiện tại.
- `STALE`: người dùng sửa sau estimate → đánh dấu kWh cũ không còn hợp lệ; không hiển thị như kết quả hiện tại.
- `ESTIMATE_ERROR`: lưu draft, hiển thị reason retry, không đoán kWh.
- `NEEDS_REVIEW`: cảnh báo về mái, độ dốc, cấu trúc, che bóng chưa khảo sát.
- `SAVED`: đã lưu địa phương trình duyệt (hoặc server khi được làm); UI phân biệt rõ với 'đã gửi tư vấn'.

### 3.5 UI Desktop 1440 px
- Header/footer PTS hiện hữu, không được thay.
- Thanh giới thiệu một dòng, tiêu đề ngắn, 'Công cụ ước tính' / badge 'Bản sơ bộ'.
- Grid dưới: phần trái ~65–70% viewport là stage; panel phải ~30–35% là form/inspector có scroll độc lập.
- Stage toolbar: 2D/3D; Orbit/Top; Fit; Reset Camera; lưới đo; Hiện hướng Bắc; screenshot (sau); accessibility fallback.
- Form phải Quick: stepper và tối đa 3–5 trường một bước; keyboard-friendly; `m` và `°` thống nhất.
- Inspector Advanced: tab `Mái`, `Vật cản`, `Tấm pin`, `Kiểm tra`.
- Summary sticky dưới: `[Tấm pin] [DC kWp] [kWh/năm] [Lưu phương án]`; phần kWh rỗng/đang tính/đã cũ phải phân biệt.
- Background white/warm gray grid 1px, navy text; gold cho focus/CTA/active, không phủ gold lên mọi tấm pin; tấm pin navy, đường viền thật mảnh.
- Cảnh 3D có 1 đường chân trời/ground phẳng và shadow nhẹ, không heavy bloom, không parallax toàn trang, không giả dự án thực tế.

### 3.6 Mobile 320–430 px
- Header/footer hiện có; stage 16:10 hoặc cao cố định ~260–340px tùy viewport.
- Toolbar tối giản: 2D/3D, reset, fit; pinch-zoom không xung đột scroll (2D mặc định nếu WebGL yếu).
- Form dưới stage dưới dạng steps hoặc bottom sheet; CTA Next/Back lớn, ít trường.
- Sticky summary compact `N tấm · kWp`, nhấn mở chi tiết; không sticky nhiều thanh chồng nhau.
- Các trường chiều dài/rộng có `inputmode=decimal`, đơn vị ngoài ô nhập; không slider thay thế số chính xác.
- Tap target 44×44px mục tiêu; mọi thao tác có bàn phím/2D thay thế; không bắt buộc orbit để chọn facet.

### 3.7 Copy và độ tin cậy
- `Sản lượng điện dự kiến`, không nói “cam kết”, “chính xác 100%”.
- Hiển thị nguồn PVGIS, phiên bản API, thời điểm tính, location accuracy, giả định loss/che bóng, tình trạng module verified/demo.
- Mọi cụm 'chi phí tiết kiệm' chỉ xuất hiện ở phase tài chính sau khi có bảng giá và phương án self-consumption được phê duyệt.

## 4. Thiết kế đối tượng 3D và Blender handoff

### 4.1 Tách asset khỏi procedural
- **Asset Blender:** mô hình một tấm pin có frame/cell/PBR (generic hoặc theo SKU đã kiểm chứng), bộ mounting rail, clamp, một mẫu nhà nhẹ dạng cảnh trang trí (optional), inverter/storage generic cho phase sau; môi trường HDRI/preview poster.
- **Procedural browser:** roof plane, roof thickness/fascia, ridge, facet mesh, trim, panel placement, highlight, gizmo, dimension line, north arrow, obstacles 2D/3D giản lược.
- **Không nhập nguyên `PTS_Technical_Realism_V05_Final.glb`** vào Roof Studio. Chỉ có thể tái sử dụng một PV prototype hoặc vật liệu đã được owner chốt và tối ưu thành asset nhỏ.

### 4.2 Blender canonical asset cho lane Roof (khi bắt đầu thực hiện)
- Có thể có **một file nguồn độc lập** `PTS_Roof_Studio.blend` để tránh ghi đồng thời `PTS_Technical_Realism_V05_Final.blend` của nhóm Energy.
- Collection `REFERENCE`, `ROOF_PROPS`, `PV_MODULE_LIBRARY`, `MOUNTING_KIT`, `PRESENTATION`, `EXPORT`. Tái sử dụng qua Append/Asset Browser collection, có attribution/licensing.
- Mesh naming `ROOF_PROP__...`, `PVMOD__...`, `RAIL__...`, `HOTSPOT__...`; không đụng tên `FLOW__...` Energy.
- Quy ước hình học: 1 Blender unit = 1 m; panel pivot là tâm, mặt trước hướng +Z trong *local face coordinates* (chốt transform và axis trong GLB contract); origin không “apply scale” mù.
- Export 1 asset GLB tối ưu / category, không 1 file cho mỗi biến thể kích thước; ưu tiên chung geometry/texture khi có thể.
- Mỗi asset kèm `assetId`, source blend, copyright/license, verified product ID hoặc `GENERIC`, bbox XYZ, units, anchors, LOD, material list, glTF extension list, SHA.

### 4.3 Ngân sách đề xuất (chưa phải số đã đo)
- LCP trang thường không phụ thuộc WebGL; Roof Studio route mới lazy-load engine 3D.
- Gói asset đầu tiên nên nhắm khoảng **≤1.5 MB compressed GLB tổng** cho preset cơ bản, dưới 4–5 textures small/atlas khi thực sự cần; số này là mục tiêu để benchmark, không phải tiêu chuẩn cứng nếu chất lượng giảm quá nhiều.
- Dùng `InstancedMesh` khi nhiều panel cùng mesh/material; decal/cell pattern tối ưu bằng texture/geometry share.
- Poster WebP/AVIF dưới ~150 KB mục tiêu; 2D/SVG fallback không phụ thuộc 3D.
- Giảm shadow/antialias/device pixel ratio cho mobile; giải phóng GL context/resource khi rời route; test Android thực.

## 5. Kiến trúc module trong Nuxt (đề xuất, xác minh chính xác trước tạo file)

```
frontend/
  app/
    pages/
      giai-phap/
        thiet-ke-mai-dien-mat-troi.vue      # proposed route; route approval needed
    components/
      roof-studio/
        RoofStudioShell.vue               # orchestrates mode/state
        RoofStudioHeader.vue              # title/steps/source-state
        RoofStageSwitcher.vue             # 2D / 3D
        RoofCanvas2D.vue                  # SVG/Canvas editor fallback
        RoofCanvas3D.client.vue           # TresCanvas; client-only
        RoofToolbar.vue
        RoofStepLocation.vue
        RoofStepType.vue
        RoofStepDimensions.vue
        RoofStepModules.vue
        RoofStepResults.vue
        RoofInspector.vue
        RoofFacetList.vue
        RoofObstructionEditor.vue
        RoofPanelInspector.vue
        RoofResultSummary.vue
        RoofMonthlyYieldChart.vue
        RoofValidationList.vue
        RoofSaveDialog.vue
        RoofDisclaimer.vue
    composables/
      useRoofProject.ts                   # store / undo / schema migration
      useRoofGeometry.ts                  # compute data, not WebGL
      usePanelLayout.ts                   # deterministic pack
      useRoofEstimate.ts                  # async cache state
      useRoofSelection.ts                 # facet/panel selection
      useRoofCamera.ts
    assets/css/
      ...                                 # existing tokens only
  shared/contracts/
    roof-project-v1.ts                    # canonical TS/schema version
    roof-catalog-v1.ts
    roof-estimate-v1.ts
    roof-assets-v1.ts
  lib/roof-studio/                       # domain pure TS; use existing conventions
    geometry/facets.ts
    geometry/transforms.ts
    geometry/polygon-ops.ts
    layout/generate.ts
    layout/collisions.ts
    layout/validations.ts
    solar/normalize.ts
    solar/azimuth.ts
    solar/cache-key.ts
    guards/project-validation.ts
    serialization/migrate.ts
  server/
    api/roof-studio/estimate.post.ts     # Nuxt API; no direct browser call PVGIS
    utils/roof-pvgis-adapter.ts
    utils/roof-rate-limit.ts
  tests/roof-studio/
    geometry.test.ts
    layout.test.ts
    orientation.test.ts
    estimator-adapter.test.ts
    schema.test.ts
    fixtures/
  public/roof-studio/
    assets/                              # generated GLB/preview, deployed only after QA
```

**Quan trọng:** ở Nuxt 4, đường `app/components`, `app/pages`, `server` và `shared` đã có thật. `lib/roof-studio` là *đề xuất*, có thể chuyển sang `shared/` nếu quy ước module trong repo ưu tiên khác. Không tạo file song song trùng chức năng.

### 5.1 Dataflow chặt chẽ
```
User input (UI)
  → validate & normalize command
  → update RoofProjectV1 [rev++]
  → Geometry Engine → Roof Facets + invalid reasons
  → Layout Engine → panel placements + DC capacity
  → render props → SVG 2D & Three 3D (pure projection)
  → normalized EstimatorInput + configHash
  → server PVGIS adapter (only after settle/debounce)
  → normalized EstimatorResult [hash, source, warnings]
  → Result UI + snapshot export
```
- One-way dataflow; 3D không trực tiếp ghi module count/roof dimensions bằng side effect.
- Viewer interaction sinh *command* (`MOVE_PANEL`, `SET_FACET_AZIMUTH`, `ADD_OBSTACLE`...), reducer/domain cập nhật project sau kiểm tra.
- Undo/Redo lưu lịch sử commands/patches giới hạn dung lượng; camera gesture không thêm lịch sử undo.
- Estimate request mang `requestHash`, kết quả cũ đến trễ mà hash khác hiện tại thì bỏ hoặc lưu cache, không thay màn hình hiện hành.

## 6. Data contract chuẩn (phải chốt trước khi 2 agent code độc lập)

### 6.1 Nguyên tắc
- Units: meters trong domain; millimeters từ datasheet chuyển **một lần** ở boundary; Wp số nguyên (module), kWp = Wp/1000, kWh float.
- Location WGS84 lat/lon float; time Asia/Ho_Chi_Minh chỉ presentation; API timestamp UTC ISO8601.
- Azimuth UI: 0=N, 90=E, 180=S, 270=W, theo chiều kim đồng hồ. **Azimuth là hướng pháp tuyến của mặt mái**, không phải hướng cạnh sống mái.
- Pitch: độ từ mặt ngang, 0=flat; facet local coordinates (u,v) nằm trên mặt phẳng mái, đơn vị *mét thực trên mặt dốc*.
- Project stores source inputs + manual overrides; engine *derives* geometry and panel placements; derived result may cache with version/hash; không gửi cả mesh topology vào API.
- ID là UUID kỹ thuật ẩn khỏi UI. Stable `facetId`, `obstacleId`, `panelPlacementId` khi chỉnh không thay ID vô lý.

### 6.2 Typescript interface thiết kế rút gọn
```ts
type RoofTopology = 'FLAT' | 'MONO' | 'GABLE';
type RoofPreset = 'HOUSE_FLAT' | 'HOUSE_GABLE' | 'UTILITY_MONO' | 'INDUSTRIAL_METAL';
type RoofMaterial = 'CONCRETE' | 'TILE' | 'METAL' | 'OTHER';
type RoofMode = 'QUICK' | 'ADVANCED';
type Quality = 'VERIFIED' | 'DEMO' | 'NEEDS_REVIEW';
type Deg = number; // runtime validation [0,360) / pitch [0,90)

type Point2 = { uM: number; vM: number };
type Polygon2 = { outer: Point2[]; holes?: Point2[][] };
interface RoofFacetV1 {
  id: string;
  surfacePolygon: Polygon2; // local slope-plane meters
  worldFrame: {
    originM: [number, number, number];
    uAxis: [number, number, number]; // unit vectors, orthogonal
    vAxis: [number, number, number];
    normal: [number, number, number];
  };
  pitchDeg: Deg;
  azimuthNorthDeg: Deg; // face normal's horizontal bearing
  material: RoofMaterial;
  setbackProfileId: string; // PRESET / REVIEWED rules, not universal legal claim
}
interface RoofObstacleV1 {
  id: string;
  facetId: string;
  polygon: Polygon2;
  kind: 'SKYLIGHT'|'WATER_TANK'|'VENT'|'TREE_SHADOW_ZONE'|'USER_NO_GO';
  clearanceM: number;
  verification: Quality;
}
interface ModuleSpecV1 {
  skuRef: string; // CMS verified ID, or prefixed 'DEMO:'
  lengthM: number;
  widthM: number;
  ratedWp: number;
  sourceDocument?: string;
  verifiedAt?: string;
  quality: Quality;
}
interface PanelPlacementV1 {
  id: string;
  facetId: string;
  center: Point2;
  orientation: 'PORTRAIT'|'LANDSCAPE';
  rotationDeg: number; // local facet plane
  locked: boolean;
}
interface RoofProjectV1 {
  schemaVersion: '1.0.0';
  id: string; revision: number; mode: RoofMode;
  site: { lat: number; lon: number; timezone: 'Asia/Ho_Chi_Minh'; accuracy: 'CITY'|'APPROX'|'SURVEYED'; label?: string };
  building: { preset: RoofPreset; topology: RoofTopology; footprintLengthM: number; footprintWidthM: number; ridgeAzimuthNorthDeg?: number };
  facets: RoofFacetV1[];
  obstacles: RoofObstacleV1[];
  module: ModuleSpecV1;
  layout: { algorithmVersion: string; panelGapM: number; panelPlacements: PanelPlacementV1[] };
  estimatePrefs: { source: 'PVGIS_5_3'; assumedLossPct: number; shading: 'UNKNOWN'|'USER_ESTIMATE'|'ENGINEER_VERIFIED'; customShadingLossPct?: number };
  review: { level: 'CONCEPT'|'NEEDS_SITE_SURVEY'|'ENGINEER_REVIEWED'; notes: string[] };
  updatedAt: string;
}
interface RoofEstimateResultV1 {
  schemaVersion: '1.0.0';
  inputHash: string;
  calculatedAt: string;
  source: { provider: 'PVGIS'; apiVersion: '5.3'; dataset?: string; requestIds?: string[] };
  total: { panels: number; dcKWp: number; annualKWh: number; monthlyKWh: number[] };
  byFacet: { facetId: string; panels: number; dcKWp: number; annualKWh: number; monthlyKWh: number[] }[];
  assumptions: { systemLossPct: number; shadingMethod: string; locationAccuracy: string; mountingChoice: string };
  warnings: { code: string; message: string }[];
}
```

### 6.3 Không dùng type làm bảo mật
- Các TypeScript types phải đi cùng runtime validation (Zod/Valibot chỉ sau khi kiểm tra dependencies và bundle).
- API re-validate tất cả giá trị, không tin nội dung từ browser; giới hạn kích thước mái/panel/obstacle theo điều kiện hệ thống và chống DoS; không allow tool chạy script do người dùng nhập.
- `module.skuRef` là tham chiếu về danh mục do backend trả, không chấp nhận Wp tùy ý tự gắn nhãn sản phẩm thật.

### 6.4 Tương thích estimator hiện có/tương lai
```
RoofProjectV1
   └─ toEstimatorInputV1(project)         → project-specific normalized facts
       └─ EnergyEstimatorPort.estimate()  → RoofEstimateResultV1
          ├─ PvgisV53Adapter             → MVP
          ├─ ExistingPTSCalculatorAdapter → khi đội tính toán bàn giao
          └─ MockDeterministicAdapter     → unit/integration tests, KHÔNG production
```
`toEstimatorInputV1` xuất mảng facet `{lat, lon, kwp, pitchDeg, azimuthNorthDeg, lossPct, metadata}`; không xuất Three.Mesh. `EnergyEstimatorPort` có `version`, `capabilities`, `estimate`, `normalizeError`. Adapter cũ/mới chỉ thay bên trong, UI không sửa.

## 7. Geometry engine — thuật toán & ràng buộc

### 7.1 3 topology, 4 preset
1. `FLAT`: 1 horizontal facet (`pitchDeg=0`), configurable future racking tilt khác roof pitch.
2. `MONO`: 1 planar tilted facet; high/low edge thể hiện bằng worldFrame.
3. `GABLE`: 2 facet nghiêng đối hướng từ ridge; có thể khác span/góc ở advanced; fascia/ridge là representation derived.
4. `INDUSTRIAL_METAL`: **preset** sử dụng MONO hoặc GABLE, mái tôn màu xám, kích thước lớn và layout patterns, không tạo topology mới.

### 7.2 Pipeline hình học
```
Input dimensions / roof preset
 → verify finite numbers and allowable ranges
 → generate footprint/ridge/facets in 3D local coordinates
 → derive each facet `Polygon2`, worldFrame and normal
 → 2D `setback`: inward offset of boundary (by rule profile)
 → subtract no-go obstacles expanded by clearance
 → get valid/usable multipolygons
 → candidate module rectangles using exact module L×W + gaps
 → for each candidate: full polygon containment + collision/no-overlap
 → deterministic placement order, tie-breaking by facet id + u/v
 → optional manual-locked placement validation
 → output placements, warnings, total count, DC Wp
 → render from same facets and placements to 2D/3D
```
- Flat and tilted roofs share local slope-plane geometry. Do not incorrectly divide slope surface area by cos(theta) twice.
- Roof dimensions UI must distinguish **footprint** from **slope surface**. Any value derived from another should be labeled/read-only until Advanced mode.
- Polygon clipping/offset requires robust library or carefully tested algorithm; clipping library alone doesn't supply uniform inward offset. Need selected offset engine and degeneracy tests before implementing freeform.
- For panels tilted on flat roof, use separate `panelTiltDeg` and row-spacing model; MVP can lock tilt to selected racking preset and mark row-shading not assessed, rather than pretend same as flush-mounted roof.
- `manual lock` panels that become invalid after changing dimensions must be flagged (and excluded from valid capacity) or require explicit re-layout choice. Never silently overlap obstacles.

### 7.3 Auto-layout strategy (MVP)
- Candidate patterns: portrait and landscape; deterministic row-first packing aligned to facet local axes.
- Evaluate both patterns by valid number of panels, then tie-break via declared business preference; no invisible optimization rule based on unreleased revenue.
- Consider margins, moduleGap, obstacle clearance; for Quick use one approved clearance profile `DEMO_NEEDS_SURVEY` and visible warning.
- For irregular roof/polygon advanced, scan candidate rectangles, use point-in-polygon + segment intersections to ensure *entire module footprint* lies in valid region.
- Once panel placement is accepted, capacity = sum of rated Wp, not estimated from area ratio.

### 7.4 Geometry test matrix
| Test | Expected invariant |
|---|---|
| Gable with 2 slopes | facets normals horizontal opposite, no gap at ridge |
| Change roof azimuth | geometry rotates world; local panel count remains if dimensions unchanged |
| Resize roof smaller | count never grows for same geometry/preset/spacing unless pattern tie changes under independent optimization, investigate case |
| Add no-go obstacle | no panel polygon intersects obstacle + clearance |
| Rotated module | boundary, gap, collision re-evaluated |
| No enough area | zero panels and explicit error/CTA, no PVGIS call |
| Shared ridge or complex polygon | no duplicate panels / NaN / negative area |
| Manual-locked panel | stable id/position if valid; invalid reason otherwise |
| Zoom/Orbit | project geometry and estimate remain unchanged |

## 8. Energy Estimator — đúng nguồn và tách biệt

### 8.1 PVGIS V5.3 (MVP)
- Base URL: `https://re.jrc.ec.europa.eu/api/v5_3/PVcalc`.
- Inputs required: lat, lon, peakpower (kW), loss (%). Fixed array: `angle` from horizontal, `aspect` 0=S, -90=E, +90=W, `outputformat=json`, `mountingplace` per actual mounting assumptions.
- **Official service forbids browser AJAX/CORS**, so server-side Nitro adapter is required. Official API has 30 req/s per IP, returns 429 and 529 under pressure; app must self-limit lower than that, retry with backoff only for transient errors and respect timeout.
- For multifacet, call once per facet with nonzero kWp or safely aggregate identical `(lat,lon,tilt,aspect,loss,mounting)` groups if result scales linearly within model semantics. Normalize `outputs.monthly.fixed[*].E_m`, yearly `E_y`, input metadata; check exact response schema through fixture at implementation before binding UI.
- Combine per facet: `monthlyTotal[m] = sum(monthlyFacet[m])`; `annualTotal` from sum of annual facet outputs. Never average headings/directions or apply aggregate azimuth.
- City-level geolocation produces city-level estimate only; UI says so.

### 8.2 Azimuth conversion
- UI compass uses 0 North, 90 East, 180 South, 270 West.
- PVGIS uses 0 South, -90 East, +90 West.
- Normalize `aspect = wrapToMinus180Plus180(azimuthNorthDeg - 180)`.
- Test anchors: N=±180; E=-90; S=0; W=+90.
- Do not confuse roof ridge angle with facet normal bearing.

### 8.3 Cache & concurrency
- Cache key includes `schemaVersion`, engine version, source version, rounded lat/lon precision policy, facet tilt, azimuth, kWp, loss, mounting type, shading inputs, dataset.
- Geometry recalcs can be immediate (WebWorker for large roofs). Estimate calls debounced after stable inputs 500–1000ms **proposal**, only if hash changed; no fetch per slider tick.
- Deduplicate concurrent identical requests; abort stale fetch when feasible; old response never overwrites new revision.
- Server cache TTL configurable (e.g. 7 days for equal estimate query; revalidate when source version changes), bounded size. Avoid persist exact property address without need.
- Errors: INVALID_INPUT/NO_PANELS/NO_COVERAGE/RATE_LIMIT/OVERLOADED/TIMEOUT/REMOTE_ERROR/UNSUPPORTED/STALE; show actionable recoveries.

### 8.4 What is and is not 'accurate'
- PVGIS is a historical-model estimate (location, weather database, slope/azimuth and losses). It is not a roof structural survey, on-site shading survey, performance guarantee or a quote.
- Visualization sun rays / moving shadows are not quantitative yield; no hidden roof-obstacle shadow loss unless implemented and verified.
- If user picks loss %, display it; don't add the same loss twice. If shade adjustment exists, document whether included/excluded from total PVGIS `loss`.
- Finance/payback requires real tariffs, self-consumption/dispatch/load, CAPEX and policies approved by PTS; explicitly postpone to later phase.
- Benchmark PVGIS against PVWatts V8 when local dataset available; PVWatts has API key and possible geographic dataset limitations, so don't assume full Vietnam coverage.

## 9. State machine, commands, side-effects

### 9.1 Commands
`SET_SITE`, `CHOOSE_PRESET`, `SET_ROOF_DIMENSIONS`, `SET_FACET_PITCH`, `SET_FACET_AZIMUTH`, `ADD_OBSTACLE`, `UPDATE_OBSTACLE`, `DELETE_OBSTACLE`, `SET_MODULE`, `SET_PANEL_ORIENTATION`, `AUTO_LAYOUT`, `LOCK_PANEL`, `MOVE_PANEL`, `REMOVE_PANEL`, `UNDO`, `REDO`, `SAVE_DRAFT`, `REQUEST_ESTIMATE`, `RETRY_ESTIMATE`, `RESET_CAMERA`, `SET_VIEW_MODE`.

### 9.2 Side-effect policy
| Command | Geometry | Layout | Estimate | Save |
|---|---|---|---|---|
| Camera / mode | No | No | No | View only |
| Site coordinates | No | No | Invalidate | Project changed |
| Roof dimensions/pitch | Yes | Recompute/validate locks | Invalidate | Project changed |
| Azimuth | Rotate world; facet normals | Count usually unchanged | Invalidate | Project changed |
| Module specs | No | Recompute | Invalidate | Project changed |
| Obstacle | Allowed region | Recompute | Invalidate | Project changed |
| Manual panel move | No | Validate count/collision | Invalidate only if valid capacity/facet grouping changes | Project changed |
| Auto layout | No | Regenerate | Invalidate if system changes | Project changed |

- `REQUEST_ESTIMATE` blocked when project invalid, no modules or no location.
- `SAVE_DRAFT` does not imply uploaded CRM; show storage scope.
- Reset design requires explicit user confirmation; Reset Camera is non-destructive.

## 10. Định nghĩa component và props/event tối thiểu

| Component | Props chuẩn | Event/Output | Empty/error state |
|---|---|---|---|
| `RoofStudioShell` | project, mode | commands, save, estimate | Loading skeleton |
| `RoofCanvas2D` | facets, placements, selected, warnings | select, drag command, zoom | no facet, invalid polygon |
| `RoofCanvas3D` | geometry scene DTO, theme, camera state | select facet/panel, camera changed | no WebGL, asset fail |
| `RoofInspector` | selectedEntity, command capabilities | change committed command | nothing selected |
| `RoofStepLocation` | site, location accuracy | SET_SITE | blank/invalid coords |
| `RoofStepType` | topology, preset | CHOOSE_PRESET | no option |
| `RoofStepDimensions` | footprint, facets | dimensions & orientation commands | invalid ranges |
| `RoofStepModules` | product catalog, layout | SET_MODULE, AUTO_LAYOUT | no verified SKU |
| `RoofStepResults` | result, estimate state | REQUEST/RETRY, EXPORT | stale/loading/error |
| `RoofMonthlyYieldChart` | normalized 12 monthlyKWh + source | None | no estimate |
| `RoofValidationList` | warnings `{code, entityId, severity}` | focus invalid entity | all checks pass |

- Components never call PVGIS directly or mutate shared mesh data as source of truth.
- Introduce `SceneDTO` to convert pure geometry data into Three.js objects; `RoofCanvas2D` uses same DTO.

## 11. Thiết kế UI/visual specification

### 11.1 Tokens and style
- Use existing CSS tokens and `Be Vietnam Pro` (contract V05).
- Navy primary `#0A2251`, deep `#061537`, gold primary `#C9962E`, light gold `#E0B654`; neutrals are sourced from existing design doc (don't invent conflicting global palette).
- Shape: slightly rounded 8–12px controls/card, crisp flat technical visual; no thick border, floating neon, purple gradient, massive glassmorphism.
- Stage grid in neutral gray 1px at low opacity; labels 12–14px, page heading 24–32px responsive; keep minimum readable sizes; use existing design scale.
- Panel color dark blue; selected outline gold; invalid outline red with explanation plus icon (not color-only).
- Motion camera easing modest; respect accessibility preferences and allow pause; no scroll-jacking or forced all-site transition.

### 11.2 Stage layers (z-index logical, not arbitrary CSS values)
1. 3D ground/roof/outline
2. PV panels instances
3. Obstacles + exclusion zones
4. Facet selected highlight
5. Dimension/compass markers
6. Annotation/hotspot overlays
7. Canvas toolbar
8. Warnings and loading overlays (nonblocking camera when estimating)

### 11.3 Required screenshots before FE merge
Desktop 1440 light, Quick step 2, Quick step 4, results loaded; desktop Advanced facet + obstacle edit; mobile 390 Quick step 3 & results, mobile WebGL fallback; empty, no-panels, PVGIS error, stale result; 2D/3D same geometry.

## 12. Test fixtures & signed acceptance

### 12.1 Fixtures
`F01 flat_home_8x12`: regular rectangle, 0° pitch, one facet, no obstacles.
`F02 gable_two_facets`: two symmetric facets, e.g. 25°, east/west azimuth; totals must sum, not average direction.
`F03 mono_east`: east facing normal 90°, PVGIS aspect -90.
`F04 industrial_large`: many panels, stress test instancing & mobile 2D fallback.
`F05 forbidden_zone`: water tank/vent exclusion, all panels outside polygon + clearance.
`F06 narrow_zero`: 0 valid panels, no API call.
`F07 manual_lock_resize`: locked panel becomes invalid after resize, user warning.
`F08 city_location`: estimated production clearly labeled approximate location.
`F09 api_rate_limit`: fake 429/529, backoff or explicit error, data not fabricated.
`F10 stale_race`: request A returns after B; UI only uses hash B.
`F11 accessibility`: keyboard-only all steps & 2D alternative.
`F12 catalog_unknown`: no real SKU -> DEMO tag; not shown as a real PTS product.

**Expected outputs phải được kiểm tra độc lập**, không copy từ chính engine sang test làm đáp án; thiết lập golden fixtures sau khi kỹ sư duyệt default clearance/module kích thước.

### 12.2 Quality gates
- **G0 Brief/source gate**: ảnh/asset có bản quyền rõ, module dimensions verified or Demo; input contract version locked; không ảnh hưởng Energy V05.
- **G1 Core geometry**: 3 topology/4 preset, local coordinate systems and azimuth unit tests pass, no NaN/invalid geometry.
- **G2 Layout**: geometry-based exact count, no collision, locked panels warnings and deterministic re-layout.
- **G3 2D/3D parity**: cùng position/count across view modes; mobile fallback; stable WebGL lifecycle.
- **G4 Estimator**: PVGIS end-to-end response normalized, multi-facet summary, source and warnings, 429/529 handled, stale requests ignored.
- **G5 UX**: Quick 5 steps, Advanced inspector, keyboard/mobile/error states/undo/save all covered.
- **G6 Integration**: isolated route, no header/footer/home regression, typecheck/build/tests, staging QA, performance measurements, rollback; no production until signed off.

### 12.3 Performance budget — proposed not verified
- WebGL only loads on Roof Studio route after user needs it, not in global chunks.
- Benchmark representative desktop/mobile and target 2D interactions feeling immediate; layout for ~100 panels ~<250ms desktop and <600ms mid-range mobile is an **engineering target** to validate, not a guarantee.
- 3D animation stable target 45+ FPS desktop, 25–30+ FPS representative Android when interactive; 2D fallback if not possible. Log hardware, model/panel count, effective pixel ratio.
- Distinguish LCP/INP/CLS performance audits (web vitals) from GPU FPS/frame time. Main site unaffected.

## 13. Roles, work packages and dependencies

### Lane ownership
| Role | Owns | Cannot modify |
|---|---|---|
| ChatGPT / project architect | Spec, source verification, acceptance, interface contracts, integration review | Production deploy / unapproved master |
| Roof-Blender agent | `PTS_Roof_Studio.blend` asset collections, render, GLB export | Energy V05 canonical file during owner work |
| Roof-Geometry agent | pure TS geometry, layout, tests and fixture schema | PVGIS adapter, brand logo project |
| Roof-FE agent | Vue/TresJS UI and 2D fallback, responsive layout | domain models without joint interface review |
| Roof-Estimator agent | PVGIS Nitro API, cache, normalization, reliability | Blender, UI component internals |
| Reviewer/QA (separate agent) | screenshot diffs, bad-case fixtures, performance report | Unapproved automatic merge |

### Task graph
```
T00 read-only snapshot + source-of-truth audit
T01 lock schema v1 + command/event contract + fixtures
   ├─ T02 Blender asset contract & one module pilot
   ├─ T03 geometry primitives / facet transforms
   └─ T04 UV/form wireframes & 2D stage skeleton
T03 → T05 polygon restrictions + auto-layout → T06 2D editor
T02 + T03 + T05 → T07 3D scene integration
T01 + T05 → T08 normalized estimate input → T09 PVGIS Nitro adapter
T06 + T07 + T09 → T10 Quick flow end to end
T10 → T11 Advanced inspector/manual adjustments
T10 + T11 → T12 QA mobile + sources + staging + review
T12 PASS → gate to production proposal (not automatic)
Next project only after T12 acceptance: PTS 3D Brand Signature
```

### Handoff package each task must return
`taskId`, files changed, branch/worktree path, base commit, source asset version/hash, contract version, tests executed and results, screenshots/GLB before-after, known limitations and blockers, impact on Energy lane, rollback directions. No claim “DONE” if no reproducible artifact/test.

### Branch/worktree
- Separate feature branch `feature/pts-roof-studio` created **once** when implementation starts, deriving from approved branch/head of `Web_PTS_PHP`; worktrees only for parallel non-overlapping code lanes.
- For code modules, PR/small merge checkpoints; avoid many long-lived branches. For binary Blender assets, one file owner at a time and Git LFS if used; do not merge `.blend` binary collisions.
- The prior Energy V05 workspace remains read-only for Roof agents until agreed handoff.
- No Docker rebuild per UI change; build at integration gate. Production and staging are protected.

## 14. Hướng tích hợp hệ tính toán sẵn có (khi đội tính toán bàn giao)

**Contract boundary** `EstimatorInputV1 → EnergyEstimatorPort` must remain fixed. On handoff:
1. Inspect calculator inputs/outputs, units, assumptions and temporal resolution; capture tests from its reference data.
2. Add adapter to convert RoofStudio `facets/module/layout` into calculator input, not a second roof configuration UI.
3. Compare 10 known fixtures (single slope, dual slope, shading not assessed, varying loss, no panels) against PVGIS baseline and existing calculator. Differences must be explained by assumptions, not forced to match.
4. If calculator supports hourly energy, attach hourly output as optional extension, keeping original 12-month contract stable.
5. Only when confidence established, unlock finance/self-consumption inputs as separate workflow. No false certainty.

## 15. Đa chiều — tradeoffs và rủi ro

| Góc nhìn | Rủi ro | Biện pháp |
|---|---|---|
| UX khách hàng | Form kỹ thuật quá dài / hỏi tọa độ chính xác | Quick 5-step, city-level default, optional precise location, explain estimate grade |
| Bán hàng | Nhầm estimate với báo giá chính thức | Demo/estimate label, lead review by sale, no auto promises |
| Kỹ sư | Lắp vật lý chưa hợp lệ (kết cấu/clearance/chuỗi điện) | Engineer-review flag, preliminary only, no automatic 'approved install' |
| FE | FPS thấp, WebGL tải mọi trang | lazy-load route, instances, compression, 2D fallback, performance budgets |
| Geometry | Các mặt mái bị biến dạng, panel tràn viền | Pure domain geometry, strict coordinates, unit/golden tests |
| Data | Sai chuẩn Wp/kWp, azimuth, footprint/slope area | Runtime validation, normalized DTO, conversion tests |
| API | PVGIS 429, CORS, 529, network | Nitro adapter, caching, throttling, retry/backoff, offline state |
| Security | MCP có quyền thực thi Blender Python, asset độc hại | Isolated machine/session, only local port, safe mode, validate sources and generated scripts |
| Multi-agent | Hai bên save .blend hoặc thay shared schema | One owner/write lock per binary, schema freeze, task contract |
| Brand | UI mùi AI, 3D lòe loẹt, đổi homepage | Existing tokens, grounded editorial design, one route and limited 3D |
| Legal | Satellite/logo/assets chưa có quyền | Explicit license/provenance manifests; no unlicensed real-project visuals |
| Maintenance | Tạo hàng loạt file `_final_vx` | one canonical blueprint, one canonical .blend per independent lane, Git checkpoints |

## 16. Decision log / chưa khóa

**Đã khóa:** one domain project schema, Nuxt source of truth, 4 presets/3 geometry topologies, Blender assets separate from procedural runtime, PVGIS 5.3 adapter, Quick+Advanced shared data, 2D mobile fallback, no modifying Energy V05 during ongoing work, no brand project until Roof accepted.

**Kiểm tra lúc chuẩn bị triển khai (không cần quyết ngay):**
- Chốt slug route theo router hiện hành và SEO; page pilot vs menu placement.
- Chốt tập SKU/datasheet PTS thật với admin; nếu chưa có thì DEMO specs (không gắn tên thật).
- Chốt clearance profile kỹ thuật cho từng mái và tòa nhà; estimate vs survey/installation.
- Chọn polygon offset/clip library và license, coi bundle size.
- Chốt phiên bản chính xác Three.js/TresJS tương thích Nuxt hiện tại, kiểm thử HMR/SSR/client-only.
- Chốt storage: local-only MVP vs backend drafts; không thay DB production không được duyệt.
- Chốt nguồn/calculation engine có sẵn và cách kết nối khi được owner bàn giao; chưa giả định là nó đã có API public.
- Chốt benchmark devices và QA thresholds với người duyệt; kiểm thử máy thực.

## 17. Brief giao agent — copy-paste khi triển khai

> Dự án: `PTS Solar Roof Studio`. Source: `/home/admin01/src/Web_PTS_PHP/frontend/` hiện tại, không dùng repo Nuxt cũ. Đọc `PTS_Solar_Roof_Studio_Blueprint.md` và các contract hiện hữu trước khi viết code. Không đổi production/staging, header/footer/home, không ghi `PTS_Technical_Realism_V05_Final.blend`. Phân công theo task ID, branch/worktree rõ ràng, một owner cho mỗi file .blend. Yêu cầu mỗi task: implementation phù hợp `RoofProjectV1` + tests + screenshots + hiệu năng + blockers. Không tự bịa SKU, thông số điện, khả năng backup, giá; demo phải được gắn nhãn. Ưu tiên 2D/geometry chính xác và mobile trước 3D embellishment. Mỗi checkpoint cần audit và xác nhận trước merge.

## 18. PTS 3D Brand Signature — chỉ chuẩn bị tiêu chuẩn tiếp nối

- Đầu vào bắt buộc: **SVG vector chính thức** logo PTS, không AI vẽ lại/đổi chữ; brand tokens hiện có.
- Một canonical Blender source của lane Brand; deliverables: `brand-signature.glb` rất nhẹ cho section đặc biệt, video/WebM/MP4 theo chất lượng thực tế, poster WebP và SVG fallback.
- Motion: `metallic extrusion` / `energy activation` trên vật liệu navy/gold, không lặp logo loading toàn trang, không bắt người dùng chờ animation để vào website.
- Chỉ bắt đầu khi Roof Studio có G6 PASS và user phê duyệt; không cần trộn scene Brand vào Roof Studio.

## 19. Tài liệu tham khảo đã kiểm tra

- PVGIS 5.3 API docs, 30req/s, CORS và PVcalc parameters: https://joint-research-centre.ec.europa.eu/photovoltaic-geographical-information-system-pvgis/using-pvgis-5/api-non-interactive-service_en
- PVGIS 6 beta status: https://joint-research-centre.ec.europa.eu/photovoltaic-geographical-information-system-pvgis/using-pvgis-6_en
- TresJS Nuxt installation: https://docs.tresjs.org/getting-started/installation
- Three.js InstancedMesh: https://threejs.org/docs/pages/InstancedMesh.html
- Three.js GLTFLoader / supported extensions: https://threejs.org/docs/pages/GLTFLoader.html
- polygon-clipping: https://github.com/mfogel/polygon-clipping
- MCP for Blender repository and safe mode: https://github.com/ahujasid/mcp-for-blender
- PVWatts v8 documentation: https://developer.nlr.gov/docs/solar/pvwatts/v8/
- Google Solar API overview and coverage: https://developers.google.com/maps/documentation/solar/overview
- Energy V05 local: `C:\Users\Work\Documents\PTS-Solar-3D\energy-digital-twin-v05\README_V05.md`, `QA_V05_Report.json`, `docs\3D_PRODUCTION_PLAYBOOK.md`; these are **locally inspected as of 05/11/2026**, not a blanket approval to reuse/edit.

---

**Definition of READY TO IMPLEMENT:** completed blueprint; first actual sprint must check T00 repo/asset snapshot and freeze actual source file paths, contracts and dependency versions. **Definition of DONE:** G6 QA PASS with visual, mobile, numerical, source and regression evidence, not merely successful export/model demo.
