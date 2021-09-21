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
            <option value="<?=$category['id_item'] ?>" style="text-align: left; padding-left: <?=($level == 0 ? '5' : $level * 20)?>px">
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

    $category = isset($_POST['category']) ? abs(intval($_POST['category'])) : null;
    $product_id = isset($_POST['product']) ? abs(intval($_POST['product'])) : null;
    $count = isset($_POST['write_off']) ? abs(intval($_POST['write_off'])) : null;
    $count_with_attributes = isset($_POST['count_with_attributes']) ? $_POST['count_with_attributes'] : null;
    $comment = isset($_POST['comment']) ? protection($_POST['comment'], 'base') : null;
    $items_count = 0;

    $product = $db->query("SELECT `id_item`, `count`, `count_with_attributes` FROM `products` WHERE `id_item` = '" . $product_id . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();

    if ($count == 0) {
        $error = 'Укажите количество списываемого товара!';
    }

    if (is_array($count_with_attributes) and $count_with_attributes) {

        $key_stmt = $db->query("SELECT `key_id`, `count` FROM `products_sub-id-keys` WHERE `product_id` = '" . $product_id . "' AND `client_id` = '" . $chief['id'] . "'");
        $key_ids = $key_counts = array();
        while ($key = $key_stmt->fetch_assoc()) {
            $key_ids[] = $key['key_id'];
            $key_counts[$key['key_id']] = $key['count'];
        }
        foreach ($count_with_attributes as $key_id => $key_count) {
            $items_count += $key_count;
            if ($key_id != '' and is_numeric($key_id) and $key_count != '' and is_numeric($key_count)) {
                if (!in_array($key_id, $key_ids)) {
                    $error = 'У товара нет такого Sub-ID!';
                } else if ($key_count > $key_counts[$key_id]) {
                    $error = ($key_counts[$key_id] == 0) ? 'Товара с таким Sub-ID нет в наличии!' : 'Нельзя списать товара с Sub-ID больше, чем есть на складе!';
                }
            } else {
                if ($key_count == 0 and $key_counts[$key_id] > 0) {
                    $error = 'Укажите количество списываемого товара с Sub-ID!';
                }
            }

            if (isset($error)) break;
        }
        if (array_sum($count_with_attributes) > $count) {
            $error = 'Общее количество указано неправильно!';
        }

    }

    if ($count > ($allow_count = $product['count'] - $product['count_with_attributes']) and $product['count_with_attributes'] > 0) {
        if (($count - $items_count) > $allow_count and $allow_count > 0) {
            $error = 'Без Sub-ID можно списать не больше <b>' . $allow_count . '</b> ед. товара! <p style="color: #757575"><b>' . $product['count_with_attributes'] . '</b> ед. товара с атрибутами.<br>Чтобы списать товары с атрибутами<br>нажмите на ссылку "Добавить Sub-ID".</p>';
        } elseif ($items_count == 0 and $allow_count == 0) {
            $error = 'Товар распределен по атрибутам.<br>Необходимо указать Sub-ID списываемого товара!';
        }
    }

    if ($product['count'] <= 0) {
        $error = 'Этого товара нет на складе!';
    } else if ($count > $product['count']) {
        $error = 'Нельзя списать товара больше, чем есть на складе!';
    }
    
    if (!empty($comment)) {
        if (mb_strlen($comment, 'UTF-8') > 200) {
            $error = 'Описание не должно превышать 200 символов!';
        }
    }

    if ($product_id == 0) {
        $error = 'Выберите товар!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `id_item` = '" . $product_id . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Выбран неизвестный товар!';
    }

    if ($category != 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `product_categories` WHERE `id_item` = '" . $category . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Выбрана неизвестная категория!';
        }
    }

    if (!isset($error)) {
        $countItems = $db->query("SELECT `write_off_of_goods` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $countItems['write_off_of_goods'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `write_off_of_goods` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `write_off_of_goods` (`id`, `id_item`, `client_id`, `employee_id`, `comment`, `date_added`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $user['id'] . "', '" . $comment . "', '" . $data['time'] . "')")) {
                $success = 1;
                // $last_woog_id = $db->insert_id;
                $last_woog_id = $id_item;
                $with_attributes = 0;
    
                // Движение товаров
                $db->query("INSERT INTO `movement_of_goods` (`id`, `client_id`, `employee_id`, `order_id`, `status_start`, `status_end`, `date_added`) VALUES (null, '" . $chief['id'] . "', '" . $user['id'] . "', '-1', '-1', '-1', '" . $data['time'] . "')");
                $last_mog_id = $db->insert_id;
    
                if (is_array($count_with_attributes) and $count_with_attributes) {
                    $with_attributes = array_sum($count_with_attributes);
                    if ($with_attributes > 0) {
                        $item_count = 0;
                        foreach ($count_with_attributes as $key_id => $key_count) {
                            $item_count += $key_count;
                            $db->query("INSERT INTO `write_off_of_goods-products` (`id`, `client_id`, `woog_id`, `product_id`, `count`) VALUES (null, '" . $chief['id'] . "', '" . $last_woog_id . "', '" . $product['id_item'] . "', '" . $key_count . "')");
                            $last_woog_product_id = $db->insert_id;
    
                            // Атрибуты
                            $insert_attributes = "INSERT INTO `write_off_of_goods-attributes` (`id`, `client_id`, `woog_product_id`, `attribute_id`) VALUES";
    
                            // Движение товаров
                            $item_key = $db->query("SELECT `count` FROM `products_sub-id-keys` WHERE `key_id` = '" . $key_id . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();

                            $db->query("INSERT INTO `movement_of_goods-products` (`id`, `client_id`, `mog_id`, `product_id`, `balance`, `balance_with_attributes`, `change`, `date_updated`) VALUES (null, '" . $chief['id'] . "', '" . $last_mog_id . "', '" . $product['id_item'] . "', '" . ($product['count'] - $item_count) . "', '" . ($item_key['count'] - $key_count) . "', '-" . $key_count . "', '" . $data['time'] . "')");
                            $last_mog_product_id = $db->insert_id; // Если будут атрибуты

                            $insert_mog_attributes = "INSERT INTO `movement_of_goods-attributes` (`id`, `client_id`, `mog_product_id`, `attribute_id`) VALUES";
    
                            $attributes = $db->query("SELECT `sub_id` FROM `products_sub-id-values` WHERE `key_id` = '" . $key_id . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
                            while ($attribute = $attributes->fetch_assoc()) {
                                $insert_attributes .= " (null, '" . $chief['id'] . "', '" . $last_woog_product_id . "', '" . $attribute['sub_id'] . "'),";
    
                                $insert_mog_attributes .= " (null, '" . $chief['id'] . "', '" . $last_mog_product_id . "', '" . $attribute['sub_id'] . "'),";
                            }
    
                            $db->query("UPDATE `products_sub-id-keys` SET `count` = `count` - '" . $key_count . "' WHERE `key_id` = '" . $key_id . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
    
                            $db->query(rtrim($insert_attributes, ','));
                            $db->query(rtrim($insert_mog_attributes, ','));
                        }
                    }
                }
                if ($with_attributes == 0) {
                    $db->query("INSERT INTO `write_off_of_goods-products` (`id`, `client_id`, `woog_id`, `product_id`, `count`) VALUES (null, '" . $chief['id'] . "', '" . $last_woog_id . "', '" . $product['id_item'] . "', '" . $count . "')");
    
                    $db->query("INSERT INTO `movement_of_goods-products` (`id`, `client_id`, `mog_id`, `product_id`, `balance`, `change`, `date_updated`) VALUES (null, '" . $chief['id'] . "', '" . $last_mog_id . "', '" . $product['id_item'] . "', '" . ($product['count'] - $count) . "', '-" . $count . "', '" . $data['time'] . "')");
                } elseif (($count - $with_attributes) > 0) {
                    $db->query("INSERT INTO `write_off_of_goods-products` (`id`, `client_id`, `woog_id`, `product_id`, `count`) VALUES (null, '" . $chief['id'] . "', '" . $last_woog_id . "', '" . $product['id_item'] . "', '" . ($count - $with_attributes) . "')");


                    $db->query("INSERT INTO `movement_of_goods-products` (`id`, `client_id`, `mog_id`, `product_id`, `balance`, `change`, `date_updated`) VALUES (null, '" . $chief['id'] . "', '" . $last_mog_id . "', '" . $product['id_item'] . "', '" . ($product['count'] - $count) . "', '-" . ($count - $with_attributes) . "', '" . $data['time'] . "')"); // ToDo: `balance`
    
                    // $db->query("INSERT INTO `movement_of_goods-products` (`id`, `client_id`, `mog_id`, `product_id`, `balance`, `change`, `date_updated`) VALUES (null, '" . $chief['id'] . "', '" . $last_mog_id . "', '" . $product['id_item'] . "', '" . ($product['count'] - ($count - $with_attributes)) . "', '-" . ($count - $with_attributes) . "', '" . $data['time'] . "')");
                }
    
                $db->query("UPDATE `products` SET `count` = `count` - '" . $count . "', `count_with_attributes` = `count_with_attributes` - '" . $with_attributes . "', `date_updated` = '" . $data['time'] . "' WHERE `id_item` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
            }

            $db->query("UPDATE `id_counters` SET `write_off_of_goods` = (`write_off_of_goods` + 1) WHERE `client_id` = '" .  $chief['id'] . "'");
        } else {
            $error = 'Произошла ошибка! Попробуйте еще раз.';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
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
                $products = $db->query("SELECT `id_item`, `count`, `name`, `model` FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'");
?>
                        <option value="">- Не указано -</option>
<?
                while ($product = $products->fetch_assoc()) {
?>
                        <option value="<?=$product['id_item']?>"<?=($product['count'] <= 0 ? ' disabled' : '')?>><?=$product['id_item'] . ' - ' . protection($product['name'] . ' ' . $product['model'] . ' (' . $product['count'] . ' шт.)', 'display')?></option>
<?
                    echo "\r\n";
                }
            }

        } else {
            if ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `category` = '" . $category . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
                $products = $db->query("SELECT `id_item`, `count`, `name`, `model` FROM `products` WHERE `deleted_at` = '0' AND `category` = '" . $category . "' AND `client_id` = '" . $chief['id'] . "'");
?>
                        <option value="">- Не указано -</option>
<?
                while ($product = $products->fetch_assoc()) {
?>
                        <option value="<?=$product['id_item']?>"<?=($product['count'] <= 0 ? ' disabled' : '')?>><?=$product['id_item'] . ' - ' . protection($product['name'] . ' ' . $product['model'] . ' (' . $product['count'] . ' шт.)', 'display')?></option>
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
            $product = $db->query("SELECT `id_item`, `count`, `count_with_attributes`, `purchase_price`, `base_price` FROM `products` WHERE `id_item` = '" . $product . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $subsCount = $db->query("SELECT COUNT(*) FROM `products_sub-id-keys` WHERE `product_id` = '" . $product['id_item'] . "' AND `count` > '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            if ($subsCount[0] > 0) $subs = 1;
            if (isset($_GET['subs'])) {
                if ($subsCount[0] > 0) {
                    $subs = array();
                    $keys = $db->query("SELECT `key_id`, `count` FROM `products_sub-id-keys` WHERE `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
                    while ($key = $keys->fetch_assoc()) {
                        $attrs = $db->query("SELECT `sub_id` FROM `products_sub-id-values` WHERE `key_id` = '" . $key['key_id'] . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
                        $attributes = array();
                        while ($attr = $attrs->fetch_assoc()) {
                            $attribute = $db->query("SELECT `attributes`.`id_item`, `attributes`.`name`, `attribute_categories`.`name` AS `category_name` FROM `attributes` INNER JOIN `attribute_categories` ON (`attributes`.`category_id` = `attribute_categories`.`id_item`) WHERE `attributes`.`id_item` = '" . $attr['sub_id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `attribute_categories`.`client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                            $attributes[$attribute['category_name']] = protection($attribute['name'], 'display');
                        }
                        if ($key['count'] > 0) $subs[] = array('key_id' => $key['key_id'], 'count' => $key['count'], 'attributes' => $attributes);
                    }
                    
                } else {
                    $success = null;
                    $error = 'У товара нет Sub-ID!';
                }
                echo json_encode(array('success' => $success, 'error' => $error, 'subs' => $subs));
                exit;
            }

            $info = array(
                'id_item' => $product['id_item'],
                'count' => $product['count'],
                'count_with_attributes' => $product['count_with_attributes'],
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

?>
    <script>
        $(function(){
            $('#product-category').on('change', function(e){
                let category = $(this).val().trim(),
                    product = $('#product-value').val().trim(),
                    data = { 'category_id': category }
                $('#product-value').prop('disabled', true).find('option:eq(0)').text('Загрузка...');
                $('.chosen-select').trigger('chosen:updated');
                $('#product-value').load('/system/ajax/addWOOG.php?get=products', data, function(response) {
                    $('#product-value').prop('disabled', false);
                    $('.chosen-select').trigger('chosen:updated');
                });

                if (category == '' || product != '') {
                    $('#product-id').text('-');
                    $('#product-count').text('-');
                    $('#product-write-off').val('');
                    $('#subs-panel div').remove();
                    $('#subs-panel b').show();
                    $('#add-sub-id').hide();
                }

            });

            $('#product-value').on('change', function(e){
                let product = $(this).val().trim();
                console.log('a')
                if (product != '') {
                    let data = { 'product_id': product }
                    $.ajax({
                        type: "POST",
                        url: "/system/ajax/addWOOG.php?get=product_info",
                        data: data,
                        success: function(response) {
                            let jsonData = JSON.parse(response);
                            console.log(jsonData)
                            if (jsonData.success == 1) {
                                $('#product-id').text(jsonData.info.id_item);
                                $('#product-count').html('<b>' + jsonData.info.count + '</b> шт.' + ((jsonData.info.count_with_attributes > 0) ? ((jsonData.info.count_with_attributes == jsonData.info.count) ? ' <span style="color: green">[Распределено]</span>' : ' <span style="color: red">[Не распределено: <b>' + (jsonData.info.count - jsonData.info.count_with_attributes) + '</b> шт.]</span>') : ''));
                                
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
                    $('#product-id').text('-');
                    $('#product-count').text('-');
                    $('#product-write-off').val('');
                    $('#subs-panel div').remove();
                    $('#subs-panel b').show();
                    $('#add-sub-id').hide();
                }

            });

            $('.modal-window-content__value').on('keyup', '.count-subs', function(e){
                let countItems = $('.modal-window-content__value').find('.count-subs'),
                    countProducts = 0;
                $.each(countItems, function() {
                    if (!isNaN($(this).val())) {
                        countProducts += Math.abs($(this).val());
                    }
                });
                $('#product-write-off').val(countProducts);
                checkFields();
            });

            

            // Добавляем атрибуты
            $('#add-sub-id-link').on('click', function(e) {
                if ($('#product-value').val() != '') {
                    $.ajax({
                        type: "POST",
                        url: "/system/ajax/addWOOG.php?get=product_info&subs",
                        data: { 'product_id': $('#product-value').val() },
                        success: function(response) {
                            let jsonData = JSON.parse(response);
                            console.log(jsonData)
                            if (jsonData.success == 1) {
                                btn.addClass('disabled');
                                $('#subs-panel b').hide();
                                $('#add-sub-id').hide();
                                let last_subs_div = $('.modal-window-wrapper').last().find('.modal-window-content__subs').length + 1,
                                    subs = jsonData.subs,
                                    subItems = '';

                                $.each(subs, function(key, value) {

                                    let attributes = '';
                                    $.each(value, function(key, value) {
                        
                                        if (key === 'attributes') {
                                            $.each(value, function(key, value) {
                                                // Атрибуты
                                                attributes += '<div class="modal-window-content__value">' +
                                                                '<span>' + key + '</span> <i class="fa fa-angle-right"></i> ' +
                                                                '<div class="modal-window-content__value-block">' + value + '</div>' +
                                                            '</div>';
                                            });
                                        }
                                        
                                    });
                                    subItems += '<div data-id="' + last_subs_div + '" class="modal-window-content__subs">' +
                                                    '<span id="clear-subs" class="modal-window-content__subs-clear" onclick="clearSubs(this);" title="Убрать атрибуты">x</span>' +
                                                    attributes +
                                                    '<div class="modal-window-content__value">' +
                                                        '<span>В наличии</span> <i class="fa fa-archive"></i> ' +
                                                        '<div class="modal-window-content__value-block">' +
                                                            '<b class="green">' + value.count + '</b> шт.' +
                                                        '</div>' +
                                                    '</div>' +
                                                    '<div class="modal-window-content__value">' +
                                                        '<span>Количество</span> <i class="fa fa-cubes"></i> ' +
                                                        '<div class="modal-window-content__value-block">' +
                                                            '<input class="small count-subs" style="width: 60px; text-align: center; margin: 0" type="text" name="count_with_attributes[' + value.key_id + ']" placeholder="0"> шт.' +
                                                        '</div>' +
                                                    '</div>' +
                                                '</div>';

                                });
                                            
                                $('#subs-panel').append(subItems);
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

            let form = $('#add-woog'),
                btn = form.find('#button-add-woog');

            function checkFields() {
                let error;

                let writeOff = $('#product-write-off').val().trim();
                if (writeOff == 0) {
                    error = 'Укажите количество списываемого товара!';
                } else if (isNaN(writeOff)) {
                    error = 'Количество списываемого товара указано неправильно!';
                }

                let product = $('#product-value').val().trim();
                if (product == 0) {
                    error = 'Выберите товар!';
                } else if (isNaN(product)) {
                    error = 'Товар указан неправильно!';
                }
                
                let category = $('#product-category').val().trim();
                if (category != 0 && isNaN(category)) {
                    error = 'Категория указана неправильно!';
                }

                let subs = $('.modal-window-content__subs .modal-window-content__value').find('input.count-subs');
                if (subs.length > 0) {
                    $.each(subs, function() {
                        if ($(this).val() != 0 && isNaN($(this).val())) {
                            error = 'Количество списываемого товара с Sub-ID указано неправильно!';
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
                    if (!$('.modal-window-content').is('.error')) {
                        $('.modal-window-content').last().prepend('<div class="error"></div>');
                        $('.error').text(error).show();
                    }
                } else {
                    let data = $(this).serializeArray(),
                        count_modal = $('.modal-window-wrapper').length;
                    $.ajax({
                        type: "POST",
                        url: "/system/ajax/addWOOG.php?action=submit",
                        data: data,
                        success: function(response) {
                            let jsonData = JSON.parse(response);
                            if (jsonData.success == 1) {
                                loadWOOG();
                                closeModalWindow(count_modal);
                            } else {
                                if (!$('.modal-window-content div').is('.error')) {
                                    $('.modal-window-content').last().prepend('<div class="error"></div>');
                                    $('.error').html(jsonData.error).show();
                                }
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
            $('#add-sub-id').show();
        }
    </script>
    <form id="add-woog" method="post" autocomplete="off">
        <div class="modal-window-content__item">
                <div class="modal-window-content__title">Товар</div>
                <div class="modal-window-content__value">
                    <span>Категория</span> <i class="fa fa-sitemap"></i> <select id="product-category" name="category" class="chosen-select">
                        <option value="">- Не указано -</option>
<?php
$query = $db->query("SELECT * FROM `product_categories` WHERE `status` = 'on' AND `client_id` = '" . $chief['id'] . "' ORDER BY `id` ASC");
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
    $query = $db->query("SELECT `id_item`, `name`, `model`, `count` FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'");
?>
                        <option value="">- Не указано -</option>
<?
    while ($product = $query->fetch_assoc()) {
?>
                        <option value="<?=$product['id_item']?>"<?=($product['count'] <= 0 ? ' disabled' : '')?>><?=$product['id_item'] . ' - ' . protection($product['name'] . ' ' . $product['model'] . ' (' . $product['count'] . ' шт.)', 'display')?></option>
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
                    <span>На складе</span> <i class="fa fa-archive"></i> <div id="product-count" class="modal-window-content__value-block">-</div>
                </div>

                <div class="modal-window-content__title">Sub-ID</div>
                <div class="modal-window-content__value">
                    <div id="subs-panel" class="modal-window-content__subs-panel" style="width: 100%; text-align: center"><b>Нет смежных товаров</b></div>
                    <div id="add-sub-id" style="padding: 10px 0 0 0; display: none; text-align: center; font-weight: 700"><i class="fa fa-plus-circle"></i> <a id="add-sub-id-link" href="javascript:void(0);">Добавить Sub-ID</a></div>
                </div>

                <div class="modal-window-content__title">Итоговая информация</div>
                <div class="modal-window-content__value">
                    <span>Количество</span> <i class="fa fa-minus-square"></i> <div class="modal-window-content__value-block"><input class="small" id="product-write-off" type="text" name="write_off" style="width: 60px; text-align: center" placeholder="0" autocomplete="off"> шт.</div>
                </div>
                <div class="modal-window-content__value">
                    <span>Описание</span> <i class="fa fa-comment"></i> <textarea name="comment" id="woog-comment"></textarea>
                </div>
                

                <div class="buttons">
                    <button id="button-add-woog" class="disabled">Списать</button>
                </div>
            </div>
            <input type="submit" style="display: none">
    </form>
