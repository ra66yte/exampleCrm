<?php
include_once '../core/begin.php';

$rows = array();
$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];

if (isset($_GET['module']) and $_GET['module'] == 'search') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $count = $db->query("SELECT COUNT(*) FROM `products` WHERE  `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
        if ($count[0] == 0) {
            $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => null, 'pagination' => $pagination));
            exit;
        } else {
    
            $search_keys = $search_values = array();
            foreach ($_POST as $key => $value) {
                if ($key != null && $value != null) {
                    if ($key == 'id') $key = 'id_item';
                    $search_keys[] = $key;
                    $search_values[] = $value;
                } else {
                    unset($_POST[$key]);
                }
            }
    
            if (count($search_values) != 0) {
    
                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $count = $db->query("SELECT COUNT(*) FROM `products` WHERE " . $chunk . " AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
                if ($count[0] == 0) {
                    $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                    echo json_encode(array('rows' => null, 'pagination' => $pagination));
                    exit;
                } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;
    
                    $items = $db->query("SELECT `id_item`, `image`, `name`, `model`, `status`, `vendor_code`, `manufacturer`, `category`, `currency`, `date_added`, `count`, `purchase_price`, `base_price` FROM `products` WHERE " . $chunk . " AND `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "' ORDER by `id` ASC LIMIT $start, $items_on_page");
                    while ($product = $items->fetch_assoc()) {
                        $category = $db->query("SELECT `name` FROM `product_categories` WHERE `id_item` = '" . $product['category'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                        $manufacturer = $db->query("SELECT `name` FROM `manufacturers` WHERE `id_item` = '" . $product['manufacturer'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                        $currency = $db->query("SELECT `name`, `symbol` FROM `currencies` WHERE `id_item` = '" . $product['currency'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                        $in_orders = /* $db->query("SELECT `id` FROM `orders` WHERE `client_id` = '" . $chief['id'] . "' AND `product` = '" . $product['id'] . "'")->num_rows; */ 0;
                        bcscale(2);
                        $total_amount = bcmul($product['count'], $product['base_price']);
                        $color = $product['count'] <= 0 ? 'red' : 'green';

                        $rows[] = array(
                            'id_item' => $product['id_item'],
                            'image' => protection($product['image'], 'display'),
                            'name' => protection($product['name'], 'display'),
                            'model' => protection($product['model'], 'display'),
                            'status' => $product['status'],
                            'vendor' => protection($product['vendor_code'], 'display'),
                            'manufacturer' => protection($manufacturer['name'], 'display'),
                            'category' => protection($category['name'], 'display'),
                            'date_added' => passed_time($product['date_added']),
                            'count' => $product['count'],
                            'in_orders' => $in_orders,
                            'purchase_price' => number_format($product['purchase_price'], 2, '.', ''),
                            'base_price' => number_format($product['base_price'], 2, '.', ''),
                            'currency_name' => protection($currency['name'], 'display'),
                            'currency_symbol' => protection($currency['symbol'], 'display'),
                            'total_amount' => number_format($total_amount, 2, '.', ''),
                            'client_id' => $chief['id']
                        );
                    }
                }
    
            } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;
        
                    $items = $db->query("SELECT `id_item`, `image`, `name`, `model`, `status`, `vendor_code`, `manufacturer`, `category`, `currency`, `date_added`, `count`, `purchase_price`, `base_price` FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "' ORDER by `id` ASC LIMIT $start, $items_on_page");
        
                    while ($product = $items->fetch_assoc()) {
                        $category = $db->query("SELECT `name` FROM `product_categories` WHERE `id_item` = '" . $product['category'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                        $manufacturer = $db->query("SELECT `name` FROM `manufacturers` WHERE `id_item` = '" . $product['manufacturer'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                        $currency = $db->query("SELECT `name`, `symbol` FROM `currencies` WHERE `id_item` = '" . $product['currency'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                        $in_orders = /* $db->query("SELECT `id` FROM `orders` WHERE `client_id` = '" . $chief['id'] . "' AND `product` = '" . $product['id'] . "'")->num_rows; */ 0;
                        bcscale(2);
                        $total_amount = bcmul($product['count'], $product['base_price']);
                        $color = $product['count'] <= 0 ? 'red' : 'green';

                        $rows[] = array(
                            'id_item' => $product['id_item'],
                            'image' => protection($product['image'], 'display'),
                            'name' => protection($product['name'], 'display'),
                            'model' => protection($product['model'], 'display'),
                            'status' => $product['status'],
                            'vendor' => protection($product['vendor_code'], 'display'),
                            'manufacturer' => protection($manufacturer['name'], 'display'),
                            'category' => protection($category['name'], 'display'),
                            'date_added' => passed_time($product['date_added']),
                            'count' => $product['count'],
                            'in_orders' => $in_orders,
                            'purchase_price' => number_format($product['purchase_price'], 2, '.', ''),
                            'base_price' => number_format($product['base_price'], 2, '.', ''),
                            'currency_name' => protection($currency['name'], 'display'),
                            'currency_symbol' => protection($currency['symbol'], 'display'),
                            'total_amount' => number_format($total_amount, 2, '.', ''),
                            'client_id' => $chief['id']
                        );
                    }
                
            }

            $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
            exit;

        }
    }
}

if (isset($_GET['show']) and $_GET['show'] == 'true') {
    $count = $db->query("SELECT COUNT(*) FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] > 0) {
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;

        $items = $db->query("SELECT `id_item`, `image`, `name`, `model`, `status`, `vendor_code`, `manufacturer`, `category`, `currency`, `date_added`, `count`, `purchase_price`, `base_price` FROM `products` WHERE `deleted_at` = '0' AND `client_id` = '" . $chief['id'] . "' ORDER by `id` ASC LIMIT $start, $items_on_page");
        while ($product = $items->fetch_assoc()) {
            $category = $db->query("SELECT `name` FROM `product_categories` WHERE `id_item` = '" . $product['category'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $manufacturer = $db->query("SELECT `name` FROM `manufacturers` WHERE `id_item` = '" . $product['manufacturer'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $currency = $db->query("SELECT `name`, `symbol` FROM `currencies` WHERE `id_item` = '" . $product['currency'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $in_orders = /* $db->query("SELECT `id` FROM `orders` WHERE `client_id` = '" . $chief['id'] . "' AND `product` = '" . $product['id'] . "'")->num_rows; */ 0;
            bcscale(2);
            $total_amount = bcmul($product['count'], $product['base_price']);
            $color = $product['count'] <= 0 ? 'red' : 'green';

            $rows[] = array(
                'id_item' => $product['id_item'],
                'image' => protection($product['image'], 'display'),
                'name' => protection($product['name'], 'display'),
                'model' => protection($product['model'], 'display'),
                'status' => $product['status'],
                'vendor' => protection($product['vendor_code'], 'display'),
                'manufacturer' => protection($manufacturer['name'], 'display'),
                'category' => protection($category['name'], 'display'),
                'date_added' => passed_time($product['date_added']),
                'count' => $product['count'],
                'in_orders' => $in_orders,
                'purchase_price' => number_format($product['purchase_price'], 2, '.', ''),
                'base_price' => number_format($product['base_price'], 2, '.', ''),
                'currency_name' => protection($currency['name'], 'display'),
                'currency_symbol' => protection($currency['symbol'], 'display'),
                'total_amount' => number_format($total_amount, 2, '.', ''),
                'client_id' => $chief['id']
            );
        }
        $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
        exit;
    } else {
        $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => null, 'pagination' => $pagination));
        exit;
    }
}
