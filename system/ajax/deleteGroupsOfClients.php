<?php
include_once '../core/begin.php';

if (!checkAccess('groups_of_clients')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['groups'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);

    // Попытка удаления перманентной группы
    if ($db->query("SELECT `id` FROM `groups_of_clients` WHERE `id` IN ($matches) AND `permanent` = 'on' AND `client_id` = '" . $chief['id'] . "'")->num_rows != 0) {
        $error = 'Хорошая попытка! :)';
    }

    if (!isset($error) and $db->query("UPDATE `clients` SET `group_id` = '0' WHERE `group_id` IN ($matches)") and $db->query("DELETE FROM `groups_of_clients` WHERE `client_id` = '" . $chief['id'] . "' AND `id` IN ($matches)")) {
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
                url: "/ajax_deleteGroupsOfClients?delete=true",
                data: { 'groups': arrayGroups },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadGroupsOfClients();
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
        <div>Вы действительно хотите удалить <b><?php echo ($countGroups == 1 ? 'группу клиентов' : plural_form($countGroups, array('группу клиентов', 'группы клиентов', 'групп клиентов'))) ?></b>?</div>
        <div class="buttons">
            <button id="delete-groups">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}