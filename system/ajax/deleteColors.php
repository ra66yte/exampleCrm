<?php
include_once '../core/begin.php';

if (!checkAccess('colors')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach($_POST['colors'] as $value) {
        $ids[] = protection($value, 'int');
    }
    $matches = implode(',', $ids);
    if ($result = $db->query("SELECT COUNT(*) FROM `colors` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == count($ids)) {
        if ($db->query("DELETE FROM `colors` WHERE `id_item` IN ($matches) AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось удалить цвет!';
        }
    } else {
        $error = 'Произошла ошибка при выполнении операции!';
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countColors = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-colors', function(e) {
            let arrayColors = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayColors.push($(this).attr('data-id'));
            });

            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteColors.php?delete=true",
                data: { 'colors': arrayColors },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadColors();
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
        <div>Вы действительно хотите удалить <b><?=($countColors == 1 ? 'цвет' : plural_form($countColors, array('цвет', 'цвета', 'цветов')))?></b>?</div>
        <div class="buttons">
            <button id="delete-colors">Да, именно</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}