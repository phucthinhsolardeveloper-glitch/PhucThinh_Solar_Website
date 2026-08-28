<?php
/**
 * Card sản phẩm - PARTIAL DÙNG CHUNG (canonical)
 *
 * Đây là nguồn DUY NHẤT cho markup card sản phẩm. Trước đây markup này bị
 * copy-paste ở 5 nơi (product_tpl, product_detail_tpl, api/product2,
 * api/product3, api/fillter_service) và đã trôi dạt: api/product3 (trang
 * /san-pham/) còn kẹt ở markup đời cũ nên card trang đó không khớp trang
 * danh mục. Mọi thay đổi card từ nay chỉ sửa ở file này.
 *
 * Biến cần có trước khi include:
 *   $v         array  - 1 dòng sản phẩm (cần: id, photo, name{lang}, slug{lang},
 *                       regular_price, sale_price)
 *   $sluglang  string - 'slugvi' | 'slugen'
 *   $lang      string - hậu tố ngôn ngữ ('' | 'en')
 *   $func      object - Functions (getImage, formatMoney)
 *
 * Include bằng đường dẫn tuyệt đối để chạy được cả từ /templates lẫn /api:
 *   include ROOT."templates/product/_product_card.php";
 */

$pcDiscount = (!empty($v['sale_price']) && !empty($v['regular_price']) && $v['regular_price'] > $v['sale_price'])
    ? round((($v['regular_price'] - $v['sale_price']) / $v['regular_price']) * 100)
    : 0;
?>
<div class="product-card">
    <a class="product-item" href="product/<?=$v[$sluglang]?>/" title="<?=$v['name'.$lang]?>">
        <div class="product-card-img">
            <?php if($pcDiscount > 0) { ?>
            <span class="badge-discount">-<?=$pcDiscount?>%</span>
            <?php } ?>
            <?=$func->getImage(['class' => 'lazy w-100', 'sizes' => '300x300x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
        </div>
        <div class="caption">
            <p class="name"><?=$v['name'.$lang]?></p>
        </div>
        <p class="price-product">
            <?php if(!empty($v['sale_price'])) { ?>
                <span class="price-new"><?=$func->formatMoney($v['sale_price'])?></span>
                <span class="price-old"><?=$func->formatMoney($v['regular_price'])?></span>
            <?php } else { ?>
                <span class="price-new"><?=(!empty($v['regular_price'])) ? $func->formatMoney($v['regular_price']) : lienhe?></span>
            <?php } ?>
        </p>
    </a>
    <div class="product-card-btns">
        <a href="javascript:void(0)" class="addcart pts-card-add" data-id="<?=$v['id']?>" data-action="addnow" title="Thêm vào giỏ hàng" aria-label="Thêm vào giỏ hàng"><i class="fas fa-plus"></i></a>
    </div>
</div>
