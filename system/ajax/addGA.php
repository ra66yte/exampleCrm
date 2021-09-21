<?php
include_once '../core/begin.php';

if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $supplier = isset($_POST['supplier']) ? abs(intval($_POST['supplier'])) : null;
    $comment = isset($_POST['comment']) ? protection($_POST['comment'], 'base') : null;
    $products = isset($_POST['products']) ? json_decode($_POST['products'], true) : null;
    $amount = 0;

    if (is_array($products) and $products) {
        foreach ($products as $product) {
            $id = abs(intval($product['id_item']));
            $count = abs(intval($product['count']));
            $price = abs(floatval($product['price']));
            $amount += number_format(($count * $price), 2, '.', '');
            $attributes = $product['attributes'];
    
            if (is_array($attributes) and $attributes) {
                if ($count_attributes = $db->query("SELECT COUNT(*) FROM `products_sub-id` WHERE `product_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                    $error = 'У товара нет Sub-ID!';
                } elseif ($count_attributes[0] != count($attributes)) {
                    $error = 'Произошла ошибка при выборе Sub-ID для товара! [e:1]' . $count_attributes[0] . ' - ' . count($attributes);
                } else {
    
                    foreach ($attributes as $attribute) {
                        if (empty($attribute)) {
                            $error = 'Значение Sub-ID не может быть пустым или равняться 0!';
                        } elseif (!is_numeric($attribute)) {
                            $error = 'Некорректное значение Sub-ID!';
                        } elseif ($result = $db->query("SELECT COUNT(*) FROM `attributes` INNER JOIN `products_sub-id` ON (`attributes`.`category_id` = `products_sub-id`.`attribute_category_id`) WHERE `products_sub-id`.`product_id` = '" . $id . "' AND `products_sub-id`.`client_id` = '" . $chief['id'] . "' AND `attributes`.`id_item` = '" . abs(intval($attribute)) . "' AND `attributes`.`client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                            $error = 'Sub-ID не найден!';
                        }
                        if (isset($error)) break;
                    }
    
                    // Проверим категории предоставленных атрибутов
                    if (count($attributes) > 1) {
                        $in_attributes = implode(",", $attributes);
                        $categories_stmt = $db->query("SELECT `attribute_categories`.`id_item` FROM `attribute_categories` INNER JOIN `attributes` ON (`attribute_categories`.`id_item` = `attributes`.`category_id`) WHERE `attributes`.`id_item` IN ($in_attributes) AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `attribute_categories`.`client_id` = '" . $chief['id'] . "'");
                        $categories = array();
                        while ($category = $categories_stmt->fetch_assoc()) {
                            $categories[] = $category['id_item'];
                        }
                        if (count($attributes) != count(array_unique($categories))) {
                            $error = 'Произошла ошибка при выборе Sub-ID для товара! [e:2]';
                        }
                    }
    
                }
            }
    
            if ($price == 0) {
                $error = 'Необходимо указать цену товара!';
            }
    
            if ($count == 0) {
                $error = 'Необходимо указать количество товара!';
            }
    
            if ($id == 0) {
                $error = 'Необходимо указать товар!';
            } elseif ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `id_item` = '" . $id . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                $error = 'Товар не найден!';
            }
    
            if ($error) break;
        }    
    } else {
        $error = 'Добавьте товар!';
    }
    

    if (!empty($comment) and mb_strlen($comment, 'UTF-8') > 200) {
        $error = 'Комментарий не должен превышать 200 символов!';
    }

    if (empty($supplier)) {
        $error = 'Укажите поставщика!';
    } elseif (!is_numeric($supplier) or ($result = $db->query("SELECT COUNT(*) FROM `suppliers` WHERE `id_item` = '" . $supplier . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0)) {
        $error = 'Поставщик не найден!';
    }
     
    // Если нет ошибок
    if (!isset($error)) {
        
        $all_arrivals = $db->query("SELECT COUNT(*) FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
        $count_arrivals = str_pad(($all_arrivals[0] + 1), 7, '0', STR_PAD_LEFT); // ToDo: изменить
        $incoming_order = $chief['id'] . '-' . $count_arrivals;

        $count_items = $db->query("SELECT `goods_arrival` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $arrival_id_item = $count_items['goods_arrival'] + 1;
        
        if ($result = $db->query("SELECT COUNT(*) FROM `arrival_of_goods` WHERE `id_item` = '" . $arrival_id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            if ($db->query("INSERT INTO `arrival_of_goods` (`id`, `id_item`, `client_id`, `employee_id`, `supplier_id`, `incoming_order`, `amount`, `date_added`, `comment`) VALUES (null, '" . $arrival_id_item . "', '" . $chief['id'] . "', '" . $user['id_item'] . "', '" . $supplier . "', '" . $incoming_order . "', '" . $amount . "', '" . $data['time'] . "', '" . $comment . "')")) {
                $success = 1;
                $last_arrival_id =  $arrival_id_item;
    
                // Движение товаров
                $db->query("INSERT INTO `movement_of_goods` (`id`, `client_id`, `employee_id`, `order_id`, `status_start`, `status_end`, `date_added`) VALUES (null, '" . $chief['id'] . "', '" . $user['id_item'] . "', '0', '0', '0', '" . $data['time'] . "')");
                $last_mog_id = $db->insert_id;
    
                // Обновляем количество товаров на складе
                $update_count = "INSERT INTO `products` (`id`, `id_item`, `count`) VALUES ";
    
                // Если будут атрибуты
                $has_attributes = null;
                $insert_arrival_attributes = "INSERT INTO `arrival_of_goods-attributes` (`id`, `client_id`, `arrival_id`, `arrival_products_id`, `product_id`, `attribute_id`) VALUES"; // Добавляем атрибуты к товарам прихода
                // Движение товаров
                $insert_mog_attributes = "INSERT INTO `movement_of_goods-attributes` (`id`, `client_id`, `mog_product_id`, `attribute_id`) VALUES";
                $mog_data = $count_items = array();
                foreach ($products as $product) {
                    $id =         abs(intval($product['id_item']));
                    $count =      abs(intval($product['count']));
                    $price =      abs(floatval($product['price']));
                    $amount =     number_format(($count * $price), 2, '.', '');
                    $attributes = $product['attributes'];
                    $count_with_attributes = 0;

                    $product = $db->query("SELECT `id`, `id_item`, `count` FROM `products` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
    
                    // Добавляем поступившие товары
                    $db->query("INSERT INTO `arrival_of_goods-products` (`id`, `client_id`, `arrival_id`, `product_id`, `count`, `price`, `amount`) VALUES (null, '" . $chief['id'] . "', '" . $last_arrival_id . "', '" . $product['id_item'] . "', '" . $count . "', '" . $price . "', '" . $amount . "')");
                    $last_arrival_products_id = $db->insert_id; // Если будут атрибуты
    
                    // Движение товаров
                    if (!array_key_exists($product['id_item'], $count_items)) $count_items[$product['id_item']] = 0;
                    $count_items[$product['id_item']] += $count;
                    if (!array_key_exists($product['id_item'], $mog_data)) $mog_data = [$product['id_item'] => array()] + $mog_data;
    
                    $db->query("INSERT INTO `movement_of_goods-products` (`id`, `client_id`, `mog_id`, `product_id`, `balance`, `change`, `date_updated`) VALUES (null, '" . $chief['id'] . "', '" . $last_mog_id . "', '" . $product['id_item'] . "', '0', '" . $count . "', '" . $data['time'] . "')");
                    $last_mog_product_id = $db->insert_id; // Если будут атрибуты
    
                    $mog_data[$product['id_item']][$last_mog_product_id] = ($product['count'] + $count_items[$product['id_item']]);
                    /* mog */
                    
                    // Если есть атрибуты
                    if (is_array($attributes) and count($attributes) > 0) {
                        $has_attributes = true;
                        $count_with_attributes += $count;
    
                        $keys = $db->query("SELECT `id`, `key_id` FROM `products_sub-id-keys` WHERE `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
                        $parent_attrs = array();
    
                        while ($key = $keys->fetch_assoc()) {
                            $attrs = $db->query("SELECT `sub_id` FROM `products_sub-id-values` WHERE `key_id` = '" . $key['key_id'] . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
                            $child_attrs = array();
                            while ($attr = $attrs->fetch_assoc()) {
                                $child_attrs[] = $attr['sub_id'];
                            }
                            $parent_attrs[$key['key_id']] = $child_attrs;
                        }

                        $key = null;
    
                        if (in_array($attributes, $parent_attrs)) { // Если такая комбинация атрибутов уже есть
                            $key = array_search($attributes, $parent_attrs); // Ключ для данной комбинации атрибутов
                            // Обновляем количество товаров с атрибутами 
                            $db->query("UPDATE `products_sub-id-keys` SET `count` = (`count` + '" . $count . "') WHERE `product_id` = '" . $product['id_item'] . "' AND `key_id` = '" . $key . "' AND `client_id` = '" . $chief['id'] . "'");
                            foreach ($attributes as $attribute) {
                                $insert_arrival_attributes .= " (null, '" . $chief['id'] . "', '" . $last_arrival_id . "', '" . $last_arrival_products_id . "', '" . $product['id_item'] . "', '" . abs(intval($attribute)) . "'),";
                                // Движение товаров
                                $insert_mog_attributes .= " (null, '" . $chief['id'] . "', '" . $last_mog_product_id . "', '" . abs(intval($attribute)) . "'),";
                            }
                        } else {
                            $last_key_count = $db->query("SELECT COUNT(*) FROM `products_sub-id-keys` WHERE `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
                            $last_key_id = ($last_key_count[0] + 1);
                            // Добавляем новый ключ и атрибуты к нему
                            $db->query("INSERT INTO `products_sub-id-keys` (`id`, `client_id`, `product_id`, `key_id`, `count`) VALUES (null, '" . $chief['id'] . "', '" . $product['id_item'] . "', '" . $last_key_id . "', '" . $count . "')");
                            $insert_attributes = "INSERT INTO `products_sub-id-values` (`id`, `client_id`, `product_id`, `key_id`, `sub_id`) VALUES"; // Добавляем атрибуты к товару
                            foreach ($attributes as $attribute) {
                                $insert_attributes .= " (null, '" . $chief['id'] . "', '" . $product['id_item'] . "', '" . $last_key_id . "', '" . abs(intval($attribute)) . "'),";
                                $insert_arrival_attributes .= " (null, '" . $chief['id'] . "', '" . $last_arrival_id . "', '" . $last_arrival_products_id . "', '" . $product['id_item'] . "', '" . abs(intval($attribute)) . "'),";
                                // Движение товаров
                                $insert_mog_attributes .= " (null, '" . $chief['id'] . "', '" . $last_mog_product_id . "', '" . abs(intval($attribute)) . "'),";
                            }
                            $db->query(rtrim($insert_attributes, ','));
                        }
    
                    }
                    // Количество товара
                    $update_count .= " ('" . $product['id'] . "', '" . $product['id_item'] . "', '" . $count . "'),";
    
                    if (isset($has_attributes)) {
                        // Обновляем количество товара с атрибутами
                        $db->query("UPDATE `products` SET `count_with_attributes` = `count_with_attributes` + '" . $count_with_attributes . "' WHERE `id_item` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
                        // Движение товаров
                        $key_id = isset($key) ? $key : $last_key_id;
                        $countItems = $db->query("SELECT `count` FROM `products_sub-id-keys` WHERE `key_id` = '" . $key_id . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                        $db->query("UPDATE `movement_of_goods-products` SET `balance_with_attributes` = '" . $countItems['count'] . "' WHERE `id` = '" . $last_mog_product_id . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'"); // О, Боги
                    }
    
                    // Дата обновления на складе
                    $db->query("UPDATE `products` SET `date_updated` = '" . $data['time'] . "' WHERE `id_item` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
                }
    
                // Как же я заебался это настраивать
                if (isset($has_attributes)) {
                    $db->query(rtrim($insert_arrival_attributes, ','));
                    // Движение товаров
                    $db->query(rtrim($insert_mog_attributes, ','));
                }
    
                // Обновляем счетчики товара
                $update_count = rtrim($update_count, ',') . " ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `id_item` = VALUES(`id_item`), `count` = (`count` + VALUES(`count`))"; // Не хотел считать каждый раз количество
                $db->query($update_count);
    
                // Движение товаров
                foreach ($mog_data as $product_id => $product_counts) {
                    foreach ($product_counts as $id => $balance) {
                        $db->query("UPDATE `movement_of_goods-products` SET `balance` = '" . $balance . "' WHERE `id` = '" . $id . "' AND `mog_id` = '" . $last_mog_id . "' AND `product_id` = '" . $product_id . "' AND `client_id` = '" . $chief['id'] . "'");
                    }
                }
    
                $db->query("UPDATE `id_counters` SET `goods_arrival` = (`goods_arrival` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось выполнить операцию!';
            }


        } else {
            $error = 'Не удалось выполнить операцию! [e:2]';
        }

    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
?>
<script>
    $(function(){
        let form = $('#add-ga'),
            btn = form.find('#button-add-ga');

        $('#ga-products tbody').on({
            mouseenter: function () {
                $(this).closest('tr').toggleClass('tr-enter');
                if ($(this).closest('tr').attr('data-role') == "parent" && $(this).closest('tr').attr('data-count') > 1) {
                    let childs = $(this).closest('tr').nextAll('tr[data-role="child"]'),
                        countChilds =  $(this).closest('tr').attr('data-count'),
                        i = 1;
                    $.each(childs, function() {
                        if (i < countChilds) {
                            $(this).toggleClass('tr-enter');
                        }
                        i++;
                    });
                }
            },
            mouseleave: function () {
                $(this).closest('tr').toggleClass('tr-enter');
                if ($(this).closest('tr').attr('data-role') == "parent" && $(this).closest('tr').attr('data-count') > 1) {
                    let childs = $(this).closest('tr').nextAll('tr[data-role="child"]'),
                        countChilds =  $(this).closest('tr').attr('data-count'),
                        i = 1;
                    $.each(childs, function() {
                        if (i < countChilds) {
                            $(this).toggleClass('tr-enter');
                        }
                        i++;
                    });
                }
            }
        }, "tr td[data-name=\"remove\"]");

        function checkFields() {
            let error,
                tableItems = $('#ga-products tbody').find('tr');
            $.each(tableItems, function() {
                if ($(this).attr('data-id') == 0) {
                    error = 'Добавьте товар!';
                    return false;
                } else {
                    if ($(this).attr('data-role') == 'parent') {
                        let tdCount = $(this).find('td[data-name="count"]').text();
                        if (tdCount == '') {
                            error = 'Вы должны указать количество товара(ов)!';
                        } else if (tdCount == 0) {
                            error = 'Количество товара(ов) должно быть больше ноля!';
                        } else if (isNaN(tdCount)) {
                            error = 'Некорректное количество товара(ов)!';
                        }

                        let tdPrice = $(this).find('td[data-name="price"]').text();
                        if (tdPrice == '') {
                            error = 'Вы должны указать цену товара(ов)!';
                        } else if (tdPrice == 0) {
                            error = 'Цена товара(ов) должна быть больше ноля!';
                        } else if (isNaN(tdPrice)) {
                            error = 'Некорректная цена товара(ов)!';
                        }

                        if ($(this).attr('data-count') > 1) {
                            let childs = $(this).nextAll('tr[data-role="child"]'),
                                countChilds = $(this).attr('data-count'),
                                i = 1;
                            $.each(childs, function() {
                                if (i < countChilds) {
                                    let attrId = $(this).find('td:eq(0)').attr('data-value');
                                    if (attrId == '') {
                                        error = 'Вы должны указать Sub-ID товара!';
                                    } else if (attrId == 0) {
                                        error = 'Sub-ID товара не может равняться нулю!';
                                    } else if (isNaN(attrId)) {
                                        error = 'Некорректное значение Sub-ID товара!';
                                    }
                                }
                                i++;
                            });
                        }
                        if (error) return false;
                    }
                }
            });

            let comment = form.find('#ga-comment').val().trim();
            if (comment != '') {
                if (comment.length > 200) {
                    error = 'Комментарий должен быть в пределах 200 символов!';
                }
            } 

            let supplier = form.find('#ga-supplier').val().trim();
            if (supplier == '') {
                error = 'Укажите поставщика!';
            } else if (isNaN(supplier)) {
                error = 'Поставщик указан неправильно!';
            }

            if (error) {
                btn.addClass('disabled'); return error;
            } else {
                btn.removeClass('disabled'); return false;
            }

        }

        form.on('keyup change', function() {
            checkFields();
        });

        form.on('submit', function(e){
            let error = checkFields();
            if (error) {
                if (!$('.modal-window-content div').is('.error')) {
                    $('.modal-window-content').last().prepend('<div class="error"></div>');
                    $('.error').text(error).show();
                }
            } else {
                let data = $(this).serializeArray(),
                    products = [],
                    tableItems = $('#ga-products tbody').find('tr');
                $.each(tableItems, function() {
                    if ($(this).attr('data-role') == 'parent') {
                        let id = $(this).find('td[data-name="id"]').text(),
                            count = $(this).find('td[data-name="count"]').text(),
                            price = $(this).find('td[data-name="price"]').text(),
                            attributes = [];
                        if ($(this).find('td:eq(1)').text() != '') {
                            attributes.push($(this).find('td:eq(1)').attr('data-value'));
                        }
                        if ($(this).attr('data-count') > 1) {
                            let childs = $(this).nextAll('tr[data-role="child"]'),
                                countChilds = $(this).attr('data-count'),
                                i = 1;
                            $.each(childs, function() {
                                if (i < countChilds) {
                                    if ($(this).find('td:eq(0)').text() != '') {
                                        attributes.push($(this).find('td:eq(0)').attr('data-value'));
                                    }
                                }
                                i++;
                            });
                        }
                        products.push({
                            'id_item': id,
                            'count': count,
                            'price': price,
                            'attributes': attributes
                        });
                    }
                });
                data.push({ name: "products", value: JSON.stringify(products) });
                $.ajax({ 
                    type: "POST",
                    url: "/system/ajax/addGA.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response),
                            count_modal = $('.modal-window-wrapper').length;
                        if (jsonData.success == 1) {
                            loadGA();
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content div').is('.error')) {
                                $('.modal-window-content').last().prepend('<div class="error"></div>');
                                $('.error').text(jsonData.error).show();
                            }
                        }
                    }
                });
                
            }
            return false;
        });
    });

    function addProductInTable() {
        showModalWindow('Выбор товара', '/system/ajax/addProductInTable.php?table=ga-products');
    }

    
    function removeProductItem(event){
        if (typeof(event) == "object") {
            // Убираем нижнюю границу
            let prevlastItem = $(event).closest("tr").prev('tr'),
                lastItems = $(event).closest("tr").nextAll('tr[data-role="parent"]');
            if (prevlastItem.attr('data-role') == 'child' && $(event).closest("tr").attr('data-role') == 'parent') {
                let prevParentItems = prevlastItem.prevAll('tr[data-role="parent"]');
                $.each(prevParentItems, function() {
                    if (lastItems.length == 0) { // Если это последняя строка
                        $(this).children('td:nth-child(1), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7), td:nth-child(8)').css('border-bottom', 'none');
                    } else if (lastItems.length > 0) {
                        let countParents = 0;
                        $.each(lastItems, function() {
                                countParents++;
                        });
                        if (countParents == 0) { // Если больше нет родительских строк
                            $(this).children('td:nth-child(1), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7), td:nth-child(8)').css('border-bottom', 'none');
                        }
                    }
                    return false;
                });
            } else if (prevlastItem.attr('data-role') == 'parent' && $(event).closest("tr").attr('data-role') == 'parent') {
                if (lastItems.length == 0) {
                    prevlastItem.children('td:nth-child(1), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7), td:nth-child(8)').css('border-bottom', 'none');
                }
            }
            
            // Удаляем строки
            if ($(event).closest("tr").attr("data-role") == 'parent') {
                let countItems = $(event).closest("tr").attr("data-count"),
                    removeItems = $(event).closest("tr").nextAll("tr[data-role=\"child\"]"),
                    i = 1;
                $.each(removeItems, function(index, value) {
                    if (i < countItems) {
                        value.remove();
                    } else {
                        return false;
                    }
                    i++;
                });    
            }

            $(event).closest("tr").remove();

            let count = $(event).closest("tr").find('td[data-name="count"]').text(),
                amount = $(event).closest("tr").find('td[data-name="amount"]').text(),
                new_count = Number($('#ga-products tfoot').find('td:eq(1) span').text()) - Number(count),
                new_amount = Number($('#ga-products tfoot').find('td:eq(2) span').text()) - Number(amount);

            $('#ga-products tfoot').find('td:eq(1) span').text(new_count);
            $('#ga-products tfoot').find('td:eq(2) span').text(new_amount.toFixed(2));

            if ($('#ga-products tbody').find('tr').length == 0) {
                $('#ga-products tbody').append('<tr data-id="0"><td colspan="8" style="padding: 15px 0"><span style="color: #900; font-size: 14px; font-weight: 700">Нет товара</span></td></tr>');
                $('#button-add-ga').addClass('disabled');
            }
            
        } else {
            return false;
        }
    }

    function viewProductInfo(id){
        if (!id) return false;
        showModalWindow('Информация о товаре', '/system/ajax/viewProductInfo.php?product_id=' + id);
    }
</script>
        <form id="add-ga" method="post">
            <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Конфигурация</div>
                    <div class="modal-window-content__value">
                        <span>Поставщик</span> <i class="fa fa-truck"></i> <select id="ga-supplier" name="supplier" class="chosen-select">
<?
$suppliers = $db->query("SELECT COUNT(*) FROM `suppliers` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
if ($suppliers[0] == 0) {
?>
                                            <option value="">- Нет поставщиков -</option>
<?
} else {
    $suppliers = $db->query("SELECT `id_item`, `name` FROM `suppliers` WHERE `client_id` = '" . $chief['id'] . "'");
?>
                                            <option value="">- Не указано -</option>
<?
    while ($supplier = $suppliers->fetch_assoc()) {
?>
                                            <option value="<?=$supplier['id_item']?>"> <?=protection($supplier['name'], 'display')?></option>
<?
    }
}
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Описание</span> <i class="fa fa-comment"> </i> <textarea id="ga-comment" name="comment" style="height: 190px"></textarea>
                    </div>
                </div>
            
                <div class="modal-window-content__item" style="width: auto; max-height: 400px">
                    <div class="modal-window-content__title">Товар</div>
                    <div class="modal-window-content__table">
                        <table id="ga-products" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>id</th>
                                    <th>sub_id</th>
                                    <th>sub_name</th>
                                    <th>Товар</th>
                                    <th>Цена</th>
                                    <th>Кол-во</th>
                                    <th>Итого</th>
                                    <th><i class="fa fa-remove"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-id="0">
                                    <td colspan="8" style="padding: 15px 0">
                                        <span style="color: #900; font-size: 14px; font-weight: 700">Нет товара</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td align="left" colspan="5"><a href="javascript:void(0);" onclick="addProductInTable();"><i class="fa fa-plus-circle"></i> <span class="dashed">Добавить товар</span></a> <span class="f-r">Всего:</span</td>
                                    <td align="right" id="ga-products-count"><span>0</span></td>
                                    <td id="ga-products-amount" colspan="2" style="border-right: none;"><span style="color: #900; font-size: 14px">0.00</span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            
            </div>

            <div class="buttons">
                <button id="button-add-ga" class="disabled form__button">Добавить</button>
            </div>
            <input type="submit" style="display: none">
        </form>