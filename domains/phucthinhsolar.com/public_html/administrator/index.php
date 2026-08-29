<?php
	require_once __DIR__.'/../libraries/session_security.php';
	startSecureSession();
	define('LIBRARIES','../libraries/');
	define('SOURCES','./sources/');
	define('TEMPLATE','./templates/');
	define('LAYOUT','layout/');
	define('THUMBS','thumbs');
	define('WATERMARK','../watermark');

	require_once LIBRARIES."config.php";
    require_once LIBRARIES.'autoload.php';
    new AutoLoad();
    $d = new PDODb($config['database']);
    $flash = new Flash();
    $seo = new Seo($d);
    $emailer = new Email($d);
	$cache = new Cache($d);
    $func = new Functions($d, $cache);

	/* Check HTTP */
	$func->checkHTTP($http, $config['arrayDomainSSL'], $configBase, $configUrl);

	/* Config type */
	require_once LIBRARIES."config-type.php";

	/* Lang Init */
	// require_once LIBRARIES."lang/langinit.php";

	/* Setting */
	$setting = $d->rawQueryOne("select * from #_setting limit 0,1");
	$optsetting = (isset($setting['options']) && $setting['options'] != '') ? json_decode($setting['options'],true) : null;

	/* Requick */
	require_once LIBRARIES."requick.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="assets/images/logo.png" rel="shortcut icon" type="image/x-icon" />
	<title>Administrator - <?=$setting['namevi']?></title>

	<!-- Css all -->
	<?php include TEMPLATE.LAYOUT."css.php"; ?>
</head>
<body class="sidebar-mini hold-transition text-sm <?=(!isset($_SESSION[$loginAdmin]['active']) || $_SESSION[$loginAdmin]['active']==false)?'login-page':''?>">
    <!-- Loader -->
	<?php if($template == 'index' || $template == 'user/login') include TEMPLATE.LAYOUT."loader.php"; ?>

    <!-- Wrapper -->
	<?php if(isset($_SESSION[$loginAdmin]['active']) && ($_SESSION[$loginAdmin]['active'] == true)) { ?>
		<div class="wrapper">
			<?php
				include TEMPLATE.LAYOUT."header.php";
				include TEMPLATE.LAYOUT."menu.php";
			?>
			<div class="content-wrapper">
				<?php if($alertlogin) { ?>
					<section class="content">
						<div class="container-fluid">
							<div class="alert my-alert alert-warning alert-dismissible text-sm bg-gradient-warning mt-3 mb-0">
								<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
								<i class="icon fas fa-exclamation-triangle"></i> <?=$alertlogin?>
							</div>
						</div>
					</section>
				<?php } ?>
				<?php include TEMPLATE.$template."_tpl.php"; ?>
			</div>
			<?php include TEMPLATE.LAYOUT."footer.php"; ?>
		</div>
	<?php } else { include TEMPLATE."user/login_tpl.php" ; } ?>

	<!-- Js all -->
	<?php include TEMPLATE.LAYOUT."js.php"; ?>
	<?php if ($template == 'sortable/man/man_add'){ ?>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function(){	
			$("ul.reorder-gallery").sortable({		
				update: function( event, ui ) {
					updateDropReorder();
				}
			});  
			$("ul.reorder-gallery-index").sortable({		
				update: function( event, ui ) {
					updateDropReorder_Index();
				}
			});  
            $("ul.reorder-gallery-detail").sortable({        
                update: function( event, ui ) {
                    updateDropReorder_Detail();
                }
            });  
		});
	</script>
	<?php } ?>
	<?php if ($template == 'index'){ ?>
	<script src="assets/js/apexcharts/dist/apexcharts.min.js"></script>
	<script type="text/javascript">

            var _templates = 'index';

            var _com = "";

            var class_ = 'theme-default';

            var color_ = 'ff6b35';

            var chart_order;

            var apexMixedChart;

            $(document).ready(function() {

                if(_templates=='index' || _com=='statitics'){

                    if(_com!='statitics'){

                        var options = {

                            colors: ['#ff6b35'],

                            chart: {

                                id: 'apexMixedChart',

                                height: 345,

                                type: 'line',

                                dropShadow: {

                                    enabled: true,

                                    color: '#000',

                                    top: 18,

                                    left: 7,

                                    blur: 10,

                                    opacity: 0.2

                                }

                            },

                            series: [{

                                    name: 'Thống kê truy cập tháng <?=$month?>',

                                    type: 'line',

                                    data: [<?php for($i = 0; $i < count($gth); $i++):?><?=$gth[$i]?><?php if($i<count($gth)-1){ ?>,<?php } ?><?php endfor; ?>]

                                }],

                            stroke: {

                              curve: 'smooth'

                            },

                            grid: {

                              borderColor: '#e7e7e7',

                              row: {

                                colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns

                                opacity: 0.5

                              },

                            },

                            markers: {

                              size: 1

                            },

                            dataLabels: {

                              enabled: false

                            },

                            labels: [<?php for($i = 1; $i <= $daysInMonth; $i++):?>'D<?=$i?>'<?php if($i<$daysInMonth){ ?>,<?php } ?><?php endfor; ?>],

                            legend: {

                              position: 'top',

                              horizontalAlign: 'right',

                              floating: true,

                              offsetY: -25,

                              offsetX: -5

                            }

                        }

                        apexMixedChart = new ApexCharts( document.querySelector("#apexMixedChart"), options );

                        apexMixedChart.render();

                        color_ = localStorage.getItem('c');

                        if(null === color_){

                            apexMixedChart.updateOptions({

                                colors: ['#ff6b35']

                            });

                        }else{

                            apexMixedChart.updateOptions({

                                colors: ['#'+color_]

                            });

                        }

                        

                    }

                    var options1 = {

                          series: [{

                          name: 'Doanh thu',

                          data: [<?php for($i = 0; $i < count($doanhthu); $i++):?><?=$doanhthu[$i]?><?php if($i<count($doanhthu)-1){ ?>,<?php } ?><?php endfor; ?>]

                        }],

                          chart: {

                            id: 'chart_revenue',

                          height: 350,

                          type: 'line',

                          dropShadow: {

                                enabled: true,

                                color: '#000',

                                top: 18,

                                left: 7,

                                blur: 10,

                                opacity: 0.2

                            }

                        },

                        stroke: {

                          width: 5,

                          curve: 'smooth'

                        },

                        xaxis: {

                          categories: [<?php for($i = 1; $i <= $daysInMonth; $i++):?>'D<?=$i?>'<?php if($i<$daysInMonth){ ?>,<?php } ?><?php endfor; ?>],

                        },

                        fill: {

                          type: 'gradient',

                          gradient: {

                            shade: 'dark',

                            gradientToColors: [ '#FDD835'],

                            shadeIntensity: 1,

                            type: 'horizontal',

                            opacityFrom: 1,

                            opacityTo: 1,

                            stops: [0, 100, 100, 100]

                          },

                        },

                        markers: {

                          size: 4,

                          colors: ["#FFA41B"],

                          strokeColors: "#fff",

                          strokeWidth: 2,

                          hover: {

                            size: 7,

                          }

                        },

                        yaxis: {

                          min: 0,

                          show: false

                        },

                        tooltip: {

                          y: {

                            formatter: function (val) {

                              return parseFloat(val, 10).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString() + 'đ';

                            }

                          }

                        }

                    };

                    chart_revenue = new ApexCharts(document.querySelector("#chart_revenue"), options1);

                    chart_revenue.render();



                    

                }

            }); 

        </script>
    <?php } ?>
    <script>
  $(document).ready(function() {
    function checkEmptyThongTinThem() {
        if ($('#attributes-container .box-input:visible').length === 0) {
            if ($('#attributes-container .box-input input[type="hidden"][name="data[thongtinthem]"]').length === 0) {
                $('#attributes-container').append(`
                    <div class="box-input" style="display: none;">
                        <input type="hidden" name="data[thongtinthem]" value="">
                    </div>
                `);
            }
        }
    }

    $('#add-thongtinthem').click(function() {
        var index = $('#attributes-container .box-input:visible').length;
        $('#attributes-container .box-input:hidden').remove(); // Xóa input ẩn nếu có
        var newAttribute = `
            <div class="box-input row">
                <div class="col-md-1">
                    <div class="form-group">
                        <input type="number" class="form-control stt-input" name="data[thongtinthem][${index}][key]" value="0" placeholder="STT" required>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="form-group">
                        <input type="text" class="form-control" name="data[thongtinthem][${index}][value]" placeholder="Thông tin">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-attribute">Xóa</button>
                </div>
            </div>
        `;
        $('#attributes-container').append(newAttribute);
    });

    $('#attributes-container').on('click', '.remove-attribute', function() {
        $(this).closest('.box-input').remove();
        checkEmptyThongTinThem();
    });

    checkEmptyThongTinThem();
});
</script>
</body>
</html>
