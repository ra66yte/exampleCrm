<?php
include_once '../core/begin.php';

$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];
$rows = array();

if (isset($_GET['module']) and $_GET['module'] == 'search') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $count = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
        if ($count[0] == 0) {

            $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => null, 'pagination' => $pagination));
            exit;

        } else {
    
            $search_keys = array();
            $search_values = array();
            foreach ($_POST as $key => $value) {
                if ($key != null && $value != null) {
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

                $count = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "' AND " . $chunk)->fetch_row();

                if ($count[0] == 0) {

                    $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                    echo json_encode(array('rows' => null, 'pagination' => $pagination));
                    exit;
            
                } else {

                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;
    
                    $items = $db->query("SELECT `id_item`, `icon`, `name`, `status`, `permanent`, `sort` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "' AND " . $chunk . "  ORDER by `sort` ASC LIMIT $start, $items_on_page");

                    while ($delivery = $items->fetch_assoc()) {
                        $rows[] = [
                            'id_item' => $delivery['id_item'],
                            'icon' => protection($delivery['icon'], 'display'),
                            'name' => protection($delivery['name'], 'display'),
                            'status' => $delivery['status'],
                            'permanent' => $delivery['permanent'],
                            'sort' => $delivery['sort']
                        ];
                    }
                }
        
            } else {

                $countPages = k_page($count[0], $items_on_page);
                $currentPage = page($countPages);
                $start = ($currentPage * $items_on_page) - $items_on_page;
        
                $items = $db->query("SELECT `id_item`, `icon`, `name`, `status`, `permanent`, `sort` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "' ORDER by `sort` ASC LIMIT $start, $items_on_page");
        
                while ($delivery = $items->fetch_assoc()) {
                    $rows[] = [
                        'id_item' => $delivery['id_item'],
                        'icon' => protection($delivery['icon'], 'display'),
                        'name' => protection($delivery['name'], 'display'),
                        'status' => $delivery['status'],
                        'permanent' => $delivery['permanent'],
                        'sort' => $delivery['sort']
                    ];
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

    $count = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] > 0) {

        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;

        $deliveries = $db->query("SELECT `id_item`, `icon`, `name`, `status`, `permanent`, `sort` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "' ORDER by `sort` ASC LIMIT $start, $items_on_page");

        while ($delivery = $deliveries->fetch_assoc()) {
            $rows[] = [
                'id_item' => $delivery['id_item'],
                'icon' => protection($delivery['icon'], 'display'),
                'name' => protection($delivery['name'], 'display'),
                'status' => $delivery['status'],
                'permanent' => $delivery['permanent'],
                'sort' => $delivery['sort']
            ];
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
