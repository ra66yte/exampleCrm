<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $country =   isset($_POST['country']) ? abs(intval($_POST['country'])) : null;
    $customer =  isset($_POST['customer']) ? protection($_POST['customer'], 'base') : null;
    $phone =     isset($_POST['phone']) ? protection($_POST['phone'], 'base') : null;
    $email =     isset($_POST['email']) ? protection($_POST['email'], 'base') : null;
    $office =    isset($_POST['office_id']) ? protection($_POST['office_id'], 'int') : null;
    $status =    isset($_POST['status']) ? abs(intval($_POST['status'])) : null;
    $reason =    isset($_POST['reason_renouncement']) ? abs(intval($_POST['reason_renouncement'])) : 0;
    $payment =   isset($_POST['payment_method']) ? abs(intval($_POST['payment_method'])) : null;
    $comment =   isset($_POST['comment']) ? protection($_POST['comment'], 'base') : null;
    $delivery =  isset($_POST['delivery_method']) ? abs(intval($_POST['delivery_method'])) : null;
    $ttn =       isset($_POST['ttn']) ? protection($_POST['ttn'], 'base') : null;
    $address =   isset($_POST['delivery_address']) ? protection($_POST['delivery_address'], 'base') : null;
    $departure = empty($_POST['departure_date']) ? 0 : $_POST['departure_date'];
    $employee =  isset($_POST['employee']) ? abs(intval($_POST['employee'])) : null;
    $ip =        getIp();
    $site =      isset($_POST['site']) ? protection($_POST['site'], 'base') : null;
    $add_1 =     isset($_POST['add_1']) ? protection($_POST['add_1'], 'base') : null;
    $add_2 =     isset($_POST['add_2']) ? protection($_POST['add_2'], 'base') : null;
    $add_3 =     isset($_POST['add_3']) ? protection($_POST['add_3'], 'base') : null;
    $add_4 =     isset($_POST['add_4']) ? protection($_POST['add_4'], 'base') : null;
    $products =  isset($_POST['products']) ? json_decode($_POST['products'], true) : null;
    $add_sale_products = isset($_POST['add_sale_products']) ? json_decode($_POST['add_sale_products'], true) : null;

    $total_amount = 0;

    if (is_array($add_sale_products) and $add_sale_products) {
        foreach ($add_sale_products as $add_sale_product) {
            $id = abs(intval($add_sale_product['id']));
            $count = abs(intval($add_sale_product['count']));
            $price = abs(floatval($add_sale_product['price']));
            $total_amount += number_format(($count * $price), 2, '.', '');
            $attributes = $add_sale_product['attributes'];
    
            if (is_array($attributes) and count($attributes) > 0) {
                if (($count_attributes = $db->query("SELECT COUNT(*) FROM `products_sub-id` WHERE `product_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row()) AND $count_attributes[0] == 0) {
                    $error = 'У товара нет Sub-ID!';
                } elseif ($count_attributes[0] != count($attributes)) {
                    $error = 'Произошла ошибка при выборе Sub-ID для товара! [e:1]';
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
    }

    if (is_array($products) and $products) {
        foreach ($products as $product) {
            $id = abs(intval($product['id']));
            $count = abs(intval($product['count']));
            $price = abs(floatval($product['price']));
            $total_amount += number_format(($count * $price), 2, '.', '');
            $attributes = $product['attributes'];
    
            if (is_array($attributes) and count($attributes) > 0) {
                if (($count_attributes = $db->query("SELECT COUNT(*) FROM `products_sub-id` WHERE `product_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row()) and $count_attributes[0] == 0) {
                    $error = 'У товара нет Sub-ID!';
                } elseif ($count_attributes[0] != count($attributes)) {
                    $error = 'Произошла ошибка при выборе Sub-ID для товара! [e:3]';
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
                            $error = 'Произошла ошибка при выборе Sub-ID для товара! [e:4]';
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
    
    if (!empty($add_4)) {
        if (mb_strlen($add_4, 'UTF-8') > 60) {
            $error = 'Дополнительной поле №4 должно содержать не больше 60 символов!';
        }
    }

    if (!empty($add_3)) {
        if (mb_strlen($add_3, 'UTF-8') > 60) {
            $error = 'Дополнительной поле №3 должно содержать не больше 60 символов!';
        }
    }

    if (!empty($add_2)) {
        if (mb_strlen($add_2, 'UTF-8') > 60) {
            $error = 'Дополнительной поле №2 должно содержать не больше 60 символов!';
        }
    }

    if (!empty($add_1)) {
        if (mb_strlen($add_1, 'UTF-8') > 60) {
            $error = 'Дополнительной поле №1 должно содержать не больше 60 символов!';
        }
    }

    if (!empty($site)) {
        // smtng
    }

    if ($employee == 0) {
        $error = 'Укажите сотрудника!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `staff` WHERE `employee_id` = '" . $employee . "' AND `chief_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Сотрудник не найден!';
    }

    if (isset($departure) and $departure != 0) {
        $date = date_create_from_format('d-m-Y H:i:s', $departure);
        $date = date_format($date, 'Y-m-d H:i:s');
        $departure = strtotime($date);
    }

    if (!empty($address)) {
        if (mb_strlen($address, 'UTF-8') > 200) {
            $error = 'Адрес должен содержать не больше 200 символов!';
        }
    }

    if (!empty($ttn)) {
        // smtng
    }

    if ($delivery != 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Способ доставки не найден!';
        }
    } else {
        $delivery = 1;
    }

    if (!empty($comment)) {
        if (mb_strlen($comment, 'UTF-8') > 512) {
            $error = 'Комментарий должен быть в пределах 512 символов!';
        }
    }

    if ($payment != 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Способ оплаты не найден!';
        }
    } else {
        $payment = 1;
    }

    $reasons = [0, 1, 2, 3, 4];
    if (!in_array($reason, $reasons)) {
        $error = 'Причина отказа указана неправильно!';
    }

    $right_id = getAccessID('statuses');
    if ($status == 0) {
        $error = 'Укажите статус заказа!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `status_order` INNER JOIN `group_rights` ON (`status_order`.`id_item` = `group_rights`.`value`) WHERE `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`access_right` = '" . $right_id . "' AND `status_order`.`status` = 'on' AND `status_order`.`id_item` = '" . $status . "' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Статус заказов не найден!'; // ToDo
    }

    if (empty($office)) {
        $error = 'Укажите отдел!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `id_item` = '" . $office . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Отдел не найден!';
    }

    if (!empty($email)) {
        if (mb_strlen($email) < 6 or mb_strlen($email) > 64) {
            $error = 'E-mail адрес должен быть в пределах от 6 до 64 символов!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail адрес указан неверно!';
        }
    }

    if (empty($phone)) {
        $error = 'Укажите номер телефона!';
    }

    if (empty($customer)) {
        $error = 'Укажите заказчика!';
    } elseif (mb_strlen($customer, 'UTF-8') > 64) {
        $error = 'Поле "Заказчик" должно содержать не больше 64 символов!';
    }

    if ($country != 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `countries` WHERE `id` = '" . $country . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Страна не найдена!';
        }
    }

    if (!isset($error)) {
        $count = $db->query("SELECT `orders` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['orders'] + 1;
        $mt = explode(' ', microtime());
        $id_order = substr(((int) $mt[1]) * 100 + ((int) round($mt[0] * 100)), 0, -1);

        if ($result = $db->query("SELECT COUNT(*) FROM `orders` WHERE `id_item` = '" . $id_item . "' AND `id_order` = '" . $id_order . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) { // Если заказов с такими id нет
            $sql = "INSERT INTO `orders` (`id`, `id_item`, `id_order`, `client_id`, `customer`, `phone`, `email`, `office_id`, `status`, `reason`, `payment_method`, `comment`, `delivery_method`, `ttn`, `delivery_address`, `departure_date`, `employee`, `site`, `ip`, `country`, `add_1`, `add_2`, `add_3`, `add_4`, `amount`, `date_added`, `updated`) VALUES (null, '" . $id_item . "',  '" . $id_order . "', '" . $chief['id'] . "', '" . $customer . "', '" . $phone . "', '" . $email . "', '" . $office . "', '" . $status . "', '" . $reason . "', '" . $payment . "', '" . $comment . "', '" . $delivery . "', '" . $ttn . "', '" . $address . "', '" . $departure . "', '" . $employee . "', '" . $site . "', '" . ip2long($ip) . "', '" . $country . "', '" . $add_1 . "', '" . $add_2 . "', '" . $add_3 . "', '" . $add_4 . "', '" . $total_amount . "', '" . $data['time'] . "', '" . $data['time'] . "')"; // ToDo: При добавлении через crm ставим updated равным date_added, но не отображаем его и понимаем, что этот заказ не надо метить как новый. При добавлении заказа через api crm: updated должен быть пустым, тем самым понимаем что это новый заказ и отмечаем его.
            
            if ($db->query($sql)) {
                $insert_order_id = $id_item;
                // Основные товары
                if (is_array($products) and $products) {
                    $has_attributes = null; // Если будут атрибуты
                    $insert_orders_attributes = "INSERT INTO `orders_attributes` (`id`, `client_id`, `order_id`, `orders_product_id`, `product_id`, `attribute_id`) VALUES"; // Добавляем атрибуты к товарам прихода
                    foreach ($products as $product) {
                        $id = abs(intval($product['id']));
                        $count = abs(intval($product['count']));
                        $price = abs(floatval($product['price']));
                        $amount = number_format(($count * $price), 2, '.', '');
                        $attributes = $product['attributes'];

                        // Добавляем товары заказа
                        $db->query("INSERT INTO `orders_products` (`id`, `client_id`, `order_id`, `product_id`, `count`, `price`, `amount`) VALUES (null, '" . $chief['id'] . "', '" . $insert_order_id . "', '" . $id . "', '" . $count . "', '" . $price . "', '" . $amount . "')");
                        $last_orders_product_id = $db->insert_id; // Если будут атрибуты

                        // Если есть атрибуты
                        if (is_array($attributes) and count($attributes) > 0) {
                            $has_attributes = true;

                            foreach ($attributes as $attribute) {
                                $insert_orders_attributes .= " (null, '" . $chief['id'] . "', '" . $insert_order_id . "', '" . $last_orders_product_id . "', '" . $id . "', '" . abs(intval($attribute)) . "'),";
                            }

                        }
                    }
                    if (isset($has_attributes)) $db->query(rtrim($insert_orders_attributes, ','));
                }
                // Допродажа
                if (is_array($add_sale_products) and $add_sale_products) {
                    $has_attributes = null; // Если будут атрибуты
                    $insert_add_sale_attributes = "INSERT INTO `add_sales_attributes` (`id`, `client_id`, `order_id`, `add_sales_product_id`, `product_id`, `attribute_id`) VALUES"; // Добавляем атрибуты к товарам допродажи
                    foreach ($add_sale_products as $add_sale_product) {
                        $id = abs(intval($add_sale_product['id']));
                        $count = abs(intval($add_sale_product['count']));
                        $price = abs(floatval($add_sale_product['price']));
                        $amount = number_format(($count * $price), 2, '.', '');
                        $attributes = $add_sale_product['attributes'];

                        // Добавляем товары допродажи
                        $db->query("INSERT INTO `add_sales_products` (`id`, `client_id`, `order_id`, `product_id`, `count`, `price`, `amount`) VALUES (null, '" . $chief['id'] . "', '" . $insert_order_id . "', '" . $id . "', '" . $count . "', '" . $price . "', '" . $amount . "')");
                        $last_add_sale_product_id = $db->insert_id; // Если будут атрибуты

                        // Если есть атрибуты
                        if (is_array($attributes) and count($attributes) > 0) {
                            $has_attributes = true;

                            foreach ($attributes as $attribute) {
                                $insert_add_sale_attributes .= " (null, '" . $chief['id'] . "', '" . $insert_order_id . "', '" . $last_add_sale_product_id . "', '" . $id . "', '" . abs(intval($attribute)) . "'),";
                            }

                        }
                    }
                    if (isset($has_attributes)) $db->query(rtrim($insert_add_sale_attributes, ','));
                }
                $db->query("UPDATE `id_counters` SET `orders` = (`orders` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось добавить заказ!';
            }
        } else {
            $error = 'Произошла ошибка при добавлении заказа! Попробуйте еще раз.';
        }
        
        if (!isset($error)) $success = 1;
    }
    echo json_encode(array('success' => $success, 'error' => $error, 'data' => array('item_id' => $insert_order_id, 'status' => $status)));
    exit;
}

?>
<style>
    .ui-datepicker {
        z-index: 1020 !important;
    }
</style>
<script>
    $(function(){
        let form = $('#add-order'),
            btn = $('#button-add-order');

        $('#add-sale').on('click', function(){
            if ($(this).is(':checked')) {
                let productsTable = $('#order-products tbody').find('tr:eq(0)');
                if (productsTable.attr('data-id') == 0) {
                    showModalWindow(null, null, 'error', 'Добавьте основной товар в заказ!');
                    $(this).prop('checked', false);
                } else {
                    $('#add-sale-block').show();
                    $('#block-products').prepend('<div class="disable"></div>');
                    $('#state-add-sale').hide();
                }
                
            } else {
                
                $('#add-sale-block').hide();
                $('#state-add-sale').show();
                $('#block-products').find('.disable').remove();
                $('#add-sale-products tbody').html('<tr data-id="0"><td colspan="8" style="padding: 15px 0"><span style="color: #900; font-size: 14px; font-weight: 700">Нет товара</span></td></tr>');
                $('#add-sale-products').find('#add-sale-products-count span').text('0');
                let addSaleAmount = Number($('#add-sale-products').find('#add-sale-products-amount span').text()),
                    totalAmount = Number($('.modal-window-content__amount').find('span').text());
    
                $({numberValue: totalAmount}).animate({numberValue: totalAmount - addSaleAmount}, {
                    duration: 250,
                    step: function() { 
                        $('.modal-window-content__amount').find('span').text(this.numberValue.toFixed(2)); 
                    },
                    complete: function() {
                        $('.modal-window-content__amount').find('span').text((totalAmount - addSaleAmount).toFixed(2));
                    }
                });

                $('#add-sale-products').find('#add-sale-products-amount span').text('0.00');
            }
        });

        $('#order-products tbody, #add-sale-products tbody').on({
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
        
        // цена
        $('#order-products, #add-sale-products').on('dblclick', 'tbody tr td:nth-child(5)', function(e) {
            let price = $(this).text(),
                width = $(this).css('width'),
                height = $(this).css('height');
            $(this).css('padding', '0px');
            $(this).html('<input id="product-price" type="text" name="price" style="width: ' + width + '; height: ' + height + '">');
            $('#product-price').focus().val(price).select();
        });

        // Количество
        $('#order-products, #add-sale-products').on('dblclick', 'tbody tr td:nth-child(6)', function(e) {
            let count = $(this).text(),
                width = $(this).css('width'),
                height = $(this).css('height');
            $(this).css('padding', '0px');
            $(this).html('<input id="product-count" type="text" name="count" style="width: ' + width + '; height: ' + height + '">');
            $('#product-count').focus().val(count).select();
        });

        $('#order-products, #add-sale-products').on('blur', '#product-price', function(e){
            let price = parseFloat($(this).val()).toFixed(2),
                parent = $(this).parent();
            parent.css('padding', '3px');
            if (isNaN(price)) price = parseFloat(0.00).toFixed(2);
            parent.html(price);

            changeProperties();
            checkFields();
        });
        $('#order-products, #add-sale-products').on('blur', '#product-count', function(e){
            let count = Number($(this).val());
                parent = $(this).parent();
            parent.css('padding', '3px');
            if (isNaN(count)) count = 0;
            parent.html(count);

            changeProperties();
            checkFields();
        });

        form.on('change', 'select[name="status"]', function(){
            let value = $(this).val();
            if (value === '5') { // Отказ
                $('#renouncement-reason').prop('disabled', false);
                $('#renouncement-reason').trigger('chosen:updated');
            } else {
                $('#renouncement-reason').prop('disabled', true);
                $('#renouncement-reason').trigger('chosen:updated');
            }
        });

        form.on('keyup change', function() {
            checkFields();
        });

        form.on('keypress', '#product-price, #product-count', function(e) {
            if (e.keyCode == 13) {
                $(this).hide(); // Велосипед))
                return false;
            }
        });

        form.on('submit', function(e){
            let error = checkFields();
            if (error) {
                if (!$('.modal-window-content').last().is('.error')) {
                    $('.modal-window-content').last().prepend('<div class="error"></div>');
                }
                $('.error').last().text(error).show();
            } else {
                let data = $(this).serializeArray(),
                    products = [],
                    addSaleProducts = [],
                    tableItems = $('#order-products tbody').find('tr'),
                    addSaleItems = $('#add-sale-products tbody').find('tr');

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
                            'id': id,
                            'count': count,
                            'price': price,
                            'attributes': attributes
                        });
                    }
                });

                $.each(addSaleItems, function() {
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
                        addSaleProducts.push({
                            'id': id,
                            'count': count,
                            'price': price,
                            'attributes': attributes
                        });
                    }
                });

                data.push({ name: "products", value: JSON.stringify(products) });

                if (addSaleProducts.length > 0) {
                    data.push({ name: "add_sale_products", value: JSON.stringify(addSaleProducts) });
                }

                $.ajax({ 
                    type: "POST",
                    url: "/system/ajax/addOrder.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response),
                            data = jsonData.data,
                            count_modal = $('.modal-window-wrapper').length;

                        if (jsonData.success == 1) {
                            
                            let wsData = {
                                action: 'add item',
                                data: {
                                    itemId: data.item_id,
                                    location: 'orders'
                                }
                            }

                            sendMessage(ws, JSON.stringify(wsData));

                            TabStatus(getParameterByName('status'), 1);
                            closeModalWindow(count_modal);

                        } else {
                            if (!$('.modal-window-content').last().is('.error')) {
                                $('.modal-window-content').last().prepend('<div class="error"></div>');
                            }
                            $('.error').last().text(jsonData.error).show();
                        }
                    }
                });

            }
            return false;
        });

        function checkFields() {
            let error;

            let addSale = $('#add-sale'),
                addSaleItems = $('#add-sale-products tbody').find('tr');

            if (addSale.is(':checked')) {
                $.each(addSaleItems, function() {
                    if ($(this).attr('data-id') == 0) {
                        error = 'Добавьте товар для допродажи!';
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
                            }
                            /*
                            else if (tdPrice == 0) {
                                error = 'Цена товара(ов) должна быть больше ноля!';
                            }
                            */
                             else if (isNaN(tdPrice)) {
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
            }

            let tableItems = $('#order-products tbody').find('tr');
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
                        }
                        /*
                         else if (tdPrice == 0) {
                            error = 'Цена товара(ов) должна быть больше ноля!';
                        }
                        */
                         else if (isNaN(tdPrice)) {
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

            let add_1 = $('#order-add-1').val().trim(),
                add_2 = $('#order-add-2').val().trim(),
                add_3 = $('#order-add-3').val().trim(),
                add_4 = $('#order-add-4').val().trim();
            
            if (add_4 != '') {
                if (add_4.length > 60) {
                    error = 'Дополнительное поле №4 должно содержать не больше 60 символов!';
                }
            }

            if (add_3 != '') {
                if (add_3.length > 60) {
                    error = 'Дополнительное поле №3 должно содержать не больше 60 символов!';
                }
            }

            if (add_2 != '') {
                if (add_2.length > 60) {
                    error = 'Дополнительное поле №2 должно содержать не больше 60 символов!';
                }
            }

            if (add_1 != '') {
                if (add_4.length > 60) {
                    error = 'Дополнительное поле №1 должно содержать не больше 60 символов!';
                }
            }

            let employee = $('#order-employee').val().trim();
            if (isNaN(employee)) {
                error = 'Сотрудник указан неправильно!';
            }

            let address = $('#order-address').val().trim();
            if (address != '') {
                if (address.length > 60) {
                    error = 'Адрес доставки должен быть в пределах 60 символов!';
                }
            }

            let ttn = $('#order-ttn').val().trim();
            if (ttn != '') {

            }

            let delivery = $('#order-delivery').val().trim();
            if (delivery != '') {
                if (isNaN(delivery)) {
                    error = 'Способ доставки указан неправильно!';
                }
            }
            
            let comment = $('#order-comment').val().trim();
            if (comment != '') {
                if (comment.length > 512) {
                    error = 'Комментарий должен быть в пределах 512 символов!';
                }
            }

            let payment = $('#order-payment').val().trim();
            if (payment != '') {
                if (isNaN(payment)) {
                    error = 'Способ оплаты указан неправильно!';
                }
            }

            let status = $('#order-status').val().trim();
            if (status == '') {
                error = 'Укажите статус заказа!';
            } else if (isNaN(status)) {
                error = 'Статус заказа указан неправильно!';
            }

            let email = $('#order-email').val().trim();
            if (email != '') {
                if (email.length < 6 || email.length > 64) {
                    error = 'E-mail должен быть в пределах от 6 до 64 символов!';
                }
            }

            let phone = $('#order-phone').val().trim();
            if (phone == '') {
                error = 'Укажите номер телефона заказчика!';
            }
            
            let customer = $('#order-customer').val().trim();
            if (customer == '') {
                error = 'Укажите заказчика!';
            }

            let country = $('#order-country').val().trim();
            if (country != '') {
                if (isNaN(country)) {
                    error = 'Страна указана неправильно!';
                }
            }

            if (error) {
                btn.addClass('disabled');
                return error;
            } else {
                btn.removeClass('disabled');
                return false;
            }
        }

        $('#order-departure').datetimepicker({
            dateFormat: "dd-mm-yy",
            timeFormat: "HH:mm:ss",
            showSecond: true,
            beforeShow: function(input) {
                $(input).prop('readonly', true);
            }
        });
    });


    function addProductInTable(table){
        if (!table) return false;
        showModalWindow('Выбор товара', '/system/ajax/addProductInTable.php?table=' + table + '&location=order');
    }

    function viewProductInfo(id){
        if (!id) return false;
        showModalWindow('Информация о товаре', '/system/ajax/viewProductInfo.php?product_id=' + id);
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

            let tbody = $(event).closest("tr").parent(),
                tfoot = $(event).closest("tr").parent().next(),
                count = $(event).closest("tr").find('td[data-name="count"]').text(),
                amount = $(event).closest("tr").find('td[data-name="amount"]').text(),
                new_count = Number(tfoot.find('td:eq(1) span').text()) - Number(count),
                new_amount = Number(tfoot.find('td:eq(2) span').text()) - Number(amount);

            $(event).closest("tr").remove();

            tfoot.find('td:eq(1) span').text(new_count);
            tfoot.find('td:eq(2) span').text(new_amount.toFixed(2));

            let totalAmount = Number($('.modal-window-content__amount').find('span').text());
            // $('.modal-window-content__amount').find('span').text((totalAmount - amount).toFixed(2));
 
            $({numberValue: totalAmount}).animate({numberValue: totalAmount - amount}, {
                duration: 250,
                step: function() { 
                    $('.modal-window-content__amount').find('span').text(this.numberValue.toFixed(2)); 
                },
                complete: function() {
                    $('.modal-window-content__amount').find('span').text((totalAmount - amount).toFixed(2));
                }
            });

            if (tbody.children('tr').length == 0) {
                tbody.append('<tr data-id="0"><td colspan="8" style="padding: 15px 0"><span style="color: #900; font-size: 14px; font-weight: 700">Нет товара</span></td></tr>');
                $('#button-add-order').addClass('disabled');
            }
            
        } else {
            return false;
        }
    }

    function changeProperties() {
        let products = $('#order-products'),
            addSales = $('#add-sale-products'),
            totalAmount = 0.00,
            oldAmount = $('#total-amount').text();
        
        let productsAmount = 0.00,
            productsCount = 0;
        $.each(products.find('tbody tr[data-role="parent"]'), function(value) {
            let price = parseFloat($(this).children('td:eq(4)').text()).toFixed(2),
                count = Number($(this).children('td:eq(5)').text()),
                amount = (price * count);
            $(this).find('td:eq(6)').text(parseFloat(amount).toFixed(2));
            productsAmount += amount;
            productsCount += count;
        });
        $('#order-products-count').html('<span>' + productsCount + '</span>');
        $('#order-products-amount').html('<span style="color: #900; font-size: 14px">' + parseFloat(productsAmount).toFixed(2) + '</span>');

        let addSalesAmount = 0.00,
            addSalesCount = 0;
        $.each(addSales.find('tbody tr[data-role="parent"]'), function(value) {
            let price = parseFloat($(this).children('td:eq(4)').text()).toFixed(2),
                count = Number($(this).children('td:eq(5)').text()),
                amount = (price * count);
            $(this).find('td:eq(6)').text(parseFloat(amount).toFixed(2));
            addSalesAmount += amount;
            addSalesCount += count;
        });
        $('#add-sale-products-count').html('<span>' + addSalesCount + '</span>');
        $('#add-sale-products-amount').html('<span style="color: #900; font-size: 14px">' + parseFloat(addSalesAmount).toFixed(2) + '</span>');

        totalAmount = (productsAmount + addSalesAmount);
        let differenceAmount = (oldAmount - totalAmount);
        $({numberValue: oldAmount}).animate({numberValue: oldAmount - differenceAmount}, {
            duration: 250,
            step: function() { 
                $('.modal-window-content__amount').find('span').text(parseFloat(this.numberValue).toFixed(2)); 
            },
            complete: function() {
                $('.modal-window-content__amount').find('span').text(parseFloat(oldAmount - differenceAmount).toFixed(2));
            }
        });
    }
</script>
        <form id="add-order" method="post" autocomplete="off" spellcheck="false">
            <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Контактная информация</div>
                    <div class="modal-window-content__value">
                        <span>Страна</span> <i class="fa fa-flag"></i> <select id="order-country" name="country" class="chosen-select">
                            <option value="">Все</option>
<?
    $countries = $db->query("SELECT `countries`.`id`, `countries`.`name`, `countries`.`code` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "' ORDER BY `id`");
    while ($country = $countries->fetch_assoc()) {
?>
                        <option value="<?=$country['id']?>" data-img-src="/img/countries/<?=strtolower($country['code'])?>.png"<?=($chief['country'] === $country['id'] ? ' selected' : '')?>><?=protection($country['name'] . ' (' . $country['code'] . ')', 'display')?></option>
<?
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Заказчик</span> <i class="fa fa-male"></i> <input id="order-customer" type="text" name="customer">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Телефон</span> <i class="fa fa-phone"></i> <input id="order-phone" type="text" name="phone">
                    </div>
                    <div class="modal-window-content__value">
                        <span>E-mail</span> <i class="fa fa-at"></i> <input id="order-email" type="text" name="email">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Отдел</span> <i class="fa fa-building"></i> <select id="user-office" name="office_id" class="chosen-select">
<?
$offices = $db->query("SELECT `id_item`, `name` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'");
while ($office = $offices->fetch_assoc()) {
?>
                            <option value="<?=$office['id_item']?>"><?=protection($office['name'], 'display')?></option>
<?
}
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Статус заказа</span> <i class="fa fa-magic"></i> <select id="order-status" name="status" class="chosen-select">
                            <option value="">- Не указано -</option>
                            <option disabled>----------------------------------------</option>
<?
    $statuses = $db->query("SELECT `id_item`, `name`, `color`, `status` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "'");
    while ($status = $statuses->fetch_assoc()) {
?>
                            <option data-id="<?=$status['id']?>" data-img-src="/getImage/?color=<?=str_replace('#', '', $status['color'])?>" value="<?=$status['id_item']?>"<?=($status['status'] == 'off' ? ' disabled' : '')?>><?=protection($status['name'], 'display')?></option>
<?
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Причина отказа</span> <i class="fa fa-question-circle"></i> <select id="renouncement-reason" name="reason_renouncement" class="chosen-select" disabled>
                            <option value="">- Не указано -</option>
                            <option value="1">Дублирующая заявка</option>
                            <option value="2">Клиент отказался или передумал</option>
                            <option value="3">Клиент не оставлял заявку</option>
                            <option value="4">Некорректный номер телефона</option>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Способ оплаты</span> <i class="fa fa-money"></i> <select id="order-payment" name="payment_method" class="chosen-select">
                            <option value="">- Не указано -</option>
                            <option disabled>----------------------------------------</option>
<?
    $payment_methods = $db->query("SELECT `id_item`, `name`, `icon` FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "'");
    $i = 0;
    while ($payment_method = $payment_methods->fetch_assoc()) {
?>
                            <option data-img-src="/system/images/payment/<?=protection($payment_method['icon'], 'display')?>"<?=($i == 0 ? ' selected' : '')?> value="<?=$payment_method['id_item']?>"><?=protection($payment_method['name'], 'display')?></option>
<?
        echo "\r\n";
        $i++;
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Комментарий</span> <i class="fa fa-comment"></i> <textarea id="order-comment" name="comment"></textarea>
                    </div>
                    <div class="modal-window-content__title">UTM метки</div>
                    <div class="modal-window-content__value">
                        <span>utm_source</span> <i class="fa fa-crosshairs"></i> <div class="modal-window-content__value-block"></div>
                    </div>
                    <div class="modal-window-content__value">
                        <span>utm_medium</span> <i class="fa fa-crosshairs"></i> <div class="modal-window-content__value-block"></div>
                    </div>
                    <div class="modal-window-content__value">
                        <span>utm_term</span> <i class="fa fa-crosshairs"></i> <div class="modal-window-content__value-block"></div>
                    </div>
                    <div class="modal-window-content__value">
                        <span>utm_content</span> <i class="fa fa-crosshairs"></i> <div class="modal-window-content__value-block"></div>
                    </div>
                    <div class="modal-window-content__value">
                        <span>utm_campaign</span> <i class="fa fa-crosshairs"></i> <div class="modal-window-content__value-block"></div>
                    </div>

                    
                </div>
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Доставка</div>
                    <div class="modal-window-content__value">
                        <span>Способ</span> <i class="fa fa-truck"></i> <select id="order-delivery" name="delivery_method" class="chosen-select">
                            <option value="">- Не указано -</option>
                            <option disabled>----------------------------------------</option>
<?
    $delivery_methods = $db->query("SELECT `id_item`, `name`, `icon` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'");
    $i = 0;
    while ($delivery_method = $delivery_methods->fetch_assoc()) {
?>
                            <option data-img-src="/system/images/delivery/<?=protection($delivery_method['icon'], 'display')?>"<?=($i == 0 ? ' selected' : '')?>  value="<?=$delivery_method['id_item']?>"><?=protection($delivery_method['name'], 'display')?></option>
<?
        echo "\r\n";
        $i++;
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>ТТН</span> <i class="fa fa-file-text"></i> <div class="modal-window-content__value-ttn"><input id="order-ttn" type="text" name="ttn" style="width: 120px"></div>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Адрес</span> <i class="fa fa-map-marker"></i> <input id="order-address" type="text" name="delivery_address">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Отправлено</span> <i class="fa fa-calendar-check-o"></i> <input id="order-departure" type="text" name="departure_date">
                    </div>
                    <div class="modal-window-content__title">Служебная информация</div>
                    <div class="modal-window-content__value">
                        <span>Сотрудник</span> <i class="fa fa-user-circle"></i> <select id="order-employee" name="employee" class="chosen-select">
                            <option value="">- Не указано -</option>
<?
    $employees = $db->query("SELECT `id_item`, `name` FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = 0) OR `chief_id` = '" . $chief['id'] . "'");
    while ($employee = $employees->fetch_assoc()) {
?>
                            <option value="<?=$employee['id_item']?>"<?=($user['id_item'] == $employee['id_item'] ? ' selected' : '')?>><?=protection($employee['name'], 'display')?></option>
<?
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>IP</span> <i class="fa fa-desktop"></i> <div class="modal-window-content__value-block"><?=protection(getIp(), 'display')?></div>
                    </div>
                    <div class="modal-window-content__value">
                        <span>Сайт</span> <i class="fa fa-globe"></i> <input id="order-site" type="text" name="site" disabled>
                    </div>
                    <div class="modal-window-content__value">
                        <span>order_id</span> <i class="fa fa-tag"></i> <div class="modal-window-content__value-block">- <i class="fa fa-info-circle" style="color: #d4d4d4"></i> <span>от <?=view_time($data['time'])?></span></div>
                    </div>

                    <div class="modal-window-content__title">Дополнительно</div>
                    <div class="modal-window-content__value">
                        <span>Доп. поле 1</span> <i class="fa fa-plus"></i> <input id="order-add-1" type="text" name="add_1">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Доп. поле 2</span> <i class="fa fa-plus"></i> <input id="order-add-2" type="text" name="add_2">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Доп. поле 3</span> <i class="fa fa-plus"></i> <input id="order-add-3" type="text" name="add_3">
                    </div>
                    <div class="modal-window-content__value">
                        <span>Доп. поле 4</span> <i class="fa fa-plus"></i> <input id="order-add-4" type="text" name="add_4">
                    </div>
                </div>
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">Товар</div>
                    <div class="modal-window-content__table" id="block-products" style="position: relative">
                        <table id="order-products" cellpadding="0" cellspacing="0">
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
                                    <td align="left" colspan="5"><a href="javascript:void(0);" onclick="addProductInTable('order-products');"><i class="fa fa-plus-circle"></i> <span class="dashed">Добавить товар</span></a> <span class="f-r">Всего:</span</td>
                                    <td align="right" id="order-products-count"><span>0</span></td>
                                    <td id="order-products-amount" colspan="2" style="border-right: none;"><span style="color: #900; font-size: 14px">0.00</span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="modal-window-content__title">
                        <label class="toggle">
                            <input id="add-sale" type="checkbox" name="add_sale" class="toggle__input">
                            <div class="toggle__control"></div>
                        </label>
                        Допродажа
                    </div>

                    <div id="state-add-sale" class="modal-window-content__value" style="text-align: center; font-size: 14px; color: #757575">
                        Нет допродажи в заказе
                    </div>

                    <div class="modal-window-content__table" id="add-sale-block" style="display: none">
                        <table id="add-sale-products" cellpadding="0" cellspacing="0">
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
                                    <td align="left" colspan="5"><a href="javascript:void(0);" onclick="addProductInTable('add-sale-products');"><i class="fa fa-plus-circle"></i> <span class="dashed">Добавить товар</span></a> <span class="f-r">Всего:</span</td>
                                    <td align="right" id="add-sale-products-count"><span>0</span></td>
                                    <td id="add-sale-products-amount" colspan="2" style="border-right: none;"><span style="color: #900; font-size: 14px">0.00</span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-window-content__amount">Сумма заказа: <span id="total-amount">0.00</span></div>
            <div class="buttons">
                <button id="button-add-order" name="submit" class="disabled">Добавить</button>
            </div>
            <input type="submit" style="display: none">
        </form>
<?
/*
CREATE TABLE `orders_products` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `count` INT(11) NOT NULL,
    `amount` decimal(24,2) not null default '0.00',
    PRIMARY KEY(`id`)
)

CREATE TABLE `orders_attributes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `order_id` INT(11) NOT NULL,
    `orders_product_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `attribute_id` INT(11) NOT NULL,
    PRIMARY KEY(`id`)
)
CREATE TABLE `add_sales_products` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `count` INT(11) NOT NULL,
    `amount` decimal(24,2) not null default '0.00',
    PRIMARY KEY(`id`)
)

CREATE TABLE `add_sales_attributes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `order_id` INT(11) NOT NULL,
    `add_sales_product_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `attribute_id` INT(11) NOT NULL,
    PRIMARY KEY(`id`)
)
*/