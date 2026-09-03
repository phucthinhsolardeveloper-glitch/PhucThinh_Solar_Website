<?php
	if(!defined('SOURCES')) die("Error");		

	if(!function_exists('checkoutPersistOrder'))
	{
		function checkoutFindPersistedOrder($d, $checkoutToken, $expectedDetailCount = null)
		{
			if(!is_string($checkoutToken) || preg_match('/^[a-f0-9]{64}$/', $checkoutToken) !== 1) return null;
			$order = $d->rawQueryOne(
				"select o.id, o.code, count(od.id) as detail_count from #_order o left join #_order_detail od on od.id_order = o.id where o.checkout_token = ? group by o.id, o.code limit 0,1",
				array($checkoutToken)
			);
			if(empty($order['id']) || (int)$order['detail_count'] < 1) return null;
			if($expectedDetailCount !== null && (int)$order['detail_count'] !== (int)$expectedDetailCount) return null;
			return array('success' => true, 'id' => (int)$order['id'], 'code' => (string)$order['code'], 'detail_count' => (int)$order['detail_count'], 'reused' => true);
		}

		function checkoutRegisterThanks($orderId, $orderCode)
		{
			$thanksToken = bin2hex(random_bytes(24));
			if(empty($_SESSION['order_thanks']) || !is_array($_SESSION['order_thanks'])) $_SESSION['order_thanks'] = array();
			foreach($_SESSION['order_thanks'] as $token => $orderSession)
			{
				if(empty($orderSession['created_at']) || time() - $orderSession['created_at'] > 7200) unset($_SESSION['order_thanks'][$token]);
			}
			$_SESSION['order_thanks'][$thanksToken] = array(
				'id' => (int)$orderId,
				'code' => (string)$orderCode,
				'created_at' => time(),
				'conversion_pending' => true
			);
			return $thanksToken;
		}

		function checkoutPersistOrder($d, $cart, $cartItems, $orderData, $lang, $faultInjector = null)
		{
			$idInsert = 0;
			$transactionStarted = false;
			$detailCount = 0;
			$cleanupState = (object)array('active' => false, 'id' => 0);
			$checkoutToken = isset($orderData['checkout_token']) ? (string)$orderData['checkout_token'] : '';
			$existingOrder = checkoutFindPersistedOrder($d, $checkoutToken, count($cartItems));
			if($existingOrder) return $existingOrder;
			if(preg_match('/^[a-f0-9]{64}$/', $checkoutToken) !== 1) $orderData['checkout_token'] = bin2hex(random_bytes(32));
			try
			{
				$d->startTransaction();
				$transactionStarted = true;
				if(is_callable($faultInjector) && $faultInjector('order', 0)) throw new Exception('Không thể tạo đơn hàng.');
				$idInsert = (int)$d->insert('order', $orderData);
				if($idInsert <= 0) throw new Exception('Không thể tạo đơn hàng.');
				$cleanupState->active = true;
				$cleanupState->id = $idInsert;
				register_shutdown_function(function() use ($d, $cleanupState) {
					if(!$cleanupState->active || $cleanupState->id <= 0) return;
					$d->rawQuery("delete from #_order_detail where id_order = ?", array($cleanupState->id));
					$d->rawQuery("delete from #_order where id = ?", array($cleanupState->id));
				});

				foreach(array_values($cartItems) as $index => $cartItem)
				{
					$pid = !empty($cartItem['productid']) ? (int)$cartItem['productid'] : 0;
					$quantity = !empty($cartItem['qty']) ? (int)$cartItem['qty'] : 0;
					$product = $pid > 0 ? $cart->getProductInfo($pid) : null;
					if(empty($product) || $quantity <= 0) throw new Exception('Không thể đọc đủ thông tin sản phẩm trong giỏ hàng.');

					$detail = array(
						'id_product' => $pid,
						'id_order' => $idInsert,
						'photo' => $product['photo'],
						'name' => $product['name'.$lang],
						'code' => $orderData['code'],
						'color' => $cart->getProductColor(!empty($cartItem['color']) ? $cartItem['color'] : 0),
						'size' => $cart->getProductSize(!empty($cartItem['size']) ? $cartItem['size'] : 0),
						'regular_price' => $product['regular_price'],
						'sale_price' => $product['sale_price'],
						'quantity' => $quantity
					);
					if(is_callable($faultInjector) && $faultInjector('detail', $index)) throw new Exception('Không thể lưu đầy đủ sản phẩm trong đơn hàng.');
					if(!$d->insert('order_detail', $detail)) throw new Exception('Không thể lưu đầy đủ sản phẩm trong đơn hàng.');
					$detailCount++;
				}

				if($detailCount !== count($cartItems)) throw new Exception('Số dòng chi tiết đơn hàng không khớp giỏ hàng.');
				$d->commit();
				$transactionStarted = false;
				$cleanupState->active = false;
				return array('success' => true, 'id' => $idInsert, 'code' => (string)$orderData['code'], 'detail_count' => $detailCount, 'reused' => false);
			}
			catch(Throwable $e)
			{
				if($transactionStarted)
				{
					try { $d->rollback(); } catch(Exception $rollbackError) {}
				}
				if($idInsert > 0)
				{
					$d->rawQuery("delete from #_order_detail where id_order = ?", array($idInsert));
					$d->rawQuery("delete from #_order where id = ?", array($idInsert));
				}
				$cleanupState->active = false;
				$existingOrder = checkoutFindPersistedOrder($d, $checkoutToken, count($cartItems));
				if($existingOrder) return $existingOrder;
				return array('success' => false, 'id' => 0, 'detail_count' => 0, 'error' => $e->getMessage());
			}
		}
	}

	if(defined('CHECKOUT_ATOMICITY_FUNCTIONS_ONLY')) return;

	if(!publicProductPricesVisible())
	{
		unset($_SESSION['cart']);
		if(!empty($_POST['thanhtoan'])) $func->transfer("Sản phẩm hiện nhận báo giá trực tiếp. Vui lòng liên hệ để được tư vấn.", "lien-he/", false);
	}

	/* SEO */
	$seo->set('title',$titleMain);

	/* breadCrumbs */
	if(!empty($titleMain)) $breadcr->set($com,$titleMain);
	$breadcrumbs = $breadcr->get();

	/* Tỉnh thành */
	$city = $cache->get("select name, id from #_city order by id asc", null, 'result', 7200);

	/* Hình thức thanh toán */
	$payments_info = $d->rawQuery("select name$lang, desc$lang, id from #_news where type = ? order by numb,id desc",array('hinh-thuc-thanh-toan'));

	if(!empty($_POST['thanhtoan']))
	{
		if(!formCsrfValid(isset($_POST['form_csrf']) ? $_POST['form_csrf'] : ''))
		{
			$func->transfer("Phiên đặt hàng không hợp lệ. Vui lòng tải lại trang.", "gio-hang/", false);
		}
		$checkoutToken = isset($_POST['checkout_token']) ? (string)$_POST['checkout_token'] : '';
		if(!checkoutIdempotencyTokenFormatValid($checkoutToken))
		{
			$func->transfer("Phiên gửi đơn hàng không hợp lệ. Vui lòng tải lại trang.", "gio-hang/", false);
		}
		$replayedOrder = checkoutFindPersistedOrder($d, $checkoutToken);
		if($replayedOrder)
		{
			$thanksToken = checkoutRegisterThanks($replayedOrder['id'], $replayedOrder['code']);
			checkoutRotateIdempotencyToken();
			unset($_SESSION['cart']);
			$func->redirect("cam-on/?token=".rawurlencode($thanksToken));
		}
		if(!checkoutIdempotencyTokenValid($checkoutToken))
		{
			$func->transfer("Phiên gửi đơn hàng không hợp lệ. Vui lòng tải lại trang.", "gio-hang/", false);
		}
		$publicIp = formClientIp($_SERVER, isset($config['login']['trusted-proxies']) ? $config['login']['trusted-proxies'] : array());
		if(!formPersistentRateLimit($d, 'checkout', $publicIp, 5, 600))
		{
			$func->transfer("Bạn thao tác quá nhanh. Vui lòng thử lại sau.", "gio-hang/", false);
		}
		/* Check order */
		if(empty($_SESSION['cart']))
		{
			$func->transfer("Đơn hàng không hợp lệ. Vui lòng thử lại sau.", $configBase, false);
		}
		foreach($_SESSION['cart'] as $cartItem)
		{
			$checkoutProduct = $cart->getProductInfo((int)$cartItem['productid']);
			if(empty($checkoutProduct) || publicProductRequiresQuote($checkoutProduct))
			{
				$func->transfer("Giỏ hàng có sản phẩm đã chuyển sang liên hệ báo giá. Vui lòng gửi yêu cầu tư vấn.", "lien-he/", false);
			}
		}

		/* Data */
		$dataOrder = (!empty($_POST['dataOrder'])) ? $_POST['dataOrder'] : null;

		/* Check data */
		if(!empty($dataOrder))
		{
			/* Info */
			$code = strtoupper($func->stringRandom(6));
			$order_date = time();
		    $fullname = (!empty($dataOrder['fullname'])) ? htmlspecialchars($dataOrder['fullname']) : '';
		    $email = (!empty($dataOrder['email'])) ? htmlspecialchars($dataOrder['email']) : '';
		    $phone = (!empty($dataOrder['phone'])) ? htmlspecialchars($dataOrder['phone']) : '';
		    $requirements = (!empty($dataOrder['requirements'])) ? htmlspecialchars($dataOrder['requirements']) : '';

		    /* Place */
		    $city = (!empty($dataOrder['city'])) ? htmlspecialchars($dataOrder['city']) : 0;
		    $district = (!empty($dataOrder['district'])) ? htmlspecialchars($dataOrder['district']) : 0;
		    $ward = (!empty($dataOrder['ward'])) ? htmlspecialchars($dataOrder['ward']) : 0;
		    $city_text = $func->getInfoDetail('name', "city", $city);
		    $district_text = $func->getInfoDetail('name', "district", $district);
		    $ward_text = $func->getInfoDetail('name', "ward", $ward);
		    $address = htmlspecialchars($dataOrder['address']).', '.$ward_text['name'].', '.$district_text['name'].', '.$city_text['name'];

		    /* Payment */
		    $order_payment = (!empty($dataOrder['payments'])) ? htmlspecialchars($dataOrder['payments']) : 0;
		    $order_payment_data = $func->getInfoDetail('namevi', 'news', $order_payment);
		    $order_payment_text = $order_payment_data['namevi'];

		    /* Ship */
		    if(!empty($config['order']['ship']))
		    {
				$ship_data = (!empty($dataOrder['ward'])) ? $func->getInfoDetail('ship_price', "ward", $dataOrder['ward']) : array();
				$ship_price = (!empty($ship_data['ship_price'])) ? $ship_data['ship_price'] : 0;
		    }
		    else
		    {
		    	$ship_price = 0;
		    }

		    /* Price */
			$temp_price = $cart->getOrderTotal();
			$total_price = (!empty($ship_price)) ? $cart->getOrderTotal() + $ship_price : $cart->getOrderTotal();

		    /* Cart */
		    $order_detail = '';
		    $max = (!empty($_SESSION['cart'])) ? count($_SESSION['cart']) : 0;
		}

	    /* Valid data */
		if(empty($dataOrder['payments']))
		{
			$response['messages'][] = 'Chưa chọn hình thức thanh toán';
		}

		if(empty($dataOrder['fullname']))
		{
			$response['messages'][] = 'Họ tên không được trống';
		}

		if(empty($dataOrder['phone']))
		{
			$response['messages'][] = 'Số điện thoại không được trống';
		}

		if(!empty($dataOrder['phone']) && !$func->isPhone($dataOrder['phone']))
		{
			$response['messages'][] = 'Số điện thoại không hợp lệ';
		}

		if(empty($dataOrder['city']))
		{
			$response['messages'][] = 'Chưa chọn tỉnh/thành phố';
		}

		if(empty($dataOrder['district']))
		{
			$response['messages'][] = 'Chưa chọn quận/huyện';
		}

		if(empty($dataOrder['ward']))
		{
			$response['messages'][] = 'Chưa chọn phường/xã';
		}


		if(empty($dataOrder['address']))
		{
			$response['messages'][] = 'Địa chỉ không được trống';
		}

		if(empty($dataOrder['email']))
		{
			$response['messages'][] = 'Email không được trống';
		}

		if(!empty($dataOrder['email']) && !$func->isEmail($dataOrder['email']))
		{
			$response['messages'][] = 'Email không hợp lệ';
		}

		if(!empty($response))
		{
			/* Flash data */
			if(!empty($dataOrder))
			{
				foreach($dataOrder as $k => $v)
				{
					if(!empty($v))
					{
						$flash->set($k, $v);
					}
				}
			}

			/* Errors */
			$response['status'] = 'danger';
			$message = base64_encode(json_encode($response));
			$flash->set("message", $message);
			$func->redirect("gio-hang");
		}

	    /* Details order */
	    for($i=0; $i<$max; $i++)
	    {
	    	$pid = $_SESSION['cart'][$i]['productid'];
			$q = $_SESSION['cart'][$i]['qty'];
			$color = $_SESSION['cart'][$i]['color'];					
			$size = $_SESSION['cart'][$i]['size'];
			$code_order = $_SESSION['cart'][$i]['code'];
			$proinfo = $cart->getProductInfo($pid);
			$text_color = $cart->getProductColor($color);
			$text_size = $cart->getProductSize($size);
			$text_attr='';
			if($text_color!='' && $text_size!='') $text_attr = $text_color." - ".$text_size;
			else if($text_color!='') $text_attr = $text_color;
			else if($text_size!='') $text_attr = $text_size;

			if($q==0) continue;

			/* Variables detail order */
			$orderDetailVars = array(
				'{productName}',
				'{productAttr}',
				'{productSalePrice}',
				'{productRegularPrice}',
				'{productQuantity}',
				'{productSaleTotalPrice}',
				'{productRegularTotalPrice}'
			);

			/* Values detail order */
			$orderDetailVals = array(
				$proinfo['name'.$lang],
				$text_attr,
				$func->formatMoney($proinfo['sale_price']),
				$func->formatMoney($proinfo['regular_price']),
				$q,
				$func->formatMoney($proinfo['sale_price'] * $q),
				$func->formatMoney($proinfo['regular_price'] * $q)
			);

			/* Get order details */
			$order_detail .= str_replace($orderDetailVars, $orderDetailVals, $emailer->markdown('order/details', ['productAttr' => $text_attr, 'salePrice' => $proinfo['sale_price']]));
	    }

	    /* Total order */
	    /* Variables total order */
	    $orderTotalVars = array(
	    	'{orderTempPrice}',
	    	'{orderShipPrice}',
	    	'{orderTotalPrice}'
	    );

	    /* Values total order */
	    $orderTotalVals = array(
	    	$func->formatMoney($temp_price),
	    	$func->formatMoney($ship_price),
	    	$func->formatMoney($total_price)
	    );

	    /* Get total order */
	    $order_detail .= str_replace($orderTotalVars, $orderTotalVals, $emailer->markdown('order/total', ['shipPrice' => $ship_price]));

		/* lưu đơn hàng */
		$data_donhang = array();
		$data_donhang['id_user'] = (!empty($_SESSION[$loginMember]['id'])) ? $_SESSION[$loginMember]['id'] : 0;
		$data_donhang['code'] = $code;
		$data_donhang['checkout_token'] = (string)$_POST['checkout_token'];
		$data_donhang['fullname'] = $fullname;
		$data_donhang['phone'] = $phone;
		$data_donhang['address'] = $address;
		$data_donhang['email'] = $email;
		$data_donhang['order_payment'] = $order_payment;
		$data_donhang['ship_price'] = $ship_price;
		$data_donhang['temp_price'] = $temp_price;
		$data_donhang['total_price'] = $total_price;
		$data_donhang['requirements'] = $requirements;
		$data_donhang['date_created'] = $order_date;
		$data_donhang['order_status'] = 1;
		$data_donhang['city'] = $city;
		$data_donhang['district'] = $district;
		$data_donhang['ward'] = $ward;
		$data_donhang['numb'] = 1;
		$faultInjector = isset($checkoutAtomicityFault) && is_callable($checkoutAtomicityFault) ? $checkoutAtomicityFault : null;
		$persistedOrder = checkoutPersistOrder($d, $cart, $_SESSION['cart'], $data_donhang, $lang, $faultInjector);
		if(empty($persistedOrder['success']))
		{
			$func->transfer("Có lỗi khi lưu đơn hàng. Giỏ hàng của bạn vẫn được giữ nguyên, vui lòng thử lại.", "gio-hang/", false);
		}
		$id_insert = (int)$persistedOrder['id'];
		$code = (string)$persistedOrder['code'];

		/* Defaults attributes email */
	    $emailDefaultAttrs = $emailer->defaultAttrs();

	    /* Variables email */
	    $emailVars = array(
	    	'{emailOrderCode}',
	    	'{emailOrderInfoFullname}',
	    	'{emailOrderInfoEmail}',
	    	'{emailOrderInfoPhone}',
	    	'{emailOrderInfoAddress}',
	    	'{emailOrderPayment}',
	    	'{emailOrderShipPrice}',
	    	'{emailOrderInfoRequirements}',
	    	'{emailOrderDetails}'
	    );
	    $emailVars = $emailer->addAttrs($emailVars, $emailDefaultAttrs['vars']);

	    /* Values email */
	    $emailVals = array(
	    	$code,
	    	$fullname,
	    	$email,
	    	$phone,
	    	$address,
	    	$order_payment_text,
	    	$ship_price,
	    	$requirements,
	    	$order_detail
	    );
	    $emailVals = $emailer->addAttrs($emailVals, $emailDefaultAttrs['vals']);

		/* Send email admin + customer (không để lỗi SMTP làm vỡ luồng đặt hàng) */
		if(empty($persistedOrder['reused']))
		{
			try
			{
				$arrayEmail = null;
				$subject = "Thông tin đơn hàng từ ".$setting['name'.$lang];
				$message = str_replace($emailVars, $emailVals, $emailer->markdown('order/admin', ['shipPrice' => $ship_price]));
				$file = '';
				$emailer->send("admin", $arrayEmail, $subject, $message, $file);

				$arrayEmail = array(
					"dataEmail" => array(
						"name" => $fullname,
						"email" => $email
					)
				);
				$subject = "Thông tin đơn hàng từ ".$setting['name'.$lang];
				$message = str_replace($emailVars, $emailVals, $emailer->markdown('order/customer', ['shipPrice' => $ship_price]));
				$file = '';
				$emailer->send("customer", $arrayEmail, $subject, $message, $file);
			}
			catch(Exception $e) {}
		}

		/* Token trang cảm ơn: ràng buộc session và chỉ phát conversion một lần */
		$thanksToken = checkoutRegisterThanks($id_insert, $code);
		checkoutRotateIdempotencyToken();

	    /* Xóa giỏ hàng */
        unset($_SESSION['cart']);
		$func->redirect("cam-on/?token=".rawurlencode($thanksToken));
	}
?>
