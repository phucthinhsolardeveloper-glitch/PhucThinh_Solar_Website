# Sổ tay storefront Nuxt

Cập nhật: 2026-11-07. Website public đã chạy bằng Nuxt từ 2026-11-05; PHP vẫn giữ
Admin, dữ liệu và API. Tài liệu này là điểm vào cho mọi việc giao diện public. Quy
trình làm việc với chủ website nằm ở
[owner-feedback-loop.md](../11-workflows/owner-feedback-loop.md).

## 1. Hệ thống trong một hình

```text
Trình duyệt → Caddy (VPS) → Nuxt (frontend/, SSR)
                               └─ Nitro /_migration/* → PHP API api/pts_v1_*.php → MariaDB
Admin, upload, đơn hàng, form → PHP (domains/phucthinhsolar.com/public_html)
```

- Nitro chỉ là cầu GET gọi DTO PHP, không đọc database.
- Nội dung marketing lấy từ Admin qua API. Ngoại lệ có chủ đích: nội dung phần
  Lịch sử hình thành viết trong `components/about/AboutHistory.vue`. API giới thiệu
  trả danh sách mốc rỗng, vì dòng CMS cũ ghi năm 2019 không còn đúng.

## 2. Code nằm ở đâu

| Cần sửa | Chỗ |
|---|---|
| Trang và route | `frontend/app/pages/`, component trang trong `frontend/app/components/<khu-vực>/` |
| Header, menu danh mục, menu điện thoại | `components/SiteHeader.vue` |
| Token, màu, kính, lớp dùng chung | `frontend/app/assets/css/main.css` |
| Hợp đồng dữ liệu với PHP | `frontend/shared/contracts/*.ts` và `api/pts_v1_*.php` |
| Thanh chip có viên trượt | `composables/useSlidingPill.ts` |
| Thanh mục theo vị trí cuộn | `composables/useSectionSpy.ts` |
| Ngắt dòng tiếng Việt, căn đều | `plugins/vi-line-breaks.client.ts`, `shared/vi-word-pairs.ts` |
| Gấp nội dung dài trên điện thoại | `plugins/mobile-fold.client.ts` (`data-fold`) |
| Nút mở trường phụ của form | `components/FormMoreToggle.vue` |
| Mô hình 3D trang chủ | `components/experience-v3/EnergyScene3DExperience.vue`, `frontend/public/pts-living-energy/` |
| Form tư vấn và đại lý dạng cửa sổ | `components/modal/StorefrontConsultationModal.vue`, `StorefrontDealerModal.vue` |

Khi đổi `frontend/public/pts-living-energy/`, tăng `v=` trong `index.html` và trong
`EnergyScene3DExperience.vue`.

## 3. Chạy và kiểm tra trên máy

Preview cổng 3100 là bản build, không tự cập nhật. Sau mỗi lần sửa, chạy từ gốc repo:

```bash
set -a; . ./.env; set +a
(cd frontend && npm run build)
P=$(ss -ltnp | grep ':3100' | sed -n 's/.*pid=\([0-9]*\).*/\1/p'); kill $P
python3 scripts/nuxt-preview-launch.py
```

Trong lúc build, người đang xem có thể gặp lỗi 500 chốc lát. Lỗi đó chỉ có ở local.

| Kiểm tra | Lệnh | Mốc gần nhất |
|---|---|---|
| Kiểu dữ liệu | `cd frontend && npx nuxt typecheck` | 0 lỗi |
| Test hợp đồng giao diện | `cd frontend && npm test` | 509 đạt |
| Test trình duyệt trên Nuxt | `cd tools/visual-tests && npm run test:nuxt` | 45 đạt, 25 bỏ qua |
| Quét cả site | `node audit/site-audit.cjs` | 0 lỗi trên 19 trang |
| Script đo theo vấn đề | xem [audit/README.md](../../tools/visual-tests/audit/README.md) | |

Test hợp đồng đọc mã nguồn bằng regex. Khi đổi class có chủ đích, sửa test cùng
lúc và ghi lý do trong báo cáo.

Trước mỗi lệnh `node` của Playwright, đặt `PLAYWRIGHT_BROWSERS_PATH=$PWD/.browsers`.

## 4. Quy tắc giao diện đã chốt với chủ website

Có hiệu lực từ 2026-11-07 và áp dụng cho toàn site. Có ngoại lệ thì phải có chỉ
đạo mới.

**Màu và trạng thái**

- Mọi thứ đang được chọn (chip, ô chọn trong form, tab, điểm giao dịch, trang
  phân trang) dùng nền `--pts-gold-gradient` và chữ navy. Dùng lớp `.pts-gold-fill`
  hoặc viên trượt của `useSlidingPill`. Ô chưa chọn trong thanh chip chỉ là chữ.
- Chữ vàng là gradient: `.pts-gold-text` trên nền tối, `.pts-gold-text-light` trên
  nền sáng. Không dùng một màu vàng nhạt phẳng.
- Nút hành động chính nền vàng gradient, chữ navy. Không còn nút nền navy chữ trắng
  cho hành động chính.

**Hiệu ứng**

- Không nhấc nút khi rê chuột (`translateY`) và không dùng `filter: brightness`.
  Cả hai làm nút giật khi chuột lướt qua. Muốn làm sáng thì dùng
  `box-shadow: inset 0 0 0 999px rgb(255 255 255 / 9%)`.
- Nút vàng trên máy tính có vệt sáng lướt qua, viền phát sáng và mũi tên nhích, định
  nghĩa chung cuối `main.css`. Trên điện thoại không có hiệu ứng này. Nút mới chỉ cần
  dùng lớp vàng sẵn có là có hiệu ứng.
- Trên màn cảm ứng, nhấn nút vàng thì nút tối nhẹ, lún xuống và mũi tên nhích.
  `plugins/touch-press.client.ts` giúp Safari iPhone hiện được trạng thái nhấn.
- Menu mở bằng rê chuột không làm mờ cả trang (`backdrop-filter`), chỉ phủ màu nhạt.

**Form**

- Mở ra chỉ có trường bắt buộc, đánh dấu sao đỏ. Trường phụ gấp sau
  `FormMoreToggle` ("+ Thêm chi tiết"). Không ghi chữ "không bắt buộc".
- Ô nhập chữ có chữ mẫu (Nguyễn Văn A, 090xxxxxxx, email@example.com…).
- Form phải vừa một màn hình từ 360×640, đo bằng `audit/form-fit.cjs`.
- Ô "Nội dung đang quan tâm" chỉ hiện khi chủ đề là sản phẩm hoặc nhu cầu thật.
  Khi khách đến từ bài viết, trang Giới thiệu hay trang chính sách, trang nguồn chỉ
  được gửi kèm cho nhân viên.

**Nội dung**

- Nội dung đầy đủ và minh bạch, không rút gọn chỉ để đẹp.
- Số liệu chủ website đưa vào là đúng, không hỏi lại.
- Không viết câu vô nghĩa hoặc câu tự phủ nhận ("prototype", "không phải công trình
  thực tế"). Giữ câu nêu phạm vi thật, ví dụ khi mất điện chỉ tải ưu tiên còn điện.
- Dữ kiện công ty: thành lập 30/5/2020, nhà sáng lập Mai Anh Tuấn (tên được làm nổi
  bật), chỉ kinh doanh sản phẩm năng lượng mặt trời.
- Không dùng nhãn eyebrow hay kicker để trang trí.

**Bố cục**

- Đoạn văn dài được căn đều, cặp từ không bị tách dòng, không để một chữ lẻ cuối
  đoạn. Plugin ngắt dòng tự xử lý; tiêu đề và thiết kế có chủ ý thì không căn đều.
- Thanh mục dùng `useSectionSpy`: bấm mục nào thì mục đó sáng ngay và giữ sáng.
  Kiểm bằng bấm chuột thật qua `audit/section-rail-clicks.cjs`.
- Trên điện thoại, thanh Bộ lọc và Sắp xếp dính trên đầu khi lướt (`.m-sticky-tools`).
- Nút "Xem thêm" là viên nhỏ có mũi tên, cả dải phía dưới vẫn bấm được.

## 5. Cạm bẫy đã tốn thời gian thật

| Triệu chứng | Nguyên nhân | Cách đúng |
|---|---|---|
| Script chụp ra trang tiếng Anh, chọn phần tử theo chữ bị lỗi | Widget dịch tự đổi ngôn ngữ trong trình duyệt tự động | Chặn `https://cdn.gtranslate.net/**` trong mọi script |
| Bấm mục tiếp theo, trang cuộn tới nhưng ô sáng vẫn ở mục cũ | Mục dừng ở `scroll-margin`, thấp hơn vạch tính của spy | Spy tính cả scroll-margin cộng 40px; mục vừa bấm giữ sáng ở cuối trang |
| Menu danh mục có thanh cuộn và khoảng trắng dưới đáy | Menu con ẩn nằm tuyệt đối trong hộp `overflow: auto` vẫn cộng vào chiều cao cuộn | Màn đủ cao thì `overflow: visible` (`.catalog-menu`) |
| Ô Sắp xếp bị bóp ở đúng 769px | `max-[769px]` và `min-[770px]` để hở 1px | Dùng cặp `max-[769px]` / `min-[769px]` |
| Banner hiện "Ánh sáng cho" bị cụt | Dòng hai của tiêu đề nằm trong JSON `options.titleLine2` | API trang chủ ghép đủ hai dòng |
| Nút vàng giật khi rê chuột | Hover có `translate` hoặc `filter` | Xem mục 4 |
| Chip trên điện thoại cao bất thường | Hai quy tắc chiều cao chồng nhau, quy tắc dùng chung đặc hiệu hơn | Đo bằng `audit/phone-rail-overlap.cjs` |
| Ảnh chụp 3D quá thời gian chờ | SwiftShader rất chậm | `page.setDefaultTimeout(120000)`; số đo 3D chỉ để tham khảo |
| Docker Desktop sập | Máy 7.7GB RAM, nhiều lane cùng build | Chỉ build một lane, gom lại build cuối |
| Test kiểm tra viết sai kết luận | Hẹn giờ quay lại chạy trong lúc chụp ảnh chậm | Đếm trạng thái trước khi chụp |

## 6. Trước khi deploy

Chỉ deploy khi chủ website nói rõ. Lệnh và chế độ ở
[deploy-production-owner.md](../06-operations/deploy-production-owner.md).

1. Gom nhánh tính năng vào `master`. Cây làm việc phải sạch: tạm chuyển file không
   thuộc release, ví dụ `docs/15-design-delivery/PTS_Solar_Roof_Studio_Blueprint.md`,
   ra ngoài repo rồi trả lại sau.
2. Bật container Nuxt diễn tập trước, vì bước predeploy
   (`scripts/test-admin-crud-lifecycle.php`) gọi `http://pts-nuxt-service-rehearsal:3000`.
   Lệnh: `docker compose -f docker-compose.nuxt-service.yml up -d --build`. Lần deploy
   2026-11-07 dừng ở bước này vì container chưa chạy.
3. Có thay đổi file PHP API, ví dụ `pts_v1_home.php` hay `pts_v1_about.php`, thì release
   phải mang theo cả PHP.
4. Sau deploy, ghi vào [deployment-log.md](../06-operations/deployment-log.md) và kiểm
   trên site thật bằng `AUDIT_BASE=https://phucthinhsolar.com node audit/site-audit.cjs`.
