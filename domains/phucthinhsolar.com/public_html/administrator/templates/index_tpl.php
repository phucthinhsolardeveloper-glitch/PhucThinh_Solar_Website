<?php /* Dữ liệu bảng điều khiển được nạp ở administrator/sources/index.php. */ ?>
<section class="content mb-3">
    <div class="container-fluid">
        <h5 class="pt-3 pb-2">Dashboard</h5>
        <div class="row mb-2 text-sm">
            <div class="col-12 col-sm-6 col-md-3">
                <a class="my-info-box info-box" href="index.php?com=setting&act=update" title="Cấu hình website">
                    <span class="my-info-box-icon info-box-icon bg-primary"><i class="fas fa-cogs"></i></span>
                    <div class="info-box-content text-dark">
                        <span class="info-box-text text-capitalize">Cấu hình website</span>
                        <span class="info-box-number">View more</span>
                    </div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <a class="my-info-box info-box" href="index.php?com=user&act=info_admin" title="Tài khoản">
                    <span class="my-info-box-icon info-box-icon bg-primary"><i class="fas fa-user-cog"></i></span>
                    <div class="info-box-content text-dark">
                        <span class="info-box-text text-capitalize">Tài khoản</span>
                        <span class="info-box-number">View more</span>
                    </div>
                </a>
            </div>
            <div class="clearfix hidden-md-up"></div>
            <div class="col-12 col-sm-6 col-md-3">
                <a class="my-info-box info-box" href="index.php?com=user&act=info_admin&changepass=1" title="Đổi mật khẩu">
                    <span class="my-info-box-icon info-box-icon bg-primary"><i class="fas fa-key"></i></span>
                    <div class="info-box-content text-dark">
                        <span class="info-box-text text-capitalize">Đổi mật khẩu</span>
                        <span class="info-box-number">View more</span>
                    </div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <a class="my-info-box info-box" href="index.php?com=contact&act=man" title="Thư liên hệ">
                    <span class="my-info-box-icon info-box-icon bg-primary"><i class="fas fa-address-book"></i></span>
                    <div class="info-box-content text-dark">
                        <span class="info-box-text text-capitalize">Thư liên hệ</span>
                        <span class="info-box-number">View more</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>
<!-- Main content -->

<section class="content mb-3">
    <div class="container-fluid">
        <h5 class="pb-2">Việc cần xử lý</h5>
        <div class="row text-sm">
            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <a class="info-box" href="index.php?com=order&act=man&order_status=1" title="Xem đơn hàng mới">
                    <span class="info-box-icon bg-danger"><i class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content text-dark"><span class="info-box-text">Đơn hàng mới</span><span class="info-box-number"><?=$dashboardNewOrders?></span></div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <a class="info-box" href="index.php?com=newsletter&act=man&type=dangkynhantin&confirm_status=pending" title="Xem yêu cầu tư vấn chưa xử lý">
                    <span class="info-box-icon bg-warning"><i class="fas fa-headset"></i></span>
                    <div class="info-box-content text-dark"><span class="info-box-text">Yêu cầu tư vấn</span><span class="info-box-number"><?=$dashboardNewConsults?></span></div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <a class="info-box" href="index.php?com=newsletter&act=man&type=dangkynhantin2&confirm_status=pending" title="Xem đăng ký đại lý chưa xử lý">
                    <span class="info-box-icon bg-info"><i class="fas fa-store"></i></span>
                    <div class="info-box-content text-dark"><span class="info-box-text">Đăng ký đại lý</span><span class="info-box-number"><?=$dashboardNewDealers?></span></div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <a class="info-box" href="index.php?com=product&act=man&type=san-pham&comment_status=new" title="Xem đánh giá chờ duyệt">
                    <span class="info-box-icon bg-primary"><i class="fas fa-star"></i></span>
                    <div class="info-box-content text-dark"><span class="info-box-text">Đánh giá chờ duyệt</span><span class="info-box-number"><?=$dashboardPendingReviews?></span></div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <a class="info-box" href="index.php?com=product&act=man&type=san-pham&data_status=missing_wattage" title="Xem sản phẩm thiếu công suất">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-bolt"></i></span>
                    <div class="info-box-content text-dark"><span class="info-box-text">Thiếu công suất</span><span class="info-box-number"><?=$dashboardMissingWattage?></span></div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                <a class="info-box" href="index.php?com=news&act=man&type=chinh-sach" title="Kiểm tra nội dung chính sách">
                    <span class="info-box-icon bg-success"><i class="fas fa-file-contract"></i></span>
                    <div class="info-box-content text-dark">
                        <span class="info-box-text">Chính sách hoàn chỉnh</span>
                        <span class="info-box-number"><?=$dashboardPolicyCompleted?>/<?=$dashboardPolicyTotal?></span>
                    </div>
                </a>
            </div>
        </div>
        <div class="alert alert-light border mb-0 py-2">
            <i class="fas fa-info-circle text-primary mr-1"></i>
            Dashboard chỉ hiển thị số lượng công việc, không hiển thị thông tin cá nhân khách hàng.
        </div>
    </div>
</section>

<section class="content pb-4">
   <div class="container-fluid">
       <div class="card">
           <div class="card-header">
               <h5 class="mb-0">Thống kê truy cập tháng <?=$month?>/<?=$year?></h5>
           </div>
           <div class="card-body">
            <form class="form-filter-charts row align-items-center mb-1" action="index.php" method="get" name="form-thongke" accept-charset="utf-8">
                <div class="col-md-4">
                    <div class="form-group">
                        <select class="form-control select2" name="month" id="month">
                            <option value="">Chọn tháng</option>
                            <?php for($i=1; $i<=12 ;$i++) { ?>
                                <?php
                                    if(isset($_GET['year'])) $selected = ($i==$_GET['month']) ? 'selected':'';
                                    else $selected = ($i==date('m')) ? 'selected':'';
                                ?>
                                <option value="<?=$i?>" <?=$selected?>>Tháng <?=$i?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <select class="form-control select2" name="year" id="year">
                            <option value="">Chọn năm</option>
                            <?php for($i=2000;$i<=date('Y')+20;$i++) { ?>
                                <?php
                                    if(isset($_GET['year'])) $selected = ($i==$_GET['year']) ? 'selected':'';
                                    else $selected = ($i==date('Y')) ? 'selected':'';
                                ?>
                                <option value="<?=$i?>" <?=$selected?>>Năm <?=$i?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group"><button type="submit" class="btn btn-primary">Thống Kê</button></div>
                </div>
            </form>
               <div id="apexMixedChart"></div>
           </div>
       </div>
   </div>
</section>
<style type="text/css" media="screen">

    .count {

        margin-right: 15px;

        border-radius: 50%;

        width: 50px;

        height: 50px;

        display: flex;

        align-items: center;

        justify-content: center;

    }

    .count._1{

        background-color: #efeef8;

    }

    .count._2{

        background-color: #e7f5e6;

    }

    .count._3{

        background-color: #fff2e1;

    }

    .count._4{

        background-color: #ffe5e3;

    }

    .wrap_bg_block_333_content{

        display: flex;

        justify-content: space-between;

        align-items: center;

    }

    .wrap_bg_block_333_content .content{

        width: calc(100% - 65px);

    }

    .wrap_bg_block_333_content .content h2{

        font-weight: 700;

        font-size: 22px;

        margin-bottom: 3px;

    }

</style>
