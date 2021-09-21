<?php
include_once '../core/begin.php';

if (isset($_GET['action']) and $_GET['action'] == 'submit' and $_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = $error = null;
    
    $id = isset($_POST['payment_id']) ? abs(intval($_POST['payment_id'])) : null;
    $name = isset($_POST['name']) ? protection($_POST['name'], 'base') : null;

    if (empty($name)) {
        $error = 'Укажите название способа оплаты!';
    } elseif (mb_strlen($name, 'UTF-8') < 3 or mb_strlen($name, 'UTF-8') > 25) {
        $error = 'Название способа оплаты должно быть в пределах от 3 до 25 символов!';
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] == 0) {
        $error = 'Способ оплаты не найден!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `id_item` = '" . $id . "' AND `permanent` = 'on' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Этот способ оплаты редактировать нельзя!';
    } elseif ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `id_item` != '" . $id . "' AND `name` = '" . $name . "' AND `client_id` = '" . $chief['id'] . "'")->fetch_row() and $result[0] > 0) {
        $error = 'Способ оплаты с таким названием уже есть!';
    }

    if (!isset($error)) {
        if ($db->query("UPDATE `payment_methods` SET `name` = '" . $name . "' WHERE `id_item` = '" . $id . "' AND `client_id` = '" . $chief['id'] . "'")) {
            $success = 1;
        } else {
            $error = 'Не удалось обновить способ оплаты!';
        }
    }
    echo json_encode(array('success' => $success, 'error' => $error));
    exit;
}

if (isset($_GET['payment_id']) and is_numeric($_GET['payment_id'])) {
    $payment_id = abs(intval($_GET['payment_id']));

    if (isset($_GET['query']) and $_GET['query'] == 'get_title' and $_SERVER['REQUEST_METHOD'] == 'POST') {
        $success = $error = $title = null;
    
        if ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $payment_id . "'")->fetch_row() and $result[0] > 0) {
            $success = 1;
            $payment = $db->query("SELECT `name` FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $payment_id . "'")->fetch_assoc();
            $title = ['payment_name' => protection($payment['name'], 'display')];
        } else {
            $error = 'Неизвестный способ оплаты!';
            $title = ['payment_name' => 'UNDEFINED'];
        }
        
        echo json_encode(array('success' => $success, 'error' => $error, 'title' => $title));
        exit;
    }

    if ($result = $db->query("SELECT COUNT(*) FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $payment_id . "'")->fetch_row() and $result[0] > 0) {
        $payment = $db->query("SELECT `id_item`, `name` FROM `payment_methods` WHERE `client_id` = '" . $chief['id'] . "' AND `id_item` = '" . $payment_id . "'")->fetch_assoc();
?>

<script>
    $(function() {
        let form = $('#change-payment-method'),
            btn = form.find('#button-change-payment-method');
        /*
        $('.chosen-select').chosen('destroy');
        $('.chosen-select').chosen({
            'disable_search': true
        });
        */
        function checkFields() {
            let error,
                name = form.find('#payment-name').val()
                
            if (name == '') {
                error = 'Введите название способа оплаты!';
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
                    $('.error').text(error).show();
                }
            } else {
                let data = $(this).serializeArray(),
                    count_modal = $('.modal-window-wrapper').length;
                $.ajax({
                    type: "POST",
                    url: "system/ajax/viewPaymentMethod.php?action=submit",
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
        <form id="change-payment-method" method="post">
            <input type="hidden" name="payment_id" value="<?=protection($payment['id_item'], 'int')?>">
            <div class="modal-window-content__item">
                <div class="modal-window-content__title">Конфигурация</div>
                <div class="modal-window-content__value">
                    <span>Название</span> <i class="fa fa-tag"> </i> <input id="payment-name" type="text" name="name" placeholder="Введите название" autocomplete="off" value="<?=protection($payment['name'], 'display')?>">
                </div>
                <div class="modal-window-content__title">Дополнительно</div>
                <div class="modal-window-content__value">
                    <span>Статус</span> <i class="fa fa-eye-slash"></i>
                    <select name="status" class="chosen-select" disabled>
                        <option value="">Не выбрано</option>
                        <option value="on"<?=($payment['status'] == 'on' ? ' selected' : '')?>>On</option>
                        <option value="off"<?=($payment['status'] == 'off' ? ' selected' : '')?>>Off</option>
                    </select>
                </div>
                <div class="buttons">
                    <button id="button-change-payment-method" name="save-changes">Сохранить и закрыть</button>
                </div>
            </div>
            <input type="submit" style="display: none">
        </form>
<?
    } else {
?>
    Информация по заданному способу оплаты отсутствует.
<?
    }
}