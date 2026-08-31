<?php 	
	include "config.php";
	$sortby = (isset($_GET['sortby'])) ? addslashes($_GET['sortby']) : "";
	$params = array();
	if(!empty($sortby)){

		$ex_sort_by = str_replace('-', ' ', $sortby);

		$order_by = ' order by '.$ex_sort_by;

		//$url_page .= '?sortby='.$sortby;

	}else{

		$order_by = ' order by numb asc, id desc';

	}
	 $sql = "select name$lang, slugvi, slugen, id, photo, regular_price, sale_price, discount, type from #_product where type='san-pham' and find_in_set('hienthi',status)";
	$sqlCache = $sql." ".$order_by;
    $items = $cache->get($sqlCache, $params, 'result', 7200);

	//$url = base64_decode($_GET['href']).$url_page;

	//$paging = $func->pagination($total,$per_page,$page,$url);

	//$html['resx'] = $_GET;

	$html['res'] = ' ';
				 foreach($items as $k => $v) { 
				 	$imgg = $func->getImage(['sizes' => '270x270x1', 'isWatermark' => true, 'prefix' => 'product', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]]);
                    $html['res'] .='    <div class="product">
                            <a class="box-product text-decoration-none" href="'.$v[$sluglang].'" title="'.$v['name'.$lang].'">
                                <p class="pic-product scale-img">
                                    '.$imgg.'
                                </p>
                                <h3 class="name-product text-split">'.$v['name'.$lang].'</h3>
                                <p class="price-product">';
                                     if(!publicProductPricesVisible()) {
                                       $html['res'] .=' <span class="price-new">'.lienhe.'</span>';
                                     } elseif($v['discount']) {
                                     $html['res'] .='   <span class="price-new">'.$func->formatMoney($v['sale_price']).'</span>
                                        <span class="price-old">'.$func->formatMoney($v['regular_price']).'</span>
                                        <span class="price-per">'.'-'.$v['discount'].'%'.'</span>';
                                     } else { 
                                       $html['res'] .=' <span class="price-new">'.($v['regular_price']) ? $func->formatMoney($v['regular_price']) : lienhe.'</span>';
                                    } 
                               $html['res'] .=' </p>
                            </a>
                            <p class="cart-product w-clear">
                                '.(publicProductPricesVisible() ? '<span class="cart-add addcart transition" data-id="'.$v['id'].'" data-action="addnow">Thêm vào giỏ hàng</span><span class="cart-buy addcart transition" data-id="'.$v['id'].'" data-action="buynow">Mua ngay</span>' : '<a href="lien-he/">Liên hệ báo giá</a>').'
                            </p>
                        </div> ';

                }  
	

	//$html['page'] = $paging;

	echo json_encode($html);
 ?>
