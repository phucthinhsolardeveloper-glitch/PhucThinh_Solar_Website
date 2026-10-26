# Phúc Thịnh Solar — Stitch design workspace

Ngày khởi tạo: 2026-10-26. Phạm vi: THIẾT KẾ, không sửa giao diện production.

## Nguồn sự thật
- Website đang chạy: https://phucthinhsolar.com/
- Source PHP tại `/home/admin01/src/Web_PTS_PHP` (master; không tự sửa/gộp).
- Nhánh chuyển Nuxt: `feature/pts-nuxt4-migration` tại `/home/admin01/src/pts-nuxt4-migration`.
- Worktree thiết kế độc lập: `/home/admin01/src/pts-stitch-design`, branch `feature/pts-stitch-design-baseline-20261026`, tách từ Nuxt commit `a44b91a`.

## Quy trình bắt buộc
1. **V1 / BASELINE / CLONE**: chụp trang LIVE (desktop và mobile), import vào Stitch; clone chính xác hình thức lẫn cấu trúc, giữ nguyên chữ tiếng Việt, logo, ảnh, nội dung, navigation và các tính năng nhìn thấy. Không đề xuất redesign trong V1.
2. QA so sánh source và thiết kế theo từng viewport, từng trang; đánh dấu `PASS`, `DEVIATION`, `MISSING`; chỉ khóa V1 sau khi khắc phục sai khác quan trọng.
3. **V2 / EXPLORE**: duplicate V1, áp dụng cải thiện UX/UI có kiểm soát, không đổi bộ nhận diện, không xóa chức năng. Giữ phiên bản V1 bất biến.
4. **V3 / SELECTED**: chỉ sau khi đánh giá/chọn phương án, xuất `DESIGN.md`, token, kích thước, asset mapping, màn hình/component specs và prototype; dùng làm đầu vào triển khai Nuxt trên nhánh khác.
5. QA runtime local, không dùng website production để thử code; không deploy/merge từ worktree thiết kế.

## Sơ đồ canvas Stitch mong muốn
- Project: `Phuc-Thinh-Solar-UI-Master` — https://stitch.google.com/projects/5456517467698612459 (PRIVATE; created successfully)
- Section `00 References`: screenshot gốc từng viewport, URL, ngày capture.
- Section `01 V1 Faithful Clone`: từng màn hình bản clone (lock).
- Section `02 V2 Concepts`: phương án cải tiến theo các nguyên tắc nhất quán.
- Section `03 V3 Approved`: phương án được duyệt, trạng thái và handoff.

## Files
- `PROMPT-01-FAITHFUL-CLONE.md`: prompt nhập Stitch khi tạo V1.
- `PROMPT-02-IMPROVE.md`: prompt **chỉ chạy sau khi V1 đạt yêu cầu**.
- `PAGES.md`: danh sách route cần chụp, độ ưu tiên và checklist.
- `.stitch/captures/`: bản HTML/screenshot local (chỉ là dữ liệu chụp, không cần đưa vào commit).

## Chạy Stitch CLI (WSL)
```bash
cd /home/admin01/src/pts-stitch-design
npx --yes @google/stitch status
npx --yes @google/stitch capture https://phucthinhsolar.com/ -o .stitch/captures/home.html
# Sau khi xác thực Stitch và có Canvas API key:
# stitch create project ... ; stitch upload screen ... ; stitch generate variants ...
```

Lưu ý: tài khoản Stitch chưa được xác thực ở thời điểm khởi tạo; HTML chụp local KHÔNG đồng nghĩa màn hình đã được upload lên Stitch.
