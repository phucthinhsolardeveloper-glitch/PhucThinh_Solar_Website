<?php if(count($splist)){foreach($splist as $v){ ?>
    <?php if(isset($productListHasItems[(int)$v['id']])){ ?>
    <section class="pts-catblock">
        <div class="pts-catblock-head">
            <div class="section-title">
                <h3><?=$v['name'.$lang]?></h3>
            </div>
            <a href="danh-muc-san-pham/<?=$v[$sluglang]?>/" class="pts-catblock-all" title="Xem thêm sản phẩm">
                Xem tất cả <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="paging-prolist paging-prolist-<?=$v['id']?>" data-list="<?=$v['id']?>"></div>
    </section>
    <?php } ?>
<?php }} ?>
