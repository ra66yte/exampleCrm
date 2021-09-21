<?php
include_once '../core/begin.php';

if (!checkAccess('order_statuses')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;
    $ids = array();
    foreach ($_POST['statuses'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `id_item` IN ($matches) AND `permanent` = '0' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] < count($ids)) {
        $error = 'Произошла ошибка при удалении!';
    }

    if (!isset($error)) {
        $prev = $db->query("SELECT `id_item`, `sort` FROM `status_order` WHERE `id_item` < '" . min($ids) . "' AND `client_id` = '" . $chief['id'] . "' ORDER BY `id_item` DESC")->fetch_assoc(); // sort
        if (!$prev) $prev['sort'] = 0;
        if ($db->query("DELETE FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` IN ($matches)")) {
            $success = 1;
            $count_next = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `id_item` > '" . min($ids) . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row();
            if ($count_next[0] > 0) {
                $next = $db->query("SELECT `id`, `id_item` FROM `status_order` WHERE `id_item` > '" . min($ids) . "' AND `client_id` = '" . $chief['id'] . "' ORDER BY `sort`"); // sort
                $update_sort = "INSERT INTO `status_order` (`id`, `id_item`, `client_id`, `sort`) VALUES "; // sort
                $i = 1;
                while ($status = $next->fetch_assoc()) {
                    $update_sort .= " ('" . $status['id'] . "', '" . $status['id_item'] . "', '" . $chief['id'] . "', '" . ($prev['sort'] + $i) . "'), ";
                    $i++;
                }
                $db->query(rtrim($update_sort, ', ') . " ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `id_item` = VALUES (`id_item`), `client_id` = VALUES (`client_id`), `sort` = VALUES (`sort`)");
            }
            // Убираем из прав доступа
            $right = getAccessId('statuses');
            $db->query("DELETE FROM `group_rights` WHERE `client_id` = '" . $chief['id'] . "' AND `access_right` = '" . $right . "' AND `value` IN ($matches)");
            // Добавляем заказы с этим статусом в корзину
            $db->query("UPDATE `orders` SET `status` = '0', `updated` = '" . $data['time'] . "', `deleted` = '1' WHERE `status` IN ($matches) AND `client_id` = '" . $chief['id'] . "'");
        } else {
            $error = 'Не удалось удалить статус' . (count($ids) > 1 ? 'ы' : '') . '!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countStatuses = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-statuses', function(e) {
            let arrayStatuses = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayStatuses.push($(this).attr('data-id'));
            });

            $.ajax({
                type: "POST",
                url: "/ajax_deleteStatus?delete=true",
                data: { 'statuses': arrayStatuses },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success == 1) {
                        loadStatuses();
                        hideOptions(true);
                        $('.status-panel__count').hide();
                    } else {
                        showModalWindow(null, null, 'error', jsonData.error);
                    }
                    closeModalWindow(count_modal);
                }
            });
        });
    </script>
        <div>Вы действительно хотите удалить <b><?=($countStatuses == 1 ? 'статус' : plural_form($countStatuses, array('статус', 'статуса', 'статусов')))?></b>?</div>
        <div class="buttons">
            <button id="delete-statuses">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}
