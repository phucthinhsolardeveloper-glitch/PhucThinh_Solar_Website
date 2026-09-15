<?php
if(!class_exists('PaginationsAjax')) require_once LIBRARIES.'class/class.PaginationsAjax.php';
$productListPager = new PaginationsAjax();
$productListPager->perpage = 4;
$productListSavedPage = isset($_GET['p']) ? $_GET['p'] : null;
$_GET['p'] = 1;
?>
<?php if(count($splist)){ ?>
<!-- Nhảy nhanh tới danh mục - trang này liệt kê tuần tự tất cả danh mục nên khá
     dài, cần lối tắt cho người đã biết mình cần gì (audit UX 2026-09-08). Không
     thêm ô tìm kiếm ở đây vì header đã có sẵn, tránh trùng lặp. -->
<div class="pts-catalog-toolbar">
    <div class="pts-catalog-jump">
        <?php foreach($splist as $jumpCatRow) { if(!isset($productListHasItems[(int)$jumpCatRow['id']])) continue; ?>
        <a href="#pts-cat-<?=$jumpCatRow['id']?>"><?=$jumpCatRow['name'.$lang]?></a>
        <?php } ?>
    </div>
</div>
<?php } ?>
<?php
    $catalogVisibleCategoryCount = count($productListHasItems);
    $catalogCampaignCount = !empty($catalogCampaigns) ? count($catalogCampaigns) : 0;
    $catalogCampaignPositions = array();
    for($catalogPositionIndex = 0; $catalogPositionIndex < $catalogCampaignCount; $catalogPositionIndex++) {
        $catalogCampaignPositions[] = max(1, (int)round((($catalogPositionIndex + 1) * $catalogVisibleCategoryCount) / ($catalogCampaignCount + 1)));
    }
    $catalogRenderedCategoryCount = 0;
    $catalogCampaignPointer = 0;
?>
<?php if(count($splist)){foreach($splist as $productListRow){ ?>
    <?php if(isset($productListHasItems[(int)$productListRow['id']])){ ?>
    <section class="pts-catblock" id="pts-cat-<?=$productListRow['id']?>">
        <div class="pts-catblock-head">
            <div class="section-title">
                <h3><?=$productListRow['name'.$lang]?></h3>
            </div>
            <a href="danh-muc-san-pham/<?=$productListRow[$sluglang]?>/" class="pts-catblock-all" title="Xem thêm sản phẩm">
                Xem tất cả <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="paging-prolist paging-prolist-<?=$productListRow['id']?>" data-list="<?=$productListRow['id']?>" data-server-rendered="1">
            <div class="grid-product grid-product-3">
                <?php foreach($productListPreview[(int)$productListRow['id']] as $v) { include ROOT."templates/product/_product_card.php"; } ?>
            </div>
            <?php
                $previewCount = isset($productListPreviewCount[(int)$productListRow['id']]) ? $productListPreviewCount[(int)$productListRow['id']] : 0;
                $previewHref = "api/product3.php?perpage=4&idList=".(int)$productListRow['id']."&p=";
                $previewTarget = '.paging-prolist-'.(int)$productListRow['id'];
                $previewPaging = $productListPager->getAllPageLinks($previewCount, $previewHref, $previewTarget);
            ?>
            <?php if($previewPaging !== '') { ?><div class="pagination-ajax"><?=$previewPaging?></div><?php } ?>
        </div>
    </section>
    <?php
        $catalogRenderedCategoryCount++;
        while($catalogCampaignPointer < $catalogCampaignCount && $catalogRenderedCategoryCount >= $catalogCampaignPositions[$catalogCampaignPointer]) {
            $catalogCampaign = $catalogCampaigns[$catalogCampaignPointer];
            $catalogCampaignLink = !empty($catalogCampaign['link']) ? $catalogCampaign['link'] : 'lien-he/';
            $catalogCampaignModal = in_array($catalogCampaignLink, array('#ptsContactModal', '#ptsDealerModal'), true) ? ltrim($catalogCampaignLink, '#') : '';
    ?>
    <section class="pts-catalog-campaign" aria-labelledby="pts-catalog-campaign-<?=$catalogCampaignPointer?>">
        <a href="<?=htmlspecialchars($catalogCampaignLink, ENT_QUOTES, 'UTF-8')?>"<?=$catalogCampaignModal !== '' ? ' data-toggle="modal" data-target="#'.$catalogCampaignModal.'"' : ''?>>
            <span class="pts-catalog-campaign-media">
                <?=$func->getImage(['isLazy' => false, 'class' => '', 'sizes' => '1600x500x1', 'upload' => $catalogCampaign['upload'], 'image' => $catalogCampaign['photo'], 'alt' => $catalogCampaign['name']])?>
            </span>
            <span class="pts-catalog-campaign-shade" aria-hidden="true"></span>
            <span class="pts-catalog-campaign-copy">
                <h3 id="pts-catalog-campaign-<?=$catalogCampaignPointer?>"><?=htmlspecialchars($catalogCampaign['name'], ENT_QUOTES, 'UTF-8')?></h3>
                <?php if($catalogCampaign['desc'] !== '') { ?><p><?=htmlspecialchars($catalogCampaign['desc'], ENT_QUOTES, 'UTF-8')?></p><?php } ?>
                <span>Tìm hiểu giải pháp <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            </span>
        </a>
    </section>
    <?php
            $catalogCampaignPointer++;
        }
    ?>
    <?php } ?>
<?php }} ?>
<?php if($productListSavedPage === null) unset($_GET['p']); else $_GET['p'] = $productListSavedPage; ?>
