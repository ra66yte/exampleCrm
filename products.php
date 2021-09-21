<?php
include_once 'system/core/begin.php';

if (!checkAccess('products')) redirect('/denied');

$data['title'] = 'Товары';
include_once 'system/core/header.php';

function build_tree_select($categories, $parent_id, $level) {
    global $db, $chief;
    if (is_array($categories) and isset($categories[$parent_id])) { //Если категория с таким parent_id существует
        foreach ($categories[$parent_id] as $category) { // Обходим
            $count_subs = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `parent_id` = '" . $category['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            /**
             * Выводим категорию 
             *  $level * 20 - отступ, $level - хранит текущий уровень вложености (0, 1, 2..)
             */

?>
            <option value="<?=$category['id_item']?>" style="text-align: left; padding-left: <?=($level == 0 ? '5' : $level * 20)?>px">
                <?php echo protection($category['name'], 'display'); if ($count_subs[0] <> 0) echo ' (' . $count_subs[0] . ') ▼'; ?>
            </option>
<? 

            $level = $level + 1; // Увеличиваем уровень вложености
            // Рекурсивно вызываем эту же функцию, но с новым $parent_id и $level
            build_tree_select($categories, $category['id_item'], $level);
            $level = $level - 1; // Уменьшаем уровень вложености
        }
    }
}

?>

<script>
    $(function(){
        // Поиск по таблице
        let form = $('#form-products'),
            table = $('#products__table');

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
            url: "/system/ajax/products.php?module=search&page=" + page,
            data: data,
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#products__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows, true);
                $('#products__table tbody').html(tableRows);

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

        loadProducts();

        $('#form-products').on('dblclick', 'tr.table__item', function(e) {
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
                let product_id = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewProduct?product_id=" + product_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Товар - <b>' + jsonData.title.product_name + ' ' + jsonData.title.product_model + '</b>', '/ajax_viewProduct?product_id=' + product_id);
                    }    
                });
            }
        });

        $('.status-panel').on('click', '#button-edit', function(e) {
            if (!$(this).hasClass('disabled')) {
                let product_id = $('tr.table__active').attr('data-id');
                $.ajax({
                    type: "POST",
                    url: "/ajax_viewProduct?product_id=" + product_id + "&query=get_title",
                    data: {},
                    success: function(response) {    
                        let jsonData = JSON.parse(response);
                        showModalWindow('Товар - <b>' + jsonData.title.product_name + ' ' + jsonData.title.product_model + '</b>', '/ajax_viewProduct?product_id=' + product_id);
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
                $('#form-products').trigger('submit');
            }
        });
        $.each($('tr select'), function(e) {
            if ($(this).attr('name') == field) {
                $('.status-panel__search div[data-field=' + field + ']').remove();
                $(this).val('');
                $('#form-products').trigger('submit');
            }
        });
        $('.chosen-select').trigger('chosen:updated');
    }

    function renderRows(data, search = false) {
        let tableRows = '',
            countTd = $('#products__table thead').find('tr td').length;

        if (Array.isArray(data) && data.length) {
            $.each(data, function(i, item){
                tableRows += ('<tr data-id="' + item.id_item + '" class="table__item' + (item.count <= 0 ? ' table__item-absence' : '') + (item.status == 'off' ? ' disabled' : '') + '">' +
                                '<td>' + item.id_item + '</td>' +
                                '<td><img src="/system/images/product/' + (item.image == 'no_photo.png' ? '' : item.client_id + '/') + item.image + '" width="50" alt="product"></td>' +
                                '<td>' + item.name + '</td>' +
                                '<td class="center">' + item.model + '</td>' +
                                '<td align="center">' +
                                    '<label class="toggle">' +
                                        '<input type="checkbox"' + (item.status == 'on' ? ' checked' : '') + ' onclick="changeStatus(\'products\', \'' + item.status + '\', \'' + item.id_item + '\');" class="toggle__input">' +
                                        '<div class="toggle__control"></div>' +
                                    '</label>' +
                                '</td>' +
                                '<td class="center">' + item.vendor + '</td>' +
                                '<td class="center">' + item.manufacturer + '</td>' +
                                '<td class="center">' + item.category + '</td>' +
                                '<td class="center">' + item.date_added + '</td>' +
                                '<td class="center"><b class="' + (item.count > 0 ? 'green' : 'red') + '">' + item.count + '</b></td>' +
                                '<td class="center">' + item.in_orders + '</td>' +
                                '<td class="center">' + item.purchase_price + '</td>' +
                                '<td class="center">' + item.base_price + '</td>' +
                                '<td class="center" title="' + item.currency_name + '">' + item.currency_symbol + '</td>' +
                                '<td class="center">' + item.total_amount + '</td>' +
                             '</tr>');
                });
        } else {
            tableRows = ('<tr class="no-result">' +
                            '<td colspan="' + countTd + '">' + (search ? 'Ничего не найдено!' : 'Здесь ничего нет.') + '</td>' +
                        '</tr>');   
        }
        return tableRows;
    }

    function loadProducts(page) {
        if (!page) {
            page = getParameterByName('page');
        } else {
            let stateObj = { foo: "products" };
            history.pushState(stateObj, "products", '?page=' + page);
        }
        $('.status-panel__count').text('').hide();
    
        $.ajax({
            type: "POST",
            url: "/system/ajax/products.php?show=true&page=" + page,
            data: {},
            beforeSend: function() {
                startPreloader();
            },
            success: function(response) {
                let jsonData = JSON.parse(response),
                    tableRows;

                $('#products__table tbody').html('');
                // Items
                tableRows = renderRows(jsonData.rows);
                $('#products__table tbody').html(tableRows);

                // Навигация
                pagination = renderPagination(jsonData.pagination);
                $('.pagination__info').html(pagination);

            },
            complete: function() {
                stopPreloader();
            }
        });
    }

    function addProduct() {
        showModalWindow('Добавление нового товара', '/system/ajax/addProduct.php');
    }

    function deleteProducts() {
        let countProducts = $('tr.table__active').length;
        if (countProducts < 1) {
            console.log('Ничего не выбрано..');
        } else {
            let name = 'товара';
            if (countProducts > 1) name = 'товаров';
            showModalWindow('Удаление ' + name, '/ajax_deleteProduct?count=' + countProducts, 'confirm');
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
                                <li id="button-delete" style="color: #AE0000" onclick="deleteProducts();"><i class="fa fa-trash-o"></i> Удалить</li>
                            </ul>
                        </div>
                    </div>
                    <button id="button-add" onclick="addProduct();" style="color: green"><i class="fa fa-plus-square"></i> Добавить</button>
                    <button id="button-select-all" title="Выбрать все"><i class="fa fa-file-text"></i></button>
                </div>
                <!-- put div here without scroll -->         
                <div class="content__overflow">
                    <form id="form-products" method="post" spellcheck="false" autocomplete="off">
                        <table id="products__table" class="table" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th data-name="id" style="min-width: 30px">id</th>
                                    <th data-name="image">Фото</th>
                                    <th data-name="name" style="min-width: 150px">Название</th>
                                    <th data-name="model" style="min-width: 90px">Модель</th>
                                    <th data-name="status" style="min-width: 95px">Статус</th>
                                    <th data-name="vendor_code" style="min-width: 90px">Артикул</th>
                                    <th data-name="manufacturer" style="min-width: 120px">Производитель</th>
                                    <th data-name="category" style="min-width: 180px">Категория</th>
                                    <th data-name="date_added" style="min-width: 100px">Добавлен</th>
                                    <th data-name="count" style="min-width: 80px">Кол-во</th>
                                    <th data-name="in_orders" style="min-width: 100px">В заказах</th>
                                    <th data-name="base_price" style="min-width: 110px">Цена (база)</th>
                                    <th data-name="purchase_price" style="min-width: 110px">Цена (закуп)</th>
                                    <th data-name="currency" style="min-width: 100px">Валюта</th>
                                    <th data-name="purchase_amount">Сумма (закуп)</th>
                                </tr>
                                <tr class="table-row-search">
                                    <td style="max-width: 20px"><input type="text" name="id"></td>
                                    <td></td>
                                    <td style="max-width: 150px"><input type="text" name="name"></td>
                                    <td style="max-width: 90px"><input type="text" name="model"></td>
                                    <td style="max-width: 100px">
                                        <select name="status" class="chosen-select">
                                            <option value="">Все</option>
                                            <option value="on">On</option>
                                            <option value="off">Off</option>
                                        </select>
                                    </td>
                                    <td style="max-width: 90px"><input type="text" name="vendor_code"></td>
                                    <td style="text-align: center; min-width: 160px">
                                        <select name="manufacturer" class="chosen-select">
<?php
$count_manufacturers = $db->query("SELECT COUNT(*) FROM `manufacturers` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($count_manufacturers[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $items = $db->query("SELECT `id_item`, `name` FROM `manufacturers` WHERE `client_id` = '" . $chief['id'] . "' ORDER BY `id`");
?>
                                            <option data-num="first" value="">Все</option>
<?
    while ($manufacturer = $items->fetch_assoc()) {
?>
                                            <option value="<?=$manufacturer['id_item']?>"><?=protection($manufacturer['name'], 'display')?></option>
<?
        echo "\r\n";
    }
}
?>
                                        </select>
                                    </td>
                                    <td style="max-width: 180px">
                                        <select name="category" class="chosen-select">
<?php
$count_categories = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($count_categories[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $items = $db->query("SELECT `id_item`, `parent_id`, `name` FROM `product_categories` WHERE `status` = 'on' AND `client_id` = '" . $user['id'] . "' ORDER BY `id`");
    $categories = array();
    while ($category = $items->fetch_assoc()) {
        $categories[$category['parent_id']][] = $category;
    }
?>
                                            <option data-num="first" value="">Все</option>
<?
    echo build_tree_select($categories, 0, 0);
}
?>
                                        </select>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td style="max-width: 110px"><input type="text" name="base_price"></td>
                                    <td style="max-width: 110px"><input type="text" name="purchase_price"></td>
                                    <td style="max-width: 100px">
                                        <select name="currency" class="chosen-select">
<?php
$count_currencies = $db->query("SELECT COUNT(*) FROM `currencies` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($count_currencies[0] == 0) {
?>
                                            <option value="">- Не указано -</option>
<?
} else {
    $items = $db->query("SELECT `id_item`, `symbol` FROM `currencies` WHERE `client_id` = '" . $user['id'] . "' ORDER BY `id`");
?>
                                            <option data-num="first" value="">Все</option>
<?
    while ($currency = $items->fetch_assoc()) {
?>
                                            <option value="<?=$currency['id_item']?>"><?=protection($currency['symbol'], 'display')?></option>
<?
        echo "\r\n";
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
