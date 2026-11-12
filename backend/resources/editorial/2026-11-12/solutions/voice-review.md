# Rà soát giọng điệu và hiển thị — 12/11/2026

## Phạm vi và trạng thái

Năm draft độc lập nằm cạnh dữ liệu biên tập, không ghi vào `posts.body`, không đổi `is_visible`, không tạo bài blog và chưa được xuất bản. Các URL vẫn giữ nguyên: `/giai-phap/dien-mat-troi-doanh-nghiep/`, `/giai-phap/dien-mat-troi-ho-gia-dinh/`, `/giai-phap/he-thong-luu-tru-bess/`, `/giai-phap/chieu-sang-nang-luong-mat-troi/`, `/giai-phap/bom-nuoc-nang-luong-mat-troi/`.

Snapshot baseline `PTS-CONTENT-REWORK-BASELINE-20261112.json` ghi nhận media: id 61 giữ `layer92-2680.png` và cover ảnh mái nhà xưởng; id 134 giữ `layer91-2078.png` và `anhthumbtylq-5086.png`; id 132, 133, 135 hiện không có image/cover/product liên kết. Không thay media hoặc product links.

## Before / after theo trang

### 61 — doanh nghiệp và nhà xưởng

Before nhấn vào lợi ích chung và các cụm như “giảm chi phí”, dễ khiến người đọc hiểu kết quả là mặc định. After bắt đầu bằng hồ sơ phụ tải, phân biệt kW/kWh, rồi đi qua mái, đấu nối, ba hướng cấu hình và hồ sơ quyết định; các kết quả tài chính chỉ còn là vấn đề cần mô hình hóa theo dữ liệu.

### 132 — hộ gia đình

Before trộn sản lượng, lưu trữ và mất điện trong một lời giới thiệu rộng. After dẫn từ thói quen dùng điện sang mái và bóng che, nói rõ nối lưới không phải backup, sau đó đưa checklist đọc báo giá. Đây là hành trình hộ gia đình, không dùng ngôn ngữ dự án nhà xưởng.

### 133 — lưu trữ và BESS

Before dùng “hệ thống lưu trữ” khá rộng. After giải thích pin là một thành phần còn BESS là hệ thống, dùng kWh/kW làm quyết định cốt lõi và đặt tương thích, tải ưu tiên, an toàn lên trước. Không gọi mọi cấu hình là backup và không đổi title/slug đầy đủ.

### 134 — chiếu sáng

Before nói nhiều về ứng dụng nhưng thiếu tiêu chí đo. After chuyển sang mặt bằng, độ rọi, độ đồng đều, chói, IP, bóng che, mùa mưa và bảo trì; tách đường, khu dân cư, sân vườn và công nghiệp để người mua biết dữ liệu cần cung cấp.

### 135 — bơm nước

Before mô tả nhu cầu tưới theo ban ngày nhưng chưa hướng dẫn sizing. After lấy lưu lượng và cột áp làm trục, thêm đường ống, mùa vụ, bể đệm, nguồn nước và bảo vệ chạy khô. Không hứa lượng nước hay thời gian chạy nếu chưa có số liệu khảo sát.

## Layout và display checks

Nên render mỗi draft trong template solution hiện hữu, không biến thành article. Cấu trúc đề xuất là H1 + summary/hero do field chuyên trách, nội dung CKEditor với H2/H3, checklist quyết định và CTA của template; không chép cứng CTA hay media vào body. Kiểm tra desktop/mobile: H1 và heading không bị cắt; đoạn dài có nhịp đọc; danh sách không mất marker; ảnh giữ đúng tỷ lệ baseline và `object-fit: contain` khi là sản phẩm; URL canonical và breadcrumb trỏ đúng slug.

Kiểm tra hiển thị sau khi owner nhập draft: không xuất hiện “published” chỉ vì file tồn tại; các liên kết media cũ vẫn tải được; solution detail không rơi về layout blog; CTA không che nội dung; reduced-motion không ảnh hưởng đọc; contrast và focus giữ theo template. Snapshot/visual audit nên chạy sau khi draft được nhập vào Admin, không sửa script audit trong task này.

## Rủi ro cần xử lý trước publish

- Cần owner duyệt copy và SEO description; file này chỉ là đề xuất.
- Cần xác nhận dữ liệu Admin thực tế cho media/product links trước khi nhập.
- Cần kiểm tra markup solution detail hiện tại bằng render thực tế. Snapshot/baseline cho thấy nội dung có thể chứa markup cũ; không tự sửa markup trong task này nếu chưa xác minh template và sanitizer.
- Menu 134/135 vẫn giữ nhãn hiện tại. Đề xuất tham khảo “Chiếu sáng solar” và “Bơm nước solar” chưa được áp dụng vì yêu cầu chỉ bắt buộc đổi id 133.

## Bổ sung sau QA trước merge

Mỗi draft nay có một nhánh quyết định riêng và một ví dụ giả định được ghi rõ là minh họa: phụ tải/đấu nối cho doanh nghiệp; thói quen dùng điện và backup cho hộ; hai tầng pin gia đình–BESS; quang học theo loại mặt bằng cho chiếu sáng; lưu lượng–cột áp–mùa vụ cho bơm. Các bảng trong draft là công cụ đặt câu hỏi, không phải thông số chào bán hay số liệu của công trình thật.

Các ảnh thân bài chỉ tham chiếu đường dẫn media/fallback đã có trong hệ thống (`/upload/news/...` hoặc `/assets/images/site-visuals/...`); không thêm ảnh stock/AI mới. Các nguồn DOE/NREL/FAO/World Bank/IES được đặt gần phần kỹ thuật tương ứng để biên tập viên kiểm tra khi nhập vào CMS; nguồn không biến thành bằng chứng cho một công trình cụ thể.
