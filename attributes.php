<?php
include_once 'system/core/begin.php';

if (!checkAccess('attributes')) redirect('/denied');

$data['title'] = 'Атрибуты товаров';
include_once 'system/core/header.php';
?>
<script>
    $(function() {
        // Поиск по таблице
        let form = $('#form-attributes'),
            table = $('#attributes__table');

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
                url: "/system/ajax/attributes.php?module=search&page=" + page,
                data: data,
                beforeSend: function() {
                    startPreloader();
                },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        tableRows;
                    $('#attributes__table tbody').html('');
                    // Items
                    tableRows = renderRows(jsonData.rows, true);
                    $('#attributes__table tbody').html(tableRows);

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

        loadAttributes();

        $('#form-attributes').on('dblclick', 'tr.table__item', function(e) {
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
                let attribute_id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/viewAttribute.php?attribute_id=" + attribute_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Атрибут товара - <b>' + jsonData.title.attribute_name + '</b>', '/system/ajax/viewAttribute.php?attribute_id=' + attribute_id);
                    }    
                });
            }
        });

        $('.status-panel').on('click', '#button-edit', function(e) {
            if (!$(this).hasClass('disabled')) {
                let attribute_id = $('tr.table__active').attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/viewAttribute.php?attribute_id=" + attribute_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Атрибут товаров - <b>' + jsonData.title.attribute_name + '</b>', '/system/ajax/viewAttribute.php?attribute_id=' + attribute_id);
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
                $('#form-attributes').trigger('submit');
            }
        });
        $.each($('tr select'), function(e) {
            if ($(this).attr('name') == field) {
                $('.status-panel__search div[data-field=' + field + ']').remove();
                $(this).val('');
                $('#form-attributes').trigger('submit');
            }
        });
        $('.chosen-select').trigger('chosen:updated');
    }

    function renderRows(data, search = false) {
        let tableRows = '',
            countTd = $('#attributes__table thead').find('tr td').length;

        if (Array.isArray(data) && data.length) {
            $.each(data, function(i, item){
                tableRows += ('<tr data-id="' + item.id_item + '" class="table__item' + (item.status == 'off' ? ' disabled' : '') + '">' +
                                '<td>' + item.id_item + '</td>' +
                                '<td class="center"><b>' + item.name + '</b></td>' +
                                '<td class="center">' + item.category + '</td>' +
                                '<td class="center">' +
                                    '<label class="toggle">' +
                                        '<input type="checkbox"' + (item.status == 'on' ? ' checked' : '') + ' onclick="changeStatus(\'attributes\', \'' + item.status + '\', \'' + item.id_item + '\');" class="toggle__input"> ' +
                                        '<div class="toggle__control"></div>' +
                                    '</label>' +
                                '</td>' +
                                '<td class="center">' + item.sort + '</td>' +
                             '</tr>');
                });
        } else {
            tableRows = ('<tr class="no-result">' +
                            '<td colspan="' + countTd + '">' + (search ? 'Ничего не найдено!' : 'Здесь ничего нет.') + '</td>' +
                        '</tr>');   
        }
        return tableRows;
    }

    function loadAttributes(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "attributes" };
            history.pushState(stateObj, "attributes", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();
    
        $.ajax({
            type: "POST",
            url: "/system/ajax/attributes.php?show=true&page=" + page,
            data: {},
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#attributes__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows);
                $('#attributes__table tbody').html(tableRows);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

            },
            complete: function() {
                stopPreloader();
            }
        });
    }
    
    function addAttribute() {
        showModalWindow('Добавление атрибута товаров', '/system/ajax/addAttribute.php');
    }

    function deleteAttributes() {
        let countAttributes = $('tr.table__active').length;
        if (countAttributes < 1) {
            console.log('Ничего не выбрано..');
        } else {
            let name = 'атрибута товаров';
            if (countAttributes > 1) name = 'атрибутов товаров';
            showModalWindow('Удаление ' + name, '/system/ajax/deleteAttributes.php?count=' + countAttributes, 'confirm');
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
                                <li id="button-edit" style="color: #8A5A00"><i class="fa fa-edit"></i> Редактировать</li>
                                <li class="hr"></li>
                                <li id="button-delete" onclick="deleteAttributes();" style="color: #AE0000"><i class="fa fa-trash-o"></i> Удалить</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" style="color: green" onclick="addAttribute();"><i class="fa fa-plus-square"></i> Добавить</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-attributes" method="post" autocomplete="off">
                        <table id="attributes__table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="name" style="min-width: 120px">Название</th>
                                    <th data-name="category_id" style="min-width: 160px">Категория</th>
                                    <th data-name="status" style="min-width: 100px">Статус</th>
                                    <th data-name="sort" style="min-width: 30px"><i class="fa fa-sort"></i></th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 20px"><input type="text" name="id"></td>
                                    <td><input type="text" name="name"></td>
                                    <td>
                                    <select name="category_id" class="chosen-select">
                                            <option value="">Все</option>
<?
$items = $db->query("SELECT `id_item`, `name` FROM `attribute_categories` WHERE `client_id` = '" . $chief['id'] . "'");
while ($category = $items->fetch_assoc()) {
?>
                                            <option value="<?=$category['id_item']?>"><?=protection($category['name'], 'display')?></option>
<?

}
?>
                                        </select>
                                    </td>
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
