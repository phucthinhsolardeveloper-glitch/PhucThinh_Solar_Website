<?php
	/* Kiểm tra có đăng nhập chưa */
	if($func->checkLoginAdmin()==false && $act!="login")
	{
		$func->redirect("index.php?com=user&act=login");
	}

	/* Kiểm tra active export word */
	if(!isset($config['order']['wordall']) || $config['order']['wordall'] == false) $func->transfer("Trang không tồn tại", "index.php", false);
	require_once LIBRARIES.'order_security.php';

	/* Setting */
	$setting = $d->rawQueryOne("select * from #_setting limit 0,1");
	$optsetting = (isset($setting['options']) && $setting['options'] != '') ? json_decode($setting['options'],true) : null;

	/* Thông tin đơn hàng */
	$time = time();
	$params = array();
	$sql = "select * from #_order where id<>0";
	$sql .= adminOrderExportFilter($_REQUEST, $params);
	$sql .= " order by date_created desc";
	$orders = $d->rawQuery($sql, $params);

	/* PHPWord */
	require_once LIBRARIES.'PHPWord.php';

	/* Khởi tạo PHPWord */
	$PHPWord = new PHPWord();

	/* file sample */
	$file_sample = LIBRARIES.'sample/orderlist.docx';

	/* Load file Word mẫu */
	$document = $PHPWord->loadTemplate($file_sample);

	/* Thông tin công ty */
	$document->setValue('{name_company}', $setting["namevi"]);
	$document->setValue('{hotline_company}', $optsetting["hotline"]);
	$document->setValue('{email_company}', $optsetting["email"]);
	$document->setValue('{address_company}', $optsetting["address"]);

	/* Tạo danh sách đơn hàng */
	$data = array();
	for($i=0;$i<count($orders);$i++) 
	{
		/* Phí ship */
		if(isset($orders[$i]['ship_price']) && $orders[$i]['ship_price'] > 0) $ship_price = $func->formatMoney(@$orders[$i]['ship_price']);
		else $ship_price = "Không";

		/* Trang thái */
		$order_status_info = $d->rawQueryOne("select namevi from #_order_status where id = ? limit 0,1",array($orders[$i]['order_status']));

		/* STT */
		$data["numb"][$i] = $i+1;

		/* Thông tin đơn hàng */
		$data["code"][$i] = @$orders[$i]['code'];
		$data["order_date"][$i] = date('H:i A d-m-Y',@$orders[$i]['date_created']);
		$data["order_status"][$i] = $order_status_info['namevi'];
		$order_payment = $func->getInfoDetail('namevi', 'news', @$orders[$i]['order_payment']);
		$data["order_payment"][$i] = $order_payment['namevi'];

		/* Thông tin khách hàng */
		$data["fullname_customer"][$i] = @$orders[$i]['fullname'];
		$data["phone_customer"][$i] = @$orders[$i]['phone'];
		$data["email_customer"][$i] = @$orders[$i]['email'];
		$data["address_customer"][$i] = @$orders[$i]['address'];

		/* Tính thành tiền */
		$data["temp_price"][$i] = $func->formatMoney(@$orders[$i]['temp_price']);
		$data["ship_price"][$i] = $ship_price;
		$data["total_price"][$i] = $func->formatMoney(@$orders[$i]['total_price']);
	}
	
	/* Thiết lập đối tượng dữ liệu từng dòng */
	$document->cloneRow('TB', $data);
	
	/* Xuất file */
	$filename = "orders_list".$time."_".date('d_m_Y').".docx";
	/* Lưu vào thư mục tạm rồi mới stream. Trước đây save($filename) dùng đường dẫn
	   tương đối nên ghi vào thư mục làm việc (administrator/), nơi www-data không
	   có quyền ghi — xuất file luôn hỏng. Tên tải về vẫn giữ nguyên $filename. */
	$exportPath = tempnam(sys_get_temp_dir(), 'orderdoc-');
	if($exportPath === false) $func->transfer("Không tạo được file tạm để xuất tài liệu", "index.php?com=order&act=man", false);
	$document->save($exportPath);
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename='.$filename);
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: '. filesize($exportPath));
	flush();
	readfile($exportPath);
	@unlink($exportPath);
?>
