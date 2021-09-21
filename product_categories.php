<?php
include_once 'system/core/begin.php';

if (!checkAccess('product_categories')) redirect('/denied');

$data['title'] = 'Категории товаров';
include_once 'system/core/header.php';
?>

<script>
    $(function() {

        $('#form-product-categories').on('submit', function(e){
            return false;
        });

        $("#name-search").keyup(function(){
            if ($('#name-search').val() == '') {
                $('.status-panel__search div[data-field="name"]').remove();
            }
            if ($('#name-search').val() != '' && !$('.status-panel__search div').length) {
                $('.status-panel__search').append('<div data-field="name" class="data-field"><span>Название: ' + $('#name-search').val() + '</span> <i class="fa fa-remove" onclick="RemoveSearchField(\'name\');"></i></div>');
            } else {
                $('.status-panel__search').find('div[data-field="name"] span').text('Название: ' + $('#name-search').val());
            }
            let _this = this;
            let count = 0;
            $.each($('#product-categories__table tbody tr').find('td:eq(1)'), function() {
                if ($(this).text().toLowerCase().indexOf($(_this).val().toLowerCase()) === -1) {
                    $(this).parent().hide();
                } else {
                    count = count + 1;
                    $(this).parent().show();                
                }
            });
            if (count == 0 && !$('#product-categories__table tbody').find('tr.no_result').length) {
                if (!$('#product-categories__table tbody tr.no-result').length) $('#product-categories__table tbody').append('<tr class="no-result">' +
                            '<td colspan="4">Здесь ничего нет.</td>' +
                        '</tr>');

                $('#pagination-start, #pagination-now, #pagination-total').text('0');
            }
            if (count > 0) {
                $('#product-categories__table tbody tr.no-result').remove();
                $('#pagination-start').text('1');
                $('#pagination-now, #pagination-total').text(count);
            }

        });

        $('button#button-search').on('click', function(e) {
            $('#form-product-categories').find('#name-search').focus();
        });

        loadCategories();

        $('#form-product-categories').on('dblclick', 'tr.table__item', function(e) {
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
                let category_id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewProductCategory?category_id=" + category_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        var jsonData = JSON.parse(response);
                        
                        if (jsonData.success == 1) {
                            showModalWindow('Категория товаров - <b>' + jsonData.title.product_category_name + '</b>', '/ajax_viewProductCategory?category_id=' + category_id);
                        }
                    }    
                });
            }
        });

        $('.status-panel').on('click', '#button-edit', function(e) {
            if (!$(this).hasClass('disabled')) {
                let category_id = $('tr.table__active').attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewProductCategory?category_id=" + category_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        var jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            showModalWindow('Категория товаров - <b>' + jsonData.title.product_category_name + '</b>', '/ajax_viewProductCategory?category_id=' + category_id);
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
                loadCategories();
            }
        });
    }

    function addProductCategory() {
        showModalWindow('Добавление категории товаров', '/system/ajax/modal.addProductCategory.php');
    }

    function renderRows(data, parent_id, level) {
        let tableRows = '',
            countTd = $('#product-categories__table thead').find('tr td').length;

        if (data && Array.isArray(data[parent_id]) && data[parent_id].length) {
            data[parent_id].reverse();

            $.each(data[parent_id], function(i, item){
                tableRows = ('<tr data-id="' + item.id_item + '" class="table__item' + (item.status == 'off' ? ' disabled' : '') + '">' +
                                '<td>' + item.id_item + '</td>' +
                                '<td style="text-align: left; padding-left: ' + (level == 0 ? '5' : level * 20) + 'px">' + (level == 0 ? '<b style="font-size: 14px">' : '') + (item.count_subs > 0 ? '<i class="fa fa-folder-open" style="color: #3A6DC2"></i>' : '<i class="fa fa-folder"></i>') + ' ' + item.name + (item.count_subs > 0 ? ' (' + item.count_subs + ')' : '') + (level == 0 ? '</b>' : '') + '</td>' +
                                '<td class="center">' +
                                    '<label class="toggle">' +
                                            '<input type="checkbox"' + (item.status == 'on' ? ' checked' : '') + ' onclick="changeStatus(\'product_categories\', \'' + item.status + '\', \'' + item.id_item + '\');" class="toggle__input"> ' +
                                            '<div class="toggle__control"></div>' +
                                        '</label>' +
                                '</td>' +
                                '<td class="center"><i class="fa fa-calendar-check-o"></i> ' + item.date_added + '</td>' +
                             '</tr>');
                level = level + 1;
                renderRows(data, item.id_item, level);
                level = level - 1;

                $('#product-categories__table tbody').prepend(tableRows);
            });
        }
        if (!data) {
            tableRows = ('<tr class="no-result">' +
                            '<td colspan="' + countTd + '">Здесь ничего нет.</td>' +
                        '</tr>');
            $('#product-categories__table tbody').prepend(tableRows);
        }
    }

    function loadCategories(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "product_categories" };
            history.pushState(stateObj, "product_categories", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();
        
        $.ajax({
            type: "POST",
            url: "/system/ajax/product_categories.php?show=true&page=" + page,
            data: {},
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#product-categories__table tbody').html('');
                // Items
                renderRows(jsonData.rows, 0, 0);

                // Навигация
                pagination = renderPagination(jsonData.pagination, true);
                $('.pagination__info').html(pagination);

                let name_search = $('#name-search');
                if (name_search.val() != '') name_search.trigger('keyup');
                
                $.each($('tr.disabled'), function(e){
                    let data_id = $(this).attr('data-id');
                    $.ajax({
                        type: "POST",
                        url: "/system/ajax/product_categories.php?disable_category=true",
                        data: { 'id_item' : data_id },
                        success: function(response) {    
                            let jsonData = JSON.parse(response);
                            if (jsonData.success == 1) {
                                if (jsonData.disable == 1) {
                                    $('tr.disabled[data-id=' + data_id + ']').find('input[type=checkbox]').parent().hide();
                                }
                            }
                        }    
                    });
                });

            },
            complete: function() {
                stopPreloader();
            }
        });
    }

    function deleteCategories() {
        let countCategories = $('tr.table__active').length;
        if (countCategories < 1) {
            console.log('Ничего не выбрано..');
        } else {
            let name = 'категории товаров';
            if (countCategories > 1) name = 'категорий товаров';
            showModalWindow('Удаление ' + name, '/ajax_deleteCategory?count=' + countCategories, 'confirm');
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
                                <li id="button-copy"><i class="fa fa-files-o"></i> Копировать</li>
                                <li id="button-edit" style="color: #8A5A00"><i class="fa fa-edit"></i> Редактировать</li>
                                <li class="hr"></li>
                                <li id="button-delete" style="color: #AE0000" onclick="deleteCategories();"><i class="fa fa-trash-o"></i> Удалить</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" onclick="addProductCategory();" style="color: green"><i class="fa fa-plus-square"></i> Добавить</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-product-categories" method="post" autocomplete="off">
                        <table id="product-categories__table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="name" style="min-width: 130px">Название</th>
                                    <th data-name="status" style="min-width: 95px">Статус</th>
                                    <th data-name="date_added" style="min-width: 170px">Добавлена</th>
                                </tr>
                                <tr class="table-row-search">
                                    <td></td>
                                    <td><input id="name-search" type="text" name="name"></td>
                                    <td></td>
                                    <td></td>
                                </td>
                            </thead> 
                            <tbody>
                            </tbody>
                        </table>
                        <!-- <input type="submit" style="display: none"> -->
                    </form>
                </div>
<?
$hide_rows_list = true;
include_once 'system/core/footer.php';
