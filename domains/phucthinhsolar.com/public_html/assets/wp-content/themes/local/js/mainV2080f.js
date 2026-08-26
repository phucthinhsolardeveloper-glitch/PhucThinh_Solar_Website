jQuery(document).ready(function ($) {
    if(jQuery('#inf_new_banner_home').length){
        jQuery('#inf_new_banner_home .wrap').slick({
            infinite: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            // adaptiveHeight: true,
            autoplay: true,
            autoplaySpeed: 3000,
            speed: 1000,
            fade: true,
            arrows: true,
            prevArrow:"<div class='inf_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
            nextArrow:"<div class='inf_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
            dots: false,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            responsive: [
                {
                    breakpoint: 767,
                    settings: {
                        dots: !0,
                        arrows: !1,
                    }
                },
            ]
        });
    }
    if(jQuery('#inf_single_project .right .content').length){
        jQuery('#inf_single_project .gallery_for').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: false,
            fade: true,
            asNavFor: '#inf_single_project .gallery_nav',
            speed: 1000,
        });
        jQuery('#inf_single_project .gallery_nav').slick({
            slidesToShow: 4,
            slidesToScroll: 1,
            asNavFor: '#inf_single_project .gallery_for',
            dots: false,
            arrows: true,
            prevArrow:"<div class='inf_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
            nextArrow:"<div class='inf_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
            speed: 1000,
            focusOnSelect: true
        });
    }
    if(jQuery('#inf_list_news_related').length){
        jQuery('#inf_list_news_related.active').slick({
            infinite: true,
            slidesToShow: 3,
            slidesToScroll: 1,
            adaptiveHeight: true,
            speed: 1000,
            arrows: true,
            prevArrow:"<div class='inf_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
            nextArrow:"<div class='inf_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
            dots: false,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 2,
                        arrows:false,
                        dots: true,
                    }
                },
                {
                    breakpoint: 454,
                    settings: {
                        slidesToShow: 1,
                        arrows:false,
                        dots: true,
                    }
                },
            ]
        });
    }
    if(jQuery('#inf_list_video_related').length){
        jQuery('#inf_list_video_related.active').slick({
            infinite: true,
            slidesToShow: 3,
            slidesToScroll: 1,
            adaptiveHeight: true,
            speed: 1000,
            arrows: true,
            prevArrow:"<div class='inf_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
            nextArrow:"<div class='inf_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
            dots: false,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 2,
                        arrows:false,
                        dots: true,
                    }
                },
                {
                    breakpoint: 454,
                    settings: {
                        slidesToShow: 1,
                        arrows:false,
                        dots: true,
                    }
                },
            ]
        });
    }
    if(jQuery('#inf_list_partner').length){
        jQuery('#inf_list_partner.active_slider').slick({
            infinite: true,
            slidesToShow: 4,
            slidesToScroll: 1,
            adaptiveHeight: true,
            speed: 1000,
            arrows: true,
            prevArrow:"<div class='inf_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
            nextArrow:"<div class='inf_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
            dots: false,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 3,
                        arrows:false,
                        dots: true,
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 2,
                        arrows:false,
                        dots: true,
                    }
                },
            ]
        });
    }
    if(jQuery('#inf_list_certification_intro').length){
        jQuery('#inf_list_certification_intro.active_slider').slick({
            infinite: true,
            slidesToShow: 3,
            slidesToScroll: 1,
            adaptiveHeight: true,
            speed: 1000,
            arrows: true,
            prevArrow:"<div class='inf_arrow left'><i class='fa fa-angle-left' aria-hidden='true'></i></div>",
            nextArrow:"<div class='inf_arrow right'><i class='fa fa-angle-right' aria-hidden='true'></i></div>",
            dots: false,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        arrows:false,
                        dots: true,
                    }
                },
                {
                    breakpoint: 454,
                    settings: {
                        slidesToShow: 1,
                        arrows:false,
                        dots: true,
                    }
                },
            ]
        });
    }
    if(jQuery('.inf_list_circle_content').length){
        jQuery('.inf_list_circle_content').slick({
            dots: true,
            arrows: false,
            infinite: true,
            speed: 1000,
            fade: true,
            adaptiveHeight: true,
            cssEase: 'linear'
        });
    };
    jQuery(".inf_play_video").fancybox({
        maxWidth    : 800,
        maxHeight   : 600,
        fitToView   : false,
        width       : '60%',
        height      : '60%',
        autoSize    : false,
        closeClick  : false,
        openEffect  : 'none',
        closeEffect : 'none'
    });
//    nvl code
    jQuery('.content_des_cat .show_more').click(function (e) {
        e.preventDefault()
        jQuery(this).parent().addClass('show')
        jQuery(this).addClass('show')
    })
    jQuery('.content_des_cat .show_less').click(function (e) {
        e.preventDefault()
        jQuery(this).parent().removeClass('show')
        jQuery(this).removeClass('show')

    })
    jQuery('.aside-filter .aside-title-f').click(function(e){
        e.preventDefault()
        jQuery(this).toggleClass('active');
        jQuery(this).parents('.aside-filter').find('.aside-hidden-mobile').stop().slideToggle();
    })
    AOS.init();
    jQuery('.filter .open_fil').click(function () {
        jQuery('.inner_filter').toggleClass('active')
        jQuery(this).parent().toggleClass('active')



    })
});
