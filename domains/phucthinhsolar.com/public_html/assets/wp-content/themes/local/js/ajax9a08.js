var $ = jQuery;
function loading(sel){
    jQuery(sel).attr('disabled','disabled');
    jQuery(sel).attr('data-loading',jQuery(sel).html());
    jQuery(sel).html('<i class="spinner-submit"></i>');
}
function unloading(sel){
    var loading=jQuery(sel).attr('data-loading');
    if(loading){
        jQuery(sel).html(loading);
        jQuery(sel).removeAttr('disabled')
    }
}
function add_notice($html){
    jQuery.notify({
        hr_content: $html,
    }, {
        style: 'hr_notice',
        autoHideDelay: 5000,
        clickToHide: false,
        globalPosition: 'bottom right',
    });
}
jQuery.notify.addStyle('hr_notice', {
    html:
        '<div class="alert_pop" data-notify-html="hr_content"></div>'
});
$( document ).ready( function() {
    /** Log in **/
    jQuery('#form_log_user').submit(function(){
        loading('.btn_registy');
        postall_or('hide_me',hr.a_url+'?action=login','log_user_name||log_user_pass||login_nonce||url_type||lang');
    });
    /** Registry **/
    jQuery('#form_reg_user').submit(function(){
        var err = 0;
        loading('.btn_registy');
        postall_or('hide_me',hr.a_url+'?action=registry','registry_nonce||reg_user_name||reg_user_phone||reg_user_pass||reg_gender_meta||reg_user_birthday||reg_user_birthmonth||reg_user_birthyear||reg_user_mail||url_type||lang');
    });
    jQuery(document).delegate(".dis_err", "click", function() {
        console.log('aa');
        jQuery(this).parents('.res-field').removeClass('hrError');
    });

    jQuery('.form_forget').submit(function(){
        loading('#find_pass_account');
        postall_or('hide_me',hr.a_url+'?action=SubmitForget','email_recover||lang');
    });
    jQuery('.form_change_pass').submit(function(){
        loading('.hrSubmitChangePass');
        postall_or('hide_me',hr.a_url+'?action=SubmitChangePassword','userid||hr_new_pass||hr_new_repass||lang');
    });
    /** filter order **/
    jQuery(document).delegate("#orders_in_ajax", 'change', function () {
        var meta_order_id = jQuery(this).val();
        jQuery('#woo_custom_orders').addClass('active_loading');
        postbyurl('woo_custom_orders', hr.a_url + '?action=filter_order_account_woo', 'meta_order_id=' + meta_order_id);
    });
    /** Video **/
    jQuery(document).delegate(".CRplay_vid", "click", function() {
        var data_vid = jQuery(this).attr('data-vid');
        var data_lang = jQuery('#data_lang').val();
        jQuery('.popup_footer').addClass('active');
        jQuery('.bg_popup_ft').addClass('active');
        jQuery('.popup_content_ft').addClass('active');
        jQuery('#popup_content_ft').addClass('active_loading');
        jQuery('#popup_content_ft').html('<div class="group_load"><div class="load-img"><div class="item_load"></div></div></div>');
        postbyurl('popup_content_ft', hr.a_url + '?action=load_vid', 'data_vid=' + data_vid + '&data_lang=' + data_lang);
    });
    /** Load more search **/
    jQuery(document).delegate(".search_all_bottom .load_more_btn", "click", function() {
        if (jQuery(this).attr('disabled') != 'disabled') {
            var input = jQuery("#CRkw");
            var kw = input.val();
            var page = jQuery(this).attr('data-page');
            var data_lang = jQuery('#data_lang').val();
            var data_posttype = jQuery(this).attr('data-posttype');
            var data_limit = jQuery(this).attr('data-limit');
            jQuery(this).attr('disabled','disabled');
            jQuery(this).attr('data-loading',jQuery(this).html());
            jQuery(this).html('<i class="spinner-submit"></i>');
            postbyurl('hide_me', hr.a_url + '?action=load_more_search_pd', 'page=' + page + '&kw=' + kw + '&data_posttype=' + data_posttype + '&data_limit=' + data_limit + '&data_lang=' + data_lang);
        }
    });
    /** Rating **/
    jQuery(document).delegate(".btn_submit_rating", "click", function() {
        var input = jQuery("#data_rating");
        var data_rating = input.val();
        var data_notice = jQuery(this).attr('data-notice');
        var data_id = jQuery(this).attr('data-id');
        var data_lang = jQuery('#data_lang').val();
        loading('.btn_submit_rating');
        if ( jQuery(this).hasClass("CRnot_log")) {
            add_notice('<font color="red">'+ data_notice +'</font>');
            unloading('.btn_submit_rating');
        }else {
            postbyurl('hide_me', hr.a_url + '?action=rating_product', 'data_rating=' + data_rating + '&data_id=' + data_id + '&data_lang=' + data_lang);
        }
    });
    /** Load more **/
    jQuery(document).delegate(".load_more_pd", "click", function() {
        jQuery('.compare_btn').removeClass('active');
        jQuery('#compare_list_pop').removeClass('active');
        if (jQuery(this).attr('disabled') != 'disabled') {
            var input = jQuery('#data_filter');
            var page = jQuery(this).attr('data-page');
            var tax = jQuery(this).attr('data-tax');
            var cat = jQuery(this).attr('data-cat');
            var data_lang = jQuery('#data_lang').val();
            var brand = input.attr('data-brand');
            var filter = input.attr('data-filter');
            var limit = input.attr('data-limit');
            var sort = input.attr('data-sort');
            jQuery(this).attr('disabled','disabled');
            jQuery(this).attr('data-loading',jQuery(this).html());
            jQuery(this).html('<i class="spinner-submit"></i>');
            postbyurl('hide_me', hr.a_url + '?action=load_more_pd', 'page=' + page + '&cat=' + cat + '&tax=' + tax + '&sort=' + sort + '&brand=' + brand + '&filter=' + filter + '&limit=' + limit + '&data_lang=' + data_lang);
        }
    });
    /** Blog **/
    jQuery(document).delegate(".CRload_blog .load_more_btn", "click", function() {
        if (jQuery(this).attr('disabled') != 'disabled') {
            var page = jQuery(this).attr('data-page');
            var data_lang = jQuery('#data_lang').val();
            var data_posttype = jQuery(this).attr('data-posttype');
            var data_limit = jQuery(this).attr('data-limit');
            var cat = jQuery(this).attr('data-cat');
            var name_tax = jQuery('#name_tax').val();
            jQuery(this).attr('disabled','disabled');
            jQuery(this).attr('data-loading',jQuery(this).html());
            jQuery(this).html('<i class="spinner-submit"></i>');
            postbyurl('hide_me', hr.a_url + '?action=load_more_post', 'page=' + page + '&name_tax=' + name_tax + '&cat=' + cat + '&data_posttype=' + data_posttype + '&data_limit=' + data_limit + '&data_lang=' + data_lang);
        }
    });
    /** Author **/
    jQuery(document).delegate("#CRauthor_list_post .load_more_btn", "click", function() {
        if (jQuery(this).attr('disabled') != 'disabled') {
            var page = jQuery(this).attr('data-page');
            var data_lang = jQuery('#data_lang').val();
            var author_id = jQuery('#CRauthor_meta').val();
            var data_posttype = jQuery(this).attr('data-posttype');
            var data_limit = jQuery(this).attr('data-limit');
            jQuery(this).attr('disabled','disabled');
            jQuery(this).attr('data-loading',jQuery(this).html());
            jQuery(this).html('<i class="spinner-submit"></i>');
            postbyurl('hide_me', hr.a_url + '?action=load_more_post_author', 'page=' + page + '&data_posttype=' + data_posttype + '&author_id=' + author_id + '&data_limit=' + data_limit + '&data_lang=' + data_lang);
        }
    });
    /** Product **/
    jQuery(document).delegate("#CRlist_products .load_more_btn", "click", function() {
        if (jQuery(this).attr('disabled') != 'disabled') {
            var input = jQuery("#all_data_filter");
            var promotion = input.attr('data-promotion');
            var material = input.attr('data-material');
            var color = input.attr('data-color');
            var capacity = input.attr('data-capacity');
            var lumens = input.attr('data-lumens');
            var disc = input.attr('data-disc');
            var sort = input.attr('data-sort');
            var price = input.attr('data-price');
            var page = jQuery(this).attr('data-page');
            var cat = input.attr('data-cat');
            var data_lang = jQuery('#data_lang').val();
            var data_posttype = jQuery(this).attr('data-posttype');
            var data_limit = jQuery(this).attr('data-limit');
            jQuery(this).attr('disabled','disabled');
            jQuery(this).attr('data-loading',jQuery(this).html());
            jQuery(this).html('<i class="spinner-submit"></i>');
            postbyurl('hide_me', hr.a_url + '?action=load_more_product', 'page=' + page + '&promotion=' + promotion + '&material=' + material + '&color=' + color + '&capacity=' + capacity + '&lumens=' + lumens + '&disc=' + disc + '&sort=' + sort + '&price=' + price + '&data_posttype=' + data_posttype + '&data_limit=' + data_limit + '&cat=' + cat + '&data_lang=' + data_lang);
        }
    });
    jQuery('.CRchange_filter').change(function(){
        var input = jQuery("#all_data_filter");
        var val = jQuery(this).val();
        var target = jQuery(this).attr('name');
        input.attr(target, val);
        CRrun_ajax_filter();
    });
    jQuery(document).delegate(".CRreset_filter", "click", function() {
        var input = jQuery("#all_data_filter");
        var val = 'all';
        input.attr('data-material', val);
        input.attr('data-color', val);
        input.attr('data-capacity', val);
        input.attr('data-lumens', val);
        input.attr('data-sort', 'new');
        input.attr('data-price', val);
        jQuery('.CRradio_box input').prop('checked', false);
        CRrun_ajax_filter();
    });
    /** Filter Mobile **/
    jQuery(document).delegate(".CRfilters_row_pd_mobile .CRpop_filter_left.CRpop_filter_def li a", "click", function() {
        if (jQuery(this).parent().hasClass("active")) {
            jQuery(this).parent().removeClass('active');
        } else {
            jQuery(this).parents('.sub_filter').find('li').removeClass('active');
            jQuery(this).parents('.sub_sort').find('li').removeClass('active');
            jQuery(this).parent().addClass('active');
        }
        var data_lang = jQuery('#data_lang').val();
        var data_cat = jQuery(this).attr('data-cat');
        var data_primary = jQuery(this).attr('data-primary');
        var id = 'CRload_pds_home_'+ data_primary;
        jQuery('html,body').animate({scrollTop: jQuery('#'+id).offset().top-230}, 'slow');
        jQuery('#'+id).addClass('active_loading');
        postbyurl(id, hr.a_url + '?action=load_pds_home', 'data_cat=' + data_cat + '&data_primary=' + data_primary + '&data_lang=' + data_lang);
    });
    // jQuery(document).delegate(".CRpop_filter_def li a", "click", function() {
    //     var input = jQuery("#all_data_filter");
    //     if (jQuery(this).parent().hasClass("active")) {
    //         jQuery(this).parent().removeClass('active');
    //         var val = 'all';
    //         var target = jQuery(this).attr('data-name');
    //         input.attr(target, val);
    //     } else {
    //         jQuery(this).parents('.sub_filter').find('li').removeClass('active');
    //         jQuery(this).parents('.sub_sort').find('li').removeClass('active');
    //         jQuery(this).parent().addClass('active');
    //         var val = jQuery(this).attr('data-val');
    //         var target = jQuery(this).attr('data-name');
    //         input.attr(target, val);
    //     }
    //     setTimeout(function () {
    //         var data_lang = jQuery('#data_lang').val();
    //         var cat = input.attr('data-cat');
    //         var material = input.attr('data-material-color');
    //         var stone = input.attr('data-stone-color');
    //         var disc = input.attr('data-disc');
    //         var gender = input.attr('data-gender');
    //         var occasion = input.attr('data-occasion');
    //         var size = input.attr('data-size');
    //         var golden = input.attr('data-golden');
    //         var limit = input.attr('data-limit');
    //         var sort = input.attr('data-sort');
    //         var price_min = input.attr('data-min-price');
    //         var price_max = input.attr('data-max-price');
    //         if (jQuery('#CRlist_products').length > 0) {
    //             jQuery('html,body').animate({scrollTop: jQuery("#CRlist_products").offset().top-180}, 'slow');
    //             jQuery('#CRlist_products').addClass('active_loading');
    //             postbyurl('CRlist_products', hr.a_url + '?action=filter_pd', 'sort=' + sort + '&material=' + material + '&stone=' + stone + '&disc=' + disc + '&price_min=' + price_min + '&price_max=' + price_max + '&cat=' + cat + '&gender=' + gender + '&occasion=' + occasion + '&size=' + size + '&golden=' + golden + '&limit=' + limit + '&data_lang=' + data_lang);
    //         }
    //         //jQuery('.CRpop_filter_def').removeClass('active');
    //     }, 1);
    //
    // });
    // jQuery(document).delegate(".CRremove_filter_mb a","click",function(event){
    //     jQuery('.sub_filter').find('li').removeClass('active');
    //     var input_max_price = jQuery("#CRmax_price_val").val();
    //     var input = jQuery("#all_data_filter");
    //     var $slider = $("#slider-range-mobile");
    //     $slider.slider("values", 0, 0);
    //     $slider.slider("values", 1, input_max_price);
    //     jQuery('#min_price_mb').val(0);
    //     jQuery('#max_price_mb').val(addCommas(input_max_price));
    //     input.attr("data-min-price", 0);
    //     input.attr("data-max-price", input_max_price);
    //     input.attr("data-material-color",'all');
    //     input.attr("data-stone-color", 'all');
    //     input.attr("data-gender", 'all');
    //     input.attr("data-cat", 'all');
    //     input.attr("data-size", 'all');
    //     input.attr("data-golden", 'all');
    //     input.attr("data-occasion", 'all');
    //     setTimeout(function () {
    //         var data_lang = jQuery('#data_lang').val();
    //         var cat = input.attr('data-cat');
    //         var material = input.attr('data-material-color');
    //         var stone = input.attr('data-stone-color');
    //         var disc = input.attr('data-disc');
    //         var gender = input.attr('data-gender');
    //         var occasion = input.attr('data-occasion');
    //         var size = input.attr('data-size');
    //         var golden = input.attr('data-golden');
    //         var limit = input.attr('data-limit');
    //         var sort = input.attr('data-sort');
    //         var price_min = input.attr('data-min-price');
    //         var price_max = input.attr('data-max-price');
    //         if (jQuery('#CRlist_products').length > 0) {
    //             jQuery('html,body').animate({scrollTop: jQuery("#CRlist_products").offset().top-180}, 'slow');
    //             jQuery('#CRlist_products').addClass('active_loading');
    //             postbyurl('CRlist_products', hr.a_url + '?action=filter_pd', 'sort=' + sort + '&material=' + material + '&stone=' + stone + '&disc=' + disc + '&price_min=' + price_min + '&price_max=' + price_max + '&cat=' + cat + '&gender=' + gender + '&occasion=' + occasion + '&size=' + size + '&golden=' + golden + '&limit=' + limit + '&data_lang=' + data_lang);
    //         }
    //         jQuery('.CRpop_filter_def').removeClass('active');
    //     }, 3);
    // });
    /** Account **/
    jQuery('#update_account_woo').submit(function(){
        loading('.btn_registy');
        postall_or('hide_me',hr.a_url+'?action=update_account_woo','userid||email_update_ac||full_name_update_ac||birthday_update_ac||birthmonth_update_ac||birthyear_update_ac||reg_id_photo||gender_hidden||lang_ac');
    });
    jQuery('#change_pass_account_woo').submit(function(){
        loading('.btn_registy');
        postall_or('hide_me',hr.a_url+'?action=change_pass_account_woo','userid||old_pass_ac||new_pass_ac||confirm_new_pass_ac||lang_ac');
    });
    /** Store **/
    jQuery('#form_filter_store').submit(function(){
        loading('.btn_store');
        postall_or('hide_me',hr.a_url+ '?action=form_filter_store','tp_store||qh_store||sl_store||lang_form_filter_store');
    });
    jQuery(document).delegate("select#tp_store", "change", function() {
        var val = jQuery(this).val();
        var data_lang = jQuery('#data_lang').val();
        jQuery('#qh_store').addClass('active_loading');
        jQuery('#sl_store').addClass('active_loading');
        postbyurl('qh_store', hr.a_url + '?action=load_term_child_sel', 'val=' + val + '&data_lang=' + data_lang);
        postbyurl('sl_store', hr.a_url + '?action=load_store_sel', 'val=' + val + '&data_lang=' + data_lang);
    });
    jQuery(document).delegate("select#qh_store", "change", function() {
        var val = jQuery(this).val();
        var data_lang = jQuery('#data_lang').val();
        jQuery('#sl_store').addClass('active_loading');
        postbyurl('sl_store', hr.a_url + '?action=load_store_sel', 'val=' + val + '&data_lang=' + data_lang);
    });
    jQuery(document).delegate(".click_map", "click", function() {
        var id = jQuery(this).attr('data-id');
        var data_lang = jQuery('#data_lang').val();
        jQuery('#map_store').addClass('active_loading');
        postbyurl('map_store', hr.a_url + '?action=load_map_store', 'id=' + id + '&data_lang=' + data_lang);
    });
    /** Quickview **/
    jQuery(document).delegate(".CRquickview_btn", "click", function() {
        var data_id = jQuery(this).attr('data-id');
        var data_lang = jQuery('#data_lang').val();
        var btn = '#CRquickview_btn_' + data_id;
        jQuery(btn).attr('disabled','disabled');
        jQuery(btn).attr('data-loading',jQuery(btn).html());
        jQuery(btn).html('<i class="spinner-submit"></i>');
        jQuery('.popup_footer').addClass('active');
        jQuery('.bg_popup_ft').addClass('active');
        jQuery('.popup_content_ft').addClass('active');
        jQuery('#popup_content_ft').addClass('active_loading');
        //jQuery('#popup_content_ft').html('<div class="group_load"><div class="load-img"><div class="item_load"></div></div></div>');
        postbyurl('popup_content_ft', hr.a_url + '?action=load_quickview', 'data_id=' + data_id + '&data_lang=' + data_lang);
    });
    jQuery(document).delegate(".CRaddCart_btn_pd", "click", function() {
        if (jQuery(this).hasClass("hide")) {
            return;
        }
        var data_type = jQuery(this).attr('data-type');
        var data_id = jQuery(this).attr('data-id');
        var data_lang = jQuery('#data_lang').val();
        var qty = eval(jQuery('.CRcustom_qty .qty').val());
        console.log(qty);
        jQuery(this).attr('disabled','disabled');
        jQuery(this).attr('data-loading',jQuery(this).html());
        jQuery(this).html('<i class="spinner-submit"></i>');
        postbyurl('hide_me', hr.a_url + '?action=CRload_add_cart', 'data_id=' + data_id + '&qty=' + qty + + '&data_type=' + data_type + '&data_lang=' + data_lang);
    });
    jQuery(document).delegate(".CRquickAdd_btn", "click", function() {
        if (jQuery(this).hasClass("CRbtn_out_stock")) {
            add_notice('<font color="red">Sản phẩm hết hàng</font>');
            return;
        }
        var data_type = jQuery(this).attr('data-type');
        var data_id = jQuery(this).attr('data-id');
        var data_lang = jQuery('#data_lang').val();
        var qty = eval(jQuery(this).attr('data-qty'));
        jQuery(this).attr('disabled','disabled');
        jQuery(this).attr('data-loading',jQuery(this).html());
        jQuery(this).html('<i class="spinner-submit"></i>');
        postbyurl('hide_me', hr.a_url + '?action=CRload_add_cart', 'data_id=' + data_id + '&qty=' + qty + + '&data_type=' + data_type + '&data_lang=' + data_lang);
    });
    /** Home Products **/
    jQuery(document).delegate(".CRterms_vertical a", "click", function() {
        if (jQuery(this).parent().hasClass("active")) {
            jQuery(this).parent().removeClass('active');
        } else {
            jQuery(this).parents('.CRterms_vertical').find('li').removeClass('active');
            jQuery(this).parent().addClass('active');
        }
        var data_lang = jQuery('#data_lang').val();
        var data_cat = jQuery(this).attr('data-cat');
        var data_primary = jQuery(this).attr('data-primary');
        var id = 'CRload_pds_home_'+ data_primary;
        jQuery('html,body').animate({scrollTop: jQuery('#'+id).offset().top-230}, 'slow');
        jQuery('#'+id).addClass('active_loading');
        postbyurl(id, hr.a_url + '?action=load_pds_home', 'data_cat=' + data_cat + '&data_primary=' + data_primary + '&data_lang=' + data_lang);
    });
    /** Promotion **/
    jQuery(document).delegate("#CRlist_promotion .load_more_btn", "click", function() {
        if (jQuery(this).attr('disabled') != 'disabled') {
            var page = jQuery(this).attr('data-page');
            var data_lang = jQuery('#data_lang').val();
            var data_limit = jQuery(this).attr('data-limit');
            jQuery(this).attr('disabled','disabled');
            jQuery(this).attr('data-loading',jQuery(this).html());
            jQuery(this).html('<i class="spinner-submit"></i>');
            postbyurl('hide_me', hr.a_url + '?action=load_more_term_promotion', 'page=' + page + '&data_limit=' + data_limit + '&data_lang=' + data_lang);
        }
    });
    /** Open Pop Cart **/
    jQuery(document).delegate(".CRopen_pop_cart", "click", function() {
        jQuery('body').addClass('CRactive');
        jQuery('#CRpop_cart').addClass('active');
    });
    /** Remove cart **/
    jQuery(document).delegate(".CRbtn_remove_pd", "click", function() {
        var data_id = jQuery(this).attr('data-id');
        jQuery(this).attr('disabled','disabled');
        jQuery(this).attr('data-loading',jQuery(this).html());
        jQuery(this).html('<i class="spinner-submit"></i>');
        jQuery('#CRload_list_cart').addClass('active_loading');
        postbyurl('hide_me', hr.a_url + '?action=CRload_remove_cart', 'data_id=' + data_id);
    });
    /** Open popup form order **/
    jQuery(document).delegate(".CRbtn_order_out_stock", "click", function() {
        var data_id = jQuery(this).attr('data-id');
        jQuery('body').addClass('CRactive');
        jQuery('.popup_order_product').addClass('active');
        jQuery('.bg_popup_order_pd').addClass('active');
        jQuery('.popup_content_order_pd').addClass('active');
        jQuery('#popup_content_order_pd').addClass('active_loading');
        jQuery('#CR_detail_order_pd').addClass('active_loading');
        postbyurl('CR_detail_order_pd', hr.a_url + '?action=load_pop_order', 'data_id=' + data_id);
    });
    jQuery('.bg_popup_order_pd').click(function () {
        jQuery('body').removeClass('CRactive');
        jQuery('.popup_order_product').removeClass('active');
        jQuery('.bg_popup_order_pd').removeClass('active');
        jQuery('#CR_detail_order_pd').removeClass('active');
        setTimeout(function () {
            jQuery('#CR_detail_order_pd').html('');
        }, 1);
    });
    handleCheckBuySame();
    jQuery(document).delegate(".CRadd_cart_purchase", "click", function() {
        if (jQuery(this).hasClass("hide")) {
            return;
        }
        var data_purchase = jQuery('#CRval_purchase').val();
        jQuery(this).attr('disabled','disabled');
        jQuery(this).attr('data-loading',jQuery(this).html());
        jQuery(this).html('<i class="spinner-submit"></i>');
        postbyurl('hide_me', hr.a_url + '?action=CRload_add_purchase', 'data_purchase=' + data_purchase);
    });
    /* Load Main Youtube */
    jQuery(document).delegate(".CRitem_yt .cont a", "click", function() {
        jQuery('.CRitem_yt').removeClass("active");
        jQuery(this).parents('.CRitem_yt').addClass("active");
        var data_id = jQuery(this).attr('data-id');
        jQuery('#CRyt_main').html('<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' + data_id +'"></iframe>');
    });
    jQuery(document).delegate(".CRitem_yt_term .cont a", "click", function() {
        jQuery('.CRitem_yt_term').removeClass("active");
        jQuery(this).parents('.CRitem_yt_term').addClass("active");
        var data_id = jQuery(this).attr('data-id');
        jQuery('#CRyt_term__main').html('<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' + data_id +'"></iframe>');
    });
    
    /* Ajax Load More */
    jQuery(document).delegate("#CRblog_page .inf_loadmore_btn", "click", function() {
        var data_id = jQuery('#inf_data_load').attr('data-id'),
            data_tax = jQuery('#inf_data_load').attr('data-tax'),
            data_post = jQuery('#inf_data_load').attr('data-post'),
            data_limit = jQuery(this).attr('data-limit'),
            data_page = jQuery(this).attr('data-page'),
            data_max = jQuery(this).attr('data-max');
        jQuery.ajax({
            url: hr.a_url+'?action=inf_loadmore',
            type: 'post',
            data :  {
                data_id: data_id,
                data_tax: data_tax,
                data_post: data_post,
                data_limit: data_limit,
                data_page: data_page,
                data_max: data_max
            },
            error: function (request) {
                console.log(request);
                unloading('.inf_loadmore_btn');
            },
            beforeSend: function () {
                loading('.inf_loadmore_btn');
            },
            success: function(data, textStatus) {
                jQuery("#inf_loadmore_post").append(data);
                var numPage = jQuery(".inf_loadmore_btn").attr('data-page'),
                    numMax = jQuery(".inf_loadmore_btn").attr('data-max');
                if(eval(numPage)>=eval(numMax)){
                    jQuery(".inf_btn_wrap").remove();
                } else {
                    jQuery(".inf_loadmore_btn").attr('data-page',eval(numPage) + 1);
                    unloading('.inf_loadmore_btn');
                }
            },
        });
    });

    /* Ajax Filter Product */
    jQuery(document).delegate(".item_filter a", "click", function() {
        var data_filter = jQuery(this).attr('data-filter'),
            data_limit = jQuery('#inf_data_product_page').attr('data-limit'),
            data_id = jQuery('#inf_data_product_page').attr('data-id');
        if (jQuery(this).parents('.item_filter').hasClass('active')) {
            return;
        } else  {
            jQuery('.item_filter').removeClass("active");
            jQuery(this).parents('.item_filter').addClass("active");
        }
        jQuery.ajax({
            url: hr.a_url+'?action=inf_load_filter_product',
            type: 'post',
            data :  {
                data_filter: data_filter,
                data_id: data_id,
                data_limit: data_limit,
            },
            error: function (request) {
                console.log(request);
            },
            beforeSend: function () {
                jQuery("#inf_list_pds").addClass('inf_load');
            },
            success: function(data, textStatus) {
                jQuery("#inf_list_pds").html(data);
                jQuery("#inf_list_pds").removeClass('inf_load');
            },
        });
    });

    /* Ajax Load More Product */
    jQuery(document).delegate("#inf_another_product_page .inf_loadmore_btn", "click", function() {
        var data_id = jQuery('#inf_data_product_page').attr('data-id'),
            data_tax = jQuery('#inf_data_product_page').attr('data-tax'),
            data_post = jQuery('#inf_data_product_page').attr('data-post'),
            data_limit = jQuery(this).attr('data-limit'),
            data_page = jQuery(this).attr('data-page'),
            data_max = jQuery(this).attr('data-max');
        jQuery.ajax({
            url: hr.a_url+'?action=inf_loadmore_pds',
            type: 'post',
            data :  {
                data_id: data_id,
                data_tax: data_tax,
                data_post: data_post,
                data_limit: data_limit,
                data_page: data_page,
                data_max: data_max
            },
            error: function (request) {
                console.log(request);
                unloading('.inf_loadmore_btn');
            },
            beforeSend: function () {
                loading('.inf_loadmore_btn');
            },
            success: function(data, textStatus) {
                jQuery("#inf_list_product_load").append(data);
                var numPage = jQuery(".inf_loadmore_btn").attr('data-page'),
                    numMax = jQuery(".inf_loadmore_btn").attr('data-max');
                if(eval(numPage)>=eval(numMax)){
                    jQuery(".inf_btn_wrap").remove();
                } else {
                    jQuery(".inf_loadmore_btn").attr('data-page',eval(numPage) + 1);
                    unloading('.inf_loadmore_btn');
                }
            },
        });
    });
    jQuery(document).delegate(".filter_product select", "change", function(event) {
        event.preventDefault()
        jQuery('html,body').animate({
                scrollTop: jQuery("#inf_list_pds").offset().top - 250},
            'slow');

        var id = jQuery(this).val();
        console.log(id)
        jQuery("#data_filter").attr("data-page",1);
        jQuery('#data_filter').attr('order',id)

        var input = jQuery('#data_filter');
        page = input.attr('data-page');
        term = input.attr('term')
        brand = input.attr('brand')
        price_from = input.attr('price-from')
        price_to = input.attr('price-to')
        order = input.attr('order')
        console.log(order)

        jQuery('#inf_list_product_load_1').empty().addClass('active')
        postbyurl('inf_list_product_load_1', hr.a_url + '?action=inf_list_product_load_1','term=' + term + '&brand=' + brand + '&price_from=' + price_from + '&price_to=' + price_to + '&order=' + order + '&page=' + page);
    })
    jQuery(document).delegate(".filter-brand li", "click", function(event) {
        event.preventDefault()
        // jQuery(this).attr("checked", "checked");
        jQuery('.filter-brand li').removeClass('check')
        jQuery(this).addClass('check')
        jQuery('html,body').animate({
                scrollTop: jQuery("#inf_list_pds").offset().top - 250},
            'slow');

        var id = jQuery(this).attr('data-id');
        console.log(id)
        jQuery("#data_filter").attr("data-page",1);
        jQuery('#data_filter').attr('brand',id)

        var input = jQuery('#data_filter');
        page = input.attr('data-page');
        term = input.attr('term')
        brand = id;
        price_from = input.attr('price-from')
        price_to = input.attr('price-to')
        order = input.attr('order')
        console.log(order)

        jQuery('#inf_list_product_load_1').empty().addClass('active')
        postbyurl('inf_list_product_load_1', hr.a_url + '?action=inf_list_product_load_1','term=' + term + '&brand=' + brand + '&price_from=' + price_from + '&price_to=' + price_to + '&order=' + order + '&page=' + page);
    })
    jQuery(document).delegate(".filter-brand li.check", "click", function(event) {
        event.preventDefault()
        // jQuery(this).attr("checked", "checked");
        jQuery(this).removeClass('check')
        jQuery('html,body').animate({
                scrollTop: jQuery("#inf_list_pds").offset().top - 250},
            'slow');

        var id = jQuery(this).attr('data-id');
        console.log(id)
        jQuery("#data_filter").attr("data-page",1);
        jQuery('#data_filter').attr('brand','all')

        var input = jQuery('#data_filter');
        page = input.attr('data-page');
        term = input.attr('term')
        brand = 'all';
        price_from = input.attr('price-from')
        price_to = input.attr('price-to')
        order = input.attr('order')
        console.log(order)

        jQuery('#inf_list_product_load_1').empty().addClass('active')
        postbyurl('inf_list_product_load_1', hr.a_url + '?action=inf_list_product_load_1','term=' + term + '&brand=' + brand + '&price_from=' + price_from + '&price_to=' + price_to + '&order=' + order + '&page=' + page);
    })
    jQuery(document).delegate(".load_paging", "click", function() {
        // jQuery('#load_more_coupon').empty();
        jQuery('html,body').animate({
                scrollTop: jQuery("#inf_list_pds").offset().top - 150},
            'slow');

        jQuery('.paging a').removeClass('active');
        jQuery(this).addClass('active');
        var data_page = jQuery(this).attr('data-paging');
        jQuery("#data_filter").attr("data-page",data_page);
        var input = jQuery('#data_filter');
        page = input.attr('data-page');
        term = input.attr('term')
        brand = input.attr('brand')
        price_from = input.attr('price-from')
        price_to = input.attr('price-to')
        order = input.attr('order')
        console.log( brand)
        console.log( order)

        jQuery('#inf_list_product_load_1').empty().addClass('active')
        postbyurl('inf_list_product_load_1', hr.a_url + '?action=inf_list_product_load_1','term=' + term + '&brand=' + brand + '&price_from=' + price_from + '&price_to=' + price_to + '&order=' + order + '&page=' + page);
    });

} );
function deleteProductCompare(url, id) {
    jQuery.ajax({
        url: url,
        type: 'post',
        data: {
            action: 'remove_compare',
            id: id
        },
        success: function (response) {
            location.reload();
        }
    });
}
function changefilter(id,at,input,e){
    var text = jQuery(id).attr(at),
        arrayText = text ? text.split(',') : [],
        newText = jQuery(input).val(),
        index = arrayText.indexOf(newText);

    if (e.target.checked) {
        arrayText.push(newText);
    } else {
        arrayText.splice(index, 1);
    }
    jQuery.unique(arrayText.sort());
    jQuery(id).attr(at, arrayText.join(','));
}
function addCommas(nStr) {
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}
function CRrun_ajax_filter() {
    setTimeout(function () {
        var input = jQuery("#all_data_filter");
        var data_lang = jQuery('#data_lang').val();
        var promotion = input.attr('data-promotion');
        var cat = input.attr('data-cat');
        var material = input.attr('data-material');
        var color = input.attr('data-color');
        var capacity = input.attr('data-capacity');
        var lumens = input.attr('data-lumens');
        var disc = input.attr('data-disc');
        var limit = input.attr('data-limit');
        var sort = input.attr('data-sort');
        var price = input.attr('data-price');
        if (jQuery('#CRlist_products').length > 0) {
            jQuery('html,body').animate({scrollTop: jQuery("#CRlist_products").offset().top-220}, 'slow');
            jQuery('#CRlist_products').addClass('active_loading');
            postbyurl('CRlist_products', hr.a_url + '?action=filter_pd', 'sort=' + sort + '&promotion=' + promotion + '&material=' + material + '&color=' + color + '&capacity=' + capacity + '&lumens=' + lumens + '&disc=' + disc + '&price=' + price + '&cat=' + cat + '&limit=' + limit + '&data_lang=' + data_lang);
        }
    }, 1);
}
function handleCheckBuySame() {
    jQuery(document).on('click', '.box_purchase .box_check input[type="checkbox"]', function() {
        var valHidden = jQuery('#CRval_purchase').val(),
            currentValue = jQuery(this).attr('value');

        if (valHidden) {
            valHidden = valHidden.split(',');
        } else {
            valHidden = [];
        }

        if (valHidden.indexOf(currentValue) === -1) {
            valHidden.push(currentValue);
        } else {
            valHidden = valHidden.filter(function (item) {return item !== currentValue});
        }

        jQuery('#CRval_purchase').val(valHidden.join(','));

        var data_purchase = jQuery('#CRval_purchase').val();
        postbyurl('hide_me', hr.a_url + '?action=return_price_purchase', 'data_purchase=' + data_purchase );
    });
}
function product_filter() {

}
