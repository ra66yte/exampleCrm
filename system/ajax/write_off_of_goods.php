<?php
include_once '../core/begin.php';

$rows = array();
$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];

if (isset($_GET['module']) and $_GET['module'] == 'search') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $count = $db->query("SELECT COUNT(*) FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
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
                    if ($key == 'id') {
                        $key = 'id_item';

                        $search_keys[] = $key;
                        $search_values[] = $value;
                    } elseif ($key == 'product_id') {
                        $search_products .= "SELECT DISTINCT `write_off_of_goods`.`id_item` FROM `write_off_of_goods` INNER JOIN `write_off_of_goods-products` ON (`write_off_of_goods`.`id_item` = `write_off_of_goods-products`.`woog_id`) WHERE `write_off_of_goods-products`.`product_id` = '" . abs(intval($value)) . "' AND  `write_off_of_goods-products`.`client_id` = '" . $chief['id'] . "' AND `write_off_of_goods`.`client_id` = '" . $chief['id'] . "'";

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

                $sql = "SELECT COUNT(*) FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "'" . ($chunk != '' ? " " . $chunk : '') . ($search_products != '' ? " AND `write_off_of_goods`.`id_item` IN (" . $search_products . ")" : '') . "";

                // $sql = "" . (($chunk != '') ? "SELECT `write_off_of_goods`.`id` FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "' " . $chunk . "": '') . " " . (($chunk == '') ? (($search_products != '') ? "" . $search_products . "" : "AND `write_off_of_goods`.`id` IN (" . $search_products . ")") : (($search_products != '') ? "AND `write_off_of_goods`.`id` IN (" . $search_products . ")" : "" . $search_products . "")) . "";

                $count = $db->query($sql)->fetch_row();
                if ($count[0] == 0) {
                    $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                    echo json_encode(array('rows' => null, 'pagination' => $pagination));
                    exit;
                } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;

                    $items = $db->query("SELECT `write_off_of_goods`.* FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "'" . ($chunk != '' ? " " . $chunk : '') . ($search_products != '' ? " AND `write_off_of_goods`.`id_item` IN (" . $search_products . ")" : '') . " ORDER by `write_off_of_goods`.`id_item` DESC LIMIT $start, $items_on_page");
                    while ($woog = $items->fetch_assoc()) {
                        $employee = $db->query("SELECT `name` FROM `user` WHERE `id_item` = '" . $woog['employee_id'] . "' AND (`chief_id` = 0 OR `chief_id` = '" . $chief['id'] . "')")->fetch_assoc();

                        $products = $db->query("SELECT `write_off_of_goods-products`.`id` AS `woog_product_id`, `write_off_of_goods-products`.`product_id`, `write_off_of_goods-products`.`count`, `products`.`name`, `products`.`model` FROM `write_off_of_goods-products` INNER JOIN `products` ON (`write_off_of_goods-products`.`product_id` = `products`.`id`) WHERE `woog_id` = '" . $woog['id_item'] . "'");

                        $productsItems = array();
                        while ($product = $products->fetch_assoc()) {
                            $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `write_off_of_goods-attributes` ON (`attributes`.`id_item` = `write_off_of_goods-attributes`.`attribute_id`) WHERE `write_off_of_goods-attributes`.`woog_product_id` = '" . $product['woog_product_id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `write_off_of_goods-attributes`.`client_id` = '" . $chief['id'] . "'");

                            $attribute_items = '';
                            while ($attribute = $attributes->fetch_assoc()) {
                                $attribute_items .= $attribute['name'] . ', ';
                            }

                            $name = $product['name'] . ' ' . $product['model'];
                            if (mb_strlen($name, 'UTF-8') > 40) $name = mb_substr($name, 0, 30, 'UTF-8') . '...';

                            $productsItems[] = array(
                                'id_item' => $product['product_id'],
                                'name' => protection($name, 'display'),
                                'count' => $product['count'],
                                'attributes' => protection(rtrim($attribute_items, ', '), 'display')
                            );
                        }

                        $rows[] = array(
                            'id_item' => $woog['id_item'],
                            'employee' => protection($employee['name'], 'display'),
                            'comment' => protection($woog['comment'], 'display'),
                            'date_added' => passed_time($woog['date_added']),
                            'products' => $productsItems
                        );
                    }
                }
            } else {
                $countPages = k_page($count[0], $items_on_page);
                $currentPage = page($countPages);
                $start = ($currentPage * $items_on_page) - $items_on_page;

                $items = $db->query("SELECT * FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "' ORDER by `id` DESC LIMIT $start, $items_on_page");
                while ($woog = $items->fetch_assoc()) {
                    $employee = $db->query("SELECT `name` FROM `user` WHERE `id_item` = '" . $woog['employee_id'] . "' AND (`chief_id` = 0 OR `chief_id` = '" . $chief['id'] . "')")->fetch_assoc();

                    $products = $db->query("SELECT `write_off_of_goods-products`.`id` AS `woog_product_id`, `write_off_of_goods-products`.`product_id`, `write_off_of_goods-products`.`count`, `products`.`name`, `products`.`model` FROM `write_off_of_goods-products` INNER JOIN `products` ON (`write_off_of_goods-products`.`product_id` = `products`.`id`) WHERE `woog_id` = '" . $woog['id_item'] . "'");

                    $productsItems = array();
                    while ($product = $products->fetch_assoc()) {
                        $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `write_off_of_goods-attributes` ON (`attributes`.`id_item` = `write_off_of_goods-attributes`.`attribute_id`) WHERE `write_off_of_goods-attributes`.`woog_product_id` = '" . $product['woog_product_id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `write_off_of_goods-attributes`.`client_id` = '" . $chief['id'] . "'");

                        $attribute_items = '';
                        while ($attribute = $attributes->fetch_assoc()) {
                            $attribute_items .= $attribute['name'] . ', ';
                        }

                        $name = $product['name'] . ' ' . $product['model'];
                        if (mb_strlen($name, 'UTF-8') > 40) $name = mb_substr($name, 0, 30, 'UTF-8') . '...';

                        $productsItems[] = array(
                            'id_item' => $product['product_id'],
                            'name' => protection($name, 'display'),
                            'count' => $product['count'],
                            'attributes' => protection(rtrim($attribute_items, ', '), 'display')
                        );
                    }

                    $rows[] = array(
                        'id_item' => $woog['id_item'],
                        'employee' => protection($employee['name'], 'display'),
                        'comment' => protection($woog['comment'], 'display'),
                        'date_added' => passed_time($woog['date_added']),
                        'products' => $productsItems
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
    $count = $db->query("SELECT COUNT(*) FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] > 0) {
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;

        $woogs = $db->query("SELECT * FROM `write_off_of_goods` WHERE `client_id` = '" . $chief['id'] . "' ORDER by `id` DESC LIMIT $start, $items_on_page");
        while ($woog = $woogs->fetch_assoc()) {
            $employee = $db->query("SELECT `name` FROM `user` WHERE `id_item` = '" . $woog['employee_id'] . "' AND (`chief_id` = 0 OR `chief_id` = '" . $chief['id'] . "')")->fetch_assoc();

                    $products = $db->query("SELECT `write_off_of_goods-products`.`id` AS `woog_product_id`, `write_off_of_goods-products`.`product_id`, `write_off_of_goods-products`.`count`, `products`.`name`, `products`.`model` FROM `write_off_of_goods-products` INNER JOIN `products` ON (`write_off_of_goods-products`.`product_id` = `products`.`id`) WHERE `woog_id` = '" . $woog['id_item'] . "'");

                    $productsItems = array();
                    while ($product = $products->fetch_assoc()) {
                        $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `write_off_of_goods-attributes` ON (`attributes`.`id_item` = `write_off_of_goods-attributes`.`attribute_id`) WHERE `write_off_of_goods-attributes`.`woog_product_id` = '" . $product['woog_product_id'] . "' AND `attributes`.`client_id` = '" . $chief['id'] . "' AND `write_off_of_goods-attributes`.`client_id` = '" . $chief['id'] . "'");

                        $attribute_items = '';
                        while ($attribute = $attributes->fetch_assoc()) {
                            $attribute_items .= $attribute['name'] . ', ';
                        }

                        $name = $product['name'] . ' ' . $product['model'];
                        if (mb_strlen($name, 'UTF-8') > 40) $name = mb_substr($name, 0, 30, 'UTF-8') . '...';

                        $productsItems[] = array(
                            'id_item' => $product['product_id'],
                            'name' => protection($name, 'display'),
                            'count' => $product['count'],
                            'attributes' => protection(rtrim($attribute_items, ', '), 'display')
                        );
                    }

                    $rows[] = array(
                        'id_item' => $woog['id_item'],
                        'employee' => protection($employee['name'], 'display'),
                        'comment' => protection($woog['comment'], 'display'),
                        'date_added' => passed_time($woog['date_added']),
                        'products' => $productsItems
                    );
            /*
            $employee = $db->query("SELECT `name` FROM `user` WHERE `id` = '" . $woog['employee_id'] . "'")->fetch_assoc();
?>
            <tr data-id="<?php echo $woog['id']; ?>" class="table__item">
                <td><?php echo $woog['id']; ?></td>
                <td><?php echo protection($employee['name'], 'display'); ?></td>
                <td style="line-height: 10px">
<?php
            $products = $db->query("SELECT `write_off_of_goods-products`.`id` AS `woog_product_id`, `write_off_of_goods-products`.`product_id`, `write_off_of_goods-products`.`count`, `products`.`name`, `products`.`model` FROM `write_off_of_goods-products` INNER JOIN `products` ON (`write_off_of_goods-products`.`product_id` = `products`.`id`) WHERE `woog_id` = '" . $woog['id'] . "'");
            while ($product = $products->fetch_assoc()) {
                $attribute_items = '';
                
                $attributes = $db->query("SELECT `attributes`.`name` FROM `attributes` INNER JOIN `write_off_of_goods-attributes` ON (`attributes`.`id` = `write_off_of_goods-attributes`.`attribute_id`) WHERE `write_off_of_goods-attributes`.`woog_product_id` = '" . $product['woog_product_id'] . "'");
                
                while ($attribute = $attributes->fetch_assoc()) {
                    $attribute_items .= $attribute['name'] . ', ';
                }
                $name = $product['name'] . ' ' . $product['model'];
                if (mb_strlen($name, 'UTF-8') > 40) $name = mb_substr($name, 0, 30, 'UTF-8') . '...';
?>
                <span><?php echo $product['product_id']; ?> - <?php echo protection($name, 'display'); ?> (<?php echo $product['count']; ?> шт.)<?php echo ($attribute_items != '' ? ' <small style="font-weight: bold; color: red; font-style: italic;">' . protection(rtrim($attribute_items, ', '), 'display') . '</small>' : ''); ?></span>
                <br>
<?
            }
?>
                </td>
                <td><?php echo protection($woog['comment'], 'display') ?></td>
                <td align="center"><i class="fa fa-calendar-check-o"></i> <?=view_time($woog['date_added'])?></td>
            </tr>
<?
            */
        }
        $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
        exit;
    } else {
        $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => null, 'pagination' => $pagination));
        exit;
    }
}
