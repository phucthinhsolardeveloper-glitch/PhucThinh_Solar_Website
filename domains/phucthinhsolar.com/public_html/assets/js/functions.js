function isExist(ele)
{
    return ele.length;
}
function pushState(options,targetTitle,targetUrl) {

    window.history.pushState(options, targetTitle, targetUrl);

    /*get state console.log(window.history.state);*/

}
function updateProductFilterResetVisibility() {
    var reset = document.querySelector('[data-filter-reset]');
    if (!reset) return;
    var hasChecked = Array.prototype.some.call(document.querySelectorAll('input[name="car_brand"]:checked, input[name="car_xuatxu"]:checked, input[name="car_congsuat"]:checked, input[name="car_price"]:checked, input[name="car_cat"]:checked, input[name="car_need"]:checked'), function() { return true; });
    var keyword = document.querySelector('input[name="keywords"]');
    var sort = document.querySelector('select[name="orderby"]');
    var active = hasChecked || (keyword && keyword.value.trim() !== '') || (sort && sort.value.trim() !== '');
    reset.hidden = !active;
    reset.setAttribute('aria-hidden', active ? 'false' : 'true');
}
function fetch_product(t=1) {
    var n = ""
      , a = ""
      , i = ""
      , c = ""
      , x = ""
      , w = ""
      , p = ""
      , g = ""
      , k = ""
      , d = ""
      , l2 = ""
      , c2 = ""
      , browserQuery = ""
      , categoryUrl = ""
      , s = $("#opensideBar-product");
    s.hasClass("active") && 0 != s.length && s.trigger("click");
    c = $('input[name="car_brand"]:checked').map(function() {
        return this.value
    }).get().join(","),
    x = $('input[name="car_xuatxu"]:checked').map(function() {
        return this.value
    }).get().join(","),
    w = $('input[name="car_congsuat"]:checked').map(function() {
        return this.value
    }).get().join(","),
    p = $('input[name="car_price"]:checked').map(function() {
        return this.value
    }).get().join(","),
    g = $('input[name="car_cat"]:checked').map(function() {
        return this.value
    }).get().join(","),
    d = $('input[name="car_need"]:checked').map(function() {
        return this.value
    }).get().join(","),
    i = $('select[name="orderby"] option:selected').map(function() {
        return this.value
    }).get().join(","),
    k = $('input[name="keywords"]').val() || "",
    l2 = $('input[name="id_list"]').map(function() {
        return this.value
    }).get().join(","),
    c2 = $('input[name="id_cat"]').map(function() {
        return this.value
    }).get().join(","),
    a = $('input[name="alias"]').val(),
    "0" != l2 && (n += "id_list=" + l2 + "&"),
    "0" != c2 && (n += "id_cat=" + c2 + "&"),
    "" != k && (n += "keyword=" + encodeURIComponent(k) + "&"),
    "" != c && (n += "is_brand=" + c + "&"),
    "" != x && (n += "is_origin=" + x + "&"),
    "" != w && (n += "is_congsuat=" + w + "&"),
    "" != p && (n += "is_price=" + p + "&"),
    "" != g && (n += "is_cat=" + g + "&"),
    "" != d && (n += "is_need=" + d + "&"),
    "" != i && (n += "is_orderby=" + i + "&"),
    browserQuery = n.substr(0, n.length - 1),
    updateProductFilterResetVisibility(),
    1 == $('input[name="car_cat"]:checked').length && (categoryUrl = $('input[name="car_cat"]:checked').attr("data-url") || ""),
    "" != categoryUrl && (a = categoryUrl, browserQuery = browserQuery.split("&").filter(function(part) { return part.indexOf("id_list=") !== 0 && part.indexOf("id_cat=") !== 0 && part.indexOf("is_cat=") !== 0 }).join("&")),
    "" != categoryUrl && (window.location.href = a + (browserQuery ? "?" + browserQuery : ""));
    if(categoryUrl) return;
    pushState({}, "", a + (browserQuery ? "?" + browserQuery : "")),
    doSearch({
        id_list: l2,
        id_cat: c2,
        keyword: k,
        is_orderby: i,
        is_brand: c,
        is_origin: x,
        is_congsuat: w,
        is_price: p,
        is_cat: g,
        is_need: d
    })
}
var productFilterRequest = null;
var productFilterSequence = 0;
function doSearch(options, restoreHistory) {
    var sequence = ++productFilterSequence;
    if (productFilterRequest) productFilterRequest.abort();
    var grid = $("#show-product");
    grid.attr('aria-busy', 'true').html('<div id="loader" role="status">Đang tải sản phẩm…</div>');
    productFilterRequest = $.ajax({
        url: 'api/fillter_service.php',
        type: 'GET',
        timeout: 15000,
        data: $.extend({}, options || {}, {eShow: '#show-product'}),
        success: function(html) {
            if (sequence !== productFilterSequence) return;
            grid.html(html);
            if (!restoreHistory) $('body,html').stop(true).animate({scrollTop: grid.offset().top - 110}, 300);
            GA_FRAMEWORK.Lazys();
            $('.product-left, .mask-sidebar').removeClass('show');
        },
        error: function(xhr, status) {
            if (sequence !== productFilterSequence || status === 'abort') return;
            grid.html('<div role="alert" class="pts-filter-error"><p>Chưa tải được sản phẩm. Vui lòng thử lại.</p><button type="button" class="btn btn-primary" data-filter-retry>Thử lại</button></div>');
            grid.find('[data-filter-retry]').on('click', function() { doSearch(options, restoreHistory); });
        },
        complete: function() {
            if (sequence === productFilterSequence) grid.attr('aria-busy', 'false');
        }
    });
}
window.addEventListener('popstate', function() {
    if (!document.querySelector('#show-product')) return;
    var query = new URLSearchParams(window.location.search);
    var options = {
        id_list: query.get('id_list') || $('input[name="id_list"]').val() || '',
        id_cat: query.get('id_cat') || $('input[name="id_cat"]').val() || '',
        keyword: query.get('keyword') || '',
        is_orderby: query.get('is_orderby') || '',
        p: query.get('p') || 1
    };
    var controls = {car_brand: 'is_brand', car_xuatxu: 'is_origin', car_congsuat: 'is_congsuat', car_price: 'is_price', car_cat: 'is_cat', car_need: 'is_need'};
    $.each(controls, function(name, key) {
        options[key] = query.get(key) || '';
        var selected = options[key].split(',');
        $('input[name="' + name + '"]').each(function() { this.checked = selected.indexOf(this.value) !== -1; });
    });
    $('input[name="keywords"]').val(options.keyword);
    var sort = $('select[name="orderby"]');
    sort.val(options.is_orderby || sort.find('option').first().val());
    updateProductFilterResetVisibility();
    doSearch(options, true);
});
function doSearchProduct(options) {

    if(!options) options = {};

    var url = '';
    var sortby = options['sortby'];
    $.each(options, function(k, v) {

        url += '?'+k+'='+v;

    });

    // url = url.substr(1);

    $.ajax({

        url: 'api/search_product.php',

        type: 'GET',

        dataType: 'json',
        data: {sortby: sortby},

        success: function(data){

            $('#search-body').html(data.res);

            $('#search-page').addClass('show-ajax');

            //$('#search-page').html(data.page);

            GA_FRAMEWORK.Lazys();

        }

    });

    

}
function getLen(str)
{
    return (/^\s*$/.test(str)) ? 0 : str.length;
}

function showNotify(text='Notify text', title='Thông báo', status='success')
{
    new Notify ({
        status: status, // success, warning, error
        title: title,
        text: text,
        effect: 'fade',
        speed: 400,
        customClass: null,
        customIcon: null,
        showIcon: true,
        showCloseButton: true,
        autoclose: true,
        autotimeout: 3000,
        gap: 10,
        distance: 10,
        type: 3,
        position: 'right top'
    });
}

function notifyDialog(content='', title='Thông báo', icon='fas fa-exclamation-triangle', type='blue')
{
    $.alert({
        title: title,
        icon: icon, // font awesome
        type: type, // red, green, orange, blue, purple, dark
        content: content, // html, text
        backgroundDismiss: true,
        animationSpeed: 600,
        animation: 'zoom',
        closeAnimation: 'scale',
        typeAnimated: true,
        animateFromElement: false,
        autoClose: 'accept|3000',
        escapeKey: 'accept',
        buttons: {
            /* btn-gold thay btn-primary (xanh Bootstrap mặc định, không
               thuộc bộ nhận diện) - đồng bộ với nút CTA vàng dùng khắp site.
               Toàn bộ 46 chỗ gọi notifyDialog/confirmDialog đều không tự
               truyền type/class riêng nên sửa đúng 1 chỗ này áp dụng hết
               (sếp 2026-10-02: "nhiều dạng nút kiểu này vẫn còn kiểu cũ"). */
            accept: {
                text: 'Đồng ý',
                btnClass: 'btn-sm btn-gold'
            }
        }
    });
}

function confirmDialog(action, text, value, title='Thông báo', icon='fas fa-exclamation-triangle', type='blue')
{
    $.confirm({
        title: title,
        icon: icon, // font awesome
        type: type, // red, green, orange, blue, purple, dark
        content: text, // html, text
        backgroundDismiss: true,
        animationSpeed: 600,
        animation: 'zoom',
        closeAnimation: 'scale',
        typeAnimated: true,
        animateFromElement: false,
        autoClose: 'cancel|3000',
        escapeKey: 'cancel',
        buttons: {
            /* btn-gold/btn-navy-outline thay btn-primary/btn-danger (xanh/đỏ
               Bootstrap mặc định) - dùng đúng cặp nút chính/phụ đã có sẵn
               trong bộ nhận diện (gold = hành động chính, navy-outline =
               phụ/hủy), không dùng đỏ cho "Hủy" vì đây là lựa chọn AN TOÀN
               (không xóa), đỏ hợp lý hơn cho hành động phá hủy chứ không
               phải nút hủy bỏ. */
            success: {
                text: 'Đồng ý',
                btnClass: 'btn-sm btn-gold',
                action: function(){
                    if(action == "delete-procart") deleteCart(value);
                }
            },
            cancel: {
                text: 'Hủy',
                btnClass: 'btn-sm btn-navy-outline'
            }
        }
    });
}

function validateForm(ele='')
{
    if(ele)
    {
        $("."+ele).find("input[type=submit]").removeAttr("disabled");
        var forms = document.getElementsByClassName(ele);
        var validation = Array.prototype.filter.call(forms,function(form){
            form.addEventListener('submit', function(event){
                if(form.checkValidity() === false)
                {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }
}

/* Reader image */
function readImage(inputFile,elementPhoto)
{
    if(inputFile[0].files[0])
    {
        if(inputFile[0].files[0].name.match(/.(jpg|jpeg|png|gif)$/i))
        {
            var size = parseInt(inputFile[0].files[0].size) / 1024;

            if(size <= 4096)
            {
                var reader = new FileReader();
                reader.onload = function(e){
                    $(elementPhoto).attr('src', e.target.result);
                };
                reader.readAsDataURL(inputFile[0].files[0]);
            }
            else
            {
                notifyDialog("Dung lượng hình ảnh lớn. Dung lượng cho phép <= 4MB ~ 4096KB");
                return false;
            }
        }
        else
        {
            $(elementPhoto).attr('src', '');
            notifyDialog("Định dạng hình ảnh không hợp lệ");
            return false;
        }
    }
    else
    {
        $(elementPhoto).attr('src', '');
        return false;
    }
}

/* Photo zone */
function photoZone(eDrag,iDrag,eLoad)
{
    if($(eDrag).length)
    {
        /* Drag over */
        $(eDrag).on("dragover",function(){
            $(this).addClass("drag-over");
            return false;
        });

        /* Drag leave */
        $(eDrag).on("dragleave",function(){
            $(this).removeClass("drag-over");
            return false;
        });

        /* Drop */
        $(eDrag).on("drop",function(e){
            e.preventDefault();
            $(this).removeClass("drag-over");

            var lengthZone = e.originalEvent.dataTransfer.files.length;

            if(lengthZone == 1)
            {
                $(iDrag).prop("files", e.originalEvent.dataTransfer.files);
                readImage($(iDrag),eLoad);
            }
            else if(lengthZone > 1)
            {
                notifyDialog("Bạn chỉ được chọn 1 hình ảnh để upload");
                return false;
            }
            else
            {
                notifyDialog("Dữ liệu không hợp lệ");
                return false;
            }
        });

        /* File zone */
        $(iDrag).change(function(){
            readImage($(this),eLoad);
        });
    }
}

function generateCaptcha(action, id)
{
    if(RECAPTCHA_ACTIVE && action && id && $('#' + id).length)
    {
        grecaptcha.execute(RECAPTCHA_SITEKEY, { action: action }).then(function (token) {
            var recaptchaResponse = document.getElementById(id);
            recaptchaResponse.value = token;
        });
    }
}

function loadPaging(url='',eShow='')
{
    if($(eShow).length && url)
    {
        $.ajax({
            url: url,
            type: "GET",
            data: {
                eShow: eShow
            },
            success: function(result){
                $(eShow).html(result);
                GA_FRAMEWORK.Lazys();
                GA_FRAMEWORK.OwlPage();
            }
        });
    }
}

function doEnter(event,obj)
{
    if(event.keyCode == 13 || event.which == 13) onSearch(obj);
}

function onSearch(obj)
{
    var keyword = $.trim($("#"+obj).val());
    
    if(keyword=='')
    {
        notifyDialog(LANG['no_keywords']);
        return false;
    }
    else
    {
        location.href = "tim-kiem?keyword="+encodeURIComponent(keyword);
    }
}

function goToByScroll(id, minusTop)
{
    minusTop = (parseInt(minusTop)) ? parseInt(minusTop) : 0;
    id = id.replace("#", "");
    $('html,body').animate({
        scrollTop: $("#" + id).offset().top - minusTop
    }, 'slow');
}

function holdonOpen(theme="sk-circle",text="Loading...",backgroundColor="rgba(0,0,0,0.8)",textColor="white")
{
    var options = {
        theme: theme,
        message: text,
        backgroundColor: backgroundColor,
        textColor: textColor
    };

    HoldOn.open(options);
}

function holdonClose()
{
    HoldOn.close();
}

function updateCart(id=0,code='',quantity=1)
{
    if(id)
    {
        var formCart = $(".form-cart");
        var ward = formCart.find(".select-ward-cart").val();

        $.ajax({
            type: "POST",
            url: "api/cart.php",
            dataType: 'json',
            data: {
                cmd: 'update-cart',
                id: id,
                code: code,
                quantity: quantity,
                ward: ward
            },
            beforeSend: function(){
                holdonOpen();
            },
            success: function(result){
                if(result)
                {
                    formCart.find('.load-price-'+code).html(result.regularPrice);
                    formCart.find('.load-price-new-'+code).html(result.salePrice);
                    formCart.find('.load-price-temp').html(result.tempText);
                    formCart.find('.load-price-total').html(result.totalText);
                    if(result.shipText) formCart.find('.load-price-ship').html(result.shipText);
                }
                holdonClose();
            },
            error: function(xhr){
                var message = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Không thể cập nhật giỏ hàng. Vui lòng thử lại.';
                showNotify(message, 'Thông báo', 'error');
                holdonClose();
            }
        });
    }
}

function deleteCart(obj)
{
    var formCart = $(".form-cart");
    var code = obj.data("code");
    var ward = formCart.find(".select-ward-cart").val();

    $.ajax({
        type: "POST",
        url:'api/cart.php',
        dataType: 'json',
        data: {
            cmd: 'delete-cart',
            code: code,
            ward: ward
        },
        beforeSend: function(){
            holdonOpen();
        },
        success: function(result){
            $('.count-cart').html(result.max);
            $('.icon-cart-header').attr('aria-label', 'Giỏ hàng có ' + result.max + ' sản phẩm');
            if(result.max)
            {
                formCart.find('.load-price-temp').html(result.tempText);
                formCart.find('.load-price-total').html(result.totalText);
                if(result.shipText) formCart.find('.load-price-ship').html(result.shipText);
                formCart.find(".procart-"+code).remove();
            }
            else
            {
                $(".wrap-cart").html('<a href="" class="empty-cart text-decoration-none"><i class="fa fa-cart-arrow-down"></i><p>'+LANG['no_products_in_cart']+'</p><span>'+LANG['back_to_home']+'</span></a>');
            }
            holdonClose();
        },
        error: function(xhr){
            var message = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Không thể xóa sản phẩm. Vui lòng thử lại.';
            showNotify(message, 'Thông báo', 'error');
            holdonClose();
        }
    });
}

function loadDistrict(id=0)
{
    $.ajax({
        type: 'post',
        url: 'api/district.php',
        data: {
            id_city: id
        },
        beforeSend: function(){
            holdonOpen();
        },
        success: function(result){
            $(".select-district").html(result);
            $(".select-ward").html('<option value="">'+LANG['ward']+'</option>');
            holdonClose();
        }
    });
}

function loadWard(id=0)
{
    $.ajax({
        type: 'post',
        url: 'api/ward.php',
        data: {
            id_district: id
        },
        beforeSend: function(){
            holdonOpen();
        },
        success: function(result){
            $(".select-ward").html(result);
            holdonClose();
        }
    });
}

/* Checkout uses the current two-level address model: city -> ward/commune. */
function loadWardByCity(id=0)
{
    $.ajax({
        type: 'post',
        url: 'api/ward-city.php',
        data: {
            id_city: id
        },
        beforeSend: function(){
            holdonOpen();
        },
        success: function(result){
            $(".select-ward-cart").html(result);
            holdonClose();
        },
        error: function(){
            $(".select-ward-cart").html('<option value="">'+LANG['ward']+'</option>');
            holdonClose();
        }
    });
}

/* Dải tab danh mục (.pts-category-tabs.is-sticky-row) không còn sticky nữa -
   chỉ hiện tự nhiên ở đầu trang rồi trôi mất khi cuộn qua, như nội dung bình
   thường (sếp 2026-10-02: "chỉ hiện đầu trang, không cần cuộn hiện xuống").
   JS ở đây chỉ còn 1 việc: bật bản "Loại X" trong sidebar (class
   pts-sidebar-category-visible trên body) ngay khi dải tab đã trôi qua khỏi
   đầu màn hình, để không mất khả năng chọn danh mục. Ngưỡng đo bằng
   KHOẢNG CÁCH THẬT từ đầu tài liệu tới dải tab (đo 1 lần lúc tải trang, lúc
   chưa cuộn) - trước đó lỡ dùng offsetHeight (chiều cao dải tab, ~47px) làm
   ngưỡng nên kích hoạt sai ngay khi vừa cuộn rất ít, kết hợp với sticky cũ
   gây dính chồng lên hero (lỗi sếp chụp ảnh báo lại). */
(function(){
    var tabsEl = document.querySelector('.pts-category-tabs.is-sticky-row');
    if(!tabsEl) return;
    var naturalTop = tabsEl.getBoundingClientRect().top + (window.scrollY || window.pageYOffset || 0);
    var ticking = false;
    function onScroll(){
        var currentY = window.scrollY || window.pageYOffset || 0;
        var pastTabs = currentY > naturalTop;
        document.body.classList.toggle('pts-sidebar-category-visible', pastTabs);
        ticking = false;
    }
    window.addEventListener('scroll', function(){
        if(!ticking){
            window.requestAnimationFrame(onScroll);
            ticking = true;
        }
    }, { passive: true });
    onScroll();
})();

function loadShip(id=0)
{
    if(SHIP_CART)
    {
        var formCart = $(".form-cart");

        $.ajax({
            type: "POST",
            url: "api/cart.php",
            dataType: 'json',
            data: {
                cmd: 'ship-cart',
                id: id
            },
            beforeSend: function(){
                holdonOpen();
            },
            success: function(result){
                if(result)
                {
                    formCart.find('.load-price-ship').html(result.shipText || 'Sẽ xác nhận sau');
                    formCart.find('.load-price-total').html(result.totalText);
                }   
                holdonClose();
            }
        }); 
    }
}
