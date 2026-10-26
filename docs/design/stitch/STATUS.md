# Google Stitch — Trạng thái V1 Clone (2026-10-26)

## Project
- Stitch project: https://stitch.google.com/projects/5456517467698612459
- Trạng thái PRIVATE. KHÔNG deploy, không merge vào main / Nuxt migration.

## Screens tham chiếu nhập thành công
- HOME full screenshot: https://stitch.google.com/projects/5456517467698612459?node-id=2335665147476433365
- HOME HTML raw: https://stitch.google.com/projects/5456517467698612459?node-id=7250835665008869492 (cảnh báo 70 asset tương đối không được upload; chỉ để đối chiếu DOM)
- CATEGORY solar lights screenshot: https://stitch.google.com/projects/5456517467698612459?node-id=1320180079159096565
- PRODUCT DETAIL JD-18500 screenshot: https://stitch.google.com/projects/5456517467698612459?node-id=171204630598867740
- ALL PRODUCTS screenshot: https://stitch.google.com/projects/5456517467698612459?node-id=9980949446600541153

## V1 thực tế đã dựng
- Stitch generated HOME DESIGN (có mã HTML): https://stitch.google.com/projects/5456517467698612459?node-id=093d06b202e545f6a979aff2c5833547
- Stitch imported HOME corrected with **logo + hero THẬT**: https://stitch.google.com/projects/5456517467698612459?node-id=7527730607863376036
- HTML patched saved locally `.stitch/generated/v1-home-clean-source-assets.html`; script tái tạo từ Stitch export: `.stitch/patch_v1.py`. Captures and patch scripts intentionally gitignored.
- Đã QA render bằng browser local: screenshot ban đầu bị ảnh web chồng đôi header/heading/CTA trong hero; bản HTML patched đã sửa bằng đúng ảnh `hero-bientan-20260924.webp` và wordmark 2026 từ website thật, khôi phục chiều cao header/hero/heading gần hơn với source, hạn chế overlap CTA và floating highlight. Screenshot `.stitch/generated/v1-clean-final.png` là ảnh QA trước chỉnh kích thước floating widget cuối.
- Thao tác `stitch edit screen` trả về DOM operations sửa hero của generated screen, NHƯNG lần tải lại HTML từ API vẫn giữ bản cũ (SHA256 giống nhau): không coi màn DESIGN gốc đã được sửa. Dùng màn corrected HTML imported để xem bố cục đã sửa.

## Open QA/blockers — chưa khóa V1
- Stitch CLI browser capture của site bị auto-translate sang tiếng Anh, trong khi HTML production là tiếng Việt. Đã khôi phục text chính bằng thiết kế HTML tiếng Việt; các reference PNG vẫn còn chữ Anh, không chuẩn nguồn VN.
- HERO cleaned vẫn chưa 1:1 pixel-perfect (cần so cùng viewport, typography, CTA, crop ảnh, card position, margin).
- Imported corrected HTML có warning 1 local path `/`; các nút/href trong HTML vẫn prototype, chưa QA điều hướng chức năng.
- CATEGORY/PDP generated đang xử lý riêng; chưa có kết quả nghiệm thu.
- MOBILE 390/375, desktop 1440/full-page, search/menu/form interactions, footer/details chưa audit.
- V2 cải tiến TUYỆT ĐỐI chưa chạy, đợi V1 clone tất cả màn chính có QA đạt.

## Nguồn assets chính xác
Xem `ASSET-MAP.md` — lấy từ chính HTML live phucthinhsolar.com. Không dùng AI-generated photos thay thế assets V1.
