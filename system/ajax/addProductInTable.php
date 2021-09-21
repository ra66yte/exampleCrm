<?php
include_once '../core/begin.php';
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
            echo "\r\n";
            $level = $level + 1; // Увеличиваем уровень вложености
            // Рекурсивно вызываем эту же функцию, но с новым $parent_id и $level
            build_tree_select($categories, $category['id_item'], $level);
            $level = $level - 1; // Уменьшаем уровень вложености
        }
    }
}

if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = $product_item = null;

    $category =             isset($_POST['category']) ? abs(intval($_POST['category'])) : null;
    $product =              isset($_POST['product']) ? abs(intval($_POST['product'])) : null;
    $income =               isset($_POST['income']) ? abs(intval($_POST['income'])) : null;
    $price_base =           isset($_POST['base_price']) ? abs(floatval($_POST['base_price'])) : null;
    $price_dollar =         isset($_POST['price_dollar']) ? abs(floatval($_POST['price_dollar'])) : null;
    $price_dollar_course =  isset($_POST['price_dollar_course']) ? abs(floatval($_POST['price_dollar_course'])) : null;
    $attribute_categories = isset($_POST['attribute_categories']) ? $_POST['attribute_categories'] : null;
    $countItems =           isset($_POST['count']) ? $_POST['count'] : null;

    if (!empty($price_dollar_course)) {
        if (!is_numeric($price_dollar_course)) {
            $error = 'Указан некорректный курс доллара!';
        }
    }

    if (!empty($price_dollar)) {
        if (!is_numeric($price_dollar)) {
            $error = 'Указана некорректная цена в долларах!';
        }
    }
    
    if (empty($price_base)) {
        $error = 'Укажите цену закупки!';
    } elseif (!is_numeric($price_base)) {
        $error = 'Указана некорректная цена закупки!';
    }

    if ($income == 0) {
        $error = 'Укажите количество товара!';
    }

    if ($product == 0) {
        $error = 'Выберите товар!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `id_item` = '" . $product . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Выбран неизвестный товар!';
    }

    if ($category != 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `id_item` = '" . $category . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Выбрана неизвестная категория!';
        }
    }
    
    if (is_array($attribute_categories) and count($attribute_categories) > 0) {
        $step['categories'] = 0;
        $step['count_products'] = 0;
        foreach ($attribute_categories as $i => $data) {
            if (empty($countItems[$i])) {
                $error = 'Укажите количество товара!';
            } elseif (!is_numeric($countItems[$i]) or $countItems[$i] < 1) {
                $error = 'Количество товара указано неправильно!';
            } else {
                $step['count_products'] += $countItems[$i];
            }

            $step['attributes'] = 0;
            foreach ($data as $category => $attribute) {
                if (!is_numeric($category)) {
                    $error = 'Некорректное значение категории Sub-ID!';
                } elseif ($result = $db->query("SELECT COUNT(*) FROM `attribute_categories` WHERE `id_item` = '" . abs(intval($category)) . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                    $error = 'Произошла ошибка при выборе Sub-ID для товара!';
                } else {
                    if ($attribute != '') {
                        if (!is_numeric($attribute)) {
                            $error = 'Некорректное значение Sub-ID!';
                        } else if ($result = $db->query("SELECT COUNT(*) FROM `attributes` WHERE `category_id` = '" . $category . "' AND `id_item` = '" . abs(intval($attribute)) . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                            $error = 'Произошла ошибка при выборе Sub-ID товара!';
                        }
                    } else {
                        $error = 'Укажите значения Sub-ID!';
                    }
                }
                $step['attributes']++;
            }
            $step['categories']++;
        }
        if ($step['count_products'] > 0 and $income <> $step['count_products']) {
            $error = 'Общее количество товара не соответствует количеству товара с Sub-ID!';
        }
    }

    if (!isset($error)) {
        $success = 1;
        $product = $db->query("SELECT `id_item`, `name`, `model` FROM `products` WHERE `id_item` = '" . $product . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();

        if (is_array($attribute_categories) and count($attribute_categories) > 0) {
            $product_item = "";
            $product_item_count = 0;
            foreach ($attribute_categories as $i => $data) {

                $count = abs(intval($countItems[$i])); // Количество товара определенного атрибута
                $step['i'] = 1;
                foreach ($data as $category_id => $attribute_id) {
                    $category = $db->query("SELECT `name` FROM `attribute_categories` WHERE `id_item` = '" . protection($category_id, 'int') . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                    $attribute = $db->query("SELECT `name` FROM `attributes` WHERE `id_item` = '" . protection($attribute_id, 'int') . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                    $product_item .= "
                        <tr data-id=\"" . $product['id_item'] . "\"" . (($step['i'] == 1) ? " data-role=\"parent\" data-count=\"" . $step['attributes'] . "\"" : "data-role=\"child\"") . ">
                            " . (($step['i'] == 1) ? "<td rowspan=\"" . $step['attributes'] . "\" data-name=\"id\">" . $product['id_item'] . "</td>" : '') . "
                            <td data-name=\"attribute_id\" data-value=\"" . protection($attribute_id, 'int') . "\" title=\"" . protection($category['name'], 'display') . "\">" . $category_id . "</td>
                            <td" . (($step['i'] > 1) ? " style=\"border-right: 1px solid #eee\"" : '') . ">" . protection($attribute['name'], 'display') . "</td>
                            " . (($step['i'] == 1) ? "<td rowspan=\"" . $step['attributes'] . "\" title=\"" . protection($product['name'] . ' ' . $product['model'], 'display') . "\"><a id=\"view_product\" href=\"javascript:void(0);\" onclick=\"viewProductInfo(" . $product['id_item'] . ");\">" . protection($product['name'] . ' ' . $product['model'], 'display') . "</a></td>" : '') . "
                            " . (($step['i'] == 1) ? "<td rowspan=\"" . $step['attributes'] . "\" data-name=\"price\">" . number_format($price_base, 2, '.', '') . "</td>" : '') . "
                            " . (($step['i'] == 1) ? "<td rowspan=\"" . $step['attributes'] . "\" data-name=\"count\">" . $count . "</td>" : '') . "
                            " . (($step['i'] == 1) ? "<td rowspan=\"" . $step['attributes'] . "\" data-name=\"amount\">" . number_format(($count * $price_base), 2, '.', ''). "</td>" : '') . "
                            " . (($step['i'] == 1) ? "<td rowspan=\"" . $step['attributes'] . "\" data-name=\"remove\"><i class=\"fa fa-remove\" style=\"color: #900; cursor: pointer\" onclick=\"removeProductItem(this)\" title=\"Убрать\"></i></td>" : '') . "
                            
                        </tr>
                    ";
                    $step['i']++;
                }

            }

        } else {
            $product_item = "
                                <tr data-id=\"" . $product['id_item'] . "\" data-role=\"parent\" data-count=\"1\">
                                    <td data-name=\"id\">" . $product['id_item'] . "</td>
                                    <td></td>
                                    <td></td>
                                    <td title=\"" . protection($product['name'] . ' ' . $product['model'], 'display') . "\"><a id=\"view_product\" href=\"javascript:void(0);\" onclick=\"viewProductInfo(" . $product['id_item'] . ");\">" . protection($product['name'] . ' ' . $product['model'], 'display') . "</a></td>
                                    <td data-name=\"price\">" . number_format($price_base, 2, '.', '') . "</td>
                                    <td data-name=\"count\">" . $income . "</td>
                                    <td data-name=\"amount\">" . number_format(($income * $price_base), 2, '.', ''). "</td>
                                    <td data-name=\"remove\"><i class=\"fa fa-remove\" style=\"color: #900; cursor: pointer\" onclick=\"removeProductItem(this)\" title=\"Убрать\"></i></td>
                                </tr>
            ";
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error, 'productItem' => $product_item));
    exit;
}

// Получаем товары
if (isset($_GET['get'])) {
    if ($_GET['get'] == 'products') {
        $category = abs(intval($_POST['category_id']));
        if ($category == 0) {
            $count = $db->query("SELECT COUNT(*) FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            if ($count[0] == 0) {         
?>
                        <option value="">- Нет товаров -</option>
<?
            } else {
                $products = $db->query("SELECT `id_item`, `name`, `model`, `status` FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'");
?>
                        <option value="">- Не указано -</option>
<?
                while ($product = $products->fetch_assoc()) {
?>
                        <option value="<?=$product['id_item']?>"<?=$product['status'] == 'off' ? ' disabled' : ''?>><?=protection($product['id_item'] . ' - ' . $product['name'] . ' ' . $product['model'], 'display')?></option>
<?
                    echo "\r\n";
                }
            }

        } else {
            if ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `category` = '" . $category . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
                $products = $db->query("SELECT `id_item`, `name`, `model`, `status` FROM `products` WHERE `category` = '" . $category . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'");
?>
                        <option value="">- Не указано -</option>
<?
                while ($product = $products->fetch_assoc()) {
?>
                        <option value="<?=$product['id_item']?>"<?=$product['status'] == 'off' ? ' disabled' : ''?>><?=protection($product['id_item'] . ' - ' . $product['name'] . ' ' . $product['model'], 'display')?></option>
<?
                    echo "\r\n";
                }
            } else {
?>
                        <option value="">- Нет товаров -</option>
<?
            }
        }

    } elseif ($_GET['get'] == 'product_info') {
        $success = $error = $info = $subs = null;
        $product = abs(intval($_POST['product_id']));
        if ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `id_item` = '" . $product . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $product = $db->query("SELECT `id_item`, `count`, `purchase_price`, `base_price` FROM `products` WHERE `id_item` = '" . $product . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $subsCount = $db->query("SELECT `attribute_category_id` FROM `products_sub-id` WHERE `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            if ($subsCount[0] > 0) $subs = 1;
            if (isset($_GET['subs'])) {
                if ($subsCount[0] > 0) {
                    $subs = array();
                    $sub_categories = $db->query("SELECT `id_item`, `name` FROM `attribute_categories` WHERE `id_item` IN (SELECT `attribute_category_id` FROM `products_sub-id` WHERE `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "') AND `client_id` = '" . $chief['id'] . "'");
                    while ($sub_category = $sub_categories->fetch_assoc()) {
                        $attributes = $db->query("SELECT `id_item`, `name` FROM `attributes` WHERE `category_id` = '" . $sub_category['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
                        $attrs = array();
                        while ($attribute = $attributes->fetch_assoc()) {
                            $attrs[$attribute['id_item']] = $attribute['name'];
                        }
                        $subs[] = array($sub_category['id_item'] => $sub_category['name'], 'attributes' => $attrs);
                    }
                } else {
                    $error = 'У товара нет Sub-ID!';
                }
                echo json_encode(array('success' => $success, 'error' => $error, 'subs' => $subs));
                exit;
            }

            $info = array(
                'id' => $product['id_item'],
                'count' => $product['count'],
                'purchase_price' => $product['purchase_price'],
                'base_price' => $product['base_price'],
                'subs' => $subs
            );
        } else {
            $error = 'Неизвестный товар!';
        }

        echo json_encode(array('success' => $success, 'error' => $error, 'info' => $info));
    }
    exit;
}

if (isset($_GET['table']) and !empty($_GET['table'])) {
    $table = protection($_GET['table'], 'display');
?>
    <script>
        $(function(){
            let form = $('#add-product-in-table__<?=$table?>'),
                btn = form.find('#button-add-product-in-table__<?=$table?>');

            $('#product-category').on('change', function(e){
                $('#product-value').val('');

                let category = $(this).val().trim(),
                    product = $('#product-value').val().trim(),
                    data = { 'category_id': category }
                    
                $('#product-value').prop('disabled', true).find('option:eq(0)').text('Загрузка...');
                $('#product-value').chosen('destroy').chosen(); // Иначе ошибка с chosenImage :(

                $('#product-value').load('/system/ajax/addProductInTable.php?get=products', data, function(response) {
                    $('#product-value').prop('disabled', false);
                    $('#product-value').chosen('destroy').chosen();
                });

                if (category == '' || product == '') {
                    $('#product-id, #product-count, #product-price').text('-');
                    $('#product-income, #product-base-price').val('');
                    $('#subs-panel div').remove();
                    $('#subs-panel b').show();
                    $('#add-sub-id').hide();
                }
            });

            $('#product-value').on('change', function(e){
                let product = $(this).val().trim();
                
                if (product != '') {
                    let data = { 'product_id': product }
                    $.ajax({
                        type: "POST",
                        url: "/system/ajax/addProductInTable.php?get=product_info",
                        data: data,
                        success: function(response) {
                            let jsonData = JSON.parse(response);
                            if (jsonData.success == 1) {
                                $('#product-id').text(jsonData.info.id);
                                $('#product-count').html('<b>' + jsonData.info.count + '</b> шт.');
                                $('#product-price').text(jsonData.info.purchase_price + ' грн.');
                                $('#product-income').focus();
                                $('#product-base-price').val(jsonData.info.base_price);
                                $('#product-price-dollar').val('');

                                $('#subs-panel div').remove();
                                $('#subs-panel b').show();
                                if (jsonData.info.subs) {
                                    $('#add-sub-id').show();
                                } else {
                                    $('#add-sub-id').hide();
                                }
                                
                                checkFields();
                            }
                        }
                    });
                } else {
                    $('#product-id, #product-count, #product-price').text('-');
                    $('#product-income, #product-base-price').val('');
                    $('#subs-panel div').remove();
                    $('#subs-panel b').show();
                    $('#add-sub-id').hide();
                }
            });

            $('#subs-panel').on('keyup', '.count-subs', function(e){
                let countItems = $('#subs-panel').find('.count-subs'),
                    countProducts = 0;
                $.each(countItems, function() {
                    if (!isNaN($(this).val())) {
                        countProducts += Math.abs($(this).val());
                    }
                });
                $('#product-income').val(countProducts);
                checkFields();
            });

            // Считаем по курсу
            $('#add-product-in-table__<?=$table?> input').on('keyup', function(e){
                let count = $('#product-income').val().trim(),
                    count_dollar = $('#product-price-dollar').val().trim(),
                    dollar_course =  $('#product-price-dollar-course').val().trim(),
                    count_money = dollar_course * count_dollar;
                if (count_money != 0 && !isNaN(count_money)) $('#product-base-price').val(count_money.toFixed(2));
            });

            // Добавляем атрибуты
            $('#add-sub-id-link').on('click', function(e) {
                if ($('#product-value').val() != '') {
                    $.ajax({
                        type: "POST",
                        url: "/system/ajax/addProductInTable.php?get=product_info&subs",
                        data: { 'product_id': $('#product-value').val() },
                        success: function(response) {
                            let jsonData = JSON.parse(response);
                            if (jsonData.success == 1) {
                                btn.addClass('disabled');
                                $('#subs-panel b').hide();
                                let last_subs_div = $('.modal-window-wrapper').last().find('.modal-window-content__subs').length + 1,
                                    subs = jsonData.subs,
                                    subItems = '';
                                $.each(subs, function(key, value) {
                                    let i = 0,
                                        attrCategory,
                                        attrCategoryName;
                                    $.each(value, function(key, value) {
                                        let attrs = '';
                                        
                                        if (i == 0) {
                                            attrCategoryId = key;
                                            attrCategoryName = value;
                                        }

                                        if (key === 'attributes') {
                                            attrs += '<select name="attribute_categories[' + last_subs_div + '][' + attrCategoryId + ']" class="item chosen-select">'+
                                                '<option value="">- Не указано -</option>';
                                            $.each(value, function(key, value) {
                                                // Атрибуты
                                                attrs += '<option value="' + key + '">' + value + '</option>';
                                            });
                                            attrs += '</select>';
                                        }
                                        // Категории
                                        if (typeof attrCategoryName !== 'undefined' && i == 1) subItems += '<div class="modal-window-content__value"><span>' + attrCategoryName + '</span> <i class="fa fa-angle-right"></i> ' + attrs + '</div>';
                                        i++;
                                    });
                                });
                                let subItem = '<div data-id="' + last_subs_div + '" class="modal-window-content__subs">' +
                                                '<span id="clear-subs" class="modal-window-content__subs-clear" onclick="clearSubs(this);" title="Убрать атрибуты">x</span>' +
                                                subItems + '<div class="modal-window-content__value">' +
                                                    '<span>Количество</span> <i class="fa fa-cubes"></i> <div class="modal-window-content__value-block">' +
                                                        '<input class="count-subs item small" style="width: 60px; text-align: center; margin: 0" type="text" name="count[' + last_subs_div + ']" value="" placeholder="0"> шт.' +
                                                    '</div>' +
                                                '</div>' +
                                            '</div>';
                                $('#subs-panel').append(subItem);
                            } else {
                                showModalWindow(null, null, 'error', jsonData.error);
                            }
                        },
                        complete: function() {
                            $('.chosen-select').chosen();
                        }
                    });
                } else {
                    showModalWindow(null, null, 'error', 'Выберите товар!'); 
                }
            });

            function checkFields() {
                let error;
                
                let product_price_dollar_course = $('#product-price-dollar-course').val().trim();
                if (product_price_dollar_course != '') {
                    if (isNaN(product_price_dollar_course)) {
                        error = 'Курс доллара указан неправильно!';
                    }
                }
                
                let product_price_dollar = $('#product-price-dollar').val().trim();
                if (product_price_dollar != '') {
                    if (isNaN(product_price_dollar)) {
                        error = 'Цена закупки в долларах указана неправильно!';
                    }
                }
                
                let product_base_price = $('#product-base-price').val().trim();
                if (product_base_price == '') {
                    error = 'Укажите цену закупки!';
                } else if (isNaN(product_base_price)) {
                    error = 'Цена закупки указана неправильно!';
                }
                
                let product_income = $('#product-income').val().trim();
                if (product_income == '') {
                    error = 'Укажите количество товара!';
                } else if (isNaN(product_income)) {
                    error = 'Количество товара указано неправильно!';
                }
                
                let product = $('#product-value').val().trim();
                if (product == '') {
                    error = 'Выберите товар!';
                } else if (isNaN(product)) {
                    error = 'Значение товара указано неправильно!';
                }
                
                let category = $('#product-category').val().trim();
                if (category != '' && isNaN(category)) {
                    error = 'Значение категории указано неправильно!';
                }

                let subs = $('#subs-panel div').find('select.item, input.item');
                if (subs.length > 0) {
                    $.each(subs, function() {
                        if ($(this).val() == '' || isNaN($(this).val())) {
                            error = 'Заполните информацию о Sub-ID!';
                        } 
                    });
                }


                if (error) {
                    btn.addClass('disabled');
                    return error;
                } else {
                    btn.removeClass('disabled');
                    return false;
                }
            }

            form.on('keyup change', function() {
                checkFields();
            });

            form.on('submit', function(e){
                let error = checkFields();
                if (error) {
                    if (!$('.modal-window-content').last().is('.error')) {
                        $('.modal-window-content').last().prepend('<div class="error" style="max-width: 337px"></div>');
                        $('.error').text(error).show();
                    }
                } else {
                    let countItems = $('#<?=$table?>').find('tbody tr[data-id!="0"]').length,
                        count_modal = $('.modal-window-wrapper').length,
                        data = $(this).serializeArray();
                    data.push({ name: "count_items", value: countItems });
                    $.ajax({
                        type: "POST",
                        url: "/system/ajax/addProductInTable.php?action=submit&table=<?=$table?>",
                        data: data,
                        success: function(response) {
                            let jsonData = JSON.parse(response);
                            if (jsonData.success == 1) {
                                let tableItems = $('#<?=$table?>').find('tbody tr');
                                $.each(tableItems, function() {
                                    if ($(this).attr('data-id') == 0) {
                                        $(this).remove();
                                        return false;
                                    }
                                });

                                let productItem = jsonData.productItem;
                                $('#<?=$table?>').find('tbody').append(productItem);

                                let lastItem = $('#<?=$table?>').find('tbody tr').last();

                                if (lastItem.attr('data-role') == 'child') {
                                    let prevParentItems = lastItem.prevAll('tr[data-role="parent"]'),
                                        i = 1;
                                    $.each(prevParentItems, function() {
                                        if (i == 1) {
                                            $(this).children('td:nth-child(1), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7), td:nth-child(8)').css('border-bottom', 'none');
                                            if ($(this).prev().attr('data-role') == 'child') {
                                                let siblingPrevParentItems = $(this).prevAll('tr[data-role="parent"]');
                                                $.each(siblingPrevParentItems, function() {
                                                    $(this).children('td:nth-child(1), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7), td:nth-child(8)').css('border-bottom', '1px solid #eee');
                                                });
                                            }
                                        } else {
                                            return false;
                                        }
                                        i++;
                                    });
                                } else {
                                    let prevChildItems = lastItem.prevAll('tr[data-role="child"]'),
                                        i = 1;
                                    $.each(prevChildItems, function() {
                                        if (i == 1) {
                                            let prevParentItems = $(this).prevAll('tr[data-role="parent"]');
                                            $.each(prevParentItems, function() {
                                                $(this).children('td:nth-child(1), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7), td:nth-child(8)').css('border-bottom', '1px solid #eee');
                                            });
                                        } else {
                                            return false;
                                        }
                                        i++;
                                    });
                                }

                                let count = 0,
                                    amount = 0;
                                $.each($('#<?=$table?> tbody tr').find('td'), function() {
                                    if ($(this).attr('data-name') == 'count') {
                                        count += Number($(this).text());
                                    } else if ($(this).attr('data-name') == 'amount') {
                                        amount += Number($(this).text());
                                    }
                                });

                                if (count != 0) $('#<?=$table?>').find('#<?=$table?>-count span').text(count);
                                if (amount != 0) $('#<?=$table?>').find('#<?=$table?>-amount span').text(amount.toFixed(2));

<?
    if (isset($_GET['location'])) {
        if ($_GET['location'] == 'order') {
?>
                                let last_amount = Number($('.modal-window-content__amount').find('span').text());
                                    total_amount = Number($('#order-products').find('#order-products-amount span').text()) + Number($('#add-sale-products').find('#add-sale-products-amount span').text()),
                                // $('.modal-window-content__amount').find('span').text(total_amount.toFixed(2));
                                
                                $({ numberValue: last_amount }).animate({ numberValue: total_amount }, {
                                    duration: 250,
                                    step: function() { 
                                        $('.modal-window-content__amount').find('span').text(this.numberValue.toFixed(2)); 
                                    },
                                    complete: function() {
                                        $('.modal-window-content__amount').find('span').text(total_amount.toFixed(2));
                                    }
                                });
<?
        }
    }
?>

                                closeModalWindow(count_modal);
                                $('#add-ga, #add-order').trigger('keyup');
                            } else {
                                if (!$('.modal-window-content div').last().is('.error')) {
                                    $('.modal-window-content').last().prepend('<div class="error" style="max-width: 337px"></div>');
                                }
                                $('.error').text(jsonData.error).show();
                            }
                        }
                    });
                    
                }
                return false;
            });

        });
        
        function clearSubs(event) {
            $(event).closest('.modal-window-content__subs').remove();
            if ($('.modal-window-wrapper').last().find('.modal-window-content__subs').length > 0) {
                return;
            } else {
                $('#subs-panel b').show();
            }
            $('.modal-window-content').last().find('.error').remove();
        }
    </script>
    <form id="add-product-in-table__<?=$table?>" method="post" autocomplete="off">
        <div class="modal-window-content__item">
                <div class="modal-window-content__title">Товар</div>
                <div class="modal-window-content__value">
                    <span>Категория</span> <i class="fa fa-sitemap"></i> <select id="product-category" name="category" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$query = $db->query("SELECT * FROM `product_categories` WHERE `status` = 'on' AND `client_id` = '" . $chief['id'] . "' ORDER BY `id`");
$categories = array();
while ($category = $query->fetch_assoc()) {
    $categories[$category['parent_id']][] = $category;
}
echo build_tree_select($categories, 0, 0);
?>
                    </select>
                </div>
                <div class="modal-window-content__value">
                    <span>Товар</span> <i class="fa fa-question"></i> <select id="product-value" name="product" class="chosen-select">
<?php
$count_products = $db->query("SELECT COUNT(*) FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($count_products[0] == 0) {
?>
                        <option value="">- Нет товаров -</option>
<?
} else {
    $query = $db->query("SELECT `id_item`, `name`, `model`, `status` FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "' ORDER BY `id`");
?>
                        <option value="">- Не указано -</option>
<?
    while ($product = $query->fetch_assoc()) {
?>
                        <option value="<?=$product['id_item']?>"<?=$product['status'] == 'off' ? ' disabled' : ''?>><?=protection($product['id_item'] . ' - ' . $product['name'] . ' ' . $product['model'], 'display')?></option>
<?
        echo "\r\n";
    }
}
?>
                                        </select>
                </div>
                <div class="modal-window-content__value">
                    <span>ID товара</span> <i class="fa fa-info"></i> <div id="product-id" class="modal-window-content__value-block">-</div>
                </div>
                <div class="modal-window-content__value">
                    <span>Текущий остаток</span> <i class="fa fa-info"></i> <div id="product-count" class="modal-window-content__value-block">-</div>
                </div>
                <div class="modal-window-content__value">
                    <span>Цена реализации</span> <i class="fa fa-info"></i> <div id="product-price" class="modal-window-content__value-block">-</div>
                </div>

                <div class="modal-window-content__title">Sub-ID</div>
                <div class="modal-window-content__value">
                    <div id="subs-panel" class="modal-window-content__subs-panel" style="width: 100%; text-align: center"><b>Нет смежных товаров</b></div>
                    <div id="add-sub-id" style="padding: 10px 0 0 0; display: none; text-align: center; font-weight: 700"><i class="fa fa-plus-circle"></i> <a id="add-sub-id-link" href="javascript:void(0);">Добавить Sub-ID</a></div>
                </div>

                <div class="modal-window-content__title">Итоговая информация</div>
                <div class="modal-window-content__value">
                    <span>Поступает</span> <i class="fa fa-plus-square"></i> <div class="modal-window-content__value-block"><input class="small" id="product-income" type="text" name="income" style="width: 60px; text-align: center" placeholder="0"> шт.</div>
                </div>
                <div class="modal-window-content__value">
                    <span>Цена закупки</span> <i class="fa fa-credit-card"></i> <div class="modal-window-content__value-block"><input class="small" id="product-base-price" type="text" name="base_price" style="width: 60px; text-align: center" placeholder="0.00"> или</div>
                </div>
                <div class="modal-window-content__value">
                    <div class="modal-window-content__value-block"><input class="small" id="product-price-dollar" type="text" name="price_dollar" style="width: 60px; text-align: center" placeholder="0.00"> $ по курсу <input class="small" id="product-price-dollar-course" type="text" name="price_dollar_course" style="width: 60px; text-align: center" placeholder="0.00"></div>
                </div>
                

                <div class="buttons">
                    <button id="button-add-product-in-table__<?=$table?>" class="disabled">Добавить</button>
                </div>
            </div>
            <input type="submit" style="display: none">
    </form>
<?
} else {
?>
    Не указана таблица!
<?
}
