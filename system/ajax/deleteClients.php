<?php
include_once '../core/begin.php';

if (!checkAccess('clients')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach ($_POST['clients'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($db->query("SELECT `id` FROM `clients` WHERE `client_id` = '" . $chief['id'] . "' AND `id` IN ($matches)")->num_rows != count($ids)) {
        $error = 'Вы не можете удалять не своих клиентов!';
    }

    if (!isset($error)) {
        if ($db->query("DELETE FROM `clients` WHERE `client_id` = '" . $chief['id'] . "' AND `id` IN ($matches)")) {
            $success = 1;
        } else {
            $error = 'Не удалось удалить пользователя(ей)!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countClients = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-clients', function(e) {
            let arrayClients = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayClients.push($(this).attr('data-id'));
            });

            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteClients.php?delete=true",
                data: { 'clients': arrayClients },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        let type = getParameterByName('type');
                        TabClients(type);
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
        <div>Вы подтверждаете удаление <b><?php echo ($countClients == 1 ? 'клиента' : plural_form($countClients, array('клиента', 'клиентов', 'клиентов'))) ?></b>?</div>
        <div class="buttons">
            <button id="delete-clients">Да, подтверждаю</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}