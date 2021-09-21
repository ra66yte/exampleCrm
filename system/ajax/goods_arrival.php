<?php
include_once '../core/begin.php';

$rows = array();
$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];

if (isset($_GET['module']) and $_GET['module'] == 'search') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $count = $db->query("SELECT COUNT(*) FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
        if ($count == 0) {
            $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => null, 'pagination' => $pagination));
            exit;
        } else {
            $search_keys = $search_values = array();
            $search_products = "";
            $date_time_start = $date_time_end = null;
            foreach ($_POST as $key => $value) {
                if ($key != null && $value != null) {
                        if ($key == 'id') {
                            $key = 'id_item';

                            $search_keys[] = $key;
                            $search_values[] = $value;
                        } elseif ($key == 'product') {
                            $search_products .= "SELECT DISTINCT `arrival_of_goods`.`id_item` FROM `arrival_of_goods` INNER JOIN `arrival_of_goods-products` ON (`arrival_of_goods`.`id_item` = `arrival_of_goods-products`.`arrival_id`) WHERE `arrival_of_goods-products`.`product_id` = '" . abs(intval($value)) . "' AND  `arrival_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `arrival_of_goods`.`client_id` = '" . $chief['id'] . "'";
                            $search_keys[] = '';
                            $search_values[] = '';
                        } elseif ($key == 'date_added_start') {
                            $date_start = date_create_from_format('d-m-Y', $value);
                            $date_start =  date_format($date_start, 'Y-m-d');
                            $date_time_start = strtotime($date_start);

                            $search_keys[] = 'date_added_start';
                            $search_values[] = $date_time_start;
                        } elseif ($key == 'date_added_end') {
                            $date_end = date_create_from_format('d-m-Y', $value);
                            $date_end =  date_format($date_end, 'Y-m-d');
                            $date_time_end = strtotime($date_end) + 86400;

                            $search_keys[] = 'date_added_end';
                            $search_values[] = $date_time_end;
                        } else {
                            $search_keys[] = $key;
                            $search_values[] = $value;
                        }
                } else {
                    unset($_POST[$key]);
                }
            }

            $chunk = '';
    
            if (count($search_values) != 0) {
                $symbol = null;
                if ($search_keys[0] != '') {
                    if ($search_keys[0] == 'date_added_start') {
                        $symbol = ">";
                        $search_keys[0] = 'date_added';
                    } elseif ($search_keys[0] == 'date_added_end') {
                        $symbol = "<";
                        $search_keys[0] = 'date_added';
                    }
                    $chunk = "AND `" . protection($search_keys[0], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[0], 'base') . "'" : "LIKE '%" . protection($search_values[0], 'base') . "%'") . "";
                }

                for ($i = 1; $i < count($search_values); $i++) {
                    if ($search_keys[$i] != '') {
                        if ($search_keys[$i] == 'date_added_start') {
                            $symbol = ">";
                            $search_keys[$i] = 'date_added';
                        } elseif ($search_keys[$i] == 'date_added_end') {
                            $symbol = "<";
                            $search_keys[$i] = 'date_added';
                        }
                        $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[$i], 'base') . "'" : "LIKE '%" . protection($search_values[$i], 'base') . "%'") . "";
                    }
                }

                // $sql = "" . (($chunk != '') ? "SELECT COUNT(*) FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "' " . $chunk . "": '') . " " . (($chunk == '') ? (($search_products != '') ? "" . $search_products . "" : "AND `arrival_of_goods`.`id_item` IN (" . $search_products . ")") : (($search_products != '') ? "AND `arrival_of_goods`.`id_item` IN (" . $search_products . ")" : "" . $search_products . "")) . "";

                $sql = "SELECT COUNT(*) FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "'" . ($chunk != '' ? " " . $chunk : '') . ($search_products != '' ? " AND `arrival_of_goods`.`id_item` IN (" . $search_products . ")" : '') . "";

                $count = $db->query($sql)->fetch_row();
    
                if ($count[0] == 0) {
                    $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                    echo json_encode(array('rows' => null, 'pagination' => $pagination));
                    exit;
                } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;

                    $items = $db->query("SELECT `arrival_of_goods`.* FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "'" . ($chunk != '' ? " " . $chunk : '') . ($search_products != '' ? " AND `arrival_of_goods`.`id_item` IN (" . $search_products . ")" : '') . " ORDER by `arrival_of_goods`.`id_item` DESC LIMIT $start, $items_on_page");

                    while ($arrival = $items->fetch_assoc()) {
                        $supplier = $db->query("SELECT `name` FROM `suppliers` WHERE `id_item` = '" . $arrival['supplier_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                        $employee = $db->query("SELECT `name` FROM `user` WHERE `id_item` = '" . $arrival['employee_id'] . "' AND (`chief_id` = '" . $chief['id'] . "' OR `chief_id` = '0')")->fetch_assoc();

                        $products = $db->query("SELECT `arrival_of_goods-products`.`id` AS `arrival_products_id`, `arrival_of_goods-products`.`product_id`, `arrival_of_goods-products`.`count`, `arrival_of_goods-products`.`price`, `products`.`name`, `products`.`model` FROM `arrival_of_goods-products` INNER JOIN `products` ON (`arrival_of_goods-products`.`product_id` = `products`.`id_item`) WHERE `arrival_of_goods-products`.`arrival_id` = '" . $arrival['id_item'] . "' AND `arrival_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `products`.`client_id` = '" . $chief['id'] . "'");

                        $arrival_products = array();

                        while ($product = $products->fetch_assoc()) {
                            $attribute_items = '';
                
                            $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `arrival_of_goods-attributes` ON (`attributes`.`id_item` = `arrival_of_goods-attributes`.`attribute_id`) WHERE `arrival_of_goods-attributes`.`arrival_products_id` = '" . $product['arrival_products_id'] . "' AND  `arrival_of_goods-attributes`.`client_id` = '" . $chief['id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "'");

                            while ($attribute = $attributes->fetch_assoc()) {
                                $attribute_items .= $attribute['name'] . ', ';
                            }

                            $product_name = $product['name'] . ' ' . $product['model'];
                            if (mb_strlen($product_name, 'UTF-8') > 30) $product_name = substr($product_name, 0, 30) . '...';

                            $arrival_products[] = array(
                                'id_item' => $product['product_id'],
                                'name' => $product_name,
                                'count' => $product['count'],
                                'price' => $product['price'],
                                'attribute_items' => rtrim($attribute_items, ', ')
                            );
                        }

                        $rows[] = array(
                            'id_item' => $arrival['id_item'],
                            'supplier' => protection($supplier['name'], 'display'),
                            'employee' => protection($employee['name'], 'display'),
                            'comment' => protection($arrival['comment'], 'display'),
                            'products' => $arrival_products,
                            'io' => protection($arrival['incoming_order'], 'display'),
                            'amount' => number_format($arrival['amount'], 2, '.', ''),
                            'date_added' => passed_time($arrival['date_added'])
                        );
                    }
                }
            } else {
                $countPages = k_page($count[0], $items_on_page);
                $currentPage = page($countPages);
                $start = ($currentPage * $items_on_page) - $items_on_page;

                $items = $db->query("SELECT `id_item`, `supplier_id`, `employee_id`, `incoming_order`, `comment`, `date_added`, `amount` FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "' ORDER BY `id_item` DESC LIMIT $start, $items_on_page");
        
                while ($arrival = $items->fetch_assoc()) {
                    $supplier = $db->query("SELECT `name` FROM `suppliers` WHERE `id_item` = '" . $arrival['supplier_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                    $employee = $db->query("SELECT `name` FROM `user` WHERE `id_item` = '" . $arrival['employee_id'] . "' AND (`chief_id` = '" . $chief['id'] . "' OR `chief_id` = '0')")->fetch_assoc();

                    $products = $db->query("SELECT `arrival_of_goods-products`.`id` AS `arrival_products_id`, `arrival_of_goods-products`.`product_id`, `arrival_of_goods-products`.`count`, `arrival_of_goods-products`.`price`, `products`.`name`, `products`.`model` FROM `arrival_of_goods-products` INNER JOIN `products` ON (`arrival_of_goods-products`.`product_id` = `products`.`id_item`) WHERE `arrival_of_goods-products`.`arrival_id` = '" . $arrival['id_item'] . "' AND `arrival_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `products`.`client_id` = '" . $chief['id'] . "'");

                    $arrival_products = array();

                    while ($product = $products->fetch_assoc()) {
                        $attribute_items = '';
            
                        $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `arrival_of_goods-attributes` ON (`attributes`.`id_item` = `arrival_of_goods-attributes`.`attribute_id`) WHERE `arrival_of_goods-attributes`.`arrival_products_id` = '" . $product['arrival_products_id'] . "' AND  `arrival_of_goods-attributes`.`client_id` = '" . $chief['id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "'");

                        while ($attribute = $attributes->fetch_assoc()) {
                            $attribute_items .= $attribute['name'] . ', ';
                        }

                        $product_name = $product['name'] . ' ' . $product['model'];
                        if (mb_strlen($product_name, 'UTF-8') > 30) $product_name = substr($product_name, 0, 30) . '...';

                        $arrival_products[] = array(
                            'id_item' => $product['product_id'],
                            'name' => $product_name,
                            'count' => $product['count'],
                            'price' => $product['price'],
                            'attribute_items' => rtrim($attribute_items, ', ')
                        );
                    }

                    $rows[] = array(
                        'id_item' => $arrival['id_item'],
                        'supplier' => protection($supplier['name'], 'display'),
                        'employee' => protection($employee['name'], 'display'),
                        'comment' => protection($arrival['comment'], 'display'),
                        'products' => $arrival_products,
                        'io' => protection($arrival['incoming_order'], 'display'),
                        'amount' => number_format($arrival['amount'], 2, '.', ''),
                        'date_added' => passed_time($arrival['date_added'])
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
    $count = $db->query("SELECT COUNT(*) FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] > 0) {
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;

        $arrivals = $db->query("SELECT `id_item`, `supplier_id`, `employee_id`, `incoming_order`, `comment`, `date_added`, `amount` FROM `arrival_of_goods` WHERE `client_id` = '" . $chief['id'] . "' ORDER by `id_item` DESC LIMIT $start, $items_on_page");
        while ($arrival = $arrivals->fetch_assoc()) {
            $supplier = $db->query("SELECT `name` FROM `suppliers` WHERE `id_item` = '" . $arrival['supplier_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $employee = $db->query("SELECT `name` FROM `user` WHERE `id_item` = '" . $arrival['employee_id'] . "' AND (`chief_id` = '" . $chief['id'] . "' OR `chief_id` = '0')")->fetch_assoc();

            $products = $db->query("SELECT `arrival_of_goods-products`.`id` AS `arrival_products_id`, `arrival_of_goods-products`.`product_id`, `arrival_of_goods-products`.`count`, `arrival_of_goods-products`.`price`, `products`.`name`, `products`.`model` FROM `arrival_of_goods-products` INNER JOIN `products` ON (`arrival_of_goods-products`.`product_id` = `products`.`id_item`) WHERE `arrival_of_goods-products`.`arrival_id` = '" . $arrival['id_item'] . "' AND `arrival_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `products`.`client_id` = '" . $chief['id'] . "'");

            $arrival_products = array();

            while ($product = $products->fetch_assoc()) {
                $attribute_items = '';
    
                $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `arrival_of_goods-attributes` ON (`attributes`.`id_item` = `arrival_of_goods-attributes`.`attribute_id`) WHERE `arrival_of_goods-attributes`.`arrival_products_id` = '" . $product['arrival_products_id'] . "' AND  `arrival_of_goods-attributes`.`client_id` = '" . $chief['id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "'");

                while ($attribute = $attributes->fetch_assoc()) {
                    $attribute_items .= $attribute['name'] . ', ';
                }

                $product_name = $product['name'] . ' ' . $product['model'];
                if (mb_strlen($product_name, 'UTF-8') > 30) $product_name = substr($product_name, 0, 30) . '...';

                $arrival_products[] = array(
                    'id_item' => $product['product_id'],
                    'name' => $product_name,
                    'count' => $product['count'],
                    'price' => $product['price'],
                    'attribute_items' => rtrim($attribute_items, ', ')
                );
            }

            $rows[] = array(
                'id_item' => $arrival['id_item'],
                'supplier' => protection($supplier['name'], 'display'),
                'employee' => protection($employee['name'], 'display'),
                'comment' => protection($arrival['comment'], 'display'),
                'products' => $arrival_products,
                'io' => protection($arrival['incoming_order'], 'display'),
                'amount' => number_format($arrival['amount'], 2, '.', ''),
                'date_added' => passed_time($arrival['date_added'])
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
