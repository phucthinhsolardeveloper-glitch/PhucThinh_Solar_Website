<?php
/**
 * Partial hiển thị card sản phẩm dùng chung.
 * $pcNoLazy tắt lazy-load cho card nằm trong carousel.
 */

$pcDiscount = publicProductPricesVisible() && (!empty($v['sale_price']) && !empty($v['regular_price']) && $v['regular_price'] > $v['sale_price'])
    ? round((($v['regular_price'] - $v['sale_price']) / $v['regular_price']) * 100)
    : 0;
?>
<div class="product-card">
    <a class="product-item" href="san-pham/<?=$v[$sluglang]?>" title="<?=$v['name'.$lang]?>">
        <div class="product-card-img">
            <?php if($pcDiscount > 0) { ?>
            <span class="badge-discount">-<?=$pcDiscount?>%</span>
            <?php } ?>
            <?php if(!empty($pcNoLazy)) { ?>
            <?=$func->getImage(['isLazy' => false, 'class' => 'w-100', 'sizes' => '300x300x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
            <?php } else { ?>
            <?=$func->getImage(['class' => 'lazy w-100', 'sizes' => '300x300x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
            <?php } ?>
        </div>
        <div class="caption">
            <p class="name"><?=$v['name'.$lang]?></p>
        </div>
        <p class="price-product">
            <?php if(publicProductRequiresQuote($v)) { ?>
                <span class="price-new"><?=lienhe?></span>
            <?php } elseif(!empty($v['sale_price'])) { ?>
                <span class="price-new"><?=$func->formatMoney($v['sale_price'])?></span>
                <span class="price-old"><?=$func->formatMoney($v['regular_price'])?></span>
            <?php } else { ?>
                <span class="price-new"><?=(!empty($v['regular_price'])) ? $func->formatMoney($v['regular_price']) : lienhe?></span>
            <?php } ?>
        </p>
    </a>
    <?php /* Hiển thị CTA báo giá khi giá công khai bị ẩn. */ ?>
    <div class="product-card-btns">
        <?php if(publicProductRequiresQuote($v)) { ?>
        <a href="san-pham/<?=$v[$sluglang]?>" class="pts-card-ask" title="Xem sản phẩm và liên hệ báo giá" aria-label="Xem sản phẩm và liên hệ báo giá"><i class="fas fa-phone-alt"></i></a>
        <?php } else { ?>
        <a href="javascript:void(0)" class="addcart pts-card-add" data-id="<?=$v['id']?>" data-action="addnow" title="Thêm vào giỏ hàng" aria-label="Thêm vào giỏ hàng"><i class="fas fa-plus"></i></a>
        <?php } ?>
    </div>
</div>
