<?php
	if(!defined('SOURCES')) die("Error");

	/* SEO */
	$seo->set('title',$titleMain);

	/* breadCrumbs */
	if(!empty($titleMain)) $breadcr->set($com,$titleMain);
	$breadcrumbs = $breadcr->get();

	/* Đơn hàng vừa đặt (nếu có mã hợp lệ) */
	$orderCode = (!empty($_GET['code'])) ? htmlspecialchars($_GET['code']) : '';
	$orderThanks = null;
	if($orderCode != '')
	{
		$orderThanks = $d->rawQueryOne("select code, fullname, total_price from #_order where code = ? limit 0,1",array($orderCode));
	}
?>
