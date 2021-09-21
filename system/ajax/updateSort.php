<?php
include_once '../core/begin.php';
if (isset($_GET['location']) and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    $ids = (is_array($_POST['ids']) and $_POST['ids']) ? $_POST['ids'] : null;
    if (isset($ids)) {
        $items = array();
        foreach ($ids as $value) {
            $items[] = protection($value, 'int');
        }
        $matches = implode(',', $items);
        $data = array();
        switch ($_GET['location']) {
            case 'statuses':
                $sql = "SELECT COUNT(*) FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` IN ($matches)";
                $result = $db->query($sql)->fetch_row();
                if ($result[0] == count($items)) {
                    $statuses = $db->query("SELECT `id`, `id_item`, `sort` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` IN ($matches) ORDER BY `sort`");
                    $update = "INSERT INTO `status_order` (`id`, `id_item`, `client_id`, `sort`) VALUES ";
                    $i = 0;
                    while ($item = $statuses->fetch_assoc()) {
                        $update .= " ('" . $item['id'] . "', '" . $items[$i] . "', '" . $chief['id'] . "', '" . $item['sort'] . "'), ";
                        $data[] = $item['sort'];
                        $i++;
                    }
                    $update = rtrim($update, ', ') . " ON DUPLICATE KEY UPDATE `id` = VALUES (`id`), `id_item` = VALUES (`id_item`), `client_id` = VALUES (`client_id`), `sort` = VALUES (`sort`)";
                    if ($db->query($update)) {
                        $success = 1;
                    } else {
                        $error = 'Произошла ошибка при выполнении операции! [e:2]';
                    }
                } else {
                    $error = 'Произошла ошибка при выполнении операции!';
                }
                break;
        }

        echo json_encode(array('success' => $success, 'error' => $error, 'data' => $data));
        exit;
    } else {
        die('Something went wrong...');
    }
} else {
    die('Something went wrong...');
}