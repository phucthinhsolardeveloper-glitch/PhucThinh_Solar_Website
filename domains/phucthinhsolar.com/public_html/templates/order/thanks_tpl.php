<div class="box-detail thankyou-page" style="text-align:center;padding:60px 15px;">
    <i class="fa fa-check-circle" style="font-size:64px;color:#5cb85c;"></i>
    <h1 style="margin-top:20px;">Đặt hàng thành công!</h1>
    <p style="font-size:16px;">Cảm ơn <?=(!empty($orderThanks['fullname'])) ? htmlspecialchars($orderThanks['fullname']) : 'bạn'?> đã đặt hàng. Chúng tôi sẽ liên hệ xác nhận đơn hàng trong thời gian sớm nhất.</p>
    <?php if(!empty($orderThanks)) { ?>
    <p style="font-size:16px;">Mã đơn hàng: <strong><?=htmlspecialchars($orderThanks['code'])?></strong></p>
    <?php } ?>
    <a href="/san-pham/" class="btn btn-primary" style="margin-top:20px;">Tiếp tục mua sắm</a>
</div>

<?php if(!empty($orderThanks) && !empty($fireOrderConversion)) { ?>
<script>
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({
        'event': 'order_success',
        'order_code': <?=json_encode($orderThanks['code'])?>,
        'order_value': <?=json_encode((float)$orderThanks['total_price'])?>,
        'currency': 'VND'
    });
</script>
<?php } ?>
