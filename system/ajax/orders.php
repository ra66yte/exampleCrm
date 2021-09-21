<?php
include_once '../core/begin.php';
// Поехали :D
if (isset($_GET['status'])) {
    $rows = array();
    $status = abs(intval($_GET['status']));
    $items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];
    if ($status == 0) {
        $status = "all";
    }
    // Загружаем
    $right_id = getAccessID('statuses');
    $count = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id_item`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id_item` AND `group_rights`.`client_id` = '" . $chief['id'] . "' AND `status_order`.`status` = 'on' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `orders`.`deleted_at` = '0' AND `orders`.`client_id` = '" . $chief['id'] . "'")->fetch_row();
    
    if ($count[0] == 0) {
        $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => 'empty', 'pagination' => $pagination));
        exit;
    } else {
        if ($status == "all") {
            $countPages = k_page($count[0], $items_on_page);
            $currentPage = page($countPages);
            $start = ($currentPage * $items_on_page) - $items_on_page;

            $items = $db->query("SELECT `orders`.* FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id_item`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id_item` AND `group_rights`.`client_id` = '" . $chief['id'] . "' AND `status_order`.`status` = 'on' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `orders`.`deleted_at` = '0' AND `orders`.`client_id` = '" . $chief['id'] . "' ORDER BY `orders`.`id` DESC LIMIT $start, $items_on_page");
            while ($order = $items->fetch_assoc()) {
                $this_order = $db->query("SELECT `name`, `color` FROM `status_order` WHERE `id_item` = '" . $order['status'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                $this_payment = $db->query("SELECT `icon`, `name` FROM `payment_methods` WHERE `id_item` = '" . $order['payment_method'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                $this_delivery = $db->query("SELECT `icon`, `name` FROM `delivery_methods` WHERE `id_item` = '" . $order['delivery_method'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                $country = $db->query("SELECT `name`, `code` FROM `countries` WHERE `id` = '" . $order['country'] . "'")->fetch_assoc();
                $completed = $order['completed'] == 0 ? 'Нет' : 'Да';

                $item_products = array();
                $total_count = $db->query("SELECT SUM(`count`) FROM `orders_products` WHERE `order_id` = '" . $order['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
                $products = $db->query("SELECT `products`.`name`, `products`.`model`, `orders_products`.`product_id`, `orders_products`.`price`, `orders_products`.`count`, `orders_products`.`amount` FROM `orders_products` INNER JOIN `products` ON (`products`.`id_item` = `orders_products`.`product_id`) WHERE `orders_products`.`order_id` = '" . $order['id_item'] . "' AND `orders_products`.`client_id` = '" . $chief['id'] . "' AND `products`.`client_id` = '" . $chief['id'] . "'");
                while ($product = $products->fetch_assoc()) {
                    $item_products[] = array(
                        'id_item' => $product['product_id'],
                        'name' => protection($product['name'] . ' ' . $product['model'], 'display'),
                        'price' => $product['price'],
                        'count' => $product['count'],
                        'amount' => $product['amount']
                    );
                }

                $rows[] = array(
                    'id_item' => $order['id_item'],
                    'id_order' => $order['id_order'],
                    'customer' => protection($order['customer'], 'display'),
                    'country_code' => protection($country['code'], 'display'),
                    'country_name' => protection($country['name'], 'display'),
                    'phone' => protection($order['phone'], 'display'),
                    'comment' => protection($order['comment'], 'display'),
                    'amount' => number_format($order['amount'], 2, '.', ' '),
                    'products' => $item_products,
                    'count_products' => $total_count[0],
                    'payment_method_icon' => protection($this_payment['icon'], 'display'),
                    'payment_method' => protection($this_payment['name'], 'display'),
                    'delivery_method_icon' => protection($this_delivery['icon'], 'display'),
                    'delivery_method' => protection($this_delivery['name'], 'display'),
                    'delivery_adress' => protection($order['delivery_address'], 'display'),
                    'ttn' => protection($order['ttn'], 'display'),
                    'ttn_status' => protection($order['ttn_status'], 'display'),
                    'departure_date' => view_time($order['departure_date']),
                    'date_added' => view_time($order['date_added']),
                    'updated' => ($order['date_added'] == $order['updated'] ? true : view_time($order['updated'])),
                    'employee' => $order['employee'],
                    'site' => protection($order['site'], 'display'),
                    'ip' => long2ip($order['ip']),
                    'order_status' => protection($this_order['name'], 'display'),
                    'complete' => $completed,
                    'status_color' => protection($this_order['color'], 'display'),
                    'blocked' => $order['blocked']
                );
            }
        } else {

            if ($result = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id_item`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id_item` AND `group_rights`.`client_id` = '" . $chief['id'] . "' AND `status_order`.`status` = 'on' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `orders`.`deleted_at` = '0' AND `orders`.`status` = '" . $status . "' AND `orders`.`client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) { // если такго статуса нет или в нем нет заказов
                $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                echo json_encode(array('rows' => 'empty', 'pagination' => $pagination));
                exit;
            }

            $count = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id_item`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id_item` AND `group_rights`.`client_id` = '" . $chief['id'] . "' AND `status_order`.`status` = 'on' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `orders`.`deleted_at` = '0' AND `orders`.`status` = '" . $status . "' AND `orders`.`client_id` = '" . $chief['id'] . "'")->fetch_row();
            $countPages = k_page($count[0], $items_on_page);
            $currentPage = page($countPages);
            $start = ($currentPage * $items_on_page) - $items_on_page;

            $items = $db->query("SELECT `orders`.* FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id_item`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id_item` AND `group_rights`.`client_id` = '" . $chief['id'] . "' AND `status_order`.`status` = 'on' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `orders`.`deleted_at` = '0' AND `orders`.`status` = '" . $status . "' AND `orders`.`client_id` = '" . $chief['id'] . "' ORDER by `id` DESC LIMIT $start, $items_on_page");
            while ($order = $items->fetch_assoc()) {
                $this_order = $db->query("SELECT `name`, `color` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $order['status'] . "'")->fetch_assoc();
                $this_payment = $db->query("SELECT `icon`, `name` FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $order['payment_method'] . "'")->fetch_assoc();
                $this_delivery = $db->query("SELECT `icon`, `name` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $order['delivery_method'] . "'")->fetch_assoc();
                $country = $db->query("SELECT `name`, `code` FROM `countries` WHERE `id` = '" . $order['country'] . "'")->fetch_assoc();
                $completed = $order['completed'] == 0 ? 'Нет' : 'Да';
                
                $item_products = array();
                $total_count = $db->query("SELECT SUM(`count`) FROM `orders_products` WHERE `order_id` = '" . $order['id_item'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
                $products = $db->query("SELECT `products`.`name`, `products`.`model`, `orders_products`.`product_id`, `orders_products`.`price`, `orders_products`.`count`, `orders_products`.`amount` FROM `orders_products` INNER JOIN `products` ON (`products`.`id_item` = `orders_products`.`product_id`) WHERE `orders_products`.`order_id` = '" . $order['id_item'] . "' AND `orders_products`.`client_id` = '" . $chief['id'] . "' AND `products`.`client_id` = '" . $chief['id'] . "'");
                while ($product = $products->fetch_assoc()) {
                    $item_products[] = array(
                        'id_item' => $product['product_id'],
                        'name' => protection($product['name'] . ' ' . $product['model'], 'display'),
                        'price' => $product['price'],
                        'count' => $product['count'],
                        'amount' => $product['amount']
                    );
                }

                $rows[] = array(
                    'id_item' => $order['id_item'],
                    'id_order' => $order['id_order'],
                    'customer' => protection($order['customer'], 'display'),
                    'country_code' => protection($country['code'], 'display'),
                    'country_name' => protection($country['name'], 'display'),
                    'phone' => protection($order['phone'], 'display'),
                    'comment' => protection($order['comment'], 'display'),
                    'amount' => number_format($order['amount'], 2, '.', ' '),
                    'products' => $item_products,
                    'count_products' => $total_count[0],
                    'payment_method_icon' => protection($this_payment['icon'], 'display'),
                    'payment_method' => protection($this_payment['name'], 'display'),
                    'delivery_method_icon' => protection($this_delivery['icon'], 'display'),
                    'delivery_method' => protection($this_delivery['name'], 'display'),
                    'delivery_adress' => protection($order['delivery_address'], 'display'),
                    'ttn' => protection($order['ttn'], 'display'),
                    'ttn_status' => protection($order['ttn_status'], 'display'),
                    'departure_date' => view_time($order['departure_date']),
                    'date_added' => view_time($order['date_added']),
                    'updated' => ($order['date_added'] == $order['updated'] ? true : view_time($order['updated'])),
                    'employee' => $order['employee'],
                    'site' => protection($order['site'], 'display'),
                    'ip' => long2ip($order['ip']),
                    'order_status' => protection($this_order['name'], 'display'),
                    'complete' => $completed,
                    'status_color' => protection($this_order['color'], 'display'),
                    'blocked' => $order['blocked']
                );
                
            }
        }

        $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
        exit;
    }
}
