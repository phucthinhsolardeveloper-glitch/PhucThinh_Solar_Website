<!-- Css Fonts -->
<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

<!-- Css Files -->
<link href="assets/fontawesome512/all.css" rel="stylesheet">
<?php if(!empty($_SESSION[$loginAdmin]['active'])) { ?>
<link href="assets/css/animate.min.css" rel="stylesheet">
<link href="assets/confirm/confirm.css" rel="stylesheet">
<link href="assets/select2/select2.css" rel="stylesheet">
<?php if(!$adminLeanAssetScreen) { ?>
<link href="assets/sumoselect/sumoselect.css" rel="stylesheet">
<link href="assets/datetimepicker/jquery.datetimepicker.css" rel="stylesheet">
<link href="assets/daterangepicker/daterangepicker.css" rel="stylesheet">
<link href="assets/rangeSlider/ion.rangeSlider.css" rel="stylesheet">
<link href="assets/filer/jquery.filer.css" rel="stylesheet">
<link href="assets/filer/jquery.filer-dragdropbox-theme.css" rel="stylesheet">
<?php } ?>
<?php if(($template ?? '') === 'order/man/mans' && $adminLeanAssetScreen) { ?>
<link href="assets/daterangepicker/daterangepicker.css" rel="stylesheet">
<link href="assets/rangeSlider/ion.rangeSlider.css" rel="stylesheet">
<?php } ?>
<link href="assets/holdon/HoldOn.css" rel="stylesheet">
<link href="assets/holdon/HoldOn-style.css" rel="stylesheet">
<?php } ?>
<link href="assets/simplenotify/simple-notify.css" rel="stylesheet">
<?php if(!empty($_SESSION[$loginAdmin]['active']) && !$adminLeanAssetScreen) { ?>
<link href="assets/comment/comment.css" rel="stylesheet">
<?php } ?>
<link href="assets/css/adminlte.css" rel="stylesheet">
<link href="assets/css/adminlte-style.css?v=<?=@filemtime(ROOT.'administrator/assets/css/adminlte-style.css')?>" rel="stylesheet">
