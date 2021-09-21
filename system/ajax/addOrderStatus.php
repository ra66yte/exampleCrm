<?php
include_once '../core/begin.php';
if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $name = protection($_POST['name'], 'base');
    $color = protection($_POST['color'], 'base');
    $warehouse = protection($_POST['warehouse'], 'base');
    $_token = isset($_POST['_token']) ? protection($_POST['_token'], 'base') : null;

    if (empty($color)) {
        $error = 'Укажите цвет!';
    } elseif (!preg_match("/#[a-zA-Z0-9]{6}/i", $color)) {
        $error = 'Укажите корректный цвет!';
    } elseif ($db->query("SELECT `id` FROM `status_order` WHERE `color` = '" . $color . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows > 0) {
        $error = 'Статус заказов с таким цветом уже есть!';
    }

    if ($warehouse == '') $warehouse = 'none';

    if (empty($name)) {
        $error = 'Укажите название!';
    } elseif (mb_strlen($name, 'UTF-8') < 3 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название должно быть в пределах от 3 до 25 символов!';
    } elseif ($db->query("SELECT `id` FROM `status_order` WHERE `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->num_rows > 0) {
        $error = 'Статус заказов с таким названием уже есть!';
    }

    if (!isset($error)) {
        $sort = $db->query("SELECT COALESCE(MAX(`sort`), 0) AS `max` FROM `status_order` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $count = $db->query("SELECT `order_statuses` FROM `id_counters` WHERE `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
        $id_item = $count['order_statuses'] + 1;

        if ($result = $db->query("SELECT COUNT(*) FROM `status_order` WHERE `id_item` = '" . $id_item . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
            $db->query("INSERT INTO `status_order` (`id`, `id_item`, `client_id`, `name`, `color`, `warehouse`, `date_added`, `sort`) VALUES (null, '" . $id_item . "', '" . $chief['id'] . "', '" . $name . "', '" . $color . "', '" . $warehouse . "', '" . $data['time'] . "', '" . ($sort['max'] + 1) . "')");
            $last_id = $db->insert_id;

            // Добавляем доступ к новому статусу супер пользователю системы
            $right_id = getAccessID('statuses');
            $main_group = $db->query("SELECT `groups_of_users`.`id` FROM `groups_of_users` INNER JOIN `user` WHERE `groups_of_users`.`id` = `user`.`group_id` AND `user`.`id` = '" . $chief['id'] . "' AND `groups_of_users`.`type` = 'administrator'")->fetch_assoc();
            $db->query("INSERT INTO `group_rights` (`id`, `client_id`, `group_id`, `access_right`, `value`) VALUES (null, '" . $chief['id'] . "', '" . $main_group['id'] . "', '" . $right_id . "', '" . $last_id . "')");

            $db->query("UPDATE `id_counters` SET `order_statuses` = (`order_statuses` + 1) WHERE `client_id` = '" . $chief['id'] . "'");
            $success = 1;
        } else {
            $error = "Не удалось добавить статус заказов!" . $id_item;
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}
?>

<script>
    $(function(){
        let form = $('#add-order-status'),
            btn = form.find('#button-add-order-status');
        form.find('#status-name').focus();

        form.find('#status-color').spectrum({
            color: '#ffffff',
            preferredFormat: 'hex',
            type: 'text',
            showInput: true,
            showAlpha: false,
            locale: 'ru',
            showPaletteOnly: true,
            togglePaletteOnly: true
        });

        function checkFields() {
            let error;

            let color =  form.find('#status-color').val();
            if (color == '') {
                error = 'Укажите цвет!';
            } else if (!color.match(/#[a-f0-9]{6}\b/gi)) {
                error = 'Введите корректный цвет!';
            }

            let name = form.find('#status-name').val();
            if (name == '') {
                error = 'Введите название статуса!';
            } else if (name.length < 3) {
                error = 'Название не может содержать меньше 3 символов!';
            } else if (name.length > 25) {
                error = 'Название должно быть в пределах 25 символов!';
            }


            if (error) {
                btn.addClass('disabled');
                return error;
            } else {
                btn.removeClass('disabled');
                return false;
            }
        }

        form.on('keyup change', function() {
            checkFields();
        });

        form.on('submit', function(e){
            let error = checkFields();
            if (error) {
                if (!$('.modal-window-content').last().is('.error')) {
                    $('.modal-window-content').last().prepend('<div class="error"></div>');
                }
                $('.error').last().text(error).show();
            } else {
                let data = $(this).serializeArray(),
                    count_modal = $('.modal-window-wrapper').length;
                $.ajax({
                    type: "POST",
                    url: "system/ajax/addOrderStatus.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadStatuses();
                            closeModalWindow(count_modal);
                        } else {
                            if (!$('.modal-window-content').last().is('.error')) {
                                $('.modal-window-content').last().prepend('<div class="error"></div>');
                            }
                            $('.error').last().html(jsonData.error).show();
                        }
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="add-order-status" method="post" autocomplete="off">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"> </i> <input id="status-name" type="text" name="name" placeholder="Введите название">
                </div>
                <div class="modal-window-content__value">
                    <span>Цвет</span> <i id="circle-color" class="fa fa-eyedropper"></i> <input id="status-color" type="text" name="color" placeholder="Например, #ffffff">
                </div>
                <div class="modal-window-content__value">
                    <span>Склад</span> <i class="fa fa-database"> </i> <select name="warehouse" id="status-warehouse" class="chosen-select">
                        <option value="">- Не указано -</option>
                        <option value="in">На склад</option>
                        <option value="out">Со склада</option>
                    </select>
                </div>
                <div class="modal-window-content__title" style="margin-top: 20px">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="1">Off</option>
                        <option value="2" selected>On</option>
                    </select>
                </div>
                <div class="modal-window-content__value center" style="max-width: 310px">
                    <br>
                    <small class="red">После добавления нового статуса заказов разрешите доступ к нему необходимым группам пользователей.</small>
                </div>
                <div class="buttons">
                    <button id="button-add-order-status" class="disabled" name="save-changes">Добавить</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>