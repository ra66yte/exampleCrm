<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;

    $order_id =        isset($_POST['order_id']) ? abs(intval($_POST['order_id'])) : null;
    $country =         isset($_POST['country']) ? abs(intval($_POST['country'])) : null;
    $customer =        isset($_POST['customer']) ? protection($_POST['customer'], 'base') : null;
    $phone =           isset($_POST['phone']) ? protection($_POST['phone'], 'base') : null;
    $email =           isset($_POST['email']) ? protection($_POST['email'], 'base') : null;
    $office =          isset($_POST['office_id']) ? protection($_POST['office_id'], 'int') : null;
    $status =          isset($_POST['status']) ? abs(intval($_POST['status'])) : null;
    $reason =          isset($_POST['reason_renouncement']) ? abs(intval($_POST['reason_renouncement'])) : 0;
    $payment =         isset($_POST['payment_method']) ? abs(intval($_POST['payment_method'])) : null;
    $comment =         isset($_POST['comment']) ? protection($_POST['comment'], 'base') : null;
    $delivery =        isset($_POST['delivery_method']) ? abs(intval($_POST['delivery_method'])) : null;
    $ttn =             isset($_POST['ttn']) ? protection($_POST['ttn'], 'base') : null;
    $address =         isset($_POST['delivery_address']) ? protection($_POST['delivery_address'], 'base') : null;
    $departure_date =  empty($_POST['departure_date']) ? null : $_POST['departure_date'];
    $employee =        isset($_POST['employee']) ? abs(intval($_POST['employee'])) : null;
    $ip =              getIp();
    $site =            isset($_POST['site']) ? protection($_POST['site'], 'base') : null;
    $add_1 =           isset($_POST['add_1']) ? protection($_POST['add_1'], 'base') : null;
    $add_2 =           isset($_POST['add_2']) ? protection($_POST['add_2'], 'base') : null;
    $add_3 =           isset($_POST['add_3']) ? protection($_POST['add_3'], 'base') : null;
    $add_4 =           isset($_POST['add_4']) ? protection($_POST['add_4'], 'base') : null;
    $products =        isset($_POST['products']) ? json_decode($_POST['products'], true) : null;
    $add_sale_products = isset($_POST['add_sale_products']) ? json_decode($_POST['add_sale_products'], true) : null;
    $add_sale =        isset($_POST['add_sale']) ? true : false;

    $total_amount = 0;
    
    if (is_array($add_sale_products) and $add_sale_products) {
        foreach ($add_sale_products as $add_sale_product) {
            $id = abs(intval($add_sale_product['id']));
            $count = abs(intval($add_sale_product['count']));
            $price = abs(floatval($add_sale_product['price']));
            $amount = number_format(($count * $price), 2, '.', '');
            $total_amount += $amount;
            $attributes = $add_sale_product['attributes'];
    
            if (is_array($attributes) and count($attributes) > 0) {
                if ($count_attributes = $db->query("SELECT COUNT(*) FROM `products_sub-id` WHERE `product_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $count_attributes[0] == 0) {
                    $error = '?? ???????????? ?????? Sub-ID!';
                } elseif ($count_attributes[0] != count($attributes)) {
                    $error = '?????????????????? ???????????? ?????? ???????????? Sub-ID ?????? ????????????! [e:1]';
                } else {
    
                    foreach ($attributes as $attribute) {
                        if (empty($attribute)) {
                            $error = '???????????????? Sub-ID ???? ?????????? ???????? ???????????? ?????? ?????????????????? 0!';
                        } elseif (!is_numeric($attribute)) {
                            $error = '???????????????????????? ???????????????? Sub-ID!';
                        } elseif ($result = $db->query("SELECT COUNT(*) FROM `attributes` INNER JOIN `products_sub-id` ON (`attributes`.`category_id` = `products_sub-id`.`attribute_category_id`) WHERE `products_sub-id`.`product_id` = '" . $id . "' AND `products_sub-id`.`client_id` = '" . $chief['id'] . "' AND `attributes`.`id_item` = '" . abs(intval($attribute)) . "' AND `attributes`.`client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                            $error = 'Sub-ID ???? ????????????!';
                        }
                        if (isset($error)) break;
                    }
    
                    // ???????????????? ?????????????????? ?????????????????????????????? ??????????????????
                    if (count($attributes) > 1) {
                        $in_attributes = implode(",", $attributes);
                        $categories_stmt = $db->query("SELECT `attribute_categories`.`id_item` FROM `attribute_categories` INNER JOIN `attributes` ON (`attribute_categories`.`id_item` = `attributes`.`category_id`) WHERE `attributes`.`id_item` IN ($in_attributes) AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `attribute_categories`.`client_id` = '" . $chief['id'] . "'");
                        $categories = array();
                        while ($category = $categories_stmt->fetch_assoc()) {
                            $categories[] = $category['id_item'];
                        }
                        if (count($attributes) != count(array_unique($categories))) {
                            $error = '?????????????????? ???????????? ?????? ???????????? Sub-ID ?????? ????????????! [e:2]';
                        }
                    }
    
                }
            }
    
            if ($price == 0) {
                $error = '???????????????????? ?????????????? ???????? ????????????!';
            }
    
            if ($count == 0) {
                $error = '???????????????????? ?????????????? ???????????????????? ????????????!';
            }
    
            if ($id == 0) {
                $error = '???????????????????? ?????????????? ??????????!';
            } elseif ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `id_item` = '" . $id . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                $error = '?????????? ???? ????????????!';
            }
    
            if ($error) break;
        }    
    }

    if (is_array($products) and $products) {
        foreach ($products as $product) {
            $id = abs(intval($product['id']));
            $count = abs(intval($product['count']));
            $price = abs(floatval($product['price']));
            $amount = number_format(($count * $price), 2, '.', '');
            $total_amount += $amount;
            $attributes = $product['attributes'];
    
            if (is_array($attributes) and count($attributes) > 0) {
                if ($count_attributes = $db->query("SELECT COUNT(*) FROM `products_sub-id` WHERE `product_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $count_attributes[0]  == 0) {
                    $error = '?? ???????????? ?????? Sub-ID!';
                } elseif ($count_attributes[0] != count($attributes)) {
                    $error = '?????????????????? ???????????? ?????? ???????????? Sub-ID ?????? ????????????! [e:3]';
                } else {
    
                    foreach ($attributes as $attribute) {
                        if (empty($attribute)) {
                            $error = '???????????????? Sub-ID ???? ?????????? ???????? ???????????? ?????? ?????????????????? 0!';
                        } elseif (!is_numeric($attribute)) {
                            $error = '???????????????????????? ???????????????? Sub-ID!';
                        } elseif ($result = $db->query("SELECT COUNT(*) FROM `attributes` INNER JOIN `products_sub-id` ON (`attributes`.`category_id` = `products_sub-id`.`attribute_category_id`) WHERE `products_sub-id`.`product_id` = '" . $id . "' AND `products_sub-id`.`client_id` = '" . $chief['id'] . "' AND `attributes`.`id_item` = '" . abs(intval($attribute)) . "' AND `attributes`.`client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                            $error = 'Sub-ID ???? ????????????!';
                        }
                        if (isset($error)) break;
                    }
    
                    // ???????????????? ?????????????????? ?????????????????????????????? ??????????????????
                    if (count($attributes) > 1) {
                        $in_attributes = implode(",", $attributes);
                        $categories_stmt = $db->query("SELECT `attribute_categories`.`id_item` FROM `attribute_categories` INNER JOIN `attributes` ON (`attribute_categories`.`id_item` = `attributes`.`category_id`) WHERE `attributes`.`id_item` IN ($in_attributes) AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `attribute_categories`.`client_id` = '" . $chief['id'] . "'");
                        $categories = array();
                        while ($category = $categories_stmt->fetch_assoc()) {
                            $categories[] = $category['id_item'];
                        }
                        if (count($attributes) != count(array_unique($categories))) {
                            $error = '?????????????????? ???????????? ?????? ???????????? Sub-ID ?????? ????????????! [e:4]';
                        }
                    }
    
                }
            }
    
            if ($price == 0) {
                $error = '???????????????????? ?????????????? ???????? ????????????!';
            }
    
            if ($count == 0) {
                $error = '???????????????????? ?????????????? ???????????????????? ????????????!';
            }
    
            if ($id == 0) {
                $error = '???????????????????? ?????????????? ??????????!';
            } elseif ($result = $db->query("SELECT COUNT(*) FROM `products` WHERE `id_item` = '" . $id . "' AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                $error = '?????????? ???? ????????????!';
            }
    
            if ($error) break;
        }    
    } else {
        $error = '???????????????? ??????????!';
    }

    if (!empty($add_4)) {
        if (mb_strlen($add_4, 'UTF-8') > 60) {
            $error = '???????????????????????????? ???????? ???4 ???????????? ?????????????????? ???? ???????????? 60 ????????????????!';
        }
    }

    if (!empty($add_3)) {
        if (mb_strlen($add_3, 'UTF-8') > 60) {
            $error = '???????????????????????????? ???????? ???3 ???????????? ?????????????????? ???? ???????????? 60 ????????????????!';
        }
    }

    if (!empty($add_2)) {
        if (mb_strlen($add_2, 'UTF-8') > 60) {
            $error = '???????????????????????????? ???????? ???2 ???????????? ?????????????????? ???? ???????????? 60 ????????????????!';
        }
    }

    if (!empty($add_1)) {
        if (mb_strlen($add_1, 'UTF-8') > 60) {
            $error = '???????????????????????????? ???????? ???1 ???????????? ?????????????????? ???? ???????????? 60 ????????????????!';
        }
    }

    if (!empty($site)) {
        // smtng
    }

    if ($employee == 0) {
        $error = '?????????????? ????????????????????!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `staff` WHERE `employee_id` = '" . $employee . "' AND `chief_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = '?????????????????? ???? ????????????!';
    }

    if (isset($departure_date)) {
        $date = date_create_from_format('d-m-Y H:i:s', str_replace(' ?? ', ' ', $departure_date));
        $date = date_format($date, 'Y-m-d H:i:s');
        $departure_date = strtotime($date);
    }

    if (!empty($address)) {
        if (mb_strlen($address, 'UTF-8') > 200) {
            $error = '?????????? ???????????? ???????? ?? ???????????????? 200 ????????????????!';
        }
    }

    if (!empty($ttn)) {
        // smtng
    }

    if ($delivery != 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = '???????????? ???????????????? ???? ????????????!';
        }
    }

    if (!empty($comment)) {
        if (mb_strlen($comment, 'UTF-8') > 512) {
            $error = '?????????????????????? ???????????? ???????? ?? ???????????????? 512 ????????????????!';
        }
    }

    if ($payment != 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = '???????????? ???????????? ???? ????????????!';
        }
    }

    $reasons = [0, 1, 2, 3, 4];
    if (!in_array($reason, $reasons)) {
        $error = '?????????????? ???????????? ?????????????? ??????????????????????!';
    }

    $right_id = getAccessID('statuses');
    if ($status == 0) {
        $error = '?????????????? ???????????? ????????????!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `status_order` INNER JOIN `group_rights` ON (`status_order`.`id_item` = `group_rights`.`value`) WHERE `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`access_right` = '" . $right_id . "' AND `status_order`.`status` = 'on' AND `status_order`.`id_item` = '" . $status . "' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = '???????????? ?????????????? ???? ????????????!';
    }

    if (empty($office)) {
        $error = '?????????????? ??????????!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `offices` WHERE `id_item` = '" . $office . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = '?????????? ???? ????????????!';
    }

    if (!empty($email)) {
        if (mb_strlen($email, 'UTF-8') < 6 or mb_strlen($email, 'UTF-8') > 64) {
            $error = 'E-mail ?????????? ???????????? ???????? ?? ???????????????? ???? 6 ???? 64 ????????????????!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail ?????????? ???????????? ??????????????!';
        }
    }

    if (empty($phone)) {
        $error = '?????????????? ?????????? ????????????????!';
    }

    if (empty($customer)) {
        $error = '?????????????? ??????????????????!';
    } elseif (mb_strlen($customer, 'UTF-8') > 64) {
        $error = '???????? "????????????????" ???????????? ?????????????????? ???? ???????????? 64 ????????????????!';
    }

    if ($country != 0) {
        if ($result = $db->query("SELECT COUNT(*) FROM `countries` WHERE `id` = '" . $country . "'")->fetch_row() and $result[0] == 0) {
            $error = '???????????? ???? ??????????????!';
        }
    }

    if (empty($order_id)) {
        $error = '?????????????????? ???????????? ?????? ???????????????????? ??????????????????!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `orders` WHERE `id_item` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = '?????????? ???? ????????????!';
    } else {
        $order = $db->query("SELECT `status` FROM `orders` WHERE `id_item` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        if (!checkAccess('statuses', $order['status'])) {
            $error = '?? ?????? ?????? ???????? ?????? ???????????????????????????? ????????????!';
        }
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `orders` SET `customer` = '" . $customer . "', `phone` = '" . $phone . "', `email` = '" . $email . "', `office_id` = '" . $office . "', `amount` = '" . $total_amount . "', `status` = '" . $status . "', `reason` = '" . $reason . "', `payment_method` = '" . $payment . "', `comment` = '" . $comment . "', `delivery_method` = '" . $delivery . "', `ttn` = '" . $ttn . "', `delivery_address` = '" . $address . "', `employee` = '" . $employee . "', `site` = '" . $site . "', `ip` = '" . ip2long($ip) . "', `country` = '" . $country . "', `add_1` = '" . $add_1 . "', `add_2` = '" . $add_2 . "', `add_3` = '" . $add_3 . "', `add_4` = '" . $add_4 . "'," . (isset($departure_date) ? " `departure_date` = '" . $departure_date . "'," : "") . " `updated` = '" . $data['time'] . "' WHERE `id_item` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
            /* ???????????? ???????????????? ?????????????? */
            // ???????????????? ?????? ???????????? ?? ????????????
            $products_in_order = $db->query("SELECT `id`, `product_id` FROM `orders_products` WHERE `order_id` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'");
            // ???????????? ?? ???????????? (orders_products.id) ?? ???????????????? ?? ??????
            $old_products = $old_attributes = [];
            while ($product_in_order = $products_in_order->fetch_assoc()) {
                $old_products[] = $product_in_order['id'];
                if ($result = $db->query("SELECT COUNT(*) FROM `orders_attributes` WHERE `orders_product_id` = '" . $product_in_order['id'] . "' AND `product_id` = '" . $product_in_order['product_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
                    $attributes = $db->query("SELECT `id` FROM `orders_attributes` WHERE `orders_product_id` = '" . $product_in_order['id'] . "' AND `product_id` = '" . $product_in_order['product_id'] . "' AND `client_id` = '" . $chief['id'] . "'");
                    while ($attribute = $attributes->fetch_assoc()) {
                        if (!array_key_exists($product_in_order['id'], $old_attributes)) $old_attributes = [$product_in_order['id'] => array()] + $old_attributes;
                        $old_attributes[$product_in_order['id']][] = $attribute['id'];
                    }
                }
            }
            
            // ?????????? ????????
            $new_products_first = array_slice($products, 0, count($old_products)); // ???????????? ?????????? ?????????? ??????????????, ?????????????? ???????????? ?????? ?????????? ???????????????? ???????????????????? ?????????????? ?? ????????????.
            $i = 0;
            foreach ($new_products_first as $product) {
                $id = abs(intval($product['id']));
                $count = abs(intval($product['count']));
                $price = abs(floatval($product['price']));
                $amount = number_format(($count * $price), 2, '.', '');
                $attributes = $product['attributes'];
                // ??????????????
                $db->query("UPDATE `orders_products` SET `product_id` = '" . $id . "', `price` = '" . $price . "', `count` = '" . $count . "', `amount` = '" . $amount . "' WHERE `id` = '" . $old_products[$i] . "' AND `client_id` = '" . $chief['id'] . "'");

                // ????????????????
                if (is_array($attributes) and $attributes) {
                    $orders_product_id = $old_products[$i];
                    if ($old_attributes and array_key_exists($orders_product_id, $old_attributes)) {
                        $new_attributes_first = array_slice($attributes, 0, count($old_attributes[$orders_product_id])); // ???????????? ?????????? ?????????? ??????????????????, ?????????????? ???????????? ?????? ?????????? ???????????????? ???????????????????? ?????????????????? ???????????? ?? ????????????.
                        $k = 0;
                        foreach ($new_attributes_first as $attribute) {
                            $db->query("UPDATE `orders_attributes` SET `product_id` = '" . $id . "', `attribute_id` = '" . abs(intval($attribute)) . "' WHERE `id` = '" . $old_attributes[$orders_product_id][$k] . "' AND `orders_product_id` = '" . $old_products[$i] . "' AND `client_id` = '" . $chief['id'] . "'"); // ToDo: ??????????, ?????? client_id ?????????????????? ?????? ???????????? :(
                            $k++;
                        }
                        if (count($attributes) < count($old_attributes[$orders_product_id])) {
                            $excess_items = implode(',', array_slice($old_attributes[$orders_product_id], count($new_attributes_first)));
                            $db->query("DELETE FROM `orders_attributes` WHERE `id` IN ($excess_items) AND `client_id` = '" . $chief['id'] . "'"); // ToDo: ???????? ??????????
                        } elseif (count($attributes) > count($old_attributes[$orders_product_id])) {
                            $new_attributes_second = array_slice($attributes, count($old_attributes[$orders_product_id])); // ???????????? ?????????? ?????????? ?????????????????? ????????????, ?????????? ?????????????? ???????????? ???????????????? ???????????????????? ?????????????????? ???????????? ?? ????????????
                            $insert_attributes = "INSERT INTO `orders_attributes` (`id`, `client_id`, `order_id`, `orders_product_id`, `product_id`, `attribute_id`) VALUES";
                            foreach ($new_attributes_second as $attribute) {
                                $insert_attributes .= " (null, '" . $chief['id'] . "', '" . $order_id . "', '" . $old_products[$i] . "', '" . $id . "', '" . abs(intval($attribute)) . "'),";
                            }
                            $db->query(rtrim($insert_attributes, ','));
                        }
                    } else { // ???????? ?????????????????? ?? ???????????? ????????????
                        $insert_attributes = "INSERT INTO `orders_attributes` (`id`, `client_id`, `order_id`, `orders_product_id`, `product_id`, `attribute_id`) VALUES";
                        foreach ($attributes as $attribute) {
                            $insert_attributes .= " (null, '" . $chief['id'] . "', '" . $order_id . "', '" . $old_products[$i] . "', '" . $id . "', '" . abs(intval($attribute)) . "'),";
                        }
                        $db->query(rtrim($insert_attributes, ','));
                    }

                } else { // ???????? ?????????????????? ???????????? ??????
                    $db->query("DELETE FROM `orders_attributes` WHERE `order_id` = '" . $order_id . "' AND `orders_product_id` = '" . $old_products[$i] . "' AND `client_id` = '" . $chief['id'] . "'"); // ToDo: ???????? ??????????
                }
                $i++;
            }
            
            // ???????? ?????????? ?????????????? ???????????? ?????? ????????????
            if (count($products) < count($old_products)) {
                $excess_items = implode(',', array_slice($old_products, count($new_products_first))); // ???????????? ????????????
                $db->query("DELETE FROM `orders_products` WHERE `id` IN ($excess_items) AND `order_id` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'");
                // ???????????????? ?????????????????? ?????????????????????????? ???? ???????????????? ??????????
            } elseif (count($products) > count($old_products)) {
                $new_products_second = array_slice($products, count($old_products)); // ???????????? ?????????? ?????????? ??????????????, ?????????? ?????????????? ???????????? ???????????????? ???????????????????? ?????????????? ?? ????????????
                foreach ($new_products_second as $product) {
                    $id = abs(intval($product['id']));
                    $count = abs(intval($product['count']));
                    $price = abs(floatval($product['price']));
                    $amount = number_format(($count * $price), 2, '.', '');
                    $attributes = $product['attributes'];

                    $db->query("INSERT INTO `orders_products` (`id`, `client_id`, `order_id`, `product_id`, `price`, `count`, `amount`) VALUES (null, '" . $chief['id'] . "', '" . $order_id . "', '" . $id . "', '" . $price . "', '" . $count . "', '" . $amount . "')");
                    $last_orders_product_id = $db->insert_id; // ???????? ?????????? ????????????????

                    $insert_attributes = "INSERT INTO `orders_attributes` (`id`, `client_id`, `order_id`, `orders_product_id`, `product_id`, `attribute_id`) VALUES";
                    foreach ($attributes as $attribute) {
                        $insert_attributes .= " (null, '" . $chief['id'] . "', '" . $order_id . "', '" . $last_orders_product_id . "', '" . $id . "', '" . abs(intval($attribute)) . "'),";
                    }
                    $db->query(rtrim($insert_attributes, ','));

                }
            }
            /* ?????????? ???????????????? ?????????????? */

            /* ???????????? ?????????????????? */
            // ???????????????? ?????? ???????????? ?? ??????????????????
            $add_sales_in_order = $db->query("SELECT `id`, `product_id` FROM `add_sales_products` WHERE `order_id` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'");
            // ???????????? ?? ?????????????????? (orders_products.id) ?? ???????????????? ?? ??????
            $old_add_sale_products = $old_add_sale_attributes = [];
            while ($add_sale_in_order = $add_sales_in_order->fetch_assoc()) {
                $old_add_sale_products[] = $add_sale_in_order['id'];
                if ($result = $db->query("SELECT COUNT(*) FROM `add_sales_attributes` WHERE `add_sales_product_id` = '" . $add_sale_in_order['id'] . "' AND `product_id` = '" . $add_sale_in_order['product_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
                    $attributes = $db->query("SELECT `id` FROM `add_sales_attributes` WHERE `add_sales_product_id` = '" . $add_sale_in_order['id'] . "' AND `product_id` = '" . $add_sale_in_order['product_id'] . "' AND `client_id` = '" . $chief['id'] . "'");
                    while ($attribute = $attributes->fetch_assoc()) {
                        if (!array_key_exists($add_sale_in_order['id'], $old_add_sale_attributes)) $old_add_sale_attributes = [$add_sale_in_order['id'] => array()] + $old_add_sale_attributes;
                        $old_add_sale_attributes[$add_sale_in_order['id']][] = $attribute['id'];
                    }
                }
            }
            if (is_array($add_sale_products) and $add_sale_products) { // ???????? ?????????????????? ???????????? ????????????????????
                $new_add_sale_products_first = array_slice($add_sale_products, 0, count($old_add_sale_products)); // ???????????? ?????????? ?????????? ??????????????, ?????????????? ???????????? ?????? ?????????? ???????????????? ???????????????????? ?????????????? ?????????????????? ?? ????????????.
                $i = 0;
                foreach ($new_add_sale_products_first as $product) {
                    $id = abs(intval($product['id']));
                    $count = abs(intval($product['count']));
                    $price = abs(floatval($product['price']));
                    $amount = number_format(($count * $price), 2, '.', '');
                    $attributes = $product['attributes'];
                    // ??????????????
                    $db->query("UPDATE `add_sales_products` SET `product_id` = '" . $id . "', `price` = '" . $price . "', `count` = '" . $count . "', `amount` = '" . $amount . "' WHERE `id` = '" . $old_add_sale_products[$i] . "' AND `client_id` = '" . $chief['id'] . "'");

                    // ????????????????
                    if (is_array($attributes) and $attributes) {
                        $add_sales_product_id = $old_add_sale_products[$i];
                        if ($old_add_sale_attributes and array_key_exists($add_sales_product_id, $old_add_sale_attributes)) {
                            $new_add_sale_attributes_first = array_slice($attributes, 0, count($old_add_sale_attributes[$add_sales_product_id])); // ???????????? ?????????? ?????????? ??????????????????, ?????????????? ???????????? ?????? ?????????? ???????????????? ???????????????????? ?????????????????? ???????????? ?? ??????????????????.
                            $k = 0;
                            foreach ($new_add_sale_attributes_first as $attribute) {
                                $db->query("UPDATE `add_sales_attributes` SET `product_id` = '" . $id . "', `attribute_id` = '" . abs(intval($attribute)) . "' WHERE `id` = '" . $old_add_sale_attributes[$add_sales_product_id][$k] . "' AND `add_sales_product_id` = '" . $old_add_sale_products[$i] . "' AND `client_id` = '" . $chief['id'] . "'");
                                $k++;
                            }
                            if (count($attributes) < count($old_add_sale_attributes[$add_sales_product_id])) {
                                $excess_items = implode(',', array_slice($old_add_sale_attributes[$add_sales_product_id], count($new_add_sale_attributes_first)));
                                $db->query("DELETE FROM `add_sales_attributes` WHERE `id` IN ($excess_items) AND `client_id` = '" . $chief['id'] . "'");
                            } elseif (count($attributes) > count($old_add_sale_attributes[$add_sales_product_id])) {
                                $new_add_sale_attributes_second = array_slice($attributes, count($old_add_sale_attributes[$add_sales_product_id])); // ???????????? ?????????? ?????????? ?????????????????? ????????????, ?????????? ?????????????? ???????????? ???????????????? ???????????????????? ?????????????????? ???????????? ?? ??????????????????
                                $insert_attributes = "INSERT INTO `add_sales_attributes` (`id`, `client_id`, `order_id`, `add_sales_product_id`, `product_id`, `attribute_id`) VALUES";
                                foreach ($new_add_sale_attributes_second as $attribute) {
                                    $insert_attributes .= " (null, '" . $chief['id'] . "', '" . $order_id . "', '" . $old_add_sale_products[$i] . "', '" . $id . "', '" . abs(intval($attribute)) . "'),";
                                }
                                $db->query(rtrim($insert_attributes, ','));
                            }
                        } else { // ???????? ?????????????????? ?? ???????????? ????????????
                            $insert_attributes = "INSERT INTO `add_sales_attributes` (`id`, `client_id`, `order_id`, `add_sales_product_id`, `product_id`, `attribute_id`) VALUES";
                            foreach ($attributes as $attribute) {
                                $insert_attributes .= " (null, '" . $chief['id'] . "', '" . $order_id . "', '" . $old_add_sale_products[$i] . "', '" . $id . "', '" . abs(intval($attribute)) . "'),";
                            }
                            $db->query(rtrim($insert_attributes, ','));
                        }

                    } else { // ???????? ?????????????????? ???????????? ??????
                        $db->query("DELETE FROM `add_sales_attributes` WHERE `order_id` = '" . $order_id . "' AND `add_sales_product_id` = '" . $old_add_sale_products[$i] . "' AND `client_id` = '" . $chief['id'] . "'");
                    }
                    $i++;
                }

                // ???????? ?????????? ?????????????? ?? ?????????????????? ???????????? ?????? ????????????
                if (count($add_sale_products) < count($old_add_sale_products)) {
                    $excess_items = implode(',', array_slice($old_add_sale_products, count($new_add_sale_products_first))); // ???????????? ????????????
                    $db->query("DELETE FROM `add_sales_products` WHERE `id` IN ($excess_items) AND `order_id` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'");
                    // ???????????????? ?????????????????? ?????????????????????????? ???? ???????????????? ??????????
                } elseif (count($add_sale_products) > count($old_add_sale_products)) {
                    $new_add_sale_products_second = array_slice($add_sale_products, count($old_add_sale_products)); // ???????????? ?????????? ?????????? ??????????????, ?????????? ?????????????? ???????????? ???????????????? ???????????????????? ?????????????? ?? ??????????????????
                    foreach ($new_add_sale_products_second as $product) {
                        $id = abs(intval($product['id']));
                        $count = abs(intval($product['count']));
                        $price = abs(floatval($product['price']));
                        $amount = number_format(($count * $price), 2, '.', '');
                        $attributes = $product['attributes'];

                        $db->query("INSERT INTO `add_sales_products` (`id`, `client_id`, `order_id`, `product_id`, `price`, `count`, `amount`) VALUES (null, '" . $chief['id'] . "', '" . $order_id . "', '" . $id . "', '" . $price . "', '" . $count . "', '" . $amount . "')");
                        $last_add_sale_product_id = $db->insert_id; // ???????? ?????????? ????????????????

                        $insert_attributes = "INSERT INTO `add_sales_attributes` (`id`, `client_id`, `order_id`, `add_sales_product_id`, `product_id`, `attribute_id`) VALUES";
                        foreach ($attributes as $attribute) {
                            $insert_attributes .= " (null, '" . $chief['id'] . "', '" . $order_id . "', '" . $last_add_sale_product_id . "', '" . $id . "', '" . abs(intval($attribute)) . "'),";
                        }
                        $db->query(rtrim($insert_attributes, ','));

                    }
                }
            } elseif (!$add_sale) {
                $db->query("DELETE FROM `add_sales_products` WHERE `order_id` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'");
                // ???????????????? ?????????????????? ?????????????????????????? ???? ???????????????? ??????????
            }
            /* ?????????? ?????????????????? */
            
        } else {
            $error = '???? ?????????????? ?????????????????? ??????????????????!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
        
if (isset($_GET['order_id'])) {
    $order_id = abs(intval($_GET['order_id']));

    $order = $db->query("SELECT `status` FROM `orders` WHERE `id_item` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
    if (!checkAccess('statuses', $order['status'])) {
        $denied = true;
    }

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `orders` WHERE `id_item` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $order = $db->query("SELECT `id_item`, `id_order`, `date_added` FROM `orders` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $order_id . "'")->fetch_assoc();
            $title = ['id' => $order['id_item'], 'id_order' => protection($order['id_order'], 'display'), 'date_added' => view_time($order['date_added'])];
        } else {
            $error = '?????????????????????? ??????????!';
            $title = ['id' => 'UNDEFINED', 'id_order' => 'UNDEFINED', 'date_added' => 'UNDEFINED'];
        }
        if (isset($denied)) $title = ['error' => '???????????? ??????????????????!'];
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }


    if ($result = $db->query("SELECT COUNT(*) FROM `orders` WHERE `id_item` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $order = $db->query("SELECT * FROM `orders` WHERE `id_item` = '" . $order_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
?>

<script>
    $(function(){
        let form = $('#view-order'),
            btn = $('#button-save-changes');

        $('#add-sale').on('click', function(){
            if ($(this).is(':checked')) {
                let productsTable = $('#order-products tbody').find('tr:eq(0)');
                if (productsTable.attr('data-id') == 0) {
                    showModalWindow(null, null, 'error', '???????????????? ?????????? ?? ??????????!');
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
                $('#add-sale-products tbody').html('<tr data-id="0"><td colspan="8" style="padding: 15px 0"><span style="color: #900; font-size: 14px; font-weight: 700">?????? ????????????</span></td></tr>');
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
        
        // ????????
        $('#order-products, #add-sale-products').on('dblclick', 'tbody tr td:nth-child(5)', function(e) {
            let price = $(this).text(),
                width = $(this).css('width'),
                height = $(this).css('height');
            $(this).css('padding', '0px');
            $(this).html('<input id="product-price" class="small" type="text" name="price" style="width: ' + width + '; height: ' + height + '">');
            $('#product-price').focus().val(price).select();
        });

        // ????????????????????
        $('#order-products, #add-sale-products').on('dblclick', 'tbody tr td:nth-child(6)', function(e) {
            let count = $(this).text(),
                width = $(this).css('width'),
                height = $(this).css('height');
            $(this).css('padding', '0px');
            $(this).html('<input id="product-count" class="small" type="text" name="count" style="width: ' + width + '; height: ' + height + '">');
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

        form.on('keyup change', function() {
            checkFields();
        });

        form.on('keypress', '#product-price, #product-count', function(e) {
            if (e.keyCode == 13) {
                $(this).hide(); // ??????????????????))
                return false;
            }
        });

        form.on('change', 'select[name="status"]', function(){
            let value = $(this).val();
            if (value === '5') { // ??????????
                $('#renouncement-reason').prop('disabled', false);
                $('#renouncement-reason').trigger('chosen:updated');
            } else {
                $('#renouncement-reason').prop('disabled', true);
                $('#renouncement-reason').trigger('chosen:updated');
            }
        });

        $('#view-order').on('submit', function(e) {
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
                    url: "/system/ajax/viewOrder.php?action=submit",
                    data: data,
                    beforeSend: function(){
                        // btn.prop('disabled', true).html('<img src="/img/load.gif" alt="load"> ????????????????????');
                    },
                    success: function(response) {
                        let jsonData = JSON.parse(response),
                            count_modal = $('.modal-window-wrapper').length,
                            wsData = {
                                action: "unlock item",
                                data: {
                                    itemId: <?=$order['id']?>,
                                    location: 'orders'
                                }
                            }
                        if (jsonData.success == 1) {
                            TabStatus(getParameterByName('status'), 1);
                            closeModalWindow(count_modal);
                            hideOptions(true);
                            sendMessage(ws, JSON.stringify(wsData));
                        } else {
                            if (!$('.modal-window-content div').is('.error')) {
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
                        error = '???????????????? ?????????? ?????? ??????????????????!';
                        return false;
                    } else {
                        if ($(this).attr('data-role') == 'parent') {
                            let tdCount = $(this).find('td[data-name="count"]').text();
                            if (tdCount == '') {
                                error = '???? ???????????? ?????????????? ???????????????????? ????????????!';
                            } else if (tdCount == 0) {
                                error = '???????????????????? ???????????? ???? ?????????? ?????????????????? ????????!';
                            } else if (isNaN(tdCount)) {
                                error = '???????????????????????? ???????????????????? ????????????!';
                            }

                            let tdPrice = $(this).find('td[data-name="price"]').text();
                            if (tdPrice == '') {
                                error = '???? ???????????? ?????????????? ???????? ????????????!';
                            }
                            /*
                            else if (tdPrice == 0) {
                                error = '???????? ????????????(????) ???????????? ???????? ???????????? ????????!';
                            }
                            */
                             else if (isNaN(tdPrice)) {
                                    error = '???????????????????????? ???????? ????????????!';
                            }

                            if ($(this).attr('data-count') > 1) {
                                let childs = $(this).nextAll('tr[data-role="child"]'),
                                    countChilds = $(this).attr('data-count'),
                                    i = 1;
                                $.each(childs, function() {
                                    if (i < countChilds) {
                                        let attrId = $(this).find('td:eq(0)').attr('data-value');
                                        if (attrId == '') {
                                            error = '???? ???????????? ?????????????? Sub-ID ????????????!';
                                        } else if (attrId == 0) {
                                             error = 'Sub-ID ???????????? ???? ?????????? ?????????????????? ????????!';
                                        } else if (isNaN(attrId)) {
                                             error = '???????????????????????? ???????????????? Sub-ID ????????????!';
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
                    error = '???????????????? ??????????!';
                    return false;
                } else {
                    if ($(this).attr('data-role') == 'parent') {
                        let tdCount = $(this).find('td[data-name="count"]').text();
                        if (tdCount == '') {
                            error = '???? ???????????? ?????????????? ???????????????????? ????????????!';
                        } else if (tdCount == 0) {
                            error = '???????????????????? ???????????? ???? ?????????? ?????????????????? ????????!';
                        } else if (isNaN(tdCount)) {
                            error = '???????????????????????? ???????????????????? ????????????!';
                        }

                        let tdPrice = $(this).find('td[data-name="price"]').text();
                        if (tdPrice == '') {
                            error = '???? ???????????? ?????????????? ???????? ????????????!';
                        }
                        /*
                        else if (tdPrice == 0) {
                            error = '???????? ????????????(????) ???????????? ???????? ???????????? ????????!';
                        }
                        */
                         else if (isNaN(tdPrice)) {
                            error = '???????????????????????? ???????? ????????????!';
                        }

                        if ($(this).attr('data-count') > 1) {
                            let childs = $(this).nextAll('tr[data-role="child"]'),
                                countChilds = $(this).attr('data-count'),
                                i = 1;
                            $.each(childs, function() {
                                if (i < countChilds) {
                                    let attrId = $(this).find('td:eq(0)').attr('data-value');
                                    if (attrId == '') {
                                        error = '???? ???????????? ?????????????? Sub-ID ????????????!';
                                    } else if (attrId == 0) {
                                        error = 'Sub-ID ???????????? ???? ?????????? ?????????????????? ????????!';
                                    } else if (isNaN(attrId)) {
                                        error = '???????????????????????? ???????????????? Sub-ID ????????????!';
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
                if (add_4.length > 30) {
                    error = '???????????????????????????? ???????? ???4 ???????????? ?????????????????? ???? ???????????? 30 ????????????????!';
                }
            }

            if (add_3 != '') {
                if (add_3.length > 30) {
                    error = '???????????????????????????? ???????? ???3 ???????????? ?????????????????? ???? ???????????? 30 ????????????????!';
                }
            }

            if (add_2 != '') {
                if (add_2.length > 30) {
                    error = '???????????????????????????? ???????? ???2 ???????????? ?????????????????? ???? ???????????? 30 ????????????????!';
                }
            }

            if (add_1 != '') {
                if (add_4.length > 30) {
                    error = '???????????????????????????? ???????? ???1 ???????????? ?????????????????? ???? ???????????? 30 ????????????????!';
                }
            }

            let employee = $('#order-employee').val().trim();
            if (isNaN(employee)) {
                error = '?????????????????? ???????????? ??????????????????????!';
            }

            let address = $('#order-address').val().trim();
            if (address != '') {
                if (address.length > 60) {
                    error = '?????????? ???????????????? ???????????? ???????? ?? ???????????????? 60 ????????????????!';
                }
            }

            let ttn = $('#order-ttn').val().trim();
            if (ttn != '') {

            }

            let delivery = $('#order-delivery').val().trim();
            if (delivery != '') {
                if (isNaN(delivery)) {
                    error = '???????????? ???????????????? ???????????? ??????????????????????!';
                }
            }

            let comment = $('#order-comment').val().trim();
            if (comment != '') {
                if (comment.length > 512) {
                    error = '?????????????????????? ???????????? ???????? ?? ???????????????? 512 ????????????????!';
                }
            }

            let payment = $('#order-payment').val().trim();
            if (payment != '') {
                if (isNaN(payment)) {
                    error = '???????????? ???????????? ???????????? ??????????????????????!';
                }
            }

            let status = $('#order-status').val().trim();
            if (status == '') {
                error = '?????????????? ???????????? ????????????!';
            } else if (isNaN(status)) {
                error = '???????????? ???????????? ???????????? ??????????????????????!';
            }

            let email = $('#order-email').val().trim();
            if (email != '') {
                if (email.length < 6 || email.length > 64) {
                    error = 'E-mail ???????????? ???????? ?? ???????????????? ???? 6 ???? 64 ????????????????!';
                }
            }

            let phone = $('#order-phone').val().trim();
            if (phone == '') {
                error = '?????????????? ?????????? ???????????????? ??????????????????!';
            }

            let customer = $('#order-customer').val().trim();
            if (customer == '') {
                error = '?????????????? ??????????????????!';
            }

            let country = $('#order-country').val().trim();
            if (country != '') {
                if (isNaN(country)) {
                    error = '???????????? ?????????????? ??????????????????????!';
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
    });

    function viewProductInfo(id){
        if (!id) return false;
        showModalWindow('???????????????????? ?? ????????????', '/system/ajax/viewProductInfo.php?product_id=' + id);
    }

    function addProductInTable(table){
        if (!table) return false;
        showModalWindow('?????????? ????????????', '/system/ajax/addProductInTable.php?table=' + table + '&location=order');
    }

    function removeProductItem(event){
        if (typeof(event) == "object") {
            // ?????????????? ???????????? ??????????????
            let prevlastItem = $(event).closest("tr").prev('tr'),
                lastItems = $(event).closest("tr").nextAll('tr[data-role="parent"]');
            if (prevlastItem.attr('data-role') == 'child' && $(event).closest("tr").attr('data-role') == 'parent') {
                let prevParentItems = prevlastItem.prevAll('tr[data-role="parent"]');
                $.each(prevParentItems, function() {
                    if (lastItems.length == 0) { // ???????? ?????? ?????????????????? ????????????
                        $(this).children('td:nth-child(1), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7), td:nth-child(8)').css('border-bottom', 'none');
                    } else if (lastItems.length > 0) {
                        let countParents = 0;
                        $.each(lastItems, function() {
                            countParents++;
                        });
                        if (countParents == 0) { // ???????? ???????????? ?????? ???????????????????????? ??????????
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
                
            // ?????????????? ????????????
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
                tbody.append('<tr data-id="0"><td colspan="8" style="padding: 15px 0"><span style="color: #900; font-size: 14px; font-weight: 700">?????? ????????????</span></td></tr>');
                $('#button-add-order').addClass('disabled');
            }
        } else {
            return false;
        }
        $('#view-order').trigger('change');
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
        <form id="view-order" method="post" autocomplete="off">
            <input type="hidden" name="order_id" value="<?=$order['id_item']?>">
            <div class="modal-window-content__row">
                <div class="modal-window-content__item">
                    <div class="modal-window-content__title">???????????????????? ????????????????????</div>
                    <div class="modal-window-content__value">
                        <span>????????????</span> <i class="fa fa-flag"></i> <select id="order-country" name="country" class="chosen-select">
                            <option value="">??????</option>
<?
    $countries = $db->query("SELECT `countries`.`id`, `countries`.`name`, `countries`.`code` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "' ORDER BY `id`");
    while ($country = $countries->fetch_assoc()) {
?>
                            <option value="<?=$country['id']?>" data-img-src="/img/countries/<?=strtolower($country['code'])?>.png"<?=($order['country'] == $country['id'] ? ' selected' : '')?>><?=protection($country['name'] . ' (' . $country['code'] . ')', 'display')?></option>
<?
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>????????????????</span> <i class="fa fa-male"></i> <input id="order-customer" type="text" name="customer" value="<?=protection($order['customer'], 'display')?>">
                    </div>
                    <div class="modal-window-content__value">
                        <span>??????????????</span> <i class="fa fa-phone"></i> <input id="order-phone" type="text" name="phone" value="<?=protection($order['phone'], 'display')?>">
                    </div>
                    <div class="modal-window-content__value">
                        <span>E-mail</span> <i class="fa fa-at"></i> <input id="order-email" type="text" name="mail" value="<?=protection($order['email'], 'display')?>">
                    </div>
                    <div class="modal-window-content__value">
                        <span>??????????</span> <i class="fa fa-building"></i> <select id="user-office" name="office_id" class="chosen-select">
<?
$offices = $db->query("SELECT `id_item`, `name` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'");
while ($office = $offices->fetch_assoc()) {
?>
                            <option value="<?=$office['id_item']?>"<?=($order['office_id'] == $office['id_item'] ? ' selected' : '')?>><?=protection($office['name'], 'display')?></option>
<?
}
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>???????????? ????????????</span> <i class="fa fa-magic"></i> <select id="order-status" name="status" class="chosen-select">
                            <option value="">- ???? ?????????????? -</option>
                            <option disabled>----------------------------------------</option>
<?
    $statuses = $db->query("SELECT `id_item`, `name`, `color`, `status` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "'");
    while ($status = $statuses->fetch_assoc()) {
?>
                            <option data-id="<?=$status['id_item']?>" data-img-src="/getImage/?color=<?=str_replace('#', '', $status['color'])?>" value="<?=$status['id_item']?>"<?=($status['status'] == 'off' ? ' disabled' : '')?><?=($order['status'] == $status['id_item'] ? ' selected' : '')?>><?=protection($status['name'], 'display')?></option>
<?
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>?????????????? ????????????</span> <i class="fa fa-question-circle"></i> <select id="renouncement-reason" name="reason_renouncement" class="chosen-select" disabled>
                            <option value="">- ???? ?????????????? -</option>
                            <option value="1">?????????????????????? ????????????</option>
                            <option value="2">???????????? ?????????????????? ?????? ??????????????????</option>
                            <option value="3">???????????? ???? ???????????????? ????????????</option>
                            <option value="4">???????????????????????? ?????????? ????????????????</option>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>???????????? ????????????</span> <i class="fa fa-money"></i> <select id="order-payment" name="payment_method" class="chosen-select">
                            <option value="">- ???? ?????????????? -</option>
                            <option disabled>----------------------------------------</option>
<?
    $payment_methods = $db->query("SELECT `id_item`, `name`, `icon` FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "'");
    while ($payment_method = $payment_methods->fetch_assoc()) {
?>
                            <option data-img-src="/system/images/payment/<?=protection($payment_method['icon'], 'display')?>" value="<?=$payment_method['id_item']?>"<?=($order['payment_method'] == $payment_method['id_item'] ? ' selected' : '')?>><?=protection($payment_method['name'], 'display')?></option>
<?
        echo "\r\n";
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>??????????????????????</span> <i class="fa fa-comment"></i> <textarea id="order-comment" name="comment"></textarea>
                    </div>
                    <div class="modal-window-content__title">UTM ??????????</div>
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
                    <div class="modal-window-content__title">????????????????</div>
                    <div class="modal-window-content__value">
                        <span>????????????</span> <i class="fa fa-truck"></i> <select id="order-delivery" name="delivery_method" class="chosen-select">
                            <option value="">- ???? ?????????????? -</option>
                            <option disabled>----------------------------------------</option>
                            <?
    $delivery_methods = $db->query("SELECT `id_item`, `name`, `icon` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'");
    while ($delivery_method = $delivery_methods->fetch_assoc()) {
?>
                            <option data-img-src="/system/images/delivery/<?=protection($delivery_method['icon'], 'display')?>" value="<?=$delivery_method['id_item']?>"<?=($order['delivery_method'] == $delivery_method['id_item'] ? ' selected' : '')?>><?=protection($delivery_method['name'], 'display')?></option>
<?
        echo "\r\n";
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>??????</span> <i class="fa fa-file-text"></i> <input id="order-ttn" type="text" name="ttn" value="<?=protection($order['ttn'], 'display')?>">
                    </div>
                    <div class="modal-window-content__value">
                        <span>??????????</span> <i class="fa fa-map-marker"></i> <input id="order-address" type="text" name="delivery_address" value="<?=protection($order['delivery_address'], 'display')?>">
                    </div>
<?
/*
$date = date_create();
date_timestamp_set($date, $order['departure_date']);
$departure_date = date_format($date, 'd-m-Y H:i:s');
*/
?>
                    <div class="modal-window-content__value">
                        <span>????????????????????</span> <i class="fa fa-calendar-check-o"></i> <input id="order-departure" type="text" name="departure_date" value="<?=strip_tags(view_time($order['departure_date']))?>" disabled>
                        
                    </div>
                    <div class="modal-window-content__title">?????????????????? ????????????????????</div>
                    <div class="modal-window-content__value">
                        <span>??????????????????</span> <i class="fa fa-user-circle"></i> <select id="order-employee" name="employee" class="chosen-select">
                            <option value="">- ???? ?????????????? -</option>
<?
    $employees = $db->query("SELECT `id_item`, `name` FROM `user` WHERE (`id` = '" . $chief['id'] . "' AND `chief_id` = 0) OR `chief_id` = '" . $chief['id'] . "'");
    while ($employee = $employees->fetch_assoc()) {
?>
                            <option value="<?=$employee['id_item']?>"<?=($order['employee'] == $employee['id_item'] ? ' selected' : '')?>><?=protection($employee['name'], 'display')?></option>
<?
    }
?>
                        </select>
                    </div>
                    <div class="modal-window-content__value">
                        <span>IP</span> <i class="fa fa-desktop"></i> <div class="modal-window-content__value-block"><?=long2ip($order['ip'])?> <i class="fa fa-angle-double-right"></i> <a>??????????????????????</a></div>
                    </div>
                    <div class="modal-window-content__value">
                        <span>????????</span> <i class="fa fa-globe"></i> <input type="text" name="site" value="<?=protection($order['site'], 'display')?>" disabled>
                    </div>
                    <div class="modal-window-content__value">
                        <span>order_id</span> <i class="fa fa-tag"></i> <div class="modal-window-content__value-block"><small style="color: #757575"><?=$order['id_order']?></small> <i class="fa fa-info-circle"></i> <span> ???? <?=view_time($order['date_added'])?></span></div>
                    </div>

                    <div class="modal-window-content__title">??????????????????????????</div>
                    <div class="modal-window-content__value">
                        <span>??????. ???????? 1</span> <i class="fa fa-plus"></i> <input id="order-add-1" type="text" name="add_1" value="<?=protection($order['add_1'], 'display')?>">
                    </div>
                    <div class="modal-window-content__value">
                        <span>??????. ???????? 2</span> <i class="fa fa-plus"></i> <input id="order-add-2" type="text" name="add_2" value="<?=protection($order['add_2'], 'display')?>">
                    </div>
                    <div class="modal-window-content__value">
                        <span>??????. ???????? 3</span> <i class="fa fa-plus"></i> <input id="order-add-3" type="text" name="add_3" value="<?=protection($order['add_3'], 'display')?>">
                    </div>
                    <div class="modal-window-content__value">
                        <span>??????. ???????? 4</span> <i class="fa fa-plus"></i> <input id="order-add-4" type="text" name="add_4" value="<?=protection($order['add_4'], 'display')?>">
                    </div>
                </div>
                <div class="modal-window-content__item" style="width: auto">
                    <div class="modal-window-content__title">??????????</div>
                    <div class="modal-window-content__table">
                        <table id="order-products" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>id</th>
                                    <th>sub_id</th>
                                    <th>sub_name</th>
                                    <th>??????????</th>
                                    <th>????????</th>
                                    <th>??????-????</th>
                                    <th>??????????</th>
                                    <th><i class="fa fa-remove"></i></th>
                                </tr>
                            </thead>
                            <tbody>
<?
$products = $db->query("SELECT `products`.`id_item`, `products`.`name`, `products`.`model`, `orders_products`.`count`, `orders_products`.`id` AS `orders_product_id`, `orders_products`.`price`, `orders_products`.`amount` FROM `products` INNER JOIN `orders_products` ON (`products`.`id_item` = `orders_products`.`product_id`) WHERE `orders_products`.`order_id` = '" . $order['id_item'] . "' AND `products`.`client_id` = '" . $chief['id'] . "' AND `orders_products`.`client_id` = '" . $chief['id'] . "'");
$count = $amount = 0;
while ($product = $products->fetch_assoc()) {
    $count += abs(intval($product['count']));
    $amount += abs(floatval($product['amount']));

    if (($attrs = $db->query("SELECT COUNT(*) FROM `orders_attributes` WHERE `order_id` = '" . $order['id_item'] . "' AND `orders_product_id` = '" . $product['orders_product_id'] . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row()) and $attrs[0] > 0) {
        $attributes = $db->query("SELECT `attribute_id` FROM `orders_attributes` WHERE `order_id` = '" . $order['id_item'] . "' AND `orders_product_id` = '" . $product['orders_product_id'] . "' AND `product_id` = '" . $product['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'");
        $step['i'] = 1;
        while ($attribute = $attributes->fetch_assoc()) {
            $attribute = $db->query("SELECT `id_item`, `name`, `category_id` FROM `attributes` WHERE `id_item` = '" . $attribute['attribute_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $category = $db->query("SELECT `id_item`, `name` FROM `attribute_categories` WHERE `id_item` = '" . $attribute['category_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            if ($step['i'] == 1) {
?>
                                <tr data-id="<?=$product['id_item']?>" data-role="parent" data-count="<?=$attrs[0]?>">
                                    <td data-name="id" rowspan="<?=$attrs[0]?>"><?=$product['id_item']?></td>
                                    <td data-name="attribute_id" data-value="<?=$attribute['id_item']?>" title="<?=protection($category['name'], 'display')?>"><?=$category['id_item']?></td>
                                    <td><?=protection($attribute['name'], 'display')?></td>
                                    <td rowspan="<?=$attrs[0]?>" title="<?=protection($product['name'] . ' ' . $product['model'], 'display')?>"><a id="view_product" href="javascript:void(0);" onclick="viewProductInfo('<?=$product['id_item']?>');"><?=protection($product['name'] . ' ' . $product['model'], 'display')?></a></td>
                                    <td data-name="price" rowspan="<?=$attrs[0]?>"><?=number_format(abs(floatval($product['price'])), 2, '.', '')?></td>
                                    <td data-name="count" rowspan="<?=$attrs[0]?>"><?=abs(intval($product['count']))?></td>
                                    <td data-name="amount" rowspan="<?=$attrs[0]?>"><?=number_format(abs(floatval($product['amount'])), 2, '.', '')?></td>
                                    <td data-name="remove" rowspan="<?=$attrs[0]?>"><i class="fa fa-remove" style="color: #900; cursor: pointer" onclick="removeProductItem(this)" title="????????????"></i></td>
                                </tr>
<?
            } else {
?>
                                <tr data-id="<?=$product['id_item']?>" data-role="child">
                                    <td data-name="attribute_id" data-value="<?=$attribute['id_item']?>" title="<?=protection($category['name'], 'display')?>"><?=$category['id_item']?></td>
                                    <td style="border-right: 1px solid #eee"><?=protection($attribute['name'], 'display')?></td>
                                </tr>
<?
            }
            $step['i']++;
        }
    } else {

?>
                                <tr data-id="<?=$product['id_item']?>" data-role="parent" data-count="1">
                                    <td data-name="id"><?=$product['id_item']?></td>
                                    <td></td>
                                    <td></td>
                                    <td title="<?=protection($product['name'] . ' ' . $product['model'], 'display')?>"><a id="view_product" href="javascript:void(0);" onclick="viewProductInfo('<?=$product['id_item']?>');"><?=protection($product['name'] . ' ' . $product['model'], 'display')?></a></td>
                                    <td data-name="price"><?=number_format(abs(floatval($product['price'])), 2, '.', '')?></td>
                                    <td data-name="count"><?=abs(intval($product['count']))?></td>
                                    <td data-name="amount"><?=number_format(abs(floatval($product['amount'])), 2, '.', '')?></td>
                                    <td data-name="remove"><i class="fa fa-remove" style="color: #900; cursor: pointer" onclick="removeProductItem(this)" title="????????????"></i></td>
                                </tr>
<?
    }
}
?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td align="left" colspan="5"><a href="javascript:void(0);" onclick="addProductInTable('order-products');"><i class="fa fa-plus-circle"></i> <span class="dashed">???????????????? ??????????</span></a> <span class="f-r">??????????:</span</td>
                                    <td align="right" id="order-products-count"><span><?=$count?></span></td>
                                    <td colspan="2" id="order-products-amount" style="border-right: none;"><span style="color: #900; font-size: 14px"><?=number_format($amount, 2, '.', '')?></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="modal-window-content__title">
<?
    $add_sale = $db->query("SELECT COUNT(*) FROM `add_sales_products` WHERE `order_id` = '" . $order['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
    $add_sale_amount = 0;
?>
                        <label class="toggle">
                            <input id="add-sale" type="checkbox" name="add_sale" class="toggle__input"<?=($add_sale[0] > 0 ? ' checked' : '')?>>
                            <div class="toggle__control"></div>
                        </label>
                        ??????????????????
                    </div>

                    <div id="state-add-sale" class="modal-window-content__value" style="text-align: center; font-size: 14px; color: #757575;<?=($add_sale[0] > 0 ? ' display: none' : '')?>">
                        ?????? ?????????????????? ?? ????????????
                    </div>

                    <div class="modal-window-content__table" id="add-sale-block" style="display: <?=($add_sale[0] > 0 ? 'block' : 'none')?>">
                        <table id="add-sale-products" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>id</th>
                                    <th>sub_id</th>
                                    <th>sub_name</th>
                                    <th>??????????</th>
                                    <th>????????</th>
                                    <th>??????-????</th>
                                    <th>??????????</th>
                                    <th><i class="fa fa-remove"></i></th>
                                </tr>
                            </thead>
                            <tbody>
<?
$products = $db->query("SELECT `products`.`id_item`, `products`.`name`, `products`.`model`, `add_sales_products`.`count`, `add_sales_products`.`id` AS `add_sales_product_id`, `add_sales_products`.`price`, `add_sales_products`.`amount` FROM `products` INNER JOIN `add_sales_products` ON (`products`.`id_item` = `add_sales_products`.`product_id`) WHERE `add_sales_products`.`order_id` = '" . $order['id_item'] . "' AND `products`.`client_id` = '" . $chief['id'] . "' AND `add_sales_products`.`client_id` = '" . $chief['id'] . "'");
$count = $add_sale_amount = 0;
while ($product = $products->fetch_assoc()) {
    $count += abs(intval($product['count']));
    $add_sale_amount += abs(floatval($product['amount']));

    if (($attrs = $db->query("SELECT COUNT(*) FROM `add_sales_attributes` WHERE `order_id` = '" . $order['id_item'] . "' AND `add_sales_product_id` = '" . $product['add_sales_product_id'] . "' AND `product_id` = '" . $product['id_item'] . "'")->fetch_row()) and $attrs[0] > 0) {
        $attributes = $db->query("SELECT `attribute_id` FROM `add_sales_attributes` WHERE `order_id` = '" . $order['id_item'] . "' AND `add_sales_product_id` = '" . $product['add_sales_product_id'] . "' AND `product_id` = '" . $product['id_item'] . "'");
        $step['i'] = 1;
        while ($attribute = $attributes->fetch_assoc()) {
            $attribute = $db->query("SELECT `id_item`, `name`, `category_id` FROM `attributes` WHERE `id_item` = '" . $attribute['attribute_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $category = $db->query("SELECT `id_item`, `name` FROM `attribute_categories` WHERE `id_item` = '" . $attribute['category_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            if ($step['i'] == 1) {
?>
                                <tr data-id="<?=$product['id_item']?>" data-role="parent" data-count="<?=$attrs[0]?>">
                                    <td data-name="id" rowspan="<?=$attrs[0]?>"><?=$product['id_item']?></td>
                                    <td data-name="attribute_id" data-value="<?=$attribute['id_item']?>" title="<?=protection($category['name'], 'display')?>"><?=$category['id_item']?></td>
                                    <td><?=protection($attribute['name'], 'display')?></td>
                                    <td rowspan="<?=$attrs[0]?>" title="<?=protection($product['name'] . ' ' . $product['model'], 'display')?>"><a id="view_product" href="javascript:void(0);" onclick="viewProductInfo('<?=$product['id_item']?>');"><?=protection($product['name'] . ' ' . $product['model'], 'display')?></a></td>
                                    <td data-name="price" rowspan="<?=$attrs[0]?>"><?=number_format(abs(floatval($product['price'])), 2, '.', '')?></td>
                                    <td data-name="count" rowspan="<?=$attrs[0]?>"><?=abs(intval($product['count']))?></td>
                                    <td data-name="amount" rowspan="<?=$attrs[0]?>"><?=number_format(abs(floatval($product['amount'])), 2, '.', '')?></td>
                                    <td data-name="remove" rowspan="<?=$attrs[0]?>"><i class="fa fa-remove" style="color: #900; cursor: pointer" onclick="removeProductItem(this)" title="????????????"></i></td>
                                </tr>
<?
            } else {
?>
                                <tr data-id="<?=$product['id_item']?>" data-role="child">
                                    <td data-name="attribute_id" data-value="<?=$attribute['id_item']?>" title="<?=protection($category['name'], 'display')?>"><?=$category['id_item']?></td>
                                    <td style="border-right: 1px solid #eee"><?=protection($attribute['name'], 'display')?></td>
                                </tr>
<?
            }
            $step['i']++;
        }
    } else {

?>
                                <tr data-id="<?=$product['id_item']?>" data-role="parent" data-count="1">
                                    <td data-name="id"><?=$product['id_item']?></td>
                                    <td></td>
                                    <td></td>
                                    <td title="<?=protection($product['name'] . ' ' . $product['model'], 'display')?>"><a id="view_product" href="javascript:void(0);" onclick="viewProductInfo('<?=$product['id_item']?>');"><?=protection($product['name'] . ' ' . $product['model'], 'display')?></a></td>
                                    <td data-name="price"><?=number_format(abs(floatval($product['price'])), 2, '.', '')?></td>
                                    <td data-name="count"><?=abs(intval($product['count']))?></td>
                                    <td data-name="amount"><?=number_format(abs(floatval($product['amount'])), 2, '.', '')?></td>
                                    <td data-name="remove"><i class="fa fa-remove" style="color: #900; cursor: pointer" onclick="removeProductItem(this)" title="????????????"></i></td>
                                </tr>
<?
    }
}
if ($add_sale[0] == 0) {
?>
                                <tr data-id="0">
                                    <td colspan="8" style="padding: 15px 0">
                                        <span style="color: #900; font-size: 14px; font-weight: 700">?????? ????????????</span>
                                    </td>
                                </tr>
<?
}
?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td align="left" colspan="5"><a href="javascript:void(0);" onclick="addProductInTable('add-sale-products');"><i class="fa fa-plus-circle"></i> <span class="dashed">???????????????? ??????????</span></a> <span class="f-r">??????????:</span</td>
                                    <td align="right" id="add-sale-products-count"><span><?=$count?></span></td>
                                    <td id="add-sale-products-amount" colspan="2" style="border-right: none;"><span style="color: #900; font-size: 14px"><?=number_format($add_sale_amount, 2, '.', '')?></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
            <div class="modal-window-content__amount">?????????? ????????????: <span id="total-amount"><?=number_format(($amount + $add_sale_amount), 2, '.', '')?></span></div>
            <div class="buttons">
                <button id="button-save-changes" name="save-changes">?????????????????? ?? ??????????????</button>
            </div>
            <input type="submit" style="display: none">
        </form>
    
<?
    } else {
?>
    ?????? ???????????????????? ???????????????? ???????????????? ????????????!
<?
    }
} else {
    exit('Something went wrong..');
}
