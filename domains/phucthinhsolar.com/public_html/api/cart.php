<?php
	include "config.php";

    $cmd = (!empty($_POST['cmd'])) ? htmlspecialchars($_POST['cmd']) : '';
	$id = (!empty($_POST['id'])) ? (int)$_POST['id'] : 0;
	$color = (!empty($_POST['color'])) ? (int)$_POST['color'] : 0;
	$size = (!empty($_POST['size'])) ? (int)$_POST['size'] : 0;
	$quantity = (!empty($_POST['quantity'])) ? (int)$_POST['quantity'] : 1;
	$quantity = max(1, min(999, $quantity));
	$code = (!empty($_POST['code'])) ? htmlspecialchars($_POST['code']) : '';
	$ward = (!empty($_POST['ward'])) ? htmlspecialchars($_POST['ward']) : 0;
	$getWardShip = function($wardId) use ($d) {
		$wardId = (int)$wardId;
		return $wardId > 0 ? $d->rawQueryOne("select ship_price from #_ward where id = ? and id_district = 0 and find_in_set('hienthi', status) limit 0,1", array($wardId)) : array();
	};
	if(!publicProductPricesVisible())
	{
		unset($_SESSION['cart']);
		http_response_code(409);
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(array('error' => 'Sản phẩm hiện nhận báo giá trực tiếp.', 'contactUrl' => 'lien-he/'));
		exit();
	}

	if($cmd == 'add-cart' && $id > 0)
	{
		$quoteProduct = $cart->getProductInfo($id);
		if(empty($quoteProduct) || publicProductRequiresQuote($quoteProduct))
		{
			http_response_code(409);
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('error' => 'Sản phẩm này nhận báo giá trực tiếp.', 'contactUrl' => 'lien-he/'));
			exit();
		}
		$cart->addToCart($quantity,$id,$color,$size);
		$max = (!empty($_SESSION['cart'])) ? count($_SESSION['cart']) : 0;
		$data = array('max' => $max);

		echo json_encode($data);
	}
	else if($cmd == 'update-cart' && $id > 0 && $code != '')
	{
		$cartProductId = 0;
		if(!empty($_SESSION['cart']))
		{
			$max = count($_SESSION['cart']);
			for($i=0;$i<$max;$i++)
			{
				if($code == $_SESSION['cart'][$i]['code'])
				{
					$_SESSION['cart'][$i]['qty'] = $quantity;
					$cartProductId = (int)$_SESSION['cart'][$i]['productid'];
					break;
				}
			}
		}
		if($cartProductId <= 0)
		{
			http_response_code(404);
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode(array('error' => 'Không tìm thấy sản phẩm trong giỏ hàng.'));
			exit();
		}

		if(!empty($config['order']['ship']))
		{
			$shipData = $getWardShip($ward);
			$ship = (!empty($shipData)) ? $shipData['ship_price'] : 0;
		}
		
		$proinfo = $cart->getProductInfo($cartProductId);
		$regular_price = $func->formatMoney($proinfo['regular_price']*$quantity);
		$sale_price = $func->formatMoney($proinfo['sale_price']*$quantity);
		$temp = $cart->getOrderTotal();
		$tempText = $func->formatMoney($temp);
		$total = $cart->getOrderTotal();
		if(!empty($ship)) $total += $ship;
		$totalText = $func->formatMoney($total);
		$data = array('regularPrice' => $regular_price, 'salePrice' => $sale_price, 'tempText' => $tempText, 'totalText' => $totalText);

		echo json_encode($data);
	}
	else if($cmd == 'delete-cart' && $code != '')
	{
		if(!empty($config['order']['ship']))
		{
			$shipData = $getWardShip($ward);
			$ship = (!empty($shipData)) ? $shipData['ship_price'] : 0;
		}

		$cart->removeProduct($code);
		$max = (!empty($_SESSION['cart'])) ? count($_SESSION['cart']) : 0;
		$temp = $cart->getOrderTotal();
		$tempText = $func->formatMoney($temp);
		$total = $cart->getOrderTotal();
		if(!empty($ship)) $total += $ship;
		$totalText = $func->formatMoney($total);
		$data = array('max' => $max, 'tempText' => $tempText, 'totalText' => $totalText);

		echo json_encode($data);
	}
	else if($cmd == 'ship-cart')
	{
		$shipData = array();
		$shipPrice = 0;
		$shipText = '0đ';
		$total = 0;
		$totalText = '';

		if($id) $shipData = $getWardShip($id);

		$total = $cart->getOrderTotal();
		if(!empty($shipData['ship_price']))
		{
			$total += $shipData['ship_price'];
			$shipText = $func->formatMoney($shipData['ship_price']);
		}
		$totalText = $func->formatMoney($total);
		$shipPrice = (!empty($shipData['ship_price'])) ? $shipData['ship_price'] : 0;
		$data = array('shipText' => $shipText, 'ship' => $shipPrice, 'totalText' => $totalText);

		echo json_encode($data);
	}
	else if($cmd == 'popup-cart')
	{ ?>
		<form class="form-cart" method="post" action="" enctype="multipart/form-data">
		    <div class="wrap-cart">
		        <div class="top-cart border-right-0">
		            <div class="list-procart">
							<div class="procart procart-label">
								<div class="form-row procart-row">
								<div class="pic-procart col-3 col-md-2"><?=hinhanh?></div>
								<div class="info-procart col-6 col-md-5"><?=tensanpham?></div>
								<div class="quantity-procart procart-actions col-3 col-md-2"><?=soluong?></div>
								<div class="price-procart procart-price-desktop col-3 col-md-3"><?=thanhtien?></div>
							</div>
						</div>
						<?php $max = count($_SESSION['cart']); for($i=0;$i<$max;$i++) {
							$pid = $_SESSION['cart'][$i]['productid'];
							$quantity = $_SESSION['cart'][$i]['qty'];
							$color = ($_SESSION['cart'][$i]['color'])?$_SESSION['cart'][$i]['color']:0;
							$size = ($_SESSION['cart'][$i]['size'])?$_SESSION['cart'][$i]['size']:0;
							$code = ($_SESSION['cart'][$i]['code'])?$_SESSION['cart'][$i]['code']:'';
							$proinfo = $cart->getProductInfo($pid);
							$pro_price = $proinfo['regular_price'];
							$pro_price_new = $proinfo['sale_price'];
							$pro_price_qty = $pro_price*$quantity;
							$pro_price_new_qty = $pro_price_new*$quantity; ?>
							<div class="procart procart-<?=$code?>">
								<div class="form-row procart-row">
									<div class="pic-procart col-3 col-md-2">
										<a class="text-decoration-none" href="san-pham/<?=$proinfo[$sluglang]?>" target="_blank" title="<?=$proinfo['name'.$lang]?>"><?=$func->getImage(['sizes' => '85x85x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $proinfo['photo'], 'alt' => $proinfo['name'.$lang]])?></a>
									</div>
									<div class="info-procart col-6 col-md-5">
										<h3 class="name-procart"><a class="text-decoration-none" href="san-pham/<?=$proinfo[$sluglang]?>" target="_blank" title="<?=$proinfo['name'.$lang]?>"><?=$proinfo['name'.$lang]?></a></h3>
										<div class="properties-procart">
											<?php if($color) { $color_detail = $d->rawQueryOne("select name$lang from #_color where type = ? and id = ? limit 0,1",array($proinfo['type'],$color)); ?>
												<p>Màu: <strong><?=$color_detail['name'.$lang]?></strong></p>
											<?php } ?>
											<?php if($size) { $size_detail = $d->rawQueryOne("select name$lang from #_size where type = ? and id = ? limit 0,1",array($proinfo['type'],$size)); ?>
												<p>Size: <strong><?=$size_detail['name'.$lang]?></strong></p>
											<?php } ?>
										</div>
									</div>
									<div class="quantity-procart procart-actions col-3 col-md-2">
										<div class="price-procart price-procart-rp">
											<?php if($proinfo['sale_price']) { ?>
												<p class="price-new-cart load-price-new-<?=$code?>">
													<?=$func->formatMoney($pro_price_new_qty)?>
												</p>
												<p class="price-old-cart load-price-<?=$code?>">
													<?=$func->formatMoney($pro_price_qty)?>
												</p>
											<?php } else { ?>
												<p class="price-new-cart load-price-<?=$code?>">
													<?=$func->formatMoney($pro_price_qty)?>
												</p>
											<?php } ?>
										</div>
						                <div class="quantity-counter-procart quantity-counter-procart-<?=$code?>">
						                        <button type="button" class="counter-procart-minus counter-procart" aria-label="Giảm số lượng">−</button>
						                        <input type="number" class="quantity-procart" min="1" max="999" step="1" inputmode="numeric" value="<?=$quantity?>" data-pid="<?=$pid?>" data-code="<?=$code?>" aria-label="Số lượng sản phẩm"/>
						                        <button type="button" class="counter-procart-plus counter-procart" aria-label="Tăng số lượng">+</button>
						                    </div>
						                    <button type="button" class="del-procart text-decoration-none" data-code="<?=$code?>"><i class="fa fa-times-circle"></i><span><?=xoa?></span></button>
									</div>
									<div class="price-procart procart-price-desktop col-3 col-md-3">
										<?php if($proinfo['sale_price']) { ?>
											<p class="price-new-cart load-price-new-<?=$code?>">
												<?=$func->formatMoney($pro_price_new_qty)?>
											</p>
											<p class="price-old-cart load-price-<?=$code?>">
												<?=$func->formatMoney($pro_price_qty)?>
											</p>
										<?php } else { ?>
											<p class="price-new-cart load-price-<?=$code?>">
												<?=$func->formatMoney($pro_price_qty)?>
											</p>
										<?php } ?>
									</div>
								</div>
							</div>
				        <?php } ?>
					</div>
		            <div class="money-procart">
		                <div class="total-procart d-flex align-items-center justify-content-between">
		                    <p><?=tamtinh?>:</p>
		                    <p class="total-price load-price-temp"><?=$func->formatMoney($cart->getOrderTotal())?></p>
		                </div>
		            </div>
		            <div class="modal-footer d-flex align-items-center justify-content-between">
		                <a href="san-pham" class="buymore-cart text-decoration-none" title="<?=tieptucmuahang?>">
		                    <i class="fa fa-angle-double-left"></i>
		                    <span><?=tieptucmuahang?></span>
		                </a>
		                <a class="btn-cart btn btn-primary" href="gio-hang" title="<?=thanhtoan?>"><?=thanhtoan?></a>
		            </div>
		        </div>
		    </div>
		</form>
	<?php }
?>
