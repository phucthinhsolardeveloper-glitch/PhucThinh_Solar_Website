/* Validation form */
validateForm("validation-newsletter");
validateForm("validation-cart");
validateForm("validation-user");
validateForm("validation-contact");

/* Lazys */
GA_FRAMEWORK.Lazys = function(){
    if(isExist($(".lazy")))
    {
        var lazyLoadInstance = new LazyLoad({
            elements_selector: ".lazy"
        });
    }
};
if(isExist($(".danhmucct")))
{
    $('.plus-nClick1').click(function(e) {
      $(this).parents('.level0').toggleClass('opened');
      $(this).parents('.level0').children('ul').slideToggle(200);
    });
    $('.plus-nClick2').click(function(e) {
      $(this).parents('.level1').toggleClass('opened');
      $(this).parents('.level1').children('ul').slideToggle(200);
    });
    $('.plus-nClick3').click(function(e) {
        $(this).parents('.level2').toggleClass('opened');
        $(this).parents('.level2').children('ul').slideToggle(200);
    });
}
// GA_FRAMEWORK.changeSortby = function(){

//         $('body').on('click', '.change-sortby', function(event) {
//             event.preventDefault();
//             var o = $(this);
//             var h = o.data('href');
//             var s = o.data('sort');
//             $('.change-sortby').removeClass('active');
//             o.addClass('active');
//             $('.show-sort-by').html(o.text() + ' <i class="fa fa-angle-down"></i>');
  
//             var href = $('input[name=href]').val();
      
           
//             pushState({sortby: s},'',h + '?sortby='+s);
//             doSearchProduct({'href':href,  'alias':h,  'sortby': s,'p':1});
//         });
//         $('body').on('click', 'a.page-link', function(event) {
//             event.preventDefault();
//             var o = $(this);
//             var l = o.attr('href');
//             var h = l.split("?");
//             var options = {};
//             var href = $('input[name=href]').val();
//             options['href'] = href;
//             $.each(h,function(i,v){
//                 if(i!=0){
//                     var f = v.split("=");
//                     options[f[0]] = f[1];
//                 }
//             })
//             pushState(options,'',l);
//             console.log(options);

//             doSearchProduct(options);
//         });

    
// }
/* Load name input file */
GA_FRAMEWORK.loadNameInputFile = function(){
    if(isExist($(".custom-file input[type=file]")))
    {
        $('body').on('change','.custom-file input[type=file]', function(){
            var fileName = $(this).val();
            fileName = fileName.substr(fileName.lastIndexOf('\\') + 1, fileName.length);
            $(this).siblings('label').html(fileName);
        });
    }
};

/* Back to top */
GA_FRAMEWORK.GoTop = function(){
    $(window).scroll(function(){
        if(!$('.scrollToTop').length) $("body").append('<div class="scrollToTop"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="64px" height="64px" viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve"><g><polyline fill="none" stroke="#000000" stroke-width="2" stroke-linejoin="bevel" stroke-miterlimit="10" points="15,40 32,23 49,40 	"></polyline></g></svg></div>');
        if($(this).scrollTop() > 100) $('.scrollToTop').fadeIn();
        else $('.scrollToTop').fadeOut();
    });

    $('body').on("click",".scrollToTop",function() {
        $('html, body').animate({scrollTop : 0},800);
        return false; 
    });
};

/* Alt images */
GA_FRAMEWORK.AltImg = function(){
    $('img').each(function(index, element) {
        if(!$(this).attr('alt') || $(this).attr('alt')=='')
        {
            $(this).attr('alt',WEBSITE_NAME);
        }
    });
};

/* Menu */
GA_FRAMEWORK.Menu = function(){
    /* Menu remove empty ul */
    if(isExist($('.menu')))
    {
        $('.menu ul li a').each(function(){
            $this = $(this);

            if(!isExist($this.next('ul').find('li')))
            {
                $this.next('ul').remove();
                $this.removeClass('has-child');
            }
        });
    }

    /* Menu fixed */
    if(isExist($(".header")))
    {
        var headerHeight = $(".header").height();
        $(window).scroll(function(){
            if($(window).scrollTop() >= headerHeight)
            {
                $(".header").addClass('fixed');
            }
            else
            {
                $(".header").removeClass('fixed');
            }
        });
         var prevScrollpos = window.pageYOffset;
        window.onscroll = function() {
        var currentScrollPos = Math.max(0, window.pageYOffset);
        /* Gần đầu trang: luôn hiện header, không xét hướng cuộn (tránh bị ẩn
           oan bởi các sự kiện scroll ảo do thanh địa chỉ Android tự ẩn/hiện).
           Xa hơn: chỉ đổi trạng thái khi cuộn tích lũy vượt ngưỡng 8px, để
           không giật liên tục theo từng rung nhỏ khi vuốt chạm (đà/đàn hồi). */
        if (currentScrollPos <= headerHeight) {
            document.getElementById("headerscroll").style.top = "0";
            prevScrollpos = currentScrollPos;
            return;
        }
        var delta = currentScrollPos - prevScrollpos;
        if (Math.abs(delta) > 8) {
            document.getElementById("headerscroll").style.top = (delta < 0) ? "0" : (-headerHeight - 8) + "px";
            prevScrollpos = currentScrollPos;
        }
        }
    }

    /* Mmenu */
    if(isExist($("nav#menu")))
    {
        $('nav#menu').mmenu({
            // "extensions": ["border-full", "position-left", "position-front"],
             navbar: {
                title: "Phúc Thịnh Solar"
            }
        });
        /* mmenu tự đo chiều cao trang lúc khởi tạo (lúc DOM vừa sẵn sàng)
           để canh wrapper .mm-page/.wap-all-page, nhưng lúc đó ảnh/khối cuối
           trang (footer, badge...) có thể chưa load xong nên đo hụt — phần
           dưới cùng bị overflow:clip cắt mất, kéo theo dòng "© ..." bị đè
           bởi cụm nút liên hệ. Bắn lại 1 sự kiện resize sau khi trang load
           xong để mmenu đo lại đúng, không cần đổi gì bên mmenu.js. */
        $(window).on('load', function(){
            setTimeout(function(){ window.dispatchEvent(new Event('resize')); }, 300);
        });
    }
};

/* Tools */
GA_FRAMEWORK.Tools = function(){
    if(isExist($(".toolbar")))
    {
        $(".footer").css({marginBottom:$(".toolbar").innerHeight()});
    }
};

/* Popup */
GA_FRAMEWORK.Popup = function(){
    var popup = $("#popup");
    if(!isExist(popup)) return;

    var campaignKey = popup.attr('data-campaign-key') || 'current';
    try
    {
        if(window.sessionStorage.getItem('pts-popup-campaign') === campaignKey) return;
        window.sessionStorage.setItem('pts-popup-campaign', campaignKey);
    }
    catch(error) {}

    popup.modal('show');
};

/* Wow */
GA_FRAMEWORK.Wows = function(){
    new WOW().init();
};

/* Pagings */
GA_FRAMEWORK.Pagings = function(){
    /* Products */
    if(isExist($(".paging-product")))
    {
        loadPaging("api/product.php?perpage=12",'.paging-product');
    }

    /* Categories */
    if(isExist($(".paging-product-category")))
    {
        $(".paging-product-category").each(function(){
            var list = $(this).data("list");
            loadPaging("api/product2.php?perpage=16&idList="+list,'.paging-product-category');
        })
        /* Rail chỉ có một vùng kết quả AJAX nên không cần UIkit switcher.
           Quản lý active state trực tiếp, tránh stopImmediatePropagation của
           UIkit làm tab đổi màu nhưng nội dung vẫn thuộc danh mục trước. */
        $(".get-product-category").on('click.ptsProductCategory', function(event) {
            event.preventDefault();
            var trigger = $(this);
            var list = trigger.data("list");
            trigger.attr('aria-pressed', 'true').closest('li').addClass('uk-active')
                .siblings().removeClass('uk-active').find('.get-product-category').attr('aria-pressed', 'false');
            loadPaging("api/product2.php?perpage=16&idList="+list,'.paging-product-category');
        });
    }

    if(isExist($(".paging-prolist")))
    {
        $(".paging-prolist").each(function(){
            if($(this).attr("data-server-rendered") === "1") return;
            var list = $(this).data("list");
            loadPaging("api/product3.php?perpage=4&idList="+list,'.paging-prolist-'+list);
        })
    }

    /* Nhảy nhanh tới danh mục trên /san-pham/: trang có <base href> trỏ về
       domain gốc nên href="#id" thường bị trình duyệt điều hướng về trang chủ
       thay vì cuộn trong trang hiện tại. Tự bắt sự kiện và cuộn bằng JS. */
    if(isExist($(".pts-catalog-jump")))
    {
        $(document).on('click', '.pts-catalog-jump a', function(event) {
            var hash = $(this).attr('href');
            if(!hash || hash.charAt(0) !== '#') return;
            var target = document.getElementById(hash.slice(1));
            if(!target) return;
            event.preventDefault();
            target.scrollIntoView({behavior: 'smooth', block: 'start'});
            /* location.pathname (không phải "hash" trần) vì <base href> của trang
               trỏ về domain gốc, pushState cũng resolve theo base đó. */
            if(history.pushState) history.pushState(null, '', location.pathname + hash);
        });
    }

    /* Ảnh nền Hero trang chủ (layout 1): nhiều ảnh (Admin > Ảnh nền Hero trang
       chủ) thì tự đổi vòng, CSS transition (theme-v2.css .pts-hero-photo-layer)
       lo phần crossfade mượt - JS chỉ đổi class is-active theo chu kỳ. Nút mũi
       tên/chấm (.pts-hero-photo-nav, nằm cùng cấp .pts-hero-photo-layers trong
       section cha) cho bấm tay - bấm xong thì reset lại đồng hồ tự động để
       không bị nhảy ảnh ngay sau khi vừa bấm (yêu cầu 2026-09-11). */
    $(".pts-hero-photo-layers").each(function() {
        var layers = $(this).find(".pts-hero-photo-layer");
        if(layers.length < 2) return;
        var interval = parseInt($(this).data("pts-hero-cycle"), 10) || 6000;
        var current = 0;
        var nav = $(this).siblings(".pts-hero-photo-nav");
        var dots = nav.find(".pts-hero-photo-dot");
        var timer = null;

        function goTo(index) {
            index = ((index % layers.length) + layers.length) % layers.length;
            if(index === current) return;
            layers.eq(current).removeClass("is-active");
            dots.eq(current).removeClass("is-active");
            current = index;
            layers.eq(current).addClass("is-active");
            dots.eq(current).addClass("is-active");
        }

        function restartTimer() {
            if(timer) clearInterval(timer);
            timer = setInterval(function() { goTo(current + 1); }, interval);
        }

        nav.find(".pts-hero-photo-nav-prev").on("click", function() { goTo(current - 1); restartTimer(); });
        nav.find(".pts-hero-photo-nav-next").on("click", function() { goTo(current + 1); restartTimer(); });
        dots.on("click", function() { goTo($(this).data("pts-hero-index")); restartTimer(); });

        restartTimer();
    });

    /* Nút chuyển thiết kế hero (yêu cầu 2026-09-11, cập nhật 2026-09-12):
       3 bản layout vẫn render sẵn trong DOM (.pts-hero-layout-panel,
       ẩn/hiện bằng thuộc tính "hidden"), JS chỉ đổi bản nào đang hiện + nhớ
       lựa chọn vào localStorage để hai layout cũ không bị xoá. Layout 3 là
       mặc định public và không hiện nút đổi; layout 1-2 vẫn có nút để sẵn
       cho màn quản lý layout trong tương lai. */
    var heroLayoutWrap = $(".pts-hero-layouts");
    if(heroLayoutWrap.length)
    {
        /* Đổi key khi chốt layout 3 để preference thử nghiệm cũ (1/2) không
           khiến một số trình duyệt tiếp tục thấy nhầm Hero legacy sau deploy. */
        var heroLayoutKey = "ptsHeroLayout20260912";
        var heroLayoutToggleBtn = heroLayoutWrap.find(".pts-hero-layout-toggle");
        var heroLayoutNextMap = { "1": "2", "2": "3", "3": "1" };
        /* Nút chỉ có icon, không chữ (yêu cầu 2026-09-11) - vẫn đổi
           title/aria-label theo bản đang hiện để hỗ trợ trình đọc màn hình. */
        var heroLayoutTitles = { "1": "Xem giao diện khác", "2": "Xem carousel mới", "3": "Quay lại giao diện gốc" };

        function applyHeroLayout(target) {
            var panels = heroLayoutWrap.find(".pts-hero-layout-panel");
            var availableTargets = [];
            panels.each(function() { availableTargets.push(String($(this).data("pts-hero-layout"))); });
            if(availableTargets.indexOf(String(target)) === -1) target = availableTargets[0];
            panels.each(function() {
                var isTarget = String($(this).data("pts-hero-layout")) === String(target);
                this.hidden = !isTarget;
                $(this).toggleClass("is-active", isTarget);
            });
            var title = heroLayoutTitles[target] || heroLayoutTitles["1"];
            heroLayoutToggleBtn.attr({ "title": title, "aria-label": title });
            var nextTarget = heroLayoutNextMap[target] || "1";
            if(availableTargets.indexOf(nextTarget) === -1) nextTarget = availableTargets[(availableTargets.indexOf(String(target)) + 1) % availableTargets.length];
            heroLayoutToggleBtn.data("pts-hero-layout-target", nextTarget);
            heroLayoutToggleBtn.prop("hidden", String(target) === "3");
        }

        /* Key mới bảo đảm mọi trình duyệt bắt đầu ở layout 3; vẫn có thể lưu
           layout 1-2 khi cơ chế quản lý layout được nối vào trong tương lai. */
        var heroLayoutSaved = null;
        try { heroLayoutSaved = window.localStorage.getItem(heroLayoutKey); } catch(e) {}
        applyHeroLayout(heroLayoutSaved || "3");

        heroLayoutToggleBtn.on("click", function() {
            var target = String($(this).data("pts-hero-layout-target") || "2");
            applyHeroLayout(target);
            try { window.localStorage.setItem(heroLayoutKey, target); } catch(e) {}
        });

        /* CTA "NHẬN TƯ VẤN NGAY" ở layout 2 trỏ tới #pts-hero2-form (form nổi
           bên phải) - trang có <base href> về domain gốc nên href="#id" trần
           không tự cuộn trong trang (như .pts-catalog-jump ở trên), phải tự
           bắt sự kiện. */
        heroLayoutWrap.on("click", 'a[href="#pts-hero2-form"]', function(event) {
            var target = document.getElementById("pts-hero2-form");
            if(!target) return;
            event.preventDefault();
            target.scrollIntoView({behavior: "smooth", block: "center"});
            var firstField = target.querySelector('input[name="dataContact[fullname]"]');
            if(firstField) setTimeout(function() { firstField.focus(); }, 400);
        });
    }

    /* Hero carousel trang chủ (2026-09-12) - layout 3 của .pts-hero-layouts ở
       trên. 1 khối markup lặp cho N slide (templates/layout/slide.php), JS chỉ
       đổi state activeIndex và bật/tắt class .is-active + aria-hidden tương
       ứng - không phải nhiều bản tĩnh cho khách tự chọn. Timer vẫn chạy nền
       kể cả khi panel đang ẩn (giống hệt cơ chế .pts-hero-photo-layers ở
       trên - không tự thêm guard hidden vì đó không phải hành vi hiện có). */
    var heroEl = document.getElementById("ptsHeroCarousel");
    if(heroEl)
    {
        var heroSlides = Array.prototype.slice.call(heroEl.querySelectorAll(".pts-hero-carousel-slide"));
        if(heroSlides.length)
        {
            var heroDots = Array.prototype.slice.call(heroEl.querySelectorAll(".pts-hero-carousel-dot"));
            var heroPauseBtn = document.getElementById("ptsHeroPause");
            var heroPrevBtn = document.getElementById("ptsHeroPrev");
            var heroNextBtn = document.getElementById("ptsHeroNext");
            var heroReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
            var heroCurrent = 0;
            var heroPaused = heroReducedMotion.matches;
            var heroTimer = null;
            var HERO_INTERVAL = 7000;

            function heroApplyTheme(index) {
                var isLight = heroSlides[index].classList.contains("is-theme-light");
                heroEl.classList.toggle("is-light-active", isLight);
            }

            function heroShow(index) {
                heroCurrent = ((index % heroSlides.length) + heroSlides.length) % heroSlides.length;
                heroSlides.forEach(function(slide, i) {
                    var active = i === heroCurrent;
                    slide.classList.toggle("is-active", active);
                    if(active) slide.removeAttribute("aria-hidden");
                    else slide.setAttribute("aria-hidden", "true");
                });
                heroDots.forEach(function(dot, i) {
                    var active = i === heroCurrent;
                    dot.classList.toggle("is-active", active);
                    if(active) dot.setAttribute("aria-current", "true");
                    else dot.removeAttribute("aria-current");
                });
                heroApplyTheme(heroCurrent);
            }

            function heroUpdatePauseUI() {
                if(!heroPauseBtn) return;
                heroPauseBtn.setAttribute("aria-pressed", String(heroPaused));
                var label = heroPaused ? "Tiếp tục chuyển slide" : "Dừng chuyển slide";
                heroPauseBtn.setAttribute("aria-label", label);
                heroPauseBtn.title = label;
                heroPauseBtn.innerHTML = heroPaused ? '<i class="fas fa-play" aria-hidden="true"></i>' : '<i class="fas fa-pause" aria-hidden="true"></i>';
            }

            function heroClearTimer() {
                if(heroTimer) { window.clearInterval(heroTimer); heroTimer = null; }
            }

            function heroStartTimer() {
                heroClearTimer();
                if(heroPaused || document.hidden) return;
                heroTimer = window.setInterval(function() { heroShow(heroCurrent + 1); }, HERO_INTERVAL);
            }

            function heroManualGoto(index) {
                heroShow(index);
                heroStartTimer();
            }

            heroApplyTheme(0);
            heroUpdatePauseUI();
            heroStartTimer();

            heroDots.forEach(function(dot, i) {
                dot.addEventListener("click", function() { heroManualGoto(i); });
            });
            if(heroPrevBtn) heroPrevBtn.addEventListener("click", function() { heroManualGoto(heroCurrent - 1); });
            if(heroNextBtn) heroNextBtn.addEventListener("click", function() { heroManualGoto(heroCurrent + 1); });
            if(heroPauseBtn) heroPauseBtn.addEventListener("click", function() {
                heroPaused = !heroPaused;
                heroUpdatePauseUI();
                heroStartTimer();
            });

            heroEl.addEventListener("mouseenter", heroClearTimer);
            heroEl.addEventListener("mouseleave", heroStartTimer);
            heroEl.addEventListener("focusin", heroClearTimer);
            heroEl.addEventListener("focusout", function() { window.setTimeout(heroStartTimer, 0); });
            document.addEventListener("visibilitychange", heroStartTimer);

            heroEl.setAttribute("tabindex", "-1");
            heroEl.addEventListener("keydown", function(event) {
                if(event.key === "ArrowLeft") { event.preventDefault(); heroManualGoto(heroCurrent - 1); }
                else if(event.key === "ArrowRight") { event.preventDefault(); heroManualGoto(heroCurrent + 1); }
            });

            var heroTouchStart = null;
            heroEl.addEventListener("touchstart", function(event) {
                var touch = event.changedTouches[0];
                heroTouchStart = { x: touch.clientX, y: touch.clientY };
            }, { passive: true });
            heroEl.addEventListener("touchend", function(event) {
                if(!heroTouchStart) return;
                var touch = event.changedTouches[0];
                var dx = touch.clientX - heroTouchStart.x;
                var dy = touch.clientY - heroTouchStart.y;
                if(Math.abs(dx) > 55 && Math.abs(dx) > Math.abs(dy) * 1.4) heroManualGoto(heroCurrent + (dx < 0 ? 1 : -1));
                heroTouchStart = null;
            }, { passive: true });

            heroReducedMotion.addEventListener("change", function() {
                if(heroReducedMotion.matches) { heroPaused = true; heroUpdatePauseUI(); heroClearTimer(); }
            });
        }
    }
};

/* Ticker scroll */
GA_FRAMEWORK.TickerScroll = function(){
    if(isExist($(".news-scroll")))
    {
        $('.news-scroll').easyTicker({
            direction: 'up',
            easing: 'swing',
            speed: 'slow',
            interval: 3500,
            height: 'auto',
            visible: 3,
            mousePause: true,
            controls:{
                up: '.news-control#up',
                down: '.news-control#down',
                // toggle: '.toggle',
                // stopText: 'Stop'
            },
            callbacks:{
                before: function(ul, li){
                    // console.log(this, ul, li);
                    // $(li).css('color', 'red');
                },
                after: function(ul, li){
                    // console.log(this, ul, li);
                }
            }
        }).data('easyTicker');
    }
};

/* Photobox */
GA_FRAMEWORK.Photobox = function(){
    if(isExist($(".album-gallery")))
    {
        $('.album-gallery').photobox('a',{thumbs:true,loop:false});
    }
};

/* Trang Về chúng tôi dùng lại lightbox chứng nhận hiện hữu. Hai nút chữ chỉ
   chuyển thao tác sang thumbnail tương ứng, vì vậy gallery không bị nhân đôi
   ảnh và không phát sinh một cơ chế xem chứng nhận thứ hai. */
GA_FRAMEWORK.AboutCertificates = function(){
    $(document).on('click', '[data-about-cert-open]', function(){
        $('.pts-about-certificate-grid .album-item:visible .album-image').first().trigger('click');
    });
    $(document).on('click', '[data-about-cert-item-open]', function(){
        $(this).closest('.pts-about-certificate-card').find('.album-image').first().trigger('click');
    });
};

/* Comment */
GA_FRAMEWORK.Comment = function(){
    if(isExist($(".comment-page")))
    {
        $(".comment-page").comments({
            url: 'api/comment.php'
        });
    }
};

/* DatePicker */
GA_FRAMEWORK.DatePicker = function(){
    if(isExist($('#birthday')))
    {
        $('#birthday').datetimepicker({
            timepicker: false,
            format: 'd/m/Y',
            formatDate: 'd/m/Y',
            minDate: '01/01/1950',
            maxDate: TIMENOW
        });
    }
};

/* Search */
GA_FRAMEWORK.Search = function(){
    if(isExist($(".icon-search")))
    {
        $(".icon-search").click(function(){
            if($(this).hasClass('active'))
            {
                $(this).removeClass('active');
                $(".search-grid").stop(true,true).animate({opacity: "0",width: "0px"}, 200);   
            }
            else
            {
                $(this).addClass('active');                            
                $(".search-grid").stop(true,true).animate({opacity: "1",width: "230px"}, 200);
            }
            document.getElementById($(this).next().find("input").attr('id')).focus();
            $('.icon-search i').toggleClass('fa fa-search fa fa-times');
        });
    }
};

/* Videos */
GA_FRAMEWORK.Videos = function(){
    /* Fancybox */
    // $('[data-fancybox="something"]').fancybox({
    //     // transitionEffect: "fade",
    //     // transitionEffect: "slide",
    //     // transitionEffect: "circular",
    //     // transitionEffect: "tube",
    //     // transitionEffect: "zoom-in-out",
    //     // transitionEffect: "rotate",
    //     transitionEffect: "fade",
    //     transitionDuration: 800,
    //     animationEffect: "fade",
    //     animationDuration: 800,
    //     slideShow: {
    //         autoStart: true,
    //         speed: 3000
    //     },
    //     arrows: true,
    //     infobar: false,
    //     toolbar: false,
    //     hash: false
    // });

    if(isExist($('[data-fancybox="video"]')))
    {
        $('[data-fancybox="video"]').fancybox({
            transitionEffect: "fade",
            transitionDuration: 800,
            animationEffect: "fade",
            animationDuration: 800,
            arrows: true,
            infobar: false,
            toolbar: true,
            hash: false
        });
    }
};

/* Owl Data */
GA_FRAMEWORK.OwlData = function(obj){
    if(!isExist(obj)) return false;
    var xsm_items = obj.attr("data-xsm-items");
    var sm_items = obj.attr("data-sm-items");
    var md_items = obj.attr("data-md-items");
    var lg_items = obj.attr("data-lg-items");
    var xlg_items = obj.attr("data-xlg-items");
    var rewind = obj.attr("data-rewind");
    var autoplay = obj.attr("data-autoplay");
    var loop = obj.attr("data-loop");
    var lazyLoad = obj.attr("data-lazyload");
    var mouseDrag = obj.attr("data-mousedrag");
    var touchDrag = obj.attr("data-touchdrag");
    var animations = obj.attr("data-animations");
    var smartSpeed = obj.attr("data-smartspeed");
    var autoplaySpeed = obj.attr("data-autoplayspeed");
    var autoplayTimeout = obj.attr("data-autoplaytimeout");
    var dots = obj.attr("data-dots");
    var nav = obj.attr("data-nav");
    var navText = false;
    var navContainer = false;
    var responsive = {};
    var responsiveClass = true;
    var responsiveRefreshRate = 200;

    if(xsm_items != '') { xsm_items = xsm_items.split(":"); }
    if(sm_items != '') { sm_items = sm_items.split(":"); }
    if(md_items != '') { md_items = md_items.split(":"); }
    if(lg_items != '') { lg_items = lg_items.split(":"); }
    if(xlg_items != '') { xlg_items = xlg_items.split(":"); }
    if(rewind == 1) { rewind = true; } else { rewind = false; };
    if(autoplay == 1) { autoplay = true; } else { autoplay = false; };
    if(loop == 1) { loop = true; } else { loop = false; };
    if(lazyLoad == 1) { lazyLoad = true; } else { lazyLoad = false; };
    if(mouseDrag == 1) { mouseDrag = true; } else { mouseDrag = false; };
    if(animations != '') { animations = animations; } else { animations = false; };
    if(smartSpeed > 0) { smartSpeed = Number(smartSpeed); } else { smartSpeed = 800; };
    if(autoplaySpeed > 0) { autoplaySpeed = Number(autoplaySpeed); } else { autoplaySpeed = 800; };
    if(autoplayTimeout > 0) { autoplayTimeout = Number(autoplayTimeout); } else { autoplayTimeout = 5000; };
    if(dots == 1) { dots = true; } else { dots = false; };
    if(nav == 1)
    {
        nav = true;
        navText = obj.attr("data-navtext");
        navContainer = obj.attr("data-navcontainer");

        if(navText != '')
        {
            navText = (navText.indexOf("|") > 0) ? navText.split("|") : navText.split(":");
            navText = [navText[0],navText[1]];
        }

        if(navContainer != '')
        {
            navContainer = navContainer;
        }
    }
    else
    {
        nav = false;
    };

    responsive = {
        0: {
            items: Number(xsm_items[0]),
            margin: Number(xsm_items[1])
        },
        576: {
            items: Number(sm_items[0]),
            margin: Number(sm_items[1])
        },
        768: {
            items: Number(md_items[0]),
            margin: Number(md_items[1])
        },
        992: {
            items: Number(lg_items[0]),
            margin: Number(lg_items[1])
        },
        1200: {
            items: Number(xlg_items[0]),
            margin: Number(xlg_items[1])
        }
    };

    obj.owlCarousel({
        rewind: rewind,
        autoplay: autoplay,
        loop: loop,
        lazyLoad: lazyLoad,
        mouseDrag: mouseDrag,
        touchDrag: touchDrag,
        smartSpeed: smartSpeed,
        autoplaySpeed: autoplaySpeed,
        autoplayTimeout: autoplayTimeout,
        dots: dots,
        nav: nav,
        navText: navText,
        navContainer: navContainer,
        responsiveClass: responsiveClass,
        responsiveRefreshRate: responsiveRefreshRate,
        responsive: responsive
    });

    if(autoplay)
    {
        obj.on("translate.owl.carousel", function(event){
            obj.trigger('stop.owl.autoplay');
        });

        obj.on("translated.owl.carousel", function(event){
            obj.trigger('play.owl.autoplay',[autoplayTimeout]);
        });
    }

    if(animations && isExist(obj.find("[owl-item-animation]")))
    {
        var animation_now = '';
        var animation_count = 0;
        var animations_excuted = [];
        var animations_list = (animations.indexOf(",")) ? animations.split(",") : animations;

        obj.on("changed.owl.carousel", function(event){
            $(this).find(".owl-item.active").find("[owl-item-animation]").removeClass(animation_now);
        });

        obj.on("translate.owl.carousel", function(event){
            var item = event.item.index;

            if(Array.isArray(animations_list))
            {
                var animation_trim = animations_list[animation_count].trim();

                if(!animations_excuted.includes(animation_trim))
                {
                    animation_now = 'animate__animated ' + animation_trim;
                    animations_excuted.push(animation_trim);
                    animation_count++;
                }
                
                if(animations_excuted.length == animations_list.length)
                {
                    animation_count = 0;
                    animations_excuted = [];
                }
            }
            else
            {
                animation_now = 'animate__animated ' + animations_list.trim();
            }
            $(this).find('.owl-item').eq(item).find('[owl-item-animation]').addClass(animation_now);
        });
    }
};

/* Owl Page */
GA_FRAMEWORK.OwlPage = function(){
    if(isExist($(".owl-page")))
    {
        $(".owl-page").each(function(){
            GA_FRAMEWORK.OwlData($(this));
        });
    }
};

/* Dom Change */
GA_FRAMEWORK.DomChange = function(){
    /* Video Fotorama */
    $('#video-fotorama').one('DOMSubtreeModified', function(){
        $("#fotorama-videos").fotorama();
    });

    /* Video Select */
    $('#video-select').one('DOMSubtreeModified', function(){
        $('.listvideos').change(function() 
        {
            var id = $(this).val();
            $.ajax({
                url:'api/video.php',
                type: "POST",
                dataType: 'html',
                data: {
                    id: id
                },
                beforeSend: function(){
                    holdonOpen();
                },
                success: function(result){
                    $('.video-main').html(result);
                    holdonClose();
                }
            });
        });
    });

    /* Chat Facebook */
    $('#messages-facebook').one('DOMSubtreeModified', function(){
        $(".js-facebook-messenger-box").on("click", function(){
            $(".js-facebook-messenger-box, .js-facebook-messenger-container").toggleClass("open"), $(".js-facebook-messenger-tooltip").length && $(".js-facebook-messenger-tooltip").toggle()
        }), $(".js-facebook-messenger-box").hasClass("cfm") && setTimeout(function(){
            $(".js-facebook-messenger-box").addClass("rubberBand animated")
        }, 3500), $(".js-facebook-messenger-tooltip").length && ($(".js-facebook-messenger-tooltip").hasClass("fixed") ? $(".js-facebook-messenger-tooltip").show() : $(".js-facebook-messenger-box").on("hover", function(){
            $(".js-facebook-messenger-tooltip").show()
        }), $(".js-facebook-messenger-close-tooltip").on("click", function(){
            $(".js-facebook-messenger-tooltip").addClass("closed")
        }));
        $(".search_open").click(function(){
            $(".search_box_hide").toggleClass('opening');
        });
    });
};

/* Cart */
GA_FRAMEWORK.Cart = function(){
    /* Add */
    $("body").on("click",".addcart",function(){
        $this = $(this);
        $parents = $this.parents(".right-pro-detail");
        var id = $this.data("id");
        var action = $this.data("action");
        var quantity = $parents.find(".quantity-pro-detail").find(".qty-pro").val();
        quantity = (quantity) ? quantity : 1;
        var color = $parents.find(".color-block-pro-detail").find(".color-pro-detail input:checked").val();
        color = (color) ? color : 0;
        var size = $parents.find(".size-block-pro-detail").find(".size-pro-detail input:checked").val();
        size = (size) ? size : 0;

        if(id)
        {
            $.ajax({
                url:'api/cart.php',
                type: "POST",
                dataType: 'json',
                async: false,
                data: {
                    cmd: 'add-cart',
                    id: id,
                    color: color,
                    size: size,
                    quantity: quantity
                },
                beforeSend: function(){
                    holdonOpen();
                },
                success: function(result){
                    if(action=='addnow')
                    {
                        $('.count-cart').html(result.max);
                        $('.icon-cart-header').attr('aria-label', 'Giỏ hàng có ' + result.max + ' sản phẩm');
                        $.ajax({
                            url:'api/cart.php',
                            type: "POST",
                            dataType: 'html',
                            async: false,
                            data: {
                                cmd: 'popup-cart'
                            },
                            success: function(result){
                                $("#popup-cart .modal-body").html(result);
                                $('#popup-cart').modal('show');
                                GA_FRAMEWORK.Lazys();
                                holdonClose();
                            }
                        });
                    }
                    else if(action=='buynow')
                    {
                        window.location = CONFIG_BASE + "gio-hang";
                    }
                }
            });
        }
    });

    /* Delete */
    $("body").on("click",".del-procart",function(){
        confirmDialog("delete-procart", LANG['delete_product_from_cart'], $(this));
    });

    /* Counter */
    $("body").on("click",".counter-procart",function(){
        var $button = $(this);
        var quantity = 1;
        var input = $button.parent().find("input");
        var id = input.data('pid');
        var code = input.data('code');
        var oldValue = $button.parent().find("input").val();
        if($button.text() == "+") quantity = parseFloat(oldValue) + 1;
        else if(oldValue > 1) quantity = parseFloat(oldValue) - 1;
        $button.parent().find("input").val(quantity);
        updateCart(id,code,quantity);
    });

    /* Quantity */
    $("body").on("change","input.quantity-procart",function(){
        var quantity = ($(this).val() < 1) ? 1 : $(this).val();
        $(this).val(quantity);
        var id = $(this).data("pid");
        var code = $(this).data("code");
        updateCart(id,code,quantity);
    });

    /* City */
    if(isExist($(".select-city-cart")))
    {
        $(".select-city-cart").change(function(){
            var id = $(this).val();
            loadDistrict(id);
            loadShip();
        });
    }

    /* District */
    if(isExist($(".select-district-cart")))
    {
        $(".select-district-cart").change(function(){
            var id = $(this).val();
            loadWard(id);
            loadShip();
        });
    }

    /* Ward */
    if(isExist($(".select-ward-cart")))
    {
        $(".select-ward-cart").change(function(){
            var id = $(this).val();
            loadShip(id);
        });
    }

    /* Payments */
    if(isExist($(".payments-label")))
    {
        $(".payments-label").click(function(){
            var payments = $(this).data("payments");
            $(".payments-cart .payments-label, .payments-info").removeClass("active");
            $(this).addClass("active");
            $(".payments-info-"+payments).addClass("active");
        });
    }

    /* Colors */
    if(isExist($(".color-pro-detail")))
    {
        $(".color-pro-detail input").click(function(){
            $this = $(this).parents("label.color-pro-detail");
            $parents = $this.parents(".attr-pro-detail");
            $parents_detail = $this.parents(".grid-pro-detail");
            $parents.find(".color-block-pro-detail").find(".color-pro-detail").removeClass("active");
            $parents.find(".color-block-pro-detail").find(".color-pro-detail input").prop("checked",false);
            $this.addClass("active");
            $this.find("input").prop("checked",true);
            var id_color = $parents.find(".color-block-pro-detail").find(".color-pro-detail input:checked").val();
            var id_pro = $this.data('idproduct');

            $.ajax({
                url:'api/color.php',
                type: "POST",
                dataType: 'html',
                data: {
                    id_color: id_color,
                    id_pro: id_pro
                },
                beforeSend: function(){
                    holdonOpen();
                },
                success: function(result){
                    if(result)
                    {
                        $parents_detail.find('.left-pro-detail').html(result);
                        MagicZoom.refresh("Zoom-1");
                        GA_FRAMEWORK.OwlData($('.owl-pro-detail'));
                        GA_FRAMEWORK.Lazys();
                    }
                    holdonClose();
                }
            });
        });
    }

    /* Sizes */
    if(isExist($(".size-pro-detail")))
    {
        $(".size-pro-detail input").click(function(){
            $this = $(this).parents("label.size-pro-detail");
            $parents = $this.parents(".attr-pro-detail");
            $parents.find(".size-block-pro-detail").find(".size-pro-detail").removeClass("active");
            $parents.find(".size-block-pro-detail").find(".size-pro-detail input").prop("checked",false);
            $this.addClass("active");
            $this.find("input").prop("checked",true);
        });
    }

    /* Quantity detail page */
    if(isExist($(".quantity-pro-detail span")))
    {
        $(".quantity-pro-detail span").click(function(){
            var $button = $(this);
            var oldValue = $button.parent().find("input").val();
            if($button.text() == "+")
            {
                var newVal = parseFloat(oldValue) + 1;
            }
            else
            {
                if(oldValue > 1) var newVal = parseFloat(oldValue) - 1;
                else var newVal = 1;
            }
            $button.parent().find("input").val(newVal);
        });
    }
};
GA_FRAMEWORK.loadTab = function () {
    /* Categories */
    if (isExist($("#load-product-tab"))) {
        $("#load-product-tab").each(function () {
            loadPaging("api/product.php?perpage=25", '#load-product-tab');
        })
    }
    $(document).on('click', '#loadmore', function(event) {
        var count = $(this).data("count");
        var perpage = count+10;
        loadPaging("api/product.php?perpage="+perpage, '#load-product-tab')
    });
};

GA_FRAMEWORK.Toc = function(){
    if (isExist($(".toc-list")))
    {
        $(".toc-list").toc({
            content: "div#toc-content",
            headings: "h2,h3,h4"
        });
        if(!$(".toc-list li").length) $(".meta-toc").hide();
        $('.toc-list').find('a').click(function(){
            var x = $(this).attr('data-rel');
            goToByScroll(x);
        });
    }
};
GA_FRAMEWORK.SlickPage = function(){
    if (isExist($(".slick-hinhanh")))
    {
        $('.slick-hinhanh').slick({
            dots: false,
            infinite: true,
            arrows: false,
            swipeToSlide: true,
            rows: 1,
            speed: 500,
            lazyLoad: 'ondemand',
            vertical: false,
            slidesToShow: 1,
            slidesToScroll: 1,
            centerPadding: '15%',
            autoplay: true,
            centerMode: true,
            focusOnSelect: true,
            responsive: [{
                breakpoint: 769,
                settings: {
                    centerPadding: '10%',
                }
            }],
        });
    }
};

GA_FRAMEWORK.HomeCategoryScroller = function(){
    $('.pts-home-category-wrap').each(function(){
        var wrap = $(this);
        var track = wrap.find('.pts-home-category-grid').get(0);
        var prev = wrap.find('.pts-category-scroll--prev');
        var next = wrap.find('.pts-category-scroll--next');
        if(!track) return;

        function updateButtons(){
            var maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
            prev.prop('disabled', track.scrollLeft <= 2);
            next.prop('disabled', track.scrollLeft >= maxScroll - 2);
        }
        function move(direction){
            track.scrollBy({left: direction * Math.max(220, track.clientWidth * 0.78), behavior: 'smooth'});
        }

        prev.on('click', function(){ move(-1); });
        next.on('click', function(){ move(1); });
        $(track).on('scroll', updateButtons);
        $(window).on('resize', updateButtons);
        updateButtons();
    });
};

/* Các rail ngang của homepage: ẩn scrollbar thị giác, dải thương hiệu có nút
   điều khiển; rail danh mục kéo chuột/trackpad hoặc vuốt chạm trực tiếp. */
GA_FRAMEWORK.HomeBrandScroller = function(){
    $('[data-pts-horizontal-rail]').each(function(){
        var rail = $(this);
        var track = rail.find('[data-pts-horizontal-track], .pts-brands-grid').first().get(0);
        var prev = rail.find('.pts-rail-prev, .pts-brands-arrow--prev');
        var next = rail.find('.pts-rail-next, .pts-brands-arrow--next');
        var drag = null;
        var suppressClick = false;
        if(!track) return;

        function updateButtons(){
            var maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
            var hasOverflow = maxScroll > 2;
            prev.prop('hidden', !hasOverflow).prop('disabled', !hasOverflow || track.scrollLeft <= 2);
            next.prop('hidden', !hasOverflow).prop('disabled', !hasOverflow || track.scrollLeft >= maxScroll - 2);
        }
        function move(direction){
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            track.scrollBy({left: direction * Math.max(210, track.clientWidth * 0.72), behavior: reduceMotion ? 'auto' : 'smooth'});
        }

        prev.on('click', function(){ move(-1); });
        next.on('click', function(){ move(1); });
        $(track).on('scroll', updateButtons);
        $(window).on('resize', updateButtons);
        track.addEventListener('pointerdown', function(event){
            if(event.pointerType === 'touch' || event.button !== 0) return;
            drag = {id:event.pointerId, x:event.clientX, left:track.scrollLeft, moved:false};
        });
        track.addEventListener('pointermove', function(event){
            if(!drag || drag.id !== event.pointerId) return;
            if(!drag.moved && Math.abs(event.clientX - drag.x) > 6) {
                drag.moved = true;
                track.setPointerCapture(event.pointerId);
                track.classList.add('is-dragging');
            }
            if(!drag.moved) return;
            track.scrollLeft = drag.left - (event.clientX - drag.x);
        });
        function endDrag(event){
            if(!drag || drag.id !== event.pointerId) return;
            if(track.hasPointerCapture(event.pointerId)) track.releasePointerCapture(event.pointerId);
            suppressClick = drag.moved;
            drag = null;
            track.classList.remove('is-dragging');
            window.setTimeout(function(){ suppressClick = false; }, 0);
        }
        track.addEventListener('pointerup', endDrag);
        track.addEventListener('pointercancel', endDrag);
        track.addEventListener('click', function(event){
            if(!suppressClick) return;
            event.preventDefault();
            event.stopPropagation();
        }, true);
        updateButtons();
    });
};

/* Bốn lối vào theo đối tượng khách hàng ở đầu trang. Gia đình/doanh nghiệp
   dùng chung section Giải pháp nhưng phải mở đúng tab dữ liệu hiện có trước
   khi cuộn tới, tránh đưa cả hai card về cùng một trạng thái. */
GA_FRAMEWORK.HomeSolutionPaths = function(){
    $(document).on('click', '[data-pts-solution-name]', function(event){
        var section = document.querySelector($(this).attr('href'));
        if(!section) return;

        event.preventDefault();
        var desiredName = String($(this).attr('data-pts-solution-name') || '').trim().toLowerCase();
        var tabLinks = section.querySelectorAll('.pts-solution-tabs > li > a');
        for(var index = 0; index < tabLinks.length; index++) {
            if(tabLinks[index].textContent.trim().toLowerCase() === desiredName) {
                tabLinks[index].click();
                break;
            }
        }

        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        section.scrollIntoView({behavior: reduceMotion ? 'auto' : 'smooth', block: 'start'});
        if(window.history && window.history.replaceState) window.history.replaceState(null, '', '#giai-phap');
    });
};

/* Cụm liên hệ nổi thu gọn: chỉ chiếm một nút ở góc màn hình, mở các kênh
   khi người dùng chủ động bấm. Giữ nguyên toàn bộ link gọi/Zalo/Facebook. */
GA_FRAMEWORK.ContactDock = function(){
    var dock = $('#button-contact-vr.pts-contact-dock');
    if(!dock.length) return;

    var toggle = dock.find('.pts-contact-dock-toggle');
    var group = dock.find('#gom-all-in-one');
    var links = group.find('a');

    function setOpen(open){
        dock.toggleClass('is-open', open);
        $('body').toggleClass('pts-contact-dock-open', open);
        toggle.attr({
            'aria-expanded': open ? 'true' : 'false',
            'aria-label': open ? 'Đóng các kênh liên hệ' : 'Mở các kênh liên hệ'
        });
        group.attr('aria-hidden', open ? 'false' : 'true');
        links.attr('tabindex', open ? '0' : '-1');
    }

    dock.addClass('is-enhanced');
    setOpen(false);
    toggle.on('click', function(event){
        event.stopPropagation();
        setOpen(!dock.hasClass('is-open'));
    });
    $(document).on('click', function(event){
        if(dock.hasClass('is-open') && !dock.get(0).contains(event.target)) setOpen(false);
    });
    $(document).on('keydown', function(event){
        if(event.key === 'Escape' && dock.hasClass('is-open')) {
            setOpen(false);
            toggle.trigger('focus');
        }
    });
};

/* Dải chọn cuộn ngang: khi bấm một ô đang nằm sát/rời mép nhìn thấy, tự đưa
   ô đó vào giữa dải. Chỉ áp dụng cho widget gắn data-click-scroll; link điều
   hướng thông thường không bị can thiệp và click vẫn chạy đúng chức năng cũ. */
GA_FRAMEWORK.ClickScrollTracks = function(){
    $(document).on('click', '[data-click-scroll] > li > a, [data-click-scroll] > a, [data-click-scroll] > button', function(){
        var item = this.parentElement && this.parentElement.parentElement && this.parentElement.parentElement.hasAttribute('data-click-scroll')
            ? this.parentElement
            : this;
        var track = item.parentElement;
        if(!track || track.scrollWidth <= track.clientWidth + 1) return;

        var itemRect = item.getBoundingClientRect();
        var trackRect = track.getBoundingClientRect();
        var desired = track.scrollLeft + itemRect.left - trackRect.left
            - Math.max(0, (track.clientWidth - itemRect.width) / 2);
        var maximum = Math.max(0, track.scrollWidth - track.clientWidth);
        var target = Math.max(0, Math.min(maximum, desired));
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        track.scrollTo({left: target, behavior: reduceMotion ? 'auto' : 'smooth'});
    });
};

/* Ready */
$(document).ready(function(){
    GA_FRAMEWORK.Lazys();
    GA_FRAMEWORK.loadTab();
    GA_FRAMEWORK.Tools();
    GA_FRAMEWORK.Popup();
    // GA_FRAMEWORK.Wows();
    GA_FRAMEWORK.SlickPage();
    GA_FRAMEWORK.HomeCategoryScroller();
    GA_FRAMEWORK.HomeBrandScroller();
    GA_FRAMEWORK.HomeSolutionPaths();
    GA_FRAMEWORK.ContactDock();
    GA_FRAMEWORK.ClickScrollTracks();
    GA_FRAMEWORK.AltImg();
    GA_FRAMEWORK.Toc();
    // GA_FRAMEWORK.GoTop();
    GA_FRAMEWORK.Menu();
    GA_FRAMEWORK.OwlPage();
    GA_FRAMEWORK.Pagings();
    GA_FRAMEWORK.Cart();
    GA_FRAMEWORK.Videos();
    GA_FRAMEWORK.Photobox();
    GA_FRAMEWORK.AboutCertificates();
    GA_FRAMEWORK.Comment();
    GA_FRAMEWORK.Search();
    GA_FRAMEWORK.DomChange();
    GA_FRAMEWORK.TickerScroll();
    GA_FRAMEWORK.DatePicker();
    GA_FRAMEWORK.loadNameInputFile();
    function setProductFilterDrawer(open) {
        $('.product-left').toggleClass('show', open).attr('aria-hidden', open ? 'false' : 'true');
        $('.mask-sidebar').toggleClass('show', open);
        $('#opensideBar-product').toggleClass('active', open).attr('aria-expanded', open ? 'true' : 'false');
        $('body').toggleClass('pts-filter-drawer-open', open);
        if(open) $('.pts-filter-drawer-close').trigger('focus');
    }
    if(window.matchMedia && window.matchMedia('(max-width: 1001px)').matches) {
        $('.product-left').attr('aria-hidden', 'true');
    }
    $(document).on('click', '.toggle-sidebar', function (event) {
        event.preventDefault();
        if(window.matchMedia && !window.matchMedia('(max-width: 1001px)').matches) {
            setProductFilterDrawer(false);
            return;
        }
        setProductFilterDrawer(!$('.product-left').hasClass('show'));
    });
    $(window).on('resize', function () {
        if(window.matchMedia && !window.matchMedia('(max-width: 1001px)').matches) setProductFilterDrawer(false);
    });
    $(document).on('keydown', function (event) {
        if(event.key === 'Escape' && $('.product-left').hasClass('show')) {
            setProductFilterDrawer(false);
            $('#opensideBar-product').trigger('focus');
        }
    });
    /* Accordion cho từng nhóm filter sidebar (Danh mục luôn mở, còn lại thu
       gọn mặc định - xem class .pts-filter-open được render sẵn ở PHP). */
    $(document).on('click', '.pts-filter-toggle', function () {
        var $group = $(this).closest('.pts-filter-group');
        var open = !$group.hasClass('pts-filter-open');
        $group.toggleClass('pts-filter-open', open);
        $(this).attr('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('keydown', '.pts-filter-toggle', function (event) {
        if(event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            $(this).trigger('click');
        }
    });
    $(document).ready(function () {
        "use strict";
        var progressPath = document.querySelector('.progress-wrap path');
        var pathLength = progressPath.getTotalLength();
        progressPath.style.transition = progressPath.style.WebkitTransition = 'none';
        progressPath.style.strokeDasharray = pathLength + ' ' + pathLength;
        progressPath.style.strokeDashoffset = pathLength;
        progressPath.getBoundingClientRect();
        progressPath.style.transition = progressPath.style.WebkitTransition = 'stroke-dashoffset 10ms linear';
        var updateProgress = function () {
            var scroll = $(window).scrollTop();
            var height = $(document).height() - $(window).height();
            var progress = pathLength - (scroll * pathLength / height);
            progressPath.style.strokeDashoffset = progress;
        };
        updateProgress();
        $(window).scroll(updateProgress);
        var offsetShow = 170;
        var offsetHide = 130;
        var duration = 550;
        /* Có khoảng đệm giữa ngưỡng hiện (170px) và ẩn (130px) thay vì 1 mốc
           duy nhất — nếu chỉ 1 mốc, cuộn rung nhẹ quanh đó (rất hay gặp khi
           vuốt chạm/đà cuộn trên mobile) sẽ bật/tắt active-progress liên tục,
           chạy lại transition 400ms mỗi lần -> nút bị giật. */
        $(window).on('scroll', function () {
            var top = $(this).scrollTop();
            if (top > offsetShow) {
                $('.progress-wrap').addClass('active-progress');
            } else if (top < offsetHide) {
                $('.progress-wrap').removeClass('active-progress');
            }
        });
        $('.progress-wrap').on('click', function (event) {
            event.preventDefault();
            $('html, body').animate({
                scrollTop: 0
            }, duration);
            return false;
        });
    });
});
