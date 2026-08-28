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
 * Tuỳ chọn:
 *   $pcNoLazy  bool   - đặt true khi card nằm trong CAROUSEL (Owl/Slick).
 *     Thư viện lazy-load của theme chạy theo sự kiện cuộn trang, không nhận
 *     biết slide bị carousel dời/nhân bản ra ngoài khung nhìn -> ảnh ở các
 *     slide sau kẹt vĩnh viễn ở data-src + visibility:hidden (đo được chỉ
 *     4/16 ảnh tải ở carousel trang chủ, 0/15 ở sản phẩm liên quan).
 *     Lưới tĩnh thì cứ để lazy bình thường.
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
            <?php if(!empty($v['sale_price'])) { ?>
                <span class="price-new"><?=$func->formatMoney($v['sale_price'])?></span>
                <span class="price-old"><?=$func->formatMoney($v['regular_price'])?></span>
            <?php } else { ?>
                <span class="price-new"><?=(!empty($v['regular_price'])) ? $func->formatMoney($v['regular_price']) : lienhe?></span>
            <?php } ?>
        </p>
    </a>
    <?php /* Chưa niêm yết giá -> không cho thêm giỏ, chuyển sang nút gọi tư vấn */ ?>
    <div class="product-card-btns">
        <?php if(empty($v['sale_price']) && empty($v['regular_price'])) { ?>
        <a href="product/<?=$v[$sluglang]?>/" class="pts-card-ask" title="Liên hệ báo giá" aria-label="Liên hệ báo giá"><i class="fas fa-phone-alt"></i></a>
        <?php } else { ?>
        <a href="javascript:void(0)" class="addcart pts-card-add" data-id="<?=$v['id']?>" data-action="addnow" title="Thêm vào giỏ hàng" aria-label="Thêm vào giỏ hàng"><i class="fas fa-plus"></i></a>
        <?php } ?>
    </div>
</div>
