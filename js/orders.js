$(function() {
    $(".chosen-select").chosen()
    .change(function(e){
        $('#form-orders').trigger('submit');
    });
    
    // Поиск по таблице
    $('#form-orders').on('submit', function(e){
        let status = getParameterByName('status'),
            page = getParameterByName('page'),
            data = $(this).serializeArray();

        if ($('.status-panel__search').length) {
            $('.status-panel__search div').remove();
        }
        $.each(data, function(e){
            if (this.value != '') {
                let field_name = $('#orders__table').find('th[data-name=' + this.name + ']').text();
                $('.status-panel__search').append('<div data-field="' + this.name + '" class="data-field"><span></span> <i class="fa fa-remove" onclick="RemoveSearchField(\'' + this.name + '\');"></i></div>');
                $('.status-panel__search div').last().find('span').text(field_name + ': ' + this.value);
                $.each($('tr.table-row-search select[name=' + this.name + ']'), function(e){
                    let name_select = $(this).next().find('a.chosen-single span').text();
                    $('.status-panel__search div').last().find('span').text(field_name + ': ' + name_select);
                });
            }
          });
        // Обновляем счетчики статусов
        updateStatusesOrderCount();

        $.ajax({ 
            url: '/system/ajax/row-search.php?status=' + status + '&page=' + (page ? page : 1),
            method: 'POST',
            cache: false,
            data: data,
            beforeSend: function(){
                startPreloader();
            },
            success: function(response){
                let jsonData = JSON.parse(response),
                    template_orders_table;

                $('#form-orders tbody').html('');

                template_orders_table = collectTemplateOrdersRow(jsonData.rows, true);
                $('#form-orders tbody').html(template_orders_table);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);
                
            },
            complete: function (){
                stopPreloader();
            }
        });

        stopPreloader();
        return false; 
    });

    $('button#button-search').on('click', function(e) {
        $('#form-orders').trigger('submit');
    });


    // Модальное окно заказа
    $('#orders__table').on('dblclick', 'tr.table__item', function(e) {
        if (!$(this).hasClass('disabled') && !$(this).hasClass('blocked-row')) {
            let order_id = $(this).attr('data-id'),
                wsData = {
                    action: 'lock item',
                    data: {
                        itemId: order_id,
                        location: 'orders'
                    }
                }
                
            $('.table tbody tr').each(function(){
                 $(this).removeClass('table__active');
            });
            
            sendMessage(ws, JSON.stringify(wsData));
                
            $(this).addClass('table__active static blocked-row'); //  Этот заказ активный
            $('.status-panel div.status-panel__count').html('<i class="fa fa-info-circle"></i> Выделено: ' + $('.table__active').length).show();
            $('.status-panel button#button-selected').removeAttr('disabled');
            
            $.ajax({
                type: "POST",
                url: "system/ajax/viewOrder.php?order_id=" + order_id + "&query=get_title",
                data: {},
                success: function(response) {    
                    let jsonData = JSON.parse(response);
                    if (jsonData.title.error) {
                        showSystemMessage('error', jsonData.title.error);
                    } else {
                        showModalWindow('Заказ № ' + jsonData.title.id + ' [' + jsonData.title.id_order + '] от ' + jsonData.title.date_added, '/ajax_viewOrder?order_id=' + order_id);
                    }
                }
            });
        } else if ($(this).hasClass('blocked-row')) {
            showModalWindow('Заказ недоступен!', null, 'error', 'В данный момент заказ редактируется другим пользователем!');
        }
    });

    $('.status-panel').on('click', '#button-edit', function(e) {
        if (!$(this).hasClass('disabled') && !$(this).hasClass('blocked-row')) {
            let order_id = $('tbody').find('.table__active').attr('data-id'),
                wsData = {
                    action: 'lock item',
                    data: {
                        itemId: order_id,
                        location: 'orders'
                    }
                }

            sendMessage(ws, JSON.stringify(wsData));

            $.ajax({
                type: "POST",
                url: "system/ajax/viewOrder.php?order_id=" + order_id + "&query=get_title",
                data: {},
                success: function(response) {    
                    let jsonData = JSON.parse(response);
                    if (jsonData.success == 1) {
                        showModalWindow('Заказ № ' + jsonData.title.id + ' [' + jsonData.title.id_order + '] от ' + jsonData.title.date_added, '/ajax_viewOrder?order_id=' + order_id);
                    } else {
                        console.log(jsonData.error);
                    }
                }    
            });
        } else if ($(this).hasClass('blocked-row')) {
            showModalWindow('Заказ недоступен!', null, 'error', 'В данный момент заказ редактируется другим пользователем!');
        }
    });

    // Меняем таб статуса на активный и наоборот
    $(document).on('click', 'ul.status-list__item li a', function (e) {
        if (!$(this).hasClass('noactive')) {
            $('ul.status-list__item li a').removeClass('tab-status-active');
            $(this).addClass('tab-status-active');
        } else {
            return false;
        }
    });

    // buttons arrow right
    $(document).on('click', '#button-arrow-right-tabs', function (e) {
        var value = $('.status-list').scrollLeft() + 250;
        $('.status-list').animate({ scrollLeft: value }, "slow");
    });
    // buttons arrow left
    $(document).on('click', '#button-arrow-left-tabs', function (e) {
        var value = $('.status-list').scrollLeft() - 250;
        $('.status-list').animate({ scrollLeft: value }, "slow");
    });

    // Статусы
    if (!getParameterByName('status')) {
        TabStatus('all');
    } else {
        TabStatus(getParameterByName('status'));
    }

    $('#departure_date_start, #departure_date_end').datepicker({
        dateFormat: "dd-mm-yy",
        beforeShow: function(input) {
            $(input).prop('readonly', true);
        },
        onSelect: function() {
            $('#form-orders').trigger('submit');
        }
    });

    let autoUpdate;
    $('#button-autoupdate').on('click', function(){
        let interval = $('#input-autoupdate').val(),
            min_interval = 60;
        if ($(this).is(':checked') ){
            if (interval > (min_interval - 1)) {
                let step = interval;
                $('#input-autoupdate').prop('readonly', true);
                autoUpdate = setInterval(function(){
                    let status = getParameterByName('status');
                    if (step < 1) {
                        step = interval;
                        TabStatus(status);
                    } else {
                        step--;
                    }
                    $('#input-autoupdate').val(step);
                }, 1000);
            } else {
                $(this).prop('checked', false);
                $('#input-autoupdate').val(min_interval).prop('readonly', false);
                showModalWindow('Интервал автообновления текущих параметров', null, 'error', 'Минимальный интервал обновления: ' + min_interval + ' секунд.');
            } 
        } else {
            clearInterval(autoUpdate);
            $('#input-autoupdate').val(min_interval).prop('readonly', false);
        }
    });
});

function RemoveSearchField(field) {
    $.each($('tr input, tr select'), function(e) {
        if ($(this).attr('name') == field) {
            $('.status-panel__search div[data-field=' + field + ']').remove();
            $(this).val('');
            $('#form-orders').trigger('submit');
        }
    });
    $('.chosen-select').trigger('chosen:updated');
}
// Формируем тело таблицы
function collectTemplateOrdersRow(data, search = false){
    let template_orders_table = '',
        countTd = $('#orders__table thead').find('tr td').length;

    if (Array.isArray(data) && data.length) {
            $.each(data, function(i, item){
                let itemProducts = '';
                $.each(item.products, function(i, product){
                    itemProducts += (product.name + ' (' + product.count + ' шт. x ' + product.price + ' = ' + product.amount + ')~ ');
                });

                template_orders_table += ('<tr data-id="' + item.id_item + '" data-order-id="' + item.id_order + '" class="table__item' + (item.blocked == 1 ? ' blocked-row' : '') + '" style="background-color: ' + item.status_color + '">' +
                                            '<td><input type="checkbox" name="item[' + item.id_item + ']">' + item.id_item + '</td>' +
                                            '<td style="color: #757575" align="center"><small>' + (item.updated == '' ? '<div class="blink">новый</div>' : item.id_order) + '</small></td>' +
                                            '<td>' + item.customer + '</td>' +
                                            '<td align="center"><img src="/img/countries/' + item.country_code + '.png" alt="*"> ' + item.country_name + '</td>' +
                                            '<td align="center">' + item.phone + '</td>' +
                                            '<td>' + item.comment + '</td>' +
                                            '<td align="center"><b>' + item.amount + '</b></td>' +
                                            '<td><span class="count-products-info" title="' + itemProducts + '">' + item.count_products + '</span> ' + itemProducts + '</td>' +
                                            '<td class="center table__item-icon"><img src="/system/images/payment/' + item.payment_method_icon + '" alt="ico"> ' + item.payment_method + '</td>' +
                                            '<td class="center table__item-icon"><img src="/system/images/delivery/' + item.delivery_method_icon + '" alt="ico"> ' + item.delivery_method + '</td>' +
                                            '<td>' + item.delivery_adress + '</td>' +
                                            '<td>' + item.ttn + '</td>' +
                                            '<td>' + item.ttn_status + '</td>' +
                                            '<td>' + item.departure_date + '</td>' +
                                            '<td class="center">' + item.date_added + '</td>' +
                                            '<td class="center">' + (item.updated === true ? '' : item.updated) + '</td>' +
                                            '<td>' + item.employee + '</td>' +
                                            '<td>' + item.site + '</td>' +
                                            '<td>' + item.ip + '</td>' +
                                            '<td class="center">' + item.order_status + '</td>' +
                                            '<td class="center">' + item.complete + '</td>' +
                                        '</tr>');
            });
    } else {
        
        if (data == 'empty' && getParameterByName('status') != 'all') {
            window.location.href = '/orders.php?status=all';
        }
        
        template_orders_table = ('<tr class="no-result">' +
                                    '<td colspan="' + countTd + '">' + ((search && $('.status-panel__search div').length)? 'Ничего не найдено!' : 'Здесь ничего нет.') + '</td>' +
                                '</tr>');   
    }
    return template_orders_table;
}

// TabStatus()
function TabStatus(tabStatus, pageLocation = false) {
    let status = tabStatus,
        page;
    if (arguments.length == 0) {
        status = "all";
    } else {
        status = status;
        if (status == 0) status = "all";
    }

    if (pageLocation) {
        page = pageLocation;
    } else if (getParameterByName('page') != null) {
        page = getParameterByName('page');
    }
    
    if (page && getParameterByName('status') == status){
        stateObj = { foo: "orders" };
        history.pushState(stateObj, "statuses", '?status=' + status + '&page=' + page);
    } else {
        stateObj = { foo: "orders" };
        history.pushState(stateObj, "statuses", '?status=' + status);   
        page = null; // Ошибка пре переключении статуса 
        
    }
    
    // Обновляем счетчики статусов
    updateStatusesOrderCount();

    if ($('.status-panel__search div').length) {
        $('#form-orders').trigger('submit');
    } else {
        // Подгружаем контент со статусами
        $.ajax({ 
            url: '/system/ajax/orders.php?status=' + status + '&page=' + (page ? page : 1),
            method: 'POST',
            cache: false,
            data : { status: status, page : page },
            beforeSend: function(){
                startPreloader();
            },
            success: function(response){
                let jsonData = JSON.parse(response),
                    template_orders_table,
                    pagination,
                    wsData = {
                        action: 'set property',
                        data: {
                            propertyName: 'activeStatuses',
                            propertyValue: {
                                'orders': (status == 'all' ? 0 : status)
                            }
                        }
                    }

                sendMessage(ws, JSON.stringify(wsData)); // Обновляем активный таб

                $('#form-orders tbody').html('');
                
                // Items
                template_orders_table = collectTemplateOrdersRow(jsonData.rows);
                $('#form-orders tbody').html(template_orders_table);
                
                
                
                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

                // Обновляем кнопку "Добавить"
                if (status === 'all') status = '1';
                $('.status-panel button#button-add').attr('onclick', 'Order("add", ' + status + ');');
                // Скролл в начало
                $('.content__overflow').scrollTop(0);
            },
            complete: function (){
                stopPreloader();
            }
        });
    }

}
// Обновление счетчика заказов во всех статусах
function updateStatusesOrderCount() {
    $.ajax({
        url: window.location.protocol + "//" + window.location.hostname + "/ajax_updateStatusesOrderCount",
        method: 'POST',
        data: { status : true }, // no empty
        success: function(response) {
            var data = JSON.parse(response);
            $('.status-list ul li').each(function(){
                var attr_id = $(this).attr('id');
                var arr = attr_id.split('-');
                var id = arr[2];                  
                $('#tab-status-' + id).find('b').text(data.count_orders[id]);
                if (getParameterByName('status') == id) {
                    $('.status-list ul li a').removeClass('tab-status-active');
                    $('#tab-status-' + id + ' a').addClass('tab-status-active');
                }
                if (data.count_orders[id] < 1 && id != 0){
                    $('#tab-status-' + id).removeAttr('onclick');
                    $('#tab-status-' + id).css('opacity', '0.5');
                    $('#tab-status-' + id + ' a').addClass('noactive');
                } else {
                    $('#tab-status-0').attr('onclick', 'TabStatus(\'all\');');
                    $('#tab-status-' + id).attr('onclick', 'TabStatus(\'' + id + '\');');
                    $('#tab-status-' + id).css('opacity', '1.0');
                    $('#tab-status-' + id + ' a').removeClass('noactive');
                }
            });
        }
    });
}

function deleteOrders() {
    let countOrders = $('tr.table__active').length;
    if (countOrders < 1) {
        console.log('Ничего не выбрано..');
    } else {
        let name = 'заказа';
        if (countOrders > 1) name = 'заказов';
        showModalWindow('Удаление ' + name, '/ajax_deleteOrders?count=' + countOrders, 'confirm');
    }
}

function Order(type, status) {
    if (type == 'add' && !isNaN(status)) {
        showModalWindow('Добавление заказа', '/system/ajax/addOrder.php');
    }
}

function changeStatuses() {
    let countOrders = $('tr.table__active').length;
    if (countOrders < 1) {
        console.log('Ничего не выбрано..');
    } else {
        showModalWindow('Изменение статуса выбранных заказов', '/ajax_changeOrdersStatuses');
    }
}