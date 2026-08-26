<?php
	if(!defined('SOURCES')) die("Error");

	/* SEO */
	$seo->set('title',$titleMain);

	/* breadCrumbs */
	if(!empty($titleMain)) $breadcr->set($com,$titleMain);
	$breadcrumbs = $breadcr->get();

	/* Chỉ hiện đơn thuộc session vừa đặt và chỉ phát conversion một lần */
	$thanksToken = (!empty($_GET['token'])) ? trim($_GET['token']) : '';
	$orderThanks = null;
	$fireOrderConversion = false;
	if(preg_match('/^[a-f0-9]{48}$/', $thanksToken) && !empty($_SESSION['order_thanks'][$thanksToken]))
	{
		$orderSession = $_SESSION['order_thanks'][$thanksToken];
		if(!empty($orderSession['created_at']) && time() - $orderSession['created_at'] <= 7200)
		{
			$orderThanks = $d->rawQueryOne("select code, fullname, total_price from #_order where id = ? and code = ? limit 0,1",array((int) $orderSession['id'], $orderSession['code']));
			if(!empty($orderThanks))
			{
				$fireOrderConversion = !empty($orderSession['conversion_pending']);
				$_SESSION['order_thanks'][$thanksToken]['conversion_pending'] = false;
			}
			else
			{
				unset($_SESSION['order_thanks'][$thanksToken]);
			}
		}
		else
		{
			unset($_SESSION['order_thanks'][$thanksToken]);
		}
	}
?>
