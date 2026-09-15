# Audit tích hợp Admin sau đợt redesign Hero/Trang chủ/Dự án/Tin tức/Về chúng tôi — 2026-09-14

> Cập nhật 2026-09-15: quyết định product-first trong [ADR 0001](../09-decisions/0001-product-first-homepage-and-content-density.md) thay thế phần kiến nghị dành cho trang chủ. About teaser, Giải pháp, Quy trình và Dự án không còn render ở trang chủ; type `home-about`/`home-about-point` được giữ tạm để không xóa cấu hình kế thừa khi chưa có kế hoạch migration.

> Cập nhật tiếp 2026-09-15: ba banner chiến dịch product-first dùng type ảnh
> `home-campaign`. Quyền được cấp bằng migration 041, chỉ cho nhóm đã có đủ
> bốn quyền `adv`; migration mặc định preview, `--apply` mới ghi và có thể chạy
> lặp. Predeploy kiểm tra preview các migration quyền 037–041 để tránh type Admin
> mới lại làm danh mục quyền lệch sau này.

> Cập nhật 2026-09-15 (hệ thống ảnh): đã bổ sung ba type ảnh
> `catalog-campaign`, `contact-visual`, `dealer-hero`, quy tắc bật/tắt không làm
> fallback tự xuất hiện lại và migration quyền 042. Kích thước/cách fit cùng
> danh sách ảnh thật cần thay được chốt tại
> [audit hệ thống ảnh](2026-09-15-public-image-system-and-slot-inventory.md) và
> [ADR 0003](../09-decisions/0003-public-image-system-and-admin-visual-slots.md).

## Mục đích và phạm vi

Rà soát toàn bộ giao diện đã nâng cấp trong phiên làm việc này (Claude +
Codex, nhánh `NewLayoutWeb` → `refactor/about-page-v2`) để trả lời 3 câu hỏi:

1. Phần nào **đã** chỉnh được ở Admin.
2. Phần nào **đang hardcode**, lẽ ra nên chỉnh được ở Admin nhưng chưa.
3. Phần nào trong Admin **đã lạc hậu/dư thừa** vì giao diện mới không còn
   dùng tới.

Chỉ đọc code/DB thật, không suy đoán (đúng CLAUDE.md). Chưa sửa gì trong đợt
audit này — đây là báo cáo + danh sách xử lý, chờ chủ project chọn thứ tự ưu
tiên.

## 1. Hero trang chủ (3 layout, `templates/layout/slide.php`)

### Đã có Admin

- Chọn chế độ Hero (Cài đặt chung ▸ "Hero trang chủ"): Banner slide ảnh/video
  (module Slideshow) hoặc Hero chữ.
- Ảnh nền cho "Hero chữ" (mục "Ảnh nền Hero trang chủ").
- Chứng nhận ủy quyền hiện ở layout 1 (`chungnhan`, dùng chung với trang Về
  chúng tôi).

### Đang hardcode — cần bổ sung Admin

- [ ] **Layout 1 & 2**: toàn bộ eyebrow/H1/mô tả/nhãn CTA/số liệu tin cậy
      (`10.000+`, `50+`/`500+`, `5+`, "giảm hàng nghìn tấn CO₂"...) đang viết
      cứng trong `slide.php`. Đây là hero **cũ nhất**, trước cả đợt redesign
      này — không phải lỗi mới, nhưng vẫn là gap thật.
- [ ] **Layout 3 (carousel mới, 2026-09-12)**: 100% hardcode ở
      `libraries/home_hero_carousel.php` — cả 4 slide (eyebrow/tiêu đề/mô
      tả/2 CTA/tag/ảnh) đều không có Admin. Đã ghi rõ trong code
      (comment đầu file) hướng mở rộng bằng `options` JSON trên record
      `slide`/`home-hero-bg`, theo đúng yêu cầu "trước tiên triển khai dần"
      của chủ project — **cố ý chưa làm**, không phải bỏ sót.
- [ ] Không có nơi nào ở Admin chọn **layout mặc định hiển thị** trong 3 bản
      (đang hardcode "2" trong `assets/js/apps.js`).

### Admin lạc hậu

- [ ] `administrator/templates/setting/man/man_add_tpl.php` dòng 93: label
      "Hero chữ (mặc định cũ)" mô tả **chỉ** tiêu đề/số liệu/chứng nhận —
      không hề nhắc tới layout 3 (carousel) hay nút chuyển 3 bản mới thêm
      2026-09-12. Admin đọc label này sẽ không biết carousel tồn tại. Sửa
      nhỏ, không đổi hành vi — nên làm sớm để tránh Admin thấy trang thật rồi
      hoang mang không tìm ra chỗ chỉnh.

## 2. Trang chủ — các khối mới (`templates/index/index_tpl.php`)

### Đã có Admin

- Danh mục "Hệ sinh thái thiết bị" (4 "HOT" + 6 thường): dữ liệu +
  cờ **major** (mới) + cờ **noibat** (Trang chủ) đều chỉnh được ở Sản phẩm ▸
  Sản phẩm cấp 1.
- Quy trình (4 bước): dùng nguyên type `quy-trinh` đã có Admin từ trước.
- Dự án tiêu biểu trên trang chủ: dùng nguyên `$dalist`/nhóm dự án đã có
  Admin.
- Thương hiệu hợp tác: dùng nguyên `chungnhan` đã có Admin (dùng chung Hero
  layout 1 + trang Về chúng tôi).

### Đang hardcode — cần bổ sung Admin

- [ ] **Khối "Giới thiệu ngắn" (About teaser, MỚI)**: ảnh (`1-27050.png`),
      eyebrow, tiêu đề, đoạn mô tả, 4 gạch đầu dòng, nhãn nút — **toàn bộ
      hardcode** trong `index_tpl.php`, không có Admin nào cả. Đây là khối
      hoàn toàn mới, gap rõ nhất trên trang chủ.
- [ ] Nhãn tiêu đề/eyebrow của khối "Hệ sinh thái thiết bị" ("SẢN PHẨM" /
      "HỆ SINH THÁI THIẾT BỊ") và "Thương hiệu hợp tác" ("ĐỒNG HÀNH CÙNG
      THƯƠNG HIỆU UY TÍN" / "THƯƠNG HIỆU HỢP TÁC") — hardcode, mức độ ưu
      tiên thấp (ít khi cần đổi).

### Redundant / cần chốt (không phải gap Admin, mà là trùng nội dung)

- [ ] Sơ đồ "Lợi ích" hình tròn (`$loiich`, có Admin riêng nhóm "Lợi ích")
      **vẫn hiển thị** trên trang chủ, trùng ý với khối About teaser/Giải
      pháp — đã bị các báo cáo trước đề nghị ẩn/gộp (2026-09-08, tài liệu
      Codex Sep-11) nhưng **vẫn chưa xử lý**.
- [ ] Khối "Con số ấn tượng" (`$solieu`) đã **ẩn code** (2026-09-12, số liệu
      không xác minh) nhưng Admin (Cài đặt hoặc mục dữ liệu `so-lieu-thong-ke`)
      **vẫn cho sửa bình thường** — sửa ở đó hiện không có tác dụng gì trên
      giao diện. Nên có `admin_note` ghi rõ "đang ẩn tạm" để tránh Admin viết
      nội dung mà tưởng đã lên web.

## 3. Trang Dự án (`sources/project_hub.php`, `templates/news/project_*`)

### Đã có Admin (đầy đủ nhất trong đợt này)

- `project-intro`, `du-an` (bài + nhóm), `project-cta` — nhóm "Trang Dự án".
- Mới 2026-09-14: cờ **"Nổi bật trang Dự án"** (`projecthub`) chọn dự án lên
  khối lớn đầu trang; ô **Mô tả ngắn** (`desc`) cho card/khối nổi bật.

### Đang hardcode — mức ưu tiên thấp

- [ ] 3 gạch đầu dòng lợi ích ("Khảo sát và tư vấn thiết kế miễn phí"...) và
      3 icon tin cậy CTA ("Tư vấn tận tâm"...) trong khối nổi bật — hardcode
      trong `$projectUi` (`sources/project_hub.php`), cố ý dùng chung cho mọi
      dự án (không phải số liệu riêng từng dự án). Có thể để Admin sửa nếu
      muốn đổi câu chữ mà không cần deploy code, nhưng không cấp thiết.

### Admin lạc hậu / code chết

- [ ] `templates/news/project_list_tpl.php` — **xác nhận chết hoàn toàn**
      (grep toàn repo: không route/include nào gọi tới). Còn dùng `$dalist`
      kiểu cũ và markup `CRlist_term_blog`. Không xoá trong đợt audit này —
      đề xuất xoá ở đợt dọn dẹp riêng sau khi chủ project xác nhận.
- [ ] 4 quyền `news_add_he-thong-dai-ly` / `_delete_` / `_edit_` / `_man_`
      trong bảng phân quyền — lỗi thời (module đã gộp/bỏ), đã ghi nhận từ
      audit 2026-09-11, **vẫn còn** trong danh mục quyền.

## 4. Trang Tin tức (`sources/news_hub.php`, `templates/news/news_*`)

### Đã có Admin — đầy đủ

- `news-intro` (+ cờ "Hiện bài nổi bật"), `tin-tuc` (+ cờ `newshub` chọn tối
  đa 3 bài nổi bật riêng cho trang này, độc lập cờ `noibat` trang chủ),
  `news-cta`. Không phát hiện gap.

## 5. Trang Giải pháp (`giai-phap`)

Theo đúng pattern `solution-intro` / `giai-phap` (+ cờ `solutionhub`) /
`solution-cta` — giống hệt cấu trúc Tin tức/Dự án đã kiểm chứng. Chưa đọc lại
toàn bộ template trong đợt audit này (ngoài phạm vi các trang vừa
redesign trực tiếp) — nếu chủ project muốn audit sâu trang này, cần một đợt
riêng.

## 6. Trang Về chúng tôi (`sources/about.php`, `templates/about/about_tpl.php`)

### Đã có Admin — đầy đủ nhất, có nhóm riêng "Trang Về chúng tôi"

`lich-su` (câu chuyện + dòng thời gian), `about-team` (section "Nhân sự"),
`about-principle` (3 điểm nhấn), `ly-do-chon-chung-toi` + `su-menh` (dùng
chéo cho khối Tin cậy/Kim chỉ nam), `chungnhan` (chứng nhận), `about-cta`.

### Đáng chú ý — không phải gap, nhưng dễ gây nhầm lẫn khi vận hành

- [ ] Khối "Tin cậy" (Vì sao khách hàng chọn chúng tôi) và "Kim chỉ nam"
      (Tầm nhìn – Sứ mệnh) khi **chưa có** record admin chuyên biệt sẽ tự
      **đoán** dữ liệu cũ (`ly-do-chon-chung-toi`/`su-menh`) bằng cách so
      khớp từ khoá trong TÊN record (vd tên chứa "bảo hành" → gán mô tả bảo
      hành mặc định). Cơ chế này thông minh nhưng **không hiển thị trong
      Admin** — nhân viên đổi tên 1 record theo hướng khác có thể vô tình
      đổi luôn nội dung hiển thị mà không rõ vì sao. Nên cân nhắc thêm dòng
      `admin_note` giải thích, hoặc về lâu dài tách hẳn thành record admin
      riêng như đã làm với `about-principle`.

## Danh sách xử lý đề xuất (ưu tiên cao → thấp)

**Cập nhật 2026-09-14 (cùng ngày, sau khi chủ project duyệt "triển khai hết"):**
mục 1/2/3/6/7/8/9 đã triển khai xong trên nhánh `refactor/about-page-v2`
(commit `72cc40f`, `4c12f32`, `8b68120`), verify đầy đủ (PHP lint,
`predeploy-local.sh`, Playwright, thao tác thật qua Admin UI). Mục 4 và 5
khi bắt tay vào làm thì phát hiện **đã được xử lý từ trước** (rất có thể do
Codex ở 1 commit sau audit ngày 11/09) — kiểm tra lại thấy khối "Lợi ích"
không còn trên trang chủ, và `sync-permission-groups.php` báo cả 3 nhóm
quyền đều khớp danh mục — không cần làm gì thêm.

| # | Việc | Loại | Trạng thái |
|---|---|---|---|
| 1 | Sửa label "Hero chữ (mặc định cũ)" trong Cài đặt chung cho đúng thực tế 3 layout | Sửa nhanh | ✅ Xong |
| 2 | Thêm Admin cho khối "Giới thiệu ngắn" trang chủ (ảnh/eyebrow/tiêu đề/mô tả/4 bullet) | Bổ sung Admin | ✅ Xong — type `home-about`/`home-about-point` |
| 3 | Ghi `admin_note` cho `so-lieu-thong-ke` là "đang ẩn tạm trên trang chủ" | Sửa nhanh | ✅ Xong |
| 4 | Chốt xử lý sơ đồ "Lợi ích" trùng ý trên trang chủ (ẩn/gộp) | Quyết định nội dung | ✅ Đã được xử lý từ trước (không phải bởi audit/đợt sửa này) |
| 5 | Dọn 4 quyền `*_he-thong-dai-ly` lỗi thời | Dọn dẹp Admin | ✅ Đã được xử lý từ trước |
| 6 | Xoá `templates/news/project_list_tpl.php` (code chết) | Dọn dẹp code | ✅ Xong, kèm dọn 1 dòng so sánh chết trong `templates/index.php` |
| 7 | Thêm Admin cho copy Hero layout 3 (carousel) | Bổ sung Admin | ✅ Xong — type ảnh mới `hero-carousel` (man_photo, 4 slide, field nâng cao lưu qua `options` JSON); sửa thêm 1 gap thật trong lúc làm: `savePhoto()` chưa có logic merge JSON mảng như `savePhotoStatic()` đã có |
| 8 | Thêm `admin_note` giải thích cơ chế đoán từ-khoá ở khối Tin cậy trang Về chúng tôi | Sửa nhanh | ✅ Xong |
| 9 | Đưa 3 bullet lợi ích + 3 icon CTA trang Dự án vào Admin | Bổ sung Admin | ✅ Xong — type `project-benefit`/`project-trust` |

Quyền cho các type Admin mới (2, 7, 9) đã cấp qua migration 039/040 (neo vào
quyền `tin-tuc`/`du-an`/`home-hero-bg` sẵn có, cùng khuôn migration 037),
áp dụng và xác nhận sạch trên local (`predeploy-local.sh` không còn cảnh
báo thiếu quyền). Toàn bộ vẫn chỉ ở local, chưa đụng demo/production.
phụ thuộc.
