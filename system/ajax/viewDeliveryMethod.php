<?php
include_once '../core/begin.php';

if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['delivery_id']) ? abs(intval($_POST['delivery_id'])) : null;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;

    if (empty($name)) {
        $error = 'Укажите название способа доставки!';
    } elseif (mb_strlen($name, 'UTF-8') < 3 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название способа доставки должно быть в пределах от 3 до 25 символов!';
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Способ доставки не найден!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `id_item` = '" . $id . "' AND `permanent` = 'on' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Этот способ доставки редактировать нельзя!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `id_item` != '" . $id . "' AND `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Способ доставки с таким названием уже есть!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `delivery_methods` SET `name` = '" . $name . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = "Не удалось обновить способ доставки!";
        }
    }

    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['delivery_id']) and is_numeric($_GET['delivery_id'])) {
    $delivery_id = abs(intval($_GET['delivery_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `id_item` = '" . $delivery_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $delivery = $db->query("SELECT `name` FROM `delivery_methods` WHERE `id_item` = '" . $delivery_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_assoc();
            $title = ['delivery_name' => protection($delivery['name'], 'display')];
        } else {
            $error = 'Неизвестный способ доставки!';
            $title = ['delivery_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `delivery_methods` WHERE `id_item` = '" . $delivery_id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $delivery = $db->query("SELECT `id_item`, `icon`, `name`, `status`, `permanent`, `sort` FROM `delivery_methods` WHERE `client_id` = '" . $chief['id'] . "' AND `id` = '" . $delivery_id . "'")->fetch_assoc();
?>
<script>
    $(function() {
        let form = $('#change-delivery-method'),
            btn = form.find('#button-change-delivery-method');

        $('.chosen-select').chosen('destroy');
        $('.chosen-select').chosen({
            'disable_search': true
        });

        function checkFields() {
            let error,
                name = form.find('#delivery-name').val();
                
            if (name == '') {
                error = 'Введите название способа доставки!';
            } else if (name.length < 3) {
                error = 'Название не может содержать меньше 3 символов!';
            } else if (name.length > 25) {
                error = 'Название должно быть в пределах 25 символов!';
            }


            if (error) return error;
            else return false;
            }

            form.on('keyup change', function() {
                checkFields();
            });

        form.on('submit', function(e){
            let error = checkFields();
            if (error) {
                if (!$('.modal-window-content div').is('.error')) {
                    $('.modal-window-content').prepend('<div class="error"></div>');
                }
                $('.error').text(error).show();
            } else {
                let data = $(this).serializeArray(),
                    count_modal = $('.modal-window-wrapper').length;
                $.ajax({
                    type: "POST",
                    url: "system/ajax/viewDeliveryMethod.php?action=submit",
                    data: data,
                    success: function(response) {
                        let jsonData = JSON.parse(response);
                        if (jsonData.success == 1) {
                            loadPaymentMethods();
                            hideOptions(true);
                            closeModalWindow(count_modal);
                        } else {
                            showModalWindow(null, null, 'error', jsonData.error);
                        }
                    }
                });
            }
            return false;
        });
    });
</script>
        <form id="change-delivery-method" method="post">
            <input type="hidden" name="delivery_id" value="<?=protection($delivery['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"></i> <input id="delivery-name" type="text" name="name" placeholder="Введите название" autocomplete="off" value="<?=protection($delivery['name'], 'display')?>">
                </div>
                <div class="modal-window-content__title">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="on" <?=($delivery['status'] == 'on' ? 'selected' : '')?>>On</option>
                        <option value="off" <?=($delivery['status'] == 'off' ? 'selected' : '')?>>Off</option>
                    </select>
                </div>
                <div class="buttons">
                    <button id="button-change-delivery-method" name="save-changes">Сохранить и закрыть</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>
<?
    } else {
?>
    Информация по заданному способу доставки отсутсвует.
<?
    }
}