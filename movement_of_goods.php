<?php
include_once 'system/core/begin.php';

if (!checkAccess('movement_of_goods')) redirect('/denied');

$data['title'] = 'Движение товаров';
include_once 'system/core/header.php';
?>
<script>
    $(function() {
        // Поиск по таблице
        let form = $('#form-mog'),
            table = $('#mog-table');

        form.find(".chosen-select").chosen()
            .change(function(e){
                form.trigger('submit');
            });

        form.on('submit', function(e){
            var page = getParameterByName('page');
            var data = $(this).serializeArray();

            if ($('.status-panel__search').length) {
                $('.status-panel__search div').remove();
            }
            $.each(data, function(e){
                if (this.value != '') {
                    let thisName = this.name,
                        thisValue = this.value,
                        date_word = '';
                        
                    if (thisName == 'date_start' || thisName == 'date_end') {
                        thisName = 'date_added';
                        if (this.name == 'date_start') date_word = 'с';
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
            url: "/system/ajax/movement_of_goods.php?module=search&page=" + page,
            data: data,
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#mog-table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows, true);

                $('#mog-table tbody').html(tableRows);

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

        loadMOG();

        $('#date_start, #date_end').datepicker({
            dateFormat: "dd-mm-yy",
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
                $('#form-mog').trigger('submit');
            }
        });
        $('.chosen-select').trigger('chosen:updated');
        
    }

    function renderRows(data, search = false) {
        let tableRows = '',
            countTd = $('#mog-table thead').find('tr td').length;

        if (Array.isArray(data) && data.length) {
            $.each(data, function(i, item){
                let itemProducts = '',
                    itemDateAdded = '',
                    itemDateUpdated = '';
                $.each(item.products, function(k, product){
                    itemProducts += ('<tr>' +
                                        '<td>' + product.id_item + '</td>' +
                                        '<td>' + product.name + (product.attributes != '' ? ' <small style="font-weight: bold; color: green; font-style: italic">' + product.attributes + '</small>' : '') + '</td>' +
                                        '<td>' + product.minus + '</td>' +
                                        '<td>' + product.plus + '</td>' +
                                        '<td>' + (product.attributes != '' ? '<span class="' + (product.balance_with_attributes == 0 ? 'red' : '') + '">' + product.balance_with_attributes + '</span> / ' : '') + '<span style="color: ' + (product.balance > 0 ? 'green' : 'red') + '">' + product.balance + '</span></td>' +
                                    '</tr>');
                });
                
                $.each(item.products_date_added, function(i, value){
                    itemDateAdded += ('<small>[' + i + '] ' + value + '</small><br>');
                });

                $.each(item.products_date_updated, function(i, value){
                    itemDateUpdated += ('<small>[' + i + '] ' + value + '</small><br>');
                });

                tableRows += ('<tr data-id="' + item.id_item + '" class="table__item">' +
                                '<td><i class="fa fa-calendar-check-o"></i> ' + item.date_added + '</td>' +
                                '<td class="center">' + (item.order_id == 0 ? '<b style="color: green">Приход</b>' : (item.order_id == -1 ? '<b style="color: red">Списание</b>' : item.order_id)) +
                                    '<br>' +
                                    '<small style="color: #ababab"><i class="fa fa-user"></i> ' + item.employee + '</small>' +
                                '</td>' +
                                '<td class="center">' +
                                    '<table class="simple__table" width="100%" cellpadding="0" cellspacing="0">' +
                                        '<thead>' +
                                            '<th>id</th>' +
                                            '<th>Название</th>' +
                                            '<th>скл. (-)</th>' +
                                            '<th>скл. (+)</th>' +
                                            '<th>ост. (фикс.)</th>' +
                                        '</thead>' +
                                        '<tbody>' +
                                        itemProducts +
                                        '</tbody>' +
                                    '</table>' +
                                '</td>' +
                                '<td class="center">' + (item.start == 0 ? '<span class="table__item-status">Закупка</span>' : (item.start == -1 ? '<span class="table__item-status" style="background: #8db9db">Склад</span>' : '')) + '</td>' +
                                '<td class="center">' + (item.end == 0 ? '<span class="table__item-status" style="background: #8db9db">Склад</span>' : (item.end == -1 ? '<span class="table__item-status" style="background: #cc0000">Утилизация</span>' : '')) + '</td>' +
                                '<td class="center">' + itemDateUpdated + '</td>' +
                                '<td class="center">' + itemDateAdded + '</td>' +
                             '</tr>');
            });
        } else {
            tableRows = ('<tr class="no-result">' +
                            '<td colspan="' + countTd + '">' + (search ? 'Ничего не найдено!' : 'Здесь ничего нет.') + '</td>' +
                        '</tr>');   
        }
        return tableRows;
    }

    function loadMOG(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "mog" };
            history.pushState(stateObj, "mog", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();
    
        $.ajax({
            type: "POST",
            url: "/system/ajax/movement_of_goods.php?show=true&page=" + page,
            data: {},
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#mog-table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows);

                $('#mog-table tbody').html(tableRows);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

            },
            complete: function() {
                stopPreloader();
            }
        });
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
                                <li class="disabled"><i class="fa fa-edit"></i> Открыть</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" style="color: green" disabled><i class="fa fa-plus-square"></i> Добавить</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-mog" method="post" autocomplete="off">
                        <table id="mog-table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th data-name="date_added" style="min-width: 170px">Дата</th>
                                    <th data-name="order_id" style="min-width: 160px">Заказ</th>
                                    <th data-name="product" style="min-width: 420px">Товар</th>
                                    <th data-name="status_start" style="min-width: 150px">Начало</th>
                                    <th data-name="status_end" style="min-width: 150px">Завершение</th>
                                    <th data-name="warehouse_updated" style="min-width: 170px">Обновлено на складе</th>
                                    <th data-name="warehouse_added" style="min-width: 170px">Добавлено на склад</th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 170px; text-align: center">с <input id="date_start" type="text" name="date_start" class="pickerdate"> по <input id="date_end" type="text" name="date_end" class="pickerdate"></td>
                                    <td style="max-width: 160px; text-align: center"><input type="text" name="order_id"></td>
                                    <td style="max-width: 420px">
                                        <select name="product" class="chosen-select">
<?
$products_count = $db->query("SELECT COUNT(*) FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($products_count[0] == 0) {
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
                                            <option value="<?=$product['id_item']?>"> <?=$product['id_item'] . ' - ' . protection($product['name'] . ' ' . $product['model'], 'display')?></option>
<?
    }
}
?>
                                        </select>
                                    </td>
                                    <td style="max-width: 150px"></td>
                                    <td style="max-width: 150px"></td>
                                    <td style="max-width: 170px; text-align: center"></td>
                                    <td style="max-width: 170px; text-align: center"></td>
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
/*
CREATE TABLE `movement_of_goods` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT,
    `client_id` INT(11) UNSIGNED NOT NULL,
    `order_id` INT(11) UNSIGNED NOT NULL,
    `status_start` INT(11) UNSiGNED NOT NULL,
    `status_end` INT(11) UNSIGNED NOT NULL,
    `date_added` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY(`id`)
);

CREATE TABLE `movement_of_goods-products` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT,
    `client_id` INT(11) UNSIGNED NOT NULL,
    `mog_id` INT(11) UNSIGNED NOT NULL,
    `product_id` INT(11) UNSIGNED NOT NULL,
    `count` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY(`id`)
);

CREATE TABLE `movement_of_goods-attributes` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT,
    `client_id` INT(11) UNSIGNED NOT NULL,
    `mog_product_id` INT(11) UNSIGNED NOT NULL,
    `attribute_id` INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY(`id`)
);
*/