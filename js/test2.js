/*function date_(format, timestamp) {  
  //   example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400);  //   returns 1: '09:09:40 m is month'
  //   example 2: date('F j, Y, g:i a', 1062462400);  //   returns 2: 'September 2, 2003, 2:26 am'
  //   example 3: date('Y W o', 1062462400);  //   returns 3: '2003 36 2003'
  //   example 4: x = date('Y m d', (new Date()).getTime()/1000);  //   example 4: (x+'').length == 10 // 2009 01 09  //   returns 4: true
  //   example 5: date('W', 1104534000);  //   returns 5: '53'
  //   example 6: date('B t', 1104534000);  //   returns 6: '999 31'
  //   example 7: date('W U', 1293750000.82); // 2010-12-31  //   returns 7: '52 1293750000'
  //   example 8: date('W', 1293836400); // 2011-01-01  //   returns 8: '52'
  //   example 9: date('W Y-m-d', 1293974054); // 2011-01-02  //   returns 9: '52 2011-01-02'
  var that = this;
  var jsdate, f;
  var txt_words = [
    'Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб',
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
  ];
  var formatChr = /\\?(.?)/gi;
  var formatChrCb = function(t, s) {
    return f[t] ? f[t]() : s;
  };
  var _pad = function(n, c) {
    n = String(n);
    while (n.length < c) {
      n = '0' + n;
    }
    return n;
  };
  f = {
    // Day
    d: function() { // Day of month w/leading 0; 01..31
      return _pad(f.j(), 2);
    },
    D: function() { // Shorthand day name; Mon...Sun
      return f.l()
        .slice(0, 3);
    },
    j: function() { // Day of month; 1..31
      return jsdate.getDate();
    },
    l: function() { // Full day name; Monday...Sunday
      return txt_words[f.w()] + 'day';
    },
    N: function() { // ISO-8601 day of week; 1[Mon]..7[Sun]
      return f.w() || 7;
    },
    S: function() { // Ordinal suffix for day of month; st, nd, rd, th
      var j = f.j();
      var i = j % 10;
      if (i <= 3 && parseInt((j % 100) / 10, 10) == 1) {
        i = 0;
      }
      return ['st', 'nd', 'rd'][i - 1] || 'th';
    },
    w: function() { // Day of week; 0[Sun]..6[Sat]
      return jsdate.getDay();
    },
    z: function() { // Day of year; 0..365
      var a = new Date(f.Y(), f.n() - 1, f.j());
      var b = new Date(f.Y(), 0, 1);
      return Math.round((a - b) / 864e5);
    },
    // Week
    W: function() { // ISO-8601 week number
      var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3);
      var b = new Date(a.getFullYear(), 0, 4);
      return _pad(1 + Math.round((a - b) / 864e5 / 7), 2);
    },
    // Month
    F: function() { // Full month name; January...December
      return txt_words[6 + f.n()];
    },
    m: function() { // Month w/leading 0; 01...12
      return _pad(f.n(), 2);
    },
    M: function() { // Shorthand month name; Jan...Dec
      return f.F()
        .slice(0, 3);
    },
    n: function() { // Month; 1...12
      return jsdate.getMonth() + 1;
    },
    t: function() { // Days in month; 28...31
      return (new Date(f.Y(), f.n(), 0))
        .getDate();
    },
    // Year
    L: function() { // Is leap year?; 0 or 1
      var j = f.Y();
      return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0;
    },
    o: function() { // ISO-8601 year
      var n = f.n();
      var W = f.W();
      var Y = f.Y();
      return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0);
    },
    Y: function() { // Full year; e.g. 1980...2010
      return jsdate.getFullYear();
    },
    y: function() { // Last two digits of year; 00...99
      return f.Y()
        .toString()
        .slice(-2);
    },
    // Time
    a: function() { // am or pm
      return jsdate.getHours() > 11 ? 'pm' : 'am';
    },
    A: function() { // AM or PM
      return f.a()
        .toUpperCase();
    },
    B: function() { // Swatch Internet time; 000..999
      var H = jsdate.getUTCHours() * 36e2;
      // Hours
      var i = jsdate.getUTCMinutes() * 60;
      // Minutes
      var s = jsdate.getUTCSeconds(); // Seconds
      return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3);
    },
    g: function() { // 12-Hours; 1..12
      return f.G() % 12 || 12;
    },
    G: function() { // 24-Hours; 0..23
      return jsdate.getHours();
    },
    h: function() { // 12-Hours w/leading 0; 01..12
      return _pad(f.g(), 2);
    },
    H: function() { // 24-Hours w/leading 0; 00..23
      return _pad(f.G(), 2);
    },
    i: function() { // Minutes w/leading 0; 00..59
      return _pad(jsdate.getMinutes(), 2);
    },
    s: function() { // Seconds w/leading 0; 00..59
      return _pad(jsdate.getSeconds(), 2);
    },
    u: function() { // Microseconds; 000000-999000
      return _pad(jsdate.getMilliseconds() * 1000, 6);
    },
    e: function() { 
      throw 'Not supported (see source code of date() for timezone on how to add support)';
    },
    I: function() { // DST observed?; 0 or 1
      var a = new Date(f.Y(), 0);
      var c = Date.UTC(f.Y(), 0);
      var b = new Date(f.Y(), 6);
      var d = Date.UTC(f.Y(), 6); // Jul 1 UTC
      return ((a - c) !== (b - d)) ? 1 : 0;
    },
    O: function() { // Difference to GMT in hour format; e.g. +0200
      var tzo = jsdate.getTimezoneOffset();
      var a = Math.abs(tzo);
      return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4);
    },
    P: function() { // Difference to GMT w/colon; e.g. +02:00
      var O = f.O();
      return (O.substr(0, 3) + ':' + O.substr(3, 2));
    },
    T: function() { // Timezone abbreviation; e.g. EST, MDT, ...      
      return 'UTC';
    },
    Z: function() { // Timezone offset in seconds (-43200...50400)
      return -jsdate.getTimezoneOffset() * 60;
    },
    // Full Date/Time
    c: function() { // ISO-8601 date.
      return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb);
    },
    r: function() { // RFC 2822
      return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb);
    },
    U: function() { // Seconds since UNIX epoch
      return jsdate / 1000 | 0;
    }
  };
  this.date = function(format, timestamp) {
    that = this;
    jsdate = (timestamp === undefined ? new Date() : // Not provided
      (timestamp instanceof Date) ? new Date(timestamp) : // JS Date()
      new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
    );
    return format.replace(formatChr, formatChrCb);
  };
  return this.date(format, timestamp);
}*/


//************************** ORDERs ****************************
function TabStatus(status){
            /*var page = $_GET('page') ? $_GET('page') : '';    
            if(page){
                var stateObj = { foo: "orders" };
                history.pushState(stateObj, "statuses", '?status='+status+'&page='+page);
            }else{
                var stateObj = { foo: "orders" };
                history.pushState(stateObj, "statuses", '?status='+status);    
            } */
        var stateObj = { foo: "orders" };
        history.pushState(stateObj, "statuses", '?status='+status); 
    //$('#filter-search').html('');    
    //$("form tr.data-row-search input[type='text']").val('');  
    //$("form tr.data-row-search select").val('');      
        AJAX_SEARCH();
}
function UpdateStatusesOrderCount(){    
    
    $.ajax({
        url: window.location.protocol+"//" + window.location.hostname + "/ajax_StatusesOrder",
        method: 'POST',
        data : {status:status},
        headers: {'X-Csrf-Token': AJAX_TOKEN()},
        beforeSend: function(){ /*$('section').ShowOverlayLoading(); */ },
        success: function(data){
            
            if( data.length !== 0){              
                $('#ul-statusy li a').each(function(){
                    var attr_id = $(this).attr('id'); 
                    var arr = attr_id.split('-');
                    var id = arr[2];                   
                    $('#tab-status-'+id).find('b').html(data.count_orders[id]);
                    
                        if(data.count_orders[id] < 1){
                            $('#tab-status-'+id).removeAttr('onclick');
                            $('#tab-status-'+id).css('opacity','0.5');
                            $('.table-data tbody').html('');
                        }else{
                            $('#tab-status-'+id).attr('onclick','TabStatus(\''+id+'\');');
                            $('#tab-status-'+id).css('opacity','1.0');
                        }
                });
                
                //$('.tab-status-active').click(); 
                    $('section').HideOverlayLoading();	
                    if($('.modal-window').length > 0){
                        modal_window_close_all(); 
                    }
                
                    //$('#button-search').click();
                    //AJAX_SEARCH();
            }
            
                    var attr_id_ = $('.tab-status-active').attr('id');                    
                    var arr_ = attr_id_.split('-');
                    var id = arr_[2]; 
                    
                    if(data.count_orders[id] < 1){
                        $('.tab-status-active').removeAttr('onclick');
                        $('.tab-status-active').css('opacity','0.5');
                        $('.table-data tbody').html('');
                    }else{
                        $('.tab-status-active').attr('onclick','TabStatus(\''+id+'\');');
                        $('.tab-status-active').css('opacity','1.0');
                    } 
            AJAX_SEARCH();        
                        
        },
        complete: function(){
            //$('#button-search').click();
            //AJAX_SEARCH();
        }
    });
}

function ChangeSeveralStatusesModal(){    
    var tr = $('.selected-row').length;
    if(tr === 1){
        // ничео не делать
    }else{
        var IDS = '';
        $('.selected-row').each(function(){
            var id = $(this).attr('id');
            IDS += id+',';            
        });        
        modal_window_show(false,'Изменение статуса для нескольких заказов','<div id="modal-window-data-change-status"></div>',false,false);
                        $('#modal-window-data-change-status').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>')
                            .load('/include/modal_change_status_orders.php?ids='+IDS);        
    }    
}
function ChangeSeveralStatuses(callback=null){
    $('#button-save-modal').attr('disabled',true).html('<img src="'+window.location.protocol+'//'+ window.location.hostname +'/style/img/load.gif" style="margin: 0px 0px 0px;"> Сохранение...');
    
        $.ajax({
            url: window.location.protocol+"//" + window.location.hostname + "/ChangeSeveralStatuses",
            method: 'POST',
            data : $('#form-modal-change-status-orders').serialize(),
            headers: {'X-Csrf-Token': AJAX_TOKEN()},
            beforeSend: function(){ },
            success: function(data){

                if(data===''){

                    UpdateStatusesOrderCount();
                }else{
                    $('#button-save-modal').attr('disabled',false).html('Сохранить');
                    $('section').HideOverlayLoading();
                    var number_modal = $('#modal-window-data-change-status').closest('.modal-window').attr('id');
                    number_modal = number_modal.split('-');

                    modal_window_close(number_modal[1]);
                    modal_window_show('alert','Результат AJAX-запроса',data,'','error');
                }   
            },
            complete:function(){
              if( !!callback ) callback();
            }
        });
}

/*function Count_new_Orders(){
        $.ajax({
            url: window.location.protocol+"//" + window.location.hostname + "/count_new_orders",
            method: 'POST',
            data : {},
            headers: {'X-Csrf-Token': AJAX_TOKEN()},
            beforeSend: function(){ },
            success: function(data){
                $('#info-count_new-orders').text(data);
            }
        });
}*/

function Orders(type,id,order_id,datetime,date_update, add_settings=""){
    //t=event.target||event.srcElement; 
    var query_str = '';
    if( !!add_settings ){
      query_str = jQuery.param(add_settings);
    }
    switch (type){
        case 'new':
            modal_window_show(false,'Добавление нового заказа','<div id="modal-window-order"></div>',false,false);
                        $('#modal-window-order').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>')
                            .load('/include/modal_orders.php?'+query_str);
        break;
        case 'edit': 
            modal_window_show(false,'Заказ № '+id+' ['+order_id+'] от '+datetime+' (Изменено: '+date_update+')','<div id="modal-window-order"></div>',false,false);
                        $('#modal-window-order').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>')
                            .load('/include/modal_orders.php?id='+id+'&'+query_str);
                $('.table-data tbody tr').each(function(){
                    $(this).removeClass('selected-row');
                });
                $('#'+id).closest('tr').addClass('selected-row');
                $('#'+id).closest('tr').find('td').eq(0).find('input[type="checkbox"]').prop('checked', true);
                COUNT_SELECTED_ROW_in_TABLE();
        break;
        case 'copy':
            //var id = $('.selected-row').attr('id');
            modal_window_show(false,'Новый заказ (копия из существующего)','<div id="modal-window-order"></div>',false,false);
                        $('#modal-window-order').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>')
                            .load('/include/modal_orders.php?id='+id+'&copy=1'+'&'+query_str);
        break;
        case 'info': 
            modal_window_show(false,'Заказ № '+id+' ['+order_id+'] от '+datetime+' (Изменено: '+date_update+')','<div id="modal-window-order-info"></div>',false,false);
                        $('#modal-window-order-info').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>')
                            .load('/include/modal_orders_info.php?id='+id+''+'&'+query_str);
                $('.table-data tbody tr').each(function(){
                    $(this).removeClass('selected-row');
                });
                $('#'+id).closest('tr').addClass('selected-row');
                $('#'+id).closest('tr').find('td').eq(0).find('input[type="checkbox"]').prop('checked', true);
                COUNT_SELECTED_ROW_in_TABLE();
        break;        
    }
}







function collectTemplateOrdersRow(data){

    var PHONE; 
    var phone_title;
    //var LOCKED_ORDERS = [];
    var locked_class = '';
    
    var template_orders_table = '';
        /*$.each(data.locked_orders, function(i,item){
            LOCKED_ORDERS[i] = item.order_id;
        });*/

    if( data.length !== 0){ 

        var listenPhoneRec = data.listenPhoneRec;

        

        $.each(data.array, function(i,item){
            // console.log(item);
            // throw Error('bob');
            var file_rec_id = (item.file_rec_id && listenPhoneRec > 0) ? '<i class="fa fa-play-circle" onclick="PlayRecordsSIP(\''+item.order_id+'\');" style="cursor:pointer; color:#4385C2;"></i>' : '';

            var order_new = item.new > 0 ? '<div class="blink">новый</div>' : item.order_id+' '+file_rec_id;
            /*var bayer_name = item.bayer_name.toLowerCase().replace(/^[\u00C0-\u1FFF\u2C00-\uD7FF\w]|\s[\u00C0-\u1FFF\u2C00-\uD7FF\w]/g, function(letter) {
                    return letter.toUpperCase();
                });*/
            var bayer_name = item.bayer_name || '';
            var total = item.total > 0 ? '<span style="color:#000;">'+item.total+'</span>' : '<span style="color:red;">'+item.total+'</span>';
            
            //var comment_ = item.comment.replace(/\r?\n/g, '<br>');
            var comment = getCommentTemplateRow(item );
            
            var payment = item.payment > 0 ? '<img src="'+window.location.protocol+'//'+window.location.hostname+'/style/img/icons/'+item.payment_icon+'" style="float:left; margin:-2px 3px 0 0;"> <span style="color:'+item.payment_color+'">'+item.payment_name+'</span>'  : '';
            var delivery = item.delivery ? '<img src="'+window.location.protocol+'//'+window.location.hostname+'/style/img/icons/'+item.delivery_icon+'" style="float:left; margin:-2px 3px 0 0;"> <span style="color:'+item.delivery_color+'">'+item.delivery+'</span>' : '';
            var user = (!!item.user && item.user===item.user_login) ? '<span style="color: #0055A5;">'+(item.user_name || "")+'</span>' : '<span style="font-family:\'h\'; font-size:13px;">*'+(item.user || "")+'</span>';
            var save_user = item.save_user > 0 ? '<i class="fa fa-lock" title="Заказ закреплён (ПРИНЯТ) данным сотрудником"></i>' : '';
            var office = item.office===item.office_id ? item.office_name : '';
            var site = getSiteTemplateRow(item);
            var status = item.status ? item.status_name : '';
            var cancel_description = item.status==='13' ? '<i class="fa fa-info-circle" title="'+item.cancel_description+'"></i>' : '';
             
            if(data.sms_history_count){
                var sms_error_color = data.sms_list_error[item.order_id] > 0 ? 'red' : 'green';
                var sms_history_count = data.sms_history_count[item.order_id] > 0 ? '<i class="fa fa-envelope-square" style="color:'+sms_error_color+'; margin-left:4px;"></i><span class="sms-history-count" style="font-size:11px;">'+data.sms_history_count[item.order_id]+'</span>' : '';
            }else{
                var sms_history_count = '';
            }                   

            PHONE = checkPhoneLocalization(item);
              
            var DOUBLE_CLICK;
            var changeDataEditOrders = getSettingsOnEditOrder(item, data);
            if( !!changeDataEditOrders.PHONE ) PHONE = changeDataEditOrders.PHONE;
            phone_title = changeDataEditOrders.phone_title;
            DOUBLE_CLICK = changeDataEditOrders.DOUBLE_CLICK;

            

            var changeDataOnHidePhone = getSettingsOnHidePhone(item, data);
            if( !!changeDataOnHidePhone.PHONE ){
               PHONE = changeDataOnHidePhone.PHONE;
               phone_title = changeDataOnHidePhone.phone_title;
            }


            
            
            //var timestamp_datetime = (parseInt((new Date(item.datetime)).getTime() / 1000).toFixed(0));
            //var DATETIME = date_("Y-m-d",timestamp_datetime)+ ' <span style="font-size:11px; opacity: 0.7;"> &nbsp; '+date_("H:i:s", timestamp_datetime)+'</span>';
            var DATETIME_arr = item.datetime ? item.datetime.split(' ') : '';
            var DATETIME = DATETIME_arr[0]+' <span style="font-size:11px; opacity: 0.7;"> &nbsp; '+DATETIME_arr[1]+'</span>';
            
            //var timestamp_date_update = (parseInt((new Date(item.date_update)).getTime() / 1000).toFixed(0));
            //var DATE_UPDATE = date_("Y-m-d",timestamp_date_update)+ ' <span style="font-size:11px; opacity: 0.7;"> &nbsp; '+date_("H:i:s", timestamp_date_update)+'</span>';
            var DATE_UPDATE_arr = item.date_update ? item.date_update.split(' ') : '';
            var DATE_UPDATE = DATE_UPDATE_arr[0]+' <span style="font-size:11px; opacity: 0.7;"> &nbsp; '+DATE_UPDATE_arr[1]+'</span>';
            
            //var timestamp_date_complete = (parseInt((new Date(item.date_complete)).getTime() / 1000).toFixed(0));
            //var DATE_COMPLETE = date_("Y-m-d",timestamp_date_complete)+ ' <span style="font-size:11px; opacity: 0.7;"> &nbsp; '+date_("H:i:s", timestamp_date_complete)+'</span>';
            var DATE_COMPLETE_arr = item.date_complete ? item.date_complete.split(' ') : '';
            var DATE_COMPLETE = DATE_COMPLETE_arr[0]+' <span style="font-size:11px; opacity: 0.7;"> &nbsp; '+DATE_COMPLETE_arr[1]+'</span>'; 
             
            //var timestamp_delivery_date = (parseInt((new Date(item.delivery_date)).getTime() / 1000).toFixed(0));
            //var DELIVERY_DATE_ = date_("Y-m-d",timestamp_delivery_date)+ ' <span style="font-size:11px; opacity: 0.7;"> &nbsp; '+date_("H:i:s", timestamp_delivery_date)+'</span>';
            var DELIVERY_DATE_arr = item.delivery_date ? item.delivery_date.split(' ') : '';
            var DELIVERY_DATE_ = DELIVERY_DATE_arr[0]+' <span style="font-size:11px; opacity: 0.7;"> &nbsp; '+DELIVERY_DATE_arr[1]+'</span>';
            
            var DELIVERY_DATE = item.delivery_date > '0000-00-00 00:00:00' ? DELIVERY_DATE_ : '';                    
            var date_complete = item.date_complete > '0000-00-00 00:00:00' ? '<i class="fa fa-check-circle" style="color:green;"></i>'+DATE_COMPLETE : '';
            var IP = getIPTemplateRow(item);
            
            
            
            var ttn_status;
            var ttn_status_split = item.ttn_status.split('|');
            item.ttn_status = ttn_status_split[0];
            
            var daysLeftKeeping = ttn_status_split[1] || '';
            var daysLeftKeeping_template = '';
            if( !!daysLeftKeeping ){
               if( daysLeftKeeping < 0 ) daysLeftKeeping = '0';
               daysLeftKeeping_template = `<span class="hightlight-conainer bg-red text-white">${daysLeftKeeping}</span>`;
            }
           
            switch (item.ttn_status){
                
                case 'Готується до відправлення' : ttn_status = '<span style="color:#8A5A00;"><i class="fa fa-clock-o"></i>'+item.ttn_status+'<span>'; break;
                case 'Відправлено' : ttn_status = '<span style="color:green;"><i class="fa fa-truck"></i>'+item.ttn_status+'<span>'; break;
                case 'Змінено адресу' : ttn_status = '<span style="color:blue;"><i class="fa fa-history"></i>'+item.ttn_status+'<span>'; break;
                case 'Прибув у відділення' : ttn_status = '<span style="color:#B75B00;"><i class="fa fa-flag-checkered"></i>'+item.ttn_status+'<span>'; break;
                case 'Одержаний' : ttn_status = '<span style="color:green;"><i class="fa fa-check"></i>'+item.ttn_status+'<span>'; break;
                case 'Відмова' : ttn_status = '<span style="color:red;"><i class="fa fa-thumbs-o-down"></i>'+item.ttn_status+'<span>'; break;
                case 'Видалено' : ttn_status = '<span style="color:red;"><i class="fa fa-remove"></i>'+item.ttn_status+'<span>'; break;
                //*********** Новые ************
                case 'Нова пошта очікує надходження від відправника' : ttn_status = '<span style="color:#8A5A00;"><i class="fa fa-clock-o"></i>'+item.ttn_status+'<span>'; break;
                case 'Номер не знайдено' : ttn_status = '<span style="color:red;"><i class="fa fa-exclamation-circle"></i>'+item.ttn_status+'<span>'; break;
                case 'Прибув на відділення' : ttn_status = '<span style="color:#B75B00;"><i class="fa fa-flag-checkered"></i>'+item.ttn_status+'<span>'; break;
                //case 'Відправлення отримано' : ttn_status = '<span style="color:green;"><i class="fa fa-check"></i>'+item.ttn_status+'<span>'; break;
                //case 'Відправлення отримано. Грошовий переказ видано одержувачу.' : ttn_status = '<span style="color:green;"><i class="fa fa-check"></i>'+item.ttn_status+'<span>'; break;
                case 'Відправлення передано до огляду отримувачу' : ttn_status = '<span style="color:green;"><i class="fa fa-search"></i>'+item.ttn_status+'<span>'; break;
                case 'На шляху до одержувача' : ttn_status = '<span style="color:green;"><i class="fa fa-car"></i>'+item.ttn_status+'<span>'; break;
                case 'Відмова одержувача' : ttn_status = '<span style="color:red;"><i class="fa fa-thumbs-o-down"></i>'+item.ttn_status+'<span>'; break;
                case 'Припинено зберігання' : ttn_status = '<span style="color:red;"><i class="fa fa-times-circle"></i>'+item.ttn_status+'<span>'; break;
                case 'Одержано і є ТТН грошовий переказ' : ttn_status = '<span style="color:green;"><i class="fa fa-check"></i>'+item.ttn_status+'<span>'; break;
                case 'Нараховується плата за зберігання' : ttn_status = '<span style="color:green;"><i class="fa fa-warning"></i>'+item.ttn_status+'<span>'; break;
                //************ OLD ************* 
                case 'Готується до видачі' : ttn_status = '<span style="color:green;"><i class="fa fa-street-view"></i>'+item.ttn_status+'<span>'; break;
                case 'Відправлення отримано' : ttn_status = '<span style="color:green;"><i class="fa fa-check"></i>'+item.ttn_status+'<span>'; break;
                case 'Вручення адресату особисто' : ttn_status = '<span style="color:green;"><i class="fa fa-handshake-o"></i>'+item.ttn_status+'<span>'; break;
                case 'Вручение адресату' : ttn_status = '<span style="color:green;"><i class="fa fa-handshake-o"></i>'+item.ttn_status+'<span>'; break;
                case 'Відмова від отримання' : ttn_status = '<span style="color:red;"><i class="fa fa-thumbs-o-down"></i>'+item.ttn_status+'<span>'; break;
                case 'Не вручене на даний час' : ttn_status = '<span style="color:green;"><i class="fa fa-street-view"></i>'+item.ttn_status+'<span>'; break;
                
                default: ttn_status = item.ttn_status;
            }
            



            var ttn_ref_icon = item.status==='11' && item.ttn_ref ? '*' : '';
            
            if($('.table-data tbody tr#'+item.id+'').length > 0){
                // дубль строки
            }else {
            
            template_orders_table += ('<tr id="'+item.id+'" ondblclick="'+DOUBLE_CLICK+'" data-info="'+item.id+'|'+item.order_id+'|'+item.datetime+'|'+item.date_update+'" style="background-color:'+item.status_color+';" class="sortable">'+
                    '<td class="'+locked_class+'" align="center" style="font-size:12px; text-overflow:clip;"><input type="checkbox" name="item['+item.id+']">'+item.id+'</td>'+
                    '<td class="'+locked_class+'" align="center" style="font-size:11px; text-overflow:clip; color: #999; width:140px;">'+order_new+'</td>'+
                    '<td class="'+locked_class+' buyer_name__row" style="color:#000;">'+bayer_name+'</td>'+
                    '<td class="'+locked_class+'"><img class="'+item.localization+'" src="'+window.location.protocol+'//'+window.location.hostname+'/style/img/flags/'+item.localization+'.ico" style="float:left; margin:-2px 3px 0 0; height:16px;">'+item.localization_title+'</td>'+
                    '<td class="'+locked_class+' phone_num__row" style="min-width: 140px;" id="'+phone_title+'">'+PHONE+''+sms_history_count+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+comment+'</td>'+
                    '<td class="'+locked_class+'" align="right" style="font-family:\'h\'; font-size:14px;">'+total+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+data.products_order[item.order_id]+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+payment+'</td>'+
                    '<td class="'+locked_class+' delivery_name__row" style="font-size:12px;">'+delivery+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+( item.delivery_adress || '' )+'</td>'+
                    '<td class="'+locked_class+' ttn__row">'+(item.ttn || '')+'</td>'+
                    '<td class="'+locked_class+'">'+DELIVERY_DATE+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;" title="'+item.ttn_status+'">'+ttn_ref_icon+''+daysLeftKeeping_template+''+ttn_status+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+DATETIME+'</td>'+
                    '<td class="'+locked_class+'">'+save_user+''+user+'</td>'+
                    '<td class="'+locked_class+'">'+office+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+DATE_UPDATE+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+site+'</td>'+
                    '<td class="'+locked_class+'">'+status+' '+cancel_description+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+date_complete+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+(item.utm_source   || '')+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+(item.utm_medium   || '')+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+(item.utm_term     || '')+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+(item.utm_content  || '')+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+(item.utm_campaign || '')+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+IP+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+(item.additional_1 || '')+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+(item.additional_2 || '')+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+(item.additional_3 || '')+'</td>'+
                    '<td class="'+locked_class+'" style="font-size:12px;">'+(item.additional_4 || '')+'</td>'+
                '</tr>');
            }
        });
        

        return template_orders_table;
    
    }
}





function getNewOrdersRow(data_orders){

    data_orders['edit_order'] = window.SETTINGS_CRM['edit_order'];
    data_orders['hidePhoneOrders'] = window.SETTINGS_CRM['hidePhoneOrders'];
    data_orders['array'][0]['status_color'] = window.SETTINGS_CRM['status_color'][data_orders['array'][0]['status']] || window.SETTINGS_CRM['status_color']['3'];
    data_orders['array'][0]['ttn_status'] = data_orders['array'][0]['ttn_status'] || '';
    //console.log(data_orders);
    //console.log(settings.status_color);
    let tmp = collectTemplateOrdersRow(data_orders);

    return tmp;
    
}

function addNewOrdersRow(data_orders){
  let template_new_order_row = getNewOrdersRow(data_orders);
  $('#form-orders .table-data tbody').prepend(template_new_order_row);
  $('.count-products-info').tooltip({
      content: function () {
          return $(this).prop('title');
      }
  });

}


function checkPhoneLocalization(item, prev_settings={}){
    switch (item.localization){ 
        case 'UA': PHONE = CheckPhone_UA(item.phone); break;
        case 'RU': PHONE = CheckPhone_RU(item.phone); break;
        case 'BY': PHONE = CheckPhone_BY(item.phone); break;
        case 'KZ': PHONE = CheckPhone_KZ(item.phone); break;
        case 'MD': PHONE = CheckPhone_MD(item.phone); break; 
        case 'AE': PHONE = CheckPhone_AE(item.phone); break;
        default: PHONE = '<img src="'+window.location.protocol+'//'+window.location.hostname+'/style/img/icons/no_icon.ico" class="icon-operator">'+item.phone;
    }

    prev_settings["PHONE"] = PHONE;

    return PHONE;

}


function getCommentTemplateRow(item, prev_settings={}){
  prev_settings.comment = item.comment ? '<i class="fa fa-info-circle" title="'+item.comment+'" style="color:#C47600;"></i>'+item.comment+'' : '';
  return prev_settings.comment;
}

function getSiteTemplateRow(item, prev_settings={}){
  prev_settings.site = item.site ? '<i class="fa fa-globe"></i>'+item.site : '';
  return prev_settings.site;
}

function getIPTemplateRow(item, prev_settings={}){
  prev_settings.ip = item.ip ? '<i class="fa fa-desktop"></i>'+item.ip : '';
  return prev_settings.ip;
}



function getSettingsOnEditOrder(item, data, prev_settings={}){

    if( data.edit_order > 0){
            prev_settings.locked_class = '';
            prev_settings.DOUBLE_CLICK = "Orders('edit','"+item.id+"','"+item.order_id+"','"+item.datetime+"', '"+item.date_update+"');";
            prev_settings.phone_title = item.phone;
    }else{
            prev_settings.DOUBLE_CLICK = "AccessLocked('ACCESS-LOCKED');";
            prev_settings.PHONE = '<i class="fa fa-phone"></i>- нет доступа -';
            prev_settings.phone_title = '';
    }

    return prev_settings;

}


function getSettingsOnHidePhone( item, data, prev_settings={} ){
    if(data.hidePhoneOrders > 0){
      //date("s").'K'.base64_encode($phone_num) 
      var date_ = new Date();
      var s = date_.getSeconds();
      var phone_number = s+"K"+Base64.encode(item.phone);                       
      //console.log(phone_number+':'+item.phone); 
      
      prev_settings.PHONE = '<i class="fa fa-phone"></i><span style="font-size:12px;" title="'+phone_number+'">- запрещен просмотр -</span>';
      prev_settings.phone_title = '';
  }

  return prev_settings
}




var timeout_ttn_update = null;
function getOrders(param){ 

    var page = $_GET('page');
    var status = $_GET('status') ? $_GET('status') : 3;

    if(page){
        var stateObj = { foo: "orders" };
        history.pushState(stateObj, "statuses", '?status='+status+'&page='+page);
    }else{
        var stateObj = { foo: "orders" };
        history.pushState(stateObj, "statuses", '?status='+status);
    }

    var template_orders_table = '';


    var performance_timer = new PerformanceTime();
    performance_timer.lock();

    

    $.ajax({
        url: window.location.protocol+"//" + window.location.hostname + "/getOrders",
        method: 'POST',
        cache: false,
        //async: true,
        data : {param:param, status:status, page:page},
        headers: {'X-Csrf-Token': AJAX_TOKEN()},
        beforeSend: function(){
            /*$('section').ShowOverlayLoading(); */
            //DisableLockEditOrderInterval();
        },
        success: function(data){

          performance_timer.addTimeStamp('success_ajax');
          

            //alert(data);
            //console.log(data);
                var OSName = "Unknown";
                if (window.navigator.userAgent.indexOf("Windows NT 10.0")!= -1) OSName="Windows 10";
                if (window.navigator.userAgent.indexOf("Windows NT 6.2") != -1) OSName="Windows 8";
                if (window.navigator.userAgent.indexOf("Windows NT 6.1") != -1) OSName="Windows 7";
                if (window.navigator.userAgent.indexOf("Windows NT 6.0") != -1) OSName="Windows Vista";
                if (window.navigator.userAgent.indexOf("Windows NT 5.1") != -1) OSName="Windows XP";
                if (window.navigator.userAgent.indexOf("Windows NT 5.0") != -1) OSName="Windows 2000";
                if (window.navigator.userAgent.indexOf("Mac")            != -1) OSName="Mac/iOS";
                if (window.navigator.userAgent.indexOf("X11")            != -1) OSName="UNIX";
                if (window.navigator.userAgent.indexOf("Linux")          != -1) OSName="Linux";
            
            //if(OSName==="Windows 2000" || OSName==="Windows XP" || OSName==="Windows Vista" || OSName==="Windows 7" || OSName==="Windows 8" || OSName==="Windows 10"){ 
            if(window.location.hostname==='testcrm.lp-crm.asdasd'){                 
                /*var Base64={_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/\r\n/g,"\n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}
                var data_c = data.substr(8);
                var decodedString = Base64.decode(data_c);
                data = '';
                try{
                  data = JSON.parse(decodedString);
                }catch(e){
                  
                }*/
                console.log(data);
            }
            
            /*modal_window_show('alert',param['table']+ ' (AJAX-запрос)',false,'','error');
            $('#modal-window-data').html(data);
            $('section').HideOverlayLoading(); data.preventDefault(); data.stopPropagation();*/
            var PHONE; 
            var phone_title;
            //var LOCKED_ORDERS = [];
            var locked_class = '';
            

                /*$.each(data.locked_orders, function(i,item){
                    LOCKED_ORDERS[i] = item.order_id;
                });*/

            if( data.length !== 0){ 

                $('.table-data tbody').html('');
                template_orders_table = collectTemplateOrdersRow(data);

                $('.table-data tbody').append(template_orders_table);

            }else{
                var colspan = $('.table-data thead tr:first-child th').length;
                $('.table-data tbody').html('<tr class="no-sortable">'+
                                                '<td colspan="'+colspan+'" align="center">'+
                                                    '<h1 style="text-align:center;">По указанным фильтрам данных не найдено.</h1>'+
                                                '</td>'+
                                            '</tr>');
                Notification('info','Результат поиска','По указанным фильтрам<br>данных не найдено.');
                setTimeout(function(){
                    notification_close('');
                },3000);
            }
            $('#page-navigation-info').html(data.navigation_button);
                $('section').HideOverlayLoading();
                $('#page-navigation-count-rows').html(data.navigation_text);
                $('#count_all_z').text(data.count_array);
                /*var status = $_GET('status') ? $_GET('status') : '3';
                var page = $_GET('page') ? $_GET('page') : '';
                if(page){
                    var stateObj = { foo: "orders" };
                    history.pushState(stateObj, "statuses", '?status='+status+'&page='+page);
                }else{
                    var stateObj = { foo: "orders" };
                    history.pushState(stateObj, "statuses", '?status='+status);
                }*/               
                
                $('#ul-statusy li a').each(function(){
                    var data_src = $(this).attr('data-src');
                    if(data_src === window.location.protocol+'//'+window.location.hostname+'/orders/?status='+status+''){
                        $(this).addClass('tab-status-active');
                    }else{
                        $(this).removeClass('tab-status-active');
                    }                  
                }); 
                
                $('#tab-status-'+status).find('b').html(data.count_by_status);                
                
                $('#ul-statusy').animate({scrollLeft: 1}, 0);
                $('#ul-statusy').animate({
                    scrollLeft: $('.tab-status-active').offset().left - ($('#ul-statusy').width() / 1.55)
                }, 100);
                
                $('.count-products-info').tooltip({
                        content: function () {
                            return $(this).prop('title');
                        }
                    });

                performance_timer.addTimeStamp('render_table_complete');
                performance_timer.showTimeDiff();
        },
        complete: function (){
            //if(status==='11' || status==='14' || status==='20' || status==='29'){
            $('table.table-data').trigger("update");
            timeout_ttn_update = clearTimeout(timeout_ttn_update);
            // если по фильтру ничего не нашли
            if( !$('#form-orders table tbody tr[data-info]').length ) return;

            if(status === 'all') return;

            // что бы получить данные о наличии блокированых заказаов вызываю обновление через 2с....
            timeout_ttn_update = setTimeout(function(){
              // получаем данные выделенных закзов
              var selected_data_row = getSelectedRowData( 'orders', $('#form-orders table tbody tr[data-info]') );

              // блокируем все заказы с ттн
              // ???????

              // фильтруем заказы которые обрабатываються ( те что заблокированы ) 
              // в выборку попадут только данные которые соответствуют всем данным с фильтра
              var filtered_data_row = filterRowData(selected_data_row, {is_edited_now: false});

              // формируем масив
              // {'название_доствки': {'id':{data},...},....}
              var sorted_data_row = collectDeliveryDataUpdate(filtered_data_row);
              // console.log(sorted_data_row);
              
              Update_Status_TTN_Nova_Poshta(status, sorted_data_row);
              Update_Status_TTN_Nova_Poshta_2(status, sorted_data_row);
              Update_Status_TTN_Fethr(status, sorted_data_row);
              Update_Status_TTN_CDEK(status, sorted_data_row);
              Update_Status_TTN_Shoplogistic(status, sorted_data_row);
              // Update_Status_TTN_Kazpost(status);
              Update_Status_TTN_Ukrposhta(status, sorted_data_row);
              
            },2100);

                        
        },
        error: function() {
            //alert('Error: update_cart');
        }
    });
    //return false;
    
}

function PlayRecordsSIP(file_order_id){
    modal_window_show(false,'Записи звонков заказа #'+file_order_id,'<div id="modal-window-play-order"></div>',false);
    $('#modal-window-play-order').load(window.location.protocol+'//'+window.location.hostname+'/include/modal_play_record.php?order_id='+file_order_id); 
}
 
function EXEC(action, template, not_close_modal, callback, callback_before){
    not_close_modal = !not_close_modal;
    $('section').ShowOverlayLoading();
    $('#button-save-modal').attr('disabled',true).html('<img src="'+window.location.protocol+'//'+ window.location.hostname +'/style/img/load.gif" style="margin: 0px 0px 0px;"> Сохранение...');
    var form = $('#form-'+template);

    // закрываем модальное окно при необходимости 
    if(not_close_modal){
      switch(template){        
          case 'modal-orders':  
              $(form).closest('.modal-window').find('.modal-window-close').click();
          break;
          case 'orders':
              modal_window_close_all();
          break;
      }
    }

    var form_data = $(form).serialize()+'&action='+action+'&template='+template;
    var callback_data = {
        form_data: form_data,
        callback:{}
    };
    if(!!callback_before){
      callback_data['callback']['complete'] = ajax_orders;
      callback_before(callback_data);
    }else{
      ajax_orders();
    }


    function ajax_orders(){
          //console.log('IN ajax_orders '+ Date.now());
          $.ajax({
            url: window.location.protocol+"//" + window.location.hostname + "/ajax_orders",
            method: 'POST',
            data : form_data,
            //data : {action:action, template:template},
            //global: false,
            //async: true,
            headers: {'X-Csrf-Token': AJAX_TOKEN()},
            beforeSend: function(){ /*$('section').ShowOverlayLoading(); */ },
            success: function(data){

                
                //console.log(data);
                var data_json = {};
                
                try{
                  var data_json = JSON.parse(data);
                }catch(e){

                }
                if(data==='' || (data_json['success'] && data_json['success'] === true) ){

                    if( !!callback ){

                      callback_data['callback']['success'] = function(data){

                      };
                      callback_data['save_response'] = data_json['data'] || null;
                      callback(callback_data);
                    }

                    //location.reload();    
                    if(not_close_modal){
                       UpdateStatusesOrderCount();
                    }else{
                      $('#button-save-modal').attr('disabled',false).html('Сохранить и закрыть');
                      $('section').HideOverlayLoading();
                    }            
                   
                    //modal_window_close_all();
                }else{
                    $('#button-save-modal').attr('disabled',false).html('Сохранить');
                    $('section').HideOverlayLoading();
                    modal_window_show('alert','Результат AJAX-запроса',data,'','error');
                    //location.reload();
                }
                
            }
        });
    }

}




function Update_Status_TTN_CDEK(status, sorted_data_row){
   
    var delivery = 'СДЭК';
    var TTNs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['ttn'] : [];
    var IDs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['id'] : [];

    // если на странице нет текущей доставки
    if( !TTNs.length ){
      return;
    }


    var check_active_autoupdate = checkAutoupdateStatusesByModName('cdek');
    if( check_active_autoupdate ){
      var interval_lock = LockedOrderBulk_ON( IDs, false );
    }


    TTNs_str = TTNs.join(',');

    var notification_num = ''; 
    $.ajax({
        url: window.location.protocol+"//" + window.location.hostname + "/handler",
        method: 'POST',
        async: true,
        data : {"status": status, "ttn": TTNs_str, "action": "track", 'mass_upd_ttn': true},
        headers: {'X-Csrf-Token': AJAX_TOKEN()},
        beforeSend: function(){
            if(TTNs.length > 0){
                notification_num = Notification('info','Обновление деклараций CDEK','<div class="wrapper-cssload-loader2"><div class="cssload-loader2"></div></div>Подождите...',false);
            }            
        },
        success: function(data){
            if(data.length > 0){ 
                //Notification('info','Обновление деклараций Нова Пошта','Подождите...');
                //$('.notification-data').html(data);
                //console.log(data);
                $( '#notification-box #notification-'+notification_num ).remove();
                notification_num = Notification('info','Обновление деклараций CDEK','Выполнено! Обновите страницу.<br><br>'+data);
            }else{
                $( '#notification-box #notification-'+notification_num ).remove();
            }
            
        },

        complete:function(){
          if( check_active_autoupdate ){
            LockedOrderBulk_OFF( IDs, interval_lock, false );
          }
          
        }
    });    
}




function Update_Status_TTN_Fethr(status, sorted_data_row){
    var delivery = 'Fetchr';
    var TTNs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['ttn'] : [];
    var IDs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['id'] : [];

    // если на странице нет текущей доставки
    if( !TTNs.length ){
      return;
    }


    var check_active_autoupdate = checkAutoupdateStatusesByModName('fetchr');
    if( check_active_autoupdate ){
      var interval_lock = LockedOrderBulk_ON( IDs, false );
    }


    TTNs_str = TTNs.join(',');
    var data__ =  encodeURIComponent(JSON.stringify({"fetchr_data":{"tracking_numbers": TTNs_str},"action":"getBulkOrderStatus","module":"fetchr_delivery"}));
    
    var notification_num = '';
    $.ajax({
        url: window.location.protocol+"//" + window.location.hostname + "/handler_fetchr",
        method: 'POST',
        async: true,
        data : {"status": status, "data" : data__},
        headers: {'X-Csrf-Token': AJAX_TOKEN()},
        beforeSend: function(){
            if(TTNs.length > 0){
                notification_num = Notification('info','Обновление деклараций Fetchr','<div class="wrapper-cssload-loader2"><div class="cssload-loader2"></div></div>Подождите...',false);
            }            
        },
        success: function(data){
            if(data.length > 0){ 
                //Notification('info','Обновление деклараций Нова Пошта','Подождите...');
                //$('.notification-data').html(data);
                //console.log(data);
                $( '#notification-box #notification-'+notification_num ).remove();
                notification_num = Notification('info','Обновление деклараций Fetchr','Выполнено! Обновите страницу.<br><br>'+data);
            }else{
                 $( '#notification-box #notification-'+notification_num ).remove();
            }
            
        },

        complete:function(){
          if( check_active_autoupdate ){
            LockedOrderBulk_OFF( IDs, interval_lock, false );
          }
          
        }
    });    
}


function Update_Status_TTN_Ukrposhta(status, sorted_data_row){
  
    var delivery = 'Укрпочта';
    var TTNs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['ttn'] : [];
    var IDs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['id'] : [];

    // если на странице нет текущей доставки
    if( !TTNs.length ){
      return;
    }


    var check_active_autoupdate = checkAutoupdateStatusesByModName('ukrposhta');
    if( check_active_autoupdate ){
      var interval_lock = LockedOrderBulk_ON( IDs, false );
    }

    var send_data = {
      'action': 'getBulkOrderStatus',
      'ukrposhta_data': {
        'tracking_number':TTNs,
        'status':status,
      },
    };

    var notification_num = '';

    var ajax_settings = {

        beforeSend: function(){
            if(TTNs.length > 0){
                notification_num = Notification('info','Обновление деклараций Укрпочта','<div class="wrapper-cssload-loader2"><div class="cssload-loader2"></div></div>Подождите...',false);
            }            
        },
        success: function(data){
            try{
              var json_data = JSON.parse(data);

              var err_mess = json_data['body']['errors']['message'];
              $( '#notification-box #notification-'+notification_num ).remove();
              //notification_num = Notification('info','Обновление деклараций Укрпочта','<br>'+err_mess);
            }catch(e){
              //console.log(e);
              if(data.length > 0){ 
                  $( '#notification-box #notification-'+notification_num ).remove();
                  notification_num = Notification('info','Обновление деклараций Укрпочта','Выполнено! Обновите страницу.<br><br>'+data);
              }else{
                   $( '#notification-box #notification-'+notification_num ).remove();
              }

            }

            
        },

        complete:function(){
          if( check_active_autoupdate ){
            LockedOrderBulk_OFF( IDs, interval_lock, false );
          }
          
        }
    };

    AJAX_SEND_DATA( 'handler_ukrposhta', send_data, ajax_settings );
}




function Update_Status_TTN_Nova_Poshta(status, sorted_data_row){
    
    var delivery = 'Новая Почта';
    var TTNs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['ttn'] : [];
    var IDs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['id'] : [];
    
    // если на странице нет текущей доставки
    if( !TTNs.length ){
      return;
    }

    var check_active_autoupdate = checkAutoupdateStatusesByModName('nova_poshta');
    if( check_active_autoupdate ){
      var interval_lock = LockedOrderBulk_ON( IDs, false );
    }
    

    var notification_num = '';
    $.ajax({
        url: window.location.protocol+"//" + window.location.hostname + "/Update_Status_TTN_Nova_Poshta",
        method: 'POST',
        async: true,
        data : {status:status, ttn_list:TTNs},
        headers: {'X-Csrf-Token': AJAX_TOKEN()},
        beforeSend: function(){
            if(TTNs.length > 0){
                notification_num = Notification('info','Обновление деклараций Нова Пошта','<div class="wrapper-cssload-loader2"><div class="cssload-loader2"></div></div>Подождите...',false);
            }            
        },
        success: function(data){
            if(data.length > 0){ 
                //Notification('info','Обновление деклараций Нова Пошта','Подождите...');
                //$('.notification-data').html(data);
                $( '#notification-box #notification-'+notification_num ).remove();
                notification_num = Notification('info','Обновление деклараций Нова Пошта','Выполнено! Обновите страницу.<br><br>'+data);
            }else{
                $( '#notification-box #notification-'+notification_num ).remove();
            }
            
        },

        complete:function(){
          if( check_active_autoupdate ){
            LockedOrderBulk_OFF( IDs, interval_lock, false );
          }
        }

    });    
}

// mod_nova_postha_2 module
function Update_Status_TTN_Nova_Poshta_2(status, sorted_data_row){

    var delivery = 'Новая Почта 2.0';
    var TTNs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['ttn'] : [];
    var IDs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['id'] : [];
    // если на странице нет текущей доставки
    if( !TTNs.length ){
        return;
    }

    var check_active_autoupdate = checkAutoupdateStatusesByModName('nova_poshta_2');
    if( check_active_autoupdate ){
      var interval_lock = LockedOrderBulk_ON( IDs, false );
    }


    var notification_num = '';
    $.ajax({
        url: window.location.protocol+"//" + window.location.hostname + "/Update_Status_TTN_Nova_Poshta_2",
        method: 'POST',
        data : {status:status, ttn_list:TTNs},
        headers: {'X-Csrf-Token': AJAX_TOKEN()},
        beforeSend: function(){
            if(TTNs.length > 0){
                notification_num = Notification('info','Обновление деклараций Нова Пошта 2.0','<div class="wrapper-cssload-loader2"><div class="cssload-loader2"></div></div>Подождите...',false);
            }
        },
        success: function(data){
            if(data.length > 0){
                //Notification('info','Обновление деклараций Нова Пошта','Подождите...');
                //$('.notification-data').html(data);
                $( '#notification-box #notification-'+notification_num ).remove();
                notification_num = Notification('info','Обновление деклараций Нова Пошта 2.0','Выполнено! Обновите страницу.<br><br>'+data);
            }else{
                $( '#notification-box #notification-'+notification_num ).remove();
            }

        },

        complete:function(){
          if( check_active_autoupdate ){
            LockedOrderBulk_OFF( IDs, interval_lock, false );
          }
        }
    });
}


function Update_Status_TTN_Shoplogistic(status, sorted_data_row){

    var delivery = 'Shop-Logistics';
    var TTNs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['ttn'] : [];
    var IDs = !!sorted_data_row[delivery] ? sorted_data_row[delivery]['id'] : [];

    // если на странице нет текущей доставки
    if( !TTNs.length ){
        return;
    }


    var check_active_autoupdate = checkAutoupdateStatusesByModName('shoplogistic');
    if( check_active_autoupdate ){
      var interval_lock = LockedOrderBulk_ON( IDs, false );
    }


    var notification_num = '';
    $.ajax({
        url: window.location.protocol+"//" + window.location.hostname + "/Update_Status_TTN_Shoplogistic",
        method: 'POST',
        async: true,
        data : {status:status, ttn_list:TTNs},
        headers: {'X-Csrf-Token': AJAX_TOKEN()},
        beforeSend: function(){
            // console.log(TTNs);
            // console.log(status);
            if(TTNs.length > 0){
                notification_num = Notification('info','Обновление деклараций Shop-Logistics','<div class="wrapper-cssload-loader2"><div class="cssload-loader2"></div></div>Подождите...',false);
            }
        },
        success: function(data){
            if(data.length > 0){
                //Notification('info','Обновление деклараций Нова Пошта','Подождите...');
                //$('.notification-data').html(data);
                $( '#notification-box #notification-'+notification_num ).remove();
                notification_num = Notification('info','Обновление деклараций Shop-Logistics','Выполнено! Обновите страницу.<br><br>'+data);
            }else{
                $( '#notification-box #notification-'+notification_num ).remove();
            }

        },

        complete:function(){
           if( check_active_autoupdate ){
            LockedOrderBulk_OFF( IDs, interval_lock, false );
           }
          
        }
    });
}









function collectDeliveryDataUpdate( filtered_data_row ){
  var result = {};
  var i = 0;
  for ( var key in filtered_data_row ) {

    //if(i > 100) continue;

    var curr_data = filtered_data_row[key],
        delivery_name = $.trim(curr_data.delivery_name),
        ttn = $.trim(curr_data.ttn);
        id = $.trim(curr_data.id);

    if( !delivery_name ) continue;
    
    i++;

    if( !result[delivery_name] ){
      result[delivery_name] = {};
      result[delivery_name]['ttn'] = [];
      result[delivery_name]['id'] = [];
      result[delivery_name]['data'] = {};
    }

    result[delivery_name]['data'][key] = curr_data;
    if( !!ttn ){
      result[delivery_name]['ttn'].push(ttn);
      result[delivery_name]['id'].push(id);
    }
    
    
  }
  
  return result;
}



function test_ajax(num) {
  console.time("Benchmarks №"+num);
  var url = window.location.protocol+"//" + window.location.hostname + "/test_ajax",
      data = {},
      success = function (data){
        console.timeEnd("Benchmarks №"+num);
        console.log(data);
/*        var d = $("<div class='php_info'></div>");
        d.append(data);
        $("body").html(d);*/
      };

/*  $.ajax({
        url: url,
        method: 'POST',
        async: true,
        data : data,
        headers: {'X-Csrf-Token': AJAX_TOKEN()},
        success: success
  });*/

  $.post( url, data, success );

/*  fetch(url, {
        method:"POST",
        headers:{'X-Csrf-Token': AJAX_TOKEN()},
        body: "SOME BODY STRING"
  })
  .then( (resp)=>{

    console.log( resp );
    console.log( resp.text() );
  })
  .catch( (resp)=>{
    console.log( "ERROR" );
    console.log( resp );
    console.log( resp.text() );
  });*/




};

/*test_ajax(1);
test_ajax(2);
test_ajax(3);
test_ajax(4);
test_ajax(5);*/




// получение id статуса по активной вкладке статусов
function getStatusIdByActiveTabStatus(){
    let current_active_status_tab = $('#tabs-panel-statusy #ul-statusy .tab-status-active').attr('id');
        current_active_status_tab = current_active_status_tab.split('-')[2];
    return current_active_status_tab;
}


// подсчет кол-ва заказов в табах статусов
function calculateOrdersInTabStatuses(stat_data){
    for ( let status_id in stat_data ) {

      let curr_data = stat_data[status_id],
          $tab_el = $('#tab-status-'+status_id),
          $text_node = $tab_el.find('b');

      // такого статуса нет у пользователя
      if( $tab_el.length <= 0 ) continue;
      let curr_val = +($text_node.text()),
          calc_val = (+$text_node.text()) + curr_data;
      if( calc_val < 0 ) calc_val = 0;

      $text_node.text(calc_val);

    }
}