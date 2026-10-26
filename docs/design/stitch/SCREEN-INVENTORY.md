# Stitch screen inventory — reference VI (2026-10-26)

Canvas PRIVATE: https://stitch.google.com/projects/5456517467698612459

Các màn hình dưới đây là SOURCE REFERENCE (ảnh thật production, 1280px desktop, 390px mobile) chứ không phải bản thiết kế AI clone đã được QA.

| Trang | URL nguồn | Desktop VI | Mobile VI | QA clone |
| --- | --- | --- | --- | --- |
| Trang chủ | [Nguồn](https://phucthinhsolar.com/) | [Desktop](https://stitch.google.com/projects/5456517467698612459?node-id=10991503787350636135) | [Mobile](https://stitch.google.com/projects/5456517467698612459?node-id=16400129143998217167) | NOT_APPROVED |
| Danh mục đèn NLMT | [Nguồn](https://phucthinhsolar.com/danh-muc-san-pham/den-nlmt/) | [Desktop](https://stitch.google.com/projects/5456517467698612459?node-id=6103249587103898348) | [Mobile](https://stitch.google.com/projects/5456517467698612459?node-id=17321385128929690923) | NOT_APPROVED |
| Chi tiết JINDIAN JD-18500 | [Nguồn](https://phucthinhsolar.com/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-18500-500w) | [Desktop](https://stitch.google.com/projects/5456517467698612459?node-id=5488667192265479706) | [Mobile](https://stitch.google.com/projects/5456517467698612459?node-id=17321385128929691624) | NOT_APPROVED |
| Tất cả sản phẩm | [Nguồn](https://phucthinhsolar.com/san-pham/) | [Desktop](https://stitch.google.com/projects/5456517467698612459?node-id=7000644630425985653) | [Mobile](https://stitch.google.com/projects/5456517467698612459?node-id=14442063905554352350) | NOT_APPROVED |
| Giải pháp | [Nguồn](https://phucthinhsolar.com/giai-phap/) | [Desktop](https://stitch.google.com/projects/5456517467698612459?node-id=3059614753716814452) | [Mobile](https://stitch.google.com/projects/5456517467698612459?node-id=5350496067620323256) | NOT_APPROVED |
| Dự án | [Nguồn](https://phucthinhsolar.com/du-an-da-thuc-hien/) | [Desktop](https://stitch.google.com/projects/5456517467698612459?node-id=16491054792925897608) | [Mobile](https://stitch.google.com/projects/5456517467698612459?node-id=4968094538031965227) | NOT_APPROVED |
| Kiến thức | [Nguồn](https://phucthinhsolar.com/tin-tuc/) | [Desktop](https://stitch.google.com/projects/5456517467698612459?node-id=4877824881276821872) | [Mobile](https://stitch.google.com/projects/5456517467698612459?node-id=7474924343924118992) | NOT_APPROVED |
| Giới thiệu | [Nguồn](https://phucthinhsolar.com/ve-chung-toi/) | [Desktop](https://stitch.google.com/projects/5456517467698612459?node-id=1498775233302951091) | [Mobile](https://stitch.google.com/projects/5456517467698612459?node-id=5555216815970586192) | NOT_APPROVED |

## Các phiên bản V1 (bản dựng, chưa đạt QA)
- [V1 clone homepage desktop từ ảnh tiếng Việt](https://stitch.google.com/projects/5456517467698612459?node-id=6dc157899d334aceab96848e4fd2848b)
- [V1 clone homepage mobile từ ảnh tiếng Việt](https://stitch.google.com/projects/5456517467698612459?node-id=b7c6eb40668d413484cca11f6ced1f27)
- [V1 homepage HTML từ server nguồn, dùng base URL](https://stitch.google.com/projects/5456517467698612459?node-id=2807968691354805921)
- Những màn hình clone còn lại chưa sinh/duyệt; không nhập nhằng source screenshot với thiết kế đã clone.

## QA capture source
- 16/16 bản nguồn: HTTP 200; giao diện lang=vi; screenshot full-page có trigger lazy loading.
- Viewport: desktop 1280x900, mobile 390x844; bản full-page có chiều cao động.
- .stitch/qa/manifest.json chứa URL, H1, kích thước, đường dẫn file và trạng thái ảnh; dữ liệu local được ignore trong Git.
- img có src rỗng trong HTML ở các module lazy/hidden; chưa có đủ bằng chứng coi là lỗi 404.
- Clone chưa QA pixel matching, menu/lọc/CTA/form và fonts/asset warning vẫn là open issues.
