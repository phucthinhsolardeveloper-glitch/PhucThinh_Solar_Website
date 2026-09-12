<?php
/* Dữ liệu 4 slide Hero trang chủ - tập trung một chỗ (yêu cầu 2026-09-12: "one
   centralized frontend config/data file", chưa có màn Admin quản lý riêng nên
   tạm định nghĩa ở đây; slide.php chỉ lặp qua mảng này bằng MỘT khối markup
   dùng chung, không copy dán 4 lần). Khi có Admin, thay hàm này bằng truy vấn
   DB (gợi ý: mở rộng cột options JSON của #_photo type 'slide' - cùng cách đã
   dùng cho watermark - thay vì ALTER TABLE) và giữ nguyên shape mảng bên dưới
   để không phải sửa slide.php/CSS/JS.

   Render thành LAYOUT 3 trong .pts-hero-layouts (templates/layout/slide.php),
   song song 2 layout Hero cũ (pts-home-hero / pts-hero2) - yêu cầu 2026-09-12
   "vẫn giữ layout hero cũ, dùng song song, có nút chuyển", không thay thế.

   Ảnh nền dùng lại đúng 4 ảnh thật đã có sẵn trong Admin > Ảnh nền Hero trang
   chủ (table_photo type=home-hero-bg, id 132-135) - không dùng ảnh dựng AI.
   Slide 4 (đại lý/đối tác) tạm dùng ảnh công trình mái lớn do chưa có ảnh
   showroom/văn phòng thật trong thư viện; nên thay bằng ảnh văn phòng/showroom
   thật khi có. */
function getHeroCarouselSlides()
{
    return array(
        array(
            'id' => 'ecosystem',
            'theme' => 'dark',
            'eyebrow' => 'PHÚC THỊNH SOLAR',
            'titleLine1' => 'Thiết bị đồng bộ.',
            'titleLine2' => 'Giải pháp phù hợp.',
            'description' => 'Tấm pin, inverter, pin lưu trữ và đèn năng lượng mặt trời. Một hệ sinh thái cho những nhu cầu khác nhau.',
            'primaryCTA' => array('label' => 'Khám phá sản phẩm', 'href' => 'san-pham/'),
            'secondaryCTA' => array('label' => 'Nhận tư vấn', 'href' => '#ptsContactModal', 'modal' => 'ptsContactModal'),
            'tags' => array('GIA ĐÌNH', 'DOANH NGHIỆP', 'DỰ ÁN'),
            'image' => '1-27050.png',
            'alt' => 'Hệ thống tấm pin năng lượng mặt trời lắp trên mái nhà, sản phẩm Phúc Thịnh Solar',
        ),
        array(
            'id' => 'lighting',
            'theme' => 'light',
            'eyebrow' => 'CHIẾU SÁNG NĂNG LƯỢNG MẶT TRỜI',
            'titleLine1' => 'Ánh sáng cho',
            'titleLine2' => 'từng công trình.',
            'description' => 'Lựa chọn đèn theo mặt bằng, thời gian sử dụng và điều kiện lắp đặt của đường phố, khu dân cư và dự án.',
            'primaryCTA' => array('label' => 'Khám phá giải pháp', 'href' => 'giai-phap/'),
            'secondaryCTA' => array('label' => 'Tư vấn dự án', 'href' => '#ptsContactModal', 'modal' => 'ptsContactModal'),
            'tags' => array('ĐƯỜNG PHỐ', 'KHU DÂN CƯ', 'CÔNG TRÌNH'),
            'image' => 'hero-den-duong-nlmt-thu-nghiem-63880.png',
            'alt' => 'Hàng đèn đường năng lượng mặt trời thực tế của Phúc Thịnh Solar dọc tuyến đường',
        ),
        array(
            'id' => 'storage',
            'theme' => 'dark',
            'eyebrow' => 'ĐIỆN MẶT TRỜI & LƯU TRỮ',
            'titleLine1' => 'Chủ động năng lượng.',
            'titleLine2' => 'Vững bước dài lâu.',
            'description' => 'Tư vấn cấu hình theo phụ tải, diện tích mái và nhu cầu sử dụng điện của gia đình, doanh nghiệp và nhà xưởng.',
            'primaryCTA' => array('label' => 'Xem giải pháp', 'href' => 'giai-phap/'),
            'secondaryCTA' => array('label' => 'Yêu cầu khảo sát', 'href' => '#ptsContactModal', 'modal' => 'ptsContactModal'),
            'tags' => array('ĐIỆN MẶT TRỜI', 'LƯU TRỮ', 'VẬN HÀNH'),
            'image' => '3-11502.png',
            'alt' => 'Hệ thống điện mặt trời áp mái công trình thực tế của Phúc Thịnh Solar',
        ),
        array(
            'id' => 'partner',
            'theme' => 'dark',
            'eyebrow' => 'ĐẠI LÝ · NHÀ PHÂN PHỐI · ĐỐI TÁC DỰ ÁN',
            'titleLine1' => 'Cùng phát triển.',
            'titleLine2' => 'Cùng đi xa hơn.',
            'description' => 'Kết nối hợp tác phân phối thiết bị và triển khai giải pháp năng lượng mặt trời trên toàn quốc.',
            'primaryCTA' => array('label' => 'Đăng ký nhận chính sách', 'href' => '#ptsDealerModal', 'modal' => 'ptsDealerModal'),
            'secondaryCTA' => array('label' => 'Tìm hiểu hợp tác', 'href' => 'dai-ly'),
            'tags' => array('KẾT NỐI', 'ĐỒNG HÀNH', 'PHÁT TRIỂN'),
            'image' => '2-97031.png',
            'alt' => 'Công trình điện mặt trời quy mô lớn Phúc Thịnh Solar đã triển khai',
        ),
    );
}
