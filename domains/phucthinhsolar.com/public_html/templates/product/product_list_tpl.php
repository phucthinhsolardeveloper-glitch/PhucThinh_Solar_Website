<?php if(count($splist)){foreach($splist as $v){$proByList = $d->rawQuery("select id from #_product where id_list = ".$v['id']." and find_in_set('hienthi',status) order by numb,id desc limit 0,4"); ?>
    <?php if(count($proByList)){ ?>
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
