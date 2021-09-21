<?php
include_once 'system/core/begin.php';

if (!checkAccess('order_statuses')) redirect('/denied');

$data['title'] = 'Статусы заказов';
include_once 'system/core/header.php';
?>
<style>
    .ui-state-highlight {
        background: #ffffca;
        height: 26px;
    }
</style>
<script>
    $(function(){
        // Поиск по таблице
        let form = $('#form-order-statuses'),
            table = $('#order-statuses__table'),
            location = 'order_statuses';

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
                    let thisName = this.name,
                        thisValue = this.value,
                        date_word = '';
                        
                    if (thisName == 'date_added_start' || thisName == 'date_added_end') {
                        thisName = 'date_added';
                        if (this.name == 'date_added_start') date_word = 'с';
                        else date_word = 'по';
                    }
                    let field_name = table.find('th[data-name=' + thisName + ']').text();
                    $('.status-panel__search').append('<div data-field="' + this.name + '" class="data-field"><span></span> <i class="fa fa-remove" onclick="RemoveSearchField(\'' + this.name + '\');"></i></div>');
                    $('.status-panel__search div').last().find('span').text(field_name + ': ' + (date_word != '' ? date_word + ' ' : '') + this.value);


                    let detect = new MobileDetect(window.navigator.userAgent);
                    if (detect.mobile()) {
                        $.each($('tr.table-row-search select[name=' + this.name + ']'), function(e){
                            let value_select = $(this).find('option[value=' + thisValue  + ']').text();
                            $('.status-panel__search div').last().find('span').text(field_name + ': ' + value_select);
                        });
                    } else {
                        $.each($('tr.table-row-search select[name=' + this.name + ']'), function(e){
                            let value_select = $(this).next().find('a.chosen-single span').text();
                            $('.status-panel__search div').last().find('span').text(field_name + ': ' + value_select);
                        });
                    }
                    
                }
            });

            $.ajax({
                type: "POST",
                url: "/system/ajax/order_statuses.php?module=search&page=" + page,
                data: data,
                beforeSend: function() {
                    startPreloader();
                },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        tableRows;

                    $('#order-statuses__table tbody').html('');
                    // Items
                    tableRows = renderRows(jsonData.rows, true);
                    $('#order-statuses__table tbody').html(tableRows);

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


        loadStatuses();

        $('#form-order-statuses').on('dblclick', 'tr.table__item', function(e) {
            if (!$(this).hasClass('disabled') && !$(this).hasClass('blocked-row')) {
                let itemId = $(this).attr('data-id'),
                    wsData = {
                        action: 'lock item',
                        data: {
                            itemId: itemId,
                            location: location
                        }
                    }

                $('.table tbody tr').each(function(){
                    $(this).removeClass('table__active');
                });

                sendMessage(ws, JSON.stringify(wsData));
                    
                $(this).addClass('table__active static blocked-row');
                $('.status-panel div.status-panel__count').html('<i class="fa fa-info-circle"></i> Выделено: ' + $('.table__active').length).show();
                $('.status-panel button#button-selected').removeAttr('disabled');

                $.ajax({
                    type: "POST",
                    url: "/ajax_viewOrderStatus?status_id=" + itemId + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            showModalWindow('Статус заказов #' + itemId + ': ' + jsonData.title.status_name, '/ajax_viewOrderStatus?status_id=' + itemId);
                        }
                    }    
                });
                
            } else if ($(this).hasClass('blocked-row')) {
                showModalWindow('Статус заказов недоступен!', null, 'error', 'В данный момент статус редактируется другим пользователем!');
            }
        });

        $('.status-panel').on('click', '#button-edit', function(e) {
            if (!$(this).hasClass('disabled')) {
                let status_id = $('tr.table__active').attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewOrderStatus?status_id=" + status_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            showModalWindow('Статус заказов #' + status_id + ': ' + jsonData.title.status_name, '/ajax_viewOrderStatus?status_id=' + status_id);
                        }
                    }    
                });
            }
        });


        let fixHelper = function(e, ui) {
            ui.children().each(function() {
                $(this).width($(this).width());
            });
            return ui;
        };
        $('.table tbody').sortable({
            axis: 'y',
            opacity: 0.8,
            distance: 5,
            cursor: "move",
            placeholder: "ui-state-highlight",
            cancel: ".disabled, input[type='checkbox']",
            helper: fixHelper,
            stop: function(){
                updateSort('statuses');
            }
        });
    });

    function RemoveSearchField(field) {
        $.each($('tr input, tr select'), function(e) {
            if ($(this).attr('name') == field) {
                $('.status-panel__search div[data-field=' + field + ']').remove();
                $(this).val('');
                $('#form-order-statuses').trigger('submit');
            }
        });
        $('.chosen-select').trigger('chosen:updated');
    }

    function renderRows(data, search = false) {
        let tableRows = '',
            countTd = $('#order-statuses__table thead').find('tr td').length;

        if (Array.isArray(data) && data.length) {
            $.each(data, function(i, item){
                tableRows += ('<tr data-id="' + item.id_item + '" class="table__item' + ((item.status == 'off' || item.permanent == 1) ? ' disabled' : '') + '">' +
                                '<td>' + item.id_item + '</td>' +
                                '<td align="center"><img src="/getImage/?color=' + item.color.replace('#', '') + '" alt="status"></td>' +
                                '<td>' + item.name + '</td>' +
                                '<td align="center">' +
                                    '<label class="toggle">' +
                                        '<input type="checkbox"' + (item.status == 'on' ? ' checked' : '') + (item.permanent == 1 ? ' disabled' : '') + ' onclick="changeStatus(\'order_statuses\', \'' + item.status + '\', \'' + item.id_item + '\');" class="toggle__input"> ' +
                                        '<div class="toggle__control"></div>' +
                                    '</label>' +
                                '</td>' +
                                '<td align="center">' + item.warehouse + '</td>' +
                                '<td align="center">' + (item.block == 'on' ? '<i class="fa fa-shield"></i>' : '') + '</td>' +
                                '<td align="center">' + (item.country == 0 ? '<i class="fa fa-globe"></i>' : '<img src="/img/countries/' + item.country + '.png" alt="*">') + '</td>' +
                                '<td align="center">' + (item.permanent == 1 ? '<i class="fa fa-lock"></i>' : item.sort) + '</td>' +
                             '</tr>');
                });
        } else {
            tableRows = ('<tr class="no-result">' +
                            '<td colspan="' + countTd + '">' + (search ? 'Ничего не найдено!' : 'Здесь ничего нет.') + '</td>' +
                        '</tr>');   
        }
        return tableRows;
    }

    function loadStatuses(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "order_statuses" };
            history.pushState(stateObj, "order_statuses", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();

        $.ajax({
            type: "POST",
            url: "/system/ajax/order_statuses.php?show=true&page=" + page,
            data: {},
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#order-statuses__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows);
                $('#order-statuses__table tbody').html(tableRows);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

            },
            complete: function() {
                stopPreloader();
            }
        });
    }

    function updateStatuses(data) {
        if (!data) return false;
        let i = 0;
        $.each($('#order-statuses__table tbody tr'), function() {
            if (!$(this).hasClass('disabled')) {
                $(this).find('td:last-child()').text(data[i]);
            }
            i++;
        });
    }

    function addOrderStatus() {
        showModalWindow('Добавление нового статуса заказов', '/system/ajax/addOrderStatus.php');
    }

    function deleteStatuses() {
        let countStatuses = $('tr.table__active').length;
        if (countStatuses < 1) {
            console.log('Ничего не выбрано..');
        } else {
            let name = 'статуса заказов';
            if (countStatuses > 1) name = 'статусов заказов';
            showModalWindow('Удаление ' + name, '/ajax_deleteStatus?count=' + countStatuses, 'confirm');
        }
    }
</script>
                <!-- Content -->
                <section class="content" data-location="order_statuses">
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
                                <li id="button-edit" style="color: #8A5A00"><i class="fa fa-edit"></i> Редактировать</li>
                                <li class="hr"></li>
                                <li id="button-delete" style="color: #AE0000" onclick="deleteStatuses();"><i class="fa fa-trash-o"></i> Удалить</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" onclick="addOrderStatus();" style="color: green"><i class="fa fa-plus-square"></i> Добавить</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-order-statuses" method="post">
                        <table id="order-statuses__table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="color" style="min-width: 60px">Цвет</th>
                                    <th data-name="name" style="min-width: 170px">Название</th>
                                    <th data-name="status" style="min-width: 95px">Статус</th>
                                    <th data-name="warehouse" style="min-width: 100px">Склад</th>
                                    <th data-name="block" style="min-width: 100px" title="Сотрудник, работавший с заказом, навсегда закрепляется за ним.">Блок</th>
                                    <th data-name="country" style="min-width: 200px">Направление</th>
                                    <th data-name="sort" style="min-width: 30px"><i class="fa fa-sort"></i></th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 20px"><input type="text" name="id" spellcheck="false" autocomplete="off"></td>
                                    <td></td>
                                    <td><input type="text" name="name" spellcheck="false" autocomplete="off"></td>
                                    <td>
                                        <select name="status" class="chosen-select">
                                            <option value="">Все</option>
                                            <option value="on">On</option>
                                            <option value="off">Off</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="warehouse" class="chosen-select">
                                            <option value="">Все</option>
                                            <option value="in">На склад</option>
                                            <option value="out">Со склада</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="block" class="chosen-select">
                                            <option value="">Все</option>
                                            <option value="on">Да</option>
                                            <option value="off">Нет</option>
                                        </select>
                                    </td>
                                    <td style="max-width: 200px">
                                        <select name="country" class="chosen-select">
<?
$countries = $db->query("SELECT COUNT(*) FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($countries[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $countries = $db->query("SELECT `countries`.`id`, `countries`.`name`, `countries`.`code` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "' ORDER BY `id`");
?>
                                            <option value="">Все</option>
<?
    while ($country = $countries->fetch_assoc()) {
?>
                                            <option data-img-src="/img/countries/<?=strtolower($country['code'])?>.png" value="<?=$country['id']?>"><?=protection($country['name'] . ' (' . $country['code'] . ')', 'display')?></option>
<?
    }
}
?>
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
