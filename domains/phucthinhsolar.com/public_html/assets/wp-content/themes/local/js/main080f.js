jQuery(document).ready(function ($) {


    show_search_form()

    var $sticky_tab_pd = $('.CRlist_tab_detail_pd ul');
    var $sticky_mn = $('#CRgroup_head');
    var $sticky_advi = $('.sidebar_single_advisory');
    var $sticky = $('.CRgr_mode');
    var $stickyrStopper = $('footer');
    var $stickyrStopper2 = $('#CRsingle_advisory_author');
    var handleVariationProduct = HandleVariationproduct();
    var optionCRintro_home = {
            responseSize: 768,
            selector: '#CRicos_intro_home',
            options: {
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows:false,
                dots:true,
            }
        },
    CRintro_home = new InitSlider(optionCRintro_home);
    CRintro_home.init();

    var optionWorkshop_intro = {
        responseSize: 769,
        selector: '.CRlist_cont_workshop',
        options: {
            slidesToShow: 1,
            slidesToScroll: 1,
            speed: 1000,
            arrows: false,
            dots: true,
            adaptiveHeight: true
        }
    },
    CRworkshop_intro = new InitSlider(optionWorkshop_intro);
    CRworkshop_intro.init();

    /* Dải danh mục tin tức/dự án dùng overflow-x native ở mobile. Trước đây
       Slick chỉ khởi tạo sau DOM ready nên dải flex nhiều dòng bị đổi thành
       carousel trong lúc người dùng đang nhìn, gây layout shift và lệch pill. */

    var optionList_filter_product = {
            responseSize: 992,
            selector: '.inf_filter_product .wrap',
            options: {
                infinite: false,
                slidesToShow: 2,
                slidesToScroll: 1,
                speed: 1000,
                arrows: false,
                dots: true,
                adaptiveHeight: true
            }
        },
    List_filter_product = new InitSlider(optionList_filter_product);
    List_filter_product.init();

    var optionList_icon_singe_pd = {
        responseSize: 767,
        selector: '.CRcustom_meta_pd .CRlist_icon_def_pd',
        options: {
            slidesToShow: 1,
            slidesToScroll: 1,
            speed: 1000,
            arrows: false,
            dots: true,
            adaptiveHeight: true,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            autoplay: true,
            autoplaySpeed: 1000,
        }
    },
    CRlist_icon_singe_pd = new InitSlider(optionList_icon_singe_pd);
    CRlist_icon_singe_pd.init();

    var optionList_brand_term = {
            responseSize: 768,
            selector: '#CRnew_slider_brands .row',
            options: {
                slidesToShow: 2,
                slidesToScroll: 2,
                speed: 1000,
                arrows: false,
                dots: true,
                cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
                autoplay: true,
                autoplaySpeed: 1000,
            }
        },
    CRlist_brand_term = new InitSlider(optionList_brand_term);
    CRlist_brand_term.init();

    var optionList_news_term = {
            responseSize: 769,
            selector: '.CRnews_term_list.active',
            options: {
                slidesToShow: 2,
                slidesToScroll: 1,
                speed: 1000,
                arrows: false,
                dots: true,
                cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
                autoplay: true,
                autoplaySpeed: 1500,
                responsive: [
                    {
                        breakpoint: 454,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1,
                        }
                    },
                ]
            }
        },
    CRlist_news_term = new InitSlider(optionList_news_term);
    CRlist_news_term.init();

    var optioninf_vision_intro = {
            responseSize: 992,
            selector: '#CRlist_vision .content',
            options: {
                slidesToShow: 1,
                slidesToScroll: 1,
                speed: 1000,
                arrows: false,
                dots: true,
                cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
                autoplay: true,
                autoplaySpeed: 1500,
                adaptiveHeight: true,
            }
        },
    inf_vision_intro = new InitSlider(optioninf_vision_intro);
    inf_vision_intro.init();

    var optionInficos_intro_page = {
            responseSize: 992,
            selector: '#CRicos_intro_home',
            options: {
                slidesToShow: 2,
                slidesToScroll: 1,
                speed: 1000,
                arrows: false,
                dots: true,
                cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
                autoplay: true,
                autoplaySpeed: 1500,
                adaptiveHeight: true,
                responsive: [
                    {
                        breakpoint: 454,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1,
                        }
                    },
                ]
            }
        },
    Inficos_intro_page = new InitSlider(optionInficos_intro_page);
    Inficos_intro_page.init();

    var optionHome4 = {
            responseSize: 767,
            selector: '.home .home-4 .uk-grid',
            options: {
                slidesToShow: 1,
                slidesToScroll: 1,
                speed: 1000,
                arrows: false,
                dots: true,
                adaptiveHeight: true
            }
        },
    Home4 = new InitSlider(optionHome4);
    Home4.init();

    handleVariationProduct.init();
    jQuery(window).scroll(function(){
        if (jQuery(window).scrollTop() >= 100) {
            jQuery('.main_header').addClass('fixed_header');
            jQuery('.newTopHead').addClass('hide');
        }
        else {
            jQuery('.main_header').removeClass('fixed_header');
            jQuery('.newTopHead').removeClass('hide');
        }
    });
    /** Mobile Filter **/
    jQuery('.CRactive_head_filter').click(function () {
        jQuery(this).parents('.CRpop_filter').find('.CRpop_filter_left').addClass('active');
    });
    jQuery('.CRactive_head_sort').click(function () {
        jQuery(this).parents('.CRpop_filter').find('.CRpop_filter_right').addClass('active');
    });
    jQuery('.close_pop_filter a').click(function () {
        jQuery('.CRpop_filter_def').removeClass('active');
    });
    jQuery(document).delegate(".open_filter p","click",function(event){
        if (jQuery(this).parent().hasClass('active_line')) {
            jQuery(this).parent().removeClass("active_line");
            jQuery(this).parent().find('.active_line').removeClass("active_line");
            jQuery(this).find('i').removeClass('fa fa-angle-up').addClass('fa fa-angle-down');
            jQuery(this).closest('ul').find('> li .CRsub_filter').slideUp();
        } else  {
            jQuery(this).closest('ul').find('> li .CRsub_filter').slideUp();
            jQuery(this).closest('ul').find('li').removeClass("active_line");
            jQuery(this).parent().addClass("active_line");
            jQuery(this).parent().find('> .CRsub_filter').slideDown();
            jQuery(this).closest('ul').find('.open_filter i').removeClass('fa fa-angle-up').addClass('fa fa-angle-down');
            jQuery(this).find('i').removeClass('fa fa-angle-down').addClass('fa fa-angle-up');
        }
    });
    /** Mobile menu **/
    if (jQuery(window).width() < 1199) {
        jQuery('.CRmain_mndes').appendTo('#CRmobile_nav');
        jQuery('.group_header_se').appendTo('#CRnewmove_search_mb');
        jQuery('#CRhotline').appendTo('#CRlog_mobile');
        jQuery('.group_cart_head').appendTo('#CRgrmobile_ico');
        jQuery(' body #mn_mobile #CRgrmobile_nav #CRmobile_nav #menu-main-menu > .menu-item-has-children > ul.sub-menu').prepend('<li class="line_hide"><span class="hide_submenu"><i class="fa fa-long-arrow-right"></i></span></li>');
        jQuery('.line_hide').click(function () {
            jQuery(this).parents('.menu-item-has-children').removeClass("active_line");
            jQuery(this).parents('.menu-item-has-children').find('i').removeClass('fa fa-angle-up').addClass('fa fa-angle-down');
        });
    }else{
        jQuery('.CRmain_mndes').appendTo('#mn_desk ');
        jQuery('.group_header_se').appendTo('#CRmove_search');
        jQuery('#CRhotline').appendTo('.top_left_header');
        jQuery('.group_cart_head').appendTo('.top_right_header');
    }
    jQuery( window ).resize(function() {
        if (jQuery(window).width() < 1199) {
            jQuery('.CRmain_mndes').appendTo('#CRmobile_nav');
            //jQuery('.group_header_se').appendTo('#CRlog_mobile');
            jQuery('#CRhotline').appendTo('#CRlog_mobile');
            jQuery('.group_cart_head').appendTo('#CRgrmobile_ico');
        }else {
            jQuery('.CRmain_mndes').appendTo('#mn_desk');
            //jQuery('.group_header_se').appendTo('#CRmove_search');
            jQuery('#CRhotline').appendTo('.top_left_header');
            jQuery('.group_cart_head').appendTo('.top_right_header');
        }
    });
    jQuery('.ico_submenu').click(function () {
        jQuery('#CRgrmobile_nav').addClass('active');
        jQuery('body').addClass('CRactive');
    });
    jQuery('.close_submn a').click(function () {
        jQuery('#CRgrmobile_nav').removeClass('active');
        jQuery('body').removeClass('CRactive');
    });
    jQuery("#main-navigation .menu-item-has-children").each(function(){
        jQuery(this).prepend('<span class="show_menu"><i class="fa fa-angle-down"></i></span>');
    })
    jQuery(".footer_top .item .footer-title").each(function(){
        jQuery(this).prepend('<span class="show_menu_mb"><i class="fa fa-angle-down"></i></span>');
    })
    jQuery(".CRchild_nav").each(function(){
        if (!$(this).hasClass("menu-item-has-children")) {
            jQuery(this).prepend('<span class="show_menu"><i class="fa fa-angle-down"></i></span>');
        }
    })
    jQuery(document).delegate(".show_menu","click",function(event){
        if (jQuery(this).parent().hasClass('active_line')) {
            jQuery(this).parent().removeClass("active_line");
            jQuery(this).parent().find('.active_line').removeClass("active_line");
            jQuery(this).find('i').removeClass('fa fa-angle-up').addClass('fa fa-angle-down');
            jQuery(this).closest('ul').find('> li .sub-menu').slideUp();
        } else  {
            jQuery(this).closest('ul').find('> li .sub-menu').slideUp();
            jQuery(this).closest('ul').find('li').removeClass("active_line");
            jQuery(this).parent().addClass("active_line");
            jQuery(this).parent().find('> .sub-menu').slideDown();
            jQuery(this).closest('ul').find('.show_menu i').removeClass('fa fa-angle-up').addClass('fa fa-angle-down');
            jQuery(this).find('i').removeClass('fa fa-angle-down').addClass('fa fa-angle-up');
        }
    });
    jQuery(document).delegate(".show_menu_mb","click",function(event){
        if (jQuery(this).parent().hasClass('active_line')) {
            jQuery(this).parent().removeClass("active_line");
            jQuery(this).parent().find('.active_line').removeClass("active_line");
            jQuery(this).find('i').removeClass('fa fa-angle-up').addClass('fa fa-angle-down');
            jQuery(this).parents('.item').find('> .CRcont_nav_mobile').slideUp();
        } else  {
            jQuery('.CRcont_nav_mobile').slideUp();
            jQuery('.footer-title').removeClass("active_line");
            jQuery(this).parent().addClass("active_line");
            jQuery(this).parents('.item').find('> .CRcont_nav_mobile').slideDown();
            jQuery('.show_menu_mb i').removeClass('fa fa-angle-up').addClass('fa fa-angle-down');
            jQuery(this).find('i').removeClass('fa fa-angle-down').addClass('fa fa-angle-up');
        }
    });
    /** Popup **/
    jQuery('.bg_popup_first,.close_pop_first').click(function () {
        jQuery('#popup_load_first').removeClass('show_pop');
        jQuery('.bg_popup_first').removeClass('show_pop');
    });
    /** trigger update cart **/
    jQuery(document).delegate('#CRtop_cart_page input.qty', 'change', function(event) {
        jQuery("#CRbtn_update_cart").find('button.btn').trigger("click");
    });
    /** Load first **/
    jQuery(window).on('load', function () {
        //jQuery('html, body').animate({scrollTop:0}, 1000);
        jQuery(".loading_left,.loading_right,.wrap-load-logo,.loading-section .loader,.loading-section .CRlogo").addClass("hidden-loading-section");
    });
    /** Add value rating **/
    jQuery(document).delegate('.gr_right_rating  .rating__input', 'change', function(event) {
        var value = '';
        if (event.target.checked) {
            value = jQuery(this).val();
        }
        jQuery('#data_rating').val(value);
    })
    /** Add value gender **/
    jQuery(document).delegate('.reg_user_gender  .form-check-input', 'change', function(event) {
        var value = '';
        if (event.target.checked) {
            value = jQuery(this).val();
        }
        jQuery('#reg_gender_meta').val(value);
    })
    jQuery(document).delegate('.gender_update_ac  .form-check-input', 'change', function(event) {
        var value = '';
        if (event.target.checked) {
            value = jQuery(this).val();
        }
        jQuery('#gender_hidden').val(value);
    })
    /** Fixed Element **/
    // if (!!$sticky_tab_pd.offset()) {
    //     var generalHeaderHeight = (jQuery('.top_header').innerHeight() + jQuery('.main_header').innerHeight()) - 85;
    //
    //     var generalFoooterHeight = $stickyrStopper.innerHeight();
    //     var generalHeight1 = jQuery('#CRaform_reg_home').innerHeight();
    //     var generalHeight2 = jQuery('#CRsingle_product_bottom').innerHeight();
    //     var generalHeight3 = jQuery('.CRcustom_content_pd').innerHeight();
    //     var stickyrStopperHeight = generalFoooterHeight + generalHeight1 + generalHeight2 ;
    //     //console.log(stickyrStopperHeight)
    //     // jQuery(window).scroll(function(){
    //
    //     //     if (jQuery(window).scrollTop() >= 100) {
    //     //         generalHeaderHeight = jQuery('#CRgroup_head.fixed_header').innerHeight();
    //     //         console.log(generalHeaderHeight);
    //     //         $.lockfixed(".CRlist_tab_detail_pd ul", {offset: {top: generalHeaderHeight, bottom: stickyrStopperHeight} });
    //     //     }
    //     //     else {
    //     //         generalHeaderHeight = jQuery('#CRgroup_head').innerHeight();
    //
    //     //         $.lockfixed(".CRlist_tab_detail_pd ul", {offset: {top: generalHeaderHeight, bottom: stickyrStopperHeight} });
    //     //     }
    //     //     $.lockfixed(".CRlist_tab_detail_pd ul", {offset: {top: generalHeaderHeight, bottom: stickyrStopperHeight} });
    //     // });
    //     // console.log(generalHeaderHeight);
    //     $.lockfixed(".CRlist_tab_detail_pd ul", {offset: {top: generalHeaderHeight, bottom: stickyrStopperHeight} });
    // }
    if (!!$sticky_mn.offset()) {
        var generalFoooterHeight = $stickyrStopper.innerHeight();
        var stickyrStopperHeight = generalFoooterHeight;
        $.lockfixed("#CRgroup_head", {offset: {top: 0, bottom: stickyrStopperHeight} });
    }
    if (!!$sticky.offset()) {
        var generalSidebarHeight = $sticky.innerHeight();
        var generalFoooterHeight = $stickyrStopper.innerHeight();
        $.lockfixed(".CRgr_mode", {offset: {top: 120, bottom: generalFoooterHeight} });
    }
    if (!!$sticky_advi.offset()) {
        var generalSidebarHeight = $sticky_advi.innerHeight();
        var generalFoooterHeight = $stickyrStopper.innerHeight();
        var generalNewsHeight = $stickyrStopper2.innerHeight();
        var stickyrStopperHeight = generalFoooterHeight + generalNewsHeight + 140;

        $.lockfixed(".sidebar_single_advisory", {offset: {top: 120, bottom: stickyrStopperHeight} });
    }
    jQuery("#list_support #accordion").on("hide.bs.collapse show.bs.collapse", e => {
        jQuery(e.target)
            .prev()
            .find("i:last-child")
            .toggleClass("fa-sort-asc");
    });
    jQuery(document).on('scroll', function() {
        if ( jQuery( "#cr_list_scroll" ).length ) {
            if(jQuery(this).scrollTop()>=jQuery('#CRlist_scroll_cont').position().top ){
                jQuery('#cr_list_scroll').addClass('show');
            }else {
                jQuery('#cr_list_scroll').removeClass('show');
            }
            if(jQuery(this).scrollTop()>=jQuery('#CRsingle_blog_author').position().top - 500){
                jQuery('#cr_list_scroll').addClass('hide');
            }else {
                jQuery('#cr_list_scroll').removeClass('hide');
            }
        }
        if ( jQuery( ".sidebar_single_advisory" ).length ) {
            if(jQuery(this).scrollTop()>=jQuery('#CRsingle_advisory_content').position().top - 50){
                jQuery('.sidebar_single_advisory').removeClass('fixed');
            }else {
                jQuery('.sidebar_single_advisory').addClass('fixed');
            }
        }

    })
    jQuery('.close_ad').click(function () {
        jQuery('#CRad_header').addClass('hide')
    })
    jQuery(".click_scroll").click(function() {
        var target = jQuery(this).data("target");
        var generalGroup_head = jQuery('#CRgroup_head').innerHeight();
        jQuery('html, body').animate({
            scrollTop: jQuery(target).offset().top - generalGroup_head
        }, 1000);
        if (jQuery(this).hasClass("active")) {
            jQuery(this).removeClass('active');
        } else {
            jQuery('#list_scroll .click_scroll').removeClass('active');
            jQuery(this).addClass('active');
        }
    });
    jQuery(".show_list").click(function() {
        jQuery(this).addClass('hide');
        jQuery('#list_scroll').addClass('active');
    });
    jQuery(".hide_list_scroll").click(function() {
        jQuery('.show_list').removeClass('hide');
        jQuery('#list_scroll').removeClass('active');
    });
    
    /* Add show more content in category product - SEO */

    jQuery( "<div class='show_more'><span>Đọc Thêm <i class='fa fa-caret-down'></i></span></div>" ).insertAfter( ".CRinfo_products_page_right .content .des_tax");
    
    jQuery('.CRinfo_products_page_right .show_more').click( function(){
         jQuery(".des_tax").addClass('show');
         jQuery(this).addClass('hide');
    });
    
    /** count text in short decription show in product single **/
    var TextShow = 150;
    var Limittext = ".";

    jQuery(".CRcustom_meta_pd .short-description").each(function () {
        var textlk = jQuery(this).html();
        if (textlk.length > TextShow) {
            var b = textlk.substr(0, TextShow);
            var htmllk = b + Limittext;
            jQuery(this).html(htmllk);
        }
    });
    /** Active Style Read **/
    jQuery('#CRmode__wrap').click(function () {
        jQuery('.tooltip').removeClass("hide");
        jQuery('body').toggleClass('CRread_tooltip');
        jQuery('.mode__wrap').toggleClass('active');
        jQuery('.CRfix_gr_mode').toggleClass('CRread_mode');
    })
    jQuery(document).delegate("#CRfaqs_page .myaccordion .card-header", "click", function() {
        if (jQuery(this).parent().hasClass("active")) {
            jQuery(this).parent().removeClass('active');
        } else {
            jQuery('#CRfaqs_page .myaccordion .card-header').parent().removeClass('active');
            jQuery(this).parent().addClass('active');
        }
    });
    jQuery(document).on('click', 'a.btn_scroll[href^="#"]', function(e) {
        var id = jQuery(this).attr('href');
        var $id = jQuery(id);
        if ($id.length === 0) {
            return;
        }
        e.preventDefault();
        var pos = $id.offset().top;
        jQuery('body, html').animate({scrollTop: pos -270}, 'slow');
    });
    /** Scroll to review product **/
    jQuery(document).on('click', '.CRviewmore_rating', function(e) {
        console.log('aaaa')
        var id = '#' + jQuery(this).attr('data-target');
        var $id = jQuery(id);
        if ($id.length === 0) {
            return;
        }
        e.preventDefault();
        var pos = $id.offset().top;
        jQuery('body, html').animate({scrollTop: pos -140}, 'slow');
    });
    /** Scroll tab product **/
    jQuery(document).on('click', '.CRscroll_tab_pd', function(e) {
        console.log('aaaa')
        var id = '#' + jQuery(this).attr('data-target');
        var $id = jQuery(id);
        if ($id.length === 0) {
            return;
        }
        e.preventDefault();
        var pos = $id.offset().top;
        jQuery('body, html').animate({scrollTop: pos -140}, 'slow');
    });
    jQuery( window ).resize(function() {
        if (jQuery(window).width() < 767) {
            if(jQuery('#mn_mobile #group_cart_icon_header').length > 0){
              console.log('a');
            } else{
                //jQuery('#group_cart_icon_header').appendTo('#mn_mobile .group_top_right_header');
            }
            //jQuery('#group_cart_icon_header').appendTo('.search_top_header');
            jQuery('.group_header_se').appendTo('#mn_mobile .group_top_right_header');
        }else {
            jQuery('#group_cart_icon_header').appendTo('.top_header .group_top_right_header');
            jQuery('.group_header_se').appendTo('.search_top_header');
        }
    });
    if (jQuery(window).width() < 767) {
        if(jQuery('#mn_mobile #group_cart_icon_header').length > 0){
            console.log('a');
        } else{
            //jQuery('#group_cart_icon_header').appendTo('#mn_mobile .group_top_right_header');
        }
        //jQuery('#group_cart_icon_header').appendTo('.search_top_header');
        jQuery('.group_header_se').appendTo('#mn_mobile .group_top_right_header');
    }else {
        jQuery('#group_cart_icon_header').appendTo('.top_header .group_top_right_header');
        jQuery('.group_header_se').appendTo('.search_top_header');
    }
    /**/
    jQuery(document).delegate("#support_page .myaccordion .card-header", "click", function() {
        if (jQuery(this).parent().hasClass("active")) {
            jQuery(this).parent().removeClass('active');
        } else {
            jQuery('section#support_page .myaccordion .card-header').parent().removeClass('active');
            jQuery(this).parent().addClass('active');
        }
    });
    jQuery(document).delegate(".term_faqs a", "click", function() {
        if (jQuery(this).parents('.item_term_faqs').hasClass("active")) {
            jQuery(this).parents('.item_term_faqs').removeClass('active');
        } else {
            jQuery('.term_faqs a').parents('.item_term_faqs').removeClass('active');
            jQuery(this).parents('.item_term_faqs').addClass('active');
        }
    });
    /**/
    jQuery(document).delegate(".item_store", "click", function() {
        if (jQuery(this).hasClass("active")) {
            jQuery(this).removeClass('active');
        } else {
            jQuery('.item_store').removeClass('active');
            jQuery(this).addClass('active');
        }
    });
    jQuery(".btn-show-recruits").click(function(e) {
        jQuery(".form-single-recruits").find('input[type="file"]').trigger("click");
    });
    jQuery('.table_recrui tbody tr').on("click",function(){
        window.location = jQuery(this).data('href');
        return false;
    });
    jQuery('.filter_gr input').on('change', function() {
        var value = jQuery(this).closest('.filter_gr').find('input').val(),
            value = jQuery(this).val();
            title = jQuery(this).attr('name');
        if (jQuery(this).is(':checked')) {
            var html = '<span data-id="' + value + '" title="' + title + '">' + title + '</span>';
            jQuery('#filter_tags').append(html);
        } else {
            jQuery('span[title="' + title + '"]').remove();
        }
    });
    jQuery('.new_ac,.sign_up_link').click(function () {
        jQuery('.CRcheckout_reg_user').addClass('active_res')
        jQuery('.CRcheckout_log_user').addClass('hide_login');
        jQuery('.CRcheckout_log_user').removeClass('active_log')
        jQuery('.CRcheckout_reg_user').removeClass('hide_res')
    })
    jQuery('.log_ac').click(function () {
        jQuery('.CRcheckout_reg_user').addClass('hide_res');
        jQuery('.CRcheckout_reg_user').removeClass('active_res');
        jQuery('.CRcheckout_log_user').removeClass('hide_login');
        jQuery('.CRcheckout_log_user').addClass('active_log')
    })
    jQuery(window).scroll(function(){
        var $ad = $('#CRad_header');
        var $top_head = $('.top_header');
        var adHeight = $ad.innerHeight();
        var topHeadHeight = $top_head.innerHeight();
        var HeadHeight = topHeadHeight + adHeight;
        if (jQuery(window).scrollTop() >= HeadHeight) {
            jQuery('#CRgroup_head').addClass('fixed_header');
        }
        else {
            jQuery('#CRgroup_head').removeClass('fixed_header');
        }
    });

    jQuery(document).delegate("#group_filter .filter_name", "click", function() {
        if (jQuery(this).parent().hasClass("active")) {
            jQuery(this).parent().removeClass('active');
        } else {
            jQuery('#group_filter .filter_name').parent().removeClass('active');
            jQuery(this).parent().addClass('active');
        }
    });
    jQuery(document).delegate(".CRbgpop_cart,.CRclose_pop_cart", "click", function() {
        jQuery('body').removeClass('CRactive');
        jQuery('#CRpop_cart').removeClass('active');
        jQuery('#CRload_list_cart').removeClass('active');
    });
    jQuery(document).delegate(".bg_popup_ft,.CRclose_quickview", "click", function() {
        jQuery('.popup_footer').removeClass('active');
        jQuery('.bg_popup_ft').removeClass('active');
        jQuery('#popup_content_ft').html('');
    });
    jQuery('.bg_popup_360').click(function(){
        jQuery('.popup_360').removeClass('active');
        jQuery('.bg_popup_360').removeClass('active');
        jQuery('#popup_content_360').html('');
    });
    jQuery('.tab_default .nav-item a').on('shown.bs.tab', function (event) {
        var index = jQuery(event.target).parents('li').index();

        if (jQuery('.custom_list_pd')[index]) {
            jQuery('.custom_list_pd')[index].slick.refresh();
        }
        jQuery('.slick-initialized.slick-slider').each(function(index, item) {
            item.slick.refresh();
        });
    })

    var btn = jQuery('.button_back_top');
    jQuery(window).scroll(function() {
        if (jQuery(window).scrollTop() > 300) {
            btn.addClass('show');
        } else {
            btn.removeClass('show');
        }
    });
    btn.on('click', function(e) {
        e.preventDefault();
        $('html, body').animate({scrollTop:0}, '300');
    });
    /**slider**/
    /** Change 03/2021 **/
    jQuery('.custom-product-slide.activate').each(function() {
        var slider = jQuery(this);
        slider.slick({
            arrows: false,
            dots: false,
            infinite: false,
            speed: 300,
            slidesToShow: 3,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 3,
                        infinite: true,
                        dots: true
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 2,
                        infinite: true,
                        dots: true
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true
                    }
                }
            ]
        });
    });
    /** End change**/
	jQuery('#CRnew_banner_home.active_slider').slick({
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        speed: 1000,
        fade: true,
        cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
        autoplay: false,
        arrows:true,
        dots:true,
        adaptiveHeight: true,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    adaptiveHeight: true
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    adaptiveHeight: true
                }
            }
        ]
    });
    jQuery('#CRbanner_home.active_slider').slick({
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        speed: 1000,
        fade: true,
        cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
        autoplay: true,
        arrows:true,
        dots:true,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 5000,
        adaptiveHeight: true,
        responsive: [
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    adaptiveHeight: true
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    adaptiveHeight: true
                }
            }
        ]
    });
    jQuery('#CRlist_collection_home.active_slider').slick({
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 1,
        autoplay: false,
        arrows:true,
        speed: 1000,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            }
        ]
    });
    jQuery('#CRlist_product_home.active_slider').slick({
        infinite: true,
        slidesToShow: 4,
        slidesToScroll: 1,
        autoplay: true,
        arrows:true,
        speed: 1000,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            }
            ,{
                breakpoint: 320,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            }
        ]
    });
    jQuery('#CRlist_view_product.active_slider').slick({
        infinite: true,
        slidesToShow: 4,
        slidesToScroll: 1,
        autoplay: true,
        arrows:true,
        speed: 1000,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            }
        ]
    });
    jQuery('#CRhome_page #CRlist_logo_partner_home.active_slider').slick({
        adaptiveHeight: true,
        rows: 2,
        dots: false,
        infinite: true,
        speed: 1000,
        slidesPerRow: 6,
        autoplay: false,
        arrows:true,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesPerRow: 6,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesPerRow: 4,
                    rows: 1,
                    arrows:false,
                    dots: true,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesPerRow: 2,
                    rows: 1,
                    arrows:false,
                    dots: true,
                }
            }
        ]
    });
    jQuery('#CRintro_page #CRlist_logo_partner_home.active_slider').slick({
        adaptiveHeight: true,
        dots: false,
        infinite: true,
        speed: 1000,
        slidesToShow: 6,
        slidesToScroll: 2,
        autoplay: false,
        arrows:true,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2,
                    arrows:false,
                    dots:true,
                }
            }
        ]
    });
    jQuery('#CRlist_workshop_intro.active_slider').slick({
        infinite: true,
        slidesToShow: 2,
        slidesToScroll: 1,
        autoplay: true,
        arrows:true,
        speed: 1000,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            }
        ]
    });
    jQuery('#CRlist_certification_intro.active_slider').slick({
        infinite: true,
        slidesToShow: 4,
        slidesToScroll: 2,
        autoplay: false,
        arrows:true,
        speed: 1000,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll:3,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll:2,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            }
        ]
    });
    jQuery('#CRslider_recruitment.active_slider').slick({
        infinite: true,
        slidesToShow: 4,
        slidesToScroll: 2,
        autoplay: false,
        arrows:true,
        speed: 1000,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll:3,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2,
                    arrows:false,
                    dots:true,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows:false,
                    dots:true,
                }
            }
        ]
    });
    jQuery('#CRlist_customer.active_slider').each(function() {
        var slider = jQuery(this);
        slider.slick({
            dots: false,
            infinite: true,
            autoplay: true,
            touchThreshold: 10,
            speed: 1000,
            centerMode: true,
            centerPadding: '15%',
            prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
            nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
            adaptiveHeight: true,
            slidesToShow: 1,
            variableWidth: true,
            arrows:true,
            responsive: [
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 1,
                    arrows:true,
                    dots: false,
                }
            },
        ]
    });
    });
    jQuery('.CRrow_term_bottom.active_slider').slick({
        adaptiveHeight: true,
        rows: 2,
        dots: false,
        infinite: true,
        speed: 1000,
        slidesPerRow: 3,
        autoplay: false,
        arrows:true,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesPerRow: 3,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesPerRow: 2,
                    rows: 1,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesPerRow: 1,
                    rows: 1,
                }
            }
        ]
    });
    jQuery('#CRsingle_list_related.active_slider').slick({
        dots: false,
        infinite: true,
        speed: 1000,
        slidesToShow: 4,
        slidesToScroll: 1,
        autoplay: false,
        arrows:true,
        prevArrow: jQuery('.CRcustom_arrow_related .left'),
        nextArrow: jQuery('.CRcustom_arrow_related .right'),
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 1,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                }
            }
        ]
    });
    jQuery('#CRbest_sell_home #CRlist_best_sell_home.active_slider').slick({
        adaptiveHeight: true,
        rows: 2,
        dots: false,
        infinite: true,
        speed: 1000,
        slidesPerRow: 4,
        autoplay: false,
        arrows:true,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesPerRow: 3,
                }
            },
            {
                breakpoint: 767,
                settings: {
                    slidesPerRow: 2,
                    rows: 1,
                }
            },
            {
                breakpoint: 454,
                settings: {
                    slidesPerRow: 2,
                    rows: 1,
                }
            }
        ]
    });
    if (jQuery(".slider_cont_pd").hasClass("active_slider")) {
        CRslider_gal_pd();
    }
    /** **/
    $('.sign_up_link').on('click',function () {
        $('#reg_user').click();
    });
    jQuery('.payment_item').filter(function (key, item) {
        if(jQuery(item).find('input[type="radio"]:checked'))
            jQuery('input[type="radio"]:checked').parent().parent().addClass('checked');

        jQuery(item).not('.checked').find('label').hover(function () {
            jQuery(this).parent().toggleClass('checked');
        });

        jQuery(item).find('input[type="radio"]').on('click', function () {
            jQuery('.payment_item').not(item).removeClass('checked');
            jQuery(item).addClass('checked');
        });
    });
    var lpay_height = jQuery('.location_checkout').innerHeight();
    if (lpay_height){
        jQuery('.checkout_step_info').css('padding-bottom', lpay_height + 'px' )
    }
    jQuery(window).resize(function(){
        var lpay_height = jQuery('.location_checkout').innerHeight();
        if (lpay_height){
            jQuery('.checkout_step_info').css('padding-bottom', lpay_height + 'px' )
        }
    });
    /** select ul li **/
    jQuery('.CRcustom_sl select').each(function () {
        var $this = jQuery(this), numberOfOptions = jQuery(this).children('option').length
        $this.addClass('select-hidden')
        var val = jQuery(this).val();
        $this.wrap('<div class="select"></div>')
        $this.after('<div class="select-styled"></div>')
        var $styledSelect = $this.next('div.select-styled')
        var $list = jQuery('<ul />', {'class': 'select-options'}).insertAfter($styledSelect)
        for (var i = 0; i < numberOfOptions; i++) {
            var active = (val == $this.children('option').eq(i).val()) ? 'active' : '';
            if (val == $this.children('option').eq(i).val()) {
                $styledSelect.text($this.children('option').eq(i).text());
            }
            jQuery('<li />', {
                text: $this.children('option').eq(i).text(),
                rel: $this.children('option').eq(i).val(),
                'data-class-section': $this.children('option').eq(i).attr('data-class-section'),
                class: active,
            }).appendTo($list)
        }
        var $listItems = $list.children('li')
        $styledSelect.click(function (e) {
            e.stopPropagation()
            jQuery('div.select-styled.active').not(this).each(function () {
                jQuery(this).removeClass('active').next('ul.select-options').hide()
            })
            jQuery(this).toggleClass('active').next('ul.select-options').toggle()
        })
        $listItems.click(function (e) {
            e.stopPropagation();
            $styledSelect.text(jQuery(this).text()).removeClass('active');
            $this.val(jQuery(this).attr('rel'));
            $this.trigger('change');
            $list.hide();
        })
        jQuery(document).click(function () {
            $styledSelect.removeClass('active')
            $list.hide()
        })
    })
    /** Woo qty **/
    jQuery( function( $ ) {

        if ( ! String.prototype.getDecimals ) {
            String.prototype.getDecimals = function() {
                var num = this,
                    match = ('' + num).match(/(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/);
                if ( ! match ) {
                    return 0;
                }
                return Math.max( 0, ( match[1] ? match[1].length : 0 ) - ( match[2] ? +match[2] : 0 ) );
            }
        }

        function wcqi_refresh_quantity_increments(){
            $( 'div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)' ).addClass( 'buttons_added' ).append( '<input type="button" value="+" class="plus" />' ).prepend( '<input type="button" value="-" class="minus" />' );
        }

        $( document ).on( 'updated_wc_div', function() {
            wcqi_refresh_quantity_increments();
        } );

        $( document ).on( 'click', '.plus, .minus', function() {
            // Get values
            var $qty      = $( this ).closest( '.quantity' ).find( '.qty'),
                currentVal = parseFloat( $qty.val() ),
                max          = parseFloat( $qty.attr( 'max' ) ),
                min          = parseFloat( $qty.attr( 'min' ) ),
                step      = $qty.attr( 'step' );
            console.log(currentVal)
            // Format values
            if ( ! currentVal || currentVal === '' || currentVal === 'NaN' ) currentVal = 0;
            if ( max === '' || max === 'NaN' ) max = '';
            if ( min === '' || min === 'NaN' ) min = 0;
            if ( step === 'any' || step === '' || step === undefined || parseFloat( step ) === 'NaN' ) step = 1;

            // Change the value
            if ( $( this ).is( '.plus' ) ) {
                if ( max && ( currentVal >= max ) ) {
                    $qty.val( max );
                } else {
                    $qty.val( ( currentVal + parseFloat( step )).toFixed( step.getDecimals() ) );
                }
            } else {
                if ( min && ( currentVal <= min ) ) {
                    $qty.val( min );
                } else if ( currentVal > 0 ) {
                    $qty.val( ( currentVal - parseFloat( step )).toFixed( step.getDecimals() ) );
                }
            }

            // Trigger change event
            $qty.trigger( 'change' );
        });
        wcqi_refresh_quantity_increments();
    });
    /** Zoom product **/
    if(jQuery('#slider_cont_pd').length){
        var demoTrigger = document.querySelector(".CRitem_zoom.slick-active .demo-trigger");
        jQuery('.CRzoom, #slider_cont_pd .customm_arrow').click(function(){
            var democurrentTrigger = document.querySelector(".CRitem_zoom.slick-current .demo-trigger");
            if ( jQuery( ".slick-current .demo-trigger" ).length ) {
                new Drift(democurrentTrigger, {
                    paneContainer: document.querySelector(".CRcustom_meta_pd"),
                    inlinePane: false,
                });
            }

        });
        new Drift(demoTrigger, {
            paneContainer: document.querySelector(".CRcustom_meta_pd"),
            inlinePane: false,
        });
    }
    /** PAGE CHECK OUT **/
    jQuery(document).delegate(".btn_next_to_payment","click",function(event){
        jQuery('.checkout.woocommerce-checkout').trigger('submit');
        return false;
    });
    jQuery(document).delegate("#CRprev_step_info","click",function(event){
        CRStepInfo();
        return false;
    });
    var CRstepOne = true;
    var data_notice = jQuery('#text_err_checkout').val();
    jQuery(document.body).on('checkout_error', function(){
        var CRwooError = jQuery('body .woocommerce-NoticeGroup-checkout .woocommerce-error li').length;
        var CRstepTwo = jQuery('body .woocommerce-NoticeGroup-checkout .woocommerce-error li span.enb_stepTwo').length;
        if(CRwooError == 1 && CRstepTwo == 1){
            CRStepPay();
        }else{
            add_notice('<font color="red">'+ data_notice +'</font>');
        }
    });
    /** FILTER **/
    jQuery(document).delegate(".CRshow_term","click",function(event){
        if (jQuery(this).parent().hasClass('active_line')) {
            jQuery(this).parent().removeClass("active_line");
            jQuery(this).parent().find('.active_line').removeClass("active_line");
            jQuery(this).find('i').removeClass('fa fa-minus').addClass('fa fa-plus');
            jQuery(this).closest('ul').find('> li .CRsub_list').slideUp();
        } else  {
            jQuery(this).closest('ul').find('> li .CRsub_list').slideUp();
            jQuery(this).closest('ul').find('li').removeClass("active_line");
            jQuery(this).parent().addClass("active_line");
            jQuery(this).parent().find('> .CRsub_list').slideDown();
            jQuery(this).closest('ul').find('.CRshow_term i').removeClass('fa fa-minus').addClass('fa fa-plus');
            jQuery(this).find('i').removeClass('fa fa-plus').addClass('fa fa-minus');
        }
    });
    jQuery(document).delegate(".CRshow_filter","click",function(event){
        jQuery('.CRfillter_center_left').removeClass("hide");
        jQuery('.CRfillter_center_right').removeClass("full");
    });
    jQuery(document).delegate(".CRhide_filter","click",function(event){
        jQuery('.CRfillter_center_left').addClass("hide");
        jQuery('.CRfillter_center_right').addClass("full");
    });
    /** VARIATIONS PRODUCT **/
    jQuery(document).delegate("#CRsingle_product_page .hrSwatch","click",function(event){
        CRrunvariationPd();
    });
    jQuery(document).delegate("#CRquickView_box .hrSwatch","click",function(event){
        jQuery('#CRquickView_box .hrSwatch').removeClass('selected');
        jQuery(this).addClass('selected');
        var val_this = jQuery(this).attr('data-value');
        console.log(val_this);
        var datas = jQuery('form.variations_form').attr('data-product_variations');
        datas = JSON.parse(datas);
        datas.forEach(function(that,i){
            var val_color = that.attributes.attribute_pa_color;
            var val_variation_id = that.variation_id;
            var price_html = that.price_html;
            if(val_this == val_color){
                jQuery('.CRmain_price').addClass('hide');
                jQuery('#CRmove_price').html('');
                jQuery(price_html).appendTo('#CRmove_price');
                jQuery('.CRaddCart_btn_pd').attr('data-id',val_variation_id);
                jQuery('.CRaddCart_btn_pd').removeClass('hide');
                jQuery('#CRquickView_box .CRcustom_meta_pd').removeClass('hide_btn');
                //jQuery('.woocommerce-variation-add-to-cart .variation_id').val(val_variation_id);
                document.getElementById('pa_color').value=val_this;
            }
        })
    });
    jQuery(document).delegate(".reset_variations","click",function(event){
        jQuery('.CRitem_attr').removeClass('active');
        jQuery('.CRcustom_sl_attr').html('');
        jQuery('#CRmove_price').html('');
        jQuery('.CRmain_price').removeClass('hide');
    });
	jQuery('.CRbtn_open_fb').click(function() {
		FB.CustomerChat.show(true)
	});
    jQuery('#CRinfo_products_page').each(function () {
        read_more();
    });
} );
function CRStepPay(){
    jQuery('#enb_stepTwo').val(1);
    jQuery('html,body').animate({scrollTop: jQuery("#CRcheckout_form").offset().top-200}, 'slow');
    jQuery('.woocommerce-NoticeGroup-checkout ul.woocommerce-error').remove();
    jQuery('.checkout-step-info').addClass('hide');
    jQuery('.checkout-step-pay').addClass('active');
    CRstepOne = false;
}
function CRStepInfo() {
    jQuery('#enb_stepTwo').val(0);
    jQuery('html,body').animate({scrollTop: jQuery("#CRcheckout_form").offset().top-200}, 'slow');
    jQuery('.woocommerce-NoticeGroup-checkout ul.woocommerce-error').remove();
    jQuery('.checkout-step-info').removeClass('hide');
    jQuery('.checkout-step-pay').removeClass('active');
    CRstepOne = false;
}
function InitSlider(params) {
    var self = this;

    this.isInit = false;
    this.responseSize = params.responseSize;
    this.selector = params.selector;
    this.options = params.options

    this.init = function () {
        if (self.responseSize) {
            if (window.innerWidth < self.responseSize) {
                self.slider();
            }

            jQuery(window).resize(function () {
                if (window.innerWidth < self.responseSize) {
                    self.slider();
                } else {
                    self.destroy();
                }
            });
        } else {
            self.slider();
        }
    };

    this.slider = function () {
        if (!self.isInit && jQuery(self.selector).length) {
            self.isInit = true;
            jQuery(self.selector).not('.slick-initialized').slick(self.options);
        }
    };

    this.destroy = function () {
        if (self.isInit) {
            self.isInit = false;

            jQuery(self.selector).slick('unslick');
        }
    };

    this.refresh = function () {
        let selector = self.selector + '.slick-initialized';

        jQuery(selector).each(function (index, item) {
            item.slick.refresh();
        });
    };

    return this;
}
function increaseValue(id) {
    var value = parseInt(document.getElementById(id).value, 10);
    value = isNaN(value) ? 0 : value;
    value++;
    document.getElementById(id).value = value;
}
function decreaseValue(id) {
    var value = parseInt(document.getElementById(id).value, 10);
    value = isNaN(value) ? 0 : value;
    value < 1 ? (value = 1) : "";
    value--;
    document.getElementById(id).value = value;
}
function HandleVariationproduct() {
    var self = this;

    this.init = function () {
        var oldValue = '';

        jQuery(document).on('focus', '.group_choose_pd input', function () {
            oldValue = jQuery(this).closest('form').find('input:checked').val();
        });

        jQuery(document).on('change', '.group_choose_pd input', function () {
            self.setValueInput(oldValue, jQuery(this).val());
        });
    }

    this.setValueInput = function (oldValue, newValue) {
        var arrayId = jQuery('#input_variations_pd').attr('data-arr-id') || '',
            idCheck = jQuery('#input_variations_pd').attr('data-id-check') || '';

        arrayId = arrayId ? arrayId.split(',') : [];

        arrayId = arrayId.filter(function (item) {
            return item !== oldValue;
        });

        arrayId = arrayId.join(',');

        jQuery('#input_variations_pd').attr('data-arr-id', arrayId);

        idCheck = idCheck ? idCheck.split(',') : [];

        idCheck = idCheck.filter(function (item) {
            return item !== oldValue;
        });

        idCheck.push(newValue);

        idCheck = idCheck.join(',');

        jQuery('#input_variations_pd').attr('data-id-check', idCheck);
    }

    return this;
}
function CRslider_gal_pd() {
    jQuery('.slider_cont_pd.active_slider .slider_for').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        fade: true,
        dots: false,
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
        asNavFor: '.slider_cont_pd .slider_nav',
    });
    jQuery('.slider_cont_pd.active_slider .slider_nav').slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        asNavFor: '.slider_cont_pd .slider_for',
        prevArrow:"<div class='customm_arrow left'><i class='fa fa-angle-up' aria-hidden='true'></i></div>",
        nextArrow:"<div class='customm_arrow right'><i class='fa fa-angle-down' aria-hidden='true'></i></div>",
        vertical: false,
        focusOnSelect: true,
        dots: false,
        arrows: false,
        autoplay: false,
        infinite: false,
        responsive: [
            {
                breakpoint: 992,
                settings: {
                    // slidesToShow: 3,
                }
            },
        ]
    });
}
function CRrunvariationPd() {
    var val = jQuery(this).attr('data-val');
    var check = '.swatch-'+val;
    jQuery('.CRmain_price').addClass('hide');
    jQuery('#CRmove_price').html('');
    jQuery('.CRcustom_meta_pd .single_variation_wrap .woocommerce-variation-price .price').appendTo('#CRmove_price');
    jQuery('.CRaddCart_btn_pd').attr('data-id',jQuery('.single_variation_wrap .variation_id').val());
    jQuery('.CRaddCart_btn_pd').removeClass('hide');
}
function read_more() {
    var readmore = jQuery('.CRread_more');
    jQuery('.new_des_tax').each(function(i){
        var commentheight = jQuery(this).height();
        var scrollcommentheight =  jQuery('.new_des_tax')[i].scrollHeight;
        console.log(commentheight +'-'+scrollcommentheight);
        if (commentheight == scrollcommentheight){
            jQuery('.new_des_tax').addClass('hide');
            jQuery(this).siblings(readmore).hide();
        }
        else {
            jQuery(this).siblings(readmore).html("Đọc thêm <i class='fa fa-caret-down'></i>");
        }
    });
    readmore.on('click', function() {
        var $this = jQuery(this);

        $this.siblings('.new_des_tax').toggleClass('active');

        if ($this.siblings('.new_des_tax').text().length < 230) {
            $this.html("Đọc thêm <i class='fa fa-caret-down'></i>");
        }
        if (jQuery('.new_des_tax').hasClass('active')) {
            $this.html("Thu gọn <i class='fa fa-caret-up'></i>");
        } else {
            $this.html("Đọc thêm <i class='fa fa-caret-down'></i>");
        }
    });
};

function show_search_form(){
    // jQuery('.main_menu').append('<li>\
    //     <a href="" class="search_open"><i class="fa fa-search" aria-hidden="true"></i></a>\
    //     <form role="search" method="get" id="searchform" class="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">\
    //         <input type="text" value="<?php echo get_search_query(); ?>" name="s" id="s" placeholder="<?php echo (ICL_LANGUAGE_CODE=='vi') ? 'Nhập ở đây':'Sign in here';?>" />\
    //         <button type="submit" id="searchsubmit">Tìm kiếm</button>\
    //     </form>\
    //     </li>') 
        jQuery( document ).delegate( ".search_open", "click", function(event) { 

        jQuery('#searchform1').addClass('show');
        //e.stopPropagation(); 
        return false;
    })
    $(document).on("click", function(e) {
        //console.log($(e.target).is("#searchform1 input"));
        if ($(e.target).is("#searchform1 input") === false) {
             //e.stopPropagation(); 
          $("#searchform1").removeClass("show");
        }
    });
    // jQuery('#searchform1').clic(function(event) {
        
    //         event.stopPropagation();
    //         console.log('xxxx');
    //       $("#searchform1").removeClass("show");
          
    //   });
     
    jQuery('.bg_close,.button_close').click(function () {
        jQuery('.wrap_search_popup').removeClass('show');
    })


        jQuery(".search_product .CRitem_product").slice(0, 6).show();
        jQuery(".load_more_product").on("click", function(e){
            e.preventDefault();
            jQuery(".search_product .CRitem_product:hidden").slice(0, 6 ).slideDown();
            if(jQuery(".search_product .CRitem_product:hidden").length == 0) {
                jQuery(".load_more_product").remove();
            }
            console.log("debug news")
        });
        
    
}
