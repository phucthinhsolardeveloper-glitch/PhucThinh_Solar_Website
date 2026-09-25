<?php
	if(!defined('SOURCES')) die("Error");

	/* Bảng điều khiển: gom toàn bộ truy vấn + tính toán ở controller, template chỉ render. */
	if(defined('PTS_DASHBOARD_LOADED')) return;
	define('PTS_DASHBOARD_LOADED', 1);

	require_once LIBRARIES.'admin_product_completeness.php';
	require_once LIBRARIES.'product_content_blocks.php';
	require_once LIBRARIES.'product_pdp_profiles.php';

	$template = 'index';

	$selectedMonth = isset($_GET['month']) ? (int) $_GET['month'] : 0;
	$selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : 0;

	if($selectedMonth >= 1 && $selectedMonth <= 12 && $selectedYear >= 2000 && $selectedYear <= (int) date('Y') + 20)
	{
		$time = $selectedYear.'-'.$selectedMonth.'-1';
		$date = strtotime($time);
	}
	else
	{
		$date = time();
	}

	$day = date('d', $date);
	$month = date('m', $date);
	$year = date('Y', $date);
	$firstDay = mktime(0,0,0,$month, 1, $year);
	$title = strftime('%B', $firstDay);
	$dayOfWeek = date('D', $firstDay);
	$daysInMonth = (int) date('t', $date);
	$timestamp = strtotime('next Sunday');
	$weekDays = array();

	/* Make data for js chart */
	$charts = array();
	$charts['month'] = $month;


	$blank = date('w', strtotime("{$year}-{$month}-01"));
	$gth = array();
	$doanhthu = array();


	$monthStart = strtotime($year.'-'.$month.'-01 00:00:00');
	$monthEnd = strtotime('+1 month', $monthStart);
	$visitorByDay = array();
	$revenueByDay = array();

	$visitorRows = $d->rawQuery(
		"select day(from_unixtime(tm)) as day_number, count(*) as total from #_counter where tm >= ? and tm < ? group by day_number",
		array($monthStart, $monthEnd)
	);
	foreach($visitorRows as $row) $visitorByDay[(int) $row['day_number']] = (int) $row['total'];

	$revenueRows = $d->rawQuery(
		"select day(from_unixtime(date_created)) as day_number, coalesce(sum(total_price), 0) as total from #_order where date_created >= ? and date_created < ? group by day_number",
		array($monthStart, $monthEnd)
	);
	foreach($revenueRows as $row) $revenueByDay[(int) $row['day_number']] = (float) $row['total'];

	for($i=1; $i<=$daysInMonth; $i++)
	{
		$todayVisitors = isset($visitorByDay[$i]) ? $visitorByDay[$i] : 0;
		$todayRevenue = isset($revenueByDay[$i]) ? $revenueByDay[$i] : 0;
		$charts['series'][] = $todayVisitors;
		$charts['labels'][] = 'D'.$i;
		$gth[] = $todayVisitors;
		$doanhthu[] = $todayRevenue;
	}

	/* Hàng đợi nghiệp vụ: chỉ đếm, không lấy PII. */
	$dashboardNewOrders = $d->rawQueryOne("select count(id) as total from #_order where order_status = ?", array(1));
	$dashboardNewConsults = $d->rawQueryOne("select count(id) as total from #_newsletter where type = ? and (confirm_status is null or confirm_status = '' or confirm_status = '0')", array('dangkynhantin'));
	$dashboardNewDealers = $d->rawQueryOne("select count(id) as total from #_newsletter where type = ? and (confirm_status is null or confirm_status = '' or confirm_status = '0')", array('dangkynhantin2'));
	$dashboardPendingReviews = $d->rawQueryOne("select count(id) as total from #_comment where id_parent = 0 and find_in_set('new-admin', status)");
	/* Dùng chung định nghĩa với filter danh sách sản phẩm (adminProductFilterIdsByStatus)
	   thay vì SQL viết tay riêng chỉ nhìn cong_suat - card dashboard và filter
	   list phải luôn khớp số, không lệch nhau. */
	$dashboardMissingWattage = count(adminProductFilterIdsByStatus($d, 'missing_wattage', "where p.type = ?", array('san-pham')));
	$dashboardPolicies = $d->rawQueryOne("select count(id) as total, sum(case when trim(coalesce(contentvi, '')) <> '' and find_in_set('hienthi', status) then 1 else 0 end) as completed from #_news where type = ?", array('chinh-sach'));

	$dashboardNewOrders = (int) $dashboardNewOrders['total'];
	$dashboardNewConsults = (int) $dashboardNewConsults['total'];
	$dashboardNewDealers = (int) $dashboardNewDealers['total'];
	$dashboardPendingReviews = (int) $dashboardPendingReviews['total'];
	$dashboardPolicyTotal = (int) $dashboardPolicies['total'];
	$dashboardPolicyCompleted = (int) $dashboardPolicies['completed'];
?>
