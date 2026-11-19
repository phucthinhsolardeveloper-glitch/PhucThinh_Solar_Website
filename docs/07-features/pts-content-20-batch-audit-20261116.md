# QA sơ bộ — 20 bài Kiến thức đang công khai
Thời điểm kiểm tra: 2026-11-16T04:01:37.960426+00:00. Chỉ static QA, **không coi là fact-check/duyệt xuất bản**.

- Bản nháp đã có: **19/20**, HOLD: **1**, còn thiếu: **0**.
- Liên kết được thăm dò: **42**; lỗi 403/429/timeout có thể do chống bot, cần kiểm tra bằng trình duyệt.
- Baseline là bản API public đã sanitize, không phải raw DB. Bất kỳ cập nhật SQL nào cũng phải xuất baseline raw mới từ DB production rồi so SHA.

## Bảng kết quả
| ID | Tình trạng | Từ (tách dấu cách) | Links nội bộ | Nguồn | Vấn đề cần xem |
|---:|---|---:|---:|---:|---|
| 100 | DRAFT | 433 | 2 | 1 | có claim tuyệt đối/độc quyền: phải đọc ngữ cảnh |
| 101 | DRAFT | 360 | 2 | 1 | có claim tuyệt đối/độc quyền: phải đọc ngữ cảnh |
| 102 | DRAFT | 713 | 1 | 2 | link cần xem lại ERR: https://betterbuildingssolutioncenter.energy.gov/resources/solar-powered-street-and-area-l |
| 103 | DRAFT | 585 | 1 | 1 | link cần xem lại ERR: https://www2.en.baochinhphu.vn/khan-truong-hoan-thien-co-che-khuyen-khich-lap-dat-dien-mat |
| 104 | DRAFT | 732 | 1 | 2 | link cần xem lại ERR: https://betterbuildingssolutioncenter.energy.gov/resources/solar-powered-street-and-area-l |
| 105 | DRAFT | 734 | 1 | 1 | link cần xem lại ERR: https://betterbuildingssolutioncenter.energy.gov/resources/solar-powered-street-and-area-l |
| 106 | DRAFT | 629 | 2 | 2 | link cần xem lại ERR: https://betterbuildingssolutioncenter.energy.gov/resources/solar-powered-street-and-area-l |
| 107 | HOLD | 0 | 0 | 0 | chưa có bản thảo |
| 108 | DRAFT | 465 | 2 | 1 | Chưa phát hiện lỗi hình thức |
| 109 | DRAFT | 440 | 3 | 1 | có claim tuyệt đối/độc quyền: phải đọc ngữ cảnh |
| 110 | DRAFT | 773 | 1 | 3 | Chưa phát hiện lỗi hình thức |
| 111 | DRAFT | 809 | 1 | 2 | link cần xem lại ERR: https://betterbuildingssolutioncenter.energy.gov/resources/solar-powered-street-and-area-l |
| 136 | DRAFT | 1162 | 4 | 2 | Chưa phát hiện lỗi hình thức |
| 137 | DRAFT | 1685 | 2 | 3 | Chưa phát hiện lỗi hình thức |
| 138 | DRAFT | 1222 | 2 | 2 | Chưa phát hiện lỗi hình thức |
| 139 | DRAFT | 887 | 3 | 3 | Chưa phát hiện lỗi hình thức |
| 140 | DRAFT | 756 | 2 | 2 | Chưa phát hiện lỗi hình thức |
| 141 | DRAFT | 693 | 2 | 3 | Chưa phát hiện lỗi hình thức |
| 142 | DRAFT | 859 | 3 | 2 | Chưa phát hiện lỗi hình thức |
| 143 | DRAFT | 881 | 2 | 2 | Chưa phát hiện lỗi hình thức |

## Kiểm tra liên kết (mức HTTP, không tự xác nhận claim)
| HTTP | URL | Địa chỉ sau chuyển hướng |
|---|---|---|
| 200 | /4-luu-y-quan-trong-khi-lap-dat-va-su-dung-den-nang-luong-mat-troi | https://phucthinhsolar.com/4-luu-y-quan-trong-khi-lap-dat-va-su-dung-den-nang-luong-mat-troi |
| 200 | /4-luu-y-quan-trong-khi-lap-dat-va-su-dung-den-nang-luong-mat-troi/ | https://phucthinhsolar.com/4-luu-y-quan-trong-khi-lap-dat-va-su-dung-den-nang-luong-mat-troi/ |
| 200 | /blue-carbon-den-nang-luong-mat-troi-cho-du-an/ | https://phucthinhsolar.com/blue-carbon-den-nang-luong-mat-troi-cho-du-an/ |
| 200 | /cach-doc-datasheet-tam-pin-nang-luong-mat-troi | https://phucthinhsolar.com/cach-doc-datasheet-tam-pin-nang-luong-mat-troi |
| 200 | /cach-doc-datasheet-tam-pin-nang-luong-mat-troi/ | https://phucthinhsolar.com/cach-doc-datasheet-tam-pin-nang-luong-mat-troi/ |
| 200 | /co-nen-lap-dien-mat-troi-khi-nha-chi-dung-dien-nhieu-vao-buoi-toi | https://phucthinhsolar.com/co-nen-lap-dien-mat-troi-khi-nha-chi-dung-dien-nhieu-vao-buoi-toi |
| 200 | /danh-muc-san-pham/den-nlmt/blue-carbon/ | https://phucthinhsolar.com/thuong-hieu/blue-carbon/ |
| 200 | /dien-mat-troi-hoat-dong-nhu-the-nao | https://phucthinhsolar.com/dien-mat-troi-hoat-dong-nhu-the-nao |
| 200 | /he-hoa-luoi-hybrid-doc-lap-nen-chon-loai-nao | https://phucthinhsolar.com/he-hoa-luoi-hybrid-doc-lap-nen-chon-loai-nao |
| 200 | /he-hoa-luoi-hybrid-doc-lap-nen-chon-loai-nao/ | https://phucthinhsolar.com/he-hoa-luoi-hybrid-doc-lap-nen-chon-loai-nao/ |
| 200 | /inverter-dien-mat-troi-la-gi-no-thuc-hien-nhung-chuc-nang-nao | https://phucthinhsolar.com/inverter-dien-mat-troi-la-gi-no-thuc-hien-nhung-chuc-nang-nao |
| 200 | /kw-kwp-va-kwh-khac-nhau-the-nao-7-vi-du-tranh-nham | https://phucthinhsolar.com/kw-kwp-va-kwh-khac-nhau-the-nao-7-vi-du-tranh-nham |
| 200 | /mua-den-nang-luong-mat-troi-dung-chi-nhin-con-so-w-quang-cao/ | https://phucthinhsolar.com/mua-den-nang-luong-mat-troi-dung-chi-nhin-con-so-w-quang-cao/ |
| 200 | /phuc-thinh-solar-chinh-thuc-la-doi-tac-phan-phoi-doc-quyen-den-nang-luong-mat-troi-blue-carbon-tai-viet-nam/ | https://phucthinhsolar.com/phuc-thinh-solar-chinh-thuc-la-doi-tac-phan-phoi-doc-quyen-den-nang-luong-mat-troi-blue-carbon-tai-viet-nam/ |
| 200 | /san-pham/ | https://phucthinhsolar.com/san-pham/ |
| 200 | /top-4-loi-ich-khi-su-dung-den-nang-luong-mat-troi/ | https://phucthinhsolar.com/top-4-loi-ich-khi-su-dung-den-nang-luong-mat-troi/ |
| 200 | /upload/filemanager/file_01696659927685.jpg | https://phucthinhsolar.com/upload/filemanager/file_01696659927685.jpg |
| 200 | /upload/filemanager/sharp-khung-1095x1536.jpg | https://phucthinhsolar.com/upload/filemanager/sharp-khung-1095x1536.jpg |
| 200 | /upload/filemanager/wp-legacy/2022/09/chung-nhan-scaled.jpg | https://phucthinhsolar.com/upload/filemanager/wp-legacy/2022/09/chung-nhan-scaled.jpg |
| ERR | https://betterbuildingssolutioncenter.energy.gov/resources/solar-powered-street-and-area-lighting-considerations-fact-sheet | HTTPSConnectionPool(host='betterbuildingssolutioncenter.energy.gov', port=443): Max retries exceeded with url: |
| 200 | https://blog.fronius.com/solar-energy/en/ | https://blog.fronius.com/solar-energy/en/ |
| 200 | https://bluecarbon.solar/faq/ | https://bluecarbon.solar/faq/ |
| 200 | https://iea-pvps.org/snapshot-reports/snapshot-2026/ | https://iea-pvps.org/snapshot-reports/snapshot-2026/ |
| 200 | https://iea-pvps.org/trends_reports/trends-2026/ | https://iea-pvps.org/trends_reports/trends-2026/ |
| 200 | https://static.longi.com/LR_7_72_HVH_630_650_M_30_30_and_15_Explorer_BGV_02_20241118_EN_Draft_426c93d9d1.pdf | https://static.longi.com/LR_7_72_HVH_630_650_M_30_30_and_15_Explorer_BGV_02_20241118_EN_Draft_426c93d9d1.pdf |
| 200 | https://vn.sharp/ | https://vn.sharp/ |
| 200 | https://www.cree-led.com/products/leds/xlamp/ | https://www.cree-led.com/products/leds/xlamp/ |
| 200 | https://www.cree-led.com/products/leds/xlamp/xm/ | https://www.cree-led.com/products/leds/xlamp/xm/ |
| 200 | https://www.energy.gov/cmei/femp/purchasing-energy-efficient-exterior-lighting | https://www.energy.gov/cmei/femp/purchasing-energy-efficient-exterior-lighting |
| 200 | https://www.energy.gov/cmei/systems/articles/pv-cells-101-primer-solar-photovoltaic-cell | https://www.energy.gov/cmei/systems/articles/pv-cells-101-primer-solar-photovoltaic-cell |
| 200 | https://www.energy.gov/cmei/systems/how-does-solar-energy-work | https://www.energy.gov/cmei/systems/how-does-solar-energy-work |
| 200 | https://www.energy.gov/cmei/systems/solar-and-resilience-basics | https://www.energy.gov/cmei/systems/solar-and-resilience-basics |
| 200 | https://www.energy.gov/cmei/systems/solar-integration-solar-energy-and-storage-basics | https://www.energy.gov/cmei/systems/solar-integration-solar-energy-and-storage-basics |
| 200 | https://www.energy.gov/cmei/systems/solar-photovoltaic-performance-and-efficiency-basics | https://www.energy.gov/cmei/systems/solar-photovoltaic-performance-and-efficiency-basics |
| 200 | https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics | https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics |
| 200 | https://www.energy.gov/cmei/systems/solar-photovoltaic-technology-basics | https://www.energy.gov/cmei/systems/solar-photovoltaic-technology-basics |
| 200 | https://www.lightingglobal.org/how-we-work/quality-assurance/ | https://www.lightingglobal.org/how-we-work/quality-assurance/ |
| 200 | https://www.lightingglobal.org/resource/lighting-global-quality-standards/ | https://www.lightingglobal.org/resource/lighting-global-quality-standards/ |
| 200 | https://www.longi.com/en/products/modules/hi-mo-x10-guardian-anti-glare/ | https://www.longi.com/en/products/modules/hi-mo-x10-guardian-anti-glare/ |
| 200 | https://www.longi.com/vn/download/ | https://www.longi.com/vn/download/ |
| 200 | https://www.signify.com/global/prof/outdoor-luminaires/solar/SMC_SOLAR_CA/category | https://www.signify.com/global/prof/outdoor-luminaires/solar/SMC_SOLAR_CA/category |
| ERR | https://www2.en.baochinhphu.vn/khan-truong-hoan-thien-co-che-khuyen-khich-lap-dat-dien-mat-troi-mai-nha-102230610205935993.htm | HTTPSConnectionPool(host='www2.en.baochinhphu.vn', port=443): Max retries exceeded with url: /khan-truong-hoan |
