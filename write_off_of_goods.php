<?php
include_once 'system/core/begin.php';

if (!checkAccess('write_off_of_goods')) redirect('/denied');

$data['title'] = 'Списание товара';
include_once 'system/core/header.php';
?>
<script>
    $(function() {
        // Поиск по таблице
        let form = $('#form-woog'),
            table = $('#woog__table');

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
                url: "/system/ajax/write_off_of_goods.php?module=search&page=" + page,
                data: data,
                beforeSend: function() {
                    startPreloader();
                },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        tableRows;

                    $('#woog__table tbody').html('');
                    // Items
                    tableRows = renderRows(jsonData.rows, true);
                    console.log(tableRows)
                    $('#woog__table tbody').html(tableRows);

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

        loadWOOG();

        $('#form-woog').on('dblclick', 'tr.table__item', function(e) {
            if (!$(this).hasClass('disabled')) {
                if ($(this).hasClass('blocked-row')) {
                    // Запрещаем открытие редактируемой строки
                } else {
                    $('tr.table__active').removeClass('table__active'); // Убираем выделеные у всех выделенных заказов
                    $(this).addClass('table__active static blocked-row'); //  Этот заказ активный
                    $('.status-panel div.status-panel__count').html('<i class="fa fa-info-circle"></i> Выделено: ' + $('.table__active').length).show();
                    $('.status-panel button#button-selected').removeAttr('disabled');
                }
                let woog_id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/viewWOOG.php?woog_id=" + woog_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        var jsonData = JSON.parse(response);
                        showModalWindow('Списание товара <b>#' + jsonData.title.id_item + '</b> от ' + jsonData.title.date_added, '/system/ajax/viewWOOG.php?woog_id=' + jsonData.title.id_item);
                    }    
                });
            }
        });

        $('.status-panel').on('click', '#button-edit', function(e) {
            if (!$(this).hasClass('disabled')) {
                let woog_id = $('tr.table__active').attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/viewWOOG.php?woog_id=" + woog_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        var jsonData = JSON.parse(response);
                        showModalWindow('Списание товара - <b>' + jsonData.title.id_item + '</b> от ' + jsonData.title.date_added, '/system/ajax/viewWOOG.php?woog_id=' + jsonData.title.id_item);
                    }    
                });
            }
        });
        

        $('#date_added_start, #date_added_end').datepicker({ // datetimepicker с выбором времени
            dateFormat:"dd-mm-yy",
            onSelect: function() {
                form.trigger('submit');
            }
        });


    });

    function RemoveSearchField(field) {
        $.each($('tr input, tr select'), function(e) {
            if ($(this).attr('name') == field) {
                $('.status-panel__search div[data-field=' + field + ']').remove();
                $(this).val('');
                $('#form-woog').trigger('submit');
            }
        });
        $('.chosen-select').trigger('chosen:updated');
        
    }

    function renderRows(data, search = false) {
        let tableRows = '',
            countTd = $('#woog__table thead').find('tr td').length;

        if (Array.isArray(data) && data.length) {
            $.each(data, function(i, item){
                itemProducts = '';
                $.each(item.products, function(k, product){
                    itemProducts += ('<span>' + product.id_item + ' - ' + product.name + ' (<b>' + product.count + '</b> шт.)' + (product.attributes != '' ? ' <small style="font-wight: bold; color: red; font-style: italic">' + product.attributes + '</small>' : '') + '</span>' +
                                '<br>');
                });

                tableRows += ('<tr data-id="' + item.id_item + '" class="table__item">' +
                                '<td>' + item.id_item + '</td>' +
                                '<td class="center"><small><i class="fa fa-user"></i> ' + item.employee + '</small></td>' +
                                '<td style="line-height: 10px">' + itemProducts + '</td>' +
                                '<td>' + item.comment + '</td>' +
                                '<td class="center"><small><i class="fa fa-calendar-minus-o"></i> ' + item.date_added + '</small></td>' +
                             '</tr>');
                });
        } else {
            tableRows = ('<tr class="no-result">' +
                            '<td colspan="' + countTd + '">' + (search ? 'Ничего не найдено!' : 'Здесь ничего нет.') + '</td>' +
                        '</tr>');   
        }
        return tableRows;
    }

    function loadWOOG(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "woog" };
            history.pushState(stateObj, "woog", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();
    
        $.ajax({
            type: "POST",
            url: "/system/ajax/write_off_of_goods.php?show=true&page=" + page,
            data: {},
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#woog__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows);
                $('#woog__table tbody').html(tableRows);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

            },
            complete: function() {
                stopPreloader();
            }
        });
    }
    
    function addWOOG() {
        showModalWindow('Списание товара', '/system/ajax/addWOOG.php');
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
                                <li id="button-edit" style="color: #8A5A00"><i class="fa fa-edit"></i> Открыть</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" style="color: green" onclick="addWOOG();"><i class="fa fa-plus-square"></i> Списать</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-woog" method="post" spellcheck="false" autocomplete="off">
                        <table id="woog__table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="employee_id" style="min-width: 160px">Сотрудник</th>
                                    <th data-name="product_id" style="min-width: 280px">Товар</th>
                                    <th data-name="comment" style="min-width: 200px">Описание</th>
                                    <th data-name="date_added" style="min-width: 170px">Дата</th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 20px"><input type="text" name="id"></td>
                                    <td style="max-width: 160px">
                                        <select name="employee_id" class="chosen-select">
<?
$employees = $db->query("SELECT COUNT(*) FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = 0) OR `chief_id` = '" . $chief['id'] . "'")->fetch_row();
if ($employees[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $employees = $db->query("SELECT `id_item`, `name` FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = 0) OR `chief_id` = '" . $chief['id'] . "'");
?>
                                            <option value="">Все</option>
<?
    while ($employee = $employees->fetch_assoc()) {
?>
                                            <option value="<?=$employee['id_item']?>"> <?=protection($employee['name'], 'display')?></option>
<?
    }
}
?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="product_id" class="chosen-select">
<?
$products = $db->query("SELECT COUNT(*) FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($products[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $products = $db->query("SELECT `id_item`, `name`, `model` FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'");
?>
                                            <option value="">Все</option>
<?
    while ($product = $products->fetch_assoc()) {
?>
                                            <option value="<?=$product['id_item']?>"> <?=$product['id_item'] . ' - ' . protection($product['name'] . ' ' . $product['model'], 'display'); ?></option>
<?
    }
}
?>
                                        </select>
                                    </td>
                                    <td style="max-width: 200px"><input type="text" name="comment"></td>
                                    <td style="max-width: 170px" align="center">с <input id="date_added_start" type="text" name="date_added_start" class="pickerdate"> по <input id="date_added_end" type="text" name="date_added_end" class="pickerdate"></td>
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
