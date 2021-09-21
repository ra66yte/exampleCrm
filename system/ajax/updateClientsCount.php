<?php
include_once '../core/begin.php';
// Погнали :-)
if (isset($_POST['clients'])) {
    $query_clients = $db->query("SELECT `id` FROM `groups_of_clients` WHERE `client_id` = '" . $chief['id'] . "'");
    $data_clients = array();
    $data_clients[0] = [$db->query("SELECT `id` FROM `clients` WHERE `client_id` = '" . $chief['id'] . "'")->num_rows];
    while ($clients = $query_clients->fetch_assoc()) {
        $count_clients = $db->query("SELECT `clients`.`id` FROM `clients` INNER JOIN `groups_of_clients` ON (`groups_of_clients`.`id` = `clients`.`group_id`) WHERE `clients`.`group_id` = '" . $clients['id'] . "' AND `clients`.`client_id` = '" . $chief['id'] . "'")->num_rows;
        $data_clients[$clients['id']] = $count_clients;
    }
    echo json_encode(array('count_clients' => $data_clients));
}
exit;
