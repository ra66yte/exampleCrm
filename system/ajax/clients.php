<?php
include_once '../core/begin.php';

$rows = array();
$items_on_page = isset($user['max_rows']) ? $user['max_rows'] : $data['orders_on_page'];
// Поехали :D
if (isset($_GET['type'])) {
    $type = abs(intval($_GET['type']));
    $_SESSION['clients_type'] = $type;
    if ($type == 0) {
        $type = "all";
    }
    // Загружаем
    $count = $db->query("SELECT COUNT(*) FROM `clients` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_row();
    if ($count[0] == 0) {
        $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => null, 'pagination' => $pagination));
        exit;
    } else {
        if ($type == "all") {
            $countPages = k_page($count[0], $items_on_page);
            $currentPage = page($countPages);
            $start = ($currentPage * $items_on_page) - $items_on_page;

            $clients = $db->query("SELECT `id_item`, `name`, `phone`, `email`, `comment`, `site`, `ip`, `group_id`, `country`, `date_added` FROM `clients` WHERE `client_id` = '" . $chief['id'] . "' ORDER by `id` ASC LIMIT $start, $items_on_page");
            while ($client = $clients->fetch_assoc()) {
                $client_group = $db->query("SELECT `name` FROM `groups_of_clients` WHERE `id_item` = '" . $client['group_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                if (!isset($client_group)) $client_group['name'] = 'Без группы';
                $client_country = $db->query("SELECT `name`, `code` FROM `countries` WHERE `id` = '" . $client['country'] . "'")->fetch_assoc();
                if (!isset($client_country)) {
                    $client_country = array('code' => '', 'name' => '');
                }
                $client_site = $db->query("SELECT `url` FROM `sites` WHERE `id_item` = '" . $client['site'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                if (!isset($client_site)) $client_site['url'] = '';

                $rows[] = array(
                    'id_item' => $client['id_item'],
                    'name' => protection($client['name'], 'display'),
                    'phone' => protection($client['phone'], 'display'),
                    'email' => protection($client['email'], 'display'),
                    'comment' => protection($client['comment'], 'display'),
                    'site' => protection($client_site['url'], 'display'),
                    'ip' => long2ip($client['ip']),
                    'group' => protection($client_group['name'], 'display'),
                    'country_code' => strtolower($client_country['code']),
                    'country_name' => protection($client_country['name'], 'display'),
                    'date_added' => passed_time($client['date_added'])
                );
            }
        } else {
            if ($result = $db->query("SELECT COUNT(*) FROM `clients` WHERE `group_id` = '" . $type . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) { // если такой категории клиентов нет или в ней нет клиентов
                $pagination = array('countPages' => 1, 'currentPage' => 1, 'totalRows' => 0, 'maxRows' => $items_on_page);
                echo json_encode(array('rows' => 'empty', 'pagination' => $pagination));
                exit;
            }

            $count = $db->query("SELECT COUNT(*) FROM `clients` WHERE `group_id` = '" . $type . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            $countPages = k_page($count[0], $items_on_page);
            $currentPage = page($countPages);
            $start = ($currentPage * $items_on_page) - $items_on_page;

            $clients = $db->query("SELECT `id_item`, `name`, `phone`, `email`, `comment`, `site`, `ip`, `group_id`, `country`, `date_added` FROM `clients` WHERE `group_id` = '" . $type . "' AND `client_id` = '" . $chief['id'] . "' ORDER by `id` ASC LIMIT $start, $items_on_page");
            while ($client = $clients->fetch_assoc()) {
                $client_group = $db->query("SELECT `name` FROM `groups_of_clients` WHERE `id_item` = '" . $client['group_id'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                if (!isset($client_group)) $client_group['name'] = 'Без группы';
                $client_country = $db->query("SELECT `name`, `code` FROM `countries` WHERE `id` = '" . $client['country'] . "'")->fetch_assoc();
                if (!isset($client_country)) {
                    $client_country = array('code' => '', 'name' => '');
                }
                $client_site = $db->query("SELECT `url` FROM `sites` WHERE `id` = '" . $client['site'] . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
                if (!isset($client_site)) $client_site['url'] = '';

                $rows[] = array(
                    'id_item' => $client['id_item'],
                    'name' => protection($client['name'], 'display'),
                    'phone' => protection($client['phone'], 'display'),
                    'email' => protection($client['email'], 'display'),
                    'comment' => protection($client['comment'], 'display'),
                    'site' => protection($client_site['url'], 'display'),
                    'ip' => long2ip($client['ip']),
                    'group' => protection($client_group['name'], 'display'),
                    'country_code' => strtolower($client_country['code']),
                    'country_name' => protection($client_country['name'], 'display'),
                    'date_added' => passed_time($client['date_added'])
                );
            }
        }

        $pagination = array('countPages' => $countPages, 'currentPage' => $currentPage, 'totalRows' => $count[0], 'maxRows' => $items_on_page);
        echo json_encode(array('rows' => $rows, 'pagination' => $pagination));
        exit;
    }
}
