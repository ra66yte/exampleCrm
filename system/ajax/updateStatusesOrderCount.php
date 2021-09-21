<?php
include_once '../core/begin.php';
// Погнали :-)
if (isset($_POST['status'])) {
    $right_id = getAccessID('statuses');
    $query_statuses = $db->query("SELECT `status_order`.`id_item` FROM `status_order` INNER JOIN `group_rights` ON (`status_order`.`id_item` = `group_rights`.`value`) WHERE `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `status_order`.`status` = 'on' AND `group_rights`.`client_id` = '" . $chief['id'] . "'");

    $data_status = array();

    $all_orders = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id_item`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE `status_order`.`status` = 'on' AND `orders`.`deleted_at` = '0' AND `orders`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id_item` AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`client_id` = '" . $chief['id'] . "'")->fetch_row();
    $data_status[0] = [$all_orders[0]];
    while ($status = $query_statuses->fetch_assoc()) {
        $count_orders = $db->query("SELECT COUNT(*) FROM `orders` INNER JOIN `status_order` ON (`orders`.`status` = `status_order`.`id_item`) INNER JOIN `group_rights` ON (`group_rights`.`access_right` = '" . $right_id . "') WHERE `status_order`.`status` = 'on' AND `orders`.`deleted_at` = '0' AND `orders`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`group_id` = '" . $user['group_id'] . "' AND `group_rights`.`value` = `status_order`.`id` AND `orders`.`status` = '" . $status['id_item'] . "' AND `status_order`.`client_id` = '" . $chief['id'] . "' AND `group_rights`.`client_id` = '" . $chief['id'] . "'")->fetch_row();
        $data_status[$status['id_item']] = $count_orders[0];
    }
    echo json_encode(array('count_orders' => $data_status));
}
exit;
