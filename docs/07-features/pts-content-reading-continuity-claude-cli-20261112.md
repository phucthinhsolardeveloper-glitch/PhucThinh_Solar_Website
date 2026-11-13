# Báo cáo rà soát độc lập: mạch đọc và liên kết nội bộ của PTS-KT-001 đến 021

Lần này mình chỉ đọc, không sửa file, không chạy lệnh, không truy vấn database. Slug bên dưới lấy từ mã nguồn (`FIRST_THREE` trong importer và cột `slug_draft` trong `calendar-180.csv`), **chưa đối chiếu với database local**. Coordinator cần tra `posts.slug` theo `editorial_plan_items.post_id` trước khi dùng. Trong `docs/07-features/` có sẵn một file `pts-content-reading-continuity-claude-cli-20261112.md.tmp` chưa được git theo dõi; file đó không phải do mình tạo và mình không đụng vào.

**File nguồn đã đọc:** 21 bài `backend/resources/editorial/2026-11-12/articles/PTS-KT-0xx.md`, `calendar-180.csv`, `written-articles.json`, `photo-cover-selections.json`, `legacy-public/voice-knowledge.md`, `legacy-public/review-knowledge.json`, tiêu đề của `solutions/SOLUTION-*.md`, `backend/app/Console/Commands/EditorialCalendarImport.php`, `backend/app/Domain/News/NewsDetail.php`, `backend/app/Domain/Content/PostVisibility.php`, `backend/app/Models/Post.php` và `frontend/nuxt.config.ts`.

---

## 1. Cơ chế hiện tại

- **Đường dẫn công khai** (`Post.php:66-72`): bài thường là `/slug/`, giải pháp là `/giai-phap/slug/`.
- **Importer** (`EditorialCalendarImport.php:159-185`):
  - Slug lấy từ `FIRST_THREE[...]` nếu có, nếu không thì từ `slug_draft`.
  - Bài tạo ra với `is_visible=false` và `published_at` theo lịch.
  - Category chỉ có hai: `tin-moi-nhat` (003, 012) và `kien-thuc-san-pham` (19 bài còn lại).
  - Dòng H1 Markdown bị cắt (`:168`), nên tiêu đề công khai là cột `title` của CSV, không phải H1 trong file .md.
- **Bài liên quan** (`NewsDetail.php:95, 197-204`): `siblings()` lấy tối đa 12 bài cùng `category_id`, sắp theo `sort_order` rồi `id desc`. Không có liên kết theo chủ đề.
- **Quy tắc hiển thị thật** (`PostVisibility::live`): `is_visible = 1` **và** (`published_at` rỗng hoặc `published_at <= now`).
- **Cache:** API tin tức không nằm trong danh sách cache 60 giây (`nuxt.config.ts:99-105`), nên bộ lọc hiển thị chạy lại ở mỗi request.

## 2. Năm cụm nội dung đề xuất

| Cụm | Bài | Vai trò |
|---|---|---|
| **A. Nền tảng PV** | 001, 004, 005, 007, 009, 010, 014 | Nguyên lý, đơn vị, inverter, loại hệ, mất điện, thời tiết |
| **B. Quy mô hộ gia đình và khảo sát** | 016, 013, 006, 018, 019, 021 | Hóa đơn, giờ dùng điện, thêm tấm, mái, nhiệt mái |
| **C. Tấm pin và công nghệ** | 002, 011, 015, 020 | Datasheet, watt so với mái, màu, TOPCon/HJT/BC |
| **D. Chiếu sáng** | 008, 017 (+ bài cũ 104, 105, 111) | Mua đèn, W/lm/lx |
| **E. Tin ngành** | 003, 012 | IEA PVPS, IEA Renewables |

**Nhận xét về category:** để 19 bài chung một mục "Kiến thức sản phẩm" làm khu kiến thức đọc như một cuốn sổ tay kỹ thuật.
- 019 (khảo sát mái), 021 (mái nóng hay mát) và 016 (đọc hóa đơn) không phải kiến thức về sản phẩm.
- Ngoài ra, `siblings()` sẽ gợi ý bài inverter cho người đang đọc bài về đèn.

Mình **không đề xuất đổi category hay URL**, vì việc đó ảnh hưởng SEO và cần chủ website duyệt. Cách xử lý là chồng một lớp "đọc tiếp" được chọn tay lên trên.

## 3. Đồ thị đọc tiếp (1–3 cạnh mỗi bài, xếp theo mức ưu tiên)

| Bài | Slug theo nguồn (cần đối chiếu DB) | Đọc tiếp | Lý do |
|---|---|---|---|
| 001 | `dien-mat-troi-hoat-dong-nhu-the-nao` | 004, 007, 009 | Mục 1 nói kWp, mục 2 nói ba loại hệ, mục 4 nói trời mây |
| 002 | `cach-doc-datasheet-tam-pin-nang-luong-mat-troi` | 011, 020, 015 | Từ đọc thông số đi sang chọn tấm theo mái, rồi công nghệ, rồi màu |
| 003 | `thi-truong-dien-mat-troi-toan-cau-2026-iea-pvps` | 012, 020 | Bản tin IEA tiếp theo; mục "Công nghệ" có nhắc TOPCon/HJT/BC |
| 004 | `kw-kwp-va-kwh-khac-nhau-the-nao-7-vi-du-tranh-nham` | 016, 013, 002 | kWh trên hóa đơn → 450 kWh → kWp từ Pmax |
| 005 | `inverter-dien-mat-troi-la-gi-no-thuc-hien-nhung-chuc-nang-nao` | 014, 010, 011 | Hybrid, mất lưới, Voc/MPPT gặp trong thực tế |
| 006 | `co-nen-lap-dien-mat-troi-khi-nha-chi-dung-dien-nhieu-vao-buoi-toi` | 013, 016, 014 | Ba hồ sơ tải → bài tập hóa đơn → chọn inverter |
| 007 | `he-hoa-luoi-hybrid-doc-lap-nen-chon-loai-nao` | 014, 010, 006 | |
| 008 | `mua-den-nang-luong-mat-troi-dung-chi-nhin-con-so-w-quang-cao` | 017, bài cũ 104, bài cũ 105 | Không gắn 107 (trùng 100% với 102), 106, 110 (đang HOLD) |
| 009 | `troi-nhieu-may-anh-sang-yeu-pin-con-tao-dien-the-nao` | 004, 005 | Đọc kWh trên app; vì sao inverter "dừng" |
| 010 | `vi-sao-co-tam-pin-nhung-mat-dien-luoi-van-co-the-bi-cup` | 014, 007, 006 | |
| 011 | `tam-pin-600w-620w-650w-va-720w-watt-cao-co-tot-hon` | 002, 019, 020 | |
| 012 | `iea-cong-bo-renewables-2026-trien-vong-dien-mat-troi-den-2031` | 003, 013, 007 | Mục "Ba điều bản tin không trả lời" dẫn về giờ dùng điện và lựa chọn loại hệ |
| 013 | `nha-dung-bao-nhieu-dien-moi-thang-thi-phu-hop-lap-nang-luong-mat-troi` | 016, 006, 018 | |
| 014 | `inverter-hoa-luoi-va-hybrid-khac-nhau-the-nao` | 010, 005, 006 | |
| 015 | `tam-pin-mau-den-co-luon-tot-hon-tam-pin-mau-xanh` | 002, 020, 021 | Mục "màu không tự quyết định nhiệt" dẫn sang 021 |
| 016 | `cach-doc-hoa-don-tien-dien-truoc-khi-thiet-ke-he-thong` | 013, 004, 019 | Mục "Những điều hóa đơn không trả lời được" dẫn sang bài mái |
| 017 | `lumen-lux-va-watt-khac-nhau-the-nao-khi-chon-den` | 008, bài cũ 105, bài cũ 111 | |
| 018 | `lap-tam-pin-cang-nhieu-co-luon-rut-ngan-hoan-von` | 013, 006, 011 | |
| 019 | `mai-ton-mai-ngoi-mai-bang-luu-y-khi-khao-sat-dien-mat-troi` | 021, 011, 016 | |
| 020 | `topcon-hjt-bc-back-contact-khac-nhau-ra-sao` | 002, 015, 011 | |
| 021 | `dien-mat-troi-co-lam-mai-nha-nong-hon-hay-mat-hon` | 019, 015 | |

- **Số liên kết trỏ đến mỗi bài:** bài nào cũng có ít nhất 1. Các bài trung tâm là 013 (5), 011 (5), 002 (4), 006 (4), 014 (4) và 016 (3).
- **Bài cũ 104, 105, 111:** slug không có trong file nguồn, phải tra DB theo `post_id`.
- **Bản nháp giải pháp** (SOLUTION-61, 132, 133, 134, 135): đang ẩn. **Không đưa vào manifest** cho tới khi được public. Nếu sau này thêm, phải lọc theo `kind` và dùng `/giai-phap/slug/`.
- **Thứ tự đăng:** phần lớn các cạnh trỏ đến bài đăng *sau* bài nguồn (ví dụ 001 ngày 13/11 trỏ đến 004, 007, 009 đăng 14–15/11). Khi 001 vừa lên, khối "đọc tiếp" phải tự rỗng rồi tự điền dần. Vì vậy bắt buộc lọc lúc chạy, không được ghi cứng link vào nội dung bài.

## 4. Chống lộ bản nháp

1. **Lọc bằng `PostVisibility::live('n.')`, không lọc bằng mỗi `is_visible`.** Một bài có `is_visible=1` nhưng `published_at` còn ở tương lai (tức đang hẹn giờ) vẫn chưa được hiện. Yêu cầu giao việc ghi "lọc theo `Post.is_visible`" là chưa đủ.
2. Ánh xạ mã kế hoạch sang bài qua `editorial_plan_items.post_id`, không qua slug, để đổi slug không làm gãy liên kết. Thêm điều kiện `kind='article'` và `slug<>''`.
3. Bỏ chính bài đang xem, bỏ trùng, giữ thứ tự trong manifest. Phần còn trống thì điền bằng `siblings()` hiện có, sau khi loại các id đã chọn.
4. Danh sách chặn bài cũ: 106, 107, 110 (theo `review-knowledge.json`).
5. Trang preview có chữ ký phải dùng cùng bộ lọc live, để preview không hiện bài nháp như một link bấm được.

## 5. Top 10 lỗi cần xử lý

1. **Hai link nội dung trỏ thẳng vào bài nháp.**
   - `PTS-KT-016.md:56` trỏ đến 013, `PTS-KT-017.md:43` trỏ đến 008.
   - `RichText::sanitize` không kiểm tra bài đích có đang hiện hay không, nên nếu 013 hoặc 008 bị hoãn thì link sẽ ra trang 404.
   - Nên chuyển hai link này vào manifest. Nếu giữ trong nội dung thì cần test chặn: ngày đăng bài đích phải ≤ ngày đăng bài nguồn, và bài nguồn chỉ được duyệt khi bài đích đã live.
2. **Khối "bài liên quan" ghép theo category nên lệch chủ đề** (`NewsDetail.php:199-203`).
   - Người đọc 008 (đèn) sẽ thấy 12 bài kiến thức mới nhất như 021, 020, 019 (mái, TOPCon...).
   - 003 và 012 có thể chỉ thấy nhau, cộng thêm các bài thông báo phân phối cũ (100, 101, 103) nếu chúng nằm chung category. Điểm này chưa xác minh được vì không truy vấn DB.
3. **Phần mất lưới, chống đảo lưới và backup được giải thích lại trong khoảng 9 bài:** 001 mục 5, 005, 006, 007, 010, 014, 018, 020, 021. Riêng 007, 010, 014 gần như ba bản của cùng một ý.
   - Nên giao mỗi bài một phần: 010 giữ phần chống đảo lưới và nhánh dự phòng, 014 giữ phần chọn inverter, 007 giữ phần chọn loại hệ.
   - Các bài khác chỉ giữ một câu và đưa link sang.
4. **Đoạn Voc, Vmp và giới hạn MPPT lặp ở 002, 005, 011, 015, 018, 020.** Lịch còn có 023 (MPPT) và 038 (Voc/Isc với inverter), nên sẽ lặp thêm hai lần.
   - Nên để 002 giữ phần nguyên lý, 038 giữ phần đi sâu.
   - Các bài còn lại chỉ giữ một câu và link.
5. **Tiêu đề trong file .md không khớp tiêu đề đăng.**
   - `PTS-KT-002.md:1` ghi "10 thông số quan trọng", nhưng thân bài là "Bắt đầu với 5 thông số" cộng 8 nhóm. Tiêu đề CSV lại là "Pmax, Voc, Isc, Vmp, Imp, NMOT".
   - H1 của 001 và 003 cũng khác CSV. Riêng ba bài đầu đã có bài sẵn trong DB, nên tiêu đề thật cần đối chiếu DB.
   - 016–021 mở bài bằng một H2 nhắc lại tiêu đề, ví dụ `016.md:1` "Cầm hóa đơn lên…", nên trang có thêm một tiêu đề thừa ngay dưới H1.
6. **018 lặp ý và chuyển ý đột ngột.**
   - `018.md:17` "Ngược lại, xưởng có bơm, quạt, máy nén…" nói lại Kịch bản B ở `:11`, dù `:15` đã tóm tắt cả hai kịch bản.
   - `018.md:39` viết "thêm pin mặt trời và thêm pin lưu trữ", dễ nhầm hai loại "pin". Nên sửa thành "thêm tấm pin".
7. **Câu tự phủ nhận vi phạm quy tắc không viết câu thừa của chủ website.**
   - `001.md:67` "…không phải công trình do Phúc Thịnh Solar thi công", `002.md:78` "…không phải nhân sự Phúc Thịnh Solar", `003.md:56` "…không phải dự án của Phúc Thịnh Solar", `016.md:26` "…không phải dữ liệu thực tế của Phúc Thịnh Solar".
   - Credit ảnh bìa đã do `EditorialImageCredits` hiển thị riêng. Nên giữ phần ghi nguồn và giấy phép ảnh, chỉ bỏ vế phủ nhận.
8. **Bài viết chưa làm đúng hướng triển khai đã ghi trong lịch.**
   - Hướng của 001 có "phân biệt cell, module, string, array", nhưng bài không nói đến string và array.
   - Hướng của 007 có "tình huống mất điện ở Gia Lai", nhưng bài không có. Trong 21 bài, chỉ 009 có bối cảnh địa phương (Gia Lai, cà phê, hồ tiêu).
9. **Lịch 022+ bị đứt mạch và trùng chủ đề:**
   - 031 "Tháng mưa còn phát điện?" gần trùng 009.
   - 096 (tin) và 170 (kiến thức) cùng là "BC có vượt TOPCon khi bị che bóng?".
   - 087 và 164 cùng nói TOPCon và độ ẩm/nhiệt ẩm (nghiên cứu UNSW).
   - 030 ("đèn quảng cáo 1000W") lặp 008 và 017.
   - 039 (tủ lạnh, Wi-Fi khi mất điện) lặp 010 và 014.
   - 028 (10 kWp không phát 10 kW) lặp 004 và mục STC của 002.
   - Thứ tự ngược:
     - PVGIS (025, ngày 21/11) đã được nhắc ở 009, 013, 018 trước đó mà chưa có bài để link.
     - N-type/P-type (029) đăng sau 020, dù `020.md:26` đã dùng khái niệm n-type.
     - Chọn dung lượng pin lưu trữ (032) đăng sau 006, 010, 013, 014, trong khi cả bốn bài đều để ngỏ ý "dung lượng khả dụng".
     - Đấu nối chuỗi (044) mới đăng sau 3 tuần.
   - Đề xuất kéo 025 và 032 lên sớm, gộp 096 với 170 và 087 với 164, và đổi 031 thành bài "đọc sản lượng theo mùa" để link về 009.
10. **Trạng thái trong CSV và ảnh bìa lệch.**
    - Cả 21 bài đã viết vẫn ghi `draft_status = Chưa biên tập` và `publish_status = Chưa đăng`.
    - Bài 004–015 ghi `image_status = Chưa chọn`, dù `photo-cover-selections.json` đã có ảnh. Importer bỏ qua các cột này (nó tự gán `stage` và `image_status`), nhưng người đọc CSV sẽ hiểu sai tiến độ.
    - Ảnh bìa 011 là cánh đồng pin lắp trên mặt đất, trong khi bài nói chuyện đặt tấm cho vừa mái nhà. Nên đổi sang ảnh mái có vật cản.
    - Alt ảnh 012 ghi "tại Việt Nam", cần xác minh lại.

## 6. Ví dụ trước và sau

**Bài 008, khối "bài liên quan":**
- *Trước:* 12 bài mới nhất của "Kiến thức sản phẩm", ví dụ 021 mái nóng, 020 TOPCon, 019 mái tôn…
- *Sau:* 017 "Lumen, lux và watt…", bài cũ 104 "Chọn đèn theo vị trí…", bài cũ 105 "Bốn lưu ý an toàn…". Phần còn trống mới điền bằng bài cùng category.

**Bài 018, dòng 15–17:**
- *Trước:* "Hai kịch bản trên cho thấy… Ngược lại, xưởng có bơm, quạt, máy nén… có thể hấp thụ phần lớn sản lượng."
- *Sau:* bỏ đoạn "Ngược lại…". Chuyển câu "biểu đồ phụ tải theo giờ quan trọng hơn tổng kWh" lên cuối dòng 15, rồi link sang 013.

**Bài 016, dòng 56:**
- *Trước:* link cứng `/nha-dung-bao-nhieu-dien-moi-thang-.../` nằm trong nội dung, có nguy cơ thành link chết.
- *Sau:* chỉ giữ câu dẫn, không gắn link, và 013 xuất hiện ở khối "đọc tiếp" khi đã live. Hoặc giữ link nhưng phải qua test thứ tự đăng.

## 7. Kế hoạch triển khai cho coordinator

1. **Manifest:** tạo `backend/resources/editorial/2026-11-12/reading-graph.json` với cấu trúc `{"PTS-KT-001": ["PTS-KT-004","PTS-KT-007","PTS-KT-009"], ..., "legacy": {"PTS-KT-008": [104,105]}}`.
2. **Bộ resolver:** tạo `backend/app/Domain/News/CuratedReading.php`. Nó đổi mã kế hoạch thành `post_id` qua `editorial_plan_items`, rồi lọc theo `kind='article'`, `PostVisibility::live('n.')`, slug không rỗng và danh sách chặn, sau đó giữ thứ tự, bỏ chính bài đang xem và bỏ trùng.
3. **`NewsDetail.php`:** chỉ sửa dòng 95, tức phần ghép kết quả chọn tay với `siblings()` sau khi loại id trùng, và dùng lại `related()` để giữ nguyên DTO. **Không đụng dòng 106–122** (sản phẩm liên kết, thuộc phần Codex A). Cũng không đụng `RelatedProductSuggestions.php`, `related-products.json`, `EditorialProductLinksCommand.php` và bảng `post_products`.
4. **Test (Feature):**
   - Bài đích có `is_visible=0` thì bị loại.
   - Bài có `is_visible=1` nhưng `published_at` ở tương lai thì bị loại.
   - Bài loại `solution` thì bị loại.
   - Liên kết trỏ về chính nó thì bị loại.
   - Thứ tự manifest được giữ nguyên và phần thiếu được điền bằng bài cùng category.
   - Preview không hiện bài nháp.
   - Test kiểm tra manifest: mọi mã có trong CSV, tối đa 3 cạnh mỗi bài, không có 106/107/110.
   - Test quét `](/` trong `articles/*.md`: slug đích phải có trong CSV và ngày đăng bài đích ≤ ngày đăng bài nguồn.
5. **Chạy test trong worktree** bằng `docker run` tạm, không chạy trong container chung (container đó đang mount bản master).
