<?php
include_once '../core/begin.php';

$rows = array();
$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];

if (isset($_GET['module']) and $_GET['module'] == 'search') {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $count = $db->query("SELECT COUNT(*) FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
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
    
                $chunk = "`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    $chunk .= " AND `" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                $count = $db->query("SELECT COUNT(*) FROM `offices` WHERE `client_id` = '" . $chief['id'] . "' AND " . $chunk . "")->fetch_row();
                if ($count[0] == 0) {
                    $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                    echo json_encode(array('rows' => null, 'pagination' => $pagination));
                    exit;
                } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;
    
                    $items = $db->query("SELECT `id_item`, `name`, `address`, `email` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "' AND " . $chunk . "  ORDER by `id` ASC LIMIT $start, $items_on_page");
                    while ($office = $items->fetch_assoc()) {
                        $rows[] = array(
                            'id_item' => $office['id_item'],
                            'name' => protection($office['name'], 'display'),
                            'address' => protection($office['address'], 'display'),
                            'email' => protection($office['email'], 'display')
                        );
                    }
                }

            } else {

                $countPages = k_page($count[0], $items_on_page);
                $currentPage = page($countPages);
                $start = ($currentPage * $items_on_page) - $items_on_page;

                $items = $db->query("SELECT `id_item`, `name`, `address`, `email` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "' ORDER by `id` ASC LIMIT $start, $items_on_page");
                while ($office = $items->fetch_assoc()) {
                    $rows[] = array(
                        'id_item' => $office['id_item'],
                        'name' => protection($office['name'], 'display'),
                        'address' => protection($office['address'], 'display'),
                        'email' => protection($office['email'], 'display')
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

    $count = $db->query("SELECT COUNT(*) FROM `offices` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] == 0) {

        $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => null, 'pagination' => $pagination));
        exit;

    } else {
        
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;

        $items = $db->query("SELECT `id_item`, `name`, `address`, `email` FROM `offices` WHERE `client_id` = '" . $chief['id'] . "' ORDER by `id` ASC LIMIT $start, $items_on_page");
        while ($office = $items->fetch_assoc()) {
            $rows[] = array(
                'id_item' => $office['id_item'],
                'name' => protection($office['name'], 'display'),
                'address' => protection($office['address'], 'display'),
                'email' => protection($office['email'], 'display')
            );
        }
        $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
        exit;

    }

}
