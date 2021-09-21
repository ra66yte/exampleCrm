<?php
include_once '../core/begin.php';

if (!checkAccess('users')) redirect('?access_is_denied');

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {
    $success = $error = null;

    $ids = array();
    foreach ($_POST['users'] as $value) {
        $ids[] = protection($value, 'int');
        if ($result = $db->query("SELECT COUNT(*) FROM `user` WHERE `id_item` = '" . protection($value, 'int') . "' AND `chief_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $error = 'Вы не можете удалять несуществующих пользователей!';
        }

        if ($result[0] > 0) {
            $employee = $db->query("SELECT `id_item`, `avatar` FROM `user` WHERE `id_item` = '" . protection($value, 'int') . "' AND `chief_id` = '" . $chief['id'] . "'")->fetch_assoc();
            // Удаляем аватар
            if ($employee['avatar'] != 'no_photo.png') {
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/system/images/photo/' . $chief['id'] . '/' . $employee['avatar'])) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/system/images/photo/' . $chief['id'] . '/' . $employee['avatar']);
                } else {
                    $error = 'Произошла ошибка при удалении аватара пользователя!';
                }
            }
            // Изменяем значение поля сотрудника для заказов
            $db->query("UPDATE `orders` SET `employee_id` SET `employee_id` = '0' WHERE `chief_id` = '" . $chief['id'] . "' AND `employee_id` = '" . $employee['id_item'] . "'");
            // Удаляем офисы пользователя
            $db->query("DELETE FROM `staff_offices` WHERE `employee_id` = '" . $employee['id_item'] . "'");
            // Удаляем права пользователя
            $db->query("DELETE FROM `employee_right` WHERE `employee_id` = '" . $employee['id_item'] . "'");
            // Удаляем пользователя из таблицы сотрудников `staff`
            $db->query("DELETE FROM `staff` WHERE `employee_id` = '" . $employee['id_item'] . "'");
        }

        if (isset($error)) break;
    }
    
    if (!isset($error)) {
        $matches = implode(',', $ids);
        if ($db->query("DELETE FROM `user` WHERE `id_item` IN ($matches) AND `chief_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось удалить пользователя!';
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
if (isset($_GET['count'])) {
    $countUsers = abs(intval($_GET['count']));
?>
    <script>
        $('.modal-window-content').on('click', 'button#delete-users', function(e) {
            let arrayUsers = new Array();
            $.each($('tr.table__active'), function(e) {
                arrayUsers.push($(this).attr('data-id'));
            });
            $.ajax({
                type: "POST",
                url: "/system/ajax/deleteUsers.php?delete=true",
                data: { 'users': arrayUsers },
                success: function(response) {
                    let jsonData = JSON.parse(response),
                        count_modal = $('.modal-window-wrapper').length;
                    if (jsonData.success) {
                        loadUsers();
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
        <div>Вы подтверждаете удаление <b><?php echo ($countUsers == 1 ? 'пользователя' : plural_form($countUsers, array('пользователя', 'пользователей', 'пользователей'))) ?></b>?</div>
        <div class="buttons">
            <button id="delete-users">Да, подтверждаю</button> <button class="btn-cancel">Нет</button>
        </div>
<?
}