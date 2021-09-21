<?php
include_once 'system/core/begin.php';

if (!checkAccess('suppliers')) redirect('/denied');

$data['title'] = 'Поставщики';
include_once 'system/core/header.php';
?>
<script>
    $(function() {
        // Поиск по таблице
        let form = $('#form-suppliers'),
            table = $('#suppliers__table');

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
            url: "/system/ajax/suppliers.php?module=search&page=" + page,
            data: data,
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#suppliers__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows, true);
                $('#suppliers__table tbody').html(tableRows);

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

        loadSuppliers();

        $('#form-suppliers').on('dblclick', 'tr.table__item', function(e) {
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
                let supplier_id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewSupplier?supplier_id=" + supplier_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Поставщик - <b>' + jsonData.title.supplier_name + '</b>', '/ajax_viewSupplier?supplier_id=' + supplier_id);
                    }    
                });
            }
        });

        $('.status-panel').on('click', '#button-edit', function(e) {
            if (!$(this).hasClass('disabled')) {
                let supplier_id = $('tr.table__active').attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewSupplier?supplier_id=" + supplier_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Поставщик - <b>' + jsonData.title.supplier_name + '</b>', '/ajax_viewSupplier?supplier_id=' + supplier_id);
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
                $('#form-suppliers').trigger('submit');
            }
        });
        $.each($('tr select'), function(e) {
            if ($(this).attr('name') == field) {
                $('.status-panel__search div[data-field=' + field + ']').remove();
                $(this).val('');
                $('#form-suppliers').trigger('submit');
            }
        });
        $('.chosen-select').trigger('chosen:updated');
    }

    function renderRows(data, search = false) {
        let tableRows = '',
            countTd = $('#suppliers__table thead').find('tr td').length;

        if (Array.isArray(data) && data.length) {
            $.each(data, function(i, item){
                tableRows += ('<tr data-id="' + item.id_item + '" class="table__item">' +
                                '<td>' + item.id_item + '</td>' +
                                '<td class="center">' + item.name + '</td>' +
                                '<td class="center">' + item.contact_person + '</td>' +
                                '<td class="center">' + item.phone + '</td>' +
                                '<td class="center">' + item.email + '</td>' +
                                '<td class="center">' + item.skype + '</td>' +
                                '<td>' + item.comment + '</td>' +
                                '<td class="center">' + item.date_added + '</td>' +
                             '</tr>');
                });
        } else {
            tableRows = ('<tr class="no-result">' +
                            '<td colspan="' + countTd + '">' + (search ? 'Ничего не найдено!' : 'Здесь ничего нет.') + '</td>' +
                        '</tr>');   
        }
        return tableRows;
    }

    function loadSuppliers(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "suppliers" };
            history.pushState(stateObj, "suppliers", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();
    
        $.ajax({
            type: "POST",
            url: "/system/ajax/suppliers.php?show=true&page=" + page,
            data: {},
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#suppliers__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows);

                $('#suppliers__table tbody').html(tableRows);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

            },
            complete: function() {
                stopPreloader();
            }
        });
    }
    
    function addSupplier() {
        showModalWindow('Добавление поставщика', '/system/ajax/addSupplier.php');
    }

    function deleteSuppliers() {
        let countSuppliers = $('tr.table__active').length;
        if (countSuppliers < 1) {
            console.log('Ничего не выбрано..');
        } else {
            let name = 'поставщика';
            if (countSuppliers > 1) name = 'поставщиков';
            showModalWindow('Удаление ' + name, '/ajax_deleteSuppliers?count=' + countSuppliers, 'confirm');
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
                                <li id="button-delete" onclick="deleteSuppliers();" style="color: #AE0000"><i class="fa fa-trash-o"></i> Удалить</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" style="color: green" onclick="addSupplier();"><i class="fa fa-plus-square"></i> Добавить</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-suppliers" method="post" autocomplete="off" spellcheck="false">
                        <table id="suppliers__table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="name" style="min-width: 180px">Организация</th>
                                    <th data-name="contact_person" style="min-width: 180px">Контактное лицо</th>
                                    <th data-name="phone" style="min-width: 150px">Телефон</th>
                                    <th data-name="email" style="min-width: 150px">E-mail</th>
                                    <th data-name="skype" style="min-width: 150px">Skype</th>
                                    <th data-name="comment" style="min-width: 180px">Комментарий</th>
                                    <th data-name="date_added" style="min-width: 130px">Добавлено</th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 20px"><input type="text" name="id"></td>
                                    <td style="max-width: 180px"><input type="text" name="name"></td>
                                    <td style="max-width: 180px"><input type="text" name="contact_person"></td>
                                    <td style="max-width: 140px"><input type="text" name="phone"></td>
                                    <td style="max-width: 140px"><input type="text" name="email"></td>
                                    <td style="max-width: 140px"><input type="text" name="skype"></td>
                                    <td style="max-width: 180px"><input type="text" name="comment"></td>
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
