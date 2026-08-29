<?php
	if(!defined('SOURCES')) die("Error");

	/* Kiểm tra active đơn hàng */
	if(!isset($config['order']['active']) || $config['order']['active'] == false) $func->transfer("Trang không tồn tại", "index.php", false);
	require_once LIBRARIES.'order_security.php';

	/* Cấu hình đường dẫn trả về */

	$strUrl = "";
	$urlParams = array();
	foreach(array('order_status', 'payment_status', 'order_payment', 'order_date', 'range_price', 'id_city', 'id_district', 'id_ward', 'keyword') as $param)
	{
		if(isset($_REQUEST[$param]) && $_REQUEST[$param] !== '') $urlParams[$param] = $_REQUEST[$param];
	}
	if(!empty($urlParams)) $strUrl = '&'.http_build_query($urlParams);
	switch($act)
	{
		case "man":
			viewMans();
			$template = "order/man/mans";
			break;
		case "edit":
			editMan();
			$template = "order/man/man_add";
			break;
		case "save":
			saveMan();
			break;
		case "delete":
			deleteMan();
			break;
		default:
			$template = "404";
	}

	/* View order */
	function viewMans()
	{
		global $d, $func, $strUrl, $curPage, $items, $paging, $minTotal, $maxTotal, $price_from, $price_to, $allNewOrder, $totalNewOrder, $allConfirmOrder, $totalConfirmOrder, $allDeliveriedOrder, $totalDeliveriedOrder, $allCanceledOrder, $totalCanceledOrder;

		$where = "";
		$params = array();
		$order_status = isset($_REQUEST['order_status']) && $_REQUEST['order_status'] !== '' ? (int) $_REQUEST['order_status'] : null;
		$payment_status = isset($_REQUEST['payment_status']) && $_REQUEST['payment_status'] !== '' ? (int) $_REQUEST['payment_status'] : null;
		$order_payment = isset($_REQUEST['order_payment']) && $_REQUEST['order_payment'] !== '' ? (int) $_REQUEST['order_payment'] : null;
		$order_date = isset($_REQUEST['order_date']) ? trim($_REQUEST['order_date']) : '';
		$range_price = isset($_REQUEST['range_price']) ? trim($_REQUEST['range_price']) : '';
		$city = isset($_REQUEST['id_city']) ? (int) $_REQUEST['id_city'] : 0;
		$district = isset($_REQUEST['id_district']) ? (int) $_REQUEST['id_district'] : 0;
		$ward = isset($_REQUEST['id_ward']) ? (int) $_REQUEST['id_ward'] : 0;

		if($order_status !== null && $order_status > 0)
		{
			$where .= " and o.order_status = ?";
			$params[] = $order_status;
		}
		if($payment_status !== null)
		{
			$where .= " and o.payment_status = ?";
			$params[] = $payment_status;
		}
		if($order_payment !== null && $order_payment > 0)
		{
			$where .= " and o.order_payment = ?";
			$params[] = $order_payment;
		}
		if($order_date !== '')
		{
			$dateParts = explode(' - ', $order_date);
			if(count($dateParts) === 2)
			{
				$date_from = strtotime(str_replace('/', '-', trim($dateParts[0])).' 00:00:00');
				$date_to = strtotime(str_replace('/', '-', trim($dateParts[1])).' 23:59:59');
				if($date_from !== false && $date_to !== false && $date_from <= $date_to)
				{
					$where .= " and o.date_created >= ? and o.date_created <= ?";
					$params[] = $date_from;
					$params[] = $date_to;
				}
			}
		}
		if($range_price !== '')
		{
			$priceParts = explode(';', $range_price);
			if(count($priceParts) === 2)
			{
				$price_from = max(0, (float) $priceParts[0]);
				$price_to = max($price_from, (float) $priceParts[1]);
				$where .= " and o.total_price >= ? and o.total_price <= ?";
				$params[] = $price_from;
				$params[] = $price_to;
			}
		}
		foreach(array('city' => $city, 'district' => $district, 'ward' => $ward) as $column => $value)
		{
			if($value > 0)
			{
				$where .= " and o.".$column." = ?";
				$params[] = $value;
			}
		}
		if(isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) !== '')
		{
			$keyword = '%'.trim($_REQUEST['keyword']).'%';
			$where .= " and (o.fullname LIKE ? or o.email LIKE ? or o.phone LIKE ? or o.code LIKE ?)";
			$params[] = $keyword;
			$params[] = $keyword;
			$params[] = $keyword;
			$params[] = $keyword;
		}

		$perPage = 10;
		$startpoint = max(0, (((int) $curPage) * $perPage) - $perPage);
		$limit = " limit ".$startpoint.",".$perPage;
		$sql = "select o.*, os.namevi as order_status_name, os.class_order, pay.namevi as order_payment_name from #_order o left join #_order_status os on os.id = o.order_status left join #_news pay on pay.id = o.order_payment where o.id<>0 $where order by o.date_created desc $limit";
		$items = $d->rawQuery($sql,$params);
		$sqlNum = "select count(*) as 'num' from #_order o where o.id<>0 $where";
		$count = $d->rawQueryOne($sqlNum,$params);
		$total = (!empty($count)) ? $count['num'] : 0;
		$url = "index.php?com=order&act=man".$strUrl;
		$paging = $func->pagination($total,$perPage,$curPage,$url);

		$priceRange = $d->rawQueryOne("select coalesce(min(total_price), 0) as min_total, coalesce(max(total_price), 0) as max_total from #_order");
		$minTotal = (float) $priceRange['min_total'];
		$maxTotal = (float) $priceRange['max_total'];

		$orderStats = array(1 => array(0, 0), 2 => array(0, 0), 4 => array(0, 0), 5 => array(0, 0));
		$statRows = $d->rawQuery("select order_status, count(id) as total, coalesce(sum(total_price), 0) as amount from #_order where order_status in (1,2,4,5) group by order_status");
		foreach($statRows as $row) $orderStats[(int) $row['order_status']] = array((int) $row['total'], (float) $row['amount']);

		list($allNewOrder, $totalNewOrder) = $orderStats[1];
		list($allConfirmOrder, $totalConfirmOrder) = $orderStats[2];
		list($allDeliveriedOrder, $totalDeliveriedOrder) = $orderStats[4];
		list($allCanceledOrder, $totalCanceledOrder) = $orderStats[5];
	}

	/* Edit order */
	function editMan()
	{
		global $d, $func, $curPage, $item, $order_detail;

		$id = (!empty($_GET['id'])) ? htmlspecialchars($_GET['id']) : 0;

		if(empty($id))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=order&act=man&p=".$curPage, false);
		}
		else
		{
			$item = $d->rawQueryOne("select * from #_order where id = ? limit 0,1",array($id));

			if(empty($item))
			{
				$func->transfer("Dữ liệu không có thực", "index.php?com=order&act=man&p=".$curPage, false);
			}
			else
			{
				/* Lấy chi tiết đơn hàng */
				$order_detail = $d->rawQuery("select * from #_order_detail where id_order = ? order by id desc",array($id));
			}
		}
	}

	/* Save order */
	function saveMan()
	{
		global $d, $func, $curPage, $config;

		/* Check post */
		if(empty($_POST))
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=order&act=man&p=".$curPage, false);
		}
		adminRequirePostCsrf();


		/* Post dữ liệu */
		$id = (!empty($_POST['id'])) ? htmlspecialchars($_POST['id']) : 0;
		$prepared = prepareAdminOrderUpdate($d, isset($_POST['data']) ? $_POST['data'] : array(), isset($config['order']['payment']) ? $config['order']['payment'] : array());
		$data = $prepared['data'];
		if(!empty($prepared['errors'])) $func->transfer(implode('. ', $prepared['errors']), "index.php?com=order&act=edit&id=".(int)$id."&p=".$curPage, false);

		/* Save data */
		if($id)
		{
			$d->where('id', $id);
			if($d->update('order',$data))
			{
				$func->transfer("Cập nhật dữ liệu thành công", "index.php?com=order&act=man&p=".$curPage);
			}
			else
			{
				$func->transfer("Cập nhật dữ liệu bị lỗi", "index.php?com=order&act=man&p=".$curPage, false);
			}
		}
		else
		{
			$func->transfer("Dữ liệu rỗng", "index.php?com=order&act=man&p=".$curPage, false);
		}
	}

	/* Delete order */
	function deleteMan()
	{
		global $d, $func, $curPage;

		adminRequirePostCsrf();
		$id = (!empty($_POST['id'])) ? (int) $_POST['id'] : 0;

		if($id)
		{
			$row = $d->rawQueryOne("select id from #_order where id = ? limit 0,1",array($id));

			if(!empty($row))
			{
				$d->rawQuery("delete from #_order_detail where id_order = ?",array($id));
				$d->rawQuery("delete from #_order where id = ?",array($id));
				$func->transfer("Xóa dữ liệu thành công", "index.php?com=order&act=man&p=".$curPage);
			}
			else
			{
				$func->transfer("Xóa dữ liệu bị lỗi", "index.php?com=order&act=man&p=".$curPage, false);
			}
		}
		elseif(isset($_POST['listid']))
		{
			$listid = array_values(array_filter(array_map('intval', explode(",", (string) $_POST['listid']))));
			
			for($i=0;$i<count($listid);$i++)
			{
				$id = (int) $listid[$i];
				$row = $d->rawQueryOne("select id from #_order where id = ? limit 0,1",array($id));

				if(!empty($row))
				{
					$d->rawQuery("delete from #_order_detail where id_order = ?",array($id));
					$d->rawQuery("delete from #_order where id = ?",array($id));
				}
			}
			
			$func->transfer("Xóa dữ liệu thành công", "index.php?com=order&act=man&p=".$curPage);
		}
		else
		{
			$func->transfer("Không nhận được dữ liệu", "index.php?com=order&act=man&p=".$curPage, false);
		}
	}
?>
