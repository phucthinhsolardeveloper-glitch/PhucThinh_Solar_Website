<?php
    /* Đăng ký nhận tin */
    $nametype = "dangkynhantin";
    $config['newsletter'][$nametype]['title_main'] = "Đăng ký nhận tin";
    $config['newsletter'][$nametype]['file'] = false;
    $config['newsletter'][$nametype]['email'] = true;
    $config['newsletter'][$nametype]['is_send'] = false;
    $config['newsletter'][$nametype]['fullname'] = true;
    $config['newsletter'][$nametype]['loaihinh'] = true;
    $config['newsletter'][$nametype]['congsuat'] = true;
    $config['newsletter'][$nametype]['phone'] = true;
    $config['newsletter'][$nametype]['address'] = true;
    $config['newsletter'][$nametype]['subject'] = false;
    $config['newsletter'][$nametype]['content'] = false;
    $config['newsletter'][$nametype]['notes'] = false;
    $config['newsletter'][$nametype]['confirm_status'] = array("1" => "Đã xem", "2" => "Đã liên hệ", "3" => "Đã thông báo");
    $config['newsletter'][$nametype]['show_name'] = true;
    $config['newsletter'][$nametype]['show_phone'] = true;
    $config['newsletter'][$nametype]['show_date'] = true;
    $config['newsletter'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    /* Đăng ký đại lý */
    $nametype = "dangkynhantin2";
    $config['newsletter'][$nametype]['title_main'] = "Đăng ký đại lý";
    $config['newsletter'][$nametype]['file'] = false;
    $config['newsletter'][$nametype]['email'] = true;
    $config['newsletter'][$nametype]['is_send'] = false;
    $config['newsletter'][$nametype]['fullname'] = true;
    $config['newsletter'][$nametype]['loaihinh'] = false;
    $config['newsletter'][$nametype]['congsuat'] = false;
    $config['newsletter'][$nametype]['phone'] = true;
    $config['newsletter'][$nametype]['address'] = true;
    $config['newsletter'][$nametype]['company'] = true;
    $config['newsletter'][$nametype]['content'] = false;
    $config['newsletter'][$nametype]['notes'] = false;
    $config['newsletter'][$nametype]['confirm_status'] = array("1" => "Đã xem", "2" => "Đã liên hệ", "3" => "Đã thông báo");
    $config['newsletter'][$nametype]['show_name'] = true;
    $config['newsletter'][$nametype]['show_phone'] = true;
    $config['newsletter'][$nametype]['show_date'] = true;
    $config['newsletter'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    // /* Đăng ký báo giá */
    // $nametype = "dangkybaogia";
    // $config['newsletter'][$nametype]['title_main'] = "Đăng ký báo giá";
    // $config['newsletter'][$nametype]['file'] = true;
    // $config['newsletter'][$nametype]['email'] = true;
    // $config['newsletter'][$nametype]['is_send'] = true;
    // $config['newsletter'][$nametype]['fullname'] = true;
    // $config['newsletter'][$nametype]['phone'] = true;
    // $config['newsletter'][$nametype]['address'] = true;
    // $config['newsletter'][$nametype]['subject'] = true;
    // $config['newsletter'][$nametype]['content'] = true;
    // $config['newsletter'][$nametype]['notes'] = true;
    // $config['newsletter'][$nametype]['confirm_status'] = array("1" => "Đã xem", "2" => "Đã liên hệ", "3" => "Đã thông báo");
    // $config['newsletter'][$nametype]['show_name'] = true;
    // $config['newsletter'][$nametype]['show_phone'] = true;
    // $config['newsletter'][$nametype]['show_date'] = true;
    // $config['newsletter'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';

    // /* Đăng ký tuyển dụng */
    // $nametype = "dangkytuyendung";
    // $config['newsletter'][$nametype]['title_main'] = "Đăng ký tuyển dụng";
    // $config['newsletter'][$nametype]['file'] = true;
    // $config['newsletter'][$nametype]['email'] = true;
    // $config['newsletter'][$nametype]['is_send'] = true;
    // $config['newsletter'][$nametype]['fullname'] = true;
    // $config['newsletter'][$nametype]['phone'] = true;
    // $config['newsletter'][$nametype]['address'] = true;
    // $config['newsletter'][$nametype]['subject'] = true;
    // $config['newsletter'][$nametype]['content'] = true;
    // $config['newsletter'][$nametype]['notes'] = true;
    // $config['newsletter'][$nametype]['confirm_status'] = array("1" => "Đã xem", "2" => "Đã liên hệ", "3" => "Đã thông báo");
    // $config['newsletter'][$nametype]['show_name'] = true;
    // $config['newsletter'][$nametype]['show_phone'] = true;
    // $config['newsletter'][$nametype]['show_date'] = true;
    // $config['newsletter'][$nametype]['file_type'] = '.doc|.docx|.pdf|.rar|.zip|.ppt|.pptx|.xls|.xlsx|.jpg|.png|.gif';
?>