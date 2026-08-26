<?php if(count($news)){ ?>
<div class="row row-service">
    <?php foreach($news as $v) { ?>
    <div class="col-md-6">
        <div class="dl-item">
            <a class="text-decoration-none" href="javascript:" title="<?=$v['name'.$lang]?>">
                <div class="dl-info">
                    <div class="name-dl"><?=htmlspecialchars_decode($v['name'.$lang])?></div>
                    <div class="info-dl">
                        <div class="item-info-dl">
                            <i class="fa fa-map-marker-alt" aria-hidden="true"></i>
                            <span><b>Địa chỉ: </b><?=$v['address']?></span>
                        </div>
                        <div class="item-info-dl">
                            <i class="fa fa-envelope" aria-hidden="true"></i>
                            <span><b>Email: </b><?=$v['email']?></span>
                        </div>
                        <div class="item-info-dl">
                            <i class="fas fa-phone-volume"></i>
                            <span><b>Hotline: </b><?=$v['phone']?></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <?php } ?>
</div>
<?php } else { ?>
<div class="">
    <div class="alert alert-warning w-100" role="alert">
        <strong><?=khongtimthayketqua?></strong>
    </div>
</div>
<?php } ?>
<div class="">
    <div class="pagination-home w-100"><?=(!empty($paging)) ? $paging : ''?></div>
</div>