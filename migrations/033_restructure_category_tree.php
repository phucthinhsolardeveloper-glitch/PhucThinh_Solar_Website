<?php
/**
 * Tái cấu trúc cây danh mục sản phẩm (12 -> 10 danh mục cấp 1, +25 danh mục
 * cấp 2) theo mapping đã được kiểm chứng ngày 2026-09-08, đã áp dụng và kiểm chứng
 * trên local (:8080) và demo (:8081) trong cùng ngày. Migration này đưa đúng
 * thay đổi đó lên production theo chuẩn dry-run/--apply của dự án, vì production
 * chưa nhận các thay đổi local trong repository (xem runbook 2026-09-06).
 *
 * Các bước (chạy trong 1 transaction khi --apply):
 *   1. Đổi tên 6 danh mục cấp 1 (giữ nguyên id/slug để không vỡ URL/SEO)
 *   2. Tạo 25 danh mục cấp 2 mới (bỏ qua nếu slug đã tồn tại - idempotent)
 *   3. Gán lại id_list/id_cat cho từng sản phẩm theo mã sản phẩm audit đã map
 *      (chỉ áp dụng cho ID thực sự tồn tại trên production; ID không thấy sẽ
 *      liệt kê riêng, không suy đoán)
 *   4. Ẩn 2 sản phẩm băng keo đóng gói (666, 667) khỏi hienthi/banchay
 *   5. Xoá sản phẩm trùng id=365 "SUN2000-40-KTL" (bản thiếu giá/công suất,
 *      audit + đặc tả đã được xác nhận giữ id=371) - CHỈ xoá nếu code khớp
 *      đúng vân tay đã biết, không thì bỏ qua và báo cần xem tay
 *   6. Giải thể 2 danh mục cấp 1 rỗng (Hệ thống điện, Đèn AC) - CHỈ khi đếm
 *      lại trên production thấy đúng 0 sản phẩm sau bước 3
 *   7. Ẩn 2 danh mục cấp 2 cũ dưới "Đèn sân vườn" (Năng Lượng Mặt Trời/AC) -
 *      CHỈ khi 0 sản phẩm còn trỏ tới sau bước 3
 *
 * Production có thể không giống hệt local/demo (chưa nhận các migration
 * 001-032, dữ liệu có thể khác). Dry-run in ra đầy đủ trước/sau cho từng bước;
 * đọc kỹ trước khi thêm --apply. Trường hợp không suy đoán được (danh mục cũ
 * không tìm thấy đúng tên, sản phẩm không tồn tại, id=365 không khớp vân tay,
 * danh mục dissolve còn sản phẩm) sẽ được liệt kê riêng và BỎ QUA, không tự ý
 * đoán.
 *
 * BACKUP DB trước khi chạy --apply. Rollback: khôi phục từ backup; không có
 * cách hoàn tác từng phần vì bước 5 xoá hẳn bản ghi.
 *
 * Chạy (từ domains/phucthinhsolar.com/public_html):
 *   php ../../../migrations/033_restructure_category_tree.php          # xem trước
 *   php ../../../migrations/033_restructure_category_tree.php --apply  # ghi DB
 */


if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('LIBRARIES', './libraries/');
if (!isset($_SERVER['SERVER_NAME'])) $_SERVER['SERVER_NAME'] = 'localhost';
$publicHtml = __DIR__.'/../domains/phucthinhsolar.com/public_html';
if (!is_file($publicHtml.'/libraries/config.php') && is_file('/var/www/html/libraries/config.php'))
    $publicHtml = '/var/www/html';
if (!is_file($publicHtml.'/libraries/config.php'))
{
    fwrite(STDERR, "Không tìm thấy libraries/config.php.\n");
    exit(1);
}
require $publicHtml.'/libraries/config.php';

$apply = in_array('--apply', $argv, true);

$pdo = new PDO(
    'mysql:host='.$config['database']['host'].';dbname='.$config['database']['dbname'].';charset=utf8mb4',
    $config['database']['username'],
    $config['database']['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$p = $config['database']['prefix'];
$type = 'san-pham';

/* ---- dữ liệu nguồn (mapping đã kiểm chứng ngày 2026-09-08) ---- */

$l1Renames = array(
	array('Hệ thống khung nhôm', 'Hệ khung & phụ kiện tấm pin'),
	array('Biến tần / Inverter', 'Biến tần (Inverter)'),
	array('Biến tần bơm ', 'Hệ thống bơm năng lượng mặt trời'),
	array('Thiết bị điện năng lượng mặt trời', 'Thiết bị & phụ kiện điện mặt trời'),
	array('Trụ Đèn', 'Trụ đèn & phụ kiện'),
	array('Đèn Sân Vườn', 'Đèn sân vườn'),
	array('Tấm pin năng lượng măt trời', 'Tấm pin năng lượng mặt trời'),
);
$l1Dissolve = array(
	'Hệ thống điện',
	'Đèn AC'
);
$newL2 = array(
	array('Biến tần (Inverter)', 'Hòa lưới'),
	array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	array('Pin lưu trữ', 'Pin / Battery'),
	array('Pin lưu trữ', 'Hệ thống ESS'),
	array('Pin lưu trữ', 'BMS / Rack & phụ kiện'),
	array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	array('Hệ thống bơm năng lượng mặt trời', 'Máy bơm NLMT'),
	array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	array('Thiết bị & phụ kiện điện mặt trời', 'Cáp & dây điện'),
	array('Thiết bị & phụ kiện điện mặt trời', 'Giám sát / đo lường'),
	array('Thiết bị & phụ kiện điện mặt trời', 'Phụ kiện đèn NLMT'),
	array('Thiết bị & phụ kiện điện mặt trời', 'Tiếp địa & ống bảo vệ'),
	array('Thiết bị & phụ kiện điện mặt trời', 'Vật tư phụ trợ'),
	array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	array('Trụ đèn & phụ kiện', 'Trụ đèn trang trí'),
	array('Trụ đèn & phụ kiện', 'Cần đèn & giá đỡ'),
	array('Trụ đèn & phụ kiện', 'Móng / đế trụ'),
	array('Đèn sân vườn', 'Đèn trụ cổng'),
	array('Đèn sân vườn', 'Đèn treo tường'),
	array('Đèn sân vườn', 'Đèn sân vườn / đèn nấm'),
	array('Đèn sân vườn', 'Đèn cắm cỏ'),
	array('Hệ khung & phụ kiện tấm pin', 'Thanh ray'),
	array('Hệ khung & phụ kiện tấm pin', 'Kẹp tấm pin'),
	array('Hệ khung & phụ kiện tấm pin', 'Kẹp mái Kliplock / Seamlock'),
);
$oldL2Hide = array(
	array('Đèn sân vườn', 'Năng Lượng Mặt Trời'),
	array('Đèn sân vườn', 'AC'),
);
$hideProductIds = array(666, 667);
$duplicateDeleteId = 365;
$duplicateKeepId = 371;
$duplicateExpectedCode = 'SUN2000-40-KTL';

/* target(productId) => array(l1_name, l2_name_or_empty) */
$productMap = array(
	874 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	873 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	872 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	871 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	870 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	869 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	868 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	867 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	866 => array('Hệ thống bơm năng lượng mặt trời', 'Máy bơm NLMT'),
	865 => array('Hệ thống bơm năng lượng mặt trời', 'Máy bơm NLMT'),
	864 => array('Hệ thống bơm năng lượng mặt trời', 'Máy bơm NLMT'),
	863 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	862 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	861 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	860 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	859 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	858 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	857 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	856 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	855 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	854 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	853 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	852 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	851 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	850 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	849 => array('Hệ thống bơm năng lượng mặt trời', 'Biến tần bơm NLMT'),
	848 => array('Pin lưu trữ', 'Pin / Battery'),
	847 => array('Pin lưu trữ', 'Pin / Battery'),
	846 => array('Pin lưu trữ', 'Pin / Battery'),
	845 => array('Pin lưu trữ', 'Hệ thống ESS'),
	844 => array('Pin lưu trữ', 'Hệ thống ESS'),
	843 => array('Pin lưu trữ', 'Hệ thống ESS'),
	842 => array('Pin lưu trữ', 'Hệ thống ESS'),
	841 => array('Pin lưu trữ', 'Hệ thống ESS'),
	840 => array('Pin lưu trữ', 'Hệ thống ESS'),
	839 => array('Pin lưu trữ', 'Hệ thống ESS'),
	838 => array('Thiết bị & phụ kiện điện mặt trời', 'Giám sát / đo lường'),
	837 => array('Thiết bị & phụ kiện điện mặt trời', 'Giám sát / đo lường'),
	836 => array('Thiết bị & phụ kiện điện mặt trời', 'Giám sát / đo lường'),
	835 => array('Thiết bị & phụ kiện điện mặt trời', 'Giám sát / đo lường'),
	834 => array('Biến tần (Inverter)', 'Hòa lưới'),
	833 => array('Biến tần (Inverter)', 'Hòa lưới'),
	832 => array('Biến tần (Inverter)', 'Hòa lưới'),
	831 => array('Biến tần (Inverter)', 'Hòa lưới'),
	830 => array('Biến tần (Inverter)', 'Hòa lưới'),
	829 => array('Biến tần (Inverter)', 'Hòa lưới'),
	828 => array('Biến tần (Inverter)', 'Hòa lưới'),
	827 => array('Biến tần (Inverter)', 'Hòa lưới'),
	826 => array('Biến tần (Inverter)', 'Hòa lưới'),
	825 => array('Biến tần (Inverter)', 'Hòa lưới'),
	824 => array('Biến tần (Inverter)', 'Hòa lưới'),
	823 => array('Biến tần (Inverter)', 'Hòa lưới'),
	822 => array('Biến tần (Inverter)', 'Hòa lưới'),
	821 => array('Biến tần (Inverter)', 'Hòa lưới'),
	820 => array('Biến tần (Inverter)', 'Hòa lưới'),
	819 => array('Pin lưu trữ', 'BMS / Rack & phụ kiện'),
	818 => array('Pin lưu trữ', 'Pin / Battery'),
	817 => array('Pin lưu trữ', 'BMS / Rack & phụ kiện'),
	816 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	815 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	814 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	813 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	812 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	811 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	810 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	809 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	808 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	807 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	806 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	805 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	804 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	803 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	802 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	801 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	800 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	799 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	798 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	797 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	796 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	795 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	794 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	793 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	792 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	791 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	790 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	789 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	788 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc MPPT'),
	787 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	786 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	785 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	784 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	783 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	782 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	781 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	780 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	779 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	778 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	777 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	776 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	775 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	774 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	773 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	772 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	771 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	770 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	769 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	768 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	767 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	766 => array('Thiết bị & phụ kiện điện mặt trời', 'Bộ điều khiển sạc PWM'),
	765 => array('Tấm pin năng lượng mặt trời', ''),
	764 => array('Tấm pin năng lượng mặt trời', ''),
	763 => array('Tấm pin năng lượng mặt trời', ''),
	762 => array('Tấm pin năng lượng mặt trời', ''),
	761 => array('Tấm pin năng lượng mặt trời', ''),
	760 => array('Tấm pin năng lượng mặt trời', ''),
	759 => array('Tấm pin năng lượng mặt trời', ''),
	758 => array('Tấm pin năng lượng mặt trời', ''),
	757 => array('Tấm pin năng lượng mặt trời', ''),
	756 => array('Tấm pin năng lượng mặt trời', ''),
	755 => array('Tấm pin năng lượng mặt trời', ''),
	754 => array('Tấm pin năng lượng mặt trời', ''),
	753 => array('Tấm pin năng lượng mặt trời', ''),
	752 => array('Tấm pin năng lượng mặt trời', ''),
	751 => array('Tấm pin năng lượng mặt trời', ''),
	750 => array('Tấm pin năng lượng mặt trời', ''),
	749 => array('Tấm pin năng lượng mặt trời', ''),
	748 => array('Tấm pin năng lượng mặt trời', ''),
	747 => array('Tấm pin năng lượng mặt trời', ''),
	746 => array('Tấm pin năng lượng mặt trời', ''),
	745 => array('Tấm pin năng lượng mặt trời', ''),
	744 => array('Tấm pin năng lượng mặt trời', ''),
	743 => array('Tấm pin năng lượng mặt trời', ''),
	742 => array('Tấm pin năng lượng mặt trời', ''),
	741 => array('Tấm pin năng lượng mặt trời', ''),
	740 => array('Tấm pin năng lượng mặt trời', ''),
	739 => array('Trụ đèn & phụ kiện', 'Trụ đèn trang trí'),
	738 => array('Trụ đèn & phụ kiện', 'Trụ đèn trang trí'),
	737 => array('Trụ đèn & phụ kiện', 'Trụ đèn trang trí'),
	736 => array('Trụ đèn & phụ kiện', 'Trụ đèn trang trí'),
	735 => array('Trụ đèn & phụ kiện', 'Trụ đèn trang trí'),
	734 => array('Trụ đèn & phụ kiện', 'Trụ đèn trang trí'),
	733 => array('Trụ đèn & phụ kiện', 'Trụ đèn trang trí'),
	732 => array('Đèn sân vườn', 'Đèn sân vườn / đèn nấm'),
	731 => array('Đèn sân vườn', 'Đèn sân vườn / đèn nấm'),
	730 => array('Đèn sân vườn', 'Đèn sân vườn / đèn nấm'),
	729 => array('Đèn sân vườn', 'Đèn treo tường'),
	728 => array('Đèn sân vườn', 'Đèn treo tường'),
	727 => array('Đèn sân vườn', 'Đèn treo tường'),
	726 => array('Đèn sân vườn', 'Đèn treo tường'),
	725 => array('Đèn sân vườn', 'Đèn treo tường'),
	724 => array('Đèn sân vườn', 'Đèn treo tường'),
	723 => array('Đèn sân vườn', 'Đèn treo tường'),
	722 => array('Đèn sân vườn', 'Đèn treo tường'),
	721 => array('Đèn sân vườn', 'Đèn treo tường'),
	720 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	719 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	718 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	717 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	716 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	715 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	714 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	713 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	712 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	711 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	710 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	709 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	708 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	707 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	706 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	705 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	704 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	703 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	702 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	701 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	700 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	699 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	698 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	697 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	696 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	695 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	694 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	693 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	692 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	691 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	690 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	689 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	688 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	687 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	686 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	685 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	684 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	683 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	682 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	681 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	680 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	679 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	678 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	677 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	676 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	675 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	674 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	673 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	672 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	671 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	670 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	669 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	668 => array('Đèn sân vườn', 'Đèn trụ cổng'),
	665 => array('Trụ đèn & phụ kiện', 'Móng / đế trụ'),
	664 => array('Trụ đèn & phụ kiện', 'Móng / đế trụ'),
	663 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	662 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	661 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	660 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	659 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	658 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	657 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	656 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	655 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	654 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	653 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	652 => array('Trụ đèn & phụ kiện', 'Trụ đèn đường'),
	651 => array('Trụ đèn & phụ kiện', 'Cần đèn & giá đỡ'),
	650 => array('Trụ đèn & phụ kiện', 'Cần đèn & giá đỡ'),
	649 => array('Trụ đèn & phụ kiện', 'Cần đèn & giá đỡ'),
	648 => array('Trụ đèn & phụ kiện', 'Cần đèn & giá đỡ'),
	647 => array('Quạt năng lượng mặt trời', ''),
	646 => array('Quạt năng lượng mặt trời', ''),
	636 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	635 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	634 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	633 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	632 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	631 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	630 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	629 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	628 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	622 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	621 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	620 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	619 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	618 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	617 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	616 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	615 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	614 => array('Thiết bị & phụ kiện điện mặt trời', 'Phụ kiện đèn NLMT'),
	613 => array('Thiết bị & phụ kiện điện mặt trời', 'Phụ kiện đèn NLMT'),
	612 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	611 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	610 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	609 => array('Thiết bị & phụ kiện điện mặt trời', 'Phụ kiện đèn NLMT'),
	608 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	607 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	606 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	605 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	604 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	603 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	602 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	600 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	599 => array('Thiết bị & phụ kiện điện mặt trời', 'Phụ kiện đèn NLMT'),
	598 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	597 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	596 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	595 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	594 => array('Thiết bị & phụ kiện điện mặt trời', 'Phụ kiện đèn NLMT'),
	593 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	592 => array('Thiết bị & phụ kiện điện mặt trời', 'Phụ kiện đèn NLMT'),
	591 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	590 => array('Thiết bị & phụ kiện điện mặt trời', 'Phụ kiện đèn NLMT'),
	589 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	588 => array('Thiết bị & phụ kiện điện mặt trời', 'Phụ kiện đèn NLMT'),
	587 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	586 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	585 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	584 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	583 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	582 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	581 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	399 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	398 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	397 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	396 => array('Thiết bị & phụ kiện điện mặt trời', 'Tiếp địa & ống bảo vệ'),
	395 => array('Thiết bị & phụ kiện điện mặt trời', 'Tiếp địa & ống bảo vệ'),
	394 => array('Thiết bị & phụ kiện điện mặt trời', 'Tiếp địa & ống bảo vệ'),
	393 => array('Thiết bị & phụ kiện điện mặt trời', 'Tiếp địa & ống bảo vệ'),
	392 => array('Thiết bị & phụ kiện điện mặt trời', 'Vật tư phụ trợ'),
	391 => array('Thiết bị & phụ kiện điện mặt trời', 'Vật tư phụ trợ'),
	390 => array('Hệ khung & phụ kiện tấm pin', 'Kẹp mái Kliplock / Seamlock'),
	389 => array('Hệ khung & phụ kiện tấm pin', 'Kẹp mái Kliplock / Seamlock'),
	388 => array('Hệ khung & phụ kiện tấm pin', 'Kẹp mái Kliplock / Seamlock'),
	387 => array('Hệ khung & phụ kiện tấm pin', 'Kẹp mái Kliplock / Seamlock'),
	386 => array('Hệ khung & phụ kiện tấm pin', 'Kẹp mái Kliplock / Seamlock'),
	385 => array('Hệ khung & phụ kiện tấm pin', 'Kẹp mái Kliplock / Seamlock'),
	384 => array('Quạt năng lượng mặt trời', ''),
	383 => array('Quạt năng lượng mặt trời', ''),
	382 => array('Quạt năng lượng mặt trời', ''),
	381 => array('Quạt năng lượng mặt trời', ''),
	380 => array('Hệ khung & phụ kiện tấm pin', 'Thanh ray'),
	379 => array('Hệ khung & phụ kiện tấm pin', 'Thanh ray'),
	378 => array('Hệ khung & phụ kiện tấm pin', 'Thanh ray'),
	377 => array('Hệ khung & phụ kiện tấm pin', 'Kẹp tấm pin'),
	376 => array('Hệ khung & phụ kiện tấm pin', 'Kẹp tấm pin'),
	375 => array('Hệ khung & phụ kiện tấm pin', 'Kẹp tấm pin'),
	374 => array('Thiết bị & phụ kiện điện mặt trời', 'Cáp & dây điện'),
	373 => array('Thiết bị & phụ kiện điện mặt trời', 'Cáp & dây điện'),
	372 => array('Thiết bị & phụ kiện điện mặt trời', 'Cáp & dây điện'),
	371 => array('Biến tần (Inverter)', 'Hòa lưới'),
	370 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	369 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	368 => array('Biến tần (Inverter)', 'Hybrid / lưu trữ'),
	367 => array('Biến tần (Inverter)', 'Hòa lưới'),
	366 => array('Biến tần (Inverter)', 'Hòa lưới'),
	365 => array('Biến tần (Inverter)', 'Hòa lưới'),
	364 => array('Pin lưu trữ', 'Pin / Battery'),
	363 => array('Pin lưu trữ', 'Pin / Battery'),
	362 => array('Biến tần (Inverter)', 'Hòa lưới'),
	361 => array('Tấm pin năng lượng mặt trời', ''),
	360 => array('Tấm pin năng lượng mặt trời', ''),
	359 => array('Tấm pin năng lượng mặt trời', ''),
	358 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	357 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	356 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	355 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	354 => array('Đèn sân vườn', 'Đèn cắm cỏ'),
	347 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	343 => array('Đèn năng lượng mặt trời', 'Đèn Ốp Trần'),
	337 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	336 => array('Đèn sân vườn', 'Đèn sân vườn / đèn nấm'),
	335 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	329 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	328 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	327 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	326 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	325 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	324 => array('Đèn sân vườn', 'Đèn sân vườn / đèn nấm'),
	321 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	311 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	536 => array('Quạt năng lượng mặt trời', ''),
	535 => array('Đèn năng lượng mặt trời', 'Đèn Ốp Trần'),
	533 => array('Đèn năng lượng mặt trời', 'Đèn UFO'),
	530 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	529 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	528 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	527 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	526 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	525 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	524 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	523 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	522 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	521 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	520 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	519 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	518 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	517 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	516 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	515 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	514 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	513 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	512 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	511 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	510 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	509 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	508 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	507 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	506 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	505 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	504 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	503 => array('Đèn năng lượng mặt trời', 'Đèn Đường'),
	502 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	501 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	500 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	499 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	498 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	497 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	496 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	495 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	494 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	493 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	492 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	491 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	490 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	489 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	488 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	487 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	486 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	485 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	484 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	483 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	482 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	481 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	480 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	479 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	478 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	477 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	476 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	475 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	474 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	473 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	472 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	471 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	470 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	469 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	468 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
	467 => array('Đèn năng lượng mặt trời', 'Đèn Pha'),
);

echo $apply ? "CHẾ ĐỘ GHI (--apply)\n\n" : "CHẾ ĐỘ XEM TRƯỚC (thêm --apply để ghi thật)\n\n";

function fetchListByName(PDO $pdo, $p, $type)
{
    $rows = $pdo->query("select id, namevi, slugvi, status from {$p}product_list where type='".$type."'")->fetchAll(PDO::FETCH_ASSOC);
    $byName = array();
    foreach ($rows as $r) $byName[$r['namevi']] = $r;
    return $byName;
}

function fetchCatByListAndName(PDO $pdo, $p, $type)
{
    $rows = $pdo->query(
        "select c.id, c.id_list, c.namevi, c.slugvi, l.namevi as list_name
         from {$p}product_cat c join {$p}product_list l on l.id = c.id_list
         where c.type='".$type."'"
    )->fetchAll(PDO::FETCH_ASSOC);
    $byListAndName = array();
    foreach ($rows as $r) $byListAndName[$r['list_name'].'|||'.$r['namevi']] = $r;
    return $byListAndName;
}

function slugify($s)
{
    $s = str_replace(array('đ','Đ'), array('d','D'), $s);
    if (function_exists('transliterator_transliterate'))
        $s = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
    else
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/* ================= Bước 1: đổi tên danh mục cấp 1 ================= */
$listByName = fetchListByName($pdo, $p, $type);
echo "== Bước 1: đổi tên danh mục cấp 1 ==\n";
$renamePlans = array();
foreach ($l1Renames as $pair)
{
    list($old, $new) = $pair;
    if (isset($listByName[$new])) { echo "  BỎ QUA \"$old\" -> \"$new\": tên mới đã tồn tại (id ", $listByName[$new]['id'], ")\n"; continue; }
    if (!isset($listByName[$old])) { echo "  BỎ QUA \"$old\" -> \"$new\": không tìm thấy tên cũ trên production\n"; continue; }
    $row = $listByName[$old];
    echo "  #", $row['id'], " \"$old\" -> \"$new\"\n";
    $renamePlans[] = array('id' => $row['id'], 'old' => $old, 'new' => $new);
}

if ($apply)
{
    foreach ($renamePlans as $plan)
        $pdo->prepare("update {$p}product_list set namevi = ?, date_updated = ? where id = ?")
            ->execute(array($plan['new'], time(), $plan['id']));
}

/* ================= Bước 2: tạo danh mục cấp 2 mới ================= */
/* Nạp lại danh sách L1 theo tên MỚI (đã đổi ở bước 1, hoặc coi như đã đổi khi dry-run). */
$listByNameAfter = $listByName;
foreach ($renamePlans as $plan)
{
    $listByNameAfter[$plan['new']] = $listByNameAfter[$plan['old']];
    unset($listByNameAfter[$plan['old']]);
}

$catByListAndName = fetchCatByListAndName($pdo, $p, $type);
echo "\n== Bước 2: tạo danh mục cấp 2 mới ==\n";
$l2CreatePlans = array();
$numbByList = array();
foreach ($newL2 as $pair)
{
    list($l1name, $l2name) = $pair;
    if (!isset($listByNameAfter[$l1name])) { echo "  BỎ QUA \"$l1name\" > \"$l2name\": không tìm thấy danh mục cấp 1\n"; continue; }
    $l1id = $listByNameAfter[$l1name]['id'];
    if (isset($catByListAndName[$l1name.'|||'.$l2name])) { echo "  Đã có: \"$l1name\" > \"$l2name\" (id ", $catByListAndName[$l1name.'|||'.$l2name]['id'], ")\n"; continue; }
    if (!isset($numbByList[$l1id])) $numbByList[$l1id] = 0;
    $numbByList[$l1id]++;
    echo "  TẠO MỚI: \"$l1name\" > \"$l2name\"\n";
    $l2CreatePlans[] = array('id_list' => $l1id, 'namevi' => $l2name, 'slugvi' => slugify($l2name), 'numb' => $numbByList[$l1id]);
}

if ($apply)
{
    foreach ($l2CreatePlans as $plan)
        $pdo->prepare(
            "insert into {$p}product_cat (id_list, slugvi, slugen, namevi, nameen, options, numb, status, type, date_created, date_updated)
             values (?, ?, ?, ?, ?, '[]', ?, 'hienthi', ?, ?, ?)"
        )->execute(array($plan['id_list'], $plan['slugvi'], $plan['slugvi'], $plan['namevi'], $plan['namevi'], $plan['numb'], $type, time(), time()));
}

/* ================= Bước 3: gán lại danh mục cho từng sản phẩm ================= */
$listByNameFinal = $apply ? fetchListByName($pdo, $p, $type) : $listByNameAfter;
$catByListAndNameFinal = $apply ? fetchCatByListAndName($pdo, $p, $type) : $catByListAndName;
if (!$apply)
{
    /* Dry-run: mô phỏng thêm các cat mới vào bảng tra để tính bước 6/7 đúng. */
    foreach ($l2CreatePlans as $plan)
    {
        $l1name = null;
        foreach ($listByNameFinal as $name => $row) if ((int)$row['id'] === (int)$plan['id_list']) { $l1name = $name; break; }
        if ($l1name !== null) $catByListAndNameFinal[$l1name.'|||'.$plan['namevi']] = array('id' => -1, 'id_list' => $plan['id_list'], 'namevi' => $plan['namevi']);
    }
}

echo "\n== Bước 3: gán lại danh mục cho ", count($productMap), " sản phẩm ==\n";
$productUpdatePlans = array();
$notFound = array();
$ambiguous = array();
foreach ($productMap as $pid => $target)
{
    list($l1name, $l2name) = $target;
    $row = $pdo->prepare("select id, code, namevi, id_list, id_cat from {$p}product where id = ? and type = ?");
    $row->execute(array($pid, $type));
    $productRow = $row->fetch(PDO::FETCH_ASSOC);
    if (!$productRow) { $notFound[] = $pid; continue; }
    if (!isset($listByNameFinal[$l1name])) { $ambiguous[] = array($pid, "không tìm thấy danh mục cấp 1 đích \"$l1name\""); continue; }
    $newL1Id = (int)$listByNameFinal[$l1name]['id'];
    $newL2Id = 0;
    if ($l2name !== '')
    {
        $key = $l1name.'|||'.$l2name;
        if (!isset($catByListAndNameFinal[$key])) { $ambiguous[] = array($pid, "không tìm thấy danh mục cấp 2 đích \"$l1name\" > \"$l2name\""); continue; }
        $newL2Id = (int)$catByListAndNameFinal[$key]['id'];
    }
    if ((int)$productRow['id_list'] === $newL1Id && (int)$productRow['id_cat'] === $newL2Id) continue; /* đã đúng, khỏi ghi */
    $productUpdatePlans[] = array('id' => $pid, 'new_l1' => $newL1Id, 'new_l2' => $newL2Id, 'old_l1' => $productRow['id_list'], 'old_l2' => $productRow['id_cat']);
}
echo "  Sẽ cập nhật: ", count($productUpdatePlans), " | Đã đúng sẵn: ", (count($productMap) - count($productUpdatePlans) - count($notFound) - count($ambiguous)), "\n";
echo "  Không tìm thấy ID trên production: ", count($notFound), "\n";
if ($notFound) echo "    ", implode(', ', $notFound), "\n";
echo "  Không suy đoán được (bỏ qua, cần xem tay): ", count($ambiguous), "\n";
foreach ($ambiguous as $a) echo "    #", $a[0], ": ", $a[1], "\n";

if ($apply)
{
    foreach ($productUpdatePlans as $plan)
        $pdo->prepare("update {$p}product set id_list = ?, id_cat = ?, date_updated = ? where id = ?")
            ->execute(array($plan['new_l1'], $plan['new_l2'], time(), $plan['id']));
}

/* ================= Bước 4: ẩn sản phẩm băng keo ================= */
echo "\n== Bước 4: ẩn sản phẩm băng keo (", implode(', ', $hideProductIds), ") ==\n";
$hidePlans = array();
foreach ($hideProductIds as $pid)
{
    $row = $pdo->prepare("select id, namevi, status from {$p}product where id = ? and type = ?");
    $row->execute(array($pid, $type));
    $productRow = $row->fetch(PDO::FETCH_ASSOC);
    if (!$productRow) { echo "  BỎ QUA #$pid: không tìm thấy\n"; continue; }
    $statusParts = array_filter(explode(',', $productRow['status']), function($s) { return $s !== 'hienthi' && $s !== 'banchay' && $s !== ''; });
    $newStatus = implode(',', $statusParts);
    if ($newStatus === $productRow['status']) { echo "  #$pid \"", $productRow['namevi'], "\" đã ẩn sẵn\n"; continue; }
    echo "  #$pid \"", $productRow['namevi'], "\": \"", $productRow['status'], "\" -> \"$newStatus\"\n";
    $hidePlans[] = array('id' => $pid, 'status' => $newStatus);
}
if ($apply)
{
    foreach ($hidePlans as $plan)
        $pdo->prepare("update {$p}product set status = ?, date_updated = ? where id = ?")
            ->execute(array($plan['status'], time(), $plan['id']));
}

/* ================= Bước 5: xoá sản phẩm trùng (id=365) ================= */
echo "\n== Bước 5: xoá sản phẩm trùng #$duplicateDeleteId (giữ #$duplicateKeepId) ==\n";
$dupRow = $pdo->prepare("select id, code, namevi, regular_price from {$p}product where id = ? and type = ?");
$dupRow->execute(array($duplicateDeleteId, $type));
$dup = $dupRow->fetch(PDO::FETCH_ASSOC);
$doDeleteDup = false;
if (!$dup) echo "  BỎ QUA: #$duplicateDeleteId không tồn tại (có thể đã xoá trước đó)\n";
elseif ($dup['code'] !== $duplicateExpectedCode) echo "  BỎ QUA: #$duplicateDeleteId có code \"", $dup['code'], "\" khác vân tay mong đợi \"$duplicateExpectedCode\" - cần xem tay, không tự xoá\n";
else
{
    $keepCheck = $pdo->prepare("select id from {$p}product where id = ? and type = ?");
    $keepCheck->execute(array($duplicateKeepId, $type));
    if (!$keepCheck->fetch()) echo "  BỎ QUA: #$duplicateKeepId (bản giữ lại) không tồn tại trên production - cần xem tay\n";
    else { echo "  Sẽ xoá #$duplicateDeleteId \"", $dup['namevi'], "\" (code ", $dup['code'], ", giá ", $dup['regular_price'], ")\n"; $doDeleteDup = true; }
}
if ($apply && $doDeleteDup)
{
    $ids = array($duplicateDeleteId);
    $pdo->prepare("delete from {$p}product where id = ?")->execute($ids);
    $pdo->prepare("delete from {$p}product_project where id_product = ?")->execute($ids);
    $pdo->prepare("delete from {$p}content_section where scope='product' and scope_id = ?")->execute($ids);
    $pdo->prepare("delete from {$p}seo where com='product' and act='man' and type=? and id_parent = ?")->execute(array($type, $duplicateDeleteId));
    $pdo->prepare("delete from {$p}product_tags where id_parent = ?")->execute($ids);
    $pdo->prepare("delete from {$p}product_sale where id_parent = ?")->execute($ids);
    $pdo->prepare("delete from {$p}gallery where kind='man' and com='product' and id_parent = ?")->execute($ids);
    $pdo->prepare("delete from {$p}product_campaign_scope where scope_type='product' and scope_id = ?")->execute($ids);
    echo "  Đã xoá #$duplicateDeleteId. Ảnh trên đĩa KHÔNG bị xoá (giữ lại, không rủi ro).\n";
}

/* ================= Bước 6: giải thể danh mục cấp 1 rỗng ================= */
echo "\n== Bước 6: giải thể danh mục cấp 1 rỗng ==\n";
foreach ($l1Dissolve as $name)
{
    if (!isset($listByNameFinal[$name])) { echo "  BỎ QUA \"$name\": không tìm thấy trên production\n"; continue; }
    $id = (int)$listByNameFinal[$name]['id'];
    /* Đếm dựa trên trạng thái sau bước 3: sản phẩm còn trỏ id_list=$id mà KHÔNG nằm trong kế hoạch cập nhật rời khỏi đây. */
    $stillCount = (int)$pdo->prepare("select count(*) from {$p}product where id_list = ?")->execute(array($id));
    $stmt = $pdo->prepare("select id from {$p}product where id_list = ?");
    $stmt->execute(array($id));
    $remainingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $movedAway = array();
    foreach ($productUpdatePlans as $plan) $movedAway[$plan['id']] = true;
    $trulyRemaining = array_filter($remainingIds, function($rid) use ($movedAway) { return !isset($movedAway[$rid]); });
    if ($trulyRemaining) { echo "  BỎ QUA \"$name\" (#$id): còn ", count($trulyRemaining), " sản phẩm chưa được gán đi nơi khác (", implode(', ', $trulyRemaining), ") - cần xem tay\n"; continue; }
    echo "  Sẽ ẩn \"$name\" (#$id) - 0 sản phẩm còn lại sau bước 3\n";
    if ($apply) $pdo->prepare("update {$p}product_list set status = '', date_updated = ? where id = ?")->execute(array(time(), $id));
}

/* ================= Bước 7: ẩn danh mục cấp 2 cũ đã thay thế ================= */
echo "\n== Bước 7: ẩn danh mục cấp 2 cũ đã thay thế ==\n";
/* Tra trực tiếp DB theo id_list (ổn định, không phụ thuộc snapshot tên L1 chụp
   một lần đầu script) thay vì dùng $catByListAndName - snapshot đó lệch tên nếu
   --apply đã ghi xong bước 1 trước khi chạy tới đây. */
foreach ($oldL2Hide as $pair)
{
    list($l1name, $l2name) = $pair;
    if (!isset($listByNameFinal[$l1name])) { echo "  BỎ QUA \"$l1name\" > \"$l2name\": không tìm thấy danh mục cấp 1\n"; continue; }
    $l1id = (int)$listByNameFinal[$l1name]['id'];
    $lookup = $pdo->prepare("select id from {$p}product_cat where id_list = ? and namevi = ? and type = ?");
    $lookup->execute(array($l1id, $l2name, $type));
    $foundId = $lookup->fetchColumn();
    if ($foundId === false) { echo "  BỎ QUA \"$l1name\" > \"$l2name\": không tìm thấy trên production\n"; continue; }
    $id = (int)$foundId;
    $stmt = $pdo->prepare("select id from {$p}product where id_cat = ?");
    $stmt->execute(array($id));
    $remainingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $movedAway = array();
    foreach ($productUpdatePlans as $plan) $movedAway[$plan['id']] = true;
    $trulyRemaining = array_filter($remainingIds, function($rid) use ($movedAway) { return !isset($movedAway[$rid]); });
    if ($trulyRemaining) { echo "  BỎ QUA \"$l1name\" > \"$l2name\" (#$id): còn ", count($trulyRemaining), " sản phẩm - cần xem tay\n"; continue; }
    echo "  Sẽ ẩn \"$l1name\" > \"$l2name\" (#$id) - 0 sản phẩm còn lại sau bước 3\n";
    if ($apply) $pdo->prepare("update {$p}product_cat set status = '', date_updated = ? where id = ?")->execute(array(time(), $id));
}

echo "\n";
if (!$apply)
{
    echo "Chưa ghi gì. Đọc kỹ các dòng BỎ QUA ở trên (có \"cần xem tay\" nghĩa là script không tự đoán được).\n";
    echo "Chạy lại kèm --apply để ghi thật SAU KHI đã backup DB.\n";
}
else
{
    echo "Đã ghi xong. Nhớ xoá public_html/caches/ để trang chủ/danh mục cập nhật ngay.\n";
}
