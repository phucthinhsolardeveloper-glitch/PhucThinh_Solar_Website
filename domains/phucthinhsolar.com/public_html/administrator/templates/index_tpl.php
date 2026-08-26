<?php

    if((isset($_GET['month']) && $_GET['month'] != '') && (isset($_GET['year']) && $_GET['year'] != ''))
    {
        $time = $_GET['year'].'-'.$_GET['month'].'-1';
        $date = strtotime($time);
    }
    else
    {
        $date = strtotime(date('y-m-d')); 
    }

    $day = date('d', $date);
    $month = date('m', $date);
    $year = date('Y', $date);
    $firstDay = mktime(0,0,0,$month, 1, $year);
    $title = strftime('%B', $firstDay);
    $dayOfWeek = date('D', $firstDay);
    $daysInMonth = cal_days_in_month(0, $month, $year);
    $timestamp = strtotime('next Sunday');
    $weekDays = array();

    /* Make data for js chart */
    $charts = array();
    $charts['month'] = $month;


    $blank = date('w', strtotime("{$year}-{$month}-01"));
    $gth = array();
    $doanhthu = array();


    for($i=1; $i<=$daysInMonth; $i++)
    {
        $k = $i+1;
        $begin = strtotime($year.'-'.$month.'-'.$i);
        $end = strtotime($year.'-'.$month.'-'.$k);
        $todayrc = $d->rawQueryOne("select count(*) as todayrecord from #_counter where tm >= ? and tm < ?",array($begin,$end));
        $today_visitors = $todayrc['todayrecord'];
        $charts['series'][] = $today_visitors;
        $charts['labels'][] = 'D'.$i;




        // $query =   "SELECT COUNT(*) AS todayrecord_dh FROM #_counter WHERE tm>='$begin' and tm<'$end'"; 

        // $todayrc_dh  = $d->rawQueryOne($query); 

        // $today_visitors_dh     =    $todayrc_dh['todayrecord_dh']; 

        array_push($gth,$today_visitors);

        $start_day = date('Y-m-d',$begin).' 00:00:00';

        $end_day = date('Y-m-d',$end).' 00:00:00';

        $query_t =    "SELECT sum(total_price) AS total FROM #_order WHERE date_created >= UNIX_TIMESTAMP('".$start_day."') and date_created <= UNIX_TIMESTAMP('".$end_day."')";

        $today_r  = $d->rawQueryOne($query_t);

        $toc = (!$today_r['total']) ? '0':$today_r['total'];

        array_push($doanhthu,$toc);
    }

    // phần cart
    
    $sql_order = "SELECT * from #_order where order_status=? order by id desc limit 0,10";

    $items = $d->rawQuery($sql_order,array(1));

    $sql_setting = "SELECT * from #_setting limit 0,1";

    $items_setting = $d->rawQueryOne($sql_setting);

    $sql_order = "SELECT sum(quantity) as total, name from #_order_detail where id_order in (select id from #_order where order_status=3) group by id_product order by id desc limit 0,10";

    $items_product = $d->rawQuery($sql_order);




    $sql_order = "select sum(total_price) as total from #_order where order_status=4";

    $total_price = $d->rawQueryOne($sql_order);



    $sql_order = "select count(id) as total from #_order where order_status=4 and payment_status=1";

    $order_success = $d->rawQueryOne($sql_order);



    $sql_order = "select count(id) as total from #_order where order_status=5";

    $order_return = $d->rawQueryOne($sql_order);



    $sql_order = "select count(id) as total from #_order where order_status=5";

    $order_cancel = $d->rawQueryOne($sql_order);
    // end cart
   // die('ssssss');
?>
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