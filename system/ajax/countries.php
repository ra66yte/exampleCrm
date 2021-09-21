<?php
include_once '../core/begin.php';

$rows = array();
$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];

if (isset($_GET['module']) and $_GET['module'] == 'search') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $count = $db->query("SELECT COUNT(*) FROM `countries`")->fetch_row();
        if ($count[0] == 0) {
            $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
            echo json_encode(array('rows' => null, 'pagination' => $pagination));
            exit;
        } else {
    
            $search_keys = array();
            $search_values = array();
            $search_status = '';
            $search_type = 0;
            foreach ($_POST as $key => $value) {
                if ($key != null && $value != null) {
                    if ($key == 'status') {
                        if ($value == 1) {
                            $search_status = "SELECT `countries`.`id`, `countries`.`code`, `countries`.`name`, (SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = `countries`.`id` AND `client_id` = '" . $chief['id'] . "') as `status` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "'";
                        } else {
                            $search_type = 1;
                            $search_status = "SELECT `id`, `code`, `name`, (SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = `countries`.`id` AND `client_id` = '" . $chief['id'] . "') as `status` FROM `countries` WHERE `id` NOT IN (SELECT `country_id` FROM `countries_list` WHERE `id` IS NOT NULL AND `client_id` = '" . $chief['id'] . "')";
                        }

                        $search_keys[] = '';
                        $search_values[] = '';
                    } else {
                        $search_keys[] = $key;
                        $search_values[] = $value;
                    }
                    
                } else {
                    unset($_POST[$key]);
                }
            }
    
            if (count($search_values) != 0) {

                $chunk = '';
    
                if ($search_keys[0] != '') $chunk .= "`countries`.`" . protection($search_keys[0], 'base') . "` LIKE '%" . protection($search_values[0], 'base') . "%'";
                for ($i = 1; $i < count($search_values); $i++) {
                    if ($search_keys[$i] != '') $chunk .= " AND `countries`.`" . protection($search_keys[$i], 'base') . "` LIKE '%" . protection($search_values[$i], 'base') . "%'";
                }

                
                $count = $db->query($search_status != '' ? ($search_type == 0 ? "SELECT COUNT(*) FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "'" . ($chunk != '' ? " AND " . $chunk . "" : '') : "SELECT COUNT(*) FROM `countries` WHERE `id` NOT IN (SELECT `country_id` FROM `countries_list` WHERE `id` IS NOT NULL AND `client_id` = '" . $chief['id'] . "')" . ($chunk != '' ? " AND " . $chunk . "" : '') . "") : "SELECT COUNT(*) FROM `countries`" . ($chunk != '' ? " WHERE " . $chunk . "" : '') . "")->fetch_row();

                if ($count[0] == 0) {
                    $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                    echo json_encode(array('rows' => null, 'pagination' => $pagination));
                    exit;
                } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;

                    $items = $db->query(($search_status != '' ? $search_status  . ($chunk != '' ? " AND " . $chunk . "" : '') : "SELECT `countries`.`id`, `countries`.`code`, `countries`.`name`, (SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = `countries`.`id` AND `client_id` = '" . $chief['id'] . "') as `status` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "' AND " . $chunk . " UNION ALL SELECT `id`, `code`, `name`, (SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = `countries`.`id` AND `client_id` = '" . $chief['id'] . "') as `status` FROM `countries` WHERE `id` NOT IN (SELECT `country_id` FROM `countries_list` WHERE `id` IS NOT NULL AND `client_id` = '" . $chief['id'] . "') AND " . $chunk . "") . " ORDER BY `status` DESC, `id` LIMIT $start, $items_on_page");
                    while ($country = $items->fetch_assoc()) {
                        $rows[] = array(
                            'id_item' => $country['id'],
                            'code' => $country['code'],
                            'name' => protection($country['name'], 'display'),
                            'status' => $country['status']
                        );
                    }
                }

            } else {
                    $countPages = k_page($count[0], $items_on_page);
                    $currentPage = page($countPages);
                    $start = ($currentPage * $items_on_page) - $items_on_page;
        
                    $items = $db->query("SELECT `countries`.`id`, `countries`.`code`, `countries`.`name`, (SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = `countries`.`id` AND `client_id` = '" . $chief['id'] . "') as `status` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "' UNION ALL SELECT `id`, `code`, `name`, (SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = `countries`.`id` AND `client_id` = '" . $chief['id'] . "') as `status` FROM `countries` WHERE `id` NOT IN (SELECT `country_id` FROM `countries_list` WHERE `id` IS NOT NULL AND `client_id` = '" . $chief['id'] . "') ORDER BY `status` DESC, `id` LIMIT $start, $items_on_page");
                    while ($country = $items->fetch_assoc()) {
                        $rows[] = array(
                            'id_item' => $country['id'],
                            'code' => $country['code'],
                            'name' => protection($country['name'], 'display'),
                            'status' => $country['status']
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
    $count = $db->query("SELECT COUNT(*) FROM `countries`")->fetch_row();
    if ($count[0] > 0) {
        $countPages = k_page($count[0], $items_on_page);
        $currentPage = page($countPages);
        $start = ($currentPage * $items_on_page) - $items_on_page;

        $items = $db->query("SELECT `countries`.`id`, `countries`.`code`, `countries`.`name`, (SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = `countries`.`id` AND `client_id` = '" . $chief['id'] . "') as `status` FROM `countries` INNER JOIN `countries_list` ON (`countries`.`id` = `countries_list`.`country_id`) WHERE `countries_list`.`client_id` = '" . $chief['id'] . "' UNION ALL SELECT `id`, `code`, `name`, (SELECT COUNT(*) FROM `countries_list` WHERE `country_id` = `countries`.`id` AND `client_id` = '" . $chief['id'] . "') as `status` FROM `countries` WHERE `id` NOT IN (SELECT `country_id` FROM `countries_list` WHERE `id` IS NOT NULL AND `client_id` = '" . $chief['id'] . "') ORDER BY `status` DESC, `id` LIMIT $start, $items_on_page");
        while ($country = $items->fetch_assoc()) {
            $rows[] = array(
                'id_item' => $country['id'],
                'code' => $country['code'],
                'name' => protection($country['name'], 'display'),
                'status' => $country['status']
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