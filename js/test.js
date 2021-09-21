$(document).ready(function() {
    $('textarea, input[type="text"]').attr('spellcheck', 'false');
    $('table.table-data').multiSelect({
        actcls: 'selected-row',
        selector: 'tr',
        except: ['tbody, section, #button-panel-table button, #button-panel-table ul, #button-panel-table ul li, header, aside, #overlay-blur, .modal-window, .toggle, .toggle__input, #ui-datepicker-div *, #ui-datepicker-div button, .table-product-in-order'],
        statics: ['thead tr, label, .danger', '.no-sortable', '[data-no="1"]'],
        callback: function(items) {
            multiselect_callback(items)
        }
    });
    $('table.table-data').tablesorter({
        widthFixed: true,
        headerTemplate: '{content} {icon}',
        widgets: ['stickyHeaders'],
        widgetOptions: {
            stickyHeaders_attachTo: '#div-scroll-table'
        }
    });
    $('body').click(function(event) {
        var this_ = $(this),
            target = $(event.target);
        var drop = $('.drop-menu'),
            current_drop = target.closest('.drop-menu');
        if (!!current_drop.length) {
            drop.not(current_drop).removeClass('active');
            current_drop.toggleClass('active')
        } else {
            drop.removeClass('active')
        }
        if (target.closest("#button-operation").length === 0) {
            $('#button-panel-table ul').hide()
        } else {
            if ($('#button-operation').is(':disabled')) {
                $('#button-panel-table ul').hide()
            } else {
                $('#button-panel-table ul').show()
            }
        }
        if (target.closest("#ShowUserPopupInfo").length !== 0) {
            $('#user-info-header-popup-box').show()
        } else {
            $('#user-info-header-popup-box').hide()
        }
    });
    $('#div-scroll-table').scroll(function() {});
    $('.data-row-search').keypress(function(e) {
        if (e.keyCode === 13) {
            AJAX_SEARCH()
        }
    });
    $('.data-row-search select').change(function(e) {
        AJAX_SEARCH()
    });
    $("#button-search").on('click', function() {
        AJAX_SEARCH()
    })
});

function multiselect_callback(items) {
    $('.table-data tbody tr').each(function() {
        if ($(this).is('.selected-row')) {
            $(this).find('td').eq(0).find('input[type="checkbox"]').eq(0).prop('checked', true)
        } else {
            $(this).find('td').eq(0).find('input[type="checkbox"]').eq(0).prop('checked', false)
        }
    });
    COUNT_SELECTED_ROW_in_TABLE()
}

function Show_Tariffs() {
    var content = '<div id="modal-load"><br><p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p></div>';
    modal_window_show(false, 'Тарифы', content, false, false);
    $('#modal-load').load('/template/tariffs.php')
}

function Contacts() {
    var content = '<div id="modal-load"><br><p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p></div>';
    modal_window_show(false, 'Контакты', content, false, false);
    $('#modal-load').load('/template/contacts.php')
}

function Navigation(page) {
    var status = $_GET('status') ? $_GET('status') : '';
    if (status) {
        var stateObj = {
            foo: "orders"
        };
        history.pushState(stateObj, "pages", '?status=' + status + '&page=' + page)
    } else {
        var stateObj = {
            foo: "orders"
        };
        history.pushState(stateObj, "pages", '?page=' + page)
    }
    AJAX_SEARCH()
}

function Send_SMS() {
    var elements = $('.selected-row').length;
    if (elements <= 0) {
        modal_window_show('alert', 'Подготовка СМС сообщений', 'Нет выделенных заказов для определения номера телефона. Отметьте нужный Вам заказ в таблице.', false, 'error');
        stopPropagation()
    }
    $('#form-send-sms-list').remove();
    $('section').append('<form id="form-send-sms-list"></form>');
    var i = 0;
    $('.selected-row td > input[type="checkbox"]:checked').each(function() {
        var fio = $(this).closest('tr').find('td').eq(2).text();
        var user_fio = fio.split(/ +/);
        var surname = user_fio[0] == null ? '' : user_fio[0];
        var name = user_fio[1] == null ? '' : user_fio[1];
        var lastname = user_fio[2] == null ? '' : user_fio[2];
        var local = $(this).closest('tr').find('td').eq(3).find('img').attr('class');
        var phone = $(this).closest('tr').find('td').eq(4).attr('id');
        if (phone === '' || phone === 'undefined') {
            var Base64 = {
                _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
                encode: function(e) {
                    var t = "";
                    var n, r, i, s, o, u, a;
                    var f = 0;
                    e = Base64._utf8_encode(e);
                    while (f < e.length) {
                        n = e.charCodeAt(f++);
                        r = e.charCodeAt(f++);
                        i = e.charCodeAt(f++);
                        s = n >> 2;
                        o = (n & 3) << 4 | r >> 4;
                        u = (r & 15) << 2 | i >> 6;
                        a = i & 63;
                        if (isNaN(r)) {
                            u = a = 64
                        } else if (isNaN(i)) {
                            a = 64
                        }
                        t = t + this._keyStr.charAt(s) + this._keyStr.charAt(o) + this._keyStr.charAt(u) + this._keyStr.charAt(a)
                    }
                    return t
                },
                decode: function(e) {
                    var t = "";
                    var n, r, i;
                    var s, o, u, a;
                    var f = 0;
                    e = e.replace(/[^A-Za-z0-9\+\/\=]/g, "");
                    while (f < e.length) {
                        s = this._keyStr.indexOf(e.charAt(f++));
                        o = this._keyStr.indexOf(e.charAt(f++));
                        u = this._keyStr.indexOf(e.charAt(f++));
                        a = this._keyStr.indexOf(e.charAt(f++));
                        n = s << 2 | o >> 4;
                        r = (o & 15) << 4 | u >> 2;
                        i = (u & 3) << 6 | a;
                        t = t + String.fromCharCode(n);
                        if (u != 64) {
                            t = t + String.fromCharCode(r)
                        }
                        if (a != 64) {
                            t = t + String.fromCharCode(i)
                        }
                    }
                    t = Base64._utf8_decode(t);
                    return t
                },
                _utf8_encode: function(e) {
                    e = e.replace(/\r\n/g, "\n");
                    var t = "";
                    for (var n = 0; n < e.length; n++) {
                        var r = e.charCodeAt(n);
                        if (r < 128) {
                            t += String.fromCharCode(r)
                        } else if (r > 127 && r < 2048) {
                            t += String.fromCharCode(r >> 6 | 192);
                            t += String.fromCharCode(r & 63 | 128)
                        } else {
                            t += String.fromCharCode(r >> 12 | 224);
                            t += String.fromCharCode(r >> 6 & 63 | 128);
                            t += String.fromCharCode(r & 63 | 128)
                        }
                    }
                    return t
                },
                _utf8_decode: function(e) {
                    var t = "";
                    var n = 0;
                    var r = c1 = c2 = 0;
                    while (n < e.length) {
                        r = e.charCodeAt(n);
                        if (r < 128) {
                            t += String.fromCharCode(r);
                            n++
                        } else if (r > 191 && r < 224) {
                            c2 = e.charCodeAt(n + 1);
                            t += String.fromCharCode((r & 31) << 6 | c2 & 63);
                            n += 2
                        } else {
                            c2 = e.charCodeAt(n + 1);
                            c3 = e.charCodeAt(n + 2);
                            t += String.fromCharCode((r & 15) << 12 | (c2 & 63) << 6 | c3 & 63);
                            n += 3
                        }
                    }
                    return t
                }
            };
            phone = $(this).closest('tr').find('td').eq(4).find('span').attr('title');
            if ($.trim(phone) !== '') {
                var encodedStringPhone = phone.substr(3)
            }
            try {
                var decodedStringPhone = Base64.decode(encodedStringPhone);
                phone = decodedStringPhone
            } catch (e) {
                phone = ''
            }
        }
        var $row_container = $(this).closest('tr');
        var ttn = $row_container.find('td').eq(11).text();
        var total = $row_container.find('td').eq(6).text();
        var order_id = $row_container.find('td').eq(1).text();
        var ID = $row_container.attr('id');
        var data_row = ordersRowData($row_container);
        try {
            order_id = data_row[ID]['order_id']
        } catch (e) {
            console.log(e)
        }
        $('#form-send-sms-list').append('<input type="hidden" name="phone_local[' + i + ']" value="' + local.trim() + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="phone_nomer[' + i + ']" value="' + phone.trim() + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="fio[' + i + ']" value="' + fio + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="surname[' + i + ']" value="' + surname + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="name[' + i + ']" value="' + name + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="lastname[' + i + ']" value="' + lastname + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="ttn[' + i + ']" value="' + ttn.trim() + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="total[' + i + ']" value="' + total.trim() + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="order_id[' + i + ']" value="' + order_id.trim() + '">');
        i++
    });
    modal_window_show(false, 'Отправка СМС сообщений', '<div id="modal-window-sms"></div>', false, false);
    $('#modal-window-sms').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>');
    $.ajax({
        type: "POST",
        url: window.location.protocol + "//" + window.location.hostname + "/mods/turbosms/modal_send_sms.php",
        data: $('#form-send-sms-list').serialize(),
        beforeSend: function() {},
        success: function(res) {
            $('#modal-window-sms').html(res)
        },
        error: function() {
            modal_window_show('alert', 'Ошибка Ajax-запроса!', 'Ошибка при выполнении запроса ajax!<p>action: &nbsp; modal_send_sms.php<br>operation: &nbsp; ajax</p>', false, 'error')
        }
    })
}

function Send_SMS_clients() {
    var elements = $('.selected-row').length;
    if (elements <= 0) {
        modal_window_show('alert', 'Подготовка СМС сообщений', 'Нет выделенных заказов для определения номера телефона. Отметьте нужный Вам заказ в таблице.', false, 'error');
        stopPropagation()
    }
    $('#form-send-sms-list').remove();
    $('section').append('<form id="form-send-sms-list"></form>');
    var i = 0;
    $('.selected-row td > input[type="checkbox"]:checked').each(function() {
        var fio = $(this).closest('tr').find('td').eq(1).text();
        var user_fio = fio.split(/ +/);
        var surname = user_fio[0] == null ? '' : user_fio[0];
        var name = user_fio[1] == null ? '' : user_fio[1];
        var lastname = user_fio[2] == null ? '' : user_fio[2];
        var local = $(this).closest('tr').find('td').eq(2).find('img').attr('class');
        var phone = $(this).closest('tr').find('td').eq(3).attr('id');
        $('#form-send-sms-list').append('<input type="hidden" name="phone_local[' + i + ']" value="' + local.trim() + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="phone_nomer[' + i + ']" value="' + phone.trim() + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="fio[' + i + ']" value="' + fio + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="surname[' + i + ']" value="' + surname + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="name[' + i + ']" value="' + name + '">');
        $('#form-send-sms-list').append('<input type="hidden" name="lastname[' + i + ']" value="' + lastname + '">');
        i++
    });
    modal_window_show(false, 'Отправка СМС сообщений', '<div id="modal-window-sms"></div>', false, false);
    $('#modal-window-sms').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>');
    $.ajax({
        type: "POST",
        url: window.location.protocol + "//" + window.location.hostname + "/mods/turbosms/modal_send_sms.php",
        data: $('#form-send-sms-list').serialize(),
        beforeSend: function() {},
        success: function(res) {
            $('#modal-window-sms').html(res)
        },
        error: function() {
            modal_window_show('alert', 'Ошибка Ajax-запроса!', 'Ошибка при выполнении запроса ajax!<p>action: &nbsp; modal_send_sms.php<br>operation: &nbsp; ajax</p>', false, 'error')
        }
    })
}

function Create_Task(id) {
    modal_window_show(false, 'Создать напоминание', '<div id="modal-window-task"></div>', false, false);
    $('#modal-window-task').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>');
    $('#modal-window-task').load(window.location.protocol + '//' + window.location.hostname + '/include/modal_task.php?id=' + id)
}

function get_Tasks() {
    modal_window_show(false, 'Напоминания', '<div id="modal-window-tasks"></div>', false, false);
    $('#modal-window-tasks').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>');
    $('#modal-window-tasks').load(window.location.protocol + '//' + window.location.hostname + '/include/modal_tasks_list.php')
}

function Chat_Support() {
    modal_window_show(false, 'Чат техподдержки', '<div id="modal-window-chat-support"></div>', false, false);
    $('#modal-window-chat-support').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>');
    $('#modal-window-chat-support').load(window.location.protocol + '//' + window.location.hostname + '/include/modal_chat_support.php')
}

function ChangeShowMaxRowsDataTable(event, name_var) {
    t = event.target || event.srcElement;
    var current_value = $(t).val();
    SET_VAR_COOKIE_SESSION(name_var, current_value);
    AJAX_SEARCH()
}

function AJAX_SEARCH(add_param = null) {
    $('section').ShowOverlayLoading();
    var template = $("form tr.data-row-search input[name='table']").val();
    var param = {
        'table': template
    };
    $('#filter-search').html('');
    $('.data-row-search td input[type="hidden"]').each(function() {
        var name = $(this).attr('name');
        var value = $(this).val();
        if (value) {
            param["" + name + ""] = value
        }
    });
    $('.data-row-search td input[type="text"]').each(function() {
        var name = $(this).attr('name');
        var value = $(this).val();
        value = value.replace(/</g, '&lt');
        value = value.replace(/>/g, '&gt');
        value = value.replace(/'/g, '&#39');
        value = value.replace(/;/g, '&#59');
        value = value.replace(/~/g, '&#126');
        value = value.replace(/`/g, '&#96');
        value = value.replace(/'/g, '&#34');
        value = value.replace(/"/g, '&quot');
        var position = $(this).closest('td').index();
        var title = $(this).closest('table').find('thead tr:first').find('th').eq(position).text().trim();
        if (value) {
            switch (name) {
                case 'datetime_dateStart':
                    title = title + ' c';
                    break;
                case 'datetime_dateStop':
                    title = title + ' по';
                    break;
                case 'date_update_dateStart':
                    title = title + ' c';
                    break;
                case 'date_update_dateStop':
                    title = title + ' по';
                    break;
                case 'date_complete_dateStart':
                    title = title + ' c';
                    break;
                case 'date_complete_dateStop':
                    title = title + ' по';
                    break;
                default:
                    title
            }
            param["" + name + ""] = value;
            $('#filter-search').append('<div title="' + value + '"><span><font>' + title + ':</font> ' + value + ' </span><i onclick="RemoveSearchField(\'' + name + '\');" class="fa fa-remove"></i></div>')
        }
    });
    $('.data-row-search td select').each(function() {
        var name = $(this).attr('name');
        var txt = $(this).find('option:selected').text();
        var value = $(this).val();
        var position = $(this).closest('td').index();
        var title = $(this).closest('table').find('thead tr:first').find('th').eq(position).text().trim();
        if (value) {
            param["" + name + ""] = value;
            $('#filter-search').append('<div title="' + txt + '"><span><font>' + title + ':</font> ' + txt + ' </span><i onclick="RemoveSearchField(\'' + name + '\');" class="fa fa-remove"></i></div>')
        }
    });
    if (!!add_param) {
        param = Object.assign(param, add_param)
    }
    switch (template) {
        case 'user_groups':
            getGroupsUsers(param);
            break;
        case 'users':
            getUsers(param);
            break;
        case 'offices':
            getOffices(param);
            break;
        case 'clients_groups':
            getGroupsClient(param);
            break;
        case 'clients':
            getClients(param);
            break;
        case 'statusy':
            getStatusesOrders(param);
            break;
        case 'orders':
            getOrders(param);
            break;
        case 'products':
            getProducts(param);
            break;
        case 'sklad_history':
            getSkladHistory(param);
            break;
        case 'plugins':
            getMods(param);
            break;
        case 'categories':
            getCategories(param);
            break;
        case 'payments':
            getPayments(param);
            break;
        case 'deliverys':
            getDeliverys(param);
            break;
        case 'provider':
            getProviders(param);
            break;
        case 'sklad_in':
            getSkladIn(param);
            break;
        case 'attributes_categories':
            getCategoriesAttributes(param);
            break;
        case 'attributes':
            getAttributes(param);
            break;
        case 'colors':
            getColors(param);
            break;
        case 'cart':
            getOrdersCart(param);
            break;
        case 'landings':
            getLandings(param);
            break;
        case 'template_sms':
            getTemplateSMS(param);
            break;
        case 'manufacturers':
            getManufacturers(param);
            break;
        case 'currency':
            getCurrency(param);
            break;
        case 'blacklist':
            getBlacklist(param);
            break;
        case 'countries':
            getCountries(param);
            break
    }
    $('#button-panel-table .panel-message').hide();
    Count_new_Orders()
}

function Count_new_Orders() {
    $.ajax({
        url: window.location.protocol + "//" + window.location.hostname + "/count_new_orders",
        method: 'POST',
        data: {},
        headers: {
            'X-Csrf-Token': AJAX_TOKEN()
        },
        beforeSend: function() {},
        success: function(data) {
            $('#info-count_new-orders').text(data)
        }
    })
}

function RemoveSearchField(name) {
    $('*[name="' + name + '"]').val('');
    $('*[name="' + name + '"]').trigger('chosen:updated');
    AJAX_SEARCH()
}

function Delete_Rows_Confirm(action, template) {
    var count = $('.selected-row').length;
    modal_window_show('alert', 'Удаление записей', '<div id="modal-window-confirm-delete"></div>', 'EXEC(\'' + action + '\',\'' + template + '\');', 'confirm');
    $('#modal-window-confirm-delete').html('ВНИМАНИЕ!<br>Вы дейcтвительно хотите удалить выделенные?<br><br>Отмечено: ' + count + ' шт.')
}(function($) {
    $.fn.HideOverlayLoading = function() {
        this.find('#div-overlay-loading').remove();
        this.disablescroll('undo')
    }
})(jQuery);
(function($) {
    $.fn.ShowOverlayLoading = function() {
        this.disablescroll();
        this.append('<div id="div-overlay-loading" style="display: table; text-align: center;">' + '<span style="vertical-align:middle; display:table-cell;">' + '<div class="cssload-loader">' + '<div class="cssload-inner cssload-one"></div>' + '<div class="cssload-inner cssload-two"></div>' + '<div class="cssload-inner cssload-three"></div>' + '</div>' + '</span>' + '</div>')
    }
})(jQuery);

function ShowUserPopupInfo() {
    $('#user-info-header-popup-box').show()
}

function COUNT_SELECTED_ROW_in_TABLE() {
    var count = $('.table-data tbody tr td:first-child input[type="checkbox"]:checked').length;
    if (count > 0) {
        $('#button-operation').attr('disabled', false);
        $('.panel-message').show().html('<i class="fa fa-info-circle"></i> Выделено: <span>' + count + '</span>');
        if (count > 1) {
            $('#button-edit').attr('disabled', true).addClass('disabled');
            $('#button-copy').attr('disabled', true).addClass('disabled');
            $('#button-change-statuses').attr('disabled', false).removeClass('disabled')
        } else {
            $('#button-edit').attr('disabled', false).removeClass('disabled');
            $('#button-copy').attr('disabled', false).removeClass('disabled');
            $('#button-change-statuses').attr('disabled', true).addClass('disabled')
        }
    } else {
        $('#button-operation').attr('disabled', true);
        $('.panel-message').hide()
    }
}

function uniqid() {
    var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
    var string_length = 10;
    var randomstring = '';
    for (var x = 0; x < string_length; x++) {
        var letterOrNumber = Math.floor(Math.random() * 2);
        if (letterOrNumber === 0) {
            var newNum = Math.floor(Math.random() * 9);
            randomstring += newNum
        } else {
            var rnum = Math.floor(Math.random() * chars.length);
            randomstring += chars.substring(rnum, rnum + 1)
        }
    }
    return randomstring
}

function GET_VAR_COOKIE_SESSION(var_name) {
    var val = $.ajax({
        type: 'POST',
        url: window.location.protocol + "//" + window.location.hostname + "/modules/get_session_variable_value.php",
        global: false,
        async: false,
        data: {
            var_name: var_name
        },
        success: function(data) {
            return data
        }
    }).responseText;
    return val
}

function SET_VAR_COOKIE_SESSION(var_name, value) {
    $.ajax({
        type: 'POST',
        url: window.location.protocol + "//" + window.location.hostname + "/set_session",
        headers: {
            'X-Csrf-Token': AJAX_TOKEN()
        },
        global: false,
        async: false,
        data: {
            var_name: var_name,
            value: value
        },
        success: function(data) {}
    })
}

function AJAX_TOKEN() {}

function PlaySound(ElementId) {
    $('audio').stop();
    var melody = document.getElementById(ElementId);
    melody.play()
}

function modal_window_close_all() {
    var ID = $('.modal-window').attr('id');
    var cod = ID.split('-');
    var unique = cod[1];
    modal_window_close(unique)
}

function modal_window_close(number_modal) {
    $('body').css({
        position: 'relative',
        overflowY: 'auto'
    });
    $('#filter-blur-' + number_modal + '').remove();
    $('#modal-' + number_modal + '').remove();
    var count_modals = $('.modal-window').length;
    if (count_modals >= 1) {} else {
        $('#overlay-blur').remove();
        $('.span-filter-blur').remove()
    }
    $('.modal-window').eq(count_modals - 1).find('*').attr('disabled', false);
    $('.modal-window').eq(count_modals - 1).find('*').removeClass('disable-modal-window');
    $('.modal-window').eq(count_modals - 1).find('.modal-window-close').attr('disabled', false);
    $('.modal-window').eq(count_modals - 1).find('select').prop('disabled', false).trigger("chosen:updated")
}
$(document).on('keydown', function(e) {
    if (e.keyCode === 27) {
        modal_window_close__not_disable()
    }
});

function modal_window_show(type_, title, content, func_ok, _icon_) {
    AJAX_TOKEN();
    var type = type_ ? type_ : 'modal';
    var count_modals = uniqid();
    var count_overlay = $('#overlay-blur').length;
    if (count_overlay >= 1) {} else {
        $('body').append('<div id="overlay-blur">' + '</div>')
    }
    switch (type) {
        case 'modal':
            $('#overlay-blur').append('<div class="modal-window box-size-bb" id="modal-' + count_modals + '">' + '<div class="modal-window-title"><h3>' + title + '<h3> <button class="modal-window-close" onclick="modal_window_close(\'' + count_modals + '\');">×</button></div>' + '<div class="modal-window-content" style="border: 1px solid #EEE;">' + '<div class="modal-window-data">' + content + '</div>' + '</div>' + '</div>');
            break;
        case 'alert':
            var icon_ = _icon_.toUpperCase();
            var icon = icon_ ? '<img src="' + window.location.protocol + '//' + window.location.hostname + '/style/img/' + icon_ + '.png" style="margin: 0px 0px 0px;">' : '';
            PlaySound('SOUND-' + icon_);
            var buttons;
            if (icon_ === 'CONFIRM') {
                buttons = '<button onclick="' + func_ok + '">OK</button> <a href="javascript:void(0);" onclick="modal_window_close(\'' + count_modals + '\');">' + lang.modal_window_text_button_cancel + '</a>'
            }
            if (icon_ === 'INFO') {
                buttons = func_ok ? '<button onclick="' + func_ok + '">OK</button>' : '<button onclick="modal_window_close(\'' + count_modals + '\');">OK</button>'
            }
            if (icon_ === 'ERROR' || icon_ === 'WARNING' || icon_ === 'ACCESS-LOCKED') {
                buttons = '<button onclick="modal_window_close(\'' + count_modals + '\');">OK</button>'
            }
            var head_title, border;
            switch (icon_) {
                case 'ERROR':
                    head_title = lang.modal_window_type_error;
                    border = '1px dashed #FEA798';
                    break;
                case 'INFO':
                    head_title = lang.modal_window_type_info;
                    border = '1px solid #E2EAF5';
                    break;
                case 'WARNING':
                    head_title = lang.modal_window_type_warning;
                    border = '1px dashed #FAC767';
                    break;
                case 'CONFIRM':
                    head_title = lang.modal_window_type_confirm;
                    border = '1px dashed #C6D5EC';
                    break;
                case 'ACCESS-LOCKED':
                    head_title = lang.modal_window_type_access_locked;
                    border = '1px dashed #FEA798';
                    break
            }
            $('#overlay-blur').append('<div class="modal-window box-size-bb" id="modal-' + count_modals + '">' + '<div class="modal-window-title"><h3>' + head_title + ': &nbsp;' + title + '<h3> <button class="modal-window-close" onclick="modal_window_close(\'' + count_modals + '\');">×</button></div>' + '<div class="modal-window-content" style="border: ' + border + ';">' + '<table border="0">' + '<tr>' + '<td width="64px" align="right" valign="middle">' + icon + '</td>' + '<td style="word-spacing:1px;">' + '<div class="modal-window-data">' + content + '</div>' + '</td>' + '</tr>' + '</table>' + '<div class="modal-window-footer">' + buttons + '</div>' + '</div>' + '</div>');
            break
    }
    if ($('.modal-window').length > 2) {
        var n = $('.modal-window').length;
        var X = parseInt(n) * parseInt(10);
        var top = $("#modal-" + count_modals + "").css('top');
        var left = $("#modal-" + count_modals + "").css('left');
        top = top.replace(/\D/g, '');
        left = left.replace(/\D/g, '');
        $("#modal-" + count_modals + "").css({
            marginTop: '20px',
            marginLeft: '20px'
        })
    }
    var count_active_modal = $('.modal-window').length;
    for (var i = 0; i <= count_active_modal - 2; i++) {
        $('.modal-window').eq(i).find('*').attr("disabled", true).addClass('disable-modal-window');
        $('.modal-window').eq(i).find('.modal-window-close').prop('disabled', true);
        $('.modal-window').eq(i).find('select').prop('disabled', true).trigger("chosen:updated")
    }
    $('.modal-window').draggable({
        containment: "body",
        cancel: ".modal-window-content, .modal-window-close, .modal-window-footer",
        scroll: false
    });
    if ($('.span-filter-blur').length < 1) {
        $('body').append('<span class="span-filter-blur" id="filter-blur-' + count_modals + '">' + '<style>' + 'body > *:not(.ui-autocomplete)' + ':not(#notification-box)' + ':not(#overlay-blur)' + ':not(.sp-container .sp-light .sp-palette-buttons-disabled .full-spectrum)' + ':not(.modal-window)' + ':not(.chosen)' + ':not(.cke_reset_all)' + ':not(.cke_1)' + ':not(.cke_editor_text_dialog)' + ':not(#ui-datepicker-div)' + ':not(#modal-' + count_modals + ')' + ':not(.ui-tooltip.ui-widget-content)' + '{' + '-webkit-filter: blur(2px);' + '-moz-filter: blur(2px);' + '-o-filter: blur(2px);' + 'filter: blur(2px);' + '}' + '</style>' + +'</span>')
    }
}

function notification_close(number_notification, not_remove = false, class_container_delete = "") {
    var delete_el = null;
    if (number_notification && not_remove) {
        var not_remove_el_str = '';
        number_notification.forEach(function(item, i, array) {
            not_remove_el_str += '#notification-' + number_notification[i] + ','
        });
        if (not_remove_el_str[not_remove_el_str.length - 1] === ',') not_remove_el_str = not_remove_el_str.slice(0, -1);
        delete_el = $('#notification-box').find('.notifi-container').not(not_remove_el_str)
    } else if (!!class_container_delete) {
        delete_el = $('#notification-box').find(class_container_delete)
    } else {
        delete_el = $('#notification-' + number_notification + '')
    }
    if (number_notification || !!class_container_delete) {
        delete_el.fadeOut();
        if (!class_container_delete) {
            setTimeout(function() {
                delete_el.remove()
            }, 1000)
        }
        var count_notification = $('#notification-box table').length;
        if (count_notification >= 1) {} else {
            $('#notification-box').remove()
        }
    } else {
        $('#notification-box').find('.notifi-container:not(.not_del_with_all)').remove();
        if ($('#notification-box > .notifi-container').length === 0) {
            $('#notification-box').remove()
        }
    }
}

function Notification(type, title, message, sound_off, add_class_box = "", add_class_container = "", create_notifi_id = '') {
    var count_notification = uniqid();
    if (!!create_notifi_id) count_notification = create_notifi_id;
    var count_notification_box = $('#notification-box').length;
    if (count_notification_box >= 1) {} else {
        $('body').append('<div id="notification-box" class="' + add_class_box + '"></div>')
    }
    switch (type) {
        case 'info':
            $('#notification-box').prepend('<table class="notifi-container num-notify-' + count_notification + ' ' + add_class_container + '" id="notification-' + count_notification + '">' + '<tr><td colspan="2" align="center" class="notification-title">' + title + '<div class="notification-close" onclick="notification_close(\'' + count_notification + '\');">×</div></td></tr>' + '<tr>' + '<td><i class="fa fa-info-circle fa-3x" style="color:#FFC;"></i></td>' + '<td class="notification-data" valign="bottom">' + message + '</td>' + '</tr>' + '</table>');
            if (sound_off !== false) {
                PlaySound('SOUND-MESSAGE')
            }
            break;
        case 'task':
            $('#notification-box').prepend('<table class="notifi-container" id="notification-' + count_notification + '">' + '<tr><td colspan="2" align="center" class="notification-title" style="color:#FBBF0D;">' + title + '<div class="notification-close" onclick="notification_close(\'' + count_notification + '\');">×</div></td></tr>' + '<tr>' + '<td><i class="fa fa-clock-o fa-3x" style="color:#FBBF0D;"></i></td>' + '<td class="notification-data" valign="bottom">' + message + '</td>' + '</tr>' + '</table>');
            if (sound_off !== false) {
                PlaySound('SOUND-ALARM')
            }
            break
    }
    return count_notification
}

function Edit_Rows(template) {
    var tr = $('.selected-row').length;
    var $row = $('.selected-row').eq(0);
    var is_edited_now = $row.is('[data-lock-order]');
    if (tr === 1) {
        var data_info = $('.selected-row').attr('data-info');
        var attr = data_info.split('|');
        switch (template) {
            case 'orders':
                var id = attr[0];
                var order_id = attr[1];
                var datetime = attr[2];
                var date_update = attr[3];
                if (!is_edited_now) {
                    Orders('edit', id, order_id, datetime, date_update)
                } else {
                    LockedOrderEdit(id)
                }
                break;
            case 'autsors':
                var id = attr[0];
                var order_id = attr[1];
                var datetime = attr[2];
                OrdersAutsors('edit', id, order_id, datetime);
                break;
            case 'plugins':
                var id = attr[0];
                var name_mods = attr[1];
                var title_mods = attr[2];
                var module_is_install = ($('.selected-row').attr('data-module-install') === 'false') ? false : true;
                Mods('edit', id, name_mods, title_mods, module_is_install);
                break;
            case 'categories':
                var id = attr[0];
                var name = attr[1];
                Category('edit', id, name);
                break;
            case 'statusy':
                var id = attr[0];
                var name = attr[1];
                StatusesOrders('edit', id, name);
                break;
            case 'deliverys':
                var id = attr[0];
                var name = attr[1];
                Deliverys('edit', id, name);
                break;
            case 'products':
                var id = attr[0];
                var name = attr[1];
                Products('edit', id, name);
                break;
            case 'provider':
                var id = attr[0];
                var name = attr[1];
                Provider('edit', id, name);
                break;
            case 'sklad_in':
                var id = attr[0];
                var name = attr[1];
                SkladIn('edit', id, name);
                break;
            case 'attributes_categories':
                var id = attr[0];
                var name = attr[1];
                CategoriesAttributes('edit', id, name);
                break;
            case 'attributes':
                var id = attr[0];
                var name = attr[1];
                Attributes('edit', id, name);
                break;
            case 'colors':
                var id = attr[0];
                var name = attr[1];
                Colors('edit', id, name);
                break;
            case 'user_groups':
                var id = attr[0];
                var name = attr[1];
                User_Groups('edit', id, name);
                break;
            case 'users':
                var id = attr[0];
                var name = attr[1];
                var login = attr[2];
                Users('edit', id, name, login);
                break;
            case 'landings':
                var id = attr[0];
                var name = attr[1];
                Colors('edit', id, name);
                break;
            case 'template_sms':
                var id = attr[0];
                var title = attr[1];
                TemplateSMS('edit', id, title);
                break;
            case 'offices':
                var id = attr[0];
                var name = attr[1];
                Offices('edit', id, name);
                break;
            case 'clients_groups':
                var id = attr[0];
                var name = attr[1];
                Clients_Groups('edit', id, name);
                break;
            case 'clients':
                var id = attr[0];
                var name = attr[1];
                Clients('edit', id, name);
                break;
            case 'manufacturers':
                var id = attr[0];
                var name = attr[1];
                Manufacturers('edit', id, name);
                break;
            case 'currency':
                var id = attr[0];
                var name = attr[1];
                Currency('edit', id, name);
                break;
            case 'blacklist':
                var id = attr[0];
                var name = attr[1];
                Blacklist('edit', id, name);
                break;
            case 'countries':
                var id = attr[0];
                var name = attr[1];
                Countries('edit', id, name);
                break
        }
    }
}

function Copy_Rows() {
    var tr = $('.selected-row').length;
    if (tr === 1) {
        var data_info = $('.selected-row').attr('data-info');
        var attr = data_info.split('|');
        var id = attr[0];
        var order_id = attr[1];
        Orders('copy', id, order_id, '')
    }
}

function Export_Exel(filename) {
    var table_ = $(".table-data").clone();
    $('#export-temp').html(table_);
    $(table_).find('td').find('img').remove();
    $(table_).find('td').find('img').remove();
    $(table_).find('td').find('input').remove();
    $(table_).find('tbody > tr').each(function() {
        if ($(this).attr('class') !== 'sortable selected-row') {
            $(this).remove()
        }
        $(this).find('.sms-history-count').remove();
        $(this).find('td *').each(function() {
            var value = $(this).text();
            $(this).text(value)
        })
    });
    var table = $('#export-temp table');
    $(table).table2excel({
        exclude: ".data-row-search",
        name: "export",
        filename: filename,
        fileext: ".xls",
        exclude_img: false,
        exclude_links: true,
        exclude_inputs: false
    });
    $('#export-temp').html('')
}

function Export_Exel_Orders_Pro(filename) {
    var table_ = $(".table-data").clone();
    $('#export-temp').html(table_);
    $(table_).find('td').find('img').remove();
    $(table_).find('td').find('*').find('img').remove();
    $(table_).find('td').find('input').remove();
    $(table_).find('tbody > tr').each(function() {
        if ($(this).attr('class') !== 'sortable selected-row') {
            $(this).remove()
        }
        $(this).find('.sms-history-count').remove();
        $(this).find('td *').each(function() {
            var value = $(this).text();
            $(this).text(value)
        })
    });
    var id_list = [];
    $('#export-temp table tbody tr').each(function() {
        var order_id = $(this).find('td').eq(1).text();
        id_list.push(order_id)
    });
    $.ajax({
        type: 'POST',
        url: window.location.protocol + "//" + window.location.hostname + "/export_exel_orders",
        data: {
            ids: id_list
        },
        headers: {
            'X-Csrf-Token': AJAX_TOKEN()
        },
        success: function(data) {
            if (data.response === 'ok') {
                var TR = '<thead> <tr>' + '<th align="center" style="height:30px;"> id </th>' + '<th align="center"> order_id </th>' + '<th align="center"> Покупатель </th>' + '<th align="center"> Локализация </th>' + '<th align="center"> Телефон </th>' + '<th align="center"> Комментарий </th>' + '<th align="center"> Сумма </th>' + '<th colspan="6" align="center"> Товар </th>' + '<th align="center"> Оплата </th>' + '<th align="center"> Тип доставки </th>' + '<th align="center"> Адрес доставки </th>' + '<th align="center"> ТТН </th>' + '<th align="center"> ТТН Статус </th>' + '<th align="center"> Добавлено </th>' + '<th align="center"> Сотрудник </th>' + '<th align="center"> Отдел </th>' + '<th align="center"> Обновлено </th>' + '<th align="center"> Сайт </th>' + '<th align="center"> Статус </th>' + '<th align="center"> utm_source </th>' + '<th align="center"> utm_medium </th>' + '<th align="center"> utm_term </th>' + '<th align="center"> utm_content </th>' + '<th align="center"> utm_campaign </th>' + '</tr> </thead>';
                $.each(data.array, function(i, item) {
                    var rowspan_p1 = $(item.products).length;
                    var rowspan_p2 = $(item.products_resale).length;
                    var rowspan_ = (rowspan_p1 + rowspan_p2) + 1;
                    var rowspan = rowspan_ > 1 ? 'rowspan="' + rowspan_ + '"' : '';
                    var title_products = rowspan_p1 > 0 ? '<td align="center" style="font-size:11px; color:#565656;"><b>id</b></td>' + '<td align="center" style="font-size:11px; color:#565656;"><b>Название</b></td>' + '<td align="center" style="font-size:11px; color:#565656;"><b>Суб. товар</b></td>' + '<td align="center" style="font-size:11px; color:#565656;"><b>Количество</b></td>' + '<td align="center" style="font-size:11px; color:#565656;"><b>Цена</b></td>' + '<td align="center" style="font-size:11px; color:#565656;"><b>Сумма</b></td>' : '<td colspan="6" align="center" style="color:#990000;"> - Нет товара в заказе - </td>';
                    TR += '<tr>' + '<td ' + rowspan + ' valign="top" align="center">' + item.id + '</td>' + '<td ' + rowspan + ' valign="top" align="center">' + item.order_id + '</td>' + '<td ' + rowspan + ' valign="top">' + item.bayer_name + '</td>' + '<td ' + rowspan + ' valign="top">' + item.localization + '</td>' + '<td ' + rowspan + ' valign="top">' + item.phone + '</td>' + '<td ' + rowspan + ' valign="top">' + item.comment + '</td>' + '<td ' + rowspan + ' valign="top">' + item.total + '</td>' + '' + title_products + '' + '<td ' + rowspan + ' valign="top">' + item.payment + '</td>' + '<td ' + rowspan + ' valign="top">' + item.delivery + '</td>' + '<td ' + rowspan + ' valign="top">' + item.delivery_adress + '</td>' + '<td ' + rowspan + ' valign="top">' + item.ttn + '</td>' + '<td ' + rowspan + ' valign="top">' + item.ttn_status + '</td>' + '<td ' + rowspan + ' valign="top">' + item.datetime + '</td>' + '<td ' + rowspan + ' valign="top">' + item.user + '</td>' + '<td ' + rowspan + ' valign="top">' + item.office + '</td>' + '<td ' + rowspan + ' valign="top">' + item.date_update + '</td>' + '<td ' + rowspan + ' valign="top">' + item.site + '</td>' + '<td ' + rowspan + ' valign="top">' + item.status + '</td>' + '<td ' + rowspan + ' valign="top">' + item.utm_source + '</td>' + '<td ' + rowspan + ' valign="top">' + item.utm_medium + '</td>' + '<td ' + rowspan + ' valign="top">' + item.utm_term + '</td>' + '<td ' + rowspan + ' valign="top">' + item.utm_content + '</td>' + '<td ' + rowspan + ' valign="top">' + item.utm_campaign + '</td>' + '</tr>';
                    $.each(item.products, function(p, p1) {
                        var itogo = parseInt(p1.quantity) * parseFloat(p1.price);
                        TR += '<tr>' + '<td>' + p1.product_id + '</td>' + '<td>' + p1.product_name + '</td>' + '<td>' + p1.sub_name + '</td>' + '<td align="right">' + p1.quantity + '</td>' + '<td align="right">' + p1.price + '</td>' + '<td align="right">' + itogo.toFixed(2) + '</td>' + '</tr>'
                    });
                    $.each(item.products_resale, function(d, p2) {
                        var itogo_resale = parseInt(p2.quantity) * parseFloat(p2.price);
                        TR += '<tr>' + '<td>' + p2.product_id + '</td>' + '<td>' + p2.product_name + '</td>' + '<td>' + p2.sub_name + '</td>' + '<td align="right">' + p2.quantity + '</td>' + '<td align="right">' + p2.price + '</td>' + '<td align="right">' + itogo_resale.toFixed(2) + '</td>' + '</tr>'
                    })
                });
                $('#export-temp table').html(TR)
            }
        },
        complete: function() {
            var table = $('#export-temp table');
            $(table).table2excel({
                exclude: ".data-row-search",
                name: "export",
                filename: filename,
                fileext: ".xls",
                exclude_img: false,
                exclude_links: true,
                exclude_inputs: false
            });
            $('#export-temp').html('')
        },
        error: function() {
            alert('ERROR: export_exel_orders')
        }
    })
}

function printBlock(id_element, add_options = '') {
    $(id_element).css('margin', '0px');
    $(id_element + ' tr').removeClass('selected-row');
    var options = {
        globalStyles: true,
        mediaPrint: false,
        stylesheet: null,
        noPrintSelector: ".data-row-search",
        iframe: true,
        append: null,
        prepend: null,
        manuallyCopyFormValues: true,
        deferred: $.Deferred().done(function() {}),
        timeout: 500,
        title: null,
        doctype: '<!doctype html>'
    };
    if (!!add_options) {
        options = Object.assign(options, add_options)
    }
    $(id_element).print(options)
}

function Delete_Rows_DataTable_Confirm(action, template, form) {
    var count = $('.selected-row').length;
    modal_window_show('alert', lang.modal_window_confirm_title, '<div id="modal-window-confirm-delete"></div>', 'ACTION_DATA_AJAX(\'' + action + '\',\'' + template + '\',\'' + form + '\');', 'confirm');
    $('#modal-window-confirm-delete').html(lang.modal_window_confirm_text + '<br><br>' + lang.modal_window_confirm_selected + ': ' + count + ' ' + lang.modal_window_confirm_pieces)
}

function clone_item(clone_to_el, clone_el, change_clone_callback) {
    var clone_to = $(clone_to_el),
        for_clone_el = $(clone_el);
    var cloned_el = for_clone_el.clone(true);
    if (!!change_clone_callback) {
        cloned_el = change_clone_callback(cloned_el, clone_el)
    }
    cloned_el.appendTo(clone_to)
}

function remove_clone_item(el, callback) {
    var remove_el = $(el),
        current_clone_item = remove_el.closest('.clone-item'),
        container = remove_el.closest('.clone-container__'),
        clone_item = container.find('.clone-item'),
        count_clone_item = clone_item.length;
    callback(current_clone_item, count_clone_item)
}

function get_preload_template(class_ = "") {
    var preload_tmp = `<div class="wrap_preload flex ${class_}"><div class="preload_crm "><div class="cssload-inner cssload-one"></div><div class="cssload-inner cssload-two"></div><div class="cssload-inner cssload-three"></div></div></div>`;
    return preload_tmp
}

function preloader(status, el, class_) {
    if (status == 'on') {
        class_ = class_ || '';
        var preload_tmp = get_preload_template(class_);
        el.append(preload_tmp)
    } else if (status == 'off') {
        el.find('.wrap_preload').fadeOut().remove()
    }
};

function fixedEncodeURIComponent(str) {
    return encodeURIComponent(str).replace(/[']/g, function(c) {
        return '%' + c.charCodeAt(0).toString(16)
    })
}

function AJAX_SEND_DATA(handler, data, callbacks, data_is_encode) {
    var post_data = {};
    if (!data_is_encode) {
        post_data = {
            data: fixedEncodeURIComponent(JSON.stringify(data))
        }
    } else {
        post_data = data
    }
    var ajax_data = {
        url: "//" + window.location.hostname + "/" + handler,
        method: 'POST',
        data: post_data,
        headers: {
            'X-Csrf-Token': AJAX_TOKEN()
        },
    };
    if (!!callbacks) {
        ajax_data = Object.assign(ajax_data, callbacks)
    }
    $.ajax(ajax_data)
};

function download_file(settings = {}) {
    var link = document.createElement("a");
    if (!!settings.url) {
        link.href = settings.url
    } else {
        var content = settings['content'],
            contentType = settings['contentType'];
        link.href = window.URL.createObjectURL(new Blob([content], {
            type: contentType
        }))
    }
    link.download = settings['fileName'];
    link.click()
}
var PRODUCTS_EVENT_LIST = {};

function ACTION_DATA_AJAX(action, template, form) {
    if (form === 'form-modal-products') {
        for (var key in PRODUCTS_EVENT_LIST) {
            if (!!PRODUCTS_EVENT_LIST[key]) {
                var func = PRODUCTS_EVENT_LIST[key];
                if (!func) {
                    return
                }
                var valid = func();
                if (!valid) return
            }
        }
    }
    var main_form = $('#' + form);
    var formData = new FormData(main_form[0]);
    formData.append('action', action);
    $.ajax({
        url: window.location.protocol + "//" + window.location.hostname + "/ajax_" + template,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        headers: {
            'X-Csrf-Token': AJAX_TOKEN()
        },
        beforeSend: function() {},
        success: function(data) {
            if (data === 'ok') {
                modal_window_close_all();
                $('#button-operation').attr('disabled', true);
                $('.panel-message').hide();
                AJAX_SEARCH()
            } else {
                modal_window_show('alert', 'Результат AJAX-запроса', data, '', 'error')
            }
        },
        complete: function() {
            $('table.table-data').trigger("update")
        },
        error: function() {}
    })
}

function update_sort_numbers(eq) {
    var n = 1;
    $('.table-data tbody tr').each(function() {
        $(this).find('td').eq(eq).text(n);
        n++
    })
}

function UpdateSort(table, eq) {
    var ids = $('.table-data tbody').sortable("toArray");
    $.ajax({
        type: "POST",
        url: window.location.protocol + "//" + window.location.hostname + "/ajax_sortable",
        data: {
            ids: ids,
            table: table
        },
        beforeSend: function() {},
        success: function(data) {
            if (data === 'ok') {
                update_sort_numbers(eq)
            } else {}
        },
        error: function() {}
    })
}

function ChangeStatus(template, status, id) {
    var table = template;
    $.ajax({
        type: "POST",
        url: window.location.protocol + "//" + window.location.hostname + "/ajax_change_status",
        data: {
            id: id,
            status: status,
            table: table
        },
        beforeSend: function() {},
        success: function(data) {
            if (data === 'ok') {
                setTimeout(function() {
                    AJAX_SEARCH()
                }, 300)
            } else {
                alert(data)
            }
        },
        error: function() {}
    })
}

function Confirm_Exit(icon_mp3) {
    modal_window_show('alert', lang.modal_title_confirm_exit, '<div id="modal-window-exit"></div>', 'LogOut();', icon_mp3);
    $('#modal-window-exit').html(lang.modal_text_confirm_exit)
}

function LogOut() {
    $('#overlay-blur, .span-filter-blur').remove();
    $('body').ShowOverlayLoading();
    var link = window.location.protocol + '//' + window.location.hostname + '/logout';
    PlaySound('SOUND-LOGOFF');
    setTimeout(function() {
        window.location.href = link
    }, 2000)
}

function LockedOrderEdit(id) {
    var icon_mp3 = 'ACCESS-LOCKED';
    modal_window_show('alert', 'Доступ ограничен!', '<div id="modal-window-locked"></div>', 'pressOK();', icon_mp3);
    var user_lock = $('.table-data tbody tr#' + id + '.blocked-row').attr('data-lock-user');
    $('#modal-window-locked').html('Заказ сейчас открыт другим пользователем!<br><br>Cотрудник: <b>' + user_lock + '</b>')
}

function InformationPlatform() {
    modal_window_show(false, lang.modal_title_information_platform, '<div id="modal-window-info-platform"></div>', false, false);
    $('#modal-window-info-platform').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>').load('/template/platform.php?language=' + lang.iso)
}

function AccessLocked(icon_mp3) {
    modal_window_show('alert', lang.modal_access_locked_title, lang.modal_access_locked_message, false, icon_mp3)
}

function ChangeSettingsUser(login, name) {
    modal_window_show(false, lang.modal_title_settings_user + ': ' + name, '<div id="modal-window-settings-user"></div>', false, false);
    $('#modal-window-settings-user').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>').load('/include/modal_settings_user.php?user=' + login)
}

function InformationAbout() {
    modal_window_show(false, lang.modal_title_information_program, '<div id="modal-window-info-program"></div>', false, false);
    $('#modal-window-info-program').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>').load('/template/information.php')
}

function pressOK() {
    alert('Вы нажали OK!')
}

function Information1(icon_mp3) {
    modal_window_show('alert', 'Сообщение', '<div id="modal-window-info-1"></div>', 'pressOK();', icon_mp3);
    $('#modal-window-info-1').html('Использование robots.txt<br>Текст для примера.<br>Использование файла - нет.')
}

function EditUser() {
    modal_window_show(false, 'Карточка пользователя', '<div id="modal-window-data-load"></div>', false, false);
    $('#modal-window-data-load').html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>').load('/include/modal_users.php')
}

function $_GET(param) {
    var vars = {};
    window.location.href.replace(location.hash, '').replace(/[?&]+([^=&]+)=?([^&]*)?/gi, function(m, key, value) {
        vars[key] = value !== undefined ? value : ''
    });
    if (param) {
        return vars[param] ? vars[param] : null
    }
    return vars
}

function in_array(needle, arr) {
    return (arr.indexOf(needle) !== -1)
}

function print_r(array, return_val) {
    var output = '',
        pad_char = ' ',
        pad_val = 4,
        d = this.window.document,
        getFuncName = function(fn) {
            var name = (/\W*function\s+([\w\$]+)\s*\(/).exec(fn);
            if (!name) {
                return '(Anonymous)'
            }
            return name[1]
        };
    repeat_char = function(len, pad_char) {
        var str = '';
        for (var i = 0; i < len; i++) {
            str += pad_char
        }
        return str
    };
    formatArray = function(obj, cur_depth, pad_val, pad_char) {
        if (cur_depth > 0) {
            cur_depth++
        }
        var base_pad = repeat_char(pad_val * cur_depth, pad_char);
        var thick_pad = repeat_char(pad_val * (cur_depth + 1), pad_char);
        var str = '';
        if (typeof obj === 'object' && obj !== null && obj.constructor && getFuncName(obj.constructor) !== 'PHPJS_Resource') {
            str += 'Array\n' + base_pad + '(\n';
            for (var key in obj) {
                if (Object.prototype.toString.call(obj[key]) === '[object Array]') {
                    str += thick_pad + '[' + key + '] => ' + formatArray(obj[key], cur_depth + 1, pad_val, pad_char)
                } else {
                    str += thick_pad + '[' + key + '] => ' + obj[key] + '\n'
                }
            }
            str += base_pad + ')\n'
        } else if (obj === null || obj === undefined) {
            str = ''
        } else {
            str = obj.toString()
        }
        return str
    };
    output = formatArray(array, 0, pad_val, pad_char);
    if (return_val !== true) {
        if (d.body) {
            this.echo(output)
        } else {
            try {
                d = XULDocument;
                this.echo('<pre xmlns="http://www.w3.org/1999/xhtml" style="white-space:pre;">' + output + '</pre>')
            } catch (e) {
                this.echo(output)
            }
        }
        return true
    }
    return output
}

function date(format, timestamp) {
    var that = this;
    var jsdate, f;
    var txt_words = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    var formatChr = /\\?(.?)/gi;
    var formatChrCb = function(t, s) {
        return f[t] ? f[t]() : s
    };
    var _pad = function(n, c) {
        n = String(n);
        while (n.length < c) {
            n = '0' + n
        }
        return n
    };
    f = {
        d: function() {
            return _pad(f.j(), 2)
        },
        D: function() {
            return f.l().slice(0, 3)
        },
        j: function() {
            return jsdate.getDate()
        },
        l: function() {
            return txt_words[f.w()] + 'day'
        },
        N: function() {
            return f.w() || 7
        },
        S: function() {
            var j = f.j();
            var i = j % 10;
            if (i <= 3 && parseInt((j % 100) / 10, 10) == 1) {
                i = 0
            }
            return ['st', 'nd', 'rd'][i - 1] || 'th'
        },
        w: function() {
            return jsdate.getDay()
        },
        z: function() {
            var a = new Date(f.Y(), f.n() - 1, f.j());
            var b = new Date(f.Y(), 0, 1);
            return Math.round((a - b) / 864e5)
        },
        W: function() {
            var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3);
            var b = new Date(a.getFullYear(), 0, 4);
            return _pad(1 + Math.round((a - b) / 864e5 / 7), 2)
        },
        F: function() {
            return txt_words[6 + f.n()]
        },
        m: function() {
            return _pad(f.n(), 2)
        },
        M: function() {
            return f.F().slice(0, 3)
        },
        n: function() {
            return jsdate.getMonth() + 1
        },
        t: function() {
            return (new Date(f.Y(), f.n(), 0)).getDate()
        },
        L: function() {
            var j = f.Y();
            return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0
        },
        o: function() {
            var n = f.n();
            var W = f.W();
            var Y = f.Y();
            return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0)
        },
        Y: function() {
            return jsdate.getFullYear()
        },
        y: function() {
            return f.Y().toString().slice(-2)
        },
        a: function() {
            return jsdate.getHours() > 11 ? 'pm' : 'am'
        },
        A: function() {
            return f.a().toUpperCase()
        },
        B: function() {
            var H = jsdate.getUTCHours() * 36e2;
            var i = jsdate.getUTCMinutes() * 60;
            var s = jsdate.getUTCSeconds();
            return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3)
        },
        g: function() {
            return f.G() % 12 || 12
        },
        G: function() {
            return jsdate.getHours()
        },
        h: function() {
            return _pad(f.g(), 2)
        },
        H: function() {
            return _pad(f.G(), 2)
        },
        i: function() {
            return _pad(jsdate.getMinutes(), 2)
        },
        s: function() {
            return _pad(jsdate.getSeconds(), 2)
        },
        u: function() {
            return _pad(jsdate.getMilliseconds() * 1000, 6)
        },
        e: function() {
            throw 'Not supported (see source code of date() for timezone on how to add support)'
        },
        I: function() {
            var a = new Date(f.Y(), 0);
            var c = Date.UTC(f.Y(), 0);
            var b = new Date(f.Y(), 6);
            var d = Date.UTC(f.Y(), 6);
            return ((a - c) !== (b - d)) ? 1 : 0
        },
        O: function() {
            var tzo = jsdate.getTimezoneOffset();
            var a = Math.abs(tzo);
            return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4)
        },
        P: function() {
            var O = f.O();
            return (O.substr(0, 3) + ':' + O.substr(3, 2))
        },
        T: function() {
            return 'UTC'
        },
        Z: function() {
            return -jsdate.getTimezoneOffset() * 60
        },
        c: function() {
            return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb)
        },
        r: function() {
            return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb)
        },
        U: function() {
            return jsdate / 1000 | 0
        }
    };
    this.date = function(format, timestamp) {
        that = this;
        jsdate = (timestamp === undefined ? new Date() : (timestamp instanceof Date) ? new Date(timestamp) : new Date(timestamp * 1000));
        return format.replace(formatChr, formatChrCb)
    };
    return this.date(format, timestamp)
}

function errorTemplate(main_data, caption, class_list = "notifi-content") {
    return `<div class="error-content ${class_list}"><div class="error-caption">${caption}</div><div class="error-message">${main_data}</div></div>`
};

function successTemplate(main_data, caption, class_list = "notifi-content") {
    return `<div class="success-content ${class_list}"><div class="success-caption">${caption}</div><div class="success-message">${main_data}</div></div>`
};

function highlightErrorRequired(container_el) {
    container_el.addClass('error-validate-container')
}

function removeHgltErrorRequired(container_el) {
    container_el.removeClass('error-validate-container')
}

function checkRequiredFields(check_settings, check_container, errFunction = null, removeErrorFunction = null) {
    let check_el_class = check_settings['check_el'],
        container_el_class = check_settings['container_el'],
        name_check_class = check_settings['name_check'],
        $all_check_el = check_container.find(check_el_class);
    errFunction = errFunction || highlightErrorRequired;
    removeErrorFunction = removeErrorFunction || removeHgltErrorRequired;
    all_error = [];
    $all_check_el.each(function() {
        let $this = $(this),
            $container_el = $this.closest(container_el_class),
            name_el = $container_el.find(name_check_class).text(),
            value = $this.val();
        if ($this.is('select')) {
            value = $this.find('option:selected').val()
        }
        value = $.trim(value);
        if ($this.is('.required') && !value) {
            all_error.push(name_el);
            errFunction($container_el)
        } else {
            removeErrorFunction($container_el)
        }
    });
    return !!(all_error.length) ? all_error : false
}

function source_autocomplete(request, response, data_sampling, add_data_request = true, ajax_data = {}, curr_input = null) {
    curr_input = curr_input || $(this.element);
    var term = (request.term).toLowerCase();
    var main_request_data = {};
    var handler_mod = '';
    if (!!add_data_request) {
        var data_action = curr_input.closest('[data-action]').attr('data-action');
        var data_mod_name = curr_input.closest('[data-module-name]').attr('data-module-name');
        handler_mod = 'handler_' + data_mod_name;
        main_request_data = {
            'action': data_action,
            [data_mod_name + '_data']: {
                'term': term,
                'add_data': add_data_request
            }
        }
    }

    function success(data) {
        try {
            var json_data = JSON.parse(data);
            if ((Array.isArray(json_data) && json_data.length > 0)) {
                response($.map(json_data, function(item) {
                    return data_sampling(item, term)
                }))
            } else {
                notification_close();
                tmp_err = errorTemplate(json_data['body']['errors']['message'], 'Ошибка получения данных');
                var notifi_id = Notification('info', '', tmp_err, false);
                var timeout = setTimeout(function() {
                    notification_close(notifi_id)
                }, 15000)
            }
        } catch (e) {
            notification_close();
            tmp_err = errorTemplate(data, 'Неверная структура данных');
            var notifi_id = Notification('info', '', tmp_err, false);
            var timeout = setTimeout(function() {
                notification_close(notifi_id)
            }, 15000)
        }
    }
    let default_ajax_data = {
        success: success,
    };
    let main_ajax_data = Object.assign({}, default_ajax_data, ajax_data);
    AJAX_SEND_DATA(handler_mod, main_request_data, main_ajax_data)
};

function collectDataForm2js($el, $containers = false) {
    var main_wrapper = $el.closest('[data-action]'),
        methodName = main_wrapper.attr('data-action'),
        collect_container = !!$containers ? $containers : main_wrapper,
        formData = collect_container.find("input.form2js:not(.disabled), textarea.form2js:not(.disabled), select.form2js:not(.disabled)"),
        containerSelector = formData.get(),
        data__ = form2js(containerSelector);
    data__['action'] = methodName;
    return data__
};

function toggleDisableElement($el, action = 'toggle') {
    if (action === 'disabled') {
        $el.addClass('disabled');
        $el.prop('disabled', true)
    } else if (action === 'enabled') {
        $el.removeClass('disabled');
        $el.prop('disabled', false)
    } else {
        if ($el.is('.disabled') || $el.prop('disabled') === true) {
            $el.removeClass('disabled');
            $el.prop('disabled', false)
        } else {
            $el.addClass('disabled');
            $el.prop('disabled', true)
        }
    }
}
$(function() {
    $('body').on('click', '.toggle_visible', function() {
        var $this = $(this),
            $toggle_container = $($this.attr('href')),
            display = $this.attr('data-display');
        $toggle_container.slideToggle(200, function() {
            var $this = $(this);
            if (!!display && !$this.is(':hidden')) $toggle_container.css('display', display)
        })
    });
    $("body").on('click', '.mobile_toggle_menu', function() {
        var $this = $(this),
            $toggle_container = $($this.attr('href'));
        $this.toggleClass('active')
    });
    $("body").on('click', '.line_slide_controller', function() {
        var $this = $(this),
            direction = $this.attr('data-direction'),
            $container = $($this.attr('data-container'));
        var clientWidth = $container.width();
        var symbol_direction = '-';
        if (direction === 'right') {
            symbol_direction = '+'
        }
        $container.animate({
            scrollLeft: `${symbol_direction}=${clientWidth}`
        })
    });
    var tmp_btn = `<button class="btn_check_all_rows"title="Выбрать все поля"><i class="fa fa-file-text"></i></button>`;
    var container_btn_panel = $('#button-panel-table');
    if (container_btn_panel.children('.btn_check_all_rows').length < 1) {
        container_btn_panel.append(tmp_btn)
    }
    $('body').on('click', ".btn_check_all_rows", function(e) {
        var $table = $(".table-data tbody"),
            $selected_row = $table.find('tr.selected-row');
        if (!!$selected_row.length) {
            $selected_row.removeClass('selected-row');
            multiselect_callback(null)
        } else {
            var ctrl_a = new KeyboardEvent('keydown', {
                ctrlKey: true,
                metaKey: true,
                key: 65,
                keyCode: 65,
            });
            document.dispatchEvent(ctrl_a)
        }
    });
    $('.tooltip_active').tooltip({
        content: function() {
            return $(this).prop('title')
        }
    });

    function copyToBuffer(str) {
        let tmp = document.createElement('INPUT'),
            focus = document.activeElement;
        tmp.value = str;
        document.body.appendChild(tmp);
        tmp.select();
        document.execCommand('copy');
        document.body.removeChild(tmp);
        focus.focus()
    }
    $('body').on('click', '.add_to_buffer', function(e) {
        var $this = $(this),
            $copy_from = $($this.attr('data-copy-from')),
            buffer_text = '';
        if (!$copy_from.length) return;
        if ($copy_from.is('input') || $copy_from.is('textarea')) {
            buffer_text = $copy_from.val()
        } else {
            buffer_text = $copy_from.text()
        }
        copyToBuffer(buffer_text);
        notification_close();
        var tmp_success = successTemplate('', 'Скопировно в буфер обмена');
        var notifi_id = Notification('info', '', tmp_success, false);
        var timeout = setTimeout(function() {
            notification_close(notifi_id)
        }, 3000)
    })
});
var Base64 = {
    _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
    encode: function(e) {
        var t = "";
        var n, r, i, s, o, u, a;
        var f = 0;
        e = Base64._utf8_encode(e);
        while (f < e.length) {
            n = e.charCodeAt(f++);
            r = e.charCodeAt(f++);
            i = e.charCodeAt(f++);
            s = n >> 2;
            o = (n & 3) << 4 | r >> 4;
            u = (r & 15) << 2 | i >> 6;
            a = i & 63;
            if (isNaN(r)) {
                u = a = 64
            } else if (isNaN(i)) {
                a = 64
            }
            t = t + this._keyStr.charAt(s) + this._keyStr.charAt(o) + this._keyStr.charAt(u) + this._keyStr.charAt(a)
        }
        return t
    },
    decode: function(e) {
        var t = "";
        var n, r, i;
        var s, o, u, a;
        var f = 0;
        e = e.replace(/[^A-Za-z0-9\+\/\=]/g, "");
        while (f < e.length) {
            s = this._keyStr.indexOf(e.charAt(f++));
            o = this._keyStr.indexOf(e.charAt(f++));
            u = this._keyStr.indexOf(e.charAt(f++));
            a = this._keyStr.indexOf(e.charAt(f++));
            n = s << 2 | o >> 4;
            r = (o & 15) << 4 | u >> 2;
            i = (u & 3) << 6 | a;
            t = t + String.fromCharCode(n);
            if (u != 64) {
                t = t + String.fromCharCode(r)
            }
            if (a != 64) {
                t = t + String.fromCharCode(i)
            }
        }
        t = Base64._utf8_decode(t);
        return t
    },
    _utf8_encode: function(e) {
        e = e.replace(/\r\n/g, "\n");
        var t = "";
        for (var n = 0; n < e.length; n++) {
            var r = e.charCodeAt(n);
            if (r < 128) {
                t += String.fromCharCode(r)
            } else if (r > 127 && r < 2048) {
                t += String.fromCharCode(r >> 6 | 192);
                t += String.fromCharCode(r & 63 | 128)
            } else {
                t += String.fromCharCode(r >> 12 | 224);
                t += String.fromCharCode(r >> 6 & 63 | 128);
                t += String.fromCharCode(r & 63 | 128)
            }
        }
        return t
    },
    _utf8_decode: function(e) {
        var t = "";
        var n = 0;
        var r = c1 = c2 = 0;
        while (n < e.length) {
            r = e.charCodeAt(n);
            if (r < 128) {
                t += String.fromCharCode(r);
                n++
            } else if (r > 191 && r < 224) {
                c2 = e.charCodeAt(n + 1);
                t += String.fromCharCode((r & 31) << 6 | c2 & 63);
                n += 2
            } else {
                c2 = e.charCodeAt(n + 1);
                c3 = e.charCodeAt(n + 2);
                t += String.fromCharCode((r & 15) << 12 | (c2 & 63) << 6 | c3 & 63);
                n += 3
            }
        }
        return t
    }
};
var success_action_ajax = function(data, options = {}) {
    var data_json = {};
    options['notifi-selector'] = options['notifi-selector'] || {
        'delete': '',
        'add': '',
    };
    try {
        data_json = JSON.parse(data);
        var body_ = data_json['body'];
        var content = data_json['body']['content'];
        if (data_json['body']['success'] === false) {
            throw new Error(1)
        }
        if (!!options['success_func']) {
            options['success_func'](data_json)
        }
    } catch (e) {
        var err_mess = '';
        if (!options['err_mess_func']) {
            if (!!data_json['body'] && !!data_json['body']['errors'] && !!data_json['body']['errors']['message']) {
                err_mess = data_json['body']['errors']['message'];
                if (!!data_json['body']['errors']['details'] && !!data_json['body']['errors']['details']['message']) {
                    err_mess += "<br>" + data_json['body']['errors']['details']['message']
                }
            }
        } else {
            err_mess = options['err_mess_func'](data_json)
        }
        if (!options['err_action_func']) {
            notification_close(false, false, options['notifi-selector']['delete']);
            err_mess = !!err_mess ? err_mess : 'Ошибка структуры данных json <br>' + data;
            tmp_err = errorTemplate(err_mess, 'Ошибка');
            var notifi_id = Notification('info', '', tmp_err, false, 'content-width-none', options['notifi-selector']['add']);
            var timeout = setTimeout(function() {
                notification_close(notifi_id)
            }, 30000)
        } else {
            options['err_action_func'](data_json, err_mess, data)
        }
    }
};

function transactionIDBRemoveExpire(DB, opt = {}) {
    try {
        var default_option = {
            live_time_minute: 180,
            type: 'readwrite',
            table_name: 'call_data',
            data: [],
        };
        var option = Object.assign(default_option, opt);
        let transaction = DB.transaction(option['table_name'], option['type']);
        let table_db = transaction.objectStore(option['table_name']);
        let all_cursor = table_db.openCursor();
        all_cursor.onsuccess = function() {
            let cursor = all_cursor.result;
            if (cursor) {
                let key = cursor.key;
                let value = cursor.value;
                if (!checkExpireTime(option['live_time_minute'], value['create_time'])) {
                    table_db.delete(key)
                }
                cursor.continue()
            } else {}
        }
    } catch (e) {
        console.log(e.message)
    }
};

function transactionGetIDB(DB, opt = {}) {
    try {
        var default_option = {
            type: 'readwrite',
            table_name: 'call_data',
            data: {},
            method: 'add',
            callback: function() {
                console.log('callback')
            },
        };
        var option = Object.assign(default_option, opt);
        let transaction = DB.transaction(option['table_name'], option['type']);
        let table_db = transaction.objectStore(option['table_name']);
        let request = table_db.get(option['data']['key']);
        request.onsuccess = function(e) {
            option['callback'](request.result)
        };
        request.onerror = function(e) {
            console.log(request.result)
        }
    } catch (e) {
        console.log(e.message)
    }
};

function transactionAddIDB(DB, opt = {}) {
    try {
        var default_option = {
            type: 'readwrite',
            table_name: 'call_data',
            data: {},
            method: 'add',
            callback: function() {
                console.log('callback')
            },
        };
        var option = Object.assign(default_option, opt);
        let transaction = DB.transaction(option['table_name'], option['type']);
        let table_db = transaction.objectStore(option['table_name']);
        let request;
        if (option['method'] === 'add') {
            request = table_db.add(option['data'])
        } else {
            if (!!option['data']['key']) {
                request = table_db.put(option['data']['value'], option['data']['key'])
            } else {
                request = table_db.put(option['data']['value'])
            }
        }
        request.onsuccess = function() {
            option['callback']()
        };
        request.onerror = function() {
            console.log("Ошибка", request.error)
        }
    } catch (e) {
        console.log(e.message)
    }
}
$(function() {
    $('body').on('click', '.show_modal_module', function(e) {
        var this_ = $(this),
            mod_name = this_.attr('data-mod-name'),
            mod_title = this_.attr('data-mod-title'),
            mod_query = this_.attr('data-mod-query'),
            mod_path = this_.attr('data-mod-path');
        modal_window_show('modal', mod_title, '<div id="modal-window-data-' + mod_name + '"></div>', false, false);
        $('#modal-window-data-' + mod_name).html('<p style="text-align:center;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></p>').load(mod_path + '?' + mod_query)
    })
});

function parseDataInfoRow($row) {
    var data_info_attr = $row.attr('data-info');
    var data_info = data_info_attr.split('|');
    return data_info
}

function ordersRowData($selected_el) {
    var result_data = {};
    $selected_el.each(function() {
        var $row = $(this);
        var data_info_arr = parseDataInfoRow($row);
        var id = data_info_arr[0],
            order_id = data_info_arr[1],
            date_add = data_info_arr[2],
            date_upd = data_info_arr[3],
            phone_num = $row.find('.phone_num__row').attr('id') || '- номер не доступен -',
            buyer_name = $row.find('.buyer_name__row').get(0).textContent,
            delivery_name = $row.find('.delivery_name__row').get(0).textContent,
            ttn = $row.find('.ttn__row').get(0).textContent,
            is_edited_now = $row.is('[data-lock-order]');
        if (!id) return true;
        result_data[id] = {
            id,
            order_id,
            date_add,
            date_upd,
            phone_num,
            buyer_name,
            delivery_name,
            ttn,
            is_edited_now,
        }
    });
    return result_data
}

function filterRowData(selected_data_row, filter_data) {
    var result = {};
    for (var key in selected_data_row) {
        var filter_fail = false;
        var curr_data = selected_data_row[key];
        for (var f_key in filter_data) {
            if (curr_data[f_key] !== filter_data[f_key]) {
                filter_fail = true;
                break
            }
        }
        if (!!filter_fail) continue;
        result[key] = selected_data_row[key]
    }
    return result
}

function getSelectedRowData(template_type, $elements = null) {
    var $selected_el = $elements || $('#div-scroll-table .table-data tr.selected-row');
    var row_data = {};
    switch (template_type) {
        case 'orders':
            row_data = ordersRowData($selected_el);
            break
    }
    return row_data
}

function showVideoModal(title, src) {
    var content = '<div class="wrapper-response-frame"><iframe width="854" height="480" src="' + src + '" frameborder="0" allowfullscreen></iframe></div>';
    modal_window_show(false, title, content, false)
}
$(function() {
    $('body').on('click', '.btn-show-video-modal', function() {
        var $this = $(this),
            title = $this.attr("data-title"),
            src = $this.attr("data-src");
        showVideoModal(title, src)
    })
});

function decodePhoneNum(num) {
    var phone = num.substr(3);
    phone = Base64.decode(phone);
    return phone
}

function getPhoneBuyer() {
    var phone_decode = $('#bayer_phone').val(),
        phone_encode = $('[data-bayer-phone-number]').attr('data-bayer-phone-number');
    var result = {
        encoded: null,
        phone: null,
        phone_decoded: '',
        display_phone: '',
    };
    if (!!phone_decode) {
        result['encoded'] = false;
        result['phone'] = phone_decode;
        result['display_phone'] = phone_decode;
        result['phone_decoded'] = phone_decode
    } else if (!!phone_encode) {
        result['encoded'] = true;
        result['phone'] = phone_encode;
        result['display_phone'] = '- Доступ запрещен -';
        result['phone_decoded'] = decodePhoneNum(phone_encode)
    }
    return result
}

function insertPhoneBuyer(insert_to_selector) {
    var phone_data = getPhoneBuyer();
    var $insert_to = $(insert_to_selector);
    if (!!phone_data['encoded']) {
        $insert_to.prop({
            'readonly': true,
        });
        $insert_to.val('');
        $insert_to.removeProp('value');
        var $clone_el = $insert_to.clone();
        $clone_el.removeAttr("name").removeAttr("data-name").removeAttr("id").removeClass("data_collect").attr({
            'disabled': true
        }).addClass('disabled').val(phone_data['display_phone']);
        $insert_to.css('display', 'none');
        $insert_to.val(phone_data['phone_decoded']);
        $insert_to.after($clone_el)
    } else {
        $insert_to.val(phone_data['display_phone'])
    }
}

function getTemplateStatusRatio(data_crm = {}, data_module = {}) {
    var template_clone_status = `<div class="wrap-status-ratio flex clone-item"><div class="select-wrapper select-wrapper-crm flex"style="order: 2;"><p class="label flex"><span class="logo-img"style="background-image:url('${(data_crm['logo'] || '')}');"></span><b>${(data_crm['caption'] || '')}</b></p><select name="status_crm[]"class='select-item chosen-container main-select status-crm-select'><option value="false"data-img-src="123"></option>${(data_crm['option_list'] || '')}</select><div class="wrapper-add-select wrapper-cancel-description"><select name="add_status_crm[]"class='select-item chosen-container add-select'style="display:none;"><option value="false"></option>${(data_crm['add_option'] || '')}</select></div></div><div class="select-wrapper select-wrapper-module flex"style="order: 1;"><p class="label flex"><span class="logo-img"style="background-image:url('${(data_module['logo'] || '')}');"></span><b>${(data_module['caption'] || '')}</b></p><select name="status_module[]"class='select-item chosen-container main-select'><option value="false"></option>${(data_module['option_list'] || '')}</select></div><div class="select-wrapper-wide select-wrapper select-wrapper-exception flex"style="order: 3;"><p class="label flex"><span class="logo-img"style="background-image:url('${(data_crm['logo'] || '')}');"></span><b> Исключения <i class="fa fa-info-circle tooltip_active tooltip-info-icon" title="Статусы црм в которых данное условие не будет выполняться"></i></b></p><input type="hidden"class="exception_aggregator input-item"name="exception_status_crm[]"><select multiple class='select-item chosen-container main-select exception_select'><option value="false"></option>${(data_crm['option_list'] || '')}</select></div><div class="wrap_action_btn flex"style="order: 33;"><div class="remove-item-action btn-item flex"><i class="fa fa-remove"aria-hidden="true"></i></div></div></div>`;
    return template_clone_status
};

function init_chosen(cloned_el) {
    var $all_select = cloned_el.find('.select-item.chosen-container');
    var chosen_params = {
        disable_search: false,
        search_contains: true,
        allow_single_deselect: true,
    };
    $all_select.each(function() {
        var $this = $(this);
        if ($this.find('[data-bg-color]').length > 0) {
            $this.chosenImage(chosen_params)
        } else {
            $this.chosen(chosen_params)
        }
    });
    return cloned_el
};

function clone_item_callback(db_data, name_tmp = "status") {
    var $order_status = db_data;
    return function(cloned_el, template) {
        if (typeof $order_status === 'object' && $order_status !== null) {
            try {
                var all_clone_el = false;
                for (var id in $order_status) {
                    var curr_item = $order_status[id];
                    var select_id_crm = curr_item['id_crm'];
                    var select_id_module = curr_item['id_module'];
                    var select_id_cancel_desc = curr_item['id_cancel'];
                    var select_id_exception = curr_item['id_exception'];
                    var new_cloned_el = $(template).clone(true);
                    new_cloned_el.find('.select-wrapper-crm .select-item.main-select option[value="' + select_id_crm + '"]').prop('selected', true);
                    new_cloned_el.find('.select-wrapper-module .select-item.main-select option[value="' + select_id_module + '"]').prop('selected', true);
                    if (!!select_id_cancel_desc) {
                        new_cloned_el.find('.select-wrapper-crm .select-item.add-select option[value="' + select_id_cancel_desc + '"]').prop('selected', true)
                    }
                    if (!!select_id_exception) {
                        var select_id_exception_arr = select_id_exception.split(',');
                        new_cloned_el.find('.select-wrapper-exception .exception_aggregator').val(select_id_exception_arr);
                        new_cloned_el.find('.select-wrapper-exception .exception_select option').each(function() {
                            var $this = $(this),
                                current_val = $this.val();
                            if (select_id_exception_arr.includes(current_val)) $this.prop('selected', true)
                        })
                    }
                    init_chosen(new_cloned_el);
                    if (!all_clone_el) {
                        all_clone_el = new_cloned_el
                    } else {
                        all_clone_el = all_clone_el.add(new_cloned_el)
                    }
                }
                cloned_el = all_clone_el
            } catch (e) {
                console.log(e);
                init_chosen(cloned_el)
            }
        } else {
            init_chosen(cloned_el)
        }
        return cloned_el
    }
};
$(function() {
    $('body').on('click', '.clone-container__ .remove-item-action', function() {
        remove_clone_item(this, function(current_clone_item, count_clone_item) {
            if (count_clone_item < 2) {
                var select = current_clone_item.find('.select-item'),
                    input_item = current_clone_item.find('.input-item'),
                    select_option = select.find('option:selected');
                select_option.prop('selected', false);
                input_item.val("");
                select.trigger('chosen:updated')
            } else {
                current_clone_item.remove()
            }
        })
    });
    $('body').on('click', '.add-template-item-action', function() {
        var this_ = $(this),
            main_container = this_.closest('[data-clone-container]'),
            container_name = main_container.attr('data-clone-template'),
            clone_container = main_container.find('.clone-container__');
        switch (container_name) {
            case 'status':
                var settings_crm = clone_container.data('settings_crm');
                var settings_module = clone_container.data('settings_module');
                var template_clone_status = getTemplateStatusRatio(settings_crm, settings_module);
                clone_item(clone_container, template_clone_status, init_chosen);
                break
        }
    });
    $('body').on('change', '.status-crm-select', function() {
        var $this = $(this),
            $selected_option = $this.find('option:selected'),
            opt_value = $selected_option.val(),
            $main_container = $this.closest('.select-wrapper'),
            $cancel_desc_container = $main_container.find('.wrapper-cancel-description'),
            $cancel_desc_select = $cancel_desc_container.find('.select-item');
        console.log(opt_value);
        if (opt_value === '13') {
            $cancel_desc_container.fadeIn(111);
            $cancel_desc_select.trigger('chosen:updated')
        } else {
            $cancel_desc_container.fadeOut(1);
            $cancel_desc_select.find('option').prop('selected', false);
            $cancel_desc_select.trigger('chosen:updated')
        }
    })
});

function get_moda_window_id($modal_container_el) {
    var id_modal = $modal_container_el.attr('id');
    id_modal = id_modal.split('-');
    var uniqid = id_modal[1];
    return uniqid
}

function modal_window_close__not_disable(count_modals) {
    var $all_modal = $('.modal-window');
    $all_modal.each(function(e) {
        var $this = $(this);
        var is_disable_window = (!!$this.children('.disable-modal-window').length);
        if (is_disable_window) return true;
        var id_modal = get_moda_window_id($this);
        modal_window_close(id_modal);
        if (!!window.OPENED_ORDER_DATA && id_modal === window.OPENED_ORDER_DATA['modal_uniq']) {
            LockedOrder_OFF(window.OPENED_ORDER_DATA['id'], interval_lock);
            window.OPENED_ORDER_DATA = {}
        }
    })
};

function changeSelectedChosen(value, selector) {
    if (!value) return;
    var $select = $(selector),
        $option_selected = $select.find('option:selected'),
        $optionForSet = $select.find('option[value="' + value + '"]');
    if (!$optionForSet.length) return;
    $option_selected.prop('selected', false);
    $optionForSet.prop('selected', true);
    $select.trigger('change');
    $select.trigger('chosen:updated')
}

function changeStatusInModalOrder(value) {
    var selector = $('#modal-orders-content [name="status"]');
    changeSelectedChosen(value, selector)
}

function changeCancelDescInModalOrder(value) {
    var selector = $('#modal-orders-content [name="cancel_description"]');
    changeSelectedChosen(value, selector)
}

function checkAutoupdateStatusesByModName(mod_name) {
    if (!window.MODULE_ACTIVE || !window.MODULE_ACTIVE['autoupdate_statuses'] || !window.MODULE_ACTIVE['autoupdate_statuses']['check_status'] || !window.MODULE_ACTIVE['autoupdate_statuses'][mod_name]) return false;
    return window.MODULE_ACTIVE['autoupdate_statuses'][mod_name]['is_active']
}
$(function() {
    $('body').on('click', '.change-tab-container .tab', function() {
        console.log(111);
        var $this = $(this),
            $main_tab_container = $this.closest('.change-tab-container'),
            $all_tabs = $main_tab_container.find('.tab');
        $all_tabs.removeClass('active-tab');
        $this.addClass('active-tab');
        var container_for_show = $this.attr('data-tab'),
            content_selector = $main_tab_container.attr('data-content-selector'),
            $all_content = $(content_selector);
        $all_content.hide();
        $(container_for_show).show()
    })
});

function HtmlSymbolEncode(text) {
    var map = {
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&apos;',
        "\\": '&#92;'
    };
    return ('' + text).replace(/<|>|"|'|\\/g, function(m) {
        return map[m]
    })
}

function HtmlSymbolDecode(text) {
    var map = {
        '&lt;': '<',
        '&gt;': '>',
        '&quot;': '"',
        '&apos;': "'",
        "&#92;": '\\',
    };
    return ('' + text).replace(/&lt;|&gt;|&quot;|&apos;|&#92;/g, function(m) {
        return map[m]
    })
}

function setCountOrderOnAddNew() {
    var count_new_order = +($('#info-count_new-orders').text()) || 0;
    var count_status_new = +($('#tab-status-3 > b').text()) || 0;
    count_new_order++;
    count_status_new++;
    $('#info-count_new-orders').text(count_new_order);
    $('#menu-full .count-info-li').text(count_new_order);
    $('#menu-full [title="Заказы"] .count-info').text(count_new_order);
    $('#tab-status-3 > b').text(count_status_new)
}


    
window.SETTINGS_CRM = {};
window.SETTINGS_CRM['route'] = 'orders';
window.SETTINGS_CRM['allowed_office'] = ["1","2","3","5","6",""];
window.SETTINGS_CRM['hidePhoneOrders'] = 0;
window.SETTINGS_CRM['edit_order'] = 1;
window.SETTINGS_CRM['status_color'] = {"3":"#F6F6F6","11":"#cef994","13":"#fb9da3","14":"#f9f943","18":"#77d449","31":"#e90e0c","52":"#dd7e6b","67":"#b4a7d6","72":"#d9ead3","73":"#ead1dc","74":"#d9d9d9","75":"#cccccc","76":"#cccccc","78":"#e69138","79":"#c48275","83":"#c7e132","89":"#e6b8af","93":"#d9d2e9","94":"#b4a7d6","95":"#e4cc8d","96":"#b6d7a8","101":"#cae3e7","102":"#f4cccc","104":"#d9ead3","106":"#d9c8c8","110":"#fce5cd","111":"#ffe599","112":"#a2c4c9","113":"#93c47d","114":"#b6d7a8"};

window.SETTINGS_CRM['realTimeChangeOrders'] = true;



//console.log(SETTINGS_CRM);