"use strict";
var $ = jQuery
$(document).ready(function () {
    // var e = $(".home-6 .uk-slider");
    // UIkit.slider(e), e.on("beforeitemhide", function () {
    //     $(".home-6 .uk-slider-items li.uk-active .caption").css({
    //         opacity: 0
    //     })
    // }), e.on("itemhidden", function () {
    //     $(".home-6 .uk-slider-items li.uk-active .caption").css({
    //         opacity: 1
    //     })
    // }), $.fancybox.defaults.parentEl = "main", switchImageIndex(), awardSwitchSlide(), memberSwitch(), changeContactMapSrc(), setupImgBottomList(), clickViewMoreLoadAjax(), changeViewProduct(), setWidthLineInSlider(), $(window).resize(function () {
    //     setupImgBottomList(), setWidthLineInSlider()
    // }), $(".product-detail-attributes .tab-content").hide(), $("#newsSideNav").append($(".news-sidenav-detail")), $("#projectSidebar").append($(".project-sidenav")), UIkit.slider("[uk-slider]", {
    //     autoplay: !1,
    //     autoplayInterval: 5e3
    // }), UIkit.slider(".solution-7 [uk-slider]", {
    //     autoplay: !1,
    //     autoplayInterval: 5e3
    // });
    // var i = document.querySelector(".subnav-wrapper ul");
    // new MenuSpy(i, {
    //     threshold: 50,
    //     callback: function(e) {
    //         "giai-phap-doanh-nghiep-6" !== e.target.id && "giai-phap-gia-dinh-6" !== e.target.id || UIkit.slider(".solution-7 [uk-slider]").startAutoplay()
    //     }
    // });
    $(".project-zone-nav .zone-child").hasClass("active") && $(".project-zone-nav .zone-all").removeClass("active"), $(".product-change-view a").eq(0).trigger("click"), $(".gallery-list a[data-fancybox]").fancybox({
        thumbs: {
            autoStart: !0,
            axis: "x"
        }
    }), $(window).width() < 1025 && UIkit.sticky(".news-subnav", {
        offset: 40
    })

    sliderHome()

    run_utilities()
});



function run_utilities() {
    if (jQuery('.round').length && jQuery(window).width() > 767) {
        // init

        let countItem = jQuery('.item-utility').length
        // console.log(countItem, 260/countItem)
        let rangeDeg = 240 / countItem,
            deg = 25
        jQuery('.item-utility').each(function () {
            jQuery(this).css('transform', 'rotate(' + deg + 'deg)')
            jQuery(this).find('img').css('transform', 'rotate(-' + deg + 'deg)')
            deg += rangeDeg
            if (deg > 145 && deg < 200) {
                deg += 120
            }
        })

        //action
        let active = jQuery('.icon-utility.active').parents('.item-utility').index()
        jQuery(document).on('click', '.icon-utility', function () {
            jQuery('.icon-utility').removeClass('active')
            jQuery(this).addClass('active')
            // rotate
            let index = jQuery(this).parents('.item-utility').index()
            deg = 25
            let rotateTranform = 0
            for (let i = 0; i < countItem; i++) {
                let changeIndex = active - (index - i),
                    nth = i + 1
                rotateTranform = changeIndex * rangeDeg + deg
                if (rotateTranform > 145) {
                    rotateTranform = rotateTranform + 120
                }
                if (rotateTranform < -60) {
                    rotateTranform = rotateTranform - 120
                }
                // else{
                //     rotateTranform = rotateTranform - 120
                // }
                jQuery('.item-utility:nth-child(' + nth + ')').css('transform', 'rotate(' + rotateTranform + 'deg)')
                jQuery('.item-utility:nth-child(' + nth + ') img').css('transform', 'rotate(' + (360 - rotateTranform) + 'deg)')
            }

            // change data
            let title = jQuery(this).data('title'),
                content = jQuery(this).data('content'),
                link = jQuery(this).data('link'),
                stringHtml = ''
            stringHtml = stringHtml + '<div class="inner text-center">'
            if (title.length > 0) {
                stringHtml = stringHtml + '<h3 class="title m-bottom-10">' + title + '</h3>'
            }
            if (content.length > 0) {
                stringHtml = stringHtml + '<div class="content fz-20">' + content + '</div>'
            }
            if (link.length > 0) {
                stringHtml = stringHtml + link
            }
            stringHtml = stringHtml + '</div>'
            jQuery('.round .group_content').html(stringHtml)
        })
    }

    jQuery('.home-6 .tab-switcher li').on('click', function () {
        let id = jQuery(this).index()
        // console.log(id);
        jQuery('.projects-switcher .tab-content').eq(id).find('.slider_6_slick').slick('slickNext')
    })
}

function sliderHome(){
    var iconSlider = new Swiper(".home-7 .icon-slider .swiper-container", {
            slidesPerView: 'auto',
            slideToClickedSlide: true,
            centerInsufficientSlides: true,
            autoplay: false,
            speed: 0,
            loop: 0,
            spaceBetween: 17,
            spaceBetween: 5,
            breakpoints: {
                768: {
                    spaceBetween: 17,
                },
            }
        }),
    
        tabcat = new Swiper(".swiper-container.sw-tab-cat", {
            slidesPerView: 2,
            spaceBetween: 5,
            centerInsufficientSlides: true,
            slideToClickedSlide: true,
            breakpoints: {
                768: {
                    slidesPerView: 2,
                    spaceBetween: 16,
                    centerInsufficientSlides: true,
                },
                1024: {
                    slidesPerView: 5,
                    spaceBetween: 16,
                }
            }
        }),
        home_5 = new Swiper(".home-5 .swiper-container", {
            slidesPerView: 2,
            autoplay: {
                delay: 5e3
            },
            loop: 1,
            navigation: {
                prevEl: ".home-5 .swiper-button-prev",
                nextEl: ".home-5 .swiper-button-next"
            },
            breakpoints: {
                768: {
                    slidesPerView: 3,
                    spaceBetween: 20
                },
                1025: {
                    slidesPerView: 4
                }
            }
        }),
        home_6 = jQuery('.slider_6_slick').slick({
            infinite: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            speed: 1000,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            autoplay: true,
            arrows: true,
            // dots:true,
            adaptiveHeight: true,
            prevArrow: "<div class='customm_arrow left'><i class='fa fa-angle-left'></i></div>",
            nextArrow: "<div class='customm_arrow right'><i class='fa fa-angle-right'></i></div>",
            autoplaySpeed: 1500,
            // responsive: [
            //     {
            //         breakpoint: 992,
            //         settings: {
            //             slidesToShow: 1,
            //             slidesToScroll: 1,
            //             arrows:false,
            //             adaptiveHeight: true
            //         }
            //     },
            //     {
            //         breakpoint: 768,
            //         settings: {
            //             slidesToShow: 1,
            //             slidesToScroll: 1,
            //             arrows:false,
            //             adaptiveHeight: true
            //         }
            //     }
            // ]
        }),
        // home_6 = new Swiper(".home-6 .swiper-container", {
        //     slidesPerView: 1,
        //     autoplay: {
        //         delay: 5e3
        //     },
        //     loop: true,
        //     centeredSlides: true,
        //     centeredSlidesBounds: true,
        //     navigation: {
        //         prevEl: ".home-6 .swiper-button-prev",
        //         nextEl: ".home-6 .swiper-button-next"
        //     },
        //     breakpoints: {
        //         768: {
        //             spaceBetween: 13
        //         },
        //     }
        // }),
        processSlider = new Swiper(".home-7 .process-slider .swiper-container", {
            slidesPerView: 1,
            spaceBetween: 30,
            autoplay: {
                delay: 5e3
            },
            loop: 0,
            navigation: {
                prevEl: ".process-slider .swiper-prev",
                nextEl: ".process-slider .swiper-next"
            },
            thumbs: {
                swiper: iconSlider
            }
        }),
        news_list = new Swiper(".news-list .swiper-container", {
            slidesPerView: 3,
            spaceBetween: 20,
            autoplay: {
                delay: 5e3
            },
            loop: 1,
            navigation: {
                prevEl: ".news-list .swiper-button-prev",
                nextEl: ".news-list .swiper-button-next"
            },
        }),
        processSlider2 = new Swiper(".solution-6 .process-slider .swiper-container", {
            slidesPerView: 2,
            spaceBetween: 30,
            navigation: {
                prevEl: ".solution-6 .process-slider .swiper-prev",
                nextEl: ".solution-6 .process-slider .swiper-next"
            },
            breakpoints: {
                320: {
                    slidesPerView: 2
                },
                576: {
                    slidesPerView: 3
                },
                768: {
                    slidesPerView: 4
                },
                1025: {
                    slidesPerView: 5
                }
            }
        }),
        iconSlider2 = new Swiper(".development-4 .icon-slider .swiper-container", {
            slidesPerView: 1,
            spaceBetween: 30,
            autoplay: {
                delay: 5e3
            },
            loop: !0,
            breakpoints: {
                360: {
                    slidesPerView: 2
                },
                576: {
                    slidesPerView: 3
                },
                768: {
                    slidesPerView: 4
                },
                1025: {
                    slidesPerView: 5
                }
            }
        }),
        contentSlider = new Swiper(".development-4 .content-slider .swiper-container", {
            spaceBetween: 30,
            navigation: {
                prevEl: ".development-4 .content-slider .swiper-prev",
                nextEl: ".development-4 .content-slider .swiper-next"
            },
            thumbs: {
                swiper: iconSlider2
            }
        }),
        memberSlider = new Swiper(".development-2 .member-slider .swiper-container", {
            slidesPerView: 2,
            spaceBetween: 10,
            autoplay: {
                delay: 5e3
            },
            loop: !0,
            navigation: {
                prevEl: ".development-2 .member-slider .swiper-prev",
                nextEl: ".development-2 .member-slider .swiper-next"
            },
            breakpoints: {
                320: {
                    slidesPerView: 1
                },
                576: {
                    slidesPerView: 2
                },
                768: {
                    slidesPerView: 3
                },
                1025: {
                    slidesPerView: 4
                }
            }
        }),
        historySlider = new Swiper(".history-slider .swiper-container", {
            slidesPerView: 1,
            spaceBetween: 30,
            slideToClickedSlide: !0,
            navigation: {
                prevEl: ".history-slider .swiper-prev",
                nextEl: ".history-slider .swiper-next"
            },
            breakpoints: {
                1025: {
                    spaceBetween: 0,
                    slidesPerView: 3,
                    direction: "vertical",
                    height: 1e3
                }
            }
        }),
        productThumbnailSlider = new Swiper(".product-image-list .thumbnail-slide .swiper-container", {
            slidesPerView: 3,
            spaceBetween: 16,
            navigation: {
                prevEl: ".product-image-list .thumbnail-slide .swiper-prev",
                nextEl: ".product-image-list .thumbnail-slide .swiper-next"
            },
            breakpoints: {
                576: {
                    slidesPerView: 5,
                    direction: "vertical",
                    spaceBetween: 16,
                    height: 535
                }
            }
        }),
    
        productImageSlider = new Swiper(".product-image-list .image-slide .swiper-container", {
            slidesPerView: 1,
            spaceBetween: 16,
            thumbs: {
                swiper: productThumbnailSlider
            }
        });

    if(jQuery(window).width()<768){
        // if(jQuery(".home-7 .icon-slider .swiper-container").length){
        //     iconSlider.destroy()
        // }
    }
}

function setupImgBottomList() {
    var e = $(".img-list-wrapper .top-list").height(),
        i = $(".img-list-wrapper .top-list").width();
    $(".img-list-wrapper .bottom-list").height(e), $(".img-list-wrapper .bottom-list").width(i)
}

function switchImageIndex() {
    $(".img-list-wrapper .img-item a").click(function (e) {
        e.preventDefault();
        var i = $(this).data("index");
        $(".img-list-wrapper .img-item").removeClass("active"), $(this).parent().addClass("active"), $(".about-2 .content-wrapper").hide(), $(".about-2 .content-wrapper[data-index='" + i + "']").show()
    })
}

function awardSwitchSlide() {
    var e = UIkit.switcher(".about-3 .uk-subnav", {
        animation: "uk-animation-fade",
        swiping: !1,
        connect: ".about-3 .tabs-container"
    });
    new Swiper($(".award-slider .swiper-container"), {
        slidesPerView: 1,
        spaceBetween: 30,
        centeredSlides: !0,
        autoplay: {
            delay: 5e3
        },
        loop: false,
        pagination: {
            el: ".swiper-pagination",
            type: "bullets",
            clickable: !0
        },
        initialSlide: 1,
        breakpoints: {
            576: {
                slidesPerView: 2,
                spaceBetween: 0
            },
            1025: {
                slidesPerView: 3
            }
        }
    });
    $(".about-3 .tab-content").hide(), $(".about-3 .uk-slider-items li").click(function (i) {
        i.preventDefault(), $(".about-3 .uk-slider-items li a").removeClass("active"), $(this).children("a").addClass("active");
        var t = $(this).index();
        e.show(t)
    }), $(".about-3 .uk-slider-items li").eq(0).click()
}

function numberWithDots(e) {
    return e.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")
}

function initSlider() {
    $(".square-slider").slider({
        range: "min",
        min: 16,
        slide: function (e, i) {
            $("#square_unit").text(i.value)
        },
        change: function (e, i) {
            $("#hdSquare").text(i.value), $("#hdSquare").val(i.value)
        }
    }), $(".bill-slider").slider({
        range: "min",
        max: 1e7,
        step: 1e3,
        slide: function (e, i) {
            $("#bill_unit").val(numberWithDots(i.value))
        },
        change: function (e, i) {
            $("#bill_unit").val(numberWithDots(i.value))
        }
    }), $(".rate-slider").slider({
        range: "min",
        max: 100,
        step: .1,
        slide: function (e, i) {
            $("#rate_unit").val(numberWithDots(i.value))
        },
        change: function (e, i) {
            $("#rate_unit").val(numberWithDots(i.value))
        }
    }), $("#square_unit").text($(".square-slider").slider("value")), $("#bill_unit").val($(".bill-slider").slider("value")), $("#bill_unit").on("change", function () {
        $(".bill-slider").slider("value", $(this).val())
    }), $("#rate_unit").val($(".rate-slider").slider("value")), $("#rate_unit").on("change", function () {
        $(".rate-slider").slider("value", $(this).val())
    })
}

function memberSwitch() {
    $(".member-tabs .member-content").eq(0).addClass("show"), $(".member-slider .item").eq(0).addClass("active"), $(".member-slider .item").click(function (e) {
        e.preventDefault(), $(".member-slider .item").removeClass("active"), $(this).addClass("active");
        var i = $(this).data("index");
        $(".member-tabs .member-content").removeClass("show"), $(".member-tabs .member-content[data-index='" + i + "']").addClass("show")
    })
}

function changeContactMapSrc() {
    $(".address-slider .item").click(function () {
        $(".address-slider .item").removeClass("active"), $(this).addClass("active");
        var e = $(this).data("src");
        $("#contactMap").attr("src", e)
        console.log($("#contactMap").attr("src", e))
    }), $(".address-slider .item").eq(0).trigger("click")
}

function clickViewMoreLoadAjax() {
    $("body").on("click", "a.ajaxpagerlink", function (e) {
        e.preventDefault();
        var i = $(this),
            t = $(this).attr("href");
        if (t.length) return $.ajax({
            url: t,
            data: {
                isajax: !0
            },
            success: function (e) {
                $(".ajaxresponse .ajaxresponsewrap").append($(e).find(".ajaxresponsewrap").html()), $(".ajaxresponse .ajaxresponsewrap").after($(e).find(".faq-view-more")), !1, i.parent().remove()
            }
        }), !1
    })
}

function addClassHeaderWhenScroll() {
    $(window).on("scroll", function () {
        $("header").toggleClass("minimal", $(window).scrollTop() > 0)
    })
}

function changeViewProduct() {
    $(".product-change-view a").click(function (e) {
        e.preventDefault(), $(".product-change-view li").removeClass("active"), $(this).parent().addClass("active");
        var i = $(this).data("view");
        $(".product-view").removeClass("show"), $(".product-view[data-view='" + i + "']").addClass("show")
    })
}

function setWidthLineInSlider() {
    if ($(".solution-6").length > 0) {
        var e = $(".process-slider .icon").eq(0).offset().left,
            i = $(".process-slider .swiper-container").width();
        $(".process-slider .line").css({
            width: i - 30 - e / 2
        })
    }
    if ($(".development-4").length > 0) {
        var t = $(".development-4 .icon").eq(0).offset().left,
            s = $(".development-4 .swiper-container").width();
        $(".development-4 .line").css({
            width: s - 30 - t / 2
        })
    }
}

$(".btn-view-more").click(function () {
    var button = $(this)
    setFieldForm(button)
})

function setFieldForm(button) {
    var par = button.prev('div').find('ul'),
        n = button.closest('div').find('p.title').text(),
        t = par.children('li:first').find('p').text(),
        w = par.children('li:nth-child(2)').find('p').text(),
        s = par.children('li:nth-child(3)').find('p').text(),
        p = par.children('li:last').find('p').text();

    $('#form-receive-quotation-modal').find('.solution').val(n)
    $('#form-receive-quotation-modal').find('.time').val(t)
    $('#form-receive-quotation-modal').find('.watt').val(w)
    $('#form-receive-quotation-modal').find('.source').val(s)
    $('#form-receive-quotation-modal').find('.price').val(p)


}

$('.cmsmasters_tabs ul.cmsmasters_tabs_list li a').on('click', function (t) {
    var tabs_parent = $(this).parents('.cmsmasters_tabs'),
        tabs = tabs_parent.find('.cmsmasters_tabs_wrap'),
        index = $(this).parents('li').index();


    tabs_parent.find('.cmsmasters_tabs_list > .current_tab').removeClass('current_tab');


    $(this).parents('li').addClass('current_tab');


    tabs.find('.cmsmasters_tab').not(':eq(' + index + ')').slideUp('fast', function () {
        $(this).removeClass('active_tab');
    });


    tabs.find('.cmsmasters_tab:eq(' + index + ')').slideDown('fast', function () {
        $(this).addClass('active_tab');
    });


    t.preventDefault();


    setTimeout(function () {
        jQuery('body').trigger('debouncedresize');
    }, 300);
});
//# sourceMappingURL=main.min.js.map