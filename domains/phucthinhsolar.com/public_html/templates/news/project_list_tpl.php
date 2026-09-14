<section class="CRlist_term_blog">
    <div class="container">
        <div class="CRlist_term_blog_box row inf_dots">
            <?php if(count($dalist)){foreach($dalist as $v) { ?>
            <div class="CRitem_term_blog">
                <div class="content ">
                    <a title="<?=$v['name'.$lang]?>" href="danh-muc-du-an/<?=$v[$sluglang]?>/"></a>
                    <div class="ico">
                        <?=$func->getImage(['class' => 'lazy w-100','size-error' => '60x60x1', 'upload' => UPLOAD_NEWS_L, 'image' => $v['photo'], 'alt' => $v['name'.$lang]])?>
                    </div>
                    <h2><?=$v['name'.$lang]?></h2>
                </div>
            </div>
            <?php }} ?>
        </div>
    </div>
</section>
<?php if(count($dalist)) { ?>
<section class="CRlist_item_blog">
    <div class="container">
        <?php foreach($dalist as $v){$da_item = $cache->get("select name$lang, title_index$lang, slugvi, slugen, desc$lang, date_created, id, photo from #_news where id_list = ".$v['id']." and type = ? and find_in_set('hienthi',status) order by numb,id desc", array($type), 'result', 7200); ?>
        <div class="CRrow_term_blog_box">
            <div class="CRrow_term_top row">
                <div class="col-sm">
                    <div class="title_term"><?=$v['name'.$lang]?></div>
                </div>
            </div>
            <div class="CRrow_term_bottom row deactivate pts-card-grid">
                <?php if(count($da_item)){foreach($da_item as $c) { ?>
                <div class="inf_item_project col-lg-4 col-md-4 col-sm-6 col-12">
                    <div class="wrap">
                        <a title="<?=$c['name'.$lang]?>" href="<?=$c[$sluglang]?>/"></a>
                        <div class="thumbnail">
                            <?=$func->getImage(['class' => 'lazy w-100','size-error' => '300x200x1', 'upload' => UPLOAD_NEWS_L, 'image' => $c['photo'], 'alt' => $c['name'.$lang]])?>
                        </div>
                        <div class="content">
                            <div class="box_title">
                                <h4 class="title"><?=$c['name'.$lang]?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <?php }} ?>
            </div>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>
