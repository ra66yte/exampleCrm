<?php
include_once 'system/core/begin.php';

if (!checkAccess('delivery_methods')) redirect('/denied');

$data['title'] = 'Способы доставки';
include_once 'system/core/header.php';
?>

<script>

    $(function(){
        // Поиск по таблице
        let form = $('#form-delivery-methods'),
            table = $('#delivery-methods__table');

        form.find(".chosen-select").chosen()
            .change(function(e){
                form.trigger('submit');
            });

        form.on('submit', function(e){
            let page = getParameterByName('page'),
                data = $(this).serializeArray();

            if ($('.status-panel__search').length) {
                $('.status-panel__search div').remove();
            }
            $.each(data, function(e){
                if (this.value != '') {
                    let field_name = table.find('th[data-name=' + this.name + ']').text();
                    $('.status-panel__search').append('<div data-field="' + this.name + '" class="data-field"><span></span> <i class="fa fa-remove" onclick="RemoveSearchField(\'' + this.name + '\');"></i></div>');
                    $('.status-panel__search div').last().find('span').text(field_name + ': ' + this.value);
                    $.each($('tr.table-row-search select[name=' + this.name + ']'), function(e){
                        let name_select = $(this).next().find('a.chosen-single span').text();
                        $('.status-panel__search div').last().find('span').text(field_name + ': ' + name_select);
                    });
                }
            });
            
            $.ajax({
            type: "POST",
            url: "/system/ajax/delivery_methods.php?module=search&page=" + page,
            data: data,
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;


                $('#delivery-methods__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows, true);
                console.log(tableRows)
                $('#delivery-methods__table tbody').html(tableRows);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

            },
            complete: function() {
                stopPreloader();
            }
        });
            return false; 
        });

        $('button#button-search').on('click', function(e) {
            form.trigger('submit');
        });

        loadDeliveryMethods();

        $('#form-delivery-methods').on('dblclick', 'tr.table__item', function(e) {
            if (!$(this).hasClass('disabled')) {
                if ($(this).hasClass('blocked-row')) {
                    // Запрещаем открытие редактируемой строки
                } else {
                    /* Отправляем ajax на обновление блокировки заказа */
                    $('tr.table__active').removeClass('table__active'); // Убираем выделеные у всех выделенных заказов
                    $(this).addClass('table__active static blocked-row'); //  Этот заказ активный
                    $('.status-panel div.status-panel__count').html('<i class="fa fa-info-circle"></i> Выделено: ' + $('.table__active').length).show();
                    $('.status-panel button#button-selected').removeAttr('disabled');
                }
                let delivery_id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewDeliveryMethod?delivery_id=" + delivery_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            showModalWindow('Способ доставки - ' + jsonData.title.delivery_name, '/ajax_viewDeliveryMethod?delivery_id=' + delivery_id);
                        } else {
                            showModalWindow(null, null, 'error', jsonData.error);
                        }
                    }    
                });
            }
        });

        $('.status-panel').on('click', '#button-edit', function(e) {
            if (!$(this).hasClass('disabled')) {
                let delivery_id = $('tr.table__active').attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewDeliveryMethod?delivery_id=" + delivery_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            showModalWindow('Способ доставки - ' + jsonData.title.delivery_name, '/ajax_viewDeliveryMethod?delivery_id=' + delivery_id);
                        } else {
                            showModalWindow(null, null, 'error', jsonData.error);
                        }
                    }    
                });
            }
        });

    });

    function RemoveSearchField(field) {
        $.each($('tr input'), function(e) {
            if ($(this).attr('name') == field) {
                $('.status-panel__search div[data-field=' + field + ']').remove();
                $(this).val('');
                $('#form-delivery-methods').trigger('submit');
            }
        });
        $.each($('tr select'), function(e) {
            if ($(this).attr('name') == field) {
                $('.status-panel__search div[data-field=' + field + ']').remove();
                $(this).val('');
                $('#form-delivery-methods').trigger('submit');
            }
        });
        $('.chosen-select').trigger('chosen:updated');
    }

    function renderRows(data) {
        let tableRows = '',
            countTd = $('#delivery-methods__table thead').find('tr td').length;

        if (Array.isArray(data) && data.length) {
            $.each(data, function(i, item){
                tableRows += ('<tr data-id="' + item.id_item + '" class="table__item' + (item.permanent == 'on' ? ' disabled' : '') + '">' +
                                '<td>' + item.id_item + '</td>' +
                                '<td class="center table__item-icon"><img src="/system/images/delivery/' + (item.permanent == 'on' ? '' : item.chief_id + '/') + item.icon + '" alt="del"></td>' +
                                '<td>' + item.name + '</td>' +
                                '<td align="center">' +
                                    '<label class="toggle">' +
                                        '<input' + (item.permanent == 'on' ? ' disabled' : '') + ' type="checkbox"' + (item.status == 'on' ? ' checked' : '') + ' onclick="changeStatus(\'delivery_methods\', \'' + item.status + '\', \'' + item.id_item + '\');" class="toggle__input">' +
                                        '<div class="toggle__control"></div>' +
                                    '</label>' +
                                '</td>' +
                                '<td align="center">' + item.id_item + '</td>' +
                             '</tr>');
                });
        } else {
            tableRows = ('<tr class="no-result">' +
                            '<td colspan="' + countTd + '">' + (search ? 'Ничего не найдено!' : 'Здесь ничего нет.') + '</td>' +
                        '</tr>');   
        }
        return tableRows;
    }

    function loadDeliveryMethods(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "delivery_methods" };
            history.pushState(stateObj, "delivery_methods", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();
    
        $.ajax({
            type: "POST",
            url: "/system/ajax/delivery_methods.php?show=true&page=" + page,
            data: {},
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                    console.log(jsonData)

                $('#delivery-methods__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows);
                $('#delivery-methods__table tbody').html(tableRows);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

            },
            complete: function() {
                stopPreloader();
            }
        });
    }

    function addDeliveryMethod() {
        showModalWindow('Добавление нового способа доставки', '/system/ajax/addDeliveryMethod.php');
    }

    function deleteDeliveryMethods() {
        let countDeliveryMethods = $('tr.table__active').length;
        if (countDeliveryMethods < 1) {
            console.log('Ничего не выбрано..');
        } else {
            let name = 'способа доставки';
            if (countOrders > 1) name = 'способов доставки';
            showModalWindow('Удаление ' + name, '/ajax_deleteDelivery?count=' + countDeliveryMethods, 'confirm');
        }
    }
</script>

            <!-- Content -->
            <section class="content">
                <div class="status-panel">
                    <div class="status-panel__row">
                        <div class="status-panel__count"></div>
                        <div class="status-panel__search"></div>
                    </div>
                    <button id="button-search" style="font-size: 14px"><i class="fa fa-search"></i></button>
                    <div style="position: relative">
                        <button id="button-selected" style="font-size: 14px" onclick="showOptions();" disabled><i class="fa fa-cog"></i></button>
                        <div class="status-panel__options">
                            <ul>
                                <li id="print-table"><i class="fa fa-print"></i> Печать таблицы</li>
                                <li class="hr"></li>
                                <li id="button-edit" class="disabled"><i class="fa fa-edit"></i> Редактировать</li> <!--  style="color: #8A5A00" -->
                                <li class="hr"></li>
                                <li id="button-delete" class="disabled"><i class="fa fa-trash-o"></i> Удалить</li> <!--  onclick="deleteDeliveryMethods();" style="color: #AE0000" -->
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" style="color: green" disabled><i class="fa fa-plus-square"></i> Добавить</button> <!-- onclick="addDeliveryMethod();" -->
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-delivery-methods" method="post">
                        <table id="delivery-methods__table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="icon" style="min-width: 50px">Icon</th>
                                    <th data-name="name" style="min-width: 120px">Название</th>
                                    <th data-name="status" style="min-width: 95px">Статус</th>
                                    <th data-name="sort" style="min-width: 30px"><i class="fa fa-sort"></i></th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 20px"><input type="text" name="id" spellcheck="false" autocomplete="off"></td>
                                    <td></td>
                                    <td style="max-width: 120px"><input type="text" name="name" spellcheck="false" autocomplete="off"></td>
                                    <td>
                                        <select name="status" class="chosen-select">
                                            <option value="">Все</option>
                                            <option value="on">On</option>
                                            <option value="off">Off</option>
                                        </select>
                                    </td>
                                    <td></td>
                                </tr>
                            </thead> 
                            <tbody>
                            </tbody>
                        </table>
                        <input type="submit" style="display: none">
                    </form>
                </div>
<?
include_once 'system/core/footer.php';
