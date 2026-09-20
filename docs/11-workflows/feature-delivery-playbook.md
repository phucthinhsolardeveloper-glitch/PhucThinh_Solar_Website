# Playbook làm tính năng — từ yêu cầu tới production

Cập nhật: 2026-09-17. Bổ sung chi tiết cho
[development-workflow.md](development-workflow.md).

---

## 1. Trước khi viết dòng code đầu tiên

### Khảo sát bằng bằng chứng, không bằng phỏng đoán

1. Tính năng này **đã tồn tại** chưa? Repo có code chết — ví dụ
   `templates/news/project_list_tpl.php` không route nào gọi tới. Kiểm bằng
   `grep` toàn repo, và kiểm trên **runtime thật** xem trang nào đang chạy.
2. **Dữ liệu thật** hiện có gì? Query DB, đừng đoán từ mockup. Ví dụ thật: một
   mockup vẽ 3 filter "Hộ gia đình / Doanh nghiệp / Chiếu sáng", nhưng DB chỉ có
   2 danh mục ("Cá nhân" id 6 có 4 dự án, "Doanh nghiệp" id 5 có **0** dự án) và
   **không có** "Chiếu sáng". Làm theo mockup nghĩa là tạo danh mục giả.
3. Đã có **ADR** nào chốt hướng chưa? Đọc [09-decisions/](../09-decisions/).

### Chốt phạm vi và ghi ra cái KHÔNG làm

Danh sách "không làm" quan trọng bằng danh sách "làm". Ví dụ từ một kế hoạch
thật:

> Không tự đổi tên danh mục "Cá nhân" → "Hộ gia đình", không tự thêm danh mục
> "Chiếu sáng", không tự sửa dữ liệu `ngayhopdong`/`tenkhachhang` trùng lặp —
> báo lại chủ project để chủ project quyết qua Admin.

### Ba điều tuyệt đối không tự làm

1. **Không tạo dữ liệu giả theo mockup** (danh mục, sản phẩm, dự án không tồn tại).
2. **Không viết số liệu chưa xác minh** vào code — "giảm đến 70%", "bền bỉ 25
   năm", "10 năm kinh nghiệm". Đây là nội dung khách hàng bắt bẻ được và làm
   công ty mất uy tín. Nếu cần một dòng lợi ích, dùng câu chung chung an toàn.
3. **Không đổi credential** mà chưa hỏi, kể cả admin local-only.

---

## 2. Triển khai

### Thứ tự đúng

```text
1. Migration SQL (nếu đổi schema)     → migrations/, khai báo vào 000_preflight
2. Khai báo Admin                     → libraries/type/config-type-*.php + admin_note
3. Logic đọc dữ liệu                  → sources/ hoặc libraries/
4. View                               → templates/
5. CSS                                → assets/css/, thêm vào templates/layout/css.php
6. Test                               → tools/visual-tests/ (+ scripts/test-*.php nếu có logic)
7. Cập nhật docs
```

Làm Admin **trước** view, không phải sau. Nếu để sau, gần như luôn thành
hardcode và phải làm lại — đúng cái audit 2026-09-15 và 2026-09-16 đã tìm ra.

### Nội dung phải sửa được ở Admin

Đây là yêu cầu vận hành, không phải tuỳ chọn. Đủ 5 bước trong
[admin-guide § 3](../07-features/admin-guide.md): khai báo → query → fallback →
render → **vào Admin bấm thử**.

Mọi khối mới phải có **fallback là nội dung đã duyệt** khi DB rỗng. Không
placeholder, không "Lorem", không trang trắng.

### CSS

Thay đổi toàn site → `design-tokens.css` (nạp cuối). Riêng một trang → file CSS
riêng của trang. Xem [frontend-guide](../02-development/frontend-guide.md).

### Nội dung tiếng Việt

- Không ngắt dòng giữa cụm từ có nghĩa hay mã model — dùng `&nbsp;` hoặc dấu
  nối không ngắt.
- Không để câu trả lời chung chung trong FAQ. Nếu không trả lời được cụ thể thì
  **xoá**, đừng để cho có. Nội dung mơ hồ làm công ty trông thiếu chuyên nghiệp.

---

## 3. Hai cổng kiểm tra

Cả hai phải sạch, không ngoại lệ.

```bash
# Cổng 1 — regression, smoke, lint, log, cleanup (~10 phút, build lại image)
LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh
# Dòng cuối phải là: PASS: Pre-deploy local hoàn tất

# Cổng 2 — browser regression desktop + mobile (~10 phút)
cd tools/visual-tests
PLAYWRIGHT_BROWSERS_PATH=.browsers npx playwright test --workers=1
# Điều kiện: 0 failed; số pass/skip thay đổi khi bộ test được mở rộng.
```

Chạy `LOCAL_ENFORCE_RBAC=1` để kiểm phân quyền giống production.

Khi test đỏ: **đọc nguyên nhân, đừng nới assertion.** Xem
[bug-fixing-playbook § 5](bug-fixing-playbook.md) cho cách xử lý flaky tất định.

---

## 4. Commit

```bash
# Tiếng Việt trong -m sẽ bị shell ăn dấu: "pathspec 'hẳn' did not match"
git commit -F - <<'MSG'
feat(scope): mô tả ngắn

Vì sao cần: ...
Nguyên nhân đo được: ...
Đã xác minh: ...

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>
MSG
```

Nội dung commit nên ghi **nguyên nhân đo được** và **cách đã xác minh**, không
chỉ liệt kê file đã sửa. Trước khi commit, đọc lại diff tìm secret/PII và thay
đổi ngoài phạm vi.

---

## 5. Deploy

### Production VPS — Docker

```bash
# PHP/API/commerce/Admin/migration hoặc khi không chắc
./scripts/deploy-production.sh --safe --confirm-production

# Frontend phạm vi hẹp
./scripts/deploy-production.sh --fast --confirm-production
```

Wrapper tách source web và deployment tools, backup, checksum, healthcheck,
clear cache, schema preflight, production smoke và log scan. Nó không tự chạy
migration. Chi tiết [deploy-production-owner.md](../06-operations/deploy-production-owner.md)
và [owner-system-handbook.md](../06-operations/owner-system-handbook.md).

---

## 6. Xác minh sau deploy

Không tin "upload thành công". Phải:

- `curl` URL thật, kiểm HTTP code và kích thước phản hồi.
- So nội dung file **trên server** với file local (`diff`).
- Grep HTML trả về tìm `Warning:`, `Notice:`, `Fatal error`, `Undefined`.
- Đọc `getComputedStyle` trên **production** cho thay đổi CSS.
- Kiểm ảnh bằng `naturalWidth > 0`.
- Đọc log server.

---

## 7. Báo cáo

Tiếng Việt. Nêu **nguyên nhân đo được** kèm số, không chỉ "đã sửa xong". Nói rõ
phần nào chưa làm và vì sao. Nếu có việc thuộc phía chủ project (nhập ảnh, nhập
nội dung, chạy SQL) thì liệt kê rõ ràng, tách khỏi phần mình đã làm.

Khi đưa ra lựa chọn, kèm **đề xuất**, đừng chỉ liệt kê phương án.
