<?php if(count($splist)){foreach($splist as $v){$proByList = $d->rawQuery("select id from #_product where id_list = ".$v['id']." and find_in_set('hienthi',status) order by numb,id desc limit 0,4"); ?>
    <?php if(count($proByList)){ ?>
    <div class="box-list">
        <div class="title-list">
            <h2><?=$v['name'.$lang]?></h2>
            <a href="danh-muc-san-pham/<?=$v[$sluglang]?>/" class="btn-list" title="Xem thêm sản phẩm">
                Xem thêm
                <svg width="18px" height="18px" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="48" height="48" fill="white" fill-opacity="0.01"/>
                    <path d="M19 12L31 24L19 36" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>
        <div class="paging-prolist paging-prolist-<?=$v['id']?>" data-list="<?=$v['id']?>"></div>
    </div>
    <?php } ?>
<?php }} ?>