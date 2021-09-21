<?php
include_once 'system/core/begin.php';

if (!checkAccess('sites')) redirect('/access_is_denied');

$data['title'] = 'Сайты';
include_once 'system/core/header.php';
?>
<script>
    $(function() {
        // Поиск по таблице
        let form = $('#form-sites'),
            table = $('#sites__table');

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
                url: "/system/ajax/sites.php?module=search&page=" + page,
                data: data,
                beforeSend: function() {
                    startPreloader();
                },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        tableRows;

                    console.log(jsonData)
                    $('#sites__table tbody').html('');
                    // Items
                    tableRows = renderRows(jsonData.rows, true);

                    $('#sites__table tbody').html(tableRows);;

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


        loadSites();

        $('#form-sites').on('dblclick', 'tr.table__item', function(e) {
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
                let site_id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/viewSite.php?site_id=" + site_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Сайт - <b>' + jsonData.title.site_name + '</b>', '/system/ajax/viewSite.php?site_id=' + site_id);
                    }    
                });
            }
        });

        $('.status-panel').on('click', '#button-edit', function(e) {
            if (!$(this).hasClass('disabled')) {
                let site_id = $('tr.table__active').attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/system/ajax/viewSite.php?site_id=" + site_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Сайт - <b>' + jsonData.title.site_name + '</b>', '/system/ajax/viewSite.php?site_id=' + site_id);
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
                    $('#form-sites').trigger('submit');
                }
            });
            $.each($('tr select'), function(e) {
                if ($(this).attr('name') == field) {
                    $('.status-panel__search div[data-field=' + field + ']').remove();
                    $(this).val('');
                    $('#form-sites').trigger('submit');
                }
            });
            $('.chosen-select').trigger('chosen:updated');
        }


    function renderRows(data, search = false) {
        let tableRows = '',
            countTd = $('#sites__table thead').find('tr td').length;

        if (Array.isArray(data) && data.length) {
            $.each(data, function(i, item){
                tableRows += ('<tr data-id="' + item.id_item + '" class="table__item">' +
                                '<td>' + item.id_item + '</td>' +
                                '<td>' + item.name + '</td>' +
                                '<td class="center"><i class="fa fa-globe"></i> <b>' + item.url + '</b></td>' +
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

    function loadSites(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "sites" };
            history.pushState(stateObj, "sites", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();
    
        $.ajax({
            type: "POST",
            url: "/system/ajax/sites.php?show=true&page=" + page,
            data: {},
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#sites__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows);

                $('#sites__table tbody').html(tableRows);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

            },
            complete: function() {
                stopPreloader();
            }
        });
    }
    
    function addSite() {
        showModalWindow('Добавление сайта', '/system/ajax/addSite.php');
    }

    function deleteSites() {
        let countSites = $('tr.table__active').length;
        if (countSites < 1) {
            console.log('Ничего не выбрано..');
        } else {
            let name = 'сайта';
            if (countSites > 1) name = 'сайтов';
            showModalWindow('Удаление ' + name, '/system/ajax/deleteSites.php?count=' + countSites, 'confirm');
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
                                <li id="button-delete" onclick="deleteSites();" style="color: #AE0000"><i class="fa fa-trash-o"></i> Удалить</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" style="color: green" onclick="addSite();"><i class="fa fa-plus-square"></i> Добавить</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-sites" method="post" autocomplete="off">
                        <table id="sites__table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="name" style="min-width: 120px">Название</th>
                                    <th data-name="url" style="min-width: 100px">Адрес</th>
                                    <th data-name="sort" style="min-width: 30px"><i class="fa fa-sort"></i></th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 20px"><input type="text" name="id"></td>
                                    <td><input type="text" name="name"></td>
                                    <td><input type="text" name="url" style="max-width: 140px"></td>
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
