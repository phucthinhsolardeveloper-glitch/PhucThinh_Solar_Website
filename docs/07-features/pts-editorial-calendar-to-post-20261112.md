# Editorial calendar → hidden CMS post (12/11/2026)

## Trạng thái đã kiểm kê

- `calendar-180.csv` có 180 slot duy nhất từ 13/11/2026 đến 11/01/2027, ba giờ mỗi ngày: 08:00, 12:00 và 19:30 (Asia/Ho_Chi_Minh).
- Trước đợt này có 21 Post liên kết và 159 mục ý tưởng chưa có Post. PTS-KT-022, 023 và 024 nay có Markdown hoàn chỉnh, metadata SEO và vẫn là bản nháp ẩn.
- Cover của 022–024 là concept/generic hoặc chưa có quyền ảnh cuối. Không dùng ảnh đó làm bằng chứng công trình, chất lượng module hay kết quả kiểm thử; trạng thái ảnh vẫn phải được duyệt riêng.
- 159 mục còn lại (`PTS-KT-025..180` sau đợt này) vẫn cần nghiên cứu, viết, kiểm tra kỹ thuật/nguồn và kiểm tra quyền ảnh. Có URL trong CSV không có nghĩa nguồn còn sống hoặc đã xác minh.

## Audit CSV → source → draft

CSV là source của lịch và brief: `title`, `angle`, `primary_source_url`, `secondary_source_url`, `image_notes` và `slug_draft`. `written-articles.json` chỉ liệt kê bài đã có Markdown đạt ngưỡng; không dùng cách đổi `draft_status` trong CSV để giả lập nội dung.

Với mỗi bài, đối chiếu theo chuỗi sau:

1. Ý tưởng: title/angle đúng CSV, không tạo claim từ image brief.
2. Nghiên cứu: mở nguồn chính, ghi ngày truy cập và phân biệt dữ kiện của nguồn với suy luận biên tập. PTS-KT-022 dùng DOE về yếu tố hướng/bóng/vị trí và giữ PVGIS như công cụ cần chạy theo tọa độ; 023 dùng DOE về cấu trúc hệ PV/inverter; 024 dùng trực tiếp Scorecard 2026 và trang Methodology của Kiwa PVEL.
3. Draft: Markdown tiếng Việt có H2/H3, đoạn văn, checklist/ví dụ hữu ích, nguồn ở cuối và tối thiểu 1.200 ký tự hiển thị sau khi render.
4. Technical/source QA: đối chiếu model, điện áp, dòng, BOM, đơn vị và điều kiện áp dụng; không nêu sản lượng/tỷ lệ nếu chưa có mô phỏng hoặc nguồn tương ứng.
5. Image rights QA: cover/inline image có nguồn, giấy phép hoặc là hình tự thiết kế đã kiểm tra. Generic mockup chỉ là placeholder.
6. Ready: editor xác nhận nội dung, SEO, link, ảnh và ghi chú hold; sau đó mới chuyển liên kết sang hidden CMS Post.

## Cơ chế chuyển đổi an toàn

`php artisan pts:editorial-calendar` chạy preview mặc định. Chỉ `--apply` mới ghi DB/media. Import đọc `written-articles.json` và chỉ tạo Post cho ID có cả metadata hợp lệ và file `articles/PTS-KT-NNN.md`; lịch trống không tạo Post rỗng.

Khi tạo Post, command:

- chuyển Markdown sang HTML, bỏ H1 đầu để template không lặp heading;
- kiểm tra nội dung hiển thị tối thiểu 1.200 ký tự;
- dùng slug từ CSV, `published_at` theo slot và nối `EditorialPlanItem.post_id`;
- đặt `is_visible=false`, `is_featured=false`, `in_hub=false`; timestamp không tự công khai bài;
- giữ nguyên slot, title, stage, source, image, notes và thay đổi editor của bản ghi đã tồn tại;
- lần chạy lại tìm theo plan key/slug và không tạo bản ghi trùng. Chỉ bổ sung liên kết khi item vẫn ở `idea` và Post vẫn ẩn.

Vì vậy đây là pipeline tạo bản nháp ẩn, không phải bộ lập lịch tự xuất bản. Người có quyền `content_publish` vẫn phải duyệt và bật hiển thị thủ công; nếu giờ đã qua, bật hiển thị là một quyết định xuất bản riêng.

## Reconciliation và test

Preview là bước reconcile an toàn để xem số slot mới, slot đã có, draft sẽ tạo và media thiếu:

```bash
php artisan pts:editorial-calendar
```

Sau khi được phê duyệt trên local DB mới dùng:

```bash
php artisan pts:editorial-calendar --apply
```

Không dùng `--apply` trên production trong task này. Test `backend/tests/Feature/EditorialCalendarImportTest.php` kiểm tra 180 slot, 24 bài review/ẩn và 156 ý tưởng, nội dung 022–024 trên 1.200 ký tự, liên kết plan→Post, rerun idempotent, không public, và bảo toàn title/stage/notes/date do editor sửa. Test hiện dùng `Storage::fake('upload_news')`; không ghi DB thật.

Rollback ở local là khôi phục DB snapshot/transaction trước khi apply và xóa có chọn lọc các Post ẩn mới theo plan key sau khi đối chiếu; không xóa upload dùng chung. Production state: không deploy, không import production, không publish.

## Quyền ảnh và các hold của batch 022–024

- 022: cần mô phỏng PVGIS theo tọa độ công trình thật nếu muốn nêu sản lượng; cover hiện là concept/generic, chờ ảnh có quyền hoặc sơ đồ tự thiết kế.
- 023: chưa có mã module/inverter cụ thể nên không ghi giới hạn điện áp/dòng; infographic phải được kỹ thuật kiểm tra trước khi dùng.
- 024: cần ghi ngày truy cập Scorecard khi duyệt; Top Performer chỉ áp dụng đúng nhóm test/model/BOM, không được dùng như claim cho toàn thương hiệu. Chưa có ảnh phòng thử nghiệm được cấp phép.

Các hold này không ngăn tạo bản nháp ẩn để biên tập, nhưng ngăn chuyển sang `ready`/publish cho đến khi được xử lý.
