<?php
include_once '../core/begin.php';

if (!checkAccess('groups_of_users')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['groups'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    // Попытка удаления группы администратора
    if ($result = $db->query("SELECT COUNT(*) FROM `groups_of_users` WHERE `id_item` IN ($matches) AND `type` = 'administrator'")->fetch_row() and $result[0] > 0) {
        $error = 'Хорошая попытка! :)';
    }

    if (!isset($error) and $db->query("UPDATE `user` SET `group_id` = '0' WHERE `group_id` IN ($matches) AND `chief_id` = '" . $chief['id'] . "'") and $db->query("DELETE FROM `groups_of_users` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
        $db->query("DELETE FROM `group_rights` WHERE `group_id` IN ($matches) AND `client_id` = '" . $chief['id'] . "'"); // Удаляем права группы
        $db->query("UPDATE `staff` SET `group_id` = '0' WHERE `group_id` IN ($matches) AND `client_id` = '" . $chief['id'] . "'"); // Перемещаем сотрудников в группу "Без группы"
        $success = 1;
    } else {
        $error = isset($error) ? $error : 'Не удалось произвести операцию!';
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countGroups = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-groups', function(e) {
            let arrayGroups = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayGroups.push($(this).attr('data-id'));
            });
            $.ajax({
                type: "POST",
                url: "/ajax_deleteGroupsOfUsers?delete=true",
                data: { 'groups': arrayGroups },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadGroupsOfUsers();
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
        <div>Вы действительно хотите удалить <b><?=($countGroups == 1 ? 'группу пользователей' : plural_form($countGroups, array('группу пользователей', 'группы пользователей', 'групп пользователей')))?></b>?</div>
        <div class="buttons">
            <button id="delete-groups">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}