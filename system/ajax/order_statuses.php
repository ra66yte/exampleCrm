<?php
include_once '../core/begin.php';

$rows = array();
$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];

if (isset($_GET['module']) and $_GET['module'] == 'search') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $count = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
        if ($count[0] == 0) {
            $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => null, 'pagination' => $pagination));
            exit;
        } else {
            $search_keys = array();
            $search_values = array();
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
                $chunk = '';
                for ($i = 0; $i < count($search_values); $i++) {
                    if ($search_keys[$i] == 'country') {
                        $chunk .= "AND `" . protection($search_keys[$i], 'base') . "` = '" . protection($search_values[$i], 'base') . "'";
                    } else {
                        $chunk .= "AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                    }
                }
                
                $count = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' " . $chunk . "")->fetch_row();

                if ($count[0] == 0) {
                    $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                    echo json_encode(array('rows' => null, 'pagination' => $pagination));
                    exit;
                } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;
                    $items = $db->query("SELECT `id_item`, `color`, `name`, `status`, `warehouse`, `block`, `country`, `permanent`, `sort` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' " . $chunk . "  ORDER BY `sort` LIMIT $start, $items_on_page");
                    while ($status = $items->fetch_assoc()) {
                        if ($status['warehouse'] == 'in') {
                            $warehouse = '<i class="fa fa-plus"></i>';
                        } elseif ($status['warehouse'] == 'out') {
                            $warehouse = '<i class="fa fa-minus"></i>';
                        } else {
                            $warehouse = '';
                        }
                        $rows[] = array(
                                    'id_item' => $status['id_item'],
                                    'color' => $status['color'],
                                    'name' => $status['name'],
                                    'status' => $status['status'],
                                    'warehouse' => $warehouse,
                                    'block' => $status['block'],
                                    'country' => $status['country'],
                                    'permanent' => $status['permanent'],
                                    'sort' => $status['sort']
                                  );
                    }
                }

            } else {
                $countPages = k_page($count[0], $items_on_page);
                $currentPage = page($countPages);
                $start = ($currentPage * $items_on_page) - $items_on_page;

                $items = $db->query("SELECT `id_item`, `color`, `name`, `status`, `warehouse`, `block`, `country`, `permanent`, `sort` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' ORDER BY `sort` LIMIT $start, $items_on_page");
                while ($status = $items->fetch_assoc()) {
                    if ($status['warehouse'] == 'in') {
                        $warehouse = '<i class="fa fa-plus"></i>';
                    } elseif ($status['warehouse'] == 'out') {
                        $warehouse = '<i class="fa fa-minus"></i>';
                    } else {
                        $warehouse = '';
                    }
                    $rows[] = array(
                                'id_item' => $status['id_item'],
                                'color' => $status['color'],
                                'name' => $status['name'],
                                'status' => $status['status'],
                                'warehouse' => $warehouse,
                                'block' => $status['block'],
                                'country' => $status['country'],
                                'permanent' => $status['permanent'],
                                'sort' => $status['sort']
                              );
                }
            }

            $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
            exit;
        }
        
    } else {
        die('Something went wrong...');
    }
}

if (isset($_GET['show']) and $_GET['show'] == 'true') {

    $count = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] == 0) {
        $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => null, 'pagination' => $pagination));
        exit;
    } else {
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;

        $items = $db->query("SELECT `id_item`, `color`, `name`, `status`, `warehouse`, `block`, `country`, `permanent`, `sort` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' ORDER BY `sort` LIMIT $start, $items_on_page");
        while ($status = $items->fetch_assoc()) {
            if ($status['warehouse'] == 'in') {
                $warehouse = '<i class="fa fa-plus"></i>';
            } elseif ($status['warehouse'] == 'out') {
                $warehouse = '<i class="fa fa-minus"></i>';
            } else {
                $warehouse = '';
            }
            $rows[] = array(
                        'id_item' => $status['id_item'],
                        'color' => $status['color'],
                        'name' => $status['name'],
                        'status' => $status['status'],
                        'warehouse' => $warehouse,
                        'block' => $status['block'],
                        'country' => $status['country'],
                        'permanent' => $status['permanent'],
                        'sort' => $status['sort']
                      );
        }
        $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
        exit;
    }
}
