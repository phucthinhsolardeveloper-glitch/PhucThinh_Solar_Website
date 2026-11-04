# PTS Experience Design System

Phạm vi W4-03: bộ token và composition primitive dùng cho các experience Nuxt
giai đoạn 3–4. Creative direction lấy từ
`docs/design/experience-research/PTS-CREATIVE-ATLAS-V3.md`; các giá trị nhận diện
tiếp tục dùng token đã duyệt trong `frontend/app/assets/css/main.css` và
`docs/15-design-delivery/12-PTS-DESIGN-LANGUAGE-2026-11.md`.

## Nguyên tắc

- Dùng chung navy/gold, Be Vietnam Pro, container và radius/material/motion tokens theo vai trò trong design language hiện hành.
- Mỗi composition thay đổi nhịp nội dung theo nhu cầu trang; không tạo theme hoặc
  component library tách rời.
- Dữ liệu sản phẩm, dự án, thông số, giá, media và claim lấy từ Admin/API. Thiếu
  dữ liệu thì ẩn an toàn hoặc hiển thị `Chưa có dữ liệu`.
- Dùng Nuxt UI cho control tương tác; các component dưới đây quản lý layout,
  media, nhịp nội dung và navigation.
- Anchor giữ hành vi cuộn tự nhiên. Reveal chỉ chạy khi người dùng không bật
  `prefers-reduced-motion`; nội dung vẫn hiện nếu JavaScript/IntersectionObserver
  không khả dụng.
- Không phụ thuộc Three.js. Component và CSS chỉ được tải như CSS/HTML thông
  thường trên các trang không dùng 3D.

## Cấu trúc triển khai

| Lớp | Vị trí | Trách nhiệm |
|---|---|---|
| Semantic tokens + primitive CSS | `frontend/app/assets/css/experience-design-system.css` | Màu semantic trỏ về navy/gold hiện có; type, spacing, motion, width; layout/section/media/data/journey primitives và responsive behavior. |
| Vue components | `frontend/app/components/design-system/` | `PtsLayout`, `PtsSection`, `PtsPageIntro`, `PtsChapterNav`, `PtsMotionReveal`, `PtsMediaFrame`, `PtsMetric`, `PtsJourney`. |
| Preview | `/experience-design-system` | Atlas nội bộ cho 8 composition; đặt `noindex, nofollow, noarchive` ở HTML meta và response header. |

## Baseline component audit

- `main.css` đã giữ bảng màu Tailwind/brand, font Be Vietnam Pro, container
  `.pts-container`, reset và `MotionConfig` đã bọc toàn app ở `app.vue`.
- Home, catalog, PDP, solution, project và editorial có component riêng theo
  payload/flow; nhiều bố cục vẫn viết utility trực tiếp. PDP và solution đã có
  chapter/TOC scroll-spy cục bộ.
- W4-03 bổ sung API layout/chapter/motion/media/sequence để trang mới chia sẻ
  primitive. Không migrate hàng loạt component hiện có trong task này để tránh
  làm đổi parity, payload hoặc behavior đã nghiệm thu ở các lane khác.

## Composition recipes

| Direction | Recipe | Dùng khi |
|---|---|---|
| Precision Paper | `precision-paper` + paper tone | Trang cần nền yên, phân cấp bằng typography và lưới. |
| Spatial Energy Stage | `spatial-energy-stage` + stage tone | Trạng thái hoặc giải thích mô hình cần tương phản cao; không hàm ý giám sát thời gian thực. |
| Solar Cinema | `solar-cinema` + cinema tone | Hero/chuyển cảnh có media và CTA rõ; không autoplay hoặc che nội dung. |
| Architectural Editorial | `architectural-editorial` + split layout | Bài giải pháp, dự án hoặc nội dung có ảnh và văn bản dài. |
| Technical Journal | `technical-journal` + ledger/metric | Thông số có label, value, đơn vị và nguồn dữ liệu cạnh nhau. |
| Product Atelier | `product-atelier` + `PtsMediaFrame` contain | Gallery và PDP; ảnh sản phẩm vuông 1:1, không crop. |
| Evidence Narrative | `evidence-narrative` + evidence rows | Claim/media có caption và provenance; thiếu quyền thì không xuất bản như bằng chứng. |
| Connected Journey | `connected-journey` + `PtsChapterNav`/`PtsJourney` | Luồng dài cần mục lục anchor hoặc các bước tiếp theo dễ thấy. |

Các recipe là layout/presentation pattern, không chứa nội dung marketing hay số
liệu thật. Mỗi route chọn các recipe cần thiết; không cần dùng đủ tám recipe trên
một trang.

## Component contract

- `PtsLayout`: width `site`, `wide`, `content`, `reading` hoặc `bleed`; chỉ một
  wrapper mỗi trục sở hữu gutter.
- `PtsSection`: `tone` paper/warm/quiet/stage/cinema và spacing compact/regular/
  roomy; nhận `composition` để áp dụng rhythm tương ứng.
- `PtsPageIntro`: một H1 và mô tả; không thêm eyebrow hoặc trang trí mặc định.
- `PtsChapterNav`: item là `{ id, label }`; link là anchor HTML; active state dùng
  `aria-current="location"`; sticky là tùy chọn.
- `PtsMotionReveal`: reveal một lần khi vào viewport; delay giới hạn tối đa 500ms,
  khoảng dịch tối đa 32px; reduced motion hiển thị ngay.
- `PtsMediaFrame`: ratio square/wide/project/content/campaign; fit contain/cover;
  dùng đúng tỷ lệ field và alt/caption từ dữ liệu phù hợp.
- `PtsMetric`: thiếu value thì render `Chưa có dữ liệu`; không parse hoặc suy luận
  thông số.
- `PtsJourney`: danh sách bước tuần tự; href chỉ dùng khi có route đích hợp lệ.

## Responsive và accessibility

- Layout dùng `minmax(0, 1fr)`, `min-width: 0` và một owner gutter để tránh tràn.
- Mobile gộp composition grid về một cột dưới 821px; gutter theo container hiện
  hành: 12px tới 359px, 16px tới 820px, 24px tablet ngang và token desktop.
- Chapter links có target tối thiểu 44px, cuộn ngang bằng touch/keyboard và không
  bắt wheel/touch. Heading đích có `scroll-margin-top` để tránh header che nội dung.
- Nội dung reveal không bị ẩn khi reduced motion, khi thiếu IntersectionObserver
  hoặc trước hydration. Tên heading, nav và ảnh vẫn có semantics HTML.
- Preview bắt buộc kiểm tra desktop và 320/390/768px; lỗi console, overflow và
  active chapter state phải được ghi trong handoff QA.

## Preview và kiểm chứng

Chạy dev trong worktree riêng:

```bash
cd frontend
npm run dev -- --port 3203
```

Mở `/experience-design-system`. Route chỉ là preview nội bộ, không thêm vào menu,
sitemap hay robots allowlist. Sau khi cập nhật token/component, chạy
`npm run typecheck` và kiểm tra browser theo mục responsive/accessibility ở trên.
