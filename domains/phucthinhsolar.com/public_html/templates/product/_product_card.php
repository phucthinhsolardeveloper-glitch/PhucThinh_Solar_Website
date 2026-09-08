<?php
/**
 * Partial hiển thị card sản phẩm dùng chung.
 * $pcNoLazy tắt lazy-load cho card nằm trong carousel.
 */
require_once LIBRARIES.'product_promo.php';

$pcDiscount = publicProductPricesVisible() && (!empty($v['sale_price']) && !empty($v['regular_price']) && $v['regular_price'] > $v['sale_price'])
    ? round((($v['regular_price'] - $v['sale_price']) / $v['regular_price']) * 100)
    : 0;

/* Ảnh riêng của sản phẩm thắng; nếu không có thì resolver xét chiến dịch/phạm vi. */
$pcPromoFrame = productPromoFrameForCard($v, $cache);
?>
<div class="product-card">
    <a class="product-item" href="san-pham/<?=$v[$sluglang]?>" title="<?=$v['name'.$lang]?>">
        <div class="product-card-img">
            <?php if($pcDiscount > 0) { ?>
            <span class="badge-discount">-<?=$pcDiscount?>%</span>
            <?php } ?>
            <?php if(!empty($pcNoLazy)) { ?>
            <?=$func->getImage(['isLazy' => false, 'class' => 'w-100 pts-product-photo', 'sizes' => '300x300x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
            <?php } else { ?>
            <?=$func->getImage(['class' => 'lazy w-100 pts-product-photo', 'sizes' => '300x300x2', 'upload' => UPLOAD_PRODUCT_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
            <?php } ?>
            <?php if(!empty($pcPromoFrame)) { ?>
            <?=$func->getImage(['isLazy' => false, 'class' => 'w-100 pts-promo-frame', 'sizes' => '300x300x2', 'upload' => $pcPromoFrame['upload'], 'image' => $pcPromoFrame['photo'], 'alt' => ''])?>
            <?php } ?>
        </div>
        <div class="caption">
            <p class="name"><?=$func->noBreakModelCode($v['name'.$lang])?></p>
            <?php
                /* Chỉ hiện thông số THẬT đang có, không bịa cho sản phẩm thiếu dữ liệu
                   (audit UX 2026-09-08: card cũ chỉ có ảnh+tên+giá, không đủ để so sánh nhanh). */
                $pcSpecs = array();
                if((float)($v['cong_suat'] ?? 0) > 0) $pcSpecs[] = round((float)$v['cong_suat']).'W';
                if(!empty($v['waterproof_rating'])) $pcSpecs[] = $v['waterproof_rating'];
                if(!empty($v['lighting_time'])) $pcSpecs[] = $v['lighting_time'];
            ?>
            <?php if($pcSpecs) { ?>
            <p class="pts-card-specs"><?=implode(' · ', array_map(function($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }, $pcSpecs))?></p>
            <?php } ?>
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
