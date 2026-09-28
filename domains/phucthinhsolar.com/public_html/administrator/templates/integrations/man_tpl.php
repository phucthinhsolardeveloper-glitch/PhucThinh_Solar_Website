<section class="content-header text-sm">
    <div class="container-fluid">
        <div class="row">
            <ol class="breadcrumb float-sm-left">
                <li class="breadcrumb-item"><a href="index.php">Bảng điều khiển</a></li>
                <li class="breadcrumb-item active">Thanh toán &amp; vận chuyển</li>
            </ol>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <div class="alert alert-info text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            Credential được giữ ngoài Git và ngoài database. Khi có credential, bật provider bằng environment rồi kiểm tra lại staging.
        </div>
        <div class="card card-primary card-outline text-sm">
            <div class="card-header"><h3 class="card-title">Thanh toán</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Provider</th><th>Trạng thái</th><th>Điều kiện</th><th>Callback</th></tr></thead>
                        <tbody>
                        <?php foreach($integrationPayments as $provider) { ?>
                            <tr>
                                <td><strong><?=htmlspecialchars($provider['label'], ENT_QUOTES, 'UTF-8')?></strong></td>
                                <td><?php if($provider['configured']) { ?><span class="badge badge-success">Sẵn sàng cấu hình</span><?php } elseif($provider['active']) { ?><span class="badge badge-warning">Thiếu credential</span><?php } else { ?><span class="badge badge-secondary">Đang tắt</span><?php } ?></td>
                                <td><?=htmlspecialchars($provider['requirements'], ENT_QUOTES, 'UTF-8')?></td>
                                <td><code>api/payment_vnpay.php?mode=return</code><br><code>api/payment_vnpay.php?mode=ipn</code></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card card-primary card-outline text-sm">
            <div class="card-header"><h3 class="card-title">Nhà vận chuyển</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Provider</th><th>Trạng thái</th><th>Giai đoạn tiếp theo</th></tr></thead>
                        <tbody>
                        <?php foreach($integrationShipping as $provider) { ?>
                            <tr>
                                <td><strong><?=htmlspecialchars($provider['label'], ENT_QUOTES, 'UTF-8')?></strong></td>
                                <td><?php if($provider['configured']) { ?><span class="badge badge-success">Đủ credential</span><?php } elseif($provider['active']) { ?><span class="badge badge-warning">Thiếu credential</span><?php } else { ?><span class="badge badge-secondary">Đang tắt</span><?php } ?></td>
                                <td>
                                    Tính phí → tạo vận đơn → webhook trạng thái → đối soát COD
                                    <?php if(!empty($provider['missing'])) { ?><br><small class="text-muted">Thiếu: <?=htmlspecialchars(implode(', ', $provider['missing']), ENT_QUOTES, 'UTF-8')?></small><?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card card-primary card-outline text-sm">
            <div class="card-header"><h3 class="card-title">Dữ liệu tích hợp</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><strong>Giao dịch thanh toán:</strong> <?=is_null($integrationTables['payment_transaction']) ? 'Chưa chạy migration' : number_format($integrationTables['payment_transaction'])?></div>
                    <div class="col-md-6"><strong>Vận đơn:</strong> <?=is_null($integrationTables['shipping_shipment']) ? 'Chưa chạy migration' : number_format($integrationTables['shipping_shipment'])?></div>
                </div>
            </div>
        </div>
    </div>
</section>
