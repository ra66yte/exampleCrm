<?php
include_once '../core/begin.php';
function change_status_categories($category, $status) {
    global $db, $chief;
    $subcategories = $db->query("SELECT `id_item`, `status` FROM `product_categories` WHERE `parent_id` = '" . abs(intval($category)) . "' AND `client_id` = '" . $chief['id'] . "'");
    while ($subcategory = $subcategories->fetch_assoc()) {
        // Меняем статус подкатегорий
        change_status_categories($subcategory['id_item'], $status);
    }
    // Меняем статус главной категории
    if ($db->query("UPDATE `product_categories` SET `status` = '" . $status . "' WHERE `id_item` = '" . abs(intval($category)) . "' AND `client_id` = '" . $chief['id'] . "'")) {
        return true;
    } 
    return false;
}

$success = $error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_GET['location'])) {
            $id = abs(intval($_POST['id_item']));
            $status = $_POST['status'];

            
        if ($_GET['location'] == 'product_categories') {
            
            if ($status == 'on') {
                $status = 'off';
            } else {
                $status = 'on';
            }
            if (change_status_categories($id, $status)) {
                $success = 1;
            } else {
                $error = 'Не удалось изменить статус категории!';
            }
            echo json_encode(array('success' => $success, 'error' => $error));
        } elseif ($_GET['location'] == 'order_statuses') {

            if ($status == 'on') {
                $status = 'off';
            } else {
                $status = 'on';
            }
            if ($db->query("SELECT `id` FROM `status_order` WHERE `id_item` = '" . $id . "' AND `permanent` = '0' AND `client_id` = '" . $chief['id'] . "'")->num_rows > 0) {
                $success = 1;
                $db->query("UPDATE `status_order` SET `status` = '" . $status . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось изменить статус статуса заказов!';
            }
            echo json_encode(array('success' => $success, 'error' => $error));
        } elseif ($_GET['location'] == 'payment_methods') {

            if ($status == 'on') {
                $status = 'off';
            } else {
                $status = 'on';
            }
            if ($db->query("SELECT `id` FROM `payment_methods` WHERE `id_item` = '" . $id . "' AND `permanent` = 'off' AND `client_id` = '" . $chief['id'] . "'")->num_rows > 0) {
                $success = 1;
                $db->query("UPDATE `payment_methods` SET `status` = '" . $status . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось изменить статус способа оплаты!';
            }
            echo json_encode(array('success' => $success, 'error' => $error));
        } elseif ($_GET['location'] == 'delivery_methods') {

            if ($status == 'on') {
                $status = 'off';
            } else {
                $status = 'on';
            }
            if ($db->query("SELECT `id` FROM `delivery_methods` WHERE `id_item` = '" . $id . "' AND `permanent` = 'off' AND `client_id` = '" . $chief['id'] . "'")->num_rows > 0) {
                $success = 1;
                $db->query("UPDATE `delivery_methods` SET `status` = '" . $status . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'");
            } else {
                $error = 'Не удалось изменить статус способа доставки!';
            }
            echo json_encode(array('success' => $success, 'error' => $error));
        } elseif ($_GET['location'] == 'products') {

            if ($status == 'on') {
                $status = 'off';
            } else {
                $status = 'on';
            }
            if ($db->query("UPDATE `products` SET `status` = '" . $status . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
                $success = 1;
            } else {
                $error = 'Не удалось изменить статус товара!';
            }
            echo json_encode(array('success' => $success, 'error' => $error));
        } elseif ($_GET['location'] == 'attribute_categories') {

            if ($status == 'on') {
                $status = 'off';
            } else {
                $status = 'on';
            }
            if ($db->query("UPDATE `attribute_categories` SET `status` = '" . $status . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
                $success = 1;
            } else {
                $error = 'Не удалось изменить статус категории атрибутов!';
            }
            echo json_encode(array('success' => $success, 'error' => $error));
        } elseif ($_GET['location'] == 'attributes') {

            if ($status == 'on') {
                $status = 'off';
            } else {
                $status = 'on';
            }
            if ($db->query("UPDATE `attributes` SET `status` = '" . $status . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
                $success = 1;
            } else {
                $error = 'Не удалось изменить статус атрибута!';
            }
            echo json_encode(array('success' => $success, 'error' => $error));
        } elseif ($_GET['location'] == 'colors') {

            if ($status == 'on') {
                $status = 'off';
            } else {
                $status = 'on';
            }
            if ($db->query("UPDATE `colors` SET `status` = '" . $status . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
                $success = 1;
            } else {
                $error = 'Не удалось изменить статус цвета!';
            }
            echo json_encode(array('success' => $success, 'error' => $error));
        } elseif ($_GET['location'] == 'plugins') {

            if ($status == '0') {
                $status = '1';
            } else {
                $status = '0';
            }
            if ($db->query("UPDATE `plugins` SET `status` = '" . $status . "' WHERE `plugin_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
                $success = 1;
            } else {
                $error = 'Не удалось изменить статус модуля!';
            }
            echo json_encode(array('success' => $success, 'error' => $error));
        } elseif ($_GET['location'] == 'countries') {

            if ($status == '0') {
                $status = '1';
                if ($result = $db->query("SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
                    if ($db->query("INSERT INTO `countries_list` (`id`, `client_id`, `country_id`) VALUES (null, '" . $chief['id'] . "', '" . $id . "')")) {
                        $success = 1;
                    } else {
                        $error = 'Не удалось изменить статус!';
                    }
                }
            } else {
                $status = '0';
                if ($db->query("DELETE FROM `countries_list` WHERE `country_id` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
                    $success = 1;
                } else {
                    $error = 'Не удалось изменить статус!';
                }
            }

            echo json_encode(array('success' => $success, 'error' => $error));
        }
    }
}
