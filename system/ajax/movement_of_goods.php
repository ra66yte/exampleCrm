<?php
include_once '../core/begin.php';

$rows = array();
$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];

if (isset($_GET['module']) and $_GET['module'] == 'search') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $count = $db->query("SELECT COUNT(*) FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
        if ($count[0] == 0) {
            $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => null, 'pagination' => $pagination));
            exit;
        } else {
            $search_keys = $search_values = array();
            $search_products = "";
            $date_time_start = $date_time_end = null;
            foreach ($_POST as $key => $value) {
                if ($key != null && $value != null) {
                        if ($key == 'product') {
                            $search_products .= "SELECT DISTINCT `movement_of_goods`.`id` FROM `movement_of_goods` INNER JOIN `movement_of_goods-products` ON (`movement_of_goods`.`id` = `movement_of_goods-products`.`mog_id`) WHERE `movement_of_goods-products`.`product_id` = '" . abs(intval($value)) . "' AND  `movement_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `movement_of_goods`.`client_id` = '" . $chief['id'] . "'";
                            $search_keys[] = '';
                            $search_values[] = '';
                        } elseif ($key == 'date_start') {
                            $date_start = date_create_from_format('d-m-Y', $value);
                            $date_start =  date_format($date_start, 'Y-m-d');
                            $date_time_start = strtotime($date_start);

                            $search_keys[] = 'date_start';
                            $search_values[] = $date_time_start;
                        } elseif ($key == 'date_end') {
                            $date_end = date_create_from_format('d-m-Y', $value);
                            $date_end =  date_format($date_end, 'Y-m-d');
                            $date_time_end = strtotime($date_end) + 86400;

                            $search_keys[] = 'date_end';
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
                if ($search_keys[0] != '') {
                    if ($search_keys[0] == 'date_start') {
                        $symbol = ">";
                        $search_keys[0] = 'date_added';
                    } elseif ($search_keys[0] == 'date_end') {
                        $symbol = "<";
                        $search_keys[0] = 'date_added';
                    }
                    $chunk = "AND `" . protection($search_keys[0], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[0], 'base') . "'" : "LIKE '%" . protection($search_values[0], 'base') . "%'") . "";
                }
                for ($i = 1; $i < count($search_values); $i++) {
                    if ($search_keys[$i] != '') {
                        if ($search_keys[$i] == 'date_start') {
                            $symbol = ">";
                            $search_keys[$i] = 'date_added';
                        } elseif ($search_keys[$i] == 'date_end') {
                            $symbol = "<";
                            $search_keys[$i] = 'date_added';
                        }
                        $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` " . (isset($symbol) ? $symbol . " '" . protection($search_values[$i], 'base') . "'" : "LIKE '%" . protection($search_values[$i], 'base') . "%'") . "";
                    }
                }

                // $sql = "" . (($chunk != '') ? "SELECT COUNT(*) FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "' " . $chunk . "": '') . " " . (($chunk == '') ? (($search_products != '') ? "" . $search_products . "" : "AND `movement_of_goods`.`id` IN (" . $search_products . ")") : (($search_products != '') ? "AND `movement_of_goods`.`id` IN (" . $search_products . ")" : "" . $search_products . "")) . "";

                $sql = "SELECT COUNT(*) FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "'" . ($chunk != '' ? " " . $chunk : '') . ($search_products != '' ? " AND `movement_of_goods`.`id` IN (" . $search_products . ")" : '') . "";

                $count = $db->query($sql)->fetch_row();
                if ($count[0] == 0) {
                    $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                    echo json_encode(array('rows' => null, 'pagination' => $pagination));
                    exit;
                } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;

                    $items = $db->query("SELECT `movement_of_goods`.* FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "'" . ($chunk != '' ? " " . $chunk : '') . ($search_products != '' ? " AND `movement_of_goods`.`id` IN (" . $search_products . ")" : '') . " ORDER by `movement_of_goods`.`id` DESC LIMIT $start, $items_on_page");
                    while ($mog = $items->fetch_assoc()) {
                        $employee = $db->query("SELECT `name` FROM `user` INNER JOIN `staff` ON (`user`.`id_item` = `staff`.`employee_id`) WHERE `staff`.`employee_id` = '" . $mog['employee_id'] . "' AND `staff`.`chief_id` = '" . $chief['id'] . "'")->fetch_assoc();

                        $products = $db->query("SELECT `movement_of_goods-products`.`id` AS `mog_product_id`, `movement_of_goods-products`.`product_id`, `movement_of_goods-products`.`balance`, `movement_of_goods-products`.`balance_with_attributes`, `movement_of_goods-products`.`change`, `movement_of_goods-products`.`date_updated` as `mog_date_updated`, `products`.`name`, `products`.`model`, `products`.`date_added` FROM `movement_of_goods-products` INNER JOIN `products` ON (`movement_of_goods-products`.`product_id` = `products`.`id`) WHERE `movement_of_goods-products`.`mog_id` = '" . $mog['id'] . "' AND `movement_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `products`.`client_id` = '" . $chief['id'] . "'");

                        $date_added = $date_updated = array();

                        $products_items = array();

                        while ($product = $products->fetch_assoc()) {
                            $attribute_items = '';
                            
                            $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `movement_of_goods-attributes` ON (`attributes`.`id_item` = `movement_of_goods-attributes`.`attribute_id`) WHERE `movement_of_goods-attributes`.`mog_product_id` = '" . $product['mog_product_id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `movement_of_goods-attributes`.`client_id` = '" . $chief['id'] . "'");
                            while ($attribute = $attributes->fetch_assoc()) {
                                $attribute_items .= $attribute['name'] . ', ';
                            }
                            $name = $product['name'] . ' ' . $product['model'];
                            if (mb_strlen($name, 'UTF-8') > 40) $name = mb_substr($name, 0, 30, 'UTF-8') . '...';
                    
                            $products_items[] = array(
                                'id_item' => $product['product_id'],
                                'name' => $name,
                                'attributes' => protection(rtrim($attribute_items, ', '), 'display'),
                                'balance' => $product['balance'],
                                'balance_with_attributes' => $product['balance_with_attributes'],
                                'minus' => (($product['change'] <=> 0) >= 0 ? '' : $product['change']),
                                'plus' => (($product['change'] <=> 0) >= 0 ? '+' . $product['change'] : ''),

                            );

                            $date_added[$product['product_id']] = view_time($product['date_added']);
                            $date_updated[$product['product_id']] = view_time($product['mog_date_updated']);
                        }

                        $rows[] = array(
                            'id_item' => $mog['id'],
                            'date_added' => view_time($mog['date_added']),
                            'order_id' => $mog['order_id'], // ToDo: 0 - приход, -1 - списание
                            'employee' => protection($employee['name'], 'display'),
                            'products' => $products_items,
                            'start' => $mog['status_start'], // ToDo: 0 - закупка, -1 - склад
                            'end' => $mog['status_end'], // ToDo: 0 - склад, -1 - утилизация
                            'products_date_added' => $date_added,
                            'products_date_updated' => $date_updated
                        );
                    }
                }
            } else {
                $countPages = k_page($count[0], $items_on_page);
                $currentPage = page($countPages);
                $start = ($currentPage * $items_on_page) - $items_on_page;

                $items = $db->query("SELECT * FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "' ORDER BY `id` DESC LIMIT $start, $items_on_page");
                while ($mog = $items->fetch_assoc()) {
                    $employee = $db->query("SELECT `name` FROM `user` INNER JOIN `staff` ON (`user`.`id_item` = `staff`.`employee_id`) WHERE `staff`.`employee_id` = '" . $mog['employee_id'] . "' AND `staff`.`chief_id` = '" . $chief['id'] . "'")->fetch_assoc();

                    $products = $db->query("SELECT `movement_of_goods-products`.`id` AS `mog_product_id`, `movement_of_goods-products`.`product_id`, `movement_of_goods-products`.`balance`, `movement_of_goods-products`.`balance_with_attributes`, `movement_of_goods-products`.`change`, `movement_of_goods-products`.`date_updated` as `mog_date_updated`, `products`.`name`, `products`.`model`, `products`.`date_added` FROM `movement_of_goods-products` INNER JOIN `products` ON (`movement_of_goods-products`.`product_id` = `products`.`id`) WHERE `movement_of_goods-products`.`mog_id` = '" . $mog['id'] . "' AND `movement_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `products`.`client_id` = '" . $chief['id'] . "'");

                    $date_added = $date_updated = array();

                    $products_items = array();

                    while ($product = $products->fetch_assoc()) {
                        $attribute_items = '';
                        
                        $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `movement_of_goods-attributes` ON (`attributes`.`id_item` = `movement_of_goods-attributes`.`attribute_id`) WHERE `movement_of_goods-attributes`.`mog_product_id` = '" . $product['mog_product_id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `movement_of_goods-attributes`.`client_id` = '" . $chief['id'] . "'");
                        while ($attribute = $attributes->fetch_assoc()) {
                            $attribute_items .= $attribute['name'] . ', ';
                        }
                        $name = $product['name'] . ' ' . $product['model'];
                        if (mb_strlen($name, 'UTF-8') > 40) $name = mb_substr($name, 0, 30, 'UTF-8') . '...';
                
                        $products_items[] = array(
                            'id_item' => $product['product_id'],
                            'name' => $name,
                            'attributes' => protection(rtrim($attribute_items, ', '), 'display'),
                            'balance' => $product['balance'],
                            'balance_with_attributes' => $product['balance_with_attributes'],
                            'minus' => (($product['change'] <=> 0) >= 0 ? '' : $product['change']),
                            'plus' => (($product['change'] <=> 0) >= 0 ? '+' . $product['change'] : ''),

                        );

                        $date_added[$product['product_id']] = view_time($product['date_added']);
                        $date_updated[$product['product_id']] = view_time($product['mog_date_updated']);
                    }

                    $rows[] = array(
                        'id_item' => $mog['id'],
                        'date_added' => view_time($mog['date_added']),
                        'order_id' => $mog['order_id'], // ToDo: 0 - приход, -1 - списание
                        'employee' => protection($employee['name'], 'display'),
                        'products' => $products_items,
                        'start' => $mog['status_start'], // ToDo: 0 - закупка, -1 - склад
                        'end' => $mog['status_end'], // ToDo: 0 - склад, -1 - утилизация
                        'products_date_added' => $date_added,
                        'products_date_updated' => $date_updated
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
    $count = $db->query("SELECT COUNT(*) FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] > 0) {
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;

        $mogs = $db->query("SELECT * FROM `movement_of_goods` WHERE `client_id` = '" . $chief['id'] . "' ORDER by `id` DESC LIMIT $start, $items_on_page");

        while ($mog = $mogs->fetch_assoc()) {
            $employee = $db->query("SELECT `name` FROM `user` INNER JOIN `staff` ON (`user`.`id_item` = `staff`.`employee_id`) WHERE `staff`.`employee_id` = '" . $mog['employee_id'] . "' AND `staff`.`chief_id` = '" . $chief['id'] . "'")->fetch_assoc();

            $products = $db->query("SELECT `movement_of_goods-products`.`id` AS `mog_product_id`, `movement_of_goods-products`.`product_id`, `movement_of_goods-products`.`balance`, `movement_of_goods-products`.`balance_with_attributes`, `movement_of_goods-products`.`change`, `movement_of_goods-products`.`date_updated` as `mog_date_updated`, `products`.`name`, `products`.`model`, `products`.`date_added` FROM `movement_of_goods-products` INNER JOIN `products` ON (`movement_of_goods-products`.`product_id` = `products`.`id`) WHERE `movement_of_goods-products`.`mog_id` = '" . $mog['id'] . "' AND `movement_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `products`.`client_id` = '" . $chief['id'] . "'");

            $date_added = $date_updated = array();

            $products_items = array();

            while ($product = $products->fetch_assoc()) {
                $attribute_items = '';
                
                $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `movement_of_goods-attributes` ON (`attributes`.`id_item` = `movement_of_goods-attributes`.`attribute_id`) WHERE `movement_of_goods-attributes`.`mog_product_id` = '" . $product['mog_product_id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `movement_of_goods-attributes`.`client_id` = '" . $chief['id'] . "'");
                while ($attribute = $attributes->fetch_assoc()) {
                    $attribute_items .= $attribute['name'] . ', ';
                }
                $name = $product['name'] . ' ' . $product['model'];
                if (mb_strlen($name, 'UTF-8') > 40) $name = mb_substr($name, 0, 30, 'UTF-8') . '...';
        
                $products_items[] = array(
                    'id_item' => $product['product_id'],
                    'name' => $name,
                    'attributes' => protection(rtrim($attribute_items, ', '), 'display'),
                    'balance' => $product['balance'],
                    'balance_with_attributes' => $product['balance_with_attributes'],
                    'minus' => (($product['change'] <=> 0) >= 0 ? '' : $product['change']),
                    'plus' => (($product['change'] <=> 0) >= 0 ? '+' . $product['change'] : ''),

                );

                $date_added[$product['product_id']] = view_time($product['date_added']);
                $date_updated[$product['product_id']] = view_time($product['mog_date_updated']);
            }

            $rows[] = array(
                'id_item' => $mog['id'],
                'date_added' => view_time($mog['date_added']),
                'order_id' => $mog['order_id'], // ToDo: 0 - приход, -1 - списание
                'employee' => protection($employee['name'], 'display'),
                'products' => $products_items,
                'start' => $mog['status_start'], // ToDo: 0 - закупка, -1 - склад
                'end' => $mog['status_end'], // ToDo: 0 - склад, -1 - утилизация
                'products_date_added' => $date_added,
                'products_date_updated' => $date_updated
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
